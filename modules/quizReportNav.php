<?php
	include "modules/authorizationCheck_quizReport.php";
?>

<style>

.headerLinkH5 {
    display: inline-block;
}

</style>

<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReport&execId=<?php echo $execId;?>"><?php echo $lang["participationStat"];?></a></h5>
<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReportAnswerStat&execId=<?php echo $execId;?>"><?php echo $lang["answerStat"];?></a></h5>
<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReportLadder&execId=<?php echo $execId;?>"><?php echo $lang["ladder"];?></a></h5>
<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReportLadder&execId=<?php echo $execId;?>&displayMode=prof"><?php echo $lang["ladderProf"];?></a></h5>
<h5 class="headerLinkH5"><a class="quizReportNav" href="?p=quizReportLadder&execId=<?php echo $execId;?>&displayMode=anonym"><?php echo $lang["ladderAnonym"];?></a></h5>
<div style="clear: both;"></div>