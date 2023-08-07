<?php
/**
 * Admin view of all projects in the showcase
 */
include_once '../../bootstrap.php';



use Model\UserType;
use DataAccess\ShowcaseProjectsDao;
use DataAccess\CategoryDao;

if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

$categoryDao = new CategoryDao($dbConn, $logger);
$categories = $categoryDao->getAllCategories();

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$projects = $projectsDao->getAllProjects(0,0,true);
$projectHtml = '';
foreach ($projects as $p) {
    $id = $p->getId();
    $title = $p->getTitle();
    $description = $p->getDescription();
    if (strlen($description) > 180) {
        $description = substr($description, 0, 180) . '...';
    }
    $created = $p->getDateCreated()->format('Y-m-d');

	$people = $projectsDao->getProjectCollaborators($id);
	$collaborators = '';
	foreach ($people AS $peep){
		$collaborators .= $peep->getUser()->getFirstname() . " " . $peep->getUser()->getLastname() . ", ";
	}
	$collaborators = substr($collaborators, 0, -2);
	
    $published = $p->isPublished();
    if ($published) {
        $publishedButtonText = 'Published';
        $publishedButtonClass = 'btn-success';
        $publishedButtonTooltip = 'Hide';
    } else {
        $publishedButtonText = 'Hidden';
        $publishedButtonClass = 'btn-danger';
        $publishedButtonTooltip = 'Publish';
    }
    $publishedButton = "
        <button class='btn btn-sm $publishedButtonClass btn-published' data-id='$id' data-published='$published'
            data-toggle='tooltip' data-placement='left' title='$publishedButtonTooltip'>
            $publishedButtonText
        </button>
    ";
	
	$options = "<option value=>None</option>";
	foreach ($categories as $category){
		$options .= "<option value='".$category->getId()."' ".($category->getId() == $p->getCategory() ? 'selected' : '').">".$category->getName()."</option>";
	}
	
	
    $projectHtml .= "
    <tr>
        <td><strong>$title</strong><BR>$collaborators</td>
        <td style='max-width: 400px'>$description</td>
        <td>$created</td>
        <td>$publishedButton
            <a href='projects/edit?id=$id' class='btn btn-sm btn-light'><i class='fas fa-edit'></i>&nbsp;&nbsp;Edit</a>
            <a href='projects/?id=$id' class='btn btn-sm btn-light'>View</a>
			<BR><select onchange='updateCategory(\"$id\");' class='form-control' id='category$id'>$options</select>
        </td>
		<td></td>
    </tr>
    ";
}

$title = 'View Projects';
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
/*********************************************************************************
* Function Name: updateMessage(id)
* Description: Updates the content of a message.
*********************************************************************************/
function updateCategory(id) {
	var categoryID = $('#category'+id).children(":selected").attr("value");
	if (categoryID == '')
			categoryID = null;
		
	let content = {
		action: 'updateCategory',
		category: categoryID,
		projectId: id
	}
	
	api.post('/showcase-projects.php', content).then(res => {
		snackbar(res.message, 'Updated');
//		alert(res.message);
	}).catch(err => {
		snackbar(err.message, 'error');
//		alert(err.message);
	});
}
</script>

<div class="admin-view">
    <?php renderAdminMenu('projects'); ?>
    <div class="admin-content">
        <div class="admin-paper">
            <table class="table" id="currentProjects">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th></th>
                        <th><th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $projectHtml; ?>
                </tbody>
            </table>
            <script>
                $('#currentProjects').DataTable({
                    stateSave: true,
					columns: [
                        null,
                        null,
                        null,
                        {
                            "orderable": false,
                            searchable: false
                        },
                        {
                            "orderable": false,
                            searchable: false
                        },
                    ]
                });
            </script>
        </div>
    </div>
</div>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>

