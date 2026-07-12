<?php

namespace app\api\controller;

use think\App;
use think\facade\Request;
use think\facade\Cache;
use app\api\QfShop;
use app\model\User as Usermodel;
use app\model\Ads as Adsmodel;
use app\model\Feedback as FeedbackModel;
use app\model\SourceCategory as SourceCategoryModel;
use app\model\Source as SourceModel;

class Tool extends QfShop
{
    /**
     * 系统配置参数
     *
     * @return void
     */
    public function getConfig()
    {
        // 与前台 window.__SITE_CFG__ 同源，后台改 conf 前后端同步
        $data = function_exists('getSitePublicConfig')
            ? getSitePublicConfig()
            : [
                'app_name' => Config('qfshop.app_name'),
                'qcode' => Config('qfshop.qcode'),
                'logo' => Config('qfshop.logo'),
                'app_description' => Config('qfshop.app_description'),
            ];
        // 图片类转绝对路径（若有）
        if (!empty($data['logo']) && function_exists('getimgurl')) {
            $data['logo'] = getimgurl($data['logo']);
        }
        if (!empty($data['qcode']) && function_exists('getimgurl')) {
            $data['qcode'] = getimgurl($data['qcode']);
        }
        if (!empty($data['app_icon']) && function_exists('getimgurl')) {
            $data['app_icon'] = getimgurl($data['app_icon']);
        }
        return jok('获取成功', $data);
    }
    /**
     * 上传图片
     *
     * @return void
     */
    public function Upload()
    {
        // 获取当前登录的用户信息
        $userInfo = $this->getLoginUser();
        
        try {
            $file = request()->file('file');
        } catch (\Exception $error) {
            return jerr('上传文件失败，请检查你的文件！');
        }
        $Usermodel = new Usermodel();
        $data = $Usermodel->Upload($file, $userInfo);
        return jok('上传成功',$data);
    }

    /**
     * 根据广告位关键词获取广告图片列表
     * 
     * @return void
     */
    public function getAdsCode()
    {
        $Adsmodel = new Adsmodel();
        $data = $Adsmodel->getAdsCode(input(''));
        return jok('获取成功',$data);
    }

    /**
     * 用户反馈
     * 
     * @return void
     */
    public function feedback()
    {
        $data = input('');
        if (empty($data['content'])) {
            return jerr("请输入要看的内容");
        }
        $FeedbackModel = new FeedbackModel();
        $FeedbackModel->save(['content' => $data['content']]);
        return jok('已反馈');
    }

    /**
     * 首页类型专区数据（自动识别）
     * refresh=1 时强制刷新缓存
     */
    public function kindModules()
    {
        $refresh = (int) input('refresh', 0);
        if ($refresh && function_exists('clearHomeKindModulesCache')) {
            clearHomeKindModulesCache();
        }

        $limit = (int) (config('qfshop.ranking_num') ?? 10);
        if ($limit < 8) {
            $limit = 8;
        }

        $SourceModel = new SourceModel();
        $modules = function_exists('buildHomeKindModules')
            ? buildHomeKindModules($SourceModel, $limit, 500)
            : [];

        return jok('获取成功', [
            'modules' => $modules,
            'updated_at' => date('Y-m-d H:i:s'),
            'auto' => true,
        ]);
    }
    

