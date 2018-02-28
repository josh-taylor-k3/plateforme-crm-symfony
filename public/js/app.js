

(function () {

    var btn_save_droits_api = document.getElementById('btn_save_droits_api');


    btn_save_droits_api.addEventListener('click', function (ev) {




        var clients = document.getElementById('checkbox_clients');
        var tickets = document.getElementById('checkbox_tickets');
        var fourn = document.getElementById('checkbox_fourn');
        var produits = document.getElementById('checkbox_produits');
        var url = "http://api.achatcentrale.fr/user/setDroits";


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