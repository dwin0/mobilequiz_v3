<?php
	include_once 'errorCodeHandler.php';
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleRegisterError($_GET["code"]);
	}
	
?>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["registerHeadline"]?></h1>
	</div>
	<p style="color:<?php echo $errorCode->getColor();?>;"><?php echo $errorCode->getText();?></p>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["pwRecoverySectionHeadline"]?></h3>
		</div>
		<div class="panel-body">
			<form id="formRegister" class="form-horizontal" action="?p=auth&action=register" method="post">
			
				<p style="margin-top: 1em;"><?php echo $lang["reistrationText"]?>:</p>
				
				<div class="control-group" style="margin-bottom: 1em;">
			        <label class="control-label" for="topic">
			            <?php echo $lang["fieldOfInterest"]?> *
			        </label>
			        <div class="controls">
		                <select id="topic" multiple="multiple" class="form-control" name="topic[]">
		                    <?php 
		                    $stmt = $dbh->prepare("select subject_id from question group by subject_id");
		                    $stmt->execute();
		                    $result = $stmt->fetchAll();
		                    
		                    for($i = 0; $i < count($result); $i++){
								if($result[$i]["subject_id"] == null)
								{
									continue;
								}
								
								$stmt = $dbh->prepare("select name from subjects where id = " . $result[$i]["subject_id"]);
								$stmt->execute();
								$resultSubjectName = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								echo "<option value=\"" . $result[$i]["subject_id"] . "\">" . $resultSubjectName[0]["name"] . "</option>";
		                    } ?>
		                </select>
					</div>
			    </div>
			
			    <div class="control-group">
			        <label class="control-label" for="email">
			            <?php echo $lang["email"]?> *
			        </label>
			        <div class="controls">
			        	<input type="email" class="form-control validate[required,custom[email]] text-input" name="email" placeholder="<?php echo $lang["your"] . " " .$lang["email"]?>" required maxlength="100" />
			        </div>
			    </div>
		        <div class="control-group">
		            <label class="control-label" for="pass">
		                <?php echo $lang["password"]?> *
		            </label>
		            <div class="controls">
		                <input id="pw" title="sdsdsd" type="password" class="form-control validate[required] text-input" id="cPass" name="pass" placeholder="<?php echo $lang["your"] . " " .$lang["password"]?>" required maxlength="100" />
		            </div>
		        </div>
			
		        <div class="control-group">
		            <label class="control-label" for="verifypass">
		                <?php echo $lang["confirmPassword"]?> *
		            </label>
		            <div class="controls">
		                <input id="confirmPw" type="password" class="form-control validate[required,equals[cPass]] text-input" id="cVerifypass" name="verifypass" placeholder="<?php echo $lang["your"] . " " .$lang["password"]?>" required maxlength="100" />
		            </div>
		        </div>
			    <div style="height: 20px;"></div>
			    <div class="control-group">
			        <label class="control-label" for="firstname">
			            <?php echo $lang["firstname"]?> *
			        </label>
			        <div class="controls">
			            <input type="text" class="form-control validate[required,minSize[2],maxSize[20],custom[nameRegex]] text-input" name="firstname" placeholder="<?php echo $lang["your"] . " " .$lang["firstname"]?>" maxlength="40" value=""/>
			        </div>
			    </div>
			    <div class="control-group">
			        <label class="control-label" for="lastname">
			            <?php echo $lang["lastname"]?> *
			        </label>
			        <div class="controls">
			            <input type="text" class="form-control validate[required,minSize[2],maxSize[20],custom[nameRegex]] text-input" name="lastname" placeholder="<?php echo $lang["your"] . " " .$lang["lastname"]?>" maxlength="40" value="" />
			        </div>
			    </div>
			    <div class="control-group">
			        <label class="control-label" for="nickname">
			            <?php echo $lang["nickname"]?>
			        </label>
			        <div class="controls">
			            <input type="text" class="form-control validate[required,minSize[2],maxSize[40],custom[nameRegex]] text-input" name="nickname" placeholder="<?php echo $lang["your"] . " " .$lang["nickname"]?>" maxlength="40" value="" />
			        </div>
			    </div>
			    <div style="height: 20px;"></div>
			    <div class="control-group">
			        <label class="control-label" for="street">
			            <?php echo $lang["street"]?>
			        </label>
			        <div class="controls">
			            <input type="text" class="form-control validate[minSize[2],maxSize[20],custom[streetRegex]] text-input" name="street" placeholder="<?php echo $lang["your"] . " " .$lang["street"]?>" maxlength="255" value="" />
			        </div>
			    </div>
			    <div class="control-group">
			        <label class="control-label" for="plz">
			            <?php echo $lang["zipcode"]?>
			        </label>
			        <div class="controls">
			            <input type="number" class="form-control validate[minSize[2],maxSize[20],custom[onlyLetterNumber]] text-input" name="plz" placeholder="<?php echo $lang["your"] . " " .$lang["zipcode"]?>" maxlength="25" value="" />
			        </div>
			    </div>
			    <div class="control-group">
			        <label class="control-label" for="city">
			            <?php echo $lang["country"]?>
			        </label>
			        <div class="controls">
			            <input type="text" class="form-control validate[minSize[2],maxSize[20],custom[nameRegex]] text-input" name="city" placeholder="<?php echo $lang["your"] . " " .$lang["country"]?>" maxlength="25" value="" />
			        </div>
			    </div>
			    <div class="control-group">
			        <label class="control-label" for="telephone">
			            <?php echo $lang["telnumber"]?>
			        </label>
			        <div class="controls">
			            <input type="tel" class="form-control validate[minSize[2],maxSize[20],custom[phone]] text-input" name="telephone" placeholder="<?php echo $lang["your"] . " " .$lang["telnumber"]?>" maxlength="25" value="" />
			        </div>
			    </div>
			    <div style="height: 20px;"></div>
			    <p><?php echo $lang["requiredFields"]?></p>
			
				<p><?php echo $lang["registrationDescription"]?></p>
			    <div style="height: 20px;"></div>
			    <div style="text-align: left; float: left">
			        <input type="button" class="btn" name="cancel" id="cancel" value="<?php echo $lang["buttonCancel"]?>" onclick="window.location='?p=auth&action=cancel&fromsite=register';"/>
			    </div>
			
			    <div style="text-align: right">
			        <input type="submit" class="btn" name="submit" id="submit" form="formRegister" value="<?php echo $lang["buttonRegister"]?>" />
			    </div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
