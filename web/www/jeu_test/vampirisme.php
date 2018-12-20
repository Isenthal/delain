<?php
include "blocks/_header_page_jeu.php";
ob_start();
if (!isset($methode)) {
    $methode = "entree";
}
$req = "select perso_niveau_vampire,race_nom from perso,race where perso_cod = $perso_cod ";
$req = $req . "and perso_race_cod = race_cod ";
$db->query($req);
$db->next_record();
$lvl = $db->f("perso_niveau_vampire");
if ($lvl != 0) {
    switch ($methode) {
        case "entree":
            // on commence par afficher un résumé
            echo "<p class=\"titre\">", $db->f("race_nom"), " - ", $niveau[$lvl], "</p>";
            // on n'affiche pas les infos d'ascendance si c'est un first, bien entendu
            if ($lvl != 100) {
                $req = "select vamp_perso_pere,vamp_nom_ppere from vampire_hist ";
                $req = $req . "where vamp_perso_fils = $perso_cod ";
                $db->query($req);
                $db->next_record();
                echo "<p>Votre ascendant est : ";
                if ($db->f("vamp_perso_pere") != '') {
                    echo "<a href=\"visu_desc_perso.php?visu=", $db->f("vamp_perso_pere"), "\">";
                }
                echo "<strong>", $db->f("vamp_nom_ppere"), "</strong>";
                if ($db->f("vamp_perso_pere") != '') {
                    echo "</a>";
                }
                echo "<br><br>";
            }

            if ($lvl >= 60) {
                $req = "select vamp_perso_fils,vamp_nom_pfils,to_char(vamp_date,'DD/MM/YYYY hh24:mi:ss') as dvamp from vampire_hist ";
                $req = $req . "where vamp_perso_pere = $perso_cod ";
                $req = $req . "order by vamp_date ";
                $db->query($req);
                if ($db->nf() == 0) {
                    echo "<p>Vous n'avez aucune descendance";
                } else {
                    echo "<p class=\"soustitre2\">Liste de descendance :</p><p>";
                    while ($db->next_record()) {
                        if ($db->f("vamp_perso_fils") != '') {
                            echo "<a href=\"visu_desc_perso.php?visu=", $db->f("vamp_perso_fils"), "\">";
                        }
                        echo "<strong>", $db->f("vamp_nom_pfils"), "</strong>";
                        if ($db->f("vamp_perso_fils") != '') {
                            echo "</a>";
                        }
                        echo " (", $db->f("dvamp"), ")<br>";
                    }
                }
                $req = "select to_char(tvamp_date,'dd/mm/yyyy hh24:mi:ss') as dvamp,tvamp_perso_fils,perso_nom from vampire_tran,perso ";
                $req = $req . "where tvamp_perso_pere = $perso_cod ";
                $req = $req . "and tvamp_perso_fils = perso_cod ";
                $db->query($req);
                if ($db->nf() == 0) {
                    echo "<p>Pas de création de descendance en cours";
                } else {
                    echo "<p>Descendances en cours :<br>";
                    while ($db->next_record()) {
                        echo "<a href=\"visu_desc_perso.php?visu=", $db->f("tvamp_perso_fils"), "\">";
                        echo "<strong>", $db->f("perso_nom"), "</strong>";
                        echo "</a>";
                        echo " (", $db->f("dvamp"), ")<br>";
                    }
                }
                echo '<p><a href="vampirisme.php?methode=cree1">Créer une descendance !</a>';
            } else {
                echo "<p>Vous ne pouvez pas créér de descendance tant que vous n'êtes pas au minimum Maitre Vampire";
            }
            break;
        case "cree1":
            $erreur = 0;
            if ($lvl < 60) {
                echo "<p>Erreur, vous ne pouvez créer de descendance tant que vous n'êtes pas au minimum Maitre Vampire";
                $erreur = 1;
            }
            if ($erreur == 0) {
                $req_pos = "select ppos_pos_cod from perso_position where ppos_perso_cod = $perso_cod ";
                $db->query($req_pos);
                $db->next_record();
                $pos_actuelle = $db->f("ppos_pos_cod");
                $req_vue = "select perso_nom,perso_cod from perso, perso_position ";
                $req_vue = $req_vue . "where ppos_pos_cod = $pos_actuelle  ";
                $req_vue = $req_vue . "and ppos_perso_cod = perso_cod ";
                //$req_vue = $req_vue . "and perso_cod != $perso_cod  ";
                $req_vue = $req_vue . "and perso_type_perso = 1 ";
                $req_vue = $req_vue . "and perso_actif = 'O' ";
                $req_vue = $req_vue . "order by perso_nom ";
                $db->query($req_vue);
                ?>
                <form name="cree" method="post" action="vampirisme.php">
                    <input type="hidden" name="methode" value="cree2">
                    <table>
                        <tr>
                            <td>
                                <p>Choisissez le perso :</td>
                            <td><p>
                                    <select name="cible">
                                        <?php
                                        while ($db->next_record()) {
                                            echo "<option value=\"", $db->f("perso_cod"), "\">", $db->f("perso_nom"), "</option>";
                                        }
                                        ?>
                                    </select></td>
                        </tr>
                        <tr>
                            <td>
                                <p>Ajoutez votre texte :</td>
                            <td><p>
                                    <textarea name="message" cols="40" rows="10"></textarea></td>
                        </tr>

                        <tr>
                            <td colspan="2"><p>Le perso recevra un message contenant votre texte, ainsi qu'un lien pour
                                    valider ou refuser votre proposition</td>
                        </tr>
                        <tr>
                            <td colspan="2"><p style="text-align:center;"><input type="submit" class="test"
                                                                                 value="Valider l'envoi"></td>
                        </tr>

                    </table>
                </form>
                <?php
            }
            break;
        case "cree2":
            //
            // on stocke les infos en base
            //
            $req = "insert into vampire_tran (tvamp_perso_pere,tvamp_perso_fils) ";
            $req = $req . "values ($perso_cod,$cible) ";
            $db->query($req);
            //
            // on envoie le message kivabien
            //
            $message = nl2br($message);
            $corps = "Un vampire vient de vous proposer de faire partie de sa descendance. Vous trouverez les instructions pour accepter ou refuser à la fin de ce message.<br>";
            $corps = $corps . "-------------<br>" . $message . "<br>-------------<br>";
            $corps = $corps . "Pour voir les conséquences, accepter ou refuser cette proposition, <a href=\"tran_vamp.php\">cliquez-ici</a>";
            $titre = "Proposition de vampirisme";
            $corps = str_replace(";", chr(127), $corps);
            $req_msg_cod = "select nextval('seq_msg_cod') as numero";
            $db->query($req_msg_cod);
            $db->next_record();
            $num_mes = $db->f("numero");
            //$titre = htmlspecialchars($titre);
            //$corps = htmlspecialchars($corps);
            // le message
            $req_ins_mes = "insert into messages (msg_cod,msg_date2,msg_date,msg_titre,msg_corps) ";
            $req_ins_mes = $req_ins_mes . "values ($num_mes,now(),now(),'$titre',e'" . pg_escape_string($corps) . "') ";
            $db->query($req_ins_mes);
            // expéditeur
            $req_ins_exp = "insert into messages_exp (emsg_cod,emsg_msg_cod,emsg_perso_cod,emsg_archive) ";
            $req_ins_exp = $req_ins_exp . "values (nextval('seq_emsg_cod'),$num_mes,$perso_cod,'N')";
            $db->query($req_ins_exp);
            // cible
            $req_ins_dest = "insert into messages_dest (dmsg_cod,dmsg_msg_cod,dmsg_perso_cod,dmsg_lu,dmsg_archive) ";
            $req_ins_dest = $req_ins_dest . "values (nextval('seq_dmsg_cod'),$num_mes,$cible,'N','N')";
            $db->query($req_ins_dest);
            echo "<p>Le message a été envoyé.";


            break;
    }
} else {
    echo "<p>Vous n'avez pas accès à cette page !";
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
