<?php

namespace jars\client;

class HttpApiClient extends ApiClient
{
    protected $url;

    public function groups(string $name, ?string $min_version = null)
    {
        $request = new ApiRequest('/report/' . $name . '/groups');

        if ($min_version) {
            $request->headers[] = 'X-Min-Version: ' . $min_version;
        }

        return json_decode($this->execute($request));
    }

    public function url()
    {
        if (func_num_args()) {
            $prev = $this->url;
            $this->url = (string) func_get_arg(0);

            return $prev;
        }

        return $this->url;
    }

    public function execute($request)
    {
        if (!preg_match('@^/@', $request->endpoint)) {
            error_response('Endpoint should start with /');
        }

        $ch = curl_init($this->url . '/api' . $request->endpoint);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!in_array($request->method, ['GET', 'POST'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        } elseif ($request->data || $request->method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if ($request->data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request->data));
        }

        $headers = [];

        if ($this->auth) {
            $headers[] = 'X-Auth: ' . $this->auth;
        }

        $headers[] = 'Content-Type: ' . $request->contentType;
        $headers = array_merge($headers, $request->headers);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (defined('APIDEBUG') && APIDEBUG) {
            error_log(var_export($request, true));
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header_line) {
            if (preg_match('/^X-Version:\s*([a-f0-9]{64})$/i', trim($header_line), $groups)) {
                $this->version = $groups[1];
            }

            return strlen($header_line);
        });

        $result = curl_exec($ch);

        if (defined('APIDEBUG') && APIDEBUG) {
            error_log(var_export($result, true));
        }

        return $result;
    }

    public function touch()
    {
        if ($this->touched === null) {
            $data = json_decode($this->execute(new ApiRequest('/touch')));
            $this->touched = is_object($data) && !property_exists($data, 'error');
        }

        return $this->touched;
    }

    public function login($username, $password)
    {
        $request = new ApiRequest('/auth/login');
        $request->data = (object) [
            'username' => $username,
            'password' => $password,
        ];

        $response = json_decode($this->execute($request));

        if (is_object($response) && @$response->token) {
            $this->auth = $response->token;
        }

        return $response;
    }

    public function logout()
    {
        $request = new ApiRequest('/auth/logout');
        $request->method = 'POST';

        return json_decode($this->execute($request));
    }

    public function report(string $name, string $group, ?string $min_version = null)
    {
        $request = new ApiRequest('/report/' . $name . '/' . $group);

        if ($min_version) {
            $request->headers[] = 'X-Min-Version: ' . $min_version;
        }

        return json_decode($this->execute($request));
    }

    public function save(array $data)
    {
        if (!$data) {
            return $data;
        }

        $request = new ApiRequest('/');
        $request->data = $data;

        return json_decode($this->execute($request));
    }

    public function delete($linetype, $id)
    {
        $request = new ApiRequest('/' . $linetype . '/' . $id);
        $request->method = 'DELETE';

        return json_decode($this->execute($request));
    }

    public function unlink($linetype, $id, $parent)
    {
        $line = (object) [
            'id' => $id,
            'parent' => $parent,
        ];

        $request = new ApiRequest('/' . $linetype . '/unlink');
        $request->data = [$line];

        return json_decode($this->execute($request));
    }

    public function get($linetype, $id)
    {
        return json_decode($this->execute(new ApiRequest("/{$linetype}/{$id}")));
    }

    public function record($table, $id)
    {
        return $this->execute(new ApiRequest("/record/{$table}/{$id}"));
    }

    public function fields($linetype)
    {
        return json_decode($this->execute(new ApiRequest("/{$linetype}/fields")));
    }

    public function preview(array $data)
    {
        if (!$data) {
            return $data;
        }

        $request = new ApiRequest('/preview');
        $request->data = $data;

        return json_decode($this->execute($request));
    }
}

require_once __DIR__ . '/ApiRequest.php';
