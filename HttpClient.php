<?php

namespace jars\client;

use jars\contract\Constants;

class HttpClient implements \jars\contract\Client
{
    protected $asuser;
    protected $content_type;
    protected $filename;
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

    private function executeAndJsonDecodeArray(ApiRequest $request, ?array &$response_headers = null): array
    {
        return $this->executeAndJsonDecode($request, $response_headers, 'array');
    }

    private function executeAndJsonDecodeString(ApiRequest $request, ?array &$response_headers = null): string
    {
        return $this->executeAndJsonDecode($request, $response_headers, 'string');
    }

    private function executeAndJsonDecodeNullableString(ApiRequest $request, ?array &$response_headers = null): ?string
    {
        return $this->executeAndJsonDecode($request, $response_headers, '?string');
    }

    private function executeAndJsonDecodeInt(ApiRequest $request, ?array &$response_headers = null): int
    {
        return $this->executeAndJsonDecode($request, $response_headers, 'int');
    }

    private function executeAndJsonDecodeNullableInt(ApiRequest $request, ?array &$response_headers = null): int
    {
        return $this->executeAndJsonDecode($request, $response_headers, '?int');
    }

    private function executeAndJsonDecodeObject(ApiRequest $request, ?array &$response_headers = null): object
    {
        return $this->executeAndJsonDecode($request, $response_headers, 'object');
    }

    private function executeAndJsonDecodeNullableObject(ApiRequest $request, ?array &$response_headers = null): ?object
    {
        return $this->executeAndJsonDecode($request, $response_headers, '?object');
    }

    private function executeAndJsonDecodeBool(ApiRequest $request, ?array &$response_headers = null): bool
    {
        return $this->executeAndJsonDecode($request, $response_headers, 'bool');
    }

    private function executeAndJsonDecode(ApiRequest $request, ?array &$response_headers = null, $expect_type = null)
    {
        $response = $this->execute($request, $response_headers);
        $result = json_decode($response);

        if ($response !== 'null' && $result === null) {
            error_log('Oops, expected valid JSON but got something else: ' . substr(preg_replace('/\s+/', ' ', var_export($response, true)), 0, 120));

            throw new Exception('Invalid response received from jars');
        }

        $right_type = match($expect_type) {
            '?array' => is_null($result) || is_array($result),
            '?bool' => is_null($result) || is_bool($result),
            '?float' => is_null($result) || is_float($result),
            '?int' => is_null($result) || is_int($result),
            '?numeric' => is_null($result) || is_numeric($result),
            '?object' => is_null($result) || is_object($result),
            '?scalar' => is_null($result) || is_scalar($result),
            '?string' => is_null($result) || is_string($result),
            'array' => is_array($result),
            'bool' => is_bool($result),
            'float' => is_float($result),
            'int' => is_int($result),
            'nonscalar' => !is_scalar($result),
            'numeric' => is_numeric($result),
            'object' => is_object($result),
            'scalar' => is_scalar($result),
            'string' => is_string($result),
            null => true,
            default => false,
        };

        if (!$right_type) {
            error_log("Oops, expected a $expect_type. If it helps, gettype() told me this is: " . gettype($result));

            throw new Exception('Type-mismatched response received from jars');
        }

        return $result;
    }

    private function execute(ApiRequest $request, ?array &$response_headers = null)
    {
        if ($response_headers == null) {
            $response_headers = [];
        }

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

            if (preg_match('/^Content-Disposition:.*;\s*filename\s*=([^;]+)/i', trim($header_line), $groups)) {
                $this->filename = trim($groups[1]);
            }

            return strlen($header_line);
        });

        $this->content_type = null;
        $this->filename = null;

        $result = curl_exec($ch);

        if (defined('APIDEBUG') && APIDEBUG) {
            error_log(var_export($result, true));
        }

        return $result;
    }

    public function touch(): ?object
    {
        if ($this->touched === null) {
            $data = $this->executeAndJsonDecodeObject(new ApiRequest('/touch'));

            if (property_exists($data, 'error')) {
                return null;
            }

            $this->touched = $data;
        }

        return $this->touched;
    }

    public function login(string $username, string $password): ?string
    {
        $request = new ApiRequest('/auth/login', null, (object) [
            'username' => $username,
            'password' => $password,
        ]);

        return $this->executeAndJsonDecodeNullableString($request);
    }

    public function logout(): bool
    {
        $request = new ApiRequest('/auth/logout', 'POST');

        return $this->executeAndJsonDecodeBool($request);
    }

    public function group(string $report, string $group, ?string $min_version = null)
    {
        $request = new ApiRequest('/report/' . $report . '/' . $group);

        if ($min_version) {
            $request->headers[] = 'X-Min-Version: ' . $min_version;
        }

        return $this->executeAndJsonDecode($request);
    }

    public function groups(string $report, string $prefix = '', ?string $min_version = null): array
    {
        if (!preg_match('/^' . Constants::GROUP_PREFIX_PATTERN . '$/', $prefix)) {
            throw new Exception('Invalid prefix');
        }

        $request = new ApiRequest('/report/' . $report . '/' . $prefix);

        if ($min_version) {
            $request->headers[] = 'X-Min-Version: ' . $min_version;
        }

        return $this->executeAndJsonDecodeArray($request);
    }

    public function save(array $lines): array
    {
        $request = new ApiRequest('/');
        $request->data = $lines;
        $result = $this->executeAndJsonDecodeArray($request, $headers);

        $this->version = $headers['X-Version'];

        return $result;
    }

    public function delete(string $linetype, string $id): array
    {
        $request = new ApiRequest('/' . $linetype . '/' . $id);
        $request->method = 'DELETE';
        $result = $this->executeAndJsonDecodeArray($request, $headers);

        $this->version = $headers['X-Version'];

        return $result;
    }

    public function get(string $linetype, string $id): ?object
    {
        return $this->executeAndJsonDecodeNullableObject(new ApiRequest("/{$linetype}/{$id}"));
    }

    public function record(string $table, string $id, ?string &$content_type = null, ?string &$filename = null): ?string
    {
        $data = $this->execute(new ApiRequest("/record/{$table}/{$id}"));

        $content_type = $this->content_type;
        $filename = $this->filename;

        return $data;
    }

    public function fields(string $linetype): array
    {
        return $this->executeAndJsonDecodeArray(new ApiRequest("/fields/{$linetype}"));
    }

    public function preview(array $lines): array
    {
        $request = new ApiRequest('/preview', null, $lines);

        return this->executeAndJsonDecodeArray($request);
    }

    public function version(): ?string
    {
        return $this->version;
    }

    public function h2n(string $h): ?int
    {
        return $this->executeAndJsonDecodeNullableInt(new ApiRequest('/h2n/' . $h));
    }

    public function linetypes(?string $report = null): array
    {
        $request = new ApiRequest(($report ? '/report/' . $report : null) . '/linetypes');

        return $this->executeAndJsonDecodeArray($request);
    }

    public function n2h(int $n): string
    {
        return $this->executeAndJsonDecodeString(new ApiRequest('/n2h/' . $n));
    }

    public static function of(string $url): static
    {
        return new static($url);
    }

    public function refresh(): string
    {
        return $this->executeAndJsonDecodeString(new ApiRequest('/refresh'));
    }

    public function reports(): array
    {
        return $this->executeAndJsonDecodeArray(new ApiRequest('/reports'));
    }
}
