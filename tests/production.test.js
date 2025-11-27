/**
 * Tests for Production Order Model
 */

const { test, describe } = require('node:test');
const assert = require('node:assert');
const { ProductionOrder, ProductionStatus, ProductionPriority } = require('../src/models/ProductionOrder');

describe('ProductionOrder Model', () => {
  test('should create a production order with default values', () => {
    const po = new ProductionOrder();
    
    assert.ok(po.id);
    assert.ok(po.productionNumber);
    assert.strictEqual(po.status, ProductionStatus.SCHEDULED);
    assert.strictEqual(po.priority, ProductionPriority.NORMAL);
  });

  test('should create a production order with provided data', () => {
    const poData = {
      orderId: 'order-123',
      priority: ProductionPriority.URGENT,
      estimatedHours: 8
    };
    
    const po = new ProductionOrder(poData);
    
    assert.strictEqual(po.orderId, 'order-123');
    assert.strictEqual(po.priority, ProductionPriority.URGENT);
    assert.strictEqual(po.estimatedHours, 8);
  });

  test('should update status correctly', () => {
    const po = new ProductionOrder();
    
    po.updateStatus(ProductionStatus.IN_PROGRESS, 'Started production');
    
    assert.strictEqual(po.status, ProductionStatus.IN_PROGRESS);
    assert.ok(po.actualStartDate);
    assert.strictEqual(po.statusHistory.length, 2);
  });

  test('should set actual end date when completed', () => {
    const po = new ProductionOrder();
    
    po.updateStatus(ProductionStatus.COMPLETED);
    
    assert.ok(po.actualEndDate);
  });

  test('should throw error for invalid status', () => {
    const po = new ProductionOrder();
    
    assert.throws(() => {
      po.updateStatus('invalid_status');
    }, /Invalid status/);
  });

  test('should add production items', () => {
    const po = new ProductionOrder();
    
    po.addProductionItem({
      productId: 'prod-1',
      productName: 'Test Product',
      quantity: 100
    });
    
    assert.strictEqual(po.productionItems.length, 1);
    assert.strictEqual(po.productionItems[0].quantity, 100);
  });

  test('should add materials and calculate cost', () => {
    const po = new ProductionOrder();
    
    po.addMaterial({
      materialId: 'mat-1',
      materialName: 'Paper',
      quantityRequired: 100,
      unitCost: 0.50
    });
    
    assert.strictEqual(po.materialsRequired.length, 1);
    assert.strictEqual(po.materialsCost, 50);
  });

  test('should update quality check', () => {
    const po = new ProductionOrder();
    
    po.updateQualityCheck(true, 'All items passed', 0);
    
    assert.strictEqual(po.qualityApproved, true);
    assert.strictEqual(po.qualityNotes, 'All items passed');
    assert.strictEqual(po.defectsFound, 0);
  });

  test('should generate production number in correct format', () => {
    const po = new ProductionOrder();
    
    assert.ok(po.productionNumber.startsWith('PROD-'));
    assert.ok(po.productionNumber.match(/^PROD-\d{4}-\d{4}$/));
  });
});
