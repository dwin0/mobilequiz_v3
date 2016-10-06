<?php 

	$fromSite = "home";
	if(isset($_GET["action"]))
	{
		$action = $_GET["action"];
	}
	if(isset($_GET["fromsite"]) && $_GET["fromsite"] != "")
	{
		$fromSite = $_GET["fromsite"];
	}
	//----------
	
	if($action == 'lang')
	{
		$locale = "";
		if(isset($_GET["locale"]))
		{
			$locale = $_GET["locale"];
		}
		$_SESSION["language"] = $locale;
		header("Location: " . $_SERVER['HTTP_REFERER']);
	}
?>