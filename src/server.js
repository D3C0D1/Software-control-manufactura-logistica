/**
 * Main Server Entry Point
 * Software de Gestión de Ciclo de Pedidos
 * 
 * This application unifies:
 * - Sales (Ventas)
 * - Production/MRP (Producción)
 * - Logistics (Logística)
 * - Customer Order Tracking (Rastreo de Estados)
 */

const express = require('express');
const cors = require('cors');
const path = require('path');

// Import routes
const {
  ordersRoutes,
  productsRoutes,
  productionRoutes,
  logisticsRoutes,
  campaignsRoutes,
  customersRoutes,
  dashboardRoutes,
  trackingRoutes
} = require('./routes');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from public directory
app.use(express.static(path.join(__dirname, '..', 'public')));

// API Routes
app.use('/api/orders', ordersRoutes);        // Sales - Order management
app.use('/api/products', productsRoutes);    // Product catalog
app.use('/api/production', productionRoutes); // MRP - Production planning
app.use('/api/logistics', logisticsRoutes);  // Logistics - Shipment management
app.use('/api/campaigns', campaignsRoutes);  // Marketing campaigns & events
app.use('/api/customers', customersRoutes);  // Customer management
app.use('/api/dashboard', dashboardRoutes);  // Dashboard & statistics
app.use('/api/tracking', trackingRoutes);    // Public order tracking

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'Software de Gestión de Ciclo de Pedidos',
    version: '1.0.0',
    timestamp: new Date().toISOString(),
    modules: {
      sales: 'active',
      production: 'active',
      logistics: 'active',
      tracking: 'active',
      campaigns: 'active'
    }
  });
});

// API documentation endpoint
app.get('/api', (req, res) => {
  res.json({
    service: 'Software de Gestión de Ciclo de Pedidos',
    description: 'Sistema integral para empresas de fabricación que unifica ventas, producción (MRP) y logística',
    version: '1.0.0',
    endpoints: {
      orders: {
        base: '/api/orders',
        methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        description: 'Gestión de pedidos (Ventas)'
      },
      products: {
        base: '/api/products',
        methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        description: 'Catálogo de productos'
      },
      production: {
        base: '/api/production',
        methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        description: 'Planificación de producción (MRP)'
      },
      logistics: {
        base: '/api/logistics',
        methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        description: 'Gestión de envíos y logística'
      },
      campaigns: {
        base: '/api/campaigns',
        methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        description: 'Campañas de comercialización y eventos gráficos'
      },
      customers: {
        base: '/api/customers',
        methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        description: 'Gestión de clientes'
      },
      dashboard: {
        base: '/api/dashboard',
        methods: ['GET'],
        description: 'Panel de control y estadísticas'
      },
      tracking: {
        base: '/api/tracking',
        methods: ['GET'],
        description: 'Sistema de rastreo de estados para clientes'
      }
    }
  });
});

// Serve the frontend for any non-API routes
app.get('/{*splat}', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'index.html'));
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({
    success: false,
    error: 'Internal Server Error',
    message: err.message
  });
});

// Start server
if (require.main === module) {
  app.listen(PORT, () => {
    console.log(`
╔════════════════════════════════════════════════════════════════════╗
║                                                                    ║
║   Software de Gestión de Ciclo de Pedidos                          ║
║   Order Cycle Management System                                    ║
║                                                                    ║
║   Server running on http://localhost:${PORT}                          ║
║                                                                    ║
║   Modules:                                                         ║
║   ├─ Ventas (Sales)           → /api/orders                        ║
║   ├─ Producción (MRP)         → /api/production                    ║
║   ├─ Logística                → /api/logistics                     ║
║   ├─ Campañas                 → /api/campaigns                     ║
║   ├─ Clientes                 → /api/customers                     ║
║   └─ Rastreo                  → /api/tracking                      ║
║                                                                    ║
║   Dashboard: http://localhost:${PORT}                                 ║
║   API Docs:  http://localhost:${PORT}/api                             ║
║                                                                    ║
╚════════════════════════════════════════════════════════════════════╝
    `);
  });
}

module.exports = app;
