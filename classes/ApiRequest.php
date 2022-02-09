<?php

namespace jars\client;

class ApiRequest
{
    public $endpoint;
    public $data;
    public $contentType = 'application/json';
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