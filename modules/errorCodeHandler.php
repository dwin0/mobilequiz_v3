<?php

	class mobileError
	{
		private $text;
		private $color;
		
		public function __construct($text, $color)
		{
			$this->text = htmlspecialchars($text, ENT_QUOTES, "ISO-8859-1", false);
			$this->color = $color;
		}
		
		public function getText()
		{
			return $this->text;
		}
		
		public function getColor()
		{
			return $this->color;
		}
	}
	
	function getErrorColor($code)
	{
		if($code > 0)
		{
			return "green";
		} else
		{
			return "red";
		}
	}

	
	
	
	
	/*--------all Errors of different pages----------*/
	//TODO: remove
	function getQuestionsErrorText($code)
	{
		switch ($code)
		{
			case -1:
			case -2:
			case -3:
			case -13:
			case -14:
			case -15:
				return "Fehler in der Bearbeitung des Vorgangs (Code: " . $code .")";
			case -4:
				return "DB insert Fehler.";
			case -5:
				return "Weniger als 2 Antwortm&ouml;glichkeiten oder keine korrekte Antwort ausgew&auml;hlt.";
			case -6:
				return "Datentransfer fehlgeschlagen.";
			case -7:
				return "Bild&uumlberpr&uumlfung fehlgeschlagen.";
			case -8:
				return "Datei ist kein Bild.";
			case -9:
				return "Datei schon vorhanden.";
			case -10:
				return "Datei zu gross.";
			case -11:
				return "Dateityp wird nicht unterst&uuml;tzt.";
			case -12:
				return "Unzureichende Berechtigungen.";
			case 1:
				return "Neue Frage erstellt";
			case 2:
				return "Frage wurde bearbeitet";
			case -16:
				return "Komprimierung fehlgeschlagen. Bitte versuchen Sie ein kleineres oder anderes Bild";
			default:
				return "Fehler (Code: " . $code .")";
		}
	}
	
	function getAdminSectionErrorText($code)
	{
		switch ($code)
		{
			case 1:
				return "Benutzer erfolgreich erstellt.";
		}
	}
	
	function getParticipationOutroErrorText($code)
	{
		switch ($code)
		{
			case 1:
				return "OK"; //TODO: Successful-Text
		}
	}
	
	function getPollErrotText($code)
	{
		switch ($code){
			case 1:
				return "";
			case -1:
				return "Unzureichende Berechtigungen";
			case -2:
			case -7:
				return "Nicht alle Parameter gesetzt";
			case -3:
				return "DB insert error poll";
			case -4:
				return "DB insert error poll answers";
			case -5:
				return "Umfrage schon durchgef&uuml;hrt";
			case -6:
				return "Umfrage nicht aktiv";
			default:
				return "Unbekannter Fehler";
		}
	}
	
	function getProfileErrorText($code, $subcode)
	{
		$codeText = "";
		
		switch ($code)
		{
			case 1:
				$codeText = "Daten erfolgreich ge&auml;ndert.";
				break;
			case 2:
				$codeText = "Ihre E-Mail wurde erfolgreich ge&auml;ndert.";
				break;
			case 3:
				$codeText = "Ihr Passwort wurde erfolgreich ge&auml;ndert.";
				break;
			case -1:
				$codeText = "Passwort falsch.";
				break;
			case -2:
				$codeText = "Nicht alle notwendigen Felder ausgef&uuml;llt.";
				break;
			case -3:
				$codeText = "Daten fehlerhaft.";
				break;
			case -4:
				$codeText = "DB Fehler.";
				break;
			case -5:
				$codeText = "Key nicht vorhanden.";
				break;
			case -6:
				$codeText = "DB Fehler (Update Email).";
				break;
			case -7:
				$codeText = "DB Fehler (Delete Email request).";
				break;
			case -8:
				$codeText = "DB Fehler (Update Passwort).";
				break;
			case -9:
				$codeText = "DB Fehler (Delete Passwort request).";
				break;
		}
		
		if(isset($subcode))
		{
			switch ($code)
			{
				case 1:
				case 2:
					$codeText .= "<br />Best&auml;tigungsmail wurde versendet.";
					break;
			}
		}
		
		return $codeText;
	}
	
	function getQuizErrorText($code)
	{
		global $lang;
		
		switch ($code)
		{
			case -8:
			case -9:
			case -10:
			case -11:
			case -6:
				return $lang["quizUploadPicError"] . " (Code: " . $code .")";
			case -2:
			case -7:
			case -12:
			case -13:
			case -14:
			case -16:
			case -17:
			case -21:
			case -22:
			case -23:
			case -24:
			case -25:
			case -27:
				return $lang["quizGeneralError"] . " (Code: " . $code .")";
			case -15:
				return $lang["quizNotAvailable"] . ".";
			case -18:
				return $lang["quizAborted"] . ".";
			case -19:
				return $lang["quizNotFinished"] . ".";
			case -20:
				return $lang["quizNotStarted"] . ".";
			case -25:
				return $lang["quizNotPublic"] . ".";
			case -26:
				return $lang["quizNotInTimeWindow"] . ".";
			case -28:
				return $lang["errorWhileUploading"] . ".";
			case -29:
				return $lang["noExcelFile"] . ".";
			case -30:
				return $lang["uploadeExcelHandleError"] . ".";
			case -31:
			case -32:
			case -33:
				return $lang["ExcelInsertError"] . " (Code: " . $code .")";
			case -34:
				return $lang["ExcelQunaireError"] . ". M&ouml;glicherweise wurde eine identische Frage mehrmals zum Quiz hinzugef&uuml;gt.";
			case -35:
				return $lang["reachedMaximumOfParticipations"] . ".";
			case -36:
				$text = $lang["PDFCreationError"] . ".";
				if(isset($_GET["info"]) && $_GET["info"] == 'noAccess4')
				{
					$text .= "<br />Lernkontrolle muss mindestens einmal durchgef&uuml;hrt werden um das Aufgabenblatt einsehen zu k&ouml;nnen.";
				}
				return $text;
			case -37:
				return "End quiz db error.";
			case -38:
				return "Diese Lernkontrolle darf nur von bestimmten Gruppen durchgef&uuml;hrt werden.";
			case -3:
				return $lang["dateOrTimeFormatError"] . ".";
			case -4:
				return $lang["numericFormatError"] . ".";
			case -1:
				return $lang["noAccessError"] . ".";
			case 1:
				return "Lernkontrolle erfolgreich gespeichert" . ".";
			case 2:
				return $lang["successfullySavedQuizAsBlueprint"] . ".";
			case -39:
				return "Keine Fragen in Excel-Datei vorhanden.";
			case -40:
				$text = $lang["savedQuizAndQuestions"] . ". ";
				if(isset($_GET["qwna"]) && $_GET["qwna"] != 0) //qwna(v) - question with no answer (value)
				{
					$text .= $_GET["qwna"]." Singlechoice-Frage mit mehr oder weniger als 1 richtiger Antwort. Bitte &uuml;berpr&uuml;fen Sie Ihre Lernkontrolle sonst kann es zu Fehlern kommen. Fragen: ";
					$qwnav = explode(",", $_GET["qwnav"]);
					for($i = 0; $i < count($qwnav); $i++)
					{
						if($i == 0)
						{
							$text .= $qwnav[$i];
						} else
						{
							$text .= ", " . $qwnav[$i];
						}
					}
				}
				return $text;
			case -41:
				return "Fragebild konnte nicht zugewiesen werden. Bitte verwenden Sie 'Fragen mit Bildern aus Excel importieren' 
						und w&auml;hlen Sie den Ordner aus, welcher das Template und die Bilder enth&auml;lt.";
			case -42:
				return "Bild konnte nicht abgespeichert werden.";
			case -43:
				return "Nicht unterst&uuml;tzes Bildformat verwendet.";
			case -44:
				return "Frage mit nur 1 Antwortm&ouml;glichkeit vorhanden. Bei jeder Frage m&uuml;ssen mindestens 2 vorhanden sein.";
			case -45:
				return "Frage ohne 1 korrekte Antwort vorhanden. Mindestens 1 Antwortm&ouml;glichkeit pro Frage muss richtig sein.";
		}
	}
	
	
	function getHomeErrorText($code)
	{
		switch ($code)
		{
			case 1:
				return "Anmeldung erfolgreich!<br />Bitte &uuml;berpr&uuml;fen Sie ihre E-Mails und aktivieren Sie ihren Account um sich einloggen zu k&ouml;nnen.";
			case 2:
				return "Aktivierung erfolgreich!<br />Sie k&ouml;nnen sich nun mit Ihren Anmeldedaten anmelden.";
			case 3:
				return "Ihnen wurde eine E-Mail gesendet.";
			case 4:
				return "Ihnen wurde eine E-Mail mit einem neuen Passwort zugesendet.";
			case 5:
				return "Sie wurden ausgeloggt.";
			case -1:
			case -2:
				return "Aktivierung fehlgeschlagen (Code: " . $code . ")";
			case -6:
				return "Senden der Passwort zur&uuml;cksetzen E-Mail fehlgeschlagen.";
			case -7:
				return "Passwort reaktivierung fehlgeschagen.";
			case -9:
				return "E-Mail nicht gefunden.";
			case -10:
				return "Key nicht gefunden.";
			case -16:
				return "Benutzer oder Passwort nicht gefunden.";
			case -17:
				return "Account noch nicht aktiviert.<br />Sollten Sie keine Best&auml;tigungsmail bekommen haben klicken Sie <a href=\"?p=auth&action=resendVerification&email=" . $_GET["email"] . "\">hier</a>.";
			case -18:
				return "Versenden der E-Mail fehlgeschlagen.";
			case -20:
				return "Sie sind nicht eingeloggt.";
			case -21:
				return "Datenbank-Fehler";
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
				return "Allgemeiner Fehler (Code " . $code . ")";
		}
	}
	
	
	function getCreateQuizErrorText($code)
	{
		switch ($code)
		{
			case -1:
				return "ID nicht gesetzt.";
			case -2:
				return "ID nicht gefunden.";
			case -3:
				return "Beantragte Sprache oder Themenbereich darf nicht leer sein.";
		}
	}
	
	function getRegisterErrorText($code)
	{
		switch($code)
		{
			case 0:
				return "Allgemeiner Fehler. (Code " . $code . ")";
			case -1:
				return "E-Mail Fehler.";
			case -2:
				return "Passwort Fehler.";
			case -3:
				return "Vorname Fehler.";
			case -4:
				return "Nachname Fehler.";
			case -5:
				return "E-Mail schon vorhanden.";
			case -10:
				return "Versenden der E-Mail fehlgeschlagen.";
			default:
				return "Allgemeiner Fehler. (Code " . $code . ")";
		}
	}
	
	function getTopicsErrorText($code)
	{
		switch ($code)
		{
			case 1:
				return "Themengebiet erfolgreich hinzugef&uuml;gt.";
			default:
				return "Fehler (Code: " . $code .")";
		}
	}
	
	
	
	
	
	/*-----------handle Errors of different pages------*/
	
	
	function handleQuestionsError($code)
	{
		return new mobileError(getQuestionsErrorText($code), getErrorColor($code));
	}
	
	function handleAdminSectionError($code)
	{
		return new mobileError(getAdminSectionErrorText($code), getErrorColor($code));
	}
	
	function handleParticipationOutroError($code)
	{
		return new mobileError(getParticipationOutroErrorText($code), getErrorColor($code));
	}
	
	function handlePollError($code)
	{
		return new mobileError(getPollErrotText($code), getErrorColor($code));
	}
	
	function handleProfileError($code, $subcode)
	{
		return new mobileError(getProfileErrorText($code, $subcode), getErrorColor($code));
	}
	
	function handleQuizError($code)
	{
		return new mobileError(getQuizErrorText($code), getErrorColor($code));
	}
	
	function handleHomeError($code)
	{
		return new mobileError(getHomeErrorText($code), getErrorColor($code));
	}
	
	function handleCreateEditQuizError($code)
	{
		return new mobileError(getCreateQuizErrorText($code), getErrorColor($code));
	}
	
	function handleRegisterError($code)
	{
		return new mobileError(getRegisterErrorText($code), getErrorColor($code));
	}
	
	function handleTopicsError($code)
	{
		return new mobileError(getTopicsErrorText($code), getErrorColor($code));
	}
	
?>