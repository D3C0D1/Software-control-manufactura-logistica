/**
 * Dashboard Routes - API routes for dashboard and statistics
 * Panel de Control y Estadísticas
 */

const express = require('express');
const router = express.Router();
const dataStore = require('../services/DataStore');

/**
 * GET /api/dashboard
 * Get dashboard statistics
 */
router.get('/', (req, res) => {
  try {
    const stats = dataStore.getDashboardStats();
    res.json({
      success: true,
      data: stats
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/dashboard/orders-summary
 * Get orders summary by status
 */
router.get('/orders-summary', (req, res) => {
  try {
    const orders = dataStore.getAll('orders');
    
    const summary = {
      total: orders.length,
      byStatus: {},
      byType: {},
      recentOrders: orders
        .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
        .slice(0, 5),
      totalRevenue: orders.reduce((sum, o) => sum + (o.totalAmount || 0), 0)
    };
    
    orders.forEach(order => {
      summary.byStatus[order.status] = (summary.byStatus[order.status] || 0) + 1;
      summary.byType[order.type] = (summary.byType[order.type] || 0) + 1;
    });
    
    res.json({
      success: true,
      data: summary
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/dashboard/production-summary
 * Get production summary
 */
router.get('/production-summary', (req, res) => {
  try {
    const productionOrders = dataStore.getAll('productionOrders');
    
    const summary = {
      total: productionOrders.length,
      byStatus: {},
      byPriority: {},
      inProgress: productionOrders.filter(po => po.status === 'in_progress'),
      scheduled: productionOrders.filter(po => po.status === 'scheduled')
    };
    
    productionOrders.forEach(po => {
      summary.byStatus[po.status] = (summary.byStatus[po.status] || 0) + 1;
      summary.byPriority[po.priority] = (summary.byPriority[po.priority] || 0) + 1;
    });
    
    res.json({
      success: true,
      data: summary
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/dashboard/logistics-summary
 * Get logistics summary
 */
router.get('/logistics-summary', (req, res) => {
  try {
    const shipments = dataStore.getAll('shipments');
    
    const summary = {
      total: shipments.length,
      byStatus: {},
      byCarrier: {},
      inTransit: shipments.filter(s => s.status === 'in_transit'),
      pendingDelivery: shipments.filter(s => 
        ['in_transit', 'out_for_delivery'].includes(s.status)
      )
    };
    
    shipments.forEach(s => {
      summary.byStatus[s.status] = (summary.byStatus[s.status] || 0) + 1;
      summary.byCarrier[s.carrier] = (summary.byCarrier[s.carrier] || 0) + 1;
    });
    
    res.json({
      success: true,
      data: summary
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/dashboard/campaigns-summary
 * Get campaigns summary
 */
router.get('/campaigns-summary', (req, res) => {
  try {
    const campaigns = dataStore.getAll('campaigns');
    
    const summary = {
      total: campaigns.length,
      active: campaigns.filter(c => c.isCurrentlyActive),
      byStatus: {},
      byType: {},
      totalBudget: campaigns.reduce((sum, c) => sum + (c.budget || 0), 0),
      totalActualCost: campaigns.reduce((sum, c) => sum + (c.actualCost || 0), 0)
    };
    
    campaigns.forEach(c => {
      summary.byStatus[c.status] = (summary.byStatus[c.status] || 0) + 1;
      summary.byType[c.type] = (summary.byType[c.type] || 0) + 1;
    });
    
    res.json({
      success: true,
      data: summary
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
