/**
 * Production Routes - API routes for production management (MRP)
 * Producción - Planificación de Requerimientos de Materiales
 */

const express = require('express');
const router = express.Router();
const dataStore = require('../services/DataStore');

/**
 * GET /api/production
 * Get all production orders
 */
router.get('/', (req, res) => {
  try {
    const { status, priority, orderId } = req.query;
    let productionOrders = dataStore.getAll('productionOrders');
    
    if (status) {
      productionOrders = productionOrders.filter(po => po.status === status);
    }
    if (priority) {
      productionOrders = productionOrders.filter(po => po.priority === priority);
    }
    if (orderId) {
      productionOrders = productionOrders.filter(po => po.orderId === orderId);
    }
    
    res.json({
      success: true,
      data: productionOrders,
      count: productionOrders.length
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/production/:id
 * Get production order by ID
 */
router.get('/:id', (req, res) => {
  try {
    const productionOrder = dataStore.getById('productionOrders', req.params.id);
    if (!productionOrder) {
      return res.status(404).json({
        success: false,
        error: 'Orden de producción no encontrada'
      });
    }
    res.json({
      success: true,
      data: productionOrder
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * POST /api/production
 * Create a new production order
 */
router.post('/', (req, res) => {
  try {
    const productionOrder = dataStore.create('productionOrders', req.body);
    
    // If linked to an order, update the order's production reference
    if (req.body.orderId) {
      const order = dataStore.orders.get(req.body.orderId);
      if (order) {
        order.productionOrderId = productionOrder.id;
        order.updateStatus('in_production', 'Orden de producción creada');
      }
    }
    
    res.status(201).json({
      success: true,
      data: productionOrder,
      message: 'Orden de producción creada exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PUT /api/production/:id
 * Update a production order
 */
router.put('/:id', (req, res) => {
  try {
    const productionOrder = dataStore.update('productionOrders', req.params.id, req.body);
    if (!productionOrder) {
      return res.status(404).json({
        success: false,
        error: 'Orden de producción no encontrada'
      });
    }
    res.json({
      success: true,
      data: productionOrder,
      message: 'Orden de producción actualizada exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PATCH /api/production/:id/status
 * Update production order status
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
    
    const productionOrders = dataStore.productionOrders;
    const productionOrder = productionOrders.get(req.params.id);
    if (!productionOrder) {
      return res.status(404).json({
        success: false,
        error: 'Orden de producción no encontrada'
      });
    }
    
    productionOrder.updateStatus(status, notes);
    
    // Update linked order status if production is completed
    if (status === 'completed' && productionOrder.orderId) {
      const order = dataStore.orders.get(productionOrder.orderId);
      if (order) {
        order.updateStatus('ready_for_shipping', 'Producción completada');
      }
    }
    
    res.json({
      success: true,
      data: productionOrder.toJSON(),
      message: `Estado de producción actualizado a: ${status}`
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PATCH /api/production/:id/quality
 * Update quality check result
 */
router.patch('/:id/quality', (req, res) => {
  try {
    const { approved, notes, defectsFound } = req.body;
    
    const productionOrders = dataStore.productionOrders;
    const productionOrder = productionOrders.get(req.params.id);
    if (!productionOrder) {
      return res.status(404).json({
        success: false,
        error: 'Orden de producción no encontrada'
      });
    }
    
    productionOrder.updateQualityCheck(approved, notes, defectsFound || 0);
    
    if (approved) {
      productionOrder.updateStatus('completed', 'Control de calidad aprobado');
    }
    
    res.json({
      success: true,
      data: productionOrder.toJSON(),
      message: approved ? 'Control de calidad aprobado' : 'Control de calidad rechazado'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * DELETE /api/production/:id
 * Delete a production order
 */
router.delete('/:id', (req, res) => {
  try {
    const success = dataStore.delete('productionOrders', req.params.id);
    if (!success) {
      return res.status(404).json({
        success: false,
        error: 'Orden de producción no encontrada'
      });
    }
    res.json({
      success: true,
      message: 'Orden de producción eliminada exitosamente'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
