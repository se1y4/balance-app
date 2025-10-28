# 1. Клонировать репозиторий
```
git clone https://github.com/se1y4/balance-app.git
cd balance-api
```
# 2. Запуск
```
docker-compose up -d
```
# 3. Применение миграции
```
docker-compose exec app php artisan migrate
```
# 4. (Опционально) Создай тестовых пользователей
```
docker-compose exec app php artisan tinker
docker-compose exec app php artisan make:seeder UserSeeder
```
# В репозитории есть файл с настройками для Postman
```
Balance API.postman_collection.json
```
