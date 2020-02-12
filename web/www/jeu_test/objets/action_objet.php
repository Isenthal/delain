<?php
include "../verif_connexion.php";


$contenu_page = '';

if (isset($_GET['tobj']))
    $tobj = $_GET['tobj'];

// VERIFICATIONS D'USAGE
$db = new base_delain;

$boule = True;

//TEST PRESENCE

$req_matos = "select perobj_obj_cod, obj_etat from perso_objets,objets "
             . "where perobj_obj_cod = obj_cod and and perobj_perso_cod = $perso_cod and obj_gobj_cod = $tobj order by perobj_obj_cod";

$db->query($req_matos);
$db->next_record();

$num_obj    = $db->f("perobj_obj_cod");
$etat_objet = $db->f("obj_etat");
if (!($db->next_record()))
{
    // PAS D'OBJET.
    $contenu_page .= "<p>Vous avez beau chercher, il n’y a pas cet objet dans votre sac</p>";
    $boule        = false;
}

// TEST PA
if ($boule == true)
{
    $req_pa = "select perso_pa,perso_nom from perso where perso_cod = $perso_cod";
    $db->query($req_pa);
    $db->next_record();
    if ($db->f("perso_pa") < 4)
    {
        $contenu_page .= "<p><strong>Vous n’avez pas assez de PA !</strong></p>";
        $boule        = false;
    }
}

// TEST PORTEUR = FAMILIER
if ($boule)
{
    if ($tobj == 269)
    {
        $is_familier  = false;
        $req_familier = "select 1 from perso where perso_cod = $perso_cod and perso_type_perso = 3";
        $db->query($req_familier);
        if ($db->next_record())
        {
            $contenu_page .= "<p>Un familier ne peut pas s’occuper d’un œuf !</p>";
            $boule        = false;
        }
    }
}

// TEST PORTEUR A UN FAMILIER
if ($boule)
{
    if ($tobj == 269)
    {
        $has_familier = false;
        $req_familier =
            "select pfam_familier_cod from perso_familier,perso where pfam_perso_cod = $perso_cod and pfam_familier_cod = perso_cod and perso_actif = 'O'";
        $db->query($req_familier);
        if ($db->next_record())
        {
            $contenu_page .= "<p>Votre familier vous fait clairement comprendre qu’il n’est pas prêt à vous laisser vous occuper de cet œuf.<br /> Peut-être faudrait-il laisser cet objet à quelqu’un qui n’a pas d’animal jaloux ?</p>";
            $boule        = false;
        }
    }
}

