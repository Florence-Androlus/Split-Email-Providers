jQuery(document).ready(function($) {

    // Ouvrir la fenêtre modale pour l'ajout
    $('#openModalBtn').on('click', function() {

        // Réinitialiser le formulaire pour l'ajout
        $('#modalTitle').text('Ajouter un Fournisseur'); // Titre pour l'ajout
        $('#fournisseurId').val(''); // Réinitialiser l'ID
        $('#actionField').val('add'); // Définir l'action sur "add"
        // Activer les champs pour la modification
        $('#nom, #adresse, #cp, #ville, #pays, #email, #telephone').removeAttr('readonly').removeAttr('disabled');

        
        // Réinitialiser les autres champs
        $('#nom').val('');
        $('#adresse').val('');
        $('#cp').val('');
        $('#ville').val('');
        $('#email').val('');
        $('#telephone').val('');

        // Sélectionner la France par défaut
        $('#pays').val('FR').trigger('change');

        $('#submitButton').text('Ajouter'); // Texte du bouton pour l'ajout
        $('#myModal').css('display', 'block');

    });

    // Ouvrir la fenêtre modale pour voir la fiche fournisseur
    $('.openFicheBtn').on('click', function() {

        $('#modalTitle').text('Fiche du Fournisseur'); // Titre 
        $('#fournisseurId').val($(this).data('id')); // Récupérer l'ID du fournisseur

        // Remplir les autres champs avec les données
        $('#nom').val($(this).data('nom')).prop('disabled', true);
        $('#adresse').val($(this).data('adresse')).prop('disabled', true);
        $('#cp').val($(this).data('cp')).prop('disabled', true);
        $('#ville').val($(this).data('ville')).prop('disabled', true);
        $('#pays').val($(this).data('pays')).trigger('change').prop('disabled', true).css({'color': '#000', 'background-color': '#f0f0f0', 'opacity': '1'});
        $('#email').val($(this).data('email')).prop('disabled', true);
        $('#telephone').val($(this).data('telephone')).prop('disabled', true);
        $('#submitButton').hide();
        $('#myModal').css('display', 'block');

    });

    // Ouvrir la fenêtre modale pour la modification
    $('.btn-warning').on('click', function() {
        $('#modalTitle').text('Modifier un Fournisseur'); // Titre pour la modification
        $('#fournisseurId').val($(this).data('id')); // Récupérer l'ID du fournisseur
        $('#actionField').val('update'); // Définir l'action sur "update"
        // Activer les champs pour la modification
        $('#nom, #adresse, #cp, #ville, #pays, #email, #telephone').removeAttr('readonly').removeAttr('disabled');

        // Remplir les autres champs avec les données
        $('#nom').val($(this).data('nom'));
        $('#adresse').val($(this).data('adresse'));
        $('#cp').val($(this).data('cp'));
        $('#ville').val($(this).data('ville'));
        $('#pays').val($(this).data('pays')).trigger('change');
        $('#email').val($(this).data('email'));
        $('#telephone').val($(this).data('telephone'));

        $('#submitButton').text('Modifier').show(); ; // Afficher et mettre le texte du bouton pour la modification
        $('#myModal').css('display', 'block');

    });

    // Ouvrir la fenêtre modale pour l'import fournisseur
    $('#importModalBtn').on('click', function() {
        console.log('importModalBtn');
        $('#importModal').css('display', 'block');
    });

    // Fermer la fenêtre modale lorsque l'utilisateur en cliquant sur la croix
    $('.close').on('click', function() {
        $('#myModal').css('display', 'none');
    });

    // Soumettre le formulaire
    $('#submitButton').on('click', function() {

        // Récupérer les données du formulaire avant l'envoi AJAX
        var fournisseurData = {
            id: $('#fournisseurId').val(),
            action: $('#actionField').val(),
            nom: $('#nom').val().replace(/[^a-zA-Z0-9\s]/g, ''), // Supprimer les caractères spéciaux du nom
            adresse: $('#adresse').val().replace(/[^a-zA-Z0-9\s]/g, ''), // Supprimer les caractères spéciaux de l'adresse
            cp: $('#cp').val().replace(/[^0-9]/g, ''), // Supprimer tout sauf les chiffres du code postal
            ville: $('#ville').val().replace(/[^a-zA-Z\s]/g, ''), // Supprimer les caractères spéciaux de la ville
            email: $('#email').val(), // Vous pouvez ajouter une validation pour l'email
            telephone: $('#telephone').val().replace(/[^0-9]/g, ''), // Supprimer tout sauf les chiffres du téléphone
            // Récupérer le nonce à partir du champ caché généré par wp_nonce_field
            fournisseur_nonce: $('#fournisseur_nonce').val()
        };

        // Requête AJAX
        $.ajax({
            url: '<?= home_url("fournisseurs") ?>',
            type: 'POST',
            data: fournisseurData, // Utilisation des données correctement définies
            success: function(response) {
                // Gestion du succès (fermer la modal, afficher un message, etc.)
                $('#myModal').css('display', 'none');
            },
        });

    });


    // Initialiser les tooltips pour les éléments présents et futurs
    $(document).on('mouseenter', '[title]', function() {
        $(this).tooltip();
    });
    
    // Suppression du message général au bout de 4 secondes
    setTimeout(function() {
        $('#auto-dismiss').fadeOut('slow');
    }, 4000); // 4000 millisecondes = 4 secondes

});

