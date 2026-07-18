<?php

/**
 * 读取站点配置（后台 qf_conf → config('qfshop')）
 * 前后端统一走这里，禁止业务里写死站点文案/主题
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function site_conf($key, $default = '')
{
    $val = config('qfshop.' . $key);
    if ($val === null || $val === '') {
        return $default;
    }
    return $val;
}

/**
 * 前台可公开的站点自定义配置（不含 cookie / api_key 等敏感项）
 * 模板、window.__SITE_CFG__、/api/tool/getConfig 共用
 *
 * @return array
 */
function getSitePublicConfig()
{
    // 确保扩展文案项已写入 conf 表（老站升级不丢自定义入口）
    if (function_exists('ensureSiteCustomConfKeys')) {
        try {
            ensureSiteCustomConfKeys();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    $theme = site_conf('home_theme', '#1e80ff');
    $textColor = site_conf('home_color', '#3e3e3e');
    $bg = site_conf('home_background', '#fafafa');
    $otherBg = site_conf('other_background', '#ffffff');

    return [
        // 品牌
        'app_name' => (string) site_conf('app_name', ''),
        'app_name_hide' => (string) site_conf('app_name_hide', '0'),
        'app_subname' => (string) site_conf('app_subname', ''),
        'app_title' => (string) site_conf('app_title', ''),
        'app_description' => (string) site_conf('app_description', ''),
        'app_keywords' => (string) site_conf('app_keywords', ''),
        'logo' => (string) site_conf('logo', ''),
        'app_icon' => (string) site_conf('app_icon', ''),
        'qcode' => (string) site_conf('qcode', ''),
        // 主题 / 背景
        'home_theme' => (string) $theme,
        'home_color' => (string) $textColor,
        'home_background' => (string) $bg,
        'other_background' => (string) $otherBg,
        'home_bg' => (string) site_conf('home_bg', ''),
        'home_css' => (string) site_conf('home_css', ''),
        // 搜索与全网
        'is_quan' => (string) site_conf('is_quan', '0'),
        'is_quan_type' => (string) site_conf('is_quan_type', '0'),
        'search_type' => (string) site_conf('search_type', '1'),
        'search_tips' => (string) site_conf('search_tips', '未找到，可换个关键词尝试哦~'),
        'search_bg' => (string) site_conf('search_bg', ''),
        'search_placeholder' => (string) site_conf('search_placeholder', '输入关键字进行搜索'),
        'search_placeholder_home' => (string) site_conf('search_placeholder_home', '搜电影、小说、教程、软件…'),
        'quan_loading_text' => (string) site_conf('quan_loading_text', ' 全网检索中，请稍等...'),
        'quan_loading_hint' => (string) site_conf('quan_loading_hint', '正在聚合中…'),
        'quan_result_hint' => (string) site_conf('quan_result_hint', '点击获取资源'),
        'local_source_name' => (string) site_conf('local_source_name', '本地库'),
        'quan_source_name' => (string) site_conf('quan_source_name', '全网搜'),
        'local_result_hint' => (string) site_conf('local_result_hint', '自动识别类型 · 点击立即访问跳转网盘'),
        'search_input_empty' => (string) site_conf('search_input_empty', '请输入你要搜索的内容~'),
        'demand_input_empty' => (string) site_conf('demand_input_empty', '请输入你想看的资源信息~'),
        // 其它前台
        'pc_type' => (string) site_conf('pc_type', '0'),
        'app_demand' => (string) site_conf('app_demand', '0'),
        'app_links' => (string) site_conf('app_links', ''),
        'footer_dec' => (string) site_conf('footer_dec', ''),
        'footer_copyright' => (string) site_conf('footer_copyright', ''),
        'ranking_type' => (string) site_conf('ranking_type', '0'),
        'ranking_num' => (string) site_conf('ranking_num', '10'),
        'ranking_m_num' => (string) site_conf('ranking_m_num', '6'),
        'home_new' => (string) site_conf('home_new', '1'),
        'home_new_img' => (string) site_conf('home_new_img', ''),
        'hero_title_fallback' => (string) site_conf('hero_title_fallback', '电影、小说与资料'),
        'hero_lead_fallback' => (string) site_conf('hero_lead_fallback', '搜索网盘资源，复制分享，跳转打开。'),
        'hero_eyebrow_fallback' => (string) site_conf('hero_eyebrow_fallback', '网盘资源搜索'),
        'hero_visual_image' => (string) site_conf('hero_visual_image', ''),
        'hero_visual_title' => (string) site_conf('hero_visual_title', '热门资源推荐'),
        'hero_visual_link' => (string) site_conf('hero_visual_link', ''),
    ];
}

/**
 * 将前台扩展自定义项写入 qf_conf（仅插入缺失项，不覆盖已有值）
 * conf_type=1 搜索相关，conf_status=1 后台可见
 */
function ensureSiteCustomConfKeys()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    // conf_key => [title, desc, default, conf_type, conf_sort, conf_spec?, conf_content?]
    // conf_spec: 0文本 2单选  conf_content 单选：名称=>值 每行一项
    $rows = [
        'search_placeholder' => ['搜索框提示（列表页）', '列表页搜索输入框 placeholder', '输入关键字进行搜索', 1, 50],
        'search_placeholder_home' => ['搜索框提示（首页）', '首页大搜索框 placeholder', '搜电影、小说、教程、软件…', 1, 49],
        'quan_loading_text' => ['全网检索加载文案', '全网搜时加载动画上的文字', ' 全网检索中，请稍等...', 1, 48],
        'quan_loading_hint' => ['全网检索副提示', '加载中副标题', '正在聚合中…', 1, 47],
        'quan_result_hint' => ['全网结果提示', '全网有结果时的副提示', '点击获取资源', 1, 46],
        'local_source_name' => ['本地库名称', '搜索源切换按钮文案', '本地库', 1, 45],
        'quan_source_name' => ['全网搜名称', '搜索源切换按钮文案', '全网搜', 1, 44],
        'local_result_hint' => ['本地结果提示', '本地库结果栏副文案', '自动识别类型 · 点击立即访问跳转网盘', 1, 43],
        'search_input_empty' => ['空搜索提示', '未输入关键词点搜索时的提示', '请输入你要搜索的内容~', 1, 42],
        'demand_input_empty' => ['空需求提示', '提交需求未填内容时提示', '请输入你想看的资源信息~', 1, 41],
        'hero_title_fallback' => ['首页副标题兜底', 'app_title 为空时首页 em 文案', '电影、小说与资料', 3, 68],
        'hero_lead_fallback' => ['首页简介兜底', 'app_description 为空时', '搜索网盘资源，复制分享，跳转打开。', 3, 67],
        'hero_eyebrow_fallback' => ['首页眉题兜底', 'app_subname 为空时', '网盘资源搜索', 3, 66],
        'hero_visual_image' => ['首页右侧展示图', '首页搜索框右侧大图，建议 1200×800 或 3:2；留空时自动展示站内最新海报', '', 3, 65, 4],
        'hero_visual_title' => ['首页右侧展示标题', '自定义大图底部显示的标题', '热门资源推荐', 3, 64],
        'hero_visual_link' => ['首页右侧展示链接', '点击自定义大图跳转的地址；留空则不跳转', '', 3, 63],
    ];

    try {
        $now = time();
        foreach ($rows as $key => $meta) {
            $exists = \think\facade\Db::name('conf')->where('conf_key', $key)->find();
            if ($exists) {
                continue;
            }
            $spec = isset($meta[5]) ? (int) $meta[5] : 0;
            $content = $meta[6] ?? null;
            \think\facade\Db::name('conf')->insert([
                'conf_key' => $key,
                'conf_value' => $meta[2],
                'conf_title' => $meta[0],
                'conf_desc' => $meta[1],
                'conf_int' => 0,
                'conf_spec' => $spec,
                'conf_content' => $content,
                'conf_type' => $meta[3],
                'conf_status' => 1,
                'conf_sort' => $meta[4],
                'conf_system' => 1,
                'conf_createtime' => $now,
                'conf_updatetime' => $now,
            ]);
            // 同步进当前请求的 config
            config([$key => $meta[2]], 'qfshop');
        }
    } catch (\Throwable $e) {
        // 安装前/无库时忽略
    }
}

/**
 * 输出正常JSON
 *
 * @param string 提示信息
 * @param array  输出数据
 * @return json
 */
function jok($message = 'success', $data = null)
{
    header("content-type:application/json;chartset=uft-8");
    if ($data) {
        echo json_encode(["code" => 200, "message" => $message, 'data' => $data]);
    } else {
        echo json_encode(["code" => 200, "message" => $message, 'data' => $data??'']);
    }
    die;
}
function jok2($message = 'success', $data = null)
{
    if ($data) {
        return ["code" => 200, "message" => $message, 'data' => $data];
    } else {
        return ["code" => 200, "message" => $message, 'data' => $data??''];
    }
}
/**
 * 输出错误JSON
 *
 * @param string 错误信息
 * @param int 错误代码
 * @return json
 */
function jerr($message = 'error', $code = 500)
{
    header("content-type:application/json;chartset=uft-8");
    echo json_encode(["code" => $code, "message" => $message]);
    die;
}
function jerr2($message = 'error', $code = 500)
{
    return ["code" => $code, "message" => $message];
}
/**
 * 密码+盐 加密
 *
 * @param string 明文密码
 * @param string 盐
 * @return string
 */
function encodePassword($password, $salt)
{
    return sha1($password . $salt . $password . $salt);
}
/**
 * 密码校验 6-16
 *
 * @param string 明文密码
 * @return boolean 是否校验通过
 */
function isValidPassword($password)
{
    return preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*\s).{6,}/', $password);
}
/**
 * 获取随机字符
 *
 * @param int $len
 * @return void
 */
function getRandString($len)
{
    $string = '';
    $randString = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    for ($i = 0; $i < $len; $i++) {
        $string .= $randString[rand(0, strlen($randString) - 1)];
    }
    return $string;
}
/**
 * 生成唯一会议编码
 * @param string $prefix 头部
 * @return string
 */
function get_order_no($prefix = 'QF')
{
    $order_no = $prefix;
    $order_no .= mb_strtoupper(dechex(date('m')), 'utf-8');
    $order_no .= date('d') . mb_substr(time(), -5, null, 'utf-8');
    $order_no .= mb_substr(microtime(), 2, 5, 'utf-8');
    return $order_no;
}
/**
 * 获取随机字母
 *
 * @param int 长度
 * @return string
 */
function getRandChar($len)
{
    $string = '';
    $randString = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    for ($i = 0; $i < $len; $i++) {
        $string .= $randString[rand(0, strlen($randString) - 1)];
    }
    return $string;
}
/**
 * 驼峰转下划线
 * @param $camelCaps
 * @param string $separator
 * @return string
 */
function uncamelize($camelCaps, $separator = '_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}
/**
 * 遍历类的方法
 *
 * @param string 指定的类名称
 * @return array
 */
function getClassMethods($class)
{
    $array_result = [];
    $array_all = get_class_methods($class);
    if ($parent_class = get_parent_class($class)) {
        $array_parent = get_class_methods($parent_class);
        $array_result = array_diff($array_all, $array_parent);
    } else {
        $array_result = $array_all;
    }
    return $array_result;
}
/**
 * 获取包含协议和端口的域名
 *
 * @return string
 */
function getFullDomain()
{
    // return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME']) . "://" . $_SERVER['HTTP_HOST'];
    return "http://" . $_SERVER['HTTP_HOST'];
}
/**
 * 图片地址转绝对路径
 *
 * @return string
 */
function getimgurl($img)
{   
    $result = '';
    if($img){
        // $url = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME']) . "://" . $_SERVER['HTTP_HOST'];
        $url = "http://" . $_SERVER['HTTP_HOST'];
        if(is_array($img)){
            $result = [];
            foreach ($img as $key => $value) {
                if(!preg_match("/^http(s)?:\\/\\/.+/", $value)){
                    $result[] = $url.$value;
                }else{
                    $result[] = $value;
                }
            }
        }else{
            if(!preg_match("/^http(s)?:\\/\\/.+/", $img)){
                $result = $url.$img;
            }else{
                $result = $img;
            }
        }
    }
    return $result;
}

/**
 * 替换编辑器内容中的文件地址
 * @param string  $content     编辑器内容
 * @return string
 */
function app_replace_content_file_url($content)
{
    \phpQuery::newDocumentHTML($content);
    $pq = pq(null);
    $domain        = request()->host();
    $images = $pq->find("img");
    if ($images->length) {
        foreach ($images as $img) {
            $img    = pq($img);
            $imgSrc = $img->attr("src");
            if(!preg_match("/^http(s)?:\\/\\/.+/", $imgSrc)){
                $img->attr("src", getimgurl($imgSrc));
            }
        }
    }
    $links = $pq->find("a");
    if ($links->length) {
        foreach ($links as $link) {
            $link = pq($link);
            $href = $link->attr("href");
            if(!preg_match("/^http(s)?:\\/\\/.+/", $href)){
                $img->attr("href", getimgurl($imgSrc));
            }
        }
    }
    $content = $pq->htmlOuter();
    \phpQuery::$documents = null;
    return $content;
}

/**
 * 获取客户端IP
 *
 * @return string
 */
function getClientIp()
{
    foreach (array(
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ) as $key) {
        if (array_key_exists($key, $_SERVER)) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if ((bool) filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6
                    // FILTER_FLAG_NO_PRIV_RANGE |
                    // FILTER_FLAG_NO_RES_RANGE
                )) {
                    return $ip;
                }
            }
        }
    }
    return null;
}

/**
 * 取文本中间
 *
 * @param string 原始字符串
 * @param string 左边字符串
 * @param string 右边字符串
 * @return string
 */
function getSubstr($str, $leftStr, $rightStr)
{
    $left = strpos($str, $leftStr);
    $right = strpos($str, $rightStr, $left);
    if ($left < 0 or $right < $left) return '';
    return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
}
/**
 * 获取操作系统
 *
 * @return string
 */
function  getOs()
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return 'Other';
    }
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($agent, 'windows nt')) {
        $platform = 'Windows';
    } elseif (strpos($agent, 'macintosh')) {
        $platform = 'MacOS';
    } elseif (strpos($agent, 'ipod')) {
        $platform = 'iPod';
    } elseif (strpos($agent, 'ipad')) {
        $platform = 'iPad';
    } elseif (strpos($agent, 'iphone')) {
        $platform = 'iPhone';
    } elseif (strpos($agent, 'android')) {
        $platform = 'Android';
    } elseif (strpos($agent, 'unix')) {
        $platform = 'Unix';
    } elseif (strpos($agent, 'linux')) {
        $platform = 'Linux';
    } else {
        $platform = 'Other';
    }
    return $platform;
}
/**
 * 获取浏览器
 *
 * @return void
 */
function  getBrowser()
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return 'Unknown';
    }
    $agent = $_SERVER["HTTP_USER_AGENT"];
    if (strpos($agent, 'MSIE') !== false || strpos($agent, 'rv:11.0')) //ie11判断
    {
        return "IE";
    } else if (strpos($agent, 'Firefox') !== false) {
        return "Firefox";
    } else if (strpos($agent, 'Chrome') !== false) {
        return "Chrome";
    } else if (strpos($agent, 'Opera') !== false) {
        return 'Opera';
    } else if ((strpos($agent, 'Chrome') == false) && strpos($agent, 'Safari') !== false) {
        return 'Safari';
    } else {
        return 'Unknown';
    }
}
/**
 * 是否手机请求
 *
 * @return boolean
 */
function isMobileRequest()
{
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
    $mobile_browser = '0';
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|uc|qq|wechat|micro|messenger|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
        $mobile_browser++;
    if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
        $mobile_browser++;
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        $mobile_browser++;
    if (isset($_SERVER['HTTP_PROFILE']))
        $mobile_browser++;
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
    $mobile_agents = array(
        'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
        'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
        'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
        'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
        'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
        'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
        'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
        'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
        'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
    );
    if (in_array($mobile_ua, $mobile_agents))
        $mobile_browser++;
    if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
        $mobile_browser++;
    // Pre-final check to reset everything if the user is on Windows  
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
        $mobile_browser = 0;
    // But WP7 is also Windows, with a slightly different characteristic  
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
        $mobile_browser++;
    if ($mobile_browser > 0)
        return true;
    else
        return false;
}
/**
 * 身份证号验证
 * @param $id
 * @return bool
 */
