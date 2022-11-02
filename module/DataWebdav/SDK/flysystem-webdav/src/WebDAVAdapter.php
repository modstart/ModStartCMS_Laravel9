<?php

namespace League\Flysystem\WebDAV;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedReadingTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;
use LogicException;
use Sabre\DAV\Client;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Xml\Property\ResourceType;
use Sabre\HTTP\HttpException;

class WebDAVAdapter extends AbstractAdapter
{
    use StreamedReadingTrait;
    use StreamedCopyTrait {
        StreamedCopyTrait::copy as streamedCopy;
    }
    use NotSupportingVisibilityTrait;

    protected static $metadataFields = [
        '{DAV:}displayname',
        '{DAV:}getcontentlength',
        '{DAV:}getcontenttype',
        '{DAV:}getlastmodified',
        '{DAV:}iscollection',
        '{DAV:}resourcetype',
    ];

    
    protected static $resultMap = [
        '{DAV:}getcontentlength' => 'size',
        '{DAV:}getcontenttype' => 'mimetype',
        'content-length' => 'size',
        'content-type' => 'mimetype',
    ];

    
    protected $client;

    
    protected $useStreamedCopy = true;

    
    public function __construct(Client $client, $prefix = null, $useStreamedCopy = true)
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
        $this->setUseStreamedCopy($useStreamedCopy);
    }

    
    protected function encodePath($path)
	{
		$a = explode('/', $path);
		for ($i=0; $i<count($a); $i++) {
			$a[$i] = rawurlencode($a[$i]);
		}
		return implode('/', $a);
	}

    
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($this->encodePath($path));

        try {
            $result = $this->client->propFind($location, static::$metadataFields);

            if (empty($result)) {
                return false;
            }

            return $this->normalizeObject($result, $path);
        } catch (Exception $e) {
            return false;
        } catch (HttpException $e) {
            return false;
        }
    }

    
    public function has($path)
    {
        return $this->getMetadata($path);
    }

    
    public function read($path)
    {
        $location = $this->applyPathPrefix($this->encodePath($path));

        try {
            $response = $this->client->request('GET', $location);

            if ($response['statusCode'] !== 200) {
                return false;
            }

            $lastModified = isset($response['headers']['last-modified']) ? $response['headers']['last-modified'] : time();

            return array_merge([
                'contents' => $response['body'],
                'timestamp' => strtotime(is_array($lastModified) ? current($lastModified) : $lastModified),
                'path' => $path,
            ], Util::map($response['headers'], static::$resultMap));
        } catch (Exception $e) {
            return false;
        }
    }

    
    public function write($path, $contents, Config $config)
    {
        if (!$this->createDir(Util::dirname($path), $config)) {
            return false;
        }

        $location = $this->applyPathPrefix($this->encodePath($path));
        $response = $this->client->request('PUT', $location, $contents);

        if ($response['statusCode'] >= 400) {
            return false;
        }

        $result = compact('path', 'contents');

        if ($config->get('visibility')) {
            throw new LogicException(__CLASS__.' does not support visibility settings.');
        }

        return $result;
    }

    
    public function writeStream($path, $resource, Config $config)
    {
        return $this->write($path, $resource, $config);
    }

    
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    
    public function updateStream($path, $resource, Config $config)
    {
        return $this->update($path, $resource, $config);
    }

    
    public function rename($path, $newpath)
    {
        $location = $this->applyPathPrefix($this->encodePath($path));
        $newLocation = $this->applyPathPrefix($this->encodePath($newpath));

        try {
            $response = $this->client->request('MOVE', '/'.ltrim($location, '/'), null, [
                'Destination' => '/'.ltrim($newLocation, '/'),
            ]);

            if ($response['statusCode'] >= 200 && $response['statusCode'] < 300) {
                return true;
            }
        } catch (NotFound $e) {
                    }

        return false;
    }

    
    public function copy($path, $newpath)
    {
        if ($this->useStreamedCopy === true) {
            return $this->streamedCopy($path, $newpath);
        } else {
            return $this->nativeCopy($path, $newpath);
        }
    }

    
    public function delete($path)
    {
        $location = $this->applyPathPrefix($this->encodePath($path));

        try {
            $response =  $this->client->request('DELETE', $location)['statusCode'];


            return $response >= 200 && $response < 300;
        } catch (NotFound $e) {
            return false;
        }
    }

    
    public function createDir($path, Config $config)
    {
        $encodedPath = $this->encodePath($path);
        $path = trim($path, '/');

        $result = compact('path') + ['type' => 'dir'];

        if (Util::normalizeDirname($path) === '' || $this->has($path)) {
            return $result;
        }

        $directories = explode('/', $path);
        if (count($directories) > 1) {
            $parentDirectories = array_splice($directories, 0, count($directories) - 1);
            if (!$this->createDir(implode('/', $parentDirectories), $config)) {
                return false;
            }
        }

        $location = $this->applyPathPrefix($encodedPath);
        $response = $this->client->request('MKCOL', $location . $this->pathSeparator);

        if ($response['statusCode'] !== 201) {
            return false;
        }

        return $result;
    }

    
    public function deleteDir($dirname)
    {
        return $this->delete($dirname);
    }

    
    public function listContents($directory = '', $recursive = false)
    {
        $location = $this->applyPathPrefix($this->encodePath($directory));
        $response = $this->client->propFind($location . '/', static::$metadataFields, 1);

        array_shift($response);
        $result = [];

        foreach ($response as $path => $object) {
            $path = $this->removePathPrefix(rawurldecode($path));
            $object = $this->normalizeObject($object, $path);
            $result[] = $object;

            if ($recursive && $object['type'] === 'dir') {
                $result = array_merge($result, $this->listContents($object['path'], true));
            }
        }

        return $result;
    }

    
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    
    public function getUseStreamedCopy()
    {
        return $this->useStreamedCopy;
    }

    
    public function setUseStreamedCopy($useStreamedCopy)
    {
        $this->useStreamedCopy = (bool)$useStreamedCopy;
    }

    
    protected function nativeCopy($path, $newPath)
    {
        if (!$this->createDir(Util::dirname($newPath), new Config())) {
            return false;
        }

        $location = $this->applyPathPrefix($this->encodePath($path));
        $newLocation = $this->applyPathPrefix($this->encodePath($newPath));

        try {
            $destination = $this->client->getAbsoluteUrl($newLocation);
            $response = $this->client->request('COPY', '/'.ltrim($location, '/'), null, [
                'Destination' => $destination,
            ]);

            if ($response['statusCode'] >= 200 && $response['statusCode'] < 300) {
                return true;
            }
        } catch (NotFound $e) {
                    }

        return false;
    }

    
    protected function normalizeObject(array $object, $path)
    {
        if ($this->isDirectory($object)) {
            return ['type' => 'dir', 'path' => trim($path, '/')];
        }

        $result = Util::map($object, static::$resultMap);

        if (isset($object['{DAV:}getlastmodified'])) {
            $result['timestamp'] = strtotime($object['{DAV:}getlastmodified']);
        }

        $result['type'] = 'file';
        $result['path'] = trim($path, '/');

        return $result;
    }

    
    protected function isDirectory(array $object)
    {
        if (isset($object['{DAV:}resourcetype'])) {
            
            $resourceType = $object['{DAV:}resourcetype'];
            return $resourceType->is('{DAV:}collection');
        }

        return isset($object['{DAV:}iscollection']) && $object['{DAV:}iscollection'] === '1';
    }
}
