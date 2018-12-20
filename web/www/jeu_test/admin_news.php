<?php
include "blocks/_header_page_jeu.php";
ob_start();
?>
    <script>
        function confirmeLance() {
            var confirmation = confirm("Êtes-vous sûr de vouloir lancer cette news sur l’interface de tous les joueurs ?");

            if (confirmation) {
                document.lanceNews.submit();
            }
            else {
                document.lanceNews.methode.value = 'debut';
            }
        }

        function confirmeLanceRumeur() {
            var confirmation = confirm("Êtes-vous sûr de vouloir lancer cette rumeur ?");

            if (confirmation) {
                document.lanceRumeur.submit();
            }
            else {
                document.lanceRumeur.methode.value = 'debut';
            }
        }

        function previsualiser() {
            document.lanceNews.methode.value = 'previsu';
            document.lanceNews.submit();
        }
    </script>
<?php
// A FAIRE POUR LANCER DES NEWS !!
$erreur = 0;
$req = "select dcompt_news from compt_droit where dcompt_compt_cod = $compt_cod";
$db->query($req);
if ($db->nf() == 0) {
    echo "<p>Erreur ! Vous n’avez pas accès à cette page !";
    $erreur = 1;
} else {
    $db->next_record();
}
if ($db->f("dcompt_news") != 'O') {
    echo "<p>Erreur ! Vous n’avez pas accès à cette page !";
    $erreur = 1;
}
if ($erreur == 0) {
    if (!isset($methode)) {
        $methode = "debut";
    }
    switch ($methode) {
        case "debut":
            ?>
            <form name="lanceNews" method="post" action="<?php echo $PHP_SELF; ?>">
                <input type="hidden" name="methode" value="previsu"/>
                <table>
                    <tr>
                        <td>Titre :</td>
                        <td><input type="text" name="titre" size="80" value="<?php echo $titre; ?>"/></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Auteur :</td>
                        <td><input type="text" name="auteur" value="<?php echo $auteur; ?>"/></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Contenu de la news :</td>
                        <td><textarea name="texte" cols="80" rows="20"><?php echo $texte; ?></textarea></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            Attention : la mise en page du contenu de la news se fait en html !
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button onclick="previsualiser();">Prévisualiser</button>
                        </td>
                    </tr>
                </table>
            </form>
            <hr/>
            <form name="lanceRumeur" method="post" action="<?php echo $PHP_SELF; ?>">
                <input type="hidden" name="methode" value="lanceRumeur"/>
                <table>
                    <tr>
                        <td>Poids (pour déterminer les chances d’apparition...<br/>
                            Dans le jeu, ce serait la quantité de brouzoufs payée)
                        </td>
                        <td><input type="text" name="poids" size="80" value="<?php echo $poids; ?>"/></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Contenu de la rumeur :</td>
                        <td><textarea name="texte" cols="80" rows="20"><?php echo $texte; ?></textarea></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            Attention : la mise en page du contenu de la rumeur se fait en html !
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button onclick="confirmeLanceRumeur();">Lancer la rumeur</button>
                        </td>
                    </tr>
                </table>
            </form>
            <?php
            break;

        case "lance":

            // on vérifie qu’on a rempli les champs correctement
            if (isset($titre) && isset($auteur) && isset($texte)) {
                if ($titre != '' && $auteur != '' && $texte != '') {
                    $titre = pg_escape_string(str_replace('\'', '’', $titre));
                    $auteur = pg_escape_string(str_replace('\'', '’', $auteur));
                    $texte = pg_escape_string(str_replace('\'', '’', $texte));

                    $req = "insert into news (news_titre, news_auteur, news_texte) values (e'$titre', e'$auteur', e'$texte')";
                    $db->query($req);

                    ?> News correctement lancée <?php

                } else {
                    ?> Échec, un champ n’est pas rempli ! <?php
                }
            } else {
                ?> Échec, un champ n’est pas rempli ! <?php
            }
            break;

        case "lanceRumeur":

            // on vérifie qu’on a rempli les champs correctement
            if (isset($poids) && isset($texte)) {
                if ($poids == '' || $poids == 0) $poids = 1;
                if (strlen(trim($texte)) > 5) {
                    $texte = pg_escape_string(str_replace('\'', '’', $texte));
                    $poids = pg_escape_string(str_replace('\'', '’', $poids));
                    $req = "insert into rumeurs (rum_perso_cod, rum_texte, rum_poids) values ($perso_cod, e'$texte', $poids)";
                    $db->query($req);

                    ?> Rumeur correctement lancée <?php

                } else {
                    ?> Échec, le texte est trop court ! <?php
                }
            } else {
                ?> Échec, un champ n’est pas rempli ! <?php
            }
            break;

        case "previsu":
            $titre = str_replace('\'', '’', $titre);
            $auteur = str_replace('\'', '’', $auteur);
            $texte = str_replace('\'', '’', $texte);
            ?>
            <p><strong>Prévisualisation</strong></p>
            <div class="bordiv" style="margin:2px;text-align:center">
                <div class="titre"><?php echo $titre ?></div>
                <div class="texteNorm" style="text-align:right;">
                    <?php echo $date_news ?>
                </div>
                <div class="texteNorm" style="text-align:left;">
                    <?php echo $texte ?>
                </div>
                <div class="texteNorm" style="text-align:right;">
                    <?php echo $auteur ?>
                </div>
            </div>
            <hr/>
            <form name="lanceNews" method="post" action="<?php echo $PHP_SELF; ?>">
                <input type="hidden" name="methode" value="lance"/>
                <table>
                    <tr>
                        <td>Titre :</td>
                        <td><input type="text" name="titre" size="80" value="<?php echo $titre; ?>"/></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Auteur :</td>
                        <td><input type="text" name="auteur" value="<?php echo $auteur; ?>"/></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Contenu de la news :</td>
                        <td><textarea name="texte" cols="80" rows="20"><?php echo $texte; ?></textarea></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            Attention : la mise en page du contenu de la news se fait en html !
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <button onclick="previsualiser();">Prévisualiser</button>
                        </td>
                        <td>
                            <button onclick="confirmeLance();">Lancer la news</button>
                        </td>
                    </tr>
                </table>
            </form>
        <?php
    }
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";