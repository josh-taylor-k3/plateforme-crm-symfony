

(function () {

    var btn_save_droits_api = document.getElementById('btn_save_droits_api');


    btn_save_droits_api.addEventListener('click', function (ev) {

        var myHeaders = new Headers();

        var myInit = { method: 'GET',
            headers: myHeaders,
            mode: 'cors',
            cache: 'default' };

        fetch('http://api.achatcentrale.fr/produit/8626',myInit)
            .then(function(response) {
                return response.blob();
            })
            .then(function(myBlob) {

                console.log(myBlob)

            });


    })

})();