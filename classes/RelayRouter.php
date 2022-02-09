<?php

namespace jars\client;

class RelayRouter extends \Router
{
    protected static $routes = [
        'POST /([^/]+)(/auth/login)' => ['RELAY_CONF', 'ENDPOINT', 'PAGE' => 'api_relay', 'AUTHSCHEME' => 'none', 'LAYOUT' => 'json'],
        'HTTP /([^/]+)(/.*)' => ['RELAY_CONF', 'ENDPOINT', 'PAGE' => 'api_relay', 'AUTHSCHEME' => 'header', 'LAYOUT' => 'json'],
   ];
}
