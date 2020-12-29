jQuery(document).ready(function() {

    /* Add existing deck cards in containers */
    if (deck_data !== undefined) {
        jQuery(".card-type").each(function (index_type, element_type) {

            jQuery(".card-type").attr("data-active", 0);
            if (deck_data['cards'][ jQuery(element_type).data("type") ] !== undefined && deck_data['cards'][ jQuery(element_type).data("type") ].length > 0) {
                jQuery(element_type).attr("data-active", 1);

                deck_data['cards'][ jQuery(element_type).data("type") ].forEach(function (card) {
                    add_card(card);
                });
            }
        });

        jQuery(".card-type").attr("data-active", 0);

        /* Enable events */
        create_deck_cards_events();
    }

    /* Search the API when the modal form is submitted */
    jQuery('#formSearchCard').submit(function(event) {
        event.preventDefault();

        jQuery("#resultSearchCard").html("Searching...");

        jQuery.ajax(
            'https://api.scryfall.com/cards/search?q=lang:fr ' + jQuery('#inputSearchCard').val()
        ).done(function (res) {
            if (res.data) {
                jQuery("#resultSearchCard").html("");
                res.data.forEach(function(item) {
                    card = {
                        name: item['printed_name'],
                        mana_cost: item['mana_cost'],
                        /* text: item['printed_text'], */
                        image_url: "https://c1.scryfall.com/file/scryfall-cards/border_crop/front/" + item['id'].substring(0, 1) + "/" + item['id'].substring(1, 2) + "/" + item['id'] + ".jpg?1562404626"
                    }
                    jQuery("#resultSearchCard").append( generate_card(card) );
                });

                jQuery(".modal-body img.mtg-card-thumbnail").click(function(event) {
                    add_card(JSON.parse(window.atob(jQuery(this).parents(".mtg-card").data("card"))));

                    create_deck_cards_events();

                    jQuery('#modalSearchCard').modal("hide"); 
                });
            }
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

        jQuery('#modalSearchCard').modal("show");
    });

    /* Btn SAVE clicked in the footer */
    jQuery(".btn-save").click(function(event) {
        event.preventDefault();

        let deck = {
            "type": "commander",
            "cards": {}
        };

        jQuery(".card-type").each(function (index_type, element_type) {
            let type = jQuery(element_type).data("type");

            deck['cards'][ type ] = [];

            jQuery(element_type).find("div.mtg-card").each(function (index_card, element_card) {
                console.log(element_card);
                deck['cards'][type].push( JSON.parse(window.atob(jQuery(element_card).data("card"))) );
            });
        });

        // Save the deck
        jQuery.ajax({
            "method": "post",
            "data": {
                "deck": deck
            },
        }).done(function (res) {
                if (res.data) {
                    jQuery("#resultSearchCard").html("");
                    res.data.forEach(function(item) {
                        card = {
                            name: item['printed_name'],
                            mana_cost: item['mana_cost'],
                            /* text: item['printed_text'], */
                            image_url: "https://c1.scryfall.com/file/scryfall-cards/border_crop/front/" + item['id'].substring(0, 1) + "/" + item['id'].substring(1, 2) + "/" + item['id'] + ".jpg?1562404626"
                        }
                        jQuery("#resultSearchCard").append( generate_card(card) );
                    });

                    jQuery("img.mtg-card-thumbnail").click(function(event) {
                        add_card(JSON.parse(window.atob(jQuery(this).parents(".mtg-card").data("card"))));

                        jQuery('img').popover("dispose");
                        jQuery('img').popover({
                            html: true,
                            content: show_popover,
                            placement: "bottom"
                        });

                        jQuery('#modalSearchCard').modal("hide"); 
                    });
                }
            });

    });

});

function show_modal(type) {
    jQuery('#modalSearchCard').modal();
}

function add_card(card) {
    if (jQuery(".card-type[data-active=1] .mtg-cards .card-text").is(":visible")) {
        jQuery(".card-type[data-active=1] .mtg-cards .card-text").hide();
    }
    jQuery(".card-type[data-active=1] .mtg-cards").append(generate_card(card));
}

function create_deck_cards_events() {
    jQuery(".card-type .mtg-cards > .mtg-card img").off("click").click(function(event) {
        event.preventDefault();

        let parent = jQuery(this).parents(".mtg-card");

        if (jQuery(parent).find(".action").length > 0) {
            jQuery(parent).find(".action").remove();
        } else {
            parent.append('<div class="action"><button class="btn btn-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16"><path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/></svg></button></div>');
            jQuery(parent).find(".action > .btn-danger").click(function(event) {
                event.preventDefault();

                jQuery(parent).remove();
            });
        }
    });
}

function generate_card(card) {

    html = '<div class="mtg-card" data-card="' + window.btoa(JSON.stringify(card)) + '">';
    html += '<img class="responsive mtg-card-thumbnail" src="' + card['image_url'] + '" />';
    html += '</div>';

    return html;
}