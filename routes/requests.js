const express = require('express');
const router = express.Router();
const requestController = require('../controllers/requestController');
const { protect } = require('../middleware/auth');

router.post('/', protect, requestController.createRequest);
router.get('/user', protect, requestController.getUserRequests);
router.get('/item/:itemId', protect, requestController.getRequestsByItem);
router.patch('/:id', protect, requestController.updateRequestStatus);

module.exports = router;
