
test:
	vendor/bin/phpcbf -p
	vendor/bin/phpstan analyse -c phpstan.neon

build:
	docker buildx build --push -t rezozero/mixedfeed:latest .
