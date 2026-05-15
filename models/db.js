const { Sequelize } = require('sequelize');
require('dotenv').config();

const sequelize = new Sequelize({
  dialect: 'mysql',
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 3306,
  database: process.env.DB_NAME || 'revaluehub',
  username: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  dialectOptions: process.env.DB_SOCKET ? { socketPath: process.env.DB_SOCKET } : {},
  logging: false,
  define: {
    timestamps: true,
    createdAt: 'created_at',
    updatedAt: 'updated_at'
  }
});

module.exports = sequelize;
