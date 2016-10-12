<?php 	
	$mailBlockSeconds = 3*60; //Seconds how long contact mail will blocked
	$mailResultMessage = "";
	if(isset($_POST["formContactSubmit"]))
	{
		if(!isset($_COOKIE["sendContactMail"]))
		{
			if(isset($_POST["email"]) && $_POST["email"] != "" && isset($_POST["subject"]) && $_POST["subject"] != "" && isset($_POST["message"]) && $_POST["message"] != "") 
			{
				include_once 'helper/PHPMailer/class.phpmailer.php';
				
				$message = "Von: " . $_POST["email"] . "<br />Vorname: " . $_POST["firstname"] . "<br />Vorname: " . $_POST["lastname"] . "<br /><br />Nachricht: ";
				$message .= $_POST["message"];
				
				if(sendMail("dwindler@hsr.ch", $_POST["subject"], $message))
				{
					$mailResultMessage = "<span style =\"color: green;\">Nachricht wurde erfolgreich versendet.</span>";
					setcookie("sendContactMail", time() . "_" . $mailBlockSeconds, time()+$mailBlockSeconds);
				} else {
					$mailResultMessage = "<span style =\"color: red;\">Fehler beim versenden der Nachricht.</span>";
				}
			} else {
				$mailResultMessage = "<span style =\"color: red;\">Fehler, es wurden nicht alle ben&ouml;tigten Felder ausgef&uuml;llt.</span>";
			}
		} else {
			$mailBlockSendArray = explode("_", $_COOKIE["sendContactMail"]);
			$mailResultMessage = "<span style =\"color: red;\">Bitte warten Sie einige Zeit bis Sie wieder ein Formular abschicken. (".gmdate("i:s", $mailBlockSendArray[1]-(time()-$mailBlockSendArray[0])).")</span>";
		}
	}
	
	//TODO: Duplicate Function 'sendMail' & Extract to file 'handleMail'
	
	function sendMail($to, $subject, $text) {
		try {
			$mail = new PHPMailer();
			$mail->isSMTP();                // Set mailer to use SMTP
			//$mail->Host = '10.20.20.22';  // Specify main and backup server. Default: localhost
			$mail->SMTPAuth = false;        // Enable SMTP authentication
			$mail->CharSet = 'utf-8';
			$mail->isHTML();
	
			$mail->From = "mobilequiz@cnlab.ch";
			$mail->FromName = "MobileQuiz.ch";
			$mail->Subject = $subject;
			$mail->AddAddress($to);
	
			$mail->Body = $text;
			return $mail->Send();
		} catch (Exception $e) {
			$file = "logs/mailErrorLog.txt";
			$text = "Datum: " . date("d.m.Y H:i:s", time());
			$text .= e.getMessage();
			$text .= "------------------------------\n";
			$fp = fopen($file, "a");
			fwrite($fp, $text);
			fclose($fp);
			
			return false;
		}
	}
	
	if(isset($_SESSION["id"]))
	{
		$stmt = $dbh->prepare("select firstname, lastname, email from user inner join user_data on user.id = user_data.user_id where id = :uId");
		$stmt->bindParam(":uId", $_SESSION["id"]);
		$stmt->execute();
		$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
	}
?>


<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["contactFormHeading"];?></h1>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">
				<?php echo $lang["contactFormHeading"];?>
			</h3>
		</div>
		<div class="panel-body">
			<p><?php echo $mailResultMessage;?></p>
			<p>
				<?php echo $lang["contactFormMessage"] . ":";?>
			</p>
			<form id="formContact" class="form-horizontal" action="<?php echo "?p=contact"; echo isset($_GET["subject"]) ? '&subject=' . $_GET["subject"] : ''; ?>"
				method="post">
				<div class="control-group">
					<label class="control-label" for="email"> 
						<?php echo $lang["yourEMail"] . "*";?>
					</label>
					<div class="controls">
						<input type="text"
							class="form-control"
							name="email" placeholder="<?php echo $lang["yourEMail"];?>"
							maxlength="50"
							value="<?php echo isset($_SESSION["id"]) ? $fetchUser["email"] : '';?>" />
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="firstname"> 
						<?php echo $lang["firstname"];?>
					</label>
					<div class="controls">
						<input type="text"
							class="form-control"
							name="firstname"
							placeholder="<?php echo $lang["firstname"];?>"
							maxlength="25"
							value="<?php echo isset($_SESSION["id"]) ? $fetchUser["firstname"] : '';?>" />
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="lastname"> 
						<?php echo $lang["lastname"];?>
					</label>
					<div class="controls">
						<input type="text"
							class="form-control"
							name="lastname"
							placeholder="<?php echo $lang["lastname"];?>"
							maxlength="25"
							value="<?php echo isset($_SESSION["id"]) ? $fetchUser["lastname"] : '';?>" />
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="subject"> 
						<?php echo $lang["contactSubject"] . "*";?>
					</label>
					<div class="controls">
						<input type="text"
							class="form-control"
							name="subject"
							placeholder="<?php echo $lang["contactSubject"];?>"
							maxlength="50"
							value="<?php 
								if(isset($_GET["subject"]))
								{
									switch($_GET["subject"])
									{
										case 'contact':
											echo "";
											break;
										case 'errorReport':
											echo "Error report";
											break;
										default: echo "";
											break;
									}
								}
							?>" />
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="message"> 
						<?php echo $lang["yourMessage"] . "*";?>
					</label>
					<div class="controls">
						<textarea name="message" wrap="soft"
							placeholder="<?php echo $lang["yourMessage"];?>"
							class="form-control"></textarea>
					</div>
				</div>
				<div id="placeholder" style="height: 20px;"></div>
				<p>
					<?php echo $lang["requiredFields"];?>
				</p>
				<div id="placeholder" style="height: 20px;"></div>
				<div style="text-align: left; float: left">
					<input type="button" class="btn" name="cancel" id="btnCancel"
						value="<?php echo $lang["buttonCancel"];?>" />
				</div>
				<div style="text-align: right">
					<input type="submit" class="btn" name="formContactSubmit"
						form="formContact" value="<?php echo $lang["btnSend"];?>" />
				</div>
			</form>
		</div>
	</div>
</div>