            </main>
            <footer class="app-footer">
                <p>SIMKAS &copy; <?php echo date('Y'); ?></p>
            </footer>
        </div>
    </div>
    <?php $page_js = $page_js ?? []; ?>
    <dialog class="app-dialog" id="dialog-logout" aria-labelledby="judul-logout">
        <div class="form-card app-dialog-card">
            <button type="button" class="app-dialog-close" data-tutup-logout aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>
            <h2 id="judul-logout">Keluar dari akun?</h2>
            <p class="form-hint">Sesi kamu akan diakhiri dan kamu perlu masuk kembali untuk mengakses SIMKAS.</p>
            <div class="form-actions">
                <button class="btn" type="button" data-tutup-logout>Batal</button>
                <a class="btn danger" href="<?php echo $base; ?>auth/logout.php">Ya, keluar</a>
            </div>
        </div>
    </dialog>
    <div class="toast-area" id="toast-area"></div>
    <script src="<?php echo aset($base, 'assets/js/toast.js'); ?>"></script>
    <script src="<?php echo aset($base, 'assets/js/sidebar.js'); ?>"></script>
    <script src="<?php echo aset($base, 'assets/js/script.js'); ?>"></script>
    <?php foreach ($page_js as $js): ?>
    <script src="<?php echo aset($base, 'assets/js/' . e($js)); ?>"></script>
    <?php endforeach; ?>
</body>

</html>
