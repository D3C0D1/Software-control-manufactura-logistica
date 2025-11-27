/**
 * Data Store Service - In-memory data storage
 * For production, this would be replaced with a database connection
 */

const { 
  Order, 
  Product, 
  ProductionOrder, 
  Shipment, 
  Campaign, 
  Customer 
} = require('../models');

class DataStore {
  constructor() {
    // Initialize data stores
    this.orders = new Map();
    this.products = new Map();
    this.productionOrders = new Map();
    this.shipments = new Map();
    this.campaigns = new Map();
    this.customers = new Map();
    
    // Initialize with sample data
    this.initializeSampleData();
  }

  initializeSampleData() {
    // Sample Products
    const products = [
      new Product({
        name: 'Folletos A4 - Color',
        description: 'Folletos impresos a color en papel couché',
        category: 'printed_material',
        basePrice: 0.15,
        stockQuantity: 5000,
        productionTimeHours: 2
      }),
      new Product({
        name: 'Banner Roll Up',
        description: 'Banner enrollable 85x200cm con estructura',
        category: 'signage',
        basePrice: 45.00,
        stockQuantity: 50,
        productionTimeHours: 4
      }),
      new Product({
        name: 'Tarjetas de Presentación',
        description: 'Tarjetas 9x5cm en cartulina 300g',
        category: 'printed_material',
        basePrice: 0.05,
        stockQuantity: 10000,
        productionTimeHours: 1
      }),
      new Product({
        name: 'Bolsas Promocionales',
        description: 'Bolsas ecológicas con logo personalizado',
        category: 'promotional',
        basePrice: 2.50,
        stockQuantity: 500,
        productionTimeHours: 3
      }),
      new Product({
        name: 'Cajas de Empaque Personalizado',
        description: 'Cajas de cartón corrugado con impresión',
        category: 'packaging',
        basePrice: 3.00,
        stockQuantity: 200,
        productionTimeHours: 2
      })
    ];
    
    products.forEach(p => this.products.set(p.id, p));

    // Sample Customers
    const customers = [
      new Customer({
        name: 'María García',
        companyName: 'Distribuidora García S.A.',
        type: 'business',
        email: 'maria@distribuidoragarcia.com',
        phone: '+1 555-0101',
        billingAddress: {
          address: 'Calle Principal 123',
          city: 'Ciudad de México',
          state: 'CDMX',
          postalCode: '06600',
          country: 'México'
        }
      }),
      new Customer({
        name: 'Carlos Rodríguez',
        type: 'individual',
        email: 'carlos.rodriguez@email.com',
        phone: '+1 555-0102'
      }),
      new Customer({
        name: 'Ana Martínez',
        companyName: 'Eventos Creativos LLC',
        type: 'business',
        email: 'ana@eventoscreativos.com',
        phone: '+1 555-0103'
      })
    ];
    
    customers.forEach(c => this.customers.set(c.id, c));

    // Sample Campaign
    const campaign = new Campaign({
      name: 'Campaña Navidad 2024',
      description: 'Promoción especial de fin de año para productos gráficos',
      type: 'seasonal',
      status: 'active',
      startDate: '2024-11-15',
      endDate: '2024-12-31',
      budget: 10000,
      targetSales: 50000,
      targetOrders: 100
    });
    this.campaigns.set(campaign.id, campaign);

    // Sample Order
    const sampleOrder = new Order({
      customerId: customers[0].id,
      customerName: customers[0].name,
      customerEmail: customers[0].email,
      type: 'campaign',
      campaignId: campaign.id,
      items: [
        {
          productId: products[0].id,
          productName: products[0].name,
          quantity: 1000,
          unitPrice: products[0].basePrice,
          subtotal: 1000 * products[0].basePrice
        },
        {
          productId: products[1].id,
          productName: products[1].name,
          quantity: 5,
          unitPrice: products[1].basePrice,
          subtotal: 5 * products[1].basePrice
        }
      ],
      shippingAddress: {
        address: 'Calle Principal 123',
        city: 'Ciudad de México',
        state: 'CDMX',
        postalCode: '06600',
        country: 'México'
      }
    });
    sampleOrder.calculateTotal();
    sampleOrder.updateStatus('confirmed', 'Pedido confirmado por ventas');
    this.orders.set(sampleOrder.id, sampleOrder);
  }

  // Generic CRUD operations
  getAll(collection) {
    const store = this[collection];
    if (!store) return [];
    return Array.from(store.values()).map(item => 
      item.toJSON ? item.toJSON() : item
    );
  }

