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
        $this->refreshFrontendTemplateCache();
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

    /**
     * 部署包覆盖模板后，部分生产环境会因压缩包保留旧 mtime 而继续执行
     * runtime 中的旧编译文件。版本变化时仅清一次前台模板缓存，让下一次
     * View::fetch 使用当前文件重新编译；不触碰数据缓存、Cookie 或配置。
     */
    private function refreshFrontendTemplateCache()
    {
        $version = '20260719-home-local-poster-v5';
        $runtime = root_path('runtime');
        $marker = $runtime . DIRECTORY_SEPARATOR . '.frontend_template_version';
        if (is_file($marker) && trim((string) @file_get_contents($marker)) === $version) {
            return;
        }

        $tempDirs = [
            $runtime . DIRECTORY_SEPARATOR . 'index' . DIRECTORY_SEPARATOR . 'temp',
            $runtime . DIRECTORY_SEPARATOR . 'temp',
        ];
        foreach ($tempDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach ((array) glob($dir . DIRECTORY_SEPARATOR . '*.php') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        if (!is_dir($runtime)) {
            @mkdir($runtime, 0755, true);
        }
        @file_put_contents($marker, $version, LOCK_EX);
    }
}
