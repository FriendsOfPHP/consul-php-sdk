<?php

namespace Consul;

use Consul\Exception\ClientException;
use Consul\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class Client implements ClientInterface
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(array $options = [], ?LoggerInterface $logger = null, ?HttpClientInterface $client = null)
    {
        if (!$client) {
            $options['base_uri'] = DsnResolver::resolve($options);
            $client = HttpClient::create($options);
        }

        $this->client = $client;
        $this->logger = $logger ?? new NullLogger();
    }

    public function get(?string $url = null, array $options = []): ConsulResponse
    {
        return $this->doRequest('GET', $url, $options);
    }

    public function head(string $url, array $options = []): ConsulResponse
    {
        return $this->doRequest('HEAD', $url, $options);
    }

    public function delete(string $url, array $options = []): ConsulResponse
    {
        return $this->doRequest('DELETE', $url, $options);
    }

    public function put(string $url, array $options = []): ConsulResponse
    {
        return $this->doRequest('PUT', $url, $options);
    }

    public function patch(string $url, array $options = []): ConsulResponse
    {
        return $this->doRequest('PATCH', $url, $options);
    }

    public function post(string $url, array $options = []): ConsulResponse
    {
        return $this->doRequest('POST', $url, $options);
    }

    public function options(string $url, array $options = []): ConsulResponse
    {
        return $this->doRequest('OPTIONS', $url, $options);
    }

    private function doRequest(string $method, string $url, array $options): ConsulResponse
    {
        if (isset($options['body']) && \is_array($options['body'])) {
            $options['body'] = json_encode($options['body'], \JSON_THROW_ON_ERROR);
        }

        $this->logger->info(\sprintf('%s "%s"', $method, $url));
        $this->logger->debug(\sprintf('Requesting %s %s', $method, $url), ['options' => $options]);

        try {
            $response = $this->client->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
            $message = \sprintf('Something went wrong when calling consul (%s).', $e->getMessage());

            $this->logger->error($message);

            throw new ServerException($message);
        }

        $this->logger->debug(\sprintf("Response:\n%s", $this->formatResponse($response)));

        if (400 <= $response->getStatusCode()) {
            $message = \sprintf('Something went wrong when calling consul (%s).', $response->getStatusCode());

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
                $headers[] = \sprintf('%s: %s', $key, $value);
            }
        }

        return \sprintf("%s\n\n%s", implode("\n", $headers), $response->getContent(false));
    }
}
