<?php
include "modules/extraFunctions.php";

if(!isset($_GET["id"]))
{
	header("Location: ?p=quiz&code=-15");
	exit;
}

if($_SESSION["role"]["user"] == 1)
{
	if($_SESSION["role"]["creator"] != 1 && !amIAssignedToThisQuiz($dbh, $_GET["id"]))
	{
		header("Location: ?p=quiz&code=-1");
		exit;
	}
}
else
{
	header("Location: ?p=home&code=-20");
	exit;
}

$stmt = $dbh->prepare("select name, owner_id, quiz_passed from questionnaire where questionnaire.id = :quizId");
$stmt->bindParam(":quizId", $_GET["id"]);
if(!$stmt->execute())
{
	header("Location: ?p=quiz&code=-25");
	exit;
}
if($stmt->rowCount() != 1)
{
	header("Location: ?p=quiz&code=-15");
	exit;
}
$fetchQuiz = $stmt->fetch(PDO::FETCH_ASSOC);
if($fetchQuiz["owner_id"] != $_SESSION["id"] && $_SESSION['role']['admin'] != 1 && !amIAssignedToThisQuiz($dbh, $_GET["id"]))
{
	header("Location: ?p=quiz&code=-1");
	exit;
}

include 'PHPExcel/Classes/PHPExcel.php';
include 'PHPExcel/Classes/PHPExcel/Writer/Excel2007.php';

function clearDir($dir)
{
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file!="." AND $file !="..") {
					unlink($dir . $file);
				}
			}
			closedir($dh);
		}
	}
}

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set properties
$objPHPExcel->getProperties()->setCreator("Mobilequiz Team");
$objPHPExcel->getProperties()->setLastModifiedBy("Mobilequiz Team");
$objPHPExcel->getProperties()->setTitle("Mobilequiz_results");

// Add the data
?>
<script type="text/javascript">

<?php if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "anonym")
{
?>
	function revealName(uId)
	{
		$.ajax({
			url: 'modules/do.php',
			type: "get",
			data: "action=revealUserName&questionnaireId="+<?php echo $_GET["id"];?>+"&userId="+uId,
			dataType: 'json',
			success: function(output) 
			{
				if(output[0] == "ok")
				{
					$('#user_' + uId).html(output[1]);
				}
				if(output[0] == "failed")
				{
					console.log("Fehler " + output);
				}
			},
			error: function(output) 
			{
				console.log("Fehler (error Func)");
			}	      
		});
		$(".tipsy").remove();
	}
<?php }?>

$(function() {
	$('.quizCompleteImg').tipsy({gravity: 'n'});
	$('.userEmail').tipsy({gravity: 'n'});
	$('.icon_reveal').tipsy({gravity: 'n'});
	
	$('#users').dataTable({
	    'bSort': true,
	    'bPaginate': false,
	    'bLengthChange': false,
	    'aoColumns': [
			{'bSearchable': false},
			null,
			<?php //if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "prof") {echo 'null,';}?>
			null,
			{'bSearchable': false},
			<?php if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "prof") {echo "{'bSearchable': false},";}?>
			{'bSearchable': false},
			{'bSearchable': false},
			{'bSearchable': false},
	    ],
	    "sDom": '<"toolbar">frtip',
	    "oLanguage": {
	        "sZeroRecords": "Es sind keine Benutzer dieser Art vorhanden",
	        "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Benutzer",
	        "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Benutzer",
	        "sInfoFiltered": "(von insgesamt _MAX_ Benutzer)",
	        "sSearch": ""
	    }
	});
	$('#users_wrapper .dataTables_filter').prepend("<div><b>Suche:</b></div>");
	$('#users_wrapper .dataTables_filter input').attr("placeholder", 'Suchbegriff eingeben');
	$('#users_wrapper .dataTables_filter input').addClass("form-control");
	$('#users_wrapper .dataTables_filter input').addClass("magnifyingGlass");
});
</script>

