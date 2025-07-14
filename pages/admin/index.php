<?php
/**
 * Main admin dashboard view of statistics about the project showcase website
 */
include_once '../../bootstrap.php';

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

use Model\UserType;
use DataAccess\ShowcaseProfilesDao;
use DataAccess\ShowcaseProjectsDao;
use DataAccess\CategoryDao;

if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

// Fetch all of the statistics we will need to render on this dashboard
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$categoryDao = new CategoryDao($dbConn, $logger);

$profileStats = $profilesDao->getStatsAboutProfiles();
$topUsers = $profilesDao->getTopProfiles();
$totalUsers = $profileStats ? $profileStats['totalUsers'] : 0;

$projectStats = $projectsDao->getStatsAboutProjects();
$totalProjects = $projectStats ? $projectStats['totalProjects'] : 0;
$usersWithProject = $projectStats ? $projectStats['usersWithProjects'] : 0;
$keywordsInProjects = $projectStats ? $projectStats['keywords'] : array();
$categories = $categoryDao->getAllCategories();
$projects = $projectsDao->getAllProjects();


$category_array = Array();
foreach ($projects AS $p){
	$category = $p->getCategory();
	if (!array_key_exists($category, $category_array))
		$category_array[$category] = 1;
	else
		$category_array[$category]++;
}


$categoryHTML = '';
foreach ($categories AS $category){
	$categoryHTML .= "" . $category->getName() . ": " . (($category_array[$category->getId()]) ? ($category_array[$category->getId()]) : "0") . "<BR>";
}

$topUsersHTML = '';
$keys = array_keys($topUsers); 
for ($i=0;$i<6;$i++){
	$user = $profilesDao->getUserProfileInformation($topUsers[$i]['user_id']);
	$topUsersHTML .= "<div class='row'><div class='col-6'>" . $user->getUser()->getFirstname() . " " . $user->getUser()->getLastname() . ":</div><div class='col-6'>".$topUsers[$i]['project_count']." projects</div></div>";
}

// Build the keywords chart data
$keywordLabels = '';
$keywordData = '';
foreach ($keywordsInProjects as $name => $count) {
    $keywordLabels .= "'$name'" . ',';
    $keywordData .= $count . ',';
}
$keywordsJs = "
{
    datasets: [{
        backgroundColor: 'rgba(220, 69, 4, 1)',
        label: 'Count',
        data: [$keywordData]
    }],
    labels: [
        $keywordLabels
    ]
}
";

$title = 'Admin Dashboard';
$css = array(
    'assets/css/admin.css'
);
$js = array(
    'https://cdn.jsdelivr.net/npm/chart.js@2.8.0/dist/Chart.min.js'
);
include_once PUBLIC_FILES . '/modules/header.php';
include_once PUBLIC_FILES . '/modules/admin-menu.php';

?>

<div class="admin-view">
    <?php renderAdminMenu(); ?>
    <div class="admin-content">
        <div class="row">
            <div class="col-md-3 col-stat">
                <a class="stat-card-link" href='admin/users.php'>
                    <div class="card">
                        <div class="card-body">
                            <p class="text-center">
                                <span class="stat-number"><?php echo $totalUsers; ?></span> users
                            </p>
                            <canvas id="statUsers"></canvas>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-stat">
                <a class="stat-card-link" href="admin/projects.php">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-center">
                                <span class="stat-number"><?php echo $totalProjects; ?></span> projects
                            </p>
                        </div>
                    </div>
                </a>
            </div>
			<div class="col-md-3 col-stat">
				<div class="card">
					<div class="card-body">
						<p class="text-center"><span class='stat-number'>Projects By Category</span></p>
						<p>
							<?php
							echo $categoryHTML;
							?>
						</p>
					</div>
				</div>
            </div>
			<div class="col-md-3 col-stat">
				<div class="card">
					<div class="card-body">
						<p class="text-center"><span class='stat-number'>Top Contributors</span></p>
						<p>
							<?php
							echo $topUsersHTML;
							?>
						</p>
					</div>
				</div>
            </div>
        </div>
        <div class="row">
            <div class="col col-stat">
                <div class="card">
                    <div class="card-body">
                        <h4>Keywords in Projects</h4>
                        <canvas id="statKeywords" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let keywordCtx = document.getElementById('statKeywords').getContext('2d');
let keywordBar = new Chart(keywordCtx, {
    type: 'bar',
    data: <?php echo $keywordsJs; ?>,
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepValue: 1
                }
            }]
        }
    }
});

let usersCtx = document.getElementById('statUsers').getContext('2d');
let usersDoughnut = new Chart(usersCtx, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?php echo $usersWithProject ?>, <?php echo $totalUsers - $usersWithProject; ?>],
            backgroundColor: [
                'rgba(220, 69, 4, 1)',
                'rgba(204, 204, 204, 1)'
            ]
        }],
        labels: [
            'Users with projects',
            'Users without projects'
        ]
    }
});
console.log(usersDoughnut);
</script>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>