    /**
     * 获取首页排行榜数据
     *
     * @return void
     */
    public function ranking()
    {
        $channel = input('channel');
        $is_m = input('is_m')??0;
        
        if (empty($channel)) {
            return [];
        }
    
        // 使用 ThinkPHP 提供的 runtime_path() 函数获取 runtime 目录路径
        $cacheDir = runtime_path('cache'); // runtime/cache 目录
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true); // 确保缓存目录存在
        }
    
        // 根据 channel 值生成缓存文件名
        $cacheFile = $cacheDir . "ranking_data_{$channel}.cache";
        $cacheTime = 12*3600; // 缓存时间为 12 小时
    
        // 检查缓存文件是否存在且在缓存时间内
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
            // 从缓存中读取数据
            $data = json_decode(file_get_contents($cacheFile), true);
        } else {
            $data = [];
            if (!empty($channel)) {
                $queryParams =  array(
                    "area" =>  "全部",
                    "year" =>  "全部",
                    "channel" =>  $channel,
                    "rank_type" =>  "最热",
                    "cate" =>  "全部",
                    "from" =>  "hot_page",
                    "start" =>  0,
                    "hit" =>  Config('qfshop.ranking_num') ?? 1,
                );
                $res = curlHelper("https://biz.quark.cn/api/trending/ranking/getYingshiRanking", "GET", null, [], $queryParams)['body'];
                $res = json_decode($res, true);
                try {
                    foreach ($res['data']['hits']['hit']['item'] as $key => $value) {
                        $data[] = array(
                            "title" => $value['title']??'',
                            "src" => $value['src']??'',
                            "ranking" => $value['ranking']??'',
                            "hot_score" => $value['hot_score']??'',
                            "desc" => $value['desc']??'',
                        );
                    }
                } catch (Exception $error) {
                    $data = [];
                }
    
                // 将数据缓存到文件中
                file_put_contents($cacheFile, json_encode($data));
            }
        }
        
        if($is_m==1){
             $ranking_m_num = Config('qfshop.ranking_m_num') ?? 6;
            $data = array_slice($data, 0, $ranking_m_num);
        }
       
        return jok('获取成功', $data);
    }


    /**
     * 网页端全网搜接口
     *
     * @return void
     */
    public function Qsearch()
    {
        $title = input('title');
        $list = [];


        $userAgent = Request::header('user-agent');
        // 定义常见爬虫的 User-Agent 关键字
        $bots = ['Googlebot', 'Bingbot', 'Baiduspider'];
        foreach ($bots as $bot) {
            if (strpos($userAgent, $bot) !== false) {
                return jerr('该接口禁止爬虫访问');
            }
        }

        if (empty($title)) {
            return jok('临时资源获取成功', $list);
        }
        
        $keys = Request::ip()."_".$title;
        if(Cache::get($keys) == 1){
            return jerr('调用太过频繁啦');
        }
        Cache::set($keys, 1, 10);

        $bController = app(\app\api\controller\Other::class);
        $list = $bController->all_search($title);

        // 全网结果：自动筛掉已检测为失效的分享（有缓存秒回）
        if (!empty($list) && is_array($list) && function_exists('filterExpiredShareItems')) {
            $list = array_values(filterExpiredShareItems($list, new SourceModel(), 6));
        }

        Cache::delete($keys);
        return jok('临时资源获取成功', $list);
    }

    /**
     * 检测分享链接是否有效（前台可选）
     */
    public function checkShare()
    {
        $url = urldecode((string) input('url', ''));
        $code = (string) input('code', '');
        if ($url === '' || !function_exists('checkPanShareStatus')) {
            return jerr('参数错误');
        }
        $r = checkPanShareStatus($url, $code);
        return jok('ok', $r);
    }

    /**
     * 按标题拉取海报（供详情页二次刷新；不返回 SVG 兜底）
     */
    public function fetchPoster()
    {
        $title = trim((string) input('title', ''));
        $year = trim((string) input('year', ''));
        $kind = trim((string) input('kind', 'video'));
        $id = (int) input('id', 0);
        if ($title === '' || !function_exists('fetchResourcePoster')) {
            return jerr('参数错误');
        }
        // 强制走新逻辑：allowFallback=false，避免把兜底图当成功
        $url = fetchResourcePoster($title, $kind !== '' ? $kind : 'other', $year, '', false);
        if ($url === '' || strpos($url, 'http') !== 0) {
            return jerr('未匹配到海报');
        }
        if ($id > 0) {
            try {
                (new SourceModel())->where('source_id', $id)->update(['vod_pic' => $url]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return jok('ok', ['url' => $url]);
    }

    /**
     * 预热详情：搜索列表后台调用，预拉海报/基本信息进缓存
     * 参数：id 单条 或 ids=1,2,3 批量（最多 8 条）
     */
    public function prefetchDetail()
    {
        if (!function_exists('warmResourceDetailById')) {
            return jerr('功能未启用');
        }
        $ids = [];
        $one = (int) input('id', 0);
        if ($one > 0) {
            $ids[] = $one;
        }
        $raw = trim((string) input('ids', ''));
        if ($raw !== '') {
            foreach (explode(',', $raw) as $p) {
                $n = (int) trim($p);
                if ($n > 0) {
                    $ids[] = $n;
                }
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return jerr('缺少 id');
        }
        // 限流：单次最多 12 条（搜索预热），避免把外网接口打爆
        $ids = array_slice($ids, 0, 12);
        $model = new SourceModel();
        $out = [];
        foreach ($ids as $id) {
            $out[] = warmResourceDetailById($id, $model);
            // 轻微错峰
            usleep(120000);
        }
        return jok('ok', ['items' => $out, 'count' => count($out)]);
    }
}


