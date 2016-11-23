<div data-role="footer" data-position="fixed">
	<?php if(isset($_SESSION["quizSession"]) && $_SESSION["quizSession"] >= 0) {

		$stmt = $dbh->prepare("select name from questionnaire where id = :qunaireId");
		$stmt->bindParam(":qunaireId", $_SESSION["quizSession"]);
		$stmt->execute();
		$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
		$quizName = $fetch["name"];
		echo "<div id=\"leftFooter\">Lernkontrolle: " . $quizName . "</div>";
		
		
		$stmt = $dbh->prepare("select firstname, lastname from user inner join user_data on user.id = user_data.user_id where user.id = :userId");
		$stmt->bindParam(":userId", $_SESSION["id"]);
		$stmt->execute();
		$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
		$participiant = $fetchUser["firstname"] . " " .$fetchUser["lastname"];
		
		echo "<div id=\"rightFooter\">Teilnehmer: " . $participiant . "</div>";
		
	}?>
</div>
