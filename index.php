<?php session_start();
include_once 'config/config.php';

/*
if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
	$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $redirect);
	exit();
}
*/

setlocale(LC_ALL, 'de_DE', 'deu_deu');

$langArray = array("ger", "en");
if(!isset($_SESSION["language"]))
	$_SESSION["language"] = "ger";
if(!in_array($_SESSION["language"], $langArray))
{
	$_SESSION["language"] = "en";
}

include_once 'language/lang_' . $_SESSION["language"] . '.php';

if(isset($_GET["quiz"]))
{
	$stmt = $dbh->prepare("select id from questionnaire where qnaire_token = :qToken");
	$stmt->bindParam(":qToken", $_GET["quiz"]);
	if(!$stmt->execute())
	{
		header("Location: ?p=quiz&code=-15");
		exit;
	}
	$fetchToken = $stmt->fetch(PDO::FETCH_ASSOC);
	header("Location: Pindex.php?p=participationIntro&quizId=" . $fetchToken["id"]);
	exit;
}

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
<!DOCTYPE html>
<html>
<head>
<meta name="description" content="Eine freie Plattform für die Erstellung von Lernkontrollen, an denen von mobilen Geräten teilgenommen werden kann." />
<meta name="keywords" content="HTML,CSS,JavaScript,Lernkontrolle,Quiz,Fragen,Antworten" />
<meta name="author" content="Patrick Eichler" />
<meta name="revised" content="September 2015" />
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
<title>MobileQuiz</title>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css" />
<link rel="stylesheet" type="text/css" href="css/jquery-ui.min.css" />
<link rel="stylesheet" type="text/css" href="css/sticky-footer-navbar.css" />
<link rel="stylesheet" type="text/css" href="js/css/jquery.powertip.min.css" />
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrap-tagsinput.css" />
<link rel="stylesheet" type="text/css" href="css/tipsy.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrap-datepicker3.min.css" />
<link rel="stylesheet" type="text/css" href="css/style.css" />

<script src="js/jquery-1.11.3.min.js"></script>
<script src="js/bootstrap.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/jquery.powertip.min.js"></script>
<script src="js/jquery.dataTables.columnFilter.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.js"></script>
<script src="js/dataTables.responsive.min.js"></script>
<script src="js/jquery.tipsy.js"></script>
<script src="js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap-tagsinput.min.js"></script>

<script src="locales/bootstrap-datepicker.de.min.js"></script>

<!-- PhotoSwipe -->
<link rel="stylesheet" href="PhotoSwipe/photoswipe.css"> 
<link rel="stylesheet" href="PhotoSwipe/default-skin/default-skin.css"> 
<script src="PhotoSwipe/photoswipe.min.js"></script> 
<script src="PhotoSwipe/photoswipe-ui-default.min.js"></script>

</head>
<body>
<div id="wrap">
	<?php include 'navHeader.php';?>
	<?php 
		echo $content;
	?>
</div>
<?php include 'footer.php';?>
</body>
</html>