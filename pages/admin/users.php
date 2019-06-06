<?php
/**
 * Admin view of all users in the project showcase
 */
include_once '../../bootstrap.php';

use Model\UserType;
use DataAccess\ShowcaseProfilesDao;

if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

// Get all of the user profiles
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$profiles = $profilesDao->getAllProfiles();
$profilesHtml = '';
foreach ($profiles as $p) {
    $uId = $p->getUserId();
    $pFirstName = $p->getUser()->getFirstName();
    $pLastName = $p->getUser()->getLastName();
    $pIsAdmin = $p->getUser()->getType()->getId() == UserType::ADMIN ? "
        <button class='btn btn-sm btn-success btn-user-type' data-id='$uId' data-admin='true' 
            data-toggle='tooltip' data-placement='right' title='Demote to Student'>
            Admin
        </button>
    " : "
        <button class='btn btn-sm btn-light btn-user-type' data-id='$uId' data-admin='false' 
            data-toggle='tooltip' data-placement='right' title='Promote to Admin'>
            Student
        </button>
    " ;

    $profilesHtml .= "
    <tr>
        <td>$pFirstName</td>
        <td>$pLastName</td>
        <td>$pIsAdmin</td>
        <td>
            <a href='profile/edit?id=$uId' class='btn btn-sm btn-light'><i class='fas fa-edit'></i>&nbsp;&nbsp;Edit</a>
            <a href='profile/?id=$uId' class='btn btn-sm btn-light'>View</a>
        </td>
    </tr>
    ";
}

$title = 'View Users';
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
    <?php renderAdminMenu('users'); ?>
    <div class="admin-content">
        <div class="admin-paper">
            <form id="formNewUser">
                <input type="hidden" name="action" value="createProfile" />
                <div class="form-row user-form">
                    <div class="col-2">
                        <input required type="text" class="form-control" max="" name="onid" placeholder="ONID"/>
                    </div>
                    <div class="col-2">
                        <input required type="text" class="form-control" max="" name="fname" placeholder="First Name"/>
                    </div>
                    <div class="col-2">
                        <input required type="text" class="form-control" max="" name="lname" placeholder="Last Name"/>
                    </div>
                    <div class="col-2">
                        <select required class="form-control" name="type">
                            <option selected value="<?php echo UserType::STUDENT; ?>">Student</option>
                            <option value="<?php echo UserType::ADMIN; ?>">Admin</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i>&nbsp;&nbsp;New User
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="admin-paper">
            <h4>Current Users</h4>
            <p><strong>IMPORTANT</strong>: changing a user's type here will also update their type on other websites 
            under the control of the senior design capstone web development team.
            </p>
            <table class="table" id="currentUsers">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Type</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php echo $profilesHtml; ?>
            </tbody>
            </table>
            <script>
                $('#currentUsers').DataTable({
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

