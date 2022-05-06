Consul SDK
==========

Installation
------------

This library can be installed with composer:

    composer require sensiolabs/consul-php-sdk

Usage
-----

The simple way to use this SDK, is to instantiate the service factory:

    $sf = new SensioLabs\Consul\ServiceFactory();

Then, a service could be retrieve from this factory:

    $kv = $sf->get('kv');

Then, a service expose few methods mapped from the consul [API](https://consul.io/docs/agent/http.html):

    $kv->put('test/foo/bar', 'bazinga');
    $kv->get('test/foo/bar', ['raw' => true]);
    $kv->delete('test/foo/bar');

All services methods follow the same convention:

    $response = $service->method($mandatoryArgument, $someOptions);

* All API mandatory arguments are placed as first;
* All API optional arguments are directly mapped from `$someOptions`;
* All methods return raw guzzle response.

So if you want to acquire an exclusive lock:

    // Start a session
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

Available services
------------------

* agent
* catalog
* health
* kv
* session

Some utilities
--------------

* Lock handler: Simple class that implement a distributed lock
