<?php

namespace jars\client;

class HttpClient implements \jars\contract\Client
{
    protected $asuser;
    protected $content_type;
    protected $token;
    protected $touched;
    protected $url;
    protected $version;

    public function __construct(string $url)
    {
        $this->url = $url;
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

    public function token()
    {
        if (func_num_args()) {
            $prev = $this->token;
            $this->token = (string) func_get_arg(0);

            return $prev;
        }

        return $this->token;
    }

    public function execute(ApiRequest $request, array &$response_headers = [])
    {
        if (!preg_match('@^/@', $request->endpoint)) {
            error_response('Endpoint should start with /');
        }

        $ch = curl_init($this->url . $request->endpoint);

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

        if ($this->token) {
            $headers[] = 'X-Auth: ' . $this->token;
        }

        $headers[] = 'Content-Type: ' . $request->contentType;
        $headers = array_merge($headers, $request->headers);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (defined('APIDEBUG') && APIDEBUG) {
            error_log(var_export($request, true));
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header_line) use (&$response_headers) {
            if (preg_match('/^([^:\s]+)\s*:\s*(.*)/i', trim($header_line), $groups)) {
                $response_headers[$groups[1]] = $groups[2];
            }

            if (preg_match('/^X-Version:\s*([a-f0-9]{64})$/i', trim($header_line), $groups)) {
                $this->version = $groups[1];
            }

            if (preg_match('/^Content-Type:\s*([a-f0-9]{64})$/i', trim($header_line), $groups)) {
                $this->content_type = $groups[1];
            }

            return strlen($header_line);
        });

        $this->content_type = null;

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
            $this->token = $response->token;
        }

        return $response;
    }

    public function logout()
    {
        $request = new ApiRequest('/auth/logout');
        $request->method = 'POST';

        return json_decode($this->execute($request));
    }

    public function group(string $report, string $group, ?string $min_version = null)
    {
        $request = new ApiRequest('/report/' . $report . '/' . $group);

        if ($min_version) {
            $request->headers[] = 'X-Min-Version: ' . $min_version;
        }

        return json_decode($this->execute($request));
    }

    public function groups(string $report, ?string $min_version = null)
    {
        $request = new ApiRequest('/report/' . $report . '/groups');

        if ($min_version) {
            $request->headers[] = 'X-Min-Version: ' . $min_version;
        }

        return json_decode($this->execute($request));
    }

    public function save(array $lines)
    {
        $request = new ApiRequest('/');
        $request->data = $lines;
        $response = $this->execute($request, $headers);

        $this->version = $headers['X-Version'];

        return json_decode($response);
    }

    public function delete($linetype, $id)
    {
        $request = new ApiRequest('/' . $linetype . '/' . $id);
        $request->method = 'DELETE';
        $response = $this->execute($request, $headers);

        $this->version = $headers['X-Version'];

        return json_decode($response);
    }

    public function get($linetype, $id)
    {
        return json_decode($this->execute(new ApiRequest("/{$linetype}/{$id}")));
    }

    public function record($table, $id, &$content_type = null)
    {
        $data = $this->execute(new ApiRequest("/record/{$table}/{$id}"));

        $content_type = $this->content_type;

        return $data;
    }

    public function fields($linetype)
    {
        return json_decode($this->execute(new ApiRequest("/{$linetype}/fields")));
    }

    public function preview(array $lines)
    {
        $request = new ApiRequest('/preview');
        $request->data = $lines;
        $response = $this->execute($request, $headers);

        $this->version = $headers['X-Version'];

        return json_decode($response);
    }

    public function version()
    {
        return $this->version;
    }

    public function h2n(string $h)
    {
        return json_decode($this->execute(new ApiRequest('/h2n/' . $h)));
    }

    public function n2h(int $n)
    {
        return json_decode($this->execute(new ApiRequest('/n2h/' . $n)));
    }

    public function of(string $url)
    {
        return new static($url);
    }

    public function refresh() : string
    {
        $this->execute(new ApiRequest('/refresh', $headers));

        return $headers['X-Version'];
    }
}
