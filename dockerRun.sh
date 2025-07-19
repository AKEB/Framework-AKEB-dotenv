#!/bin/bash

HOST_DIR=${2}/
DOCKER_DIR="/opt"
SCRIPT_DIR=`dirname "${3}"`/
FILE_NAME=${3/"$SCRIPT_DIR"/""}
SCRIPT_DIR=${SCRIPT_DIR/"$HOST_DIR"/""}
version=${1}

ARGS=''
for ((argnum = 4; argnum <= $#; argnum++)); do
  ARGS="${ARGS}${!argnum} "
done

mv ${HOST_DIR}/composer.lock ${HOST_DIR}/composer.lock.local > /dev/null 2>&1
mv ${HOST_DIR}/vendor/ ${HOST_DIR}/vendor.local/ > /dev/null 2>&1

rm -rf ${HOST_DIR}/.phpunit.cache ${HOST_DIR}/.phpunit.result.cache > /dev/null 2>&1
mkdir -p "${HOST_DIR}/docker/composer/" > /dev/null 2>&1

mkdir -p "${HOST_DIR}/docker/composer/${version}/" > /dev/null 2>&1
	
lock_file="${HOST_DIR}/docker/composer/${version}/composer.lock"
composer_folder="${HOST_DIR}/docker/composer/${version}/vendor/"
mv ${lock_file} ${HOST_DIR}/composer.lock > /dev/null 2>&1
mv ${composer_folder} ${HOST_DIR}/vendor/ > /dev/null 2>&1
CMD=""

# CMD="${CMD} apt-get install -y memcached 1>/dev/null 2>&1;"

CMD="${CMD} composer install --prefer-install=auto --no-interaction 1>/dev/null 2>&1;"
CMD="${CMD} composer update --prefer-install=auto --no-interaction 1>/dev/null 2>&1;"

CMD="${CMD} php ${SCRIPT_DIR}${FILE_NAME} "

docker run --rm -v "${HOST_DIR}":/opt -w /opt akeb/nginx-php-fpm-${version}:latest /bin/bash -c "${CMD}"

mv ${HOST_DIR}/composer.lock ${lock_file} > /dev/null 2>&1
mv ${HOST_DIR}/vendor/ ${composer_folder} > /dev/null 2>&1

mv ${HOST_DIR}/composer.lock.local ${HOST_DIR}/composer.lock > /dev/null 2>&1
mv ${HOST_DIR}/vendor.local/ ${HOST_DIR}/vendor/ > /dev/null 2>&1