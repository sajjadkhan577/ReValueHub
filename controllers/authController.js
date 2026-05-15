const User = require('../models/User');
const jwt = require('jsonwebtoken');

const signToken = (id) => {
  return jwt.sign({ id }, process.env.JWT_SECRET, {
    expiresIn: '30d'
  });
};

const userPayload = (user) => ({
  id: user.id,
  name: user.name,
  email: user.email,
  phone: user.phone,
  avatar: user.avatar,
  role: user.role
});

exports.register = async (req, res) => {
  try {
    const { name, email, password, phone } = req.body;
    if (!name || !email || !password) {
      return res.status(400).json({ status: 'fail', message: 'Name, email and password are required' });
    }
    if (!/^\S+@\S+\.\S+$/.test(email)) {
      return res.status(400).json({ status: 'fail', message: 'Please provide a valid email address' });
    }
    if (password.length < 6) {
      return res.status(400).json({ status: 'fail', message: 'Password must be at least 6 characters' });
    }

    const newUser = await User.create({
      name: name.trim(),
      email: email.trim().toLowerCase(),
      password,
      phone: phone ? phone.trim() : null
    });
    
    const token = signToken(newUser.id);
    
    res.status(201).json({
      status: 'success',
      token,
      user: userPayload(newUser)
    });
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.login = async (req, res) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) {
      return res.status(400).json({ status: 'fail', message: 'Please provide email and password' });
    }
    
    const user = await User.findOne({ where: { email: email.trim().toLowerCase() } });
    if (!user || !(await user.comparePassword(password))) {
      return res.status(401).json({ status: 'fail', message: 'Incorrect email or password' });
    }
    
    const token = signToken(user.id);
    
    res.status(200).json({
      status: 'success',
      token,
      user: userPayload(user)
    });
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.getMe = async (req, res) => {
  res.status(200).json({
    status: 'success',
    user: userPayload(req.user)
  });
};

exports.updateProfile = async (req, res) => {
  try {
    const name = req.body.name?.trim();
    const email = req.body.email?.trim().toLowerCase();

    if (!name || !email) {
      return res.status(400).json({ status: 'fail', message: 'Name and email are required' });
    }
    if (!/^\S+@\S+\.\S+$/.test(email)) {
      return res.status(400).json({ status: 'fail', message: 'Please provide a valid email address' });
    }

    const updates = {
      name,
      email,
      phone: req.body.phone ? req.body.phone.trim() : req.user.phone
    };
    if (req.file) updates.avatar = `/uploads/${req.file.filename}`;

    await req.user.update(updates);

    res.status(200).json({
      status: 'success',
      user: userPayload(req.user)
    });
  } catch (err) {
    res.status(400).json({ status: 'fail', message: err.message });
  }
};

exports.logout = async (req, res) => {
  res.status(200).json({ status: 'success', message: 'Logged out' });
};
