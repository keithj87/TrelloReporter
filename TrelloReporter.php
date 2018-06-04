<?php
	function get($type,$boardId,$key,$token)
  	{
  		$urls = [
  			'list' => "https://api.trello.com/1/boards/{$boardId}/lists?fields=name&cards=none&key={$key}&token={$token}",
			'cards' => "https://api.trello.com/1/boards/{$boardId}/cards/?&fields=id,name,labels,idList&key={$key}&token={$token}"
	  	];

	  	$ch = curl_init($urls[$type]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$results = curl_exec($ch);
		curl_close($ch);
		return json_decode($results);
  	}

	$options = getopt("b:t:l:",['overview::','detailed::']);

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

	$lists = get('list',$board,$key,$token);
	foreach($lists as $l) {
		$listArray[$l->id] = strtolower($l->name);
	}

	$cards = get('cards',$board,$key,$token);
	print "There are currently " . count($cards) . " cards for this board.".PHP_EOL;

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

		print "Tag [{$tag}] currently has " . count($cardsIn) . " cards tagged.".PHP_EOL;
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

		foreach($cardsIn as $c){
			print "{$c->name} is in {$listArray[$c->idList]}".PHP_EOL;
		}

		exit(0);
	}

	$cardsIn = (empty($cardsIn)) ? $cards : $cardsIn;
	
	foreach($cardsIn as $c) {
		print "{$c->name}".PHP_EOL;
	}

	exit(0);