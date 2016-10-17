<?php 

	//TODO: Extract to File 'HandleCode'	

	$code = 0;
	$codeTxt = "";
	$color = "";
	if(isset($_GET["code"]))
	{
		$code = $_GET["code"];
		
		$color = "red";
		if($code > 0)
			$color = "green";
		switch ($code)
		{
			case 1:
				$codeTxt = "Anmeldung erfolgreich!<br />Bitte &uuml;berpr&uuml;fen Sie ihre E-Mails und aktivieren Sie ihren Account um sich einloggen zu k&ouml;nnen.";
				break;
			case 2:
				$codeTxt = "Aktivierung erfolgreich!<br />Sie k&ouml;nnen sich nun mit Ihren Anmeldedaten anmelden.";
				break;
			case 3:
				$codeTxt = "Ihnen wurde eine E-Mail gesendet.";
				break;
			case 4:
				$codeTxt = "Ihnen wurde eine E-Mail mit einem neuen Passwort zugesendet.";
				break;
			case 5:
				$codeTxt = "Sie wurden ausgeloggt.";
				break;
			case -1:
			case -2:
				$codeTxt = "Aktivierung fehlgeschlagen (Code: " . htmlspecialchars($code) . ")";
				break;
			case -6:
				$codeTxt = "Senden der Passwort zur&uuml;cksetzen E-Mail fehlgeschlagen.";
				break;
			case -7:
				$codeTxt = "Passwort reaktivierung fehlgeschagen.";
				break;
			case -9:
				$codeTxt = "E-Mail nicht gefunden.";
				break;
			case -10:
				$codeTxt = "Key nicht gefunden.";
				break;
			case -16:
				$codeTxt = "Benutzer oder Passwort nicht gefunden.";
				break;
			case -17:
				$codeTxt = "Account noch nicht aktiviert.<br />Sollten Sie keine Best&auml;tigungsmail bekommen haben klicken Sie <a href=\"?p=auth&action=resendVerification&email=" . $_GET["email"] . "\">hier</a>.";
				break;
			case -18:
				$codeTxt = "Versenden der E-Mail fehlgeschlagen.";
				break;
			case -20:
				$codeTxt = "Sie sind nicht eingeloggt.";
				break;
			case -3:
			case -4:
			case -5:
			case -8:
			case -11:
			case -12:
			case -13:
			case -14:
			case -15:
			default:
				$codeTxt = "Allgemeiner Fehler (Code " . htmlspecialchars($code) . ")";
				break;
		}
	}
?>
<script type="text/javascript">

	function checkFields()
	{
		if(('#email').val().length < 1 || ('#password').val().length < 1)
		{
			return false;
		}
		return true;
	}

</script>
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
	        <form id="loginForm" class="form-signin" action="?p=auth&action=login" method="post" onsubmit="return checkFields()">
	            <input type="text" name="email" id="email"  class="form-control text-input" maxlength="100" value="" required="required" placeholder="<?php echo $lang["yourEMail"]?>"/>
	            <input type="password" name="password" id="password"  class="form-control text-input" value="" required="required" maxlength="100" placeholder="<?php echo $lang["yourPassword"]?>" />
	            <input type="hidden" name="toQuiz" value="<?php echo isset($_GET["toQuiz"]) ? $_GET["toQuiz"] : '';?>">
	            <p><a href="?p=recoverPassword"><?php echo $lang["recoverPassword"]?></a></p>
	            <input type="submit" name="submit" class="btn btn-lg btn-primary btn-block" value="<?php echo $lang["login"]?>" />
			</form> 
			<div style="clear:both;" ></div>
			<p style="color:<?php echo $color;?>;"><?php echo $codeTxt;?></p>
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