$('#confirmPw').data('powertip', '<span style="color: red;">Passw&ouml;rter stimmen nicht &uuml;berein.</span>');
$('#confirmPw').powerTip({
	placement: 'sw-alt', // north-east tooltip position
	manual: true
});
$(document).ready(function () {
	checkForm();
	$("#confirmPw").keyup(checkForm);
	$("#pw").keyup(checkForm);
});

$(document).ready(function() {
    $('#topic').multiselect({

    	buttonText: function(options, select) {
            if (options.length === 0) {
                return '<?php echo $lang["pleaseChoose"]?>';
            }
             else {
                 var labels = [];
                 options.each(function() {
                     if ($(this).attr('label') !== undefined) {
                         labels.push($(this).attr('label'));
                     }
                     else {
                         labels.push($(this).html());
                     }
                 });
                 return labels.join(', ') + '';
             }
        }
    });
});

function checkForm()
{
	var samePw = false;
	var pwLength = false;
	
	var password = $("#pw").val();
    var confirmPassword = $("#confirmPw").val();

    if (password != confirmPassword)
    {
    	$('#confirmPw').powerTip('show');
    	samePw = false;
    }
    else
    {
    	$('#confirmPw').powerTip('hide');
    	samePw = true;
    }

    if(password.length < 1)
    {
    	pwLength = false;
    }
    else
    {
    	pwLength = true;
    }

    if(samePw && pwLength)
		$('#submit').removeAttr("disabled");
    else
    	$('#submit').attr("disabled", "disabled");
}
</script>