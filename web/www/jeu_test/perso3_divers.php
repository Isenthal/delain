<?php 
//CETTE PAGE REGROUPE LES DONNÉES CONCERNANT LES QUÊTES ET LE TABLEAU DE CHASSE

function gereColonnes($colonne, $debut, $largeur = '')
{
	$resultat = '';
	$largeur = ($largeur === '') ? '' : " width='$largeur'";
	// Gestion de l'affichage en colonne
	if ($colonne == 0 && !$debut)
		$resultat .= '</td></tr><tr><td valign="top"' . $largeur . '>';
	if ($colonne > 0)
		$resultat .= '</td><td valign="top"' . $largeur . '>';

	return $resultat;
}



$contenu_page .= '<p class="titre">Concours de bardes</p>';
$contenu_page .= '<p><a href="concours_barde.php">Accéder à la page du concours de bardes.</a></p><br />';
$contenu_page .= '<p class="titre">Quêtes</p>';
$colonneMax = 2;
$colonne = 0;
$debut = true;
$contenu_page .= '<table><tr><td valign="top">';

//Quête enlumineur
$req = "select pquete_nombre,to_char(pquete_date_debut,'YYYY-MM-DD') as date_deb,pquete_param_texte from quete_perso where pquete_quete_cod = 16 and pquete_perso_cod = ". $perso_cod;
$stmt = $pdo->query($req); 
$result = $stmt->fetch();
if ($stmt->rowCount() != 0)
{
	$contenu_page .= gereColonnes($colonne, $debut);
	$colonne = ($colonne + 1) % $colonneMax;
	$debut = false;

	$nombre = $result['pquete_nombre'];
	$position = $result['pquete_param_texte'];
	$contenu_page .= '<table>
					<tr>
						<td class="titre"><strong>Quête Enlumineur</strong></td>
					</tr>';	

	$date_deb = $result['date_deb'];
	$contenu_page .= '<tr><td class="soustitre2">Vous êtes actuellement engagé dans la quête pour devenir enlumineur</td></tr>';
	$contenu_page .= '<tr><td class="soustitre2">Vous l’avez débutée le '. $date_deb .'</td></tr>';
	if ($nombre == 1)
	{
		$req = "select pos_cod,pos_x,pos_y,pos_etage from positions,perso_position where ppos_pos_cod = pos_cod and ppos_perso_cod = ". $perso_cod;
		$stmt = $pdo->query($req); 
		$result = $stmt->fetch();
		$perso_x = $result['pos_x'];
		$perso_y = $result['pos_y'];
		$perso_et = $result['pos_etage'];
		$perso_pos = $result['pos_cod'];
		$req = "select pos_x,pos_y,pos_etage from positions where pos_cod = ". $position;
		$stmt = $pdo->query($req); 
		$result = $stmt->fetch();
		$pos_x = $result['pos_x'];
		$pos_y = $result['pos_y'];
		$pos_et = $result['pos_etage'];
		$dif_x = $pos_x-$perso_x;
		$dif_y = $pos_y-$perso_y;
		$exp_x = pow($dif_x,2);
		$exp_y = pow($dif_y,2);
		$degre = rad2deg(acos($dif_x / (sqrt($exp_x + $exp_y) )));
		if ($dif_y < 0)
		{
			$degre = $degre * -1;
		}


		if ($pos_et != $perso_et)
		{
			$contenu_page .= 'Vous n’êtes plus au même étage que la quête que vous devez réaliser. Allez dans un centre de maîtrise magique ou un magasin runique pour retenter.';
		}
		else if ($position == $perso_pos)
		{
			$contenu_page .= '<tr>
				<td>Vous êtes parvenu au lieu indiqué. Vous ressentez comme un frétillement dans l’air.
				<br>En attendant un peu, il se pourrait qu’il se produise l’événement tant attendu...</td></tr>';
		}
		else
		{
			//	<script src="colortest/js/jquery/jquery-1.4.1.min.js" type="text/javascript"></script>
			//	<script src="colortest/js/jquery/jquery-ui-1.8rc1.custom.min.js" type="text/javascript"></script>
			$contenu_page .= '
				<style type="text/css">
					body div.boussole {
						background-image: url(\'/images/boussole1.png\');
						background-repeat: no-repeat;
					}
				</style>';
			$contenu_page .= '<tr>
				<td>
					<div class="boussole"><img src="boussole2.php?angle='.$degre.'" /></div>
				</td>
			</tr>';
			
			$contenu_page .= '<tr><td class="soustitre2">Vous devez vous confronter à vous même. Pour cela, cette boussole va vous y aider et vous guider vers la bonne destination
				</td></tr>';
		}
	}
	else if ($nombre == 2)
	{
		$contenu_page .= '<tr><td class="soustitre2">Vous êtez parvenu à vous défaire de vos démons. Rendez vous maintenant dans un centre de maîtrise magique ou un magasin runique.</td></tr>';
	}
	$contenu_page .= "</table>";
}

//Quête enchanteur
$req = "select pquete_nombre,pquete_param_texte from quete_perso where pquete_quete_cod = 15 and pquete_perso_cod = ". $perso_cod;
$stmt = $pdo->query($req); 
$result = $stmt->fetch();
if ($stmt->rowCount() != 0 && $result['pquete_nombre'] != 2)
{
	$contenu_page .= gereColonnes($colonne, $debut);
	$colonne = ($colonne + 1) % $colonneMax;
	$debut = false;

	$texte = explode(";",$result['pquete_param_texte']);
	$contenu_page .= '<p class="titre">Quête Enchanteur : questions à résoudre</p><p>'. $texte[1] .'</p>';
}

//Quête alchimiste
$req = "select pquete_nombre,to_char(pquete_date_debut,'YYYY-MM-DD') as date_deb from quete_perso where pquete_quete_cod = 14 and pquete_perso_cod = ". $perso_cod;
$stmt = $pdo->query($req); 
$result = $stmt->fetch();
if ($stmt->rowCount() != 0 && $result['pquete_nombre'] == 1)
{
	$contenu_page .= gereColonnes($colonne, $debut);
	$colonne = ($colonne + 1) % $colonneMax;
	$debut = false;

	$contenu_page .= '<table>
					<tr>
						<td class="titre"><strong>Quête alchimiste </strong></td>
					</tr>';	

	$date_deb = $result['date_deb'];
	$contenu_page .= '<tr><td class="soustitre2">Vous êtes actuellement engagé dans la quête pour devenir alchimiste</td></tr>';
	$contenu_page .= '<tr><td class="soustitre2">Vous l’avez débuté le '. $date_deb .'</td></tr>';
	$contenu_page .= '<tr><td>Pour compléter cette quête, il vous faut réaliser un contrat de chasse auprès d’un bâtiment administratif ou d’un traqueur.</td></tr>';
	$contenu_page .= "</table>";
}

//La quête des auberges
$req = "select lieu_nom from perso_auberge,lieu where paub_lieu_cod = lieu_cod and paub_visite = 'O' and paub_perso_cod = ". $perso_cod;
$stmt = $pdo->query($req); 
if ($stmt->rowCount() != 0)
{
	$contenu_page .= gereColonnes($colonne, $debut);
	$colonne = ($colonne + 1) % $colonneMax;
	$debut = false;

	$contenu_page .= '<table>
					<tr>
						<td class="titre"><strong>Liste des auberges visitées </strong><br><em>dans le cadre de la quête des tavernes</em></td>
					</tr>';	
	while($result = $stmt->fetch())
	{
		$nom_auberge = $result['lieu_nom'];
		$contenu_page .= '<tr><td class="soustitre2">'. $nom_auberge .'</td></tr>';
	}
	$contenu_page .= "</table>";
}

while ($colonne != 0)
{
	$contenu_page .= gereColonnes($colonne, $debut);
	$colonne = ($colonne + 1) % $colonneMax;
	$debut = false;
}
$contenu_page .= '</td></tr></table><br /><br />';

require_once('perso2_factions.php');
require_once('perso2_defis.php');

$contenu_page .= '<p class="titre">Contrats de chasse</p>';
//Contrats terminés
$req = "select pquete_quete_cod,pquete_date_fin,pquete_date_debut,pquete_termine,pquete_nombre,pquete_param,gmon_nom from quete_perso,monstre_generique
					where pquete_param = gmon_cod
					and pquete_perso_cod = $perso_cod
					and pquete_quete_cod in (11,12,13)
					and pquete_termine in ('O','R')";
$stmt = $pdo->query($req);

/* Modifié le 07/01/2010 par Maverick : Ajout de l'origine des contrats de chasse */
if ($stmt->rowCount() == 0)
{
	$contenu_page .= "<p>Vous n’avez terminé aucun contrat</p>";
}
else
{
	$origine_texte = ''; // Valeur texte de la provenance du contrat
	$contenu_page .= '<p align="left"><table width="60%">
						<tr>
							<td><strong>Anciens contrats validés</strong></td>
							<td><strong>Origine</strong></td>
							<td><strong><em>(Récompense obtenue)</em></strong></td>
						</tr>';
	while($result = $stmt->fetch())
	{
		if ($result['pquete_termine'] == 'O')
		{
			$recompense = 'Oui';
		}
		else
		{
			$recompense = 'Raté';	
		}

		$origine = $result['pquete_quete_cod']; // Valeur int de la provenance du contrat
		switch ($origine) {
			case '11':
				$origine_texte = 'B&acirc;timent administratif';
			break;
			case '12':
			case '13':
				$origine_texte = 'Traqueur';
			break;
			default:
				$origine_texte = 'Animation';
			break;
		}

		$contenu_page .= '<tr>
					<td class="soustitre2">' . $result['pquete_nombre'] . ' ' . $result['gmon_nom'] . '</td>
					<td class="soustitre2">' . $origine_texte . '</td>
					<td class="soustitre2">' . $recompense . '</td>
				</tr>';
	}
	$contenu_page .= "</table><br>";
}

//Contrats en cours
$req2 = "select pquete_quete_cod,to_char(pquete_date_fin,'YYYY-MM-DD') as date_fin,to_char(pquete_date_debut,'YYYY-MM-DD') as date_debut,pquete_date_fin,pquete_date_debut,pquete_termine,pquete_nombre,pquete_param,gmon_nom
					from quete_perso,monstre_generique
					where pquete_param = gmon_cod
					and pquete_perso_cod = $perso_cod
					and pquete_quete_cod in (11,12,13)
					and pquete_termine ='N'";
$stmt = $pdo->query($req2);
if ($stmt->rowCount() == 0)
{
	$contenu_page .= "<p>Vous n’avez aucun contrat en cours</p><br /><br />";
}
else
{
	$origine_texte = ''; // Valeur texte de la provenance du contrat
	$contenu_page .= '<br><table>
							<tr>
							 <td><strong>Chasse en cours</strong></td>
							 <td><strong>Origine</strong></td>
							 <td><strong>Début du contrat <br>de chasse</strong></td>
							 <td><strong>À finir avant le</strong></td>
							 <td><strong>Nombre de monstres <br>tués pour l’instant</strong></td>
							 <td><strong>Réussite possible du contrat</strong></td>
						 </tr>';
	while($result = $stmt->fetch())
	{
		$date_fin = $result['date_fin'];
		$time_fin = strtotime($date_fin);
		$date_debut = $result['date_debut'];
		$fin_mission = $result['pquete_date_fin'];
		$debut_mission = $result['pquete_date_debut'];
		$nombre_quete = $result['pquete_nombre'];
		$monstre_quete = $result['gmon_nom'];
		$monstre = $result['pquete_param'];
		$now = time();
		$origine = $result['pquete_quete_cod']; // Valeur int de la provenance du contrat
		$texte_validation = '';

		switch ($origine)
		{
			case '11':
				$origine_texte = 'B&acirc;timent administratif';
				$texte_validation = 'dans un bâtiment administratif';
				break;
			case '12':
			case '13':
				$origine_texte = 'Traqueur';
				$texte_validation = 'auprès d’un traqueur';
				break;
			default:
				$origine_texte = 'Animation';
				break;
		}
		
		$contenu_page .= '<tr>
							<td class="soustitre2">'. $nombre_quete .' '. $monstre_quete .'</td>
							<td class="soustitre2">' . $origine_texte . '</td>
							<td class="soustitre2">'. $date_debut .'</td>
							<td class="soustitre2">'. $date_fin .'</td>';
		$req = "select sum(ptab_total) as total from perso_tableau_chasse
										where ptab_gmon_cod = $monstre
										and ptab_perso_cod = $perso_cod
										and ptab_date > '$debut_mission' 
										and ptab_date < '$fin_mission'";
		$stmt2 = $pdo->query($req);
		$result2 = $stmt2->fetch();
		$total = $result2['total'];
								
		$contenu_page .= '<td class="soustitre2">'. $total .'</td>';
		if ($time_fin < $now)
		{
			if ($total >= $nombre_quete)
			{
				$contenu_page .= '<td class="soustitre2"><em>Vous avez respecté votre engagement pour ce contrat !<br />
					Il vous faut maintenant aller le valider '.$texte_validation.', pour récupérer la récompense associée.</em></td></tr>';
			}
			else
			{
				$contenu_page .= '<td class="soustitre2"><em>Ce contrat a été raté. Le quota de la chasse n’a pas été respecté, et elle est échue.</em></td></tr>';
			}
		}
		else
		{
			$contenu_page .= '<td class="soustitre2"><em>Contrat en cours de réalisation</em></td></tr>';
		}
	}
	$contenu_page .= "</table><br></p>";
}
/* Fin modif */

//Liste du tableau de chasse
if (!isset($ordreaff))
{
	$ordreaff = 0;
}
switch ($ordreaff)
{
	case 0: $orderby = 'race_nom, gmon_nom'; break;						// Par famille et par nom
	case 1: $orderby = 'gmon_nom'; break;								// Par nom
	case 2: $orderby = 'gmon_niveau DESC'; break;						// Par niveau
	case 3: $orderby = 'race_nom, gmon_niveau DESC, gmon_nom'; break;	// Par famille et par niveau
	case 4: $orderby = 'sum(ptab_total) DESC, gmon_nom'; break;			// Par total
	case 5: $orderby = 'sum(ptab_solo) DESC, gmon_nom'; break;			// Par total solo
	default: $orderby = 'race_nom, gmon_nom'; break;					// Par famille et par nom
}

$req = "select gmon_nom, race_nom, gmon_niveau, sum(ptab_total) as total, sum(ptab_solo) as solo from perso_tableau_chasse
		inner join monstre_generique on ptab_gmon_cod = gmon_cod
		inner join race on race_cod = gmon_race_cod
		where ptab_perso_cod = $perso_cod 
		group by gmon_nom, race_nom, gmon_niveau
		order by $orderby";
$stmt = $pdo->query($req);
if ($stmt->rowCount() != 0)
{
	$contenu_page .= '<p class="titre">Tableau de chasse</p>';
	$contenu_page .= '<p class="soustitre2">Trier par... <a href="?m=5&ordreaff=1">nom</a> - <a href="?m=5&ordreaff=2">niveau</a> - 
							<a href="?m=5&ordreaff=0">race puis nom</a> - <a href="?m=5&ordreaff=3">race et niveau</a> - 
							<a href="?m=5&ordreaff=4">total</a> - <a href="?m=5&ordreaff=5">total solo</a></p>';
	$contenu_page .= '<table width="100%">';
	$contenu_page .= '<tr><td class="soustitre2"><strong>Monstre</strong></td><td class="soustitre2"><strong>Race</strong></td><td class="soustitre2"><strong>Total<br />achevé</strong></td><td class="soustitre2"><strong>Total<br />achevé<br />en solo</strong></td></tr>';
	while($result = $stmt->fetch())
	{
		$contenu_page .= '<tr><td class="soustitre2">' . $result['gmon_nom'] . '</td>';
		$contenu_page .= '<td class="soustitre2">' . $result['race_nom'] . '</td>';
		$contenu_page .= '<td class="soustitre2">' . $result['total'] . '</td>';
		$contenu_page .= '<td class="soustitre2">' . $result['solo'] . '</td></tr>';
	}
	$contenu_page .= "</table><p>Monstre achevé en solo = 1 seul bénéficiaire au partage d'XP</p>";
}

