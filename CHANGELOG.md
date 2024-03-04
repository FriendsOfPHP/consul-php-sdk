# CHANGELOG

## 5.2.0 (not released yet)

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
