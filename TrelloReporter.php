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

	$options = getopt("b:t:",['overview::','detailed::']);

	$key = getenv('TRELLO_API_KEY',true);
  	$token = getenv('TRELLO_API_TOKEN',true);

  	if( !isset($options['b']) || !isset($options['t']) ) {
  		print 'Invalid Usage: Please make sure board id and tag are set!'.PHP_EOL;
  		exit(0);
  	}

  	if( !$key || !$token ) {
  		print 'Invalid Usage: API key and token required!'.PHP_EOL;
  		exit(0);
  	}

  	$board = $options['b'];
  	$tag = $options['t'];
  	$listArray = [];
	$cardsIn = [];
	$listCounts = [];

	$lists = get('list',$board,$key,$token);
	foreach($lists as $l) {
		$listArray[$l->id]  = $l->name;
	}

	$cards = get('cards',$board,$key,$token);
	foreach($cards as $c) {
		foreach($c->labels as $l){
			if (strpos($l->name,$tag) !== false) {
				$cardsIn[] = [
					'id' => $c->id,
					'name' => $c->name,
					'list' => $c->idList
				];

				if (!isset($listCounts[$c->idList])) {
					$listCounts[$c->idList] = 1;
				} else {
					$listCounts[$c->idList]++;
				}
			}	
		}
	}

	print "{$tag} currently has " . count($cardsIn) . " cards tagged.".PHP_EOL;

	if(isset($options['overview'])) {
		foreach($listCounts as $id => $total) {
			print "{$total} item(s) in '{$listArray[$id]}'".PHP_EOL;
		}
	} else if (isset($options['detailed'])) {
		foreach($cardsIn as $c){
			print "{$c['name']} is in {$listArray[$c['list']]}".PHP_EOL;
		}
	}

	exit(0);