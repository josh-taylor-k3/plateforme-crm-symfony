

(function () {




    $('#app_profil').on('change', function (e) {

        let choix = $(this).val();


        if ( choix === "CENTRALE" )
        {
            $('#selection_centrale').show();
        }else if(choix == "FOURNISSEUR"){

            $('#selection_fournisseur').show();
        }

    });




    let btn_save_droits_api = document.getElementById('btn_save_droits_api');


    btn_save_droits_api.addEventListener('click', function (ev) {


        let clients = $('#checkbox_clients').is(':checked') ? 1 : 0;
        let tickets = $('#checkbox_tickets').is(':checked') ? 1 : 0;
        let fourn = $('#checkbox_fourn').is(':checked') ? 1 : 0;
        let produits = $('#checkbox_produits').is(':checked') ? 1 : 0;
        let url = "http://api.achatcentrale.fr/user/setDroits";
        let id = $('#id_user_actual').html();


        console.log(clients);
        console.log(tickets);
        console.log(fourn);
        console.log(produits);
        console.log(id);


        $.ajax({

            type: "POST",
            url: url,
            headers: {
                "X-ac-key":"hdmSTymnVdBm2r7xGL64Ie7hB6PQ1Hnd3jAAXF36"
            },
            data: {
                clients: clients,
                tickets: tickets,
                fourn: fourn,
                produits: produits,
                id: id
            },

            // La fonction à apeller si la requête aboutie
            success: function (data) {
                console.log(data);
                window.location.reload()
            },

            // La fonction à appeler si la requête n'a pas abouti
            error: function (data) {
                console.log(data);
            }

        });





    })






})();