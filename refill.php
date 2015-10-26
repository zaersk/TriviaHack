<?php
	// ini_set('display_errors',1);
	// error_reporting(-1);
	// read json input
	$data_back = json_decode(file_get_contents('php://input'));

	// set header as json
	header("Content-type: application/json");
	
	function GUID()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
	
	// Login
	$data = array("password" => "PASSWORD_HERE", "email" => "EMAIL_HERE", "user_device" => array("device" => "iphone", "notification_id" => "", "account_type" => "default", "installation_id" => GUID()));                                                                    
	$result = login($data);
	// echo $result;
	
	// decode json
	// get status code
	$read = json_decode($result);
	$status = $read->{'code'};
	if (strcmp($status, '604') === 0) {
		// invalid password
		// send a message to user stating that a reconfiguration of preferences is imperative
	} else if (strcmp($status, '301') === 0) {
		// user not found
	} else if (strcmp($status, '202') === 0) {
		// invalid mail characters
	} else {
		$session = $read->{'session'};
		$session_id = $session->{'session'};
		$id = $read->{'id'};
		// print_r($session);
		// print($id);
		// Obtain user dashboard
		$resp = getDashboard($session_id,$id);
		
		// decode json response
		$resp_decoded = json_decode($resp);
		$lives_object = $resp_decoded->{'lives'};
		$lives_quantity = intval($lives_object->{'quantity'});
		$unlimited = $resp_decoded->{'unlimited'};
		if ((strcmp($unlimited, 'true') === 0) || $lives_quantity > 0) {
			echo('enough lives!');
			// create room
			$data = array("language" => "EN", "type" => "DUEL_GAME");
			$room_result = createRoom($data, $session_id, $id);
			// decode json response
			$room_result_decoded = json_decode($room_result);
			$room_id = $room_result_decoded->{'id'};
			// delay (addition of one second is frivolous, just testing)
			// $seconds_delay = intval($room_result_decoded->{'countdown'}) + 1;
			sleep(19);
			// get room data
			// accept the request first
			$accept_response = acceptRoomRequest($session_id, $id, $room_id);
			echo($accept_response);
			$resp2 = getDashboard($session_id,$id);
			echo($resp2);
			/*
			$accept_response_decoded = json_decode($accept_response);
			$countdown = $accept_response_decoded->{'countdown'};
			echo($countdown);
			*/
			sleep(10);
			$room_resp = getRoom($session_id, $id, $room_id);
			$room_resp_decoded = json_decode($room_resp);
			$game = $room_resp_decoded->{'game'};
			$questions = $game->{'questions'};
			// print_r($questions);
			// iterator
			$answers_arr = array();
			foreach ($questions as $key => $value) {
				$question_id = $value->{'id'};
				$category = $value->{'category'};
				$correct_answer = $value->{'correct_answer'};
				array_push($answers_arr,array('id' => $question_id,'answer' => $correct_answer,'category' => $category));
			}
			
			print_r($answers_arr);
			// post answers
			postAnswers_room($session_id, $id, $room_id, $answers_arr);
			//$read_postAnswers_room_response = json_decode($postAnswers_room_response);
			//$game_status = $read_postAnswers_room_response->{'game_status'};
			//print_r($postAnswers_room_response);
			
		} else {
			echo('not enough lives!');
		}
	}
	
	function login(&$data) {
		$data_string = json_encode($data);
		
		$ch = curl_init('https://api.preguntados.com/api/login');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data_string))                                                                       
		);                                                                                                                   
		 
		// print response
		$result = curl_exec($ch);
		// Close request to clear up some resources
		curl_close($ch);
		// return result
		return $result;
	}
	
	function getDashboard(&$session_id,&$id) {
		// Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		$getURL = 'http://api.preguntados.com/api/users/' . $id . '/dashboard?app_config_version=1420491447';
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $getURL,
			CURLOPT_HTTPHEADER => array(                                                                          
				'Eter-Agent: 1|iOS-AppStr|iPhone 4S|0|iOS 8.1.1|0|1.9.2|en|en|US|1',                                                                                
				'Cookie: ap_session=' . $session_id,
				'User-Agent: Preguntados/1.9.2 (iPhone; iOS 8.1.1; Scale/2.00)'                                                                 
			)
		));
		// Send the request & save response to $resp
		$resp = curl_exec($curl);
		// Close request to clear up some resources
		curl_close($curl);
		// return response
		return $resp;
	}
	
	function createRoom(&$data,&$session_id,&$id) {
		$data_string = json_encode($data);
		
		$ch = curl_init('http://api.preguntados.com/api/users/' . $id . '/rooms');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data_string),
			'Eter-Agent: 1|iOS-AppStr|iPhone 4S|0|iOS 8.1.1|0|1.9.2|en|en|US|1',                                                                                
			'Cookie: ap_session=' . $session_id,
			'User-Agent: Preguntados/1.9.2 (iPhone; iOS 8.1.1; Scale/2.00)')                                                                       
		);                                                                                                                   
		 
		// get response
		$result = curl_exec($ch);
		// Close request to clear up some resources
		curl_close($ch);
		// return response
		return $result;
	}
	
	function acceptRoomRequest(&$session_id,&$id,&$room_id) {
		/*
		$data = array('action' => 'ACCEPT');
		
		$data_string = json_encode($data);
		
		$ch = curl_init('http://api.preguntados.com/api/users/' . $id . '/games/' . $room_id . '/actions');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data_string),
			'Eter-Agent: 1|iOS-AppStr|iPhone 4S|0|iOS 8.1.1|0|1.9.2|en|en|US|1',                                                                                
			'Cookie: ap_session=' . $session_id,
			'User-Agent: Preguntados/1.9.2 (iPhone; iOS 8.1.1; Scale/2.00)')                                                                       
		);                                                                                                                   
		 
		// get response
		$result = curl_exec($ch);
		// Close request to clear up some resources
		curl_close($ch);
		// return response (not needed)
		//return $result;
		*/
		// Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		$getURL = 'http://api.preguntados.com/api/users/' . $id . '/rooms/' . $room_id;
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $getURL,
			CURLOPT_HTTPHEADER => array(                                                                          
				'Eter-Agent: 1|iOS-AppStr|iPhone 4S|0|iOS 8.1.1|0|1.9.2|en|en|US|1',                                                                                
				'Cookie: ap_session=' . $session_id,
				'User-Agent: Preguntados/1.9.2 (iPhone; iOS 8.1.1; Scale/2.00)'                                                                 
			)
		));
		// Send the request & save response to $resp
		$resp = curl_exec($curl);
		// Close request to clear up some resources
		curl_close($curl);
		// return response
		return $resp;
	}
	
	function getRoom(&$session_id,&$id,&$room_id) {
		
		// Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		$getURL = 'http://api.preguntados.com/api/users/' . $id . '/rooms/' . $room_id;
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $getURL,
			CURLOPT_HTTPHEADER => array(                                                                          
				'Eter-Agent: 1|iOS-AppStr|iPhone 4S|0|iOS 8.1.1|0|1.9.2|en|en|US|1',                                                                                
				'Cookie: ap_session=' . $session_id,
				'User-Agent: Preguntados/1.9.2 (iPhone; iOS 8.1.1; Scale/2.00)'                                                                 
			)
		));
		// Send the request & save response to $resp
		$resp = curl_exec($curl);
		// Close request to clear up some resources
		curl_close($curl);
		// return response
		return $resp;
	}
	
	function postAnswers_room(&$session_id,&$id,&$room_id,&$answers_arr) {
		$data = array('finish_time' => 00001, 'answers' => $answers_arr);
		$data_string = json_encode($data);
		echo($data_string);
		
		$ch = curl_init('http://api.preguntados.com/api/users/' . $id . '/games/' . $room_id . '/answers');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data_string),
			'Eter-Agent: 1|iOS-AppStr|iPhone 4S|0|iOS 8.1.1|0|1.9.2|en|en|US|1',                                                                                
			'Cookie: ap_session=' . $session_id,
			'User-Agent: Preguntados/1.9.2 (iPhone; iOS 8.1.1; Scale/2.00)')                                                                       
		);                                                                                                                   
		 
		// get response
		$result = curl_exec($ch);
		// Close request to clear up some resources
		curl_close($ch);
		// return response
		//return $result;
		echo($result);
	}
	
?>