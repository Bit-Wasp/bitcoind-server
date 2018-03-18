
pretest:
		if [ ! -d vendor ] || [ ! -f composer.lock ]; then composer install; else echo "Already have dependencies"; fi

phpunit-ci: pretest
		[ ! -f build ] && mkdir -p build
		php ${EXT_PHP} vendor/bin/phpunit --coverage-text --coverage-clover=build/coverage.clover


phpcbf: pretest
		vendor/bin/phpcbf --standard=PSR1,PSR2 -n src test/unit

phpcs: pretest
		vendor/bin/phpcs --standard=PSR1,PSR2 -n src test/unit

ocular:
		wget https://scrutinizer-ci.com/ocular.phar

ifdef OCULAR_TOKEN
scrutinizer: ocular
		@php ocular.phar code-coverage:upload --format=php-clover tests/output/coverage.clover --access-token=$(OCULAR_TOKEN);
else
scrutinizer: ocular
		php ocular.phar code-coverage:upload --format=php-clover tests/output/coverage.clover;
endif
