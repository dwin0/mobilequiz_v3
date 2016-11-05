<?php
	include_once 'modules/extraFunctions.php';
	include_once 'errorCodeHandler.php';
	

	if(!isset($_GET["id"]))
	{
		$mode = "create";
		$code = -1;
	}
	
	$maxCharactersQuiz = 30;
	$maxCharactersTopic = 30;
	$maxCharactersQuizDesc = 120;
	
	$code = 0;
	$codeText = "";
	$mode = "create";
	if(isset($_GET["mode"]))
	{
		$mode = $_GET["mode"];
	}
	
	if($_SESSION["role"]["user"])
	{
		if(! $_SESSION["role"]["creator"] && ($mode == 'edit' && !amIAssignedToThisQuiz($dbh, $_GET["id"])))
		{
			header("Location: ?p=quiz&code=-1&info=qqq");
			exit;
		}
	}
	else
	{
		header("Location: ?p=home&code=-20");
		exit;
	}
	
	if($mode == 'edit')
	{
		$stmt = $dbh->prepare("select questionnaire.*, user_data.firstname, user_data.lastname from questionnaire inner join user on user.id = questionnaire.owner_id inner join user_data on user_data.user_id = user.id where questionnaire.id = :id");
		$stmt->bindParam(":id", $_GET["id"]);
		$stmt->execute();
		if($stmt->rowCount() > 0)
			$quizFetch = $stmt->fetch(PDO::FETCH_ASSOC);
		else
		{
			$mode = "create";
			$code = -2;
		}
	}
	
	// can be merged with the upper role comparation
	if(! $_SESSION['role']['creator'] && $quizFetch["owner_id"] != $_SESSION["id"] && !amIAssignedToThisQuiz($dbh, $_GET["id"])) {
		header("Location: ?p=quiz&code=-1&info=lll");
		exit;
	}
	
	
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleCreateEditQuizError($_GET["code"]);
	}
	
	
	$stmt = $dbh->prepare("select id, email from user");
	$stmt->execute();
	$fetchUserMails = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
	$fetchUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	function getIdToName($var)
	{
		for ($i = 0; $i < count($fetchUsers); $i++) {
			if($fetchUsers[$i]["email"] == $var)
				return $fetchUsers[$i]["id"];
		}
		return -1;
	}
