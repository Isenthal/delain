<?php
include "blocks/_header_page_jeu.php";
ob_start();
echo "<table cellspacing=\"1\" cellpadding=\"1\">";
$req = "select cherche_multi(5) as resultat ";
$db->query($req);
$db->next_record();
$chaine = $db->f("resultat");
$longueur = strlen($chaine);
$erreur = 0;
if ($longueur == 0)
{
	echo "<p>Aucun enregistrement !";
	$erreur = 1;
}
if ($erreur == 0)
{
	$chaine = substr($chaine,0,($longueur - 1));
	$tab1 = explode("#",$chaine);
	$nb1 = count($tab1);
	for ($cpt=0;$cpt<$nb1;$cpt++)
	{
		echo "<tr>";
		$tab2 = explode(";",$tab1[$cpt]);
		$req = "select compt_nom from compte where compt_cod = $tab2[0]";
		$db->query($req);
		$db->next_record();
		echo "<td class=\"soustitre2\"><p><a href=\"detail_compte.php?compte=" . $tab2[0] . "\">" . $db->f("compt_nom") . "</td>";
		
		$req = "select compt_nom from compte where compt_cod = $tab2[1]";
		$db->query($req);
		$db->next_record();
	echo "<td class=\"soustitre2\"><p><a href=\"detail_compte.php?compte=" . $tab2[1] . "\">" . $db->f("compt_nom") . "</td>";
	echo "<td><p>(" . $tab2[2] . ")</td>";
		echo "</tr>";
		
	}
}
echo "</table>";
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
