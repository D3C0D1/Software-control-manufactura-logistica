/**
 * Production Order Model - Modelo de Orden de Producción (MRP)
 * Gestiona la planificación de materiales y producción
 */

const { v4: uuidv4 } = require('uuid');

// Estados de la orden de producción
const ProductionStatus = {
  SCHEDULED: 'scheduled',       // Programado
  IN_QUEUE: 'in_queue',         // En cola
  IN_PROGRESS: 'in_progress',   // En progreso
  PAUSED: 'paused',             // Pausado
  QUALITY_CHECK: 'quality_check', // Control de calidad
  COMPLETED: 'completed',       // Completado
  CANCELLED: 'cancelled'        // Cancelado
};

// Prioridades de producción
const ProductionPriority = {
  LOW: 'low',
  NORMAL: 'normal',
  HIGH: 'high',
  URGENT: 'urgent'
};

class ProductionOrder {
  constructor(data = {}) {
    this.id = data.id || uuidv4();
    this.productionNumber = data.productionNumber || this.generateProductionNumber();
    
    // Linked order
    this.orderId = data.orderId || null;
    this.orderNumber = data.orderNumber || null;
    
    // Production details
    this.status = data.status || ProductionStatus.SCHEDULED;
    this.priority = data.priority || ProductionPriority.NORMAL;
    
    // Items to produce
    this.productionItems = data.productionItems || [];
    
    // Materials required (MRP - Material Requirements Planning)
    this.materialsRequired = data.materialsRequired || [];
    this.materialsCost = data.materialsCost || 0;
    
    // Resources
    this.assignedMachines = data.assignedMachines || [];
    this.assignedWorkers = data.assignedWorkers || [];
    
    // Time tracking
    this.estimatedHours = data.estimatedHours || 0;
    this.actualHours = data.actualHours || 0;
    
    // Dates
    this.scheduledStartDate = data.scheduledStartDate || null;
    this.scheduledEndDate = data.scheduledEndDate || null;
    this.actualStartDate = data.actualStartDate || null;
    this.actualEndDate = data.actualEndDate || null;
    this.createdAt = data.createdAt || new Date().toISOString();
    this.updatedAt = data.updatedAt || new Date().toISOString();
    
    // Quality control
    this.qualityNotes = data.qualityNotes || '';
    this.defectsFound = data.defectsFound || 0;
    this.qualityApproved = data.qualityApproved || false;
    
    // Status history
    this.statusHistory = data.statusHistory || [{
      status: this.status,
      timestamp: this.createdAt,
      notes: 'Orden de producción creada'
    }];
    
    // Notes
    this.notes = data.notes || '';
  }

  generateProductionNumber() {
    const date = new Date();
    const year = date.getFullYear().toString().slice(-2);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    return `PROD-${year}${month}-${random}`;
  }

  updateStatus(newStatus, notes = '') {
    if (!Object.values(ProductionStatus).includes(newStatus)) {
      throw new Error(`Invalid status: ${newStatus}`);
    }
    
    this.status = newStatus;
    this.updatedAt = new Date().toISOString();
    
    // Update actual dates based on status
    if (newStatus === ProductionStatus.IN_PROGRESS && !this.actualStartDate) {
      this.actualStartDate = this.updatedAt;
    }
    if (newStatus === ProductionStatus.COMPLETED || newStatus === ProductionStatus.CANCELLED) {
      this.actualEndDate = this.updatedAt;
    }
    
    this.statusHistory.push({
      status: newStatus,
      timestamp: this.updatedAt,
      notes: notes || `Estado actualizado a: ${newStatus}`
    });
    
    return this;
  }

  addProductionItem(item) {
    this.productionItems.push({
      id: uuidv4(),
      productId: item.productId,
      productName: item.productName,
      quantity: item.quantity,
      completedQuantity: 0,
      specifications: item.specifications || {}
    });
    this.calculateEstimatedTime();
    return this;
  }

  addMaterial(material) {
    this.materialsRequired.push({
      id: uuidv4(),
      materialId: material.materialId,
      materialName: material.materialName,
      quantityRequired: material.quantityRequired,
      quantityUsed: 0,
      unitCost: material.unitCost || 0,
      status: 'pending'
    });
    this.calculateMaterialsCost();
    return this;
  }

  calculateEstimatedTime() {
    this.estimatedHours = this.productionItems.reduce((sum, item) => {
      return sum + (item.productionTimeHours || 1) * item.quantity;
    }, 0);
    return this.estimatedHours;
  }

  calculateMaterialsCost() {
    this.materialsCost = this.materialsRequired.reduce((sum, material) => {
      return sum + (material.unitCost * material.quantityRequired);
    }, 0);
    return this.materialsCost;
  }

  updateQualityCheck(approved, notes = '', defectsFound = 0) {
    this.qualityApproved = approved;
    this.qualityNotes = notes;
    this.defectsFound = defectsFound;
    this.updatedAt = new Date().toISOString();
    return this;
  }

  toJSON() {
    return {
      id: this.id,
      productionNumber: this.productionNumber,
      orderId: this.orderId,
      orderNumber: this.orderNumber,
      status: this.status,
      priority: this.priority,
      productionItems: this.productionItems,
      materialsRequired: this.materialsRequired,
      materialsCost: this.materialsCost,
      assignedMachines: this.assignedMachines,
      assignedWorkers: this.assignedWorkers,
      estimatedHours: this.estimatedHours,
      actualHours: this.actualHours,
      scheduledStartDate: this.scheduledStartDate,
      scheduledEndDate: this.scheduledEndDate,
      actualStartDate: this.actualStartDate,
      actualEndDate: this.actualEndDate,
      createdAt: this.createdAt,
      updatedAt: this.updatedAt,
      qualityNotes: this.qualityNotes,
      defectsFound: this.defectsFound,
      qualityApproved: this.qualityApproved,
      statusHistory: this.statusHistory,
      notes: this.notes
    };
  }
}

module.exports = { ProductionOrder, ProductionStatus, ProductionPriority };
