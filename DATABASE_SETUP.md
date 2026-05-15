# ReValue Hub MariaDB Setup

This project uses MariaDB through Sequelize. The app reads database settings from `.env`, creates the `revaluehub` database if the configured user has permission, syncs tables, and seeds demo accounts/items when the database is empty.

## 1. Create the database user

Run these commands in your terminal. If root login fails without `sudo`, use `sudo mariadb`.

```sql
CREATE DATABASE IF NOT EXISTS revaluehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'revalue_user'@'localhost' IDENTIFIED BY 'revalue_password';
GRANT ALL PRIVILEGES ON revaluehub.* TO 'revalue_user'@'localhost';
FLUSH PRIVILEGES;
```

One-line version:

```bash
sudo mariadb -e "CREATE DATABASE IF NOT EXISTS revaluehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'revalue_user'@'localhost' IDENTIFIED BY 'revalue_password'; GRANT ALL PRIVILEGES ON revaluehub.* TO 'revalue_user'@'localhost'; FLUSH PRIVILEGES;"
```

## 2. Update `.env`

Use these values:

```env
NODE_ENV=development
PORT=3000
DB_HOST=localhost
DB_PORT=3306
DB_NAME=revaluehub
DB_USER=revalue_user
DB_PASS=revalue_password
JWT_SECRET=your-super-secret-jwt-key-change-in-production
```

## 3. Start the app

```bash
npm start
```

If port `3000` is busy:

```bash
PORT=3001 npm start
```

On a successful DB connection you should see:

```text
MariaDB connected
Tables synced
Seeded ... items and 3 users
```

## 4. Demo logins

Admin:

```text
admin@revalue.com
admin123
```

Users:

```text
sarah@revalue.com
user123
```

```text
david@revalue.com
user123
```

## 5. Pages

App:

```text
http://localhost:3000/Landing%20page.html
```

User login:

```text
http://localhost:3000/Login%20page.html
```

User dashboard:

```text
http://localhost:3000/User%20dashboard.html
```

Admin dashboard:

```text
http://localhost:3000/Admin%20dashboard.html
```

Adminer is not included in this project. If you install Adminer separately, use:

```text
System: MySQL
Server: localhost
Username: revalue_user
Password: revalue_password
Database: revaluehub
```
