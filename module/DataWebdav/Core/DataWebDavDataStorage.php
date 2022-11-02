<?php


namespace Module\DataWebdav\Core;


use League\Flysystem\Filesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use ModStart\Data\AbstractRemoteDataStorage;
use ModStart\Data\Traits\LocalMultipartUploadTrait;
use ModStart\ModStart;
use Sabre\DAV\Client;

class DataWebDavDataStorage extends AbstractRemoteDataStorage
{
    use LocalMultipartUploadTrait;

    
    private $client;
    private $bucket;

    public function init()
    {
        $this->remoteType = 'DataWebdav';
        $client = new Client([
            'baseUri' => $this->option['url'],
            'userName' => $this->option['user'],
            'password' => $this->option['pass'],
        ]);
        $adapter = new WebDAVAdapter($client);
        $this->client = new Filesystem($adapter);
    }

    public function has($file)
    {
        return $this->client->has($file);
    }

    public function move($from, $to)
    {
        try {
            $this->client->copy($from, $to);
            $this->client->delete($from);
            return true;
        } catch (\Exception $e) {
        }
        return false;
    }

    public function delete($file)
    {
        try {
            return $this->client->delete($file);
        } catch (\Exception $e) {
        }
        return false;
    }

    public function put($file, $content)
    {
        if (ModStart::env() == 'laravel9') {
            $this->client->write($file, $content);
            return true;
        }
        return $this->client->put($file, $content);
    }

    public function get($file)
    {
        if (ModStart::env() == 'laravel9') {
            return $this->client->read($file);
        }
        return $this->client->get($file);
    }

    public function size($file)
    {
        if (ModStart::env() == 'laravel9') {
            return $this->client->fileSize($file);
        }
        return $this->client->getSize($file);
    }

    public function saveLocalToRemote($local, $remote)
    {
        $local = public_path($local);
        $content = file_get_contents($local);
        return $this->client->put($remote, $content);
    }
}
