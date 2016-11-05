<?php

function changeActiveStateUser()
{
	global $dbh;
	
	if($_SESSION["role"]["admin"] && isset($_GET["userId"]))
	{
		$stmt = $dbh->prepare("select isActivated from user where id = :uId");
		$stmt->bindParam(":uId", $_GET["userId"]);
		$stmt->execute();
		$userFetch = $stmt->fetch(PDO::FETCH_ASSOC);
			
		$active = false;
		if(!$userFetch["isActivated"])
			$active = true;
	
			$stmt = $dbh->prepare("update user set isActivated = :iA where id = :uId");
			$stmt->bindParam(":uId", $_GET["userId"]);
			$stmt->bindParam(":iA", $active);
			if($stmt->execute())
			{
				{echo json_encode(["ok", $active]);}
			} else {echo json_encode(["failed"]);}
				
	} else {echo json_encode(["failed"]);}
}

?>