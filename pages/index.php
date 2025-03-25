<?php
/**
 * Home page for the project showcase website.
 */
include_once '../bootstrap.php';

$heroButtonHtml = $isLoggedIn ? "
    <a href='browse.php' class='btn btn-lg btn-primary'>
    <i class='fas fa-user'></i>&nbsp;&nbsp;Browse Projects
    </a>&nbsp;&nbsp;
	<a href='profile/index.php' class='btn btn-lg btn-success'>
    <i class='fas fa-user'></i>&nbsp;&nbsp;View Your Profile
    </a>
" : "
    <a href='browse.php' class='btn btn-lg btn-primary'>
    <i class='fas fa-user'></i>&nbsp;&nbsp;Browse Projects
    </a>
";
$css = array(
    'assets/css/home.css'
);
include_once PUBLIC_FILES . '/modules/header.php';
?>

<div class="hero-home">
    <h1 class="hero-title">Showcase Your Engineering Project</h1>
    <p class="hero-subtitle">Show employers you have what it takes</p>
    <p><?php echo $heroButtonHtml; ?></p>
</div>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
			<p>
			This is your platform to create an impressive portfolio that highlights your engineering projects and achievements. Here, you can demonstrate the quality of your work, effectively communicate your skills, and make a lasting impression on future employers.
			</p>
			<p class="hero-subtitle">Why Create a Portfolio and Add Your Projects?</p>
			<ul style="font-size: 1.3rem;">
			<li>Showcase your talent: Highlight your best projects and engineering solutions, demonstrating to potential employers the breadth of your skills and creativity.</li>
			<li>Communicate your expertise: Utilize your portfolio to clarify the technical details and impact of your work, allowing industry professionals to easily grasp your capabilities.</li>
			<li>Stand out in the job market: An effectively crafted portfolio distinguishes you from other candidates, providing you with a competitive advantage in securing internships and full-time positions.</li>
			</ul>
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