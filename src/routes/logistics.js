/**
 * Logistics Routes - API routes for shipment/logistics management
 * Logística - Gestión de envíos
 */

const express = require('express');
const router = express.Router();
const dataStore = require('../services/DataStore');

/**
 * GET /api/logistics
 * Get all shipments
 */
router.get('/', (req, res) => {
  try {
    const { status, carrier, orderId } = req.query;
    let shipments = dataStore.getAll('shipments');
    
    if (status) {
      shipments = shipments.filter(s => s.status === status);
    }
    if (carrier) {
      shipments = shipments.filter(s => s.carrier === carrier);
    }
    if (orderId) {
      shipments = shipments.filter(s => s.orderId === orderId);
    }
    
    res.json({
      success: true,
      data: shipments,
      count: shipments.length
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/logistics/:id
 * Get shipment by ID
 */
router.get('/:id', (req, res) => {
  try {
    const shipment = dataStore.getById('shipments', req.params.id);
    if (!shipment) {
      return res.status(404).json({
        success: false,
        error: 'Envío no encontrado'
      });
    }
    res.json({
      success: true,
      data: shipment
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/logistics/track/:trackingNumber
 * Get shipment by tracking number (public endpoint for customers)
 */
router.get('/track/:trackingNumber', (req, res) => {
  try {
    const shipments = dataStore.getAll('shipments');
    const shipment = shipments.find(s => 
      s.trackingNumber === req.params.trackingNumber || 
      s.shipmentNumber === req.params.trackingNumber
    );
    
    if (!shipment) {
      return res.status(404).json({
        success: false,
        error: 'Envío no encontrado'
      });
    }
    
    // Return only public tracking info
    const shipmentInstance = dataStore.shipments.get(shipment.id);
    res.json({
      success: true,
      data: shipmentInstance.getPublicTrackingInfo()
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * POST /api/logistics
 * Create a new shipment
 */
router.post('/', (req, res) => {
  try {
    const shipment = dataStore.create('shipments', req.body);
    
    // If linked to an order, update the order's shipment reference
    if (req.body.orderId) {
      const order = dataStore.orders.get(req.body.orderId);
      if (order) {
        order.trackingNumber = shipment.shipmentNumber;
        order.carrier = shipment.carrier;
      }
    }
    
    res.status(201).json({
      success: true,
      data: shipment,
      message: 'Envío creado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PUT /api/logistics/:id
 * Update a shipment
 */
router.put('/:id', (req, res) => {
  try {
    const shipment = dataStore.update('shipments', req.params.id, req.body);
    if (!shipment) {
      return res.status(404).json({
        success: false,
        error: 'Envío no encontrado'
      });
    }
    res.json({
      success: true,
      data: shipment,
      message: 'Envío actualizado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PATCH /api/logistics/:id/status
 * Update shipment status with location
 */
router.patch('/:id/status', (req, res) => {
  try {
    const { status, location, notes } = req.body;
    if (!status) {
      return res.status(400).json({
        success: false,
        error: 'Status is required'
      });
    }
    
    const shipments = dataStore.shipments;
    const shipment = shipments.get(req.params.id);
    if (!shipment) {
      return res.status(404).json({
        success: false,
        error: 'Envío no encontrado'
      });
    }
    
    shipment.updateStatus(status, location, notes);
    
    // Update linked order status
    if (shipment.orderId) {
      const order = dataStore.orders.get(shipment.orderId);
      if (order) {
        if (status === 'in_transit') {
          order.updateStatus('in_transit', 'Envío en tránsito');
        } else if (status === 'delivered') {
          order.updateStatus('delivered', 'Pedido entregado');
          order.actualDeliveryDate = new Date().toISOString();
        }
      }
    }
    
    res.json({
      success: true,
      data: shipment.toJSON(),
      message: `Estado del envío actualizado a: ${status}`
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PATCH /api/logistics/:id/delivery
 * Confirm delivery
 */
router.patch('/:id/delivery', (req, res) => {
  try {
    const { signedBy, signature, notes } = req.body;
    if (!signedBy) {
      return res.status(400).json({
        success: false,
        error: 'SignedBy is required'
      });
    }
    
    const shipments = dataStore.shipments;
    const shipment = shipments.get(req.params.id);
    if (!shipment) {
      return res.status(404).json({
        success: false,
        error: 'Envío no encontrado'
      });
    }
    
    shipment.confirmDelivery(signedBy, signature, notes);
    
    // Update linked order
    if (shipment.orderId) {
      const order = dataStore.orders.get(shipment.orderId);
      if (order) {
        order.updateStatus('delivered', `Entregado. Recibido por: ${signedBy}`);
        order.actualDeliveryDate = new Date().toISOString();
      }
    }
    
    res.json({
      success: true,
      data: shipment.toJSON(),
      message: `Entrega confirmada. Recibido por: ${signedBy}`
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * DELETE /api/logistics/:id
 * Delete a shipment
 */
router.delete('/:id', (req, res) => {
  try {
    const success = dataStore.delete('shipments', req.params.id);
    if (!success) {
      return res.status(404).json({
        success: false,
        error: 'Envío no encontrado'
      });
    }
    res.json({
      success: true,
      message: 'Envío eliminado exitosamente'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
