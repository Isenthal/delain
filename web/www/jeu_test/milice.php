<?php
include "blocks/_header_page_jeu.php";
ob_start();
$erreur = 0;
if ($db->is_milice($perso_cod) == 0) {
    echo "<p>Erreur ! Vous n'avez pas accès à cette page !";
    $erreur = 1;
}
if ($erreur == 0) {
    $req = "select pguilde_rang_cod from guilde_perso where pguilde_perso_cod = $perso_cod and pguilde_rang_cod = 3 ";
    $db->query($req);
    if ($db->nf() != 0) {
        ?>
        <p><a href="magistrat.php">Acceder à la partie magistrat ?</a>
        <hr>
        <?php
    }
    $req = "select pguilde_rang_cod from guilde_perso where pguilde_perso_cod = $perso_cod and pguilde_rang_cod = 0 ";
    $db->query($req);
    if ($db->nf() != 0) {
        ?>
        <p><a href="prefet.php">Acceder à la partie préfet ?</a>
        <hr>
        <?php
    }
    $req = "select pguilde_rang_cod from guilde_perso where pguilde_perso_cod = $perso_cod and pguilde_rang_cod = 16 ";
    $db->query($req);
    if ($db->nf() != 0) {
        ?>
        <p><a href="geolier.php">Acceder à la partie geolier ?</a>
        <hr>
        <?php
    }
    if (!isset($methode)) {
        $methode = 'debut';
    }
    switch ($methode) {
        case "debut":


            $req = "select pguilde_mode_milice from guilde_perso where pguilde_perso_cod = $perso_cod ";
            $db->query($req);
            $db->next_record();
            $mode[1] = 'normal';
            $mode[2] = 'Application des peines';
            $mode[3] = 'CRS';
            $vmode = $db->f("pguilde_mode_milice");
            echo "<p>Vous êtes actuellement en mode <strong> $mode[$vmode]</strong><br>";
            echo "<a href=\"", $PHP_SELF, "?methode=changem\">Changer le mode ?</a><br> ";
            echo "<a href=\"", $PHP_SELF, "?methode=voir\">Voir les peines en attente d'éxécution</a><br> ";
            echo "<a href=\"megaphone.php\">Utiliser son mégaphone ?</a> ";
            break;
        case "changem":
            $mode[1] = 'normal';
            $mode[2] = 'Application des peines';
            $mode[3] = 'CRS';
            ?>
            <form name="changem" method="post" action="<?php echo $PHP_SELF; ?>">
                <input type="hidden" name="methode" value="changem2">
                Choisissez le mode dans lequel vous souhaitez vous placer :<br>
                <select name="mode">
                    <?php
                    for ($cpt = 1; $cpt <= 3; $cpt++) {
                        echo "<option value=\"$cpt\">$mode[$cpt]</option>";
                    }
                    ?>
                </select>
                <br>
                <input type="submit" class="test centrer" value="Valider !">
            </form>
            <?php
            break;
        case "changem2":
            $req = "update guilde_perso set pguilde_mode_milice = $mode where pguilde_perso_cod = $perso_cod ";
            if ($db->query($req)) {
                echo "<p>Modification effectuée !";
            }
            break;
        case "voir":
            echo "<p class=\"titre\">Peines existantes </p>";
            $req = "select peine_cod,acc.perso_cod as c_acc,acc.perso_nom as n_acc,mag.perso_cod as c_mag,mag.perso_nom as n_mag,peine_type,peine_duree,peine_faite,to_char(peine_date,'DD/MM/YYYY hh24:mi:ss') as date_peine ";
            $req = $req . "from peine,perso acc,perso mag ";
            $req = $req . "where peine_magistrat = mag.perso_cod ";
            $req = $req . "and peine_perso_cod = acc.perso_cod ";
            $req = $req . "and peine_faite < 2 ";
            $db->query($req);
            if ($db->nf() == 0) {
                echo "<p>Aucune peine non effectuée en cours.";
            } else {
                $etat[0] = "Non effectuée";
                $etat[1] = "En cours";
                $peine[0] = "Peine de mort";
                $peine[1] = "Emprisonnement limité";
                $peine[2] = "Emprisonnement à pertpétuité";
                ?>
                <table>
                    <tr>
                        <td class="soustitre2"><strong>Dossier</strong></td>
                        <td class="soustitre2"><strong>Accusé</strong></td>
                        <td class="soustitre2"><strong>Peine</strong></td>
                        <td class="soustitre2"><strong>Validée par</strong></td>
                        <td class="soustitre2"><strong>Date de peine</strong></td>
                        <td class="soustitre2"><strong>Etat de la peine</strong></td>
                    </tr>
                    <?php
                    while ($db->next_record()) {
                        $v_peine = $db->f("peine_type");
                        $v_faite = $db->f("peine_faite");
                        echo "<tr>";
                        echo "<td class=\"soustitre2\">", $db->f("peine_cod"), "</td>";
                        echo "<td class=\"soustitre2\"><a href=\"visu_desc_perso.php?visu=", $db->f("c_acc"), "\"><strong>", $db->f("n_acc"), "</strong></td>";
                        echo "<td>$peine[$v_peine]</td>";
                        echo "<td class=\"soustitre2\"><a href=\"visu_desc_perso.php?visu=", $db->f("c_mag"), "\"><strong>", $db->f("n_mag"), "</strong></td>";
                        echo "<td>", $db->f("date_peine"), "</td>";
                        echo "<td>$etat[$v_faite]</td>";
                        echo "</tr>";
                    }

                    ?>
                </table>
                <?php
            }
            break;
    }
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";


