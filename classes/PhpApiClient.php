<?php

namespace jars\client;

class PhpApiClient extends ApiClient
{
    protected $onetime = false;
    protected $filesystem;

    public function __construct($auth = null)
    {
        parent::__construct($auth);

        $this->filesystem = new Filesystem();
    }

    public function config($expression = null)
    {
        $config = BlendsConfig::get($this->auth, $this->filesystem);

        if (!$expression) {
            return $config;
        }

        return property_expression_value($config, $expression);
    }

    public function groups(string $name, ?string $min_version = null)
    {
        if (!$report = Report::load($this->auth, $this->filesystem, $name)) {
            return null;
        }

        return $report->groups($min_version);
    }

    public function touch()
    {
        if (!Blends::verify_token($this->auth)) {
            return false;
        }

        return true;
    }

    public function login($username, $password)
    {
        if (!$username) {
            error_response('Missing: username');
        }

        if (!$password) {
            error_response('Missing: username');
        }

        $token = Blends::login($this->onetime ? null : $this->filesystem, $username, $password, $this->onetime);

        if (!$token) {
            return null;
        }

        $this->auth = $token;

        return (object) ['token' => $token];
    }

    public function logout()
    {
        Blends::logout($this->auth);
    }

    public function report(string $name, string $group, ?string $min_version = null)
    {
        if (!$report = Report::load($this->auth, $this->filesystem, $name)) {
            error_response('No such report');
        }

        if (!in_array($group, $report->groups($min_version))) {
            return null;
        }

        return $report->get($group, $min_version);
    }

    public function save(array $data)
    {
        if (!$db_home = @Config::get()->db_home) {
            error_response('db_home not defined', 500);
        }

        $data = Blends::save($this->auth, $this->filesystem, $data);
        $this->version = $this->filesystem->get($db_home . '/version.dat');

        return $data;
    }

    public function delete($linetype, $id)
    {
        $linetype = Linetype::load($this->auth, $linetype);

        return $linetype->delete($this->auth, get_query_filters());
    }

    public function unlink($linetype, $id, $parent)
    {
        error_response('Not implemented');
    }

    public function get($linetype, $id)
    {
        $line = Linetype::load($this->auth, $this->filesystem, $linetype)->get($this->auth, $this->filesystem, $id);

        if (!$line) {
            return null;
        }

        return $line;
    }

    public function get_childset(string $linetype, string $id, string $property)
    {
        return Linetype::load($this->auth, $this->filesystem, $linetype)->get_childset($this->auth, $this->filesystem, $id, $property);
    }

    public function record($table, $id)
    {
        $tableinfo = @BlendsConfig::get()->tables[$table];
        $ext = @$tableinfo->extension ?? 'json';
        $content_type = @$tableinfo->type ?? 'application/json';
        $file = Config::get()->db_home . '/current/records/' . $table . '/' . $id . ($ext ? '.' . $ext : null);

        if (!is_file($file)) {
            return null;
        }

        return file_get_contents($file);
    }

    public function onetime()
    {
        if (func_num_args()) {
            $prev = $this->onetime;
            $this->onetime = (bool) func_get_arg(0);

            return $prev;
        }

        return $this->onetime;
    }

    public function fields($linetype)
    {
        return Linetype::load($this->auth, $linetype)->fieldInfo();
    }

    public function preview(array $data)
    {
        return Blends::save($this->auth, $this->filesystem, $data, true);
    }

    public function filesystem()
    {
        if (func_num_args()) {
            $filesystem = func_get_arg(0);

            if (!($filesystem instanceof Filesystem)) {
                error_response(__METHOD__ . ': argument should be instance of Filesystem');
            }

            $prev = $this->filesystem;
            $this->filesystem = $filesystem;

            return $prev;
        }

        return $this->filesystem;
    }

    public function auth()
    {
        if (func_num_args()) {
            $filesystem = func_get_arg(0);

            $prev = $this->auth;
            $this->auth = $auth;

            return $prev;
        }

        return $this->auth;
    }
}
