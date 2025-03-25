<?php
/**
 * Admin view of all projects in the showcase
 */
include_once '../../bootstrap.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Model\UserType;
use DataAccess\FlagDao;
use DataAccess\ShowcaseProjectsDao;

$baseUrl = $configManager->getBaseUrl();
if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$flagDao = new FlagDao($dbConn, $logger);
$flags = $flagDao->getAllFlags();
$outputHTML = '';

foreach ($flags as $c) {	
    $id = $c->getId();
    $description = $c->getDescription();
    $active = $c->getActive();
	$dateCreated = $c->getDateCreated();
	
	
	$outputHTML .= "
    <tr>
        <td><strong>$description</strong></td>
        <td>$dateCreated</td>
        <td>$active</td>
		<td></td>
		<td></td>
    </tr>
    ";
	
}

$title = 'Edit Flags';
$css = array(
    'assets/css/admin.css',
    'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'
);
$js = array(
    array(
        'src' => 'assets/js/admin.js',
        'defer' => 'true'
    ),
    'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js'
);
include_once PUBLIC_FILES . '/modules/header.php';
include_once PUBLIC_FILES . '/modules/admin-menu.php';

?>
<script type='text/javascript'>
function testing(){
	alert('Caught Submit');
}

function addFlag(e){
	//here I want to prevent default
    e = e || window.event;
    e.preventDefault();
	
	let name = $('#name').val().trim();
	let data = {
		name: name,
		action: 'createFlag'
	};

	if (name !=''){
		api.post('/showcase-projects.php', data).then(res => {
			snackbar(res.message, 'info');
			location.reload();
		}).catch(err => {
			snackbar(err.message, 'error');
		});
	} else {
		snackbar('One or more fields are empty.', 'error');
	}
}
</script>

<div class="admin-view">
    <?php renderAdminMenu('flags'); ?>
    <div class="admin-content">
        <div class="admin-paper">
			<div class="form-row">
				<form class="form-inline" id="newCategory">
				<div class="col"><input type="text" id="name" class="form-control" placeholder="Flag Name"></div>
				<button onclick="addFlag(event)" id="addFlagID" class="btn btn-primary">Add Flag</button>
				</form>
			</div>
		</div>
		<div class="admin-paper">		
            <table class="table" id="currentCategories">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Active</th>
						<th>Projects</th>
						<th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $outputHTML; ?>
                </tbody>
            </table>
            <script>
                $('#currentCategories').DataTable({
                    stateSave: true,
					paging: false,
					columns: [
                        null,
                        null,
                        null
                    ]
                });
            </script>
        </div>
    </div>
</div>



<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>

