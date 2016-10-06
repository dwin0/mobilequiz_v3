<?php

	$quizId = -1;
	if(isset($_GET["id"]))
		$quizId = $_GET["id"];

?>
<a class="quizReportNav" href="?p=quizReport&id=<?php echo $quizId;?>"><h5><?php echo $lang["participationStat"];?></h5></a>
<a class="quizReportNav" href="?p=quizReportAnswerStat&id=<?php echo $quizId;?>"><h5><?php echo $lang["answerStat"];?></h5></a>
<a class="quizReportNav" href="?p=quizReportLadder&id=<?php echo $quizId;?>"><h5><?php echo $lang["ladder"];?></h5></a>
<a class="quizReportNav" href="?p=quizReportLadder&id=<?php echo $quizId;?>&displayMode=prof"><h5><?php echo $lang["ladderProf"];?></h5></a>
<a class="quizReportNav" href="?p=quizReportLadder&id=<?php echo $quizId;?>&displayMode=anonym"><h5><?php echo $lang["ladderAnonym"];?></h5></a>
<div style="clear: both;"></div>