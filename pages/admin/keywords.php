<?php
/**
 * Page to manage the keywords used by projects
 */
include_once '../../bootstrap.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Model\UserType;
use DataAccess\KeywordsDao;

if (!$isLoggedIn || $_SESSION['userType'] != UserType::ADMIN) {
    $_SESSION['error'] = 'You do not have permission to access the requested page';
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl/error');</script>";
    die();
}

$keywordsDao = new KeywordsDao($dbConn, $logger);

$title = 'Edit Keywords';
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
    <?php renderAdminMenu('keywords'); ?>
    <div class = "admin-content" style="padding-bottom: 0">
    <div class="container-fluid" style="position: relative;">
        <button class="btn btn-outline-primary" style="position: relative; left: 50%; transform: translate(-50%); margin-bottom: 10px" onclick="mergeSelected()">Merge Selected Keywords</button>
        <div class="tableWrapper" style="width: auto;  height: calc(100vh - 225px); overflow: scroll; border: 2px solid #ccc; border-radius: 5px; scrollbar-width: none; -ms-overflow-style: none">
        <!-- margin-left: 50%; transform: translate(-50%); in the css if you want a thinner table-->
            <div class="tableHead" style="background: lightgreen; padding: 6px 10px; font-weight: bold; border-bottom: 2px solid #ccc">Approved Keywords</div>
            <?php
                        $keywords = $keywordsDao->getApprovedKeywords();

                        $countUp = 0;
                        foreach($keywords as $keyword) {
                            echo '<div id="keyword'.$keyword->getId().'" class="tableRow" style="display: flex; align-items: center; width: 100%; flex-wrap: nowrap; gap: 5px;">';
                            echo '<input name="mergeCheck" type="checkbox" style="width: 16px; height: 16px;">';
                            echo '<input id="keywordText'.$keyword->getId().'" type="text" value="'.htmlspecialchars($keyword->getName()).'" style="flex: 1; min-width: 0;">';
                            echo '<p style="width: 150px; margin: .5rem 0">'.$keywordsDao->getKeywordUsedCount($keyword->getId()).' Projects Use</p>';
                            echo '<button class="btn btn-outline-danger unapproveKeyword" onclick="updateApproval('.$keyword->getId().', false)">Unapprove</button>';
                            echo '<button class="btn btn-outline-warning editKeyword" onclick="editKeyword('.$keyword->getId().')">Update</button>';
                            echo '<button class="btn btn-outline-danger deleteKeyword" onclick="removeKeyword('.$keyword->getId().')">Delete</button>';
                            echo '</div>';
                            $countUp++;
                        }
            ?>
            
            <div class="tableHead" style="margin-top: 10px; border-top: 2px solid #777; top: -2px; min-height: 37px; background: #f3f38b; padding: 6px 10px; font-weight: bold; border-bottom: 1px solid #ccc">User-Added Keywords</div>
            <?php
                $keywords = $keywordsDao->getUnapprovedKeywords();

                        $countUp = 0;
                        foreach($keywords as $keyword) {
                            echo '<div id="keyword'.$keyword->getId().'" class="tableRow" style="display: flex; align-items: center; width: 100%; flex-wrap: nowrap; gap: 5px;">';
                            echo '<input name="mergeCheck" type="checkbox" style="width: 16px; height: 16px;">';
                            echo '<input id="keywordText'.$keyword->getId().'" type="text" value="'.htmlspecialchars($keyword->getName()).'" style="flex: 1; min-width: 0;">';
                            echo '<p style="width: 150px; margin: .5rem 0">'.$keywordsDao->getKeywordUsedCount($keyword->getId()).' Projects Use</p>';
                            echo '<button class="btn btn-outline-success approveKeyword" onclick="updateApproval('.$keyword->getId().', true)">Approve</button>';
                            echo '<button class="btn btn-outline-warning editKeyword" onclick="editKeyword('.$keyword->getId().')">Update</button>';
                            echo '<button class="btn btn-outline-danger deleteKeyword" onclick="removeKeyword('.$keyword->getId().')">Delete</button>';
                            echo '</div>';
                            $countUp++;
                        }
            ?>
            
        </div>
	</div>
    </div>
    
</div>

<script>
function mergeSelected() {
    // Getting selected boxes credit: https://stackoverflow.com/a/46015793/21684315
    let selectedBoxes = document.querySelectorAll('input[type=checkbox][name=mergeCheck]:checked');

    if(selectedBoxes.length < 2) return;
    
    let keywordIds = [];
    let listKeywords = '';

    selectedBoxes.forEach(box => {
        let id = box.parentElement.id.substr(7);
        keywordIds.push(id);
        listKeywords += '\n- ' + document.getElementById('keywordText'+id).value;
    });


    if(confirm(`Are you sure you want to merge ${keywordIds.length} keywords?${listKeywords}`)) {
        let body = {
            action: 'mergeKeywords',
            keywordIds: keywordIds
        }
        api.post('/keywords.php', body).then(res => {
                // Reload in case the edit should move the keyword
                setTimeout(() => {location.reload()}, 3000);
                snackbar(res.message, 'success');
            })
            .catch(err => {
                snackbar(err.message, 'error');
            });
    }
}

function updateApproval(keywordId, approved) {
    let keywordText = document.getElementById('keywordText'+keywordId).value;

    let confirmation = 'Are you sure you want to ' + (approved ? 'approve' : 'unapprove') + ' this keyword ('+keywordText+')? ';
    confirmation += (approved ? 
        'Approved keywords show up in keyword autocomplete when editing projects and in the filter options for browsing projects.' : 
        'Unapproved keywords do not show up in keyword autocomplete when editing projects or in the filter options for browsing projects.');

    if(confirm(confirmation)) {
        let body = {
            action: 'updateApproval',
            keywordId: keywordId,
            approved: approved
        };

        api.post('/keywords.php', body).then(res => {
                // Move keyword to correct section
                setTimeout(() => {location.reload()}, 3000);
                snackbar(res.message, 'success');
            })
            .catch(err => {
                snackbar(err.message, 'error');
            });
    }
}

function editKeyword(keywordId) {
    let keywordText = document.getElementById('keywordText'+keywordId).value;

    if(!keywordText) return;

    let body = {
        action: 'editKeyword',
        keywordId: keywordId,
        keywordText: keywordText
    };

    api.post('/keywords.php', body).then(res => {
            // Reload in case the edit should move the keyword
            setTimeout(() => {location.reload()}, 3000);
            snackbar(res.message, 'success');
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });
}

function removeKeyword(keywordId) {
    let keywordText = document.getElementById('keywordText'+keywordId).value;
    
    if(confirm('Are you sure you want to delete this keyword ('+keywordText+')? This keyword will be permanently removed from all projects.')) {
        let body = {
            action: 'removeKeyword',
            keywordId: keywordId
        };
    
        api.post('/keywords.php', body).then(res => {
                document.getElementById('keyword'+keywordId).remove();
                snackbar(res.message, 'success');
            })
            .catch(err => {
                snackbar(err.message, 'error');
            });
    }
}

</script>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>