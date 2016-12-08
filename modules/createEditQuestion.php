<?php 

	if($_SESSION["role"]["creator"] != 1)
	{
		header("Location: ?p=quiz&code=-1");
		exit;
	}
	

	global $dbh;
	
	$mode = "create";
	if(isset($_GET["mode"]))
	{
		$mode = $_GET["mode"];
	}
	
	if($mode == "edit")
	{
		//check if owner or admin
		$stmt = $dbh->prepare("select owner_id from question where id = :question_id");
		$stmt->bindParam(":question_id", $_GET["id"]);
		$stmt->execute();
		$fetchQuestionOwner = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(!$_SESSION["role"]["creator"] || ($fetchQuestionOwner["owner_id"] != $_SESSION["id"] && $_SESSION["role"]["admin"] != 1))
		{
			header("Location: ?p=quiz&code=-1");
			exit;
		}
		
		
		//get question
		if(isset($_GET["id"]))
		{
			$stmt = $dbh->prepare("select question.*, user_data.firstname, user_data.lastname from question inner join user on user.id = question.owner_id inner join user_data on user_data.user_id = user.id where question.id = :id");
			$stmt->bindParam(":id", $_GET["id"]);
			$stmt->execute();
			if($stmt->rowCount() > 0)
				$questionFetch = $stmt->fetch(PDO::FETCH_ASSOC);
			else 
			{
				header("Location: ?p=quiz&code=-48");
				exit;
			}
		} else {
			header("Location: ?p=quiz&code=-48");
			exit;
		}
		
	} else if($mode == "create")
	{		
		$language = "Deutsch";
		
		if($_SESSION["language"] == "en")
		{
			$language = "English";
		}
		
		$stmt = $dbh->prepare("insert into question	(text, owner_id, type_id, subject_id, language, creation_date, public, last_modified, picture_link)
							values ('', " . $_SESSION["id"] . ", 1, NULL, '" . $language . "', " . time() . ", 0, " . time() . ", NULL);");		
		if(!$stmt->execute())
		{
			header("Location: ?p=home&code=-21");
			exit;
		}
		
		$newQuestionId = $dbh->lastInsertId();
		
		
		//Call from questionnaire-site -> add question to this questionnaire
		if(isset($_GET["fromsite"]) && $_GET["fromsite"] == "createEditQuiz")
		{
			if(!isset($_GET["quizId"]))
			{
				header("Location: ?p=quiz&code=-46");
				exit;
			}
			
			$stmt = $dbh->prepare("select count(question_id) as total from qunaire_qu where questionnaire_id = :qunaireId");
			$stmt->bindParam(":qunaireId", $_GET["quizId"]);
			$stmt->execute();
			
			$fetchAmoutOfQuestions = $stmt->fetch(PDO::FETCH_ASSOC);
			$nextOrder = $fetchAmoutOfQuestions["total"]; //order starts with 0
			
			$stmt = $dbh->prepare("insert into qunaire_qu values (:qunaireId, :questionId, :order)");
			$stmt->bindParam(":qunaireId", $_GET["quizId"]);
			$stmt->bindParam(":questionId", $newQuestionId);
			$stmt->bindParam(":order", $nextOrder);
			
			if(!$stmt->execute())
			{
				header("Location: ?p=quiz&code=-47");
				exit;
			}			
		}
	}
	
	const MAX_CHARACTERS_PER_QUESTION = 400;
	const MAX_CHARACTERS_PER_TOPIC = 30;
	const MAX_CHARACTERS_PER_ANSWER = 250;
?>

<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $mode == "create" ? $lang["createQuestion"] : $lang["editQuestion"];?></h1>
	</div>
	<?php if($code != 0) {?>
	<p style="color:red"><?php echo $codeText;?></p>
	<?php }?>
	<p><?php echo $lang["requiredFields"];?></p>
	
	<ul id="questionTab" class="nav nav-tabs">
		<li class="active"><a href="#questionTextTab" data-toggle="tab"><?php echo $lang["question"]?></a></li>
		<li><a href="#answers" data-toggle="tab"><?php echo $lang["answers"]?></a></li>
	</ul>
	
	<div id="questionTabContent" class="tab-content">
		<div class="tab-pane fade in active form-horizontal" id="questionTextTab">
			
			<!-- Question-Text -->
			<div class="form-group">
				<label for="questionText" class="col-md-2 col-sm-3 control-label">
					<?php echo $lang["questionQuestionTextOnly"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<textarea name="questionText" wrap="soft" id="questionText"
						class="form-control text-input" required="required"
						placeholder="<?php echo $lang["questionQuestionTextOnly"] . " (" . $lang["maximum"] . " " . MAX_CHARACTERS_PER_QUESTION . " " . $lang["characters"] . ")";?>" maxlength="<?php echo MAX_CHARACTERS_PER_QUESTION;?>"><?php 
						if($mode == "edit")
						{
							echo $questionFetch["text"];
						}
						?></textarea>
				</div>
			</div>
			
			
			<!-- Keywords -->
			<div class="form-group">
				<label for="keywords" class="col-md-2 col-sm-3 control-label">
					<?php echo $lang["navHeaderKeywords"];?>
				</label>
				<div class="col-md-10 col-sm-9">
				<?php 
				if($mode == "edit" && isset($_GET["id"]))
				{
					$stmt = $dbh->prepare("select keyword.word from qu_keyword inner join keyword on keyword.id = qu_keyword.keyword_id where qu_keyword.qu_id = :qu_id");
					$stmt->bindParam(":qu_id", $_GET["id"]);
					$stmt->execute();
					$keywordFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
				}
				?>
					<input type="text" name="keywords" id="keywords" class="form-control"
						value="<?php 
						if($mode == "edit")
						{
							for($i = 0; $i < count($keywordFetch); $i++)
							{
								if($i > 0)
									echo ",";
								echo $keywordFetch[$i]["word"];
							}
						}
						?>" data-role="tagsinput" />
				</div>
			</div>
			
			
			<!-- Language -->
			<div class="form-group">
				<label for="language" class="col-md-2 col-sm-3 control-label"> 
					<?php echo $lang["quizLanguage"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<select id="language" class="form-control" name="language" onchange="showNewLanguageInput()">
	                	<?php
	                    $stmt = $dbh->prepare("select language from question group by language");
	                    $stmt->execute();
	                    $result = $stmt->fetchAll();
	                    
	                    for($i = 0; $i < count($result); $i++){
							$stmt = $dbh->prepare("select id from question where language = '" . $result[$i]["language"] . "'");
							$stmt->execute();
							$selected = $language;
							if($mode == "edit")
							{
								$selected = ($questionFetch["language"] == $result[$i]["language"]) ? 'selected' : '';
							} else if($mode == "create") {
								$selected = ($language == $result[$i]["language"]) ? 'selected' : '';
							}
							echo "<option value=\"" . $result[$i]["language"] . "\"" . $selected . ">" . $result[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")</option>";
	                    } ?>
	                	<option value="newLanguage"><?php echo $lang["requestNewLanguage"];?></option>
	                </select>
	                <?php
						if($mode == "edit")
						{
							$stmt = $dbh->prepare("select language from language_request where question_id = :questionId");
							$stmt->bindParam(":questionId", $questionFetch["id"]);
							$stmt->execute();
							$fetchLanguage = $stmt->fetch(PDO::FETCH_ASSOC);
							
							if(isset($fetchLanguage["language"]))
							{
								echo "<p style='margin-top: 10px; margin-bottom:5px;'>" . $lang["requested"] . ":</p>";
							}
						}
						
						echo $style;
						?>
	                <input type="text" id="newLanguage"
						class="form-control" name="newLanguage"
						<?php
						
						$style = "style='display: none'";
						
						if(isset($fetchLanguage["language"]))
						{
							$style = "style='display: initial' value=" . $fetchLanguage["language"];
						}
						
						echo $style;
						?>
						placeholder="<?php echo $lang["newLanguagePlaceholder"];?>" maxlength="30"/>
				</div>
			</div>
			
			
			<!-- Topic -->
			<div class="form-group">
				<label for="applicationArea" class="col-md-2 col-sm-3 control-label"> 
					<?php echo $lang["quizTableTopic"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<select id="topic" class="form-control" name="topic" onchange="showNewTopicInput()">
		                    <?php 
		                    $stmt = $dbh->prepare("select * from subjects");
		                    $stmt->execute();
		                    $result = $stmt->fetchAll();
		                    
		                    for($i = 0; $i < count($result); $i++){
								$stmt = $dbh->prepare("select id from question where subject_id = " . $result[$i]["id"]);
								$stmt->execute();
								$rowCount = $stmt->rowCount();
								
								$selected = "";
								if($mode == "edit")
									$selected = ($questionFetch["subject_id"] == $result[$i]["id"]) ? 'selected="selected"' : '';
								echo "<option value=\"" . $result[$i]["id"] . "\" " . $selected . ">" . $result[$i]["name"] . " (" . $rowCount . " " . $lang["quizzes"] . ")</option>";
		                    } ?>
		                    <option value="null" <?php if($mode != "edit") {echo ' selected="selected"';} else {
		                    	if($questionFetch["subject_id"] == NULL) {echo ' selected="selected"';}
		                    }?>>Nicht zugeordnet <?php 
			                    $stmt = $dbh->prepare("select id from question where subject_id is null");
			                    $stmt->execute();
			                    echo " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")";
		                    ?></option>
		                	<option value="newTopic"><?php echo $lang["requestNewTopic"];?></option>
		                </select>
		                <?php
						if($mode == "edit")
						{
							$stmt = $dbh->prepare("select topic from topic_request where question_id = :questionId");
							$stmt->bindParam(":questionId", $questionFetch["id"]);
							$stmt->execute();
							$fetchTopic = $stmt->fetch(PDO::FETCH_ASSOC);
							
							if(isset($fetchTopic["topic"]))
							{
								echo "<p style='margin-top: 10px; margin-bottom:5px;'>" . $lang["requested"] . ":</p>";
							}
						}
						?>
					<input type="text" id="newTopic"
						class="form-control" name="newTopic"
						<?php
						
						$style = "style='display: none'";
						
						if(isset($fetchTopic["topic"]))
						{
							$style = "style='display: initial' value=" . $fetchTopic["topic"];
						}
						
						echo $style;
						?>
						placeholder="<?php echo $lang["newTopicPlaceholder"] . " (" . $lang["maximum"] . " " . MAX_CHARACTERS_PER_TOPIC . " " . $lang["characters"] . ")";?>" maxlength="<?php echo MAX_CHARACTERS_PER_TOPIC?>"/>
				</div>
			</div>
			
			
			<!-- Question-Image -->
			<div class="form-group">
				
				<form id="imageForm">
				
					<label for="questionLogo" class="col-md-2 col-sm-3 control-label">
						<?php echo $lang["picture"];?>
					</label>
					<div class="col-md-10 col-sm-9" id="questionLogoWrapper">
						<input type="file" id="questionLogo" name="questionLogo"
							style="<?php if($mode == "edit" && $questionFetch["picture_link"] != "") { echo "display: none;"; }?>"
							class="btn" accept=".jpeg,.jpg,.bmp,.png,.gif"/>
						<div id="picturePreview">
						<?php if($mode == "edit" && $questionFetch["picture_link"] != "")
						{
							echo "<br /><img style=\"float:left; max-width:300px; max-height:300px; width:70%; margin-bottom: 10px; margin-top: -20px\" src=\"" . $questionFetch["picture_link"] . "\" id=\"questionImage\"></img>";
						?>
							<img style="margin-left:10px; margin-top:-40px; height:18px; width:18px" src="assets/icon_delete.png" id="deleteQuestionLogo">
						<?php }?>
						</div>
					</div>
				</form>
			</div>
			
			
			<!-- Publication -->
			<div class="form-group" style="clear: both; padding-top: 15px">
				<div class="col-md-2 col-sm-3 control-label">
					<label> 
						<?php echo $lang["publication"];?> *
					</label>
				</div>
				<div class="col-md-10 col-sm-9 radio-inline" style="width: initial">
					<label class="radio-inline"> <input type="radio" name="isPrivate"
						value="0" required 
						<?php if($mode == "create") { echo "checked"; }
						else if($mode == "edit") {
							if($questionFetch["public"] == 0)
								echo " checked";
						}?>/> <?php echo $lang["public"];?>
					</label> 
					<label class="radio-inline" style="white-space: pre;"> <input type="radio"
						name="isPrivate" value="1"
						<?php if($mode == "edit") {
							if($questionFetch["public"] == 1)
								echo " checked";
						}?>/><?php echo $lang["privateMoreInfo"];?>
					</label>
				</div>
			</div>
			
			
			<!-- Last Edited -->
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
							echo $questionFetch["firstname"] . " " . $questionFetch["lastname"];
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
							echo date("d.m.Y H:i:s", $questionFetch["creation_date"]);
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
							echo date("d.m.Y H:i:s", $questionFetch["last_modified"]);
						}
					?>
					</p>
				</div>
			</div>
			<?php }?>
				
		</div>
		
		
		<!-- Answers -->
		<div class="tab-pane fade form-horizontal" id="answers">
		
			<!-- Question-Image -->
			<div class="form-group">
				
				<form id="imageForm">
					<label for="answerQuestionLogo" class="col-md-2 col-sm-3 control-label">
						<?php echo $lang["picture"];?>
					</label>
					<div class="col-md-10 col-sm-9" id="questionLogoWrapper">
						<div id="answerPicturePreview">
						<?php if($mode == "edit" && $questionFetch["picture_link"] != "")
						{
							echo "<br /><img style=\"float:left; max-width:300px; max-height:300px; width:70%; margin-bottom: 10px; margin-top: -20px;\" src=\"" . $questionFetch["picture_link"] . "\" id=\"answerQuestionImage\"></img>";
						} else
						{
							echo "<p style='padding-top: 7px;'>" . $lang["noPicture"] . "</p>";
						}
						?>
						</div>
					</div>
				</form>
			</div>
		
			<div class="form-group" style="clear: left;">
				<div class="col-md-2 col-sm-3 control-label">
					<label> 
		            	<?php echo $lang["showAs"];?> *
					</label>
				</div>
				<div class="col-md-10 col-sm-9 radio-inline" id="questionType">
					<label class="radio-inline"> <input type="radio"
						name="questionType" id="questionTypeSingleChoice"
						value="singlechoice" required
						<?php if($mode == "create") { echo "checked"; }
						else if($mode == "edit") {
							if($questionFetch["type_id"] == 1)
								echo " checked";
						}
						?> /> <?php echo $lang["singleChoice"];?>
					</label> 
					<label class="radio-inline"> <input type="radio"
						name="questionType" id="questionTypeMultipleChoice"
						value="multiplechoice" <?php if($mode == "edit") {
							if($questionFetch["type_id"] == 2)
								echo " checked";
						}?>/>
						<?php echo $lang["multipleChoice"];?>
					</label>
				</div>
			</div>
			
		
			<div class="form-group">
			
				<div class="panel-body" style="padding-top: 0;">
					<div class="row">
						<div class="col-md-2 col-sm-3 col-xs-3"></div>
						<div class="col-md-9 col-sm-7 col-xs-7"></div>
						<div class="col-md-1 col-sm-2 col-xs-2">
							<label id="correctAnswerHeading"> 
				            	<?php echo $lang["correctAnswer"];?>
							</label>
						</div>
					</div>
					<?php 
					if($mode == "edit")
					{
						$stmt = $dbh->prepare("select question.id as Qid, answer_question.*, answer.text from question inner join answer_question on answer_question.question_id = question.id inner join answer on answer_question.answer_id = answer.id where question.id = :id order by `order` asc;");
						$stmt->bindParam(":id", $_GET["id"]);
						$stmt->execute();
						$answerFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
					}
					for($i = 0; $i < 5; $i++) {
					?>
					<div class="row">
						<div class="col-md-2 col-sm-3 col-xs-3 control-label">
							<label> 
				            	<?php echo $lang["answertext"] . " " . ($i+1);
				            	if($i < 2)
				            		echo " *";
				            	?>
							</label>
						</div>
						<div class="col-md-9 col-sm-7 col-xs-10">
							<textarea id="<?php echo "answerText_" . $i;?>" name="<?php echo "answerText_" . $i;?>" wrap="soft" style="margin-bottom: 15px"
								class="form-control" <?php echo $i < 2 ? " required='required' " : ''?>
								placeholder="<?php echo $lang["answertext"] . " " . ($i+1) . " (" . $lang["maximum"] . " " . MAX_CHARACTERS_PER_ANSWER . " " . $lang["characters"] . ")";?>" maxlength="<?php echo MAX_CHARACTERS_PER_ANSWER;?>"><?php 
								if($mode == "edit")
								{
									if($i < $stmt->rowCount())
										echo $answerFetch[$i]["text"];
								}
								?></textarea>
								<input type="hidden" id="answerId_<?php echo $i;?>" value="<?php echo $answerFetch[$i]["answer_id"];?>" />
						</div>
						<div class="col-md-1 col-sm-2 col-xs-1">
							<input id="<?php echo "correctAnswer_" . $i;?>"
							type="<?php if($mode == "edit" && $questionFetch["type_id"] == 2)
										{
											echo "checkbox";
										} else 
										{
											echo "radio";
										}
										?>"
									name="correctAnswer" class="checkbox" value="1" 
								<?php if($mode == "edit")
								{
									if($i < $stmt->rowCount())
									{
										if($answerFetch[$i]["is_correct"] == 1)
											echo 'checked="checked"';
									}
								}
								?> 
							/>
						</div>
					</div>
					<?php }?>
				</div>
				
				
				<!-- Error-Messages -->
				<div class="form-group" style="clear: left;">
					<div class="col-md-2 col-sm-3 control-label"></div>
					<div class="col-md-10 col-sm-9">
						<p id="failMsg" style="color: red;"></p>
					</div>
				</div>
				
				
			</div>
		</div>
	</div>
	
	<div>
		<div style="text-align: left; float: left;">
			<input type="button" class="btn" id="btnBack"
				value="<?php echo $lang["btnBack"];?>"/>
		</div>

		<div style="text-align: right; margin-top: 10px;">
			<input type="hidden" name="mode" value="<?php echo $mode;?>">
			<input type="hidden" name="question_id" value="<?php echo ($mode == "edit") ? $questionFetch["id"] : $newQuestionId;?>">
			<input type="hidden" name="fromsite" value="<?php echo isset($_GET["fromsite"]) ? $_GET["fromsite"] : '';?>">
			<input type="hidden" name="fromQuizId" value="<?php echo isset($_GET["quizId"]) ? $_GET["quizId"] : '';?>">
			<input type="button" class="btn" id="btnSaveAndNext"
				value="<?php echo $lang["createNextQuestion"];?>" />
		</div>
	</div>
	
	
	
	
	<!-- HTML for Fullscreen-Image-View -->
	<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="pswp__bg"></div>
    <div class="pswp__scroll-wrap">

        <div class="pswp__container">
            <!-- don't modify these 3 pswp__item elements, data is added later on -->
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
        </div>

        <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
        <div class="pswp__ui pswp__ui--hidden">

            <div class="pswp__top-bar">
            
            <div class="pswp__counter"></div>

                <button id="closePhoto">Schliessen</button>
                
                <div class="pswp__preloader">
                    <div class="pswp__preloader__icn">
                      <div class="pswp__preloader__cut">
                        <div class="pswp__preloader__donut"></div>
                      </div>
                    </div>
                </div>
            </div>

            <div class="pswp__caption">
                <div class="pswp__caption__center"></div>
            </div>

          </div>
		</div>
	</div>
	
</div>




<script type="text/javascript" src="js/bootstrap-tabcollapse.js"></script>
<script type="text/javascript" src="js/photoSwipeController.js"></script>
<script type="text/javascript">

	$(document).ready(function() {
		
		$(document).on("change", "#questionText, #keywords, #language, #newLanguage, #topic, #newTopic, #questionLogo, " +
				"[name='isPrivate'], [name='questionType'], [name^='answerText_'], [name='correctAnswer']", updateQuestionData);

		$(document).on("click", "#deleteQuestionLogo", updateQuestionData);

		$("#answerQuestionImage").on("click", function () {
			$("#questionImage").click();
		});

		$(window).unload(function() {
			var id = $("[name=question_id]").val();
			if(window.location.href == "http://localhost/mobilequiz_v3/?p=createEditQuestion")
			{
				window.location.href  += "&mode=edit&id=" + id;
			}
		});

	});

	function updateQuestionData(event)
	{
		
		if(this.value == this.oldvalue && event.target.id != "deleteQuestionLogo") return;

		var target = event.target.id;
		if(target == "") {
			target = event.target.name;
		}
		
		var url = '?p=actionHandler&action=updateQuestion';
		var field;
		var data = new FormData();

		switch(target) {
			case "questionText":
				field = "questionText";
			    data.append("questionText", event.target.value);
				break;
			case "keywords":
				field = "keywords";
			    data.append("keywords", event.target.value);
				break;
			case "language":
			case "newLanguage":
				field = "language";
				data.append("language", event.target.value);
				break;
			case "topic":
			case "newTopic":
				field = "topic";
				data.append("topic", event.target.value);
				break;
			case "questionLogo":
				file = event.target.files[0];
				field = "addQuestionImage";
			    data.append("addQuestionImage", file, file.name);
				break;
			case "deleteQuestionLogo":
				field = "deleteQuestionImage";
				$("#questionLogo").val(null); //remove input-field value to enable selecting the same image twice
				break;
			case "isPrivate":
				field = "isPrivate";
				data.append("isPrivate", event.target.value);
				break;
			case "questionTypeSingleChoice":
			case "questionTypeMultipleChoice":
				field = "questionType";
				data.append("questionType", event.target.value);
				break;
		}

		if(event.target.id.startsWith("answerText_") || event.target.id.startsWith("correctAnswer_"))
		{
			var id = event.target.id;
			var numberPos = id.indexOf("_") + 1;
			var number = id.substring(numberPos, numberPos + 1);
			
			field = "answerText";
			data.append("answerId", $("#answerId_" + number).val());
			data.append("answerText", $("[name=answerText_" + number + "]").val());
			data.append("answerNumber", number);
			data.append("isCorrect", $("#correctAnswer_" + number).is(":checked"));
		}

		uploadChange(url, data, field);
	}

	function uploadChange(url, data, field) 
	{
		data.append("questionId", $("[name='question_id']").val());
		
		$.ajax({
	        url: url + '&field=' + field,
	        type: 'POST',
	        data: data,
	        cache: false,
	        dataType: 'json',
	        processData: false,
	        contentType: false,
	        success: function(data)
	        {
				switch(data.status)
				{
					case "OK":
						console.log("OK");
						break;
					case "ADDED":
						console.log("OK");		            	
		            	
		            	var parent = $("#picturePreview");
		            	var parent2 = $("#answerPicturePreview");
		            	var downloadingImage = $("<img>");
		            	var removeIcon = $("<img>");
		            	
		            	downloadingImage.load(function(){
			            	this.id = "questionImage";
		            		parent.append(this.cloneNode());

		            		this.id = "answerQuestionImage";
		            		this.onclick = function() { $("#questionImage").click(); };
		            		parent2.html("<br />");
		            		parent2.append(this);
		            	});

		            	removeIcon.load(function(){
		            		parent.append(this);	
		            	});

		            	downloadingImage.attr("style", "float:left; max-width: 300px; max-height: 300px; width: 70%; margin-bottom: 10px; margin-top: -20px");
		            	downloadingImage.attr("src", data.text);

		            	removeIcon.attr("style", "margin-left: 10px; margin-top:-40px; height: 18px; width: 18px");
		            	removeIcon.attr("id", "deleteQuestionLogo");
		            	removeIcon.attr("src", "assets/icon_delete.png");

		            	$("#picturePreview > span").remove();
		            	$("#questionLogo").hide();
						
		            	break;
					case "DELETED":
						console.log("OK");
						$("#questionImage").remove();
						$("#answerQuestionImage").remove();
						$("#deleteQuestionLogo").remove();
		            	$("#picturePreview").append("<span style=\"color:green;\">Bild erfolgreich entfernt.</span>");
		            	$("#answerPicturePreview").html("<div style='padding-top: 7px;'><p><?php echo $lang["noPicture"]?></p><div>");
		            	$("#questionLogo").show();
		            	break;
					case "ANSWER_INSERTED":
						console.log("OK");
						
						var insertedId = data.answerId;
						var answerNumber = data.answerNumber;						

						$("#answerId_" + answerNumber).attr("value", insertedId);
								
						break;
					case "ANSWER_DELETED":
						console.log("OK");
						
						var answerNumber = data.answerNumber;
						$("#answerId_" + answerNumber).attr("value", "");
								
						break;
					case "TYPE_CHANGED":

						if(data.text == "singlechoice")
						{
							$("[name='correctAnswer']").attr("type", "radio");
							$("[name='correctAnswer']").attr("checked", null);
						} else
						{
							$("[name='correctAnswer']").attr("type", "checkbox");
						}
						
						break;
					case "error":
						alert("Error: " + data.text);
						break;
				}
	        },
	        error: function()
	        {
	            console.log("Ajax couldn't send data");
	            alert("Ajax couldn't send data");
	        }
	    });
	}
	

	$('#questionTab').tabCollapse();
	

	function formCheck()
	{
		var correctAnswersOk = false;
		var answerTextOK = true;
		var answerCount = 0;
		
		$("#failMsg").html("");
		$("#correctAnswerHeading").css("color","black");
		
		for(var i = 0; i < 5; i++)
		{
			if($("#correctAnswer_" + i + ":checked").length > 0)
			{
				answerCount++;
			}

			$("[name=answerText_" + i + "]").css("background-color","white");
			
			if($("#correctAnswer_" + i + ":checked").length > 0 && $("[name=answerText_" + i + "]").val() == "")
			{
				answerTextOK = false;
				$("[name=answerText_" + i + "]").css("background-color","#ffdbdb");
			}
		}
		
		if($("#questionTypeSingleChoice").is(":checked"))
		{
			if(answerCount == 1)
			{
				correctAnswersOk = true;
			} else
			{
				$("#failMsg").append("<?php echo $lang["singechoiceAnswerError"]?>");
			}
		}
		
		if($("#questionTypeMultipleChoice").is(":checked"))
		{
			if(answerCount >= 1)
			{
				correctAnswersOk = true;
			} else
			{
				$("#failMsg").append("<?php echo $lang["multiplechoiceAnswerError"]?>");
			}
		}
		
		if(!correctAnswersOk)
		{
			$("#correctAnswerHeading").css("color","#ff0000");
		}

		if(!answerTextOK)
		{
			$("#failMsg").append("<br /><?php echo $lang["noAnswerTextError"]?>");
		}

		return correctAnswersOk && answerTextOK;
	}


	function showNewLanguageInput()
	{
		if($("#language").val() == "newLanguage")
		{
			$( "#newLanguage" ).show("slow", false);
		}else {
			$( "#newLanguage" ).hide("slow", false);
		}
	}

	function showNewTopicInput()
	{
		if($("#topic").val() == "newTopic")
		{
			$( "#newTopic" ).show("slow", false);
		} else {
			$( "#newTopic" ).hide("slow", false);
		}
	}

	
	$("#btnBack").on("click", function() {

		if(formCheck())
		{
			var fromsite = $("[name=fromsite]").val();
			var quizId = $("[name=fromQuizId]").val();

			if(fromsite == "")
			{
				window.location = "?p=questions";
			} else 
			{
				window.location = "?p=" + fromsite + "&mode=edit&id=" + quizId;
			}
		}
	});

	$("#btnSaveAndNext").on("click", function() {
		
		if(formCheck())
		{
			var nextSite = "createEditQuestion";
			var mode = "create";
			var fromsite = $("[name=fromsite]").val();
			var quizId = $("[name=fromQuizId]").val();

			if(fromsite == "")
			{
				window.location = "?p=createEditQuestion";
			} else 
			{
				window.location = "?p=" + nextSite + "&mode=" + mode + "&fromsite=" + fromsite + "&quizId=" + quizId;
			}
		}
	});
	
</script>
