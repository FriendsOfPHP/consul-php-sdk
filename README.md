Consul SDK
==========

Compatibility
-------------

This table shows this SDK compatibility regarding Guzzle version:

| SDK Version | Guzzle Version
| ----------- | --------------
| 1.x         | >=4, <6
| 2.x         | 6

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

The LockHandler helper assists with acquiring advisory exclusive locks. As long as the same key is used across threads 
exclusivity is maintained. Be aware that there is nothing preventing other processes that are not using the LockHandler 
from updating the key/value directly through the KV service, the lock relies upon well behaved clients co-operating. 

    $lockHandler = new SensioLabs\Consul\Helper\LockHandler(
        'tests/session/a-lock',
        'locked value',
    );
    
    // The helper caters for ephemeral or permanent keys to hold the lock.
    // Setting this to false will cause the key/value to remain in the store after the lock is released.
    // Default is for the key/value to be deleted from the store on release/invalidation.  
    $lockHandler->setEphemeral(false); 
    
    if(false === $lockHandler->lock()) {
        echo "The lock is already acquired by another node.\n";
        exit(1);
    }
    
    echo "Do your jobs here....";
    sleep(5);
    echo "End\n";    

    // Optionally release the lock. Releasing the lock is also handled by a registered shutdown function so there is 
    // no need to explicitly call unless the release is required before the end of execution. 
    // For permanent keys an optional new value can be specified on release 
    $lockHandler->release('this is now released');

Blocking locks are also available

    $lockHandler = new SensioLabs\Consul\Helper\LockHandler(
        'tests/session/a-blocking-lock',
        'locked value',
    );
    
    // Wait for up to 60 seconds to get the lock on the specified key. 
    if(false === $lockHandler->lock(60)) {
        echo "The lock is already acquired by another node.\n";
        exit(1);
    }
    
    echo "Do your jobs here....";
    sleep(5);
    echo "End\n";    
