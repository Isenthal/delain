<?php
require G_CHE . 'includes/message.php';
include "blocks/_header_page_jeu.php";
$perso  = $verif_connexion->perso;
$compte = $verif_connexion->compte;
/** @var integer $perso_cod défini par _header_page_jeu */
include "../includes/binettes.php";
//
// initialisation tableau
//
$mess[0] = 'Boite de réception';
$mess[1] = 'Archives';
$mess[2] = 'Nouveau message';
$mess[3] = 'Boite d’envoi';
$mess[4] = 'Listes de diffusion';
$nb      = count($mess);
//
// Si pas de parametres passés
//


if (!isset($m))
{
    $m = 0;
}

if (!isset($methode))
{
    $methode = 'debut';
}

$contenu_page .= '
	<table cellspacing="0" cellpadding="0" width="100%">
<tr>';
for ($cpt = 0; $cpt < $nb; $cpt++)
{
    $lien   = '<a href="messagerie2.php?m=' . $cpt . '">';
    $f_lien = '</a>';
    if ($cpt == $m)
    {
        $style = 'onglet';
    } else
    {
        $style = 'pas_onglet';

    }
    $contenu_page .= '<td class="' . $style . '"><div style="text-align:center">' . $lien . $mess[$cpt] . $f_lien . '</div></td>';
}
$contenu_page .= '
	</tr>
	<tr>
		<td colspan="' . $nb . '" class="reste_onglet"><center>';
