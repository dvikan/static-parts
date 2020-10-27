<?php
declare(strict_types=1);

namespace dvikan\SimpleParts;

use Exception;

class HttpClient
{
    /** @var array */
    private $options;

    public function __construct(array $options = [])
    {
        $defaults = [
            'auth_basic' => [],
            'auth_bearer' => '',
        ];
        $this->options = array_merge($defaults, $options);
    }

    public function get(string $url): Response
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        return $this->createResponse($ch);
    }

    public function post(string $url, array $vars = []): Response
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);

        return $this->createResponse($ch);
    }

    private function createResponse($ch): Response
    {
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $requestHeaders = [];

        if ($this->options['auth_basic']) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->options['auth_basic'][0] . ':' . $this->options['auth_basic'][1]);
        }

        if ($this->options['auth_bearer']) {
            $requestHeaders[] = "Authorization: token " . $this->options['auth_bearer'];
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        $headers = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($_, $header) use (&$headers) {
            $headers[] = trim($header);
            return strlen($header);
        });

        $body = curl_exec($ch);

        if ($body === false) {
            throw new Exception('curl error: ' . curl_error($ch));
        }

        $response = new Response($body, curl_getinfo($ch, CURLINFO_HTTP_CODE), $headers);

        curl_close($ch);

        return $response;
    }
}
