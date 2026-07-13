<?php

namespace app\model;

use app\model\QfShop;

use Lizhichao\Word\VicWord;

class Source extends QfShop
{
    /**
     * 主键
     * @var string
     */
    protected $pk = 'source_id';

    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'source_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'source_id'    => 'integer',
        'is_delete'      => 'integer',
        'status'      => 'integer',
        'time'  =>  'timestamp',
    ];

    /**
     * 资源写入后清理首页类型专区缓存，保证自动更新
     */
    public static function onAfterWrite($model)
    {
        if (function_exists('clearHomeKindModulesCache')) {
            clearHomeKindModulesCache();
        }
    }

    /**
     * 资源删除后清理首页类型专区缓存
     */
    public static function onAfterDelete($model)
    {
        if (function_exists('clearHomeKindModulesCache')) {
            clearHomeKindModulesCache();
        }
    }
    
    /**
     * hasOne qf_source_category
     * @access public
     * @return mixed
     */
    public function category()
    {
        return $this
            ->hasOne(SourceCategory::class, 'source_category_id', 'source_category_id')
            ->joinType('left')
            ->field('source_category_id,name');
    }


    /**
     * @description: 获取一个信息
     * @param {*} $code
     * @return {*}
     */
    public function getDetail(array $data)
    {
        if (empty($data['id'])) {
            return null;
        }
        $map[] = ['status', '=', 1];
        $map[] = ['is_delete', '=', 0];
        $map[] = ['source_id', '=', (int) $data['id']];
        $field = 'source_id as id,source_category_id,title,url,code,description,create_time as time,update_time,vod_content,vod_pic,is_type,is_time,page_views';
        $result = $this->with('category')->where($map)->field($field)->find();
        if (is_null($result)) {
            return null;
        }

        try {
            $result->inc('page_views')->update();
        } catch (\Throwable $e) {
            // ignore
        }

        $timeRaw = $result['time'] ?? $result['update_time'] ?? '';
        if (is_numeric($timeRaw)) {
            $result['times'] = date('Y-m-d', (int) $timeRaw);
        } else {
            $result['times'] = $timeRaw ? substr((string) $timeRaw, 0, 10) : '';
        }

        $string = trim((string) $result['title']);
        $string = str_replace("'", '', $string);
        $result['title'] = $string;

        // 本地库通常无人工详情：从标题/链接自动提取并生成展示信息
        $auto = function_exists('buildResourceAutoDetail')
            ? buildResourceAutoDetail(
                $string,
                (string) ($result['url'] ?? ''),
                (int) ($result['is_type'] ?? 0),
                (string) ($result['code'] ?? '')
            )
            : [];

        if (!empty($auto)) {
            $result['resource_kind'] = $auto['resource_kind'] ?? 'other';
            $result['resource_kind_label'] = $auto['resource_kind_label'] ?? '其他';
            $result['pan_name'] = $auto['pan_name'] ?? '';
            $result['clean_title'] = $auto['clean_title'] ?? $string;
            $result['auto_year'] = $auto['year'] ?? '';
            $result['auto_episode'] = $auto['episode'] ?? '';
            $result['auto_size'] = $auto['size'] ?? '';
            $result['auto_qualities'] = $auto['qualities'] ?? [];
            $result['auto_langs'] = $auto['langs'] ?? [];
            $result['auto_tags'] = $auto['tags'] ?? [];
            $result['share_id'] = $auto['share_id'] ?? '';
            if (empty($result['code']) && !empty($auto['code'])) {
                $result['code'] = $auto['code'];
            }
            // 无人工简介 → 用自动摘要；有人工简介则保留
            $manual = trim((string) ($result['vod_content'] ?? ''));
            if ($manual === '' || $manual === $string) {
                $result['vod_content'] = $auto['summary'] ?? $string;
                $result['is_auto_detail'] = 1;
            } else {
                $result['is_auto_detail'] = 0;
            }
            // URL 推断的 is_type 更准时覆盖展示
            if (isset($auto['is_type'])) {
                $result['is_type'] = (int) $auto['is_type'];
            }
        } else {
            $resourceKind = function_exists('detectResourceKind')
                ? detectResourceKind($string, (string) ($result['url'] ?? ''))
                : ['key' => 'other', 'label' => '其他'];
            $result['resource_kind'] = $resourceKind['key'] ?? 'other';
            $result['resource_kind_label'] = $resourceKind['label'] ?? '其他';
            if (empty($result['vod_content'])) {
                $result['vod_content'] = $string;
            }
            $result['is_auto_detail'] = 0;
        }

        // 栏目是强类型信号：同名小说与影视改编必须进入不同详情/海报链路。
        if (function_exists('detectResourceKindWithCategory')) {
            $categoryName = (string) ($result['category']['name'] ?? '');
            $resolvedKind = detectResourceKindWithCategory(
                $string,
                (string) ($result['url'] ?? ''),
                $categoryName
            );
            $result['resource_kind'] = $resolvedKind['key'] ?? 'other';
            $result['resource_kind_label'] = $resolvedKind['label'] ?? '其他';
        }

        $kindKey = (string) ($result['resource_kind'] ?? 'other');
        $result['online_ok'] = 0;
        $result['online_summary'] = '';
        $result['online_image'] = '';
        $result['online_url'] = '';
        $result['online_source'] = '';
        $result['online_title'] = '';
        $result['poster_auto'] = 0;
        $result['poster_fallback'] = 0;
        $result['poster_query'] = (string) ($result['clean_title'] ?? $string);
        // 基本信息（豆瓣/BGM）
        $result['info_ok'] = 0;
        $result['info_source'] = '';
        $result['info_source_url'] = '';
        $result['info_title'] = '';
        $result['info_year'] = '';
        $result['info_rating'] = '';
        $result['info_rating_count'] = 0;
        $result['info_card'] = '';
        $result['info_genres'] = [];
        $result['info_countries'] = [];
        $result['info_languages'] = [];
        $result['info_directors'] = [];
        $result['info_authors'] = [];
        $result['info_actors'] = [];
        $result['info_episodes'] = '';
        $result['info_duration'] = '';
        $result['info_intro'] = '';
        $result['info_kind'] = '';

        $cleanForSearch = (string) ($result['clean_title'] ?? $string);
        $yearForSearch = (string) ($result['auto_year'] ?? '');

        // 全自动：按类型抓取基本信息/海报（无需后台配置）
        // 若库里已有封面/简介则保留，避免重复写库；没有则自动补全
        $storedRealPic = !empty($result['vod_pic']) && strpos((string) $result['vod_pic'], 'data:') !== 0
            && strpos((string) $result['vod_pic'], 'http') === 0;
        $strictPosterKind = in_array($kindKey, ['video', 'novel'], true);
        // 旧库中的 vod_pic 没有类型来源标记，不能证明没有串台。影视/小说详情
        // 只采用当前严格分源重新确认过的海报；未命中时显示各自的模块兜底图。
        if ($strictPosterKind) {
            $result['vod_pic'] = '';
        }
        $hadRealPic = $strictPosterKind ? false : $storedRealPic;
        $hadManualIntro = trim((string) ($result['vod_content'] ?? '')) !== ''
            && trim((string) ($result['vod_content'] ?? '')) !== $string
            && empty($result['is_auto_detail']);

        $result['info_card_title'] = $kindKey === 'novel' ? '书籍信息' : '';

        $autoPack = is_array($auto) ? $auto : [];
        if (function_exists('fetchResourceBasicInfo')) {
            try {
                $bi = fetchResourceBasicInfo(
                    $cleanForSearch,
                    $kindKey,
                    $yearForSearch,
                    (string) ($result['url'] ?? ''),
                    $autoPack
                );
                if (!empty($bi['ok'])) {
                    $result['info_ok'] = 1;
                    $result['info_source'] = (string) ($bi['source'] ?? '');
                    $result['info_source_url'] = (string) ($bi['source_url'] ?? '');
                    $result['info_title'] = (string) ($bi['title'] ?? '');
                    $result['info_year'] = (string) ($bi['year'] ?? '');
                    $result['info_rating'] = (string) ($bi['rating'] ?? '');
                    $result['info_rating_count'] = (int) ($bi['rating_count'] ?? 0);
                    $result['info_card'] = (string) ($bi['card_subtitle'] ?? '');
                    $result['info_genres'] = $bi['genres'] ?? [];
                    $result['info_countries'] = $bi['countries'] ?? [];
                    $result['info_languages'] = $bi['languages'] ?? [];
                    $result['info_directors'] = $bi['directors'] ?? [];
                    $result['info_authors'] = $bi['authors'] ?? [];
                    $result['info_actors'] = $bi['actors'] ?? [];
                    $result['info_episodes'] = (string) ($bi['episodes'] ?? '');
                    $result['info_duration'] = (string) ($bi['duration'] ?? '');
                    $result['info_intro'] = (string) ($bi['intro'] ?? '');
                    $result['info_kind'] = (string) ($bi['kind'] ?? $kindKey);
                    $result['info_file_format'] = (string) ($bi['file_format'] ?? '');
                    $result['info_platforms'] = $bi['platforms'] ?? [];
                    $result['info_file_size'] = (string) ($bi['file_size'] ?? '');

                    // 强制与 resource_kind 一致，禁止小说/影视字段混用
                    $result['info_kind'] = $kindKey;
                    if ($kindKey === 'novel') {
                        $result['info_directors'] = [];
                        $result['info_actors'] = [];
                        $result['info_episodes'] = '';
                        $result['info_duration'] = '';
                        $result['info_card_title'] = '书籍信息';
                    } elseif ($kindKey === 'video') {
                        $result['info_authors'] = [];
                        $result['info_card_title'] = '影视信息';
                    } else {
                        $result['info_directors'] = [];
                        $result['info_actors'] = [];
                        $result['info_authors'] = [];
                        $result['info_episodes'] = '';
                        $result['info_duration'] = '';
                    }

                    // 无人工简介时用自动简介
                    if (!$hadManualIntro && $result['info_intro'] !== '') {
                        $result['vod_content'] = $result['info_intro'];
                        $result['is_auto_detail'] = 1;
                    } elseif ($hadManualIntro) {
                        $result['info_intro'] = trim((string) $result['vod_content']);
                    }

                    // 无封面时用自动海报（仅影视/小说远程图；且 bi.kind 须匹配）
                    $allowRemotePoster = in_array($kindKey, ['video', 'novel'], true);
                    $biKindOk = $strictPosterKind
                        ? ((string) ($bi['kind'] ?? '') === $kindKey)
                        : (empty($bi['kind']) || $bi['kind'] === $kindKey);
                    if (!$hadRealPic && $allowRemotePoster && $biKindOk
                        && !empty($bi['poster']) && strpos((string) $bi['poster'], 'http') === 0) {
                        $result['vod_pic'] = $bi['poster'];
                        $result['poster_auto'] = 1;
                        $result['poster_fallback'] = 0;
                        try {
                            $this->where('source_id', (int) $result['id'])->update(['vod_pic' => $bi['poster']]);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // ② 海报：按 kind 拉取（非影视不会打 movie.douban）
        $hadPic = !empty($result['vod_pic']) && strpos((string) $result['vod_pic'], 'data:') !== 0;
        if (!$hadPic && function_exists('fetchResourcePoster')) {
            try {
                $poster = fetchResourcePoster($cleanForSearch, $kindKey, $yearForSearch, '', true);
                if ($poster !== '') {
                    $result['vod_pic'] = $poster;
                    $result['poster_auto'] = 1;
                    if (strpos($poster, 'data:') === 0) {
                        $result['poster_fallback'] = 1;
                    } else {
                        $result['poster_fallback'] = 0;
                        // 仅影视/小说远程海报落库
                        if (in_array($kindKey, ['video', 'novel'], true)) {
                            try {
                                $this->where('source_id', (int) $result['id'])->update(['vod_pic' => $poster]);
                            } catch (\Throwable $e) {
                                // ignore
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // ③ 维基：仅影视补充；小说/文档/软件不拉维基影视条目
        if ($kindKey === 'video' && empty($result['info_intro']) && function_exists('fetchResourceOnlineMeta')) {
            try {
                $online = fetchResourceOnlineMeta($cleanForSearch, 'video', $yearForSearch);
                if (!empty($online['ok'])) {
                    $result['online_ok'] = 1;
                    $result['online_summary'] = (string) ($online['summary'] ?? '');
                    $result['online_image'] = (string) ($online['image'] ?? '');
                    $result['online_url'] = (string) ($online['url'] ?? '');
                    $result['online_source'] = (string) ($online['source'] ?? '在线百科');
                    $result['online_title'] = (string) ($online['title'] ?? '');
                    if ($result['online_summary'] !== '') {
                        $result['info_intro'] = $result['online_summary'];
                        if (empty($result['vod_content']) || !empty($result['is_auto_detail'])) {
                            $result['vod_content'] = $result['online_summary'];
                        }
                    }
                    if ((!empty($result['poster_fallback']) || empty($result['vod_pic']) || strpos((string) $result['vod_pic'], 'data:') === 0)
                        && !empty($result['online_image'])) {
                        $result['vod_pic'] = $result['online_image'];
                        $result['poster_auto'] = 1;
                        $result['poster_fallback'] = 0;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 链接存活检测（放最后，失败不影响封面）
        $result['link_status'] = 'unknown';
        $result['link_status_text'] = '';
        if (function_exists('checkPanShareStatus')) {
            try {
                $chk = checkPanShareStatus(
                    (string) ($result['url'] ?? ''),
                    (string) ($result['code'] ?? '')
                );
                if (($chk['status'] ?? '') === 'unknown') {
                    $chk = checkPanShareStatus(
                        (string) ($result['url'] ?? ''),
                        (string) ($result['code'] ?? ''),
                        true
                    );
                }
                $result['link_status'] = $chk['status'] ?? 'unknown';
                $result['link_status_text'] = $chk['message'] ?? '';
                // 详情页仅展示检测状态，不因一次外部检测失败永久修改数据库。
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 浏览器不直接热链公开资料站图片；预先缓存到本站再展示。
        if (!empty($result['vod_pic']) && strpos((string) $result['vod_pic'], 'http') === 0
            && function_exists('cachePublicPosterLocally')) {
            $localPoster = cachePublicPosterLocally((string) $result['vod_pic']);
            if ($localPoster !== '') {
                $result['vod_pic_origin'] = (string) $result['vod_pic'];
                $result['vod_pic'] = $localPoster;
            }
        }

        unset($result['time']);
        return $result;
    }
    
     /**
     * 获取列表（前台本地库 / API 搜索）
     *
     * 关键修复（对照 so.laowu.life_EGcPe）：
     * 1) is_time：前台搜索应包含「正式资源 is_time=0」+「临时资源 is_time=1」
     *    旧代码用 array_search 删条件，可能删错下标导致匹配异常
     * 2) 搜索匹配：分词 AND 过严时回退「整词 LIKE」，避免库里有资源却 0 条
     * 3) 本地库本质是已入库的网盘分享链接（title/url），不是实时列目录
     *
     * @access public
     * @param array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getList(array $data)
    {
        $order = ['source_id' => 'desc'];
        $searchTitle = [];
        $keywordRaw = isset($data['title']) ? trim((string) $data['title']) : '';
        $requestedKind = strtolower(trim((string) ($data['resource_kind'] ?? '')));
        if (!in_array($requestedKind, ['video', 'novel', 'document', 'software'], true)) {
            $requestedKind = '';
        }

        // 基础条件：启用 + 未删除
        $map = [
            ['status', '=', 1],
            ['is_delete', '=', 0],
        ];

        // is_time：
        // - 不传 / 0：只查正式资源（is_time=0，后台导入的本地库）
        // - 1：正式 + 临时都查（前台 list 传 1，含全网搜转存的临时链）
        // 不再用 array_search+unset（会误删 status 条件）
        $includeTemp = !empty($data['is_time']) && (int) $data['is_time'] === 1;
        if (!$includeTemp) {
            $map[] = ['is_time', '=', 0];
        }

        if (!empty($data['day']) && (int) $data['day'] === 2) {
            $todayStart = strtotime(date('Y-m-d'));
            $todayEnd = $todayStart + 86400;
            $yesterdayStart = $todayStart - 86400;
            $map[] = ['create_time', 'between', [$yesterdayStart, $todayEnd]];

            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $ips = Log::where(['ip' => $ip])->find();
                if (empty($ips)) {
                    $log = new Log();
                    $log->save(['name' => '访问记录', 'ip' => $ip]);
                } else {
                    Log::where('id', $ips['id'])->update(['update_time' => time()]);
                }
            } catch (\Throwable $e) {
                // ignore log failure
            }
        }

        if (!empty($data['category_id'])) {
            $categoryIds = explode(',', (string) $data['category_id']);
            $map[] = ['source_category_id', 'in', $categoryIds];
        }

        if (!empty($data['type']) && (int) $data['type'] === 2) {
            $map[] = ['is_type', '=', 0];
        }

        // 搜索模式：0 精准整词 / 1 分词全满足(AND) / 2 分词任一(OR)
        // isset(search_type) 表示前台网页搜索，读取 conf；否则机器人等走简化逻辑
        if (isset($data['search_type'])) {
            $searchMode = (int) (config('qfshop.search_type') ?? 1);
        } else {
            $searchMode = (int) (config('qfshop.search_type') ?? 1);
            if ($searchMode !== 0) {
                $searchMode = 1;
            }
        }

        $query = null;
        $usedAlias = false;

        if ($keywordRaw !== '') {
            $keywords = $this->buildSearchKeywords($keywordRaw);
            $searchTitle = $keywords;

            if ($searchMode === 0 || empty($keywords)) {
                // 精准：整词命中 title 或 description
                $map[] = ['title|description', 'like', '%' . $keywordRaw . '%'];
                $query = $this->where($map);
            } elseif ($searchMode === 1) {
                // 模糊/AND：每个分词都要命中；同时保留「整词」作为更优先的匹配路径
                // 先按 AND 查；若 0 条再回退整词 LIKE（见下方 count 后处理）
                foreach ($keywords as $keyword) {
                    $map[] = ['title|description', 'like', '%' . $keyword . '%'];
                }
                $query = $this->where($map);
            } else {
                // 分词 OR + 权重
                $safeKeywords = [];
                foreach ($keywords as $keyword) {
                    $safeKeywords[] = str_replace(["'", '\\'], '', $keyword);
                }
                $weightExpr = [];
                foreach ($safeKeywords as $keyword) {
                    $weightExpr[] = "IF(title LIKE '%{$keyword}%' OR description LIKE '%{$keyword}%', 1, 0)";
                }
                // 整词额外加权
                $safeFull = str_replace(["'", '\\'], '', $keywordRaw);
                $weightExpr[] = "IF(title LIKE '%{$safeFull}%' OR description LIKE '%{$safeFull}%', 2, 0)";
                $weightSql = implode(' + ', $weightExpr);

                $query = $this->alias('a')
                    ->field('a.*, (' . $weightSql . ') as weight')
                    ->where($map)
                    ->where(function ($q) use ($keywords, $keywordRaw) {
                        $q->whereLike('title', '%' . $keywordRaw . '%')
                            ->whereOr('description', 'like', '%' . $keywordRaw . '%');
                        foreach ($keywords as $keyword) {
                            $q->whereOr('title', 'like', '%' . $keyword . '%')
                                ->whereOr('description', 'like', '%' . $keyword . '%');
                        }
                    });
                $order = ['weight' => 'desc', 'source_id' => 'desc'];
                $usedAlias = true;
            }
        } else {
            $query = $this->where($map);
        }

        if (!empty($data['type']) && (int) $data['type'] === 2) {
            $order = ['source_id' => 'asc'];
        }

        $result = ['total_result' => 0, 'items' => []];

        try {
            $result['total_result'] = (int) $query->count();
        } catch (\Throwable $e) {
            $result['total_result'] = 0;
        }

        // AND/分词 0 条时：回退整词 LIKE（本地库有资源却匹配不到的主因）
        if ($result['total_result'] <= 0 && $keywordRaw !== '' && $searchMode !== 0) {
            $fallbackMap = [
                ['status', '=', 1],
                ['is_delete', '=', 0],
            ];
            if (!$includeTemp) {
                $fallbackMap[] = ['is_time', '=', 0];
            }
            if (!empty($data['category_id'])) {
                $fallbackMap[] = ['source_category_id', 'in', explode(',', (string) $data['category_id'])];
            }
            if (!empty($data['type']) && (int) $data['type'] === 2) {
                $fallbackMap[] = ['is_type', '=', 0];
            }
            $fallbackMap[] = ['title|description', 'like', '%' . $keywordRaw . '%'];
            $query = $this->where($fallbackMap);
            $order = ['source_id' => 'desc'];
            $usedAlias = false;
            try {
                $result['total_result'] = (int) $query->count();
            } catch (\Throwable $e) {
                $result['total_result'] = 0;
            }
            $searchTitle = [$keywordRaw];
        }

        if ($result['total_result'] <= 0) {
            $result['items'] = [];
            return $result;
        }

        // 类型筛选必须先对完整候选集分类再分页，否则只过滤当前 10 条会造成
        // 总数与翻页错误。分块扫描避免固定 2000 条上限和一次性大内存占用。
        $items = null;
        if ($requestedKind !== '') {
            try {
                $pageNo = max(1, (int) ($data['page_no'] ?? 1));
                $pageSize = max(1, (int) ($data['page_size'] ?? 10));
                $wantedStart = ($pageNo - 1) * $pageSize;
                $filteredTotal = 0;
                $items = [];
                $chunkSize = 500;
                $chunkCount = max(1, (int) ceil((int) $result['total_result'] / $chunkSize));
                for ($chunkPage = 1; $chunkPage <= $chunkCount; $chunkPage++) {
                    $chunkQuery = clone $query;
                    if ($usedAlias) {
                        $candidates = $chunkQuery->order($order)
                            ->with('category')
                            ->page($chunkPage, $chunkSize)
                            ->select()
                            ->toArray();
                    } else {
                        $candidates = $chunkQuery->order($order)
                            ->field('source_id as id, source_category_id, title, is_type, code, url, update_time as time, is_time')
                            ->with('category')
                            ->page($chunkPage, $chunkSize)
                            ->select()
                            ->toArray();
                    }
                    foreach ($candidates as $candidate) {
                        $candidateTitle = trim((string) ($candidate['title'] ?? ''));
                        $candidateKind = function_exists('detectResourceKindWithCategory')
                            ? detectResourceKindWithCategory(
                                $candidateTitle,
                                (string) ($candidate['url'] ?? ''),
                                (string) ($candidate['category']['name'] ?? '')
                            )
                            : detectResourceKind($candidateTitle, (string) ($candidate['url'] ?? ''));
                        if (($candidateKind['key'] ?? 'other') !== $requestedKind) {
                            continue;
                        }
                        if ($filteredTotal >= $wantedStart && count($items) < $pageSize) {
                            $items[] = $candidate;
                        }
                        $filteredTotal++;
                    }
                }
                $result['total_result'] = $filteredTotal;
            } catch (\Throwable $e) {
                $items = [];
                $result['total_result'] = 0;
            }
        }

        // 无类型筛选时沿用数据库分页；带 alias 时不要重复 field 覆盖 weight。
        try {
            if ($items !== null) {
                // 已在上方完成分类与分页。
            } elseif ($usedAlias) {
                $items = $query->order($order)
                    ->with('category')
                    ->withSearch(['page', 'order'], $data)
                    ->select()
                    ->toArray();
            } else {
                $items = $query->order($order)
                    ->field('source_id as id, source_category_id, title, is_type, code, url, update_time as time, is_time')
                    ->with('category')
                    ->withSearch(['page', 'order'], $data)
                    ->select()
                    ->toArray();
            }
        } catch (\Throwable $e) {
            $items = [];
            $result['total_result'] = 0;
            $result['items'] = [];
            return $result;
        }

        foreach ($items as &$item) {
            // alias 查询时可能是 source_id 而非 id
            if (empty($item['id']) && !empty($item['source_id'])) {
                $item['id'] = $item['source_id'];
            }
            if (empty($item['time']) && !empty($item['update_time'])) {
                $item['time'] = is_numeric($item['update_time'])
                    ? date('Y-m-d H:i:s', (int) $item['update_time'])
                    : $item['update_time'];
            }

            $title = isset($item['title']) ? trim((string) $item['title']) : '';
            $title = str_replace("'", '', $title);
            $item['title'] = $title;
            $item['name'] = highlightKeywords($title, $searchTitle);
            $item['times'] = !empty($item['time']) ? substr((string) $item['time'], 0, 10) : '';
            unset($item['time']);

            $resourceKind = function_exists('detectResourceKindWithCategory')
                ? detectResourceKindWithCategory($title, $item['url'] ?? '', (string) ($item['category']['name'] ?? ''))
                : (function_exists('detectResourceKind')
                    ? detectResourceKind($title, $item['url'] ?? '')
                    : ['key' => 'other', 'label' => '其他']);
            $item['resource_kind'] = $resourceKind['key'] ?? 'other';
            $item['resource_kind_label'] = $resourceKind['label'] ?? '其他';
        }
        unset($item);

        // 自动筛选过期/失效分享（本地库 + 临时资源共用）
        // 有缓存秒回；无缓存时本页最多探测 8 条，避免拖慢搜索
        if (function_exists('filterExpiredShareItems') && !empty($items)) {
            $before = count($items);
            $items = filterExpiredShareItems($items, $this, 8);
            $removed = $before - count($items);
            if ($removed > 0 && $result['total_result'] >= $removed) {
                $result['total_result'] = max(0, (int) $result['total_result'] - $removed);
            }
        }

        $result['items'] = $items;

        // 临时资源被搜到时刷新 update_time，配合 30 分钟清理逻辑
        if ($includeTemp && !empty($items)) {
            $ids = [];
            foreach ($items as $item) {
                if (!empty($item['is_time']) && (int) $item['is_time'] === 1 && !empty($item['id'])) {
                    $ids[] = (int) $item['id'];
                }
            }
            if (!empty($ids)) {
                try {
                    $this->whereIn('source_id', $ids)->update(['update_time' => time()]);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        return $result;
    }

    /**
     * 生成搜索分词（失败时退回整词）
     */
    protected function buildSearchKeywords(string $title): array
    {
        $title = trim($title);
        if ($title === '') {
            return [];
        }

        $keywords = [];
        try {
            $fc = new VicWord();
            $parts = $fc->getAutoWord($title);
            if (function_exists('filterAndExtractWords')) {
                $parts = filterAndExtractWords($parts);
            } else {
                $parts = array_map(function ($item) {
                    return is_array($item) ? ($item[0] ?? '') : (string) $item;
                }, (array) $parts);
            }
            foreach ($parts as $keyword) {
                $keyword = trim((string) $keyword);
                if ($keyword !== '' && mb_strlen($keyword, 'UTF-8') > 1) {
                    $keywords[] = $keyword;
                }
            }
        } catch (\Throwable $e) {
            $keywords = [];
        }

        $keywords = array_values(array_unique($keywords));
        if (empty($keywords)) {
            $keywords = [$title];
        }
        return $keywords;
    }
    
    
    
    /**
     * 获取最新
     * @access public
     * @param array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getNew(array $data)
    {

        // 搜索条件
        $map = [];

        $map[] = ['status', '=', 1];
        $map[] = ['is_time', '=', 0];

        $result['total_result'] = $this->where($map)->count();
        if ($result['total_result'] <= 0) {
            return $result;
        }

        $result['items'] = $this->setDefaultOrder(['create_time' => 'desc'])
            ->field('title,create_time as time')
            ->where($map)
            ->withSearch(['page', 'order'], $data)
            ->select()->each(function($item,$key){
                $item['times'] = substr($item['time'], 5, 5);
                unset($item['time']); 
                return $item;
            })
            ->toArray();
        return $result;
    }
    
    
     /**
     * 获取最热
     * @access public
     * @param array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getHot(array $data)
    {
        $urlData = array(
            'endDay' => date("Y-m-d", strtotime("-1 day")), 
            'startDay' => date("Y-m-d", strtotime("-1 day"))
        );
        $urlHeader = array('Content-Type: application/json');
        
        //线路2
        $res = curlHelper("https://sycsp-prd.matesec.net/api/sp/miniApp/seriesRankList", "POST", json_encode($urlData),$urlHeader)['body'];
        $res = json_decode($res, true);
        if($res['code'] !== 200){
            return jerr($res['msg']);
        }
        if(empty($res['data']['seriesHeatRankList'])){
            $urlData = array(
                'endDay' => date("Y-m-d", strtotime("-2 day")), 
                'startDay' => date("Y-m-d", strtotime("-2 day"))
            );
            $urlHeader = array('Content-Type: application/json');
            
            //线路2
            $res = curlHelper("https://sycsp-prd.matesec.net/api/sp/miniApp/seriesRankList", "POST", json_encode($urlData),$urlHeader)['body'];
            $res = json_decode($res, true);
            if($res['code'] !== 200){
                return jerr($res['msg']);
            }
        }
        
        $ranking = 1;
        $result = [];
        foreach ($res['data']['seriesHeatRankList'] as $value) {
            $result[] = [
              'ranking' => $ranking++,
              'title' => $value['seriesName']??'',
              'hot' => $value['heatCount']??0,
              'hots' => $value['heatCountDisplay']??'',
            ];
        }
        return $result;
    }
    
    
}
