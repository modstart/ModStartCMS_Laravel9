<?php

namespace Sabre\DAV;


abstract class ServerPlugin {

    
    abstract function initialize(Server $server);

    
    function getFeatures() {

        return [];

    }

    
    function getHTTPMethods($path) {

        return [];

    }

    
    function getPluginName() {

        return get_class($this);

    }

    
    function getSupportedReportSet($uri) {

        return [];

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => null,
            'link'        => null,
        ];

    }

}
