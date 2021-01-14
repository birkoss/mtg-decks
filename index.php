<?php
header('Content-Type: text/html; charset=utf-8');

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

// @TODO: Allow to add status tracking regarding if we want to change the card (wrong language, is foil, condition, etc...)
// @TODO: Add the qty in a badge on the card (always visible, and updated)
// @TODO: Prevent adding a card with it already exists in the current TYPE (prevent duplicate, and in the total deck)
// @TODO: Prevent search without keyword (min length)
// @TODO: Add navbar for view mode (picture mode, compact mode, stats, etc.)

include("includes/var.inc.php");
include("includes/functions.inc.php");

/*
$mysqli = new mysqli("127.0.0.1", $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
$mysqli->set_charset('utf8mb4');

$stmt = $mysqli->prepare("SELECT * FROM cards");
$stmt->execute();

$result = $stmt->get_result();
if ($result->num_rows) {
	while ($card = $result->fetch_assoc()) {
		$data = json_decode($card['data'], true);

		$colors = implode(",", $data['colors']);
		$update = $mysqli->prepare("UPDATE cards set lang=?, colors=?, cmc=? where id=?;");
		$update->bind_param("ssss", $data['lang'], $colors, $data['cmc'], $card['id']);
		$updated = $update->execute();
		$update->close();
	}
}

$stmt->close();
$mysqli->close();

die("-------------------------");
*/

$deck = (isset($_GET['deck']) ? $_GET['deck'] : "");
$action = (isset($_GET['action']) ? $_GET['action'] : "");
$output = (isset($_GET['output']) && $_GET['output'] == "text" ? "text" : "image");

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
			$mysqli->set_charset('utf8mb4');

			$stmt = $mysqli->prepare("SELECT * FROM cards WHERE id=?;");
			$stmt->bind_param("s", $_POST['id']);

			$stmt->execute();

			$card_data = json_decode($_POST['card'], true);

			$name_fr = $name_en = "";
			if (isset($card_data['card_faces']) && count($card_data['card_faces']) > 1) {
				$name_fr = (isset($card_data['card_faces'][0]['printed_name']) ? $card_data['card_faces'][0]['printed_name'] : "");
				$name_en = $card_data['card_faces'][0]['name'];
				$colors = $card_data['card_faces'][0]['colors'];
			} else {
				$name_fr = (isset($card_data['printed_name']) ? $card_data['printed_name'] : "");
				$name_en = $card_data['name'];
				$colors = $card_data['colors'];
			}

			$result = $stmt->get_result();
			if ($result->num_rows) {
				$card = $result->fetch_assoc();
			} else {
				$card = array(
					"id" => $_POST['id'],
					"name_fr" => $name_fr,
					"name_en" => $card_data['name'],
					"colors" => implode(",", $colors),
					"cmc" => $card_data['cmc'],
					"lang" => $card_data['lang'],
					"date_added" => date("Y-m-d H:i:s"),
					"date_updated" => date("Y-m-d H:i:s"),
					"type" => "",
					"data" => json_encode($card_data)
				);

				try {
					$insert = $mysqli->prepare("INSERT INTO cards (id, name_fr, name_en, lang, cmc, colors, date_added, date_updated, type, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
					$insert->bind_param("ssssssssss", $card['id'], $card['name_fr'], $card['name_en'], $card['lang'], $card['cmc'], $card['colors'], $card['date_added'], $card['date_updated'], $card['type'], $card['data']);
					$result = $insert->execute();
					
				} catch (Exception $e) {
					print_r($e);
				}
				$insert->close();
			}

			$card['qty'] = 1;
			$card['is_starred'] = 0;
			$html = generate_card($card);
			
			$stmt->close();
			$mysqli->close();

			die($html);
		} else if ($_POST['action'] == "save") {
			$mysqli = new mysqli("127.0.0.1", $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
			$mysqli->set_charset('utf8mb4');

			$cards = $_POST['cards'];

			$current_cards = get_cards($deck);

			/* Get the existing saved cards for a backup purpose */
			$deck_backup = array();
			$select = $mysqli->prepare("SELECT dc.qty, dc.type, dc.card_id, dc.is_starred, dc.category FROM deck_cards dc WHERE dc.deck_id=?");
			$select->bind_param("s", $deck);
			$select->execute();
		
			$result = $select->get_result();
			if ($result->num_rows) {
				while ($card = $result->fetch_assoc()) {
					if (!isset($deck_backup[$card['type']])) {
						$deck_backup[ $card['type'] ] = array();
					}
					$deck_backup[ $card['type'] ][] = array(
						"id" => $card['card_id'],
						"qty" => $card['qty'],
						"category" => $card['category'],
						"is_starred" => $card['is_starred']
					);
				}
			}
			$select->close();


			$stats = array(
				"updated" => 0,
				"added" => 0,
				"deleted" => 0,
				"total" => 0,
				"backuped" => 0
			);

			/* Update quantity or add new cards in deck */
			foreach ($cards as $type_id => $type_cards) {
				foreach ($type_cards as $card) {
					$stats['total']++;

					$existing = false;
					$need_update = false;
					if (isset($current_cards[$type_id])) {
						foreach ($current_cards[$type_id] as $current_index => $current_card) {
							if ($current_card['id'] == $card['card_id']) {
								$existing = true;
	
								if ($current_card['qty'] != $card['qty']
									|| $current_card['is_starred'] != $card['is_starred']
									|| $current_card['category'] != $card['category']) {
									$need_update = true;
								}
	
								// Remove it from the existing index (to be able to delete the remaining)
								$current_cards[$type_id][$current_index] = null;
								break;
							}
						}
					}

					$now = date("Y-m-d H:i:s");

					if ($existing) {
						if ($need_update) {
							$update = $mysqli->prepare("UPDATE deck_cards set qty=?, is_starred=?, category=?, date_updated=? where deck_id=? AND card_id=? AND type=?;");
							$update->bind_param("sdsssss", $card['qty'], $card['is_starred'], $card['category'], $now, $deck, $card['card_id'], $type_id);
							$updated = $update->execute();
							$update->close();
			
							if ($updated) {
								$stats['updated']++;
							}
						}
					} else {
						$insert = $mysqli->prepare("INSERT INTO deck_cards (deck_id, card_id, type, qty, category, is_starred, date_added, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
						$insert->bind_param("sssssdss", $deck, $card['card_id'], $type_id, $card['qty'], $card['category'], $card['is_starred'], $now, $now);
						$added = $insert->execute();
						$insert->close();
		
						if ($added) {
							$stats['added']++;
						}
					}
				}
			}

			/* Delete existing card not in the deck anymore */
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

			/* If we need to create a backup, do it */
			if ($stats['updated'] > 0 || $stats['deleted'] > 0 || $stats['added'] > 0) {
				$now = date("Y-m-d H:i:s");
				$cards = json_encode($deck_backup);

				$insert = $mysqli->prepare("INSERT INTO deck_backups (deck_id, date_added, cards) VALUES (?, ?, ?);");
				$insert->bind_param("sss", $deck, $now, $cards);
				$backuped = $insert->execute();
				$insert->close();

				if ($backuped) {
					$stats['backuped'] = 1;
				}
			}

			$mysqli->close();

			die(json_encode($stats));
		}
	}
}

