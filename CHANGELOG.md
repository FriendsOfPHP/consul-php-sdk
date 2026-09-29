# CHANGELOG

## 5.5.0 (not released yet)

## 5.4.0 (2026-09-29)

* Drop support for PHP 8.1
* Drop support for Symfony 5.4, 7.0, 7.1, 7.2, 7.3 and 8.0
* Drop support for psr/log 1.x
* Switch the test suite from symfony/phpunit-bridge to PHPUnit 11+
* Modernize the code base (readonly classes and properties, constructor property promotion, ...)
* Fix `MultiSemaphore` reusing the metadata of a previous resource when acquiring many resources
* Fix `Session::create()` (and so `LockHandler`) when called without arguments
* Rework the README
* Read the ACL token from the `CONSUL_HTTP_TOKEN` environment variable
* Add `ACL`, `Config`, `Connect`, `Coordinate`, `DiscoveryChain`, `Event`,
  `Operator`, `Peering`, `PreparedQuery`, `Snapshot` and `Status` services
* Add all missing endpoints to `Agent`, `Catalog` and `Health` services
* Whitelist all documented options (blocking queries, consistency modes,
  filtering, ...) in `Agent`, `Catalog`, `Health`, `KV` and `Session` services
* Support multi-valued options (e.g. `['tag' => ['v1', 'primary']]`)
* Allow the `dc` option on all endpoints not local to the agent
* Fix `Agent::join()` and `Agent::forceLeave()` HTTP method (`PUT` instead of `GET`)

## 5.3.0 (2025-12-08)

* Add support for PHP 8.4, 8.5
* Add support for Symfony 8.x

## 5.2.0 (2024-03-04)

* Drop support for PHP < .8.0
* Add support for PHP 8.2, and 8.3
* Drop support for Symfony < 5.4, and 6.0, 6.1, 6.2, and 6.3

## 5.1.0 (2023-12-21)

* Add support for Support symfony/http-client 7.x

## 5.0.0 (2022-06-13)

Release notes:

This is the first big release under friendsofphp umbrella. There are lot of BC
breaks, but they should be easy to fix. From now on, a particular attention will
be given to not break the BC and to provide a nice upgrade path.

* Rename package from `sensiolabs/consul-php-sdk` to `friendsofphp/consul-php-sdk`
* Get ride of SensioLabs namespace (from `SensioLabs\Consul` to `Consul`)
* Add typehint where possible
* Force JSON body request where possible (now you must pass an array as body)
* Remove the factory and almost all interfaces
* Bump to PHP 7.4+
* Add support for missing scheme in DSN
* Switch from Travis to GitHub Action
* Add some internal tooling (php-cs-fixer, phpstan, phpunit, Makefile)
* Add MultiLockHandler and MultiSemaphore helpers

---

Previous CHANGELOGs are missing
