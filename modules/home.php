<?php 

	include_once 'errorCodeHandler.php';
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleHomeError($_GET["code"]);
	}
	
?>

<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["welcomeHeadingHome"]?></h1>
	</div>
	<p><?php echo $lang["upperTextHome"]?></p>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["login"]?></h3>
		</div>
		<div class="panel-body">
	        <form id="loginForm" class="form-signin" action="?p=auth&action=login" method="post">
	            <input type="email" name="email" id="email"  class="form-control text-input" maxlength="100" required placeholder="<?php echo $lang["yourEMail"]?>"/>
	            <input type="password" name="password" id="password"  class="form-control text-input" required maxlength="100" placeholder="<?php echo $lang["yourPassword"]?>" />
	            <input type="hidden" name="toQuiz" value="<?php echo isset($_GET["toQuiz"]) ? $_GET["toQuiz"] : '';?>">
	            <p><a href="?p=recoverPassword"><?php echo $lang["recoverPassword"]?></a></p>
	            <input type="submit" name="submit" class="btn btn-lg btn-primary btn-block" value="<?php echo $lang["login"]?>" />
			</form> 
			<div style="clear:both;" ></div>
			<p style="color:<?php echo $errorCode->getColor();?>;"><?php echo $errorCode->getText();?></p>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["registration"]?></h3>
		</div>
		<div class="panel-body">
			<p><?php echo $lang["registerTextHome"]?> <a href="?p=register" data-ajax="false"><?php echo $lang["register"]?></a>.</p>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["oldVersionHeadline"]?></h3>
		</div>
		<div class="panel-body">
			<p><?php 
			$oldVersionStr = str_replace("[0]", "<a href=\"http://mobilequiz.ch/index_2.php\">mobilequiz.ch</a>", $lang["oldVersionText"]);
			echo $oldVersionStr;?>.</p>
		</div>
	</div>
</div>