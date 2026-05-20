<?php
// Main layout wrapper
?>
<?php include __DIR__ . '/header.php'; ?>
<main id="main-content">
	<?php echo $content ?? ''; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
