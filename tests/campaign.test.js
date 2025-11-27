/**
 * Tests for Campaign Model
 */

const { test, describe } = require('node:test');
const assert = require('node:assert');
const { Campaign, CampaignType, CampaignStatus } = require('../src/models/Campaign');

describe('Campaign Model', () => {
  test('should create a campaign with default values', () => {
    const campaign = new Campaign();
    
    assert.ok(campaign.id);
    assert.ok(campaign.campaignCode);
    assert.strictEqual(campaign.type, CampaignType.MARKETING);
    assert.strictEqual(campaign.status, CampaignStatus.DRAFT);
  });

  test('should create a campaign with provided data', () => {
    const campaignData = {
      name: 'Summer Sale',
      type: CampaignType.SEASONAL,
      budget: 5000
    };
    
    const campaign = new Campaign(campaignData);
    
    assert.strictEqual(campaign.name, 'Summer Sale');
    assert.strictEqual(campaign.type, CampaignType.SEASONAL);
    assert.strictEqual(campaign.budget, 5000);
  });

  test('should update status', () => {
    const campaign = new Campaign();
    
    campaign.updateStatus(CampaignStatus.ACTIVE);
    
    assert.strictEqual(campaign.status, CampaignStatus.ACTIVE);
  });

  test('should throw error for invalid status', () => {
    const campaign = new Campaign();
    
    assert.throws(() => {
      campaign.updateStatus('invalid_status');
    }, /Invalid status/);
  });

  test('should add products', () => {
    const campaign = new Campaign();
    
    campaign.addProduct({
      productId: 'prod-1',
      productName: 'Test Product',
      specialPrice: 9.99,
      discount: 10
    });
    
    assert.strictEqual(campaign.products.length, 1);
    assert.strictEqual(campaign.products[0].discount, 10);
  });

  test('should link orders', () => {
    const campaign = new Campaign();
    
    campaign.linkOrder('order-1');
    campaign.linkOrder('order-2');
    campaign.linkOrder('order-1'); // Duplicate should be ignored
    
    assert.strictEqual(campaign.orderIds.length, 2);
    assert.strictEqual(campaign.actualOrders, 2);
  });

  test('should update sales', () => {
    const campaign = new Campaign();
    
    campaign.updateSales(100);
    campaign.updateSales(50);
    
    assert.strictEqual(campaign.actualSales, 150);
  });

  test('should calculate progress', () => {
    const campaign = new Campaign({
      targetSales: 1000,
      targetOrders: 10,
      budget: 500
    });
    
    campaign.actualSales = 500;
    campaign.actualOrders = 5;
    campaign.actualCost = 250;
    
    const progress = campaign.getProgress();
    
    assert.strictEqual(progress.salesProgress, 50);
    assert.strictEqual(progress.ordersProgress, 50);
    assert.strictEqual(progress.budgetUsed, 50);
  });

  test('should check if active within date range', () => {
    const now = new Date();
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    const campaign = new Campaign({
      status: CampaignStatus.ACTIVE,
      startDate: yesterday.toISOString(),
      endDate: tomorrow.toISOString()
    });
    
    assert.strictEqual(campaign.isActive(), true);
  });

  test('should not be active if status is not active', () => {
    const campaign = new Campaign({
      status: CampaignStatus.DRAFT
    });
    
    assert.strictEqual(campaign.isActive(), false);
  });

  test('should generate campaign code in correct format', () => {
    const campaign = new Campaign();
    
    assert.ok(campaign.campaignCode.startsWith('CAMP-'));
    assert.ok(campaign.campaignCode.match(/^CAMP-\d{2}-\d{4}$/));
  });
});
