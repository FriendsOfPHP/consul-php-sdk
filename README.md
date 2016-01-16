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
    
Or you can use MultiLockHandler to lock one or more resources:

    $serviceFactory = new ServiceFactory(['base_url' => 'http://127.0.0.1:8500']);
    
    $multiLockHandlerFactory = new MultiLockHandlerFactory(
        $serviceFactory->get('session'),
        $serviceFactory->get('kv'),
        'test/lock/'
    );
    
    $resources = ['resource1', 'resource2'];
    
    $multiLockHandler = $multiLockHandlerFactory->getMultiLockHandler($resources, 10);
    
    if (!$multiLockHandler->lock()) {
        echo "The lock is already acquire by another node.\n";
        exit(1);
    }
    
    echo "Do you jobs here....";
    sleep(5);
    echo "End\n";
    
    $multiLockHandler->release();
    
    
Also you can use MultiSemaphore:

    $serviceFactory = new ServiceFactory(['base_url' => 'http://127.0.0.1:8500']);

    $multiSemaphoreFactory = new MultiSemaphoreFactory(
        $serviceFactory->get('session'),
        $serviceFactory->get('kv'),
        'test/semaphore'
    );
    
    $resources = [
        new Resource('resource1', 2, 7),
        new Resource('resource2', 3, 6),
        new Resource('resource3', 1, 1),
    ];

    $semaphore = $multiSemaphoreFactory->getMultiSemaphore($resources, 60);
    if (!$semaphore1->acquire()) {
        echo "Resource are not available.\n";
        exit(1);
    }
    
    echo "Do you jobs here....";
    sleep(5);
    echo "End\n";
    
    $semaphore->release()

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
* Multi Lock handler: Class that implement a distributed lock for many resources
* Multi Semaphore handler: Class that implement a distributed semaphore for many resources
