/**
 * Models Index - Exports all models
 */

const { Order, OrderStatus, OrderType } = require('./Order');
const { Product, ProductCategory } = require('./Product');
const { ProductionOrder, ProductionStatus, ProductionPriority } = require('./ProductionOrder');
const { Shipment, ShipmentStatus, ShipmentType, Carrier } = require('./Shipment');
const { Campaign, CampaignType, CampaignStatus } = require('./Campaign');
const { Customer, CustomerType } = require('./Customer');

module.exports = {
  Order,
  OrderStatus,
  OrderType,
  Product,
  ProductCategory,
  ProductionOrder,
  ProductionStatus,
  ProductionPriority,
  Shipment,
  ShipmentStatus,
  ShipmentType,
  Carrier,
  Campaign,
  CampaignType,
  CampaignStatus,
  Customer,
  CustomerType
};
