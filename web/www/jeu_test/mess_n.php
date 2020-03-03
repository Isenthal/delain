<?php 
$contenu_page .= '<script language="javascript" src="../scripts/messEnvoi.js"></SCRIPT>';
$db2 = new base_delain;
if (!$db->is_admin($compt_cod)
     || ($db->is_admin_monstre($compt_cod) 
         && ($db->is_monstre($perso_cod) || $db->is_pnj($perso_cod))))
{

	if (!$db->is_bernardo($perso_cod))
	{
		if (!isset($n_dest))
		{
			$n_dest = "";
		}
		else
		{
			$n_dest = str_replace("''","'",$n_dest);	
		}
		$n_message = '';
		if (isset($msg_init))
		{
			//On récupère le label du message précédent
			/* Modif par Maverick le 27/05/11
			 * Rangement automatique des anciens messages.
			 */
			$req_msg =	"select perso_nom, msg_corps
												from messages
												left join messages_exp on emsg_msg_cod=msg_cod
												left join perso on perso_cod=emsg_perso_cod
												where msg_cod=".$num_message;
			$db->query($req_msg);
			$db->next_record();
			$mess_old = str_replace(chr(127),';',$db->f('msg_corps'));

            $breaks = array("<br />","<br>","<br/>");
            $mess_old = str_ireplace($breaks, "", $mess_old);

            //$mess_old = eregi_replace('<br[[:space:]]*/?[[:space:]]*>','',$mess_old);
			preg_match("/.*?(_{1,}\[.*\]_{1,}.*_{12,16}).*/s", $mess_old, $mess_olds); // Dernier ajout
			if (count($mess_olds)==0) { // S'il n'y a qu'un précédent message
				$n_message = '__['.$db->f('perso_nom')."]__\n".trim($mess_old)."\n________________\n";
			} else { // S'il y a plusieurs précédents messages
				$mess_old = str_replace($mess_olds[1],'',$mess_old);
				//$mess_olds[1] = eregi_replace('_{12,16}','',$mess_olds[1]);
				$n_message = $mess_olds[1]."\n__[".$db->f('perso_nom')."]__\n".trim($mess_old)."\n________________\n";
			}
			/* // Ancienne version
			$req_msg = "select msg_corps from messages where msg_cod = " . $num_message;
			$db->query($req_msg);
			$db->next_record();
			$n_message = str_replace(chr(127),';',$db->f('msg_corps'));
			$n_message = str_replace("<br />",'',$n_message);
			$n_message = '[Message précédemment reçu : '.$n_message.']';
			*/
		}
		if (!isset($n_titre))
		{
			$n_titre = "";
		}
		else
		{
			if (preg_match('/^Re:(.*)$/i', $n_titre, $m))
			{
   			$n_titre = 'Re[2]:'.$m[1]; 
			}
			elseif (substr($n_titre,0,3) == 'Re[')
			{
   			$index_cr = strpos($n_titre,"]");
   			$lg = $index_cr - 3;
   			$index = substr($n_titre,3,$lg);
   			$index++;
   			$corps = substr($n_titre,$index_cr+2);
   			$n_titre = 'Re[' . $index . ']:' . $corps;
			}
			else
			{
				$n_titre = 'Re: '.$n_titre; 
			}
		}
		// remplissage de contenu
		if(!isset($msg_init))
			$msg_init = 0;
		$contenu_page .= '
		<form name="nouveau_message" method="post" action="action_message.php">
		<input type="hidden" name="msg_init" value="' . $msg_init . '">
		<input type="hidden" name="methode" value="nouveau_message">
		<table cellpadding="2" cellspacing="2">
		
		<tr>
		<td class="titre" colspan="3"><div class="titre">Expédition d\'un nouveau message</div></td>
		</tr>';
		$req_pos = "select ppos_pos_cod,distance_vue($perso_cod) as dist_vue,pos_etage,pos_x,pos_y from perso_position,perso,positions where ppos_perso_cod = $perso_cod and perso_cod = $perso_cod and ppos_pos_cod = pos_cod ";
		$db->query($req_pos);
		$db->next_record();
		$pos_actuelle = $db->f("ppos_pos_cod");
		$v_x = $db->f("pos_x");
		$v_y = $db->f("pos_y");
		$vue =  $db->f("dist_vue");
		$etage = $db->f("pos_etage");
		// remplissage de contenu
		$contenu_page .= '
		<tr>
		<td class="soustitre2">Destinataires : <br><em>(Entrez les noms des destinataires séparés par des ";")</em></td>
		<td><input type="text" name="dest" size="80" value="' . $n_dest . '"></td>
		<td>
		<select name="joueur" onChange="changeDestinataire(0);">
		<option value="">---------------</option>';

        $req = "select pguilde_guilde_cod from guilde_perso where pguilde_perso_cod = $perso_cod and pguilde_valide = 'O' ";
		$db->query($req);
		if ($db->nf() != 0) $has_guilde=true; else  $has_guilde=false;
        $req = 'select pgroupe_groupe_cod from groupe_perso where pgroupe_perso_cod = ' . $perso_cod . ' and pgroupe_statut > 0 ';
        $db->query($req);
        if ($db->nf() != 0) $has_coterie=true; else  $has_coterie=false;

        if ($has_guilde)
		{
			// remplissage de contenu
			$contenu_page .= '<optgroup label="Guilde">
			<option value="guilde;">Message à toute la guilde</option>
			</optgroup>';
		}
        $contenu_page .= ' <optgroup label="Les joueurs (=1 perso par triplette)">';
		$contenu_page .= ' <option value="_tous_joueurs_vue_;">Tous les joueurs en vue</option>';
        if ($has_coterie) $contenu_page .= ' <option value="_tous_joueurs_coterie_;">Tous les joueurs de la coterie</option>';
        if ($has_guilde) $contenu_page .= ' <option value="_tous_joueurs_guilde_;">Tous les joueurs de la guilde</option>';
        if ($db->is_admin_monstre($compt_cod)) $contenu_page .= '<option value="_tous_joueurs_carte_;">Message à tous les joueurs de la carte</option>';
        $contenu_page .= ' <option value="_filtre_1_ppj_;">Filtrer les destinataires à 1 perso par joueur </option>';

        $contenu_page .= '</optgroup>';
        $dist_init = -1;
		$req_vue = "select perso_nom,distance(ppos_pos_cod,$pos_actuelle) as dist,trajectoire_vue($pos_actuelle,pos_cod) as traj from perso, perso_position, positions
												where pos_x >= ($v_x - $vue) and pos_x <= ($v_x + $vue)
												and pos_y >= ($v_y - $vue) and pos_y <= ($v_y + $vue)
												and ppos_perso_cod = perso_cod
												and perso_cod != $perso_cod 
												and perso_type_perso != 2
												and perso_actif = 'O'
												and ppos_pos_cod = pos_cod
												and pos_etage = $etage
												order by dist,perso_type_perso,perso_nom ";
		$db->query($req_vue);
		$ch = '';
		while($db->next_record())
		{
			if ($db->f('traj') == 1)
			{
				if ($db->f('dist') != $dist_init)
				{
					$ch .= '</optgroup><optgroup label="Distance ' . $db->f('dist') . '">';
					$dist_init = $db->f('dist');
				}
				$ch .= '<option value="' . $db->f('perso_nom') . ';">' . $db->f('perso_nom') . '</option>';

			}
		}
			$ch = substr($ch,11);
			$contenu_page .= $ch;
			$req = "select cliste_cod,cliste_nom from contact_liste where cliste_perso_cod = $perso_cod ";
			$db->query($req);
			if ($db->nf() != 0)
			{
				$db2 = new base_delain();
				while ($db->next_record())
				{
					$contenu_page .= '<optgroup label="liste - ' . $db->f("cliste_nom") . '">';
					$contenu_page .= '<option value="liste_dif_' . $db->f("cliste_cod") . ';">Toute la liste</option>';
					$req = "select perso_cod,perso_nom from perso,contact ";
					$req = $req . "where contact_cliste_cod = " . $db->f("cliste_cod") . " ";
					$req = $req . "and contact_perso_cod = perso_cod ";
					$req = $req . "order by perso_nom ";
					$db2->query($req);
					while ($db2->next_record())
					{
						$contenu_page .= '<option value="' . $db2->f("perso_nom") . ';">' . $db2->f("perso_nom") . '</option>';
					}
					
					$contenu_page .= '</optgroup>';
					
				}
			}
			$contenu_page .= '</select>';
		$contenu_page .= '
		</td>
		</tr>
		
		
		<tr>
		<td class="soustitre2">Titre du message : </td>';
		$contenu_page .= '<td colspan="2"><input type="text" name="titre" size="50" MAXLENGTH="50" value="' . $n_titre . '"></td>';
		$contenu_page .= '
		</tr>
		<tr>
			<td colspan="3" class="soustitre2"><em>Rappel : </em>Merci de bien vouloir éviter les insultes, et de rester dans le cadre de la courtoisie dans vos messages. Tout abus pourra amener à une clôture du compte sans préavis.</td>
		</tr>
		<tr>
			<td class="soustitre2">Corps du message : </td>
			<td colspan="2">
			<table>
				<tr>
					<td><textarea name="corps" cols="80" rows="20">'.$n_message.'</textarea></td>
					<td><input type="button" class="test" onClick="javascript:window.open(\'http://www.jdr-delain.net/includes/codes.php\',\'Smilies\',\'scrollbars=yes,width=300,height=500\')" value="Voir les smilies"></td>
				</tr>
			</table>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="soustitre2"><center><input type="submit" accesskey="s" class="test" value="Envoyer le message !"></center></td>
		</tr>
		</table>
		</form>';
	}
	else
	{
		$contenu_page .= 'Vous êtes sous l\'effet du sort Bernardo. Vous ne pouvez pas poster de message.';
	}
}
else
{
	$contenu_page .= 'Vous n\'êtes pas autorisé à écrire un nouveau message en tant qu\'admin !';
}