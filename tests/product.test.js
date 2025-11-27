/**
 * Tests for Product Model
 */

const { test, describe } = require('node:test');
const assert = require('node:assert');
const { Product, ProductCategory } = require('../src/models/Product');

describe('Product Model', () => {
  test('should create a product with default values', () => {
    const product = new Product();
    
    assert.ok(product.id);
    assert.ok(product.sku);
    assert.strictEqual(product.stockQuantity, 0);
    assert.strictEqual(product.isActive, true);
  });

  test('should create a product with provided data', () => {
    const productData = {
      name: 'Test Product',
      description: 'A test product',
      category: ProductCategory.PRINTED_MATERIAL,
      basePrice: 10.00,
      stockQuantity: 100
    };
    
    const product = new Product(productData);
    
    assert.strictEqual(product.name, 'Test Product');
    assert.strictEqual(product.category, ProductCategory.PRINTED_MATERIAL);
    assert.strictEqual(product.basePrice, 10.00);
    assert.strictEqual(product.stockQuantity, 100);
  });

  test('should update stock with add operation', () => {
    const product = new Product({ stockQuantity: 100 });
    
    product.updateStock(50, 'add');
    
    assert.strictEqual(product.stockQuantity, 150);
  });

  test('should update stock with subtract operation', () => {
    const product = new Product({ stockQuantity: 100 });
    
    product.updateStock(30, 'subtract');
    
    assert.strictEqual(product.stockQuantity, 70);
  });

  test('should throw error for insufficient stock', () => {
    const product = new Product({ stockQuantity: 10 });
    
    assert.throws(() => {
      product.updateStock(20, 'subtract');
    }, /Insufficient stock/);
  });

  test('should update stock with set operation', () => {
    const product = new Product({ stockQuantity: 100 });
    
    product.updateStock(500, 'set');
    
    assert.strictEqual(product.stockQuantity, 500);
  });

  test('should detect low stock', () => {
    const product = new Product({
      stockQuantity: 5,
      minStockLevel: 10
    });
    
    assert.strictEqual(product.isLowStock(), true);
  });

  test('should not detect low stock when sufficient', () => {
    const product = new Product({
      stockQuantity: 100,
      minStockLevel: 10
    });
    
    assert.strictEqual(product.isLowStock(), false);
  });

  test('should generate SKU in correct format', () => {
    const product = new Product();
    
    assert.ok(product.sku.match(/^[A-Z]{3}-\d{4}$/));
  });
});
