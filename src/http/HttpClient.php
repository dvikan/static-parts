<?php declare(strict_types=1);

namespace dvikan\SimpleParts;

final class HttpClient
{
    const OPTIONS = [
        'auth_bearer' => null,
        'client_id' => null,
    ];

    private $options;
    private $ch;

    public function __construct(array $options = [])
    {
        $this->options = array_merge(self::OPTIONS, $options);
        $this->ch = null;
    }

    public function get(string $url): Response
    {
        if (!isset($this->ch)) {
            $this->ch = $this->createCurlHandle();
        }

        curl_setopt($this->ch, CURLOPT_URL, $url);

        return $this->execute();
    }

    public function post(string $url, array $vars = []): Response
    {
        if ($this->ch === null) {
            $this->ch = $this->createCurlHandle();
        }

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $vars);

        return $this->execute();
    }

    private function createCurlHandle()
    {
        $ch = curl_init();

        if ($ch === false) {
            throw new SimpleException('Unable to create curl handle');
        }

        curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $headers = [];

        if (isset($this->options['auth_bearer'])) {
            $headers[] = sprintf("Authorization: Bearer %s", $this->options['auth_bearer']);
        }

        if (isset($this->options['client_id'])) {
            $headers[] = sprintf("client-id: %s", $this->options['client_id']);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        return $ch;
    }

    private function execute(): Response
    {
        $headers = [];
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$headers) {
            $len = strlen($header);
            $header = explode(':', $header);
            if(count($header) !== 2) {
                return $len;
            }
            $name = strtolower(trim($header[0]));
            $value = trim($header[1]);
            $headers[$name] = $value;
            return $len;
        });

        $body = curl_exec($this->ch);

        if ($body === false) {
            throw new SimpleException(sprintf('Curl error: %s', curl_error($this->ch)));
        }

        $response = new Response($body, curl_getinfo($this->ch, CURLINFO_HTTP_CODE), $headers);

        if ($response->isOk()) {
            return $response;
        }

        throw new SimpleException('The response was not ok');
    }

    public function __destruct()
    {
        if (isset($this->ch)) {
            curl_close($this->ch);
        }
    }
}