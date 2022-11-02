<?php

namespace Module\DataWebdav\Core;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use ModStart\Admin\Config\AdminMenu;
use ModStart\ModStart;
use ModStart\Module\ModuleClassLoader;

class ModuleServiceProvider extends ServiceProvider
{
    
    public function boot(Dispatcher $events)
    {
        AdminMenu::register([
            [
                'title' => L('Site Manage'),
                'icon' => 'cog',
                'sort' => 400,
                'children' => [
                    [
                        'title' => 'WebDav存储',
                        'url' => '\Module\DataWebdav\Admin\Controller\ConfigController@index',
                    ],
                ]
            ]
        ]);
        if (modstart_config('DataWebdav_Enable', false)) {

            $this->app['config']->set('DataStorageDriver', 'DataStorage_DataWebdav');
            $this->app->bind('DataStorage_DataWebdav', function () {
                include_once __DIR__ . '/../SDK/sabre-uri/lib/functions.php';
                include_once __DIR__ . '/../SDK/sabre-xml/lib/Deserializer/functions.php';
                ModuleClassLoader::addNamespace('Sabre\\DAV', __DIR__ . '/../SDK/sabre-dav/lib/DAV');
                ModuleClassLoader::addNamespace('Sabre\\HTTP', __DIR__ . '/../SDK/sabre-http/lib');
                ModuleClassLoader::addNamespace('Sabre\\Event', __DIR__ . '/../SDK/sabre-event/lib');
                ModuleClassLoader::addNamespace('Sabre\\Xml', __DIR__ . '/../SDK/sabre-xml/lib');
                ModuleClassLoader::addNamespace('Sabre\\Uri', __DIR__ . '/../SDK/sabre-uri/lib');
                ModuleClassLoader::addNamespace('Sabre\\VObject', __DIR__ . '/../SDK/sabre-vobject/lib');
                if (ModStart::env() == 'laravel9') {
                    ModuleClassLoader::addNamespace('League\\Flysystem\\WebDAV', __DIR__ . '/../SDK/flysystem-webdav-9');
                } else {
                    ModuleClassLoader::addNamespace('League\\Flysystem\\WebDAV', __DIR__ . '/../SDK/flysystem-webdav/src');
                }
                $option = [];
                $option['url'] = modstart_config()->getWithEnv('DataWebdav_Url');
                $option['user'] = modstart_config()->getWithEnv('DataWebdav_User');
                $option['pass'] = modstart_config()->getWithEnv('DataWebdav_Pass');
                $storage = new DataWebdavDataStorage($option);
                $storage->init();
                return $storage;
            });
        }
    }

    
    public function register()
    {

    }
}
