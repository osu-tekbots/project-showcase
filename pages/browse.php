<?php
/**
 * This page houses the frontend code for the browse projects functionality of the showcase website.
 */
include_once '../bootstrap.php';

use DataAccess\ShowcaseProjectsDao;
use DataAccess\CategoryDao;
use DataAccess\KeywordsDao;

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$categoryDao = new CategoryDao($dbConn, $logger);
// $recentProjects = $projectsDao->getMostRecentProjects(20);
$liftedProjects = $projectsDao->getAllProjectsSortByScore(10);

if (isset($_REQUEST['all'])){
	$allProjects = $projectsDao->getAllProjects();
} else {
	$allProjects = $projectsDao->getAllRecentlyCreatedProjects();
}

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

if (isset($_REQUEST['category'])){
		//	echo "<h4>Category Found: ".$_REQUEST['category']."</h4>";
	if ($_REQUEST['category'] != ''){
		$categoryProjects = $projectsDao->getProjectsByCategory($_REQUEST['category']);
		$category = $categoryDao->getCategoryByShortName($_REQUEST['category']);
	}
}

?>

<div class="browse-header justify-content-center">
    <form id="formBrowse" class="browse-input col-sm-8 col-md-4">
        <div class="input-group col-sm-8 col-md-12">
            <input type="text" class="form-control" name="query" placeholder="Search projects" />
            <div class="input-group-append">
                <button type="submit" class="btn btn-sm btn-secondary">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
		<div id="loading" class="spinner-border" style="display: none;">
			<span style="opacity: 0;">Loading...</span>
		</div>
    </form>
	<form id="formFilter" class="browse-input">
		<div class="row">
		<div class="col-sm-12 col-md-6 justify-content-center" >
			<select class="w-100 form-control m-1" name="category" onchange="this.form.submit()" aria-label="Default select example">
				<option value=''>Any Category</option>
				<?php
					$categories = $categoryDao->getAllCategories();
					foreach($categories as $currCategory) {
						if (isset($_REQUEST['category'])){
							if ($_REQUEST['category'] == $currCategory->getShortName()){
								echo ("<option value='".$currCategory->getShortName()."' selected>".$currCategory->getName()."</option>");
							} else {
								echo ("<option value='".$currCategory->getShortName()."'>".$currCategory->getName()."</option>");
							}
						} else {
							echo ("<option value='".$currCategory->getShortName()."'>".$currCategory->getName()."</option>");
						}
					}
				?>
			</select>
		</div>
		<div class="col-sm-12 col-md-6 justify-content-center">
			<select class="w-100 form-control m-1" name="keyword" onchange="this.form.submit()" aria-label="Default select example">
				<option value=''>Any Keyword</option>
				<?php
					$keywords = $keywordsDao->getAllKeywords();
					foreach($keywords as $currKeyword) {
						if (isset($_REQUEST['keyword'])){
							if ($_REQUEST['keyword'] == $currKeyword->getName()){
								echo ("<option value='".$currKeyword->getName()."' selected>".$currKeyword->getName()."</option>");
							} else {
								echo ("<option value='".$currKeyword->getName()."'>".$currKeyword->getName()."</option>");
							}
						} else {
							echo ("<option value='".$currKeyword->getName()."'>".$currKeyword->getName()."</option>");
						}
					}
				?>
			</select>
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
                <?php echo (isset($categoryProjects) ? '<h3>'.$category->getName().' Projects</h3>' : '')?>
            </div>
        </div>
        <div class="row projects-row">
            <div class="col recent-projects">
                <?php
                include_once PUBLIC_FILES . '/modules/project.php';
                if (isset($categoryProjects)){
					foreach ($categoryProjects as $p) {
						$keywords = $keywordsDao->getKeywordsForEntity($p->getId());
						$awards = $projectsDao->getProjectAwards($p->getId());
						$p->setKeywords($keywords);
						$p->setAwards($awards);
						$keywordInProject = false;
						if (isset($_REQUEST['keyword'])){
							if ($_REQUEST['keyword'] != '') {
								foreach ($keywords as $keyword) {
									if ($_REQUEST['keyword'] == $keyword->getName()){
										$keywordInProject = true;
									}
								}
								if ($keywordInProject) {
									echo createProfileProjectHtml($p, false);
								}
							} else {
								echo createProfileProjectHtml($p, false);
							}
						} else {
							echo createProfileProjectHtml($p, false);
						}
					} 
				} 
				// else {
				// 	foreach ($recentProjects as $p) {
				// 		$keywords = $keywordsDao->getKeywordsForEntity($p->getId());
				// 		$awards = $projectsDao->getProjectAwards($p->getId());
				// 		$p->setKeywords($keywords);
				// 		$p->setAwards($awards);
				// 		echo createProfileProjectHtml($p, false);
				// 	} 
				// }
					?>
            </div>
        </div>
		
		<div class="row projects-row" <?php if($liftedProjects == false) {echo "hidden";} else{echo "";} ?>>
            <div class="col">
                <h3>Lifted Projects</h3>
            </div>
        </div>
        <div class="row projects-row">
            <div class="col recent-projects">
                <?php
                include_once PUBLIC_FILES . '/modules/project.php';
                
					foreach ($liftedProjects as $p) {
						$keywords = $keywordsDao->getKeywordsForEntity($p->getId());
						$awards = $projectsDao->getProjectAwards($p->getId());
						$p->setKeywords($keywords);
						$p->setAwards($awards);
						$keywordInProject = false;
						if (isset($_REQUEST['keyword'])){
							if ($_REQUEST['keyword'] != '') {
								foreach ($keywords as $keyword) {
									if ($_REQUEST['keyword'] == $keyword->getName()){
										$keywordInProject = true;
									}
								}
								if ($keywordInProject) {
									echo createProfileProjectHtml($p, false);
								}
							} else {
								echo createProfileProjectHtml($p, false);
							}
						} else {
							echo createProfileProjectHtml($p, false);
						}
					} 
				
					?>
            </div>
        </div>
        
<?php		
		if (!(isset($categoryProjects))){
			echo '<div class="row projects-row" >
				<div class="col">
					' . (isset($_REQUEST['all']) ? '<h3>All Projects</h3>' : '<h3>Recent Projects (Last 24 Months)</h3> <a href="/browse.php?all" class="btn btn-outline-osu">Show All</a>'). 
				'</div>
			</div>
			<div class="row projects-row" >
				<div class="col recent-projects">';
					
					foreach ($allProjects as $p) {
						$keywords = $keywordsDao->getKeywordsForEntity($p->getId());
						$awards = $projectsDao->getProjectAwards($p->getId());
						$p->setKeywords($keywords);
						$p->setAwards($awards);
						$keywordInProject = false;
						if (isset($_REQUEST['keyword'])){
							if ($_REQUEST['keyword'] != '') {
								foreach ($keywords as $keyword) {
									if ($_REQUEST['keyword'] == $keyword->getName()){
										$keywordInProject = true;
									}
								}
								if ($keywordInProject) {
									echo createProfileProjectHtml($p, false);
								}
							} else {
								echo createProfileProjectHtml($p, false);
							}
						} else {
							echo createProfileProjectHtml($p, false);
						}
					} 
				   
			echo '    </div>
			</div>';
		}
?>
		
		
    </div>
    

</div>


<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>