const Item = require('../models/Item');
const User = require('../models/User');
const { Op } = require('sequelize');

exports.getAllItems = async (req, res) => {
  try {
    const { search, category, location, limit } = req.query;
    const where = {};

    // Search by title (case-insensitive)
    if (search) {
      where.title = { [Op.like]: `%${search}%` };
    }

    // Filter by category
    if (category) {
      where.category = category;
    }

    // Filter by location (partial match)
    if (location) {
      where.location = { [Op.like]: `%${location}%` };
    }

    const queryOptions = {
      where,
      include: [{ model: User, attributes: ['id', 'name', 'email'] }],
      order: [['created_at', 'DESC']]
    };

    if (limit) {
      queryOptions.limit = parseInt(limit);
    }

    const items = await Item.findAll(queryOptions);
    res.status(200).json(items);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.getItemById = async (req, res) => {
  try {
    const item = await Item.findByPk(req.params.id, {
      include: [{ model: User, attributes: ['id', 'name', 'email'] }]
    });
    if (!item) return res.status(404).json({ status: 'fail', message: 'Item not found' });
    res.status(200).json(item);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.createItem = async (req, res) => {
  try {
    const { title, description, category, location, condition, mfgDate, expDate } = req.body;
    if (!title || !description || !category || !location) {
      return res.status(400).json({ status: 'fail', message: 'Title, description, category and location are required' });
    }

    // Handle images: uploaded files
    let images = [];
    if (req.files && req.files.length > 0) {
      images = req.files.map(file => `/uploads/${file.filename}`);
    }

    const newItem = await Item.create({
      title: title.trim(),
      description: description.trim(),
      category: category.trim(),
      location: location.trim(),
      image: images,
      condition: condition || 'good',
      user_id: req.user.id,
      ...(category === 'medicine' && { mfgDate, expDate })
    });
    res.status(201).json(newItem);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.getUserItems = async (req, res) => {
  try {
    const items = await Item.findAll({
      where: { user_id: req.user.id },
      order: [['created_at', 'DESC']]
    });
    res.status(200).json(items);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.deleteItem = async (req, res) => {
  try {
    const item = await Item.findByPk(req.params.id);
    if (!item) return res.status(404).json({ status: 'fail', message: 'Item not found' });
    
    // Admin can delete any item, user can only delete their own
    if (req.user.role !== 'admin' && item.user_id !== req.user.id) {
      return res.status(403).json({ status: 'fail', message: 'Unauthorized' });
    }
    
    await item.destroy();
    res.status(204).json(null);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};
