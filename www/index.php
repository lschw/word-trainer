<?php
require_once("config.php");

/**
 * Constants
 */
define("VERSION", "2.1.2");
define("PATH_ROOT", realpath(dirname(__FILE__))."/");
define("PATH_LIB", PATH_ROOT."lib/");
define("PATH_PAGES", PATH_ROOT."pages/");

define(
    "HTTP_ROOT",
    ((USE_HTTPS) ? "https" : "http")
    . "://" . $_SERVER['HTTP_HOST'] . str_replace(
        "//",
        "/",
        dirname($_SERVER["SCRIPT_NAME"]) . "/"
    )
);

define("HTTP_CSS", HTTP_ROOT."css/");


/**
 * Setup error handling
 */
error_reporting(E_ALL);
ini_set("display_errors", DEBUG);
ini_set("display_startup_errors", DEBUG);
ini_set("html_errors", DEBUG);
function my_error_handler($severity, $message, $filename, $lineno)
{
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
}
set_error_handler("my_error_handler");


/**
 * Global variables
 */
require_once(PATH_LIB."string_helper.php");
require_once(PATH_LIB."array_helper.php");
require_once(PATH_LIB."DbException.php");
require_once(PATH_LIB."Database.php");
$_db = new Database(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME, DB_PREFIX);
$_title = "";


/**
 * Load page
 */
$valid_pages = array("list", "training", "question", "answer", "error500");
$page = "";
if (isset($_GET["page"]) && in_array($_GET["page"], $valid_pages)) {
    $page = $_GET["page"];
}
$path = realpath(PATH_PAGES . (($page == "") ? "home" : $page) . ".php");
$content = "";
try {
    ob_start();
    require_once($path);
    $content = ob_get_clean();
} catch (Exception $e) {
    ob_get_clean();
    $_error = $e;
    $path = realpath(PATH_PAGES  . "error500.php");
    ob_start();
    require_once($path);
    $content = ob_get_clean();
}


/**
 * Render page
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes" />
    <title>Word Trainer<?=($_title != "") ? " - {$_title}" : ""?></title>
    <link href="<?=HTTP_CSS?>reset.css" rel="stylesheet">
    <link href="<?=HTTP_CSS?>page.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
<div id="container">
    <header>
        <a href="<?=HTTP_ROOT?>">Word Trainer v.<?=VERSION?></a>
    </header>

    <main>
    <div id="content">
<?=$content?>
    </div>
    </main>
</div>
</body>
</html>
