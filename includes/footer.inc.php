  </main><!-- /.container -->

  <?php if ($action == "edit") { ?>
    <nav class="navbar fixed-bottom navbar-expand-sm navbar-light bg-light" style="justify-content: flex-end;">
      <button class="btn btn-secondary btn-save my-2 my-sm-0">Save</button>
    </nav>
  <?php } ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://bootswatch.com/_vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/scripts.js?v=<?php echo rand(0, 10000000) ?>"></script>

  </body>
</html>
