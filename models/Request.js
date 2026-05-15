const { DataTypes } = require('sequelize');
const sequelize = require('./db');
const Item = require('./Item');
const User = require('./User');

const Request = sequelize.define('Request', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  status: {
    type: DataTypes.ENUM('pending', 'approved', 'rejected'),
    defaultValue: 'pending'
  }
}, {
  tableName: 'requests',
  underscored: true
});

Request.belongsTo(Item, { foreignKey: 'item_id' });
Request.belongsTo(User, { foreignKey: 'requester_id' });
Item.hasMany(Request, { foreignKey: 'item_id' });
User.hasMany(Request, { foreignKey: 'requester_id' });

module.exports = Request;
