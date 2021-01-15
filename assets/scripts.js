var search_cards = {};
var current_card = null;

jQuery(document).ready(function() {
    /* Enable events */
    if (jQuery("body.action-edit").length !== 0) {
        create_deck_cards_events();
    }

    update_cards_total();

    /* Enable the Chart and canvas when needed */
    if (jQuery("#canvas").length) {
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: barChartData,
            options: {
                tooltips: {
                    mode: 'index',
                    intersect: false
                },
                responsive: true,
                scales: {
                    xAxes: [{
                        stacked: true,
                    }],
                    yAxes: [{
                        stacked: true
                    }]
                }
            }
        });
    }

    jQuery("a.card-category").click(function(event) {
        event.preventDefault();

        /* Do nothing when clicked on the ACTIVE category */
        if (jQuery(this).hasClass("active")) {
            return;
        }

        jQuery("a.card-category").removeClass("active");
        jQuery(this).addClass("active");

        if (current_card == null) {
            return;
        }

        jQuery(current_card).attr("data-category", jQuery(this).attr("data-category"));

        if (sort === "category") {
            jQuery(current_card).detach().appendTo(jQuery(".tab-pane.active .card-section[data-section-id='" + jQuery(this).attr("data-category") + "'] .mtg-cards"))
            sort_section(".tab-pane.active .card-section[data-section-id='" + jQuery(this).attr("data-category") + "'] .mtg-cards");

            update_cards_total();
        }

        jQuery('#modalCategory').modal("hide");
    });

    /* Search the API when the modal form is submitted */
    jQuery('#formSearchCard').submit(function(event) {
        event.preventDefault();

        jQuery("#resultSearchCard").html("Searching...");

        let lang = jQuery(".modal-lang label.active input").val();

        let query = 'lang:' + lang + ' ' + jQuery('#inputSearchCard').val();

        /* Force the colors from the commander */
        if (commander_colors.length > 0) {
            query = "commander:" + commander_colors.join("") + " " + query;
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
                        name: (item['lang'] === "fr" ? item['printed_name'] : item['name']),
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
                    }).done(function (res) {
                        data = JSON.parse(res);

                        /* Add the card in the correct section */
                        add_card(data['card'][sort], data['html']);

                        /* Set it as a wishlist if it's the current tab */
                        if (jQuery(".tab-pane.active").attr("id") === "wishlist") {
                            jQuery(".tab-pane.active .mtg-card[data-id='" + data['card']['id'] + "']").attr("data-wishlist", 1);
                        }

                        /* Sort the cards by name for the active Card Type */
                        sort_section(".tab-pane.active .card-section[data-section-id='" + data['card'][sort] + "'] .mtg-cards");

                        /* Enable cards events */
                        create_deck_cards_events();

                        /* Hide the form */
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

    /* Select the current card category */
    jQuery('#modalCategory').on('shown.bs.modal', function () {
        jQuery("a.card-category").removeClass("active");

        if (current_card !== null) {
            jQuery("a.card-category[data-category='" + jQuery(current_card).attr("data-category") + "'").addClass("active");
        }

        update_cards_categories_total();
    });

    /* Focus the text input then the modal is shown */
    jQuery('#modalSearchCard').on('shown.bs.modal', function () {
        jQuery('#inputSearchCard').trigger('focus')
    });

    /* Btn SEARCH clicked in the Cart Type */
    jQuery("button.btn-search-cards").click(function(event) {
        event.preventDefault();

        jQuery("#resultSearchCard").html("");
        jQuery("#inputSearchCard").val("");
        jQuery('#modalSearchCard').modal("show");
    });

    /* Btn SAVE clicked in the footer */
    jQuery(".btn-save").click(function(event) {
        event.preventDefault();

        jQuery("#save-results").html("");

        let cards = [];

        jQuery(".card-section").each(function (index_section, element_section) {
            jQuery(element_section).find("div.mtg-card").each(function (index_card, element_card) {
                cards.push({
                    "card_id": jQuery(element_card).data("id"),
                    "qty": jQuery(element_card).attr("data-qty"),
                    "category": jQuery(element_card).attr("data-category"),
                    "is_starred": jQuery(element_card).attr("data-starred"),
                    "is_wishlist": jQuery(element_card).attr("data-wishlist")
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


function sort_section(selector) {
    var mtgCards = jQuery(selector);
    mtgCards.find('.mtg-card').sort(function(card_a, card_b) {
        return card_a.dataset.name.toUpperCase().localeCompare(card_b.dataset.name.toUpperCase());
    })
    .appendTo(mtgCards);
}


function update_cards_categories_total() {
    jQuery("a.card-category").each(function(index, category) {
        let total = 0;
        jQuery(".mtg-card[data-category='" + jQuery(category).attr("data-category") + "']").each(function (card_index, card_element) {
            total += parseInt(jQuery(card_element).attr("data-qty"));
        });
        jQuery(this).find(".badge-pill").html(total);
    });
}

function show_modal(type) {
    jQuery('#modalSearchCard').modal();
}

function add_card(section_id, card_html) {
    jQuery(".tab-pane.active .card-section[data-section-id='" + section_id + "'] .mtg-cards").append(card_html);
    jQuery(".tab-pane.active .card-section[data-section-id='" + section_id + "']").show();

    update_cards_total();
}

function update_cards_total() {
    jQuery(".card-section").each(function (type_index, type_element) {
        let total = 0;
        jQuery(type_element).find(".mtg-card").each(function (card_index, card_element) {
            total += parseInt(jQuery(card_element).attr("data-qty"));
        });
        jQuery(type_element).find(".cards-total").html(" x " + total);

        if (total === 0) {
            jQuery(type_element).hide();
        } else {
            jQuery(type_element).show();
        }

        /* Apply the Card Type limit (if available) */
        if (jQuery(type_element).data("limit") > 0) {
            if (total >= jQuery(type_element).data("limit")) {
                jQuery(type_element).find(".btn-search-cards").hide();
            } else {
                jQuery(type_element).find(".btn-search-cards").show();
            }
        }
    });
}

function create_deck_cards_events() {
    jQuery(".card-section .mtg-cards > .mtg-card").hover(function(event) {
        event.preventDefault();

        let parent = jQuery(this);
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

            let buttonStar = document.createElement("button");
            buttonStar.className = "btn btn-" + (jQuery(parent).attr("data-starred") == 1 ? "info" : "secondary");
            let star = document.createElement("i");
            star.className = "fas fa-star";

            buttonStar.appendChild(star);
            action.appendChild(buttonStar);

            let buttonCategory = document.createElement("button");
            buttonCategory.className = "btn btn-" + (jQuery(parent).attr("data-category") != "" ? "info" : "secondary");
            let listIcon = document.createElement("i");
            listIcon.className = "fas fa-list";

            buttonCategory.appendChild(listIcon);
            action.appendChild(buttonCategory);

            let buttonWishlist = document.createElement("button");
            buttonWishlist.className = "btn btn-" + (jQuery(parent).attr("data-wishlist") == 1 ? "info" : "secondary");
            let cartIcon = document.createElement("i");
            cartIcon.className = "fas fa-shopping-cart";

            buttonWishlist.appendChild(cartIcon);
            action.appendChild(buttonWishlist);

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

            /* Button DELETE */
            jQuery(button).click(function(event) {
                event.preventDefault();

                jQuery(parent).remove();
                update_cards_total();

                /* Show the default text if no card are present */
                if (jQuery(container).find(".mtg-card").length === 0) {
                    jQuery(container).find(".card-text").show();
                }
            });

            /* Button STAR */
            jQuery(buttonStar).click(function(event) {
                event.preventDefault();

                let is_starred = jQuery(this).parents(".mtg-card").attr("data-starred");

                jQuery(this).removeClass("btn-info").removeClass("btn-secondary");

                if (is_starred == 1) {
                    jQuery(this).addClass("btn-secondary");
                } else {
                    jQuery(this).addClass("btn-info");
                }

                jQuery(this).parents(".mtg-card").attr("data-starred", (is_starred == 1 ? 0 : 1));
            });

            /* Button WISHLIST */
            jQuery(buttonWishlist).click(function(event) {
                event.preventDefault();

                let is_wishlist = jQuery(this).parents(".mtg-card").attr("data-wishlist");

                jQuery(this).removeClass("btn-info").removeClass("btn-secondary");

                if (is_wishlist == 1) {
                    jQuery(this).addClass("btn-secondary");
                } else {
                    jQuery(this).addClass("btn-info");
                }

                jQuery(this).parents(".mtg-card").attr("data-wishlist", (is_wishlist == 1 ? 0 : 1));
            });

            /* Button CATEGORY */
            jQuery(buttonCategory).click(function(event) {
                event.preventDefault();

                current_card = jQuery(this).parents(".mtg-card");

                jQuery("a.card-category").removeClass("active");
                jQuery('#modalCategory').modal("show");
            });

        }
    }, function() {
        jQuery(this).find(".action").remove();
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