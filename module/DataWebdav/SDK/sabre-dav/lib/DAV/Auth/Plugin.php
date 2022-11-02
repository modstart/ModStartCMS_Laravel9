<?php

namespace Sabre\DAV\Auth;

use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class Plugin extends ServerPlugin {

    
    public $autoRequireLogin = true;

    
    protected $backends;

    
    protected $currentPrincipal;

    
    function __construct(Backend\BackendInterface $authBackend = null) {

        if (!is_null($authBackend)) {
            $this->addBackend($authBackend);
        }

    }

    
    function addBackend(Backend\BackendInterface $authBackend) {

        $this->backends[] = $authBackend;

    }

    
    function initialize(Server $server) {

        $server->on('beforeMethod', [$this, 'beforeMethod'], 10);

    }

    
    function getPluginName() {

        return 'auth';

    }

    
    function getCurrentPrincipal() {

        return $this->currentPrincipal;

    }

    
    function beforeMethod(RequestInterface $request, ResponseInterface $response) {

        if ($this->currentPrincipal) {

                                                                                                                                                            return;

        }

        $authResult = $this->check($request, $response);

        if ($authResult[0]) {
                        $this->currentPrincipal = $authResult[1];
            $this->loginFailedReasons = null;
            return;
        }



                        $this->currentPrincipal = null;
        $this->loginFailedReasons = $authResult[1];

        if ($this->autoRequireLogin) {
            $this->challenge($request, $response);
            throw new NotAuthenticated(implode(', ', $authResult[1]));
        }

    }

    
    function check(RequestInterface $request, ResponseInterface $response) {

        if (!$this->backends) {
            throw new \Sabre\DAV\Exception('No authentication backends were configured on this server.');
        }
        $reasons = [];
        foreach ($this->backends as $backend) {

            $result = $backend->check(
                $request,
                $response
            );

            if (!is_array($result) || count($result) !== 2 || !is_bool($result[0]) || !is_string($result[1])) {
                throw new \Sabre\DAV\Exception('The authentication backend did not return a correct value from the check() method.');
            }

            if ($result[0]) {
                $this->currentPrincipal = $result[1];
                                return [true, $result[1]];
            }
            $reasons[] = $result[1];

        }

        return [false, $reasons];

    }

    
    function challenge(RequestInterface $request, ResponseInterface $response) {

        foreach ($this->backends as $backend) {
            $backend->challenge($request, $response);
        }

    }

    
    protected $loginFailedReasons;

    
    function getLoginFailedReasons() {

        return $this->loginFailedReasons;

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Generic authentication plugin',
            'link'        => 'http://sabre.io/dav/authentication/',
        ];

    }

}