function isIDCard($id)
{
    $id = strtoupper($id);
    $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
    $arr_split = array();
    if (!preg_match($regx, $id)) {
        return FALSE;
    }
    if (15 == strlen($id)) //检查15位
    {
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
        @preg_match($regx, $id, $arr_split);
        //检查生日日期是否正确
        $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if (!strtotime($dtm_birth)) {
            return FALSE;
        } else {
            return TRUE;
        }
    } else { //检查18位
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        @preg_match($regx, $id, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if (!strtotime($dtm_birth)) //检查生日日期是否正确
        {
            return FALSE;
        } else {
            //检验18位身份证的校验码是否正确。
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sign = 0;
            for ($i = 0; $i < 17; $i++) {
                $b = (int) $id[$i];
                $w = $arr_int[$i];
                $sign += $b * $w;
            }
            $n = $sign % 11;
            $val_num = $arr_ch[$n];
            if ($val_num != substr($id, 17, 1)) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }
}
/**
 * 是否是整数
 *
 * @param string 输入内容
 * @return boolean
 */
function isInteger($input)
{
    return (ctype_digit(strval($input)));
}
/**
 * 获取一个key摘要
 *
 * @param string 原始key
 * @return string
 */
function getTicket($key)
{
    return sha1($key . (env('SYSTEM_SALT') ?? 'qfshop') . $key);
}
/**
 * 通用CURL请求函数
 *
 * @param string $url URL地址
 * @param string $method 请求方法, 支持GET/POST/PUT/DELETE/PATCH/TRACE/OPTIONS/HEAD 默认POST
 * @param mixed $data 请求数据包体
 * @param array $header 请求头 数组
 * @param array $queryParams 查询参数 数组
 * @param string $cookies 请求COOKIES字符串
 * @param int $timeout 请求超时时间，默认30秒
 * @return array 响应数组，包括header, body, detail, error
 */
function curlHelper($url, $method = 'POST', $data = null, $header = [], $queryParams = "", $cookies = "", $timeout = 60)
{
    // 构建查询参数
    if (!empty($queryParams)) {
        $queryString = http_build_query($queryParams);
        $url .= '?' . $queryString;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    // 自动解压 gzip/deflate，否则豆瓣等接口 body 是二进制，json_decode 全失败
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // 设置超时时间
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(8, (int) $timeout)); // 设置连接超时时间

    // 根据请求方法设置选项
    switch (strtoupper($method)) {
        case "POST":
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case "PUT":
        case "DELETE":
        case "PATCH":
        case "TRACE":
        case "OPTIONS":
        case "HEAD":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case "GET":
        default:
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
    }

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $output = [
        'header' => substr($response, 0, $headerSize),
        'body' => substr($response, $headerSize),
        'detail' => curl_getinfo($ch)
    ];

    curl_close($ch);
    return $output;
}

/**
 * 模拟表单上传文件请求
 * @param $$url 提交地址
 * @param $data 提交数据
 * @param $cookies 如设置了Content-Type将被自动覆写为formdata
 * ex.
 * $data = ['file'=>new \CURLFile(realpath($file_dir)),appid"=>"1234"];
 * $result = curl_form($url,$data);
 * @return mixed
 */
function curlForm($url, $data = null, $header = [], $cookies = "")
{
    $header[] = 'Content-Type: multipart/form-data';
    return curlHelper($url, "POST", $data, $header, $cookies);
}
/**
 * 多维数组合并（支持多数组）
 * @param arraylist arrayMergeMulti(['1'=>'1','2'=>'2','3'=>'3'],['4'=>'4','5'=>'5','6'=>'6'])
 * @return array
 */
function arrayMergeMulti()
{
    //获取当前方法捕获到的所有参数数组
    $args = func_get_args();
    $array = [];
    foreach ($args as $arg) {
        if (is_array($arg)) {
            foreach ($arg as $k => $v) {
                if (is_array($v)) {
                    $array[$k] = isset($array[$k]) ? $array[$k] : [];
                    $array[$k] = arrayMergeMulti($array[$k], $v);
                } else {
                    $array[$k] = $v;
                }
            }
        }
    }

    return $array;
}
/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list   查询结果
 * @param string $field 排序的字段名
 * @param array $sortBy 排序类型
 *                      asc正向排序 desc逆向排序 nat自然排序
 * @return array|bool
 */
function listSortBy($list, $field, $sortBy = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = [];
        foreach ($list as $i => $data) {
            $refer[$i] = &$data[$field];
        }
        switch ($sortBy) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc': // 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val) {
            $resultSet[] = &$list[$key];
        }
        return $resultSet;
    }

    return false;
}

/**
 * 格式化字节大小
 * @param  number   $size       字节数
 * @param  int      $float      小数保留位数
 * @param  string   $delimiter  数字和单位分隔符
 * @return string   格式化后的带单位的大小
 */
function formatBytes($size, $float = 2, $delimiter = '')
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;

    return round($size, $float) . $delimiter . $units[$i];
}
/**
 * 生成标准UUID
 *
 * @return string
 */
function getUuid()
{
    mt_srand((float) microtime() * 10000);
    $uuid = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    return $uuid;
}


/**
 * 判断是否为空参数
 * @param mixed $parm
 * @return bool
 */
function is_empty_parm(&$parm)
{
    return !(isset($parm) && '' !== $parm);
}

/**
 * 返回当前账号openid
 * $openid wxapp_openid wechat_openid
 * @return string
 */
function get_client_openid($user_id, $openid)
{
    return \think\facade\Db::name('user')->where('user_id', $user_id)->value($openid);
}

/**
 * 产生数字与字母混合随机字符串
 * @param int $len 数值长度,默认6位
 * @return string
 */
function get_randstr($len = 6)
{
    $chars = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
        'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
        'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G',
        'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2',
        '3', '4', '5', '6', '7', '8', '9',
    ];

    $charsLen = count($chars) - 1;
    shuffle($chars);

    $output = '';
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }

    return $output;
}

/**
 * 产生随机数值
 * @param int $len 数值长度,默认8位
 * @return string
 */
function rand_number($len = 8)
{
    $chars = str_repeat('123456789', 3);
    if ($len > 10) {
        $chars = str_repeat($chars, $len);
    }

    $chars = str_shuffle($chars);
    return mb_substr($chars, 0, $len, 'utf-8');
}

/**
 * 智能字符串模糊化
 * @param string $str 被模糊的字符串
 * @param int    $len 模糊的长度
 * @return string
 */
function auto_hid_substr(string $str, $len = 3)
{
    if (empty($str)) {
        return null;
    }

    $sub_str = mb_substr($str, 0, 1, 'utf-8');
    for ($i = 0; $i < $len; $i++) {
        $sub_str .= '*';
    }

    if (mb_strlen($str, 'utf-8') <= 2) {
        $str = $sub_str;
    }

    $sub_str .= mb_substr($str, -1, 1, 'utf-8');
    return $sub_str;
}

/**
 * 多维数组，根据某个特定字段过滤重复值
 * @return array
 */
function assoc_unique($arr, $key) {
    $tmp_arr = array();
    foreach ($arr as $k => $v) {
        if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
            unset($arr[$k]);
        } else {
            $tmp_arr[] = $v[$key];
        }
    }
    sort($arr); //sort函数对数组进行排序
    return $arr;
}




function getDom($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // 设置超时时间
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 连接超时：5秒
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);       // 响应超时：5秒
    // 临时跳过 SSL 验证（测试用）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 避免跳转被拦
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // 模拟浏览器UA
    $html = curl_exec($ch);
    curl_close($ch);
    
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    
    return $dom;
}


/**
 * 过滤标点符号并提取词语
 *
 * @param array $result 分词结果
 * @return array 过滤后的词汇
 */
function filterAndExtractWords(array $result)
{
    // 定义要过滤掉的符号，包括标点符号和其他符号
    $pattern = '/[\p{P}\p{S}]/u';

    // 提取词语、过滤掉符号并删除空的词语
    $filteredWords = array();
    foreach ($result as $item) {
        $word = preg_replace($pattern, '', $item[0]);
        // 只保留非空的词汇
        if (!empty($word)) {
            $filteredWords[] = $word;
        }
    }

    return $filteredWords;
}


/**
 * 高亮显示关键词
 * @param string $title 原始标题
 * @param string $searchTitle 搜索标题
 * @return string 带有高亮的标题
 */
function highlightKeywords($title, $searchTitle)
{
    $originalTitle = $title; // 保存原始标题

    // 使用正则表达式来高亮所有关键词
    foreach ($searchTitle as $keyword) {
        $title = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<span>$1</span>', $title);
    }

    // 如果有关键词被高亮，添加<p>标签
    if ($title !== $originalTitle) {
        $title = '<p>' . $title . '</p>';
    }

    return $title;
}

/**
 * 判断是哪个网盘
 * @return int 网盘类型
 */
function determineIsType($url) {
    $domains = [
        'alipan.com' => 1,
        'aliyundrive.com' => 1,
        'baidu.com' => 2,
        'uc.cn' => 3,
        'xunlei.com' => 4
    ];

    foreach ($domains as $domain => $type) {
        if (strpos($url, $domain) !== false) {
            return $type;
        }
    }

    // 默认值是夸克网盘，返回 0
    return 0;
}

/**
 * 解析网盘分享链接
 * 
 * @param string $input 输入的链接文本，可以是多行
 * @return array 解析后的链接数组，每个元素包含url、title和code
 */
function parsePanLinks($input)
{
    // 分割多行输入
    $links = explode("\n", $input);
    
    // 去掉数组元素中的空白字符
    $links = array_map('trim', $links);
    
    // 过滤掉空值的数组元素
    $links = array_filter($links);
    
    $parsedLinks = array_values(array_filter(array_map(function ($item) {
        // 统一处理百度网盘分享格式，同时匹配两种格式
        if (preg_match('/链接:?\s*(https?:\/\/[^\s]+)\s*提取码:?\s*([a-zA-Z0-9]{4})/i', $item, $matches)) {
            $url = trim($matches[1]);
            $code = trim($matches[2]);
            // 确保URL中包含提取码
            if (!empty($code) && strpos($url, '?pwd=') === false) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . 'pwd=' . $code;
            }
            return [
                'url' => $url,
                'title' => '',
                'code' => $code
            ];
        }
        
        // 提取 URL
        if (!preg_match('/https?:\/\/[^\s]+/', $item, $matches)) {
            return null; // 没有匹配到 URL，直接丢弃
        }
    
        $url = trim($matches[0]);
        $code = '';
    
        // 提取提取码（?pwd= 或 , 分割）
        if (preg_match('/\?pwd=([^,\s&]+)/', $item, $pwdMatch)) {
            $code = trim($pwdMatch[1]);
        } elseif (preg_match('/,\s*([a-zA-Z0-9]{4})\s*$/', $item, $commaMatch)) {
            $code = trim($commaMatch[1]);
            // 添加提取码到URL
            if (!empty($code)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . 'pwd=' . $code;
            }
        } elseif (preg_match('/提取码:?\s*([a-zA-Z0-9]{4})/i', $item, $codeMatch)) {
            $code = trim($codeMatch[1]);
            // 添加提取码到URL
            if (!empty($code)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . 'pwd=' . $code;
            }
        }
    
        // 返回结果时，确保 title 保持为空字符串
        return [
            'url' => $url,
            'title' => '',
            'code' => $code
        ];
    }, $links)));
    
    // 去重，使用 'url' 字段来去重
    $uniqueUrls = [];
    $result = array_filter($parsedLinks, function($item) use (&$uniqueUrls) {
        if (!in_array($item['url'], $uniqueUrls)) {
            $uniqueUrls[] = $item['url'];  // 添加到已处理的 URL 列表
            return true;  // 保留此项目
        }
        return false;  // 去掉重复的项目
    });
    
    return array_values($result);
}


/**
 * 夸克线路一
 * @return array
 */
function source1($isStoken, $title, $type = 0, $num = 5, $index = 0)
{
    return sourceData1($isStoken,$title,$type,$num,$index);
}

/**
 * 夸克线路二
 * @return array
 */
function source2($isStoken, $title, $type = 0, $num = 5)
{
    return sourceData2($isStoken,$title,$type,$num);
}

/**
 * 网络资源搜索源一
 * @return array
 */
function sourceData1($isStoken, $title, $type = 0, $maxCount = 100, $apiType = 0)
{
    $urlDefault = "https://m.kkkba.com"; //http://s.kkkob.com
    $url2 = [];
    
    // 定义匹配不同网盘的正则表达式
    $pattern = [
        0 => '/https:\/\/pan\.quark\.cn\/[^\s]*/', // 只匹配夸克
        2 => '/https:\/\/pan\.baidu\.com\/[^\s]*/' // 只匹配百度
    ];
    if (!isset($pattern[$type])) {
        return [];
    }

    try {
        $res = curlHelper($urlDefault."/v/api/getToken", "GET", null, [], "", "", 5)['body'];
    } catch (Exception $err ) {
         return $url2;
    }
    
    $res = json_decode($res, true);
    $token = $res['token'] ?? '';
    if(empty($token)){
        return $url2;
    }
    
    $urlData = array(
        'name' => $title, 
        'token' => $token
    );
    $urlHeader = array('Content-Type: application/json');
    
    $allApiList = [
        1 => "/v/api/getJuzi",
        2 => "/v/api/search",
        3 => "/v/api/getXiaoyu",
        4 => "/v/api/getDJ",
        5 => "/v/api/getKK"
    ];
        
    // 根据 apiType 确定要调用的接口列表
    if ($apiType == 0) {
        // 全部接口
        $apiList = array_values($allApiList);
    } elseif (isset($allApiList[$apiType])) {
        // 指定某个接口
        $apiList = [$allApiList[$apiType]];
    } else {
        // 错误类型，直接返回空
        return [];
    }
    
    foreach ($apiList as $api) {
        $res = curlHelper($urlDefault . $api, "POST", json_encode($urlData), $urlHeader, "", "", 5)['body'];
        $res = json_decode($res, true);
        if (!empty($res['list'] ?? [])) {
            foreach ($res['list'] as $value) {
                if (preg_match($pattern[$type], $value['answer'], $matches)) {
                    $link = $matches[0];
                    if (preg_match('/提取码[:：]?\s*([a-zA-Z0-9]{4})/', $value['answer'], $codeMatch)) {
                        $link .= '?pwd=' . $codeMatch[1];
                    }
                    $titleText = preg_replace('/\s*[\(（]?(夸克|百度)?[\)）]?\s*/u', '', $value['question']);
                    if ($isStoken && $type == 0) {
                        $infoData = verificationUrl($link);
                        if (!empty($infoData['stoken'])) {
                            $url2[] = [
                                'title' => $titleText,
                                'url' => $link,
                                'stoken' => $infoData['stoken']
                            ];
                        }
                    } else {
                        $url2[] = [
                            'title' => $titleText,
                            'url' => $link
                        ];
                    }
                    if (count($url2) >= $maxCount) {
                        return $url2;
                    }
                }
            }
        }
    }
    return $url2;
}


/**
 * 网络资源搜索源二
 * @return array
 */
function sourceData2($isStoken, $title, $type = 0, $maxCount = 100)
{
    // 根据类型选择搜索参数
    $panType = [
        0 => 'quark',   // 夸克
        2 => 'baidu'    // 百度
    ];

    if (!isset($panType[$type])) {
        return [];
    }
    
    $results = [];

    // 仅获取指定网盘的资源
    $url = 'https://www.pansearch.me/search?keyword='.urlencode($title).'&pan='.$panType[$type];
    $dom = getDom($url);
    $finder = new DomXPath($dom);
    
    // XPath 选择 class 包含 "whitespace-pre-wrap break-all" 的 div
    $nodes = $finder->query('//div[contains(concat(" ", normalize-space(@class), " "), " whitespace-pre-wrap ") and contains(concat(" ", normalize-space(@class), " "), " break-all ")]');

    foreach ($nodes as $node) {
        $content = $node->textContent;
        
        $parsedItem = [
            'title' => '',
            'url' => ''
        ];

        // 提取资源名称
        if (preg_match('/名称：(.*?)\n\n描述：/s', $content, $titleMatch)) {
            $parsedItem['title'] = trim($titleMatch[1]);
        } else {
            $parsedItem['title'] = $title;
        }

        // 定义不同类型的链接匹配规则（百度网盘包含提取码）
        $pattern = [
            0 => '/https:\/\/pan\.quark\.cn\/s\/[a-zA-Z0-9]+/', // 夸克
            2 => '/https:\/\/pan\.baidu\.com\/s\/[a-zA-Z0-9_-]+(\?pwd=[a-zA-Z0-9]+)?/' // 百度（包含提取码）
        ];

        // 提取下载链接
        if (preg_match($pattern[$type], $content, $urlMatch)) {
            $parsedItem['url'] = trim($urlMatch[0]);
        }

        if ($parsedItem['title'] && $parsedItem['url']) {
            // 只有标题包含搜索关键字才加入结果
            if (strpos($parsedItem['title'], $title) !== false || strpos($title, $parsedItem['title']) !== false) {
                if($isStoken && $type==0){
                    $infoData = verificationUrl($parsedItem['url']);
                    if(!empty($infoData['stoken'])){
                        $parsedItem['stoken'] = $infoData['stoken'];
                        $results[] = $parsedItem;
                    }  
                }else{
                    $results[] = $parsedItem;  
                }
            }
        }
        
        // 如果已获取的条目达到限制数量，退出循环
        if (count($results) >= $maxCount) {
            return $results;
        }
    }
    
    return $results;
}

/**
 * 验证夸克地址是否有效
 * @return array
 */
function verificationUrl($url) {
    $code = '';
    if (preg_match('/\?pwd=([^,\s&]+)/', $url, $pwdMatch)) {
        $code = trim($pwdMatch[1]);
    }
    $urlData = [
        'url' => $url,
        'code' => $code,
        'isType' => 1
    ];

    $transfer = new \netdisk\Transfer();
    $res = $transfer->transfer($urlData);

    if ($res['code'] !== 200) {
        return 0;
    }
    
    return $res['data'];
}


