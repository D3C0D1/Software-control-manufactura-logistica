/**
 * Frontend Application - Order Cycle Management System
 * Software de Gestión de Ciclo de Pedidos
 */

// API Base URL
const API_BASE = '/api';

// Global state
let currentSection = 'dashboard';
let orders = [];
let products = [];
let productionOrders = [];
let shipments = [];
let campaigns = [];
let customers = [];

// ============================================
// Initialization
// ============================================

document.addEventListener('DOMContentLoaded', () => {
  initNavigation();
  updateDateTime();
  setInterval(updateDateTime, 1000);
  loadDashboard();
});

function initNavigation() {
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', () => {
      const section = item.dataset.section;
      navigateToSection(section);
    });
  });

  // Mobile menu toggle
  const menuToggle = document.getElementById('menuToggle');
  menuToggle.addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('open');
  });
}

function navigateToSection(sectionName) {
  // Update active nav item
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.toggle('active', item.dataset.section === sectionName);
  });

  // Update active section
  document.querySelectorAll('.section').forEach(section => {
    section.classList.remove('active');
  });
  document.getElementById(`${sectionName}Section`).classList.add('active');

  // Update header title
  const titles = {
    dashboard: 'Dashboard',
    orders: 'Gestión de Pedidos (Ventas)',
    production: 'Producción (MRP)',
    logistics: 'Logística - Envíos',
    campaigns: 'Campañas y Eventos Gráficos',
    products: 'Catálogo de Productos',
    customers: 'Gestión de Clientes',
    tracking: 'Rastreo de Pedidos'
  };
  document.getElementById('sectionTitle').textContent = titles[sectionName] || sectionName;

  // Load section data
  loadSectionData(sectionName);
  currentSection = sectionName;

  // Close mobile menu
  document.querySelector('.sidebar').classList.remove('open');
}

function loadSectionData(section) {
  switch (section) {
    case 'dashboard':
      loadDashboard();
      break;
    case 'orders':
      loadOrders();
      break;
    case 'production':
      loadProduction();
      break;
    case 'logistics':
      loadShipments();
      break;
    case 'campaigns':
      loadCampaigns();
      break;
    case 'products':
      loadProducts();
      break;
    case 'customers':
      loadCustomers();
      break;
  }
}

function updateDateTime() {
  const now = new Date();
  const options = { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  };
  document.getElementById('currentDateTime').textContent = now.toLocaleDateString('es-ES', options);
}

// ============================================
// API Functions
// ============================================

