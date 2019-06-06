<?php
/**
 * This page houses the frontend code for the browse projects functionality of the showcase website.
 */

use DataAccess\ShowcaseProjectsDao;
use DataAccess\KeywordsDao;

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$recentProjects = $projectsDao->getMostRecentProjects(4);

$keywordsDao = new KeywordsDao($dbConn, $logger);



$title = 'Browse Showcase Projects';
$css = array(
    'assets/css/browse.css',
    'assets/css/project.css'
);
$js = array(
    array(
        'src' => 'assets/js/browse.js',
        'defer' => 'true'
    )
);
include_once PUBLIC_FILES . '/modules/header.php';

?>

<div class="browse-header justify-content-center">
    <form id="formBrowse" class="browse-input col-sm-8 col-md-4">
        <div class="input-group">
            <input type="text" class="form-control" name="query" placeholder="Search projects" />
            <div class="input-group-append">
                <button type="submit" class="btn btn-sm btn-secondary">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="container-fluid">
    <div id="results" style="display: none;">
        <div class="row projects-row">
            <div class="col">
                <h3>Search Results</h3>
            </div>
        </div>
        <div class="row projects-row" id="resultsContent">
        </div>
    </div>

    <div id="suggestions">
        <div class="row projects-row" >
            <div class="col">
                <h3>Recently Added</h3>
            </div>
        </div>
        <div class="row projects-row">
            <div class="col recent-projects">
                <?php
                include_once PUBLIC_FILES . '/modules/project.php';
                foreach ($recentProjects as $p) {
                    $keywords = $keywordsDao->getKeywordsForEntity($p->getId());
                    $p->setKeywords($keywords);
                    echo createProfileProjectHtml($p, false);
                } ?>
            </div>
        </div>
    </div>

</div>


<?
include_once PUBLIC_FILES . '/modules/footer.php';
?>