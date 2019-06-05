<?php
use Model\UserType;
use DataAccess\ShowcaseProfilesDao;
use DataAccess\ShowcaseProjectsDao;

if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

// Fetch all of the statistics we will need to render on this dashboard
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);

$profileStats = $profilesDao->getStatsAboutProfiles();
$totalUsers = $profileStats ? $profileStats['totalUsers'] : 0;

$projectStats = $projectsDao->getStatsAboutProjects();
$totalProjects = $projectStats ? $projectStats['totalProjects'] : 0;
$usersWithProject = $projectStats ? $projectStats['usersWithProjects'] : 0;
$keywordsInProjects = $projectStats ? $projectStats['keywords'] : array();

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
                <a class="stat-card-link" href='admin/users'>
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
                <a class="stat-card-link" href="admin/projects">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-center">
                                <span class="stat-number"><?php echo $totalProjects; ?></span> projects
                            </p>
                        </div>
                    </div>
                </a>
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

