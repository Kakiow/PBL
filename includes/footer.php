</main>
<footer>
    <p>SIMKAS &copy; <?php echo date('Y'); ?></p>
</footer>
<?php $page_js = $page_js ?? []; ?>
<script src="<?php echo $base; ?>assets/js/script.js"></script>
<?php foreach ($page_js as $js): ?>
<script src="<?php echo $base . 'assets/js/' . e($js); ?>"></script>
<?php endforeach; ?>
</body>

</html>