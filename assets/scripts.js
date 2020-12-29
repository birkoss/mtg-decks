jQuery(document).ready(function() {

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

        jQuery('img').popover({
            html: true,
            content: show_popover,
            placement: "bottom"
        });
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

                    jQuery('#modalSearchCard').modal("hide"); 

                    jQuery('img').popover({
                        html: true,
                        content: show_popover,
                        placement: "bottom"
                    });
                });
            }
        });
    });

    /* Focus the text input then the modal is shown */
    jQuery('#modalSearchCard').on('shown.bs.modal', function () {
      jQuery('#inputSearchCard').trigger('focus')
    });

    jQuery(".card-type button.btn-search-cards").click(function(event) {
        event.preventDefault();

        // Reset all active Card Type
        jQuery(".card-type").attr("data-active", 0);
        // Set the clicked Card Type as active
        jQuery(this).parents(".card-type").attr("data-active", 1);

        jQuery('#modalSearchCard').modal("show");
    });

    jQuery('img').on('shown.bs.popover', function () {
        jQuery('img').not(this).popover('hide');

      let image = this;
      let popover_id = jQuery(this).attr("aria-describedby");

        jQuery("#" + popover_id).click(function(event) {
            jQuery(image).popover('hide');
            jQuery(image).remove();
        });
    });

    console.log("init");

    jQuery(".btn-save").click(function(event) {
        event.preventDefault();

        let deck = {
            "type": "commander",
            "cards": {}
        };

        jQuery(".card-type").each(function (index_type, element_type) {
            let type = jQuery(element_type).data("type");

            deck['cards'][ type ] = [];

            jQuery(element_type).find("img.mtg-card-thumbnail").each(function (index_card, element_card) {
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

function show_popover() {
    return "<a href='#' class='btn btn-danger'>Delete</a>";
}

function show_modal(type) {
    jQuery('#modalSearchCard').modal();
}

function add_card(card) {
    if (jQuery(".card-type[data-active=1] .card-text img.mtg-card-thumbnail").length === 0) {
        jQuery(".card-type[data-active=1] .card-text").html('');
    }
    jQuery(".card-type[data-active=1] .card-text").append('<img class="responsive mtg-card-thumbnail" data-card="' + window.btoa(JSON.stringify(card)) + '" src="' + card['image_url'] + '" />');
}

function generate_card(card) {

    cost = '';

    const regexp = /{([^}]+)}/g;
    const costs = [...card['mana_cost'].matchAll(regexp)];
    costs.forEach(function(c) {
        cost += '<abbr class="card-symbol card-symbol-' + c[1].replace("/", "") + '">' + c[0] + '</abbr>'
    });

    html = '<div class="mtg-card card border-primary mb-3" data-card="' + window.btoa(JSON.stringify(card)) + '">';
    html += '<div class="card-header"><span>' + card['name'] + '</span><span>' + cost + '</span></div>';
  html += '<div class="card-body">';
    // html += '<p class="card-text">' + card['text'] + '</p>';
    html += '<p class="card-text"><img class="responsive mtg-card-thumbnail" src="' + card['image_url'] + '" /></p>';
  html += '</div>';
html += '</div>';

    return html;
}