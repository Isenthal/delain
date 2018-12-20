<?php
include "blocks/_header_page_jeu.php";
ob_start();
$erreur = 0;
$req = "select dcompt_modif_perso, dcompt_modif_gmon, dcompt_controle, dcompt_creer_monstre from compt_droit where dcompt_compt_cod = $compt_cod ";
$db->query($req);
if ($db->nf() == 0)
{
    $droit['modif_perso'] = 'N';
    $droit['modif_gmon'] = 'N';
    $droit['controle'] = 'N';
    $droit['creer_monstre'] = 'N';
} else
{
    $db->next_record();
    $droit['modif_perso'] = $db->f("dcompt_modif_perso");
    $droit['modif_gmon'] = $db->f("dcompt_modif_gmon");
    $droit['controle'] = $db->f("dcompt_controle");
    $droit['creer_monstre'] = $db->f("dcompt_creer_monstre");
}
if ($droit['modif_gmon'] != 'O')
{
    echo "<p>Erreur ! Vous n'avez pas accès à cette page !";
    $erreur = 1;
}
if ($erreur == 0)
{
    include "admin_edition_header.php";

    // TRAITEMENT DE FORMULAIRE
    if (isset($_POST['methode']))
    {
        switch ($methode)
        {
            case "creer_serie":
                $req = "insert into serie_equipement (seequ_nom,seequ_proba_sans_objet ) values ('$seequ_nom',$seequ_proba_sans_objet)";
                $db->query($req);
                echo "<p>CREATION</p>";
                break;
            case "modifier_serie":
                $req = "update serie_equipement set seequ_nom = '$seequ_nom'"
                    . ",seequ_proba_sans_objet = $seequ_proba_sans_objet"
                    . " where seequ_cod = $seequ_cod";
                $db->query($req);
                echo "<p>MODIFICATION</p>";
                break;
            case "ajouter_serie_element":
                if ($seequo_gobj_cod != 'null')
                {
                    $req = "insert into serie_equipement_objet (seequo_seequ_cod,seequo_gobj_cod,seequo_proba,seequo_etat_min,seequo_etat_max ) values ($seequo_seequ_cod,$seequo_gobj_cod,$seequo_proba,$seequo_etat_min,$seequo_etat_max)";
                    $db->query($req);
                    echo "<p>AJOUT</p>";
                }
                break;
            case "modifier_serie_element":
                if ($seequo_gobj_cod != 'null')
                {
                    $req = "update serie_equipement_objet set seequo_gobj_cod = $seequo_gobj_cod,seequo_proba = $seequo_proba,seequo_etat_min = $seequo_etat_min,seequo_etat_max = $seequo_etat_max where seequo_cod = $seequo_cod";
                    $db->query($req);
                    echo "<p>MAJ</p>";
                }
                break;
            case "supprimer_serie_element":
                $req = "delete from serie_equipement_objet where seequo_cod = $seequo_cod";
                $db->query($req);
                echo "<p>DELETE</p>";
                break;

        }
    }


    $req = "select seequ_cod,seequ_nom,seequ_proba_sans_objet from serie_equipement order by seequ_cod";
    $db->query($req);
    ?>
    <HR>
    CREER UNE NOUVELLE SERIE:<BR>
    <form action="admin_serie_equipements.php" method="post">
        <input type="hidden" name="methode" value="creer_serie">
        SERIE <input type="text" name="seequ_nom" value="">
        Chances sans objet:<input type="text" name="seequ_proba_sans_objet" value="">
        <input type="submit" value="Créer">
    </form>
    <?php
    while ($db->next_record())
    {
        ?>
        <HR>
        <form action="admin_serie_equipements.php" method="post">
            <input type="hidden" name="methode" value="modifier_serie">
            <input type="hidden" name="seequ_cod" value="<?php echo $db->f("seequ_cod") ?>">
            SERIE <input type="text" name="seequ_nom" value="<?php echo $db->f("seequ_nom") ?>">
            Chances sans objet:<input type="text" name="seequ_proba_sans_objet"
                                      value="<?php echo $db->f("seequ_proba_sans_objet") ?>">
            <input type="submit" value="Modifier">
        </form>
        <table width="80%" align="center" border="1">
            <tr>
                <td>Arme</td>
                <td>Chance</td>
                <td>Etat mini</td>
                <td>Etat maxi</td>
            </tr>
            <form action="admin_serie_equipements.php" method="post">
                <input type="hidden" name="methode" value="ajouter_serie_element">
                <input type="hidden" name="seequo_seequ_cod" value="<?php echo $db->f("seequ_cod") ?>">
                <tr>
                    <td>
                        <SELECT name="seequo_gobj_cod">
                            <option value="null">aucune</option>
                            <?php // LISTE DES ARMES ET ARMURES
                            $req_armes = "select 	gobj_cod,gobj_nom from objet_generique where gobj_tobj_cod IN (1,2) order by gobj_nom";
                            $db_armes = new base_delain;
                            $db_armes->query($req_armes);
                            while ($db_armes->next_record())
                            {
                                $arme_cod = $db_armes->f("gobj_cod");
                                echo "<OPTION value=\"$arme_cod\">" . $db_armes->f("gobj_nom") . "</OPTION>\n";
                            }
                            ?>
                        </SELECT>
                    </td>
                    <td>
                        <input type="text" name="seequo_proba" value="">
                    </td>
                    <td><input type="text" name="seequo_etat_min" value="100"></td>
                    <td><input type="text" name="seequo_etat_max" value="100"></td>
                    <td><input type="submit" value="Ajouter"></td>
                </tr>
            </form>
            <?php
            $seequ_cod = $db->f("seequ_cod");
            $db2 = new base_delain;
            $req = "select seequo_cod,seequo_gobj_cod,seequo_proba,seequo_etat_min,seequo_etat_max from  	serie_equipement_objet where seequo_seequ_cod = $seequ_cod ";
            $db2->query($req);
            while ($db2->next_record())
            {
                ?>
                <form action="admin_serie_equipements.php" method="post">
                    <input type="hidden" name="methode" value="modifier_serie_element">
                    <input type="hidden" name="seequo_cod" value="<?php echo $db2->f("seequo_cod") ?>">
                    <tr>
                        <td>
                            <SELECT name="seequo_gobj_cod">
                                <option value="null">aucune</option>
                                <?php // LISTE DES ARMES ET ARMURES
                                $req_armes = "select 	gobj_cod,gobj_nom from objet_generique where gobj_tobj_cod IN (1,2) order by gobj_nom";
                                $db_armes = new base_delain;
                                $db_armes->query($req_armes);
                                while ($db_armes->next_record())
                                {
                                    $arme_cod = $db_armes->f("gobj_cod");
                                    $sel = "";
                                    if ($arme_cod == $db2->f("seequo_gobj_cod"))
                                    {
                                        $sel = "selected";
                                    }
                                    echo "<OPTION value=\"$arme_cod\" $sel>" . $db_armes->f("gobj_nom") . "</OPTION>\n";
                                }
                                ?>
                            </SELECT></td>
                        <td><input type="text" name="seequo_proba" value="<?php echo $db2->f("seequo_proba"); ?>"></td>
                        <td><input type="text" name="seequo_etat_min" value="<?php echo $db2->f("seequo_etat_min"); ?>">
                        </td>
                        <td><input type="text" name="seequo_etat_max" value="<?php echo $db2->f("seequo_etat_max"); ?>">
                        </td>
                        <td><input type="submit" value="Modifier"></td>
                </form>
                <form action="admin_serie_equipements.php" method="post">
                    <input type="hidden" name="methode" value="supprimer_serie_element">
                    <input type="hidden" name="seequo_cod" value="<?php echo $db2->f("seequo_cod") ?>">
                    <td><input type="submit" value="Supprimer"></td>
                </form>
                </tr>

                <?php
            } ?>
        </table>
        <?php
    }
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
