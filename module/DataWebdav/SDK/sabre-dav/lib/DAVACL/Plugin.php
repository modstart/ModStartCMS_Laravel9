<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAVACL\Exception\NeedPrivileges;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;


class Plugin extends DAV\ServerPlugin {

    
    const R_PARENT = 1;

    
    const R_RECURSIVE = 2;

    
    const R_RECURSIVEPARENTS = 3;

    
    protected $server;

    
    public $principalCollectionSet = [
        'principals',
    ];

    
    public $hideNodesFromListings = false;

    
    public $principalSearchPropertySet = [
        '{DAV:}displayname'                     => 'Display name',
        '{http://sabredav.org/ns}email-address' => 'Email address',
    ];

    
    public $adminPrincipals = [];

    
    public $allowUnauthenticatedAccess = true;

    
    function getFeatures() {

        return ['access-control', 'calendarserver-principal-property-search'];

    }

    
    function getMethods($uri) {

        return ['ACL'];

    }

    
    function getPluginName() {

        return 'acl';

    }

    
    function getSupportedReportSet($uri) {

        return [
            '{DAV:}expand-property',
            '{DAV:}principal-match',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set',
        ];

    }


    
    function checkPrivileges($uri, $privileges, $recursion = self::R_PARENT, $throwExceptions = true) {

        if (!is_array($privileges)) $privileges = [$privileges];

        $acl = $this->getCurrentUserPrivilegeSet($uri);

        $failed = [];
        foreach ($privileges as $priv) {

            if (!in_array($priv, $acl)) {
                $failed[] = $priv;
            }

        }

        if ($failed) {
            if ($this->allowUnauthenticatedAccess && is_null($this->getCurrentUserPrincipal())) {
                                $authPlugin = $this->server->getPlugin('auth');
                $reasons = $authPlugin->getLoginFailedReasons();
                $authPlugin->challenge(
                    $this->server->httpRequest,
                    $this->server->httpResponse
                );
                throw new notAuthenticated(implode(', ', $reasons) . '. Login was needed for privilege: ' . implode(', ', $failed) . ' on ' . $uri);
            }
            if ($throwExceptions) {

                throw new NeedPrivileges($uri, $failed);
            } else {
                return false;
            }
        }
        return true;

    }

    
    function getCurrentUserPrincipal() {

        
        $authPlugin = $this->server->getPlugin('auth');
        if (!$authPlugin) {
            return null;
        }
        return $authPlugin->getCurrentPrincipal();

    }


    
    function getCurrentUserPrincipals() {

        $currentUser = $this->getCurrentUserPrincipal();

        if (is_null($currentUser)) return [];

        return array_merge(
            [$currentUser],
            $this->getPrincipalMembership($currentUser)
        );

    }

    
    function setDefaultAcl(array $acl) {

        $this->defaultAcl = $acl;

    }

    
    function getDefaultAcl() {

        return $this->defaultAcl;

    }

    
    protected $defaultAcl = [
        [
            'principal' => '{DAV:}authenticated',
            'protected' => true,
            'privilege' => '{DAV:}all',
        ],
    ];

    
    protected $principalMembershipCache = [];


    
    function getPrincipalMembership($mainPrincipal) {

                if (isset($this->principalMembershipCache[$mainPrincipal])) {
            return $this->principalMembershipCache[$mainPrincipal];
        }

        $check = [$mainPrincipal];
        $principals = [];

        while (count($check)) {

            $principal = array_shift($check);

            $node = $this->server->tree->getNodeForPath($principal);
            if ($node instanceof IPrincipal) {
                foreach ($node->getGroupMembership() as $groupMember) {

                    if (!in_array($groupMember, $principals)) {

                        $check[] = $groupMember;
                        $principals[] = $groupMember;

                    }

                }

            }

        }

                $this->principalMembershipCache[$mainPrincipal] = $principals;

        return $principals;

    }

    
    function principalMatchesPrincipal($checkPrincipal, $currentPrincipal = null) {

        if (is_null($currentPrincipal)) {
            $currentPrincipal = $this->getCurrentUserPrincipal();
        }
        if ($currentPrincipal === $checkPrincipal) {
            return true;
        }
        return in_array(
            $checkPrincipal,
            $this->getPrincipalMembership($currentPrincipal)
        );

    }


    
    function getSupportedPrivilegeSet($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        $supportedPrivileges = null;
        if ($node instanceof IACL) {
            $supportedPrivileges = $node->getSupportedPrivilegeSet();
        }

        if (is_null($supportedPrivileges)) {

                        $supportedPrivileges = [
                '{DAV:}read' => [
                    'abstract'   => false,
                    'aggregates' => [
                        '{DAV:}read-acl' => [
                            'abstract'   => false,
                            'aggregates' => [],
                        ],
                        '{DAV:}read-current-user-privilege-set' => [
                            'abstract'   => false,
                            'aggregates' => [],
                        ],
                    ],
                ],
                '{DAV:}write' => [
                    'abstract'   => false,
                    'aggregates' => [
                        '{DAV:}write-properties' => [
                            'abstract'   => false,
                            'aggregates' => [],
                        ],
                        '{DAV:}write-content' => [
                            'abstract'   => false,
                            'aggregates' => [],
                        ],
                        '{DAV:}unlock' => [
                            'abstract'   => false,
                            'aggregates' => [],
                        ],
                    ],
                ],
            ];
            if ($node instanceof DAV\ICollection) {
                $supportedPrivileges['{DAV:}write']['aggregates']['{DAV:}bind'] = [
                    'abstract'   => false,
                    'aggregates' => [],
                ];
                $supportedPrivileges['{DAV:}write']['aggregates']['{DAV:}unbind'] = [
                    'abstract'   => false,
                    'aggregates' => [],
                ];
            }
            if ($node instanceof IACL) {
                $supportedPrivileges['{DAV:}write']['aggregates']['{DAV:}write-acl'] = [
                    'abstract'   => false,
                    'aggregates' => [],
                ];
            }

        }

        $this->server->emit(
            'getSupportedPrivilegeSet',
            [$node, &$supportedPrivileges]
        );

        return $supportedPrivileges;

    }

    
    final function getFlatPrivilegeSet($node) {

        $privs = [
            'abstract'   => false,
            'aggregates' => $this->getSupportedPrivilegeSet($node)
        ];

        $fpsTraverse = null;
        $fpsTraverse = function($privName, $privInfo, $concrete, &$flat) use (&$fpsTraverse) {

            $myPriv = [
                'privilege'  => $privName,
                'abstract'   => isset($privInfo['abstract']) && $privInfo['abstract'],
                'aggregates' => [],
                'concrete'   => isset($privInfo['abstract']) && $privInfo['abstract'] ? $concrete : $privName,
            ];

            if (isset($privInfo['aggregates'])) {

                foreach ($privInfo['aggregates'] as $subPrivName => $subPrivInfo) {

                    $myPriv['aggregates'][] = $subPrivName;

                }

            }

            $flat[$privName] = $myPriv;

            if (isset($privInfo['aggregates'])) {

                foreach ($privInfo['aggregates'] as $subPrivName => $subPrivInfo) {

                    $fpsTraverse($subPrivName, $subPrivInfo, $myPriv['concrete'], $flat);

                }

            }

        };

        $flat = [];
        $fpsTraverse('{DAV:}all', $privs, null, $flat);

        return $flat;

    }

    
    function getAcl($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }
        if (!$node instanceof IACL) {
            return $this->getDefaultAcl();
        }
        $acl = $node->getACL();
        foreach ($this->adminPrincipals as $adminPrincipal) {
            $acl[] = [
                'principal' => $adminPrincipal,
                'privilege' => '{DAV:}all',
                'protected' => true,
            ];
        }
        return $acl;

    }

    
    function getCurrentUserPrivilegeSet($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        $acl = $this->getACL($node);

        $collected = [];

        $isAuthenticated = $this->getCurrentUserPrincipal() !== null;

        foreach ($acl as $ace) {

            $principal = $ace['principal'];

            switch ($principal) {

                case '{DAV:}owner' :
                    $owner = $node->getOwner();
                    if ($owner && $this->principalMatchesPrincipal($owner)) {
                        $collected[] = $ace;
                    }
                    break;


                                case '{DAV:}all' :
                    $collected[] = $ace;
                    break;

                case '{DAV:}authenticated' :
                                        if ($isAuthenticated) {
                        $collected[] = $ace;
                    }
                    break;

                case '{DAV:}unauthenticated' :
                                        if (!$isAuthenticated) {
                        $collected[] = $ace;
                    }
                    break;

                default :
                    if ($this->principalMatchesPrincipal($ace['principal'])) {
                        $collected[] = $ace;
                    }
                    break;

            }


        }

                $flat = $this->getFlatPrivilegeSet($node);

        $collected2 = [];
        while (count($collected)) {

            $current = array_pop($collected);
            $collected2[] = $current['privilege'];

            if (!isset($flat[$current['privilege']])) {
                                $this->server->getLogger()->debug('A node has the "' . $current['privilege'] . '" in its ACL list, but this privilege was not reported in the supportedPrivilegeSet list. This will be ignored.');
                continue;
            }
            foreach ($flat[$current['privilege']]['aggregates'] as $subPriv) {
                $collected2[] = $subPriv;
                $collected[] = $flat[$subPriv];
            }

        }

        return array_values(array_unique($collected2));

    }


    
    function getPrincipalByUri($uri) {

        $result = null;
        $collections = $this->principalCollectionSet;
        foreach ($collections as $collection) {

            try {
                $principalCollection = $this->server->tree->getNodeForPath($collection);
            } catch (NotFound $e) {
                                continue;
            }

            if (!$principalCollection instanceof IPrincipalCollection) {
                                                continue;
            }

            $result = $principalCollection->findByUri($uri);
            if ($result) {
                return $result;
            }

        }

    }

    
    function principalSearch(array $searchProperties, array $requestedProperties, $collectionUri = null, $test = 'allof') {

        if (!is_null($collectionUri)) {
            $uris = [$collectionUri];
        } else {
            $uris = $this->principalCollectionSet;
        }

        $lookupResults = [];
        foreach ($uris as $uri) {

            $principalCollection = $this->server->tree->getNodeForPath($uri);
            if (!$principalCollection instanceof IPrincipalCollection) {
                                                continue;
            }

            $results = $principalCollection->searchPrincipals($searchProperties, $test);
            foreach ($results as $result) {
                $lookupResults[] = rtrim($uri, '/') . '/' . $result;
            }

        }

        $matches = [];

        foreach ($lookupResults as $lookupResult) {

            list($matches[]) = $this->server->getPropertiesForPath($lookupResult, $requestedProperties, 0);

        }

        return $matches;

    }

    
    function initialize(DAV\Server $server) {

        if ($this->allowUnauthenticatedAccess) {
            $authPlugin = $server->getPlugin('auth');
            if (!$authPlugin) {
                throw new \Exception('The Auth plugin must be loaded before the ACL plugin if you want to allow unauthenticated access.');
            }
            $authPlugin->autoRequireLogin = false;
        }

        $this->server = $server;
        $server->on('propFind',            [$this, 'propFind'], 20);
        $server->on('beforeMethod',        [$this, 'beforeMethod'], 20);
        $server->on('beforeBind',          [$this, 'beforeBind'], 20);
        $server->on('beforeUnbind',        [$this, 'beforeUnbind'], 20);
        $server->on('propPatch',           [$this, 'propPatch']);
        $server->on('beforeUnlock',        [$this, 'beforeUnlock'], 20);
        $server->on('report',              [$this, 'report']);
        $server->on('method:ACL',          [$this, 'httpAcl']);
        $server->on('onHTMLActionsPanel',  [$this, 'htmlActionsPanel']);
        $server->on('getPrincipalByUri',  function($principal, &$uri) {

            $uri = $this->getPrincipalByUri($principal);

                        if ($uri) return false;

        });

        array_push($server->protectedProperties,
            '{DAV:}alternate-URI-set',
            '{DAV:}principal-URL',
            '{DAV:}group-membership',
            '{DAV:}principal-collection-set',
            '{DAV:}current-user-principal',
            '{DAV:}supported-privilege-set',
            '{DAV:}current-user-privilege-set',
            '{DAV:}acl',
            '{DAV:}acl-restrictions',
            '{DAV:}inherited-acl-set',
            '{DAV:}owner',
            '{DAV:}group'
        );

                        $server->resourceTypeMapping['Sabre\\DAVACL\\IPrincipal'] = '{DAV:}principal';

                        $server->xml->elementMap['{DAV:}group-member-set'] = 'Sabre\\DAV\\Xml\\Property\\Href';
        $server->xml->elementMap['{DAV:}acl'] = 'Sabre\\DAVACL\\Xml\\Property\\Acl';
        $server->xml->elementMap['{DAV:}acl-principal-prop-set'] = 'Sabre\\DAVACL\\Xml\\Request\\AclPrincipalPropSetReport';
        $server->xml->elementMap['{DAV:}expand-property'] = 'Sabre\\DAVACL\\Xml\\Request\\ExpandPropertyReport';
        $server->xml->elementMap['{DAV:}principal-property-search'] = 'Sabre\\DAVACL\\Xml\\Request\\PrincipalPropertySearchReport';
        $server->xml->elementMap['{DAV:}principal-search-property-set'] = 'Sabre\\DAVACL\\Xml\\Request\\PrincipalSearchPropertySetReport';
        $server->xml->elementMap['{DAV:}principal-match'] = 'Sabre\\DAVACL\\Xml\\Request\\PrincipalMatchReport';

    }

    

    
    function beforeMethod(RequestInterface $request, ResponseInterface $response) {

        $method = $request->getMethod();
        $path = $request->getPath();

        $exists = $this->server->tree->nodeExists($path);

                if (!$exists) return;

        switch ($method) {

            case 'GET' :
            case 'HEAD' :
            case 'OPTIONS' :
                                $this->checkPrivileges($path, '{DAV:}read');
                break;

            case 'PUT' :
            case 'LOCK' :
                                                                                $this->checkPrivileges($path, '{DAV:}write-content');
                break;

            case 'UNLOCK' :
                                break;

            case 'PROPPATCH' :
                $this->checkPrivileges($path, '{DAV:}write-properties');
                break;

            case 'ACL' :
                $this->checkPrivileges($path, '{DAV:}write-acl');
                break;

            case 'COPY' :
            case 'MOVE' :
                                                                                                                                                                                                $this->checkPrivileges($path, '{DAV:}read', self::R_RECURSIVE);
                break;

        }

    }

    
    function beforeBind($uri) {

        list($parentUri) = Uri\split($uri);
        $this->checkPrivileges($parentUri, '{DAV:}bind');

    }

    
    function beforeUnbind($uri) {

        list($parentUri) = Uri\split($uri);
        $this->checkPrivileges($parentUri, '{DAV:}unbind', self::R_RECURSIVEPARENTS);

    }

    
    function beforeUnlock($uri, DAV\Locks\LockInfo $lock) {


    }

    
    function propFind(DAV\PropFind $propFind, DAV\INode $node) {

        $path = $propFind->getPath();

                if (!$this->checkPrivileges($path, '{DAV:}read', self::R_PARENT, false)) {
            
                                                if ($this->hideNodesFromListings) {
                return false;
            }

                        foreach ($propFind->getRequestedProperties() as $requestedProperty) {
                $propFind->set($requestedProperty, null, 403);
            }

            return;

        }

        
        if ($node instanceof IPrincipal) {

            $propFind->handle('{DAV:}alternate-URI-set', function() use ($node) {
                return new Href($node->getAlternateUriSet());
            });
            $propFind->handle('{DAV:}principal-URL', function() use ($node) {
                return new Href($node->getPrincipalUrl() . '/');
            });
            $propFind->handle('{DAV:}group-member-set', function() use ($node) {
                $members = $node->getGroupMemberSet();
                foreach ($members as $k => $member) {
                    $members[$k] = rtrim($member, '/') . '/';
                }
                return new Href($members);
            });
            $propFind->handle('{DAV:}group-membership', function() use ($node) {
                $members = $node->getGroupMembership();
                foreach ($members as $k => $member) {
                    $members[$k] = rtrim($member, '/') . '/';
                }
                return new Href($members);
            });
            $propFind->handle('{DAV:}displayname', [$node, 'getDisplayName']);

        }

        $propFind->handle('{DAV:}principal-collection-set', function() {

            $val = $this->principalCollectionSet;
                        foreach ($val as $k => $v) $val[$k] = $v . '/';
            return new Href($val);

        });
        $propFind->handle('{DAV:}current-user-principal', function() {
            if ($url = $this->getCurrentUserPrincipal()) {
                return new Xml\Property\Principal(Xml\Property\Principal::HREF, $url . '/');
            } else {
                return new Xml\Property\Principal(Xml\Property\Principal::UNAUTHENTICATED);
            }
        });
        $propFind->handle('{DAV:}supported-privilege-set', function() use ($node) {
            return new Xml\Property\SupportedPrivilegeSet($this->getSupportedPrivilegeSet($node));
        });
        $propFind->handle('{DAV:}current-user-privilege-set', function() use ($node, $propFind, $path) {
            if (!$this->checkPrivileges($path, '{DAV:}read-current-user-privilege-set', self::R_PARENT, false)) {
                $propFind->set('{DAV:}current-user-privilege-set', null, 403);
            } else {
                $val = $this->getCurrentUserPrivilegeSet($node);
                return new Xml\Property\CurrentUserPrivilegeSet($val);
            }
        });
        $propFind->handle('{DAV:}acl', function() use ($node, $propFind, $path) {
            
            if (!$this->checkPrivileges($path, '{DAV:}read-acl', self::R_PARENT, false)) {
                $propFind->set('{DAV:}acl', null, 403);
            } else {
                $acl = $this->getACL($node);
                return new Xml\Property\Acl($this->getACL($node));
            }
        });
        $propFind->handle('{DAV:}acl-restrictions', function() {
            return new Xml\Property\AclRestrictions();
        });

        
        if ($node instanceof IACL) {
            $propFind->handle('{DAV:}owner', function() use ($node) {
                return new Href($node->getOwner() . '/');
            });
        }

    }

    
    function propPatch($path, DAV\PropPatch $propPatch) {

        $propPatch->handle('{DAV:}group-member-set', function($value) use ($path) {
            if (is_null($value)) {
                $memberSet = [];
            } elseif ($value instanceof Href) {
                $memberSet = array_map(
                    [$this->server, 'calculateUri'],
                    $value->getHrefs()
                );
            } else {
                throw new DAV\Exception('The group-member-set property MUST be an instance of Sabre\DAV\Property\HrefList or null');
            }
            $node = $this->server->tree->getNodeForPath($path);
            if (!($node instanceof IPrincipal)) {
                                return false;
            }

            $node->setGroupMemberSet($memberSet);
            
            $this->principalMembershipCache = [];

            return true;
        });

    }

    
    function report($reportName, $report, $path) {

        switch ($reportName) {

            case '{DAV:}principal-property-search' :
                $this->server->transactionType = 'report-principal-property-search';
                $this->principalPropertySearchReport($path, $report);
                return false;
            case '{DAV:}principal-search-property-set' :
                $this->server->transactionType = 'report-principal-search-property-set';
                $this->principalSearchPropertySetReport($path, $report);
                return false;
            case '{DAV:}expand-property' :
                $this->server->transactionType = 'report-expand-property';
                $this->expandPropertyReport($path, $report);
                return false;
            case '{DAV:}principal-match' :
                $this->server->transactionType = 'report-principal-match';
                $this->principalMatchReport($path, $report);
                return false;
            case '{DAV:}acl-principal-prop-set' :
                $this->server->transactionType = 'acl-principal-prop-set';
                $this->aclPrincipalPropSetReport($path, $report);
                return false;

        }

    }

    
    function httpAcl(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();
        $body = $request->getBodyAsString();

        if (!$body) {
            throw new DAV\Exception\BadRequest('XML body expected in ACL request');
        }

        $acl = $this->server->xml->expect('{DAV:}acl', $body);
        $newAcl = $acl->getPrivileges();

                foreach ($newAcl as $k => $newAce) {
            $newAcl[$k]['principal'] = $this->server->calculateUri($newAce['principal']);
        }
        $node = $this->server->tree->getNodeForPath($path);

        if (!$node instanceof IACL) {
            throw new DAV\Exception\MethodNotAllowed('This node does not support the ACL method');
        }

        $oldAcl = $this->getACL($node);

        $supportedPrivileges = $this->getFlatPrivilegeSet($node);

        
        foreach ($oldAcl as $oldAce) {

            if (!isset($oldAce['protected']) || !$oldAce['protected']) continue;

            $found = false;
            foreach ($newAcl as $newAce) {
                if (
                    $newAce['privilege'] === $oldAce['privilege'] &&
                    $newAce['principal'] === $oldAce['principal'] &&
                    $newAce['protected']
                )
                $found = true;
            }

            if (!$found)
                throw new Exception\AceConflict('This resource contained a protected {DAV:}ace, but this privilege did not occur in the ACL request');

        }

        foreach ($newAcl as $newAce) {

                        if (!isset($supportedPrivileges[$newAce['privilege']])) {
                throw new Exception\NotSupportedPrivilege('The privilege you specified (' . $newAce['privilege'] . ') is not recognized by this server');
            }

            if ($supportedPrivileges[$newAce['privilege']]['abstract']) {
                throw new Exception\NoAbstract('The privilege you specified (' . $newAce['privilege'] . ') is an abstract privilege');
            }

                        try {
                $principal = $this->server->tree->getNodeForPath($newAce['principal']);
            } catch (NotFound $e) {
                throw new Exception\NotRecognizedPrincipal('The specified principal (' . $newAce['principal'] . ') does not exist');
            }
            if (!($principal instanceof IPrincipal)) {
                throw new Exception\NotRecognizedPrincipal('The specified uri (' . $newAce['principal'] . ') is not a principal');
            }

        }
        $node->setACL($newAcl);

        $response->setStatus(200);

                return false;

    }

    

    

    
    protected function principalMatchReport($path, Xml\Request\PrincipalMatchReport $report) {

        $depth = $this->server->getHTTPDepth(0);
        if ($depth !== 0) {
            throw new BadRequest('The principal-match report is only defined on Depth: 0');
        }

        $currentPrincipals = $this->getCurrentUserPrincipals();

        $result = [];

        if ($report->type === Xml\Request\PrincipalMatchReport::SELF) {

                                    foreach ($currentPrincipals as $currentPrincipal) {

                if ($currentPrincipal === $path || strpos($currentPrincipal, $path . '/') === 0) {
                    $result[] = $currentPrincipal;
                }

            }

        } else {

                                    $candidates = $this->server->getPropertiesForPath(
                $path,
                [$report->principalProperty],
                1
            );

            foreach ($candidates as $candidate) {

                if (!isset($candidate[200][$report->principalProperty])) {
                    continue;
                }

                $hrefs = $candidate[200][$report->principalProperty];

                if (!$hrefs instanceof Href) {
                    continue;
                }

                foreach ($hrefs->getHrefs() as $href) {
                    if (in_array(trim($href, '/'), $currentPrincipals)) {
                        $result[] = $candidate['href'];
                        continue 2;
                    }
                }
            }

        }

        $responses = [];

        foreach ($result as $item) {

            $properties = [];

            if ($report->properties) {

                $foo = $this->server->getPropertiesForPath($item, $report->properties);
                $foo = $foo[0];
                $item = $foo['href'];
                unset($foo['href']);
                $properties = $foo;

            }

            $responses[] = new DAV\Xml\Element\Response(
                $item,
                $properties,
                '200'
            );

        }

        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setBody(
            $this->server->xml->write(
                '{DAV:}multistatus',
                $responses,
                $this->server->getBaseUri()
            )
        );


    }

    
    protected function expandPropertyReport($path, $report) {

        $depth = $this->server->getHTTPDepth(0);

        $result = $this->expandProperties($path, $report->properties, $depth);

        $xml = $this->server->xml->write(
            '{DAV:}multistatus',
            new DAV\Xml\Response\MultiStatus($result),
            $this->server->getBaseUri()
        );
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setBody($xml);

    }

    
    protected function expandProperties($path, array $requestedProperties, $depth) {

        $foundProperties = $this->server->getPropertiesForPath($path, array_keys($requestedProperties), $depth);

        $result = [];

        foreach ($foundProperties as $node) {

            foreach ($requestedProperties as $propertyName => $childRequestedProperties) {

                                if (count($childRequestedProperties) === 0) continue;

                                                if (!array_key_exists($propertyName, $node[200])) continue;

                if (!$node[200][$propertyName] instanceof DAV\Xml\Property\Href) {
                    continue;
                }

                $childHrefs = $node[200][$propertyName]->getHrefs();
                $childProps = [];

                foreach ($childHrefs as $href) {
                                        $childProps[] = [
                        'name'  => '{DAV:}response',
                        'value' => $this->expandProperties($href, $childRequestedProperties, 0)[0]
                    ];
                }

                                $node[200][$propertyName] = $childProps;

            }
            $result[] = new DAV\Xml\Element\Response($node['href'], $node);

        }

        return $result;

    }

    
    protected function principalSearchPropertySetReport($path, $report) {

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth !== 0) {
            throw new DAV\Exception\BadRequest('This report is only defined when Depth: 0');
        }

        $writer = $this->server->xml->getWriter();
        $writer->openMemory();
        $writer->startDocument();

        $writer->startElement('{DAV:}principal-search-property-set');

        foreach ($this->principalSearchPropertySet as $propertyName => $description) {

            $writer->startElement('{DAV:}principal-search-property');
            $writer->startElement('{DAV:}prop');

            $writer->writeElement($propertyName);

            $writer->endElement(); 
            if ($description) {
                $writer->write([[
                    'name'       => '{DAV:}description',
                    'value'      => $description,
                    'attributes' => ['xml:lang' => 'en']
                ]]);
            }

            $writer->endElement(); 

        }

        $writer->endElement(); 
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setBody($writer->outputMemory());

    }

    
    protected function principalPropertySearchReport($path, Xml\Request\PrincipalPropertySearchReport $report) {

        if ($report->applyToPrincipalCollectionSet) {
            $path = null;
        }
        if ($this->server->getHttpDepth('0') !== 0) {
            throw new BadRequest('Depth must be 0');
        }
        $result = $this->principalSearch(
            $report->searchProperties,
            $report->properties,
            $path,
            $report->test
        );

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, $prefer['return'] === 'minimal'));

    }

    
    protected function aclPrincipalPropSetReport($path, Xml\Request\AclPrincipalPropSetReport $report) {

        if ($this->server->getHTTPDepth(0) !== 0) {
            throw new BadRequest('The {DAV:}acl-principal-prop-set REPORT only supports Depth 0');
        }

                                $acl = $this->server->getProperties($path, '{DAV:}acl');

        if (!$acl || !isset($acl['{DAV:}acl'])) {
            throw new Forbidden('Could not fetch ACL rules for this path');
        }

        $principals = [];
        foreach ($acl['{DAV:}acl']->getPrivileges() as $ace) {

            if ($ace['principal'][0] === '{') {
                                continue;
            }

            $principals[] = $ace['principal'];

        }

        $properties = $this->server->getPropertiesForMultiplePaths(
            $principals,
            $report->properties
        );

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setBody(
            $this->server->generateMultiStatus($properties)
        );

    }


    

    
    function htmlActionsPanel(DAV\INode $node, &$output) {

        if (!$node instanceof PrincipalCollection)
            return;

        $output .= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new principal</h3>
            <input type="hidden" name="sabreAction" value="mkcol" />
            <input type="hidden" name="resourceType" value="{DAV:}principal" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <label>Email address:</label> <input type="text" name="{http://sabredav*DOT*org/ns}email-address" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds support for WebDAV ACL (rfc3744)',
            'link'        => 'http://sabre.io/dav/acl/',
        ];

    }
}
