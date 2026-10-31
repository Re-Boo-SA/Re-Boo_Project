<?php
session_start();

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    $_SESSION['flash_error'] = 'Acceso denegado: Esta sección es exclusiva para Administradores.';
    header('Location: login.php');
    exit();
}

$nombreAdmin = $_SESSION['nombre_usuario'] ?? 'Administrador';
$correoAdmin = $_SESSION['correo'] ?? '';
$idAdmin = $_SESSION['usuario_id'] ?? 0;

$fachada = new FachadaLogica();
$jugadores = $fachada->retornoIJugadorLogica()->listarJugadores();
$admins = $fachada->retornoIAdminLogica()->listarAdmins();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración | Re-Boo</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <header class="navbar">
        <div class="brand-logo">
            <div class="brand-icon">R</div>
            <span class="brand-text">Re-Boo</span>
            <span class="brand-badge">Admin Core</span>
        </div>
        <div>
            <a href="logout.php" class="btn btn-outline" style="width: auto; padding: 0.5rem 1.25rem;" id="btn-admin-logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </header>

    <main class="dashboard-container">
        <!-- Tarjeta de Perfil Administrador -->
        <section class="profile-card">
            <div class="user-identity">
                <div class="user-avatar admin-avatar">
                    <?= strtoupper(substr($nombreAdmin, 0, 1)) ?>
                </div>
                <div class="user-details">
                    <h2><?= htmlspecialchars($nombreAdmin) ?></h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($correoAdmin) ?></p>
                    <span class="user-role-tag role-admin">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                        Administrador del Sistema
                    </span>
                </div>
            </div>

            <div>
                <span style="font-size: 0.85rem; color: var(--text-dim);">ID Usuario BDD: #<?= htmlspecialchars((string)$idAdmin) ?></span>
            </div>
        </section>

        <!-- Estadísticas del Sistema -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Jugadores Registrados</div>
                <div class="stat-value cyan"><?= count($jugadores) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Administradores Activos</div>
                <div class="stat-value rose"><?= count($admins) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Estado Base de Datos</div>
                <div class="stat-value emerald" style="font-size: 1.5rem; font-family: inherit;">Activo / Conectado</div>
            </div>
        </section>

        <!-- Mensaje Informativo sobre la Regla de Negocio -->
        <section class="content-box" style="margin-bottom: 2rem;">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--accent-rose)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                Política de Gestión de Cuentas Administrativas
            </h3>
            <p style="color: var(--text-muted); line-height: 1.6; font-size: 0.95rem;">
                Por políticas estrictas de seguridad del juego <strong>Re-Boo</strong>, los administradores <strong>no</strong> cuentan con registro a través del portal web. Su alta y asignación de permisos se gestiona directamente en las tablas correspondientes (<code>USUARIOS</code> y <code>ADMINISTRADORES</code>) en la base de datos MySQL.
            </p>
        </section>

        <!-- Lista de Jugadores Registrados -->
        <section class="content-box">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--accent-cyan)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Jugadores Registrados en Plataforma
            </h3>

            <?php if (empty($jugadores)): ?>
                <p style="color: var(--text-dim);">No hay jugadores registrados activos en este momento.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-card); color: var(--text-dim); text-align: left;">
                                <th style="padding: 0.75rem;">ID</th>
                                <th style="padding: 0.75rem;">Jugador</th>
                                <th style="padding: 0.75rem;">Correo</th>
                                <th style="padding: 0.75rem;">Fichas</th>
                                <th style="padding: 0.75rem;">Partidas</th>
                                <th style="padding: 0.75rem;">Ganadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jugadores as $j): ?>
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.04);">
                                    <td style="padding: 0.75rem; color: var(--text-dim);">#<?= $j->getIdUsuario() ?></td>
                                    <td style="padding: 0.75rem; font-weight: 600; color: #ffffff;"><?= htmlspecialchars($j->getNombreUsuario()) ?></td>
                                    <td style="padding: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($j->getCorreo()) ?></td>
                                    <td style="padding: 0.75rem; color: var(--accent-cyan); font-family: monospace;"><?= $j->getFichasActuales() ?></td>
                                    <td style="padding: 0.75rem; color: var(--text-muted);"><?= $j->getPartidasJugadas() ?></td>
                                    <td style="padding: 0.75rem; color: var(--accent-emerald); font-weight: 600;"><?= $j->getPartidasGanadas() ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