async function apiRequest(endpoint, options = {}) {
  try {
    const response = await fetch(`${API_BASE}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      }
    });
    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.error || 'Error en la solicitud');
    }
    return data;
  } catch (error) {
    console.error('API Error:', error);
    showNotification(error.message, 'error');
    throw error;
  }
}

// ============================================
// Dashboard
// ============================================

async function loadDashboard() {
  try {
    const response = await apiRequest('/dashboard');
    const stats = response.data;

    // Update stats cards
    document.getElementById('totalOrders').textContent = stats.summary.totalOrders;
    document.getElementById('totalProduction').textContent = stats.summary.totalProductionOrders;
    document.getElementById('totalShipments').textContent = stats.summary.totalShipments;
    document.getElementById('totalRevenue').textContent = `$${stats.summary.totalRevenue.toFixed(2)}`;

    // Orders by status
    renderStatusList('ordersByStatus', stats.ordersByStatus, 'pedido(s)');
    
    // Production by status
    renderStatusList('productionByStatus', stats.productionByStatus, 'orden(es)');

    // Active campaigns
    renderCampaignsList(stats.activeCampaigns);

    // Low stock products
    renderLowStockProducts(stats.lowStockProducts);

  } catch (error) {
    console.error('Error loading dashboard:', error);
  }
}

function renderStatusList(containerId, statusData, label) {
  const container = document.getElementById(containerId);
  if (Object.keys(statusData).length === 0) {
    container.innerHTML = '<p class="empty-state">No hay datos disponibles</p>';
    return;
  }
  
  container.innerHTML = Object.entries(statusData).map(([status, count]) => `
    <div class="status-item">
      <span class="status-badge status-${status}">${translateStatus(status)}</span>
      <span>${count} ${label}</span>
    </div>
  `).join('');
}

function renderCampaignsList(campaignsList) {
  const container = document.getElementById('activeCampaigns');
  if (!campaignsList || campaignsList.length === 0) {
    container.innerHTML = '<p class="empty-state">No hay campañas activas</p>';
    return;
  }
  
  container.innerHTML = campaignsList.map(campaign => `
    <div class="status-item">
      <span>📢 ${campaign.name}</span>
      <span class="status-badge status-active">Activa</span>
    </div>
  `).join('');
}

function renderLowStockProducts(productsList) {
  const container = document.getElementById('lowStockProducts');
  if (!productsList || productsList.length === 0) {
    container.innerHTML = '<p class="empty-state">✅ Todos los productos tienen stock suficiente</p>';
    return;
  }
  
  container.innerHTML = productsList.map(product => `
    <div class="status-item">
      <span>⚠️ ${product.name}</span>
      <span class="product-stock low">${product.stockQuantity} unidades</span>
    </div>
  `).join('');
}

// ============================================
// Orders Management
// ============================================

async function loadOrders() {
  try {
    const response = await apiRequest('/orders');
    orders = response.data;
    renderOrdersTable(orders);
  } catch (error) {
    console.error('Error loading orders:', error);
  }
}

function renderOrdersTable(ordersList) {
  const tbody = document.getElementById('ordersTableBody');
  if (ordersList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No hay pedidos</td></tr>';
    return;
  }

  tbody.innerHTML = ordersList.map(order => `
    <tr>
      <td><strong>${order.orderNumber}</strong></td>
      <td>${order.customerName || 'N/A'}</td>
      <td><span class="status-badge status-${order.type}">${translateType(order.type)}</span></td>
      <td><span class="status-badge status-${order.status}">${translateStatus(order.status)}</span></td>
      <td>$${order.totalAmount.toFixed(2)}</td>
      <td>${formatDate(order.createdAt)}</td>
      <td>
        <button class="btn btn-icon btn-sm" onclick="viewOrder('${order.id}')" title="Ver detalles">👁️</button>
        <button class="btn btn-icon btn-sm" onclick="editOrderStatus('${order.id}')" title="Cambiar estado">✏️</button>
      </td>
    </tr>
  `).join('');
}

function filterOrders() {
  const status = document.getElementById('orderStatusFilter').value;
  const filtered = status ? orders.filter(o => o.status === status) : orders;
  renderOrdersTable(filtered);
}

async function viewOrder(orderId) {
  try {
    const response = await apiRequest(`/orders/${orderId}/tracking`);
    const order = response.data;
    
    const content = `
      <div class="order-details">
        <div class="detail-section">
          <h4>📋 Información del Pedido</h4>
          <div class="detail-row">
            <span class="detail-label">Número:</span>
            <span class="detail-value">${order.orderNumber}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Estado:</span>
            <span class="status-badge status-${order.status}">${translateStatus(order.status)}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Tipo:</span>
            <span class="detail-value">${translateType(order.type)}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Total:</span>
            <span class="detail-value">$${order.totalAmount.toFixed(2)}</span>
          </div>
        </div>
        
        <div class="detail-section">
          <h4>👤 Cliente</h4>
          <div class="detail-row">
            <span class="detail-label">Nombre:</span>
            <span class="detail-value">${order.customerName || 'N/A'}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Email:</span>
            <span class="detail-value">${order.customerEmail || 'N/A'}</span>
          </div>
        </div>
        
        <div class="detail-section">
          <h4>📦 Items</h4>
          <div class="items-list">
            ${order.items.map(item => `
              <div class="item-row">
                <span>${item.productName}</span>
                <span>${item.quantity} x $${item.unitPrice} = $${item.subtotal.toFixed(2)}</span>
              </div>
            `).join('')}
          </div>
        </div>
        
        <div class="detail-section">
          <h4>📜 Historial de Estados</h4>
          ${order.statusHistory.map(h => `
            <div class="detail-row">
              <span class="status-badge status-${h.status}">${translateStatus(h.status)}</span>
              <span>${formatDate(h.timestamp)}</span>
            </div>
          `).join('')}
        </div>
      </div>
    `;
    
    showModal(`Pedido ${order.orderNumber}`, content);
  } catch (error) {
    console.error('Error viewing order:', error);
  }
}

async function editOrderStatus(orderId) {
  const order = orders.find(o => o.id === orderId);
  if (!order) return;

  const statuses = [
    { value: 'pending', label: 'Pendiente' },
    { value: 'confirmed', label: 'Confirmado' },
    { value: 'in_production', label: 'En Producción' },
    { value: 'quality_check', label: 'Control de Calidad' },
    { value: 'ready_for_shipping', label: 'Listo para Envío' },
    { value: 'in_transit', label: 'En Tránsito' },
    { value: 'delivered', label: 'Entregado' },
    { value: 'cancelled', label: 'Cancelado' }
  ];

  const content = `
    <form id="updateStatusForm">
      <div class="form-group">
        <label>Estado Actual</label>
        <input type="text" value="${translateStatus(order.status)}" disabled />
      </div>
      <div class="form-group">
        <label>Nuevo Estado</label>
        <select id="newStatus" required>
          ${statuses.map(s => `<option value="${s.value}" ${s.value === order.status ? 'selected' : ''}>${s.label}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Notas</label>
        <textarea id="statusNotes" rows="3" placeholder="Agregar notas opcionales..."></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Actualizar Estado</button>
    </form>
  `;

  showModal(`Actualizar Estado - ${order.orderNumber}`, content);

  document.getElementById('updateStatusForm').onsubmit = async (e) => {
    e.preventDefault();
    const newStatus = document.getElementById('newStatus').value;
    const notes = document.getElementById('statusNotes').value;
    
    try {
      await apiRequest(`/orders/${orderId}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status: newStatus, notes })
      });
      closeModal();
      showNotification('Estado actualizado exitosamente', 'success');
      loadOrders();
    } catch (error) {
      console.error('Error updating status:', error);
    }
  };
}

function showNewOrderModal() {
  const content = `
    <form id="newOrderForm">
      <div class="form-row">
        <div class="form-group">
          <label>Nombre del Cliente *</label>
          <input type="text" id="orderCustomerName" required />
        </div>
        <div class="form-group">
          <label>Email del Cliente</label>
          <input type="email" id="orderCustomerEmail" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Teléfono</label>
          <input type="tel" id="orderCustomerPhone" />
        </div>
        <div class="form-group">
          <label>Tipo de Pedido</label>
          <select id="orderType">
            <option value="standard">Estándar</option>
            <option value="campaign">Campaña</option>
            <option value="event">Evento</option>
            <option value="wholesale">Mayorista</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Notas</label>
        <textarea id="orderNotes" rows="3" placeholder="Notas adicionales..."></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Pedido</button>
    </form>
  `;

  showModal('Nuevo Pedido', content);

  document.getElementById('newOrderForm').onsubmit = async (e) => {
    e.preventDefault();
    const orderData = {
      customerName: document.getElementById('orderCustomerName').value,
      customerEmail: document.getElementById('orderCustomerEmail').value,
      customerPhone: document.getElementById('orderCustomerPhone').value,
      type: document.getElementById('orderType').value,
      notes: document.getElementById('orderNotes').value
    };

    try {
      await apiRequest('/orders', {
        method: 'POST',
        body: JSON.stringify(orderData)
      });
      closeModal();
      showNotification('Pedido creado exitosamente', 'success');
      loadOrders();
    } catch (error) {
      console.error('Error creating order:', error);
    }
  };
}

// ============================================
// Production Management
// ============================================

async function loadProduction() {
  try {
    const response = await apiRequest('/production');
    productionOrders = response.data;
    renderProductionTable(productionOrders);
  } catch (error) {
    console.error('Error loading production:', error);
  }
}

function renderProductionTable(productionList) {
  const tbody = document.getElementById('productionTableBody');
  if (productionList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No hay órdenes de producción</td></tr>';
    return;
  }

  tbody.innerHTML = productionList.map(po => `
    <tr>
      <td><strong>${po.productionNumber}</strong></td>
      <td>${po.orderNumber || 'N/A'}</td>
      <td><span class="status-badge status-${po.status}">${translateStatus(po.status)}</span></td>
      <td><span class="status-badge priority-${po.priority}">${translatePriority(po.priority)}</span></td>
      <td>${po.estimatedHours}h</td>
      <td>${po.scheduledStartDate ? formatDate(po.scheduledStartDate) : 'No programado'}</td>
      <td>
        <button class="btn btn-icon btn-sm" onclick="viewProductionOrder('${po.id}')" title="Ver detalles">👁️</button>
        <button class="btn btn-icon btn-sm" onclick="editProductionStatus('${po.id}')" title="Cambiar estado">✏️</button>
      </td>
    </tr>
  `).join('');
}

function filterProduction() {
  const status = document.getElementById('productionStatusFilter').value;
  const priority = document.getElementById('productionPriorityFilter').value;
  let filtered = productionOrders;
  
  if (status) filtered = filtered.filter(po => po.status === status);
  if (priority) filtered = filtered.filter(po => po.priority === priority);
  
  renderProductionTable(filtered);
}

async function viewProductionOrder(id) {
  const po = productionOrders.find(p => p.id === id);
  if (!po) return;

  const content = `
    <div class="order-details">
      <div class="detail-section">
        <h4>🏭 Información de Producción</h4>
        <div class="detail-row">
          <span class="detail-label">Número:</span>
          <span class="detail-value">${po.productionNumber}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Estado:</span>
          <span class="status-badge status-${po.status}">${translateStatus(po.status)}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Prioridad:</span>
          <span class="status-badge priority-${po.priority}">${translatePriority(po.priority)}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Horas Estimadas:</span>
          <span class="detail-value">${po.estimatedHours}h</span>
        </div>
      </div>
      
      <div class="detail-section">
        <h4>📜 Historial de Estados</h4>
        ${po.statusHistory.map(h => `
          <div class="detail-row">
            <span class="status-badge status-${h.status}">${translateStatus(h.status)}</span>
            <span>${formatDate(h.timestamp)}</span>
          </div>
        `).join('')}
      </div>
    </div>
  `;

  showModal(`Producción ${po.productionNumber}`, content);
}

async function editProductionStatus(id) {
  const po = productionOrders.find(p => p.id === id);
  if (!po) return;

  const statuses = [
    { value: 'scheduled', label: 'Programado' },
    { value: 'in_queue', label: 'En Cola' },
    { value: 'in_progress', label: 'En Progreso' },
    { value: 'paused', label: 'Pausado' },
    { value: 'quality_check', label: 'Control de Calidad' },
    { value: 'completed', label: 'Completado' },
    { value: 'cancelled', label: 'Cancelado' }
  ];

  const content = `
    <form id="updateProductionStatusForm">
      <div class="form-group">
        <label>Estado Actual</label>
        <input type="text" value="${translateStatus(po.status)}" disabled />
      </div>
      <div class="form-group">
        <label>Nuevo Estado</label>
        <select id="newProductionStatus" required>
          ${statuses.map(s => `<option value="${s.value}" ${s.value === po.status ? 'selected' : ''}>${s.label}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Notas</label>
        <textarea id="productionStatusNotes" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Actualizar Estado</button>
    </form>
  `;

  showModal(`Actualizar Producción - ${po.productionNumber}`, content);

  document.getElementById('updateProductionStatusForm').onsubmit = async (e) => {
    e.preventDefault();
    const newStatus = document.getElementById('newProductionStatus').value;
    const notes = document.getElementById('productionStatusNotes').value;
    
    try {
      await apiRequest(`/production/${id}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status: newStatus, notes })
      });
      closeModal();
      showNotification('Estado de producción actualizado', 'success');
      loadProduction();
    } catch (error) {
      console.error('Error updating production status:', error);
    }
  };
}

function showNewProductionModal() {
  const content = `
    <form id="newProductionForm">
      <div class="form-group">
        <label>Número de Pedido (Opcional)</label>
        <input type="text" id="productionOrderNumber" placeholder="ORD-2411-0001" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Prioridad</label>
          <select id="productionPriority">
            <option value="low">Baja</option>
            <option value="normal" selected>Normal</option>
            <option value="high">Alta</option>
            <option value="urgent">Urgente</option>
          </select>
        </div>
        <div class="form-group">
          <label>Horas Estimadas</label>
          <input type="number" id="productionHours" value="8" min="1" />
        </div>
      </div>
      <div class="form-group">
        <label>Fecha de Inicio Programada</label>
        <input type="date" id="productionStartDate" />
      </div>
      <div class="form-group">
        <label>Notas</label>
        <textarea id="productionNotes" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Orden de Producción</button>
    </form>
  `;

  showModal('Nueva Orden de Producción', content);

  document.getElementById('newProductionForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = {
      orderNumber: document.getElementById('productionOrderNumber').value,
      priority: document.getElementById('productionPriority').value,
      estimatedHours: parseInt(document.getElementById('productionHours').value),
      scheduledStartDate: document.getElementById('productionStartDate').value || null,
      notes: document.getElementById('productionNotes').value
    };

    try {
      await apiRequest('/production', {
        method: 'POST',
        body: JSON.stringify(data)
      });
      closeModal();
      showNotification('Orden de producción creada', 'success');
      loadProduction();
    } catch (error) {
      console.error('Error creating production order:', error);
    }
  };
}

// ============================================
// Logistics Management
// ============================================

async function loadShipments() {
  try {
    const response = await apiRequest('/logistics');
    shipments = response.data;
    renderShipmentsTable(shipments);
  } catch (error) {
    console.error('Error loading shipments:', error);
  }
}

function renderShipmentsTable(shipmentsList) {
  const tbody = document.getElementById('logisticsTableBody');
  if (shipmentsList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No hay envíos</td></tr>';
    return;
  }

  tbody.innerHTML = shipmentsList.map(s => `
    <tr>
      <td><strong>${s.shipmentNumber}</strong></td>
      <td>${s.orderNumber || 'N/A'}</td>
      <td><span class="status-badge status-${s.status}">${translateShipmentStatus(s.status)}</span></td>
      <td>${translateCarrier(s.carrier)}</td>
      <td>${s.destinationAddress.city || 'N/A'}</td>
      <td>${s.estimatedDeliveryDate ? formatDate(s.estimatedDeliveryDate) : 'No definido'}</td>
      <td>
        <button class="btn btn-icon btn-sm" onclick="viewShipment('${s.id}')" title="Ver detalles">👁️</button>
        <button class="btn btn-icon btn-sm" onclick="editShipmentStatus('${s.id}')" title="Cambiar estado">✏️</button>
      </td>
    </tr>
  `).join('');
}

function filterShipments() {
  const status = document.getElementById('shipmentStatusFilter').value;
  const filtered = status ? shipments.filter(s => s.status === status) : shipments;
  renderShipmentsTable(filtered);
}

async function viewShipment(id) {
  const s = shipments.find(sh => sh.id === id);
  if (!s) return;

  const content = `
    <div class="order-details">
      <div class="detail-section">
        <h4>🚚 Información del Envío</h4>
        <div class="detail-row">
          <span class="detail-label">Número:</span>
          <span class="detail-value">${s.shipmentNumber}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Estado:</span>
          <span class="status-badge status-${s.status}">${translateShipmentStatus(s.status)}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Transportista:</span>
          <span class="detail-value">${translateCarrier(s.carrier)}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Número de Rastreo:</span>
          <span class="detail-value">${s.trackingNumber || 'N/A'}</span>
        </div>
      </div>
      
      <div class="detail-section">
        <h4>📍 Destino</h4>
        <div class="detail-row">
          <span class="detail-label">Ciudad:</span>
          <span class="detail-value">${s.destinationAddress.city || 'N/A'}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Dirección:</span>
          <span class="detail-value">${s.destinationAddress.address || 'N/A'}</span>
        </div>
      </div>
      
      <div class="detail-section">
        <h4>📜 Historial de Rastreo</h4>
        ${s.trackingHistory.map(h => `
          <div class="detail-row">
            <span class="status-badge status-${h.status}">${translateShipmentStatus(h.status)}</span>
            <span>${formatDate(h.timestamp)} ${h.location ? `- ${h.location}` : ''}</span>
          </div>
        `).join('')}
      </div>
    </div>
  `;

  showModal(`Envío ${s.shipmentNumber}`, content);
}

async function editShipmentStatus(id) {
  const s = shipments.find(sh => sh.id === id);
  if (!s) return;

  const statuses = [
    { value: 'pending', label: 'Pendiente' },
    { value: 'preparing', label: 'Preparando' },
    { value: 'ready', label: 'Listo' },
    { value: 'picked_up', label: 'Recogido' },
    { value: 'in_transit', label: 'En Tránsito' },
    { value: 'out_for_delivery', label: 'En Reparto' },
    { value: 'delivered', label: 'Entregado' },
    { value: 'failed_delivery', label: 'Entrega Fallida' }
  ];

  const content = `
    <form id="updateShipmentStatusForm">
      <div class="form-group">
        <label>Estado Actual</label>
        <input type="text" value="${translateShipmentStatus(s.status)}" disabled />
      </div>
      <div class="form-group">
        <label>Nuevo Estado</label>
        <select id="newShipmentStatus" required>
          ${statuses.map(st => `<option value="${st.value}" ${st.value === s.status ? 'selected' : ''}>${st.label}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Ubicación</label>
        <input type="text" id="shipmentLocation" placeholder="Ej: Centro de distribución" />
      </div>
      <div class="form-group">
        <label>Notas</label>
        <textarea id="shipmentStatusNotes" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Actualizar Estado</button>
    </form>
  `;

  showModal(`Actualizar Envío - ${s.shipmentNumber}`, content);

  document.getElementById('updateShipmentStatusForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = {
      status: document.getElementById('newShipmentStatus').value,
      location: document.getElementById('shipmentLocation').value,
      notes: document.getElementById('shipmentStatusNotes').value
    };

    try {
      await apiRequest(`/logistics/${id}/status`, {
        method: 'PATCH',
        body: JSON.stringify(data)
      });
      closeModal();
      showNotification('Estado del envío actualizado', 'success');
      loadShipments();
    } catch (error) {
      console.error('Error updating shipment status:', error);
    }
  };
}

function showNewShipmentModal() {
  const content = `
    <form id="newShipmentForm">
      <div class="form-group">
        <label>Número de Pedido (Opcional)</label>
        <input type="text" id="shipmentOrderNumber" placeholder="ORD-2411-0001" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Transportista</label>
          <select id="shipmentCarrier">
            <option value="internal">Transporte Propio</option>
            <option value="fedex">FedEx</option>
            <option value="ups">UPS</option>
            <option value="dhl">DHL</option>
            <option value="local">Mensajería Local</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tipo de Envío</label>
          <select id="shipmentType">
            <option value="standard">Estándar</option>
            <option value="express">Express</option>
            <option value="overnight">Nocturno</option>
            <option value="same_day">Mismo Día</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Ciudad de Destino</label>
        <input type="text" id="shipmentCity" required />
      </div>
      <div class="form-group">
        <label>Dirección de Destino</label>
        <input type="text" id="shipmentAddress" />
      </div>
      <div class="form-group">
        <label>Fecha Estimada de Entrega</label>
        <input type="date" id="shipmentDeliveryDate" />
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Envío</button>
    </form>
  `;

  showModal('Nuevo Envío', content);

  document.getElementById('newShipmentForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = {
      orderNumber: document.getElementById('shipmentOrderNumber').value,
      carrier: document.getElementById('shipmentCarrier').value,
      type: document.getElementById('shipmentType').value,
      destinationAddress: {
        city: document.getElementById('shipmentCity').value,
        address: document.getElementById('shipmentAddress').value
      },
      estimatedDeliveryDate: document.getElementById('shipmentDeliveryDate').value || null
    };

    try {
      await apiRequest('/logistics', {
        method: 'POST',
        body: JSON.stringify(data)
      });
      closeModal();
      showNotification('Envío creado exitosamente', 'success');
      loadShipments();
    } catch (error) {
      console.error('Error creating shipment:', error);
    }
  };
}

// ============================================
// Campaigns Management
// ============================================

async function loadCampaigns() {
  try {
    const response = await apiRequest('/campaigns');
    campaigns = response.data;
    renderCampaignsGrid(campaigns);
  } catch (error) {
    console.error('Error loading campaigns:', error);
  }
}

function renderCampaignsGrid(campaignsList) {
  const grid = document.getElementById('campaignsGrid');
  if (campaignsList.length === 0) {
    grid.innerHTML = '<div class="empty-state"><div class="icon">📢</div><p>No hay campañas</p></div>';
    return;
  }

  grid.innerHTML = campaignsList.map(c => `
    <div class="campaign-card">
      <div style="display: flex; justify-content: space-between; align-items: start;">
        <h4>${c.name}</h4>
        <span class="status-badge status-${c.status}">${translateCampaignStatus(c.status)}</span>
      </div>
      <p>${c.description || 'Sin descripción'}</p>
      <p><strong>Tipo:</strong> ${translateCampaignType(c.type)}</p>
      <div class="progress-bar">
        <div class="progress-fill" style="width: ${c.progress.salesProgress}%"></div>
      </div>
      <p style="font-size: 0.85rem;">Progreso de ventas: ${c.progress.salesProgress.toFixed(1)}%</p>
      <div class="campaign-meta">
        <span>📅 ${c.startDate ? formatDate(c.startDate) : 'No iniciada'}</span>
        <span>💰 $${c.budget.toLocaleString()}</span>
      </div>
    </div>
  `).join('');
}

function showNewCampaignModal() {
  const content = `
    <form id="newCampaignForm">
      <div class="form-group">
        <label>Nombre de la Campaña *</label>
        <input type="text" id="campaignName" required />
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <textarea id="campaignDescription" rows="3"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Tipo</label>
          <select id="campaignType">
            <option value="marketing">Marketing</option>
            <option value="graphic_event">Evento Gráfico</option>
            <option value="seasonal">Estacional</option>
            <option value="product_launch">Lanzamiento</option>
            <option value="promotional">Promocional</option>
          </select>
        </div>
        <div class="form-group">
          <label>Presupuesto</label>
          <input type="number" id="campaignBudget" value="0" min="0" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Fecha de Inicio</label>
          <input type="date" id="campaignStartDate" />
        </div>
        <div class="form-group">
          <label>Fecha de Fin</label>
          <input type="date" id="campaignEndDate" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Meta de Ventas</label>
          <input type="number" id="campaignTargetSales" value="0" min="0" />
        </div>
        <div class="form-group">
          <label>Meta de Pedidos</label>
          <input type="number" id="campaignTargetOrders" value="0" min="0" />
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Campaña</button>
    </form>
  `;

  showModal('Nueva Campaña', content);

  document.getElementById('newCampaignForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = {
      name: document.getElementById('campaignName').value,
      description: document.getElementById('campaignDescription').value,
      type: document.getElementById('campaignType').value,
      budget: parseFloat(document.getElementById('campaignBudget').value),
      startDate: document.getElementById('campaignStartDate').value || null,
      endDate: document.getElementById('campaignEndDate').value || null,
      targetSales: parseFloat(document.getElementById('campaignTargetSales').value),
      targetOrders: parseInt(document.getElementById('campaignTargetOrders').value)
    };

    try {
      await apiRequest('/campaigns', {
        method: 'POST',
        body: JSON.stringify(data)
      });
      closeModal();
      showNotification('Campaña creada exitosamente', 'success');
      loadCampaigns();
    } catch (error) {
      console.error('Error creating campaign:', error);
    }
  };
}

// ============================================
// Products Management
// ============================================

async function loadProducts() {
  try {
    const response = await apiRequest('/products');
    products = response.data;
    renderProductsGrid(products);
  } catch (error) {
    console.error('Error loading products:', error);
  }
}

function renderProductsGrid(productsList) {
  const grid = document.getElementById('productsGrid');
  if (productsList.length === 0) {
    grid.innerHTML = '<div class="empty-state"><div class="icon">📦</div><p>No hay productos</p></div>';
    return;
  }

  grid.innerHTML = productsList.map(p => `
    <div class="product-card">
      <h4>${p.name}</h4>
      <p>${p.description || 'Sin descripción'}</p>
      <p><span class="status-badge">${translateCategory(p.category)}</span></p>
      <div class="product-meta">
        <span class="product-price">$${p.basePrice.toFixed(2)}</span>
        <span class="product-stock ${p.isLowStock ? 'low' : ''}">
          📦 ${p.stockQuantity} ${p.isLowStock ? '⚠️' : ''}
        </span>
      </div>
    </div>
  `).join('');
}

function showNewProductModal() {
  const content = `
    <form id="newProductForm">
      <div class="form-group">
        <label>Nombre del Producto *</label>
        <input type="text" id="productName" required />
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <textarea id="productDescription" rows="3"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Categoría</label>
          <select id="productCategory">
            <option value="printed_material">Material Impreso</option>
            <option value="promotional">Artículos Promocionales</option>
            <option value="packaging">Empaque</option>
            <option value="signage">Señalización</option>
            <option value="graphic_design">Diseño Gráfico</option>
            <option value="custom">Personalizado</option>
          </select>
        </div>
        <div class="form-group">
          <label>Precio Base</label>
          <input type="number" id="productPrice" value="0" min="0" step="0.01" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Stock Inicial</label>
          <input type="number" id="productStock" value="0" min="0" />
        </div>
        <div class="form-group">
          <label>Stock Mínimo</label>
          <input type="number" id="productMinStock" value="10" min="0" />
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Producto</button>
    </form>
  `;

  showModal('Nuevo Producto', content);

  document.getElementById('newProductForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = {
      name: document.getElementById('productName').value,
      description: document.getElementById('productDescription').value,
      category: document.getElementById('productCategory').value,
      basePrice: parseFloat(document.getElementById('productPrice').value),
      stockQuantity: parseInt(document.getElementById('productStock').value),
      minStockLevel: parseInt(document.getElementById('productMinStock').value)
    };

    try {
      await apiRequest('/products', {
        method: 'POST',
        body: JSON.stringify(data)
      });
      closeModal();
      showNotification('Producto creado exitosamente', 'success');
      loadProducts();
    } catch (error) {
      console.error('Error creating product:', error);
    }
  };
}

// ============================================
// Customers Management
// ============================================

async function loadCustomers() {
  try {
    const response = await apiRequest('/customers');
    customers = response.data;
    renderCustomersTable(customers);
  } catch (error) {
    console.error('Error loading customers:', error);
  }
}

function renderCustomersTable(customersList) {
  const tbody = document.getElementById('customersTableBody');
  if (customersList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No hay clientes</td></tr>';
    return;
  }

  tbody.innerHTML = customersList.map(c => `
    <tr>
      <td><strong>${c.customerNumber}</strong></td>
      <td>${c.displayName}</td>
      <td><span class="status-badge">${translateCustomerType(c.type)}</span></td>
      <td>${c.email || 'N/A'}</td>
      <td>${c.phone || 'N/A'}</td>
      <td>${c.totalOrders}</td>
      <td>
        <button class="btn btn-icon btn-sm" onclick="viewCustomer('${c.id}')" title="Ver detalles">👁️</button>
        <button class="btn btn-icon btn-sm" onclick="viewCustomerOrders('${c.id}')" title="Ver pedidos">🛒</button>
      </td>
    </tr>
  `).join('');
}

async function viewCustomer(id) {
  const c = customers.find(cu => cu.id === id);
  if (!c) return;

  const content = `
    <div class="order-details">
      <div class="detail-section">
        <h4>👤 Información del Cliente</h4>
        <div class="detail-row">
          <span class="detail-label">Código:</span>
          <span class="detail-value">${c.customerNumber}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Nombre:</span>
          <span class="detail-value">${c.displayName}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Tipo:</span>
          <span class="status-badge">${translateCustomerType(c.type)}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Email:</span>
          <span class="detail-value">${c.email || 'N/A'}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Teléfono:</span>
          <span class="detail-value">${c.phone || 'N/A'}</span>
        </div>
      </div>
      
      <div class="detail-section">
        <h4>📊 Estadísticas</h4>
        <div class="detail-row">
          <span class="detail-label">Total Pedidos:</span>
          <span class="detail-value">${c.totalOrders}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Total Gastado:</span>
          <span class="detail-value">$${c.totalSpent.toFixed(2)}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Último Pedido:</span>
          <span class="detail-value">${c.lastOrderDate ? formatDate(c.lastOrderDate) : 'Sin pedidos'}</span>
        </div>
      </div>
    </div>
  `;

  showModal(`Cliente ${c.displayName}`, content);
}

async function viewCustomerOrders(customerId) {
  try {
    const response = await apiRequest(`/customers/${customerId}/orders`);
    const customerOrders = response.data;
    const customer = customers.find(c => c.id === customerId);

    if (customerOrders.length === 0) {
      showNotification('El cliente no tiene pedidos', 'info');
      return;
    }

    const content = `
      <div class="order-details">
        <div class="detail-section">
          <h4>📋 Pedidos de ${customer?.displayName || 'Cliente'}</h4>
          ${customerOrders.map(o => `
            <div class="item-row">
              <span><strong>${o.orderNumber}</strong> - $${o.totalAmount.toFixed(2)}</span>
              <span class="status-badge status-${o.status}">${translateStatus(o.status)}</span>
            </div>
          `).join('')}
        </div>
      </div>
    `;

    showModal(`Pedidos del Cliente`, content);
  } catch (error) {
    console.error('Error loading customer orders:', error);
  }
}

function showNewCustomerModal() {
  const content = `
    <form id="newCustomerForm">
      <div class="form-row">
        <div class="form-group">
          <label>Nombre *</label>
          <input type="text" id="customerName" required />
        </div>
        <div class="form-group">
          <label>Empresa</label>
          <input type="text" id="customerCompany" />
        </div>
      </div>
      <div class="form-group">
        <label>Tipo de Cliente</label>
        <select id="customerType">
          <option value="individual">Persona Natural</option>
          <option value="business">Empresa</option>
          <option value="wholesale">Mayorista</option>
          <option value="distributor">Distribuidor</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="customerEmail" />
        </div>
        <div class="form-group">
          <label>Teléfono</label>
          <input type="tel" id="customerPhone" />
        </div>
      </div>
      <div class="form-group">
        <label>Notas</label>
        <textarea id="customerNotes" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Cliente</button>
    </form>
  `;

  showModal('Nuevo Cliente', content);

  document.getElementById('newCustomerForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = {
      name: document.getElementById('customerName').value,
      companyName: document.getElementById('customerCompany').value,
      type: document.getElementById('customerType').value,
      email: document.getElementById('customerEmail').value,
      phone: document.getElementById('customerPhone').value,
      notes: document.getElementById('customerNotes').value
    };

    try {
      await apiRequest('/customers', {
        method: 'POST',
        body: JSON.stringify(data)
      });
      closeModal();
      showNotification('Cliente creado exitosamente', 'success');
      loadCustomers();
    } catch (error) {
      console.error('Error creating customer:', error);
    }
  };
}

// ============================================
// Order Tracking (Public)
// ============================================

async function trackOrder() {
  const orderNumber = document.getElementById('trackingInput').value.trim();
  if (!orderNumber) {
    showNotification('Por favor ingrese un número de pedido', 'warning');
    return;
  }

  try {
    const response = await apiRequest(`/tracking/order/${orderNumber}`);
    const tracking = response.data;
    renderTrackingResult(tracking);
  } catch (error) {
    document.getElementById('trackingResult').innerHTML = `
      <div class="empty-state">
        <div class="icon">❌</div>
        <p>No se encontró el pedido: ${orderNumber}</p>
      </div>
    `;
  }
}

function renderTrackingResult(tracking) {
  const container = document.getElementById('trackingResult');
  
  container.innerHTML = `
    <div class="order-details">
      <div class="detail-section">
        <h4>📋 Información del Pedido</h4>
        <div class="detail-row">
          <span class="detail-label">Número de Pedido:</span>
          <span class="detail-value">${tracking.orderNumber}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Estado Actual:</span>
          <span class="status-badge status-${tracking.status}">${tracking.statusDescription}</span>
        </div>
        ${tracking.trackingNumber ? `
        <div class="detail-row">
          <span class="detail-label">Número de Rastreo:</span>
          <span class="detail-value">${tracking.trackingNumber}</span>
        </div>
        ` : ''}
        ${tracking.expectedDeliveryDate ? `
        <div class="detail-row">
          <span class="detail-label">Entrega Estimada:</span>
          <span class="detail-value">${formatDate(tracking.expectedDeliveryDate)}</span>
        </div>
        ` : ''}
      </div>
      
      <div class="detail-section">
        <h4>📍 Línea de Tiempo</h4>
        <div class="tracking-timeline">
          ${tracking.timeline.map(step => `
            <div class="timeline-item ${step.completed ? 'completed' : ''} ${step.current ? 'current' : ''}">
              <div class="timeline-icon">${step.icon}</div>
              <div class="timeline-content">
                <h4>${step.label}</h4>
                ${step.timestamp ? `<p>${formatDate(step.timestamp)}</p>` : '<p>Pendiente</p>'}
                ${step.notes ? `<p style="font-size: 0.85rem; color: #64748b;">${step.notes}</p>` : ''}
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    </div>
  `;
}

// ============================================
// Modal Functions
// ============================================

function showModal(title, content) {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').innerHTML = content;
  document.getElementById('modal').classList.add('active');
}

function closeModal() {
  document.getElementById('modal').classList.remove('active');
}

// Close modal on outside click
document.getElementById('modal').addEventListener('click', (e) => {
  if (e.target === document.getElementById('modal')) {
    closeModal();
  }
});

// ============================================
// Notification Functions
// ============================================

function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.remove();
  }, 3000);
}

// ============================================
// Translation Functions
// ============================================

function translateStatus(status) {
  const translations = {
    'pending': 'Pendiente',
    'confirmed': 'Confirmado',
    'in_production': 'En Producción',
    'quality_check': 'Control de Calidad',
    'ready_for_shipping': 'Listo para Envío',
    'in_transit': 'En Tránsito',
    'delivered': 'Entregado',
    'cancelled': 'Cancelado',
    'scheduled': 'Programado',
    'in_queue': 'En Cola',
    'in_progress': 'En Progreso',
    'paused': 'Pausado',
    'completed': 'Completado'
  };
  return translations[status] || status;
}

function translateShipmentStatus(status) {
  const translations = {
    'pending': 'Pendiente',
    'preparing': 'Preparando',
    'ready': 'Listo',
    'picked_up': 'Recogido',
    'in_transit': 'En Tránsito',
    'out_for_delivery': 'En Reparto',
    'delivered': 'Entregado',
    'failed_delivery': 'Entrega Fallida',
    'returned': 'Devuelto',
    'cancelled': 'Cancelado'
  };
  return translations[status] || status;
}

function translateType(type) {
  const translations = {
    'standard': 'Estándar',
    'campaign': 'Campaña',
    'event': 'Evento',
    'wholesale': 'Mayorista'
  };
  return translations[type] || type;
}

function translatePriority(priority) {
  const translations = {
    'low': 'Baja',
    'normal': 'Normal',
    'high': 'Alta',
    'urgent': 'Urgente'
  };
  return translations[priority] || priority;
}

function translateCarrier(carrier) {
  const translations = {
    'internal': 'Transporte Propio',
    'fedex': 'FedEx',
    'ups': 'UPS',
    'dhl': 'DHL',
    'local': 'Mensajería Local',
    'other': 'Otro'
  };
  return translations[carrier] || carrier;
}

function translateCategory(category) {
  const translations = {
    'printed_material': 'Material Impreso',
    'promotional': 'Promocional',
    'packaging': 'Empaque',
    'signage': 'Señalización',
    'graphic_design': 'Diseño Gráfico',
    'custom': 'Personalizado'
  };
  return translations[category] || category;
}

function translateCustomerType(type) {
  const translations = {
    'individual': 'Persona Natural',
    'business': 'Empresa',
    'wholesale': 'Mayorista',
    'distributor': 'Distribuidor'
  };
  return translations[type] || type;
}

function translateCampaignType(type) {
  const translations = {
    'marketing': 'Marketing',
    'graphic_event': 'Evento Gráfico',
    'seasonal': 'Estacional',
    'product_launch': 'Lanzamiento',
    'promotional': 'Promocional'
  };
  return translations[type] || type;
}

function translateCampaignStatus(status) {
  const translations = {
    'draft': 'Borrador',
    'planned': 'Planificada',
    'active': 'Activa',
    'paused': 'Pausada',
    'completed': 'Completada',
    'cancelled': 'Cancelada'
  };
  return translations[status] || status;
}

// ============================================
// Utility Functions
// ============================================

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
