<?php
/**
 * Admin view of all projects in the showcase
 */
include_once '../../bootstrap.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Model\UserType;
use DataAccess\CategoryDao;
use DataAccess\ShowcaseProjectsDao;

$baseUrl = $configManager->getBaseUrl();
if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$categoryDao = new CategoryDao($dbConn, $logger);
$categories = $categoryDao->getAllCategories();
$categoriesHTML = '';

foreach ($categories as $c) {	
    $id = $c->getId();
    $name = $c->getName();
    $shortname = $c->getShortName();
	$projectCount = count($projectsDao->getProjectsByCategory($shortname));
    
	
	$categoriesHTML .= "
    <tr>
        <td><strong>$name</strong></td>
        <td><a href='$baseUrl/browse.php?category=$shortname'>$shortname</a></td>
        <td>$projectCount</td>
    </tr>
    ";
	
}

$title = 'Edit Categories';
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

function addCategory(e){
	//here I want to prevent default
    e = e || window.event;
    e.preventDefault();
	
	let name = $('#name').val().trim();
	let shrtname =  $('#shrtname').val().trim();
	let data = {
		name: name,
		shrtname: shrtname,
		action: 'createCategory'
	};

	if (name !='' && shrtname != ''){
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
    <?php renderAdminMenu('categories'); ?>
    <div class="admin-content">
        <div class="admin-paper">
			<div class="form-row">
				<form class="form-inline" id="newCategory">
				<div class="col"><input type="text" id="name" class="form-control" placeholder="Display Name"></div>
				<div class="col"><input type="text" pattern="[A-Za-z0-9]{4,10}" id="shrtname" class="form-control" placeholder="Short Name" title="4-10 characters, no symbols, no spaces."></div>
				<button onclick="addCategory(event)" id="addCategoryID" class="btn btn-primary">Add Category</button>
				</form>
			</div>
		</div>
		<div class="admin-paper">		
            <table class="table" id="currentCategories">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Short Name</th>
                        <th>Projects in<BR>this Category</th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $categoriesHTML; ?>
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

