<?php
include "blocks/_header_page_jeu.php";
ob_start();
if ($quantite <= 0) {
    echo("La somme que vous voulez mettre au sol n'est pas valide !)");
} else {
    $req_depose = "select depose_or($perso_cod,$quantite) as depose";
    $db->query($req_depose);
    $db->next_record();
    if ($db->f("depose") == 0) {
        echo("<p>Vous avez déposé avec succès $quantite brouzoufs au sol.");
    } else {
        printf("<p>Une erreur est survenue : %s", $db->f("depose"));
    }
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
