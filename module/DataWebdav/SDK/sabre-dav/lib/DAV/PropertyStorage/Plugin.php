<?php

namespace Sabre\DAV\PropertyStorage;

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;


class Plugin extends ServerPlugin {

    
    public $pathFilter;

    
    public $backend;

    
    function __construct(Backend\BackendInterface $backend) {

        $this->backend = $backend;

    }

    
    function initialize(Server $server) {

        $server->on('propFind',    [$this, 'propFind'], 130);
        $server->on('propPatch',   [$this, 'propPatch'], 300);
        $server->on('afterMove',   [$this, 'afterMove']);
        $server->on('afterUnbind', [$this, 'afterUnbind']);

    }

    
    function propFind(PropFind $propFind, INode $node) {

        $path = $propFind->getPath();
        $pathFilter = $this->pathFilter;
        if ($pathFilter && !$pathFilter($path)) return;
        $this->backend->propFind($propFind->getPath(), $propFind);

    }

    
    function propPatch($path, PropPatch $propPatch) {

        $pathFilter = $this->pathFilter;
        if ($pathFilter && !$pathFilter($path)) return;
        $this->backend->propPatch($path, $propPatch);

    }

    
    function afterUnbind($path) {

        $pathFilter = $this->pathFilter;
        if ($pathFilter && !$pathFilter($path)) return;
        $this->backend->delete($path);

    }

    
    function afterMove($source, $destination) {

        $pathFilter = $this->pathFilter;
        if ($pathFilter && !$pathFilter($source)) return;
                        if ($pathFilter && !$pathFilter($destination)) return;

        $this->backend->move($source, $destination);

    }

    
    function getPluginName() {

        return 'property-storage';

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'This plugin allows any arbitrary WebDAV property to be set on any resource.',
            'link'        => 'http://sabre.io/dav/property-storage/',
        ];

    }
}
