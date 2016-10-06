<?php 
	if($_SESSION["role"]["user"] != 1)
	{
		header("Location: ?p=home&code=-20");
		exit;
	}
?>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["keywordsHeadline"];?></h1>
	</div>
	<p></p>
</div>