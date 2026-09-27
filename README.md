# Consul PHP SDK

[![CI](https://github.com/FriendsOfPHP/consul-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/FriendsOfPHP/consul-php-sdk/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/friendsofphp/consul-php-sdk)](https://packagist.org/packages/friendsofphp/consul-php-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/friendsofphp/consul-php-sdk)](https://packagist.org/packages/friendsofphp/consul-php-sdk)
[![PHP Version](https://img.shields.io/packagist/dependency-v/friendsofphp/consul-php-sdk/php)](https://packagist.org/packages/friendsofphp/consul-php-sdk)
[![License](https://img.shields.io/packagist/l/friendsofphp/consul-php-sdk)](LICENSE)

A thin, no-magic PHP wrapper around the [Consul](https://www.consul.io/) HTTP API,
built on top of [Symfony HttpClient](https://symfony.com/doc/current/http_client.html).

```php
$kv = new Consul\Services\KV();

$kv->put('config/feature-flag', 'enabled');

echo $kv->get('config/feature-flag', ['raw' => true])->getBody(); // enabled
```

## ✨ Features

- 🗝️ **Key/Value store**, **sessions** and **transactions**
- 🩺 **Service discovery**: agent, catalog and health endpoints
- 🔒 **Distributed locks and semaphores**, ready to use
- 🪶 **Lightweight**: only depends on `symfony/http-client` and `psr/log`
- 🔌 **Pluggable**: bring your own HTTP client and PSR-3 logger

## 📦 Installation

```bash
composer require friendsofphp/consul-php-sdk
```

## 🚀 Usage

### Configuration

By default, services talk to the agent on `http://127.0.0.1:8500`. The address
can be changed with the `CONSUL_HTTP_ADDR` environment variable (the same one
the Consul CLI uses), or with the `base_uri` option:

```php
use Consul\Client;
use Consul\Services\KV;

$client = new Client(['base_uri' => 'https://consul.example.com:8500']);

$kv = new KV($client);
```

The first argument of `Client` accepts any
[Symfony HttpClient option](https://symfony.com/doc/current/reference/configuration/framework.html#http-client),
which comes handy to send an [ACL token](https://developer.hashicorp.com/consul/docs/security/acl/tokens):

```php
$client = new Client([
    'base_uri' => 'https://consul.example.com:8500',
    'headers' => ['X-Consul-Token' => $token],
]);
```

The token can also be set with the `CONSUL_HTTP_TOKEN` environment variable.

You can also pass a PSR-3 logger, and your own `HttpClientInterface` instance:

```php
$client = new Client(logger: $logger, client: $httpClient);
```

### Available services

| Service                    | Consul API                                                                   |
|----------------------------|------------------------------------------------------------------------------|
| `Consul\Services\Agent`    | [`/v1/agent`](https://developer.hashicorp.com/consul/api-docs/agent)         |
| `Consul\Services\Catalog`  | [`/v1/catalog`](https://developer.hashicorp.com/consul/api-docs/catalog)     |
| `Consul\Services\Health`   | [`/v1/health`](https://developer.hashicorp.com/consul/api-docs/health)       |
| `Consul\Services\KV`       | [`/v1/kv`](https://developer.hashicorp.com/consul/api-docs/kv)               |
| `Consul\Services\Session`  | [`/v1/session`](https://developer.hashicorp.com/consul/api-docs/session)     |
| `Consul\Services\TXN`      | [`/v1/txn`](https://developer.hashicorp.com/consul/api-docs/txn)             |

### Conventions

All services follow the same convention:

```php
$response = $service->method($mandatoryArgument, $someOptions);
```

- Mandatory API arguments come first;
- Optional API arguments are passed in the `$options` array, with the same name
  as in the Consul documentation;
- Every method returns a `Consul\ConsulResponse`, which exposes `getBody()`,
  `json()`, `getHeaders()`, `getStatusCode()` and `isSuccessful()`;
- A `4xx` response throws a `Consul\Exception\ClientException`;
- A `5xx` response, or a network error, throws a `Consul\Exception\ServerException`.

Both exceptions implement `Consul\Exception\ConsulExceptionInterface`:

```php
use Consul\Exception\ConsulExceptionInterface;

try {
    $kv->get('does/not/exist');
} catch (ConsulExceptionInterface $e) {
    // ...
}
```

## 🍳 Cookbook

### Register a service with a health check

```php
use Consul\Services\Agent;
use Consul\Services\Health;

$agent = new Agent();

$agent->registerService([
    'ID' => 'api-1',
    'Name' => 'api',
    'Address' => '10.0.0.12',
    'Port' => 8080,
    'Check' => [
        'HTTP' => 'http://10.0.0.12:8080/health',
        'Interval' => '10s',
    ],
]);

// Later, find all healthy instances
$instances = (new Health())->service('api', ['passing' => true])->json();
```

### Read many keys at once

```php
$kv = new Consul\Services\KV();

foreach ($kv->get('config/', ['recurse' => true])->json() as $entry) {
    echo $entry['Key'], ' = ', base64_decode($entry['Value']), "\n";
}
```

### Run a transaction

```php
$txn = new Consul\Services\TXN();

$txn->put([
    ['KV' => ['Verb' => 'set', 'Key' => 'config/a', 'Value' => base64_encode('1')]],
    ['KV' => ['Verb' => 'set', 'Key' => 'config/b', 'Value' => base64_encode('2')]],
]);
```

### Acquire an exclusive lock

`LockHandler` takes a lock on a key, and releases it automatically at the end
of the script:

```php
use Consul\Helper\LockHandler;

$lock = new LockHandler('locks/my-job');

if (!$lock->lock()) {
    echo "The lock is already acquired by another node.\n";
    exit(1);
}

// Do your job here...

$lock->release();
```

### Lock many resources at once

`MultiLockHandler` locks all resources, or none of them:

```php
use Consul\Helper\MultiLockHandler;
use Consul\Services\KV;
use Consul\Services\Session;

$lock = new MultiLockHandler(['resource1', 'resource2'], 60, new Session(), new KV(), 'my/lock/');

if ($lock->lock()) {
    try {
        // Do your job here...
        // and call $lock->renew() before the TTL (60s) expires, if needed
    } finally {
        $lock->release();
    }
}
```

### Use a distributed semaphore

`MultiSemaphore` allows up to `limit` concurrent holders per resource. Each
`Resource` is defined by a name, the number of slots to acquire, and a limit:

```php
use Consul\Helper\MultiSemaphore;
use Consul\Helper\MultiSemaphore\Resource;
use Consul\Services\KV;
use Consul\Services\Session;

$resources = [
    new Resource('resource1', 2, 7),
    new Resource('resource2', 3, 6),
    new Resource('resource3', 1, 1),
];

$semaphore = new MultiSemaphore($resources, 60, new Session(), new KV(), 'my/semaphore');

if ($semaphore->acquire()) {
    try {
        // Do your job here...
    } finally {
        $semaphore->release();
    }
}
```

## 🧩 Compatibility

| Version | PHP    | symfony/http-client  |
|---------|--------|----------------------|
| 5.4     | ≥ 8.2  | 6.4, 7.4, 8.1+       |
| 5.3     | ≥ 8.1  | 5.4, 6.4, 7.x, 8.x   |

Looking for Guzzle support, or older versions of PHP? Check the
[CHANGELOG](CHANGELOG.md) and this
[older README](https://github.com/FriendsOfPHP/consul-php-sdk/tree/404366acbce4285d08126c0a55ace84c10e361d1).

## 🧪 Running the test suite

The test suite needs a Consul agent listening on `localhost:8500` (or on
`CONSUL_HTTP_ADDR`), with ACLs enabled and `root` as management token. The
easiest way is to use Docker:

```bash
docker run -d --rm --name consul -p 8500:8500 \
    -e CONSUL_LOCAL_CONFIG='{"acl":{"enabled":true,"default_policy":"allow","tokens":{"initial_management":"root"}}}' \
    hashicorp/consul
```

Then run:

```bash
composer install
vendor/bin/phpunit
```

## 📄 License

This library is released under the [MIT license](LICENSE).
