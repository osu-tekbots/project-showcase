<?php
/**
 * Admin view of all projects in the showcase
 */
include_once '../../bootstrap.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Model\UserType;
use DataAccess\AwardDao;

if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

$awardsDao = new AwardDao($dbConn, $logger);
$awards = $awardsDao->getAllAwards();
$awardsHtml = '';

foreach ($awards as $a) {	
    $id = $a->getId();
    $name = $a->getName();
    $description = $a->getDescription();
	$imageNameSquare = $a->getImageNameSquare();
    $imageNameRectangle = $a->getImageNameRectangle();
    
	$recipientHTML = '';
	$recipients = $awardsDao->getAwardRecipients($id);
				
	foreach ($recipients as $r)
		$recipientHTML .= $r->getTitle() . '<BR>';
	
	$awardsHtml .= "
    <tr>
        <td><strong>$name</strong></td>
        <td style='max-width:400px;'>$description</td>
        <td>$recipientHTML</td>
		<td></td>
    </tr>
    ";
	
}

$title = 'Edit Awards';
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

<div class="admin-view">
    <?php renderAdminMenu('awards'); ?>
    <div class="admin-content">
        <div class="admin-paper">
            <form id="formNewAward">
                <input type="hidden" name="action" value="createAward" />
                <div class="form-row user-form">
                    <div class="col-2">
                        <input required type="text" class="form-control" max="" name="name" placeholder="Name"/>
                    </div>
                    <div class="col-8">
                        <input required type="text" class="form-control" max="" name="description" placeholder="Description"/>
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i>&nbsp;&nbsp;New Award
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="admin-paper">
            <table class="table" id="currentAwards">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Recipients</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $awardsHtml; ?>
                </tbody>
            </table>
            <script>
                $('#currentAwards').DataTable({
                    stateSave: true,
					columns: [
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
                        {
                            "orderable": false,
                            searchable: false
                        },
						{
                            "orderable": false,
                            searchable: false
                        }
                    ]
                });
            </script>
        </div>
    </div>
</div>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>

