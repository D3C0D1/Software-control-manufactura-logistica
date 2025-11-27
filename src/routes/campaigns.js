/**
 * Campaigns Routes - API routes for campaign management
 * Gestión de Campañas de Comercialización y Eventos Gráficos
 */

const express = require('express');
const router = express.Router();
const dataStore = require('../services/DataStore');

/**
 * GET /api/campaigns
 * Get all campaigns
 */
router.get('/', (req, res) => {
  try {
    const { type, status, active } = req.query;
    let campaigns = dataStore.getAll('campaigns');
    
    if (type) {
      campaigns = campaigns.filter(c => c.type === type);
    }
    if (status) {
      campaigns = campaigns.filter(c => c.status === status);
    }
    if (active === 'true') {
      campaigns = campaigns.filter(c => c.isCurrentlyActive);
    }
    
    res.json({
      success: true,
      data: campaigns,
      count: campaigns.length
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/campaigns/:id
 * Get campaign by ID
 */
router.get('/:id', (req, res) => {
  try {
    const campaign = dataStore.getById('campaigns', req.params.id);
    if (!campaign) {
      return res.status(404).json({
        success: false,
        error: 'Campaña no encontrada'
      });
    }
    res.json({
      success: true,
      data: campaign
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/campaigns/:id/orders
 * Get orders linked to a campaign
 */
router.get('/:id/orders', (req, res) => {
  try {
    const campaign = dataStore.getById('campaigns', req.params.id);
    if (!campaign) {
      return res.status(404).json({
        success: false,
        error: 'Campaña no encontrada'
      });
    }
    
    const orders = dataStore.findOrdersByCampaign(req.params.id);
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
 * POST /api/campaigns
 * Create a new campaign
 */
router.post('/', (req, res) => {
  try {
    const campaign = dataStore.create('campaigns', req.body);
    res.status(201).json({
      success: true,
      data: campaign,
      message: 'Campaña creada exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PUT /api/campaigns/:id
 * Update a campaign
 */
router.put('/:id', (req, res) => {
  try {
    const campaign = dataStore.update('campaigns', req.params.id, req.body);
    if (!campaign) {
      return res.status(404).json({
        success: false,
        error: 'Campaña no encontrada'
      });
    }
    res.json({
      success: true,
      data: campaign,
      message: 'Campaña actualizada exitosamente'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * PATCH /api/campaigns/:id/status
 * Update campaign status
 */
router.patch('/:id/status', (req, res) => {
  try {
    const { status } = req.body;
    if (!status) {
      return res.status(400).json({
        success: false,
        error: 'Status is required'
      });
    }
    
    const campaigns = dataStore.campaigns;
    const campaign = campaigns.get(req.params.id);
    if (!campaign) {
      return res.status(404).json({
        success: false,
        error: 'Campaña no encontrada'
      });
    }
    
    campaign.updateStatus(status);
    
    res.json({
      success: true,
      data: campaign.toJSON(),
      message: `Estado de campaña actualizado a: ${status}`
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * DELETE /api/campaigns/:id
 * Delete a campaign
 */
router.delete('/:id', (req, res) => {
  try {
    const success = dataStore.delete('campaigns', req.params.id);
    if (!success) {
      return res.status(404).json({
        success: false,
        error: 'Campaña no encontrada'
      });
    }
    res.json({
      success: true,
      message: 'Campaña eliminada exitosamente'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
