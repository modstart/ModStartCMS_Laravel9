<?php

namespace Sabre\Uri;




function resolve($basePath, $newPath) {

    $base = parse($basePath);
    $delta = parse($newPath);

    $pick = function($part) use ($base, $delta) {

        if ($delta[$part]) {
            return $delta[$part];
        } elseif ($base[$part]) {
            return $base[$part];
        }
        return null;

    };

            if ($delta['scheme']) {
        return build($delta);
    }

    $newParts = [];

    $newParts['scheme'] = $pick('scheme');
    $newParts['host'] = $pick('host');
    $newParts['port'] = $pick('port');

    $path = '';
    if ($delta['path']) {
                if ($delta['path'][0] === '/') {
            $path = $delta['path'];
        } else {
                        $path = $base['path'];
            if (strpos($path, '/') !== false) {
                $path = substr($path, 0, strrpos($path, '/'));
            }
            $path .= '/' . $delta['path'];
        }
    } else {
        $path = $base['path'] ?: '/';
    }
        $pathParts = explode('/', $path);
    $newPathParts = [];
    foreach ($pathParts as $pathPart) {

        switch ($pathPart) {
                        case '.' :
                break;
            case '..' :
                array_pop($newPathParts);
                break;
            default :
                $newPathParts[] = $pathPart;
                break;
        }
    }

    $path = implode('/', $newPathParts);

        $newParts['path'] = $path;
    if ($delta['query']) {
        $newParts['query'] = $delta['query'];
    } elseif (!empty($base['query']) && empty($delta['host']) && empty($delta['path'])) {
                $newParts['query'] = $base['query'];
    }
    if ($delta['fragment']) {
        $newParts['fragment'] = $delta['fragment'];
    }
    return build($newParts);

}


function normalize($uri) {

    $parts = parse($uri);

    if (!empty($parts['path'])) {
        $pathParts = explode('/', ltrim($parts['path'], '/'));
        $newPathParts = [];
        foreach ($pathParts as $pathPart) {
            switch ($pathPart) {
                case '.':
                                        break;
                case '..' :
                                        array_pop($newPathParts);
                    break;
                default :
                                        $newPathParts[] = rawurlencode(rawurldecode($pathPart));
                    break;
            }
        }
        $parts['path'] = '/' . implode('/', $newPathParts);
    }

    if ($parts['scheme']) {
        $parts['scheme'] = strtolower($parts['scheme']);
        $defaultPorts = [
            'http'  => '80',
            'https' => '443',
        ];

        if (!empty($parts['port']) && isset($defaultPorts[$parts['scheme']]) && $defaultPorts[$parts['scheme']] == $parts['port']) {
                        unset($parts['port']);
        }
                switch ($parts['scheme']) {
            case 'http' :
            case 'https' :
                if (empty($parts['path'])) {
                                        $parts['path'] = '/';
                }
                break;
        }
    }

    if ($parts['host']) $parts['host'] = strtolower($parts['host']);

    return build($parts);

}


function parse($uri) {

                        $uri = preg_replace_callback(
        '/[^[:ascii:]]/u',
        function($matches) {
            return rawurlencode($matches[0]);
        },
        $uri
    );

    $result = parse_url($uri);
    if (!$result) {
        $result = _parse_fallback($uri);
    }

    return
         $result + [
            'scheme'   => null,
            'host'     => null,
            'path'     => null,
            'port'     => null,
            'user'     => null,
            'query'    => null,
            'fragment' => null,
        ];

}


function build(array $parts) {

    $uri = '';

    $authority = '';
    if (!empty($parts['host'])) {
        $authority = $parts['host'];
        if (!empty($parts['user'])) {
            $authority = $parts['user'] . '@' . $authority;
        }
        if (!empty($parts['port'])) {
            $authority = $authority . ':' . $parts['port'];
        }
    }

    if (!empty($parts['scheme'])) {
                $uri = $parts['scheme'] . ':';

    }
    if ($authority || (!empty($parts['scheme']) && $parts['scheme'] === 'file')) {
                $uri .= '//' . $authority;

    }

    if (!empty($parts['path'])) {
        $uri .= $parts['path'];
    }
    if (!empty($parts['query'])) {
        $uri .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $uri .= '#' . $parts['fragment'];
    }

    return $uri;

}


function split($path) {

    $matches = [];
    if (preg_match('/^(?:(?:(.*)(?:\/+))?([^\/]+))(?:\/?)$/u', $path, $matches)) {
        return [$matches[1], $matches[2]];
    }
    return [null,null];

}


function _parse_fallback($uri) {

                        $uri = preg_replace_callback(
        '/[^[:ascii:]]/u',
        function($matches) {
            return rawurlencode($matches[0]);
        },
        $uri
    );

    $result = [
        'scheme'   => null,
        'host'     => null,
        'port'     => null,
        'user'     => null,
        'path'     => null,
        'fragment' => null,
        'query'    => null,
    ];

    if (preg_match('% ^([A-Za-z][A-Za-z0-9+-\.]+): %x', $uri, $matches)) {

        $result['scheme'] = $matches[1];
                $uri = substr($uri, strlen($result['scheme']) + 1);

    }

        if (strpos($uri, '#') !== false) {
        list($uri, $result['fragment']) = explode('#', $uri, 2);
    }
        if (strpos($uri, '?') !== false) {
        list($uri, $result['query']) = explode('?', $uri, 2);
    }

    if (substr($uri, 0, 3) === '///') {
                  $result['path'] = substr($uri, 2);
      $result['host'] = '';
    } elseif (substr($uri, 0, 2) === '//') {
                $regex = '
          %^
            //
            (?: (?<user> [^:@]+) (: (?<pass> [^@]+)) @)?
            (?<host> ( [^:/]* | \[ [^\]]+ \] ))
            (?: : (?<port> [0-9]+))?
            (?<path> / .*)?
          $%x
        ';
        if (!preg_match($regex, $uri, $matches)) {
            throw new InvalidUriException('Invalid, or could not parse URI');
        }
        if ($matches['host']) $result['host'] = $matches['host'];
        if ($matches['port']) $result['port'] = (int)$matches['port'];
        if (isset($matches['path'])) $result['path'] = $matches['path'];
        if ($matches['user']) $result['user'] = $matches['user'];
        if ($matches['pass']) $result['pass'] = $matches['pass'];
    } else {
        $result['path'] = $uri;
    }

    return $result;
}