function encryptObject($object) {
    $jsonString = json_encode($object);
    // 使用 AES 加密算法加密 JSON 字符串
    $key = 'ABCD';  // 加密密钥
    $encrypted = openssl_encrypt($jsonString, 'aes-256-cbc', $key, 0, '1234567890123456');
    return $encrypted;
}
function decryptObject($encryptedObject) {
    // 解密密钥
    $key = 'ABCD';
    // 解密加密后的对象字符串
    $decryptedJson = openssl_decrypt($encryptedObject, 'aes-256-cbc', $key, 0, '1234567890123456');
    // 将解密后的 JSON 字符串转换回对象
    $object = json_decode($decryptedJson, true);  // true 转换为关联数组
    return $object;
}

/**
 * 资源类型识别规则（标题/链接关键词，不下载网盘文件）。
 * 首页专区、列表标签共用此规则。
 */
function getResourceKindRules()
{
    return [
        'video' => [
            'label' => '影视视频',
            'icon' => '▶',
            'subtitle' => '电影 / 剧集 / 动漫 / 纪录',
            'search' => '电影',
            'keywords' => ['.m3u8', '.mp4', '.mkv', '.avi', '.mov', '.wmv', '.flv', '.rmvb', '电影', '电视剧', '短剧', '综艺', '动漫', '纪录片', '剧集', '1080p', '4k', '4K', '蓝光', '国漫', '韩剧', '美剧', '日剧', '电影合集', '连续剧', '更新至', '更至', 'WEB-4K', '年番', '剧场版', 'S01', '第1'],
        ],
        'novel' => [
            'label' => '小说阅读',
            'icon' => '文',
            'subtitle' => 'TXT / EPUB / 网文 / 完结',
            'search' => '小说',
            // 注意：不要用孤立的「小说」——《刺杀小说家》是电影；强特征见 detectResourceKind
            'keywords' => ['.txt', '.epub', '.mobi', '.azw3', '.umd', '网文', '言情', '玄幻', '都市修真', '修仙小说', '完本', '全本', '电子书', 'txt全集', '精校', 'TXT', 'EPUB', '爽文', '系统流', '女频', '男频', '长篇小说', '网络小说', '小说txt', '小说下载', '小说合集'],
        ],
        'document' => [
            'label' => '学习文档',
            'icon' => '▤',
            'subtitle' => 'PDF / 课件 / 教程 / 资料',
            'search' => '教程',
            'keywords' => ['.pdf', '.doc', '.docx', '.ppt', '.pptx', '.xls', '.xlsx', '.md', '教程', '课件', '资料', '论文', '笔记', '讲义', '教材', '学习', '考试', 'pdf', 'PPT', '文档'],
        ],
        'software' => [
            'label' => '软件工具',
            'icon' => '⌘',
            'subtitle' => '应用 / 插件 / 源码',
            'search' => '软件',
            'keywords' => ['.exe', '.dmg', '.apk', '.ipa', '.msi', '.pkg', '.deb', '.rpm', '软件', '应用', '插件', '源码', '工具箱', '绿色版', '破解', '安装包', 'Windows', 'Mac', 'Android'],
        ],
        'archive' => [
            'label' => '压缩资源',
            'icon' => '▣',
            'subtitle' => 'ZIP / RAR / 合集包',
            'search' => '合集',
            'keywords' => ['.zip', '.rar', '.7z', '.tar', '.gz', '.iso', '合集', '打包', '网盘打包', '资源包'],
        ],
        'image' => [
            'label' => '图片素材',
            'icon' => '▣',
            'subtitle' => '壁纸 / 素材 / 设计',
            'search' => '壁纸',
            'keywords' => ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.psd', '.ai', '壁纸', '素材', '图片', '摄影', '图包', '表情包'],
        ],
    ];
}

/**
 * 去掉误写空格的关键词（防御）
 */
function normalizeKindKeywords(array $keywords)
{
    $out = [];
    foreach ($keywords as $kw) {
        $kw = trim((string) $kw);
        if ($kw === '') {
            continue;
        }
        $out[] = $kw;
    }
    return $out;
}

/**
 * 根据资源标题和链接推测资源内容类型。
 *
 * 这是展示层的轻量规则，不会读取或下载网盘中的文件；当分享链接只指向目录时，
 * 分类结果只能作为参考。后续可在转存后根据真实文件清单替换此结果。
 */
function detectResourceKind($title = '', $url = '') {
    $raw = trim($title . ' ' . $url);
    $text = mb_strtolower($raw, 'UTF-8');
    $rules = getResourceKindRules();

    // 扩展名硬信号（最高优先级，互斥）
    $hasNovelExt = (bool) preg_match('/\.(txt|epub|mobi|azw3|umd)(?:\b|$|[^\w])/iu', $text);
    $hasVideoExt = (bool) preg_match('/\.(mp4|mkv|m3u8|avi|mov|wmv|flv|rmvb)(?:\b|$|[^\w])/iu', $text);
    if ($hasNovelExt && !$hasVideoExt) {
        return ['key' => 'novel', 'label' => $rules['novel']['label']];
    }
    if ($hasVideoExt && !$hasNovelExt) {
        return ['key' => 'video', 'label' => $rules['video']['label']];
    }

    // 影视强信号（含「刺杀小说家」这类片名带「小说」但实际是电影）
    $videoHard = ['1080p', '720p', '4k', '蓝光', '电影', '电视剧', '短剧', '综艺', '动漫', '国漫', '剧场版', '更新至', '更至', '年番', 'web-4k', 'web-dl', 'hdr', '杜比', 'remux', '高码率', '第1季', '第2季', 's01', 's02', '全集完结版'];
    $hasVideoHard = false;
    foreach ($videoHard as $k) {
        if (mb_strpos($text, $k, 0, 'UTF-8') !== false) {
            $hasVideoHard = true;
            break;
        }
    }

    // 小说强信号（不含孤立「小说」，避免电影片名误伤）
    $novelHard = ['完本', '全本', '网文', '电子书', '精校', '女频', '男频', '网络小说', '小说txt', 'txt全集', '小说下载', '小说合集', '修仙小说', '言情小说', '玄幻小说', '万字', '分卷阅读', '作者:'];
    $hasNovelHard = false;
    foreach ($novelHard as $k) {
        if (mb_strpos($text, $k, 0, 'UTF-8') !== false) {
            $hasNovelHard = true;
            break;
        }
    }
    // 搜索源常把格式放在标题最前面，例如「TXT的育儿日记」。TXT 必须是
    // 独立格式标记，不能因为普通英文单词中碰巧含有 txt 就误判。
    if (!$hasNovelHard && preg_match('/(?:^|[\s\[【(（_\-])txt(?:版|电子书|小说|全集|全本|完本|的|$|[\s\]】)）_\-])/iu', $raw)) {
        $hasNovelHard = true;
    }
    // 独立词「 小说 / 小说 」且无影视硬特征
    if (!$hasNovelHard && !$hasVideoHard) {
        if (preg_match('/(?:^|[\s\[【(（_\-])小说(?:$|[\s\]】)）_\-])/u', $raw)
            || preg_match('/小说完整版|小说全集|长篇小说|网络小说/u', $raw)) {
            $hasNovelHard = true;
        }
    }

    if ($hasNovelHard && !$hasVideoHard) {
        return ['key' => 'novel', 'label' => $rules['novel']['label']];
    }
    if ($hasVideoHard && !$hasNovelHard) {
        return ['key' => 'video', 'label' => $rules['video']['label']];
    }
    // 双硬信号：扩展名已处理；其余优先视频规格词
    if ($hasNovelHard && $hasVideoHard) {
        if (preg_match('/1080p|4k|蓝光|web-?dl|remux|更新至/iu', $text)) {
            return ['key' => 'video', 'label' => $rules['video']['label']];
        }
        return ['key' => 'novel', 'label' => $rules['novel']['label']];
    }

    // 计分匹配，避免「小说」一词把影视片名误判成小说
    $scores = [];
    foreach ($rules as $kind => $rule) {
        $scores[$kind] = 0;
        foreach (normalizeKindKeywords($rule['keywords'] ?? []) as $keyword) {
            $kw = mb_strtolower((string) $keyword, 'UTF-8');
            if ($kw === '' || mb_strpos($text, $kw, 0, 'UTF-8') === false) {
                continue;
            }
            // 扩展名权重更高
            $w = (isset($kw[0]) && $kw[0] === '.') ? 5 : 2;
            // 孤立「小说」权重压低；若已有影视硬特征则直接忽略
            if ($kw === '小说' || $kw === '长篇小说') {
                if ($hasVideoHard) {
                    continue;
                }
                $w = 1;
            }
            $scores[$kind] += $w;
        }
    }
    // 并列时：有明确文档/软件特征则不抢视频；否则视频优先（资源站以影视为主）
    $order = ['novel', 'document', 'software', 'archive', 'image', 'video'];
    $best = 'other';
    $bestScore = 0;
    foreach ($order as $kind) {
        $s = $scores[$kind] ?? 0;
        if ($s > $bestScore) {
            $bestScore = $s;
            $best = $kind;
        }
    }
    // 视频与小说同分：无规格词则其他，有规格则视频
    if ($bestScore > 0 && ($scores['video'] ?? 0) === ($scores['novel'] ?? 0)
        && ($scores['video'] ?? 0) === $bestScore) {
        $best = $hasVideoHard ? 'video' : ($hasNovelHard ? 'novel' : 'video');
    }
    if ($bestScore <= 0) {
        return ['key' => 'other', 'label' => '其他'];
    }
    return ['key' => $best, 'label' => $rules[$best]['label'] ?? '其他'];
}

/**
 * 分类栏目是人工维护的强信号。优先使用栏目隔离影视与小说，避免同名作品
 * （例如小说和影视改编）共用错误的详情源或海报缓存。
 */
function detectResourceKindWithCategory($title = '', $url = '', $categoryName = '')
{
    $category = mb_strtolower(trim((string) $categoryName), 'UTF-8');
    if ($category !== '') {
        if (preg_match('/小说|网文|电子书|书籍|文学|读书/u', $category)) {
            return ['key' => 'novel', 'label' => '小说阅读'];
        }
        if (preg_match('/影视|电影|电视剧|剧集|短剧|动漫|动画|国漫|综艺|纪录片/u', $category)) {
            return ['key' => 'video', 'label' => '影视视频'];
        }
    }
    return detectResourceKind($title, $url);
}

/**
 * 从标题 + 网盘链接自动提取资源信息（不读网盘文件）
 * 用于本地库「无人工详情」时即时生成可看的详情卡
 *
 * @param string $title
 * @param string $url
 * @param int|string $isType 0夸克 1阿里 2百度 3UC 4迅雷
 * @param string $code 提取码
 * @return array
 */
function buildResourceAutoDetail($title = '', $url = '', $isType = 0, $code = '')
{
    $title = trim((string) $title);
    $url = trim((string) $url);
    $code = trim((string) $code);
    $isType = (int) $isType;

    $panMap = [
        0 => '夸克网盘',
        1 => '阿里云盘',
        2 => '百度网盘',
        3 => 'UC网盘',
        4 => '迅雷网盘',
    ];
    // 若 is_type 不可靠，再从 URL 猜
    if ($url !== '') {
        $u = mb_strtolower($url, 'UTF-8');
        if (strpos($u, 'quark.cn') !== false || strpos($u, 'pan.quark') !== false) {
            $isType = 0;
        } elseif (strpos($u, 'alipan.com') !== false || strpos($u, 'aliyundrive.com') !== false) {
            $isType = 1;
        } elseif (strpos($u, 'pan.baidu.com') !== false || strpos($u, 'baidu.com/s/') !== false) {
            $isType = 2;
        } elseif (strpos($u, 'drive.uc.cn') !== false || strpos($u, 'fast.uc.cn') !== false) {
            $isType = 3;
        } elseif (strpos($u, 'pan.xunlei.com') !== false || strpos($u, 'xunlei.com') !== false) {
            $isType = 4;
        }
    }
    $panName = $panMap[$isType] ?? '网盘';

    $kind = function_exists('detectResourceKind')
        ? detectResourceKind($title, $url)
        : ['key' => 'other', 'label' => '其他'];

    $year = '';
    if (preg_match('/(?:^|[^\d])((?:19|20)\d{2})(?:[^\d]|$)/u', $title, $m)) {
        $year = $m[1];
    }

    $qualities = [];
    $qualityRules = [
        '4K' => ['4k', '2160p', 'uhd'],
        '1080P' => ['1080p', '1080', 'fhd'],
        '720P' => ['720p', '720'],
        'HDR' => ['hdr10+', 'hdr10', 'hdr'],
        '杜比视界' => ['杜比视界', 'dolby vision', 'dv'],
        '杜比全景声' => ['杜比全景声', 'atmos', 'ddp7.1', 'dts:x'],
        '高码率' => ['高码率', 'hq', '高码'],
        '60帧' => ['60帧', '60fps'],
        'REMUX' => ['remux'],
        'WEB-DL' => ['web-dl', 'webdl', 'web-4k', 'webrip'],
        '蓝光' => ['蓝光', 'bluray', 'blu-ray', 'bdrip'],
    ];
    $titleLower = mb_strtolower($title, 'UTF-8');
    foreach ($qualityRules as $label => $keys) {
        foreach ($keys as $k) {
            if (mb_strpos($titleLower, $k, 0, 'UTF-8') !== false) {
                $qualities[] = $label;
                break;
            }
        }
    }
    $qualities = array_values(array_unique($qualities));

    $episode = '';
    if (preg_match('/更新至\s*第?\s*(\d+)\s*集/u', $title, $m)) {
        $episode = '更新至第' . $m[1] . '集';
    } elseif (preg_match('/第\s*(\d+)\s*[-~到至]\s*(\d+)\s*集/u', $title, $m)) {
        $episode = '第' . $m[1] . '-' . $m[2] . '集';
    } elseif (preg_match('/全\s*(\d+)\s*集/u', $title, $m)) {
        $episode = '全' . $m[1] . '集';
    } elseif (preg_match('/共\s*(\d+)\s*集/u', $title, $m)) {
        $episode = '共' . $m[1] . '集';
    } elseif (preg_match('/(\d+)\s*集/u', $title, $m)) {
        $episode = $m[1] . '集';
    } elseif (preg_match('/S(\d{1,2})E(\d{1,3})(?:\s*[-~]\s*E?(\d{1,3}))?/iu', $title, $m)) {
        $episode = !empty($m[3])
            ? ('S' . $m[1] . 'E' . $m[2] . '-' . $m[3])
            : ('S' . $m[1] . 'E' . $m[2]);
    }

    $size = '';
    if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|G|MB|TB|T)\b/iu', $title, $m)) {
        $unit = strtoupper($m[2]);
        if ($unit === 'G') {
            $unit = 'GB';
        }
        if ($unit === 'T') {
            $unit = 'TB';
        }
        $size = $m[1] . $unit;
    }

    $langs = [];
    $langRules = [
        '国语' => ['国语', '国配', '中配'],
        '粤语' => ['粤语'],
        '英语' => ['英语', '英文'],
        '中字' => ['中字', '中文字幕', '简中', '繁中', '简繁'],
        '双语' => ['双语', '双语音轨'],
    ];
    foreach ($langRules as $label => $keys) {
        foreach ($keys as $k) {
            if (mb_strpos($title, $k, 0, 'UTF-8') !== false) {
                $langs[] = $label;
                break;
            }
        }
    }
    $langs = array_values(array_unique($langs));

    // 干净片名：去掉括号/【】内的规格噪声，保留主名
    $clean = $title;
    $clean = preg_replace('/【[^】]*】/u', ' ', $clean);
    $clean = preg_replace('/\[[^\]]*\]/u', ' ', $clean);
    $clean = preg_replace('/（[^）]*）/u', ' ', $clean);
    $clean = preg_replace('/\([^)]*\)/u', ' ', $clean);
    $clean = preg_replace('/\s+/u', ' ', trim((string) $clean));
    // 详情检索必须使用核心名称。网盘标题常带 4K、HDR、更新至、集数等
    // 包装信息，直接拿整串请求公开资料源会显著降低命中率。
    if ($clean !== '' && function_exists('extractPosterSearchQueries')) {
        $detailQueries = extractPosterSearchQueries($clean, $year);
        if (!empty($detailQueries[0])) {
            $clean = trim((string) $detailQueries[0]);
        }
    }
    if ($clean === '') {
        $clean = $title;
    }
    // 截断过长
    if (mb_strlen($clean, 'UTF-8') > 80) {
        $clean = mb_substr($clean, 0, 80, 'UTF-8') . '…';
    }

    // 分享 ID（仅展示，不请求网盘）
    $shareId = '';
    if (preg_match('#/(?:s|t)/([a-zA-Z0-9_-]{5,})#', $url, $m)) {
        $shareId = $m[1];
    } elseif (preg_match('#[?&]surl=([a-zA-Z0-9_-]+)#', $url, $m)) {
        $shareId = $m[1];
    }

    // 标题里自带的提取码
    if ($code === '' && preg_match('/提取码[:：\s]*([a-zA-Z0-9]{3,8})/u', $title, $m)) {
        $code = $m[1];
    }
    if ($code === '' && preg_match('/[?&]pwd=([a-zA-Z0-9]+)/', $url, $m)) {
        $code = $m[1];
    }

    // 自动生成简介（纯本地规则，不调外部 API）
    $bits = [];
    $bits[] = '「' . $clean . '」';
    $bits[] = '类型：' . ($kind['label'] ?? '其他');
    $bits[] = '网盘：' . $panName;
    if ($year !== '') {
        $bits[] = '年份：' . $year;
    }
    if ($episode !== '') {
        $bits[] = '进度：' . $episode;
    }
    if (!empty($qualities)) {
        $bits[] = '规格：' . implode(' / ', array_slice($qualities, 0, 6));
    }
    if ($size !== '') {
        $bits[] = '体量：' . $size;
    }
    if (!empty($langs)) {
        $bits[] = '音轨/字幕：' . implode('、', $langs);
    }
    if ($code !== '') {
        $bits[] = '提取码：' . $code;
    }
    $bits[] = '信息由标题与分享链接自动识别生成，未读取网盘内文件清单。';
    $summary = implode(' · ', $bits);

    $tags = array_values(array_filter(array_merge(
        [$kind['label'] ?? ''],
        $year !== '' ? [$year] : [],
        $episode !== '' ? [$episode] : [],
        $qualities,
        $langs,
        $size !== '' ? [$size] : [],
        [$panName]
    )));

    return [
        'clean_title' => $clean,
        'resource_kind' => $kind['key'] ?? 'other',
        'resource_kind_label' => $kind['label'] ?? '其他',
        'pan_name' => $panName,
        'is_type' => $isType,
        'year' => $year,
        'episode' => $episode,
        'size' => $size,
        'qualities' => $qualities,
        'langs' => $langs,
        'tags' => $tags,
        'share_id' => $shareId,
        'code' => $code,
        'summary' => $summary,
        'auto_generated' => true,
    ];
}