if ($boule)
{
    // ON ENLEVE LES PAs
    $req_enl_pa = "update perso set perso_pa = perso_pa - 4 where perso_cod = $perso_cod";
    $db->query($req_enl_pa);

    // ON DIMINUE 'ETAT
    $diff_etat  = mt_rand(0, 25) + 1;
    $etat_objet = $etat_objet - $diff_etat;
    $req_etat   = "update objets set obj_etat = $etat_objet where obj_cod = $num_obj";
    $db->query($req_etat);

    switch ($tobj)
    {
        case 269:
            $contenu_page .= "<p><strong>Vous réchauffez l’œuf quelques minutes...</strong></p>";
            break;
        case 640:
            $contenu_page .= "<p><strong>Vous faites un peu de sport pour retrouver la ligne ...</strong></p>";
            break;
        default:
            $contenu_page .= "<p><strong>Vous tentez de détruire cet objet ...</strong></p>";
            break;
    }

    //ETAT NEGATIF (destruction)

    if ($etat_objet <= 0)
    {
        // ON SUPPRIME L'OBJET.
        $req_supr_obj = "select  f_del_objet($num_obj)";
        $db->query($req_supr_obj);

        switch ($tobj)
        {
            case 269:
                // POSITION DU PROPRIETAIRE
                $req_pos = "select ppos_pos_cod from perso_position where ppos_perso_cod = $perso_cod";
                $db->query($req_pos);
                $db->next_record();
                $perso_position = $db->f("ppos_pos_cod");

                $choix = mt_rand(0, 100);
                if ($choix < 25)
                {
                    $contenu_page .= "<p><strong>Un cobra apparaît ! Il n’a pas l’air amical. </strong><br></p>";
                    $req_monstre  = "select cree_monstre_pos(16,$perso_position) as num";
                    $db->query($req_monstre);
                } else if ($choix < 50)
                {
                    $contenu_page .= "<p><strong>Un basilic apparaît ! Il n’a pas l’air amical. </strong><br></p>";
                    $req_monstre  = "select cree_monstre_pos(13,$perso_position) as num";
                    $db->query($req_monstre);
                } else
                {
                    $typefam      = mt_rand(0, 1) + 192;    // 192 combat, 193 distance
                    $contenu_page .= "<p><strong>Un familier sort de l’œuf, il a l’air de vous apprécier et de vous suivre. </strong><br></p>";
                    $req_monstre  = "select cree_monstre_pos($typefam, $perso_position) as num";
                    $db->query($req_monstre);
                    $db->next_record();
                    $num_fam     = $db->f("num");
                    $nom2        = 'Familier de ' . pg_escape_string($perso_nom);
                    $req_monstre =
                        "update perso set perso_nom = e'$nom2', perso_lower_perso_nom = lower(e'$nom2'), perso_type_perso = 3 "
                        . "where perso_cod = $num_fam";
                    $db->query($req_monstre);
                    $req_etat =
                        "insert into perso_familier (pfam_perso_cod,pfam_familier_cod) values ($perso_cod,$num_fam)";
                    $db->query($req_etat);
                    // Ajout à la coterie du maître
                    $req_coterie =
                        "select pgroupe_groupe_cod from groupe_perso where pgroupe_perso_cod=$perso_cod and pgroupe_statut = 1";
                    $db->query($req_coterie);
                    if ($db->next_record())
                    {
                        $num_coterie = $db->f("pgroupe_groupe_cod");
                        $req_ajout   = "insert into groupe_perso (pgroupe_groupe_cod, pgroupe_perso_cod, pgroupe_statut, pgroupe_messages, pgroupe_message_mort)
					values ($num_coterie, $num_fam, 1, 0, 0)";
                    }
                }
                break;
            case 640:
                $contenu_page .= "<p><strong>Ouf !  Les efforts ont porté leurs fruits: vous avez retrouvé la ligne !</strong><br></p>";
                break;
            default:
                $contenu_page .= "<p><strong>Et cela a marché: l'objet est détruit !</strong></p>";
                break;
        }
    } else
    {
        switch ($tobj)
        {
            case 269:
                if ($etat_objet < 20)
                    $contenu_page .= "<p>L’œuf est craquellé de toutes parts, quelques morceaux de la coquille se détachent... Il est sur le point d’éclore !</p>";
                else if ($etat_objet < 40)
                    $contenu_page .= "<p>L’œuf bouge de plus en plus les craquelures sont plus nettes et plus nombreuses.</p>";
                else if ($etat_objet < 60)
                    $contenu_page .= "<p>Les mouvements à l’intérieur de l’œuf sont plus nombreux, quelques craquelures apparaissent par endroits.</p>";
                else if ($etat_objet < 80)
                    $contenu_page .= "<p>On sent quelques légers mouvements à l’intérieur de l’œuf, la coquille se fendille légèrement.</p>";
                else
                    $contenu_page .= "<p>L’œuf est intact et inerte, rien à signaler.</p>";
                break;
            case 640:
                $contenu_page .= "<p>Vous transpirez et vous sentez mieux ... mais encore un effort pour retrouver la forme !</p>";
                break;
            default:
                $contenu_page .= "<p>Des fissures apparaissent ... mais l'objet tient bon !</p>";
                break;
        }
    }
    $contenu_page .= '<form method="post" action="action_objet.php">
	<input type="hidden" name="methode" value="rechauffer">';
    switch ($tobj)
    {
        case 269:
            $contenu_page .= '<input type="submit" value="Réchauffer (4PA)"  class="test">';
            $contenu_page .= '<input type="hidden" name="tobj" value="269" />';
            break;
        case 640:
            $contenu_page .= '<input type="submit" value="Faire du sport (4PA)"  class="test">';
            $contenu_page .= '<input type="hidden" name="tobj" value="640" />';
            break;
        default:
            $contenu_page .= '<input type="submit" value="Tenter de casser cet objet (4PA)"  class="test">';
            $contenu_page .= '<input type="hidden" name="tobj" value="999" />';
            break;
    }

    $contenu_page .= '</form>';
}
// on va maintenant charger toutes les variables liées au menu
include('../variables_menu.php');
$template     = $twig->load('template_jeu.twig');
$options_twig = array(

    'CONTENU_PAGE' => $contenu_page
);
echo $template->render(array_merge($var_twig_defaut, $options_twig_defaut, $options_twig));