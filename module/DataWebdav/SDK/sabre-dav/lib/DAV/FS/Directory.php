<?php

namespace Sabre\DAV\FS;

use Sabre\DAV;


class Directory extends Node implements DAV\ICollection, DAV\IQuota {

    
    function createFile($name, $data = null) {

        $newPath = $this->path . '/' . $name;
        file_put_contents($newPath, $data);
        clearstatcache(true, $newPath);

    }

    
    function createDirectory($name) {

        $newPath = $this->path . '/' . $name;
        mkdir($newPath);
        clearstatcache(true, $newPath);

    }

    
    function getChild($name) {

        $path = $this->path . '/' . $name;

        if (!file_exists($path)) throw new DAV\Exception\NotFound('File with name ' . $path . ' could not be located');

        if (is_dir($path)) {

            return new self($path);

        } else {

            return new File($path);

        }

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

    
    function childExists($name) {

        $path = $this->path . '/' . $name;
        return file_exists($path);

    }

    
    function delete() {

        foreach ($this->getChildren() as $child) $child->delete();
        rmdir($this->path);

    }

    
    function getQuotaInfo() {
        $absolute = realpath($this->path);
        return [
            disk_total_space($absolute) - disk_free_space($absolute),
            disk_free_space($absolute)
        ];

    }

}