/**
 * 在线检索公开百科摘要（维基百科 API，无需 key）
 * - 仅作详情页增强；匹配不准时返回空，不强行套错条目
 * - 结果缓存到 runtime，避免每次打开详情都请求外网
 *
 * @param string $cleanTitle 清洗后的主标题
 * @param string $kindKey video/novel/...
 * @param string $year
 * @return array{ok:bool,title?:string,summary?:string,image?:string,url?:string,source?:string}
 */
function fetchResourceOnlineMeta($cleanTitle = '', $kindKey = 'other', $year = '')
{
    $cleanTitle = trim((string) $cleanTitle);
    if ($cleanTitle === '' || mb_strlen($cleanTitle, 'UTF-8') < 2) {
        return ['ok' => false];
    }

    // 再收一次查询词：去掉常见规格噪声
    $query = $cleanTitle;
    $query = preg_replace('/\b(4K|1080P|720P|HDR10\+?|HDR|WEB-?DL|REMUX|蓝光|高码率|杜比[^\s]*)\b/iu', ' ', $query);
    $query = preg_replace('/第?\d+集|更新至|全\d+集|S\d+E\d+/iu', ' ', $query);
    $query = preg_replace('/\s+/u', ' ', trim((string) $query));
    // 取主名（中文名常在前半）
    if (preg_match('/^([\x{4e00}-\x{9fff}A-Za-z0-9·：:]{2,40})/u', $query, $m)) {
        $query = trim($m[1], " ·：:");
    }
    if ($query === '') {
        $query = $cleanTitle;
    }

    $cacheKey = 'online_meta_v1_' . md5(mb_strtolower($query . '|' . $kindKey . '|' . $year, 'UTF-8'));
    $cacheDir = root_path('runtime/cache');
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 7 * 86400)) {
        $cached = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $suffixes = [''];
    if ($kindKey === 'video') {
        $suffixes = [' 电影', ' 电视剧', ' 动画', ''];
    } elseif ($kindKey === 'novel') {
        $suffixes = [' 小说', ''];
    }

    $candidates = [];
    foreach (['zh.wikipedia.org', 'en.wikipedia.org'] as $host) {
        foreach ($suffixes as $suf) {
            $sr = trim($query . $suf);
            if ($year !== '' && $host === 'zh.wikipedia.org') {
                // 年份辅助消歧，但不强制
                $tryList = [$sr, $sr . ' ' . $year];
            } else {
                $tryList = [$sr];
            }
            foreach ($tryList as $srq) {
                $searchUrl = 'https://' . $host . '/w/api.php?' . http_build_query([
                    'action' => 'query',
                    'list' => 'search',
                    'srsearch' => $srq,
                    'srlimit' => 5,
                    'format' => 'json',
                    'utf8' => 1,
                ]);
                $data = resourceHttpGetJson($searchUrl, 3.5);
                $hits = $data['query']['search'] ?? [];
                foreach ($hits as $hit) {
                    $t = (string) ($hit['title'] ?? '');
                    if ($t === '') {
                        continue;
                    }
                    $score = resourceTitleSimilarity($query, $t);
                    // 消歧义页降权
                    if (mb_strpos($t, '消歧義', 0, 'UTF-8') !== false || mb_strpos($t, 'disambiguation', 0, 'UTF-8') !== false) {
                        $score -= 30;
                    }
                    $candidates[] = [
                        'host' => $host,
                        'title' => $t,
                        'score' => $score,
                        'snippet' => strip_tags((string) ($hit['snippet'] ?? '')),
                    ];
                }
                if (!empty($hits)) {
                    break 2; // 该 host 已有结果，换下一个 host 前先评估
                }
            }
        }
    }

    usort($candidates, function ($a, $b) {
        return ($b['score'] <=> $a['score']);
    });

    $best = $candidates[0] ?? null;
    // 匹配阈值不足则放弃（避免「光阴之外」错配作家条目）
    if (!$best || $best['score'] < 58) {
        $miss = ['ok' => false, 'reason' => 'low_score', 'query' => $query];
        @file_put_contents($cacheFile, json_encode($miss, JSON_UNESCAPED_UNICODE));
        return $miss;
    }

    $pageUrl = 'https://' . $best['host'] . '/w/api.php?' . http_build_query([
        'action' => 'query',
        'prop' => 'extracts|pageimages|info',
        'inprop' => 'url',
        'exintro' => 1,
        'explaintext' => 1,
        'exchars' => 420,
        'piprop' => 'thumbnail',
        'pithumbsize' => 480,
        'titles' => $best['title'],
        'format' => 'json',
        'utf8' => 1,
        'redirects' => 1,
    ]);
    $pageData = resourceHttpGetJson($pageUrl, 4.0);
    $pages = $pageData['query']['pages'] ?? [];
    $page = null;
    foreach ($pages as $p) {
        if (!empty($p) && empty($p['missing'])) {
            $page = $p;
            break;
        }
    }
    if (!$page) {
        $miss = ['ok' => false, 'reason' => 'no_page', 'query' => $query];
        @file_put_contents($cacheFile, json_encode($miss, JSON_UNESCAPED_UNICODE));
        return $miss;
    }

    $extract = trim((string) ($page['extract'] ?? ''));
    // 过短或明显无关
    if (mb_strlen($extract, 'UTF-8') < 20) {
        $miss = ['ok' => false, 'reason' => 'short_extract', 'query' => $query];
        @file_put_contents($cacheFile, json_encode($miss, JSON_UNESCAPED_UNICODE));
        return $miss;
    }
    // 截断到合适长度
    if (mb_strlen($extract, 'UTF-8') > 480) {
        $extract = mb_substr($extract, 0, 480, 'UTF-8') . '…';
    }

    $ok = [
        'ok' => true,
        'title' => (string) ($page['title'] ?? $best['title']),
        'summary' => $extract,
        'image' => (string) (($page['thumbnail']['source'] ?? '') ?: ''),
        'url' => (string) ($page['fullurl'] ?? ('https://' . $best['host'] . '/wiki/' . rawurlencode(str_replace(' ', '_', $best['title'])))),
        'source' => ($best['host'] === 'zh.wikipedia.org' ? '中文维基百科' : '英文维基百科'),
        'score' => $best['score'],
        'query' => $query,
    ];
    @file_put_contents($cacheFile, json_encode($ok, JSON_UNESCAPED_UNICODE));
    return $ok;
}

/**
 * 简易 HTTP JSON GET（带超时与 UA）
 * @param array $extraHeaders 额外请求头，如 ['Referer: https://movie.douban.com/']
 */
function resourceHttpGetJson($url, $timeout = 4.0, array $extraHeaders = [])
{
    $timeout = max(1.5, min(8.0, (float) $timeout));
    $headers = array_merge([
        'Accept: application/json, text/javascript, */*; q=0.01',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ], $extraHeaders);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => (int) ceil($timeout),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            // 关键：不自动解压时豆瓣返回 gzip 魔数，json 解析必失败 → 全站只剩「自动封面」
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            return [];
        }
        // 少数环境仍可能拿到 gzip 裸流
        if (strlen($body) >= 2 && ord($body[0]) === 0x1f && ord($body[1]) === 0x8b && function_exists('gzdecode')) {
            $decoded = @gzdecode($body);
            if ($decoded !== false) {
                $body = $decoded;
            }
        }
        $data = json_decode($body, true);
        // 豆瓣 suggest 顶层是数组；失败页是 HTML
        if (!is_array($data)) {
            return [];
        }
        return $data;
    }

    $headerStr = '';
    foreach ($headers as $h) {
        $headerStr .= $h . "\r\n";
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'header' => $headerStr,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        return [];
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
}

/**
 * 将可信公开资料源的海报缓存到本站，解决豆瓣/Bangumi 防盗链导致浏览器图片失败。
 * 仅允许固定图片域名，避免变成任意 URL 代理。
 */
function cachePublicPosterLocally($url = '')
{
    $url = trim((string) $url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return '';
    }
    $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST), 'UTF-8');
    $allowed = ['doubanio.com', 'douban.com', 'bgm.tv', 'tmdb.org', 'wikimedia.org', 'wikipedia.org'];
    $hostOk = false;
    foreach ($allowed as $suffix) {
        $suffixWithDot = '.' . $suffix;
        if ($host === $suffix || substr($host, -strlen($suffixWithDot)) === $suffixWithDot) {
            $hostOk = true;
            break;
        }
    }
    if (!$hostOk || !function_exists('curl_init')) {
        return '';
    }

    $dir = public_path('runtime/posters');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    // 每天最多清理一次：删除 30 天未使用的图片，并将缓存控制在 1000 张内。
    $cleanupStamp = root_path('runtime/cache') . DIRECTORY_SEPARATOR . 'poster_cleanup.stamp';
    if (!is_file($cleanupStamp) || time() - (int) @filemtime($cleanupStamp) > 86400) {
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.{jpg,png,webp,gif}', GLOB_BRACE) ?: [];
        foreach ($files as $cachedFile) {
            if (is_file($cachedFile) && time() - (int) @filemtime($cachedFile) > 30 * 86400) {
                @unlink($cachedFile);
            }
        }
        $files = array_values(array_filter(
            glob($dir . DIRECTORY_SEPARATOR . '*.{jpg,png,webp,gif}', GLOB_BRACE) ?: [],
            'is_file'
        ));
        if (count($files) > 1000) {
            usort($files, static function ($a, $b) {
                return ((int) @filemtime($a)) <=> ((int) @filemtime($b));
            });
            foreach (array_slice($files, 0, count($files) - 1000) as $oldFile) {
                @unlink($oldFile);
            }
        }
        if (!is_dir(dirname($cleanupStamp))) {
            @mkdir(dirname($cleanupStamp), 0755, true);
        }
        @touch($cleanupStamp);
    }
    $key = hash('sha256', $url);
    foreach (['jpg', 'png', 'webp', 'gif'] as $ext) {
        $existing = $dir . DIRECTORY_SEPARATOR . $key . '.' . $ext;
        if (is_file($existing) && filesize($existing) > 128) {
            return '/runtime/posters/' . basename($existing);
        }
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        // 不跟随重定向，避免允许域名把请求跳转到内网或未知主机。
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            'Referer: https://movie.douban.com/',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        ],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($body) || $code >= 400 || strlen($body) < 128 || strlen($body) > 5 * 1024 * 1024) {
        return '';
    }
    $info = @getimagesizefromstring($body);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    $ext = is_array($info) ? ($types[$info[2] ?? 0] ?? '') : '';
    if ($ext === '') {
        return '';
    }
    $file = $dir . DIRECTORY_SEPARATOR . $key . '.' . $ext;
    if (@file_put_contents($file, $body, LOCK_EX) === false) {
        return '';
    }
    return '/runtime/posters/' . basename($file);
}

/**
 * 从杂乱网盘标题提取海报搜索关键词（多候选）
 * @return string[]
 */
function extractPosterSearchQueries($title = '', $year = '')
{
    $title = trim((string) $title);
    if ($title === '') {
        return [];
    }
    $t = $title;
    // 去网盘包装符号与规格噪声
    $t = preg_replace('/【[^】]*】/u', ' ', $t);
    $t = preg_replace('/\[[^\]]*\]/u', ' ', $t);
    $t = preg_replace('/（[^）]*）/u', ' ', $t);
    $t = preg_replace('/\([^)]*\)/u', ' ', $t);
    $t = preg_replace('/\{[^}]*\}/u', ' ', $t);
    $t = preg_replace('/\b(4K|2160[Pp]|1080[Pp]|720[Pp]|HDR10\+?|HDR|SDR|WEB-?DL|WEB-?4K|WEBRip|REMUX|BluRay|BDRip|高码率|高码|杜比视界|杜比全景声|Atmos|DDP\d?(?:\.\d)?|DTS(?:-?HD)?|HIFI|DV|HQ|60帧|60fps|内封|简繁|双语|特效字幕|中字|国语|粤语|英语)\b/iu', ' ', $t);
    $t = preg_replace('/(?:更新|更)(?:至)?\s*(?:第)?\d+(?:集)?|更新完结|已完结|全\d+集|共\d+集|第\d+[-~到至]\d+集|第\d+集|\d+集|EP?\d+|年番\d*|S\d{1,2}E\d{1,3}(?:\s*[-~]\s*E?\d{1,3})?/iu', ' ', $t);
    $t = preg_replace('/[#🗄📦💾📁🏷🛍🔍⬇️·|｜\/\\\\]+/u', ' ', $t);
    // 搜索接口常把字段标签直接拼进标题，例如「动漫名称：光阴之外」。
    // 这类标签若不去掉，公开资料源会把整串当片名，导致详情和海报均 miss。
    $t = preg_replace('/^\s*(?:(?:资源|动漫|动画|影视|电影|电视剧|剧集|视频|小说|书籍|文档|软件)\s*)?(?:名称|标题|片名|书名|资源名)\s*[：:]\s*/u', '', $t);
    $t = preg_replace('/^[\p{So}\p{Sk}\p{Cf}\s]+/u', '', $t);
    $t = preg_replace('/\s+/u', ' ', trim((string) $t));

    $queries = [];
    if ($t !== '') {
        $queries[] = $t;
    }
    // 主名：中文/字母开头连续片段
    if (preg_match('/^([\x{4e00}-\x{9fff}A-Za-z0-9：:·\s]{2,40})/u', $t, $m)) {
        $core = trim($m[1], " ：:\t");
        // 去掉末尾孤立年份
        $core = preg_replace('/\s*(19|20)\d{2}\s*$/u', '', $core);
        $core = trim((string) $core);
        if ($core !== '' && $core !== $t) {
            $queries[] = $core;
        }
        // 去掉季数后缀「3」「第三季」便于匹配第一部海报
        $base = preg_replace('/\s*[第]?\s*[一二三四五六七八九十\d]+\s*[季部章]?$/u', '', $core);
        $base = trim((string) $base);
        if ($base !== '' && mb_strlen($base, 'UTF-8') >= 2 && $base !== $core) {
            $queries[] = $base;
        }
    }
    // 冒号/之 前半
    if (preg_match('/^(.+?)[：:](.+)$/u', $t, $m)) {
        $left = trim($m[1]);
        if (mb_strlen($left, 'UTF-8') >= 2) {
            $queries[] = $left;
        }
    }

    // 去重保序
    $out = [];
    $seen = [];
    foreach ($queries as $q) {
        $q = trim((string) $q);
        if ($q === '' || mb_strlen($q, 'UTF-8') < 2) {
            continue;
        }
        $k = mb_strtolower($q, 'UTF-8');
        if (isset($seen[$k])) {
            continue;
        }
        $seen[$k] = true;
        $out[] = $q;
        // 带年份再试一轮
        if ($year !== '' && !preg_match('/(19|20)\d{2}/', $q)) {
            $out[] = $q . ' ' . $year;
        }
    }
    return array_slice($out, 0, 6);
}

/**
 * 豆瓣海报 URL 升到较大尺寸
 */
function upgradeDoubanPosterUrl($url)
{
    $url = (string) $url;
    if ($url === '') {
        return '';
    }
    $url = preg_replace('#^http://#i', 'https://', $url);
    // s_ratio_poster / m_ratio_poster → l_ratio_poster
    $url = str_replace(
        ['/s_ratio_poster/', '/m_ratio_poster/', '/sqxs/', '/small/'],
        ['/l_ratio_poster/', '/l_ratio_poster/', '/l_ratio_poster/', '/l_ratio_poster/'],
        $url
    );
    return $url;
}

