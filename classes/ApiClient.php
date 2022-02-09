<?php

namespace jars\client;

abstract class ApiClient
{
    protected $auth;
    protected $asuser;
    protected $touched;
    protected $version;

    public function __construct($auth = null)
    {
        $this->auth = $auth;
    }

    public function auth()
    {
        if (func_num_args()) {
            $prev = $this->auth;
            $this->auth = (string) func_get_arg(0);

            return $prev;
        }

        return $this->auth;
    }

    public abstract function delete($linetype, $id);
    public abstract function fields($linetype);
    public abstract function get($linetype, $id);
    public abstract function groups(string $name, ?string $min_version = null);

    public static function http($auth = null, $url = null)
    {
        require_once __DIR__ . '/HttpApiClient.php';

        $client = new HttpApiClient($auth);
        $client->url($url);

        return $client;
    }

    public abstract function login($username, $password);
    public abstract function logout();

    public static function php($auth = null, bool $onetime = false)
    {
        require_once __DIR__ . '/PhpApiClient.php';
        $client = new PhpApiClient($auth);

        if ($onetime) {
            $client->onetime(true);
        }

        return $client;
    }

    public abstract function preview(array $data);
    public abstract function record($table, $id);
    public abstract function report(string $name, string $group, ?string $min_version = null);
    public abstract function save(array $data);
    public abstract function touch();
    public abstract function unlink($linetype, $id, $parent);

    public function version()
    {
        return $this->version;
    }
}
