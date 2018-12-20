<?php // on regarde si le joueur est bien sur un lieu
$erreur = 0;
if (!$db->is_lieu($perso_cod))
{
	echo("<p>Erreur ! Vous n’êtes pas sur un lieu !!!</p>");
	$erreur = 1;
}
if ($erreur == 0)
// On commence alors réellement l’include, permettant d’intégrer des quêtes aux bâtiments
{
	$tab_lieu = $db->get_lieu($perso_cod);

	switch ($tab_lieu['type_lieu'])
	{
		case 1: require_once('quete.lieu.banque.php');	break;
		case 2: require_once('quete.lieu.dispensaire.php');	break;
		case 4: require_once('quete.lieu.auberge.php');	break;
		case 9: require_once('quete.lieu.bat_adm.php');	break;
		case 13: require_once('quete.lieu.centre_maitrise_magique.php');	break;
		case 14: require_once('quete.lieu.centre_maitrise_magique.php');	break;
		case 15: require_once('quete.lieu.poste_garde.php');	break;
		case 17: require_once('quete.lieu.temple.php');	break;
		case 23: require_once('quete.lieu.poste_garde.php');	break;
		case 27: require_once('quete.lieu.poste_garde.php');	break;
		default: break;
	}
}