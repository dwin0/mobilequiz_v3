<?php

	include_once 'helper/PHPMailer/class.phpmailer.php';

	function sendMail($to, $subject, $text)
	{
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
			$text .= $e->getMessage();
			$text .= "------------------------------\n";
			$fp = fopen($file, "a");
			fwrite($fp, $text);
			fclose($fp);
	
			return false;
		}
	}
?>