/**
 * Tests for Shipment Model
 */

const { test, describe } = require('node:test');
const assert = require('node:assert');
const { Shipment, ShipmentStatus, ShipmentType, Carrier } = require('../src/models/Shipment');

describe('Shipment Model', () => {
  test('should create a shipment with default values', () => {
    const shipment = new Shipment();
    
    assert.ok(shipment.id);
    assert.ok(shipment.shipmentNumber);
    assert.strictEqual(shipment.status, ShipmentStatus.PENDING);
    assert.strictEqual(shipment.type, ShipmentType.STANDARD);
    assert.strictEqual(shipment.carrier, Carrier.INTERNAL);
  });

  test('should create a shipment with provided data', () => {
    const shipmentData = {
      orderId: 'order-123',
      carrier: Carrier.FEDEX,
      type: ShipmentType.EXPRESS
    };
    
    const shipment = new Shipment(shipmentData);
    
    assert.strictEqual(shipment.orderId, 'order-123');
    assert.strictEqual(shipment.carrier, Carrier.FEDEX);
    assert.strictEqual(shipment.type, ShipmentType.EXPRESS);
  });

  test('should update status with location', () => {
    const shipment = new Shipment();
    
    shipment.updateStatus(ShipmentStatus.IN_TRANSIT, 'Distribution Center', 'Package in transit');
    
    assert.strictEqual(shipment.status, ShipmentStatus.IN_TRANSIT);
    assert.strictEqual(shipment.trackingHistory.length, 2);
    assert.strictEqual(shipment.trackingHistory[1].location, 'Distribution Center');
  });

  test('should set delivery date when delivered', () => {
    const shipment = new Shipment();
    
    shipment.updateStatus(ShipmentStatus.DELIVERED);
    
    assert.ok(shipment.actualDeliveryDate);
  });

  test('should throw error for invalid status', () => {
    const shipment = new Shipment();
    
    assert.throws(() => {
      shipment.updateStatus('invalid_status');
    }, /Invalid status/);
  });

  test('should add packages and calculate totals', () => {
    const shipment = new Shipment();
    
    shipment.addPackage({
      description: 'Box 1',
      weight: 5,
      dimensions: { length: 10, width: 10, height: 10 }
    });
    
    shipment.addPackage({
      description: 'Box 2',
      weight: 3,
      dimensions: { length: 5, width: 5, height: 5 }
    });
    
    assert.strictEqual(shipment.packages.length, 2);
    assert.strictEqual(shipment.totalWeight, 8);
    assert.strictEqual(shipment.totalVolume, 1125); // 1000 + 125
  });

  test('should confirm delivery', () => {
    const shipment = new Shipment();
    
    shipment.confirmDelivery('John Doe', null, 'Left at door');
    
    assert.strictEqual(shipment.status, ShipmentStatus.DELIVERED);
    assert.strictEqual(shipment.deliveryConfirmation.signedBy, 'John Doe');
    assert.strictEqual(shipment.deliveryConfirmation.notes, 'Left at door');
  });

  test('should get public tracking info', () => {
    const shipment = new Shipment({
      trackingNumber: 'TRACK123',
      carrier: Carrier.UPS
    });
    
    const publicInfo = shipment.getPublicTrackingInfo();
    
    assert.ok(publicInfo.shipmentNumber);
    assert.strictEqual(publicInfo.trackingNumber, 'TRACK123');
    assert.strictEqual(publicInfo.carrier, Carrier.UPS);
    assert.ok(Array.isArray(publicInfo.trackingHistory));
  });

  test('should generate shipment number in correct format', () => {
    const shipment = new Shipment();
    
    assert.ok(shipment.shipmentNumber.startsWith('SHIP-'));
    assert.ok(shipment.shipmentNumber.match(/^SHIP-\d{4}-\d{4}$/));
  });
});
