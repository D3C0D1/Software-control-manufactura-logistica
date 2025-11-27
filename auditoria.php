<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php?error=unauthorized');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría y Historial - Glamcity</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <style>
        .audit-container { max-width: 1200px; margin: 30px auto; padding: 0 16px; }
        .audit-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .audit-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .audit-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); max-width: 1100px; margin: 0 auto; }
        .audit-card .card-header { padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 8px; }
        .audit-card .card-body { padding: 16px 20px; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 8px; }
        .audit-table { width: 100%; border-collapse: collapse; min-width: 960px; margin: 0 auto; }
        .audit-table th, .audit-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .audit-table th { background: #fafafa; text-align: left; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; background: #eef; color: #334; }
        .log-view { font-family: monospace; background: #0b0d10; color: #cde2ff; padding: 12px; border-radius: 8px; max-height: 300px; overflow: auto; }
        .actions { display: flex; gap: 8px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; border: none; border-radius: 20px; padding: 8px 14px; cursor: pointer; }
        .btn-refresh { background: #007bff; color: #fff; }
        .btn-back { background: #6c757d; color: #fff; text-decoration: none; }
        .search-bar { display: grid; grid-template-columns: repeat(5, minmax(140px, 1fr)); gap: 10px; margin-bottom: 12px; align-items: center; }
        .search-bar input { padding: 8px 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .search-bar select { padding: 8px 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #fff; }
        @media (max-width: 992px) {
            .audit-card { max-width: 100%; }
            .audit-table { min-width: 840px; }
            .search-bar { grid-template-columns: repeat(3, minmax(160px, 1fr)); }
        }
        @media (max-width: 768px) {
            .search-bar { grid-template-columns: repeat(2, minmax(160px, 1fr)); }
            .audit-table th, .audit-table td { padding: 6px 8px; font-size: 13px; }
            .audit-table { min-width: 720px; }
        }
        @media (max-width: 520px) {
            .search-bar { grid-template-columns: 1fr; }
            .audit-table { min-width: 600px; }
        }
    </style>
    <script>
        let _movimientosData = [];
        let _minPedidoId = null; // ID mínimo para normalizar (p.ej., 73 => 1)

        async function loadStartPedidoId() {
            try {
                const res = await fetch('php/get_pedidos_bounds.php', { credentials: 'same-origin' });
                const data = await res.json();
                if (data && data.success && typeof data.min_id === 'number') {
                    _minPedidoId = data.min_id;
                }
            } catch (e) {
                console.warn('No se pudo obtener min_id, usando ID real', e);
            }
        }

        function renderMovimientos() {
            const movimientosTbody = document.querySelector('#tabla-movimientos tbody');
            const filtroFecha = document.getElementById('filtro-fecha');
            const filtroNombre = document.getElementById('filtro-nombre');
            const filtroTelefono = document.getElementById('filtro-telefono');
            const filtroGuia = document.getElementById('filtro-guia');

            const fechaStr = filtroFecha?.value || '';
            const nombreStr = (filtroNombre?.value || '').toLowerCase();
            const telStr = (filtroTelefono?.value || '').toLowerCase();
            const guiaStr = (filtroGuia?.value || '').toLowerCase();

            const filtered = _movimientosData.filter(m => {
                const dEntrada = m.fecha_entrada ? new Date(m.fecha_entrada) : null;
                let matchFecha = true;
                if (fechaStr && dEntrada) {
                    const yyyy = dEntrada.getFullYear();
                    const mm = String(dEntrada.getMonth() + 1).padStart(2, '0');
                    const dd = String(dEntrada.getDate()).padStart(2, '0');
                    const isoDate = `${yyyy}-${mm}-${dd}`;
                    matchFecha = (isoDate === fechaStr);
                }

                const nombre = (m.nombre_cliente || '').toLowerCase();
                const tel = (m.numero_cliente || '').toLowerCase();
                const guia = (m.numero_guia || '').toLowerCase();

                const matchNombre = !nombreStr || nombre.includes(nombreStr);
                const matchTel = !telStr || tel.includes(telStr);
                const matchGuia = !guiaStr || guia.includes(guiaStr);

                return matchFecha && matchNombre && matchTel && matchGuia;
            });

            movimientosTbody.innerHTML = '';
            for (const m of filtered) {
                const dEntrada = m.fecha_entrada ? new Date(m.fecha_entrada) : null;
                const dSalida = m.fecha_salida ? new Date(m.fecha_salida) : null;
                const fechaMovimiento = dEntrada ? dEntrada.toLocaleDateString() : '-';
                const horaMovimiento = dEntrada ? dEntrada.toLocaleTimeString() : '-';
                const fechaEntrada = dEntrada ? dEntrada.toLocaleDateString() : '-';
                const horaEntrada = dEntrada ? dEntrada.toLocaleTimeString() : '-';
                const fechaSalida = dSalida ? dSalida.toLocaleDateString() : '-';
                const horaSalida = dSalida ? dSalida.toLocaleTimeString() : '-';
                const tiempo = m.tiempo_en_area ? (m.tiempo_en_area + 's') : '-';
                const areaBadge = m.area_nombre ? m.area_nombre : ('Área ' + (m.area_id ?? ''));
                const tipo = m.tipo_evento || 'movimiento';
                const isDeleted = tipo === 'eliminado';
                const displayId = (typeof m.pedido_id === 'number' && typeof _minPedidoId === 'number')
                    ? (m.pedido_id - _minPedidoId + 1)
                    : m.pedido_id;
                movimientosTbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td title="ID real ${m.pedido_id}">${displayId}</td>
                        <td>${m.numero_guia ?? ''}</td>
                        <td><span class="badge" style="${isDeleted ? 'background:#fee;color:#a00;' : ''}">${areaBadge}</span></td>
                        <td>${m.usuario_nombre ?? ('ID ' + (m.usuario_entrada_id ?? ''))}</td>
                        <td>${fechaMovimiento} ${horaMovimiento}</td>
                        <td>${fechaEntrada}</td>
                        <td>${horaEntrada}</td>
                        <td>${fechaSalida}</td>
                        <td>${horaSalida}</td>
                        <td>${tiempo}</td>
                    </tr>
                `);
            }

            if (!filtered.length) {
                movimientosTbody.innerHTML = '<tr><td colspan="10">Sin movimientos que coincidan con el filtro</td></tr>';
            }
        }

        async function loadAudit() {
            const movimientosTbody = document.querySelector('#tabla-movimientos tbody');
            const usuariosTbody = document.querySelector('#tabla-usuarios tbody');
            const logsPre = document.getElementById('logs-pre');
            const errorsPre = document.getElementById('errors-pre');
            movimientosTbody.innerHTML = '<tr><td colspan="10">Cargando...</td></tr>';
            usuariosTbody.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';
            logsPre.textContent = 'Cargando logs...';
            if (errorsPre) errorsPre.textContent = 'Cargando errores...';
            try {
                const limitEl = document.getElementById('filtro-registros');
                const limitVal = limitEl ? (limitEl.value || '') : '';
                const url = limitVal ? `php/get_audit.php?limit=${encodeURIComponent(limitVal)}` : 'php/get_audit.php';
                const res = await fetch(url, { credentials: 'same-origin' });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Error al cargar auditoría');

                // Guardar y render movimientos
                _movimientosData = Array.isArray(data.movimientos) ? data.movimientos : [];
                renderMovimientos();

                // Render usuarios
                usuariosTbody.innerHTML = '';
                if (Array.isArray(data.usuarios) && data.usuarios.length) {
                    for (const u of data.usuarios) {
                        const last = u.last_activity ? new Date(u.last_activity).toLocaleString() : 'Sin registro';
                        usuariosTbody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>${u.id}</td>
                                <td>${u.usuario}</td>
                                <td>${u.nombre_completo}</td>
                                <td>${u.rol ?? '-'}</td>
                                <td>${u.area_empleado ?? '-'}</td>
                                <td>${last}</td>
                            </tr>
                        `);
                    }
                } else {
                    usuariosTbody.innerHTML = '<tr><td colspan="6">Sin datos de usuarios</td></tr>';
                }

                // Render logs
                logsPre.textContent = '';
                if (Array.isArray(data.logs) && data.logs.length) {
                    logsPre.textContent = data.logs.join('\n');
                } else {
                    logsPre.textContent = 'Sin registros disponibles';
                }

                // Render errores
                if (errorsPre) {
                    errorsPre.textContent = '';
                    if (Array.isArray(data.errors) && data.errors.length) {
                        errorsPre.textContent = data.errors.join('\n');
                    } else {
                        errorsPre.textContent = 'Sin errores detectados';
                    }
                }
            } catch (err) {
                movimientosTbody.innerHTML = '<tr><td colspan="10">Error al cargar movimientos</td></tr>';
                usuariosTbody.innerHTML = '<tr><td colspan="6">Error al cargar usuarios</td></tr>';
                logsPre.textContent = 'Error al cargar logs';
                if (errorsPre) errorsPre.textContent = 'Error al cargar registro de errores';
                console.error(err);
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            await loadStartPedidoId();
            loadAudit();
            // Filtros en tiempo real
            ['filtro-fecha','filtro-nombre','filtro-telefono','filtro-guia','filtro-registros'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', renderMovimientos);
                if (el && el.type === 'date') el.addEventListener('change', renderMovimientos);
                if (id === 'filtro-registros' && el) el.addEventListener('change', () => { loadAudit(); });
            });
        });
    </script>
    </head>
<body>
    <div class="audit-container">
        <div class="audit-header">
            <h1><i class="fas fa-clipboard-list"></i> Auditoría y Historial</h1>
            <div class="audit-date" style="color:#555"><i class="far fa-calendar-alt"></i> Fecha: <?php echo date('d/m/Y'); ?></div>
            <div class="actions">
                <button class="btn btn-refresh" onclick="loadAudit()"><i class="fas fa-sync"></i> Refrescar</button>
                <a class="btn btn-back" href="config.php"><i class="fas fa-arrow-left"></i> Volver a Configuración</a>
            </div>
        </div>

        <div class="audit-grid">
            <div class="audit-card">
                <div class="card-header"><i class="fas fa-random"></i> Movimientos de Pedidos</div>
                <div class="card-body">
                    <div class="search-bar">
                        <select id="filtro-registros" title="Registros">
                            <option value="100" selected>100 filas</option>
                            <option value="50">50 filas</option>
                            <option value="25">25 filas</option>
                            <option value="all">Todos</option>
                        </select>
                        <input type="date" id="filtro-fecha" placeholder="Fecha movimiento" />
                        <input type="text" id="filtro-nombre" placeholder="Nombre cliente" />
                        <input type="text" id="filtro-telefono" placeholder="Número teléfono" />
                        <input type="text" id="filtro-guia" placeholder="Número de guía" />
                    </div>
                    <div class="table-responsive">
                    <table id="tabla-movimientos" class="audit-table">
                        <thead>
                            <tr>
                                <th>N° Pedido</th>
                                <th>Número Guía</th>
                                <th>Área</th>
                                <th>Usuario</th>
                                <th>Fecha y Hora Movimiento</th>
                                <th>Fecha Entrada</th>
                                <th>Hora Entrada</th>
                                <th>Fecha Salida</th>
                                <th>Hora Salida</th>
                                <th>Tiempo en Área</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="audit-card">
                <div class="card-header"><i class="fas fa-users"></i> Actividad de Usuarios</div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table id="tabla-usuarios" class="audit-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Área</th>
                                <th>Última Actividad</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    </div>
                    <p style="margin-top:8px;color:#666">Nota: La última actividad se actualiza periódicamente desde la app.</p>
                </div>
            </div>

            <div class="audit-card">
                <div class="card-header"><i class="fas fa-file-code"></i> Registros de Desarrollador</div>
                <div class="card-body">
                    <div class="log-view"><pre id="logs-pre"></pre></div>
                </div>
            </div>

            <div class="audit-card">
                <div class="card-header"><i class="fas fa-exclamation-triangle"></i> Registro de Errores</div>
                <div class="card-body">
                    <div class="log-view"><pre id="errors-pre"></pre></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>