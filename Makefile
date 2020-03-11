
test:
	vendor/bin/phpcbf -p
	vendor/bin/phpstan analyse -l 2 ./src
