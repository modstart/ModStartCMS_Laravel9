<?php

namespace Module\DataWebdav\Admin\Controller;

use Illuminate\Routing\Controller;
use ModStart\Admin\Layout\AdminConfigBuilder;

class ConfigController extends Controller
{
    public function index(AdminConfigBuilder $builder)
    {
        $builder->pageTitle('WebDav云存储设置');
        $builder->switch('DataWebdav_Enable', '启用');
        $builder->text('DataWebdav_Url', 'URL')->help('');
        $builder->text('DataWebdav_User', '用户')->help('');
        $builder->text('DataWebdav_Pass', '密码')->help('');
        $builder->text('DataWebdav_Domain', '访问域名前缀')
            ->help('如果您开启了CDN加速，可直接配置CDN域名（如 http://xxx.com/test）')
            ->ruleUrl();
        $builder->formClass('wide');
        return $builder->perform();
    }

}
