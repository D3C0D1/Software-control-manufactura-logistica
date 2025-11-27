# Software de Control de Manufactura y Logística

## Software de Gestión de Ciclo de Pedidos

Sistema integral para empresas de fabricación con áreas para comercialización y campañas o eventos gráficos. Unifica ventas, producción (MRP) y logística con un sistema de rastreo de estados para el cliente.

### 🎯 Características Principales

- **📦 Gestión de Pedidos (Ventas)**: Creación, seguimiento y gestión completa del ciclo de vida de pedidos
- **🏭 Producción (MRP)**: Planificación de requerimientos de materiales y control de producción
- **🚚 Logística**: Gestión de envíos, transportistas y rastreo de paquetes
- **📢 Campañas y Eventos**: Gestión de campañas de marketing y eventos gráficos
- **👥 Clientes**: Base de datos de clientes con historial y estadísticas
- **🔍 Rastreo de Pedidos**: Sistema público de consulta de estado para clientes

### 🛠️ Tecnologías

- **Backend**: Node.js con Express
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Almacenamiento**: In-memory (configurable para bases de datos)

### 📁 Estructura del Proyecto

```
├── src/
│   ├── models/           # Modelos de datos
│   │   ├── Order.js      # Modelo de pedidos
│   │   ├── Product.js    # Modelo de productos
│   │   ├── ProductionOrder.js  # Modelo de órdenes de producción
│   │   ├── Shipment.js   # Modelo de envíos
│   │   ├── Campaign.js   # Modelo de campañas
│   │   └── Customer.js   # Modelo de clientes
│   ├── routes/           # Rutas de la API
│   │   ├── orders.js     # API de pedidos
│   │   ├── products.js   # API de productos
│   │   ├── production.js # API de producción
│   │   ├── logistics.js  # API de logística
│   │   ├── campaigns.js  # API de campañas
│   │   ├── customers.js  # API de clientes
│   │   ├── dashboard.js  # API del dashboard
│   │   └── tracking.js   # API de rastreo público
│   ├── services/         # Servicios
│   │   └── DataStore.js  # Almacenamiento de datos
│   └── server.js         # Servidor principal
├── public/               # Archivos estáticos (Frontend)
│   ├── index.html
│   ├── css/
│   │   └── styles.css
│   └── js/
│       └── app.js
├── tests/                # Pruebas
│   ├── order.test.js
│   ├── product.test.js
│   ├── production.test.js
│   ├── shipment.test.js
│   ├── campaign.test.js
│   ├── customer.test.js
│   └── api.test.js
└── package.json
```

### 🚀 Instalación y Ejecución

```bash
# Instalar dependencias
npm install

# Ejecutar servidor de desarrollo
npm start

# Ejecutar pruebas
npm test
```

El servidor estará disponible en `http://localhost:3000`

### 📡 API Endpoints

#### Pedidos (Ventas)
- `GET /api/orders` - Listar todos los pedidos
- `GET /api/orders/:id` - Obtener pedido por ID
- `GET /api/orders/:id/tracking` - Obtener pedido con información de rastreo
- `POST /api/orders` - Crear nuevo pedido
- `PUT /api/orders/:id` - Actualizar pedido
- `PATCH /api/orders/:id/status` - Actualizar estado del pedido
- `DELETE /api/orders/:id` - Eliminar pedido

#### Productos
- `GET /api/products` - Listar todos los productos
- `GET /api/products/:id` - Obtener producto por ID
- `POST /api/products` - Crear nuevo producto
- `PUT /api/products/:id` - Actualizar producto
- `PATCH /api/products/:id/stock` - Actualizar stock
- `DELETE /api/products/:id` - Eliminar producto

#### Producción (MRP)
- `GET /api/production` - Listar órdenes de producción
- `GET /api/production/:id` - Obtener orden por ID
- `POST /api/production` - Crear orden de producción
- `PUT /api/production/:id` - Actualizar orden
- `PATCH /api/production/:id/status` - Actualizar estado
- `PATCH /api/production/:id/quality` - Registrar control de calidad
- `DELETE /api/production/:id` - Eliminar orden

#### Logística
- `GET /api/logistics` - Listar envíos
- `GET /api/logistics/:id` - Obtener envío por ID
- `GET /api/logistics/track/:trackingNumber` - Rastrear envío
- `POST /api/logistics` - Crear envío
- `PUT /api/logistics/:id` - Actualizar envío
- `PATCH /api/logistics/:id/status` - Actualizar estado con ubicación
- `PATCH /api/logistics/:id/delivery` - Confirmar entrega
- `DELETE /api/logistics/:id` - Eliminar envío

#### Campañas
- `GET /api/campaigns` - Listar campañas
- `GET /api/campaigns/:id` - Obtener campaña por ID
- `GET /api/campaigns/:id/orders` - Obtener pedidos de la campaña
- `POST /api/campaigns` - Crear campaña
- `PUT /api/campaigns/:id` - Actualizar campaña
- `PATCH /api/campaigns/:id/status` - Actualizar estado
- `DELETE /api/campaigns/:id` - Eliminar campaña

#### Clientes
- `GET /api/customers` - Listar clientes
- `GET /api/customers/:id` - Obtener cliente por ID
- `GET /api/customers/:id/orders` - Obtener pedidos del cliente
- `POST /api/customers` - Crear cliente
- `PUT /api/customers/:id` - Actualizar cliente
- `PATCH /api/customers/:id/portal-access` - Habilitar acceso al portal
- `DELETE /api/customers/:id` - Eliminar cliente

#### Dashboard
- `GET /api/dashboard` - Estadísticas generales
- `GET /api/dashboard/orders-summary` - Resumen de pedidos
- `GET /api/dashboard/production-summary` - Resumen de producción
- `GET /api/dashboard/logistics-summary` - Resumen de logística
- `GET /api/dashboard/campaigns-summary` - Resumen de campañas

#### Rastreo Público
- `GET /api/tracking/order/:orderNumber` - Rastrear pedido por número
- `GET /api/tracking/shipment/:trackingNumber` - Rastrear envío
- `GET /api/tracking/customer/:customerId` - Pedidos del cliente

### 📋 Estados de Pedido

1. **Pendiente** (pending) - Pedido recibido
2. **Confirmado** (confirmed) - Pedido confirmado por ventas
3. **En Producción** (in_production) - Siendo fabricado
4. **Control de Calidad** (quality_check) - Verificación de calidad
5. **Listo para Envío** (ready_for_shipping) - Preparado para enviar
6. **En Tránsito** (in_transit) - En camino al cliente
7. **Entregado** (delivered) - Entregado al cliente
8. **Cancelado** (cancelled) - Pedido cancelado

### 📝 Licencia

MIT License - Ver [LICENSE](LICENSE) para más detalles.
