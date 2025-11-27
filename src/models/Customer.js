/**
 * Customer Model - Modelo de Cliente
 * Gestiona la información de clientes para el sistema de rastreo
 */

const { v4: uuidv4 } = require('uuid');

// Tipos de cliente
const CustomerType = {
  INDIVIDUAL: 'individual',       // Persona natural
  BUSINESS: 'business',           // Empresa
  WHOLESALE: 'wholesale',         // Mayorista
  DISTRIBUTOR: 'distributor'      // Distribuidor
};

class Customer {
  constructor(data = {}) {
    this.id = data.id || uuidv4();
    this.customerNumber = data.customerNumber || this.generateCustomerNumber();
    
    // Basic info
    this.name = data.name || '';
    this.companyName = data.companyName || '';
    this.type = data.type || CustomerType.INDIVIDUAL;
    
    // Contact info
    this.email = data.email || '';
    this.phone = data.phone || '';
    this.alternatePhone = data.alternatePhone || '';
    
    // Tax info
    this.taxId = data.taxId || '';
    
    // Addresses
    this.billingAddress = data.billingAddress || {
      address: '',
      city: '',
      state: '',
      postalCode: '',
      country: ''
    };
    this.shippingAddresses = data.shippingAddresses || [];
    
    // Preferences
    this.preferredPaymentMethod = data.preferredPaymentMethod || 'cash';
    this.preferredShippingMethod = data.preferredShippingMethod || 'standard';
    this.currency = data.currency || 'USD';
    
    // Credit terms
    this.creditLimit = data.creditLimit || 0;
    this.currentBalance = data.currentBalance || 0;
    this.paymentTermsDays = data.paymentTermsDays || 30;
    
    // Tracking portal access
    this.portalAccess = data.portalAccess || false;
    this.portalUsername = data.portalUsername || '';
    this.portalPasswordHash = data.portalPasswordHash || '';
    
    // Statistics
    this.totalOrders = data.totalOrders || 0;
    this.totalSpent = data.totalSpent || 0;
    this.lastOrderDate = data.lastOrderDate || null;
    
    // Assigned sales rep
    this.salesRepId = data.salesRepId || null;
    
    // Status
    this.isActive = data.isActive !== undefined ? data.isActive : true;
    
    // Timestamps
    this.createdAt = data.createdAt || new Date().toISOString();
    this.updatedAt = data.updatedAt || new Date().toISOString();
    
    // Notes
    this.notes = data.notes || '';
  }

  generateCustomerNumber() {
    const random = Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
    return `CUST-${random}`;
  }

  addShippingAddress(address) {
    this.shippingAddresses.push({
      id: uuidv4(),
      label: address.label || 'Default',
      address: address.address || '',
      city: address.city || '',
      state: address.state || '',
      postalCode: address.postalCode || '',
      country: address.country || '',
      isDefault: this.shippingAddresses.length === 0
    });
    this.updatedAt = new Date().toISOString();
    return this;
  }

  updateStatistics(orderAmount) {
    this.totalOrders += 1;
    this.totalSpent += orderAmount;
    this.lastOrderDate = new Date().toISOString();
    this.updatedAt = new Date().toISOString();
    return this;
  }

  checkCreditAvailable(amount) {
    return (this.creditLimit - this.currentBalance) >= amount;
  }

  enablePortalAccess(username) {
    this.portalAccess = true;
    this.portalUsername = username || this.email;
    this.updatedAt = new Date().toISOString();
    return this;
  }

  getDisplayName() {
    return this.type === CustomerType.INDIVIDUAL ? this.name : (this.companyName || this.name);
  }

  toJSON() {
    return {
      id: this.id,
      customerNumber: this.customerNumber,
      name: this.name,
      companyName: this.companyName,
      displayName: this.getDisplayName(),
      type: this.type,
      email: this.email,
      phone: this.phone,
      alternatePhone: this.alternatePhone,
      taxId: this.taxId,
      billingAddress: this.billingAddress,
      shippingAddresses: this.shippingAddresses,
      preferredPaymentMethod: this.preferredPaymentMethod,
      preferredShippingMethod: this.preferredShippingMethod,
      currency: this.currency,
      creditLimit: this.creditLimit,
      currentBalance: this.currentBalance,
      paymentTermsDays: this.paymentTermsDays,
      portalAccess: this.portalAccess,
      portalUsername: this.portalUsername,
      totalOrders: this.totalOrders,
      totalSpent: this.totalSpent,
      lastOrderDate: this.lastOrderDate,
      salesRepId: this.salesRepId,
      isActive: this.isActive,
      createdAt: this.createdAt,
      updatedAt: this.updatedAt,
      notes: this.notes
    };
  }
}

module.exports = { Customer, CustomerType };
