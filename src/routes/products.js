/**
 * Products Routes - API routes for product management
 * Gestión de Productos
 */

const express = require('express');
const router = express.Router();
const dataStore = require('../services/DataStore');

/**
 * GET /api/products
 * Get all products
 */
router.get('/', (req, res) => {
  try {
    const { category, lowStock, active } = req.query;
    let products = dataStore.getAll('products');
    
    if (category) {
      products = products.filter(p => p.category === category);
    }
    if (lowStock === 'true') {
      products = products.filter(p => p.isLowStock);
    }
    if (active !== undefined) {
      products = products.filter(p => p.isActive === (active === 'true'));
    }
    
    res.json({
      success: true,
      data: products,
      count: products.length
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/products/:id
 * Get product by ID
 */
router.get('/:id', (req, res) => {
  try {
    const product = dataStore.getById('products', req.params.id);
    if (!product) {
      return res.status(404).json({
        success: false,
        error: 'Producto no encontrado'
      });
    }
    res.json({
      success: true,
      data: product
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * POST /api/products
 * Create a new product
 */
router.post('/', (req, res) => {
  try {
    const product = dataStore.create('products', req.body);
    res.status(201).json({
      success: true,
      data: product,
      message: 'Producto creado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PUT /api/products/:id
 * Update a product
 */
router.put('/:id', (req, res) => {
  try {
    const product = dataStore.update('products', req.params.id, req.body);
    if (!product) {
      return res.status(404).json({
        success: false,
        error: 'Producto no encontrado'
      });
    }
    res.json({
      success: true,
      data: product,
      message: 'Producto actualizado exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PATCH /api/products/:id/stock
 * Update product stock
 */
router.patch('/:id/stock', (req, res) => {
  try {
    const { quantity, operation } = req.body;
    if (quantity === undefined || !operation) {
      return res.status(400).json({
        success: false,
        error: 'Quantity and operation are required'
      });
    }
    
    const products = dataStore.products;
    const product = products.get(req.params.id);
    if (!product) {
      return res.status(404).json({
        success: false,
        error: 'Producto no encontrado'
      });
    }
    
    product.updateStock(quantity, operation);
    
    res.json({
      success: true,
      data: product.toJSON(),
      message: `Stock actualizado. Nuevo stock: ${product.stockQuantity}`
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * DELETE /api/products/:id
 * Delete a product
 */
router.delete('/:id', (req, res) => {
  try {
    const success = dataStore.delete('products', req.params.id);
    if (!success) {
      return res.status(404).json({
        success: false,
        error: 'Producto no encontrado'
      });
    }
    res.json({
      success: true,
      message: 'Producto eliminado exitosamente'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
