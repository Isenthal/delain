<?php
include "blocks/_header_page_jeu.php";
ob_start();
$erreur = 0;
$perso  = new perso;
$perso  = $verif_connexion->perso;
if ($perso->is_milice() == 0)
{
	echo "<p>Erreur ! Vous n'averz pas accès à cette page !";
	$erreur = 1;
}
$lieu['entree'] = 15;
$lieu['bat_adm'] = 9;
$lieu['poste_garde'] = 5;
if (!$perso->is_lieu())
{
	echo("<p>Erreur ! Vous n'êtes pas sur un lieu permettant cette action !!!");
	$erreur = 1;
}
if ($erreur == 0)
{
	$suite = 1;
	$tab_lieu = $perso->get_lieu();
	if (!in_array($tab_lieu['lieu_type']->tlieu_cod, $lieu))
	{
   	echo "<p>Erreur ! Le lieu sur lequel vous vous trouvez ne permet pas cette action !";
   	$suite = 0;
	}
	$etage_min = $parm->getparm(67);
	$req = "select pos_etage from positions,perso_position ";
	$req = $req . "where ppos_perso_cod = $perso_cod and ppos_pos_cod = pos_cod ";
	$stmt = $pdo->query($req);
	$result = $stmt->fetch();
	if (($result['pos_etage'] > 0) || ($result['pos_etage'] < -3))
	{
		echo "<p>Erreur ! Le lieu sur lequel vous vous trouvez ne permet pas cette action !";
   	$suite = 0;
	}
	if ($suite == 1)
	{
		$req = "select ppos_pos_cod from perso_position where ppos_perso_cod = $perso_cod ";
		$stmt = $pdo->query($req);
		$result = $stmt->fetch();
		$pos_actu = $result['ppos_pos_cod'];
		echo "<p>Liste des destinations possibles (cliquez sur un lieu pour vous y rendre - ", $parm->getparm(68) , " PA):";
		echo "<table>";
		$req = "select pos_cod,lieu_nom,pos_x,pos_y,etage_libelle,pos_etage ";
		$req = $req . "from lieu,lieu_position,positions,etage ";
		$req = $req . "where lieu_tlieu_cod in (15,9,5) ";
		$req = $req . "and lpos_lieu_cod = lieu_cod ";
		$req = $req . "and lpos_pos_cod = pos_cod ";
		$req = $req . "and pos_cod != $pos_actu ";
		$req = $req . "and pos_etage <= 0 ";
		$req = $req . "and pos_etage >= $etage_min ";
		$req = $req . "and etage_numero = pos_etage ";
		$req = $req . "order by pos_etage desc, lieu_nom ";
		$stmt = $pdo->query($req);
		while ($result = $stmt->fetch())
		{
			echo "<tr>";
			echo "<td class=\"soustitre2\"><strong><a href=\"action.php?methode=milice_tel&destination=" , $result['pos_cod'] , "\">" , $result['lieu_nom'] , "</a><strong></td>";
			echo "<td>" , $result['pos_x'] , "</td>";
			echo "<td class=\"soustitre2\">" , $result['pos_y'] , "</td>";
			echo "<td>" , $result['etage_libelle'] , "</td>";
			echo "</tr>";
		}
		echo "</table>";



		
	}	
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";

