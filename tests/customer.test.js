/**
 * Tests for Customer Model
 */

const { test, describe } = require('node:test');
const assert = require('node:assert');
const { Customer, CustomerType } = require('../src/models/Customer');

describe('Customer Model', () => {
  test('should create a customer with default values', () => {
    const customer = new Customer();
    
    assert.ok(customer.id);
    assert.ok(customer.customerNumber);
    assert.strictEqual(customer.type, CustomerType.INDIVIDUAL);
    assert.strictEqual(customer.isActive, true);
    assert.strictEqual(customer.portalAccess, false);
  });

  test('should create a customer with provided data', () => {
    const customerData = {
      name: 'John Doe',
      companyName: 'ACME Corp',
      type: CustomerType.BUSINESS,
      email: 'john@acme.com'
    };
    
    const customer = new Customer(customerData);
    
    assert.strictEqual(customer.name, 'John Doe');
    assert.strictEqual(customer.companyName, 'ACME Corp');
    assert.strictEqual(customer.type, CustomerType.BUSINESS);
    assert.strictEqual(customer.email, 'john@acme.com');
  });

  test('should add shipping address', () => {
    const customer = new Customer();
    
    customer.addShippingAddress({
      label: 'Office',
      address: '123 Main St',
      city: 'New York',
      country: 'USA'
    });
    
    assert.strictEqual(customer.shippingAddresses.length, 1);
    assert.strictEqual(customer.shippingAddresses[0].isDefault, true);
  });

  test('should update statistics', () => {
    const customer = new Customer();
    
    customer.updateStatistics(100);
    customer.updateStatistics(150);
    
    assert.strictEqual(customer.totalOrders, 2);
    assert.strictEqual(customer.totalSpent, 250);
    assert.ok(customer.lastOrderDate);
  });

  test('should check credit availability', () => {
    const customer = new Customer({
      creditLimit: 1000,
      currentBalance: 700
    });
    
    assert.strictEqual(customer.checkCreditAvailable(200), true);
    assert.strictEqual(customer.checkCreditAvailable(400), false);
  });

  test('should enable portal access', () => {
    const customer = new Customer({ email: 'test@test.com' });
    
    customer.enablePortalAccess();
    
    assert.strictEqual(customer.portalAccess, true);
    assert.strictEqual(customer.portalUsername, 'test@test.com');
  });

  test('should get display name for individual', () => {
    const customer = new Customer({
      name: 'John Doe',
      type: CustomerType.INDIVIDUAL
    });
    
    assert.strictEqual(customer.getDisplayName(), 'John Doe');
  });

  test('should get display name for business', () => {
    const customer = new Customer({
      name: 'John Doe',
      companyName: 'ACME Corp',
      type: CustomerType.BUSINESS
    });
    
    assert.strictEqual(customer.getDisplayName(), 'ACME Corp');
  });

  test('should generate customer number in correct format', () => {
    const customer = new Customer();
    
    assert.ok(customer.customerNumber.startsWith('CUST-'));
    assert.ok(customer.customerNumber.match(/^CUST-\d{6}$/));
  });
});
