<?php
include_once "verif_connexion.php";

include '../includes/template.inc';
$t = new template;

require "variables_menu.php";


require_once CHEMIN . '../includes/Twig/Autoloader.php';
Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem(CHEMIN . '/../templates');

$twig     = new Twig_Environment($loader, array());
$template = $twig->loadTemplate('switch.twig');

//
// gestion des vote
//
$cv           = new compte_vote();
$totalXpGagne = 0;
$tab          = $cv->getBy_compte_vote_compte_cod($compte->compt_cod);
if (count($tab) > 0)
{
    $totalXpGagne = $tab[0]->compte_vote_total_px_gagner;
}


$cvip    = new compte_vote_ip();
$tab     = $cvip->getByCompteTrue($compte->compt_cod);
$nbrVote = count($tab);


$tab         = $cvip->getByCompteTrueMois($compte->compt_cod);
$nbrVoteMois = count($tab);

$tab          = $cvip->getVoteAValider($compte->compt_cod);
$VoteAValider = count($tab);

$tab          = $cvip->getVoteRefus($compte->compt_cod);
$votesRefusee = count($tab);

if ($verif_auth)
{
    if ($is_admin_monstre === true)
    {
        $admin  = 'O';
        $chemin = '.';
        include "switch_monstre.php";
    }
    elseif ($is_admin === true)
    {
        include "switch_admin.php";
    }
    else
    {
        $persos_actifs = $compte->getPersosActifs();
        $nb_perso_max  = $compte->compt_ligne_perso * 3;

        $type_4        = $compte->compt_type_quatrieme;
        $premier_perso = $persos_actifs[0]->perso_cod;
        foreach ($persos_actifs as $perso_actif)
        {
            // on prend toutes les infos nécessaires du perso
            $perso_actif->prepare_for_tab_switch();
            if ($perso_actif->perso_type_perso == 1 && $perso_actif->perso_pnj != 2)
            {
                // on est sur un perso normal
                $perso_joueur[] = $perso_actif;
            }
            if ($perso_actif->perso_type_perso == 2 || $perso_actif->perso_pnj == 2)
            {
                // on est sur un 4e, monstre ou perso
                $perso_quatrieme[] = $perso_actif;
            }
            if ($perso_actif->perso_type_perso == 3)
            {
                // familiers
                $familiers[] = $perso_actif;
            }
        }
        $cases_vides = $nb_perso_max - count($perso_joueur);
        for ($i = 0; $i < $cases_vides; $i++)
        {
            $perso_vide             = new perso;
            $perso_vide->perso_vide = true;
            $perso_joueur[]         = $perso_vide;
        }

        // on regarde s'il y a des comptes sittés
        $persos_sittes = $compte->getPersosSittes();
        foreach ($persos_sittes as $perso_sitte)
        {
            $perso_sitte->prepare_for_tab_switch();
        }
        $type_perso   = $perso->perso_type_perso;
        $options_twig = array(
            'URL'              => G_URL,
            'URL_IMAGES'       => G_IMAGES,
            'HTTPS'            => $type_flux,
            'TYPE_PERSO'       => $type_perso,
            'ISAUTH'           => $verif_auth,
            'IS_ADMIN_MONSTRE' => $is_admin_monstre,
            'COMPTE'           => $compte,
            'PERSOS_ACTIFS'    => $persos_actifs,
            'PERSOS_JOUEURS'   => $perso_joueur,
            'PERSOS_QUATRIEME' => $perso_quatrieme,
            'PERSO_PAR_LIGNE'  => $nb_perso_ligne,
            'NB_PERSO_MAX'     => $nb_perso_max,
            'OK_4'             => $ok_4,
            'FAMILIERS'        => $familiers,
            'PERSOS_SITTES'    => $persos_sittes,
            'PREMIER_PERSO'    => $premier_perso,
            'TOTAL_XP_GAGNE'   => $totalXpGagne,
            'NBR_VOTE'         => $nbrVote,
            'NBR_VOTE_MOIS'    => $nbrVoteMois,
            'VOTE_A_VALIDER'   => $VoteAValider,
            'VOTE_REFUSE'      => $votesRefusee

        );
        echo $template->render($options_twig);
    }
}
