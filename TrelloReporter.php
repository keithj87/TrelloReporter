<?php

	function get($type,$boardId,$key,$token) {
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
		$to = '';								// add to email
		$headers = 
			'From:  '. "\r\n" .					// add from email
			'MIME-Version: 1.0 '. "\r\n" .
			'Content-type: text/plain';

		if(!mail($to,$subject,$body,$headers)) 
		{
		    echo "Mailer Error: ";
		}
  	}

	function outputCards($cards) {
		foreach($cards as $c) {
			print "{$c->name}".PHP_EOL;
		}
	}

	//var_dump($argv);

	$options = getopt("b:e:t:l:p:",['overview::','detailed::','csv::']);
	$key = getenv('TRELLO_API_KEY',true);
  	$token = getenv('TRELLO_API_TOKEN',true);

	//var_dump($options);

  	if( !isset($options['b']) ) {
  		print 'Invalid Usage: Board id required!'.PHP_EOL;
  		exit(0);
  	}

  	if( !$key || !$token ) {
  		print 'Invalid Usage: API key and token required!'.PHP_EOL;
  		exit(0);
  	}

  	$board = $options['b'];
	$listArray = [];
	$cardsToOutput = [];
	$listCounts = [];
	$customFieldsArray = [];

	$lists = get('list',$board,$key,$token);

	foreach($lists as $l) {
		$listArray[$l->id] = strtolower($l->name);
	}

	$cards = get('cards',$board,$key,$token);
	
	if ( !isset($options['csv']) ) {
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
				$cardsToOutput[] = $c;
			}	
		}

		print "Lane [".ucwords($lane)."] currently has " . count($cardsToOutput). " cards in it.".PHP_EOL;
	}

	if( isset($options['e']) ) {
		$newCardsIn = [];	
	  	$cardsToOutput = ( empty($cardsToOutput) ) ? $cards : $cardsToOutput;

		$tagsOptions = explode(',',$options['e']);

		$tags = array_filter( $tagsOptions, function($t) {
			return $t != '';
		});
		
		if ( count($tags) < 1 ) {
			print 'Invalid Usage: Exclusion tags not provided!'.PHP_EOL;
  			exit(0);
		}
		
	  	foreach($cardsToOutput as $c) {
			
			$cardLabelValues = array_map(
				function ($label) { return $label->name; },
				$c->labels
			);

			$matchingLabels = array_intersect($cardLabelValues,$tags);

			if ( count( $matchingLabels ) == 0 ) {
				$newCardsIn[] = $c;
			}

		}

		$cardsToOutput = $newCardsIn;
		print "There are currently ".count($cardsToOutput)." cards without tags [{$options['e']}]".PHP_EOL.PHP_EOL;
	}

	if( isset($options['p']) && $options['p'] ) {
		$newCardsIn = [];
		$cardsToOutput = ( empty($cardsToOutput) ) ? $cards : $cardsToOutput;
		$sorted = [];

		$tagsOptions = explode(',',$options['p']);

		$tags = array_filter( $tagsOptions, function($t) {
			return $t != '';
		});
		
		if ( count($tags) < 1 ) {
			print 'Invalid Usage: Prioritization tags not provided!'.PHP_EOL;
  			exit(0);
		}

		foreach($tags as $t) {
			$tagTransformed = str_replace( ' ' , '_' , strtolower($t) );
			$sorted[$tagTransformed] = [];
		}

		foreach($cardsToOutput as $c) {
			$cardLabelValues = array_map(
				function ($label) { return $label->name; },
				$c->labels
			);

			$matchingLabels = array_values( array_intersect($tags,$cardLabelValues) );

			if ( count($matchingLabels) > 0 ) {
				$label = strtolower( $matchingLabels[0] );
				$cardTagTranformed = str_replace(' ','_',$label);
				array_push($sorted[$cardTagTranformed],$c);
			}
		}

		foreach( $tags as $t ) {
			$tagTransformed = str_replace( ' ' , '_' , strtolower($t) );
			$cardsForTag = $sorted[$tagTransformed];

			foreach($cardsForTag as $card) {
				array_push($newCardsIn,$card);
			}
		}

		$cardsToOutput = $newCardsIn;
		//print "Cards have been prioritized by tag sequence [{$options['p']}]".PHP_EOL.PHP_EOL;
	}

	if( isset($options['t']) && $options['t'] ) {
		$newCardsIn = [];
		$tags = explode(',',$options['t']);
	  	$cardsToOutput = (empty($cardsToOutput)) ? $cards : $cardsToOutput;

	  	foreach($cardsToOutput as $c) {
			
			$cardLabelValues = array_map(
				function ($label){ return $label->name; },
				$c->labels
			);

			if ( count(array_intersect($cardLabelValues,$tags)) == count($tags) ) {
				$newCardsIn[] = $c;
			}
		}

		$cardsToOutput = $newCardsIn;
		if ( count($tags) <= 1 ) {
			print "To date [".date('m/d/Y')."] tag [{$options['t']}] currently has " . count($cardsToOutput) . " cards.".PHP_EOL.PHP_EOL;
		} else {
			print "To date [".date('m/d/Y')."] tags [{$options['t']}] currently have " . count($cardsToOutput) . " cards.".PHP_EOL.PHP_EOL;
		}
	}

	if( isset($options['overview']) ) {
		$cardsToOutput = (empty($cardsToOutput)) ? $cards : $cardsToOutput;

		foreach($cardsToOutput as $c) {
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

	if ( isset($options['detailed']) ) {
		$cardsToOutput = (empty($cardsToOutput)) ? $cards : $cardsToOutput;
		$list = [];
		$body = '';

		foreach($cardsToOutput as $c){

			/*
			foreach ($c->customFieldItems as $cf) {
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
			}
			*/

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

		//sendEmail("State of {$tag} - [".date('d/m/yy')."]",$body,$body);

		exit(0);
	}

	if ( isset($options['csv']) ) {
		$cardsToOutput = (empty($cardsToOutput)) ? $cards : $cardsToOutput;

		// col headers
		print "LANE,LABELS,TITLE".PHP_EOL;

		foreach($cardsToOutput as $c){
			$labels = '';

			foreach($c->labels as $l) {
				$labels .= "[{$l->name}] ";
			}

			print ucwords($listArray[$c->idList]) .",{$labels},{$c->name}".PHP_EOL;
		}

		exit(0);
	}

	
	outputCards(cardsToOutput);
	exit(0);