#CONFIGURATION
export PROJECT_IP = 127.0.0.1
export PROJECT_NAME   = lot-transmitter
export PROJECT_DOMAIN = ${PROJECT_NAME}.dev.privatrepo
export SHELL = bash
export VERSION = 0.0.1
export PHP_IDE_CONFIG = serverName=${PROJECT_DOMAIN}

#xdebug
export HOST_IP = $(shell tools/print-host-ip.sh)
export HOST_PORT = 9000

export S3_BUCKET = lot_data
export S3_PREFIX =
export S3_PORT = 45701
export REST_ENDPOINT_URL = http://${HOST_IP}:8082

#DOCKER
DOCKER = docker
DOCKER_COMPOSE = docker-compose -p ${PROJECT_NAME}
CLI = $(DOCKER_COMPOSE) exec -T ${PROJECT_NAME}
CONSOLE = $(CLI) ./vendor/bin/console

#COLORS
GREEN  := $(shell tput -Txterm setaf 2)
WHITE  := $(shell tput -Txterm setaf 7)
YELLOW := $(shell tput -Txterm setaf 3)
RED    := $(shell tput -Txterm setaf 5)
RESET  := $(shell tput -Txterm sgr0)

# Add the following 'help' target to your Makefile
# And add help text after each target name starting with '\#\#'
# A category can be added with @category
HELP_FUN = \
        %help; \
        while(<>) { push @{$$help{$$2 // 'options'}}, [$$1, $$3] if /^([a-zA-Z\-]+)\s*:.*\#\#(?:@([a-zA-Z\-]+))?\s(.*)$$/ }; \
        print "usage: make [target]\n\n"; \
        for (sort keys %help) { \
        print "${WHITE}$$_:${RESET}\n"; \
        for (@{$$help{$$_}}) { \
        $$sep = " " x (32 - length $$_->[0]); \
        print "  ${YELLOW}$$_->[0]${RESET}$$sep${GREEN}$$_->[1]${RESET}\n"; \
        }; \
        print "\n"; }

help: ##@other Show this help
	@perl -e '$(HELP_FUN)' $(MAKEFILE_LIST)
.PHONY: help

pull: ##@development pull containers
	$(DOCKER_COMPOSE) pull
.PHONY: pull

start: ##@development start project
	$(DOCKER_COMPOSE) up -d
.PHONY: start

stop: ##@development stop project
	$(DOCKER_COMPOSE) stop
.PHONY: stop

down: ##@development delete project container
	$(DOCKER_COMPOSE) down
.PHONY: down

ps: ##@development show running container
	$(DOCKER_COMPOSE) ps
.PHONY: ps

build: ##@development will rebuild all containers for the project
	@$(DOCKER) run -v "${PWD}/../:/var/www/html" docker-registry.privatrepo.ag/sl-php-7.1-fpm-dev:latest /bin/sh -c "composer config --global --auth http-basic.repo.packagist.com token ${PACKAGIST_TOKEN} && composer install --no-progress"
	$(DOCKER) build -t docker-registry.privatrepo.ag/${PROJECT_NAME}:development -f ../kubernetes/images/Dockerfile ..
	$(DOCKER) push docker-registry.privatrepo.ag/${PROJECT_NAME}:development
.PHONY: build

logs: ##@development show server logs
	$(DOCKER_COMPOSE) logs -f
.PHONY: logs

cli: ##@development get shell
	$(DOCKER_COMPOSE) exec ${PROJECT_NAME} $(SHELL)
.PHONY: cli

.PHONY: composer-token
composer-token:
	$(CLI) composer config --global --auth http-basic.repo.packagist.com token $(PACKAGIST_TOKEN)

composer-install: composer-token ##@development run composer install
	$(CLI) composer install --ansi
.PHONY: composer-install

composer-update: composer-token ##@development run composer update
	$(CLI) composer update --ansi
.PHONY: composer-update

setup: start composer-install ##@development configure development environment
	grep -q -F "${PROJECT_IP} ${PROJECT_DOMAIN}" /etc/hosts || sudo sh -c "echo '${PROJECT_IP} ${PROJECT_DOMAIN}' >> /etc/hosts"
	$(CLI) /bin/sh -c "sed -i '/remote_host/d' /etc/php/conf.d/xdebug.ini"
	$(CLI) /bin/sh -c "echo xdebug.remote_host=$(HOST_IP) >> /etc/php/conf.d/xdebug.ini"
	$(DOCKER_COMPOSE) restart ${PROJECT_NAME}
.PHONY: setup

check-host-ip: ##@other check host ip
	@sh tools/print-host-ip.sh
.PHONY: check-host-ip

refresh-xdebug-ip: ##@other check host ip
	grep -q -F "${PROJECT_IP} ${PROJECT_DOMAIN}" /etc/hosts || sudo sh -c "echo '${PROJECT_IP} ${PROJECT_DOMAIN}' >> /etc/hosts"
	$(CLI) /bin/sh -c "sed -i '/remote_host/d' /etc/php/conf.d/xdebug.ini"
	$(CLI) /bin/sh -c "echo xdebug.remote_host=${HOST_IP} >> /etc/php/conf.d/xdebug.ini"
.PHONY: refresh-xdebug-ip

clean: down ##@other clean up project (docker images and settings)
	@[[ -z `$(DOCKER) images -q docker_${PROJECT_NAME}` ]] || $(DOCKER) rmi docker_${PROJECT_NAME}
	@[[ -z `$(DOCKER) images -q --filter dangling=true` ]] || $(DOCKER) rmi `$(DOCKER) images --filter dangling=true --quiet`
	@[[ -z `$(DOCKER) images | grep "^<none>"` ]] || $(DOCKER) rmi `docker images | grep "^<none>" | awk "{print $3}"`
	@sudo sed -i '' "/${PROJECT_NAME}/d" /etc/hosts
.PHONY: clean

create-bucket: ##@aws
	aws --endpoint-url='http://localhost:${S3_PORT}' s3 mb s3://${S3_BUCKET} --region fakes3
.PHONY: create-bucket

delete-bucket: ##@aws
	$(CLI) /bin/sh -c "rm -rf dev/s3/*"
.PHONY: create-bucket

list-bucket-content: ##aws
	aws --endpoint-url='http://localhost:${S3_PORT}' s3 ls s3://${S3_BUCKET}/ --region fakes3 --recursive
.PHONY: list-bucket-content


upload:
	$(CLI) /bin/sh -c "php dev/scripts/upload_files_to_s3.php"
.PHONY: upload

composer-dump-autoload: ##@development run composer dump-autoload
	$(CLI) composer dump-autoload -a
.PHONY: composer-dump-autoload

codeception-build: ##@testing runs codeception tests
	$(CLI) php vendor/bin/codecept build
.PHONY: codeception-build

codeception-tests: ##@testing runs codeception tests
	$(CLI) php vendor/bin/codecept run
.PHONY: codeception-tests

transmit: ##@transmit files to
	$(CLI) php index.php
.PHONY: transmit