/**
 * 根据片名生成兜底海报（SVG data URI），保证详情永远有图
 */
function buildFallbackPosterDataUri($title = '', $kindKey = 'other')
{
    $title = trim((string) $title);
    if ($title === '') {
        $title = '资源';
    }
    // 展示短标题
    $show = $title;
    if (mb_strlen($show, 'UTF-8') > 14) {
        $show = mb_substr($show, 0, 14, 'UTF-8') . '…';
    }
    $colors = [
        'video' => ['#1e1b4b', '#4c1d95', '#c4b5fd'],
        'novel' => ['#1e3a5f', '#1d4ed8', '#93c5fd'],
        'document' => ['#064e3b', '#047857', '#6ee7b7'],
        'software' => ['#0c4a6e', '#0369a1', '#7dd3fc'],
        'archive' => ['#7c2d12', '#c2410c', '#fdba74'],
        'image' => ['#581c87', '#a21caf', '#e9d5ff'],
        'other' => ['#1f2937', '#4b5563', '#d1d5db'],
    ];
    $c = $colors[$kindKey] ?? $colors['other'];
    $safe = htmlspecialchars($show, ENT_QUOTES | ENT_XML1, 'UTF-8');
    // 多行：每行约 7 字
    $lines = [];
    $tmp = $show;
    while ($tmp !== '' && count($lines) < 3) {
        $lines[] = mb_substr($tmp, 0, 7, 'UTF-8');
        $tmp = mb_substr($tmp, 7, null, 'UTF-8');
    }
    $tspans = '';
    $y = 200 - (count($lines) - 1) * 14;
    foreach ($lines as $i => $line) {
        $yy = $y + $i * 28;
        $tspans .= '<tspan x="150" y="' . $yy . '">' . htmlspecialchars($line, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</tspan>';
    }
    $svg = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450" viewBox="0 0 300 450">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="' . $c[0] . '"/><stop offset="100%" stop-color="' . $c[1] . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="300" height="450" fill="url(#g)"/>'
        . '<circle cx="150" cy="120" r="42" fill="none" stroke="' . $c[2] . '" stroke-width="2" opacity="0.55"/>'
        . '<text x="150" y="128" text-anchor="middle" fill="' . $c[2] . '" font-size="22" font-family="sans-serif" opacity="0.95">'
        . htmlspecialchars(
            ['video' => '▶', 'novel' => '文', 'document' => 'PDF', 'software' => 'APP', 'archive' => 'ZIP', 'image' => 'IMG', 'other' => '·'][$kindKey] ?? '·',
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        )
        . '</text>'
        . '<text text-anchor="middle" fill="#fff" font-size="18" font-weight="700" font-family="PingFang SC,Microsoft YaHei,sans-serif">' . $tspans . '</text>'
        . '<text x="150" y="400" text-anchor="middle" fill="' . $c[2] . '" font-size="12" font-family="sans-serif" opacity="0.85">'
        . htmlspecialchars(
            ['video' => '影视', 'novel' => '小说', 'document' => '文档', 'software' => '软件', 'archive' => '压缩包', 'image' => '图片', 'other' => '资源'][$kindKey] ?? '资源',
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        )
        . '</text>'
        . '<text x="150" y="422" text-anchor="middle" fill="' . $c[2] . '" font-size="10" font-family="sans-serif" opacity="0.65">模块封面</text>'
        . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * 预热单条资源详情缓存（搜索列表后台调用）
 * 预写 basic_info / poster 缓存与 vod_pic，用户点进 /d/{id} 时基本秒开
 *
 * @param int $sourceId
 * @param \app\model\Source|null $model
 * @return array
 */
function warmResourceDetailById($sourceId, $model = null)
{
    $sourceId = (int) $sourceId;
    if ($sourceId <= 0) {
        return ['ok' => false, 'reason' => 'bad_id'];
    }

    try {
        if ($model === null) {
            if (class_exists('\\app\\model\\Source')) {
                $model = new \app\model\Source();
            } else {
                return ['ok' => false, 'reason' => 'no_model'];
            }
        }
        $row = $model->with('category')->where([
            ['source_id', '=', $sourceId],
            ['status', '=', 1],
            ['is_delete', '=', 0],
        ])->field('source_id,source_category_id,title,url,code,is_type,vod_pic,vod_content')->find();
        if (empty($row)) {
            return ['ok' => false, 'reason' => 'not_found'];
        }
        $title = trim((string) ($row['title'] ?? ''));
        $url = (string) ($row['url'] ?? '');
        $code = (string) ($row['code'] ?? '');
        $isType = (int) ($row['is_type'] ?? 0);
        $storedPic = !empty($row['vod_pic']) && strpos((string) $row['vod_pic'], 'data:') !== 0
            && strpos((string) $row['vod_pic'], 'http') === 0;

        $categoryName = (string) ($row['category']['name'] ?? '');
        $kind = function_exists('detectResourceKindWithCategory')
            ? detectResourceKindWithCategory($title, $url, $categoryName)
            : (function_exists('detectResourceKind')
                ? detectResourceKind($title, $url)
                : ['key' => 'other', 'label' => '其他']);
        $kindKey = $kind['key'] ?? 'other';
        $strictPosterKind = in_array($kindKey, ['video', 'novel'], true);
        $hadPic = $strictPosterKind ? false : $storedPic;

        $auto = function_exists('buildResourceAutoDetail')
            ? buildResourceAutoDetail($title, $url, $isType, $code)
            : [];
        if ($code === '' && !empty($auto['code'])) {
            $code = (string) $auto['code'];
        }
        $clean = (string) ($auto['clean_title'] ?? $title);
        $year = (string) ($auto['year'] ?? '');

        // 已有 basic_info 缓存则跳过外网
        $infoCached = false;
        $cacheKey = 'basic_info_v6_' . md5(mb_strtolower($clean . '|' . $kindKey . '|' . $year, 'UTF-8'));
        $cacheFile = root_path('runtime/cache') . DIRECTORY_SEPARATOR . $cacheKey . '.json';
        if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 7 * 86400)) {
            $c = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($c) && !empty($c['ok'])) {
                $ck = (string) ($c['kind'] ?? '');
                // 缓存 kind 必须与当前识别一致，否则作废重拉
                if ($ck === '' || $ck === $kindKey) {
                    $infoCached = true;
                } else {
                    @unlink($cacheFile);
                }
            }
        }

        $infoOk = $infoCached;
        $posterUrl = $hadPic ? (string) $row['vod_pic'] : '';

        // 全自动预热：按 kind 分流拉详情+海报（小说≠影视≠其它）
        // 即使详情缓存已存在也读取一次缓存内容，以便同步预热其类型正确的海报。
        if (function_exists('fetchResourceBasicInfo')) {
            $bi = fetchResourceBasicInfo($clean, $kindKey, $year, $url, is_array($auto) ? $auto : []);
            if (!empty($bi['ok'])) {
                $infoOk = true;
                $biKindOk = $strictPosterKind
                    ? ((string) ($bi['kind'] ?? '') === $kindKey)
                    : (empty($bi['kind']) || $bi['kind'] === $kindKey);
                if (!$hadPic && $biKindOk && !empty($bi['poster']) && strpos((string) $bi['poster'], 'http') === 0
                    && in_array($kindKey, ['video', 'novel'], true)
                ) {
                    $posterUrl = (string) $bi['poster'];
                }
            }
        }

        if ($posterUrl === '' && in_array($kindKey, ['video', 'novel'], true) && function_exists('fetchResourcePoster')) {
            // 海报同样严格按 kind（novel→读书封面，video→电影海报）
            $p = fetchResourcePoster($clean, $kindKey, $year, '', false);
            if ($p !== '' && strpos($p, 'http') === 0) {
                $posterUrl = $p;
            }
        }

        if ($posterUrl !== '' && strpos($posterUrl, 'http') === 0
            && (!$storedPic || (string) $row['vod_pic'] !== $posterUrl)) {
            try {
                $model->where('source_id', $sourceId)->update(['vod_pic' => $posterUrl]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $browserPoster = $posterUrl;
        if ($posterUrl !== '' && strpos($posterUrl, 'http') === 0 && function_exists('cachePublicPosterLocally')) {
            $localPoster = cachePublicPosterLocally($posterUrl);
            if ($localPoster !== '') {
                $browserPoster = $localPoster;
            }
        }

        // 轻量验链写缓存（不阻塞太久，失败无所谓）
        $linkStatus = ['status' => 'unknown', 'message' => '未检测'];
        if (function_exists('checkPanShareStatus') && $url !== '') {
            try {
                $linkStatus = checkPanShareStatus($url, $code);
                // unknown 多为临时网络/风控，短暂错峰后绕过缓存重试一次。
                if (($linkStatus['status'] ?? '') === 'unknown') {
                    usleep(180000);
                    $linkStatus = checkPanShareStatus($url, $code, true);
                }
                // 预热只记录检测结果，不自动修改资源状态。临时风控、账号失效或
                // 提取码错误都可能产生假阴性，永久下架必须交给后台复核流程。
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return [
            'ok' => true,
            'id' => $sourceId,
            'kind' => $kindKey,
            'info_ok' => $infoOk ? 1 : 0,
            'info_cached' => $infoCached ? 1 : 0,
            'poster' => $browserPoster,
            'link_status' => (string) ($linkStatus['status'] ?? 'unknown'),
            'link_message' => (string) ($linkStatus['message'] ?? ''),
            'warmed' => (!$infoCached || ($posterUrl !== '' && !$hadPic)) ? 1 : 0,
        ];
    } catch (\Throwable $e) {
        return ['ok' => false, 'reason' => 'exception', 'msg' => $e->getMessage()];
    }
}

/**
 * 按资源类型严格分流拉基本信息（禁止串台）：
 * - video  → 豆瓣影视 / BGM 动画
 * - novel  → 豆瓣读书 / BGM 书籍
 * - document / software / archive / image / other → 仅本地解析，绝不走影视/读书百科
 */
function fetchResourceBasicInfo($cleanTitle = '', $kindKey = 'other', $year = '', $url = '', array $autoMeta = [])
{
    $cleanTitle = trim((string) $cleanTitle);
    $kindKey = trim((string) $kindKey);
    if ($kindKey === '') {
        $kindKey = 'other';
    }
    if ($cleanTitle === '' || mb_strlen($cleanTitle, 'UTF-8') < 2) {
        return ['ok' => false];
    }

    // v6：按 kind 隔离，并采用去集数后的核心标题，杜绝小说/影视串台旧缓存。
    $cacheKey = 'basic_info_v6_' . md5(mb_strtolower($cleanTitle . '|' . $kindKey . '|' . $year, 'UTF-8'));
    $cacheDir = root_path('runtime/cache');
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 7 * 86400)) {
        $c = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($c) && !empty($c['ok'])) {
            // 强制 kind 一致：小说绝不能吃到影视字段
            $ck = (string) ($c['kind'] ?? '');
            if ($ck !== '' && $ck !== $kindKey) {
                @unlink($cacheFile);
            } elseif ($kindKey === 'novel' && (!empty($c['directors']) || !empty($c['actors']))) {
                @unlink($cacheFile);
            } elseif ($kindKey === 'video' && !empty($c['authors']) && empty($c['directors']) && empty($c['actors'])) {
                // 疑似图书缓存被标成 video
                @unlink($cacheFile);
            } else {
                return $c;
            }
        }
        // 外部资料源偶发风控/超时时不能把 miss 锁半小时；短缓存后允许重试。
        if (is_array($c) && empty($c['ok']) && (time() - filemtime($cacheFile) < 120)) {
            return $c;
        }
    }

    $queries = function_exists('extractPosterSearchQueries')
        ? extractPosterSearchQueries($cleanTitle, $year)
        : [$cleanTitle];
    if (empty($queries)) {
        $queries = [$cleanTitle];
    }

    $info = ['ok' => false];

    // 严格分流：小说只走读书源，影视只走电影源，其它只本地
    if ($kindKey === 'novel') {
        $info = fetchNovelBasicInfo($queries, $cleanTitle, $year);
        if (!empty($info['ok'])) {
            $info['kind'] = 'novel';
            $info['directors'] = [];
            $info['actors'] = [];
            $info['episodes'] = '';
            $info['duration'] = '';
            $info['role_label_director'] = '作者';
            $info['role_label_actor'] = '';
        }
    } elseif ($kindKey === 'video') {
        $info = fetchVideoBasicInfo($queries, $cleanTitle, $year, 'video');
        if (!empty($info['ok'])) {
            $info['kind'] = 'video';
            $info['authors'] = [];
            $info['role_label_director'] = '导演';
            $info['role_label_actor'] = '主演';
        }
    } else {
        // document / software / archive / image / other：本地规格信息，禁止影视/小说 API 串台
        $info = buildLocalModuleBasicInfo($cleanTitle, $url, $kindKey, $autoMeta);
        if (!empty($info['ok'])) {
            $info['kind'] = $kindKey;
            $info['directors'] = [];
            $info['actors'] = [];
            $info['authors'] = [];
        }
    }

    @file_put_contents($cacheFile, json_encode($info, JSON_UNESCAPED_UNICODE));
    return $info;
}

/**
 * 文档/软件/压缩/图片/其它：从标题与链接生成模块化基本信息（不请求影视 API）
 */
function buildLocalModuleBasicInfo($title, $url = '', $kindKey = 'other', array $autoMeta = [])
{
    $title = trim((string) $title);
    $url = trim((string) $url);
    $labels = [
        'document' => '学习文档',
        'software' => '软件工具',
        'archive' => '压缩资源',
        'image' => '图片素材',
        'other' => '其他资源',
        'novel' => '小说阅读',
        'video' => '影视视频',
    ];
    $kindLabel = $labels[$kindKey] ?? '资源';

    // 扩展名
    $ext = '';
    if (preg_match('/\.([a-z0-9]{2,5})(?:\s|$|["\')\]])/iu', $title . ' ' . $url, $m)) {
        $ext = strtolower($m[1]);
    }
    $formatMap = [
        'pdf' => 'PDF 文档', 'doc' => 'Word', 'docx' => 'Word', 'ppt' => 'PPT', 'pptx' => 'PPT',
        'xls' => 'Excel', 'xlsx' => 'Excel', 'md' => 'Markdown', 'txt' => '文本',
        'epub' => 'EPUB', 'mobi' => 'MOBI',
        'exe' => 'Windows 程序', 'msi' => 'Windows 安装包', 'dmg' => 'macOS 镜像', 'pkg' => 'macOS 安装包',
        'apk' => 'Android 应用', 'ipa' => 'iOS 应用', 'deb' => 'Debian 包', 'rpm' => 'RPM 包',
        'zip' => 'ZIP 压缩包', 'rar' => 'RAR 压缩包', '7z' => '7Z 压缩包', 'iso' => 'ISO 镜像',
        'jpg' => 'JPEG 图片', 'jpeg' => 'JPEG 图片', 'png' => 'PNG 图片', 'gif' => 'GIF', 'webp' => 'WebP', 'psd' => 'PSD',
        'mp4' => 'MP4 视频', 'mkv' => 'MKV 视频',
    ];
    $formatLabel = $ext !== '' ? ($formatMap[$ext] ?? (strtoupper($ext) . ' 文件')) : '';

    // 平台
    $platforms = [];
    $blob = mb_strtolower($title . ' ' . $url, 'UTF-8');
    $platRules = [
        'Windows' => ['windows', 'win10', 'win11', 'win7', '.exe', '.msi'],
        'macOS' => ['macos', 'mac os', 'osx', '.dmg', '.pkg'],
        'Android' => ['android', '.apk'],
        'iOS' => ['ios', '.ipa'],
        'Linux' => ['linux', '.deb', '.rpm'],
    ];
    foreach ($platRules as $name => $keys) {
        foreach ($keys as $k) {
            if (mb_strpos($blob, $k, 0, 'UTF-8') !== false) {
                $platforms[] = $name;
                break;
            }
        }
    }
    $platforms = array_values(array_unique($platforms));

    $size = (string) ($autoMeta['size'] ?? '');
    if ($size === '' && preg_match('/(\d+(?:\.\d+)?)\s*(GB|G|MB|TB|T)\b/iu', $title, $m)) {
        $u = strtoupper($m[2]);
        if ($u === 'G') {
            $u = 'GB';
        }
        if ($u === 'T') {
            $u = 'TB';
        }
        $size = $m[1] . $u;
    }

    $pan = (string) ($autoMeta['pan_name'] ?? '');
    $tags = [];
    if ($formatLabel !== '') {
        $tags[] = $formatLabel;
    }
    foreach ($platforms as $p) {
        $tags[] = $p;
    }
    if ($size !== '') {
        $tags[] = $size;
    }

    // 简介：模块说明 + 标题要点，绝不编造导演主演
    $bits = [];
    $bits[] = '类型：' . $kindLabel;
    if ($formatLabel !== '') {
        $bits[] = '格式：' . $formatLabel;
    }
    if (!empty($platforms)) {
        $bits[] = '平台：' . implode(' / ', $platforms);
    }
    if ($size !== '') {
        $bits[] = '体积：' . $size;
    }
    if ($pan !== '') {
        $bits[] = '网盘：' . $pan;
    }
    $bits[] = '信息由标题与链接本地解析，未套用影视/读书百科。';
    $intro = implode(' · ', $bits);

    return [
        'ok' => true,
        'kind' => $kindKey,
        'source' => '本地解析',
        'source_id' => '',
        'source_url' => '',
        'title' => $title,
        'original_title' => '',
        'year' => (string) ($autoMeta['year'] ?? ''),
        'card_subtitle' => $kindLabel . ($formatLabel !== '' ? ' · ' . $formatLabel : ''),
        'rating' => '',
        'rating_count' => 0,
        'genres' => $tags,
        'countries' => [],
        'languages' => [],
        'directors' => [],
        'authors' => [],
        'actors' => [],
        'episodes' => '',
        'duration' => '',
        'intro' => $intro,
        'poster' => '', // 封面走 kind 专用 SVG，不拉影视海报
        'subtype' => $ext,
        'score_match' => 100,
        'role_label_director' => '',
        'role_label_actor' => '',
        'file_format' => $formatLabel,
        'platforms' => $platforms,
        'file_size' => $size,
    ];
}

/**
 * 影视基本信息（豆瓣电影/剧集）
 */
function fetchVideoBasicInfo(array $queries, $cleanTitle, $year = '', $kindKey = 'video')
{
    // 影视同样全自动，无需后台开关
    $best = null;
    $bestScore = 0;
    foreach ($queries as $query) {
        $suggest = resourceHttpGetJson(
            'https://movie.douban.com/j/subject_suggest?q=' . rawurlencode($query),
            5.0,
            [
                'Referer: https://movie.douban.com/',
                'Origin: https://movie.douban.com',
                'X-Requested-With: XMLHttpRequest',
            ]
        );
        if (!is_array($suggest)) {
            continue;
        }
        $rows = isset($suggest[0]) || $suggest === [] ? $suggest : ($suggest['subjects'] ?? []);
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = (string) ($row['type'] ?? '');
            // 严格只要 movie/tv，拒绝 book 等
            if ($type !== '' && !in_array($type, ['movie', 'tv'], true)) {
                continue;
            }
            $name = (string) ($row['title'] ?? $row['sub_title'] ?? '');
            $sid = (string) ($row['id'] ?? '');
            if ($name === '' || $sid === '') {
                continue;
            }
            $score = resourceTitleSimilarity($query, $name);
            if ($year !== '' && !empty($row['year']) && (string) $row['year'] === (string) $year) {
                $score = min(100, $score + 10);
            }
            if (in_array($type, ['movie', 'tv'], true)) {
                $score = min(100, $score + 3);
            }
            if ($score > $bestScore && $score >= 50) {
                $bestScore = $score;
                $best = $row;
            }
        }
        if ($bestScore >= 88) {
            break;
        }
    }
    if (!$best || empty($best['id'])) {
        // Bangumi 动画 type=2 作影视补充（国漫）
        return fetchBangumiBasicInfo($queries, $cleanTitle, $year, [2, 6], 'video');
    }

    $sid = (string) $best['id'];
    $detail = null;
    foreach (['movie', 'tv', 'subject'] as $path) {
        $detail = resourceHttpGetJson(
            'https://m.douban.com/rexxar/api/v2/' . $path . '/' . rawurlencode($sid),
            6.0,
            [
                'Referer: https://m.douban.com/movie/subject/' . $sid . '/',
                'Origin: https://m.douban.com',
            ]
        );
        if (is_array($detail) && (!empty($detail['title']) || !empty($detail['intro']))) {
            // 再保险：简介里若是图书也不要（极少）
            break;
        }
        $detail = null;
    }
    if (!is_array($detail)) {
        return ['ok' => false, 'reason' => 'video_detail_miss'];
    }

    $abstract = resourceHttpGetJson(
        'https://movie.douban.com/j/subject_abstract?subject_id=' . rawurlencode($sid),
        4.0,
        ['Referer: https://movie.douban.com/subject/' . $sid . '/']
    );
    $absSub = is_array($abstract) ? ($abstract['subject'] ?? []) : [];

    $directors = [];
    foreach (($detail['directors'] ?? []) as $d) {
        if (!empty($d['name'])) {
            $directors[] = (string) $d['name'];
        }
    }
    $actors = [];
    foreach (($detail['actors'] ?? []) as $a) {
        if (!empty($a['name'])) {
            $actors[] = (string) $a['name'];
        }
    }
    $genres = [];
    foreach (($detail['genres'] ?? []) as $g) {
        if (is_string($g)) {
            $genres[] = $g;
        }
    }
    $countries = [];
    foreach (($detail['countries'] ?? []) as $c) {
        if (is_string($c)) {
            $countries[] = $c;
        }
    }
    $langs = [];
    foreach (($detail['languages'] ?? []) as $l) {
        if (is_string($l)) {
            $langs[] = $l;
        }
    }
    $rating = $detail['rating']['value'] ?? ($absSub['rate'] ?? '');
    $poster = '';
    if (!empty($detail['pic']['large'])) {
        $poster = (string) $detail['pic']['large'];
    } elseif (!empty($detail['cover_url'])) {
        $poster = (string) $detail['cover_url'];
    } elseif (!empty($best['img'])) {
        $poster = upgradeDoubanPosterUrl($best['img']);
    }
    $poster = preg_replace('#^http://#i', 'https://', (string) $poster);
    $poster = str_replace(['/s_ratio_poster/', '/m_ratio_poster/'], '/l_ratio_poster/', $poster);
    $intro = trim((string) ($detail['intro'] ?? ''));
    $durations = $detail['durations'] ?? [];
    $duration = is_array($durations) ? implode(' / ', $durations) : (string) $durations;

    return [
        'ok' => true,
        'kind' => 'video',
        'source' => '豆瓣影视',
        'source_id' => $sid,
        'source_url' => (string) ($detail['url'] ?? ('https://movie.douban.com/subject/' . $sid . '/')),
        'title' => (string) ($detail['title'] ?? $best['title'] ?? $cleanTitle),
        'original_title' => (string) ($detail['original_title'] ?? ''),
        'year' => (string) ($detail['year'] ?? ($best['year'] ?? $year)),
        'card_subtitle' => (string) ($detail['card_subtitle'] ?? ''),
        'rating' => $rating !== '' && $rating !== null ? (string) $rating : '',
        'rating_count' => (int) ($detail['rating']['count'] ?? 0),
        'genres' => $genres,
        'countries' => $countries,
        'languages' => $langs,
        'directors' => array_slice($directors, 0, 6),
        'authors' => [],
        'actors' => array_slice($actors, 0, 10),
        'episodes' => (string) ($detail['episodes_count'] ?? ($absSub['episodes_count'] ?? '')),
        'duration' => $duration,
        'intro' => $intro,
        'poster' => $poster,
        'subtype' => (string) ($detail['subtype'] ?? ($detail['type'] ?? 'movie')),
        'score_match' => $bestScore,
        'role_label_director' => '导演',
        'role_label_actor' => '主演',
    ];
}

