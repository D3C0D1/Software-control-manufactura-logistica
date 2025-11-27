/**
 * Tracking Routes - Public API routes for order tracking
 * Sistema de Rastreo de Estados para el Cliente
 */

const express = require('express');
const router = express.Router();
const dataStore = require('../services/DataStore');

/**
 * GET /api/tracking/order/:orderNumber
 * Track order by order number (public endpoint)
 */
router.get('/order/:orderNumber', (req, res) => {
  try {
    const orders = dataStore.getAll('orders');
    const order = orders.find(o => 
      o.orderNumber === req.params.orderNumber || 
      o.id === req.params.orderNumber
    );
    
    if (!order) {
      return res.status(404).json({
        success: false,
        error: 'Pedido no encontrado'
      });
    }
    
    // Return simplified tracking info for customers
    const trackingInfo = {
      orderNumber: order.orderNumber,
      status: order.status,
      statusDescription: getStatusDescription(order.status),
      type: order.type,
      createdAt: order.createdAt,
      expectedDeliveryDate: order.expectedDeliveryDate,
      actualDeliveryDate: order.actualDeliveryDate,
      trackingNumber: order.trackingNumber,
      carrier: order.carrier,
      statusHistory: order.statusHistory.map(h => ({
        status: h.status,
        statusDescription: getStatusDescription(h.status),
        timestamp: h.timestamp,
        notes: h.notes
      })),
      timeline: generateTimeline(order)
    };
    
    res.json({
      success: true,
      data: trackingInfo
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/tracking/shipment/:trackingNumber
 * Track shipment by tracking number
 */
router.get('/shipment/:trackingNumber', (req, res) => {
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
    
    const shipmentInstance = dataStore.shipments.get(shipment.id);
    const trackingInfo = shipmentInstance.getPublicTrackingInfo();
    
    res.json({
      success: true,
      data: {
        ...trackingInfo,
        statusDescription: getShipmentStatusDescription(trackingInfo.status)
      }
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * GET /api/tracking/customer/:customerId
 * Get all orders for a customer (requires authentication in production)
 */
router.get('/customer/:customerId', (req, res) => {
  try {
    const customer = dataStore.getById('customers', req.params.customerId);
    if (!customer) {
      return res.status(404).json({
        success: false,
        error: 'Cliente no encontrado'
      });
    }
    
    const orders = dataStore.findOrdersByCustomer(req.params.customerId);
    const ordersWithTracking = orders.map(order => ({
      orderNumber: order.orderNumber,
      status: order.status,
      statusDescription: getStatusDescription(order.status),
      type: order.type,
      totalAmount: order.totalAmount,
      createdAt: order.createdAt,
      expectedDeliveryDate: order.expectedDeliveryDate,
      actualDeliveryDate: order.actualDeliveryDate,
      trackingNumber: order.trackingNumber
    }));
    
    res.json({
      success: true,
      data: {
        customer: {
          name: customer.displayName,
          email: customer.email
        },
        orders: ordersWithTracking,
        count: ordersWithTracking.length
      }
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Helper functions
function getStatusDescription(status) {
  const descriptions = {
    'pending': 'Pendiente - Su pedido ha sido recibido',
    'confirmed': 'Confirmado - Su pedido ha sido confirmado',
    'in_production': 'En Producción - Su pedido está siendo fabricado',
    'quality_check': 'Control de Calidad - Verificando calidad del producto',
    'ready_for_shipping': 'Listo para Envío - Su pedido está listo para ser enviado',
    'in_transit': 'En Tránsito - Su pedido está en camino',
    'delivered': 'Entregado - Su pedido ha sido entregado',
    'cancelled': 'Cancelado - Su pedido ha sido cancelado'
  };
  return descriptions[status] || status;
}

function getShipmentStatusDescription(status) {
  const descriptions = {
    'pending': 'Pendiente - Envío pendiente de preparación',
    'preparing': 'Preparando - Empacando su pedido',
    'ready': 'Listo - Preparado para recolección',
    'picked_up': 'Recogido - Recogido por el transportista',
    'in_transit': 'En Tránsito - En camino a su destino',
    'out_for_delivery': 'En Reparto - Salió para entrega',
    'delivered': 'Entregado - Entregado exitosamente',
    'failed_delivery': 'Entrega Fallida - No se pudo entregar',
    'returned': 'Devuelto - Paquete devuelto al remitente',
    'cancelled': 'Cancelado - Envío cancelado'
  };
  return descriptions[status] || status;
}

function generateTimeline(order) {
  const allStatuses = [
    { key: 'pending', label: 'Recibido', icon: '📋' },
    { key: 'confirmed', label: 'Confirmado', icon: '✅' },
    { key: 'in_production', label: 'En Producción', icon: '🏭' },
    { key: 'quality_check', label: 'Control de Calidad', icon: '🔍' },
    { key: 'ready_for_shipping', label: 'Listo para Envío', icon: '📦' },
    { key: 'in_transit', label: 'En Tránsito', icon: '🚚' },
    { key: 'delivered', label: 'Entregado', icon: '🎉' }
  ];
  
  const completedStatuses = order.statusHistory.map(h => h.status);
  const currentStatusIndex = allStatuses.findIndex(s => s.key === order.status);
  
  return allStatuses.map((status, index) => {
    const historyEntry = order.statusHistory.find(h => h.status === status.key);
    return {
      ...status,
      completed: completedStatuses.includes(status.key),
      current: status.key === order.status,
      timestamp: historyEntry ? historyEntry.timestamp : null,
      notes: historyEntry ? historyEntry.notes : null
    };
  });
}

module.exports = router;
