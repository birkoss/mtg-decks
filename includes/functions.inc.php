<?php

// If a deck is provided, force it to be valid
if (isset($_GET['deck'])) {
	$valid_deck = false;
	foreach (get_decks() as $deck) {
		if (substr($deck, 0, -5) == $_GET['deck']) {
			$valid_deck = true;
			break;
		}
	}
	if (!$valid_deck) {
		header("Location: /");
		exit();
	}
}

// Helpers
function get_decks() {
	$decks = array();

	$path = "./decks";

	if ($handle = opendir($path)) {
	    while (false !== ($entry = readdir($handle))) {
	    	if (is_file($path."/".$entry) && preg_match("|.json$|Uim", $entry)) {
	        	$decks[] = $entry;
	    	}
	    }
	    closedir($handle);
	}

	return $decks;
}