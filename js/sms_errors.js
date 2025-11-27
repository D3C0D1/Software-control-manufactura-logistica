// js/sms_errors.js
// Carga y filtra el historial de errores SMS desde php/get_sms_errors.php

function statusBadge(code) {
  const c = parseInt(code, 10);
  if (isNaN(c)) return '<span class="badge bg-secondary">N/A</span>';
  if (c >= 500) return `<span class="badge bg-dark">${c}</span>`;
  if (c === 403) return `<span class="badge bg-danger">${c}</span>`;
  if (c === 401) return `<span class="badge bg-warning text-dark">${c}</span>`;
  if (c === 400) return `<span class="badge bg-info text-dark">${c}</span>`;
  return `<span class="badge bg-secondary">${c}</span>`;
}

function sanitize(text) {
  if (!text && text !== 0) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

async function loadSmsErrors() {
  const status = document.getElementById('sms-status-selector')?.value || '';
  const from = document.getElementById('sms-from')?.value || '';
  const to = document.getElementById('sms-to')?.value || '';
  const pedido = document.getElementById('sms-pedido')?.value || '';

  const params = new URLSearchParams();
  if (status) params.append('status_code', status);
  if (from) params.append('from', from);
  if (to) params.append('to', to);
  if (pedido) params.append('pedido_id', pedido);
  params.append('limit', '200');

  const tbody = document.getElementById('sms-errors-body');
  if (tbody) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>`;
  }

  try {
    const res = await fetch(`php/get_sms_errors.php?${params.toString()}`, { credentials: 'same-origin' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    const entries = Array.isArray(data.entries) ? data.entries : [];

    if (!tbody) return;

    if (entries.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Sin errores registrados${status ? ` (código ${status})` : ''}</td></tr>`;
      return;
    }

    const html = entries.map(e => {
      const codeHtml = statusBadge(e.status_code);
      const origin = e.source === 'db' ? 'BD' : 'Log';
      const msg = sanitize(e.error_message || '');
      const detail = sanitize(e.response_body || e.request_body || '');
      const pedido = sanitize(e.pedido_id || '');
      const ts = sanitize(e.timestamp || '');
      return `<tr>
        <td>${ts}</td>
        <td>${pedido}</td>
        <td>${codeHtml}</td>
        <td style="max-width:480px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${msg}</td>
        <td>${origin}</td>
        <td style="max-width:480px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${detail}</td>
      </tr>`;
    }).join('');

    tbody.innerHTML = html;
  } catch (err) {
    if (tbody) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error cargando errores SMS: ${sanitize(err.message)}</td></tr>`;
    }
    console.error('Error cargando errores SMS', err);
  }
}

function setupSmsErrorsUI() {
  const btn = document.getElementById('sms-errors-filter-btn');
  const selects = ['sms-status-selector', 'sms-from', 'sms-to'];
  if (btn) btn.addEventListener('click', () => loadSmsErrors());
  selects.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => loadSmsErrors());
  });
  // Primera carga
  loadSmsErrors();
}

document.addEventListener('DOMContentLoaded', setupSmsErrorsUI);