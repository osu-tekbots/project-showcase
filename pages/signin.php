<?php
$title = 'Showcase Signup';
$js = array(
    'assets/js/signin.js'
);
include_once PUBLIC_FILES . '/modules/header.php';
?>

<div class="container-fluid">
    <div class="col-4">
        <a href="auth/signin.php?provider=onid">
            <button class="btn btn-outline-osu">
                <i class="fas fa-university"></i>&nbsp;&nbsp;Login with ONID
            </button>
        </a>
        <a href="auth/signin.php?provider=google">
            <button class="btn btn-outline-success">
                <i class="fab fa-google"></i>&nbsp;&nbsp;Login with Google
            </button>
        </a>
    </div>
    <div class="col-8">

    </div>
</div>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>