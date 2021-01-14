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

	$select = $mysqli->prepare("SELECT dc.qty, dc.type, dc.is_starred, dc.category, c.id, c.name_fr, c.name_en, c.colors, c.cmc, c.data, (CASE c.name_fr WHEN '' THEN c.name_en ELSE c.name_fr END) as visible_name  FROM deck_cards dc LEFT JOIN cards c on c.id=dc.card_id WHERE dc.deck_id=? ORDER BY visible_name");
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
	$category = (isset($card['category']) ? $card['category'] : "");

	$html = "";
	$html .= '<div class="mtg-card" data-category="'.$category.'" data-id="'.$card['id'].'" data-starred="'.(int)$card['is_starred'].'" data-name="'.($card['name_fr'] != "" ? $card['name_fr'] : $card['name_en']).'" data-qty="'.$qty.'">';
	$html .= '<img loading="lazy" class="responsive" width="480" height="640" src="/assets/cards/' . $card['id'] . '.jpg" />';
	$html .= '</div>';
	return $html;
}


function get_card_type($type_line) {
	$type = "";

	if (preg_match("|Sorcery|Uim", $type_line)) {
		$type = "sorcery";
	} else if (preg_match("|Instant|Uim", $type_line)) {
		$type = "instant";
	} else if (preg_match("|Planeswalker|Uim", $type_line)) {
		$type = "planeswalker";
	} else if (preg_match("|Artifact|Uim", $type_line)) {
		$type = "artifact";
	} else if (preg_match("|Creature|Uim", $type_line)) {
		$type = "creature";
	} else if (preg_match("|Enchantment|Uim", $type_line)) {
		$type = "enchantment";
	} else if (preg_match("|Land|Uim", $type_line)) {
		$type = "land";
	}

	return $type;
}