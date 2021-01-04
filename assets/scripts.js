var search_cards = {};

jQuery(document).ready(function() {
    /* Enable events */
    if (jQuery("body.action-edit").length !== 0) {
        create_deck_cards_events();
    }

    update_cards_total();

    /* Search the API when the modal form is submitted */
    jQuery('#formSearchCard').submit(function(event) {
        event.preventDefault();

        jQuery("#resultSearchCard").html("Searching...");

        let query = 'lang:fr ' + jQuery('#inputSearchCard').val();

        if (jQuery(".card-type[data-active=1]").data("card-type") != "") {
            query = "type:" + jQuery(".card-type[data-active=1]").data("card-type") + " " + query;
        }
        if (jQuery(".card-type[data-active=1]").data("legendary") != "") {
            query = "type:legendary " + query;
        }

        jQuery.ajax(
            'https://api.scryfall.com/cards/search?q=' + query
        ).done(function (res) {
            if (res.data) {
                jQuery("#resultSearchCard").html("");
                
                search_cards = {};
                res.data.forEach(function(item) {
                    console.log(item);
                    search_cards[ item['id'] ] = item;
                    card = {
                        name: item['printed_name'],
                        mana_cost: item['mana_cost'],
                        image_url: "https://c1.scryfall.com/file/scryfall-cards/border_crop/front/" + item['id'].substring(0, 1) + "/" + item['id'].substring(1, 2) + "/" + item['id'] + ".jpg?1562404626",
                        image_id: item['id'],
                        id: item['id']
                    }
                    jQuery("#resultSearchCard").append( generate_card(card) );
                });

                jQuery(".modal-body img.mtg-card-thumbnail").click(function(event) {
                    let card = jQuery(this).parents(".mtg-card");
                    let card_info = get_card_info(card);

                    jQuery.ajax({
                        "method": "post",
                        "data": {
                            "action": "import",
                            "id": card_info['id'],
                            "card": JSON.stringify(search_cards[ card_info['id'] ])
                        }
                    }).done(function (html) {
                        add_card(html);

                        /* Sort the cards by name for the active Card Type */
                        var mtgCards = jQuery('.card-type[data-active=1] .mtg-cards');
                        mtgCards.find('.mtg-card').sort(function(card_a, card_b) {
                            return card_a.dataset.name.toUpperCase().localeCompare(card_b.dataset.name.toUpperCase());
                        })
                        .appendTo(mtgCards);

                        create_deck_cards_events();

                        jQuery('#modalSearchCard').modal("hide"); 
                    }).fail(function (res) {
                        console.log("NOT IMPORTED"); 
                    });
                });
            }
        }).fail(function(res) {
            jQuery("#resultSearchCard").html("Nothing matched");
        });
    });

    /* Focus the text input then the modal is shown */
    jQuery('#modalSearchCard').on('shown.bs.modal', function () {
      jQuery('#inputSearchCard').trigger('focus')
    });

    /* Btn SEARCH clicked in the Cart Type */
    jQuery(".card-type button.btn-search-cards").click(function(event) {
        event.preventDefault();

        // Reset all active Card Type
        jQuery(".card-type").attr("data-active", 0);
        // Set the clicked Card Type as active
        jQuery(this).parents(".card-type").attr("data-active", 1);

        jQuery("#resultSearchCard").html("");
        jQuery("#inputSearchCard").val("");
        jQuery('#modalSearchCard').modal("show");
    });

    /* Btn SAVE clicked in the footer */
    jQuery(".btn-save").click(function(event) {
        event.preventDefault();

        jQuery("#save-results").html("");

        let cards = {};

        jQuery(".card-type").each(function (index_type, element_type) {
            let type = jQuery(element_type).data("type");

            cards[ type ] = [];

            jQuery(element_type).find("div.mtg-card").each(function (index_card, element_card) {
                cards[type].push({
                    "card_id": jQuery(element_card).data("id"),
                    "qty": jQuery(element_card).attr("data-qty")
                });
            });
        });

        // Save the deck
        jQuery.ajax({
            "method": "post",
            "data": {
                "action": "save",
                "cards": cards
            }
        }).done(function (res) {
            jQuery(this).attr("disabled", true);

            stats = JSON.parse(res);

            let saved_results = "";
            if (stats['added'] > 0) {
                saved_results += stats['added'] + " added, ";
            }
            if (stats['updated'] > 0) {
                saved_results += stats['updated'] + " updated, ";
            }
            if (stats['deleted'] > 0) {
                saved_results += stats['deleted'] + " deleted, ";
            }

            if (saved_results == "") {
                saved_results = "Nothing changed, ";
            }

            console.log(saved_results);

            jQuery("#save-results").html(saved_results.substring(0, saved_results.length-2));
        }).fail(function (res) {
            console.log("NOT SAVED"); 
        });
    });
});

