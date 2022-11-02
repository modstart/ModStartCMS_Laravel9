<?php

namespace Sabre\DAV\FSExt;

use Sabre\DAV;
use Sabre\DAV\FS\Node;


class Directory extends Node implements DAV\ICollection, DAV\IQuota, DAV\IMoveTarget {

    
    function createFile($name, $data = null) {

                if ($name == '.' || $name == '..') throw new DAV\Exception\Forbidden('Permission denied to . and ..');
        $newPath = $this->path . '/' . $name;
        file_put_contents($newPath, $data);
        clearstatcache(true, $newPath);

        return '"' . sha1(
            fileinode($newPath) .
            filesize($newPath) .
            filemtime($newPath)
        ) . '"';

    }

    
    function createDirectory($name) {

                if ($name == '.' || $name == '..') throw new DAV\Exception\Forbidden('Permission denied to . and ..');
        $newPath = $this->path . '/' . $name;
        mkdir($newPath);
        clearstatcache(true, $newPath);

    }

    
    function getChild($name) {

        $path = $this->path . '/' . $name;

        if (!file_exists($path)) throw new DAV\Exception\NotFound('File could not be located');
        if ($name == '.' || $name == '..') throw new DAV\Exception\Forbidden('Permission denied to . and ..');

        if (is_dir($path)) {

            return new self($path);

        } else {

            return new File($path);

        }

    }

    
    function childExists($name) {

        if ($name == '.' || $name == '..')
            throw new DAV\Exception\Forbidden('Permission denied to . and ..');

        $path = $this->path . '/' . $name;
        return file_exists($path);

    }

    
    function getChildren() {

        $nodes = [];
        $iterator = new \FilesystemIterator(
            $this->path,
            \FilesystemIterator::CURRENT_AS_SELF
          | \FilesystemIterator::SKIP_DOTS
        );

        foreach ($iterator as $entry) {

            $nodes[] = $this->getChild($entry->getFilename());

        }
        return $nodes;

    }

    
    function delete() {

                foreach ($this->getChildren() as $child) $child->delete();

                rmdir($this->path);

        return true;

    }

    
    function getQuotaInfo() {

        $total = disk_total_space(realpath($this->path));
        $free = disk_free_space(realpath($this->path));

        return [
            $total - $free,
            $free
        ];
    }

    
    function moveInto($targetName, $sourcePath, DAV\INode $sourceNode) {

                        if (!$sourceNode instanceof self && !$sourceNode instanceof File) {
            return false;
        }

                                rename($sourceNode->path, $this->path . '/' . $targetName);

        return true;

    }

}
