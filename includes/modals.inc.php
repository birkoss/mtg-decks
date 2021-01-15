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