  </main><!-- /.container -->

  <nav class="navbar fixed-bottom navbar-expand-sm navbar-light bg-light" style="justify-content: flex-end;">

    <div class="collapse navbar-collapse" id="navbarColor01">
      <ul class="navbar-nav mr-auto">
        <?php
          $pages = array(
            array(
              "url" => "/deck/".$deck."/",
              "label" => "Preview",
              "action" => "preview"
            ),
            array(
              "url" => "/deck/".$deck."/stats/",
              "label" => "Stats",
              "action" => "stats"
            )
          );

          foreach ($pages as $single_page) {
            ?>
            <li class="nav-item<?php echo ($single_page['action'] == $action ? " active" : "") ?>">
              <a class="nav-link" href="<?php echo $single_page['url'] ?>"><?php echo $single_page['label'] ?></a>
            </li>
            <?php
          }

          if ($action != "stats") {
        ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Sort</a>
          <div class="dropdown-menu" style="top: inherit; bottom: 40px;">
            <?php
              $sorts = array(
                array(
                  "url" => "/deck/".$deck."/",
                  "label" => "Card Types",
                  "sort" => "type"
                ),
                array(
                  "url" => "/deck/".$deck."/?sort=category",
                  "label" => "Categories",
                  "sort" => "category"
                )
              );

              foreach ($sorts as $single_sort) {
                echo '<a class="dropdown-item'.($single_sort['sort'] == $sort ? " active" : "").'" href="'.$single_sort['url'].'">'.$single_sort['label'].'</a>';
              }
            ?>
          </div>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Output</a>
          <div class="dropdown-menu" style="top: inherit; bottom: 40px;">
            <?php
              $outputs = array(
                array(
                  "url" => "/deck/".$deck."/",
                  "label" => "Image",
                  "output" => "image"
                ),
                array(
                  "url" => "/deck/".$deck."/?output=text",
                  "label" => "Text",
                  "output" => "text"
                )
              );

              foreach ($outputs as $single_output) {
                echo '<a class="dropdown-item'.($single_output['output'] == $output ? " active" : "").'" href="'.$single_output['url'].'">'.$single_output['label'].'</a>';
              }
            ?>
          </div>
        </li>
        <?php } ?>
      </ul>
    </div>
    <?php if ($action == "edit") { ?>
      <div id="save-results" class="text-muted"></div>
      <button class="btn btn-primary btn-search-cards my-2 my-sm-0 mr-3">Ajouter une carte</button>
      <button class="btn btn-secondary btn-save my-2 my-sm-0">Enregistrer</button>
    <?php } ?>
  </nav>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://bootswatch.com/_vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/f6224a5728.js" crossorigin="anonymous"></script>
    <script src="/assets/chart.js"></script>
    <script src="/assets/scripts.js?v=<?php echo rand(0, 10000000) ?>"></script>

  </body>
</html>
