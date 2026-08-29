<?php
declare(strict_types=1);
if (!defined('BASE_URL')) require_once __DIR__ . '/../config/constants.php';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex,nofollow">
    <title><?= h($pageTitle ?? 'EHRS') ?> — Secure Healthcare Records</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Google+Sans+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= h($css) ?>">
    <?php endforeach; endif; ?>
</head>
<body class="role-<?= h($_SESSION['role'] ?? 'guest') ?>">

<?php if (!empty($flash)): ?>
<div class="toast toast-<?= h($flash['type']) ?>" id="flashToast" role="alert" aria-live="assertive">
    <span class="toast-icon"><?= $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '✕' : '⚠') ?></span>
    <?= h($flash['msg']) ?>
    <button class="toast-close" onclick="this.parentElement.remove()">×</button>
</div>
<script>setTimeout(()=>{const t=document.getElementById('flashToast');if(t)t.remove();},5000);</script>
<?php endif; ?>
