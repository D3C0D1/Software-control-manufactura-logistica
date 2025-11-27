/**
 * Product Model - Modelo de Producto
 * Representa un producto en el sistema de manufactura
 */

const { v4: uuidv4 } = require('uuid');

// Categorías de productos
const ProductCategory = {
  PRINTED_MATERIAL: 'printed_material',   // Material impreso
  PROMOTIONAL: 'promotional',             // Artículos promocionales
  PACKAGING: 'packaging',                 // Empaque
  SIGNAGE: 'signage',                     // Señalización
  GRAPHIC_DESIGN: 'graphic_design',       // Diseño gráfico
  CUSTOM: 'custom'                        // Personalizado
};

class Product {
  constructor(data = {}) {
    this.id = data.id || uuidv4();
    this.sku = data.sku || this.generateSKU();
    this.name = data.name || '';
    this.description = data.description || '';
    this.category = data.category || ProductCategory.CUSTOM;
    
    // Pricing
    this.basePrice = data.basePrice || 0;
    this.currency = data.currency || 'USD';
    
    // Inventory
    this.stockQuantity = data.stockQuantity || 0;
    this.minStockLevel = data.minStockLevel || 10;
    this.maxStockLevel = data.maxStockLevel || 1000;
    
    // Production details
    this.productionTimeHours = data.productionTimeHours || 0;
    this.materialsRequired = data.materialsRequired || [];
    this.machinesRequired = data.machinesRequired || [];
    
    // Status
    this.isActive = data.isActive !== undefined ? data.isActive : true;
    
    // Timestamps
    this.createdAt = data.createdAt || new Date().toISOString();
    this.updatedAt = data.updatedAt || new Date().toISOString();
  }

  generateSKU() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const prefix = Array(3).fill(null).map(() => chars[Math.floor(Math.random() * chars.length)]).join('');
    const number = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    return `${prefix}-${number}`;
  }

  updateStock(quantity, operation = 'add') {
    if (operation === 'add') {
      this.stockQuantity += quantity;
    } else if (operation === 'subtract') {
      if (this.stockQuantity < quantity) {
        throw new Error('Insufficient stock');
      }
      this.stockQuantity -= quantity;
    } else if (operation === 'set') {
      this.stockQuantity = quantity;
    }
    this.updatedAt = new Date().toISOString();
    return this;
  }

  isLowStock() {
    return this.stockQuantity <= this.minStockLevel;
  }

  toJSON() {
    return {
      id: this.id,
      sku: this.sku,
      name: this.name,
      description: this.description,
      category: this.category,
      basePrice: this.basePrice,
      currency: this.currency,
      stockQuantity: this.stockQuantity,
      minStockLevel: this.minStockLevel,
      maxStockLevel: this.maxStockLevel,
      productionTimeHours: this.productionTimeHours,
      materialsRequired: this.materialsRequired,
      machinesRequired: this.machinesRequired,
      isActive: this.isActive,
      isLowStock: this.isLowStock(),
      createdAt: this.createdAt,
      updatedAt: this.updatedAt
    };
  }
}

module.exports = { Product, ProductCategory };