<?php 
	
	$stmt = $dbh->prepare("select user_qunaire_session.*, user.nickname, firstname, lastname, email, group.name from user_qunaire_session inner join user on user.id = user_qunaire_session.user_id left outer join `group` on user.group_id = group.id inner join user_data on user_data.user_id = user.id where questionnaire_id = :questionnaire_id");
	$stmt->bindParam(":questionnaire_id", $_GET["id"]);
	$stmt->execute();
	$fetchSession = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$userSessions = array();
	//echo "<br /><br /><br /><br /><br /><br />b: " . count($fetchSession) . " "  . $_GET["id"];
	for($i = 0; $i < count($fetchSession); $i++)
	{
		//echo "a: " . $i . " " . $fetchSession[$i]["id"];
		$fetchPoints = getPoints($dbh, $_GET["id"], $fetchSession[$i]["id"], 2);
		if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "prof")
			$sessionKey = $fetchSession[$i]["lastname"] . " " . $fetchSession[$i]["firstname"];
		else
			$sessionKey = $fetchSession[$i]["nickname"];
		$timeUsed = $fetchSession[$i]["endtime"] - $fetchSession[$i]["starttime"];
		if($userSessions[$sessionKey] == null)
		{
			$userSessions[$sessionKey] = [$fetchPoints[0], $fetchPoints[1], $fetchPoints[2], 1, $timeUsed, $fetchSession[$i]["name"], $fetchSession[$i]["user_id"], $fetchSession[$i]["email"],$fetchSession[$i]["starttime"]];
			//echo "c: " . $sessionKey;
		}
		else {
			if($fetchPoints[2]>$userSessions[$sessionKey][2])
			{
				$userSessions[$sessionKey][0] = $fetchPoints[0];
				$userSessions[$sessionKey][1] = $fetchPoints[1];
				$userSessions[$sessionKey][2] = $fetchPoints[2];
				$userSessions[$sessionKey][4] = $timeUsed;
				$userSessions[$sessionKey][5] = $fetchSession[$i]["name"];
				$userSessions[$sessionKey][6] = $fetchSession[$i]["user_id"];
				$userSessions[$sessionKey][7] = $fetchSession[$i]["email"];
				$userSessions[$sessionKey][8] = $fetchSession[$i]["starttime"];
			}
			++$userSessions[$sessionKey][3];
		}
		//array_push($userSessions, $fetchPoints);
	}
	
	$points = array();
	foreach ($userSessions as $key => $row)
	{
		$points[$key] = $row[2];
	}
	array_multisort($points, SORT_DESC, $userSessions);
?>

