<?php
/**
 * This header module should be included in all PHP files that render visible HTML content. It includes all the necessary
 * JavaScript and CSS files and creates the header navigation bar.
 * 
 * Before including the header file, you can specify a `$js` or `$css` variable to add additional JavaScript files
 * and CSS stylesheets to be included when the page loads in the browser. These additional files will be included
 * **after** the default scripts and styles already included in the header.
 */

if (!session_id()) {
    session_start();
}

$baseUrl = $configManager->getBaseUrl();

$title = isset($title) ? $title : 'Senior Design Capstone | OSU';


// CSS to include in the page. If you provide a CSS reference as an associative array, the keys are the
// atributes of the <link> tag. If it is a string, the string is assumed to be the href.
if (!isset($css)) {
    $css = array();
}
$css = array_merge(
    array(
        // Stylesheets to use on all pages
        array(
            'href' => 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css',
            'integrity' => 'sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T',
            'crossorigin' => 'anonymous'
        ),
        'assets/css/theme.css',
        'assets/css/layout.css',
        'assets/css/header.css',
        'assets/css/footer.css'
    ),
    $css
);

// JavaScript to include in the page. If you provide a JS reference as an associative array, the keys are the
// atributes of the <script> tag. If it is a string, the string is assumed to be the src.
if (!isset($js)) {
    $js = array();
}
$js = array_merge( 
    // Scripts to use on all pages
    array(
        array(
            'src' => 'https://code.jquery.com/jquery-3.3.1.slim.min.js',
            'integrity' => 'sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo',
            'crossorigin' => 'anonymous'
        ),
        array(
            'src' => 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js',
            'integrity' => 'sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1',
            'crossorigin' => 'anonymous'
        ),
        array(
            'src' => 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js',
            'integrity' => 'sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM',
            'crossorigin' => 'anonymous'
        ),
        'https://cdn.jsdelivr.net/npm/lodash@4.17.11/lodash.min.js'
    ), $js
);

$loggedIn = isset($_SESSION['userID']) && !empty($_SESSION['userID']);

// Setup the navigation links
$navlinks = array(
    'LOGIN' => 'pages/auth/login.php',
    'SIGNUP' => 'pages/auth/signup.php'
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?php echo $baseUrl ?>" />
    <title><?php echo $title; ?></title>

    <?php
    // Include the CSS Stylesheets
    foreach ($css as $style) {
        if (!is_array($style)) {
            echo "<link rel=\"stylesheet\" href=\"$style\" />";
        } else {
            $link = '<link rel="stylesheet" ';
            foreach ($style as $attr => $value) {
                $link .= $attr . '="' . $value . '" ';
            }
            $link .= '/>';
            echo $link;
        }
    } 
    
    // Include the JavaScript files
    foreach ($js as $script) {
        if (!is_array($script)) {
            echo "<script type=\"text/javascript\" src=\"$script\"></script>";
        } else {
            $link = '<script type="text/javascript" ';
            foreach ($script as $attr => $value) {
                $link .= $attr . '="' . $value . '" ';
            }
            $link .= '></script>';
            echo $link;
        }
    } ?>

</head>
<body>
    <header id="header" class="dark">
        <div class="logo">
            <img class="logo" src="assets/img/osu-logo-orange.png" />
            <h1><span id="projectPrefix">Project </span>Showcase</h1>
        </div>
        <nav class="navigation">
            <ul>
            <?php 
            foreach ($navlinks as $title => $link) {
                echo "
                <a href='$link'><li>$title</li></a>
                ";
            }
            ?>
            </ul>
        </nav>
    </header>
    <main>