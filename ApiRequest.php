<?php

namespace jars\client;

class ApiRequest
{
    public $data = null;
    public ?string $endpoint = null;
    public array $headers = [];
    public string $contentType = 'application/json';
    public string $method = 'GET';

    public function __construct(?string $endpoint = null, $method = null, $data = null)
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