<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["quizReportHeading"] . " &laquo;" . $fetchQuiz["name"] . "&raquo;"?></h1>
	</div>
	<?php include 'modules/quizReportNav.php';?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["ladder"] . " - Stand vom " . date("d.m.Y H:i:s", time()); ?></h3>
		</div>
		<div class="panel-body">
			<div>
				<?php $dataName = 'QuizExport' . date("d_m_Y_H_i_s", time());?>
				<a href="<?php echo "PHPExcel/exportedData/" . $dataName . '.xlsx';?>" download>Datenexport Excel (.xlsx)</a>
			</div>
			<div class="control-group">
				<table class="tblListOfUsers" id="users" style="width: 100%">
					<thead>
						<tr>
							<th>
		                        <?php echo $lang["rank"];?>
		                    </th>
							<th>
		                        <?php 
		                        if(isset($_GET["displayMode"]))
		                        {
		                        	if($_GET["displayMode"] == "prof")
		                        		echo $lang["participant"];
		                        	else if($_GET["displayMode"] == "anonym")
		                        		echo $lang["participantAnonym"];
		                        } else {
									echo $lang["participantNick"];
								}
								?>
		                    </th>
		                    <?php /*if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "prof") {?>
			                    <th>
			                        <?php echo $lang["email"];?>
			                    </th>
		                    <?php }*/?>
							<th>
		                        <?php echo $lang["groupName"];?>
		                    </th>
							<th>
		                        <?php echo $lang["percentOfBestParticipation"];?>
		                    </th>
		                    <?php if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "prof") {?>
		                    	<th>
		                    		<?php echo $lang["bestPaticipationDate"];?>
		                    	</th>
		                    <?php }?>
							<th>
		                        <?php echo $lang["amountParticipations"];?>
		                    </th>
							<th>
		                        <?php echo $lang["totalTimeNeeded"];?>
		                    </th>
							<th>
		                        <?php echo $lang["pointsPerMin"];?>
		                    </th>
						</tr>
					</thead>
		            <tbody>
		            	<?php 
		            	$i = 1;
						$objPHPExcel->getActiveSheet()->SetCellValue('A' . $i, 'Rang');
						$objPHPExcel->getActiveSheet()->SetCellValue('B' . $i, 'Teilnehmer');
						$objPHPExcel->getActiveSheet()->SetCellValue('C' . $i, 'Email');
						$objPHPExcel->getActiveSheet()->SetCellValue('D' . $i, 'Gruppe');
						$objPHPExcel->getActiveSheet()->SetCellValue('E' . $i, 'Beste Teilnahme');
						$objPHPExcel->getActiveSheet()->SetCellValue('F' . $i, 'Anzahl Teilnahmen');
	            		foreach($userSessions as $key => $val) {?>
			            <tr>
							<td>
		                        <?php 
								echo $i++;
								$objPHPExcel->getActiveSheet()->SetCellValue('A' . $i, ($i-1));
		                        ?>
		                    </td>
							<td>
		                        <?php 
		                        if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "anonym")
		                        {
		                        	echo '<span id="user_'.$val[6].'">- <img alt="reveal" src="assets/icon_eye_open.png" width="13" height="10" class="icon_reveal" style="cursor: pointer;" original-title="'.$lang["revealUserRanking"].'" onclick="revealName('.$val[6].')"></span>';
									$objPHPExcel->getActiveSheet()->SetCellValue('B' . $i, "-");
									$objPHPExcel->getActiveSheet()->SetCellValue('C' . $i, "-");
		                        }
		                        else 
		                        {
		                        	echo '<a href="?p=quizReportAnswerPersonalized&uId='.$val[6].'&qId='.$_GET["id"].'" original-title="'.$val[7].'" class="userEmail">' . $key . '</a>';
		                        	echo ' <a href="?p=generatePDF&action=getQuizTaskPaperWithMyAnswers&quizId='. $_GET["id"].'&uId='.$val[6].'" target="_blank" class="showPersonalizedAnswers" original-title="Ergebnisse anzeigen"><img src="assets/pdf_icon.png" alt="" height="18px" width="18px"></a>&nbsp;';
									$objPHPExcel->getActiveSheet()->SetCellValue('B' . $i, $key);
									$objPHPExcel->getActiveSheet()->SetCellValue('C' . $i, $val[7]);
		                        }
		                        ?>
		                    </td>
		                    <?php /*if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "prof") {?>
		                    	<td>
		                    		<?php echo $val[7];
		                    		$objPHPExcel->getActiveSheet()->SetCellValue('C' . $i, $val[7]);
		                    		?>
		                    	</td>
		                    <?php }*/?>
							<td>
		                        <?php 
		                        if($val[5] == NULL || (isset($_GET["displayMode"]) && $_GET["displayMode"] == "anonym"))
		                        {
		                        	echo "-";
		                        	$objPHPExcel->getActiveSheet()->SetCellValue('D' . $i, "-");
		                        }
		                        else
		                        {
		                        	echo $val[5];
		                        	$objPHPExcel->getActiveSheet()->SetCellValue('D' . $i, $val[5]);
		                        }
		                        ?>
		                    </td>
							<td>
		                        <?php echo $val[2] . " %";
		                        $completeImg = "icon_noPassing";
		                        $hint = $lang["noPassing"];
		                        if($fetchQuiz["quiz_passed"] != 0){
			                        if($val[2] >= $fetchQuiz["quiz_passed"])
		                            {
		                            	$completeImg = "icon_correct";
		                            	$hint = "Quiz abgeschlossen";
		                            }
		                            else
		                            { 
		                            	$completeImg = "icon_incorrect";
		                            	$hint = "Quiz nicht abgeschlossen";
		                            }
                            	}
								$objPHPExcel->getActiveSheet()->SetCellValue('E' . $i, $val[2] . " %");
	                            ?>
	                            <img class="quizCompleteImg" src="<?php echo "assets/" . $completeImg . ".png";?>" original-title="<?php echo $hint;?>" />
		                    </td>
		                    <?php if(isset($_GET["displayMode"]) && $_GET["displayMode"] == "prof") {?>
		                    	<td>
		                    		<?php echo date("d.m.y - H:i:s", $val[8]);?>
		                    	</td>
		                    <?php }?>
							<td>
		                        <?php 
		                        echo $val[3];
								$objPHPExcel->getActiveSheet()->SetCellValue('F' . $i, $val[3]);
								?>
		                    </td>
							<td>
								<?php echo gmdate("H:i:s", $val[4]);?>
	                    	</td>
							<td>
		                        <?php echo number_format($val[0] / ($val[4]/60), 2);?>
		                    </td>
						</tr>
		                <?php }
		                // Rename sheet
		                $objPHPExcel->getActiveSheet()->setTitle('Results');
		                
		                //clear directory
		                clearDir('/var/www/html/PHPExcel/exportedData/');
		                
		                // Save Excel 2007 file
		                $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
		                $objWriter->save(str_replace('.php', '.xlsx', '/var/www/html/PHPExcel/exportedData/' . $dataName . '.php'));
		                ?>
		            </tbody>
				</table>
			</div>
		</div>
	</div>
</div>
