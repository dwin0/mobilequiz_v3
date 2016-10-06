<?php
	$count = 1;
	if(isset($_GET["count"])){
		$count = $_GET["count"];
	}

	echo "<table>";
	
	for($i = 0; $i <$count ; $i++)
	{
		$randomKey = substr(md5(uniqid(rand(), true)), 0, 8);
		
		$options = [
			'cost' => 12,
		];
		$encryptedPw = password_hash($randomKey, PASSWORD_BCRYPT, $options);
		/*$stmt = $dbh->prepare("insert into user (email, create_date, password, role_id, nickname) values (:email, " . time() . ", :encryptedPw, 4, :nickname)");
		$stmt->bindValue(':email', "g" . ($i+1) . "@mobilequiz.ch");
		$stmt->bindParam(':encryptedPw', $encryptedPw);
		$stmt->bindValue(':nickname', "Gruppe" . ($i+1));
		$insertUserQuery = $stmt->execute();
		$stmt = null;
			
		$lastId = $dbh->lastInsertId();
		$stmt = $dbh->prepare("insert into user_data (user_id, firstname, lastname, street, plz, city, tel) values (" . $lastId. ", :firstname, :lastname, :street, :plz, :city, :tel)");
		$stmt->bindValue(':firstname', "Gruppe" . ($i+1));
		$stmt->bindValue(':lastname', "Gruppe" . ($i+1));
		$stmt->bindValue(':street', "");
		$stmt->bindValue(':plz', "");
		$stmt->bindValue(':city', "");
		$stmt->bindValue(':tel', "");
		$insertUserData = $stmt->execute();
		$stmt = null;*/

		echo "<tr>";
		//echo "<td>Gruppe" . ($i+1)."</td>";
		//echo "<td>g" . ($i+1) . "@mobilequiz.ch</td>";
		echo "<td>" . ($i+1).": </td>";
		echo "<td style=\"background-color: rgb(14, 227, 219);\">".$randomKey."</td>";
		echo "<td>".$encryptedPw."</td>";
		echo "</tr>";
	}
	
	echo "</table>";
?>