  getById(collection, id) {
    const store = this[collection];
    if (!store) return null;
    const item = store.get(id);
    return item ? (item.toJSON ? item.toJSON() : item) : null;
  }

  create(collection, data) {
    const store = this[collection];
    if (!store) throw new Error(`Collection ${collection} not found`);
    
    let item;
    switch (collection) {
      case 'orders':
        item = new Order(data);
        break;
      case 'products':
        item = new Product(data);
        break;
      case 'productionOrders':
        item = new ProductionOrder(data);
        break;
      case 'shipments':
        item = new Shipment(data);
        break;
      case 'campaigns':
        item = new Campaign(data);
        break;
      case 'customers':
        item = new Customer(data);
        break;
      default:
        throw new Error(`Unknown collection: ${collection}`);
    }
    
    store.set(item.id, item);
    return item.toJSON ? item.toJSON() : item;
  }

  update(collection, id, data) {
    const store = this[collection];
    if (!store) throw new Error(`Collection ${collection} not found`);
    
    const existingItem = store.get(id);
    if (!existingItem) return null;
    
    // Update properties
    Object.keys(data).forEach(key => {
      if (key !== 'id') {
        existingItem[key] = data[key];
      }
    });
    existingItem.updatedAt = new Date().toISOString();
    
    return existingItem.toJSON ? existingItem.toJSON() : existingItem;
  }

  delete(collection, id) {
    const store = this[collection];
    if (!store) return false;
    return store.delete(id);
  }

  // Specialized queries
  findByField(collection, field, value) {
    const items = this.getAll(collection);
    return items.filter(item => item[field] === value);
  }

  findOrdersByCustomer(customerId) {
    return this.findByField('orders', 'customerId', customerId);
  }

  findOrdersByCampaign(campaignId) {
    return this.findByField('orders', 'campaignId', campaignId);
  }

  findProductionOrdersByOrder(orderId) {
    return this.findByField('productionOrders', 'orderId', orderId);
  }

  findShipmentsByOrder(orderId) {
    return this.findByField('shipments', 'orderId', orderId);
  }

  // Order status updates with related entities
  updateOrderStatus(orderId, newStatus, notes = '') {
    const order = this.orders.get(orderId);
    if (!order) return null;
    
    order.updateStatus(newStatus, notes);
    return order.toJSON();
  }

  // Get order with full tracking info
  getOrderWithTracking(orderId) {
    const order = this.getById('orders', orderId);
    if (!order) return null;
    
    const production = this.findProductionOrdersByOrder(orderId);
    const shipments = this.findShipmentsByOrder(orderId);
    const customer = this.getById('customers', order.customerId);
    
    return {
      ...order,
      production: production.length > 0 ? production[0] : null,
      shipment: shipments.length > 0 ? shipments[0] : null,
      customer: customer
    };
  }

  // Dashboard statistics
  getDashboardStats() {
    const orders = this.getAll('orders');
    const products = this.getAll('products');
    const productionOrders = this.getAll('productionOrders');
    const shipments = this.getAll('shipments');
    const campaigns = this.getAll('campaigns');
    const customers = this.getAll('customers');

    const ordersByStatus = {};
    orders.forEach(order => {
      ordersByStatus[order.status] = (ordersByStatus[order.status] || 0) + 1;
    });

    const productionByStatus = {};
    productionOrders.forEach(po => {
      productionByStatus[po.status] = (productionByStatus[po.status] || 0) + 1;
    });

    const shipmentsByStatus = {};
    shipments.forEach(s => {
      shipmentsByStatus[s.status] = (shipmentsByStatus[s.status] || 0) + 1;
    });

    const totalRevenue = orders.reduce((sum, o) => sum + (o.totalAmount || 0), 0);
    const lowStockProducts = products.filter(p => p.isLowStock);
    const activeCampaigns = campaigns.filter(c => c.isCurrentlyActive);

    return {
      summary: {
        totalOrders: orders.length,
        totalProducts: products.length,
        totalProductionOrders: productionOrders.length,
        totalShipments: shipments.length,
        totalCampaigns: campaigns.length,
        totalCustomers: customers.length,
        totalRevenue: totalRevenue
      },
      ordersByStatus,
      productionByStatus,
      shipmentsByStatus,
      lowStockProducts,
      activeCampaigns
    };
  }
}

// Singleton instance
const dataStore = new DataStore();

module.exports = dataStore;
