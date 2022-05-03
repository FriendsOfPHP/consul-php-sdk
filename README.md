# Consul PHP SDK

## Compatibility

See previous version of
[README](https://github.com/FriendsOfPHP/consul-php-sdk/tree/404366acbce4285d08126c0a55ace84c10e361d1)
to find some version compatible with older version of symfony/http-client or
guzzle

## Installation

This library can be installed with composer:

    composer require friendsofphp/consul-php-sdk

## Usage

Instantiate a services, and start using it:

```php

$kv = new Consul\Services\KV();

$kv->put('test/foo/bar', 'bazinga');
$kv->get('test/foo/bar', ['raw' => true]);
$kv->delete('test/foo/bar');
```

A service expose few methods mapped from the consul [API](https://consul.io/docs/agent/http.html):

All services methods follow the same convention:

```php
$response = $service->method($mandatoryArgument, $someOptions);
```

* All API mandatory arguments are placed as first;
* All API optional arguments are directly mapped from `$someOptions`;
* All methods return a raw http client response.

So if you want to acquire an exclusive lock:

```php
// Start a session

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

## Available services

* agent
* catalog
* health
* kv
* session
* txn

## Some utilities

* Lock handler: Simple class that implement a distributed lock

## Run the test suite

You need a consul agent running on `localhost:8500`.

But you ca override this address:

```
export CONSUL_HTTP_ADDR=http://172.17.0.2:8500
```

If you don't want to install Consul locally you can use the Docker image:

```
docker run -d --name=dev-consul -e CONSUL_BIND_INTERFACE=eth0 consul
```

Then

```
vendor/bin/simple-phpunit
```
