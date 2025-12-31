# PHP_Laravel12_Google_2_Factor_Authentication_using_API

A complete Laravel 12 project implementing Google Two-Factor Authentication (2FA) using API endpoints. This project demonstrates secure user authentication with optional 2FA using the Google Authenticator mobile application.

This repository is designed for learning, interviews, and real-world API-based authentication systems.

---

## Project Overview

This project provides:

* User authentication using API tokens
* Optional Google 2-Factor Authentication
* QR code generation for Google Authenticator
* Recovery codes for account access
* API-first architecture
* Simple web UI for testing APIs

---

## Features

* User registration and login
* Token-based authentication using Laravel Sanctum
* Google Authenticator 2FA setup
* QR code generation for scanning
* Enable and disable 2FA
* Recovery codes support
* Secure logout
* RESTful API structure

---

## Technology Stack

* PHP 8.1+
* Laravel 12
* Laravel Sanctum
* PragmaRX Google2FA
* BaconQrCode
* MySQL
* Tailwind CSS

---

## Prerequisites

Make sure the following are installed:

* PHP 8.1 or higher
* Composer
* Node.js and npm
* MySQL database
* Apache, Nginx, or PHP built-in server

---

## Installation Guide

### Step 1: Clone Repository

```bash
git clone https://github.com/yourusername/laravel-2fa-api.git
cd laravel-2fa-api
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install JavaScript Dependencies

```bash
npm install
```

### Step 4: Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_2fa
DB_USERNAME=root
DB_PASSWORD=
```

### Step 5: Generate Application Key

```bash
php artisan key:generate
```

### Step 6: Run Database Migrations

```bash
php artisan migrate
```

### Step 7: Publish Google 2FA Configuration

```bash
php artisan vendor:publish --provider="PragmaRX\Google2FALaravel\ServiceProvider"
```

### Step 8: Publish Sanctum Configuration

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

---

## Running the Application

### Start Backend Server

```bash
php artisan serve
```

Application URL:

```
http://127.0.0.1:8000
```

### Compile Frontend Assets

```bash
npm run dev
```

For production:

```bash
npm run build
```
---
## Screenshot
### Login Page
<img width="812" height="592" alt="image" src="https://github.com/user-attachments/assets/394e0c01-6ed8-4eb8-a44a-4c9b6df9c335" />

### Register Page
<img width="713" height="539" alt="image" src="https://github.com/user-attachments/assets/9f5af4cb-f6b8-46f9-b88e-7f6d0bfca73f" />

### Laravel 2FA API Demo
<img width="817" height="970" alt="image" src="https://github.com/user-attachments/assets/f852084f-54d2-44b1-9b4b-18fc5984d3ce" />

### Two-Factor Authentication
<img width="670" height="448" alt="image" src="https://github.com/user-attachments/assets/043abaa9-4a7c-404a-80d3-fcf2fd54e7c6" />

---
---

## API Authentication Endpoints

### Register User

**POST** `/api/auth/register`

Request Body:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

Response:

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "access_token",
  "message": "Registration successful"
}
```

---

### Login User

**POST** `/api/auth/login`

Request Body:

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

Response (2FA disabled):

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "access_token",
  "requires_2fa": false
}
```

Response (2FA enabled):

```json
{
  "message": "2FA verification required",
  "requires_2fa": true,
  "user_id": 1
}
```

---

### Verify 2FA Code

**POST** `/api/auth/verify-2fa`

Request Body:

```json
{
  "user_id": 1,
  "code": "123456"
}
```

Response:

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "access_token",
  "message": "2FA verified successfully"
}
```

---

### Logout User

**POST** `/api/auth/logout`

Header:

```
Authorization: Bearer YOUR_TOKEN
```

Response:

```json
{
  "message": "Logged out successfully"
}
```

---

## Two-Factor Authentication Endpoints

### Generate 2FA Secret

**POST** `/api/2fa/generate`

Response:

```json
{
  "secret": "JBSWY3DPEHPK3PXP",
  "qr_code_url": "otpauth://totp/...",
  "qr_code_svg": "<svg>...</svg>",
  "message": "2FA secret generated successfully"
}
```

---

### Enable 2FA

**POST** `/api/2fa/enable`

Request Body:

```json
{
  "code": "123456"
}
```

Response:

```json
{
  "message": "2FA enabled successfully",
  "recovery_codes": ["CODE1", "CODE2"],
  "user": {
    "id": 1,
    "google2fa_enabled": true
  }
}
```

---

### Disable 2FA

**POST** `/api/2fa/disable`

Response:

```json
{
  "message": "2FA disabled successfully",
  "user": {
    "id": 1,
    "google2fa_enabled": false
  }
}
```

---

### Check 2FA Status

**GET** `/api/2fa/status`

Response:

```json
{
  "google2fa_enabled": true,
  "has_secret": true
}
```

---

## Use Case Flow

1. User registers
2. User logs in
3. User generates 2FA secret
4. User scans QR code in Google Authenticator
5. User verifies OTP to enable 2FA
6. On next login, OTP verification is required

---

## Security Notes

* Tokens are managed securely via Sanctum
* OTP validation uses time-based verification
* Recovery codes provide backup access
* Passwords are hashed using Laravel defaults

---

## Common Issues

* Invalid OTP: Ensure server time is correct
* Unauthorized error: Check Authorization header
* QR not scanning: Use SVG or URL format

---

## License

This project is open-source and available under the MIT License.

---

## Final Notes

This project is suitable for:

* Learning Laravel API security
* Implementing enterprise-level authentication
* College final-year projects
* Interview preparation

You are free to extend this project with role-based access, email alerts, or mobile app integration.
