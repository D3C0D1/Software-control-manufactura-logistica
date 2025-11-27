/**
 * Routes Index - Exports all routes
 */

const ordersRoutes = require('./orders');
const productsRoutes = require('./products');
const productionRoutes = require('./production');
const logisticsRoutes = require('./logistics');
const campaignsRoutes = require('./campaigns');
const customersRoutes = require('./customers');
const dashboardRoutes = require('./dashboard');
const trackingRoutes = require('./tracking');

module.exports = {
  ordersRoutes,
  productsRoutes,
  productionRoutes,
  logisticsRoutes,
  campaignsRoutes,
  customersRoutes,
  dashboardRoutes,
  trackingRoutes
};
