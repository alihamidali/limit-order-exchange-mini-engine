# Crypto Trading Platform

A full-stack cryptocurrency trading platform built with **Laravel 10** (backend) and **Vue 3 + Pinia** (frontend).  
Supports user registration, login, wallet management, placing limit orders, real-time updates via **Pusher**, and order book viewing.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Environment Setup](#environment-setup)
- [Backend Setup](#backend-setup)
- [Pusher Integration](#pusher-integration)
- [License](#license)

---

## Features

- User authentication (register, login, logout)
- Profile and wallet overview (USD balance, crypto assets)
- Place buy and sell limit orders
- Cancel orders
- Match orders manually
- View order book (buy/sell)
- Real-time order updates using Pusher
- Token-based authentication via Laravel Sanctum

---

## Tech Stack

**Backend:**
- Laravel 10
- Laravel Sanctum (API authentication)
- Pusher (WebSocket events)



**Database:**
- MySQL / MariaDB

---

## Environment Setup

### Backend (`.env`)

```env
APP_NAME=CryptoTrading
APP_ENV=local
APP_KEY=base64:YourAppKeyHere
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

LOG_CHANNEL=stack
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crypto_trading
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=2089631
PUSHER_APP_KEY=6692b3093204c9706f50
PUSHER_APP_SECRET=ab2719d98fbc4f965724
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
Frontend (.env)
env
Copy code
VITE_API_URL=http://127.0.0.1:8000/api
VITE_PUSHER_KEY=6692b3093204c9706f50
VITE_PUSHER_CLUSTER=mt1
Backend Setup
Clone the repository:

bash
Copy code
git clone <your-repo-url>
cd backend
Install dependencies:

bash
Copy code
composer install
Copy .env:

bash
Copy code
cp .env.example .env
Generate app key:

bash
Copy code
php artisan key:generate
Run migrations:

bash
Copy code
php artisan migrate
Serve the backend:

bash
Copy code
php artisan serve
