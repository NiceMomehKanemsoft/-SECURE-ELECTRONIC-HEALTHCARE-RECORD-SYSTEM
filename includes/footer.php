    <footer class="app-footer">
        <span>© <?= date('Y') ?> EHRS — Secure Electronic Healthcare Records</span>
        <span class="footer-enc">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            AES-256-GCM Protected
        </span>
    </footer>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
    <script src="<?= BASE_URL ?>/assets/js/<?= h($js) ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>
