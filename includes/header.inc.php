<!doctype html>
<html lang="en" class="h-100">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <title>MTG</title>

    <!-- Bootstrap core CSS -->
    <link href="https://bootswatch.com/4/darkly/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    
    <!-- Custom styles for this template -->
    <link href="/assets/style.css?v=<?php echo rand(0, 10000000000); ?>" rel="stylesheet">
  </head>
  <body class="d-flex flex-column h-100 action-<?php echo $action ?>">
    
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <a class="navbar-brand" href="/">MTG</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarColor01">
    <ul class="navbar-nav ml-auto">
      <?php
        $decks = get_decks();
        foreach ($decks as $d) {
          $deck_filename = substr($d, 0, -5);
          $is_selected = false;
          if (isset($_GET['deck']) && $_GET['deck'] == $deck_filename) {
            $is_selected = true;
          }
          ?>
          <li class="nav-item">
            <a class="nav-link<?php echo ($is_selected ? " active" : "") ?>" href="/deck/<?php echo $deck_filename ?>/"><?php echo ucfirst($deck_filename) ?></a>
          </li>
          <?php
        }
      ?>
    </ul>
  </div>
</nav>

<main class="container flex-shrink-0">