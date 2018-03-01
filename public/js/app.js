

(function () {

    var btn_save_droits_api = document.getElementById('btn_save_droits_api');


    btn_save_droits_api.addEventListener('click', function (ev) {




        var clients = $('#checkbox_clients').is(':checked') ? 1 : 0;
        var tickets = $('#checkbox_tickets').is(':checked') ? 1 : 0;
        var fourn = $('#checkbox_fourn').is(':checked') ? 1 : 0;
        var produits = $('#checkbox_produits').is(':checked') ? 1 : 0;
        var url = "http://api.achatcentrale.fr/user/setDroits";


        console.log(clients);
        console.log(tickets);
        console.log(fourn);
        console.log(produits);

        $.ajax({
            type: "POST",
            url: url,
            contentType: "json",
            cache: false,
            processData:false,
            headers: {
                "X-ac-key":"hdmSTymnVdBm2r7xGL64Ie7hB6PQ1Hnd3jAAXF36"
            },
            data : {
                "clients" : clients,
                "tickets" : tickets,
                "fourn" : fourn,
                "produits" : produits
            }
        }).done(function(data) {
            console.log(data);
        });


    })



})();