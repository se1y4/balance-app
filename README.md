1. Клонировать репозиторий
```
git clone https://github.com/se1y4/balance-app.git
cd balance-api
```
2. Запуск
```
docker-compose up -d
```
3. Применение миграции
```
docker-compose exec app php artisan migrate
```
4. (Опционально) Создание тестовых пользователей
```
docker-compose exec app php artisan make:seeder UserSeeder
```
Пополнение баланса
```
curl -X POST http://localhost:8000/api/deposit \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": 500.00,
    "comment": "Пополнение через карту"
  }'
```
Ожидаемый ответ (200):
```
{"status":"ok"}
```

Списание средств
```
curl -X POST http://localhost:8000/api/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": 200.00,
    "comment": "Покупка подписки"
  }'
```
Ожидаемый ответ (200):
```
{"status":"ok"}
```
Если средств недостаточно → ответ (409):
```
{"error":"Insufficient funds"}
```

Перевод между пользователями
```
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "from_user_id": 1,
    "to_user_id": 2,
    "amount": 150.00,
    "comment": "Перевод другу"
  }'
```
Ожидаемый ответ (200):
```
{"status":"ok"}
```
Если from_user_id == to_user_id → ответ (422)
Если недостаточно средств → ответ (409)

Получение баланса
```
curl http://localhost:8000/api/balance/{user_id}
```
Ожидаемый ответ (200):
```
{
  "user_id": 1,
  "balance": 350.00
}
```
