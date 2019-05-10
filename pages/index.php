<?php

$heroButtonHtml = $isLoggedIn ? "
    <a href='profile/' class='btn btn-lg btn-primary'>
    <i class='fas fa-user'></i>&nbsp;&nbsp;View Your Profile
    </a>
" : "
    <a href='signin' class='btn btn-lg btn-primary'>
        <i class='fas fa-sign-in-alt'></i>&nbsp;&nbsp;Sign In
    </a>
";
$css = array(
    'assets/css/home.css'
);
include_once PUBLIC_FILES . '/modules/header.php';
?>

<div class="hero-home">
    <h1 class="hero-title">Where Education Meets Application</h1>
    <p class="hero-subtitle">Show employers you have what it takes</p>
    <p><?php echo $heroButtonHtml; ?></p>
</div>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <p class="text-center">The Oregon State University EECS Project Showcase assists students in obtaining 
                internships and full-time employment by providing the opportunity to build a portfolio of projects 
                they have completed.
            </p>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-3 offset-md-4">
            
        </div>
        <div class="col-md-4">

        </div>
    </div>
</div>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>