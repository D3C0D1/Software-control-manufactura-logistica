/**
 * Shipment Model - Modelo de Envío (Logística)
 * Gestiona el seguimiento de envíos y logística
 */

const { v4: uuidv4 } = require('uuid');

// Estados del envío
const ShipmentStatus = {
  PENDING: 'pending',             // Pendiente
  PREPARING: 'preparing',         // Preparando
  READY: 'ready',                 // Listo para envío
  PICKED_UP: 'picked_up',         // Recogido por transportista
  IN_TRANSIT: 'in_transit',       // En tránsito
  OUT_FOR_DELIVERY: 'out_for_delivery', // En camino a entrega
  DELIVERED: 'delivered',         // Entregado
  FAILED_DELIVERY: 'failed_delivery', // Entrega fallida
  RETURNED: 'returned',           // Devuelto
  CANCELLED: 'cancelled'          // Cancelado
};

// Tipos de envío
const ShipmentType = {
  STANDARD: 'standard',           // Estándar
  EXPRESS: 'express',             // Exprés
  OVERNIGHT: 'overnight',         // Nocturno
  SAME_DAY: 'same_day',           // Mismo día
  SCHEDULED: 'scheduled'          // Programado
};

// Transportistas
const Carrier = {
  INTERNAL: 'internal',           // Transporte propio
  FEDEX: 'fedex',
  UPS: 'ups',
  DHL: 'dhl',
  LOCAL: 'local',                 // Mensajería local
  OTHER: 'other'
};

class Shipment {
  constructor(data = {}) {
    this.id = data.id || uuidv4();
    this.shipmentNumber = data.shipmentNumber || this.generateShipmentNumber();
    
    // Linked order
    this.orderId = data.orderId || null;
    this.orderNumber = data.orderNumber || null;
    
    // Status
    this.status = data.status || ShipmentStatus.PENDING;
    this.type = data.type || ShipmentType.STANDARD;
    
    // Carrier information
    this.carrier = data.carrier || Carrier.INTERNAL;
    this.trackingNumber = data.trackingNumber || null;
    this.carrierTrackingUrl = data.carrierTrackingUrl || null;
    
    // Origin address (warehouse/factory)
    this.originAddress = data.originAddress || {
      name: '',
      address: '',
      city: '',
      state: '',
      postalCode: '',
      country: '',
      phone: ''
    };
    
    // Destination address (customer)
    this.destinationAddress = data.destinationAddress || {
      name: '',
      address: '',
      city: '',
      state: '',
      postalCode: '',
      country: '',
      phone: '',
      instructions: ''
    };
    
    // Package details
    this.packages = data.packages || [];
    this.totalWeight = data.totalWeight || 0;
    this.totalVolume = data.totalVolume || 0;
    
    // Costs
    this.shippingCost = data.shippingCost || 0;
    this.insuranceCost = data.insuranceCost || 0;
    this.totalCost = data.totalCost || 0;
    
    // Dates
    this.createdAt = data.createdAt || new Date().toISOString();
    this.updatedAt = data.updatedAt || new Date().toISOString();
    this.estimatedDeliveryDate = data.estimatedDeliveryDate || null;
    this.actualDeliveryDate = data.actualDeliveryDate || null;
    this.pickupDate = data.pickupDate || null;
    
    // Delivery confirmation
    this.deliveryConfirmation = data.deliveryConfirmation || {
      signature: null,
      signedBy: null,
      deliveryPhoto: null,
      notes: ''
    };
    
    // Status history with location tracking
    this.trackingHistory = data.trackingHistory || [{
      status: this.status,
      timestamp: this.createdAt,
      location: '',
      notes: 'Envío creado'
    }];
    
    // Notes
    this.notes = data.notes || '';
  }

  generateShipmentNumber() {
    const date = new Date();
    const year = date.getFullYear().toString().slice(-2);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    return `SHIP-${year}${month}-${random}`;
  }

  updateStatus(newStatus, location = '', notes = '') {
    if (!Object.values(ShipmentStatus).includes(newStatus)) {
      throw new Error(`Invalid status: ${newStatus}`);
    }
    
    this.status = newStatus;
    this.updatedAt = new Date().toISOString();
    
    // Update delivery date when delivered
    if (newStatus === ShipmentStatus.DELIVERED) {
      this.actualDeliveryDate = this.updatedAt;
    }
    
    this.trackingHistory.push({
      status: newStatus,
      timestamp: this.updatedAt,
      location: location,
      notes: notes || `Estado actualizado a: ${newStatus}`
    });
    
    return this;
  }

  addPackage(pkg) {
    const newPackage = {
      id: uuidv4(),
      description: pkg.description || '',
      weight: pkg.weight || 0,
      dimensions: pkg.dimensions || { length: 0, width: 0, height: 0 },
      contents: pkg.contents || []
    };
    
    this.packages.push(newPackage);
    this.calculateTotals();
    return this;
  }

  calculateTotals() {
    this.totalWeight = this.packages.reduce((sum, pkg) => sum + pkg.weight, 0);
    this.totalVolume = this.packages.reduce((sum, pkg) => {
      const dims = pkg.dimensions;
      return sum + (dims.length * dims.width * dims.height);
    }, 0);
    this.totalCost = this.shippingCost + this.insuranceCost;
    return this;
  }

  confirmDelivery(signedBy, signature = null, notes = '') {
    this.deliveryConfirmation = {
      signature: signature,
      signedBy: signedBy,
      deliveryPhoto: null,
      notes: notes,
      timestamp: new Date().toISOString()
    };
    this.updateStatus(ShipmentStatus.DELIVERED, '', `Entregado. Recibido por: ${signedBy}`);
    return this;
  }

  getPublicTrackingInfo() {
    // Information safe to share with customers
    return {
      shipmentNumber: this.shipmentNumber,
      status: this.status,
      carrier: this.carrier,
      trackingNumber: this.trackingNumber,
      estimatedDeliveryDate: this.estimatedDeliveryDate,
      actualDeliveryDate: this.actualDeliveryDate,
      trackingHistory: this.trackingHistory.map(entry => ({
        status: entry.status,
        timestamp: entry.timestamp,
        location: entry.location,
        notes: entry.notes
      }))
    };
  }

  toJSON() {
    return {
      id: this.id,
      shipmentNumber: this.shipmentNumber,
      orderId: this.orderId,
      orderNumber: this.orderNumber,
      status: this.status,
      type: this.type,
      carrier: this.carrier,
      trackingNumber: this.trackingNumber,
      carrierTrackingUrl: this.carrierTrackingUrl,
      originAddress: this.originAddress,
      destinationAddress: this.destinationAddress,
      packages: this.packages,
      totalWeight: this.totalWeight,
      totalVolume: this.totalVolume,
      shippingCost: this.shippingCost,
      insuranceCost: this.insuranceCost,
      totalCost: this.totalCost,
      createdAt: this.createdAt,
      updatedAt: this.updatedAt,
      estimatedDeliveryDate: this.estimatedDeliveryDate,
      actualDeliveryDate: this.actualDeliveryDate,
      pickupDate: this.pickupDate,
      deliveryConfirmation: this.deliveryConfirmation,
      trackingHistory: this.trackingHistory,
      notes: this.notes
    };
  }
}

module.exports = { Shipment, ShipmentStatus, ShipmentType, Carrier };
