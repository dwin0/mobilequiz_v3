<div data-role="footer" data-position="fixed">
	<?php if(isset($_SESSION["quizSession"]) && $_SESSION["quizSession"] >= 0) {

		$stmt = $dbh->prepare("select questionnaire.name from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id where execution_id = :execId");
		$stmt->bindParam(":execId", $_SESSION["quizSession"]);
		$stmt->execute();
		$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
		$quizName = $fetch["name"];
		echo "<div id=\"leftFooter\">Lernkontrolle: " . htmlspecialchars($quizName) . "</div>";
		
		
		$stmt = $dbh->prepare("select firstname, lastname from user inner join user_data on user.id = user_data.user_id where user.id = :userId");
		$stmt->bindParam(":userId", $_SESSION["id"]);
		$stmt->execute();
		$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
		$participiant = $fetchUser["firstname"] . " " .$fetchUser["lastname"];
		
		echo "<div id=\"rightFooter\">Teilnehmer: " . htmlspecialchars($participiant) . "</div>";
		
	}?>
</div>
