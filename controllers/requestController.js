const Request = require('../models/Request');
const Item = require('../models/Item');
const User = require('../models/User');

exports.createRequest = async (req, res) => {
  try {
    const { item_id } = req.body;
    const item = await Item.findByPk(item_id);
    if (!item) return res.status(404).json({ status: 'fail', message: 'Item not found' });
    
    if (item.user_id === req.user.id) {
      return res.status(400).json({ status: 'fail', message: 'You cannot request your own item' });
    }

    // Check if already requested
    const existing = await Request.findOne({
      where: { item_id, requester_id: req.user.id }
    });
    if (existing) {
      return res.status(400).json({ status: 'fail', message: 'You have already requested this item' });
    }
    
    const newRequest = await Request.create({
      item_id,
      requester_id: req.user.id
    });
    
    res.status(201).json(newRequest);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.getUserRequests = async (req, res) => {
  try {
    const requests = await Request.findAll({
      where: { requester_id: req.user.id },
      include: [
        { model: Item, attributes: ['id', 'title', 'category', 'image', 'location'] },
        { model: User, attributes: ['name', 'email'] }
      ],
      order: [['created_at', 'DESC']]
    });
    res.status(200).json(requests);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.getRequestsByItem = async (req, res) => {
  try {
    const requests = await Request.findAll({
      where: { item_id: req.params.itemId },
      include: [{ model: User, attributes: ['name', 'email'] }]
    });
    res.status(200).json(requests);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.updateRequestStatus = async (req, res) => {
  try {
    const { status } = req.body;
    const request = await Request.findByPk(req.params.id, {
      include: [{ model: Item }]
    });
    
    if (!request) return res.status(404).json({ status: 'fail', message: 'Request not found' });
    
    // Only item owner can approve/reject
    if (request.Item.user_id !== req.user.id) {
      return res.status(403).json({ status: 'fail', message: 'Unauthorized' });
    }
    
    request.status = status;
    await request.save();
    
    res.status(200).json(request);
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};
