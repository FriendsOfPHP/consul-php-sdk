# Consul PHP SDK

Consul PHP SDK is a thin wrapper around the [Consul](https://consul.io/) HTTP API.

## Compatibility

See previous version of
[README.md](https://github.com/FriendsOfPHP/consul-php-sdk/tree/404366acbce4285d08126c0a55ace84c10e361d1)
to find some version compatible with older version of symfony/http-client or
guzzle

## Installation

This library can be installed with composer:

    composer require friendsofphp/consul-php-sdk

## Supported services

* agent
* catalog
* health
* kv
* session
* txn

## Usage

Instantiate a services, and start using it:

```php

$kv = new Consul\Services\KV();

$kv->put('test/foo/bar', 'bazinga');
$kv->get('test/foo/bar', ['raw' => true]);
$kv->delete('test/foo/bar');
```

A service exposes few methods mapped from the consul [API](https://consul.io/docs/agent/http.html):

**All services methods follow the same convention:**

```php
$response = $service->method($mandatoryArgument, $someOptions);
```

* All API mandatory arguments are placed as first;
* All API optional arguments are directly mapped from `$someOptions`;
* All methods return a `Consul\ConsulResponse`;
* If the API responds with a 4xx response, a `Consul\Exception\ClientException` is thrown;
* If the API responds with a 5xx response, a `Consul\Exception\ServeException` is thrown.

## Cookbook

### How to acquire an exclusive lock?

```php
$session = new Consul\Services\Session();

$sessionId = $session->create()->json()['ID'];

// Lock a key / value with the current session
$lockAcquired = $kv->put('tests/session/a-lock', 'a value', ['acquire' => $sessionId])->json();

if (false === $lockAcquired) {
    $session->destroy($sessionId);

    echo "The lock is already acquire by another node.\n";
    exit(1);
}

echo "Do you jobs here....";
sleep(5);
echo "End\n";

$kv->delete('tests/session/a-lock');
$session->destroy($sessionId);
```

## Some utilities

* `Consul\Helper\LockHandler`: Simple class that implement a distributed lock

## Run the test suite

You need a consul agent running on `localhost:8500`.

But you ca override this address:

```
export CONSUL_HTTP_ADDR=172.17.0.2:8500
```

If you don't want to install Consul locally you can use a docker container:

```
docker run -d --name=dev-consul -e CONSUL_BIND_INTERFACE=eth0 consul
```

Then, run the test suite

```
vendor/bin/simple-phpunit
```
