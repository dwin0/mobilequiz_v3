<?php

	include_once 'mail.php';
	include "modules/extraFunctions.php";

	$action = -1;
	$fromSite = -1;
	if(isset($_GET["action"]))
	{
		$action = $_GET["action"];
	}
	if(isset($_GET["fromsite"]))
	{
		$fromSite = $_GET["fromsite"];
	}
	//----------
	$serveraddress = "http://sinv-56082.edu.hsr.ch/index.php";
	
	if($action == 'register')
	{
		$allOk = true;
		$email = "";
		$pw = "";
		$vpw = " ";
		$firstname = "";
		$lastname = "";
		$street = "";
		$plz = 0000;
		$city = "";
		$tel = "";
		$nickname = "";
		$registerErrorCode = 0;
		
		if(isset($_POST["email"]))
		{
			$email = $_POST["email"];
			if(strlen($email) < 6 || strpos($email, '@') === false || strpos($email, '.') === false)
			{
				$allOk = false;
				$registerErrorCode = -1;
			}
		}
		else {
			$allOk = false;
			$registerErrorCode = -8;
		}
		if(isset($_POST["pass"]) && isset($_POST["verifypass"]))
		{
			$pw = $_POST["pass"];
			$vpw = $_POST["verifypass"];
			if(strlen($pw) < 1 && strlen($vpw) < 1)
			{
				if($pw != $vpw)
				{
					$allOk = false;
					$registerErrorCode = -2;
				}
			}
		}
		else {
			$allOk = false;
			$registerErrorCode = -7;
		}
		if(isset($_POST["firstname"]))
		{
			$firstname = $_POST["firstname"];
			if(strlen($firstname) <= 1)
			{
				$allOk = false;
				$registerErrorCode = -3;
			}
		}
		else
		{
			$allOk = false;
			$registerErrorCode = -6;
		}
		if(isset($_POST["lastname"]))
		{
			$lastname = $_POST["lastname"];
			if(strlen($lastname) <= 1)
			{
				$allOk = false;
				$registerErrorCode = -4;
			}
		}
		else
		{
			$allOk = false;
			$registerErrorCode = -5;
		}
		if(isset($_POST["street"]) && strlen($_POST["street"])>0)
			$street = $_POST["street"];
		if(isset($_POST["plz"]) && strlen($_POST["plz"])>0)
			$plz = $_POST["plz"];
		if(isset($_POST["city"]) && strlen($_POST["city"])>0)
			$city = $_POST["city"];
		if(isset($_POST["telephone"]) && strlen($_POST["telephone"])>0)
			$tel = $_POST["telephone"];
		if(isset($_POST["nickname"]) && strlen($_POST["nickname"])>0)
				$nickname = $_POST["nickname"];
			else 
				$nickname = $_POST["firstname"] . " " . $_POST["lastname"];
		
		$stmt = $dbh->prepare("select id from user where email = :email");
		$stmt->bindParam(":email", $email);
		$stmt->execute();
		if($stmt->rowCount() > 0)
		{
			$allOk = false;
			$registerErrorCode = -5;
		}
		
		if($allOk)
		{
			$options = [
				'cost' => 12,
			];
			$encryptedPw = password_hash($pw, PASSWORD_BCRYPT, $options);
			$stmt = $dbh->prepare("insert into user (email, create_date, password, role_id, nickname) values (:email, " . time() . ", :encryptedPw, 4, :nickname)");
			$stmt->bindParam(':email', $email);
			$stmt->bindParam(':encryptedPw', $encryptedPw);
			$stmt->bindParam(':nickname', $nickname);
			$insertUserQuery = $stmt->execute();
			$stmt = null;
			
			$lastId = $dbh->lastInsertId();
			$stmt = $dbh->prepare("insert into user_data (user_id, firstname, lastname, street, plz, city, tel) values (" . $lastId. ", :firstname, :lastname, :street, :plz, :city, :tel)");
			$stmt->bindParam(':firstname', $firstname);
			$stmt->bindParam(':lastname', $lastname);
			$stmt->bindParam(':street', $street);
			$stmt->bindParam(':plz', $plz);
			$stmt->bindParam(':city', $city);
			$stmt->bindParam(':tel', $tel);
			$insertUserData = $stmt->execute();
			$stmt = null;
			
			$randomKey = md5(uniqid(rand(), true));
			$stmt = $dbh->prepare("insert into user_activation (user_id, verification_key) values (" . $lastId . ", :randomKey)");
			$stmt->bindParam(':randomKey', $randomKey);
			$insertVerification = $stmt->execute();
			$stmt = null;
				
			if(!$insertUserQuery || !$insertUserData || !$insertVerification)
			{
				$allOk = false;
				$registerErrorCode = -9;
			}
			
			$activationLink = "http://sinv-56082.edu.hsr.ch/index.php?p=auth&action=verification&key=" . $randomKey;
			//$activationLink = $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']  . "?p=auth&action=verification&key=" . $randomKey;
			$text= "<b>Guten Tag</b> " . $nickname . "<br /><br />Vielen Dank, dass Sie sich auf MobileQuiz.ch angemeldet haben.<br />
				<br/>Ihre Logindaten lauten:<br />E-Mail: " . $email . "<br /><br />
				Um Ihren Account in vollem Umfang nutzen zu k&ouml;nnen klicken Sie bitte auf diesen Aktivierungslink:<br />
				<a href=\"" . $activationLink . "\">" . $activationLink . "</a><br /><br />
				Freundliche Gr&uumlsse,<br />Ihr MobileQuiz Team";
			if(!sendMail($email, "Registrierung abschliessen",$text))
			{
				$allOk = false;
				$registerErrorCode = -10;
			}
		}
		
		if($allOk)
		{
			$msg = $nickname . " (" . $email . ") just registered.";
			addEvent($dbh, 'Register', $msg);
			header("Location: ?p=home&code=1");
		}
		else 
			header("Location: ?p=register&code=" . $registerErrorCode);
	}
	else if($action == 'cancel')
	{
		$returnPage = "";
		switch($fromSite)
		{
			case 'profileSettings':
				$returnPage = "profile";
				break;
			case 'register':
			case 'recoverPassword':
			default: 
				$returnPage = "home";
				break;
		}
		header("Location: ?p=" . $returnPage);
	}
	else if($action == 'verification')
	{
		$key = "";
		if(isset($_GET["key"]))
		{
			$key = $_GET["key"];
		} else {
			header("Location: ?p=home&code=-10");
		}
		
		$stmt = $dbh->prepare("select * from user_activation where verification_key = :key");
		$stmt->bindParam(":key", $key);
		$stmt->execute();
		$fetchUserActivation = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $dbh->prepare("update user set isActivated = 1 where id = " . $fetchUserActivation["user_id"]);
		$query_result = $stmt->execute();
		
		$stmt = $dbh->prepare("select nickname, email from user where id = :userId");
		$stmt->bindParam(":userId", $fetchUserActivation["user_id"]);
		$stmt->execute();
		$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if($query_result)
		{
			$stmt = $dbh->prepare("delete from user_activation where verification_key = :key");
			$stmt->bindParam(":key", $key);
			$query_result = $stmt->execute();
			if($query_result)
			{
				$msg = $fetchUser["nickname"] . " (" . $fetchUser["email"] . ") activated his account.";
				addEvent($dbh, 'Activation', $msg);
				header("Location: ?p=home&code=2");
			}
			else
				header("Location: ?p=home&code=-2");
		}
		else
		{
			header("Location: ?p=home&code=-1");
		}
	} else if($action == 'resendVerification') 
	{
		$email = "";
		if(isset($_GET["email"]))
		{
			$email = $_GET["email"];
			$stmt = $dbh->prepare("select id from user where email = :email");
			$stmt->bindParam(":email", $email);
			$query_result = $stmt->execute();
				
			if($query_result)
			{
				$colcount = $stmt->rowCount();
				if($colcount != 1)
				{
					header("Location: ?p=home&code=-9");
				}
				else
				{
					$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $dbh->prepare("select verification_key from user_activation where user_id = ". $fetchUser["id"]);
					$query_result = $stmt->execute();
					if($query_result)
					{
						$fetchActivation = $stmt->fetch(PDO::FETCH_ASSOC);
						$activationLink = $serveraddress . "?p=auth&action=verification&key=" . $fetchActivation["verification_key"];
						//$activationLink = $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']  . "?p=auth&action=verification&key=" . $fetchActivation["verification_key"];
						$text= "<b>Guten Tag</b> " . $fetchUser["nickname"] . "<br /><br />Vielen Dank, dass Sie sich auf MobileQuiz.ch angemeldet haben.<br /><br />
							Um Ihren Account in vollem Umfang nutzen zu k&ouml;nnen klicken Sie bitte auf diesen Aktivierungslink:<br />
							<a href=\"" . $activationLink . "\">" . $activationLink . "</a><br /><br />
							Freundliche Gr&uumlsse,<br />Ihr MobileQuiz Team";
						if(sendMail($email, "Registrierung abschliessen",$text))
						{
							header("Location: ?p=home&code=3");
						} else { header("Location: ?p=home&code=-18"); }
					} else { header("Location: ?p=home&code=-15&c"); }
				}
			} else { header("Location: ?p=home&code=-15&b"); }
		}
		else
		{
			header("Location: ?p=home&code=-15&a");
		}
	} else if($action == 'recoverPassword')
	{
		$email = "";
		if(isset($_POST["emailforgot"]) && (strlen($_POST["emailforgot"]) >= 6 || strpos($_POST["emailforgot"], '@') === true || strpos($_POST["emailforgot"], '.') === true))
		{
			$email = $_POST["emailforgot"];
		}
		else {
			header("Location: ?p=home&code=-3");
		}
		
		$stmt = $dbh->prepare("select id, nickname from user where email = :email");
		$stmt->bindParam(":email", $email);
		$query_result = $stmt->execute();

		if($query_result)
		{
			$colcount = $stmt->rowCount();
			if($colcount != 1)
			{
				header("Location: ?p=home&code=-9");
			}
			else {
				$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
				$randomKey = md5(uniqid(rand(), true));
				$stmt = $dbh->prepare("insert into recover_pw (recover_key, create_date, user_id) values ('" . $randomKey . "', " . time() . ", " . $fetchUser["id"] . ")");
				$query_result = $stmt->execute();
				if(!$query_result)
				{
					header("Location: ?p=home&code=-4");
				} 
				else {
					$recoverLink = $serveraddress . "?p=auth&action=recoverPasswordVerification&key=" . $randomKey;
					//$recoverLink = $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']  . "?p=auth&action=recoverPasswordVerification&key=" . $randomKey;
					$text= "<b>Guten Tag</b> " . $fetchUser["nickname"] . "<br /><br />Soll Ihr Passwort wirklich zur&uuml;ckgesetzt werden?<br />
					<br/>Wenn ja, dann folgen Sie bitte diesem Link:<br />
					<a href=\"" . $recoverLink . "\">" . $recoverLink . "</a><br /><br />
					Falls nich, ignorieren Sie diese E-Mail.<br /><br />
					Freundliche Gr&uumlsse,<br />Ihr MobileQuiz Team";
					if(!sendMail($email, "Passwort zuruecksetzen", $text))
					{
						header("Location: ?p=home&code=-6");
					}
					else {
						header("Location: ?p=home&code=3");
					}
				}
			}
		}
		else {
			header("Location: ?p=home&code=-5");
		}
	} else if($action == 'recoverPasswordVerification')
	{
		$key = "";
		if(isset($_GET["key"]))
		{
			$key = $_GET["key"];
		}
		else
		{
			header("Location: ?p=home&code=-7");
		}
		
		$stmt = $dbh->prepare("select user_id from recover_pw where recover_key = :key");
		$stmt->bindParam(":key", $key);
		$query_result = $stmt->execute();
		
		if($query_result)
		{
			$colcount = $stmt->rowCount();
			if($colcount == 0)
			{
				header("Location: ?p=home&code=-10");
			}
			else 
			{
				$pwString = substr(md5(uniqid(rand(), true)), 0, 6);
				$newPw = password_hash($pwString, PASSWORD_BCRYPT, ['cost' => 12]);
				$fetchRecoverPw = $stmt->fetch(PDO::FETCH_ASSOC);
				$stmt = $dbh->prepare("update user set password = '" . $newPw . "' where id = " . $fetchRecoverPw["user_id"] . "");
				$query_result = $stmt->execute();
				
				if($query_result)
				{
					$stmt = $dbh->prepare("select nickname, email from user where id = " . $fetchRecoverPw["user_id"] . "");
					$query_result = $stmt->execute();
					
					if($query_result)
					{
						$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
						$text= "<b>Guten Tag</b> " . $fetchUser["nickname"] . "<br /><br />Ihr Passwort wurde zur&uuml;ckgesetzt.<br />
						<br />Ihr neues Passwort lautet:<br />
						Passwort: " . $pwString . "<br /><br />
						Bitte &auml;ndern Sie Ihr Passwort sofort nachdem Sie sich eingeloggt haben.<br />
						Falls Sie nie ein neues Passwort angefordert haben, wenden Sie sich bitte an einen Administrator.<br /><br />
						Freundliche Gr&uumlsse,<br />Ihr MobileQuiz Team";
						if(!sendMail($fetchUser["email"], "Passwort zurueckgesetzt",$text))
						{
							header("Location: ?p=home&code=-12");
						}
						else
						{
							$stmt = $dbh->prepare("delete from recover_pw where recover_key = :key");
							$stmt->bindParam(":key", $key);
							$query_result = $stmt->execute();
							if(!$query_result)
								header("Location: ?p=home&code=-14");
							else 
								header("Location: ?p=home&code=4");
						}
					}
					else 
					{
						header("Location: ?p=home&code=-13");
					}
				}
				else
				{
					header("Location: ?p=home&code=-11");
				}
			}
		}
		else 
		{
			header("Location: ?p=home&code=-8");
		}
	} else if($action == 'login')
	{
		$email = "";
		$pw = "";
		if(isset($_POST["email"]) && isset($_POST["password"]) && $_POST["submit"])
		{
			$email = $_POST["email"];
			$pw = $_POST["password"];
			$stmt = $dbh->prepare("select * from user where email = :email");
			$stmt->bindParam(":email", $email);
			$query_result = $stmt->execute();
			
			if($query_result)
			{
				$colcount = $stmt->rowCount();
				if($colcount != 1)
				{
					header("Location: ?p=home&code=-16");
				}
				else 
				{
					$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
					if(password_verify($pw, $fetchUser["password"]))
					{
						if($fetchUser["isActivated"] == 1)
						{
							$stmt = $dbh->prepare("select * from role where id = " . $fetchUser["role_id"]);
							$query_result = $stmt->execute();
							
							if($query_result)
							{
								$fetchRole = $stmt->fetch(PDO::FETCH_ASSOC);
								$_SESSION['id'] = $fetchUser["id"];
								$_SESSION['nickname'] = $fetchUser["nickname"];
								$_SESSION['email'] = $fetchUser["email"];
								$_SESSION['role']['name'] = $fetchRole["name"];
								$_SESSION['role']['admin'] = $fetchRole["admin"];
								$_SESSION['role']['manager'] = $fetchRole["manager"];
								$_SESSION['role']['creator'] = $fetchRole["creator"];
								$_SESSION['role']['user'] = $fetchRole["user"];
								$_SESSION['role']['fakeUser'] = -1;
								$_SESSION['role']['guest'] = $fetchRole["guest"];
								
								if(isset($_POST["toQuiz"]) && $_POST["toQuiz"] != '')
									header("Location: Pindex.php?p=participationIntro&quizId=" . $_POST["toQuiz"]);
								else
									header("Location: ?p=quiz");
								
							} else {
								header("Location: ?p=home&code=-15");
							}
						}
						else
						{
							header("Location: ?p=home&code=-17&email=" . $email);
						}
					}
					else
					{
						header("Location: ?p=home&code=-16");
					}
				}
			}
			else
			{
				header("Location: ?p=home&code=-15");
			}
		}
		else
		{
			header("Location: ?p=home&code=-15");
		}
	} else if($action == 'logout')
	{
		$language = $_SESSION["language"];
		session_destroy();
		header("Location: ?p=home&code=5&lang=" . $language);
	} else if($action == 'createNewUser')
	{
		if($_SESSION['role']['manager'] == 1)
		{
			if(isset($_POST["email"]) && isset($_POST["nickname"]) && isset($_POST["password"]) && isset($_POST["lastname"]) && isset($_POST["firstname"]) && isset($_POST["submit"]) &&
				$_POST["email"] != "" && $_POST["password"] != "" && $_POST["firstname"] != "" && $_POST["lastname"] != "")
			{
				$options = [
				'cost' => 12,
				];
				$encryptedPw = password_hash($_POST["password"], PASSWORD_BCRYPT, $options);
				$stmt = $dbh->prepare("insert into user (email, create_date, password, role_id, nickname, isActivated) values (:email, " . time() . ", :encryptedPw, 4, :nickname, 1)");
				$stmt->bindParam(':email', $_POST["email"]);
				$stmt->bindParam(':encryptedPw', $encryptedPw);
				$nickname = $_POST["nickname"];
				if($nickname == "") {$nickname = $_POST["firstname"] . " " . $_POST["lastname"];}
				$stmt->bindParam(':nickname', $nickname);
				$insertUserQuery = $stmt->execute();
				$stmt = null;
					
				$lastId = $dbh->lastInsertId();
				$stmt = $dbh->prepare("insert into user_data (user_id, firstname, lastname, street, plz, city, tel) values (" . $lastId. ", :firstname, :lastname, :street, :plz, :city, :tel)");
				$stmt->bindParam(':firstname', $_POST["firstname"]);
				$stmt->bindParam(':lastname', $_POST["lastname"]);
				$stmt->bindValue(':street', "");
				$stmt->bindValue(':plz', "");
				$stmt->bindValue(':city', "");
				$stmt->bindValue(':tel', "");
				$insertUserData = $stmt->execute();
				$stmt = null;
				
				if($insertUserQuery && $insertUserData)
				{
					header("Location: ?p=adminSection&code=1");
					exit;
				}
			} else {
				header("Location: ?p=quiz&code=-2");
				exit;
			}
		} else {
			header("Location: ?p=quiz&code=-1");
			exit;
		}
	}else if($action == 'email_request')
	{
		$key = "";
		if(isset($_GET["key"]))
		{
			$key = $_GET["key"];
		} else {
			header("Location: ?p=profile&code=-5");
			exit;
		}
		
		$stmt = $dbh->prepare("select * from email_request where `key` = :key");
		$stmt->bindParam(":key", $key);
		$stmt->execute();
		if($stmt->rowCount() != 1)
		{
			header("Location: ?p=profile&code=-5");
			exit;
		}
		$fetchEmailRequest = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $dbh->prepare("update user set email = :email where id = :uId");
		$stmt->bindParam(":email", $fetchEmailRequest["new_email"]);
		$stmt->bindParam(":uId", $fetchEmailRequest["user_id"]);
		if($stmt->execute())
		{
			$stmt = $dbh->prepare("delete from email_request where `key` = :key");
			$stmt->bindParam(":key", $key);
			if($stmt->execute())
			{
				header("Location: ?p=profile&code=2");
				exit;
			} else {
				header("Location: ?p=profile&code=-7");
				exit;
			}
		} else {
			header("Location: ?p=profile&code=-6");
			exit;
		}
	}else if($action == 'password_request')
	{
		$key = "";
		if(isset($_GET["key"]))
		{
			$key = $_GET["key"];
		} else {
			header("Location: ?p=profile&code=-5");
			exit;
		}
		
		$stmt = $dbh->prepare("select * from password_request where `key` = :key");
		$stmt->bindParam(":key", $key);
		$stmt->execute();
		if($stmt->rowCount() != 1)
		{
			header("Location: ?p=profile&code=-5");
			exit;
		}
		$fetchPasswordRequest = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $dbh->prepare("update user set password = :password where id = :uId");
		$stmt->bindParam(":password", $fetchPasswordRequest["new_password"]);
		$stmt->bindParam(":uId", $fetchPasswordRequest["user_id"]);
		if($stmt->execute())
		{
			$stmt = $dbh->prepare("delete from password_request where `key` = :key");
			$stmt->bindParam(":key", $key);
			if($stmt->execute())
			{
				header("Location: ?p=profile&code=3");
				exit;
			} else {
				header("Location: ?p=profile&code=-9");
				exit;
			}
		} else {
			header("Location: ?p=profile&code=-8");
			exit;
		}
	} else if($action == 'switchRole')
	{
		if(isset($_SESSION["id"]))
		{
			if($_SESSION["role"]["creator"] == 1)
			{
				$_SESSION['role']['admin'] = 0;
				$_SESSION['role']['manager'] = 0;
				$_SESSION['role']['creator'] = 0;
				$_SESSION['role']['user'] = 1;
				$_SESSION['role']['fakeUser'] = 1;
				$_SESSION['role']['guest'] = 1;
			} else {
				$stmt = $dbh->prepare("select * from role where `name` = :name");
				$stmt->bindParam(":name", $_SESSION['role']['name']);
				$stmt->execute();
				$fetchRoleForSwitch = $stmt->fetch(PDO::FETCH_ASSOC);
				
				$_SESSION['role']['admin'] = $fetchRoleForSwitch["admin"];
				$_SESSION['role']['manager'] = $fetchRoleForSwitch["manager"];
				$_SESSION['role']['creator'] = $fetchRoleForSwitch["creator"];
				$_SESSION['role']['user'] = $fetchRoleForSwitch["user"];
				$_SESSION['role']['fakeUser'] = -1;
				$_SESSION['role']['guest'] = $fetchRoleForSwitch["guest"];
			}
			header("Location: " . $_SERVER['HTTP_REFERER']);
			exit;
		}
	}
	
	$stmt = null;
	$dbh = null;
?>