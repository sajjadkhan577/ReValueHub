const { DataTypes } = require('sequelize');
const sequelize = require('./db');
const User = require('./User');

const Item = sequelize.define('Item', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  title: {
    type: DataTypes.STRING,
    allowNull: false
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: false
  },
  category: {
    type: DataTypes.STRING,
    allowNull: false
  },
  location: {
    type: DataTypes.STRING,
    allowNull: false
  },
  image: {
    type: DataTypes.TEXT,
    allowNull: true,
    get() {
      const rawValue = this.getDataValue('image');
      return rawValue ? JSON.parse(rawValue) : [];
    },
    set(val) {
      this.setDataValue('image', JSON.stringify(val));
    }
  },
  condition: {
    type: DataTypes.STRING,
    allowNull: true,
    defaultValue: 'good'
  },
  mfgDate: {
    type: DataTypes.DATEONLY,
    allowNull: true
  },
  expDate: {
    type: DataTypes.DATEONLY,
    allowNull: true
  }
}, {
  tableName: 'posts',
  underscored: true
});

Item.belongsTo(User, { foreignKey: 'user_id' });
User.hasMany(Item, { foreignKey: 'user_id' });

module.exports = Item;
