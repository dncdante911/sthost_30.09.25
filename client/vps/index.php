<?php
/**
 * ============================================
 * VPS MANAGEMENT PAGE - StormHosting UA
 * Страница управления VPS серверами
 * ============================================
 */

define('SECURE_ACCESS', true);

// Проверка авторизации
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Подключение к базе данных и классам
try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_connect.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/classes/VPSManager.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/classes/LibvirtManager.php';
    
    $pdo = DatabaseConnection::getSiteConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die('Ошибка подключения к базе данных');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Инициализируем VPS Manager
$vpsManager = new VPSManager($pdo);

// Получаем список VPS пользователя
$vps_result = $vpsManager->getUserVPS($user_id);
$user_vps_list = $vps_result['success'] ? $vps_result['vps_list'] : [];

// Получаем планы VPS для создания новых
$plans_result = $vpsManager->getVPSPlans();
$vps_plans = $plans_result['success'] ? $plans_result['plans'] : [];

// Получаем шаблоны ОС
$os_result = $vpsManager->getOSTemplates();
$os_templates = $os_result['success'] ? $os_result['templates'] : [];

// Группируем ОС по типам
$os_by_type = [];
foreach ($os_templates as $os) {
    $os_by_type[$os['type']][] = $os;
}

// Получаем последние операции
$recent_operations = [];
try {
    $stmt = $pdo->prepare("
        SELECT vol.*, vi.hostname, vi.ip_address
        FROM vps_operations_log vol
        LEFT JOIN vps_instances vi ON vol.vps_id = vi.id
        WHERE vol.user_id = ?
        ORDER BY vol.started_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $recent_operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Таблица операций может не существовать
}

// Статистика VPS
$vps_stats = [
    'total' => count($user_vps_list),
    'active' => 0,
    'running' => 0,
    'stopped' => 0,
    'suspended' => 0,
    'total_cpu' => 0,
    'total_ram_gb' => 0,
    'total_disk_gb' => 0
];

foreach ($user_vps_list as $vps) {
    if ($vps['status'] === 'active') $vps_stats['active']++;
    if ($vps['libvirt_status'] === 'active') $vps_stats['running']++;
    if ($vps['libvirt_status'] === 'stopped') $vps_stats['stopped']++;
    if ($vps['status'] === 'suspended') $vps_stats['suspended']++;
    
    $vps_stats['total_cpu'] += $vps['plan_cpu'] ?? 0;
    $vps_stats['total_ram_gb'] += ($vps['plan_ram'] ?? 0) / 1024;
    $vps_stats['total_disk_gb'] += $vps['plan_disk'] ?? 0;
}

// Мета-данные страницы
$page_title = 'Управление VPS - StormHosting UA';
$page_description = 'Управление виртуальными приватными серверами';

include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/vps-management.css">
</head>

<body class="vps-management-page">

<main class="main-content">
    <div class="container-fluid">
        
        <!-- Заголовок страницы -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="page-title">
                        <i class="bi bi-hdd-stack me-2"></i>
                        Управление VPS
                    </h1>
                    <p class="page-subtitle">Мониторинг и управление вашими виртуальными серверами</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="page-actions">
                        <a href="/client/dashboard.php" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-arrow-left"></i>
                            К дашбоарду
                        </a>
                        <?php if (!empty($vps_plans)): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createVPSModal">
                            <i class="bi bi-plus-circle"></i>
                            Создать VPS
                        </button>
                        <?php else: ?>
                        <a href="/pages/vps.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i>
                            Заказать VPS
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Статистика VPS -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card stats-total">
                    <div class="stats-icon">
                        <i class="bi bi-hdd-stack"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo $vps_stats['total']; ?></h3>
                        <p>Всего VPS</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stats-card stats-running">
                    <div class="stats-icon">
                        <i class="bi bi-play-circle"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo $vps_stats['running']; ?></h3>
                        <p>Запущено</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stats-card stats-stopped">
                    <div class="stats-icon">
                        <i class="bi bi-stop-circle"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo $vps_stats['stopped']; ?></h3>
                        <p>Остановлено</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stats-card stats-resources">
                    <div class="stats-icon">
                        <i class="bi bi-cpu"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo $vps_stats['total_cpu']; ?></h3>
                        <p>CPU ядер</p>
                        <small><?php echo round($vps_stats['total_ram_gb'], 1); ?> GB RAM</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Основной контент -->
        <div class="row g-4">
            
            <!-- Список VPS -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="bi bi-list"></i>
                            Мои VPS сервери
                        </h4>
                        <div class="card-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshVPSList()">
                                <i class="bi bi-arrow-clockwise"></i>
                                Обновить
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($user_vps_list)): ?>
                        
                        <div class="vps-list">
                            <?php foreach ($user_vps_list as $vps): ?>
                            <div class="vps-card" data-vps-id="<?php echo $vps['id']; ?>">
                                <!-- VPS Header -->
                                <div class="vps-header">
                                    <div class="vps-info">
                                        <h5 class="vps-name">
                                            <?php echo htmlspecialchars($vps['hostname']); ?>
                                            <span class="vps-os"><?php echo htmlspecialchars($vps['os_name'] ?? 'Unknown'); ?></span>
                                        </h5>
                                        <div class="vps-details">
                                            <span class="detail-item">
                                                <i class="bi bi-globe"></i>
                                                IP: <?php echo htmlspecialchars($vps['ip_address']); ?>
                                            </span>
                                            <span class="detail-item">
                                                <i class="bi bi-calendar3"></i>
                                                Создан: <?php echo date('d.m.Y', strtotime($vps['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="vps-status">
                                        <span class="status-badge status-<?php echo $vps['status']; ?>">
                                            <?php echo ucfirst($vps['status']); ?>
                                        </span>
                                        <span class="power-status power-<?php echo $vps['libvirt_status'] ?? 'unknown'; ?>">
                                            <?php echo ucfirst($vps['libvirt_status'] ?? 'unknown'); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- VPS Specs -->
                                <div class="vps-specs">
                                    <div class="spec-item">
                                        <i class="bi bi-cpu"></i>
                                        <span class="spec-label">CPU:</span>
                                        <span class="spec-value"><?php echo $vps['plan_cpu'] ?? 1; ?> ядра</span>
                                    </div>
                                    <div class="spec-item">
                                        <i class="bi bi-memory"></i>
                                        <span class="spec-label">RAM:</span>
                                        <span class="spec-value"><?php echo $vps['plan_ram'] ?? 1024; ?> MB</span>
                                    </div>
                                    <div class="spec-item">
                                        <i class="bi bi-hdd"></i>
                                        <span class="spec-label">Диск:</span>
                                        <span class="spec-value"><?php echo $vps['plan_disk'] ?? 20; ?> GB</span>
                                    </div>
                                    <div class="spec-item">
                                        <i class="bi bi-bar-chart"></i>
                                        <span class="spec-label">План:</span>
                                        <span class="spec-value"><?php echo htmlspecialchars($vps['plan_name'] ?? 'Unknown'); ?></span>
                                    </div>
                                </div>
                                
                                <!-- VPS Actions -->
                                <div class="vps-actions">
                                    <div class="action-group">
                                        <!-- Управление питанием -->
                                        <button class="btn btn-sm btn-success vps-action-btn" 
                                                data-action="start" 
                                                data-vps-id="<?php echo $vps['id']; ?>"
                                                title="Запустить"
                                                <?php echo ($vps['libvirt_status'] === 'active') ? 'disabled' : ''; ?>>
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                        
                                        <button class="btn btn-sm btn-warning vps-action-btn" 
                                                data-action="restart" 
                                                data-vps-id="<?php echo $vps['id']; ?>"
                                                title="Перезагрузить"
                                                <?php echo ($vps['libvirt_status'] === 'stopped') ? 'disabled' : ''; ?>>
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        
                                        <button class="btn btn-sm btn-danger vps-action-btn" 
                                                data-action="stop" 
                                                data-vps-id="<?php echo $vps['id']; ?>"
                                                title="Остановить"
                                                <?php echo ($vps['libvirt_status'] === 'stopped') ? 'disabled' : ''; ?>>
                                            <i class="bi bi-stop-fill"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="action-group">
                                        <!-- Дополнительные действия -->
                                        <button class="btn btn-sm btn-outline-primary vps-action-btn" 
                                                data-action="reset_password" 
                                                data-vps-id="<?php echo $vps['id']; ?>"
                                                title="Сбросить пароль">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        
                                        <a href="/client/vps/console.php?id=<?php echo $vps['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="VNC консоль" 
                                           target="_blank">
                                            <i class="bi bi-terminal"></i>
                                        </a>
                                        
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="/client/vps/stats.php?id=<?php echo $vps['id']; ?>">
                                                    <i class="bi bi-graph-up"></i> Статистика
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="showSnapshotModal(<?php echo $vps['id']; ?>)">
                                                    <i class="bi bi-camera"></i> Снапшоты
                                                </a></li>
                                                <li><a class="dropdown-item" href="/client/vps/settings.php?id=<?php echo $vps['id']; ?>">
                                                    <i class="bi bi-gear"></i> Настройки
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmDeleteVPS(<?php echo $vps['id']; ?>)">
                                                    <i class="bi bi-trash"></i> Удалить
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Resource Usage (если доступно) -->
                                <?php if (isset($vps['resource_usage'])): ?>
                                <div class="vps-usage">
                                    <div class="usage-item">
                                        <span class="usage-label">CPU:</span>
                                        <div class="usage-bar">
                                            <div class="usage-fill" style="width: <?php echo $vps['resource_usage']['cpu_usage']; ?>%"></div>
                                        </div>
                                        <span class="usage-value"><?php echo round($vps['resource_usage']['cpu_usage'], 1); ?>%</span>
                                    </div>
                                    <div class="usage-item">
                                        <span class="usage-label">RAM:</span>
                                        <div class="usage-bar">
                                            <div class="usage-fill" style="width: <?php echo $vps['resource_usage']['memory_usage']; ?>%"></div>
                                        </div>
                                        <span class="usage-value"><?php echo round($vps['resource_usage']['memory_usage'], 1); ?>%</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php else: ?>
                        
                        <!-- Пустое состояние -->
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-hdd-stack"></i>
                            </div>
                            <h5>У вас пока нет VPS серверов</h5>
                            <p>Создайте свой первый виртуальный сервер для размещения проектов</p>
                            <?php if (!empty($vps_plans)): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createVPSModal">
                                <i class="bi bi-plus-circle"></i>
                                Создать первый VPS
                            </button>
                            <?php else: ?>
                            <a href="/pages/vps.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i>
                                Заказать VPS
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Боковая панель -->
            <div class="col-lg-4">
                
                <!-- Последние операции -->
                <?php if (!empty($recent_operations)): ?>
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-clock-history"></i>
                            Последние операции
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="operations-list">
                            <?php foreach (array_slice($recent_operations, 0, 10) as $operation): ?>
                            <div class="operation-item">
                                <div class="operation-icon status-<?php echo $operation['status']; ?>">
                                    <i class="bi bi-<?php echo getOperationIcon($operation['operation_type']); ?>"></i>
                                </div>
                                <div class="operation-content">
                                    <div class="operation-title">
                                        <?php echo getOperationName($operation['operation_type']); ?>
                                    </div>
                                    <div class="operation-target">
                                        <?php echo htmlspecialchars($operation['hostname']); ?>
                                        <?php if ($operation['ip_address']): ?>
                                        (<?php echo htmlspecialchars($operation['ip_address']); ?>)
                                        <?php endif; ?>
                                    </div>
                                    <div class="operation-time">
                                        <?php echo date('d.m.Y H:i', strtotime($operation['started_at'])); ?>
                                    </div>
                                </div>
                                <div class="operation-status">
                                    <span class="status-dot status-<?php echo $operation['status']; ?>"></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($recent_operations) > 10): ?>
                        <div class="text-center mt-3">
                            <a href="/client/vps/logs.php" class="btn btn-sm btn-outline-primary">
                                Показать все операции
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Быстрые действия -->
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-lightning"></i>
                            Быстрые действия
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <?php if (!empty($vps_plans)): ?>
                            <button type="button" class="quick-action-btn" data-bs-toggle="modal" data-bs-target="#createVPSModal">
                                <i class="bi bi-plus-circle"></i>
                                <span>Создать VPS</span>
                            </button>
                            <?php endif; ?>
                            
                            <a href="/client/vps/templates.php" class="quick-action-btn">
                                <i class="bi bi-collection"></i>
                                <span>Шаблоны ОС</span>
                            </a>
                            
                            <a href="/client/vps/snapshots.php" class="quick-action-btn">
                                <i class="bi bi-camera"></i>
                                <span>Снапшоты</span>
                            </a>
                            
                            <a href="/pages/vps.php" class="quick-action-btn">
                                <i class="bi bi-cart"></i>
                                <span>Тарифы VPS</span>
                            </a>
                            
                            <a href="https://bill.sthost.pro/client/support" class="quick-action-btn" target="_blank">
                                <i class="bi bi-question-circle"></i>
                                <span>Поддержка</span>
                            </a>
                            
                            <a href="/client/vps/docs.php" class="quick-action-btn">
                                <i class="bi bi-book"></i>
                                <span>Документация</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ============================================
     МОДАЛЬНЫЕ ОКНА
============================================ -->

<!-- Модальное окно создания VPS -->
<?php if (!empty($vps_plans)): ?>
<div class="modal fade" id="createVPSModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Создание нового VPS
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="createVPSForm">
                <div class="modal-body">
                    
                    <!-- Выбор плана -->
                    <div class="mb-4">
                        <h6 class="form-label">Выберите тарифный план</h6>
                        <div class="row g-3">
                            <?php foreach ($vps_plans as $plan): ?>
                            <div class="col-md-4">
                                <div class="plan-card">
                                    <input type="radio" 
                                           name="plan_id" 
                                           value="<?php echo $plan['id']; ?>" 
                                           id="plan_<?php echo $plan['id']; ?>" 
                                           required>
                                    <label for="plan_<?php echo $plan['id']; ?>" class="plan-label">
                                        <div class="plan-name"><?php echo htmlspecialchars($plan['name_ua']); ?></div>
                                        <div class="plan-specs">
                                            <div><i class="bi bi-cpu"></i> <?php echo $plan['cpu_cores']; ?> CPU</div>
                                            <div><i class="bi bi-memory"></i> <?php echo $plan['ram_mb']; ?> MB RAM</div>
                                            <div><i class="bi bi-hdd"></i> <?php echo $plan['storage_gb']; ?> GB SSD</div>
                                        </div>
                                        <div class="plan-price"><?php echo $plan['price_monthly']; ?> грн/мес</div>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Выбор ОС -->
                    <div class="mb-4">
                        <h6 class="form-label">Операционная система</h6>
                        
                        <div class="os-tabs">
                            <ul class="nav nav-tabs" role="tablist">
                                <?php foreach ($os_by_type as $type => $templates): ?>
                                <li class="nav-item">
                                    <button class="nav-link <?php echo $type === 'linux' ? 'active' : ''; ?>" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#os-<?php echo $type; ?>">
                                        <?php echo ucfirst($type); ?>
                                    </button>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="tab-content mt-3">
                                <?php foreach ($os_by_type as $type => $templates): ?>
                                <div class="tab-pane <?php echo $type === 'linux' ? 'active' : ''; ?>" id="os-<?php echo $type; ?>">
                                    <div class="row g-2">
                                        <?php foreach ($templates as $template): ?>
                                        <div class="col-md-6">
                                            <div class="os-option">
                                                <input type="radio" 
                                                       name="os_template_id" 
                                                       value="<?php echo $template['id']; ?>" 
                                                       id="os_<?php echo $template['id']; ?>" 
                                                       <?php echo $template['is_popular'] ? 'required' : ''; ?>>
                                                <label for="os_<?php echo $template['id']; ?>" class="os-label">
                                                    <div class="os-name"><?php echo htmlspecialchars($template['display_name']); ?></div>
                                                    <?php if ($template['is_popular']): ?>
                                                    <span class="badge bg-success">Популярная</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Настройки VPS -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="hostname" class="form-label">Имя хоста</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="hostname" 
                                   name="hostname" 
                                   placeholder="my-vps" 
                                   pattern="[a-zA-Z0-9-]+" 
                                   maxlength="63" 
                                   required>
                            <div class="form-text">Только буквы, цифры и дефисы</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="root_password" class="form-label">Пароль root (необязательно)</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="root_password" 
                                   name="root_password" 
                                   placeholder="Генерировать автоматически">
                            <div class="form-text">Оставьте пустым для автогенерации</div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                Я согласен с <a href="/terms" target="_blank">условиями использования</a>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        Создать VPS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Модальное окно снапшотов -->
<div class="modal fade" id="snapshotModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-camera me-2"></i>
                    Управление снапшотами
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="snapshot-content">
                    <!-- Контент будет загружен через AJAX -->
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/vps-management.js"></script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

</body>
</html>

<?php
// ============================================
// HELPER FUNCTIONS
// ============================================

function getOperationIcon($operation_type) {
    switch ($operation_type) {
        case 'start': return 'play-fill';
        case 'stop': return 'stop-fill';
        case 'restart': return 'arrow-clockwise';
        case 'create': return 'plus-circle';
        case 'delete': return 'trash';
        case 'reset_password': return 'key';
        case 'create_snapshot': return 'camera';
        case 'restore_snapshot': return 'arrow-counterclockwise';
        default: return 'gear';
    }
}

function getOperationName($operation_type) {
    $names = [
        'start' => 'Запуск VPS',
        'stop' => 'Остановка VPS',
        'restart' => 'Перезагрузка VPS',
        'create' => 'Создание VPS',
        'delete' => 'Удаление VPS',
        'reset_password' => 'Сброс пароля',
        'create_snapshot' => 'Создание снапшота',
        'restore_snapshot' => 'Восстановление снапшота'
    ];
    
    return $names[$operation_type] ?? ucfirst($operation_type);
}
?>