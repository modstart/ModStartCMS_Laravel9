<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\DAV\Inode;
use Sabre\DAV\PropFind;
use Sabre\HTTP\URLUtil;


class GuessContentType extends DAV\ServerPlugin {

    
    public $extensionMap = [

                'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',

                'ics' => 'text/calendar',
        'vcf' => 'text/vcard',

                'txt' => 'text/plain',

    ];

    
    function initialize(DAV\Server $server) {

                        $server->on('propFind', [$this, 'propFind'], 200);

    }

    
    function propFind(PropFind $propFind, INode $node) {

        $propFind->handle('{DAV:}getcontenttype', function() use ($propFind) {

            list(, $fileName) = URLUtil::splitPath($propFind->getPath());
            return $this->getContentType($fileName);

        });

    }

    
    protected function getContentType($fileName) {

                $extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
        if (isset($this->extensionMap[$extension])) {
            return $this->extensionMap[$extension];
        }
        return 'application/octet-stream';

    }

}
