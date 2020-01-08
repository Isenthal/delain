<?php

session_start();
$verif_auth = false;

//
// fonction d'affichage du formulaire de login si pas authentifié
//
function montre_formulaire_connexion($isAuthOk)
{
    $resultat = "";

    /* if ($erreur !== "")
      {
      $resultat .= '<strong>Erreur !</strong> ' . $erreur . '<br />';
      } */

    if (!$isAuthOk)
    {
        $resultat .= '<strong><a href="formu_cree_compte.php" style="font-size:1.1em">Créer un compte</a></strong><br /><br />Se connecter :<br />';
        $resultat .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
				Login<br />
				<input type="text" name="username" size="15"><br />
				Password<br />
				<input type="password" name="password" size="15"><br /><br />
				<input type="submit" class="test" value="Valider !"><br /><br />
				<a href="renvoi_mdp.php" target="droite">Mot de passe oublié ? </a>
			</form>';
    }
    else
    {
        global $compt_nom;
        $resultat .= '<strong>' . $compt_nom . '</strong><div style="margin:5px">
			<strong><img src="http://www.jdr-delain.net/images/attaquer.gif" title="Jouer" />&nbsp;<a href="validation_login2.php">Jouer</a></strong><hr style="margin-left:5px;margin-right:5px"/>
			<img src="http://www.jdr-delain.net/images/deconnection.gif" title="Se déconnecter" />&nbsp;<a href="logout.php" target="_top">Se déconnecter</a></div>';
    }
    return $resultat;
}

$pdo = new bddpdo;

$compte = new compte;
$perso  = new perso;

// si on change perso, il faut le faire tout de suite !
// on passe par du pg_query standard pour ne pas bousculer les classes


$normal_auth = false;
$myAuth      = new myauth;
$myAuth->start();
if (!$myAuth->verif_auth)
{
    // est-ce qu'on vient de recevoir des infos de formulaire ?
    if (isset($_POST['username']) && isset($_POST['password']))
    {
        // si oui, on checke
        if ($compte->getByLoginPassword($_POST['username'], $_POST['password']))
        {
            // le check est bien passé, on stocke la session
            $myAuth->stocke($compte->compt_cod);
            $verif_auth  = true;
            $normal_auth = true;
            $compt_nom   = $compte->compt_nom;
            $compt_cod   = $compte->compt_cod;

            // on ajoute le token pour la suite
            $auth_token = new auth_token();
            $api_token = $auth_token->create_token($compte);
            $_SESSION['api_token'] = $api_token;
            setcookie("api_token", $api_token, time() + 36000, "/", G_URL);

            // est-ce qu'on change de perso ?
            if (isset($change_perso))
            {
                if ($compte->autoriseJouePerso($change_perso))
                {
                    $compte->compt_der_perso_cod = $change_perso;
                    $compte->stocke();
                }
                else
                {
                    die('Accès interdit à ce perso');
                }

            }
            //-----------------------------------------------------------------------------------//
            // à partir d'ici, on va initialiser les variables nécessaires à la poursuite du jeu //
            //-----------------------------------------------------------------------------------//
            $type_perso       = 'joueur';
            $is_admin_monstre = false;
            $is_admin         = false;
            if ($compte->compt_monstre == 'O')
            {
                $type_perso       = 'monstre';
                $is_admin_monstre = true;
            }
            if ($compte->compt_admin == 'O')
            {
                $type_perso = 'admin';
                $is_admin   = true;
            }
            /*if (!$perso->getByComptDerPerso($compte->compt_cod))
            {
                echo 'Authentification échouée, erreur sur le chargement de perso';
                $verif_auth = false;
            }*/

            $perso_nom = $perso->perso_nom;
            $perso_cod = $perso->perso_cod;

            $myAuth->perso_cod = $perso_cod;
            $myAuth->compt_cod = $compt_cod;
        }
        else
        {
            if (!empty($_POST['username']))
            {
                echo 'Authentification échouée';
            }
        }
    }
}
else
{
    // on est déjà authentifié !

    $compt_cod = $myAuth->id;
    $compte    = new compte;
    if ($compte->charge($compt_cod))
    {
        $verif_auth  = true;
        $normal_auth = true;
        $compt_nom   = $compte->compt_nom;
        // est-ce qu'on change de perso ?
        if (isset($change_perso))
        {
            if ($compte->autoriseJouePerso($change_perso))
            {
                $compte->compt_der_perso_cod = $change_perso;
                $compte->stocke();
            }
            else
            {
                die('Accès interdit à ce perso');
            }
        }

        //-----------------------------------------------------------------------------------//
        // à partir d'ici, on va initialiser les variables nécessaires à la poursuite du jeu //
        //-----------------------------------------------------------------------------------//
        // compte
        $type_perso       = 'joueur';
        $is_admin_monstre = false;
        $is_admin         = false;
        if ($compte->compt_monstre == 'O')
        {
            $type_perso       = 'monstre';
            $is_admin_monstre = true;
        }
        if ($compte->compt_admin == 'O')
        {
            $type_perso = 'admin';
            $is_admin   = true;
        }
        $perso->getByComptDerPerso($compte->compt_cod);

        $perso_nom = $perso->perso_nom;
        $perso_cod = $perso->perso_cod;

        $myAuth->perso_cod = $perso_cod;
        $myAuth->compt_cod = $compt_cod;
    }
}




montre_formulaire_connexion($verif_auth);

// on met la variable ISAUTH dans options_tiwg_defaut
// ca permettra de la passer automatiquement au template
$temp_array = array('ISAUTH' => $verif_auth);
$options_twig_defaut = array_merge($options_twig_defaut,$temp_array);
// Bon, je sais, c'est mal de mettre ça ici, mais je suis sur
// que c'est appelé tout le temps...
$finances  = new finances();
$workYear  = date('Y');
$workMonth = date('m');

$negatif = abs($finances->getTotalByDate($workMonth, $workYear, -1));
$positif = $finances->getTotalByDate($workMonth, $workYear, 1);
if ($negatif == 0)
{
    $percent_finances = 0;
}
else
{
    $percent_finances = ($positif * 100) / $negatif;
    if ($percent_finances > 100)
    {
        $percent_finances = 100;
    }

}
