<?php

declare(strict_types=1);

namespace app\index;

use think\App;
use EasyWeChat\Factory;
use app\model\Conf as ConfModel;
use app\model\User as UserModel;

/**
 * 控制器基础类
 */
#[\AllowDynamicProperties]
abstract class QfShop
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        $this->confModel = new ConfModel();

        $configs = $this->confModel->select()->toArray();
        $c = array_column($configs, 'conf_value', 'conf_key');
        config($c, 'qfshop');

        // 补齐前台自定义 conf 项（仅插入缺失，不覆盖后台已改值）
        if (function_exists('ensureSiteCustomConfKeys')) {
            ensureSiteCustomConfKeys();
            // 重新读一次，保证新插入的键可用
            $configs = $this->confModel->select()->toArray();
            $c = array_column($configs, 'conf_value', 'conf_key');
            config($c, 'qfshop');
        }
    }
}