/**
 * 小说基本信息（全自动）：豆瓣读书优先 → Bangumi 书籍 → 本地解析
 * 绝不请求 movie.douban.com，无需后台配置
 */
function fetchNovelBasicInfo(array $queries, $cleanTitle, $year = '')
{
    $best = null;
    $bestScore = 0;
    foreach ($queries as $query) {
        $suggest = resourceHttpGetJson(
            'https://book.douban.com/j/subject_suggest?q=' . rawurlencode($query),
            5.0,
            [
                'Referer: https://book.douban.com/',
                'Origin: https://book.douban.com',
                'X-Requested-With: XMLHttpRequest',
            ]
        );
        if (!is_array($suggest)) {
            continue;
        }
        $rows = isset($suggest[0]) || $suggest === [] ? $suggest : [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            // type=b 图书
            $type = (string) ($row['type'] ?? 'b');
            if ($type !== '' && $type !== 'b' && $type !== 'book') {
                continue;
            }
            $name = (string) ($row['title'] ?? '');
            $sid = (string) ($row['id'] ?? '');
            if ($name === '' || $sid === '') {
                continue;
            }
            $score = resourceTitleSimilarity($query, $name);
            if ($year !== '' && !empty($row['year']) && (string) $row['year'] === (string) $year) {
                $score = min(100, $score + 8);
            }
            if ($score > $bestScore && $score >= 50) {
                $bestScore = $score;
                $best = $row;
            }
        }
        if ($bestScore >= 88) {
            break;
        }
    }

    if ($best && !empty($best['id'])) {
        $sid = (string) $best['id'];
        $detail = resourceHttpGetJson(
            'https://m.douban.com/rexxar/api/v2/book/' . rawurlencode($sid),
            6.0,
            [
                'Referer: https://m.douban.com/book/subject/' . $sid . '/',
                'Origin: https://m.douban.com',
            ]
        );
        if (is_array($detail) && (!empty($detail['title']) || !empty($detail['intro']))) {
            $authors = [];
            $authorField = $detail['author'] ?? $detail['authors'] ?? [];
            if (is_string($authorField) && $authorField !== '') {
                $authors[] = $authorField;
            } elseif (is_array($authorField)) {
                foreach ($authorField as $a) {
                    if (is_string($a)) {
                        $authors[] = $a;
                    } elseif (is_array($a) && !empty($a['name'])) {
                        $authors[] = (string) $a['name'];
                    }
                }
            }
            if (empty($authors) && !empty($best['author_name'])) {
                $authors[] = (string) $best['author_name'];
            }
            $poster = '';
            if (!empty($detail['pic']['large'])) {
                $poster = (string) $detail['pic']['large'];
            } elseif (!empty($detail['cover_url'])) {
                $poster = (string) $detail['cover_url'];
            } elseif (!empty($best['pic'])) {
                $poster = preg_replace('#^http://#i', 'https://', (string) $best['pic']);
                // 读书封面 s → l
                $poster = str_replace('/s/public/', '/l/public/', $poster);
            }
            $poster = preg_replace('#^http://#i', 'https://', (string) $poster);
            $rating = $detail['rating']['value'] ?? '';
            $genres = [];
            foreach (($detail['tags'] ?? []) as $tg) {
                if (is_array($tg) && !empty($tg['name'])) {
                    $genres[] = (string) $tg['name'];
                } elseif (is_string($tg)) {
                    $genres[] = $tg;
                }
            }

            return [
                'ok' => true,
                'kind' => 'novel',
                'source' => '豆瓣读书',
                'source_id' => $sid,
                'source_url' => (string) ($detail['url'] ?? ('https://book.douban.com/subject/' . $sid . '/')),
                'title' => (string) ($detail['title'] ?? $best['title'] ?? $cleanTitle),
                'original_title' => '',
                'year' => (string) ($detail['pubdate'] ?? ($detail['year'] ?? ($best['year'] ?? $year))),
                'card_subtitle' => (string) ($detail['card_subtitle'] ?? ''),
                'rating' => $rating !== '' && $rating !== null ? (string) $rating : '',
                'rating_count' => (int) ($detail['rating']['count'] ?? 0),
                'genres' => array_slice($genres, 0, 8),
                'countries' => [],
                'languages' => [],
                'directors' => [],
                'authors' => array_slice($authors, 0, 6),
                'actors' => [],
                'episodes' => '',
                'duration' => '',
                'intro' => trim((string) ($detail['intro'] ?? '')),
                'poster' => $poster,
                'subtype' => 'book',
                'score_match' => $bestScore,
                'role_label_director' => '作者',
                'role_label_actor' => '',
            ];
        }
    }

    // 豆瓣读书未命中 → Bangumi 书籍
    $bgm = fetchBangumiBasicInfo($queries, $cleanTitle, $year, [1], 'novel');
    if (!empty($bgm['ok'])) {
        return $bgm;
    }

    // 都没有 → 本地解析（仍有类型/网盘等，不套影视）
    return buildLocalModuleBasicInfo($cleanTitle, '', 'novel', ['year' => $year]);
}

/**
 * Bangumi 按类型拉详情
 * @param int[] $allowTypes 1书 2动画 3音乐 4游戏 6三次元
 */
function fetchBangumiBasicInfo(array $queries, $cleanTitle, $year, array $allowTypes, $kindLabel = 'other')
{
    foreach ($queries as $query) {
        // 优先带 type 参数
        $typeParam = count($allowTypes) === 1 ? ('&type=' . (int) $allowTypes[0]) : '';
        $bgm = resourceHttpGetJson(
            'https://api.bgm.tv/search/subject/' . rawurlencode($query) . '?responseGroup=small' . $typeParam,
            4.0,
            ['User-Agent: SeagullResourceHub/1.3 (basic-info)']
        );
        $pick = null;
        $ps = 0;
        foreach (($bgm['list'] ?? []) as $row) {
            $type = (int) ($row['type'] ?? 0);
            if (!empty($allowTypes) && $type && !in_array($type, $allowTypes, true)) {
                continue;
            }
            $label = (string) (($row['name_cn'] ?? '') ?: ($row['name'] ?? ''));
            $sc = resourceTitleSimilarity($query, $label);
            $q2 = preg_replace('/\s+/u', '', mb_strtolower($query, 'UTF-8'));
            $l2 = preg_replace('/\s+/u', '', mb_strtolower($label, 'UTF-8'));
            $contain = $q2 && $l2 && (mb_strpos($l2, $q2, 0, 'UTF-8') !== false || mb_strpos($q2, $l2, 0, 'UTF-8') !== false);
            if ((!$contain && $sc < 70) || $sc <= $ps) {
                continue;
            }
            $ps = $sc;
            $pick = $row;
        }
        if (!$pick || empty($pick['id'])) {
            continue;
        }
        $bid = (int) $pick['id'];
        $detail = resourceHttpGetJson(
            'https://api.bgm.tv/v0/subjects/' . $bid,
            5.0,
            ['User-Agent: SeagullResourceHub/1.3', 'Accept: application/json']
        );
        if (!is_array($detail) || (empty($detail['summary']) && empty($detail['name']))) {
            $detail = resourceHttpGetJson(
                'https://api.bgm.tv/subject/' . $bid . '?responseGroup=large',
                5.0,
                ['User-Agent: SeagullResourceHub/1.3']
            );
        }
        if (!is_array($detail)) {
            continue;
        }
        // 再次校验 type
        $dtype = (int) ($detail['type'] ?? $pick['type'] ?? 0);
        if ($dtype && !empty($allowTypes) && !in_array($dtype, $allowTypes, true)) {
            continue;
        }
        $tags = [];
        foreach (($detail['tags'] ?? []) as $tg) {
            if (!empty($tg['name'])) {
                $tags[] = (string) $tg['name'];
            }
        }
        $poster = $detail['images']['large'] ?? $detail['images']['common'] ?? $pick['images']['large'] ?? '';
        $poster = preg_replace('#^http://#i', 'https://', (string) $poster);
        $rating = isset($detail['rating']['score']) ? (string) $detail['rating']['score'] : '';
        $isNovel = ($kindLabel === 'novel' || $dtype === 1);

        return [
            'ok' => true,
            'kind' => $isNovel ? 'novel' : 'video',
            'source' => 'Bangumi',
            'source_id' => (string) $bid,
            'source_url' => 'https://bgm.tv/subject/' . $bid,
            'title' => (string) (($detail['name_cn'] ?? '') ?: ($detail['name'] ?? $pick['name_cn'] ?? $cleanTitle)),
            'original_title' => (string) ($detail['name'] ?? ''),
            'year' => substr((string) ($detail['date'] ?? ''), 0, 4),
            'card_subtitle' => (string) ($detail['platform'] ?? ''),
            'rating' => $rating,
            'rating_count' => (int) ($detail['rating']['total'] ?? 0),
            'genres' => array_slice($tags, 0, 8),
            'countries' => [],
            'languages' => [],
            'directors' => [],
            'authors' => [],
            'actors' => [],
            'episodes' => (string) ($detail['eps'] ?? $detail['total_episodes'] ?? ''),
            'duration' => '',
            'intro' => trim((string) ($detail['summary'] ?? $pick['summary'] ?? '')),
            'poster' => $poster,
            'subtype' => (string) ($detail['platform'] ?? ''),
            'score_match' => $ps,
            'role_label_director' => $isNovel ? '作者' : '导演',
            'role_label_actor' => $isNovel ? '' : '主演',
        ];
    }
    return ['ok' => false, 'reason' => 'bgm_miss'];
}

/**
 * 多源拉取海报封面
 * 优先级：豆瓣 suggest → Bangumi → 维基 pageimage → TMDB(可选) → SVG 兜底
 * 返回 https 图 URL 或 data:image/svg 兜底（保证详情永远有图）
 */
