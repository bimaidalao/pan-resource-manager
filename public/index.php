<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

// 检测 PHP 环境：与当前 ThinkPHP 依赖声明保持一致，不限制 PHP 8。
if (version_compare(PHP_VERSION, '7.2.5', '<')) {
    die('PHP 7.2.5 or newer is required.');
}

// 检测是否是新安装
if(file_exists("./install") && !file_exists("./install/install.lock")){
	$url=$_SERVER['HTTP_HOST'].trim($_SERVER['SCRIPT_NAME'],'index.php').'install/index.php';
	header("Location:http://$url");
	die;
}

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

// $response = $http->run();

// 特殊路由
$_amain = 'index';
$_aother = 'admin|qfadmin|api'; // 这里是除了home以外的所有其他应用
 
if (preg_match('/^\/('.$_aother.')\/?/', $_SERVER['REQUEST_URI'])) {
    $response = $http->run();
} else {
    $response = $http->name($_amain)->run();
}

$response->send();
$http->end($response);
