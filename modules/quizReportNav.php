<?php

	$quizId = -1;
	if(isset($_GET["id"]))
		$quizId = $_GET["id"];

?>

<style>

.headerLinkH5 {
    display: inline-block;
}

</style>

<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReport&id=<?php echo $quizId;?>"><?php echo $lang["participationStat"];?></a></h5>
<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReportAnswerStat&id=<?php echo $quizId;?>"><?php echo $lang["answerStat"];?></a></h5>
<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReportLadder&id=<?php echo $quizId;?>"><?php echo $lang["ladder"];?></a></h5>
<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReportLadder&id=<?php echo $quizId;?>&displayMode=prof"><?php echo $lang["ladderProf"];?></a></h5>
<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReportLadder&id=<?php echo $quizId;?>&displayMode=anonym"><?php echo $lang["ladderAnonym"];?></a></h5>
<div style="clear: both;"></div>