?>
<script src="js/spin.min.js"></script>
<script type="text/javascript">

	var shouldConfirm = false;
	window.onbeforeunload = function() {
        if(shouldConfirm) {
            return "Seite wirklich verlassen?";
        }
    }

    function setConfirm(val, elem)
    {
    	shouldConfirm = val;
    	//console.log("e:" + elem);
    }

	function openFileDialog()
    {
        $('#btnImportQuestionsFromCSV2').click();
    }

	function setChecked(id)
	{
		$('#' + id).prop('checked', true);
	}

	function showNewLanguageInput()
	{
		if($('#language').val() == "newLanguage")
		{
			$( "#newLanguage" ).show("slow", false);
		}else {
			$( "#newLanguage" ).hide("slow", false);
		}
	}

	function showNewTopicInput()
	{
		if($('#topic').val() == "newTopic")
		{
			$( "#newTopic" ).show("slow", false);
		} else {
			$( "#newTopic" ).hide("slow", false);
		}
	}

	function setDatesEnabled()
	{
		var val = false;
		if($("#noParticipationPeriod1").prop('checked'))
		{
			val = true;
		}

		var timeBoxes = ["startDate", "startTime", "endDate", "endTime"];

		$.each(timeBoxes, function()
		{
			$("#" + this).prop('readonly', val);
		});
	}

	function addCreator()
	{
		var userEmail = $('#autocompleteUsers').val();
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=addAssignation&userEmail="+userEmail+"&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>,
			success: function(output) 
			{
				if(output == "ok1")
				{
					$('#ajaxAnswer').html('<span style="color: green;">Berechtigung zugewiesen.</span>');
					//$('#assignTbl > tbody:last-child').append('<tr><td>'+userEmail+'</td><td></td></tr>');
					$('#assignTbl').DataTable().row.add([userEmail, '']).draw(false);
					$('#autocompleteUsers').val('');
					
				}
				if(output == "failed")
				{
					$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function delAssigned(userId)
	{
		console.log("del: " + userId);
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=delAssignation&userId="+userId+"&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>,
			success: function(output) 
			{
				if(output == "ok1")
				{
					$('#ajaxAnswer').html('<span style="color: green;">Berechtigung aberkannt.</span>');
					//$('#assignation_'+userId).css('display', 'none');
					$('#assignTbl').DataTable().row($('#assignation_'+userId)).remove().draw();
					$('.tipsy').remove();
				}
				if(output == "failed")
				{
					$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function delQuestion(qId)
	{
		console.log("del: " + qId);
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=delQuestionFromQuiz&questionId="+qId+"&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>,
			success: function(output) 
			{
				if(output == "ok")
				{
					$('#tblListOfQuestions').DataTable().row($('#question_'+qId)).remove().draw();
					$('.tipsy').remove();
				}
				if(output == "failed")
				{
					$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
			}
		});
	}

	function formCheck()
	{
		console.log("ASdasd");
		$('#btnSave').prop('disabled', true);
		$('#btnSaveAsDraft').prop('disabled', true);
		$('#btnBackToOverview').prop('disabled', true);
		var opts = {position: 'fixed', color: '#fff', length: 56, radius: 70, width: 22};
		var spinner = new Spinner(opts).spin();
		var over = '<div id="overlay"></div>';
		$(over).appendTo('body');
		$('body').append(spinner.el);
		return true;
	}

	var updateData = function(e, ui)
	{
		var qOrder = [];
		$('#sortable').find('.qId').each (function(col, td) {
			qOrder.push($(td).html());
		});   
		console.log(JSON.stringify(qOrder));
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=moveQuestion&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>+"&qOrder="+JSON.stringify(qOrder),
			success: function(output) 
			{
				console.log(output);
			}
		});
	}

	$(function() {
		var tooltipElements = ['#singlechoiseMultHelp', '#assignationHelp', '.delAssignedImg', '.delQuestionImg',
			'.editQuestion', '.groupName', '.questionTypeInfo', '#showQuizTaskPaperHelp'];

		$.each(tooltipElements, function(i, string)
			{
				$(string).tipsy({gravity: 'n'});
				console.log($(string));
			});

		setDatesEnabled();
		$("#groupAddSuccess").hide();
		$("#groupAddError").hide();
		
	    $( "#sortable" ).sortable({
		    stop: updateData}).disableSelection();
		
		document.getElementById("btnImportQuestionsFromCSV2").addEventListener("change",function(){
		    document.getElementById("fileName").innerHTML=
		        document.getElementById("btnImportQuestionsFromCSV2").files[0].name;
		    shouldConfirm = true;
		});

		$( "#additionalSettingsContent" ).hide();	
		var additionalSettingsContent = false;	
		$("#additionalSettingsHeading").click(function() {
			$( "#additionalSettingsContent" ).toggle("slow", false);
			additionalSettingsContent = !additionalSettingsContent;
			if(!additionalSettingsContent)
				$('#arrow1').html('&#9654;');
			else
				$('#arrow1').html('&#9660;');
		});

		var sourceData = <?php echo json_encode($fetchUserMails);?>;
		$( "#autocompleteUsers" ).autocomplete({
		  source: sourceData
		});

		$('#tblListOfQuestions').DataTable({
            'bSort': true,
            'bPaginate': true,
            'bLengthChange': true,
            'aaSorting': [[1, 'asc']],
            'aoColumns': [
				{'bSearchable': false, 'bSortable':false},
                {'bSearchable': false, 'bSortable':true},
                {'bSearchable': false, 'bSortable':false},
                {'bSortable':false},
                {'bSearchable': false, 'bSortable':false},
                {'bSearchable': false},
                {'bSearchable': false, 'bSortable':false}
            ],
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Alle"]],
            "sDom": 'lfrtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Fragen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Fragen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Fragen",
                "sInfoFiltered": "(von insgesamt _MAX_ Fragen)",
                "sSearch": ""
            }
        });
        $('.dataTables_filter input').attr("placeholder", 'Suchbegriff in Spalte "Fragetext (Beschreibung)" suchen');
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').addClass("magnifyingGlass");

        $('#assignTbl').dataTable({
            'bSort': true,
            'bFilter': false,
            'bPaginate': false,
            'bLengthChange': false,
            'bInfo': false,
            'aoColumns': [
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false}
            ]
        });

        $('#startDate').datepicker({
            format: "dd.mm.yyyy",
            startDate: "+0d",
            language: "de",
            orientation: "top left",
            autoclose: true,
            todayHighlight: true
        });
        
        $('#endDate').datepicker({
            format: "dd.mm.yyyy",
            startDate: "+0d",
            language: "de",
            orientation: "top left",
            autoclose: true,
            todayHighlight: true
        });
        
		$( "#assignGroupToQuizSortable1, #assignGroupToQuizSortable2" ).sortable({
			connectWith: ".assignGroupToQuizCconnectedSortable"
		}).disableSelection();

		$( "#assignGroupToQuizSortable1, #assignGroupToQuizSortable2" ).sortable({
				stop: function( event, ui ) {
					console.log("changed");
					var assignedGroups = [];
					$( "#assignGroupToQuizSortable1 li").each(function(index, elem) {
						assignedGroups.push($(elem).attr('id'));
					});
					console.log("assignedGroups: " + JSON.stringify(assignedGroups));
					$.ajax({
						url: 'modules/actionHandler.php',
						type: "get",
						data: "action=changeAssignedGroups&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>+"&groups="+JSON.stringify(assignedGroups),
						success: function(output) 
						{
							//alertify
							if(output == "ok") {
								
								console.log("success: " + output);
				                $("#groupAddSuccess").slideDown(1000);   
				                $("#groupAddSuccess").fadeTo(2000, 500).slideUp(1000);   
							} else {
								console.log("error: " + output);
				                $("#groupAddError").slideDown(1000);   
				                $("#groupAddError").fadeTo(2000, 500).slideUp(1000); 
							}
						}, error: function(output)
						{
							console.log("error: " + output);
			                $("#groupAddError").slideDown(1000);   
			                $("#groupAddError").fadeTo(2000, 500).slideUp(1000); 
						}
					});
				}
			});
		
	});
    
</script>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $mode == "create" ? $lang["createQuiz"] : str_replace("[1]", '&laquo;' . $quizFetch["name"] . '&raquo;', $lang["editQuiz"]);?></h1>
	</div>
	<?php if($_GET["code"] != 0) {?>
	<p id="createEditQuizActionResult" style="color:<?php echo $errorCode->getColor();?>;"><?php echo $errorCode->getText();?></p>
	<?php }?>
	<p><?php echo $lang["requiredFields"];?></p>
	<form id="createQuiz"
		action="<?php echo "?p=actionHandler&action=insertQuiz&mode=" . $mode;?>"
		class="form-horizontal" method="POST" enctype="multipart/form-data"
		onsubmit="return formCheck()">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo $lang["generalInformations"]?></h3>
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="quizText" class="col-md-2 col-sm-3 control-label">
						<?php echo $lang["quizCreateName"];?> *
					</label>
					<div class="col-md-10 col-sm-9">
						<input id="quizText" name="quizText" class="form-control"
							onchange="setConfirm(true, 'name')"
							required="required"
							placeholder="<?php echo $lang["quizCreateName"] . " (" . $lang["maximum"] . " " . $maxCharactersQuiz . " " . $lang["characters"] . ")";?>"
							maxlength="<?php echo $maxCharactersQuiz;?>" value="<?php 
							if($mode == "edit")
							{
								echo $quizFetch["name"];
							}
							?>"/>
					</div>
				</div>
				<div class="form-group">
					<label for="description" class="col-md-2 col-sm-3 control-label">
						<?php echo $lang["description"];?>
					</label>
					<div class="col-md-10 col-sm-9">
						<textarea name="description" id="description" onchange="setConfirm(true, 'desc')"
							class="form-control text-input" wrap="soft" placeholder="<?php echo $lang["descriptionOfQuiz"] . " (" . $lang["maximum"] . " " . $maxCharactersQuizDesc . " " . $lang["characters"] . ")";?>"><?php 
							if($mode == "edit")
							{
								echo $quizFetch["description"];
							}
							?></textarea>
					</div>
				</div>
				<div class="form-group">
					<label for="quizLogo" class="col-md-2 col-sm-3 control-label">
						<?php echo $lang["picture"];?>
					</label>
					<div class="col-md-10 col-sm-9" id="quizLogoWrapper">
						<input type="file" id="quizLogo" name="quizLogo" class="btn" onchange="setConfirm(true, 'pic')"
							accept=".jpeg,.jpg,.bmp,.png,.gif" />
						<div id="picturePreview">
						<?php if($mode == "edit" && $quizFetch["picture_link"] != "")
						{
							echo "<br /><img style=\"float:left;\" src=\"" . $quizFetch["picture_link"] . "\" width=\"200px\" height=\"75px\" ></img>";
							?>
							<img style="margin-left: 10px;" src="assets/icon_delete.png"
								alt="" title="" height="18px" width="18px"
								onclick="delPicture(<?php echo $quizFetch["id"];?>)">
							<?php 
						}?>	
						</div>
					</div>
				</div>
				<div class="form-group">
					<label for="language" class="col-md-2 col-sm-3 control-label"> 
						<?php echo $lang["quizLanguage"];?> *
					</label>
					<div class="col-md-10 col-sm-9">
						<select id="language" class="form-control" name="language" required="required" onchange="showNewLanguageInput()">
		                	<?php 
		                	$stmt = $dbh->prepare("select id from questionnaire");
		                	$stmt->execute();
		                	$allQuestionsCount = $stmt->rowCount();
		                	
		                    $stmt = $dbh->prepare("select language from questionnaire group by language");
		                    $stmt->execute();
		                    $result = $stmt->fetchAll();
		                    
		                    for($i = 0; $i < count($result); $i++){
								$stmt = $dbh->prepare("select id from questionnaire where language = '" . $result[$i]["language"] . "'");
								$stmt->execute();
								$selected = "";
								if($mode == "edit")
									$selected = ($quizFetch["language"] == $result[$i]["language"]) ? ' selected="selected"' : '';
								echo "<option value=\"" . $result[$i]["language"] . "\"" . $selected . ">" . $result[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")</option>";
		                    } ?>
		                    <option value="newLanguage"><?php echo $lang["requestNewLanguage"];?></option>
		                </select> 
		                <input type="text" id="newLanguage"
							class="form-control" name="newLanguage"
							placeholder="<?php echo $lang["newLanguagePlaceholder"];?>"
							maxlength="30" style="display:none;"/>
					</div>
				</div>
				<div class="form-group">
					<label for="applicationArea"
						class="col-md-2 col-sm-3 control-label"> 
						<?php echo $lang["quizTableTopic"];?> *
					</label>
					<div class="col-md-10 col-sm-9">
						<select id="topic" class="form-control" name="topic" required="required" onchange="showNewTopicInput()">
			                    <?php 
			                    $stmt = $dbh->prepare("select * from subjects");
			                    $stmt->execute();
			                    $result = $stmt->fetchAll();
			                    
			                    for($i = 0; $i < count($result); $i++){
									$stmt = $dbh->prepare("select id from questionnaire where subject_id = " . $result[$i]["id"]);
									$stmt->execute();
									$rowCount = $stmt->rowCount();
									
									$selected = "";
									if($mode == "edit")
										$selected = ($quizFetch["subject_id"] == $result[$i]["id"]) ? 'selected="selected"' : '';
									echo "<option value=\"" . $result[$i]["id"] . "\" " . $selected . ">" . $result[$i]["name"] . " (" . $rowCount . " " . $lang["quizzes"] . ")</option>";
			                    } ?>
			                    <option value="null"
								<?php if($mode != "edit") {echo ' selected="selected"';} else {
			                    	if($quizFetch["subject_id"] == NULL) {echo ' selected="selected"';}
			                    }?>>Nicht zugeordnet <?php 
				                    $stmt = $dbh->prepare("select id from questionnaire where subject_id is null");
				                    $stmt->execute();
				                    echo " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")";
			                    ?></option>
			                    <option value="newTopic"><?php echo $lang["requestNewTopic"];?></option>
						</select> 
						<input type="text" id="newTopic" class="form-control"
							name="newTopic"
							placeholder="<?php echo $lang["newTopicPlaceholder"] . " (" . $lang["maximum"] . " " . $maxCharactersTopic . " " . $lang["characters"] . ")";?>"
							maxlength="<?php echo $maxCharactersTopic?>"  style="display:none;"/>
					</div>
				</div>
				<?php if($mode == 'edit') {?>
				<div class="form-group">
					<label class="col-md-2 col-sm-3 control-label"> 
			        	<?php echo $lang["createdBy"];?>
					</label>
					<div class="col-md-10 col-sm-9">
						<p class="form-control-static">
						<?php 
							if($mode == "edit")
							{
								echo $quizFetch["firstname"] . " " . $quizFetch["lastname"];
							}
						?>
						</p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 col-sm-3 control-label"> 
			        	<?php echo $lang["createdAt"];?>
					</label>
					<div class="col-md-10 col-sm-9">
						<p class="form-control-static">
						<?php 
							if($mode == "edit")
							{
								echo date("d.m.Y H:i:s", $quizFetch["creation_date"]);
							}
						?>
						</p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 col-sm-3 control-label"> 
			        	<?php echo $lang["lastModified"];?>
					</label>
					<div class="col-md-10 col-sm-9">
						<p class="form-control-static">
						<?php 
							if($mode == "edit")
							{
								echo date("d.m.Y H:i:s", $quizFetch["last_modified"]);
							}
						?>
						</p>
					</div>
				</div>
				<?php }?>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo $lang["participationOptions"];?></h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2 col-sm-2">
						<label><?php echo $lang["noParticipationPeriod2"];?> *</label>
					</div>
					<div class="col-md-2 col-sm-2 radio-inline">
						<?php 
						if($mode == "edit")
						{
							$noParticipationPeriod2 = $quizFetch["noParticipationPeriod"];
						}
						?>
						<label for="noParticipationPeriod1" class="radio-inline"> 
						<input type="radio" id="noParticipationPeriod1" name="noParticipationPeriod" onchange="setDatesEnabled()"
							value="1" <?php echo $noParticipationPeriod2 == 1 || $mode == "create" ? 'checked':'';?> /> <?php echo $lang["noParticipationPeriod3"];?>
						</label>
					</div>
					<div class="col-md-8 col-sm-8">
						<label for="noParticipationPeriod0" class="radio-inline"> 
						<input type="radio" id="noParticipationPeriod0" name="noParticipationPeriod" onchange="setDatesEnabled()"
							value="0" <?php echo $noParticipationPeriod2 == 0 ? 'checked':'';?> />
						<?php echo $lang["quizStartDate"];?>
						<input type="text" id="startDate" name="startDate" 
							style="width: 120px; display: inline;"
							value="<?php 
							$displayedTime = time();
							if($mode == "edit")
							{
								$displayedTime = $quizFetch["starttime"];
							}
							echo date("d.m.Y", $displayedTime);
							?>" class="form-control" required="required"/>
						<input type="time" id="startTime" name="startTime" onchange="setConfirm(true, 'starttime')"
							style="width: 90px; display: inline;"
							value="<?php echo date("H:i", $displayedTime); ?>" class="form-control" required="required"/> (h:min)
						<br />
						<?php echo $lang["quizEndDate"];?>
						<input type="text" id="endDate" name="endDate"
							style="width: 120px; display: inline;"
							value="<?php 
							$displayedTime = strtotime('+1 Week');
							if($mode == "edit")
							{
								$displayedTime = $quizFetch["endtime"];
							}
							echo date("d.m.Y", $displayedTime);
							?>"
							class="form-control" required="required"/> 
						<input type="time" id="endTime" onchange="setConfirm(true, 'endTime')"
							name="endTime" style="width: 90px; display: inline;"
							value="<?php echo date("H:i", $displayedTime);?>" class="form-control" required="required"/> (h:min)
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2">
						<label><?php echo $lang["quizTimeLimitation"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
							$timeLimit = "00:00";
							$noLimitChecked = true;
							if($mode == "edit")
							{
								if($quizFetch["limited_time"] != 0)
								{
									$timeLimit = gmdate("i:s", $quizFetch["limited_time"]);
									$noLimitChecked = false;
								}
							}
						?>
						<label for="radioNoneTimeLimit" class="radio-inline"> 
						<input type="radio" id="radioNoneTimeLimit" name="timeLimitMode" onchange="setConfirm(true, 'timelimit')"
							value="0" <?php echo $noLimitChecked ? 'checked':'';?> /> <?php echo $lang["noLimitation"];?>
		                </label>
						<label for="radioQuizTimeLimit" class="radio-inline" style="margin-left: 22px;">
							<input style="margin-top: 11px;" type="radio" id="radioQuizTimeLimit" onchange="setConfirm(true, 'timelimit2')" name="timeLimitMode" value="1" <?php echo $noLimitChecked ? '':'checked';?> /> 
							<input type="time" id="quizTimeLimit" class="form-control" onchange="setConfirm(true)" name="quizTimeLimit" style="width: 90px; display: inline;" 
								value="<?php echo $timeLimit;?>" onfocus="setChecked('radioQuizTimeLimit')"/> (min:s)
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2">
						<label><?php echo $lang["amountOfQuestions"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
							$questionAmount = "0";
							$questionAmountChecked = true;
							if($mode == "edit")
							{
								if($quizFetch["amount_of_questions"] != 0)
								{
									$questionAmount = $quizFetch["amount_of_questions"];
									$questionAmountChecked = false;
								}
							}
						?>
						<label for="radioNoneQuestionAmount" class="radio-inline"> 
							<input type="radio" id="radioNoneQuestionAmount" name="amountQuestionMode" onchange="setConfirm(true, 'questionamount')"
								value="0" <?php echo $questionAmountChecked ? 'checked':'';?> /> <?php echo $lang["noQuestionAmount"];?>
		                </label>
						<label for="radioNoneQuestionMode" class="radio-inline">
							<input style="margin-top: 11px;" type="radio" id="radioNoneQuestionMode" onchange="setConfirm(true, 'questionamount2')" name="amountQuestionMode" value="1" <?php echo $questionAmountChecked ? '':'checked';?> />
							<input type="number" id="amountOfQuestions" name="amountOfQuestions" onchange="setConfirm(true, 'questionamount3')" class="form-control" style="width: 90px; display: inline;" value="<?php 
								echo $questionAmount;
							?>" required="required"  onfocus="setChecked('radioNoneQuestionMode')"/>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["amountMaxParticipations"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
							$maxParticipations = "0";
							$maxParticipationsChecked = true;
							if($mode == "edit")
							{
								if($quizFetch["amount_participations"] != 0)
								{
									$maxParticipations = $quizFetch["amount_participations"];
									$maxParticipationsChecked = false;
								}
							}
						?>
						<label for="radioNoneMaxParticipations" class="radio-inline"> 
						<input type="radio" id="radioNoneMaxParticipations" name="maxParticipationsMode" onchange="setConfirm(true, 'maxpart')"
							value="0" <?php echo $maxParticipationsChecked ? 'checked':'';?> /> <?php echo $lang["maxParticipations"];?>
		                </label>
						<label for="maxParticipationsMode" class="radio-inline">
							<input style="margin-top: 11px;" type="radio" id="maxParticipationsMode" onchange="setConfirm(true, 'maxpart')" name="maxParticipationsMode" value="1" <?php echo $maxParticipationsChecked ? '':'checked';?> />
							<input type="number" id="maxParticipations" name="maxParticipations" onchange="setConfirm(true, 'maxpart')" class="form-control" style="width: 90px; display: inline;" value="<?php 
								echo $maxParticipations;
							?>" required="required"  onfocus="setChecked('maxParticipationsMode')"/>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2">
						<label><?php echo $lang["quizPriority"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
						$resultChecked = 0;
						if($mode == "edit")
							$resultChecked = $quizFetch["priority"];
						?>
						<select name="quizPriority" class="form-control" style="width: 195px; display: inline;" onchange="setConfirm(true, 'priority')" required="required">
							<option value="0" <?php echo $resultChecked == 0 ? 'selected' : '';?>><?php echo $lang["prioLearningHelp"];?></option>
							<option value="1" <?php echo $resultChecked == 1 ? 'selected' : '';?>><?php echo $lang["prioExamRequirement"];?></option>
							<option value="2" <?php echo $resultChecked == 2 ? 'selected' : '';?>><?php echo $lang["prioExam"];?></option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["quizPassed"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
							$quizPassed = "80";
							$quizPassedChecked = false;
							if($mode == "edit")
							{
								if($quizFetch["quiz_passed"] != 0)
								{
									$quizPassed = $quizFetch["quiz_passed"];
									$quizPassedChecked = false;
								} else {
									$quizPassed = 0;
									$quizPassedChecked = true;
								}
							}
						?>
						<label for="radioNoneQuizPassed" class="radio-inline"> 
						<input type="radio" id="radioNoneQuizPassed" name="quizPassedMode" onchange="setConfirm(true, 'passedmode')" 
							value="0" <?php echo $quizPassedChecked ? 'checked':'';?> /> <?php echo $lang["noPassing"];?>
		                </label>
						<label for="quizPassedMode" class="radio-inline">
							<input style="margin-top: 11px;" type="radio" id="quizPassedMode" onchange="setConfirm(true, 'passedmode2')" name="quizPassedMode" value="1" <?php echo $quizPassedChecked ? '':'checked';?> />
							<input type="number" id="quizPassed" name="quizPassed" onchange="setConfirm(true, 'passedmode3')" class="form-control" style="width: 90px; display: inline;" value="<?php 
								echo $quizPassed;
							?>" required="required"  onfocus="setChecked('quizPassedMode')"/>%
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["singlechoiseMult"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
						$singlechoiseMult = 2;
						if($mode == "edit")
						{
							$singlechoiseMult = $quizFetch["singlechoise_multiplier"];
						}
						?>
						<input type="number" id="singlechoiseMult" name="singlechoiseMult" onchange="setConfirm(true, 'singlechoise')" class="form-control" style="width: 90px; display: inline;" value="<?php 
							echo $singlechoiseMult; ?>" required="required" /><img id="singlechoiseMultHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Um Singlechoisefragen gegen&uuml;ber Multiplechoisefragen nicht abzuwerten k&ouml;nnen diese mit einem Multiplizierer aufgewertet werden" width="18" height="18">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["showQuizTaskPaperSelection"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
						$showQuizTaskPaper = 0;
						if($mode == "edit")
						{
							$showQuizTaskPaper = $quizFetch["showTaskPaper"];
						}
						?>
						<label for="showQuizTaskPaper" style="float: left;">
							<input type="checkbox" id="showQuizTaskPaper" name="showQuizTaskPaper" onchange="setConfirm(true, 'showQuizTaskPaper')" <?php if($showQuizTaskPaper == 1) echo "checked";?>/>
						</label>
						<img id="showQuizTaskPaperHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 10px; margin-top: 11px;" original-title="Wenn eingeschalten, k&ouml;nnen die Aufgabenbl&auml;tter nur eingesehen werden, wenn mind. einmal dran teilgenommen wurde, ansonsten immer." width="18" height="18">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["moreOptions"];?></label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<div>
							<div class="checkbox">
								<label> <input type="checkbox" name="randomizeQuestions"
									value="1" <?php 
									if($mode == "edit")
										echo $quizFetch["random_questions"]==1 ? 'checked':'';
									else 
										echo "checked";
									?> /><?php echo $lang["randomQuestions"];?>
			                    </label>
							</div>
						</div>
						<div>
							<div class="checkbox">
								<label> <input type="checkbox" name="randomizeAnswers" value="1"
									<?php
									if($mode == "edit")
										echo $quizFetch["random_answers"]==1 ? 'checked':'';
									else
										echo "checked";
									?> /><?php echo $lang["randomAnswers"];?>
			                    </label>
							</div>
						</div>
						<div>
							<div class="checkbox">
								<label> <input type="checkbox" name="isPublic" value="1" 
								<?php 
								if($mode == "edit")
								{
									if($quizFetch["public"] == 1)
										echo "checked";
								} else {
									echo "checked";
								}
								?> /><?php echo $lang["quizPublic"];?>
			                    </label>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["quizResultShow"];?></label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
                    	<?php 
                    	
                    	$resultChecked = 1;
                    	$pointsChecked = 1;
                    	if($mode == "edit")
                    	{
                    		$resultChecked = $quizFetch["result_visible"];
                    		$pointsChecked = $quizFetch["result_visible_points"];
                    	}
                    	?>
                    	
                    	<?php echo $lang["showPointsOption"] . ":";?><br />
                    	<label class="radio-inline">
                    		<input type="radio" name="reportAfterQuizPoints" value="1" id="reportAfterQuizPoints1" <?php echo $pointsChecked == 1 ? 'checked':'';?>/>
                    		 <?php echo $lang["showResultAtTheEndPointsYes"];?>
                    	</label>
                    	<label class="radio-inline">
                    		<input type="radio" name="reportAfterQuizPoints" value="2" id="reportAfterQuizPoints2" <?php echo $pointsChecked == 2 ? 'checked':'';?>/>
                    		<?php echo $lang["showResultAtTheEndPointsNo"];?>
                    	</label>
                    	
                    	<br /><br />
                    	<div>
	                    	<?php echo $lang["showDetailedInformationOption"] . ":";?><br />
	                    	<label class="radio" style="font-weight: normal">
	                    		<input type="radio" name="reportAfterQuizResults" value="3" id="reportAfterQuizResults3" <?php echo $resultChecked == 3 ? 'checked':'';?>/> <?php echo $lang["showResultNever"];?>
	                    	</label>
							<label class="radio" style="font-weight: normal">
								<input type="radio" name="reportAfterQuizResults" value="2" id="reportAfterQuizResults2" <?php echo $resultChecked == 2 ? 'checked':'';?>/> <?php echo $lang["showResultAtTheEnd"];?>
							</label>
							<label class="radio" style="font-weight: normal">
								<input type="radio" name="reportAfterQuizResults" value="1" id="reportAfterQuizResults1" <?php echo $resultChecked == 1 ? 'checked':'';?>/> <?php echo $lang["showResultAtTheEndDetailed"];?>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo $lang["addChangeQuestions"];?></h3>
			</div>
			<div class="panel-body">
				<?php 
				if($mode == "edit")
				{
					$stmt = $dbh->prepare("select questionnaire.owner_id, question_id, question.text, type_id from qunaire_qu inner join question on question.id = qunaire_qu.question_id inner join questionnaire on questionnaire.id = qunaire_qu.questionnaire_id where questionnaire_id = " . $quizFetch["id"] . " order by qunaire_qu.order");
					$stmt->execute();
					$fetchQnaireQu = $stmt->fetchAll(PDO::FETCH_ASSOC);

				?>
				<table class="tblListOfQuestions" id="tblListOfQuestions" style="width: 100%">
		            <thead>
		                <tr>
		                	<th style="display: none;"></th>
		                    <th><?php echo $lang["positionShort"];?></th>
		                    <th></th>
		                    <th><?php echo $lang["questionQuestionText"];?></th>
		                    <th><?php echo $lang["questionAmountAnswers"];?></th>
		                    <th><?php echo $lang["results"];?></th>
		                    <th><?php echo $lang["quizTableActions"];?></th>
		                </tr>
		            </thead>
		            <tbody id="sortable">
		            	<?php for ($i=0; $i < count($fetchQnaireQu); $i++) {
                        	$qType = "singlechoice";
	                        if($fetchQnaireQu[$i]["type_id"] == 2)
	                        	$qType = "multiplechoice";
                        ?>
		            	<tr id="<?php echo "question_" . $fetchQnaireQu[$i]["question_id"];?>">
		            		<td class="qId" style="display: none;"><?php echo $fetchQnaireQu[$i]["question_id"];?></td>
		            		<td><?php echo ($i+1);?></td>
		            		<td>
		            		<img alt="up" src="assets/icon_downUp.png" width="18" height="18" style="cursor: move;">
		            		</td>
		            		<td><img width="15" height="15" class="questionTypeInfo" original-title="<?php echo $qType;?>" style="margin-right: 5px; margin-bottom: 3px;" src="assets/icon_<?php echo $qType;?>.png"><?php echo $fetchQnaireQu[$i]["text"];?></td>
		            		<td>
		            		<?php 
			            		$stmt = $dbh->prepare("select answer_id from answer_question where question_id = :qId");
			            		$stmt->bindParam(":qId", $fetchQnaireQu[$i]["question_id"]);
			            		$stmt->execute();
		            		
		            			echo $stmt->rowCount();
							?>
		            		</td>
		            		<td>
			            		<?php 
	                        	$stmt = $dbh->prepare("select * from an_qu_user inner join answer_question on answer_question.answer_id = an_qu_user.answer_id where an_qu_user.question_id = :qId");
	                        	$stmt->bindParam(":qId", $fetchQnaireQu[$i]["question_id"]);
	                        	$stmt->execute();
	                        	$fetchAnQuUser = $stmt->fetchAll(PDO::FETCH_ASSOC);
	                        	$correctCounter = 0;
	                        	$totalCounter = 0;
	                        	for($j = 0; $j < count($fetchAnQuUser); $j++)
	                        	{
	                        		if($fetchAnQuUser[$j]["is_correct"] == 1 && $fetchAnQuUser[$j]["selected"] == 1)
	                        		{
	                        			$correctCounter++;
	                        		} else if($fetchAnQuUser[$j]["is_correct"] == -1 && $fetchAnQuUser[$j]["selected"] == -1)
	                        		{
	                        			$correctCounter++;
	                        		}
	                        		if($fetchAnQuUser[$j]["is_correct"] != 0)
	                        		{
	                        			$totalCounter++;
	                        		}
	                        	}
	                        	if($totalCounter != 0)
	                        		echo "(".$correctCounter."/".$totalCounter.") " . number_format(($correctCounter*100)/$totalCounter, 1) . "%";
	                        	else 
	                        		echo "-";
	                        	?>
		            		</td>
		            		<td>
		            		<?php if($_SESSION['role']['admin'] == 1 || $fetchQnaireQu[$i]["owner_id"] == $_SESSION["id"]) {?>
	                            <a href="?p=createEditQuestion&mode=edit&fromsite=createEditQuiz&quizId=<?php echo $_GET["id"];?>&id=<?php echo $fetchQnaireQu[$i]["question_id"];?>" class="editQuestion" original-title="Frage bearbeiten"><img id="editQuestion" src="assets/icon_edit.png" alt="" height="18px" width="18px"></a>&nbsp;
	                            <img id="delQuestionImg" style="cursor: pointer;" class="deleteQuestion delQuestionImg" src="assets/icon_delete.png" alt="" original-title="Frage aus dieser Lernkontrolle l&ouml;schen" height="18px" width="18px" onclick="delQuestion(<?php echo $fetchQnaireQu[$i]["question_id"];?>)"><br />
	                        <?php }?>
		            		</td>
		            	</tr>
		            	<?php }?>
		            </tbody>
		        </table>
		        <?php }?>
		        <br />
		        <div style="margin-top: 54px;">
					<input id="btnAddQuestion" name="btnAddQuestion" class="btn" onclick="setConfirm(false, 'btn3')" type="submit" value="<?php echo $lang["addNewQuestion"];?>" /><br />
					<input id="btnImportQuestion" name="btnImportQuestion" class="btn" type="button" value="<?php echo $lang["importQuestionsFromCSV"];?>" onclick="openFileDialog()" style="margin-top: 10px;"/>&nbsp;<span id="fileName"><?php echo " <b>" . $lang["noFileSelected"] . "</b>";?></span><br />
					<label class="radio-inline">
						<input type="radio" name="addOrReplaceQuestions" value="0" style="margin-left: 30px;">
						<?php echo $lang["replaceQuestions"];?>
					</label><br />
					<label class="radio-inline">
						<input type="radio" name="addOrReplaceQuestions" value="1" checked style="margin-left: 30px;">
						<?php echo $lang["addQuestions"];?>
					</label>
				</div>
				<input type="file" style="opacity:0;position:absolute;top:-999px;left:-999px;display:none;" name="btnImportQuestionsFromCSV2" id="btnImportQuestionsFromCSV2" accept=".csv" />
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading" id="assignQuizToGroup">
				<h3 class="panel-title"><?php echo $lang["assignQuizToGroupHeading"];?></h3>
			</div>
			<div class="panel-body" id="assignQuizToGroup" style="padding: 0px;">
				<div class="alert alert-success alert-dismissable" id="groupAddSuccess" style="border-radius: 0 0 4px 4px;">
				    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
				    <strong>Erfolgreich! </strong>
				    Gruppe zugeordnet.
				</div>
				<div class="alert alert-danger alert-dismissable" id="groupAddError" style="border-radius: 0 0 4px 4px;">
				    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
				    <strong>Fehler! </strong>
				    Es ist ein Fehler aufgetreten.
				</div>
				<div class="form-horizontal">
					<div class="col-md-3 col-sm-3" style="margin: 20px 0px 20px 0px;"> 
						<label><?php echo $lang["assignQuizToGroupInfo1"];?></label>
						<div style="min-height: 75px; max-height: 200px; overflow-y: scroll;">
							<ul id="assignGroupToQuizSortable1" class="assignGroupToQuizCconnectedSortable">
								<?php 
								$stmt = $dbh->prepare("select assign_group_qunaire.*, `group`.name, (select count(*) from user where group_id = `group`.id) as memberCount from assign_group_qunaire inner join `group` on `group`.id = assign_group_qunaire.group_id where questionnaire_id = :qId");
								$stmt->bindParam(":qId", $_GET["id"]);
								$stmt->execute();
								$fetchAssignedGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								for($i = 0; $i < count($fetchAssignedGroups); $i++)
								{
								?>
									<li class="ui-state-default groupName" original-title="<?php echo $fetchAssignedGroups[$i]["name"];?>" id="<?php echo $fetchAssignedGroups[$i]["group_id"];?>"><?php echo strlen($fetchAssignedGroups[$i]["name"]) > 19 ? substr($fetchAssignedGroups[$i]["name"], 0, 19) . "..." : $fetchAssignedGroups[$i]["name"]; echo " (" . $fetchAssignedGroups[$i]["memberCount"] . ")";?></li>
								<?php }?>
							</ul>
						</div>
					</div>
					<div class="col-md-1 col-sm-1" style="margin-top: 25px; text-align: center;"> 
						<img style="margin-left: 8px;" alt="drag and drop" src="assets/arrow-leftRight.png" width="56" height="32">
						<span style="font-size: 10px;">Drag & Drop</span>
					</div>
					<div class="col-md-3 col-sm-3" style="margin: 20px 0px 20px 0px;"> 
						<label><?php echo $lang["assignQuizToGroupInfo2"];?></label>
						<div style="min-height: 75px; max-height: 200px; overflow-y: scroll;">
							<ul id="assignGroupToQuizSortable2" class="assignGroupToQuizCconnectedSortable">
								<?php 
								$stmt = $dbh->prepare("select *, (select count(*) from user where group_id = `group`.id) as memberCount  from `group` where id not in (select group_id from assign_group_qunaire where questionnaire_id = :qId)");
								$stmt->bindParam(":qId", $_GET["id"]);
								$stmt->execute();
								$fetchGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								for($i = 0; $i < count($fetchGroups); $i++)
								{
								?>
									<li class="ui-state-default groupName" original-title="<?php echo $fetchGroups[$i]["name"];?>" id="<?php echo $fetchGroups[$i]["id"];?>"><?php echo strlen($fetchGroups[$i]["name"]) > 19 ? substr($fetchGroups[$i]["name"], 0, 19) . "..." : $fetchGroups[$i]["name"]; echo " (" . $fetchGroups[$i]["memberCount"] . ")"?></li>
								<?php }?>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading" id="additionalSettingsHeading">
				<span id="arrow1" style="float: left; margin-right: 7px;">&#9654;</span>
				<h3 class="panel-title"><?php echo $lang["additionalsettings"];?></h3>
			</div>
			<div class="panel-body" id="additionalSettingsContent">
				<div class="row">
					<div class="col-md-3 col-sm-3"> 
						<label><?php echo $lang["assignQuizToMember"];?><img id="assignationHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Hier k&ouml;nnen Benutzer eingetragen werden, welche die Berechtigungen bekommen dieses Quiz zu bearbeiten oder die Ergebnisse einzusehen" width="18" height="18"></label>
					</div>
					<div class="col-md-9 col-sm-9 radio-inline" style="padding-top: 0px;">
						<input type="email" id="autocompleteUsers"><img style="margin-left: 8px;" alt="add" src="assets/arrow-right.png" width="28" height="32" onclick="addCreator(<?php echo $quizFetch["id"];?>)">
					</div>
				</div>
				<div class="row">
					<div style="margin: 0px 0px 0px 10px;" id="ajaxAnswer">
					</div>
				</div>
				<div class="row">
					<?php 
					$quizId = -1;
					if($mode == "edit")
					{
						$quizId = $_GET["id"];
					}
					$stmt = $dbh->prepare("select user.email, user_id from qunaire_assigned_to inner join user on user.id = qunaire_assigned_to.user_id where questionnaire_id = :qId");
					$stmt->bindParam(":qId", $quizId);
					$stmt->execute();
					$fetchAssigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
					?>
					<table class="assignTbl" id="assignTbl" style="width: 350px; margin: 0px 0px 0px 10px;">
			            <thead>
			                <tr>
			                    <th><?php echo $lang["email"];?></th>
			                    <th><?php echo $lang["quizTableActions"];?></th>
			                </tr>
			            </thead>
			            <tbody>
			            	<?php for ($i = 0; $i < count($fetchAssigns); $i++) {?>
			            	<tr id="<?php echo "assignation_" . $fetchAssigns[$i]["user_id"];?>">
			            		<td><?php echo $fetchAssigns[$i]["email"];?></td>
			            		<td><img id="delAssignedId" class="deleteAssigned delAssignedImg" src="assets/icon_delete.png" style="cursor: pointer;" alt="" original-title="Berechtigung entziehen" height="18px" width="18px" onclick="delAssigned(<?php echo $fetchAssigns[$i]["user_id"];?>)"></td>
			            	</tr>
			            	<?php }?>
			            </tbody>
		        	</table>
	        	</div>
			</div>
		</div>
		<div style="float: left;">
			<input type="button" class="btn" id="btnBackToOverview" value="<?php echo $lang["buttonBackToOverview"];?>" onclick="window.location='?p=quiz';"/>
		</div>
		<input type="hidden" name="mode" value="<?php echo $mode;?>">
		<?php if($mode == "edit"){?>
			<input type="hidden" name="quiz_id" value="<?php echo $quizFetch["id"];?>">
		<?php }?>
		<div style="float: right; padding-left: 10px;">
			<input type="hidden" name="btnSave" value="<?php echo $lang["buttonSaveAndPublish"];?>" />
			<input type="submit" class="btn" id="btnSave" name="btnSave" onclick="setConfirm(false, 'btn1')" value="<?php echo $lang["buttonSaveAndPublish"];?>" />
		</div>
		<div style="float: right;">
			<input type="submit" class="btn" id="btnSaveAsDraft" onclick="setConfirm(false, 'btn2')" name="btnSaveAsDraft" value="<?php echo $lang["buttonSaveDraft"];?>" />
		</div>
	</form>
</div>