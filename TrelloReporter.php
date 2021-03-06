<?php

	function get($type,$boardId,$key,$token)
  	{
  		$urls = [
  			'list' => "https://api.trello.com/1/boards/{$boardId}/lists?fields=name&cards=none&key={$key}&token={$token}",
			'cards' => "https://api.trello.com/1/boards/{$boardId}/cards/?&fields=id,name,labels,idList&customFieldItems=true&key={$key}&token={$token}"
	  	];

	  	$ch = curl_init($urls[$type]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$results = curl_exec($ch);
		curl_close($ch);
		return json_decode($results);
  	}

  	function sendEmail($subject,$body,$altBdy) {
		$to = 'kjackson@clacorp.com';
		$headers = 
			'From: kjackson@clacorp.com '. "\r\n" .
			'MIME-Version: 1.0 '. "\r\n" .
			'Content-type: text/plain';

		if(!mail($to,$subject,$body,$headers)) 
		{
		    echo "Mailer Error: ";
		}
  	}

	$options = getopt("b:t:l:",['overview::','detailed::','csv::']);

	$key = getenv('TRELLO_API_KEY',true);
  	$token = getenv('TRELLO_API_TOKEN',true);

  	if( !isset($options['b']) ) {
  		print 'Invalid Usage: Please make sure board id and tag are set!'.PHP_EOL;
  		exit(0);
  	}

  	if( !$key || !$token ) {
  		print 'Invalid Usage: API key and token required!'.PHP_EOL;
  		exit(0);
  	}

  	$board = $options['b'];
	$listArray = [];
	$cardsIn = [];
	$listCounts = [];
	$customFieldsArray = [];

	$lists = get('list',$board,$key,$token);
	foreach($lists as $l) {
		$listArray[$l->id] = strtolower($l->name);
	}

	$cards = get('cards',$board,$key,$token);
	
	if (!isset($options['csv'])) {
		print "There are currently " . count($cards) . " cards for this board.".PHP_EOL;
	}

	if(isset($options['l'])) {
		$lane = $options['l'];

		if (!in_array($lane,array_keys(array_flip($listArray)))) {
			print 'Invalid Usage: Provided lane is not found!'.PHP_EOL;
  			exit(0);
		}

		$laneId = array_flip($listArray)[$lane];

		foreach($cards as $c) {
			if ($c->idList === $laneId) {
				$cardsIn[] = $c;
			}	
		}

		print "Lane [".ucwords($lane)."] currently has " . count($cardsIn). " cards in it.".PHP_EOL;
	}

    if(isset($options['t']) && $options['t']) {
		$newCardsIn = [];
		$tag = $options['t'];
	  	$cardsIn = (empty($cardsIn)) ? $cards : $cardsIn;

	  	foreach($cardsIn as $c) {
			foreach($c->labels as $l){
				if (strpos($l->name,$tag) !== false) {
					$newCardsIn[] = $c;
				}	
			}
		}

		$cardsIn = $newCardsIn;

		print "To date [".date('m/d/Y')."] tag [{$tag}] currently has " . count($cardsIn) . " cards.".PHP_EOL.PHP_EOL;
	}

	if(isset($options['overview'])) {
		$cardsIn = (empty($cardsIn)) ? $cards : $cardsIn;

		foreach($cardsIn as $c) {
			if (!isset($listCounts[$c->idList])) {
				$listCounts[$c->idList] = 1;
			} else {
				$listCounts[$c->idList]++;
			}
		}

		foreach($listCounts as $id => $total) {
			print "{$total} item(s) in '{$listArray[$id]}'".PHP_EOL;
		}

		exit(0);
	} 

	if (isset($options['detailed'])) {
		$cardsIn = (empty($cardsIn)) ? $cards : $cardsIn;
		$list = [];
		$body = '';

		foreach($cardsIn as $c){

		 /*foreach ($c->customFieldItems as $cf){
		 	if(isset($cf->value)) {
print json_encode($c).PHP_EOL;
print json_encode($cf).PHP_EOL;

				$ch = curl_init("https://api.trello.com/1/boards/{$board}/customFields?key={$key}&token={$token}");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$results = curl_exec($ch);
				curl_close($ch);
print $results[2].PHP_EOL;exit;
				return json_decode($results);
			}
			}*/

			if (!isset($list[$c->idList])) {
				$list[$c->idList] = [];
			}

			array_push($list[$c->idList],$c->name);
		}

		foreach ($list as $key => $array) {
			$txt = count($array) . " items in " . ucwords($listArray[$key]) . PHP_EOL;
			$body .= $txt;
			print $txt;

			foreach ($array as $card) {
				$txt = " - {$card}" . PHP_EOL;
				$body .= $txt;
				print $txt;
			}
			$body .= PHP_EOL;
			print PHP_EOL;
		}

		//sendEmail("State of {$tag} Release - [".date('d/m/yy')."]",$body,$body);
		exit(0);
	}

	if (isset($options['csv'])) {
		$cardsIn = (empty($cardsIn)) ? $cards : $cardsIn;

		// col headers
		print "LANE,LABELS,TITLE".PHP_EOL;

		foreach($cardsIn as $c){
			$labels = '';

			foreach($c->labels as $l){
				//if (strpos($l->name,'NWE') !== false) {
					$labels .= "[{$l->name}] ";
				//}	
			}

			print ucwords($listArray[$c->idList]) .",{$labels},{$c->name}".PHP_EOL;
		}

		exit(0);
	}

	$cardsIn = (empty($cardsIn)) ? $cards : $cardsIn;
	
	foreach($cardsIn as $c) {
		print "{$c->name}".PHP_EOL;
	}

	exit(0);