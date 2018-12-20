<?php
include "blocks/_header_page_jeu.php";

//
//Contenu de la div de droite
//
$contenu_page = '';
ob_start();
$erreur = 0;
$req = "select perso_admin_echoppe from perso where perso_cod = $perso_cod ";
$db->query($req);
$db->next_record();
if ($db->f("perso_admin_echoppe") != 'O') {
    echo "<p>Erreur ! Vous n'avez pas accès à cette page !";
    $erreur = 1;
}
if ($erreur == 0) {
    // Conversion en numeric pour minimiser l'injection sql
    $perso_cible = 1 * $perso_cible;
    $lieu = 1 * $lieu;

    // pour vérif, on récupère les coordonnées du magasin
    $req = "select pos_x,pos_y,etage_libelle ";
    $req = $req . "from lieu_position,positions,etage ";
    $req = $req . "where lpos_lieu_cod = $lieu ";
    $req = $req . "and lpos_pos_cod = pos_cod ";
    $req = $req . "and pos_etage = etage_numero ";
    $db->query($req);
    $db->next_record();
    echo "<p class=\"titre\">Gestion de l'échoppe " . $db->f("pos_x") . ", " . $db->f("pos_y") . ", " . $db->f("etage_libelle") . "</p>";
    switch ($methode) {
        case "ajout":
            $req = "insert into magasin_gerant (mger_perso_cod,mger_lieu_cod) values ($perso_cible,$lieu) ";
            if ($db->query($req)) {
                echo "<p>Modif effectuée !";
            } else {
                echo "<p>Anomalie sur la requête !";
            }
            break;
        case "modif":
            $req = "update magasin_gerant set mger_perso_cod = $perso_cible where mger_lieu_cod = $lieu ";
            if ($db->query($req)) {
                echo "<p>Modif effectuée !";
            } else {
                echo "<p>Anomalie sur la requête !";
            }
            break;
        case "supprime":
            $req = "delete from  magasin_gerant where mger_lieu_cod = $lieu ";
            if ($db->query($req)) {
                echo "<p>Modif effectuée !";
            } else {
                echo "<p>Anomalie sur la requête !";
            }
            break;
    }


}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
