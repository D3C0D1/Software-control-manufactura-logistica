/**
 * Customers Routes - API routes for customer management
 * Gestión de Clientes y Portal de Rastreo
 */

const express = require('express');
const router = express.Router();
const dataStore = require('../services/DataStore');

/**
 * GET /api/customers
 * Get all customers
 */
router.get('/', (req, res) => {
  try {
    const { type, active } = req.query;
    let customers = dataStore.getAll('customers');
    
    if (type) {
      customers = customers.filter(c => c.type === type);
    }
    if (active !== undefined) {
      customers = customers.filter(c => c.isActive === (active === 'true'));
    }
    
    res.json({
      success: true,
      data: customers,
      count: customers.length
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/customers/:id
 * Get customer by ID
 */
router.get('/:id', (req, res) => {
  try {
    const customer = dataStore.getById('customers', req.params.id);
    if (!customer) {
      return res.status(404).json({
        success: false,
        error: 'Cliente no encontrado'
      });
    }
    res.json({
      success: true,
      data: customer
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/customers/:id/orders
 * Get orders for a customer
 */
router.get('/:id/orders', (req, res) => {
  try {
    const customer = dataStore.getById('customers', req.params.id);
    if (!customer) {
      return res.status(404).json({
        success: false,
        error: 'Cliente no encontrado'
      });
    }
    
    const orders = dataStore.findOrdersByCustomer(req.params.id);
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
 * POST /api/customers
 * Create a new customer
 */
router.post('/', (req, res) => {
  try {
    const customer = dataStore.create('customers', req.body);
    res.status(201).json({
      success: true,
      data: customer,
      message: 'Cliente creado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PUT /api/customers/:id
 * Update a customer
 */
router.put('/:id', (req, res) => {
  try {
    const customer = dataStore.update('customers', req.params.id, req.body);
    if (!customer) {
      return res.status(404).json({
        success: false,
        error: 'Cliente no encontrado'
      });
    }
    res.json({
      success: true,
      data: customer,
      message: 'Cliente actualizado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PATCH /api/customers/:id/portal-access
 * Enable portal access for customer tracking
 */
router.patch('/:id/portal-access', (req, res) => {
  try {
    const { username } = req.body;
    
    const customers = dataStore.customers;
    const customer = customers.get(req.params.id);
    if (!customer) {
      return res.status(404).json({
        success: false,
        error: 'Cliente no encontrado'
      });
    }
    
    customer.enablePortalAccess(username);
    
    res.json({
      success: true,
      data: customer.toJSON(),
      message: 'Acceso al portal habilitado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * DELETE /api/customers/:id
 * Delete a customer
 */
router.delete('/:id', (req, res) => {
  try {
    const success = dataStore.delete('customers', req.params.id);
    if (!success) {
      return res.status(404).json({
        success: false,
        error: 'Cliente no encontrado'
      });
    }
    res.json({
      success: true,
      message: 'Cliente eliminado exitosamente'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
