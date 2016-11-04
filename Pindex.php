<?php
session_start();
include_once 'config/config.php';
setlocale(LC_ALL, 'de_DE');

$langArray = array("ger", "en");
if(!isset($_SESSION["language"]))
	$_SESSION["language"] = "ger";
if(!in_array($_SESSION["language"], $langArray))
{
	$_SESSION["language"] = "en";
}

include_once 'language/lang_' . $_SESSION["language"] . '.php';

ob_start();
$site = "home";
if(isset($_GET["p"]))
{
	if(is_file('modules/' . $_GET["p"] . '.php'))
	{
		$site = $_GET["p"];
	}
	else
	{
		$site = "404";
	}
}

include 'modules/' . $site . ".php";

$content = ob_get_contents();
ob_end_clean();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="description" content="Eine freie Plattform für die Erstellung von Lernkontrollen, an denen von mobilen Geräten teilgenommen werden kann." />
<meta name="keywords" content="HTML,CSS,JavaScript,Lernkontrolle,Quiz,Fragen,Antworten" />
<meta name="author" content="Patrick Eichler" />
<meta name="revised" content="September 2015" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>MobileQuiz - Quiz</title>
<link rel="stylesheet" type="text/css" href="css/jquery.mobile-1.4.5.min.css" />
<link rel="stylesheet" type="text/css" href="css/jquery.mobile.theme-1.4.5.min.css" />
<link rel="stylesheet" type="text/css" href="css/tipsy.css" />
<link rel="stylesheet" type="text/css" href="css/style.css" />

<script src="js/jquery-1.11.3.min.js"></script>
<script src="js/jquery.mobile-1.4.5.min.js"></script>
<script src="js/jquery.tipsy.js"></script>

</head>
<body>
	<div data-role="page">
		<?php include 'PHeader.php';?>
		<div data-role="content">
			<div class="wrapper">
				<?php 
				echo $content;
				?>
			</div>
			<div class="placeholder"></div>
		</div>
		<?php include 'PFooter.php';?>
	</div>
</body>
</html>