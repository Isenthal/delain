<?php
include "blocks/_header_page_jeu.php";
$compte = $verif_connexion->compte;

$num_perso2 = $_REQUEST['num_perso2'];

function insert_evt($num_perso2, $texte_evt)
{
    $levt                  = new ligne_evt();
    $levt->levt_tevt_cod   = 43;
    $levt->levt_perso_cod1 = $num_perso2;
    $levt->levt_texte      = $texte_evt;
    $levt->levt_lu         = 'N';
    $levt->levt_visible    = 'N';
    $levt->stocke(true);
    unset($levt);
}

ob_start();
$erreur = 0;
if ($compt->compt_quete != 'O')
{
    echo "<p>Erreur ! Vous n'avez pas accès à cette page !";
    $erreur = 1;
}
if ($erreur == 0)
{
    $methode                            = get_request_var('methode', 'debut');
    switch ($methode)
    {
        case "debut":
            ?>
            <form name="login2" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="methode" value="choix_perso">
                <p>Entrez directement le numéro de perso sur lequel vous voulez intervenir : <input type="text"
                                                                                                    name="num_perso2">
                    <input type="submit" value="Suite" class="test">
            </form>
            <input type="button" class="test" value="Rechercher un perso !"
                   onClick="window.open('<?php echo $type_flux . G_URL; ?>rech_perso.php','rech','width=500,height=300');">
            <hr>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?methode=appel&met_appel=debut">Lancer un appel ?</a><br>

            <?php
            break;
        case "choix_perso":
            $req       =
                "select perso_tangible,perso_nom,pos_x,pos_y,etage_libelle,perso_pv,perso_pv_max,to_char(perso_dlt,'DD/MM/YYYY hh24:mi:ss') as dlt,perso_actif ";
            $req       = $req . "from perso,perso_position,positions,etage ";
            $req       = $req . "where perso_cod = $num_perso2 ";
            $req       = $req . "and ppos_perso_cod = perso_cod ";
            $req       = $req . "and ppos_pos_cod = pos_cod ";
            $req       = $req . "and pos_etage = etage_numero ";
            $stmt      = $pdo->query($req);
            $result    = $stmt->fetch();
            $tangible  = $result['perso_tangible'];
            $err_actif = 0;
            if ($result['perso_actif'] != 'O')
            {
                $err_actif = 1;
                switch ($result['perso_actif'])
                {
                    case "N":
                        echo "Ce perso est <strong>Inactif !</strong>. Vous ne pouvez pas intervenir dessus !";
                        break;
                    case "H":
                        echo "Ce perso est <strong>en hibernation !</strong>. Vous ne pouvez pas intervenir dessus !";
                        break;
                }
            } else
            {
                echo "<p><strong>", $result['perso_nom'], "</strong> se trouve en ", $result['pos_x'], ", ", $result['pos_y'], ", ", $result['etage_libelle'], ".<br>";
                echo "Sa dlt est à <strong>", $result['dlt'], ".</br>";
                echo "Il est à ", $result['perso_pv'], "/", $result['perso_pv_max'], " PV.";
                $persotemp = new perso;
                $persotemp->charge($num_perso2);
                if ($persotemp->is_locked())
                {
                    echo "<p><strong>Ce perso est locké en combat !</strong>";
                }
                echo "<p class=\"titre\">Actions possibles : </p>";
                echo "<p>";
                echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=depl&met_depl=debut&num_perso2=", $num_perso2, "\">Déplacer le perso ?</a><br>";
                echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=dlt&num_perso2=", $num_perso2, "\">Initialiser sa DLT à l'heure actuelle ?</a><br>";
                echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=objet&met_obj=debut&num_perso2=", $num_perso2, "\">Créer un nouvel objet de quête dans son inventaire ?</a><br>";
                echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=objet_ex&met_obj=debut&num_perso2=", $num_perso2, "\">Créer un objet de quête (déjà existant) dans son inventaire ?</a><br>";
                echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=objet_nq&met_obj=debut&num_perso2=", $num_perso2, "\">Créer un objet (hors quête) dans son inventaire ?</a><br>";
                if ($tangible == 'O')
                {
                    echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=palpable&t=N&num_perso2=", $num_perso2, "\">Rendre ce perso impalpable ?</a><br>";
                } else
                {
                    echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=palpable&t=O&num_perso2=", $num_perso2, "\">Rendre ce perso palpable ?</a><br>";
                }


            }
            break;
        case "depl":
            switch ($met_depl)
            {
                case "debut":
                    $persotemp = new perso;
                    $persotemp->charge($num_perso2);
                    {
                        echo "<p><strong>Ce perso est locké en combat !</strong> Son déplacement va rompre tous les locks de combat.";
                    }
                    ?>
                    <form name="login2" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" name="methode" value="depl">
                        <input type="hidden" name="met_depl" value="dest">
                        <input type="hidden" name="num_perso2" value="<?php echo $num_perso2; ?>">
                        <p>Entrez la position à laquelle vous souhaitez déplacer ce perso :<br>
                            X : <input type="text" name="pos_x" maxlength="5" size="5"> -
                            Y : <input type="text" name="pos_y" maxlength="5" size="5"> -
                            Etage : <select name="etage">
                                <?php
                                $req  = "SELECT etage_numero,etage_libelle FROM etage ORDER BY etage_numero DESC ";
                                $stmt = $pdo->query($req);
                                while ($result = $stmt->fetch())
                                {
                                    echo "<option value=\"", $result['etage_numero'], "\">", $result['etage_libelle'], "</option>";
                                }
                                ?>
                            </select><br>
                            <input type="submit" class="test centrer" value="Déplacer !">
                    </form>
                    <?php
                    break;
                case "dest":
                    $err_depl = 0;
                    $req      =
                        "select pos_cod from positions where pos_x = $pos_x and pos_y = $pos_y and pos_etage = $etage ";
                    $stmt     = $pdo->query($req);
                    if ($stmt->rowCount() == 0)
                    {
                        echo "<p>Aucune position trouvée à ces coordonnées.<br>";
                        echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=depl&met_depl=debut&num_perso2=", $num_perso2, "\">Retour au choix des coordonnées ?</a><br>";
                        $err_depl = 1;
                    }
                    $result  = $stmt->fetch();
                    $pos_cod = $result['pos_cod'];
                    $req     = "select mur_pos_cod from murs where mur_pos_cod = $pos_cod ";
                    $stmt    = $pdo->query($req);
                    if ($stmt->rowCount() != 0)
                    {
                        echo "<p>impossible de déplacer le perso : un mur en destination.<br>";
                        echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=depl&met_depl=debut&num_perso2=", $num_perso2, "\">Retour au choix des coordonnées ?</a><br>";
                        $err_depl = 1;
                    }
                    if ($err_depl == 0)
                    {
                        // insertion dun évènement
                        $texte_evt = "[perso_cod1] a été déplacé par un admin quête.";
                        insert_evt($num_perso2, $texte_evt);

                        // effacement des locks
                        $req  = "delete from lock_combat where lock_cible = $num_perso2 ";
                        $stmt = $pdo->query($req);
                        $req  = "delete from lock_combat where lock_attaquant = $num_perso2 ";
                        $stmt = $pdo->query($req);
                        // déplacement
                        $req  = "update perso_position set ppos_pos_cod = $pos_cod where ppos_perso_cod = $num_perso2 ";
                        $stmt = $pdo->query($req);
                        echo "<p>Le perso a bien été déplacé !";
                    }
                    break;
            }
            break;
        case "dlt":
            // insertion dun évènement
            $texte_evt             = "La DLT de [perso_cod1] a été actualisée par un admin quête.";
            $levt                  = new ligne_evt();
            $levt->levt_tevt_cod   = 43;
            $levt->levt_perso_cod1 = $num_perso2;
            $levt->levt_texte      = $texte_evt;
            $levt->levt_lu         = 'N';
            $levt->levt_visible    = 'N';
            $req                   = "update perso set perso_dlt = now() where perso_cod = $num_perso2 ";
            $stmt                  = $pdo->query($req);
            echo "<p>La dlt de ce joueur est prête à être activée.";
            break;
        case "objet":
            switch ($met_obj)
            {
                case "debut":
                    ?>
                    <p><strong>Attention ! </strong>Cette procédure n'a pour but que de créer de nouveaux objets
                        (première
                        apparition dans le jeu) dans l'inventaire d'un perso.<br>
                        Si vous souhaitez créer un objet déjà existant, <a
                                href="<?php echo $_SERVER['PHP_SELF']; ?>?methode=objet_ex&met_obj=debut&num_perso=<?php echo $num_perso2; ?>">merci
                            de cliquer ici !</a>
                        <form name="login2" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                            <input type="hidden" name="methode" value="objet">
                            <input type="hidden" name="met_obj" value="etape2">
                            <input type="hidden" name="num_perso" value="<?php echo $num_perso2; ?>">
                            <table>
                                <tr>
                                    <td class="soustitre2"><p>Nom de l'objet (une fois identifié) :</p></td>
                    <td><input type="text" name="nom_objet" size="50"></td>
                    </tr>
                    <tr>
                        <td class="soustitre2"><p>Nom de l'objet (pas encore identifié) :</td>
                        <td><input type="text" name="nom_objet_non_iden" size="50"></td>
                    </tr>
                    <tr>
                        <td class="soustitre2"><p>Description :</td>
                        <td><textarea name="desc" rows="10" cols="30"></textarea></td>
                    </tr>
                    <tr>
                        <td class="soustitre2"><p>Poids de l'objet :</td>
                        <td><input type="text" name="poids_objet"></td>
                    </tr>
                    </table>
                    <input type="submit" class="test centrer" value="Créer !"></form>
                    <?php
                    break;
                case "etape2":
                    // recherche du num objet generique
                    $req                = "select nextval('seq_gobj_cod') as gobj";
                    $stmt               = $pdo->query($req);
                    $result             = $stmt->fetch();
                    $gobj_cod           = $result['gobj'];
                    $nom_objet          = str_replace("'", "\'", $nom_objet);
                    $nom_objet_non_iden = str_replace("'", "\'", $nom_objet_non_iden);
                    $desc               = str_replace("'", "\'", $desc);
                    // création dans les objets génériques
                    $req  = "INSERT INTO objet_generique (gobj_cod,gobj_nom,gobj_nom_generique,gobj_tobj_cod,gobj_valeur,gobj_poids,gobj_description,gobj_deposable,gobj_visible,gobj_echoppe) 
                      values ($gobj_cod,'$nom_objet','$nom_objet_non_iden',11,0,$poids_objet,'$desc','O','O','N')";
                    $stmt = $pdo->query($req);
                    // insertion dun évènement
                    $texte_evt = "Un admin quête a créé un objet dans l\'inventaire de [perso_cod1].";
                    insert_evt($num_perso2, $texte_evt);
                    // création
                    $req  = "select cree_objet_perso_nombre($gobj_cod,$num_perso2,1) ";
                    $stmt = $pdo->query($req);
                    echo "<p>L'objet a bien été créé !";
                    break;
            }
            break;
        case "objet_ex":
            switch ($met_obj)
            {
                case "debut":
                    ?>
                    <p><strong>Attention ! </strong>Cette procédure n'a pour but que de créer des objets existants (pas
                        encore
                        créés dans le jeu) dans l'inventaire d'un perso.<br>
                        Si vous souhaitez créer un nouvel objet, <a
                                href="<?php echo $_SERVER['PHP_SELF']; ?>?methode=objet&met_obj=debut&num_perso=<?php echo $num_perso2; ?>">merci
                            de cliquer ici !</a><br>
                    <form name="login2" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" name="methode" value="objet_ex">
                        <input type="hidden" name="met_obj" value="etape2">
                        <input type="hidden" name="num_perso" value="<?php echo $num_perso2; ?>">
                        <br/>Objet à créer : <select name="gobj">
                            <?php
                            $req  =
                                "SELECT gobj_nom,gobj_cod FROM objet_generique WHERE gobj_tobj_cod = 11 ORDER BY gobj_nom ";
                            $stmt = $pdo->query($req);
                            while ($result = $stmt->fetch())
                            {
                                echo "<option value=\"", $result['gobj_cod'], "\">", $result['gobj_nom'], "</option>";
                            }
                            ?></select><br>
                        <input type="submit" class="test centrer" value="Créer !"></form>
                    <?php
                    break;
                case "etape2":
                    // insertion dun évènement
                    $texte_evt = "Un admin quête a créé un objet dans l\'inventaire de [perso_cod1].";
                    insert_evt($num_perso2, $texte_evt);
                    // création
                    $req  = "select cree_objet_perso_nombre($gobj,$num_perso2,1) ";
                    $stmt = $pdo->query($req);
                    echo "<p>L'objet a bien été créé !";
                    break;
            }
            break;
        case "objet_nq":
            switch ($met_obj)
            {
                case "debut":
                    ?>
                    <p><strong>Attention ! </strong>Cette procédure n'a pour but que de créer des objets existants (pas
                        encore
                        créés dans le jeu) dans l'inventaire d'un perso.<br>
                        Si vous souhaitez créer un nouvel objet, <a
                                href="<?php echo $_SERVER['PHP_SELF']; ?>?methode=objet&met_obj=debut&num_perso=<?php echo $num_perso2; ?>">merci
                            de cliquer ici !</a><br>
                    <form name="login2" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" name="methode" value="objet_ex">
                        <input type="hidden" name="met_obj" value="etape2">
                        <input type="hidden" name="num_perso" value="<?php echo $num_perso2; ?>">
                        <br/>Objet à créer : <select name="gobj">
                            <?php
                            $req  =
                                "SELECT gobj_nom,gobj_cod,tobj_libelle,tobj_cod FROM objet_generique,type_objet WHERE gobj_tobj_cod != 11 AND gobj_tobj_cod = tobj_cod ORDER BY tobj_cod,gobj_nom ";
                            $stmt = $pdo->query($req);
                            while ($result = $stmt->fetch())
                            {
                                echo "<option value=\"", $result['gobj_cod'], "\">", $result['gobj_nom'], " - (", $result['tobj_libelle'], ")</option>";
                            }
                            ?></select><br>
                        <input type="submit" class="test centrer" value="Créer !"></form>
                    <?php
                    break;
                case "etape2":
                    // insertion dun évènement
                    $texte_evt = "Un admin quête a créé un objet dans l\'inventaire de [perso_cod1].";
                    insert_evt($num_perso2, $texte_evt);
                    // création
                    $req  = "select cree_objet_perso_nombre($gobj,$num_perso2,1) ";
                    $stmt = $pdo->query($req);
                    echo "<p>L'objet a bien été créé !";
                    break;
            }
            break;
        case "appel":
            switch ($met_appel)
            {
                case "debut":
                    ?>
                    <form name="login2" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" name="methode" value="appel">
                        <input type="hidden" name="met_appel" value="dest">
                        <input type="hidden" name="num_perso" value="<?php echo $num_perso2; ?>">
                        <table>
                            <tr>
                                <td class="soustitre2">
                                    <p>Entrez la position à partir de laquelle l'appel sera lancé :</td>
                                <td>
                                    X : <input type="text" name="pos_x" maxlength="5" size="5"> -
                                    Y : <input type="text" name="pos_y" maxlength="5" size="5"> -
                                    Etage : <select name="etage">
                                        <?php
                                        $req  =
                                            "SELECT etage_numero,etage_libelle FROM etage ORDER BY etage_numero DESC ";
                                        $stmt = $pdo->query($req);
                                        while ($result = $stmt->fetch())
                                        {
                                            echo "<option value=\"", $result['etage_numero'], "\">", $result['etage_libelle'], "</option>";
                                        }
                                        ?>
                                    </select></td>
                            </tr>
                            <tr>
                                <td class="soustitre2">
                                    <p>Entrez la distance :</td>
                                <td>
                                    <select name="distance">
                                        <?php
                                        for ($i = 0; $i <= 5; $i++)
                                        {
                                            echo "<option value=\"", $i, "\">", $i, "</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="soustitre2">
                                    <p>Entrez le Numéro de perso lançant l'appel :</td>
                                <td><input type="text" name="perso" size="50"></td>
                            </tr>
                            <tr>
                                <td class="soustitre2">
                                    <p>Entrez le titre :</td>
                                <td><input type="text" name="titre" size="50"></td>
                            </tr>
                            <tr>
                                <td class="soustitre2">
                                    <p>Entrez le texte :</td>
                                <td><textarea name="corps" cols="50" rows="10"></textarea></td>
                            </tr>
                        </table>

                        <input type="submit centrer" class="test" value="Lancer l'appel !">
                    </form>
                    <?php
                    break;
                case "dest":
                    $err_depl = 0;
                    $req
                              = "select pos_cod,pos_x,pos_y,pos_etage 
											from positions 
											where pos_x = $pos_x 
											and pos_y = $pos_y 
											and pos_etage = $etage ";
                    $stmt     = $pdo->query($req);
                    if ($stmt->rowCount() == 0)
                    {
                        echo "<p>Aucune position trouvée à ces coordonnées.<br>";
                        echo "<a href=\"", $_SERVER['PHP_SELF'], "?methode=appel&met_appel=debut\">Retour au choix des coordonnées ?</a><br>";
                        $err_depl = 1;
                    }
                    if ($err_depl == 0)
                    {
                        $message             = new message();
                        $message->sujet      = $_REQUEST['titre'];
                        $message->corps      = $_REQUEST['corps'];
                        $message->expediteur = $perso_cible;


                        // enregistrement des destinataires
                        // recherche de la position
                        $req_pos
                                      = "select pos_etage,pos_x,pos_y 
														from positions 
														where pos_x = $pos_x 
														and pos_y = $pos_y 
														and pos_etage = $etage";
                        $stmt         = $pdo->query($req_pos);
                        $result       = $stmt->fetch();
                        $pos_actuelle = $result['ppos_pos_cod'];
                        $v_x          = $result['pos_x'];
                        $v_y          = $result['pos_y'];
                        $etage        = $result['pos_etage'];
                        // rechreche des dest
                        $req_vue
                              = "select perso_cod,perso_type_perso,perso_nom from perso, perso_position, positions
														where pos_x >= ($pos_x - $distance) and pos_x <= ($pos_x + $distance)
														and pos_y >= ($pos_y - $distance) and pos_y <= ($pos_y + $distance)
														and ppos_perso_cod = perso_cod
														and perso_actif = 'O'
														and perso_type_perso = 1
														and ppos_pos_cod = pos_cod
														and pos_etage = $etage ";
                        $stmt = $pdo->query($req_vue);

                        while ($result = $stmt->fetch())
                        {
                            $message->ajouteDestinataire($result['perso_cod']);

                        }
                        $message->envoieMessage();
                        echo "<p>Votre message a été envoyé à toutes les personnes présentes à $volume de distance de vous.";
                    }


                    break;

            }
            break;
        case "palpable":
            switch ($t)
            {
                case "O":
                    $texte_evt = "Un admin quête a rendu [perso_cod1] palpable.";
                    $req       =
                        "update perso set perso_tangible = 'O',perso_nb_tour_intangible = 0 where perso_cod = $num_perso2 ";
                    break;
                case "N":
                    $req       =
                        "update perso set perso_tangible = 'N',perso_nb_tour_intangible = 4 where perso_cod = $num_perso2 ";
                    $texte_evt = "Un admin quête a rendu [perso_cod1] impalpable.";
                    break;
            }
            echo "<p>Opération effectuée !";
            $stmt = $pdo->query($req);

            insert_evt($num_perso2, $texte_evt);
            break;
    }
}
echo "<a class=<\"centrer\" href=\"", $_SERVER['PHP_SELF'], "\">Retour au début</a>";

$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
