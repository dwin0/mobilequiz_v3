<?php 

	global $dbh;

	if($_SESSION["role"]["user"] == 1)
	{
		if($_SESSION["role"]["creator"] != 1)
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
	
	$code = 0;
	$codeText = "";
	$mode = "create";
	if(isset($_GET["mode"]))
	{
		$mode = $_GET["mode"];
	}
	
	$maxCharactersQuestion = 400;
	$maxCharactersTopic = 30;
	$maxCharactersAnswer = 250;
	
	if($mode == "edit")
	{
		if(isset($_GET["id"]))
		{
			$stmt = $dbh->prepare("select question.*, user_data.firstname, user_data.lastname from question inner join user on user.id = question.owner_id inner join user_data on user_data.user_id = user.id where question.id = :id");
			$stmt->bindParam(":id", $_GET["id"]);
			$stmt->execute();
			if($stmt->rowCount() > 0)
				$questionFetch = $stmt->fetch(PDO::FETCH_ASSOC);
			else 
			{
				$mode = "create";
				$code = -2;
				$codeText = "ID nicht gefunden.";
			}
		} else {
			$mode = "create";
			$code = -1;
			$codeText = "ID nicht gesetzt.";
		}
	} else if($mode == "create")
	{
		if($_SESSION["language"] == "ger")
		{
			$language = "Deutsch";
		} else {
			$language = "English";
		}
		
		$stmt = $dbh->prepare("insert into question	(text, owner_id, type_id, subject_id, language, creation_date, public, last_modified, picture_link)
							values ('', " . $_SESSION["id"] . ", 1, NULL, '" . $language . "', " . time() . ", 1, " . time() . ", NULL);");		
		$stmt->execute();
		
		$newQuestionId = $dbh->lastInsertId();
	}
?>

<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $mode == "create" ? $lang["createQuestion"] : $lang["editQuestion"];?></h1>
	</div>
	<?php if($code != 0) {?>
	<p style="color:red"><?php echo $codeText;?></p>
	<?php }?>
	
	<ul id="questionTab" class="nav nav-tabs">
		<li class="active"><a href="#questionText" data-toggle="tab"><?php echo $lang["question"]?></a></li>
		<li><a href="#answers" data-toggle="tab"><?php echo $lang["answers"]?></a></li>
	</ul>
	
	<div id="questionTabContent" class="tab-content">
		<div class="tab-pane fade in active form-horizontal" id="questionText">
			
			<!-- Question-Text -->
			<div class="form-group">
				<label for="questionText" class="col-md-2 col-sm-3 control-label">
					<?php echo $lang["questionQuestionTextOnly"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<textarea name="questionText" wrap="soft" id="questionText"
						class="form-control text-input" required="required"
						placeholder="<?php echo $lang["questionQuestionTextOnly"] . " (" . $lang["maximum"] . " " . $maxCharactersQuestion . " " . $lang["characters"] . ")";?>" maxlength="<?php echo $maxCharactersQuestion;?>"><?php 
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
					<select id="language" class="form-control" name="language">
	                	<?php
	                    $stmt = $dbh->prepare("select language from question group by language");
	                    $stmt->execute();
	                    $result = $stmt->fetchAll();
	                    
	                    for($i = 0; $i < count($result); $i++){
							$stmt = $dbh->prepare("select id from question where language = '" . $result[$i]["language"] . "'");
							$stmt->execute();
							$selected = "";
							if($mode == "edit")
								$selected = ($questionFetch["language"] == $result[$i]["language"]) ? 'selected="selected"' : '';
							echo "<option value=\"" . $result[$i]["language"] . "\"" . $selected . ">" . $result[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")</option>";
	                    } ?>
	                </select>
	                <input type="text" id="newLanguage"
						class="form-control" name="newLanguage"
						placeholder="<?php echo $lang["newLanguagePlaceholder"];?>" maxlength="30"/>
				</div>
			</div>
			
			
			<!-- Topic -->
			<div class="form-group">
				<label for="applicationArea" class="col-md-2 col-sm-3 control-label"> 
					<?php echo $lang["quizTableTopic"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<select id="topic" class="form-control" name="topic">
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
		                </select>
					<input type="text" id="newTopic"
						class="form-control" name="newTopic"
						placeholder="<?php echo $lang["newTopicPlaceholder"] . " (" . $lang["maximum"] . " " . $maxCharactersTopic . " " . $lang["characters"] . ")";?>" maxlength="<?php echo $maxCharactersTopic?>"/>
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
							echo "<br /><img style=\"float:left; max-width:300px; max-height:300px; width:50%\" src=\"" . $questionFetch["picture_link"] . "\" id=\"questionImage\"></img>";
							?>
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
							<img style="margin-left:10px; height:18px; width:18px" src="assets/icon_delete.png" id="deleteQuestionLogo">
							
							<?php 
						}?>	
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
		
				
		<div class="tab-pane fade form-horizontal" id="answers">
		
			<div class="form-group">
				<div class="col-md-2 col-sm-3 control-label">
					<label> 
		            	<?php echo $lang["showAs"];?> *
					</label>
				</div>
				<div class="col-md-10 col-sm-9 radio-inline" id="questionType">
					<label class="radio-inline"> <input type="radio"
						name="questionType" id="questionTypeSingleChoice"
						value="singelchoise" required
						<?php if($mode == "create") { echo "checked"; }
						else if($mode == "edit") {
							if($questionFetch["type_id"] == 1)
								echo " checked";
						}
						?> /> <?php echo $lang["singleChoise"];?>
					</label> 
					<label class="radio-inline"> <input type="radio"
						name="questionType" id="questionTypeMultipleChoice"
						value="multiplechoise" <?php if($mode == "edit") {
							if($questionFetch["type_id"] == 2)
								echo " checked";
						}?>/>
						<?php echo $lang["multipleChoise"];?>
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
							<label id="<?php echo "answerText_" . $i;?>"> 
				            	<?php echo $lang["answertext"] . " " . ($i+1);
				            	if($i < 2)
				            		echo " *";
				            	?>
							</label>
						</div>
						<div class="col-md-9 col-sm-7 col-xs-10">
							<textarea name="<?php echo "answerText_" . $i;?>" wrap="soft" style="margin-bottom: 15px"
								class="form-control" <?php echo $i < 2 ? " required='required' " : ''?>
								placeholder="<?php echo $lang["answertext"] . " " . ($i+1) . " (" . $lang["maximum"] . " " . $maxCharactersAnswer . " " . $lang["characters"] . ")";?>" maxlength="<?php echo $maxCharactersAnswer;?>"><?php 
								if($mode == "edit")
								{
									if($i < $stmt->rowCount())
										echo $answerFetch[$i]["text"];
								}
								?></textarea>
						</div>
						<div class="col-md-1 col-sm-2 col-xs-1">
							<input id="<?php echo "correctAnswer_" . $i;?>" type="checkbox" name="<?php echo "correctAnswer_" . $i;?>"
								class="checkbox" value="1" 
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
			</div>
			
		</div>
	</div>
	
	<div style="text-align: left; margin-top: 10px; float: left">
		<input type="submit" class="btn" name="btnSave" value="<?php echo $lang["buttonBackToOverview"]; ?>">
	</div>
	
	<div style="text-align: right; margin-top: 10px">
		
		<input type="submit" class="btn" name="btnSaveAndNext" value="<?php echo $lang["saveQuestion"] . " " . $lang["createNext"];?>" />
		<input type="submit" class="btn" name="btnSave" value="<?php echo $lang["saveQuestion"]; ?>">
	</div>
	
	
	
	
	
	
	
	
	
	
	
	<form id="createQuestion" action="<?php echo "?p=actionHandler&action=insertQuestion&mode=" . $mode;?>" class="form-horizontal" method="POST" enctype="multipart/form-data" onsubmit="return formCheck()">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo $lang["generalInformations"]?></h3>
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="questionText" class="col-md-2 col-sm-3 control-label">
						<?php echo $lang["questionQuestionTextOnly"];?> *
					</label>
					<div class="col-md-10 col-sm-9">
						<textarea name="questionText" wrap="soft" id="questionText"
							class="form-control text-input" required="required"
							placeholder="<?php echo $lang["questionQuestionTextOnly"] . " (" . $lang["maximum"] . " " . $maxCharactersQuestion . " " . $lang["characters"] . ")";?>" maxlength="<?php echo $maxCharactersQuestion;?>"><?php 
							if($mode == "edit")
							{
								echo $questionFetch["text"];
							}
							?></textarea>
							<?php
							//TODO: Link to Help-Site
							?>
							<p id="questionTextHelp" title="Wie m&uuml;ssen Texte eingegeben werden?" style="cursor: pointer; text-decoration: underline;"><?php echo $lang["help"];?></p>
					</div>
				</div>
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
				<div class="form-group">
					<label for="questionLogo" class="col-md-2 col-sm-3 control-label">
						<?php echo $lang["picture"];?>
					</label>
					<div class="col-md-10 col-sm-9" id="questionLogoWrapper">
						<input type="file" id="questionLogo" name="questionLogo"
							class="btn" accept=".jpeg,.jpg,.bmp,.png,.gif"/>
						<div id="picturePreview">
						<?php if($mode == "edit" && $questionFetch["picture_link"] != "")
						{
							echo "<br /><img style=\"float:left; max-width:300px; max-height:300px\" src=\"" . $questionFetch["picture_link"] . "\" width=\"50%\" ></img>";
							?>
							<img style="margin-left: 10px;" src="assets/icon_delete.png" alt="" title="" height="18px" width="18px" onclick="delPicture(<?php echo $questionFetch["id"];?>)">
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
						<select id="language" class="form-control" name="language">
		                	<?php 
		                	$stmt = $dbh->prepare("select id from question");
		                	$stmt->execute();
		                	$allQuestionsCount = $stmt->rowCount();
		                	
		                    $stmt = $dbh->prepare("select language from question group by language");
		                    $stmt->execute();
		                    $result = $stmt->fetchAll();
		                    
		                    for($i = 0; $i < count($result); $i++){
								$stmt = $dbh->prepare("select id from question where language = '" . $result[$i]["language"] . "'");
								$stmt->execute();
								$selected = "";
								if($mode == "edit")
									$selected = ($questionFetch["language"] == $result[$i]["language"]) ? 'selected="selected"' : '';
								echo "<option value=\"" . $result[$i]["language"] . "\"" . $selected . ">" . $result[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")</option>";
		                    } ?>
		                </select>
		                <input type="text" id="newLanguage"
							class="form-control" name="newLanguage"
							placeholder="<?php echo $lang["newLanguagePlaceholder"];?>" maxlength="30"/>
					</div>
				</div>
				<div class="form-group">
					<label for="applicationArea" class="col-md-2 col-sm-3 control-label"> 
						<?php echo $lang["quizTableTopic"];?> *
					</label>
					<div class="col-md-10 col-sm-9">
						<select id="topic" class="form-control" name="topic">
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
			                </select>
						<input type="text" id="newTopic"
							class="form-control" name="newTopic"
							placeholder="<?php echo $lang["newTopicPlaceholder"] . " (" . $lang["maximum"] . " " . $maxCharactersTopic . " " . $lang["characters"] . ")";?>" maxlength="<?php echo $maxCharactersTopic?>"/>
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
		</div>
		<div class="panel panel-default">
			<div class="panel-body">
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
						<label id="<?php echo "answerText_" . $i;?>"> 
			            	<?php echo $lang["answertext"] . " " . ($i+1);
			            	if($i < 2)
			            		echo " *";
			            	?>
						</label>
					</div>
					<div class="col-md-9 col-sm-7 col-xs-7">
						<textarea name="<?php echo "answerText_" . $i;?>" wrap="soft"
							class="form-control" <?php echo $i < 2 ? " required='required' " : ''?>
							placeholder="<?php echo $lang["answertext"] . " " . ($i+1) . " (" . $lang["maximum"] . " " . $maxCharactersAnswer . " " . $lang["characters"] . ")";?>" maxlength="<?php echo $maxCharactersAnswer;?>"><?php 
							if($mode == "edit")
							{
								if($i < $stmt->rowCount())
									echo $answerFetch[$i]["text"];
							}
							?></textarea>
					</div>
					<div class="col-md-1 col-sm-2 col-xs-2">
						<input id="<?php echo "correctAnswer_" . $i;?>" type="checkbox" name="<?php echo "correctAnswer_" . $i;?>"
							class="checkbox" value="1" 
							<?php if($i < $stmt->rowCount()) 
							{
								if($answerFetch[$i]["is_correct"] == 1)
									echo 'checked="checked"';
							}
							?> 
						/>
					</div>
				</div>
				<?php }?>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2 col-sm-3 control-label">
						<label> 
			            	<?php echo $lang["showAs"];?> *
						</label>
					</div>
					<div class="col-md-10 col-sm-9 radio-inline">
						<label class="radio-inline"> <input type="radio"
							name="questionType" id="questionTypeSingleChoice"
							value="singelchoise" required
							<?php if($mode == "create") { echo "checked"; }
							else if($mode == "edit") {
								if($questionFetch["type_id"] == 1)
									echo " checked";
							}
							?> /> <?php echo $lang["singleChoise"];?>
						</label> 
						<label class="radio-inline"> <input type="radio"
							name="questionType" id="questionTypeMultipleChoice"
							value="multiplechoise" <?php if($mode == "edit") {
								if($questionFetch["type_id"] == 2)
									echo " checked";
							}?>/>
							<?php echo $lang["multipleChoise"];?>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-3 control-label">
						<label> 
							<?php echo $lang["publication"];?> *
						</label>
					</div>
					<div class="col-md-10 col-sm-9 radio-inline">
						<label class="radio-inline"> <input type="radio" name="isPrivate"
							value="0" required 
							<?php if($mode == "create") { echo "checked"; }
							else if($mode == "edit") {
								if($questionFetch["public"] == 0)
									echo " checked";
							}?>/> <?php echo $lang["public"];?>
						</label> 
						<label class="radio-inline"> <input type="radio"
							name="isPrivate" value="1"
							<?php if($mode == "edit") {
								if($questionFetch["public"] == 1)
									echo " checked";
							}?>/> <?php echo $lang["privateMoreInfo"];?>
						</label>
					</div>
				</div>
			</div>
		</div>
		<p>
			<?php echo $lang["requiredFields"];?>
		</p>
		<p id="failMsg" style="color:red;"></p>
		<div>
			<div style="text-align: left; float: left;">
				<input type="button" class="btn" id="btnCancel"
					value="<?php echo $lang["buttonCancel"];?>" onclick="window.location='?p=questions';"/>
			</div>

			<div style="text-align: right">
				<input type="hidden" name="mode" value="<?php echo $mode;?>">
				<input type="hidden" name="question_id" value="<?php echo ($mode == "edit") ? $questionFetch["id"] : $newQuestionId;?>">
				<input type="hidden" name="fromsite" value="<?php echo isset($_GET["fromsite"]) ? $_GET["fromsite"] : '';?>">
				<input type="hidden" name="fromQuizId" value="<?php echo isset($_GET["quizId"]) ? $_GET["quizId"] : '';?>">
				<input type="submit" class="btn" name="btnSave" form="createQuestion"
					value="<?php echo $lang["saveQuestion"];?>" /> 
				<input type="submit" class="btn" name="btnSaveAndNext" form="createQuestion"
					value="<?php echo $lang["saveQuestion"] . " " . $lang["createNext"];?>" />
			</div>
		</div>
	</form>
</div>




<script type="text/javascript" src="js/bootstrap-tabcollapse.js"></script>
<script type="text/javascript" src="js/photoSwipeController.js"></script>
<script type="text/javascript">

	$(document).ready(function() {
		
		$(document).on("change", "#questionText, #keywords, #language, #newLanguage, #topic, #newTopic, #questionLogo, " +
				"[name='isPrivate'], [name='questionType'], [name^='answerText_']", updateQuestionData);

		$(document).on("click", "#deleteQuestionLogo", updateQuestionData);
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
				break;
			case "isPrivate":
				console.log("isPrivate");
				break;
			case "questionTypeSingleChoice":
				console.log("questionTypeSingleChoice");
				break;
			case "questionTypeMultipleChoice":
				console.log("questionTypeMultipleChoice");
				break;
		}

		if(event.target.name.startsWith("answerText_"))
		{
			console.log(event.target.name);
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
		            	var downloadingImage = $("<img>");
		            	var removeIcon = $("<img>");
		            	
		            	downloadingImage.load(function(){
		            		parent.append(this);	
		            	});

		            	removeIcon.load(function(){
		            		parent.append(this);	
		            	});

		            	downloadingImage.attr("style", "float:left; max-width: 300px; max-height: 300px; width: 50%");
		            	downloadingImage.attr("id", "questionImage");
		            	downloadingImage.attr("src", data.text);

		            	removeIcon.attr("style", "margin-left: 10px; height: 18px; width: 18px");
		            	removeIcon.attr("id", "deleteQuestionLogo");
		            	removeIcon.attr("src", "assets/icon_delete.png");

		            	$("#picturePreview > span").remove();
		            	$("#questionLogo").hide();
						
		            	break;
					case "DELETED":
						console.log("OK");
		            	$('#picturePreview').html("<span style=\"color:green;\">Bild erfolgreich entfernt.</span>");
		            	$("#questionLogo").show();
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
		var correctAnswersOk1 = false;
		var correctAnswersOk2 = false;
		var answerCount = 0;
		for(var i = 0; i < 6; i++)
		{
			if(document.getElementById("correctAnswer_" + i).checked)
				answerCount++;
		}

		if(document.getElementById("questionTypeSingleChoice").checked)
		{
			if(answerCount == 1)
				correctAnswersOk1 = true;
		}
		
		if(document.getElementById("questionTypeMultipleChoice").checked)
		{
			if(answerCount >= 1)
				correctAnswersOk2 = true;
		}
		
		if(!correctAnswersOk1)
		{
			$('#correctAnswerHeading').css('color','#ff0000');
		}

		if(((document.getElementById("questionTypeSingleChoice").checked && correctAnswersOk1) || 
				(document.getElementById("questionTypeMultipleChoice").checked && correctAnswersOk2)))
		{
			return true;
		} else {
			$('#failMsg').html("Mindestens ein ben&ouml;tigtes Feld ist nicht korrekt ausgef&uuml;llt.");

			if(document.getElementById("questionTypeSingleChoice").checked && !correctAnswersOk1)
				$('#failMsg').append('<br />Es darf nur eine Antwort bei Singlechoise ausgew&auml;hlt werden.');

			if(document.getElementById("questionTypeMultipleChoice").checked && !correctAnswersOk2)
				$('#failMsg').append('<br />Es m&uuml;ssen mindestens zwei Antworten bei Multiplechoise ausgew&auml;hlt werden.');
			
			return false;
		}
	}
	
</script>
