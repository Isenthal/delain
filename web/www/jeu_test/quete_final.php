<?php
/*	$verif_connexion = new verif_connexion();
$verif_connexion->verif();
$perso_cod = $verif_connexion->perso_cod;
$compt_cod = $verif_connexion->compt_cod;*/
/*******************************************************/
/* Modifs by Merrick le 09/06/2006                     */
/* gestion du buffer pour éviter une sortie classique  */
/* modifs du rajout de po                              */
/* modif du rajout de prestige                         */
/*******************************************************/
//
// gestion du buffer
// ob_stsart empêche la sortie standard et met tout dans le buffer
//
ob_start();
//
// on regarde si le joueur est bien sur un dispensaire
$erreur = 0;
$perso = new perso;
$perso      = $verif_connexion->perso;

if (!$perso->is_lieu())
{
	echo("<p>Erreur ! Vous n'êtes pas sur un lieu !!!");
	$erreur = 1;
}
if ($erreur == 0)
{
    // On commence alors réellement l'include, permettant d'intégrer des quêtes aux bâtiments
    $tab_lieu = $perso->get_lieu();
    /*************************** Début du traitement des dispensaires ******************************/
    if ($tab_lieu['lieu']->lieu_tlieu_cod == 2)
    {
        $methode2          = get_request_var('methode2', 'debut');
        switch($methode2)
        {
            case "debut":
                //à modifier dans le cas où une quête nécessite un test sur un type d'objet particulier
                $req = "select obj_gobj_cod,perobj_obj_cod,obj_nom 
                    from objets,perso_objets 
                    where perobj_obj_cod = obj_cod 
                    and perobj_perso_cod = $perso_cod 
                    and perobj_identifie = 'O' 
                    and obj_gobj_cod in (373,374,376) order by obj_gobj_cod";
                $stmt = $pdo->query($req);
                while($result = $stmt->fetch())
                {
                    $obj_gen_quete = $result['obj_gobj_cod'];
                    $obj_quete = $result['perobj_obj_cod'];
                    $obj_nom = $result['obj_nom'];
                    if($obj_gen_quete == 373)
                    {
                        //Quête du collectionneur : cette quête a pour vocation de faire ramener un objet rare,
                        // droppé par les capitaines morbelins ou trouvé dans des cachettes
                        ?>
                        <form name="cede" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
                        <p>En arrivant dans le dispensaire, un guérisseur vous interpelle :
                        <br><em>Je crois que vous possédez un objet qui pourrait intéresser l'un des membres de notre communauté.
                        Il s'agit d'une <?php  echo $obj_nom; ?> .
                        <br>Seriez vous prêt à me la céder, pour que je la lui remette ? Dans un tel cas, je vous rétribuerais pour cela.</em>
                        <br><br>Le guérisseur attend alors votre réponse ...<br>
                        <input type="hidden" name="methode" value="cede_objet1">
                        <table>
                            <tr>
                                <td class="soustitre2">Volontiers ! Cela m'encombrait plus qu'autre chose.</td>
                                <td><input type="radio" class="vide" name="controle1" value="cede_objet1"></td>
                            </tr>
                            <tr>
                                <td class="soustitre2">Non, je préfère le garder, sait-on jamais, il pourrait me servir plus tard ...</td>
                                <td><input type="radio" class="vide" name="controle1" value="non"></td>
                            </tr>
                            <tr>
                                <td><input type="hidden" class="vide" name="obj_quete" value="<?php echo $obj_quete;?>"></td>
                            </tr>
                        </table>
                        <input type="submit" class="test" value="Valider !">
                        </form>
                        <?php
                    }
                }
                // cas particulier des écailles
                $nb_ecailles = $perso->compte_objet(182);
                if ($nb_ecailles >= 10)
                {
                    // Quête des écailles de basilic contre des parchemins. Cette quête permet d'échanger 10 écailles de basilic contre deux parchemins
                    // Particularité : posséder 10 écailles minimum, et arriver avant le 16 du mois
                    $req = "select to_char(now(),'DD') as date ";
                    $stmt2 = $pdo->query($req);
                    $result2 = $stmt2->fetch();
                    $jour = $result2['date'];
                    if ($jour < 16)
                    {
                        // Pour la réutilisabilité : Formulaire à modifier en fonction de l'objet, notamment phrase à mettre en contexte
                        ?>
                        <form name="cede" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
                        <p>Un soignant en chef du dispensaire s'approche de vous :
                        <br><em>Je vois que  vous êtes en possession d'écailles de basilic ! Celles-ci nous sont fort utile pour réaliser certaines de nos préparations. Pourriez vous nous les céder ? Nous vous en prendrons dix.</em>
                        <input type="hidden" name="methode" value="cede_objet2">
                        <table>
                            <tr>
                                <td class="soustitre2">Volontiers ! Cela m'encombrait plus qu'autre chose.</td>
                                <td><input type="radio" class="vide" name="controle2" value="cede_objet2"></td>
                            </tr>
                            <tr>
                                <td class="soustitre2">Non, je préfère le garder, sait-on jamais, elles pourraient me servir plus tard ...</td>
                                <td><input type="radio" class="vide" name="controle2" value="non"></td>
                            </tr>
                            <tr>
                                <td colspan="2"><input type="hidden" class="vide" name="obj_gen_quete"
                                value="182"></td>
                            </tr>
                        </table>
                        <input type="submit" class="test" value="Valider !">
                        </form>
                        <?php
                    }
                    else
                    {
                        echo 'Un guérisseur du dispensaire vous interpelle : <br><em>Je vois que vous possédez des écailles de basilic ! Ceci nous sert parfois pour faire nos préparations pour soigner nos patient. Mais actuellement, nous n\'en avons pas besoin. Revenez un autre jour, nous pourrons surement faire affaire !</em>';
                    }
                }
                break;
            // Résultat des quêtes
            // Quête du collectionneur
            case "cede_objet1":
                $cede_objet = $_POST['controle1'];
                if($cede_objet == 'cede_objet1')
                {
                    ?>
                    <br>Quel choix judicieux. Je vous remercie de m'avoir cédé cet objet. Je vais le donner à notre collectionneur !
                    <br> Pour votre peine, je vais puiser dans mes richesses et vous donner 3000 brouzoufs
                    <?php
                    $req = "select f_del_objet($obj_quete)";
                    $stmt = $pdo->query($req);

                    $perso->perso_po = $perso->perso_po + 3000;
                    $perso->stocke();
                }
                else if($cede_objet == 'non')
                {
                    ?>
                    <br>Ceci est particulièrement dommage. Vous auriez pu en retirer tant d'avantages ...
                    <?php
                }
                else
                {
                    ?>
                    <br>Pensez vous que ce soit bien malin de tenter de tricher ?
                    <?php
                }
                break;
            case "cede_objet2":
                 // Quête des écailles
                $cede_objet = $_POST['controle2'];
                if($cede_objet == 'cede_objet2')
                {
                    ?>
                    <br>"Nous vous remercions pour votre geste. Cela nous permettra surement de sauver de nombreux patients
                    <br>Pour marquer votre offrande, nous allons vous offrir un petit quelque chose au regard de nos maigres moyens."
                    <br><em>En regardant à nouveau votre inventaire, vous pouvez voir que deux parchemins s'y sont glissés. Un certain sentiment de fierté pour cette bonne action vous envahit.</em>
                    <?php
                    // On crée deux parchemins différents dans l'inventaire du perso
                    $req = "select lancer_des(1,6) as des ";
                    $stmt = $pdo->query($req);
                    $result = $stmt->fetch();
                    $parchemin = 362 + $result['des'];
                    $req2 = "select cree_objet_perso_nombre('$parchemin','$perso_cod','1')";
                    $stmt = $pdo->query($req2);
                    $result = $stmt->fetch();
                    $req = "select lancer_des(1,6) as des ";
                    $stmt = $pdo->query($req);
                    $result = $stmt->fetch();
                    $parchemin = 362 + $result['des'];
                    $req2 = "select cree_objet_perso_nombre('$parchemin','$perso_cod','1')";
                    $stmt = $pdo->query($req2);
                    $result = $stmt->fetch();
                    $req3 = "select vente_ecailles($perso_cod,$obj_gen_quete)";
                    $stmt = $pdo->query($req3);
                    $result = $stmt->fetch();
                }
                else if($cede_objet == 'non')
                {
                    ?>
                    <br>Nous sommes navrés de ce choix. De nombreux patients risquent de mourir du fait de votre geste ...
                    <?php
                }
                else
                {
                    ?>
                    <br>Pensez vous que ce soit bien malin de tenter de tricher ?
                    <?php
                }
                break;
        }
    }
/*************************** Fin du traitement des dispensaires ******************************/	

/*************************** Début du traitement des auberges ******************************/	
            if ($tab_lieu['type_lieu'] == 4)
            {
                    $methode2          = get_request_var('methode2', 'debut');
                    switch($methode2)
                    {
                            case "debut":
                            //Cas de l'introduction de la quête7 pour les persos à haut niveau de Points de prestige
                            // Ce passage permet de donner à certains persos l'emplacement des cachettes

                            $prestige = $perso->perso_prestige;
                            $nom = $perso->perso_nom;
                            if ($prestige >= 10 and $prestige <= 20)
                            {
                                srand ((double) microtime() * 10000000); // pour intialiser le random
                                $input = array (
                                "<br>Alors que vous rentrez dans cette auberge, une sorte d'ivrogne s'approche de vous :
                                <br><em> Et dîtes, ch'vous connais vous ! vous n'seriez pas <? $nom ?> ? Ou alors Graspork ?
                                <br>pfff, encore un de ces espèces de %#$£^ù qui cherche la gloire à tous les étages !
                                <br>En plus, j'suis sûr que vous n'connaissez même pas les bons coins	qui font la gloire !
                                <br>allez, si vous m'payez un coup à boire, j'vous diraisz un secret. Il parait qu'il y a une drole de grotte ",
                                "<br>Et vous là, vous avez une tête qui m'revient. A moins que ce ne soit sur une affiche de la Milice que je vous ai vu ...",
                                "<br>Vous surprenez une conversation, plongé dans votre verre :
                                <br><em>Un jour, en cherchant un morceau de ferraille pour le forgeron du coin, j'suis tombé sur une cachette !
                                <br>J'me souviens plus bien où c'était mais franchement, y'avait un max à se faire !</em>",
                                "",
                                "",
                                ""
                                );
                                // Rajouter la position de cachettes réelles pour donner les indications
                                $cachette = array_rand ($input, 1);
                                $phrase = $input[$cachette];
                            }
                            else if ($prestige > 20)
                            {
                                ?>
                                <hr><br>Un homme s'approche de vous, et vous interpelle :
                                <br><em>" Il me semble vous reconnaître ! Ne seriez vous pas <?php  echo $nom; ?> ? Je ne pense pas me tromper.
                                <br>Vos exploits sont contés deci delà, et nul ne les ignore maintenant. Je m'en vais diffuser la nouvelle de votre venue en cet endroit !
                                <?php
                            }
                            break;
                    }
            }
/*************************** Fin du traitement des auberges ******************************/					

/*************************** Début du traitement des banques ******************************/					
            if ($tab_lieu['type_lieu'] == 1)
            {
                    $methode2          = get_request_var('methode2', 'debut');
                    switch($methode2)
                    {
                            case "debut":

                                $req = "select obj_gobj_cod,perobj_obj_cod,obj_nom from objets,perso_objets where perobj_obj_cod = obj_cod and perobj_perso_cod = $perso_cod and perobj_identifie = 'O' and obj_gobj_cod in ('380') order by obj_gobj_cod ";
                                $stmt = $pdo->query($req);
                                        while($result = $stmt->fetch())
                                        {
                                        $obj_gen_quete = $result['obj_gobj_cod'];
                                        $obj_quete = $result['perobj_obj_cod'];
                                        $obj_nom = $result['obj_nom'];
                                                if
                                                ($obj_gen_quete == 380)
                                                //Quête du forgeron Trelmar Mogresh ayant perdu ses caisses de minerais volées par des brigands
                                                {
                                                $nb_caisses = $perso->compte_objet(380);
                                                ?>
                                                <form name="cede" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
                                                <p><em>Vous voilà bien chargé cher Monsieur. Souhaitez vous faire un dépôt ?
                                                <br>Ou alors peut-être souhaiteriez vous vous délester de ces lourdes caisses ? (Vous êtes en possession de <?php  echo $nb_caisses; ?> caisses)
                                                <br>Dans ce cas, posez donc ces caisses dans ce coin, nous transmettrons cet échange à leur propriétaire, qui vous remettra la récompense promise ...</em>
                                                        <input type="hidden" name="methode" value="cede_objet1">
                                                        <table>
                                                        <tr>
                                                            <td class="soustitre2">Poser les caisses à la banque</td>
                                                            <td><input type="radio" class="vide" name="controle1" value="cede_objet1"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="soustitre2">Non, finalement, je vais les garder. Je pourrais en retirer plus de brouzoufs plus tard ...</td>
                                                            <td><input type="radio" class="vide" name="controle1" value="non"></td>
                                                        </tr>
                                                        <tr>
                                                            <td><input type="hidden" class="vide" name="obj_gen_quete" value="<?php echo $obj_gen_quete;?>"></td>
                                                        </tr>
                                                        <tr>
                                                            <td><input type="hidden" class="vide" name="nb_caisses" value="<?php echo $nb_caisses;?>"></td>
                                                        </tr>
                                                        </table>
                                                        <input type="submit" class="test" value="Valider !">
                                                        </form>
                                                <?php
                                                }
                                                break;
                                        }
                                break;

                                //Résultat Quête du forgeron Trelmar Mogresh
                                case "cede_objet1":
                                $cede_objet = $_POST['controle1'];
                                if($cede_objet == 'cede_objet1')
                                {
                                        ?>
                                        <br>Nous vous remercions.
                                        <br>Votre dépôt sera signalé au propriétaire de ces objets. Une dépêche est partie de votre part (<em>message visible dans votre boîte d'envoi</em>)
                                        <br>Il vous contactera certainement rapidement pour la récompense.
                                        <?php
                                        $req = "select vente_caisses($perso_cod,$obj_gen_quete)";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();

                                        $perso->perso_prestige = $perso->perso_prestige +1;
                                        $perso->stocke();


                                        //
                                        $corps = "Livraison effectuée - STOP - transmettre propriétaire - STOP - Cargaison bon état - STOP -" . $nb_caisses . " caisses livrées - STOP - Fin Transmission - STOP -
                                        ";

                                        $titre = "Livraison Caisses";

                                        $message->sujet      = $titre;
            $message->corps      = $corps;
            $message->expediteur = $perso_cod;
            $message->ajouteDestinataire(200017);
            $message->envoieMessage();


                                }
                                else if($cede_objet == 'non')
                                {
                                        ?>
                                        <br>Quelle étrange attitude de s'encombrer de tels objets ...
                                        <br>Vous changerez peut-être d'idée plus tard ...
                                        <?php
                                }
                                // on gère le cas où il y a risque de triche
                                else
                                {
                                        ?>
                                        <br>Pensez vous que ce soit bien malin de tenter de tricher ?
                                        <?php
                                }
                            break;
                    }
            }

/*************************** Fin du traitement des banques ******************************/

/*************************** Début du traitement des temples ******************************/	
            if ($tab_lieu['type_lieu'] == 17)
            {

                    $methode2          = get_request_var('methode2', 'debut');
                    switch($methode2)
                    {
                            case "debut":
                            //Quête de la guerre des Dieux.
                            //On Sélectionne le Dieu qui est concerné (le dieu du temple dans lequel on est)
                            $req = "select lieu_dieu_cod from lieu,lieu_position,perso_position 
                                            where lieu_cod = lpos_lieu_cod
                                            and lpos_pos_cod = ppos_pos_cod
                                            and ppos_perso_cod = $perso_cod";
                            $stmt = $pdo->query($req);
                            $result = $stmt->fetch();
                            $dieu = $result['lieu_dieu_cod'];

                            if
                            //Cas des temples de Balgur
                            ($dieu == 2)
                                        {
                                        // Test sur la foi du perso (pas les novices) et l'or pour payer
                                        $req = "select perso_po,dper_dieu_cod from perso,dieu_perso 
                                                        where perso_cod = $perso_cod
                                                        and dper_perso_cod = $perso_cod
                                                        and dper_niveau > 0";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        $brouzoufs = $result['perso_po'];
                                        $dieu_perso = $result['dper_dieu_cod'];
                                        if
                                        ($brouzoufs > 1001 and $dieu_perso == 2)
                                                        {
                                                        $req = "select pquete_nombre from quete_perso 
                                                                        where pquete_quete_cod = 7 
                                                                        and pquete_perso_cod = $perso_cod";
                                                        $stmt = $pdo->query($req);
                                                        $result = $stmt->fetch();
                                                        $quete7 = $result['pquete_nombre'];
                                                        // On est dans la première étape de la quête, jamais réalisée
                                                        if ($stmt->rowCount() == 0)
                                                                {
                                                                ?>
                                                                <p><br><br>Votre statut vous permet de réaliser <strong><a href="<?php echo  $_SERVER['PHP_SELF']; ?>?methode2=dieu2_quete7">une dévotion à Balgur.</a></strong>
                                                                <br>Cela vous en coutera un millier de brouzoufs pour les offrandes. Certains affirment avoir eu des visions en rentrant en transe, et entendre les paroles de Balgur.
                                                                <br>Mais cela peut aussi comporter des risques. D'autres ont subit des séquelles...
                                                                <?php
                                                                }
                                                        // On est dans la deuxième étape de la quête, après découverte de la première cachette
                                                        else if ($quete7 == 2)
                                                                {
                                                                ?>
                                                                <p><br><br>Votre statut vous permet de réaliser <strong><a href="<?php echo  $_SERVER['PHP_SELF']; ?>?methode2=dieu2_quete7_2">une dévotion à Balgur.</a></strong>
                                                                <br>Cela vous en coutera un millier de brouzoufs pour les offrandes. Certains affirment avoir eu des visions en rentrant en transe.
                                                                <br>Mais cela peut aussi comporter des risques. D'autres ont subit des séquelles...
                                                                <?php
                                                                }

                                                        }
                                        }
                            //Fin cas des temples de Balgur

                            else if
                            //Cas des temples d'Elian
                            ($dieu == 4)
                                        {
                                        // Test sur la foi du perso (pas les novices) et l'or pour payer
                                        $req = "select perso_po,dper_dieu_cod from perso,dieu_perso 
                                                        where perso_cod = $perso_cod
                                                        and dper_perso_cod = $perso_cod
                                                        and dper_niveau > 0";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        $brouzoufs = $result['perso_po'];
                                        $dieu_perso = $result['dper_dieu_cod'];
                                        if ($brouzoufs > 1001 and $dieu_perso == 4)
                                                        {
                                                        $req = "select pquete_nombre from quete_perso 
                                                                        where pquete_quete_cod = 8 
                                                                        and pquete_perso_cod = $perso_cod";
                                                        $stmt = $pdo->query($req);
                                                        $result = $stmt->fetch();
                                                        $quete8 = $result['pquete_nombre'];
                                                        // On est dans la première étape de la quête, jamais réalisée
                                                        if ($stmt->rowCount() == 0)
                                                                    {
                                                                    ?>
                                                                    <p><br><br>Votre statut vous permet de réaliser <strong><a href="<?php echo  $_SERVER['PHP_SELF']; ?>?methode2=dieu4_quete8">une dévotion à Elian.</a></strong>
                                                                    <br>Cela vous en coutera un millier de brouzoufs pour les offrandes. Certains affirment être entré en contact avec Elian lors de leur transe.
                                                                    <br>Mais cela peut aussi comporter des risques. D'autres ont subit des séquelles...
                                                                    <?php
                                                                    }
                                                        // On est dans la deuxième étape de la quête, quête en cours
                                                        else if ($quete8 == 2)
                                                                    {
                                                                    ?>
                                                                    <p><br><br>Votre statut vous permet de réaliser <strong><a href="<?php echo  $_SERVER['PHP_SELF']; ?>?methode2=dieu4_quete8_2">une dévotion à Elian.</a></strong>
                                                                    <br>Cela vous en coutera un millier de brouzoufs pour les offrandes. Certains affirment avoir eu des visions en rentrant en transe.
                                                                    <br>Mais cela peut aussi comporter des risques.
                                                                    <?php
                                                                    }
                                                        }
                                        }
                            //Fin cas des temples d'Elian

                            else if
                            //Cas des temples de Io
                            ($dieu == 1)
                                        {
                                        // Test sur la foi du perso (pas les novices) et l'or pour payer
                                        $req = "select perso_po,dper_dieu_cod from perso,dieu_perso 
                                                        where perso_cod = $perso_cod
                                                        and dper_perso_cod = $perso_cod
                                                        and dper_niveau > 0";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        $brouzoufs = $result['perso_po'];
                                        $dieu_perso = $result['dper_dieu_cod'];
                                        if ($brouzoufs > 1001 and $dieu_perso == 1)
                                                        {
                                                        $req = "select pquete_nombre from quete_perso 
                                                                        where pquete_quete_cod = 10 
                                                                        and pquete_perso_cod = $perso_cod";
                                                        $stmt = $pdo->query($req);
                                                        $result = $stmt->fetch();
                                                        $quete10 = $result['pquete_nombre'];
                                                        // On est dans la première étape de la quête, jamais réalisée
                                                        if ($stmt->rowCount() == 0)
                                                                    {
                                                                    ?>
                                                                    <p><br><br>Votre statut vous permet de réaliser <strong><a href="<?php echo  $_SERVER['PHP_SELF']; ?>?methode2=dieu1_quete10">une dévotion à Io.</a></strong>
                                                                    <br>Cela vous en coutera un millier de brouzoufs pour les offrandes. L'Aveugle offre parfois des paroles à ceux qui atteignent l'état de transe.
                                                                    <br>Mais cela peut aussi comporter des risques. Certains ont subit des séquelles suite à cette expérience.
                                                                    <?php
                                                                    }
                                                        // On est dans la deuxième étape de la quête, quête en cours
                                                        else if ($quete10 == 2)
                                                                    {
                                                                    ?>
                                                                    <p><br><br>Votre statut vous permet de réaliser <strong><a href="<?php echo  $_SERVER['PHP_SELF']; ?>?methode2=dieu1_quete10_2">une dévotion à Io.</a></strong>
                                                                    <br>Cela vous en coutera un millier de brouzoufs pour les offrandes. Certains affirment avoir eu des visions en rentrant en transe.
                                                                    <br>Mais cela peut aussi comporter des risques.
                                                                    <?php
                                                                    }
                                                        }
                                    }
                            //Fin cas des temples de de Io
                            break;


/**************************** Résolution des différents cas des Dieux **********************************************************/
                            //Balgur
                            case "dieu2_quete7": // Balgur premières dévotions

                            $des = rand (1,10);
                            if ($des < 3)
                                        {
                                        ?>
                                        <hr>Une douleur très violente se fait ressentir dans votre tête !
                                        <br>Vous n'êtes pas capable de canaliser cette puissance, et la douleur en arrive à devenir physique.
                                        <br>Vous vous écroulez, du sang coulant de vos narines.
                                        <?php
                                        $perso->perso_pv = $perso->perso_pv - 5;
                                        $perso->stocke();
                                        }
                            else
                                        {
                                        srand ((double) microtime() * 10000000); // pour intialiser le random
                                        $input = array (
                                                "le pays des pas perdus, au sud est, dans le cul de sac mais pas tout au bout, en longeant le mur ouest",
                                                "le nord est, à dix positions de lescalier sud, et huit positions de lescalier est.",
                                                "le sud ouest du pays des gelées, tout au sud ouest, quatres points sombres sont marqués, le plus au sud sera lendroit recherché",
                                                "le colimaçon du pays des flammes et des démons qui a perdu sa forme, dans la petite salle en dessous du dispensaire",
                                                "lest de la Moria, la croix du milieu en son centre le secret tu trouveras."
                                        );
                                        // Rajouter la position de cachettes réelles pour donner les indications

                                        $cachette = array_rand ($input, 1);
                                        $nom_cachette = $input[$cachette];
                                        ?>
                                        <hr>Une voix résonne dans votre tête. Profonde et grave, elle ne semble pas interrompre les prières des moines présents
                                        <br>Calmement, les mots se font plus précis et clairs :
                                        <br><br><strong><em>Maintenant tu dois trouver ma parole ailleurs. Cherche ton chemin vers <?php  echo $nom_cachette; ?>.
                                        <br>La vérité tu trouveras, mais cachée elle sera. Barrer la route aux imprudents nous devons.</em></strong>
                                        <?php
                                        //Mise à jour de l'étape terminée pour passer à la cachette
                                        $req = "insert into quete_perso 
                                                                values (default, '7', $perso_cod,'1')";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        }
                            //Mise à jour des brouzoufs dans tous les cas
                            $req = "update perso set perso_po = perso_po - 1000 
                                                        where perso_cod = $perso_cod";
                            $stmt = $pdo->query($req);
                            $result = $stmt->fetch();
                            break; // Fin du traitement Etape 1 de Balgur
/***********************************************************************************************************/								
                            case "dieu2_quete7_2": // Balgur deuxièmes dévotions
                            $des = rand (1,10);
                            if ($des < 3)
                                        {
                                        ?>
                                        <hr>Une douleur très violente se fait ressentir dans votre tête !
                                        <br>Vous n'êtes pas capable de canaliser cette puissance, et la douleur en arrive à devenir physique.
                                        <br>Vous vous écroulez, du sang coulant de vos narines.
                                        <?php
                                        $perso->perso_pv = $perso->perso_pv - 5;
                                        $perso->stocke();
                                        }
                            else
                                        {
                                        srand ((double) microtime() * 10000000); // pour intialiser le random
                                        $input = array (
                                                "le pays des pas perdus, au nord ouest, en dessous des escaliers qui descendent, derrière le mur",
                                                "le pays des morts-vivants, au nord ouest, en longeant le crâne par lextérieur, le haut du côté gauche",
                                                "lest du pays des gelées, juste à côté dun cailloux doré",
                                                "le nord-ouest du pays des flammes et des démons, en longeant le bord avant le cul de sac.",
                                                "le château, au sud un dispensaire, treize pas de plus et tu y seras.",
                                                "le sud ouest de la Moria, la dernière croix vers son centre la vérité tu trouveras."
                                        );
                                        // Rajouter la position de cachettes réelles pour donner les indications
                                        //Rajouter les autres implications de la quête (incrémentation, diminution des brouzoufs ...)

                                        $cachette = array_rand ($input, 1);
                                        $nom_cachette = $input[$cachette];
                                        ?>
                                        <hr>Une voix résonne dans votre tête. Profonde et grave, elle ne semble pas interrompre les prières des moines présents
                                        <br>Calmement, les mots se font plus précis et clairs :
                                        <br><br><strong><em>Tu as poursuivis mon but. Tu es un fidèle parmis les fidèles.
                                        <br>Mais la tache n'est pas finie. Le plus dur reste à faire. L'Aveugle tu dois convaincre.</em></strong>
                                        <?php
                                        //Mise à jour de l'étape pour finaliser la quête 7
                                        $req = "update quete_perso 
                                                        set pquete_nombre = 3 
                                                        where pquete_perso_cod = $perso_cod 
                                                        and pquete_quete_cod = 7;";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        }
                            //Mise à jour des brouzoufs
                            $req = "update perso set perso_po = perso_po - 1000 
                                                        where perso_cod = $perso_cod";
                            $stmt = $pdo->query($req);
                            $result = $stmt->fetch();
                            break; // Fin du traitement Etape 2 de Balgur
                            // Fin du traitement de Balgur pour la quête 7
/***********************************************************************************************************/								
                            case "dieu7": //Falis
                            break; // Fin du traitement de Falis
/***********************************************************************************************************/								
                            case "dieu4_quete8": //Elian, premières dévotions
                            $des = rand (1,10);
                            if ($des < 3)
                                        {
                                        ?>
                                        <hr>Une douleur très violente se fait ressentir dans votre tête !
                                        <br>Vous n'êtes pas capable de canaliser cette puissance, et la douleur en arrive à devenir physique.
                                        <br>Vous vous écroulez, du sang coulant de vos narines.
                                        <?php
                                        $perso->perso_pv = $perso->perso_pv - 5;
                                        $perso->stocke();
                                        }
                            else
                                        {
                                        srand ((double) microtime() * 10000000); // pour intialiser le random
                                        $input = array (
                                                "les premiers sous sols, au nord ouest, dans un coin intérieur de mur.",
                                                "le pays des morts-vivants, au sud ouest, à lhorizontal de la croix sur le bord de ce territoire. Au sud est se trouvera un dispensaire.",
                                                "le pays des flammes et des démons, au sud, avec un dispensaire et un temple, le triangle tu marqueras, en direction du de louest.",
                                                "le château, au nord un dispensaire, deux pas de plus et tu y seras.",
                                                "la deuxième salle des orks, au nord, un recoin tattendra."
                                        );
                                        //Les cachettes donnent l'indication de l'étape suivante
                                        $cachette = array_rand ($input, 1);
                                        $nom_cachette = $input[$cachette];
                                        ?>
                                        <hr>Une voix résonne dans votre tête. Profonde et grave, elle ne semble pas interrompre les prières des moines présents
                                        <br>Calmement, les mots se font plus précis et clairs :
                                        <br><br><strong><em>Maintenant tu dois trouver ma parole ailleurs. Cherche ton chemin vers <?php  echo $nom_cachette; ?>.
                                        <br>Des choix pour le futur seront réalisés. La Justice guidera tes actes.</em></strong>
                                        <?php
                                        //Mise à jour de l'étape terminée pour passer à la cachette
                                        $req = "insert into quete_perso 
                                                                values (default, '8', $perso_cod,'1')";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        }
                            //Mise à jour des brouzoufs dans tous les cas
                            $req = "update perso set perso_po = perso_po - 1000 
                                            where perso_cod = $perso_cod";
                            $stmt = $pdo->query($req);
                            $result = $stmt->fetch();
                            break; // Fin du traitement Etape 1 d'Elian

                            case "dieu4_quete8_2": //Elian, deuxièmes dévotions
                            $des = rand (1,10);
                            if ($des < 3)
                                        {
                                        ?>
                                        <hr>Une douleur très violente se fait ressentir dans votre tête !
                                        <br>Vous n'êtes pas capable de canaliser cette puissance, et la douleur en arrive à devenir physique.
                                        <br>Vous vous écroulez, du sang coulant de vos narines.
                                        <?php
                                        $perso->perso_pv = $perso->perso_pv - 5;
                                        $perso->stocke();
                                        }
                            else
                                        {
                                        srand ((double) microtime() * 10000000); // pour intialiser le random
                                        $input = array (
                                        "le colimaçon du pays des flammes et des démons qui a perdu sa forme, juste à côté du dispensaire.",
                                        "le nord du pays des gelées, afin de se trouver juste à l'ouest du dispensaire"
                                        );
                                        // Rajouter la position de cachettes réelles pour donner les indications
                                        $cachette = array_rand ($input, 1);
                                        $nom_cachette = $input[$cachette];
                                        ?>
                                        <hr>Une voix résonne dans votre tête. Profonde et grave, elle ne semble pas interrompre les prières des moines présents
                                        <br>Calmement, les mots se font plus précis et clairs :
                                        <br><br><strong><em>Maintenant tu dois trouver ma parole ailleurs. Cherche ton chemin vers <?php  echo $nom_cachette; ?>.
                                        <br>La vérité tu trouveras, mais cachée elle sera, et vers d'autres les réponses tu chercheras.</em></strong>
                                        <?php
                                        //Cloture de cette étape pour ne pas la retrouver ensuite
                                        $req = "update quete_perso 
                                                        set pquete_nombre = 3 
                                                        where pquete_perso_cod = $perso_cod 
                                                        and pquete_quete_cod = 8;";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        //Mise à jour de l'étape terminée pour passer à la cachette
                                        $req = "insert into quete_perso 
                                                                values (default, '9', $perso_cod,'1');";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        }
                            //Mise à jour des brouzoufs
                            $req = "update perso set perso_po = perso_po - 1000 
                                            where perso_cod = $perso_cod";
                            $stmt = $pdo->query($req);
                            $result = $stmt->fetch();
                            break; // Fin du traitement Etape 2 d'Elian
                            //Fin globale du traitement d'Elian
/***********************************************************************************************************/

                            case "dieu1_quete10": // Io, premières dévotions
                            $des = rand (1,10);
                            if ($des < 3)
                                        {
                                        ?>
                                        <hr>Une douleur très violente se fait ressentir dans votre tête !
                                        <br>Vous n'êtes pas capable de canaliser cette puissance, et la douleur en arrive à devenir physique.
                                        <br>Vous vous écroulez, du sang coulant de vos narines.
                                        <?php

                                        $perso->perso_pv = $perso->perso_pv - 5;
                                        $perso->stocke();
                                        }
                            else
                                        {
                                        srand ((double) microtime() * 10000000); // pour intialiser le random
                                        $input = array (
                                        "le colimaçon du pays des flammes et des démons qui a perdu sa forme, juste à côté du dispensaire.",
                                        "le nord du pays des gelées, afin de se trouver juste au sud du dispensaire"
                                        );
                                        // Rajouter la position de cachettes réelles pour donner les indications
                                        $cachette = array_rand ($input, 1);
                                        $nom_cachette = $input[$cachette];
                                        ?>
                                        <hr>Une voix résonne dans votre tête. Profonde et grave, elle ne semble pas interrompre les prières des moines présents
                                        <br>Calmement, les mots se font plus précis et clairs :
                                        <br><br><strong><em>Cherche en direction du  <?php  echo $nom_cachette; ?>.
                                        <br>Tu pourras trouver la lumière qui guide l'Aveugle</em></strong>
                                        <?php
                                        //Mise à jour de l'étape terminée pour passer à la cachette
                                        $req = "insert into quete_perso 
                                                                values (default, '10', $perso_cod,'1')";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        }
                            //Mise à jour des brouzoufs

                            $perso->perso_po = $perso->perso_po - 1000;
                                        $perso->stocke();
                            break; // Fin du traitement de Io Etape 1
/***********************************************************************************************************/										
                            case "dieu1_quete10_2": // Io deuxièmes dévotions
                            $des = rand (1,10);
                            if ($des < 3)
                                        {
                                        ?>
                                        <hr>Une douleur très violente se fait ressentir dans votre tête !
                                        <br>Vous n'êtes pas capable de canaliser cette puissance, et la douleur en arrive à devenir physique.
                                        <br>Vous vous écroulez, du sang coulant de vos narines.
                                        <?php

                                        $perso->perso_pv = $perso->perso_pv - 5;
                                        $perso->stocke();
                                        }
                            else
                                        {
                                        srand ((double) microtime() * 10000000); // pour intialiser le random
                                        $input = array ("le sud ouest du crane", "l'est bien sûr", "le nord, ça ne vous dit pas ?");

                                        //****************************************************************************
                                        // Rajouter la position de cachettes réelles pour donner les indications
                                        //****************************************************************************

                                        $cachette = array_rand ($input, 1);
                                        $nom_cachette = $input[$cachette];
                                        ?>
                                        <hr>Votre dévotion ne semble pas produire les effets escomptés. Io n'entends sans doute pas encore votre message.
                                        <br>Sa décision est sans doute lourde de conséquences, et la Balance est son approche, un pas d'un côté modifie forcément l'équilibre ...
                                        <?php

                                        /*A ne mettre en ligne que lorsque tout sera prêt pour le final
                                        ?>
                                        <hr>Une voix résonne dans votre tête. Profonde et grave, elle ne semble pas interrompre les prières des moines présents
                                        <br>Calmement, les mots se font plus précis et clairs :
                                        <br><br><strong><em>Tu as donc ouvert tes yeux sur le futur.
                                        <br>Tu vas devoir choisir lequel il sera.
                                        <br>Pour ça, en XXXXX tu guideras, et le futur tu détermineras.
                                        </em></strong>
                                        <?
                                        //Mise à jour de l'étape terminée pour cloturer cette étape
                                        $req = "update quete_perso
                                                        set pquete_nombre = 3
                                                        where pquete_perso_cod = $perso_cod
                                                        and pquete_quete_cod = 10;";
                                        $stmt = $pdo->query($req);
                                        $result = $stmt->fetch();
                                        Fin mise en commentaire à supprimer*/

                                        }
                            //Mise à jour des brouzoufs

                            $perso->perso_po = $perso->perso_po - 1000;
                            $perso->stocke();
                            break; // Fin du traitement de Io Etape
                            // Fin global du traitement de Io
/***********************************************************************************************************/																						
                    }
/*************************** Fin du switch de méthode pour les temples *************************************/																	
            }
/*************************** Fin du traitement des temples *************************************************/	


/*************************** Début du traitement des bâtiments en construction******************************/	
/*A ne mettre en ligne que lorsque tout sera prêt pour le final*/
/*					if ($tab_lieu['type_lieu'] == 18)
                        {
                        $req = "select lpos_lieu_cod from lieu_position,perso_position
                                                    where ppos_perso_cod = $perso_cod
                                                    and lpos_pos_cod = ppos_pos_cod";
                        $stmt = $pdo->query($req);
                        $result = $stmt->fetch();
                        $lieu = $result['lpos_lieu_cod'];

                        //En fonction du dieu, résultat différent ==> à mettre dans le case de résolution
                        $req = "select dper_dieu_cod from perso,dieu_perso
                                                    where perso_cod = $perso_cod
                                                    and dper_perso_cod = $perso_cod";
                        $stmt = $pdo->query($req);
                        $result = $stmt->fetch();
                        $dieu = $result['dper_dieu_cod'];

                        //Modifier le lieu quand il sera créé ***Attention ! Batiment en construction obligatoire***
                        if ($lieu = XXXX)
                                    {

                                    if ($dieu = 2)

                                        //Quêtes des Dieux  : Le final du mot de passe
                                            $req = "select pquete_quete_cod,pquete_perso_cod,pquete_nombre
                                                                        from quete_perso
                                                                        where pquete_perso_cod = $perso_cod";
                                            $stmt = $pdo->query($req);
                                                    while($result = $stmt->fetch())
                                                    {
                                                    $quete = $result['pquete_quete_cod'];
                                                    $perso = $result['pquete_perso_cod'];
                                                    $quete_nombre = $result['pquete_nombre'];
                                                            if
                                                            ($quete == 7 and $quete_nombre > 1)
                                                            {
                                                            // Quête des Dieux : le mot de passe
                                                            // A partir d'un poème vu dans plusieurs cachettes, et d'un code obtenu dans un dispensaire les aventuriers doivent reconstituer un mot de passe
                                                                    ?>
                                                                    <form name="cede" method="post" action="<?=$_SERVER['PHP_SELF'];?>">
                                                                    <p>L'endroit est totalement sordide. Vous pouvez en faire le tour rapidement, et observer deci delà des marques de construction d'un autre temps.
                                                                    <br>Les ouvrages ne sont pas terminés, et parfois, des morceaux d'instruments sont encore visibles au sol.
                                                                    <br>En fouillant un peu, ou en déplaçant des petits tas de poussière, vous apercevez des ossements. Pas d'arme, juste des instruments et des ossements ...
                                                                    <br>
                                                                    <br>Au détour d'un petit monticule, vous êtes brusquement surpris par un ectoplasme, qui semble vous dévisager, si ce n'était ces cavités à la place des yeux.
                                                                    <br>Il vous observe quelques instants, et commence à vous parler ;
                                                                    <br><em>Vous portez les stigmates d'une récente épreuve. Je peux le sentir. Vous êtes donc à même de tenter cette nouvelle épreuve. Mais saurez vous m'indiquer ce qui pourra vous faire avancer ?</em>
                                                                    <input type="hidden" name="methode" value="password">
                                                                    <table>
                                                                    <tr>
                                                                        <td class="soustitre2">Test</td>
                                                                        <td><input type="text" name="passe"></td>
                                                                    </tr>
                                                                    <tr>
                                                                    <td><input type="hidden" class="vide" name="obj_quete" value="<?=$quete;?>"></td>
                                                                    </tr>
                                                                    </table>
                                                                    <input type="submit" class="test" value="Valider !">
                                                                    </form>
                                                                    <?
                                                            }
                                                    }
                                    }
                        }
                        break;

                                                    // Quête du mot de passe
                        case "password":
                                $passe = $_POST['passe'];
                                if($passe == 'souvenir')
                                {
                                        ?>
                                        <br>bingo
                                        <?
                                        //rajouter une incrémentation des points de prestige pour cette quête
                                        $req = 'insert into quete_perso values (DEFAULT,\'7\',\'' . $perso_cod . '\',\'1\')';
                                        $stmt = $pdo->query($req);
                                        $req2 = 'select quete_nom from quetes,quete_perso
                                                                    where quete_cod = pquete_quete_cod
                                                                    and pquete_perso_cod = ' . $perso_cod;
                                        $stmt = $pdo->query($req2);
                                        $result = $stmt->fetch();
                                        $nom_quete = $result['obj_gobj_cod'];
                                        echo '<br>vous venez de terminer la quête ' . $nom_quete;
                                }
                                else
                                {
                                        ?>
                                        <br>dommage
                                        <?
                                }
                                break;*/
/*************************** Fin du traitement des bâtiments en construction******************************/
    }

// 
// gestion du buffer
//
// on met tout le contenu du buffer dans une variable
$sortie_quete = ob_get_contents();
// on vide maintenant le buffer
ob_end_clean();
