<?php
$pageTitle = "Patient Masterlist";
require 'db.php';
include 'routes/web.php';
include 'layouts/header.php';
include 'layouts/sidebar.php';
?>

<!-- MAIN CONTENT -->
<main class="col-md-9 ms-sm-auto col-lg-10 p-0">
    <?php
    if (array_key_exists($page, $routes)) {
        include $routes[$page];  // include the module inside <main>
    } else {
        echo "<h3 class='text-danger p-4'>Under Maintenance :)</h3>";
    }
    ?>
</main>

<?php include 'layouts/footer.php'; ?>