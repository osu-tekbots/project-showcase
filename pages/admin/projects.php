<?php
use Model\UserType;
use DataAccess\ShowcaseProjectsDao;

if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$projects = $projectsDao->getAllProjects();
$projectHtml = '';
foreach($projects as $p) {
    $id = $p->getId();
    $title = $p->getTitle();
    $description = $p->getDescription();
    if(strlen($description) > 180) {
        $description = substr($description, 0, 180) . '...';
    }
    $created = $p->getDateCreated()->format('Y-m-d');
    $projectHtml .= "
    <tr>
        <td>$title</td>
        <td style='max-width: 400px'>$description</td>
        <td>$created</td>
        <td>
            <a href='projects/edit?id=$id' class='btn btn-sm btn-light'><i class='fas fa-edit'></i>&nbsp;&nbsp;Edit</a>
            <a href='projects/?id=$id' class='btn btn-sm btn-light'>View</a>
        </td>
    </tr>
    ";
}

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
    <?php renderAdminMenu('projects'); ?>
    <div class="admin-content">
        <div class="admin-paper">
            <table class="table" id="currentProjects">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th><th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $projectHtml; ?>
                </tbody>
            </table>
            <script>
                $('#currentProjects').DataTable({
                    columns: [
                        null,
                        null,
                        null,
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

