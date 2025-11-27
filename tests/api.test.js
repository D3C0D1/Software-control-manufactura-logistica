/**
 * API Integration Tests
 */

const { test, describe, before, after } = require('node:test');
const assert = require('node:assert');
const http = require('http');

const app = require('../src/server');

let server;
let baseUrl;

// Helper function to make HTTP requests
function makeRequest(method, path, body = null) {
  return new Promise((resolve, reject) => {
    const url = new URL(path, baseUrl);
    const options = {
      method,
      hostname: url.hostname,
      port: url.port,
      path: url.pathname + url.search,
      headers: {
        'Content-Type': 'application/json'
      }
    };

    const req = http.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve({
            status: res.statusCode,
            data: JSON.parse(data)
          });
        } catch {
          resolve({
            status: res.statusCode,
            data: data
          });
        }
      });
    });

    req.on('error', reject);
    
    if (body) {
      req.write(JSON.stringify(body));
    }
    req.end();
  });
}

describe('API Integration Tests', () => {
  before(() => {
    return new Promise((resolve) => {
      server = app.listen(0, () => {
        const { port } = server.address();
        baseUrl = `http://localhost:${port}`;
        resolve();
      });
    });
  });

  after(() => {
    return new Promise((resolve) => {
      server.close(resolve);
    });
  });

  describe('Health Check', () => {
    test('GET /api/health should return healthy status', async () => {
      const response = await makeRequest('GET', '/api/health');
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.status, 'healthy');
      assert.strictEqual(response.data.service, 'Software de Gestión de Ciclo de Pedidos');
    });
  });

  describe('Orders API', () => {
    test('GET /api/orders should return list of orders', async () => {
      const response = await makeRequest('GET', '/api/orders');
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.ok(Array.isArray(response.data.data));
    });

    test('POST /api/orders should create a new order', async () => {
      const newOrder = {
        customerName: 'Test Customer',
        customerEmail: 'test@test.com',
        type: 'standard'
      };
      
      const response = await makeRequest('POST', '/api/orders', newOrder);
      
      assert.strictEqual(response.status, 201);
      assert.strictEqual(response.data.success, true);
      assert.ok(response.data.data.id);
      assert.strictEqual(response.data.data.customerName, 'Test Customer');
    });

    test('GET /api/orders/:id should return specific order', async () => {
      // First create an order
      const createResponse = await makeRequest('POST', '/api/orders', {
        customerName: 'Get Test Customer'
      });
      const orderId = createResponse.data.data.id;
      
      const response = await makeRequest('GET', `/api/orders/${orderId}`);
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.strictEqual(response.data.data.id, orderId);
    });

    test('GET /api/orders/:id should return 404 for non-existent order', async () => {
      const response = await makeRequest('GET', '/api/orders/non-existent-id');
      
      assert.strictEqual(response.status, 404);
      assert.strictEqual(response.data.success, false);
    });
  });

  describe('Products API', () => {
    test('GET /api/products should return list of products', async () => {
      const response = await makeRequest('GET', '/api/products');
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.ok(Array.isArray(response.data.data));
    });

    test('POST /api/products should create a new product', async () => {
      const newProduct = {
        name: 'Test Product',
        description: 'A test product',
        category: 'printed_material',
        basePrice: 10.00,
        stockQuantity: 100
      };
      
      const response = await makeRequest('POST', '/api/products', newProduct);
      
      assert.strictEqual(response.status, 201);
      assert.strictEqual(response.data.success, true);
      assert.strictEqual(response.data.data.name, 'Test Product');
    });
  });

  describe('Production API', () => {
    test('GET /api/production should return list of production orders', async () => {
      const response = await makeRequest('GET', '/api/production');
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.ok(Array.isArray(response.data.data));
    });

    test('POST /api/production should create a new production order', async () => {
      const newProduction = {
        priority: 'high',
        estimatedHours: 8
      };
      
      const response = await makeRequest('POST', '/api/production', newProduction);
      
      assert.strictEqual(response.status, 201);
      assert.strictEqual(response.data.success, true);
      assert.strictEqual(response.data.data.priority, 'high');
    });
  });

  describe('Logistics API', () => {
    test('GET /api/logistics should return list of shipments', async () => {
      const response = await makeRequest('GET', '/api/logistics');
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.ok(Array.isArray(response.data.data));
    });

    test('POST /api/logistics should create a new shipment', async () => {
      const newShipment = {
        carrier: 'fedex',
        type: 'express',
        destinationAddress: {
          city: 'New York',
          address: '123 Main St'
        }
      };
      
      const response = await makeRequest('POST', '/api/logistics', newShipment);
      
      assert.strictEqual(response.status, 201);
      assert.strictEqual(response.data.success, true);
      assert.strictEqual(response.data.data.carrier, 'fedex');
    });
  });

  describe('Campaigns API', () => {
    test('GET /api/campaigns should return list of campaigns', async () => {
      const response = await makeRequest('GET', '/api/campaigns');
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.ok(Array.isArray(response.data.data));
    });

    test('POST /api/campaigns should create a new campaign', async () => {
      const newCampaign = {
        name: 'Test Campaign',
        type: 'marketing',
        budget: 5000
      };
      
      const response = await makeRequest('POST', '/api/campaigns', newCampaign);
      
      assert.strictEqual(response.status, 201);
      assert.strictEqual(response.data.success, true);
      assert.strictEqual(response.data.data.name, 'Test Campaign');
    });
  });

  describe('Customers API', () => {
    test('GET /api/customers should return list of customers', async () => {
      const response = await makeRequest('GET', '/api/customers');
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.ok(Array.isArray(response.data.data));
    });

    test('POST /api/customers should create a new customer', async () => {
      const newCustomer = {
        name: 'New Test Customer',
        email: 'newtest@test.com',
        type: 'individual'
      };
      
      const response = await makeRequest('POST', '/api/customers', newCustomer);
      
      assert.strictEqual(response.status, 201);
      assert.strictEqual(response.data.success, true);
      assert.strictEqual(response.data.data.name, 'New Test Customer');
    });
  });

  describe('Dashboard API', () => {
    test('GET /api/dashboard should return dashboard stats', async () => {
      const response = await makeRequest('GET', '/api/dashboard');
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.ok(response.data.data.summary);
      assert.ok(response.data.data.ordersByStatus);
    });
  });

  describe('Tracking API', () => {
    test('GET /api/tracking/order/:orderNumber should return tracking info', async () => {
      // First create an order
      const createResponse = await makeRequest('POST', '/api/orders', {
        customerName: 'Tracking Test Customer'
      });
      const orderNumber = createResponse.data.data.orderNumber;
      
      const response = await makeRequest('GET', `/api/tracking/order/${orderNumber}`);
      
      assert.strictEqual(response.status, 200);
      assert.strictEqual(response.data.success, true);
      assert.ok(response.data.data.timeline);
      assert.ok(response.data.data.statusHistory);
    });

    test('GET /api/tracking/order/:orderNumber should return 404 for non-existent order', async () => {
      const response = await makeRequest('GET', '/api/tracking/order/NON-EXISTENT');
      
      assert.strictEqual(response.status, 404);
      assert.strictEqual(response.data.success, false);
    });
  });
});
