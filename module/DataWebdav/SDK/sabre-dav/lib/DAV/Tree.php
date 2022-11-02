<?php

namespace Sabre\DAV;

use Sabre\HTTP\URLUtil;


class Tree {

    
    protected $rootNode;

    
    protected $cache = [];

    
    function __construct(ICollection $rootNode) {

        $this->rootNode = $rootNode;

    }

    
    function getNodeForPath($path) {

        $path = trim($path, '/');
        if (isset($this->cache[$path])) return $this->cache[$path];

                if (!strlen($path)) {
            return $this->rootNode;
        }

                list($parentName, $baseName) = URLUtil::splitPath($path);

                if ($parentName === "") {
            $node = $this->rootNode->getChild($baseName);
        } else {
                        $parent = $this->getNodeForPath($parentName);

            if (!($parent instanceof ICollection))
                throw new Exception\NotFound('Could not find node at path: ' . $path);

            $node = $parent->getChild($baseName);

        }

        $this->cache[$path] = $node;
        return $node;

    }

    
    function nodeExists($path) {

        try {

                        if ($path === '') return true;

            list($parent, $base) = URLUtil::splitPath($path);

            $parentNode = $this->getNodeForPath($parent);
            if (!$parentNode instanceof ICollection) return false;
            return $parentNode->childExists($base);

        } catch (Exception\NotFound $e) {

            return false;

        }

    }

    
    function copy($sourcePath, $destinationPath) {

        $sourceNode = $this->getNodeForPath($sourcePath);

                list($destinationDir, $destinationName) = URLUtil::splitPath($destinationPath);

        $destinationParent = $this->getNodeForPath($destinationDir);
        $this->copyNode($sourceNode, $destinationParent, $destinationName);

        $this->markDirty($destinationDir);

    }

    
    function move($sourcePath, $destinationPath) {

        list($sourceDir) = URLUtil::splitPath($sourcePath);
        list($destinationDir, $destinationName) = URLUtil::splitPath($destinationPath);

        if ($sourceDir === $destinationDir) {
                        $sourceNode = $this->getNodeForPath($sourcePath);
            $sourceNode->setName($destinationName);
        } else {
            $newParentNode = $this->getNodeForPath($destinationDir);
            $moveSuccess = false;
            if ($newParentNode instanceof IMoveTarget) {
                                $sourceNode = $this->getNodeForPath($sourcePath);
                $moveSuccess = $newParentNode->moveInto($destinationName, $sourcePath, $sourceNode);
            }
            if (!$moveSuccess) {
                $this->copy($sourcePath, $destinationPath);
                $this->getNodeForPath($sourcePath)->delete();
            }
        }
        $this->markDirty($sourceDir);
        $this->markDirty($destinationDir);

    }

    
    function delete($path) {

        $node = $this->getNodeForPath($path);
        $node->delete();

        list($parent) = URLUtil::splitPath($path);
        $this->markDirty($parent);

    }

    
    function getChildren($path) {

        $node = $this->getNodeForPath($path);
        $children = $node->getChildren();
        $basePath = trim($path, '/');
        if ($basePath !== '') $basePath .= '/';

        foreach ($children as $child) {

            $this->cache[$basePath . $child->getName()] = $child;

        }
        return $children;

    }

    
    function markDirty($path) {

                        $path = trim($path, '/');
        foreach ($this->cache as $nodePath => $node) {
            if ($path === '' || $nodePath == $path || strpos($nodePath, $path . '/') === 0)
                unset($this->cache[$nodePath]);

        }

    }

    
    function getMultipleNodes($paths) {

                $parents = [];
        foreach ($paths as $path) {
            list($parent, $node) = URLUtil::splitPath($path);
            if (!isset($parents[$parent])) {
                $parents[$parent] = [$node];
            } else {
                $parents[$parent][] = $node;
            }
        }

        $result = [];

        foreach ($parents as $parent => $children) {

            $parentNode = $this->getNodeForPath($parent);
            if ($parentNode instanceof IMultiGet) {
                foreach ($parentNode->getMultipleChildren($children) as $childNode) {
                    $fullPath = $parent . '/' . $childNode->getName();
                    $result[$fullPath] = $childNode;
                    $this->cache[$fullPath] = $childNode;
                }
            } else {
                foreach ($children as $child) {
                    $fullPath = $parent . '/' . $child;
                    $result[$fullPath] = $this->getNodeForPath($fullPath);
                }
            }

        }

        return $result;

    }


    
    protected function copyNode(INode $source, ICollection $destinationParent, $destinationName = null) {

        if ((string)$destinationName === '') {
            $destinationName = $source->getName();
        }

        if ($source instanceof IFile) {

            $data = $source->get();

                        if (is_string($data)) {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $data);
                rewind($stream);
                $data = $stream;
            }
            $destinationParent->createFile($destinationName, $data);
            $destination = $destinationParent->getChild($destinationName);

        } elseif ($source instanceof ICollection) {

            $destinationParent->createDirectory($destinationName);

            $destination = $destinationParent->getChild($destinationName);
            foreach ($source->getChildren() as $child) {

                $this->copyNode($child, $destination);

            }

        }
        if ($source instanceof IProperties && $destination instanceof IProperties) {

            $props = $source->getProperties([]);
            $propPatch = new PropPatch($props);
            $destination->propPatch($propPatch);
            $propPatch->commit();

        }

    }

}
