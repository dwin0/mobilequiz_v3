<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["pwRecoveryHeadline"]?></h1>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["pwRecoverySectionHeadline"]?></h3>
		</div>
		<div class="panel-body">
			<p><?php echo $lang["pwRecoverySetionText"]?></p>
			<form id="formForgotLogin" class="form-horizontal" action="?p=auth&action=recoverPassword" method="post">
				<div class="control-group">
					<label class="control-label" for="email">
						<?php echo $lang["email"]?> * 
					</label>
					<div class="controls">
						<input type="email" class="form-control validate[required,custom[email]] text-input"  name="emailforgot" value="" maxlength="100" placeholder="Ihre E-Mail Adresse" required="required"/>
					</div>
				</div>
				<div style="height: 20px;"></div>
				<p><?php echo $lang["requiredFields"]?></p>
				<div style="text-align: left; float: left">
					<input type="submit" class="btn" id="forgotPassCancel" name="cancel" value="<?php echo $lang["buttonCancel"]?>" onclick="window.location='?p=auth&action=cancel&fromsite=recoverPassword';" />
				</div>
				<div style="text-align: right">
					<input type="submit" class="btn" id="forgotPassRecovery" name="submit" value="<?php echo $lang["buttonRecoverPw"]?>" />
				</div>
			</form> 
		</div>
	</div>
</div>