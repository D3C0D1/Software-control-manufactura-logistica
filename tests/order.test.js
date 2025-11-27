/**
 * Tests for Order Model
 */

const { test, describe } = require('node:test');
const assert = require('node:assert');
const { Order, OrderStatus, OrderType } = require('../src/models/Order');

describe('Order Model', () => {
  test('should create an order with default values', () => {
    const order = new Order();
    
    assert.ok(order.id);
    assert.ok(order.orderNumber);
    assert.strictEqual(order.status, OrderStatus.PENDING);
    assert.strictEqual(order.type, OrderType.STANDARD);
    assert.strictEqual(order.totalAmount, 0);
    assert.ok(Array.isArray(order.items));
    assert.ok(Array.isArray(order.statusHistory));
  });

  test('should create an order with provided data', () => {
    const orderData = {
      customerName: 'Test Customer',
      customerEmail: 'test@example.com',
      type: OrderType.CAMPAIGN
    };
    
    const order = new Order(orderData);
    
    assert.strictEqual(order.customerName, 'Test Customer');
    assert.strictEqual(order.customerEmail, 'test@example.com');
    assert.strictEqual(order.type, OrderType.CAMPAIGN);
  });

  test('should update order status', () => {
    const order = new Order();
    
    order.updateStatus(OrderStatus.CONFIRMED, 'Order confirmed');
    
    assert.strictEqual(order.status, OrderStatus.CONFIRMED);
    assert.strictEqual(order.statusHistory.length, 2);
    assert.strictEqual(order.statusHistory[1].status, OrderStatus.CONFIRMED);
  });

  test('should throw error for invalid status', () => {
    const order = new Order();
    
    assert.throws(() => {
      order.updateStatus('invalid_status');
    }, /Invalid status/);
  });

  test('should add items and calculate total', () => {
    const order = new Order();
    
    order.addItem({
      productId: '123',
      productName: 'Test Product',
      quantity: 10,
      unitPrice: 5.00
    });
    
    assert.strictEqual(order.items.length, 1);
    assert.strictEqual(order.totalAmount, 50);
  });

  test('should remove items and recalculate total', () => {
    const order = new Order();
    
    order.addItem({
      productId: '123',
      productName: 'Product 1',
      quantity: 10,
      unitPrice: 5.00
    });
    
    order.addItem({
      productId: '456',
      productName: 'Product 2',
      quantity: 5,
      unitPrice: 10.00
    });
    
    const itemToRemove = order.items[0].id;
    order.removeItem(itemToRemove);
    
    assert.strictEqual(order.items.length, 1);
    assert.strictEqual(order.totalAmount, 50);
  });

  test('should generate order number in correct format', () => {
    const order = new Order();
    
    assert.ok(order.orderNumber.startsWith('ORD-'));
    assert.ok(order.orderNumber.match(/^ORD-\d{4}-\d{4}$/));
  });

  test('should serialize to JSON correctly', () => {
    const order = new Order({
      customerName: 'Test',
      customerEmail: 'test@test.com'
    });
    
    const json = order.toJSON();
    
    assert.strictEqual(json.customerName, 'Test');
    assert.strictEqual(json.customerEmail, 'test@test.com');
    assert.ok(json.id);
    assert.ok(json.orderNumber);
  });
});
