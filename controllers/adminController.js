const User = require('../models/User');
const Item = require('../models/Item');
const Request = require('../models/Request');

exports.getAllUsers = async (req, res) => {
  try {
    const users = await User.findAll({
      attributes: ['id', 'name', 'email', 'role', 'created_at']
    });
    res.status(200).json(users);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.getAllItems = async (req, res) => {
  try {
    const items = await Item.findAll({
      include: [{ model: User, attributes: ['name', 'email'] }],
      order: [['created_at', 'DESC']]
    });
    res.status(200).json(items);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.deleteUser = async (req, res) => {
  try {
    const user = await User.findByPk(req.params.id);
    if (!user) return res.status(404).json({ status: 'fail', message: 'User not found' });
    
    await user.destroy();
    res.status(204).json(null);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.getStats = async (req, res) => {
  try {
    const userCount = await User.count();
    const itemCount = await Item.count();
    const requestCount = await Request.count();
    res.status(200).json({
      users: userCount,
      items: itemCount,
      requests: requestCount
    });
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};
