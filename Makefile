.PHONY: help test-phpcsfixer test-phpstan test-phpunit test-all

.DEFAULT_GOAL := help

help:
	@grep -h -e ' ### ' $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

test-phpcsfixer: ### Execute phpcsfixer
	php-cs-fixer fix

test-phpstan: ### Execute phpstan
	phpstan

test-phpunit: ### Execute phpunit
	vendor/bin/simple-phpunit

test-all: test-phpcsfixer test-phpstan test-phpunit ### Test everything
