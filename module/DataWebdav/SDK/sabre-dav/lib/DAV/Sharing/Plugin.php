<?php

namespace Sabre\DAV\Sharing;

use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\DAV\Xml\Property;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class Plugin extends ServerPlugin {

    const ACCESS_NOTSHARED = 0;
    const ACCESS_SHAREDOWNER = 1;
    const ACCESS_READ = 2;
    const ACCESS_READWRITE = 3;
    const ACCESS_NOACCESS = 4;

    const INVITE_NORESPONSE = 1;
    const INVITE_ACCEPTED = 2;
    const INVITE_DECLINED = 3;
    const INVITE_INVALID = 4;

    
    protected $server;

    
    function getFeatures() {

        return ['resource-sharing'];

    }

    
    function getPluginName() {

        return 'sharing';

    }

    
    function initialize(Server $server) {

        $this->server = $server;

        $server->xml->elementMap['{DAV:}share-resource'] = 'Sabre\\DAV\\Xml\\Request\\ShareResource';

        array_push(
            $server->protectedProperties,
            '{DAV:}share-mode'
        );

        $server->on('method:POST',              [$this, 'httpPost']);
        $server->on('propFind',                 [$this, 'propFind']);
        $server->on('getSupportedPrivilegeSet', [$this, 'getSupportedPrivilegeSet']);
        $server->on('onHTMLActionsPanel',       [$this, 'htmlActionsPanel']);
        $server->on('onBrowserPostAction',      [$this, 'browserPostAction']);

    }

    
    function shareResource($path, array $sharees) {

        $node = $this->server->tree->getNodeForPath($path);

        if (!$node instanceof ISharedNode) {

            throw new Forbidden('Sharing is not allowed on this node');

        }

                $acl = $this->server->getPlugin('acl');

                if ($acl) {
            $acl->checkPrivileges($path, '{DAV:}share');
        }

        foreach ($sharees as $sharee) {
                                    $principal = null;
            $this->server->emit('getPrincipalByUri', [$sharee->href, &$principal]);
            $sharee->principal = $principal;
        }
        $node->updateInvites($sharees);

    }

    
    function propFind(PropFind $propFind, INode $node) {

        if ($node instanceof ISharedNode) {

            $propFind->handle('{DAV:}share-access', function() use ($node) {

                return new Property\ShareAccess($node->getShareAccess());

            });
            $propFind->handle('{DAV:}invite', function() use ($node) {

                return new Property\Invite($node->getInvites());

            });
            $propFind->handle('{DAV:}share-resource-uri', function() use ($node) {

                return new Property\Href($node->getShareResourceUri());

            });

        }

    }

    
    function httpPost(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();
        $contentType = $request->getHeader('Content-Type');

                if (strpos($contentType, 'application/davsharing+xml') === false) {
            return;
        }

        $message = $this->server->xml->parse(
            $request->getBody(),
            $request->getUrl(),
            $documentType
        );

        switch ($documentType) {

            case '{DAV:}share-resource':

                $this->shareResource($path, $message->sharees);
                $response->setStatus(200);
                                                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                                return false;

            default :
                throw new BadRequest('Unexpected document type: ' . $documentType . ' for this Content-Type');

        }

    }

    
    function getSupportedPrivilegeSet(INode $node, array &$supportedPrivilegeSet) {

        if ($node instanceof ISharedNode) {
            $supportedPrivilegeSet['{DAV:}share'] = [
                'abstract'   => false,
                'aggregates' => [],
            ];
        }
    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'This plugin implements WebDAV resource sharing',
            'link'        => 'https://github.com/evert/webdav-sharing'
        ];

    }

    
    function htmlActionsPanel(INode $node, &$output, $path) {

        if (!$node instanceof ISharedNode) {
            return;
        }

        $aclPlugin = $this->server->getPlugin('acl');
        if ($aclPlugin) {
            if (!$aclPlugin->checkPrivileges($path, '{DAV:}share', \Sabre\DAVACL\Plugin::R_PARENT, false)) {
                                return;
            }
        }

        $output .= '<tr><td colspan="2"><form method="post" action="">
            <h3>Share this resource</h3>
            <input type="hidden" name="sabreAction" value="share" />
            <label>Share with (uri):</label> <input type="text" name="href" placeholder="mailto:user@example.org"/><br />
            <label>Access</label>
                <select name="access">
                    <option value="readwrite">Read-write</option>
                    <option value="read">Read-only</option>
                    <option value="no-access">Revoke access</option>
                </select><br />
             <input type="submit" value="share" />
            </form>
            </td></tr>';

    }

    
    function browserPostAction($path, $action, $postVars) {

        if ($action !== 'share') {
            return;
        }

        if (empty($postVars['href'])) {
            throw new BadRequest('The "href" POST parameter is required');
        }
        if (empty($postVars['access'])) {
            throw new BadRequest('The "access" POST parameter is required');
        }

        $accessMap = [
            'readwrite' => self::ACCESS_READWRITE,
            'read'      => self::ACCESS_READ,
            'no-access' => self::ACCESS_NOACCESS,
        ];

        if (!isset($accessMap[$postVars['access']])) {
            throw new BadRequest('The "access" POST must be readwrite, read or no-access');
        }
        $sharee = new Sharee([
            'href'   => $postVars['href'],
            'access' => $accessMap[$postVars['access']],
        ]);

        $this->shareResource(
            $path,
            [$sharee]
        );
        return false;

    }

}
