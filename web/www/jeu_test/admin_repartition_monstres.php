<?php
include "blocks/_header_page_jeu.php";
ob_start();
$db2 = new base_delain;
$erreur = 0;
$req = "select dcompt_modif_perso, dcompt_modif_gmon, dcompt_controle, dcompt_creer_monstre from compt_droit where dcompt_compt_cod = $compt_cod ";
$db->query($req);
if ($db->nf() == 0)
{
	$droit['modif_perso'] = 'N';
	$droit['modif_gmon'] = 'N';
	$droit['controle'] = 'N';
	$droit['creer_monstre'] = 'N';
}
else
{
	$db->next_record();
	$droit['modif_perso'] = $db->f("dcompt_modif_perso");
	$droit['modif_gmon'] = $db->f("dcompt_modif_gmon");
	$droit['controle'] = $db->f("dcompt_controle");
	$droit['creer_monstre'] = $db->f("dcompt_creer_monstre");
}
if ($droit['modif_gmon'] != 'O')
{
	echo "<p>Erreur ! Vous n'avez pas accès à cette page !";
	$erreur = 1;
}
if ($erreur == 0)
{
	include "admin_edition_header.php";

	// TRAITEMENT DE FORMULAIRE
	if(isset($_POST['methode']))
	{
		switch ($methode) {
			case "ajouter":
				$req = "insert into repart_monstre (rmon_gmon_cod,rmon_etage_cod,rmon_poids, rmon_max) values ($gmon_cod,$pos_etage,$poids, $rmon_max) ";
				$db->query($req);
				echo "<p>AJOUTE</p>";
			break;

			case "modifier":
				$req = "update repart_monstre set rmon_poids = $poids, rmon_max = $rmon_max where rmon_cod = $rmon_cod ";
				$db->query($req);
				echo "<p>MODIFIER</p>";
			break;

			case "supprimer":
				$req = "delete from repart_monstre where rmon_cod = $rmon_cod and rmon_etage_cod = $pos_etage ";
				$db->query($req);
				echo "<p>SUPPRIMER</p>";
			break;

			case "modifier_proportion":
				$req = "select rjmon_repart,rjmon_type from rep_mon_joueur where  rjmon_etage = $pos_etage";
				$db->query($req);
				if($db->next_record())
				{
					// UPDATE
					$req = "update rep_mon_joueur set rjmon_repart =  $rjmon_repart,rjmon_type = '$rjmon_type' where  rjmon_etage = $pos_etage";
					$db->query($req);
				} else
				{
					// INSERT
					$req = "insert into rep_mon_joueur (rjmon_repart,rjmon_type,rjmon_etage) values ($rjmon_repart,'$rjmon_type',$pos_etage)";
					$db->query($req);

				}
			break;
		}
	}

?>
REPARTION DES MONSTRES PAR ÉTAGE :
<form method="post">
	Étage : <select name="pos_etage">
	<?php 
		echo $html->etage_select($pos_etage);
	?>
	</select><br>
<input type="submit" value="Valider" class='test'>
</form>
<hr>
<?php if(isset($_POST['pos_etage']))
{
	$req = "select etage_libelle,count(ppos_perso_cod) as perso_presents from etage,perso_position,positions,perso where  etage_numero = $pos_etage
		and ppos_pos_cod = pos_cod and pos_etage = etage_numero 
		and ppos_perso_cod = perso_cod
		and perso_type_perso = 1
		and perso_actif = 'O'
		group by etage_libelle";
	$db->query($req);
	$db->next_record();
	$req_monstre = "select etage_libelle,count(ppos_perso_cod) as perso_presents from etage,perso_position,positions,perso where  etage_numero = $pos_etage
		and ppos_pos_cod = pos_cod and pos_etage = etage_numero 
		and ppos_perso_cod = perso_cod
		and perso_type_perso = 2
		and perso_actif = 'O'
		group by etage_libelle";
	$db2->query($req_monstre);
	$db2->next_record();?>
<p><strong>RÉPARTITION DES MONSTRES pour l’étage: <?php  echo $db->f("etage_libelle");?></strong> / Nombre de persos présents : <?php  echo $db->f("perso_presents");?> / Nombre de monstres présents : <?php  echo $db2->f("perso_presents");?></p><br>
<p>
<?php 
$rjmon_repart = 0.0;
$rjmon_type = '';
$req = "select rjmon_repart, rjmon_type from rep_mon_joueur where rjmon_etage = $pos_etage";
$db->query($req);
if($db->next_record())
{
	$rjmon_repart = $db->f("rjmon_repart");
	$rjmon_type = $db->f("rjmon_type");
}
?>
	<form method="post" name="modif_proportion">
		<input type="hidden" name="methode" value="modifier_proportion">
		<input type="hidden" name="pos_etage" value="<?php echo $pos_etage;?>">
		Proportion de monstres pour cet étage : Ratio = <input type="text" name="rjmon_repart" value="<?php  echo $rjmon_repart; ?>">
		Type(P ou H) = <input type="text" name="rjmon_type" value="<?php  echo $rjmon_type; ?>"> <input type="submit" value="Mettre à jour" class='test'>
	</form>
</p>
<p>
	<table width="80%" border="1">
		<TR>
			<TH width="20%" align="center" valign="top" nowrap="nowrap">Monstre</TH>
			<TH width="20%" align="center" valign="top" nowrap="nowrap">Poids / nombre présents</TH>
			<TH width="20%" align="center" valign="top" nowrap="nowrap">Nombre maximal (0 = pas de maximum)</TH>
			<TH width="20%" align="center" valign="top" nowrap="nowrap">Modifier</TH>
			<TH width="20%" align="center" valign="top" nowrap="nowrap">Supprimer</TH>
		</TR>
<?php 
	$req = "select rmon_cod, mg.gmon_cod, mg.gmon_nom, rmon_poids, rmon_max, coalesce(monstres.nombre, 0) as nombre
		from repart_monstre
		inner join monstre_generique mg ON mg.gmon_cod = rmon_gmon_cod
		left outer join
			(select gmon_cod, count(*) as nombre
			from perso
			inner join monstre_generique on gmon_cod = perso_gmon_cod
			inner join perso_position on ppos_perso_cod = perso_cod
			inner join positions on ppos_pos_cod = pos_cod
			where pos_etage = $pos_etage
				and perso_actif = 'O'
			group by gmon_cod, gmon_nom) monstres
			on monstres.gmon_cod = rmon_gmon_cod
		where rmon_etage_cod = $pos_etage
		order by mg.gmon_nom";
	$db->query($req);
	while($db->next_record())
	{
?>
	<form method="post" name="modif_mon_<?php  echo  $db->f("rmon_cod"); ?>">
		<input type="hidden" name="methode" value="modifier">
		<input type="hidden" name="pos_etage" value="<?php echo $pos_etage;?>">
		<input type="hidden" name="rmon_cod" value="<?php  echo  $db->f("rmon_cod"); ?>">
		<TR>
			<TD><?php echo '<strong>' . $db->f("gmon_nom") . '</strong><em> (code = ' . $db->f("gmon_cod") . ')</em>';?></TD>
			<TD><p align="center"><input type="text" name="poids" value="<?php  echo  $db->f("rmon_poids"); ?>"> / <?php  echo  $db->f("nombre"); ?></p></TD>
			<TD><p align="center"><input type="text" name="rmon_max" value="<?php  echo  $db->f("rmon_max"); ?>"></p></TD>
			<TD><p align="center"><input type="submit" value="Modifier"></p></TD>
			<TD><p align="center"><input type="submit" value="Supprimer" onClick="document.modif_mon_<?php  echo  $db->f("rmon_cod"); ?>.methode.value='supprimer'"></p></TD>
		</TR>
	</form>
<?php 	} ?>
	</table>
</p>
Ajouter un nouveau monstre :
<form method="post">
	<input type="hidden" name="methode" value="ajouter">
	<input type="hidden" name="pos_etage" value="<?php echo $pos_etage;?>">
	Monstre : <select name="gmon_cod">
<?php 
	$req = "select gmon_cod,gmon_nom from monstre_generique order by gmon_nom ";
	$db->query($req);
	while($db->next_record())
	{
		echo "<option value=\"" , $db->f("gmon_cod") , "\" >" , $db->f("gmon_nom") , "</option>";
	}
?>
	</select>
	Poids : <input type="text" name="poids" value="0">
	Nombre maximal : <input type="text" name="rmon_max" value="0">
	<input type="submit" value="Ajouter" class='test'>
</form>
<?php }
}
?>
<p style="text-align:center;"><a href="<?php echo$PHP_SELF ?>">Retour au début</a>
<?php $contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
