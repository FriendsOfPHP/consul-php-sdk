<?php

namespace SensioLabs\Consul;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
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
            'base_uri' => 'http://127.0.0.1:8500',
            'http_errors' => false
        ), $options);

        $this->client = $client ?: new GuzzleClient($options);
        $this->logger = $logger ?: new NullLogger();
    }

    public function get($url = null, array $options = array())
    {
        return $this->send($this->buildRequest('GET', $url, $options));
    }

    public function head($url, array $options = array())
    {
        return $this->send($this->buildRequest('HEAD', $url, $options));
    }

    public function delete($url, array $options = array())
    {
        return $this->send($this->buildRequest('DELETE', $url, $options));
    }

    public function put($url, array $options = array())
    {
        return $this->send($this->buildRequest('PUT', $url, $options));
    }

    public function patch($url, array $options = array())
    {
        return $this->send($this->buildRequest('PATCH', $url, $options));
    }

    public function post($url, array $options = array())
    {
        return $this->send($this->buildRequest('POST', $url, $options));
    }

    public function options($url, array $options = array())
    {
        return $this->send($this->buildRequest('OPTIONS', $url, $options));
    }

    public function send(RequestInterface $request)
    {
        $this->logger->info(sprintf('%s "%s"', $request->getMethod(), $request->getUri()));
        $this->logger->debug(sprintf("Request:\n%s\n%s\n%s", $request->getUri(), $request->getMethod(), json_encode($request->getHeaders())));

        try {
            $response = $this->client->send($request);
        } catch (TransferException $e) {
            $message = sprintf('Something went wrong when calling consul (%s).', $e->getMessage());

            $this->logger->error($message);

            throw new ServerException($message);
        }

        $this->logger->debug(sprintf("Response:\n%s\n%s\n%s", $response->getStatusCode(), json_encode($response->getHeaders()), $response->getBody()->getContents()));

        if (400 <= $response->getStatusCode()) {
            $message = sprintf('Something went wrong when calling consul statusCode=[%s] reasonPhrase=[%s] uri=[%s]).', $response->getStatusCode(), $response->getReasonPhrase(), $request->getUri());

            $this->logger->error($message);


            $message .= "\n" . $response->getBody()->__toString();
            if (500 <= $response->getStatusCode()) {
                throw new ServerException($message);
            }

            throw new ClientException($message);
        }

        return $response;
    }

    /**
     * @param $method
     * @param $url
     * @param array $options
     * @return RequestInterface
     */
    private function buildRequest($method, $url, array $options = [])
    {
        $uri = new Uri($url);
        $request = new Request($method, $uri);
        foreach ($options as $key => $optValue) {
            switch ($key) {
                case 'query':
                    if (is_array($optValue)) {
                        $optValue = \GuzzleHttp\Psr7\build_query($optValue);
                    }
                    $uri = $uri->withQuery($optValue);
                    $request = $request->withUri($uri);
                    break;
                case 'headers':
                    foreach ($optValue as $headerName => $headerValue) {
                        $request = $request->withHeader($headerName, $headerValue);
                    }
                    break;
                case 'body':
                    $request = $request->withBody(\GuzzleHttp\Psr7\stream_for($optValue));
                    break;
            }
        }
        return $request;
    }
}
