  </main><!-- /.container -->

  <?php if ($action == "edit") { ?>
    <nav class="navbar fixed-bottom navbar-expand-sm navbar-light bg-light" style="justify-content: flex-end;">
      <button class="btn btn-secondary btn-save my-2 my-sm-0">Save</button>
    </nav>
  <?php } ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js" crossorigin="anonymous"></script>
    <script src="https://getbootstrap.com/docs/5.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
    <script src="/assets/scripts.js?v=<?php echo rand(0, 10000000) ?>"></script>

  </body>
</html>
