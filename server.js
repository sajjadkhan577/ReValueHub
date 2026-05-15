const express = require('express');
const sequelize = require('./models/db');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const mysql = require('mysql2/promise');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;
const HOST = process.env.HOST || '127.0.0.1';

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

fs.mkdirSync(path.join(__dirname, 'uploads'), { recursive: true });

// Serve static files (HTML/images)
app.use(express.static(path.join(__dirname)));

// Serve uploaded images
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// Basic route test
app.get('/api/ping', (req, res) => {
  res.json({ message: 'ReValue Hub API running!' });
});

async function ensureDatabase() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST || 'localhost',
    port: process.env.DB_PORT || 3306,
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    socketPath: process.env.DB_SOCKET || undefined
  });
  const dbName = process.env.DB_NAME || 'revaluehub';
  await connection.query(`CREATE DATABASE IF NOT EXISTS \`${dbName}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
  await connection.end();
}

// Require models to register them with sequelize before sync
require('./models/User');
require('./models/Item');
require('./models/Request');

// DB Connect & Seed
(async () => {
  try {
    await ensureDatabase();
    await sequelize.authenticate();
    console.log('✅ MariaDB connected');
    await sequelize.sync({ alter: true });
    console.log('✅ Tables synced');

    // Seed dummy data if empty
    const seed = require('./seeders/seed');
    await seed();
  } catch (err) {
    console.error('❌ DB error (continuing without DB):', err.message);
  }
})();

// Routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/items', require('./routes/items'));
app.use('/api/requests', require('./routes/requests'));
app.use('/api/admin', require('./routes/admin'));

const authController = require('./controllers/authController');
const itemController = require('./controllers/itemController');
const requestController = require('./controllers/requestController');
const { protect } = require('./middleware/auth');
const itemsRouter = require('./routes/items');

app.post('/register', authController.register);
app.post('/login', authController.login);
app.post('/logout', authController.logout);
app.post('/create-post', protect, itemsRouter.uploadSingle, itemController.createItem);
app.get('/get-posts', itemController.getAllItems);
app.get('/get-post/:id', itemController.getItemById);
app.delete('/delete-post/:id', protect, itemController.deleteItem);
app.post('/request-item', protect, requestController.createRequest);

// 404 handler
app.use('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'landing.html'));
});

app.listen(PORT, HOST, () => {
  console.log(`🚀 Server running on http://localhost:${PORT}`);
  console.log(`📱 Landing: http://localhost:${PORT}/landing.html`);
  console.log(`🔧 API: http://localhost:${PORT}/api/ping`);
});
