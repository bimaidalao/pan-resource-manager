<?php

namespace app\index\controller;

use think\App;
use think\facade\View;
use think\facade\Request;
use think\facade\Cache;
use app\index\QfShop;
use app\model\Source as SourceModel;
use app\model\SourceCategory as SourceCategoryModel;
use app\model\ApiList as ApiListModel;

use Lizhichao\Word\VicWord;


class Index extends QfShop
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->SourceModel = new SourceModel();
        $this->SourceCategoryModel = new SourceCategoryModel();
        $this->ApiListModel = new ApiListModel();
    }

    /**
     * @description: 首页
     * @param {*}
     * @return {*}
     */
    public function index()
    {
        $rankList = $this->SourceCategoryModel->field('source_category_id,name,image,is_sys,is_type')->where([['status', '=', 0]])->order('sort desc')->select();
        $newList = [];

        // 与搜索一致：不限制 is_time（线上入库资源多为 is_time=1）
        $map[] = ['status', '=', 1];
        $map[] = ['is_delete', '=', 0];
        if (config("qfshop.home_new") == 0) {
            //最新榜
            $newList = $this->SourceModel->order(['create_time' => 'desc'])
                ->field('title,create_time as time,source_id as id')
                ->where($map)
                ->limit(Config('qfshop.ranking_num') ?? 1)
                ->select()->each(function ($item, $key) {
                    $item['times'] = substr($item['time'], 5, 5);
                    unset($item['time']);
                    return $item;
                });
        }

        //热门排行榜数据
        $hotList = [];
        $cacheDir = root_path('runtime/api/cache'); // runtime/cache 目录
        foreach ($rankList as $value) {
            if ($value['is_sys'] == 1 && $value['is_type'] == 0) {
                $cacheFile = $cacheDir . "ranking_data_{$value['name']}.cache";
                if (file_exists($cacheFile)) {
                    $hotList[] = array(
                        'name' => $value['name'],
                        'image' => $value['image'],
                        'list' => json_decode(file_get_contents($cacheFile), true),
                    );
                }
            } else {
                $list = $this->SourceModel->order(['create_time' => 'desc'])
                    ->field('title,create_time as time,source_id as id')
                    ->where($map)
                    ->where(['source_category_id' => $value['source_category_id']])
                    ->limit(Config('qfshop.ranking_num') ?? 1)
                    ->select()->each(function ($item, $key) {
                        $item['times'] = substr($item['time'], 5, 5);
                        unset($item['time']);
                        return $item;
                    })->toArray();
                $hotList[] = array(
                    'name' => $value['name'],
                    'image' => $value['image'],
                    'list' => $list,
                );
            }
        }

        $config = config("qfshop");
        if (!is_array($config)) {
            $config = [];
        }
        $config += [
            'ban_keywords' => '',
            'is_quan' => 0,
            'app_name' => 'Pan Resource Manager',
            'app_title' => '',
            'app_keywords' => '',
            'app_description' => '',
        ];

        // 首页自动识别专区：始终返回 6 类（影视/小说/文档/软件/压缩/图片）
        $kindLimit = (int) (config('qfshop.ranking_num') ?? 10);
        if ($kindLimit < 8) {
            $kindLimit = 8;
        }
        $kindModules = function_exists('buildHomeKindModules')
            ? buildHomeKindModules($this->SourceModel, $kindLimit, 500)
            : [];

        // 首页右侧主视觉未上传自定义图片时，自动使用已经预热成功的站内
        // 海报。只读取现成 vod_pic，不在首页请求外部资料源。
        $heroShowcase = [];
        try {
            $heroCandidates = $this->SourceModel
                ->where($map)
                ->where('vod_pic', '<>', '')
                ->field('source_id as id,title,vod_pic as poster')
                ->order(['source_id' => 'desc'])
                ->limit(12)
                ->select()
                ->toArray();
            foreach ($heroCandidates as $row) {
                $poster = trim((string) ($row['poster'] ?? ''));
                if ($poster === '' || strpos($poster, 'data:') === 0) {
                    continue;
                }
                // 首页绝不再直接热链豆瓣/BGM；只选择已由详情预热完成、
                // 浏览器可直接访问的本地海报。旧 runtime 缓存会在这里迁移。
                $localPoster = function_exists('cachePublicPosterLocally')
                    ? cachePublicPosterLocally($poster, false)
                    : '';
                if ($localPoster === '') {
                    continue;
                }
                $row['poster'] = $localPoster;
                $heroShowcase[] = $row;
                if (count($heroShowcase) >= 3) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            $heroShowcase = [];
        }

        View::assign('newList', $newList);
        View::assign('hotList', $hotList);
        View::assign('kindModules', $kindModules);
        View::assign('heroShowcase', $heroShowcase);
        View::assign('config', $config);
        View::assign('rankList', $rankList);
        View::assign('fixed', 1);
        View::assign('category_id', 0);
        View::assign('seo_title', $config['app_name'] . ' - ' . $config['app_title']);
        View::assign('seo_keywords', $config['app_keywords']);
        View::assign('seo_description', $config['app_description']);
        return View::fetch('/news/index');
    }


    /**
     * @description: 搜索列表
     * @param {*}
     * @return {*}
     */
    public function list($name, $page = 1, $cate = '')
    {
        // ThinkPHP leaves percent-encoded UTF-8 path parameters untouched in
        // this route (for example, Chinese search terms). Decode once before
        // displaying the keyword or querying the database/API lines.
        $name = rawurldecode((string) $name);

        $config = config("qfshop");
        if (!is_array($config)) {
            $config = [];
        }
        $config += [
            'ban_keywords' => '',
            'is_quan' => 0,
            'app_name' => 'Pan Resource Manager',
            'app_keywords' => '',
            'app_description' => '',
        ];

        // 被屏蔽的关键词，用逗号分隔
        $banKeywords = explode(',', $config['ban_keywords']);

        // 默认$list为空
        $list = [
            'total_result' => 0,
            'items' => []
        ];

        // 检查$name是否包含屏蔽关键词
        $blocked = false;
        foreach ($banKeywords as $keyword) {
            $keyword = trim($keyword);
            if ($keyword === '') {
                continue;
            }
            $containsKeyword = function_exists('mb_strpos')
                ? mb_strpos($name, $keyword, 0, 'UTF-8') !== false
                : strpos($name, $keyword) !== false;
            if ($containsKeyword) {
                $blocked = true;
                break;
            }
        }

        $data['page_no'] = $page;
        $data['page_size'] = 10;
        $data['title'] = $name;
        $data['category_id'] = $cate;
        $searchKind = strtolower(trim((string) input('kind', '')));
        if (!in_array($searchKind, ['video', 'novel', 'document', 'software'], true)) {
            $searchKind = '';
        }
        $data['resource_kind'] = $searchKind;
        $data['search_type'] = 1;
        $data['is_time'] = 1;
        if (!$blocked) {
            // 生产环境的旧数据、分词扩展或外链检测异常，都不能让搜索页
            // 整体返回空白 500；本地结果失败时前端仍可自动切到全网搜索。
            try {
                $localList = $this->getLocalSearchList($data);
                if (is_array($localList)) {
                    $list = $localList;
                }
            } catch (\Throwable $e) {
                $this->logSearchFailure('local-list', $e);
            }
        }

        $rankList = [];
        $category = [];
        try {
            $rankList = $this->SourceCategoryModel->field('name,image')->where([['status', '=', 0], ['is_sys', '=', 1]])->order('sort desc')->select();
            $category = $this->SourceCategoryModel->field('name,source_category_id as id')->where([['status', '=', 0]])->order('sort desc')->select();
        } catch (\Throwable $e) {
            $this->logSearchFailure('categories', $e);
        }


        //热门排行榜数据
        $hotList = [];
        $cacheDir = root_path('runtime/api/cache'); // runtime/cache 目录
        foreach ($rankList as $value) {
            try {
                $cacheFile = $cacheDir . "ranking_data_{$value['name']}.cache";
                if (file_exists($cacheFile)) {
                    $hotList[] = array(
                        'name' => $value['name'],
                        'image' => $value['image'],
                        'list' => json_decode((string) @file_get_contents($cacheFile), true),
                    );
                }
            } catch (\Throwable $e) {
                $this->logSearchFailure('ranking-cache', $e);
            }
        }

        // 查询数据库，按 weight 排序
        $lines = [];
        try {
            $lines = $this->ApiListModel
                ->field('pantype, COUNT(*) as total, MAX(weight) as max_weight')
                ->where('status', 1)
                ->group('pantype')
                ->order('max_weight desc')
                ->select();
        } catch (\Throwable $e) {
            $this->logSearchFailure('api-lines', $e);
        }

        // 统计数量
        $linesTotal = [];
        foreach ($lines as $item) {
            $linesTotal[$item['pantype']] = $item['total'];
        }

        // 定义名称映射
        $names = [
            0 => '夸克网盘',
            2 => '百度网盘',
            3 => 'UC网盘',
            4 => '迅雷云盘'
        ];

        // 根据查询结果生成显示列表（顺序和数据库一致）
        $displayList = [];
        foreach ($lines as $item) {
            if (!empty($item['total'])) {
                $displayList[] = [
                    'type' => $item['pantype'],
                    'name' => $names[$item['pantype']] ?? '未知网盘',
                    'total' => $item['total']
                ];
            }
        }

        // 记录第一个 key（如果需要前端默认选中）
        $firstKey = !empty($displayList) ? $displayList[0]['type'] : null;

        // 如果没有任何数据
        if (empty($displayList)) {
            $config['is_quan'] = 0;
        }

        // 传给模板
        View::assign('blocked', $blocked);
        View::assign('displayList', $displayList);
        View::assign('firstKey', $firstKey);
        View::assign('hotList', $hotList);
        View::assign('rankList', $rankList);
        View::assign('category', $category);
        View::assign('list', $list);
        View::assign('config', $config);
        View::assign('keyword', $data['title']);
        View::assign('page_size', $data['page_size']);
        View::assign('page_no', $data['page_no']);
        View::assign('category_id', $data['category_id']);
        View::assign('search_kind', $searchKind);
        View::assign('seo_title', $data['title'] . ' - ' . $config['app_name']);
        View::assign('seo_keywords', $data['title'] . ',' . $config['app_keywords']);
        View::assign('seo_description', $data['title'] . ' - ' . $config['app_description']);
        try {
            return View::fetch('/news/list');
        } catch (\Throwable $e) {
            $this->logSearchFailure('template', $e);
            return $this->renderSearchFallback($name, $searchKind, $config);
        }
    }

    /**
     * 网页搜索使用轻量本地查询。列表请求不再同步验链、访问外部资料源或
     * 写海报缓存，避免某条历史数据/外部网盘响应把整个搜索页拖成 500。
     * 详情拉取和验链仍由详情页及搜索 SSE 的后台预热流程负责。
     */
    private function getLocalSearchList(array $data)
    {
        $keyword = trim((string) ($data['title'] ?? ''));
        $categoryId = trim((string) ($data['category_id'] ?? ''));
        $pageNo = max(1, (int) ($data['page_no'] ?? 1));
        $pageSize = max(1, min(30, (int) ($data['page_size'] ?? 10)));
        $requestedKind = strtolower(trim((string) ($data['resource_kind'] ?? '')));
        if (!in_array($requestedKind, ['video', 'novel', 'document', 'software'], true)) {
            $requestedKind = '';
        }

        $buildQuery = function () use ($keyword, $categoryId) {
            $query = $this->SourceModel->where([
                ['status', '=', 1],
                ['is_delete', '=', 0],
            ]);
            if ($keyword !== '') {
                $query->where('title|description', 'like', '%' . $keyword . '%');
            }
            if ($categoryId !== '') {
                $query->where('source_category_id', 'in', explode(',', $categoryId));
            }
            return $query;
        };

        if ($requestedKind === '') {
            $total = (int) $buildQuery()->count();
            $rows = $buildQuery()
                ->field('source_id as id,title,is_type,code,url,update_time as time')
                ->order('source_id', 'desc')
                ->page($pageNo, $pageSize)
                ->select()
                ->toArray();
        } else {
            // 类型是历史资源的推导字段，分块读取候选后分类，再做正确分页。
            $candidateRows = $buildQuery()
                ->field('source_id as id,title,is_type,code,url,update_time as time')
                ->order('source_id', 'desc')
                ->limit(1000)
                ->select()
                ->toArray();
            $filtered = [];
            foreach ($candidateRows as $row) {
                $kind = $this->safeResourceKind((string) ($row['title'] ?? ''), (string) ($row['url'] ?? ''));
                if (($kind['key'] ?? 'other') === $requestedKind) {
                    $row['_kind'] = $kind;
                    $filtered[] = $row;
                }
            }
            $total = count($filtered);
            $rows = array_slice($filtered, ($pageNo - 1) * $pageSize, $pageSize);
        }

        $items = [];
        foreach ($rows as $row) {
            $title = trim(str_replace("'", '', (string) ($row['title'] ?? '')));
            $kind = $row['_kind'] ?? $this->safeResourceKind($title, (string) ($row['url'] ?? ''));
            $row['title'] = $title;
            $row['name'] = highlightKeywords($title, [$keyword]);
            $row['times'] = !empty($row['time']) ? substr((string) $row['time'], 0, 10) : '';
            $row['resource_kind'] = (string) ($kind['key'] ?? 'other');
            $row['resource_kind_label'] = (string) ($kind['label'] ?? '其他');
            unset($row['time'], $row['_kind']);
            $items[] = $row;
        }

        return ['total_result' => $total, 'items' => $items];
    }

    private function safeResourceKind($title, $url)
    {
        try {
            return function_exists('detectResourceKind')
                ? detectResourceKind((string) $title, (string) $url)
                : ['key' => 'other', 'label' => '其他'];
        } catch (\Throwable $e) {
            return ['key' => 'other', 'label' => '其他'];
        }
    }

    /**
     * 记录搜索页的故障阶段。只写服务器日志，不向访客暴露路径、SQL 或配置。
     */
    private function logSearchFailure($stage, \Throwable $e)
    {
        @error_log('[search-page][' . $stage . '] ' . get_class($e) . ': ' . $e->getMessage());
    }

    /**
     * 模板自身无法编译时的最后保障页。它保留搜索框和全网 SSE 搜索，避免
     * 部署环境差异把用户留在空白 500 页面。
     */
    private function renderSearchFallback($keyword, $searchKind, array $config)
    {
        $siteName = htmlspecialchars((string) ($config['app_name'] ?? 'Pan Resource Manager'), ENT_QUOTES, 'UTF-8');
        $safeKeyword = htmlspecialchars((string) $keyword, ENT_QUOTES, 'UTF-8');
        $keywordJson = json_encode((string) $keyword, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $kindJson = json_encode((string) $searchKind, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $html = '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $safeKeyword . ' - ' . $siteName . '</title><style>'
            . 'body{margin:0;background:#f7f5ef;color:#202637;font-family:system-ui,-apple-system,"PingFang SC",sans-serif}.wrap{max-width:980px;margin:auto;padding:28px 18px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:26px}.brand{font-weight:800;font-size:22px}.search{display:flex;gap:10px;background:#fff;border:1px solid #ead7ae;border-radius:18px;padding:10px;box-shadow:0 12px 35px #6a512017}.search input{flex:1;border:0;outline:0;font-size:16px;padding:9px}.search button{border:0;border-radius:12px;background:#d8a84f;color:#18130b;font-weight:700;padding:0 22px}.notice{margin:22px 0;color:#687084}.grid{display:grid;gap:12px}.card{display:block;text-decoration:none;color:inherit;background:#fff;border:1px solid #ebe7de;border-radius:16px;padding:18px;box-shadow:0 7px 22px #2d34400a}.title{font-weight:750;font-size:17px}.meta{margin-top:8px;color:#8a91a0;font-size:13px}.state{padding:24px;text-align:center;color:#777}@media(max-width:600px){.wrap{padding:18px 12px}.search button{padding:0 15px}.brand{font-size:19px}}'
            . '</style></head><body><main class="wrap"><div class="top"><div class="brand">' . $siteName . '</div><a href="/" style="color:#9b6b17">返回首页</a></div>'
            . '<form class="search" id="searchForm"><input id="keyword" value="' . $safeKeyword . '" autocomplete="off" placeholder="搜索影视、小说、文档或软件"><button>搜索</button></form>'
            . '<div class="notice">本地检索暂时不可用，已自动启用全网搜索。</div><section class="grid" id="results"><div class="state">正在连接全网搜索…</div></section></main>'
            . '<script>(function(){var keyword=' . $keywordJson . ',kind=' . $kindJson . ',box=document.getElementById("results"),seen={};document.getElementById("searchForm").onsubmit=function(e){e.preventDefault();var q=document.getElementById("keyword").value.trim();if(q)location.href="/s/"+encodeURIComponent(q)+".html"+(kind?"?kind="+encodeURIComponent(kind):"")};'
            . 'function esc(s){var d=document.createElement("div");d.textContent=s||"";return d.innerHTML}function done(msg){if(!box.children.length||box.querySelector(".state"))box.innerHTML="<div class=\"state\">"+esc(msg)+"</div>"}if(!keyword){done("请输入关键词");return}var p=new URLSearchParams({title:keyword,is_type:"0",kind:kind||""}),es=new EventSource("/api/other/web_search?"+p.toString());es.onmessage=function(ev){if(!ev.data)return;if(ev.data.indexOf("[DONE]")>=0){es.close();if(!Object.keys(seen).length)done("暂未找到可用资源");return}try{var x=JSON.parse(ev.data);if(x.search_ready||x.detail_update)return;var key=x.result_key||x.url||x.title;if(seen[key])return;seen[key]=1;if(box.querySelector(".state"))box.innerHTML="";var a=document.createElement("a");a.className="card";a.href=x.showUrl||x.url||"#";a.innerHTML="<div class=\"title\">"+esc(x.title||"未命名资源")+"</div><div class=\"meta\">"+esc(x.resource_kind_label||"全网资源")+" · 点击查看</div>";box.appendChild(a)}catch(e){}};es.onerror=function(){es.close();if(!Object.keys(seen).length)done("全网搜索连接暂时不可用，请稍后重试")}})();</script></body></html>';
        return response($html, 200)->header(['Content-Type' => 'text/html; charset=utf-8', 'X-Search-Fallback' => '1']);
    }


    /**
     * @description: 详情
     * @param {*}
     * @return {*}
     */
    public function detail($id)
    {
        if (empty($id)) {
            return redirect('/');
        }

        // 详情首屏只读取数据库和现成海报缓存。公开资料、海报下载与验链由
        // 搜索页 prefetchDetail / 详情页 fetchPoster 异步执行，禁止外部接口
        // 异常把整个详情页打成 500。
        try {
            $detail = $this->getLocalDetail((int) $id);
        } catch (\Throwable $e) {
            @error_log('[detail-page][local] ' . get_class($e) . ': ' . $e->getMessage());
            $detail = null;
        }

        if (empty($detail)) {
            return redirect('/');
        }

        $rankList = [];
        try {
            $rankList = $this->SourceCategoryModel->field('name,image')->where([['status', '=', 0], ['is_sys', '=', 1]])->order('sort desc')->select();
        } catch (\Throwable $e) {
            $rankList = [];
        }

        //热门排行榜数据
        $hotList = [];
        $cacheDir = root_path('runtime/api/cache'); // runtime/cache 目录
        foreach ($rankList as $value) {
            $cacheFile = $cacheDir . "ranking_data_{$value['name']}.cache";
            if (file_exists($cacheFile)) {
                $hotList[] = array(
                    'name' => $value['name'],
                    'image' => $value['image'],
                    'list' => json_decode((string) @file_get_contents($cacheFile), true),
                );
            }
        }

        // 相关资源改为稳定的同分类最新列表，不再拼接分词权重 SQL。
        $sameList = [];
        try {
            $sameQuery = $this->SourceModel->where([
                ['status', '=', 1],
                ['is_delete', '=', 0],
                ['source_id', '<>', (int) $detail['id']],
            ]);
            if (!empty($detail['source_category_id'])) {
                $sameQuery->where('source_category_id', (int) $detail['source_category_id']);
            }
            $sameList = $sameQuery->field('source_id,title')->order('source_id', 'desc')->limit(10)->select();
        } catch (\Throwable $e) {
            $sameList = [];
        }

        $config = config("qfshop");
        if (!is_array($config)) {
            $config = [];
        }
        $config += ['app_name' => 'Pan Resource Manager', 'app_keywords' => '', 'app_description' => ''];

        View::assign('sameList', $sameList);
        View::assign('hotList', $hotList);
        View::assign('rankList', $rankList);
        View::assign('detail', $detail);
        View::assign('config', $config);
        View::assign('category_id', 0);

        if ($detail['category'] && $detail['category']['name']) {
            View::assign('seo_title', $detail['title'] . '_' . $detail['category']['name'] . ' - ' . $config['app_name']);
            View::assign('seo_keywords', $detail['title'] . '_' . $detail['category']['name'] . ',' . $config['app_keywords']);
            View::assign('seo_description', $detail['title'] . '_' . $detail['category']['name'] . ' - ' . $config['app_description']);
        } else {
            View::assign('seo_title', $detail['title'] . ' - ' . $config['app_name']);
            View::assign('seo_keywords', $detail['title'] . ',' . $config['app_keywords']);
            View::assign('seo_description', $detail['title'] . ' - ' . $config['app_description']);
        }
        return View::fetch('/news/detail');
    }

    /**
     * 构建不访问外网的详情首屏数据。
     */
    private function getLocalDetail($id)
    {
        $row = $this->SourceModel
            ->with('category')
            ->where([
                ['status', '=', 1],
                ['is_delete', '=', 0],
                ['source_id', '=', (int) $id],
            ])
            ->field('source_id as id,source_category_id,title,url,code,description,create_time as time,update_time,vod_content,vod_pic,is_type,is_time,page_views')
            ->find();
        if (empty($row)) {
            return null;
        }
        try {
            $this->SourceModel->where('source_id', (int) $id)->inc('page_views')->update();
        } catch (\Throwable $e) {
            // 浏览量失败不影响详情页。
        }
        $detail = $row->toArray();
        $title = trim(str_replace("'", '', (string) ($detail['title'] ?? '')));
        $detail['title'] = $title;
        $time = $detail['time'] ?? $detail['update_time'] ?? '';
        $detail['times'] = is_numeric($time) ? date('Y-m-d', (int) $time) : ($time ? substr((string) $time, 0, 10) : '');

        $auto = function_exists('buildResourceAutoDetail')
            ? buildResourceAutoDetail($title, (string) ($detail['url'] ?? ''), (int) ($detail['is_type'] ?? 0), (string) ($detail['code'] ?? ''))
            : [];
        $kind = function_exists('detectResourceKindWithCategory')
            ? detectResourceKindWithCategory($title, (string) ($detail['url'] ?? ''), (string) ($detail['category']['name'] ?? ''))
            : $this->safeResourceKind($title, (string) ($detail['url'] ?? ''));
        $detail['resource_kind'] = (string) ($kind['key'] ?? ($auto['resource_kind'] ?? 'other'));
        $detail['resource_kind_label'] = (string) ($kind['label'] ?? ($auto['resource_kind_label'] ?? '其他'));
        $detail['pan_name'] = (string) ($auto['pan_name'] ?? '');
        $detail['clean_title'] = (string) ($auto['clean_title'] ?? $title);
        $detail['auto_year'] = (string) ($auto['year'] ?? '');
        $detail['auto_episode'] = (string) ($auto['episode'] ?? '');
        $detail['auto_size'] = (string) ($auto['size'] ?? '');
        $detail['auto_qualities'] = $auto['qualities'] ?? [];
        $detail['auto_langs'] = $auto['langs'] ?? [];
        $detail['auto_tags'] = $auto['tags'] ?? [];
        $detail['share_id'] = (string) ($auto['share_id'] ?? '');
        if (empty($detail['code']) && !empty($auto['code'])) {
            $detail['code'] = (string) $auto['code'];
        }
        $manual = trim((string) ($detail['vod_content'] ?? ''));
        if ($manual === '' || $manual === $title) {
            $detail['vod_content'] = (string) ($auto['summary'] ?? $title);
            $detail['is_auto_detail'] = 1;
        } else {
            $detail['is_auto_detail'] = 0;
        }

        foreach (['online_summary', 'online_image', 'online_url', 'online_source', 'online_title', 'info_source', 'info_source_url', 'info_title', 'info_year', 'info_rating', 'info_card', 'info_duration', 'info_intro'] as $key) {
            $detail[$key] = '';
        }
        foreach (['info_genres', 'info_countries', 'info_languages', 'info_directors', 'info_authors', 'info_actors'] as $key) {
            $detail[$key] = [];
        }
        $detail['online_ok'] = 0;
        $detail['info_ok'] = 0;
        $detail['info_rating_count'] = 0;
        $detail['info_episodes'] = '';
        $detail['info_kind'] = $detail['resource_kind'];
        $detail['poster_auto'] = 0;
        $detail['poster_fallback'] = 0;
        $detail['poster_query'] = $detail['clean_title'];
        $detail['link_status'] = 'unknown';
        $detail['link_status_text'] = '后台检测中';

        $poster = trim((string) ($detail['vod_pic'] ?? ''));
        if ($poster !== '' && function_exists('cachePublicPosterLocally')) {
            $cachedPoster = cachePublicPosterLocally($poster, false);
            if ($cachedPoster !== '') {
                $detail['vod_pic_origin'] = $poster;
                $poster = $cachedPoster;
                $detail['poster_auto'] = 1;
            } elseif (strpos($poster, '/runtime/posters/') === 0 || strpos($poster, 'http') === 0) {
                // 未缓存的远程图交给浏览器异步 fetchPoster，不阻塞首屏。
                $poster = '';
            }
        }
        if ($poster === '') {
            $poster = function_exists('buildFallbackPosterDataUri')
                ? buildFallbackPosterDataUri($detail['clean_title'], $detail['resource_kind'])
                : '';
            $detail['poster_fallback'] = 1;
        }
        $detail['vod_pic'] = $poster;
        unset($detail['time']);
        return $detail;
    }


    public function show()
    {
        $data = input('');
        $this->SourceModel = new SourceModel();

        // 搜索条件
        $map = [];

        $map[] = ['status', '=', 1];
        $map[] = ['is_time', '=', 0];

        if (!empty($data['type'])) {
            // 将 $data['type'] 转换为时间戳
            $dayStart = strtotime($data['type']);
            $dayEnd = $dayStart + 86400; // 86400 秒 = 24 小时

            // 添加日期范围条件，只统计所选日期的记录
            $map[] = ['create_time', 'between', [$dayStart, $dayEnd]];
            View::assign('day', date('n月j日', $dayStart));
        } else {
            // 获取今天的时间戳范围
            $todayStart = strtotime(date('Y-m-d'));
            $todayEnd = $todayStart + 86400; // 86400 秒 = 24 小时

            // 添加日期范围条件，只统计今天的记录
            $map[] = ['create_time', 'between', [$todayStart, $todayEnd]];
            View::assign('day', date('n月j日'));
        }


        $result = $this->SourceModel->field('source_id as id,source_category_id,title,url,create_time as time,is_time')->where($map)->select()->each(function ($item, $key) {
            $item['times'] = substr($item['time'], 0, 10);
            unset($item['time']);
            return $item;
        })->toArray();


        View::assign('list', $result);
        return View::fetch();
    }
}
