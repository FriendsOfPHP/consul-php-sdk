<?php

namespace SensioLabs\Consul;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SensioLabs\Consul\Exception\ClientException;
use SensioLabs\Consul\Exception\ServerException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class Client implements ClientInterface
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(array $options = [], LoggerInterface $logger = null, HttpClientInterface $client = null)
    {
        $baseUri = 'http://127.0.0.1:8500';

        if (isset($options['base_uri'])) {
            $baseUri = $options['base_uri'];
        } elseif (\array_key_exists('CONSUL_HTTP_ADDR', $_SERVER)) {
            $baseUri = $_SERVER['CONSUL_HTTP_ADDR'];
        }

        $options = array_replace([
            'base_uri' => $baseUri,
        ], $options);

        $this->client = $client ?? HttpClient::create($options);
        $this->logger = $logger ?? new NullLogger();
    }

    public function get($url = null, array $options = []): ConsulResponse
    {
        return $this->doRequest('GET', $url, $options);
    }

    public function head($url, array $options = []): ConsulResponse
    {
        return $this->doRequest('HEAD', $url, $options);
    }

    public function delete($url, array $options = []): ConsulResponse
    {
        return $this->doRequest('DELETE', $url, $options);
    }

    public function put($url, array $options = []): ConsulResponse
    {
        return $this->doRequest('PUT', $url, $options);
    }

    public function patch($url, array $options = []): ConsulResponse
    {
        return $this->doRequest('PATCH', $url, $options);
    }

    public function post($url, array $options = []): ConsulResponse
    {
        return $this->doRequest('POST', $url, $options);
    }

    public function options($url, array $options = []): ConsulResponse
    {
        return $this->doRequest('OPTIONS', $url, $options);
    }

    private function doRequest($method, $url, $options): ConsulResponse
    {
        if (isset($options['body']) && \is_array($options['body'])) {
            $options['body'] = json_encode($options['body'], \JSON_THROW_ON_ERROR);
        }

        $this->logger->info(sprintf('%s "%s"', $method, $url));
        $this->logger->debug(sprintf('Requesting %s %s', $method, $url), ['options' => $options]);

        try {
            $response = $this->client->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
            $message = sprintf('Something went wrong when calling consul (%s).', $e->getMessage());

            $this->logger->error($message);

            throw new ServerException($message);
        }

        $this->logger->debug(sprintf("Response:\n%s", $this->formatResponse($response)));

        if (400 <= $response->getStatusCode()) {
            $message = sprintf('Something went wrong when calling consul (%s).', $response->getStatusCode());

            $this->logger->error($message);

            $message .= "\n".(string) $response->getContent(false);
            if (500 <= $response->getStatusCode()) {
                throw new ServerException($message, $response->getStatusCode());
            }

            throw new ClientException($message, $response->getStatusCode());
        }

        return new ConsulResponse($response->getHeaders(), (string) $response->getContent(), $response->getStatusCode());
    }

    private function formatResponse(ResponseInterface $response): string
    {
        $headers = [];

        foreach ($response->getHeaders(false) as $key => $values) {
            foreach ($values as $value) {
                $headers[] = sprintf('%s: %s', $key, $value);
            }
        }

        return sprintf("%s\n\n%s", implode("\n", $headers), $response->getContent(false));
    }
}
