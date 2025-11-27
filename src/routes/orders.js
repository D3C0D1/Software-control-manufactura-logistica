/**
 * Orders Routes - API routes for order management (Sales)
 * Ventas - Gestión de pedidos
 */

const express = require('express');
const router = express.Router();
const dataStore = require('../services/DataStore');

/**
 * GET /api/orders
 * Get all orders
 */
router.get('/', (req, res) => {
  try {
    const { status, customerId, campaignId } = req.query;
    let orders = dataStore.getAll('orders');
    
    if (status) {
      orders = orders.filter(o => o.status === status);
    }
    if (customerId) {
      orders = orders.filter(o => o.customerId === customerId);
    }
    if (campaignId) {
      orders = orders.filter(o => o.campaignId === campaignId);
    }
    
    res.json({
      success: true,
      data: orders,
      count: orders.length
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/orders/:id
 * Get order by ID
 */
router.get('/:id', (req, res) => {
  try {
    const order = dataStore.getById('orders', req.params.id);
    if (!order) {
      return res.status(404).json({
        success: false,
        error: 'Pedido no encontrado'
      });
    }
    res.json({
      success: true,
      data: order
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/orders/:id/tracking
 * Get order with full tracking information
 */
router.get('/:id/tracking', (req, res) => {
  try {
    const orderWithTracking = dataStore.getOrderWithTracking(req.params.id);
    if (!orderWithTracking) {
      return res.status(404).json({
        success: false,
        error: 'Pedido no encontrado'
      });
    }
    res.json({
      success: true,
      data: orderWithTracking
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * POST /api/orders
 * Create a new order
 */
router.post('/', (req, res) => {
  try {
    const order = dataStore.create('orders', req.body);
    res.status(201).json({
      success: true,
      data: order,
      message: 'Pedido creado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PUT /api/orders/:id
 * Update an order
 */
router.put('/:id', (req, res) => {
  try {
    const order = dataStore.update('orders', req.params.id, req.body);
    if (!order) {
      return res.status(404).json({
        success: false,
        error: 'Pedido no encontrado'
      });
    }
    res.json({
      success: true,
      data: order,
      message: 'Pedido actualizado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PATCH /api/orders/:id/status
 * Update order status
 */
router.patch('/:id/status', (req, res) => {
  try {
    const { status, notes } = req.body;
    if (!status) {
      return res.status(400).json({
        success: false,
        error: 'Status is required'
      });
    }
    
    const order = dataStore.updateOrderStatus(req.params.id, status, notes);
    if (!order) {
      return res.status(404).json({
        success: false,
        error: 'Pedido no encontrado'
      });
    }
    res.json({
      success: true,
      data: order,
      message: `Estado del pedido actualizado a: ${status}`
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * DELETE /api/orders/:id
 * Delete an order
 */
router.delete('/:id', (req, res) => {
  try {
    const success = dataStore.delete('orders', req.params.id);
    if (!success) {
      return res.status(404).json({
        success: false,
        error: 'Pedido no encontrado'
      });
    }
    res.json({
      success: true,
      message: 'Pedido eliminado exitosamente'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
