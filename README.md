# CallCenter Telegram Bot Manager

Веб-приложение для управления сообщениями из Telegram бота через Long Polling.

## Стек

* **Backend:** Laravel 13 + PHP 8.4 + MySQL
* **Frontend:** Vue.js 3 + Vite + Axios
* **Аутентификация:** JWT
* **Интеграция:** Telegram Bot API (Long Polling)
* **Контейнеризация:** Docker + Docker Compose

## Возможности

* Получение сообщений из Telegram бота в реальном времени
* Чат-интерфейс с разделением входящих/исходящих
* Система "Взять в работу" — только один оператор отвечает
* JWT-аутентификация и регистрация
* Полностью Docker-контейнеризировано

## Установка

```bash
# Клонировать репозиторий
git clone https://github.com/YOUR\\\_USERNAME/callcenter-telegram.git
cd callcenter-telegram

# Скопировать .env
cp C7/.env.example C7/.env

# Настроить .env (токен бота, MySQL, JWT)
nano C7/.env

# Запустить
cd C7
docker-compose up -d --build

# Миграции
docker-compose exec app php artisan migrate

# Запустить Long Polling
docker-compose up -d telegram


