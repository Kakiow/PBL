            </main>
            <footer class="app-footer">
                <p>KasKelas &copy; <?php echo date('Y'); ?></p>
            </footer>
        </div>
    </div>
    <?php $page_js = $page_js ?? []; ?>
    <script src="<?php echo $base; ?>assets/js/script.js"></script>
    <?php foreach ($page_js as $js): ?>
    <script src="<?php echo $base . 'assets/js/' . e($js); ?>"></script>
    <?php endforeach; ?>
</body>

</html>
