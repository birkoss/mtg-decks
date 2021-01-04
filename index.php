<?php

// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

// @TODO: Save should show progress and status (show result (updated, added, deleted))
// @TODO: deck_cards_history (and delete it also on deleting the card?)
// @TODO: Add lazy load
// @TODO: Allow language switcher in the search modal
// @TODO: Allow to add status regarding if we want to change the card (wrong language, etc...)
// @TODO: Add the qty in a badge (always visible)
// @TODO: Force the color to be depending on the Commander Identity
// @TODO: Allow to limit the number of card in a type (commander: only 1)
// @TODO: Prevent adding a card with it already exists in the current TYPE

include("includes/var.inc.php");
include("includes/functions.inc.php");

$deck = (isset($_GET['deck']) ? $_GET['deck'] : "");
$action = (isset($_GET['action']) ? $_GET['action'] : "");

if ($action == "image" && isset($_GET['image'])) {
	$image_id = $_GET['image'];

	$filename = "assets/cards/".$image_id.".jpg";
	if (!file_exists($filename)) {
		$content = file_get_contents("https://c1.scryfall.com/file/scryfall-cards/border_crop/front/" . substr($image_id, 0, 1) . "/" . substr($image_id, 1, 1) . "/" . $image_id . ".jpg?1562404626");
		file_put_contents($filename, $content);
	}

	header('Content-type: image/jpeg');
	echo file_get_contents($filename);
	die();
}

