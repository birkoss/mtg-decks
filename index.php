<?php
include("includes/functions.inc.php");

$deck = (isset($_GET['deck']) ? $_GET['deck'] : "");
$action = (isset($_GET['action']) ? $_GET['action'] : "");

if ($deck != "" && $action == "edit" && isset($_POST['deck'])) {
	file_put_contents("decks/".$deck.".json", json_encode($_POST['deck']));
	echo "OK";
	die("");
}

include("includes/header.inc.php");

if ($deck != "") {
	$deck_data = json_decode(file_get_contents("decks/".$deck.".json"), true);

	?><script>
		let deck_data = <?php echo json_encode($deck_data); ?>;
	</script><?php

	$types = array(
		"commander" => array(
			"label" => "Commander",
			"type" => "creature"
		),
		"creatures" => array(
			"label" => "Creatures",
			"type" => "creature"
		)
	);

	echo '<div class="types my-5">';
	foreach($types as $type_id => $type) {
		?>
		<div class="card-type card border-primary mb-5" data-type="<?php echo $type_id ?>">
		  <div class="card-header">
		  	<span><?php echo $type['label'] ?></span>
		  	<?php if ($action == "edit") { ?>
		  		<button class="btn-search-cards btn btn-primary my-2 my-sm-0" data-type="<?php echo json_encode($type) ?>">Search</button>
		  	<?php } ?>
		  </div>
		  <div class="card-body mtg-cards">
		    <p class="card-text">No cards for the moment.</p>
		  </div>
		</div>
		<?php
	}
	echo '</div>';
	?>
	<div class="modal fade" id="modalSearchCard" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title">Cards Search</h5>
			<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
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
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
		</div>
		</div>
	</div>
	</div>
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