<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;


class VCFExportPlugin extends DAV\ServerPlugin {

    
    protected $server;

    
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('method:GET', [$this, 'httpGet'], 90);
        $server->on('browserButtonActions', function($path, $node, &$actions) {
            if ($node instanceof IAddressBook) {
                $actions .= '<a href="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '?export"><span class="oi" data-glyph="book"></span></a>';
            }
        });
    }

    
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        $queryParams = $request->getQueryParameters();
        if (!array_key_exists('export', $queryParams)) return;

        $path = $request->getPath();

        $node = $this->server->tree->getNodeForPath($path);

        if (!($node instanceof IAddressBook)) return;

        $this->server->transactionType = 'get-addressbook-export';

                if ($aclPlugin = $this->server->getPlugin('acl')) {
            $aclPlugin->checkPrivileges($path, '{DAV:}read');
        }

        $nodes = $this->server->getPropertiesForPath($path, [
            '{' . Plugin::NS_CARDDAV . '}address-data',
        ], 1);

        $format = 'text/directory';

        $output = null;
        $filenameExtension = null;

        switch ($format) {
            case 'text/directory':
                $output = $this->generateVCF($nodes);
                $filenameExtension = '.vcf';
                break;
        }

        $filename = preg_replace(
            '/[^a-zA-Z0-9-_ ]/um',
            '',
            $node->getName()
        );
        $filename .= '-' . date('Y-m-d') . $filenameExtension;

        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Content-Type', $format);

        $response->setStatus(200);
        $response->setBody($output);

                return false;

    }

    
    function generateVCF(array $nodes) {

        $output = "";

        foreach ($nodes as $node) {

            if (!isset($node[200]['{' . Plugin::NS_CARDDAV . '}address-data'])) {
                continue;
            }
            $nodeData = $node[200]['{' . Plugin::NS_CARDDAV . '}address-data'];

                        $vcard = VObject\Reader::read($nodeData);
            $output .= $vcard->serialize();

                        $vcard->destroy();

        }

        return $output;

    }

    
    function getPluginName() {

        return 'vcf-export';

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds the ability to export CardDAV addressbooks as a single vCard file.',
            'link'        => 'http://sabre.io/dav/vcf-export-plugin/',
        ];

    }

}