$auth_mes     = 0;
$message      = new messages();
$mid          = isset($_REQUEST['mid']) ? $_REQUEST['mid'] : 0 ;
$message->charge($mid);
if ($message->is_auth_msg($perso_cod))
{
    $auth_mes = 1;
}
switch ($methode)
{
    /************************************/
    /* V O I R    U N    M E S S A G E  */
    /************************************/
    case "visu_msg":
        if ($auth_mes == 1)
        {
            $disparu = '<em>-- Personnage disparu --</em>';

            //
            // On recherche la guilde
            //
            $req  =
                "select pguilde_guilde_cod from guilde_perso where pguilde_perso_cod = $perso_cod and pguilde_valide = 'O' ";
            $stmt = $pdo->query($req);

            if ($stmt->rowCount() != 0)
            {
                $result     = $stmt->fetch();
                $num_guilde = $result['pguilde_guilde_cod'];
            } else
            {
                $num_guilde = 0;
            }
            //
            // On marque le message comme lu
            //
            if (!$compte->is_admin())
            {
                $req_lu = 'update messages_dest
					set dmsg_lu = \'O\'
					where dmsg_perso_cod = ' . $perso_cod . '
					and dmsg_msg_cod = ' . $mid;
                $pdo->query($req_lu);
            }
            //
            // On prend les infos du message
            //
            $req_msg        = "select to_char(msg_date2,'DD/MM/YYYY hh24:mi:ss') as date_mes,
					msg_init, msg_titre, msg_cod, msg_corps,
					msg_guilde, msg_guilde_cod
				from messages
				where msg_cod = $mid";
            $stmt           = $pdo->query($req_msg);
            $result         = $stmt->fetch();
            $corps          = str_replace(chr(127), ';', $result['msg_corps']);
            $date           = $result['date_mes'];
            $titre          = str_replace(chr(127), ';', $result['msg_titre']);
            $msg_init       = $result['msg_init'];
            $msg_guilde_cod = $result['msg_guilde_cod'];
            $msg_guilde     = $result['msg_guilde'];
            $n_titre        = str_replace(chr(39), " ", $titre);

            //
            // Puis les infos de l’expéditeur
            //
            $req_exp       = "select emsg_perso_cod, coalesce(perso_nom, '$disparu') as perso_nom
				from messages_exp
				left outer join perso on perso_cod = emsg_perso_cod
				where emsg_msg_cod = $mid";
            $stmt          = $pdo->query($req_exp);
            $is_expediteur = ($stmt->rowCount() > 0);
            if ($result = $stmt->fetch())
            {
                $exp            = str_replace("'", "\'", $result['perso_nom']) . ";";
                $emsg_perso_cod = $result['emsg_perso_cod'];
                $emsg_perso_nom = $result['perso_nom'];
            } else
            {
                $exp            = '';
                $emsg_perso_cod = -1;
                $emsg_perso_nom = $disparu;
            }


            //
            // on regarde sur quel type de message on est
            // et on construit le tableau <--précédent suivant -->
            //
            $precedent_suivant = '<table width="100%"><tr><td>';

            // reçu non archivé
            $pref[0]   = 'dmsg_';
            $suff[0]   = '_dest';
            $restr[0]  = ' and dmsg_archive = \'N\' ';
            $restr2[0] = ' and dmsg_efface = 0';
            // reçu archive
            $pref[1]   = 'dmsg_';
            $suff[1]   = '_dest';
            $restr[1]  = ' and dmsg_archive = \'O\' ';
            $restr2[1] = ' and dmsg_efface = 0';
            // envoyé
            $pref[3]   = 'emsg_';
            $suff[3]   = '_exp';
            $restr[3]  = ' ';
            $restr2[3] = ' ';
            $req_ordre = 'select ' . $pref[$m] . 'msg_cod,' . $pref[$m] . 'lu
				from messages' . $suff[$m] . '
				where ' . $pref[$m] . 'perso_cod = ' . $perso_cod . '
				and ' . $pref[$m] . 'msg_cod < ' . $mid . $restr[$m] . $restr2[$m] . '
				order by ' . $pref[$m] . 'msg_cod desc limit 1';
            $stmt3     = $pdo->query($req_ordre);
            if ($stmt3->rowCount() != 0)
            {
                $result3           = $stmt3->fetch();
                $t_var             = $pref[$m] . 'msg_cod';
                $precedent_suivant .= '<a href="' . $PHP_SELF . '?m=' . $m . '&mid=' . $result3[$t_var] . '&methode='
                                      . $methode . '">';
                if ($result3[$pref[$m] . 'lu'] == 'N')
                {
                    $precedent_suivant .= '<strong>';
                }
                $precedent_suivant .= '<== Message plus ancien ';
                if ($result3[$pref[$m] . 'lu'] == 'N')
                {
                    $precedent_suivant .= '</strong>';
                }
                $precedent_suivant .= '</a>';
            }
            $precedent_suivant .= '</td><td>';
            $req_ordre2        = 'select ' . $pref[$m] . 'msg_cod,' . $pref[$m] . 'lu
				from messages' . $suff[$m] . '
				where ' . $pref[$m] . 'perso_cod = ' . $perso_cod . '
				and ' . $pref[$m] . 'msg_cod > ' . $mid . $restr[$m] . $restr2[$m] . '
				order by ' . $pref[$m] . 'msg_cod asc limit 1';
            $stmt3             = $pdo->query($req_ordre2);
            if ($stmt3->rowCount() != 0)
            {
                $result3           = $stmt3->fetch();
                $t_var             = $pref[$m] . 'msg_cod';
                $precedent_suivant .= '<div style="text-align:right;"><a href="' . $PHP_SELF . '?m=' . $m . '&mid=' . $result3[$t_var] . '&methode=' . $methode . '">';
                if ($result3[$pref[$m] . 'lu'] == 'N')
                {
                    $precedent_suivant .= '<strong>';
                }
                $precedent_suivant .= 'Message plus récent ==> ';
                if ($result3[$pref[$m] . 'lu'] == 'N')
                {
                    $precedent_suivant .= '</strong>';
                }
                $precedent_suivant .= '</a>';
            }
            $precedent_suivant .= '</td></div></td></tr></table>';

            //
            // On construit la page
            //
            $contenu_page .= $precedent_suivant;
            $contenu_page .= '<hr>
				<table cellspacing="2" width="100%">
					<tr>
						<td class="soustitre2" width="200">Date : </td>
						<td>' . $date . '</td>
					</tr>
					<tr>
						<td class="soustitre2" width="200">Titre : </td>
						<td><strong>' . $titre . '</strong></td>
					</tr>
					<tr>
						<td class="soustitre2" width="200">Expéditeur : </td>
						<td><a href="visu_desc_perso.php?visu=' . $emsg_perso_cod . '">' . $emsg_perso_nom . '</a></td>
					</tr>';

            // On construit la liste des destinataires du 'Répondre à tous'
            // en commençant par ajouter l’expéditeur s’il n’est pas soi-même
            // ni dans la même guilde pour un message de guilde.
            if ($msg_guilde == 'O' && $msg_guilde_cod == $num_guilde
                || $emsg_perso_cod == $perso_cod
            )
            {
                $liste_dest = '';
            } else
            {
                $liste_dest = ($is_expediteur) ? $exp . ';' : '';
            }

            //
            // Puis les infos des destinataires
            //
            $req_dest         = "select dmsg_perso_cod, coalesce(perso_nom, '$disparu') as perso_nom, dmsg_lu, 
					coalesce(pguilde_guilde_cod, -1) as pguilde_guilde_cod
				from messages_dest
				left outer join perso on perso_cod = dmsg_perso_cod
				left outer join guilde_perso on pguilde_perso_cod = perso_cod and pguilde_valide = 'O'
				where dmsg_msg_cod = $mid";
            $stmt             = $pdo->query($req_dest);
            $is_destinataires = ($stmt->rowCount() > 0);

            $contenu_page .= '
				<tr><td class="soustitre2" width="200">Destinataire(s) : </td>
					<td>';
            if ($msg_guilde == 'O' && $msg_guilde_cod == $num_guilde)
            {
                $liste_dest       = $liste_dest . 'guilde;';
                $contenu_page     .= "Guilde, ";
                $is_destinataires = true;
            }
            if (!$is_destinataires)
            {
                $contenu_page .= $disparu;
            }
            while ($result = $stmt->fetch())
            {
                $nom_dest        = $result['perso_nom'];
                $num_dest        = $result['dmsg_perso_cod'];
                $guilde_cod_dest = $result['pguilde_guilde_cod'];

                // Construction de la liste des destinataires du Répondre à tous
                if ($num_dest != $perso_cod)    // On ne s’inclut pas
                {
                    if ($msg_guilde != 'O' || $msg_guilde_cod != $num_guilde || $guilde_cod_dest != $msg_guilde_cod)
                        // On inclut tous ceux qui ne sont pas dans la guilde du message,
                        // ou tout le monde si la personne qui répond à tous n’est pas dans la guilde du message
                        // ou tout le monde si le message n’a pas de guilde
                    {
                        $liste_dest = $liste_dest . str_replace("'", "\'", $nom_dest) . ";";
                    }
                }
                if ($result['dmsg_lu'] == 'O')
                {
                    $contenu_page .= '<a href="visu_desc_perso.php?visu=' . $num_dest . '">' . $nom_dest . '</a>, ';
                } else
                {
                    $contenu_page .= '<a href="visu_desc_perso.php?visu=' . $num_dest . '"><strong>' . $nom_dest . '</strong></a>, ';
                }
            }
            $contenu_page .= '</td></tr>';
            $req          = 'select valeur_bonus(' . $perso_cod . ' , \'ULT\') as bonus_valeur';
            $stmt         = $pdo->query($req);
            if ($stmt->rowCount() != 0)
            {
                $result   = $stmt->fetch();
                $chance   = $result['bonus_valeur'];
                $longueur = strlen($corps);
                for ($cpt = 0; $cpt < $longueur; $cpt++)
                {
                    if (rand(1, 100) < $chance)
                    {
                        $char  = rand(1, 255);
                        $char2 = chr($char);
                        $corps = substr_replace($corps, $char2, $cpt, 1);
                    }
                }
            }
            $corps = binettes($corps);

            $contenu_page .= '<td colspan="2" class="soustitre2">' . nl2br($corps) . '</td>
				</tr>
				</table>
				<hr>';
            $contenu_page .= $precedent_suivant;

            $contenu_page .= '<table cellspacing="2" width="100%">
				<tr>
				<form name="message" method="post" action="messagerie2.php">
				<input type="hidden" name="m" value="2">
				<input type="hidden" name="n_dest">
				<input type="hidden" name="n_titre">
				<input type="hidden" name="num_reponse">
				<input type="hidden" name="msg_init">
				<input type="hidden" name="num_message" value="' . $mid . '">
				<input type="hidden" name="dmsg_cod">
					<td class="soustitre2"><div style=text-align:center><a href="javascript:document.message.n_dest.value=\'' . $exp . '\';document.message.n_titre.value=\'' . $n_titre . '\';document.message.msg_init.value=' . $msg_init . ';document.message.submit();">Répondre</a></div></td>
					<td class="soustitre2"><div style=text-align:center><a href="javascript:document.message.n_dest.value=\'' . $liste_dest . '\';document.message.n_titre.value=\'' . $n_titre . '\';document.message.msg_init.value=' . $msg_init . ';document.message.submit();">Répondre à tous</div></td>
					<td class="soustitre2"><div style=text-align:center><a href="action_message.php?m=' . $m . '&methode=archive_msg&mid=' . $mid . '">Archiver</a></div></td>
					<td class="soustitre2"><div style=text-align:center><a href="action_message.php?m=' . $m . '&methode=efface_msg&mid=' . $mid . '">Effacer</a></div></td>
					<td class="soustitre2"><div style=text-align:center><a href="action_message.php?m=' . $m . '&methode=non_lu_msg&mid=' . $mid . '">Marquer comme non lu</a></div></td>
				</tr></table>';
        } else
        {
            $contenu_page .= '<div class="titre">Vous n’avez pas accès à ce message !';
        }
        break;
    case "tout_lu":
        $requete      = 'update messages_dest set dmsg_lu = \'O\' where dmsg_perso_cod = ' . $perso_cod;
        $stmt         = $pdo->query($requete);
        $contenu_page .= 'Tous les messages de votre boite de réception sont marqués comme lus.';
        break;
    case "select_efface":
        $nb = 0;
        for ($cpt = 0; $cpt < 20; $cpt++)
        {
            if (isset($msg[$cpt]) && $msg[$cpt] != '')
            {
                $nb      = $nb + 1;
                $requete =
                    'update messages_dest set dmsg_efface = 1,dmsg_lu = \'O\' where dmsg_cod = ' . $msg[$cpt] . ' and dmsg_perso_cod = ' . $perso_cod;
                $stmt    = $pdo->query($requete);
            }
        }
        $contenu_page .= $nb . ' messages ont été supprimés de votre boite de réception.';
        break;
    case "select_archive":
        $nb = 0;
        for ($cpt = 0; $cpt < 20; $cpt++)
        {
            if (isset($msg[$cpt]))
            {
                $nb      = $nb + 1;
                $requete =
                    'update messages_dest set dmsg_archive = \'O\',dmsg_lu = \'O\' where dmsg_cod = ' . $msg[$cpt] . ' and dmsg_perso_cod = ' . $perso_cod;
                $stmt    = $pdo->query($requete);
            }
        }
        $contenu_page .= $nb . ' messages ont été archivés.';
        break;
    case "select_non_lu":
        $nb = 0;
        for ($cpt = 0; $cpt < 20; $cpt++)
        {
            if (isset($msg[$cpt]))
            {
                $nb      = $nb + 1;
                $requete =
                    'update messages_dest set dmsg_lu = \'N\' where dmsg_cod = ' . $msg[$cpt] . ' and dmsg_perso_cod = ' . $perso_cod;
                $stmt    = $pdo->query($requete);
            }
        }
        $contenu_page .= $nb . ' messages ont été marqués comme non lus.';
        break;
    case "efface_msg":
        $requete      =
            'update messages_dest set dmsg_efface = 1,dmsg_lu = \'O\' where dmsg_msg_cod = ' . $mid . ' and dmsg_perso_cod = ' . $perso_cod;
        $stmt         = $pdo->query($requete);
        $contenu_page .= 'Le message a bien été effacé.';
        break;
    case "archive__vue generale_msg":
        $requete      =
            'update messages_dest set dmsg_archive = \'O\' where dmsg_cod = ' . $mid . ' and dmsg_perso_cod = ' . $perso_cod;
        $stmt         = $pdo->query($requete);
        $contenu_page .= 'Le message a été archivé.';
        break;
    case "efface_vue generale_msg":
        $requete      =
            'update messages_dest set dmsg_efface = 1,dmsg_lu = \'O\' where dmsg_cod = ' . $mid . ' and dmsg_perso_cod = ' . $perso_cod;
        $stmt         = $pdo->query($requete);
        $contenu_page .= 'Le message a bien été effacé. Message : ' . $mid . ' perso : ' . $perso_cod;
        break;
    case "archive_msg":
        $requete      =
            'update messages_dest set dmsg_archive = \'O\' where dmsg_msg_cod = ' . $mid . ' and dmsg_perso_cod = ' . $perso_cod;
        $stmt         = $pdo->query($requete);
        $requete      =
            'update messages_exp set emsg_archive = \'O\' where emsg_msg_cod = ' . $mid . ' and emsg_perso_cod = ' . $perso_cod;
        $stmt         = $pdo->query($requete);
        $contenu_page .= 'Le message a été archivé.';
        break;
    case "non_lu_msg":
        $requete      =
            'update messages_dest set dmsg_lu = \'N\' where dmsg_msg_cod = ' . $mid . ' and dmsg_perso_cod = ' . $perso_cod;
        $stmt         = $pdo->query($requete);
        $contenu_page .= 'Le message a été marqué comme non lu.';
        break;
    case "nouveau_message":

        $guilde = 'N';
        $erreur = 0;

        // Modif Morgenese
        // permet de matcher les codes html et de supprimer les ; au bout du caractère
        // pour ne par le confondre avec un autre perso
        //$dest = preg_replace("(&#[0-9]+);","\\1", $dest);
        //fin modif
        $pdo = new bddpdo;

        $tab_dest     = explode(";", $dest);
        $nb_dest      = count($tab_dest);
        $nb_vrai_dest = 0;
        $req          = "select valeur_bonus($perso_cod, 'BER')";
        $stmt         = $pdo->query($req);
        $result       = $stmt->fetch();
        if ($result['valeur_bonus'] > 0)
        {
            $contenu_page .= '<br><br><strong>********* Vous êtes sous l’effet d’un Bernardo, vous ne pouvez pas envoyer de message ! *********</strong><br><br>';
            $erreur       = 1;
        }


        $req_pos =
            "select ppos_pos_cod, distance_vue($perso_cod) as dist, pos_etage, pos_x, pos_y from perso_position, perso, positions where ppos_perso_cod = $perso_cod and perso_cod = $perso_cod and ppos_pos_cod = pos_cod ";
        $stmt    = $pdo->prepare($req_pos);
        $stmt->execute();
        $rows         = $stmt->fetch();
        $pos_actuelle = (int)$rows["ppos_pos_cod"];
        $v_x          = (int)$rows["pos_x"];
        $v_y          = (int)$rows["pos_y"];
        $etage        = (int)$rows["pos_etage"];
        $vue          = (int)$rows["dist"];

        $req_guilde =
            "select pguilde_guilde_cod from guilde_perso where pguilde_perso_cod = $perso_cod and pguilde_valide = 'O' ";
        $stmt       = $pdo->prepare($req_guilde);
        $stmt->execute();
        if ($rows = $stmt->fetch()) $num_guilde = (int)$rows["pguilde_guilde_cod"]; else $num_guilde = 0;


        $req_coterie =
            'select pgroupe_groupe_cod from groupe_perso where pgroupe_perso_cod = ' . $perso_cod . ' and pgroupe_statut > 0 ';
        $stmt        = $pdo->prepare($req_coterie);
        $stmt->execute();
        if ($rows = $stmt->fetch()) $num_coterie = (int)$rows["pgroupe_groupe_cod"]; else $num_coterie = 0;

        // == Préparation des variable
        $msg_guilde        = 0;
        $nb_expedie        = 0;
        $nb_non_expedie    = 0;
        $liste_non_expedie = "";
        $liste_expedie     = "";

        // Recherche de des perso_cod et injection des listes
        $tab_dest_cod      = array();
        $tab_dest_cod_1ppj = array();
        $filtre_1_ppj      = false;   // filtre à 1 perso par joueurs
        for ($cpt = 0; $cpt < $nb_dest; $cpt++)
        {
            if ($tab_dest[$cpt] != "")
            {


                if (!strcasecmp($tab_dest[$cpt], 'guilde'))
                {
                    $msg_guilde = $num_guilde;     // pour ajout cas particulier de message guilde
                    $request    = 'select pcompt_compt_cod, perso_cod from perso,guilde_perso, perso_compte
                                        where  pcompt_perso_cod=perso_cod 
                                        and pguilde_guilde_cod = ' . $num_guilde . '
                                        and pguilde_perso_cod != ' . $perso_cod . '
                                        and pguilde_perso_cod = perso_cod
                                        and pguilde_valide = \'O\' and pguilde_message = \'O\' ';
                    $stmt       = $pdo->prepare($request);
                    $stmt->execute();

                } else if (substr($tab_dest[$cpt], 0, 10) == 'liste_dif_')
                {
                    $liste = substr($tab_dest[$cpt], 10);
                    // on vérfie que cette liste soit bien au bon perso
                    $request = "select cliste_cod
                                from contact_liste
							    where (cliste_cod = $liste and cliste_perso_cod = $perso_cod)
								or exists (select 1 from contact,perso where contact_cliste_cod = $liste and contact_perso_cod = $perso_cod) ";
                    $stmt    = $pdo->prepare($request);
                    $stmt->execute();

                    if (!$rows = $stmt->fetch())
                    {
                        $request      = "";
                        $contenu_page .= "Vous ne pouvez pas écrire à cette liste: #{$tab_dest[$cpt]} !";
                    } else
                    {
                        $request = "select pcompt_compt_cod, contact_perso_cod as perso_cod 
                                    from contact, perso, perso_compte 
                                    where   pcompt_perso_cod=perso_cod 
                                        and contact_cliste_cod = $liste 
                                        and contact_perso_cod = perso_cod ";
                        $stmt    = $pdo->prepare($request);
                        $stmt->execute();
                    }
                } else if ($tab_dest[$cpt] == "_tous_joueurs_vue_")
                {
                    $request = 'select pcompt_compt_cod, min(perso_cod) as perso_cod 
                                    from perso, perso_position, positions, perso_compte
                                    where pcompt_perso_cod=perso_cod 
                                        and pos_x >= (' . $v_x . ' - ' . $vue . ') and pos_x <= (' . $v_x . ' + ' . $vue . ')
                                        and pos_y >= (' . $v_y . ' - ' . $vue . ') and pos_y <= (' . $v_y . ' + ' . $vue . ')
                                        and ppos_perso_cod = perso_cod
                                        and perso_cod != ' . $perso_cod . '
                                        and perso_type_perso = 1
                                        and perso_actif = \'O\'
                                        and ppos_pos_cod = pos_cod
                                        and pos_etage = ' . $etage . ' 
                                    group by pcompt_compt_cod ';
                    $stmt    = $pdo->prepare($request);
                    $stmt->execute();
                } else if ($tab_dest[$cpt] == "_tous_joueurs_coterie_")
                {
                    $request = 'select pcompt_compt_cod, min(perso_cod) as perso_cod 
                                    from perso,groupe_perso, perso_compte
                                    where pcompt_perso_cod=perso_cod
                                        and pgroupe_groupe_cod = ' . $num_coterie . '
                                        and pgroupe_perso_cod != ' . $perso_cod . '
                                        and pgroupe_perso_cod = perso_cod
                                        and pgroupe_statut > 0  
                                    group by pcompt_compt_cod ';
                    $stmt    = $pdo->prepare($request);
                    $stmt->execute();

                } else if ($tab_dest[$cpt] == "_tous_joueurs_guilde_")
                {
                    $request = 'select pcompt_compt_cod, min(perso_cod) as perso_cod 
                                    from perso,guilde_perso, perso_compte
                                    where pcompt_perso_cod=perso_cod 
                                        and pguilde_guilde_cod = ' . $num_guilde . '
                                        and pguilde_perso_cod != ' . $perso_cod . '
                                        and pguilde_perso_cod = perso_cod
                                        and pguilde_valide = \'O\' and pguilde_message = \'O\' 
                                    group by pcompt_compt_cod ';
                    $stmt    = $pdo->prepare($request);
                    $stmt->execute();
                } else if ($tab_dest[$cpt] == "_tous_joueurs_carte_")
                {
                    $request = 'select pcompt_compt_cod, min(perso_cod) as perso_cod 
                                    from perso, perso_position, positions, perso_compte
                                    where pcompt_perso_cod=perso_cod 
                                        and ppos_perso_cod = perso_cod
                                        and perso_cod != ' . $perso_cod . '
                                        and perso_type_perso = 1
                                        and perso_actif = \'O\'
                                        and ppos_pos_cod = pos_cod
                                        and pos_etage = ' . $etage . '
                                    group by pcompt_compt_cod ';
                    $stmt    = $pdo->prepare($request);
                    $stmt->execute();
                } else if ($tab_dest[$cpt] == "_filtre_1_ppj_")
                {
                    $filtre_1_ppj = true;
                    $request      = "";
                } else
                {
                    // rechercher le code du perso
                    $request = "select f_cherche_perso(?) as perso_cod ;";
                    $stmt    = $pdo->prepare($request);
                    $stmt    = $pdo->execute(array(ltrim(rtrim($tab_dest[$cpt]))), $stmt);
                    if (!$rows = $stmt->fetch())
                    {
                        $nb_non_expedie++;
                        $liste_non_expedie = ltrim(rtrim($tab_dest[$cpt])) . ", ";
                    } else
                    {
                        //$request = "select perso_cod,perso_nom, pcompt_compt_cod from perso join perso_compte on pcompt_perso_cod=perso_cod where perso_cod=?;";
                        // le perso ou son familier
                        $request = "select perso_cod,perso_nom, COALESCE(pcompt_compt_cod,0) as pcompt_compt_cod from perso left join perso_compte on pcompt_perso_cod=perso_cod where perso_cod=:perso_cod
                                        union all
                                    select perso_cod,perso_nom, pcompt_compt_cod from perso_familier join perso_compte on pcompt_perso_cod=pfam_perso_cod join perso on perso_cod=pfam_familier_cod where pfam_familier_cod=:perso_cod AND perso_actif='O' ";
                        $stmt    = $pdo->prepare($request);
                        $stmt    = $pdo->execute(array(":perso_cod" => $rows["perso_cod"]), $stmt);
                    }
                }

                if ($request != "")
                {
                    //$contenu_page .=$request;
                    while ($rows = $stmt->fetch())
                    {
                        if ((int)$rows["perso_cod"] > 0)
                        {
                            $tab_dest_cod[] = (int)$rows["perso_cod"];
                            if (isset($tab_dest_cod_1ppj[$rows["pcompt_compt_cod"]]))
                            {
                                $tab_dest_cod_1ppj[$rows["pcompt_compt_cod"]] =
                                    min((int)$rows["perso_cod"], $tab_dest_cod_1ppj[$rows["pcompt_compt_cod"]]);
                            } else
                            {
                                $tab_dest_cod_1ppj[$rows["pcompt_compt_cod"]] = (int)$rows["perso_cod"];
                            }
                        }
                    }
                }
            }
        }

        if ($filtre_1_ppj)
        {
            $tab_dest_msg = $tab_dest_cod_1ppj;
        } else
        {
            $tab_dest_msg = $tab_dest_cod;
        }

        //$contenu_page .= "<pre>".print_r($tab_dest_msg, true). "</pre>";
        $nb_dest = count($tab_dest_msg);

        if ($nb_dest > 300)
        {
            $contenu_page .= '<br><br><strong>********* Vous ne pouvez pas envoyer un message à plus de 300 destinataires ! *********</strong><br><br>';
            $erreur       = 1;
        }
        if ($nb_dest == 0)
        {
            $contenu_page .= '<br><br><strong>********* Vous devez renseigner au moins un destinataire ! *********</strong><br><br>';
            $erreur       = 1;
        }
        if ($titre == '')
        {
            $contenu_page .= '<br><br><strong>********* Vous devez mettre un titre au message !*********</strong><br><br>';
            $erreur       = 1;
        }
        if (strlen($titre) >= 50)
        {
            $contenu_page .= '<br><br><strong>********* Votre titre est trop long, merci de le raccourcir ! *********</strong><br><br>';
            $erreur       = 1;
        }
        if ($erreur == 1)
        {
            $contenu_page .= '<!-- Titre original: [' . $titre . '] -->';
            $titre        = htmlspecialchars($titre);
            $titre        = str_replace(";", chr(127), $titre);
            $titre        = pg_escape_string($titre);
            $contenu_page .= '<!-- Titre final: [' . $titre . '] -->';
            $corps        = htmlspecialchars($corps);
            $corps        = str_replace(";", chr(127), $corps);
            $corps        = pg_escape_string($corps);
            $contenu_page .= '<form name="nouveau_message" method="post" action="action_message.php">
				<input type="hidden" name="msg_init" value="' . $msg_init . '">
				<input type="hidden" name="methode" value="nouveau_message">
				<table cellpadding="2" cellspacing="2">
					<tr>
						<td class="soustitre2">Destinataires : <br><em>(Entrez les noms des destinataires séparés par des ";")</em></td>
						<td><input type="text" name="dest" size="40" value="' . $dest . '"></td>
						<td>
					<tr>
						<td class="soustitre2">Titre du message : </td>
						<td colspan="2"><input type="text" name="titre" size="50" MAXLENGTH="50" value="' . $titre . '"></td>
					</tr>
					<tr>
						<td colspan="2" class="soustitre2"><em>Rappel : </em>Merci de bien vouloir éviter les insultes, et de rester dans le cadre de la courtoisie dans vos messages. Tout abus pourra amener à une cloture du compte sans préavis.</td>
					</tr>
					<tr>
						<td class="soustitre2">Corps du message : </td>
						<td colspan="2">
						<table>
							<tr>
								<td><textarea name="corps" cols="40" rows="10">' . $corps . '</textarea>
								</td>
								<td><input type="button" class="test" onClick="javascript:window.open(\'http://www.jdr-delain.net/includes/codes.php\',\'Smilies\',\'scrollbars=yes,width=300,height=500\')" value="Voir les smilies">
							</td></tr>
						</table></td></tr>
					<tr>
						<td colspan="3" class="soustitre2"><center><input type="submit" accesskey="s" class="test" value="Envoyer le message !"></center></td>
					</tr>
				</table>
				</form>';
        }
        if ($erreur == 0)
        {
            /************************************/
            /* d’abord on enregistre le message */
            /************************************/

            $msg = new message();

            if (!isset($msg_init))
            {
                $msg_init = 0;
            }
            $msg->enReponseA = $msg_init;

            // Crapaud ?
            $req    = "select perso_crapaud from perso where perso_cod = $perso_cod ";
            $stmt   = $pdo->query($req);
            $result = $stmt->fetch();
            if ($result['perso_crapaud'] == 1)
            {
                $titre = "Crôôa ?!!??? ";
                $corps = "Croa, crôoâa, crôâ, CROOOAAAAA !
				" . $corps;
            }

            if ($msg_guilde > 0) $msg->guilde = $msg_guilde;
            $msg->corps      = $corps;
            $msg->sujet      = $titre;
            $msg->expediteur = $perso_cod;

            /**********************************/
            /* On boucle sur les destintaires */
            /**********************************/
            foreach ($tab_dest_msg as $msg_perso_cod)
            {
                $request = "select perso_nom from perso where perso_cod = ? ;";
                $stmt    = $pdo->prepare($request);
                $stmt    = $pdo->execute(array($msg_perso_cod), $stmt);
                if (!$rows = $stmt->fetch())
                {
                    $nb_non_expedie++;
                    $liste_non_expedie = $liste_non_expedie . '#' . $msg_perso_cod . ", ";
                } else
                {
                    $msg->ajouteDestinataire($msg_perso_cod);
                    $liste_expedie = $liste_expedie . $rows["perso_nom"] . ", ";
                    $nb_expedie++;
                }
            }
            if ($nb_non_expedie != 0)
            {
                $contenu_page .= "Le message n’a pas été expédié à $liste_non_expedie : aventurier(s) inexistant(s).";
            }

            $envoi = $msg->envoieMessage();
            if ($envoi)
            {
                $contenu_page .= "Le message a été envoyé correctement à $liste_expedie. Il arrivera sous peu.";
            } else
            {
                $contenu_page .= 'Le message n’a pas été envoyé : pas de destinataires valides trouvés.';
            }
        }
        break;
}

$contenu_page .= '</center></td>
	</tr>
	</table>';

include "blocks/_footer_page_jeu.php";
