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

		$type_line = $data['type_line'];
		$type = get_card_type($data['type_line']);

		$update = $mysqli->prepare("UPDATE cards set type=? where id=?;");
		$update->bind_param("ss", $type, $card['id']);
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
$sort = (isset($_GET['sort']) && $_GET['sort'] == "category" ? "category" : "type");


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
					"type" => get_card_type($card_data['type_line']),
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
			$card['is_wishlist'] = 0;
			$html = generate_card($card);
			
			$stmt->close();
			$mysqli->close();

			$output = array(
				"card" => $card,
				"html" => $html
			);

			die( json_encode($output) );
		} else if ($_POST['action'] == "save") {
			$mysqli = new mysqli("127.0.0.1", $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
			$mysqli->set_charset('utf8mb4');

			$cards = $_POST['cards'];

			$current_cards = get_cards($deck);

			/* Get the existing saved cards for a backup purpose */
			$select = $mysqli->prepare("SELECT dc.qty, dc.card_id, dc.is_starred, dc.is_wishlist, dc.category FROM deck_cards dc WHERE dc.deck_id=?");
			$select->bind_param("s", $deck);
			$select->execute();
		
			$deck_backup = array();
			$result = $select->get_result();
			if ($result->num_rows) {
				while ($card = $result->fetch_assoc()) {
					$deck_backup[] = array(
						"id" => $card['card_id'],
						"qty" => $card['qty'],
						"category" => $card['category'],
						"is_starred" => $card['is_starred'],
						"is_wishlist" => $card['is_wishlist']
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
			foreach ($cards as $card) {
				$stats['total']++;

				$existing = false;
				$need_update = false;
				foreach ($current_cards as $current_index => $current_card) {
					if ($current_card['id'] == $card['card_id']) {
						$existing = true;

						if ($current_card['qty'] != $card['qty']
							|| $current_card['is_starred'] != $card['is_starred']
							|| $current_card['is_wishlist'] != $card['is_wishlist']
							|| $current_card['category'] != $card['category']) {
							$need_update = true;
						}

						// Remove it from the existing index (to be able to delete the remaining)
						$current_cards[$current_index] = null;
						break;
					}
				}

				$now = date("Y-m-d H:i:s");

				if ($existing) {
					if ($need_update) {
						$update = $mysqli->prepare("UPDATE deck_cards set qty=?, is_starred=?, is_wishlist=?, category=?, date_updated=? where deck_id=? AND card_id=?;");
						$update->bind_param("sddssss", $card['qty'], $card['is_starred'], $card['is_wishlist'], $card['category'], $now, $deck, $card['card_id']);
						$updated = $update->execute();
						$update->close();
		
						if ($updated) {
							$stats['updated']++;
						}
					}
				} else {
					$insert = $mysqli->prepare("INSERT INTO deck_cards (deck_id, card_id, qty, category, is_starred, is_wishlist, date_added, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
					$insert->bind_param("ssssddss", $deck, $card['card_id'], $card['qty'], $card['category'], $card['is_starred'], $card['is_wishlist'], $now, $now);
					$added = $insert->execute();
					$insert->close();
	
					if ($added) {
						$stats['added']++;
					}
				}
			}

			/* Delete existing card not in the deck anymore */
			foreach ($current_cards as $card) {
				if ($card != null) {
					$delete = $mysqli->prepare("DELETE FROM deck_cards WHERE deck_id=? AND card_id=?;");
					$delete->bind_param("ss", $deck, $card['id']);
					$deleted = $delete->execute();
					$delete->close();

					if ($deleted) {
						$stats['deleted']++;
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
	"commander" => array(
		"label" => "Commander"
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
	),
	"lands" => array(
		"label" => "Lands"
	),
	"" => array(
		"label" => "Uncategorized"
	)
);

$types = array(
	"commander" => array(
		"label" => "Commander",
		"type" => "creature",
		"is-legendary" => 1,
		"limit" => 1
	),
	"creature" => array(
		"label" => "Creatures",
		"type" => "creature"
	),
	"sorcery" => array(
		"label" => "Sorceries",
		"type" => "sorcery"
	),
	"instant" => array(
		"label" => "Instants",
		"type" => "instant"
	),
	"enchantment" => array(
		"label" => "Enchantments",
		"type" => "enchantment"
	),
	"artifact" => array(
		"label" => "Artifacts",
		"type" => "artifact"
	),
	"planeswalker" => array(
		"label" => "Planeswalker",
		"type" => "planeswalker"
	),
	"land" => array(
		"label" => "Lands",
		"type" => "land"
	)
);

if ($action == "edit" || $action == "preview") {
	$all_cards = get_cards($deck);
	$sections = ($sort == "type" ? $types : $categories);

	$cards = array(
		"deck" => array(),
		"wishlist" => array()
	);

	foreach ($all_cards as $card) {
		$card_type = ($card['is_wishlist'] == 1 ? "wishlist" : "deck");
		$cards[$card_type][] = $card;
	}
	?>
	<ul class="nav nav-tabs" style="margin: 20px 0;">
		<?php foreach ($cards as $type_id => $type_cards) { ?>
			<li class="nav-item">
				<a class="nav-link<?php echo ($type_id == "deck" ? " active" : "") ?>" data-toggle="tab" href="#<?php echo $type_id ?>"><?php echo $type_id ?> <span class="badge badge-primary badge-pill tab-total">2</span></a>
			</li>
		<?php } ?>
	</ul>
	<?php
	?>
	<div class="tab-content">
		<?php
		foreach ($cards as $type_id => $type_cards) {
			?>
			<div class="tab-pane fade<?php echo ($type_id == "deck" ? " active show" : "") ?>" id="<?php echo $type_id; ?>">

				<?php
				echo '<div class="types my-5">';
				$commander_colors = [];
				foreach($sections as $section_id => $section) {
					//$is_legendary = (isset($type['is-legendary']) ? (int)$type['is-legendary'] : 0);
					//$limit = (isset($type['limit']) ? (int)$type['limit'] : 0);
					//$card_type = (isset($type['type']) ? (int)$type['type'] : "");
					//$has_cards = (isset($cards[$type_id]) && count($cards[$type_id]) > 0 ? true : false);

					/* No cards in preview mode, skip this card types content */
					$cards = array();
					foreach ($type_cards as $single_card) {
						if ($single_card['category'] == "commander") {
							$commander_colors = explode(",", $single_card['colors']);
							if ($sort == "type") {
								$single_card['type'] = "commander";
							}
						}
						if ($single_card[ $sort ] == $section_id) {
							$cards[] = $single_card;
						}
					}
					?>
					<div class="card-section card border-primary mb-5"<?php echo (count($cards) == 0 ? " style='display: none'" : "") ?> data-section-id="<?php echo $section_id ?>">
					<div class="card-header">
						<span><?php echo $section['label'] ?><span class="badge badge-primary badge-pill cards-total"><?php echo count($cards) ?></span></span>
					</div>
					<div class="card-body mtg-cards">
						<?php 
							foreach ($cards as $card) {
								if ($output == "image") {
									echo generate_card($card);
								} else {
									echo $card['qty'] . " x " . $card['name_fr'] . " (". $card['name_en'].")<br />";
								}
							}
						?>
					</div>
					</div>
					<?php
				}
				echo '</div>';
				?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	echo '<script>var commander_colors = ' . json_encode($commander_colors) . ';</script>';
	echo "<script>var sort = '" . $sort . "';</script>";

	include("includes/modals.inc.php");

} else if ($action == "stats") {
	$cards = get_cards($deck);

	$mana_costs = array();
	$unique_colors = array();

	$card_colors = array();

	$card_types = array(
		"creature" => 0,
		"instant" => 0,
		"sorcery" => 0,
		"enchantment" => 0,
		"artifact" => 0,
		"land" => 0,
		"planeswalker" => 0,
	);

	$colors = array(
		"G" => "green",
		"U" => "blue",
		"B" => "black",
		"R" => "red",
		"W" => "white"
 	);

	foreach ($cards as $card) {
		if ($card['is_wishlist'] == 1) {
			continue;
		}

		$card_types[ $card['type'] ]++;
		
		if ($card['type'] != "land") {

			$color = $card['colors'];
			if ($color == "") {
				$color = "colorless";
			} else if (preg_match_all("|,|Uim", $color)) {
				$color = "gold";
			} else if (isset($colors[$color])) {
				$color = $colors[$color];
			}

			if ($color != "colorless") {
				if (!isset($card_colors[$color])) {
					$card_colors[$color] = 0;
				}
				$card_colors[ $color ] ++;
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
	<div class="card bg-light mb-3 mt-3">
		<div class="card-header">Mana Curve</div>
		<div class="card-body">
			<canvas id="canvas_casting_cost"></canvas>
		</div>
	</div>

	<div class="row">
		<div class="col-sm">
			<div class="card bg-light mb-3">
				<div class="card-header">Card Types</div>
				<div class="card-body">
					<canvas id="canvas_card_type"></canvas>
				</div>
			</div>
		</div>
		<div class="col-sm">
			<div class="card bg-light mb-3">
				<div class="card-header">Card Colors</div>
				<div class="card-body">
					<canvas id="canvas_card_color"></canvas>
				</div>
			</div>
		</div>
	</div>
	
	<script>
		var data_casting_cost = {
			labels: <?php echo json_encode($mana_cost_amounts); ?>,
			datasets: <?php echo json_encode($data) ?>
		};

		var data_card_type = {
			datasets: [{
					data: [
						<?php echo $card_types['creature'] ?>,
						<?php echo $card_types['land'] ?>,
						<?php echo $card_types['instant'] ?>,
						<?php echo $card_types['sorcery'] ?>,
						<?php echo $card_types['artifact'] ?>,
						<?php echo $card_types['enchantment'] ?>,
						<?php echo $card_types['planeswalker'] ?>
					],
					backgroundColor: [
						"red",
						"blue",
						"black",
						"orange",
						"yellow",
						"gray",
						"white"
					],
					label: 'Dataset 1'
				}],
				labels: [
					'Creatures',
					'Lands',
					'Instants',
					'Sorceries',
					'Artifacts',
					'Enchantments',
					'Planeswalkers'
				]
		};

		var data_card_color = {
			datasets: [{
					data: <?php echo json_encode(array_values($card_colors)) ?>,
					backgroundColor: <?php echo json_encode(array_keys($card_colors)) ?>,
					label: 'Dataset 1'
				}],
				labels: <?php echo json_encode(array_keys($card_colors)) ?>
		};

		var ctx_casting_cost = document.getElementById('canvas_casting_cost').getContext('2d');
		var ctx_card_type = document.getElementById('canvas_card_type').getContext('2d');
		var ctx_card_color = document.getElementById('canvas_card_color').getContext('2d');
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