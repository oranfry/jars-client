<?php

namespace jars\client;

class ApiRequest
{
    public $contentType = 'application/json';
    public $data = null;
    public $endpoint = null;
    public $headers = [];
    public $method = 'GET';

    public function __construct($endpoint = null, $method = null, $data = null)
    {
        if ($endpoint) {
            $this->endpoint = $endpoint;
        }

        if ($method) {
            $this->method = $method;
        }

        if ($data) {
            $this->data = $data;
        }
    }
}