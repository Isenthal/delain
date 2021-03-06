<?php
include "blocks/_header_page_jeu.php";
ob_start();
$erreur = 0;
$perso  = new perso;
$perso  = $verif_connexion->perso;
if ($perso->is_milice() == 0)
{
    echo "<p>Erreur ! Vous n'avez pas accès à cette page !";
    $erreur = 1;
}
if ($erreur == 0)
{
    $methode = get_request_var('methode', 'debut');
    if (!$perso->is_bernardo())
    {
        switch ($methode)
        {
            case "debut":
                ?>
                <form name="nouveau_message" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="methode" value="envoi">
                    <table cellpadding="2" cellspacing="2">

                        <tr>
                            <td class="titre" colspan="2"><p class="titre">Utilisation du mégaphone</p></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="soustitre2"><p><em>Rappel : </em>Merci de bien vouloir éviter les
                                    insultes, et de rester dans le cadre de la courtoisie dans vos messages. Tout abus
                                    pourra amener à une cloture du compte sans préavis.</p></td>
                        </tr>

                        <tr>
                            <td class="soustitre2"><p>Régalge du volume </p></td>
                            <td>
                                <select name="volume">
                                    <?php
                                    for ($i = 0; $i <= 2; $i++)
                                    {
                                        echo "<option value=\"$i\">$i</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td class="soustitre2"><p>Message que vous allez hurler dans le megaphone : </p></td>
                            <td>
                                <textarea name="corps" cols="40" rows="10"></textarea>
                            </td>
                        </tr>


                        <tr>
                            <td colspan="2" class="soustitre2"><input type="submit" accesskey="s" class="test centrer"
                                                                      value="Envoyer le message !"></td>
                        </tr>

                    </table>
                </form>
                <?php
                break;
            case "envoi";
                // titre
                $titre = "C\'est la milice qui vous parle !";
                $titre = htmlspecialchars($titre);

                $message             = new message();
                $message->sujet      = "C'est la milice qui vous parle !";
                $message->corps      = $_REQUEST['corps'];
                $message->expediteur = $perso_cod;

                // enregistrement des destinataires
                // recherche de la position
                $req_pos      =
                    "select ppos_pos_cod,pos_etage,pos_x,pos_y from perso_position,perso,positions where ppos_perso_cod = $perso_cod and perso_cod = $perso_cod and ppos_pos_cod = pos_cod ";
                $stmt         = $pdo->query($req_pos);
                $result       = $stmt->fetch();
                $pos_actuelle = $result['ppos_pos_cod'];
                $v_x          = $result['pos_x'];
                $v_y          = $result['pos_y'];
                $etage        = $result['pos_etage'];
                // rechreche des dest
                $req_vue =
                    "select perso_cod,perso_nom,distance(ppos_pos_cod,$pos_actuelle) from perso, perso_position, positions ";
                $req_vue = $req_vue . "where pos_x >= ($v_x - $volume) and pos_x <= ($v_x + $volume) ";
                $req_vue = $req_vue . "and pos_y >= ($v_y - $volume) and pos_y <= ($v_y + $volume) ";
                $req_vue = $req_vue . "and ppos_perso_cod = perso_cod ";
                $req_vue = $req_vue . "and perso_cod != $perso_cod  ";
                $req_vue = $req_vue . "and perso_actif = 'O' ";
                $req_vue = $req_vue . "and ppos_pos_cod = pos_cod ";
                $req_vue = $req_vue . "and pos_etage = $etage ";
                $stmt    = $pdo->query($req_vue);

                while ($result = $stmt->fetch())
                {
                    $message->ajouteDestinataire($result['perso_cod']);

                }
                $message->envoieMessage();
                echo "<p>Votre message a été envoyé à toutes les personnes présents à $volume de distance de vous.";
                break;
        }
    } else
    {
        echo("<p>Vous êtes sous l'effet du sort Bernardo. Vous ne pouvez pas poster de message.");
    }
} else
{
    ?>
    <p>Erreur ! vous n'avez pas accès à cette page !
    <?php
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";


