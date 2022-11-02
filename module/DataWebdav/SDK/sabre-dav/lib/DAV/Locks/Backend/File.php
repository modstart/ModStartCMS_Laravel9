<?php

namespace Sabre\DAV\Locks\Backend;

use Sabre\DAV\Locks\LockInfo;


class File extends AbstractBackend {

    
    private $locksFile;

    
    function __construct($locksFile) {

        $this->locksFile = $locksFile;

    }

    
    function getLocks($uri, $returnChildLocks) {

        $newLocks = [];

        $locks = $this->getData();

        foreach ($locks as $lock) {

            if ($lock->uri === $uri ||
                                ($lock->depth != 0 && strpos($uri, $lock->uri . '/') === 0) ||

                                ($returnChildLocks && (strpos($lock->uri, $uri . '/') === 0))) {

                $newLocks[] = $lock;

            }

        }

                foreach ($newLocks as $k => $lock) {
            if (time() > $lock->timeout + $lock->created) unset($newLocks[$k]);
        }
        return $newLocks;

    }

    
    function lock($uri, LockInfo $lockInfo) {

                $lockInfo->timeout = 1800;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        $locks = $this->getData();

        foreach ($locks as $k => $lock) {
            if (
                ($lock->token == $lockInfo->token) ||
                (time() > $lock->timeout + $lock->created)
            ) {
                unset($locks[$k]);
            }
        }
        $locks[] = $lockInfo;
        $this->putData($locks);
        return true;

    }

    
    function unlock($uri, LockInfo $lockInfo) {

        $locks = $this->getData();
        foreach ($locks as $k => $lock) {

            if ($lock->token == $lockInfo->token) {

                unset($locks[$k]);
                $this->putData($locks);
                return true;

            }
        }
        return false;

    }

    
    protected function getData() {

        if (!file_exists($this->locksFile)) return [];

                $handle = fopen($this->locksFile, 'r');
        flock($handle, LOCK_SH);

                $data = stream_get_contents($handle);

                flock($handle, LOCK_UN);
        fclose($handle);

                $data = unserialize($data);
        if (!$data) return [];
        return $data;

    }

    
    protected function putData(array $newData) {

                $handle = fopen($this->locksFile, 'a+');
        flock($handle, LOCK_EX);

                ftruncate($handle, 0);
        rewind($handle);

        fwrite($handle, serialize($newData));
        flock($handle, LOCK_UN);
        fclose($handle);

    }

}
