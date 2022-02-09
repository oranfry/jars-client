<?php

if (!($relays = @Config::get()->api_relays) || !isset($relays[RELAY_CONF])) {
    error_response('No such relay config');
}

$conf = $relays[RELAY_CONF];

if (!@$conf->url) {
    error_response('No url set for this relay config');
}

if ($body = file_get_contents('php://input')) {
    $data = json_decode($body);

    if ($data === null && strtolower($body) !== 'null') {
        error_response('Will not relay invalid data');
    }
}

$api = ApiClient::http(value(@$conf->auth), $conf->url);
$request = new ApiRequest(ENDPOINT, $_SERVER['REQUEST_METHOD'], @$data);

return [
    'data' => json_decode($api->execute($request)),
];
