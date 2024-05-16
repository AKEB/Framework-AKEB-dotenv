#!/bin/bash

versions='7.1 7.2 7.3 7.4 8.0 8.1 8.2 8.3'

mv ${PWD}/composer.lock ${PWD}/composer.lock.local > /dev/null 2>&1
mv ${PWD}/vendor/ ${PWD}/vendor.local/ > /dev/null 2>&1

rm -rf ${PWD}/.phpunit.cache ${PWD}/.phpunit.result.cache > /dev/null 2>&1
mkdir -p "${PWD}/docker/composer/" > /dev/null 2>&1

for version in ${versions}; do
	echo "$(tput setaf 16)$(tput setab 2)Run test for PHP ${version}$(tput sgr 0)"
	mkdir -p "${PWD}/docker/composer/${version}/" > /dev/null 2>&1
	
	lock_file="${PWD}/docker/composer/${version}/composer.lock"
	composer_folder="${PWD}/docker/composer/${version}/vendor/"
	mv ${lock_file} ${PWD}/composer.lock > /dev/null 2>&1
	mv ${composer_folder} ${PWD}/vendor/ > /dev/null 2>&1
	CMD=""
	CMD="${CMD} composer install --prefer-install=auto --no-interaction;"
	CMD="${CMD} composer update --prefer-install=auto --no-interaction > /dev/null 2>&1;"

	CMD="${CMD} php ./vendor/bin/phpunit "
	CMD="${CMD} --no-coverage"
	CMD="${CMD} -c ./phpunit.xml"

	docker run --rm -v "$PWD":/opt -w /opt babadzhanyan/php-fpm:${version} /bin/bash -c "${CMD}"

	mv ${PWD}/composer.lock ${lock_file} > /dev/null 2>&1
	mv ${PWD}/vendor/ ${composer_folder} > /dev/null 2>&1
done;

mv ${PWD}/composer.lock.local ${PWD}/composer.lock > /dev/null 2>&1
mv ${PWD}/vendor.local/ ${PWD}/vendor/ > /dev/null 2>&1