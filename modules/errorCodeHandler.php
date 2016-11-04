<?php

	class mobileError
	{
		private $text;
		private $color;
		
		public function __construct($text, $color)
		{
			$this->text = $text;
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
	
	function getQuestionsErrorText($code)
	{
		switch ($code)
		{
			case -1:
			case -2:
			case -3:
			case -5:
			case -13:
			case -14:
			case -15:
				return "Fehler in der Bearbeitung des Vorgangs (Code: " . htmlspecialchars($code) .")";
			case -4:
				return "DB insert Fehler.";
			case -6:
				return "Datentransfer fehlgeschlagen.";
			case -7:
				return "Bild&uumlberpr&uumlfung fehlgeschlagen.";
			case -8:
				return "Keine Datei gefunden.";
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
			default:
				return "Fehler (Code: " . htmlspecialchars($code) .")";
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
		switch ($code)
		{
			case -8:
			case -9:
			case -10:
			case -11:
			case -6:
				return $lang["quizUploadPicError"] . " (Code: " . htmlspecialchars($code) .")";
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
				return $lang["quizGeneralError"] . " (Code: " . htmlspecialchars($code) .")";
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
				return $lang["noCSVFile"] . ".";
			case -30:
				return $lang["uploadeCSVHandleError"] . ".";
			case -31:
			case -32:
			case -33:
				return $lang["csvInsertError"] . " (Code: " . htmlspecialchars($code) .")";
			case -34:
				return $lang["csvQunaireError"] . ".";
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
				$text = $lang["successfullySavedQuiz"] . ".";
				if(isset($_GET["qwna"]) && $_GET["qwna"] != 0) //qwna(v) - question with no answer (value)
				{
					$text .= "<br /><span style=\"color: red;\">".$_GET["qwna"]." Fragen ohne mindestens eine richtie Antwort vorhanden. Bitte &uuml;berpr&uuml;fen Sie Ihre Lernkontrolle sonst kann es zu Fehlern kommen.";
					$qwnav = explode(",", $_GET["qwnav"]);
					for($i = 0; $i < count($qwnav); $i++)
					{
						if($i == 0)
							$text .= "<ul>";
							$text .= "<li>".$qwnav[$i]."</li>";
							if($i == count($qwnav)-1)
								$text .= "</ul>";
					}
					$text .= "</span>";
				}
				return $text;
			case 2:
				return $lang["successfullySavedQuizAsBlueprint"] . ".";
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
	
?>