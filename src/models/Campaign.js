/**
 * Campaign Model - Modelo de Campaña
 * Gestiona campañas de comercialización y eventos gráficos
 */

const { v4: uuidv4 } = require('uuid');

// Tipos de campaña
const CampaignType = {
  MARKETING: 'marketing',         // Campaña de marketing
  GRAPHIC_EVENT: 'graphic_event', // Evento gráfico
  SEASONAL: 'seasonal',           // Campaña estacional
  PRODUCT_LAUNCH: 'product_launch', // Lanzamiento de producto
  PROMOTIONAL: 'promotional'      // Promocional
};

// Estados de campaña
const CampaignStatus = {
  DRAFT: 'draft',                 // Borrador
  PLANNED: 'planned',             // Planificada
  ACTIVE: 'active',               // Activa
  PAUSED: 'paused',               // Pausada
  COMPLETED: 'completed',         // Completada
  CANCELLED: 'cancelled'          // Cancelada
};

class Campaign {
  constructor(data = {}) {
    this.id = data.id || uuidv4();
    this.campaignCode = data.campaignCode || this.generateCampaignCode();
    this.name = data.name || '';
    this.description = data.description || '';
    
    // Type and status
    this.type = data.type || CampaignType.MARKETING;
    this.status = data.status || CampaignStatus.DRAFT;
    
    // Dates
    this.startDate = data.startDate || null;
    this.endDate = data.endDate || null;
    this.createdAt = data.createdAt || new Date().toISOString();
    this.updatedAt = data.updatedAt || new Date().toISOString();
    
    // Budget and costs
    this.budget = data.budget || 0;
    this.actualCost = data.actualCost || 0;
    this.currency = data.currency || 'USD';
    
    // Products associated with the campaign
    this.products = data.products || [];
    
    // Orders linked to this campaign
    this.orderIds = data.orderIds || [];
    
    // Target metrics
    this.targetSales = data.targetSales || 0;
    this.actualSales = data.actualSales || 0;
    this.targetOrders = data.targetOrders || 0;
    this.actualOrders = data.actualOrders || 0;
    
    // Event-specific details (for graphic events)
    this.eventDetails = data.eventDetails || {
      venue: '',
      address: '',
      expectedAttendees: 0,
      actualAttendees: 0,
      eventDate: null,
      setupDate: null
    };
    
    // Responsible team
    this.teamMembers = data.teamMembers || [];
    this.managerId = data.managerId || null;
    
    // Notes
    this.notes = data.notes || '';
    
    // Attachments/assets
    this.assets = data.assets || [];
  }

  generateCampaignCode() {
    const date = new Date();
    const year = date.getFullYear().toString().slice(-2);
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    return `CAMP-${year}-${random}`;
  }

  updateStatus(newStatus) {
    if (!Object.values(CampaignStatus).includes(newStatus)) {
      throw new Error(`Invalid status: ${newStatus}`);
    }
    this.status = newStatus;
    this.updatedAt = new Date().toISOString();
    return this;
  }

  addProduct(product) {
    this.products.push({
      productId: product.productId,
      productName: product.productName,
      specialPrice: product.specialPrice || null,
      discount: product.discount || 0,
      targetQuantity: product.targetQuantity || 0,
      soldQuantity: 0
    });
    return this;
  }

  linkOrder(orderId) {
    if (!this.orderIds.includes(orderId)) {
      this.orderIds.push(orderId);
      this.actualOrders = this.orderIds.length;
      this.updatedAt = new Date().toISOString();
    }
    return this;
  }

  updateSales(amount) {
    this.actualSales += amount;
    this.updatedAt = new Date().toISOString();
    return this;
  }

  getProgress() {
    return {
      salesProgress: this.targetSales > 0 ? (this.actualSales / this.targetSales) * 100 : 0,
      ordersProgress: this.targetOrders > 0 ? (this.actualOrders / this.targetOrders) * 100 : 0,
      budgetUsed: this.budget > 0 ? (this.actualCost / this.budget) * 100 : 0,
      daysRemaining: this.endDate ? Math.ceil((new Date(this.endDate) - new Date()) / (1000 * 60 * 60 * 24)) : null
    };
  }

  isActive() {
    if (this.status !== CampaignStatus.ACTIVE) return false;
    const now = new Date();
    const start = this.startDate ? new Date(this.startDate) : null;
    const end = this.endDate ? new Date(this.endDate) : null;
    if (start && now < start) return false;
    if (end && now > end) return false;
    return true;
  }

  toJSON() {
    return {
      id: this.id,
      campaignCode: this.campaignCode,
      name: this.name,
      description: this.description,
      type: this.type,
      status: this.status,
      startDate: this.startDate,
      endDate: this.endDate,
      createdAt: this.createdAt,
      updatedAt: this.updatedAt,
      budget: this.budget,
      actualCost: this.actualCost,
      currency: this.currency,
      products: this.products,
      orderIds: this.orderIds,
      targetSales: this.targetSales,
      actualSales: this.actualSales,
      targetOrders: this.targetOrders,
      actualOrders: this.actualOrders,
      eventDetails: this.eventDetails,
      teamMembers: this.teamMembers,
      managerId: this.managerId,
      notes: this.notes,
      assets: this.assets,
      progress: this.getProgress(),
      isCurrentlyActive: this.isActive()
    };
  }
}

module.exports = { Campaign, CampaignType, CampaignStatus };