function fetchResourcePoster($cleanTitle = '', $kindKey = 'other', $year = '', $wikiImage = '', $allowFallback = true)
{
    $cleanTitle = trim((string) $cleanTitle);
    if ($cleanTitle === '') {
        return $allowFallback ? buildFallbackPosterDataUri('资源', $kindKey) : '';
    }

    // v5：kind 隔离，并采用更干净的核心标题（小说/影视海报缓存互不复用）。
    $cacheKey = 'poster_v5_' . md5(mb_strtolower($cleanTitle . '|' . $kindKey . '|' . $year, 'UTF-8'));
    $cacheDir = root_path('runtime/cache');
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';
    if (is_file($cacheFile)) {
        $c = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($c) && !empty($c['url'])) {
            $cached = (string) $c['url'];
            // 绝不复用 data: 兜底缓存（会把「全站自动封面」锁死数小时）
            if (strpos($cached, 'data:') === 0) {
                @unlink($cacheFile);
            } elseif (strpos($cached, 'http') === 0 && (time() - filemtime($cacheFile) < 14 * 86400)) {
                return $cached;
            }
        } elseif (is_array($c) && array_key_exists('url', $c) && $c['url'] === '') {
            // 外部图片源偶发风控时短暂 miss，2 分钟后允许自动恢复。
            if (time() - filemtime($cacheFile) < 120) {
                return $allowFallback ? buildFallbackPosterDataUri($cleanTitle, $kindKey) : '';
            }
        }
    }

    $queries = function_exists('extractPosterSearchQueries')
        ? extractPosterSearchQueries($cleanTitle, $year)
        : [$cleanTitle];
    if (empty($queries)) {
        $queries = [$cleanTitle];
    }

    $bestPoster = '';
    $bestScore = 0;
    $bestSrc = '';

    // 维基图：仅当标题相似度够才用（防错图）
    if (!empty($wikiImage) && preg_match('#^https?://#i', $wikiImage)) {
        // 暂作候选，score 中等
        $bestPoster = preg_replace('#^http://#i', 'https://', $wikiImage);
        $bestScore = 60;
        $bestSrc = 'wiki';
    }

    $isNovel = ($kindKey === 'novel');
    // 严禁：other/文档/软件 等走影视海报（会串台）
    $isVideo = ($kindKey === 'video');
    $isLocalOnly = in_array($kindKey, ['document', 'software', 'archive', 'image', 'other'], true);

    // 非影视/小说：直接模块化 SVG 封面，不请求豆瓣电影
    if ($isLocalOnly) {
        $poster = $allowFallback ? buildFallbackPosterDataUri($cleanTitle, $kindKey) : '';
        // 不写长缓存死锁；短 miss 即可
        return $poster;
    }

    foreach ($queries as $query) {
        if ($bestScore >= 92) {
            break;
        }

        // 小说：豆瓣读书封面（禁止 movie.douban）
        if ($isNovel) {
            try {
                $ds = resourceHttpGetJson(
                    'https://book.douban.com/j/subject_suggest?q=' . rawurlencode($query),
                    4.0,
                    ['Referer: https://book.douban.com/', 'Origin: https://book.douban.com']
                );
                if (is_array($ds)) {
                    foreach ($ds as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $name = (string) ($row['title'] ?? '');
                        $img = (string) ($row['pic'] ?? $row['img'] ?? '');
                        if ($name === '' || $img === '') {
                            continue;
                        }
                        $score = resourceTitleSimilarity($query, $name);
                        if ($score >= 52 && $score > $bestScore) {
                            $bestScore = $score;
                            $bestPoster = preg_replace('#^http://#i', 'https://', $img);
                            $bestPoster = str_replace('/s/public/', '/l/public/', $bestPoster);
                            $bestSrc = 'douban_book';
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
            // Bangumi 书 type=1
            if ($bestScore < 90) {
                try {
                    $bgm = resourceHttpGetJson(
                        'https://api.bgm.tv/search/subject/' . rawurlencode($query) . '?type=1&responseGroup=small',
                        4.0,
                        ['User-Agent: SeagullResourceHub/1.3 (poster-novel)']
                    );
                    foreach (($bgm['list'] ?? []) as $row) {
                        if ((int) ($row['type'] ?? 0) !== 1 && isset($row['type'])) {
                            continue;
                        }
                        $label = (string) (($row['name_cn'] ?? '') ?: ($row['name'] ?? ''));
                        $score = resourceTitleSimilarity($query, $label);
                        $img = $row['images']['large'] ?? $row['images']['common'] ?? '';
                        if ($img && $score >= 60 && $score > $bestScore) {
                            $bestScore = $score;
                            $bestPoster = preg_replace('#^http://#i', 'https://', (string) $img);
                            $bestSrc = 'bangumi_book';
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            continue; // 小说不再走影视豆瓣
        }

        // 影视：豆瓣 movie suggest
        if ($isVideo) {
            try {
                $du = 'https://movie.douban.com/j/subject_suggest?q=' . rawurlencode($query);
                $ds = resourceHttpGetJson($du, 4.0, [
                    'Referer: https://movie.douban.com/',
                    'Origin: https://movie.douban.com',
                ]);
                if (is_array($ds)) {
                    foreach ($ds as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $type = (string) ($row['type'] ?? '');
                        if ($type !== '' && !in_array($type, ['movie', 'tv'], true)) {
                            continue;
                        }
                        $name = (string) ($row['title'] ?? '');
                        $img = (string) ($row['img'] ?? '');
                        if ($name === '' || $img === '') {
                            continue;
                        }
                        $score = resourceTitleSimilarity($query, $name);
                        if ($year !== '' && !empty($row['year']) && (string) $row['year'] === (string) $year) {
                            $score = min(100, $score + 8);
                        }
                        if ($score >= 52 && $score > $bestScore && $img) {
                            $bestScore = $score;
                            $bestPoster = upgradeDoubanPosterUrl($img);
                            $bestSrc = 'douban';
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // Bangumi 动画 type=2
            if ($bestScore < 90) {
                try {
                    $bgm = resourceHttpGetJson(
                        'https://api.bgm.tv/search/subject/' . rawurlencode($query) . '?type=2&responseGroup=small',
                        4.0,
                        ['User-Agent: SeagullResourceHub/1.3 (poster-video)']
                    );
                    foreach (($bgm['list'] ?? []) as $row) {
                        $nameCn = (string) ($row['name_cn'] ?? '');
                        $name = (string) ($row['name'] ?? '');
                        $label = $nameCn !== '' ? $nameCn : $name;
                        if ($label === '') {
                            continue;
                        }
                        $score = max(
                            resourceTitleSimilarity($query, $label),
                            $nameCn !== '' ? resourceTitleSimilarity($query, $nameCn) : 0
                        );
                        $q2 = preg_replace('/\s+/u', '', mb_strtolower($query, 'UTF-8'));
                        $l2 = preg_replace('/\s+/u', '', mb_strtolower($label, 'UTF-8'));
                        $contain = ($q2 !== '' && $l2 !== '' && (mb_strpos($l2, $q2, 0, 'UTF-8') !== false || mb_strpos($q2, $l2, 0, 'UTF-8') !== false));
                        if (!$contain && $score < 72) {
                            continue;
                        }
                        $img = $row['images']['large'] ?? $row['images']['common'] ?? '';
                        if ($img && $score > $bestScore) {
                            $bestScore = $score;
                            $bestPoster = preg_replace('#^http://#i', 'https://', (string) $img);
                            $bestSrc = 'bangumi';
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        // 3) 维基 pageimages（仅影视高相似度；小说已 continue）
        if ($isVideo && $bestScore < 85) {
            try {
                $wSearch = resourceHttpGetJson('https://zh.wikipedia.org/w/api.php?' . http_build_query([
                    'action' => 'query',
                    'list' => 'search',
                    'srsearch' => $query,
                    'srlimit' => 5,
                    'format' => 'json',
                    'utf8' => 1,
                ]), 3.5);
                foreach (($wSearch['query']['search'] ?? []) as $hit) {
                    $t = (string) ($hit['title'] ?? '');
                    if ($t === '' || mb_strpos($t, '消歧', 0, 'UTF-8') !== false) {
                        continue;
                    }
                    $score = resourceTitleSimilarity($query, $t);
                    if ($score < 70) {
                        continue;
                    }
                    $wPage = resourceHttpGetJson('https://zh.wikipedia.org/w/api.php?' . http_build_query([
                        'action' => 'query',
                        'prop' => 'pageimages',
                        'piprop' => 'thumbnail',
                        'pithumbsize' => 500,
                        'titles' => $t,
                        'format' => 'json',
                        'utf8' => 1,
                        'redirects' => 1,
                    ]), 3.5);
                    foreach (($wPage['query']['pages'] ?? []) as $pg) {
                        $img = (string) (($pg['thumbnail']['source'] ?? '') ?: '');
                        if ($img && $score > $bestScore) {
                            $bestScore = $score;
                            $bestPoster = $img;
                            $bestSrc = 'wiki';
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    // 4) 可选 TMDB（仅影视）
    if ($isVideo && ($bestPoster === '' || $bestScore < 70)) {
        $tmdbKey = '';
        try {
            $tmdbKey = (string) (function_exists('site_conf') ? site_conf('tmdb_api_key', '') : (config('qfshop.tmdb_api_key') ?? ''));
        } catch (\Throwable $e) {
            $tmdbKey = '';
        }
        if ($tmdbKey !== '') {
            foreach ($queries as $query) {
                try {
                    $tu = 'https://api.themoviedb.org/3/search/multi?' . http_build_query([
                        'api_key' => $tmdbKey,
                        'query' => $query,
                        'language' => 'zh-CN',
                        'include_adult' => 'false',
                        'page' => 1,
                    ]);
                    $td = resourceHttpGetJson($tu, 4.0);
                    foreach (($td['results'] ?? []) as $row) {
                        $name = (string) ($row['title'] ?? $row['name'] ?? '');
                        $score = resourceTitleSimilarity($query, $name);
                        if ($score < 55 || empty($row['poster_path'])) {
                            continue;
                        }
                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $bestPoster = 'https://image.tmdb.org/t/p/w500' . $row['poster_path'];
                            $bestSrc = 'tmdb';
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
                if ($bestScore >= 88) {
                    break;
                }
            }
        }
    }

    // 阈值：太低宁可用兜底图，避免挂错海报
    if ($bestPoster !== '' && $bestScore < 48) {
        $bestPoster = '';
        $bestSrc = '';
    }

    // 真实海报才写长缓存；兜底图不写缓存，下次继续尝试外网
    if ($bestPoster !== '' && strpos($bestPoster, 'data:') !== 0) {
        @file_put_contents($cacheFile, json_encode([
            'url' => $bestPoster,
            'score' => $bestScore,
            'src' => $bestSrc,
            'q' => $queries,
        ], JSON_UNESCAPED_UNICODE));
        return $bestPoster;
    }

    @file_put_contents($cacheFile, json_encode([
        'url' => '',
        'score' => $bestScore,
        'src' => 'miss',
        'q' => $queries,
    ], JSON_UNESCAPED_UNICODE));

    if ($allowFallback) {
        return buildFallbackPosterDataUri($queries[0] ?? $cleanTitle, $kindKey);
    }
    return '';
}

/**
 * 检测网盘分享链接是否仍有效
 * @return array{status:string,message:string} status=alive|dead|unknown
 */
function checkPanShareWithConfiguredAccount($url, $code, $type)
{
    $type = (int) $type;
    if (!in_array($type, [0, 2], true) || !class_exists('\\netdisk\\Transfer')) {
        return null;
    }

    $cookieKey = $type === 2 ? 'baidu_cookie' : 'quark_cookie';
    try {
        $cookie = (string) (function_exists('site_conf') ? site_conf($cookieKey, '') : '');
    } catch (\Throwable $e) {
        $cookie = '';
    }
    if ($cookie === '') {
        return null;
    }

    try {
        // isType=1 只读取分享资源信息，不转存、不删除用户网盘文件。
        $transfer = new \netdisk\Transfer();
        $res = $transfer->transfer([
            'url' => (string) $url,
            'code' => (string) $code,
            'isType' => 1,
        ]);
        if (is_array($res) && (int) ($res['code'] ?? 0) === 200 && !empty($res['data'])) {
            return ['status' => 'alive', 'message' => '已读取真实资源详情，分享有效'];
        }

        $message = (string) ($res['message'] ?? $res['msg'] ?? '');
        foreach (['失效', '过期', '不存在', '取消', '删除', '违规', '提取码错误'] as $hint) {
            if ($message !== '' && mb_stripos($message, $hint, 0, 'UTF-8') !== false) {
                return ['status' => 'dead', 'message' => $message];
            }
        }
    } catch (\Throwable $e) {
        // 账号接口临时失败时继续使用公开页检查，不直接误判死链。
    }
    return null;
}

function checkPanShareStatus($url = '', $code = '', $forceRefresh = false)
{
    $url = trim((string) $url);
    $code = trim((string) $code);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return ['status' => 'unknown', 'message' => '无效链接'];
    }

    $cacheKey = 'share_alive_v1_' . md5(mb_strtolower($url . '|' . $code, 'UTF-8'));
    $cacheDir = root_path('runtime/cache');
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';
    // 有效缓存 6 小时；失效缓存 24 小时（减少误杀反复探测）
    if (!$forceRefresh && is_file($cacheFile)) {
        $c = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($c) && !empty($c['status'])) {
            $ttl = $c['status'] === 'dead' ? 86400 : ($c['status'] === 'alive' ? 21600 : 600);
            if (time() - filemtime($cacheFile) < $ttl) {
                return $c;
            }
        }
    }

    $type = function_exists('determineIsType') ? (int) determineIsType($url) : 0;
    $result = ['status' => 'unknown', 'message' => '无法判定'];

    try {
        $accountResult = checkPanShareWithConfiguredAccount($url, $code, $type);
        if (is_array($accountResult) && !empty($accountResult['status'])) {
            $result = $accountResult;
        } elseif ($type === 0) {
            // 夸克：优先 cookie 调 token 接口；否则看公开页特征
            $result = checkQuarkShareAlive($url, $code);
        } elseif ($type === 1) {
            $result = checkAliShareAlive($url);
        } elseif ($type === 2) {
            $result = checkBaiduShareAlive($url, $code);
        } elseif ($type === 3) {
            $result = checkUcShareAlive($url, $code);
        } elseif ($type === 4) {
            $result = checkXunleiShareAlive($url, $code);
        } else {
            $result = checkGenericSharePage($url);
        }
    } catch (\Throwable $e) {
        $result = ['status' => 'unknown', 'message' => '检测异常'];
    }

    @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE));
    return $result;
}

function extractQuarkPwdId($url)
{
    if (preg_match('#pan\.quark\.cn/s/([a-zA-Z0-9]+)#', $url, $m)) {
        return $m[1];
    }
    return '';
}

function checkQuarkShareAlive($url, $code = '')
{
    $pwd = extractQuarkPwdId($url);
    if ($pwd === '') {
        return ['status' => 'unknown', 'message' => '无法解析夸克分享 ID'];
    }
    $cookie = '';
    try {
        $cookie = (string) (function_exists('site_conf') ? site_conf('quark_cookie', '') : (config('qfshop.quark_cookie') ?? ''));
    } catch (\Throwable $e) {
        $cookie = '';
    }
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'Referer: https://pan.quark.cn/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ];
    if ($cookie !== '') {
        // curlHelper 用 cookies 参数
    }
    $body = json_encode(['passcode' => $code ?: '', 'pwd_id' => $pwd], JSON_UNESCAPED_UNICODE);
    $api = 'https://drive-pc.quark.cn/1/clouddrive/share/sharepage/token?pr=ucpro&fr=pc';
    $res = function_exists('curlHelper')
        ? curlHelper($api, 'POST', $body, $headers, '', $cookie, 6)
        : ['body' => '', 'error' => 'no curlHelper'];
    $raw = is_array($res) ? (string) ($res['body'] ?? '') : '';
    // curlHelper 可能把 header 拼在 body 前
    if ($raw && ($pos = strpos($raw, '{')) !== false) {
        $raw = substr($raw, $pos);
    }
    $data = json_decode($raw, true);
    if (is_array($data)) {
        $msg = (string) ($data['message'] ?? $data['msg'] ?? '');
        $st = $data['status'] ?? $data['code'] ?? null;
        // 成功通常 code=0 且有 data.stoken
        if ((isset($data['data']['stoken']) && $data['data']['stoken'] !== '') || (string) $st === '0' || $st === 0) {
            return ['status' => 'alive', 'message' => '分享有效'];
        }
        $deadHints = ['不存在', '失效', '过期', '取消', '删除', '违规', '禁止', '//', 'invalid', 'expired', 'not found', '不存在了'];
        foreach ($deadHints as $h) {
            if ($msg !== '' && mb_stripos($msg, $h, 0, 'UTF-8') !== false) {
                return ['status' => 'dead', 'message' => $msg ?: '分享已失效'];
            }
        }
        // 常见错误码
        if (in_array((string) $st, ['41006', '41001', '41002', '14001', '14002', '25005'], true)) {
            return ['status' => 'dead', 'message' => $msg ?: '分享已失效'];
        }
        if ($msg !== '') {
            // 有明确业务错误但非成功
            if (isset($data['data']) && empty($data['data'])) {
                return ['status' => 'dead', 'message' => $msg];
            }
        }
    }
    return ['status' => 'unknown', 'message' => '夸克接口未返回明确状态（可配置 quark_cookie 提高准确率）'];
}

function checkAliShareAlive($url)
{
    $shareId = '';
    if (preg_match('#/(?:s|t)/([a-zA-Z0-9]+)#', $url, $m)) {
        $shareId = $m[1];
    }
    if ($shareId === '') {
        return ['status' => 'unknown', 'message' => '无法解析阿里分享 ID'];
    }
    $api = 'https://api.aliyundrive.com/adrive/v3/share_link/get_share_by_anonymous';
    $headers = [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0',
    ];
    $res = function_exists('curlHelper')
        ? curlHelper($api, 'POST', json_encode(['share_id' => $shareId]), $headers, '', '', 6)
        : ['body' => ''];
    $raw = (string) ($res['body'] ?? '');
    if (($pos = strpos($raw, '{')) !== false) {
        $raw = substr($raw, $pos);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['status' => 'unknown', 'message' => '阿里接口无响应'];
    }
    if (!empty($data['share_name']) || !empty($data['file_infos']) || !empty($data['file_count'])) {
        return ['status' => 'alive', 'message' => '分享有效'];
    }
    $msg = (string) ($data['message'] ?? $data['msg'] ?? '');
    if ($msg !== '' || !empty($data['code'])) {
        $deadHints = ['取消', '失效', '过期', '不存在', '禁止', '违规', 'ShareLink.Cancelled', 'NotFound', 'Forbidden'];
        $blob = $msg . ' ' . (string) ($data['code'] ?? '');
        foreach ($deadHints as $h) {
            if (mb_stripos($blob, $h, 0, 'UTF-8') !== false) {
                return ['status' => 'dead', 'message' => $msg ?: '分享已失效'];
            }
        }
        // 无 share_name 且有 code 多数为失效
        if (empty($data['share_name'])) {
            return ['status' => 'dead', 'message' => $msg ?: '分享不可用'];
        }
    }
    return ['status' => 'unknown', 'message' => '阿里状态不明'];
}

function checkBaiduShareAlive($url, $code = '')
{
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: text/html',
    ];
    $res = function_exists('curlHelper')
        ? curlHelper($url, 'GET', null, $headers, '', '', 6)
        : ['body' => ''];
    $html = (string) ($res['body'] ?? '');
    $dead = ['分享的文件已经被取消了', '此链接分享内容可能因为涉及侵权', '链接不存在', '页面不存在', '分享已过期', '啊哦，你所访问的页面不存在了'];
    foreach ($dead as $d) {
        if (mb_strpos($html, $d, 0, 'UTF-8') !== false) {
            return ['status' => 'dead', 'message' => $d];
        }
    }
    $alive = ['请输入提取码', '提取码', '分享文件', '文件列表', '网盘-分享'];
    foreach ($alive as $a) {
        if (mb_strpos($html, $a, 0, 'UTF-8') !== false) {
            return ['status' => 'alive', 'message' => '分享页面可访问'];
        }
    }
    return ['status' => 'unknown', 'message' => '百度分享状态不明'];
}

function checkUcShareAlive($url, $code = '')
{
    // UC 结构类似夸克，无 cookie 时做页面关键字探测
    return checkGenericSharePage($url, ['失效', '过期', '不存在', '取消分享', '违规']);
}

function checkXunleiShareAlive($url, $code = '')
{
    return checkGenericSharePage($url, ['失效', '过期', '不存在', '取消', '已失效', '分享不存在']);
}

function checkGenericSharePage($url, $deadHints = null)
{
    $deadHints = $deadHints ?: ['失效', '过期', '不存在', '取消', '删除', '404'];
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: text/html,application/json',
    ];
    $res = function_exists('curlHelper')
        ? curlHelper($url, 'GET', null, $headers, '', '', 6)
        : ['body' => ''];
    $html = (string) ($res['body'] ?? '');
    if ($html === '') {
        return ['status' => 'unknown', 'message' => '页面无响应'];
    }
    foreach ($deadHints as $d) {
        if (mb_strpos($html, $d, 0, 'UTF-8') !== false) {
            return ['status' => 'dead', 'message' => '疑似失效：' . $d];
        }
    }
    return ['status' => 'alive', 'message' => '页面可访问'];
}

/**
 * 批量过滤失效分享（用于列表）
 * - 使用缓存，未缓存的最多检测 $maxCheck 条，避免拖慢搜索
 * - dead 只从本次列表隐藏，不自动修改数据库状态，避免网络假阴性误下架
 *
 * @param array $items  每项需有 url / id
 * @param object|null $sourceModel
 * @param int $maxCheck
 * @return array 仍有效（或 unknown）的 items
 */
function filterExpiredShareItems(array $items, $sourceModel = null, $maxCheck = 8)
{
    if (empty($items)) {
        return $items;
    }
    $checked = 0;
    $alive = [];
    foreach ($items as $item) {
        $url = (string) ($item['url'] ?? '');
        $code = (string) ($item['code'] ?? '');
        $id = (int) ($item['id'] ?? $item['source_id'] ?? 0);
        if ($url === '') {
            $alive[] = $item;
            continue;
        }

        // 先读缓存，不耗额度
        $cacheKey = 'share_alive_v1_' . md5(mb_strtolower($url . '|' . $code, 'UTF-8'));
        $cacheFile = root_path('runtime/cache') . DIRECTORY_SEPARATOR . $cacheKey . '.json';
        $status = null;
        if (is_file($cacheFile)) {
            $c = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($c) && !empty($c['status'])) {
                $ttl = $c['status'] === 'dead' ? 86400 : ($c['status'] === 'alive' ? 21600 : 600);
                if (time() - filemtime($cacheFile) < $ttl) {
                    $status = $c['status'];
                }
            }
        }

        if ($status === null && $checked < $maxCheck && function_exists('checkPanShareStatus')) {
            $r = checkPanShareStatus($url, $code);
            $status = $r['status'] ?? 'unknown';
            $checked++;
        }

        if ($status === 'dead') {
            continue; // 过滤掉
        }

        $item['link_status'] = $status ?: 'unknown';
        $alive[] = $item;
    }
    return $alive;
}

/**
 * 标题相似度 0–100（中文友好的粗匹配）
 */
function resourceTitleSimilarity($a, $b)
{
    $a = mb_strtolower(trim((string) $a), 'UTF-8');
    $b = mb_strtolower(trim((string) $b), 'UTF-8');
    // 去括号内容再比
    $a2 = preg_replace('/[（(].*?[）)]/u', '', $a);
    $b2 = preg_replace('/[（(].*?[）)]/u', '', $b);
    $a2 = preg_replace('/\s+/u', '', $a2);
    $b2 = preg_replace('/\s+/u', '', $b2);
    if ($a2 === '' || $b2 === '') {
        return 0;
    }
    if ($a2 === $b2) {
        return 100;
    }
    if (mb_strpos($b2, $a2, 0, 'UTF-8') !== false || mb_strpos($a2, $b2, 0, 'UTF-8') !== false) {
        $la = mb_strlen($a2, 'UTF-8');
        $lb = mb_strlen($b2, 'UTF-8');
        $ratio = min($la, $lb) / max($la, $lb);
        return (int) round(70 + 30 * $ratio);
    }
    // 字符重叠
    $charsA = preg_split('//u', $a2, -1, PREG_SPLIT_NO_EMPTY);
    $charsB = preg_split('//u', $b2, -1, PREG_SPLIT_NO_EMPTY);
    if (!$charsA || !$charsB) {
        return 0;
    }
    $setB = array_fill_keys($charsB, true);
    $hit = 0;
    foreach ($charsA as $ch) {
        if (isset($setB[$ch])) {
            $hit++;
        }
    }
    $overlap = $hit / max(count($charsA), 1);
    $score = (int) round($overlap * 75);
    // similar_text 辅助（对英文更有效）
    if (function_exists('similar_text')) {
        similar_text($a2, $b2, $pct);
        $score = max($score, (int) round($pct * 0.85));
    }
    return min(100, $score);
}

/**
 * 从模型行中安全取字段（兼容 Model / 数组 / 字段别名）
 */
function sourceRowValue($row, $key, $fallbackKeys = [])
{
    $keys = array_merge([$key], (array) $fallbackKeys);
    foreach ($keys as $k) {
        if (is_array($row) && array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
        if (is_object($row)) {
            try {
                $v = $row[$k] ?? null;
                if ($v !== null && $v !== '') {
                    return $v;
                }
            } catch (\Throwable $e) {
                // ignore
            }
            if (isset($row->$k) && $row->$k !== null && $row->$k !== '') {
                return $row->$k;
            }
        }
    }
    return null;
}

/**
 * 首页「自动识别专区」：
 * 1) 扫最近资源 + detectResourceKind 分桶（能识别「仙逆 4K」）
 * 2) 影视类再叠加后台分类
 * 3) 关键词 like 补齐其它类
 *
 * 关键修复：线上本地库资源多为 is_time=1（与搜索 list 一致），
 * 旧逻辑强制 is_time=0 导致六大专区永远为空。
 */
function buildHomeKindModules($sourceModel, $perKind = 10, $scanLimit = 800)
{
    $perKind = max(6, min(30, (int) $perKind));
    $scanLimit = max(200, min(3000, (int) $scanLimit));
    // v6：去掉 is_time=0 限制，与本地搜索一致
    $cacheKey = 'home_kind_modules_v6_' . $perKind . '_' . $scanLimit;

    try {
        $cached = \think\facade\Cache::get($cacheKey);
        if (is_array($cached) && count($cached) >= 6) {
            // 空缓存不复用，避免“永远空”
            $any = false;
            foreach ($cached as $m) {
                if (!empty($m['count'])) {
                    $any = true;
                    break;
                }
            }
            if ($any) {
                return $cached;
            }
        }
    } catch (\Throwable $e) {
        // ignore
    }

    $rules = getResourceKindRules();
    $priority = ['video', 'novel', 'document', 'software', 'archive', 'image'];
    $buckets = [];
    foreach ($priority as $kind) {
        $buckets[$kind] = [];
    }
    $seen = [];

    // 与搜索一致：status=1 + is_delete=0，不限制 is_time
    // （搜索 list 会传 is_time=1 去掉 is_time=0 条件）
    $baseMap = [
        ['status', '=', 1],
        ['is_delete', '=', 0],
    ];

    $pushItem = function ($kind, $id, $title) use (&$buckets, &$seen, $perKind) {
        if (!isset($buckets[$kind])) {
            return false;
        }
        if (count($buckets[$kind]) >= $perKind) {
            return false;
        }
        $title = trim((string) $title);
        $id = (int) $id;
        if ($id <= 0 || $title === '') {
            return false;
        }
        if (isset($seen[$kind][$title]) || isset($seen['_all'][$title])) {
            return false;
        }
        $seen[$kind][$title] = true;
        $seen['_all'][$title] = true;
        $buckets[$kind][] = ['id' => $id, 'title' => $title];
        return true;
    };

    // 影视分类 ID
    $videoCatIds = [];
    try {
        $cats = \think\facade\Db::name('source_category')
            ->where('status', 0)
            ->field('source_category_id,name')
            ->select();
        foreach ($cats as $c) {
            $name = (string) (is_array($c) ? ($c['name'] ?? '') : ($c->name ?? ''));
            if (preg_match('/电影|电视|剧|动漫|综艺|短剧|影视|国漫|韩剧|美剧|番/u', $name)) {
                $videoCatIds[] = (int) (is_array($c) ? $c['source_category_id'] : $c->source_category_id);
            }
        }
    } catch (\Throwable $e) {
        $videoCatIds = [];
    }

    // —— 1) 扫最近资源分桶 ——
    try {
        $rows = null;
        // 优先用 Db 直查，避免模型作用域/软删等干扰
        try {
            $rows = \think\facade\Db::name('source')
                ->where($baseMap)
                ->field('source_id, title, url, source_category_id')
                ->order('source_id', 'desc')
                ->limit($scanLimit)
                ->select();
        } catch (\Throwable $e) {
            $rows = null;
        }
        if ($rows === null && $sourceModel) {
            $rows = $sourceModel
                ->where($baseMap)
                ->field('source_id, title, url, source_category_id')
                ->order(['source_id' => 'desc'])
                ->limit($scanLimit)
                ->select();
        }

        foreach ($rows ?: [] as $row) {
            $title = trim((string) sourceRowValue($row, 'title'));
            $url = (string) sourceRowValue($row, 'url');
            $id = (int) sourceRowValue($row, 'source_id', ['id']);
            $catId = (int) sourceRowValue($row, 'source_category_id');
            if ($id <= 0 || $title === '') {
                continue;
            }

            $detected = detectResourceKind($title, $url);
            $key = $detected['key'] ?? 'other';

            // 标题无关键词：影视分类 → video
            if ($key === 'other' && $catId && in_array($catId, $videoCatIds, true)) {
                $key = 'video';
            }
            // 本站本地库以影视为主，无匹配时默认归 video，保证首页有内容
            if ($key === 'other' || !isset($buckets[$key])) {
                $key = 'video';
            }
            $pushItem($key, $id, $title);
        }
    } catch (\Throwable $e) {
        // keep going
    }

    // —— 2) 影视不足：按分类补 ——
    if (count($buckets['video']) < $perKind && !empty($videoCatIds)) {
        try {
            $more = \think\facade\Db::name('source')
                ->where($baseMap)
                ->whereIn('source_category_id', $videoCatIds)
                ->field('source_id, title')
                ->order('source_id', 'desc')
                ->limit($perKind * 3)
                ->select();
            foreach ($more as $row) {
                $pushItem(
                    'video',
                    sourceRowValue($row, 'source_id', ['id']),
                    sourceRowValue($row, 'title')
                );
                if (count($buckets['video']) >= $perKind) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    // —— 3) 各类不足：title like 关键词补 ——
    foreach (['novel', 'document', 'software', 'archive', 'image', 'video'] as $kind) {
        if (count($buckets[$kind]) >= $perKind || empty($rules[$kind]['keywords'])) {
            continue;
        }
        // 优先用区分度高的关键词（跳过纯扩展名里过短的）
        $keywords = normalizeKindKeywords($rules[$kind]['keywords']);
        $prefer = [];
        foreach ($keywords as $kw) {
            if (mb_strlen($kw, 'UTF-8') >= 2) {
                $prefer[] = $kw;
            }
        }
        $keywords = array_slice($prefer ?: $keywords, 0, 10);
        if (empty($keywords)) {
            continue;
        }
        try {
            $query = \think\facade\Db::name('source')->where($baseMap)->where(function ($q) use ($keywords) {
                foreach ($keywords as $i => $kw) {
                    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $kw) . '%';
                    if ($i === 0) {
                        $q->whereLike('title', $like);
                    } else {
                        $q->whereOr('title', 'like', $like);
                    }
                }
            });
            $more = $query->field('source_id,title,url')
                ->order('source_id', 'desc')
                ->limit($perKind * 3)
                ->select();
            foreach ($more as $row) {
                $title = trim((string) sourceRowValue($row, 'title'));
                $url = (string) sourceRowValue($row, 'url');
                $id = (int) sourceRowValue($row, 'source_id', ['id']);
                if ($id <= 0 || $title === '') {
                    continue;
                }
                $detected = detectResourceKind($title, $url);
                $dkey = $detected['key'] ?? 'other';
                if ($kind === 'video') {
                    // video 桶：detect 为 video/other 都可（其它明确类型不抢）
                    if (in_array($dkey, ['novel', 'document', 'software', 'archive', 'image'], true)) {
                        continue;
                    }
                } else {
                    if ($dkey !== $kind) {
                        // 关键词 like 命中但 detect 不一致时：只要 title 含本类关键词仍收下
                        $hit = false;
                        $low = mb_strtolower($title, 'UTF-8');
                        foreach ($keywords as $kw) {
                            if (mb_strpos($low, mb_strtolower($kw, 'UTF-8'), 0, 'UTF-8') !== false) {
                                $hit = true;
                                break;
                            }
                        }
                        if (!$hit) {
                            continue;
                        }
                    }
                }
                $pushItem($kind, $id, $title);
                if (count($buckets[$kind]) >= $perKind) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            // ignore single kind
        }
    }

    $modules = [];
    $hasAny = false;
    foreach ($priority as $kind) {
        if (empty($rules[$kind])) {
            continue;
        }
        $meta = $rules[$kind];
        $list = array_slice($buckets[$kind] ?? [], 0, $perKind);
        if (count($list) > 0) {
            $hasAny = true;
        }
        $modules[] = [
            'key' => $kind,
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'subtitle' => $meta['subtitle'],
            'search' => $meta['search'],
            'list' => $list,
            'count' => count($list),
            'empty' => count($list) === 0,
        ];
    }

    // 有数据才缓存；空结果不缓存，便于下次立刻重试
    if ($hasAny) {
        try {
            \think\facade\Cache::set($cacheKey, $modules, 300);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    return $modules;
}

/**
 * 清除首页类型专区缓存（资源新增/删除后调用，保证自动更新）
 */
function clearHomeKindModulesCache()
{
    try {
        for ($i = 6; $i <= 30; $i++) {
            foreach ([200, 400, 500, 800, 1000, 2000, 3000] as $scan) {
                \think\facade\Cache::delete('home_kind_modules_v6_' . $i . '_' . $scan);
                \think\facade\Cache::delete('home_kind_modules_v5_' . $i . '_' . $scan);
            }
            \think\facade\Cache::delete('home_kind_modules_v4_' . $i);
            \think\facade\Cache::delete('home_kind_modules_v3_' . $i);
            \think\facade\Cache::delete('home_kind_modules_v1_' . $i . '_500');
            \think\facade\Cache::delete('home_kind_modules_v1_' . $i . '_400');
        }
    } catch (\Throwable $e) {
        // ignore
    }
}
