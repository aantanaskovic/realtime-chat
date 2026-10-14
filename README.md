# Real-Time Chat & Collaboration Platform

A focused, real-time messaging and team collaboration application built with Laravel and Livewire.

The project demonstrates instantaneous message exchange, live typing states, and reactive conversation management using a modern event-driven architecture.

## Features

- **Real-time messaging**
    - Instant delivery of messages across active chat sessions
    - Real-time text rendering without full or partial page refreshes
    - Dual indicator elements showing standard message arrival

- **Typing indicators**
    - Visual feedback showing when another user is actively typing a reply
    - Smooth animation states that automatically clear when typing stops

- **Unread indicators**
    - Distinct visual badges for conversations with unread activity
    - Automatic state updates to help users easily track missed updates

- **Private spaces**
    - Managed spaces for focused team collaboration
    - Membership tracking and secure conversation boundaries

- **Modern reactive UI**
    - Built using Livewire Volt inline components for high performance
    - Styled with Tailwind CSS, featuring smooth transitions and micro-interactions

## Tech Stack

- PHP 8.4
- Laravel 13
- Livewire 4 (Volt)
- Tailwind CSS 4
- Laravel Reverb (Broadcasting Server)
- Pusher Protocol & Laravel Echo
- MySQL

## Requirements

Before installing the application, make sure your development environment includes:

- PHP 8.4 and the PHP extensions required by Laravel
- Composer
- Node.js and npm
- MySQL
- A running Laravel Reverb server or compatible WebSocket broadcasting server

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/aantanaskovic/realtime-chat.git
cd chat-platform
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create your environment file

```bash
cp .env.example .env
```

On Windows, you can copy `.env.example` to `.env` manually or use:

```powershell
Copy-Item .env.example .env
```

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Configure the database and broadcasting

Create a MySQL database and configure the following values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_platform
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-reverb-app-id
REVERB_APP_KEY=your-reverb-app-key
REVERB_APP_SECRET=your-reverb-app-secret
REVERB_HOST="127.0.0.1"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${VITE_REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Adjust the database name, credentials, and Reverb keys to match your local setup.

### 6. Run migrations and seed data

```bash
php artisan migrate --seed
```

### 7. Install and build frontend assets

```bash
npm install
npm run build
```

### 8. Start the local development servers

To fully run the real-time functionality, you need three separate terminal windows:

**Terminal 1: PHP Application Server**

```bash
php artisan serve
```

**Terminal 2: Laravel Reverb (WebSocket Server)**

```bash
php artisan reverb:start
```

**Terminal 3: Frontend Development (Vite with Hot Reload)**

```bash
npm run dev
```

Now, open `http://127.0.0.1:8000` in your browser to view the application.

## Project Structure

The application follows Laravel's standard project structure with domain logic organized into actions, models, and inline Livewire Volt components.

Key areas include:

```text
app/
├── Actions/
├── Livewire/        # Contains Livewire Volt reactive components
└── Models/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
└── views/           # Blade templates including home.blade.php

routes/
├── channels.php     # Broadcast channel authorization definitions
└── web.php
```

## Security Notes

- Keep `.env` out of version control.
- Never commit passwords, API keys, tokens, or Reverb credentials.
- Ensure all private chat rooms are protected via proper authorization logic inside `routes/channels.php`.
- Use secure WebSockets (`wss://`) and HTTPS in production environments.

## Project Status

This repository is a demonstration and portfolio project.

It is intended to showcase modern reactive full-stack development using Laravel, Livewire Volt, and native real-time broadcasting with Laravel Reverb. Review the code and configuration before using the project in a production environment.

## License

No license has been specified yet.

Add a license file if you intend to permit others to use, modify, or distribute this project.
