<?php
use Model\UserType;

if(!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

$css = array(
    'assets/css/admin.css'
);
include_once PUBLIC_FILES . '/modules/header.php';
include_once PUBLIC_FILES . '/modules/admin-menu.php';

?>

<div class="admin-view">
    <?php renderAdminMenu(); ?>
    <div class="admin-content">
        <p>Hello, world!</p>
    </div>
</div>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>