include("includes/header.inc.php");

$categories = array(
	"lands" => array(
		"label" => "Lands"
	),
	"draw" => array(
		"label" => "Draw"
	),
	"ramp" => array(
		"label" => "Ramp"
	),
	"removal" => array(
		"label" => "Removal"
	),
	"strategy" => array(
		"label" => "Strategy"
	)
);

$types = array(
	"commander" => array(
		"label" => "Commander",
		"type" => "creature",
		"is-legendary" => 1,
		"limit" => 1
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
	"planeswalker" => array(
		"label" => "Planeswalker",
		"type" => "planeswalker"
	),
	"lands" => array(
		"label" => "Lands",
		"type" => "land"
	),
	"wishlist" => array(
		"label" => "Wishlist",
		"exclude" => true
	)
);

if ($action == "preview" || $action == "edit") {
	$cards = get_cards($deck);

	echo '<div class="types my-5">';
	$commander_colors = [];
	foreach($types as $type_id => $type) {
		$is_legendary = (isset($type['is-legendary']) ? (int)$type['is-legendary'] : 0);
		$limit = (isset($type['limit']) ? (int)$type['limit'] : 0);
		$card_type = (isset($type['type']) ? (int)$type['type'] : "");
		$has_cards = (isset($cards[$type_id]) && count($cards[$type_id]) > 0 ? true : false);

		/* No cards in preview mode, skip this card types content */
		if ($action != "edit" && !$has_cards) {
			continue;
		}
		?>
		<div class="card-type card border-primary mb-5" data-limit="<?php echo $limit ?>" data-legendary="<?php echo $is_legendary ?>" data-type="<?php echo $type_id ?>" data-card-type="<?php echo $card_type ?>">
		  <div class="card-header">
		  	<span><?php echo $type['label'] ?><span class="cards-total"></span></span>
		  	<?php if ($action == "edit") { ?>
		  		<button class="btn-search-cards btn btn-primary my-2 my-sm-0">Ajouter une carte</button>
		  	<?php } ?>
		  </div>
		  <div class="card-body mtg-cards">
		    <p class="card-text"<?php echo ($has_cards ? " style='display: none'" : "") ?>>No cards for the moment.</p>
			<?php 
			if ($has_cards) {
				foreach ($cards[$type_id] as $card) {
					if ($output == "image") {
						echo generate_card($card);
					} else {
						echo $card['qty'] . " x " . $card['name_fr'] . " (". $card['name_en'].")<br />";
					}
				}

				if ($type_id == "commander") {
					$commander = $cards[$type_id][0];
					$commander_colors = explode(",", $commander['colors']);
				}
			}
			?>
		  </div>
		</div>
		<?php
	}
	echo '</div>';

	echo '<script>var commander_colors = ' . json_encode($commander_colors) . ';</script>';

	if ($action == "edit") {
		?>
		<div class="modal fade" id="modalSearchCard" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
				<div class="modal-header" style="align-items: center;">
					<h5 class="modal-title">Recherche </h5>

					<div class="modal-lang btn-group btn-group-toggle ml-3" data-toggle="buttons" style="">
						<label class="btn btn-primary"><input type="radio" name="options" id="option1" value="fr" autocomplete="off" checked=""> Français</label>
						<label class="btn btn-primary"><input type="radio" name="options" id="option2" value="en" autocomplete="off"> Anglais</label>
					</div>

					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form id="formSearchCard">
					<div class="form-group">
						<label for="inputSearchCard">Nom de la carte</label>
						<input type="text" class="form-control" style="max-width: 300px;" id="inputSearchCard" placeholder="Nom de la carte..." autocomplete="off" />
						</div>
					</form>
					<div id="resultSearchCard"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
				</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="modalCategory" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
				<div class="modal-header" style="align-items: center;">
					<h5 class="modal-title">Catégorie</h5>

					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="list-group">
						<?php
						foreach ($categories as $category_id => $category) {
							?>
							<a href="#" data-category="<?php echo $category_id ?>" class="card-category list-group-item list-group-item-action d-flex justify-content-between align-items-center">
								<?php echo $category['label'] ?>
								<span class="badge badge-primary badge-pill">0</span>
							</a>
							<?php
						}
						?>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
				</div>
				</div>
			</div>
		</div>
		<?php
	}
} else if ($action == "stats") {
	$cards = get_cards($deck);

	$mana_costs = array();
	$unique_colors = array();

	$colors = array(
		"G" => "green",
		"U" => "blue",
		"B" => "black",
		"R" => "red",
		"W" => "white"
 	);

	foreach($types as $type_id => $type) {
		/* Skip excluded Card Types */
		if (isset($type['exclude']) && $type['exclude'] ) {
			continue;
		}
		
		if (isset($cards[$type_id]) && count($cards[$type_id]) > 0) {
			foreach ($cards[$type_id] as $card) {
				if ($type_id != "lands") {

					$color = $card['colors'];
					if ($color == "") {
						$color = "colorless";
					} else if (preg_match_all("|,|Uim", $color)) {
						$color = "gold";
					} else if (isset($colors[$color])) {
						$color = $colors[$color];
					}

					$unique_colors[ $color ] = 1;

					if (!isset($mana_costs[ $card['cmc'] ])) {
						$mana_costs[ $card['cmc'] ] = array();
					}
					if (!isset($mana_costs[ $card['cmc'] ][ $color ])) {
						$mana_costs[ $card['cmc'] ][ $color ] = 0;
					}

					$mana_costs[ $card['cmc'] ][ $color ]++;
				}
			}
			//print_r($cards[$type_id]);

			//die();
		}
	}

	$mana_cost_amounts = array_keys($mana_costs);
	sort($mana_cost_amounts);

	$datasets = array();
	foreach ($unique_colors as $color => $junk) {
		$datasets[$color] = array();
		foreach ($mana_cost_amounts as $amount) {
			$datasets[$color][$amount] = 0;
		}
	}

	foreach ($mana_costs as $amount => $colors) {
		foreach ($colors as $color => $total) {
			$datasets[$color][$amount] = $total;
		}
	}

	$data = array();
	foreach ($datasets as $color => $d) {
		$data[] = array(
			"label" => ucfirst($color),
			"backgroundColor" => ($color == "colorless" ? "gray" : $color),
			"data" => array_values($d)
		);
	}
	

	ksort($mana_costs);
	//print_r($mana_costs);
	?>
	<canvas id="canvas"></canvas>
	<script>
		var barChartData = {
			labels: <?php echo json_encode($mana_cost_amounts); ?>,
			datasets: <?php echo json_encode($data) ?>

		};


		var ctx = document.getElementById('canvas').getContext('2d');
	</script>
	<?php
} else {
	?>
	<div class="jumbotron mt-5">
		<h1 class="display-3">MTG Decks</h1>
		<p class="lead">This is a simple MTG deck editor.</p>
	</div>
	<?php
}

include("includes/footer.inc.php");