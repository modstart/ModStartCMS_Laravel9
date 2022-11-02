<?php

namespace Sabre\DAVACL\PrincipalBackend;


abstract class AbstractBackend implements BackendInterface {

    
    function findByUri($uri, $principalPrefix) {

                        if (substr($uri, 0, 7) !== 'mailto:') {
            return;
        }
        $result = $this->searchPrincipals(
            $principalPrefix,
            ['{http://sabredav.org/ns}email-address' => substr($uri, 7)]
        );

        if ($result) {
            return $result[0];
        }

    }

}
