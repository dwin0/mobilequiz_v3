<?php

function updateLanguage($language, $type, $id, $dbh)
{
	$response_array["status"] = "OK";

	if($language == "newLanguage") //Option to show the new-language-field
	{
		return $response_array;
	}

	if($language == "")
	{
		return $response_array;
	}
	
	if($type != "question" && $type != "questionnaire")
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Unknown type";
		return $response_array;
	}

	$stmt = $dbh->prepare("select language from $type group by language");	
	$stmt->execute();
	$allLanguages = $stmt->fetchAll();

	for($i = 0; $i < count($allLanguages); $i++){
		if($allLanguages[$i]["language"] == $language)
		{
			$existingLanguage = true;
				
			//update question/questionnaire with existing language
			$stmt = $dbh->prepare("update $type set language = :language where id = :id");
			$stmt->bindParam(":language", $language);
			$stmt->bindParam(":id", $id);

			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't update database";
				return $response_array;
			}
		}
	}

	if(!$existingLanguage) //requested language doesn't exist
	{
		if($type == "question")
		{
			$type_id = "question_id";
		} else 
		{
			$type_id = "questionnaire_id";
		}
		
		$stmt = $dbh->prepare("select id from language_request where $type_id = :id");
		$stmt->bindParam(":id", $id);
		$stmt->execute();
		$fetchRequestId = $stmt->fetch(PDO::FETCH_ASSOC);

		if(isset($fetchRequestId["id"])) //question/questionnaire has already requested a new language -> update request
		{
			$stmt = $dbh->prepare("update language_request set language = :language, timestamp = " . time() . " where id = :reqId");
			$stmt->bindParam(":language", $language);
			$stmt->bindParam(":reqId", $fetchRequestId["id"]);
				
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't update database";
			}

		} else //create new language-request
		{
			$stmt = $dbh->prepare("insert into language_request (user_id, language, timestamp, $type_id) values (:user_id, :language, " . time() . ", :id)");
			$stmt->bindParam(":user_id", $_SESSION["id"]);
			$stmt->bindParam(":language", $language);
			$stmt->bindParam(":id", $id);
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't update database";
			}
		}
	}

	return $response_array;
}


?>