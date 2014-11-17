<?php

namespace SensioLabs\Consul;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SensioLabs\Consul\Exception\ClientException;
use SensioLabs\Consul\Exception\ServerException;

class Client
{
    private $client;
    private $logger;

    public function __construct(array $options = array(), LoggerInterface $logger = null, GuzzleClient $client = null)
    {
        $options = array_replace(array(
            'base_url' => 'http://127.0.0.1:8500',
        ), $options);

        $this->client = $client ?: new GuzzleClient($options);
        $this->client->setDefaultOption('exceptions', false);
        $this->logger = $logger ?: new NullLogger();
    }

    public function get($url = null, array $options = array())
    {
        return $this->send($this->client->createRequest('GET', $url, $options));
    }

    public function head($url, array $options = array())
    {
        return $this->send($this->client->createRequest('HEAD', $url, $options));
    }

    public function delete($url, array $options = array())
    {
        return $this->send($this->client->createRequest('DELETE', $url, $options));
    }

    public function put($url, array $options = array())
    {
        return $this->send($this->client->createRequest('PUT', $url, $options));
    }

    public function patch($url, array $options = array())
    {
        return $this->send($this->client->createRequest('PATCH', $url, $options));
    }

    public function post($url, array $options = array())
    {
        return $this->send($this->client->createRequest('POST', $url, $options));
    }

    public function options($url, array $options = array())
    {
        return $this->send($this->client->createRequest('OPTIONS', $url, $options));
    }

    public function send(RequestInterface $request)
    {
        $this->logger->info(sprintf('%s "%s"', $request->getMethod(), $request->getUrl()));
        $this->logger->debug(sprintf("Request:\n%s", (string) $request));

        try {
            $response = $this->client->send($request);
        } catch (TransferException $e) {
            $message = sprintf('Something went wrong when calling consul (%s).', $e->getMessage());

            $this->logger->error($message);

            throw new ServerException($message);
        }

        $this->logger->debug(sprintf("Response:\n%s", $response));

        if (400 <= $response->getStatusCode()) {
            $message = sprintf('Something went wrong when calling consul (%s - %s).', $response->getStatusCode(), $response->getReasonPhrase());

            $this->logger->error($message);

            $message .= "\n$response";
            if (500 <= $response->getStatusCode()) {
                throw new ServerException($message);
            }

            throw new ClientException($message);
        }

        return $response;
    }
}
