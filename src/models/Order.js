/**
 * Order Model - Modelo de Pedido
 * Representa un pedido en el sistema de gestión de ciclo de pedidos
 */

const { v4: uuidv4 } = require('uuid');

// Estados del pedido (Order states for tracking)
const OrderStatus = {
  PENDING: 'pending',           // Pendiente - Order created
  CONFIRMED: 'confirmed',       // Confirmado - Order confirmed by sales
  IN_PRODUCTION: 'in_production', // En producción - Being manufactured
  QUALITY_CHECK: 'quality_check', // Control de calidad
  READY_FOR_SHIPPING: 'ready_for_shipping', // Listo para envío
  IN_TRANSIT: 'in_transit',     // En tránsito - Being delivered
  DELIVERED: 'delivered',       // Entregado - Delivered to customer
  CANCELLED: 'cancelled'        // Cancelado
};

// Tipos de pedido (Order types)
const OrderType = {
  STANDARD: 'standard',         // Pedido estándar
  CAMPAIGN: 'campaign',         // Campaña de marketing
  EVENT: 'event',               // Evento gráfico
  WHOLESALE: 'wholesale'        // Venta mayorista
};

class Order {
  constructor(data = {}) {
    this.id = data.id || uuidv4();
    this.orderNumber = data.orderNumber || this.generateOrderNumber();
    this.customerId = data.customerId || null;
    this.customerName = data.customerName || '';
    this.customerEmail = data.customerEmail || '';
    this.customerPhone = data.customerPhone || '';
    
    // Order details
    this.type = data.type || OrderType.STANDARD;
    this.status = data.status || OrderStatus.PENDING;
    this.items = data.items || [];
    this.totalAmount = data.totalAmount || 0;
    this.currency = data.currency || 'USD';
    
    // Campaign/Event details
    this.campaignId = data.campaignId || null;
    this.eventName = data.eventName || '';
    
    // Dates
    this.createdAt = data.createdAt || new Date().toISOString();
    this.updatedAt = data.updatedAt || new Date().toISOString();
    this.expectedDeliveryDate = data.expectedDeliveryDate || null;
    this.actualDeliveryDate = data.actualDeliveryDate || null;
    
    // Tracking history
    this.statusHistory = data.statusHistory || [{
      status: this.status,
      timestamp: this.createdAt,
      notes: 'Pedido creado'
    }];
    
    // Production (MRP) related
    this.productionOrderId = data.productionOrderId || null;
    this.productionPriority = data.productionPriority || 'normal';
    
    // Logistics related
    this.shippingAddress = data.shippingAddress || {};
    this.trackingNumber = data.trackingNumber || null;
    this.carrier = data.carrier || null;
    
    // Notes and metadata
    this.notes = data.notes || '';
    this.salesRepId = data.salesRepId || null;
  }

  generateOrderNumber() {
    const date = new Date();
    const year = date.getFullYear().toString().slice(-2);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    return `ORD-${year}${month}-${random}`;
  }

  updateStatus(newStatus, notes = '') {
    if (!Object.values(OrderStatus).includes(newStatus)) {
      throw new Error(`Invalid status: ${newStatus}`);
    }
    
    this.status = newStatus;
    this.updatedAt = new Date().toISOString();
    this.statusHistory.push({
      status: newStatus,
      timestamp: this.updatedAt,
      notes: notes || `Estado actualizado a: ${newStatus}`
    });
    
    return this;
  }

  addItem(item) {
    this.items.push({
      id: uuidv4(),
      productId: item.productId,
      productName: item.productName,
      quantity: item.quantity,
      unitPrice: item.unitPrice,
      subtotal: item.quantity * item.unitPrice,
      specifications: item.specifications || {}
    });
    this.calculateTotal();
    return this;
  }

  removeItem(itemId) {
    this.items = this.items.filter(item => item.id !== itemId);
    this.calculateTotal();
    return this;
  }

  calculateTotal() {
    this.totalAmount = this.items.reduce((sum, item) => sum + item.subtotal, 0);
    return this.totalAmount;
  }

  toJSON() {
    return {
      id: this.id,
      orderNumber: this.orderNumber,
      customerId: this.customerId,
      customerName: this.customerName,
      customerEmail: this.customerEmail,
      customerPhone: this.customerPhone,
      type: this.type,
      status: this.status,
      items: this.items,
      totalAmount: this.totalAmount,
      currency: this.currency,
      campaignId: this.campaignId,
      eventName: this.eventName,
      createdAt: this.createdAt,
      updatedAt: this.updatedAt,
      expectedDeliveryDate: this.expectedDeliveryDate,
      actualDeliveryDate: this.actualDeliveryDate,
      statusHistory: this.statusHistory,
      productionOrderId: this.productionOrderId,
      productionPriority: this.productionPriority,
      shippingAddress: this.shippingAddress,
      trackingNumber: this.trackingNumber,
      carrier: this.carrier,
      notes: this.notes,
      salesRepId: this.salesRepId
    };
  }
}

module.exports = { Order, OrderStatus, OrderType };
