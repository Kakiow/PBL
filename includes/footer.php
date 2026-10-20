            </main>
            <footer class="app-footer">
                <p>SIMKAS &copy; <?php echo date('Y'); ?></p>
            </footer>
        </div>
    </div>
    <?php $page_js = $page_js ?? []; ?>
    <div class="toast-area" id="toast-area"></div>
    <script src="<?php echo aset($base, 'assets/js/toast.js'); ?>"></script>
    <script src="<?php echo aset($base, 'assets/js/sidebar.js'); ?>"></script>
    <script src="<?php echo aset($base, 'assets/js/script.js'); ?>"></script>
    <?php foreach ($page_js as $js): ?>
    <script src="<?php echo aset($base, 'assets/js/' . e($js)); ?>"></script>
    <?php endforeach; ?>
</body>

</html>
