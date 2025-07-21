# vim: set tabstop=8 softtabstop=8 noexpandtab:
.PHONY: help
help: ## Displays this list of targets with descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: static-code-analysis
static-code-analysis: vendor ## Runs a static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyse --configuration=phpstan-default.neon.dist --memory-limit=-1

.PHONY: static-code-analysis-baseline
static-code-analysis-baseline: check-symfony vendor ## Generates a baseline for static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyze --configuration=phpstan-default.neon.dist --generate-baseline=phpstan-default-baseline.neon --memory-limit=-1

.PHONY: tests
tests: vendor
	vendor/bin/phpunit tests

.PHONY: vendor
vendor: composer.json composer.lock ## Installs composer dependencies
	composer install

.PHONY: cs
cs: ## Update Coding Standards
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --verbose


postinst:
	DEBCONF_DEBUG=developer /usr/share/debconf/frontend /var/lib/dpkg/info/multiflexi-cli.postinst configure $(nextversion)

redeb:
	 sudo apt -y purge multiflexi-cli; rm ../multiflexi-cli_*_all.deb ; debuild -us -uc ; sudo gdebi  -n ../multiflexi-cli_*_all.deb ; sudo apache2ctl restart

debs:
	debuild -i -us -uc -b

dimage:
	docker build -t vitexsoftware/multiflexi-cli .

demoimage:
	docker build -f Dockerfile.demo -t vitexsoftware/multiflexi-cli .

demorun:
	docker run  -dit --name MultiFlexiCli -p 8282:80 vitexsoftware/multiflexi-cli

drun: dimage
	docker run  -dit --name MultiFlexiCli -p 8080:80 vitexsoftware/multiflexi-cli

phpunit:
	vendor/bin/phpunit -c tests/configuration.xml tests/

clischema:
	./cli.sh describe 

clitest:
	./tests/test-cli.sh

instprobe:
	multiflexi-cli-json2app tests/multiflexi-cli_probe.multiflexi-cli.app.json

reset:
	git fetch origin
	git reset --hard origin/$(git rev-parse --abbrev-ref HEAD)

