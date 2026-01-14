# Booking Management System - Setup Guide

## Backend Setup (Laravel)

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL/MariaDB or SQLite
- XAMPP (already installed)

### Configuration

1. **Database Configuration**
   - The application is currently configured to use SQLite (database.sqlite)
   - To use MySQL, update `.env` file:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=booking_db
     DB_USERNAME=root
     DB_PASSWORD=
     ```

2. **Environment Variables**
   - The `.env` file should already be configured
   - Make sure `APP_URL` is set correctly

3. **Run Migrations** (Already done)
   ```bash
   php artisan migrate
   ```

4. **Seed Database** (Already done)
   ```bash
   php artisan db:seed
   ```

5. **Start Laravel Server**
   ```bash
   php artisan serve
   ```
   The API will be available at: `http://localhost:8000`

### Default Admin Credentials
- **Email**: admin@booking.com
- **Password**: password

### API Endpoints

- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout (requires auth)
- `GET /api/auth/me` - Get current user (requires auth)
- `GET /api/categories` - Get all categories (requires auth)
- `GET /api/time-slots` - Get all time slots (requires auth)
- `GET /api/calendar/weeks/{month}/{year}` - Get weeks for a month (requires auth)
- `GET /api/bookings` - Get bookings with filters (requires auth)
- `POST /api/bookings` - Create booking (requires auth)
- `GET /api/bookings/{id}` - Get booking by ID (requires auth)
- `PUT /api/bookings/{id}` - Update booking (requires auth)
- `DELETE /api/bookings/{id}` - Delete booking (requires auth)

## Frontend Setup (React)

### Prerequisites
- Node.js 18+ and npm

### Configuration

1. **Environment Variables**
   - Create `.env` file in `booking-frontend` directory:
     ```
     VITE_API_BASE_URL=http://localhost:8000/api
     ```

2. **Install Dependencies** (Already done)
   ```bash
   npm install
   ```

3. **Start Development Server**
   ```bash
   npm run dev
   ```
   The frontend will be available at: `http://localhost:5173`

### Default Login
- **Email**: admin@booking.com
- **Password**: password

## Project Structure

### Backend
```
booking-backend/
├── app/
│   ├── Http/Controllers/    # API Controllers
│   └── Models/               # Eloquent Models
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/             # Database seeders
└── routes/
    └── api.php              # API routes
```

### Frontend
```
booking-frontend/
├── src/
│   ├── components/          # Reusable components
│   ├── context/             # React Context (Auth)
│   ├── pages/               # Page components
│   ├── services/            # API service layer
│   └── utils/               # Utility functions
```

## Features

- ✅ Hierarchical navigation (Month → Category → Week → Daily Table)
- ✅ Time slot scheduling (9:00 AM - 5:00 PM hourly slots)
- ✅ Booking management (Create, Read, Update, Delete)
- ✅ Payment tracking (Status, Amount, Balance)
- ✅ Search functionality (Invoice number, Phone number)
- ✅ Responsive design
- ✅ Multi-user authentication

## Troubleshooting

1. **CORS Issues**: Make sure the frontend URL is added to `SANCTUM_STATEFUL_DOMAINS` in `.env`
2. **Database Connection**: Verify database credentials in `.env`
3. **API Not Found**: Ensure Laravel server is running on port 8000
4. **Frontend Not Loading**: Check that `VITE_API_BASE_URL` is set correctly

