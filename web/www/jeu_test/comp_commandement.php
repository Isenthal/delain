<?php
include "blocks/_header_page_jeu.php";
ob_start();
?>
    <script language="javascript" src="../scripts/cocheCase.js"></script>

<?php
$db2 = new base_delain;
/*********************/
/* COMMANDEMENT : 80 */
/*********************/
$db = new base_delain;
$req_comp = "select pcomp_modificateur from perso_competences ";
$req_comp = $req_comp . "where pcomp_perso_cod = $perso_cod ";
$req_comp = $req_comp . "and pcomp_modificateur != 0 ";
$req_comp = $req_comp . "and pcomp_pcomp_cod = 80";
$db->query($req_comp);

$erreur = true;
if ($db->next_record())
{
    $valeur_comp = $db->f("pcomp_modificateur");
    $commandant_cod = $perso_cod;
    $erreur = false;
} else
{
    $req_comp = "select perso_superieur_cod from perso_commandement where $perso_cod = perso_subalterne_cod";
    $db->query($req_comp);
    if ($db->next_record())
    {
        $valeur_comp = 0;
        $commandant_cod = $db->f("perso_superieur_cod");
        $erreur = false;
    }
}

$erreur = $erreur || !$db->is_admin_monstre($compt_cod);

if (!$erreur)
{

	$req_vue = "select distance_vue($perso_cod) as distance_vue, ppos_pos_cod, pos_etage, pos_x, pos_y, perso_nom "
		. "from perso "
		. "inner join perso_position on ppos_perso_cod = perso_cod "
		. "inner join positions on pos_cod = ppos_pos_cod "
		. "where perso_cod = $perso_cod";
	$db->query($req_vue);
	$db->next_record();
	$vue = $db->f("distance_vue");
	$x = $db->f("pos_x");
	$y = $db->f("pos_y");
	$etage = $db->f("pos_etage");
	$perso_nom = $db->f("perso_nom");
	$req_troupe = "delete from perso_commandement where not exists(select 1 from perso where perso_actif = 'O' and perso_cod = perso_subalterne_cod)";
	$db->query($req_troupe);
	$methode = (isset($_POST['methode'])) ? $_POST['methode'] : ((isset($_GET['methode'])) ? $_GET['methode'] : false);

	// TRAITEMENT DE FORMULAIRE
	switch ($methode)
	{
		case "ajouter_subalterne":
			// Ajout au commandement
			$req_troupe = "select ajoute_commandement($commandant_cod, $nouv_perso_cod, false) as resultat";
			$db->query($req_troupe);
			$db->next_record();
			echo '<p>' . $db->f('resultat') . '</p>';

			// Ajout au compte de l’admin
			$req_compte = "delete from perso_compte where pcompt_perso_cod = $nouv_perso_cod";
			$db->query($req_compte);
			$req_compte = "insert into perso_compte (pcompt_perso_cod, pcompt_compt_cod) values ($nouv_perso_cod, $compt_cod)";
			$db->query($req_compte);
		break;

		case "changer_description":
			$erreur = 0;
			$description = pg_escape_string(htmlspecialchars(str_replace('\'', '’', $description)));
    		if (strlen($description)>=254)
			{
				echo "<p>Votre description est trop longue (max 254 caractères), merci de la raccourcir !<br></p>";
				$erreur = 1;
			}
    		if (!isset($_POST['subalterne_cod']))
			{
				echo "<p>Erreur de paramètres : les subalternes n’ont pas été sélectionnés.</p>";
				$erreur = 1;
			}
			if ($erreur == 0)
			{
				$in_val = "";
				$array = $_POST['subalterne_cod'];
				foreach ($array as $i => $value) {
					$in_val.=$value.",";
				}
				$in_val = substr($in_val, 0, strlen($in_val)-1);
				$req_desc = "update perso set perso_description = '$description' where perso_cod in ($in_val) ";
				//echo $req_desc;
				$db->query($req_desc);
				echo("<p>La description est enregistrée !<br></p>");
			}
		break;

		case "changer_statique":
			$in_val = "";
        	if (!isset($_POST['subalterne_cod']))
			{
				echo "<p>Erreur de paramètres : les subalternes n’ont pas été sélectionnés.</p>";
			}
            else
            {
                $array = $_POST['subalterne_cod'];
                foreach ($array as $i => $value) {
                    $in_val.=$value.",";
                }
                $in_val = substr($in_val, 0, strlen($in_val)-1);
                $req_desc = "update perso set perso_sta_combat = '$perso_sta_combat', perso_sta_hors_combat = '$perso_sta_hors_combat'
                    where perso_cod in ($in_val) ";
                //echo $req_desc;
                $db->query($req_desc);
                echo("<p>Les propriétés statiques sont enregistrée !<br></p>");
            }
		break;

		case "hors_ia":
			$in_val = "";
            if (!isset($_POST['subalterne_cod']))
			{
				echo "<p>Erreur de paramètres : les subalternes n’ont pas été sélectionnés.</p>";
			}
            else
            {
                $array = $_POST['subalterne_cod'];
                foreach ($array as $i => $value) {
                    $in_val.=$value.",";
                }
                $in_val = substr($in_val, 0, strlen($in_val)-1);
                $req_desc = "update perso set perso_dirige_admin = '$hors_ia' where perso_cod in ($in_val) ";
                //echo $req_desc;
                $db->query($req_desc);
                echo("<p>L’utilisation de l’IA a été enregistrée !<br></p>");
            }
		break;

		case "renvoyer":
			$in_val = "";
            if (!isset($_POST['subalterne_cod']))
			{
				echo "<p>Erreur de paramètres : les subalternes n’ont pas été sélectionnés.</p>";
			}
            else
            {
                $array = $_POST['subalterne_cod'];
                foreach ($array as $i => $value) {
                    $in_val.=$value.",";
                }
                $in_val = substr($in_val, 0, strlen($in_val)-1);
                $req_troupe = "delete from perso_commandement where perso_subalterne_cod in ($in_val)";
                $db->query($req_troupe);
                
                // Renvoi de la coterie, si le commandant en fait partie
                $req_coterie = "select pgroupe_groupe_cod from groupe_perso where pgroupe_perso_cod = $commandant_cod and pgroupe_statut <> 0";
                $db->query($req_coterie);
                $pgroupe_groupe_cod = -1;
                if ($db->next_record())
                	$pgroupe_groupe_cod = $db->f('pgroupe_groupe_cod');
                
                $req_coterie = "delete from groupe_perso where pgroupe_perso_cod in ($in_val) and pgroupe_groupe_cod = $pgroupe_groupe_cod";
                $db->query($req_coterie);
                
                // Renvoi du compte de l’admin
                $req_compte = "delete from perso_compte where pcompt_perso_cod in ($in_val) and pcompt_compt_cod = $compt_cod";
                $db->query($req_compte);
            }
		break;

		case "modifier_IA":
            if (!isset($_POST['subalterne_cod']))
    		{
				echo "<p>Erreur de paramètres : les subalternes n’ont pas été sélectionnés.</p>";
			}
            else
            {
    			$array = $_POST['subalterne_cod'];
    			foreach ($array as $i => $mon_cod) {
    				if($pia_ia_type != -1){
    					$req = "update perso set perso_dirige_admin = 'N' where perso_cod = $mon_cod";
    					$db->query($req);
    					$req = "delete from perso_ia where pia_perso_cod = $mon_cod";
    					$db->query($req);
    					$req = "insert into perso_ia (pia_perso_cod,pia_ia_type) values ($mon_cod,$pia_ia_type)";
    					$db->query($req);
    				} else {
    					$req = "update perso set perso_dirige_admin = 'N' where perso_cod = $mon_cod";
    					$db->query($req);
    					$req = "delete from perso_ia where pia_perso_cod = $mon_cod";
    					$db->query($req);
    				}
    			}
            }
		break;

		case "modifier_IA_pos":
			$erreur = 0;
			$req = "select pos_cod from positions where pos_x = $pos_x and pos_y = $pos_y and pos_etage = $etage ";
			$db->query($req);
			if ($db->nf() == 0)
			{
				echo "<p>Aucune position trouvée à ces coordonnées.<br></p>";
				$erreur = 1;
			}
			$db->next_record();
			$pos_cod = $db->f("pos_cod");
			$req = "select mur_pos_cod from murs where mur_pos_cod = $pos_cod ";
			$db->query($req);
			if ($db->nf() != 0)
			{
				echo "<p>Impossible d’aller sur cette position : un mur en destination.<br></p>";
				$erreur = 1;
			}
        	if (!isset($_POST['subalterne_cod']))
			{
				echo "<p>Erreur de paramètres : les subalternes n’ont pas été sélectionnés.</p>";
				$erreur = 1;
			}
			if ($erreur == 0)
			{
				$array = $_POST['subalterne_cod'];
				foreach ($array as $i => $mon_cod) {
					if($pia_ia_type_pos != -1){
						$req = "update perso set perso_dirige_admin = 'N' where perso_cod = $mon_cod";
						$db->query($req);
						$req = "delete from perso_ia where pia_perso_cod = $mon_cod";
						$db->query($req);
						$req = "insert into perso_ia (pia_perso_cod,pia_ia_type,pia_parametre) values ($mon_cod,$pia_ia_type_pos,$pos_cod)";
						$db->query($req);
					} else {
						$req = "update perso set perso_dirige_admin = 'N' where perso_cod = $mon_cod";
						$db->query($req);
						$req = "delete from perso_ia where pia_perso_cod = $mon_cod";
						$db->query($req);
					}
				}
			}
		break;

		case "modifier_IA_cib":
			$erreur = 0;
        	if (!isset($_POST['subalterne_cod']))
			{
				echo "<p>Erreur de paramètres : les subalternes n’ont pas été sélectionnés.</p>";
				$erreur = 1;
			}
			if ($erreur == 0)
			{
				$array = $_POST['subalterne_cod'];
				foreach ($array as $i => $mon_cod) {
					if($pia_ia_type_cib != -1){
						$req = "update perso set perso_dirige_admin = 'N' where perso_cod = $mon_cod";
						$db->query($req);
						$req = "delete from perso_ia where pia_perso_cod = $mon_cod";
						$db->query($req);
						$req = "insert into perso_ia (pia_perso_cod,pia_ia_type,pia_parametre) values ($mon_cod,$pia_ia_type_cib,$cible_cod)";
						$db->query($req);
					} else {
						$req = "update perso set perso_dirige_admin = 'N' where perso_cod = $mon_cod";
						$db->query($req);
						$req = "delete from perso_ia where pia_perso_cod = $mon_cod";
						$db->query($req);
					}
				}
			}
		break;

		case "lancer_ia":
            if (!isset($_GET['cod_monstre']))
			{
				echo "<p>Erreur de paramètres : le monstre dont l’IA doit être lancé n’a pas été sélectionné.</p>";
				$erreur = 1;
			}
			$cod_monstre = $_GET['cod_monstre'];
			$req = "select ia_monstre($cod_monstre) as resultat";
			$db->query($req);
			$db->next_record();
			echo "<p>Résultat de l’IA :</p>";
			echo "<p>" . $db->f('resultat') . "</p>";
		break;
	}

	?>
	<h1>Commandement</h1>
	<p>Nombre maximal de troupes : <?php  echo $valeur_comp ?> </p>
	<p><strong> Donner des ordres : </strong></p>
	<form method="post" name="troupes" action="comp_commandement.php">
		<input type="hidden" name="methode" value="">
		<table  border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr><td class="soustitre2" width="20%">Nom
				</td><td class="soustitre2" width="10%">Position
				</td><td class="soustitre2" width="10%">DLT
				</td><td class="soustitre2" width="7%">PA
				</td><td class="soustitre2" width="15%">Etat
				</td><td class="soustitre2" width="10%">IA Actuelle
				</td><td class="soustitre2" width="10%">Statique En combat/hors Combat
				</td><td class="soustitre2" width="25%">Locks combat
				</td><td class="soustitre2" width="25%">Lancer l’IA manuellement
				</td>
			</tr>
	<?php 
	$req_troupe = "select perso_sta_combat, perso_sta_hors_combat, perso_cod as perso_subalterne_cod, perso_nom, perso_description,
			perso_pv, perso_pv_max, perso_pa, perso_dirige_admin, pos_x, pos_y, to_char(perso_dlt,'DD/MM/YYYY hh24:mi:ss') as dlt,
			dlt_passee(perso_cod) as dlt_passee
		from perso
		inner join perso_position ON ppos_perso_cod = perso_cod
		inner join positions ON pos_cod = ppos_pos_cod
		where perso_cod = $commandant_cod
	UNION ALL
		select perso_sta_combat, perso_sta_hors_combat, perso_subalterne_cod, perso_nom, perso_description,
			perso_pv, perso_pv_max, perso_pa, perso_dirige_admin, pos_x, pos_y, to_char(perso_dlt,'DD/MM/YYYY hh24:mi:ss') as dlt,
			dlt_passee(perso_cod) as dlt_passee
		from perso
		inner join perso_commandement ON perso_subalterne_cod = perso_cod
		inner join perso_position ON ppos_perso_cod = perso_cod
		inner join positions ON pos_cod = ppos_pos_cod
		where perso_superieur_cod = $commandant_cod";
	$db->query($req_troupe);
	$nb_subalternes = $db->nf();
	$db2 = new base_delain;
	$n = 0;
	while($db->next_record()){
		$n++;
		$cod_monstre = $db->f("perso_subalterne_cod");
		$monstre_ia = $db->f("perso_dirige_admin");
		if(fmod($n,2) == 0){
			$cl = "class=\"soustitre2\"";
		} else {
			$cl = "";
		}
		$req_ia = "select ia_type,ia_nom,pia_parametre from type_ia,perso_ia where pia_ia_type = ia_type and pia_perso_cod = ".$db->f("perso_subalterne_cod");
		$db2->query($req_ia);
		if($db2->next_record())
		{
			$ia = $db2->f("ia_nom");
    	    $ia_param = $db2->f("pia_parametre");
    	    
    	    if ($ia == null || $ia_param == null)
			{
				$ia = "Par défaut";
			    $ia_param = false;
			}
    	    
		}
		else
		{
			$ia = "Par défaut";
    	    $ia_param = false;
		}
		if(strpos($ia,'[position]') != false && $ia_param !== false)
		{
			$req_pos = "select pos_x, pos_y from positions where pos_cod = $ia_param";
			$db2->query($req_pos);
			$db2->next_record();
			$ia .= " (".$db2->f("pos_x").",".$db2->f("pos_y").")";
		}
		if(strpos($ia,'[cible]')  != false && $ia_param !== false)
		{
			$req_pos = "select perso_nom from perso where perso_cod = $ia_param";
			$db2->query($req_pos);
			$db2->next_record();
			$ia .= " (".$db2->f("perso_nom").")";
		}
		$image = ($n == 1) ? '<img src="' . G_IMAGES . 'commandant.png" title="Commandant" />' : '';
		$checkbox = ($commandant_cod == $perso_cod) ? '<input type="checkbox" name="subalterne_cod[]" value="' . $db->f("perso_subalterne_cod") . '">' : '';
	?>
			<tr><td <?php  echo $cl;?>>
				<?php  echo "$image $checkbox";?> <a href="visu_evt_perso.php?visu=<?php  echo $db->f("perso_subalterne_cod"); ?>"><?php  echo $db->f("perso_nom");?></a>
					<a href="../validation_login_monstre.php?numero=<?php  echo $db->f("perso_subalterne_cod"); ?>&compt_cod=<?php echo  $compt_cod; ?>">(jouer)</a>
				</td><td <?php  echo $cl;?>>
				(<?php  echo $db->f("pos_x");?>,<?php  echo $db->f("pos_y");?>)
				</td><td <?php  echo $cl;?>>
	<?php 
		if ($db->f("dlt_passee") == 1)
		{
			echo("<strong>");
		}
		echo $db->f("dlt");
		if ($db->f("dlt_passee") == 1)
		{
			echo("</strong>");
		}?>
				</td><td <?php  echo $cl;?>>
					<?php echo $db->f("perso_pa");?> / 12
				</td><td <?php  echo $cl;?>>
					(<?php echo $db->f("perso_pv");?> / <?php echo $db->f("perso_pv_max");?> PV)
				</td><td <?php  echo $cl;?>>
	<?php 
		if ($monstre_ia == 'N')
		{
			echo $ia;
		}
		else
		{
			echo "<strong>Hors IA</strong>";
		}
	?>
				</td>
				<td <?php  echo $cl;?>>
					<?php echo $db->f("perso_sta_combat");?> / <?php echo $db->f("perso_sta_hors_combat");?>
				</td>
				<td <?php  echo $cl;?>>
	<?php 
		$req = "select perso_nom from perso inner join
			(select lock_attaquant as lock from lock_combat
			where lock_cible = $cod_monstre
			union all select lock_cible as lock from lock_combat
			where lock_attaquant = $cod_monstre) as t2 on perso.perso_cod = t2.lock group by perso_nom";
		$db2->query($req);
		while ($db2->next_record())
		{
			echo $db2->f("perso_nom") . "<br>";
		}
	?>
				</td>
				<td <?php  echo $cl;?>>
					<p><a href="<?php echo  $PHP_SELF; ?>?methode=lancer_ia&cod_monstre=<?php echo $cod_monstre;?>">Lancer l’IA du <?php echo $cod_monstre;?>
				</td>
			</tr>
			<tr><td <?php  echo $cl;?> colspan="9">
				<p style="font-size:7pt;"><?php echo $db->f("perso_description");?></p></td></tr>

	<?php 	}
	echo '</table>';
	if ($commandant_cod == $perso_cod)
	{
	?>
		<a style="font-size:7pt;" href="javascript:toutCocher(document.troupes,'subalterne_cod');">cocher/décocher/inverser</a><br><br><br>
		<textarea name="description" rows="8" cols="25"></textarea><br><br>
		<input type="submit" value="Changer la description" onClick="methode.value='changer_description'" class="test"><br><br>
		Statique en combat : Oui<input type="radio" name="perso_sta_combat" value="O"> Non<input checked type="radio" name="perso_sta_combat" value="N"><br>
		Statique hors combat : Oui<input type="radio" name="perso_sta_hors_combat" value="O"> Non<input checked type="radio" name="perso_sta_hors_combat" value="N"><br>
		<input type="submit" value="Changer le statisme" onClick="methode.value='changer_statique'" class="test"><br><br>
		Monstres hors IA : Oui<input type="radio" name="hors_ia" value="O"> Non<input checked type="radio" name="hors_ia" value="N"><br>
		<input type="submit" value="Passer en IA ou Hors IA" onClick="methode.value='hors_ia'" class="test"><br><br>
		<input type="submit" value="Renvoyer de la troupe" onClick="methode.value='renvoyer'" class="test"><br><br>
	<?php 
		$array_ia = array();
		$req = "select ia_type,ia_nom from type_ia order by ia_type desc ";
		$db->query($req);
		while($db->next_record())
		{
			$array_ia[$db->f("ia_type")] = $db->f("ia_nom");
		}
	?>
		<!-- IA CLASSIQUE -->
		Modifier l’IA : <select name="pia_ia_type">
			<option value="-1">Aucune (IA par défaut)</option>
	<?php 
		foreach ($array_ia as $key => $nom) {
			if(strpos($nom,'[position]') == false && strpos($nom,'[cible]') == false){
				echo "<option value=\"" , $key , "\">" , $nom , "</option>";
			}
		}
	?>
		</select>
		<input type="submit" value="Modifier l’IA" onClick="methode.value='modifier_IA'" class="test"><br><br>
		<!-- IA LOCALISEE-->
		Modifier l’IA : <select name="pia_ia_type_pos">
			<option value="-1">Aucune (IA par défaut)</option>
	<?php 
		foreach ($array_ia as $key => $nom) {
			if(strpos($nom,'[position]') == true){
				echo "<option value=\"" , $key , "\">" , $nom , "</option>";
			}
		}
	?>
		</select>
		X : <input type="text" name="pos_x"> Y : <input type="text" name="pos_y">
		<input type="submit" value="Modifier l’IA (Localisée)" onClick="methode.value='modifier_IA_pos'" class="test">
		<br><br>
	<!-- IA CIBLEE-->
		Modifier l’IA : <select name="pia_ia_type_cib">
			<option value="-1">Aucune (IA par défaut)</option>
	<?php 
		foreach ($array_ia as $key => $nom) {
			if(strpos($nom,'[cible]') == true){
				echo "<option value=\"" , $key , "\">" , $nom , "</option>";
			}
		}
	?>
		</select>
	CIBLE : <?php 
		// LISTE DE TOUTES LES CIBLES POSSIBLES

		// On recherche les monstres en vue
		$req_vue_joueur = "select perso_nom,perso_cod,race_nom ";
		$req_vue_joueur = $req_vue_joueur . "from perso,positions,perso_position,race ";
		$req_vue_joueur = $req_vue_joueur . "where pos_x between ($x-$vue) and ($x+$vue) ";
		$req_vue_joueur = $req_vue_joueur . "and pos_y between ($y-$vue) and ($y+$vue) ";
		$req_vue_joueur = $req_vue_joueur . "and pos_cod = ppos_pos_cod ";
		$req_vue_joueur = $req_vue_joueur . "and pos_etage = $etage ";
		$req_vue_joueur = $req_vue_joueur . "and ppos_perso_cod = perso_cod ";
		$req_vue_joueur = $req_vue_joueur . "and perso_actif = 'O' ";
		$req_vue_joueur = $req_vue_joueur . "and perso_race_cod = race_cod ";
		$req_vue_joueur = $req_vue_joueur . "order by perso_cod desc ";
		$db->query($req_vue_joueur);
	?>
		<select name="cible_cod">
	<?php 
		while($db->next_record()){
	?>
			<option value="<?php  echo $db->f("perso_cod") ?>"> <?php echo $db->f("perso_nom") ?></option>
	<?php 
		}
	?>
		</select>

		<input type="submit" value="Modifier l’IA (Ciblée)" onClick="methode.value='modifier_IA_cib'" class="test">
		<br><br>
	</form>

	<p><strong> Recrutement : </strong></p>
	<?php 
		// SI LE NOMBRE MAX N’EST PAS ATTEINT ON PEUT ENGAGER DES TROUPES

		if($nb_subalternes < $valeur_comp ){
			// On recherche les monstres en vue
			$req_race = "select perso_race_cod from perso where perso_cod = $commandant_cod ";
			$db->query($req_race);
			$db->next_record();
			$race_cod_commandant = $db->f('perso_race_cod');

			// On recherche les monstres en vue
			$req_vue_joueur = "select perso_nom, perso_cod, perso_race_cod, pos_x, pos_y, perso_pa, coalesce(compt_nom, '') as compte_nom "
				 . "from perso "
				 . "inner join perso_position on ppos_perso_cod = perso_cod "
				 . "inner join positions on pos_cod = ppos_pos_cod "
				 . "left outer join perso_compte on pcompt_perso_cod = perso_cod "
				 . "left outer join compte on compt_cod = pcompt_compt_cod "
				 . "where pos_x between ($x - $vue) and ($x + $vue) "
					 . "and pos_y between ($y - $vue) and ($y + $vue) "
					 . "and pos_etage = $etage "
					 . "and perso_cod != $commandant_cod "
					 . "and perso_actif = 'O' "
					 . "and perso_type_perso = 2 "
					 . "and not exists "
						. "(select 1 from perso_commandement "
						. "where perso_subalterne_cod = perso_cod)"
				 . "order by perso_nom ";
			$db->query($req_vue_joueur);
	?>

	Vous pouvez engager les troupes suivantes :<br>
	<form method="post" action="comp_commandement.php">
		<input type="hidden" name="methode" value="ajouter_subalterne">
		<select name="nouv_perso_cod">
	<?php 
		while($db->next_record())
		{
			$monstre_x = $db->f("pos_x");
			$monstre_y = $db->f("pos_y");
			$monstre_nom = $db->f("perso_nom");
			$monstre_cod = $db->f("perso_cod");
			$monstre_pa = $db->f("perso_pa");
			$monstre_compte = ($db->f("compte_nom") != '') ? '(' . $db->f("compte_nom") . ')' : '';
			echo "<option value='$monstre_cod'>$monstre_nom ($monstre_x, $monstre_y) $monstre_pa PA $monstre_compte</option>";
		}
	?>
		</select>
		<input type="Submit" value="Ajouter">
	</form>
	<?php 
		} else {?>
			<p>Votre troupe est au maximum de ses effectifs, vous ne pouvez plus engager personne.</p>
			<?php 
		}
	}
} else {
    echo '<p>Erreur ! Vous n’êtes pas admin monstre, ou ne disposez pas de cette compétence !</p>';
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
