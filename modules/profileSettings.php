<?php
session_start();

include_once '../config/config.php';
include_once "../modules/extraFunctions.php";
include_once "modules/extraFunctions.php";
include_once 'mail.php';

$serveraddress = "http://sinv-56082.edu.hsr.ch/index.php";
if($_POST["action"] == 'changeRole')
{
	if($_SESSION["id"] == $_POST["userId"])
	{
		$stmt = $dbh->prepare("select id from role_request where user_id = :userId");
		$stmt->bindParam(":userId", $_POST["userId"]);
		$stmt->execute();
		
		if($stmt->rowCount() == 0)
		{
			$stmt = $dbh->prepare("select id from role where name = :roleName");
			$stmt->bindParam(":roleName", $_POST["requestedRole"]);
			$stmt->execute();
			$fetchRole = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$stmt = $dbh->prepare("insert into role_request (user_id, role_id, timestamp) values (:userId, :roleId, " . time() . ")");
			$stmt->bindParam(":userId", $_POST["userId"]);
			$stmt->bindParam(":roleId", $fetchRole["id"]);
			if($stmt->execute())
			{
				echo "ok";
			} else 
			{
				echo "failed";
			}
		} else {
			echo "requestExisting";
		}
	} else 
	{
		echo "failed";
	}
} else if($_GET["action"] == 'saveProfileData')
{
	if(isset($_POST["oldPass"]) && isset($_POST["email"]) && isset($_POST["lastname"]) &&
		isset($_POST["firstname"]))
	{
		$stmt = $dbh->prepare("select password, email, nickname from user where id = :id");
		$stmt->bindParam(":id", $_SESSION["id"]);
		$stmt->execute();
		$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$subCode = 0;
		if(password_verify($_POST["oldPass"], $fetchUser["password"]))
		{
			if(strlen($_POST["email"]) < 6 || strpos($_POST["email"], '@') === false || strpos($_POST["email"], '.') === false)
			{
				header("Location: ?p=profile&code=-3&info=email");
				exit;
			}
			if(strlen($_POST["firstname"]) <= 1)
			{
				header("Location: ?p=profile&code=-3&info=firstname");
				exit;
			}
			if(strlen($_POST["lastname"]) <= 1)
			{
				header("Location: ?p=profile&code=-3&info=lastname");
				exit;
			}
			
			$street = "";
			if(isset($_POST["street"]) && strlen($_POST["street"])>0)
				$street = $_POST["street"];
			$plz = 0;
			if(isset($_POST["plz"]) && strlen($_POST["plz"])>0)
				$plz = $_POST["plz"];
			$city = "";
			if(isset($_POST["city"]) && strlen($_POST["city"])>0)
				$city = $_POST["city"];
			$tel = "";
			if(isset($_POST["telephone"]) && strlen($_POST["telephone"])>0)
				$tel = $_POST["telephone"];
			$nickname = "";
			if(isset($_POST["nickname"]) && strlen($_POST["nickname"])>0)
				$nickname = $_POST["nickname"];
			else 
				$nickname = $_POST["firstname"] . " " . $_POST["lastname"];
			
			$stmt = $dbh->prepare("update user set nickname = :nickname where id = :id");
			$stmt->bindParam(":nickname", $nickname);
			$stmt->bindParam(":id", $_SESSION["id"]);
			if(!$stmt->execute())
			{
				header("Location: ?p=profile&code=-4&info=user");
				exit;
			}
			
			$stmt = $dbh->prepare("update user_data set firstname = :firstname, lastname = :lastname, street = :street, plz = :plz, city = :city, tel = :tel where user_id = :user_id");
			$stmt->bindParam(":firstname", $_POST["firstname"]);
			$stmt->bindParam(":lastname", $_POST["lastname"]);
			$stmt->bindParam(":street", $street);
			$stmt->bindParam(":plz", $plz);
			$stmt->bindParam(":city", $city);
			$stmt->bindParam(":tel", $tel);
			$stmt->bindParam(":user_id", $_SESSION["id"]);
			if(!$stmt->execute())
			{
				header("Location: ?p=profile&code=-4&info=user_data");
				exit;
			}

			if($fetchUser["email"] != $_POST["email"])
			{
				//verifizierungs-/bestätigungsmail schicken
				$randomKey = md5(uniqid(rand(), true));
				$stmt = $dbh->prepare("insert into email_request (user_id, new_email, `key`) values (:uId, :newEmail, :randomKey)");
				$stmt->bindParam(':uId', $_SESSION["id"]);
				$stmt->bindParam(':newEmail', $_POST["email"]);
				$stmt->bindParam(':randomKey', $randomKey);
				$insertEmail_request = $stmt->execute();
			
				if($insertEmail_request)
				{
					$activationLink =  $serveraddress . "?p=auth&action=email_request&key=" . $randomKey;
					//$activationLink = $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']  . "?p=auth&action=email_request&key=" . $randomKey;
					$text= "<b>Guten Tag</b> " . $nickname . "<br /><br />Sie haben eine neue E-Mail Adresse angegeben.<br />
						<br/>Diese lautet:<br />E-Mail: " . $_POST["email"] . "<br /><br />
						Um diese Email zu aktivieren, klicken Sie bitte auf diesen Aktivierungslink:<br />
						<a href=\"" . $activationLink . "\">" . $activationLink . "</a><br /><br />
						Falls Sie keine neue E-Mail Adresse angegeben haben, ignorieren Sie diese Mail.<br /><br />
						Freundliche Gr&uuml;sse,<br />Ihr MobileQuiz Team";
					if(!sendMail($fetchUser["email"], "Neue E-Mail bestaetigen", $text))
					{
						header("Location: ?p=profile&code=-3&info=email3");
						exit;
					}
						
					$subCode = 1;
				}
				else
				{
					header("Location: ?p=profile&code=-3&info=email2");
					exit;
				}
			}
			if(isset($_POST["newPassword"]) && isset($_POST["confirmNewPassword"]) && strlen($_POST["newPassword"]) > 1 && strlen($_POST["confirmNewPassword"]) > 1)
			{
				//verifizierungs-/bestätigungsmail schicken
				
				$options = [
					'cost' => 12,
				];
				$encryptedPw = password_hash($_POST["newPassword"], PASSWORD_BCRYPT, $options);
				
				$randomKey = md5(uniqid(rand(), true));
				$stmt = $dbh->prepare("insert into password_request (user_id, new_password, `key`) values (:uId, :newPassword, :randomKey)");
				$stmt->bindParam(':uId', $_SESSION["id"]);
				$stmt->bindParam(':newPassword', $encryptedPw);
				$stmt->bindParam(':randomKey', $randomKey);
				$insertPassword_request = $stmt->execute();
					
				if($insertPassword_request)
				{
					$activationLink = $serveraddress  . "?p=auth&action=password_request&key=" . $randomKey;
					//$activationLink = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']  . "?p=auth&action=password_request&key=" . $randomKey;
					$text= "<b>Guten Tag</b> " . $nickname . "<br /><br />Sie haben eine neues Passwort angegeben.<br />
						Wenn Sie das Passwort wirklich &auml;ndern wollen, klicken Sie bitte auf diesen Aktivierungslink:<br />
						<a href=\"" . $activationLink . "\">" . $activationLink . "</a><br /><br />
						Falls Sie Ihr Passwort nicht ge&auml;ndert haben, ignorieren Sie diese Mail.<br /><br />
						Freundliche Gr&uuml;sse,<br />Ihr MobileQuiz Team";
					if(!sendMail($fetchUser["email"], "Neues Passwort bestaetigen", $text))
					{
						header("Location: ?p=profile&code=-3&info=password3");
						exit;
					}
					$subCode = 2;
				}
				else
				{
					header("Location: ?p=profile&code=-3&info=password2");
					exit;
				}
			}
			
			$subCodeText = "";
			if($subCode != 0)
			{
				$subCodeText = "&subCode=" . $subCode;
			}
			
			$msg = $fetchUser["nickname"] . " (" . $fetchUser["email"] . ") updated his profile.";
			addEvent($dbh, 'Updated profile', $msg);
			header("Location: ?p=profile&code=1" . $subCodeText);
			exit;
			
		} else {
			header("Location: ?p=profile&code=-1");
			exit;
		}
	} else {
		header("Location: ?p=profile&code=-2");
		exit;
	}
} else if($_POST["action"] == 'roleDecision' && $_SESSION["role"]["admin"] == 1)
{
	if(isset($_POST["userId"]) && isset($_POST["decision"]))
	{
		$stmt = $dbh->prepare("select nickname, email from user where id = :uId");
		$stmt->bindParam(":uId", $_SESSION["id"]);
		$stmt->execute();
		$fetchYourName = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $dbh->prepare("select nickname, email from user where id = :uId");
		$stmt->bindParam(":uId", $_POST["userId"]);
		$stmt->execute();
		$fetchHisName = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $dbh->prepare("select role_id from role_request where user_id = :user_id");
		$stmt->bindParam(":user_id", $_POST["userId"]);
		if(!$stmt->execute())
		{
			echo "failed";
			exit;
		}
		if($stmt->rowCount() == 0)
		{
			echo "failed";
			exit;
		}
		$fetchRole = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $dbh->prepare("select name from role where id = :role_id");
		$stmt->bindParam(":role_id", $fetchRole["role_id"]);
		$stmt->execute();
		$fetchRoleName = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if($_POST["decision"] == 1)
		{
			
			$stmt = $dbh->prepare("update user set role_id = :role_id where id = :user_id");
			$stmt->bindParam(":role_id", $fetchRole["role_id"]);
			$stmt->bindParam(":user_id", $_POST["userId"]);
			if($stmt->execute())
			{
				$stmt = $dbh->prepare("delete from role_request where user_id = :user_id");
				$stmt->bindParam(":user_id", $_POST["userId"]);
				if($stmt->execute())
				{
					$msg = $fetchYourName["nickname"] . " (" . $fetchYourName["email"] . ") granted " . $fetchHisName["nickname"] . " (" . $fetchHisName["email"] . ") the role '" . $fetchRoleName["name"] . "'";
					addEvent($dbh, 'Role granted', $msg);
					echo "ok1";
				}
				else
					echo "failed";
			}else
				echo "failed";
					
		} else if($_POST["decision"] == 0)
		{
			$stmt = $dbh->prepare("delete from role_request where user_id = :user_id");
			$stmt->bindParam(":user_id", $_POST["userId"]);
			if(!$stmt->execute())
			{
				echo "failed";
				exit;
			} else {
				$msg = $fetchYourName["nickname"] . " (" . $fetchYourName["email"] . ") refused " . $fetchHisName["nickname"] . " (" . $fetchHisName["email"] . ") the role '" . $fetchRoleName["name"] . "'";
				addEvent($dbh, 'Role refused', $msg);
				echo "ok2";
			}
		}
	} else {
		echo "failed";
	}
} else if($_POST["action"] == 'languageDecision' && $_SESSION["role"]["manager"] == 1)
{
	if(isset($_POST["languageRequestId"]) && isset($_POST["decision"]))
	{
		if($_POST["decision"] == 1)
		{
			$stmt = $dbh->prepare("select * from language_request where id = :id");
			$stmt->bindParam(":id", $_POST["languageRequestId"]);
			if(!$stmt->execute())
				echo "failed";
			$fetchRequest = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if(isset($fetchRequest["questionnaire_id"]))
			{
				$stmt = $dbh->prepare("update questionnaire set language = :language where id = :qId");
				$stmt->bindParam(":language", $fetchRequest["language"]);
				$stmt->bindParam(":qId", $fetchRequest["questionnaire_id"]);
				if($stmt->execute())
				{
					$stmt = $dbh->prepare("delete from language_request where id = :id");
					$stmt->bindParam(":id", $_POST["languageRequestId"]);
					if($stmt->execute())
					{
						echo "ok1";
					} else {
						echo "failed";
					}
				} else {
					echo "failed";
				}
			} else if(isset($fetchRequest["question_id"]))
			{
				$stmt = $dbh->prepare("update question set language = :language where id = :qestionId");
				$stmt->bindParam(":language", $fetchRequest["language"]);
				$stmt->bindParam(":qestionId", $fetchRequest["question_id"]);
				if($stmt->execute())
				{
					$stmt = $dbh->prepare("delete from language_request where id = :id");
					$stmt->bindParam(":id", $_POST["languageRequestId"]);
					if($stmt->execute())
					{
						echo "ok1";
					} else {
						echo "failed";
					}
				} else {
					echo "failed";
				}
			} else {
				echo "failed";
			}
			
			
		} else if($_POST["decision"] == 0)
		{
			$stmt = $dbh->prepare("delete from language_request where id = :id");
			$stmt->bindParam(":id", $_POST["languageRequestId"]);
			if($stmt->execute())
				echo "ok2";
			else
				echo "failed";
		}
	} else 
		echo "failed";
} else if($_POST["action"] == 'topicDecision' && $_SESSION["role"]["manager"] == 1)
{
	if(isset($_POST["topicRequestId"]) && isset($_POST["decision"]))
	{
		if($_POST["decision"] == 1)
		{
			$stmt = $dbh->prepare("select * from topic_request where id = :id");
			$stmt->bindParam(":id", $_POST["topicRequestId"]);
			if(!$stmt->execute())
				echo "failed";
			$fetchRequest = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$stmt = $dbh->prepare("insert into subjects (name) values (:name)");
			$stmt->bindParam(":name", $fetchRequest["topic"]);
			if(!$stmt->execute())
				echo "failed";
			else
				$lastInsertedId = $dbh->lastInsertId();
			
			$stmt = $dbh->prepare("update questionnaire set subject_id = :subject_id where id = :qId");
			$stmt->bindParam(":subject_id", $lastInsertedId);
			$stmt->bindParam(":qId", $fetchRequest["questionnaire_id"]);
			if($stmt->execute())
			{
				//set answer topic aswell
				$stmt = $dbh->prepare("update question set subject_id = :subject_id where question.id in (select question_id from qunaire_qu where questionnaire_id = :qId)");
				$stmt->bindParam(":subject_id", $lastInsertedId);
				$stmt->bindParam(":qId", $fetchRequest["questionnaire_id"]);
				
				if($stmt->execute())
				{
					$stmt = $dbh->prepare("delete from topic_request where id = :id");
					$stmt->bindParam(":id", $_POST["topicRequestId"]);
					if($stmt->execute())
						echo "ok1";
					else
						echo "failed";
				} else 
					echo "failed";
			} else 
				echo "failed";
			
		} else if($_POST["decision"] == 0)
		{
			$stmt = $dbh->prepare("delete from topic_request where id = :id");
			$stmt->bindParam(":id", $_POST["topicRequestId"]);
			if($stmt->execute())
				echo "ok1";
			else
				echo "failed";
		}
	}
}else if($_POST["action"] == 'joinGroup' && $_SESSION["role"]["user"] == 1)
{
	if(isset($_SESSION["id"]) && isset($_POST["groupToJoin"]) && $_POST["groupToJoin"] != "" && isset($_POST["token"]) && $_POST["token"] != "")
	{
		$stmt = $dbh->prepare("select token from `group` where id = :gId");
		$stmt->bindParam(":gId", $_POST["groupToJoin"]);
		if($stmt->execute())
		{
			$fetchGroup = $stmt->fetch(PDO::FETCH_ASSOC);
			if($fetchGroup["token"] == $_POST["token"])
			{
				$stmt = $dbh->prepare("update user set group_id = :gId where id = :uId");
				$stmt->bindParam(":gId", $_POST["groupToJoin"]);
				$stmt->bindParam(":uId", $_SESSION["id"]);
				if($stmt->execute())
					echo json_encode(["ok", $_POST["groupToJoin"]]);
				else
					echo json_encode(["failed"]);
			} else echo json_encode(["failed"]);
		} else echo json_encode(["failed"]);
	} else echo json_encode(["failed"]);
} else 
{
	header("Location: ?p=quiz");
}

?>