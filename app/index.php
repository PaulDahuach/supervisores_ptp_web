<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
auth_require_login();

$name    = sys('short_name', sys('name'));
$primary = sys('primary', '#2563eb');
$menu    = sys('menu', []);
$ro      = db_readonly();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="<?= h(sys('theme','dark')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($name) ?> — Menú Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= bu('/assets/css/app.css') ?>" rel="stylesheet">
    <style>:root{ --fc-primary: <?= h($primary) ?>; }</style>
    <script>window.IWK_BASE = '<?= rtrim(sys('base_url',''),'/') ?>';</script>
</head>
<body>
<div class="fc-topbar">
    <div class="d-flex align-items-center gap-3">
        <h1><i class="bi bi-grid-3x3-gap me-2"></i><?= h($name) ?></h1>
        <?php if ($ro): ?><span class="badge bg-warning text-dark"><i class="bi bi-eye me-1"></i>Sólo lectura</span><?php endif; ?>
        <?php if (auth_sector_login() && auth_sector()): ?>
        <span class="badge bg-primary"><i class="bi bi-diagram-3 me-1"></i><?= h(auth_sector_name()) ?></span>
        <a href="<?= bu('/app/sector.php') ?>" class="btn btn-sm btn-outline-light py-0 px-1" title="Cambiar sector"><i class="bi bi-arrow-repeat"></i></a>
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-light"><i class="bi bi-person-circle me-1"></i><?= h(auth_user()) ?></span>
        <button id="btnLogout" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Salir</button>
        <div class="theme-toggle">
            <span class="theme-icon"><i class="bi bi-sun-fill"></i></span>
            <div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" id="themeSwitch" role="switch"></div>
            <span class="theme-icon"><i class="bi bi-moon-fill"></i></span>
        </div>
    </div>
</div>

<div class="container-fluid py-3" style="max-width:1200px;">
<?php foreach ($menu as $section => $cards): ?>
    <div class="menu-section-title"><?= h($section) ?></div>
    <div class="row g-3 mb-4">
        <?php foreach ($cards as $c): ?>
        <div class="col-md-3 col-6">
            <a href="<?= h(bu($c['url'])) ?>" class="menu-card">
                <div class="menu-icon"><i class="bi <?= h($c['icon'] ?? 'bi-app') ?>"></i></div>
                <div>
                    <div class="menu-label"><?= h($c['label']) ?></div>
                    <?php if (!empty($c['desc'])): ?><div class="menu-desc"><?= h($c['desc']) ?></div><?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php if (!$menu): ?>
    <div class="alert alert-info">No hay módulos configurados. Editá <code>config/system.php → 'menu'</code>.</div>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= bu('/assets/js/app.js') ?>"></script>
</body>
</html>
