
# Restaurant API (Laravel 12, PHP 8.3, MySQL)

**Функционал:**
- Регистрация/авторизация (Sanctum)
- Добавление ресторанов и роли (owner/manager/waiter)
- График работы ресторана + исключения
- График работы официантов (смены)
- Упр. залами → зонами → столиками
- Упр. меню (категории/позиции)
- Привязка официантов к зонам/столикам

## Установка
```bash
composer install
cp .env.example .env
# укажите БД
php artisan key:generate
php artisan migrate
```

## Запуск
```bash
php artisan serve
```

## Документация API
- Маршруты: `routes/api.php`
- Мини OpenAPI: `openapi.yaml`

## Быстрый старт
1. Зарегистрируй пользователя: `POST /api/auth/register`
2. Вход: `POST /api/auth/login` → токен
3. Создай ресторан: `POST /api/restaurants`
4. Настрой расписание, залы/зоны/столики, персонал и меню.