if ($deck != "" ) {
	if (isset($_POST['action'])) {
		if ($_POST['action'] == "import") {
			$mysqli = new mysqli("127.0.0.1", $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

			$stmt = $mysqli->prepare("SELECT * FROM cards WHERE id=?;");
			$stmt->bind_param("s", $_POST['id']);

			$stmt->execute();

			$card_data = json_decode($_POST['card'], true);

			$result = $stmt->get_result();
			if ($result->num_rows) {
				$card = $result->fetch_assoc();
			} else {
				$card = array(
					"id" => $_POST['id'],
					"name" => $card_data['printed_name'],
					"name_en" => $card_data['name'],
					"date_added" => date("Y-m-d H:i:s"),
					"date_updated" => date("Y-m-d H:i:s"),
					"type" => "",
					"data" => json_encode($card_data)
				);

				try {
					$insert = $mysqli->prepare("INSERT INTO cards (id, name, name_en, date_added, date_updated, type, data) VALUES (?, ?, ?, ?, ?, ?, ?);");
					$insert->bind_param("sssssss", $card['id'], $card['name'], $card['name_en'], $card['date_added'], $card['date_updated'], $card['type'], $card['data']);
					$result = $insert->execute();
					
				} catch (Exception $e) {
					print_r($e);
				}
				$insert->close();
			}

			$card['qty'] = 1;
			$html = generate_card($card);
			
			$stmt->close();
			$mysqli->close();

			die($html);
		} else if ($_POST['action'] == "save") {
			$mysqli = new mysqli("127.0.0.1", $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

			$cards = $_POST['cards'];

			$current_cards = get_cards($deck);

			$stats = array(
				"updated" => 0,
				"added" => 0,
				"deleted" => 0,
				"total" => 0
			);

			foreach ($cards as $type_id => $type_cards) {
				foreach ($type_cards as $card) {
					$stats['total']++;

					$existing = false;
					$need_update = false;
					foreach ($current_cards[$type_id] as $current_index => $current_card) {
						if ($current_card['id'] == $card['card_id']) {
							$existing = true;

							if ($current_card['qty'] != $card['qty']) {
								$need_update = true;
							}

							// Remove it from the existing index (to be able to delete the remaining)
							$current_cards[$type_id][$current_index] = null;
							break;
						}
					}

					if ($existing) {
						if ($need_update) {
							$update = $mysqli->prepare("UPDATE deck_cards set qty=?, date_updated=? where deck_id=? AND card_id=? AND type=?;");
							$update->bind_param("sssss", $card['qty'], date("Y-m-d H:i:s"), $deck, $card['card_id'], $type_id);
							$updated = $update->execute();
							$update->close();
			
							if ($updated) {
								$stats['updated']++;
							}
						}
					} else {
						$insert = $mysqli->prepare("INSERT INTO deck_cards (deck_id, card_id, type, qty, date_added, date_updated) VALUES (?, ?, ?, ?, ?, ?);");
						$insert->bind_param("ssssss", $deck, $card['card_id'], $type_id, $card['qty'], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
						$added = $insert->execute();
						$insert->close();
		
						if ($added) {
							$stats['added']++;
						}
					}
				}
			}

			foreach ($current_cards as $type_id => $type_cards) {
				foreach ($type_cards as $card) {
					if ($card != null) {
						$delete = $mysqli->prepare("DELETE FROM deck_cards WHERE deck_id=? AND card_id=? AND type=?;");
						$delete->bind_param("sss", $deck, $card['id'], $type_id);
						$deleted = $delete->execute();
						$delete->close();

						if ($deleted) {
							$stats['deleted']++;
						}
					}
				}
			}

			$mysqli->close();

			print_r($stats);
			die("OK");
		}
	}
}

include("includes/header.inc.php");

if ($deck != "") {
	$cards = get_cards($deck);

	$types = array(
		"commander" => array(
			"label" => "Commander",
			"type" => "creature",
			"is-legendary" => 1
			/* @TODO: limit => 1 */
		),
		"creatures" => array(
			"label" => "Creatures",
			"type" => "creature"
		),
		"sorceries" => array(
			"label" => "Sorceries",
			"type" => "sorcery"
		),
		"instants" => array(
			"label" => "Instants",
			"type" => "instant"
		),
		"enchantments" => array(
			"label" => "Enchantments",
			"type" => "enchantment"
		),
		"artifacts" => array(
			"label" => "Artifacts",
			"type" => "artifact"
		),
		"lands" => array(
			"label" => "Lands",
			"type" => "land"
		),
		"wishlist" => array(
			"label" => "Wishlist"
		)
	);

	echo '<div class="types my-5">';
	foreach($types as $type_id => $type) {
		?>
		<div class="card-type card border-primary mb-5" data-legendary="<?php echo (int)$type['is-legendary'] ?>" data-type="<?php echo $type_id ?>" data-card-type="<?php echo $type['type'] ?>">
		  <div class="card-header">
		  	<span><?php echo $type['label'] ?><span class="cards-total"></span></span>
		  	<?php if ($action == "edit") { ?>
		  		<button class="btn-search-cards btn btn-primary my-2 my-sm-0" data-type="<?php echo json_encode($type) ?>">Search</button>
		  	<?php } ?>
		  </div>
		  <div class="card-body mtg-cards">
		    <p class="card-text"<?php echo (isset($cards[$type_id]) && count($cards[$type_id]) > 0 ? " style='display: none'" : "") ?>>No cards for the moment.</p>
			<?php 
			if (isset($cards[$type_id])) {
				foreach ($cards[$type_id] as $card) {
					echo generate_card($card);
				}
			}
			?>
		  </div>
		</div>
		<?php
	}
	echo '</div>';

	if ($action == "edit") {
		?>
		<div class="modal fade" id="modalSearchCard" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Cards Search</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form id="formSearchCard">
					<div class="form-group">
						<label for="inputSearchCard">Card Name</label>
						<input type="text" class="form-control" style="max-width: 300px;" id="inputSearchCard" placeholder="Card Name" autocomplete="off" />
						</div>
					</form>
					<div id="resultSearchCard"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
				</div>
			</div>
		</div>
		<?php
	}
} else {
	?>
	<div class="jumbotron mt-5">
		<h1 class="display-3">MTG Decks</h1>
		<p class="lead">This is a simple MTG deck editor.</p>
	</div>
	<?php
}

include("includes/footer.inc.php");