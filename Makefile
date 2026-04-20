init:
	docker-compose up -d --build
	docker-compose exec php composer install
	npm install
	docker-compose exec php cp .env.example .env
	docker-compose exec php php artisan key:generate
	@make fresh

fresh:
	docker compose exec php php artisan migrate:fresh --seed

up:
	docker-compose up -d

down:
	docker compose down --remove-orphans

restart:
	@make down
	@make up

cache:
	docker-compose exec php php artisan cache:clear
	docker-compose exec php php artisan config:cache

stop:
	docker-compose stop

dev:
	docker-compose exec php npm run dev

sh:
	docker-compose exec php bash