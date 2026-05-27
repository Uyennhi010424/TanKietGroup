<?php
// Renders favicon and touch icon links using settings values.
// Expects includes/site.php to be loaded so site_favicon_url/site_logo_url are available.
if (!function_exists('site_favicon_url')) {
    return;
}
$fav = htmlspecialchars(site_favicon_url(), ENT_QUOTES, 'UTF-8');
$logo = htmlspecialchars(site_logo_url('/img/logo.jpg'), ENT_QUOTES, 'UTF-8');
?>
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $fav; ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $fav; ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $logo; ?>">
<link rel="shortcut icon" href="<?php echo $fav; ?>">
