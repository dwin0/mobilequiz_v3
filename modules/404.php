<?php 
	include_once 'modules/extraFunctions.php';
	
	addEvent($dbh, "404", $_SERVER["REQUEST_URI"] . " userId: " . $_SESSION["id"] .  " from: " . $_SERVER["HTTP_REFERER"]);
?>
<div class="container theme-showcase">
	<div class="page-header">
		<h1>404 - Oops!</h1>
	</div>
</div>