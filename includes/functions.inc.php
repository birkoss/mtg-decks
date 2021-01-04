<?php

// If a deck is provided, force it to be valid
if (isset($_GET['deck'])) {
	$valid_deck = false;
	foreach (get_decks() as $deck) {
		if ($deck['id'] == $_GET['deck']) {
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


function get_cards($deck) {
	include("var.inc.php");

	$mysqli = new mysqli("127.0.0.1", $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
	$mysqli->set_charset('utf8mb4');

	$select = $mysqli->prepare("SELECT dc.qty, dc.type, c.id, c.name_fr, c.name_en, c.colors, c.data, (CASE c.name_fr WHEN '' THEN c.name_en ELSE c.name_fr END) as visible_name  FROM deck_cards dc LEFT JOIN cards c on c.id=dc.card_id WHERE dc.deck_id=? ORDER BY visible_name");
	$select->bind_param("s", $deck);
	$select->execute();

	$cards = array();

	$result = $select->get_result();
	if ($result->num_rows) {
		while ($card = $result->fetch_assoc()) {
			if (!isset($cards[$card['type']])) {
				$cards[ $card['type'] ] = array();
			}
			$cards[ $card['type'] ][] = $card;
		}
	}
	
	$select->close();
	$mysqli->close();

	return $cards;
}


function get_decks() {
	include("var.inc.php");

	$decks = array();

	$mysqli = new mysqli("127.0.0.1", $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
	$mysqli->set_charset('utf8mb4');

	$select = $mysqli->prepare("SELECT * FROM decks");
	$select->execute();

	$result = $select->get_result();
	if ($result->num_rows) {
		while($deck = $result->fetch_assoc()) {
			$decks[] = $deck;
		}
	}
	
	$select->close();
	$mysqli->close();

	return $decks;
}


function generate_card($card) {
	$qty = max(1, (int)$card['qty']);

	$html = "";
	$html .= '<div class="mtg-card" data-id="'.$card['id'].'" data-name="'.($card['name_fr'] != "" ? $card['name_fr'] : $card['name_en']).'" data-qty="'.$qty.'">';
	$html .= '<img loading="lazy" class="responsive" width="480" height="640" src="/assets/cards/' . $card['id'] . '.jpg" />';
	$html .= '</div>';
	return $html;
}