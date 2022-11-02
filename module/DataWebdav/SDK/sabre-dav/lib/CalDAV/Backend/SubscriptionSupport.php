<?php

namespace Sabre\CalDAV\Backend;

use Sabre\DAV;


interface SubscriptionSupport extends BackendInterface {

    
    function getSubscriptionsForUser($principalUri);

    
    function createSubscription($principalUri, $uri, array $properties);

    
    function updateSubscription($subscriptionId, DAV\PropPatch $propPatch);

    
    function deleteSubscription($subscriptionId);

}
