<?php

/**
 * Renders the HTML for the sidebar menu on the Admin dashboard pages
 * @return void
 */
function renderAdminMenu($active = 'dashboard') {
    $menuitems = array(
        'dashboard' => array(
            'icon' => 'fas fa-chart-line',
            'link' => 'admin/',
            'title' => 'Dashboard'
        ),
        'users' => array(
            'icon' => 'fas fa-user',
            'link' => 'admin/users.php',
            'title' => 'Users'
        ),
        'projects' => array(
            'icon' => 'fas fa-project-diagram',
            'link' => 'admin/projects.php',
            'title' => 'Projects'
        ),
        'awards' => array(
            'icon' => 'fas fa-project-diagram',
            'link' => 'admin/awards.php',
            'title' => 'Edit Awards'
        ),
		'categories' => array(
            'icon' => 'fas fa-project-diagram',
            'link' => 'admin/categories.php',
            'title' => 'Edit Categories'
        )
    );
    
    echo "
    <div class='admin-menu'>
        <ul>
    ";

    foreach ($menuitems as $name => $item) {
        $icon = $item['icon'];
        $link = $item['link'];
        $title = $item['title'];
        $style = $active == $name ? 'active' : '';
        echo "<a href='$link'><li class='$style'><span><i class='$icon'></i></span>$title</li></a>";
    }

    echo '
        </ul>
    </div>
    ';
}
