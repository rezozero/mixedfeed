
test:
	vendor/bin/phpcbf -p
	vendor/bin/phpstan analyse -c phpstan.neon -l 3 ./src
