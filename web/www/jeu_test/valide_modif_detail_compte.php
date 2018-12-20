<?php
include "blocks/_header_page_jeu.php";
ob_start();
if ($db->is_admin($compt_cod)) {
    switch ($methode) {
        case "comment":
            $req = "select compt_nom from compte where compt_cod = $compt_cod ";
            $db->query($req);
            $db->next_record();
            $nom = $db->f("compt_nom");

            $req = "select to_char(now(),'DD/MM/YYYY hh24:mi:ss') as maint ";
            $db->query($req);
            $db->next_record();
            $maint = $db->f("maint");


            $comment = nl2br($comment);
            $req = "update compte set compt_commentaire = '<br><strong>$maint par $nom </strong><br>$comment'||coalesce(compt_commentaire,' ') ";
            $req = $req . "where compt_cod = $compte ";
            if ($db->query($req)) {
                echo "<p>Requête effectuée !";
            } else {
                echo "<p>Erreur sur la requête !";
            }
            break;


    }


} else {
    echo "<p>Erreur ! Vous n'êtes pas administrateur !";
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