function show_modal(type) {
    jQuery('#modalSearchCard').modal();
}

function add_card(card_html) {
    if (jQuery(".card-type[data-active=1] .mtg-cards .card-text").is(":visible")) {
        jQuery(".card-type[data-active=1] .mtg-cards .card-text").hide();
    }
    jQuery(".card-type[data-active=1] .mtg-cards").append(card_html);

    update_cards_total();
}

function update_cards_total() {
    jQuery(".card-type").each(function (type_index, type_element) {
        let total = 0;
        jQuery(type_element).find(".mtg-card").each(function (card_index, card_element) {
            total += parseInt(jQuery(card_element).attr("data-qty"));
        });
        jQuery(type_element).find(".cards-total").html(" x " + total);
    });
}

function create_deck_cards_events() {
    jQuery(".card-type .mtg-cards > .mtg-card img").off("click").click(function(event) {
        event.preventDefault();

        let parent = jQuery(this).parents(".mtg-card");
        let container = jQuery(parent).parents(".mtg-cards");

        if (jQuery(parent).find(".action").length > 0) {
            jQuery(parent).find(".action").remove();
        } else {
            let action = document.createElement("div");
            action.className = "action";

            let button = document.createElement("button");
            button.className = "btn btn-danger";
            button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16"><path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/></svg>';

            let form = document.createElement("form");

            let input = document.createElement("input");
            input.className = "form-control";
            input.value = jQuery(parent).attr("data-qty");

            form.appendChild(input);

            action.appendChild(form);

            action.appendChild(button);

            parent.append(action);

            jQuery(form).submit(function(event) {
                event.preventDefault();
                let qty = jQuery(this).find("input").val();

                if (isNaN(qty) || qty === "") {
                    jQuery(this).find("input").val( jQuery(this).parents(".mtg-card").data("qty") );
                } else {
                    jQuery(this).parents(".mtg-card").attr("data-qty", qty);

                    update_cards_total();
                }
            });

            //parent.append('<div class="action"><button class="btn btn-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16"><path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/></svg></button></div>');
            jQuery(button).click(function(event) {
                event.preventDefault();

                jQuery(parent).remove();
                update_cards_total();

                /* Show the default text if no card are present */
                if (jQuery(container).find(".mtg-card").length === 0) {
                    jQuery(container).find(".card-text").show();
                }
            });
        }
    });
}

function get_card_info(element) {
    if (element instanceof jQuery) {
        element = element[0];
    }

    let card_info = {
        name: element.dataset.name,
        mana_cost: element.dataset.manaCost,
        image_id: element.dataset.imageId,
        qty: element.dataset.qty,
        id: element.dataset.id,
    }

    return card_info;
}

function generate_card(card_info) {

    let card = document.createElement("div");
    card.className = "mtg-card";
    card.dataset.name = card_info['name'];
    card.dataset.manaCost = card_info['mana_cost'];
    card.dataset.imageId = card_info['image_id'];
    card.dataset.card = card_info['card'];
    card.dataset.id = card_info['id'];

    if (card_info['qty'] !== undefined && !isNaN(card_info['qty'])) {
        card.dataset.qty = card_info['qty'];
    } else {
        card.dataset.qty = 1;
    }

    let img = document.createElement("img");
    img.className = "responsive mtg-card-thumbnail";
    if (card_info['image_url']) {
        img.src = card_info['image_url'];
    } else {
        img.src = "https://mtg.birkoss.com/image/" + card_info['image_id'] + ".jpg?v=4";
    }

    card.appendChild(img);

    return card;
}