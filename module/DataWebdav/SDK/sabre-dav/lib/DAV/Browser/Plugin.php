<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\URLUtil;


class Plugin extends DAV\ServerPlugin {

    
    protected $server;

    
    protected $enablePost = true;

    
    public $uninterestingProperties = [
        '{DAV:}supportedlock',
        '{DAV:}acl-restrictions',
        '{DAV:}supported-method-set',
    ];

    
    function __construct($enablePost = true) {

        $this->enablePost = $enablePost;

    }

    
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('method:GET', [$this, 'httpGetEarly'], 90);
        $this->server->on('method:GET', [$this, 'httpGet'], 200);
        $this->server->on('onHTMLActionsPanel', [$this, 'htmlActionsPanel'], 200);
        if ($this->enablePost) $this->server->on('method:POST', [$this, 'httpPOST']);
    }

    
    function httpGetEarly(RequestInterface $request, ResponseInterface $response) {

        $params = $request->getQueryParameters();
        if (isset($params['sabreAction']) && $params['sabreAction'] === 'info') {
            return $this->httpGet($request, $response);
        }

    }

    
    function httpGet(RequestInterface $request, ResponseInterface $response) {

                        $getVars = $request->getQueryParameters();

                $response->setHeader('Content-Security-Policy', "default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';");

        $sabreAction = isset($getVars['sabreAction']) ? $getVars['sabreAction'] : null;

        switch ($sabreAction) {

            case 'asset' :
                                $this->serveAsset(isset($getVars['assetName']) ? $getVars['assetName'] : null);
                return false;
            default :
            case 'info' :
                try {
                    $this->server->tree->getNodeForPath($request->getPath());
                } catch (DAV\Exception\NotFound $e) {
                                                            return;
                }

                $response->setStatus(200);
                $response->setHeader('Content-Type', 'text/html; charset=utf-8');

                $response->setBody(
                    $this->generateDirectoryIndex($request->getPath())
                );

                return false;

            case 'plugins' :
                $response->setStatus(200);
                $response->setHeader('Content-Type', 'text/html; charset=utf-8');

                $response->setBody(
                    $this->generatePluginListing()
                );

                return false;

        }

    }

    
    function httpPOST(RequestInterface $request, ResponseInterface $response) {

        $contentType = $request->getHeader('Content-Type');
        list($contentType) = explode(';', $contentType);
        if ($contentType !== 'application/x-www-form-urlencoded' &&
            $contentType !== 'multipart/form-data') {
                return;
        }
        $postVars = $request->getPostData();

        if (!isset($postVars['sabreAction']))
            return;

        $uri = $request->getPath();

        if ($this->server->emit('onBrowserPostAction', [$uri, $postVars['sabreAction'], $postVars])) {

            switch ($postVars['sabreAction']) {

                case 'mkcol' :
                    if (isset($postVars['name']) && trim($postVars['name'])) {
                                                list(, $folderName) = URLUtil::splitPath(trim($postVars['name']));

                        if (isset($postVars['resourceType'])) {
                            $resourceType = explode(',', $postVars['resourceType']);
                        } else {
                            $resourceType = ['{DAV:}collection'];
                        }

                        $properties = [];
                        foreach ($postVars as $varName => $varValue) {
                                                                                    if ($varName[0] === '{') {
                                                                                                                                                                                                                                $varName = str_replace('*DOT*', '.', $varName);
                                $properties[$varName] = $varValue;
                            }
                        }

                        $mkCol = new MkCol(
                            $resourceType,
                            $properties
                        );
                        $this->server->createCollection($uri . '/' . $folderName, $mkCol);
                    }
                    break;

                                case 'put' :

                    if ($_FILES) $file = current($_FILES);
                    else break;

                    list(, $newName) = URLUtil::splitPath(trim($file['name']));
                    if (isset($postVars['name']) && trim($postVars['name']))
                        $newName = trim($postVars['name']);

                                        list(, $newName) = URLUtil::splitPath($newName);

                    if (is_uploaded_file($file['tmp_name'])) {
                        $this->server->createFile($uri . '/' . $newName, fopen($file['tmp_name'], 'r'));
                    }
                    break;
                
            }

        }
        $response->setHeader('Location', $request->getUrl());
        $response->setStatus(302);
        return false;

    }

    
    function escapeHTML($value) {

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

    }

    
    function generateDirectoryIndex($path) {

        $html = $this->generateHeader($path ? $path : '/', $path);

        $node = $this->server->tree->getNodeForPath($path);
        if ($node instanceof DAV\ICollection) {

            $html .= "<section><h1>Nodes</h1>\n";
            $html .= "<table class=\"nodeTable\">";

            $subNodes = $this->server->getPropertiesForChildren($path, [
                '{DAV:}displayname',
                '{DAV:}resourcetype',
                '{DAV:}getcontenttype',
                '{DAV:}getcontentlength',
                '{DAV:}getlastmodified',
            ]);

            foreach ($subNodes as $subPath => $subProps) {

                $subNode = $this->server->tree->getNodeForPath($subPath);
                $fullPath = $this->server->getBaseUri() . URLUtil::encodePath($subPath);
                list(, $displayPath) = URLUtil::splitPath($subPath);

                $subNodes[$subPath]['subNode'] = $subNode;
                $subNodes[$subPath]['fullPath'] = $fullPath;
                $subNodes[$subPath]['displayPath'] = $displayPath;
            }
            uasort($subNodes, [$this, 'compareNodes']);

            foreach ($subNodes as $subProps) {
                $type = [
                    'string' => 'Unknown',
                    'icon'   => 'cog',
                ];
                if (isset($subProps['{DAV:}resourcetype'])) {
                    $type = $this->mapResourceType($subProps['{DAV:}resourcetype']->getValue(), $subProps['subNode']);
                }

                $html .= '<tr>';
                $html .= '<td class="nameColumn"><a href="' . $this->escapeHTML($subProps['fullPath']) . '"><span class="oi" data-glyph="' . $this->escapeHTML($type['icon']) . '"></span> ' . $this->escapeHTML($subProps['displayPath']) . '</a></td>';
                $html .= '<td class="typeColumn">' . $this->escapeHTML($type['string']) . '</td>';
                $html .= '<td>';
                if (isset($subProps['{DAV:}getcontentlength'])) {
                    $html .= $this->escapeHTML($subProps['{DAV:}getcontentlength'] . ' bytes');
                }
                $html .= '</td><td>';
                if (isset($subProps['{DAV:}getlastmodified'])) {
                    $lastMod = $subProps['{DAV:}getlastmodified']->getTime();
                    $html .= $this->escapeHTML($lastMod->format('F j, Y, g:i a'));
                }
                $html .= '</td>';

                $buttonActions = '';
                if ($subProps['subNode'] instanceof DAV\IFile) {
                    $buttonActions = '<a href="' . $this->escapeHTML($subProps['fullPath']) . '?sabreAction=info"><span class="oi" data-glyph="info"></span></a>';
                }
                $this->server->emit('browserButtonActions', [$subProps['fullPath'], $subProps['subNode'], &$buttonActions]);

                $html .= '<td>' . $buttonActions . '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';

        }

        $html .= "</section>";
        $html .= "<section><h1>Properties</h1>";
        $html .= "<table class=\"propTable\">";

                $propFind = new PropFindAll($path);
        $properties = $this->server->getPropertiesByNode($propFind, $node);

        $properties = $propFind->getResultForMultiStatus()[200];

        foreach ($properties as $propName => $propValue) {
            if (!in_array($propName, $this->uninterestingProperties)) {
                $html .= $this->drawPropertyRow($propName, $propValue);
            }

        }


        $html .= "</table>";
        $html .= "</section>";

        

        $output = '';
        if ($this->enablePost) {
            $this->server->emit('onHTMLActionsPanel', [$node, &$output, $path]);
        }

        if ($output) {

            $html .= "<section><h1>Actions</h1>";
            $html .= "<div class=\"actions\">\n";
            $html .= $output;
            $html .= "</div>\n";
            $html .= "</section>\n";
        }

        $html .= $this->generateFooter();

        $this->server->httpResponse->setHeader('Content-Security-Policy', "default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';");

        return $html;

    }

    
    function generatePluginListing() {

        $html = $this->generateHeader('Plugins');

        $html .= "<section><h1>Plugins</h1>";
        $html .= "<table class=\"propTable\">";
        foreach ($this->server->getPlugins() as $plugin) {
            $info = $plugin->getPluginInfo();
            $html .= '<tr><th>' . $info['name'] . '</th>';
            $html .= '<td>' . $info['description'] . '</td>';
            $html .= '<td>';
            if (isset($info['link']) && $info['link']) {
                $html .= '<a href="' . $this->escapeHTML($info['link']) . '"><span class="oi" data-glyph="book"></span></a>';
            }
            $html .= '</td></tr>';
        }
        $html .= "</table>";
        $html .= "</section>";

        

        $html .= $this->generateFooter();

        return $html;

    }

    
    function generateHeader($title, $path = null) {

        $version = '';
        if (DAV\Server::$exposeVersion) {
            $version = DAV\Version::VERSION;
        }

        $vars = [
            'title'     => $this->escapeHTML($title),
            'favicon'   => $this->escapeHTML($this->getAssetUrl('favicon.ico')),
            'style'     => $this->escapeHTML($this->getAssetUrl('sabredav.css')),
            'iconstyle' => $this->escapeHTML($this->getAssetUrl('openiconic/open-iconic.css')),
            'logo'      => $this->escapeHTML($this->getAssetUrl('sabredav.png')),
            'baseUrl'   => $this->server->getBaseUri(),
        ];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>$vars[title] - sabre/dav $version</title>
    <link rel="shortcut icon" href="$vars[favicon]"   type="image/vnd.microsoft.icon" />
    <link rel="stylesheet"    href="$vars[style]"     type="text/css" />
    <link rel="stylesheet"    href="$vars[iconstyle]" type="text/css" />

</head>
<body>
    <header>
        <div class="logo">
            <a href="$vars[baseUrl]"><img src="$vars[logo]" alt="sabre/dav" /> $vars[title]</a>
        </div>
    </header>

    <nav>
HTML;

                if ($path)  {
            list($parentUri) = URLUtil::splitPath($path);
            $fullPath = $this->server->getBaseUri() . URLUtil::encodePath($parentUri);
            $html .= '<a href="' . $fullPath . '" class="btn">⇤ Go to parent</a>';
        } else {
            $html .= '<span class="btn disabled">⇤ Go to parent</span>';
        }

        $html .= ' <a href="?sabreAction=plugins" class="btn"><span class="oi" data-glyph="puzzle-piece"></span> Plugins</a>';

        $html .= "</nav>";

        return $html;

    }

    
    function generateFooter() {

        $version = '';
        if (DAV\Server::$exposeVersion) {
            $version = DAV\Version::VERSION;
        }
        return <<<HTML
<footer>Generated by SabreDAV $version (c)2007-2016 <a href="http://sabre.io/">http://sabre.io/</a></footer>
</body>
</html>
HTML;

    }

    
    function htmlActionsPanel(DAV\INode $node, &$output, $path) {

        if (!$node instanceof DAV\ICollection)
            return;

                        if (get_class($node) === 'Sabre\\DAV\\SimpleCollection')
            return;

        $output .= <<<HTML
<form method="post" action="">
<h3>Create new folder</h3>
<input type="hidden" name="sabreAction" value="mkcol" />
<label>Name:</label> <input type="text" name="name" /><br />
<input type="submit" value="create" />
</form>
<form method="post" action="" enctype="multipart/form-data">
<h3>Upload file</h3>
<input type="hidden" name="sabreAction" value="put" />
<label>Name (optional):</label> <input type="text" name="name" /><br />
<label>File:</label> <input type="file" name="file" /><br />
<input type="submit" value="upload" />
</form>
HTML;

    }

    
    protected function getAssetUrl($assetName) {

        return $this->server->getBaseUri() . '?sabreAction=asset&assetName=' . urlencode($assetName);

    }

    
    protected function getLocalAssetPath($assetName) {

        $assetDir = __DIR__ . '/assets/';
        $path = $assetDir . $assetName;

                $path = str_replace('\\', '/', $path);
        if (strpos($path, '/../') !== false || strrchr($path, '/') === '/..') {
            throw new DAV\Exception\NotFound('Path does not exist, or escaping from the base path was detected');
        }
        if (strpos(realpath($path), realpath($assetDir)) === 0 && file_exists($path)) {
            return $path;
        }
        throw new DAV\Exception\NotFound('Path does not exist, or escaping from the base path was detected');
    }

    
    protected function serveAsset($assetName) {

        $assetPath = $this->getLocalAssetPath($assetName);

                $mime = 'application/octet-stream';
        $map = [
            'ico' => 'image/vnd.microsoft.icon',
            'png' => 'image/png',
            'css' => 'text/css',
        ];

        $ext = substr($assetName, strrpos($assetName, '.') + 1);
        if (isset($map[$ext])) {
            $mime = $map[$ext];
        }

        $this->server->httpResponse->setHeader('Content-Type', $mime);
        $this->server->httpResponse->setHeader('Content-Length', filesize($assetPath));
        $this->server->httpResponse->setHeader('Cache-Control', 'public, max-age=1209600');
        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setBody(fopen($assetPath, 'r'));

    }

    
    protected function compareNodes($a, $b) {

        $typeA = (isset($a['{DAV:}resourcetype']))
            ? (in_array('{DAV:}collection', $a['{DAV:}resourcetype']->getValue()))
            : false;

        $typeB = (isset($b['{DAV:}resourcetype']))
            ? (in_array('{DAV:}collection', $b['{DAV:}resourcetype']->getValue()))
            : false;

                if ($typeA === $typeB) {
            return strnatcasecmp($a['displayPath'], $b['displayPath']);
        }
        return (($typeA < $typeB) ? 1 : -1);

    }

    
    private function mapResourceType(array $resourceTypes, $node) {

        if (!$resourceTypes) {
            if ($node instanceof DAV\IFile) {
                return [
                    'string' => 'File',
                    'icon'   => 'file',
                ];
            } else {
                return [
                    'string' => 'Unknown',
                    'icon'   => 'cog',
                ];
            }
        }

        $types = [
            '{http://calendarserver.org/ns/}calendar-proxy-write' => [
                'string' => 'Proxy-Write',
                'icon'   => 'people',
            ],
            '{http://calendarserver.org/ns/}calendar-proxy-read' => [
                'string' => 'Proxy-Read',
                'icon'   => 'people',
            ],
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox' => [
                'string' => 'Outbox',
                'icon'   => 'inbox',
            ],
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox' => [
                'string' => 'Inbox',
                'icon'   => 'inbox',
            ],
            '{urn:ietf:params:xml:ns:caldav}calendar' => [
                'string' => 'Calendar',
                'icon'   => 'calendar',
            ],
            '{http://calendarserver.org/ns/}shared-owner' => [
                'string' => 'Shared',
                'icon'   => 'calendar',
            ],
            '{http://calendarserver.org/ns/}subscribed' => [
                'string' => 'Subscription',
                'icon'   => 'calendar',
            ],
            '{urn:ietf:params:xml:ns:carddav}directory' => [
                'string' => 'Directory',
                'icon'   => 'globe',
            ],
            '{urn:ietf:params:xml:ns:carddav}addressbook' => [
                'string' => 'Address book',
                'icon'   => 'book',
            ],
            '{DAV:}principal' => [
                'string' => 'Principal',
                'icon'   => 'person',
            ],
            '{DAV:}collection' => [
                'string' => 'Collection',
                'icon'   => 'folder',
            ],
        ];

        $info = [
            'string' => [],
            'icon'   => 'cog',
        ];
        foreach ($resourceTypes as $k => $resourceType) {
            if (isset($types[$resourceType])) {
                $info['string'][] = $types[$resourceType]['string'];
            } else {
                $info['string'][] = $resourceType;
            }
        }
        foreach ($types as $key => $resourceInfo) {
            if (in_array($key, $resourceTypes)) {
                $info['icon'] = $resourceInfo['icon'];
                break;
            }
        }
        $info['string'] = implode(', ', $info['string']);

        return $info;

    }

    
    private function drawPropertyRow($name, $value) {

        $html = new HtmlOutputHelper(
            $this->server->getBaseUri(),
            $this->server->xml->namespaceMap
        );

        return "<tr><th>" . $html->xmlName($name) . "</th><td>" . $this->drawPropertyValue($html, $value) . "</td></tr>";

    }

    
    private function drawPropertyValue($html, $value) {

        if (is_scalar($value)) {
            return $html->h($value);
        } elseif ($value instanceof HtmlOutput) {
            return $value->toHtml($html);
        } elseif ($value instanceof \Sabre\Xml\XmlSerializable) {

                                    $xml = $this->server->xml->write('{DAV:}root', $value, $this->server->getBaseUri());
                                    $xml = explode("\n", $xml);
            $xml = array_slice($xml, 2, -2);
            return "<pre>" . $html->h(implode("\n", $xml)) . "</pre>";

        } else {
            return "<em>unknown</em>";
        }

    }

    
    function getPluginName() {

        return 'browser';

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Generates HTML indexes and debug information for your sabre/dav server',
            'link'        => 'http://sabre.io/dav/browser-plugin/',
        ];

    }

}
