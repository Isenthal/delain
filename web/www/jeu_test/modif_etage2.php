<?php
include "blocks/_header_page_jeu.php";
ob_start();
?>
    <link rel="stylesheet" type="text/css" href="../style.css" title="essai">
    <link rel="stylesheet" type="text/css" href="../styles/onglets.css" title="essai">
    <script language="javascript" src="../scripts/onglets.js"></script>
    <script language="javascript">
        function blocking(nr) {
            if (document.layers) {
                current = (document.layers[nr].display == 'none') ? 'block' : 'none';
                document.layers[nr].display = current;
            }
            else if (document.all) {
                current = (document.all[nr].style.display == 'none') ? 'block' : 'none';
                document.all[nr].style.display = current;
            }
            else if (document.getElementById) {
                vista = (document.getElementById(nr).style.display == 'none') ? 'block' : 'none';
                document.getElementById(nr).style.display = vista;
            }
        }
    </script>
<?php $erreur = 0;
$req = "select dcompt_modif_carte from compt_droit where dcompt_compt_cod = $compt_cod ";
$db->query($req);
if ($db->nf() == 0) {
    $droit['carte'] = 'N';
} else {
    $db->next_record();
    $droit['carte'] = $db->f("dcompt_modif_carte");
}
if ($droit['carte'] != 'O') {
    die("<p>Erreur ! Vous n'avez pas accès à cette page !");
}
$db2 = new base_delain;
$db3 = new base_delain;
if (!isset($methode)) {
    $methode = 'debut';
}
if ($erreur == 0) {

    // POSITION DU JOUEUR
    $req_matos = "select pos_x,pos_y,pos_etage,pos_cod "
        . "from perso_position,positions "
        . "where ppos_perso_cod = $perso_cod"
        . "and ppos_pos_cod = pos_cod ";
    $db->query($req_matos);
    $db->next_record();
    $perso_pos_x = $db->f("pos_x");
    $perso_pos_y = $db->f("pos_y");
    $perso_pos_etage = $db->f("pos_etage");
    $perso_pos_cod = $db->f("pos_cod");

    if (isset($_POST['methode'])) {
        switch ($methode) {
            case "aj_non_cr":
                $list = explode(";", $_POST['pos_codes']);
                for ($i = 0; $i < count($list); $i++) {
                    $temp_pos_cod = $list[$i];
                    if ($temp_pos_cod) {
                        $req = "delete from murs where mur_pos_cod = " . $temp_pos_cod;
                        $db->query($req);
                        $req = "insert into murs (mur_pos_cod) values (" . $temp_pos_cod . ")";
                        $db->query($req);
                        $req = "select init_automap_pos(" . $temp_pos_cod . ")";
                        $db->query($req);
                    }
                }
                break;
            case "aj_cr":
                $list = explode(";", $_POST['pos_codes']);
                for ($i = 0; $i < count($list); $i++) {
                    $temp_pos_cod = $list[$i];
                    if ($temp_pos_cod) {
                        $req = "delete from murs where mur_pos_cod = " . $temp_pos_cod;
                        $db->query($req);
                        $req = "insert into murs (mur_pos_cod,mur_creusable,mur_usure,mur_richesse) values (" . $temp_pos_cod . ",'O'," . $_POST['usure'] . "," . $_POST['qualite'] . ")";
                        $db->query($req);
                        $req = "select init_automap_pos(" . $temp_pos_cod . ")";
                        $db->query($req);
                    }
                }
                break;
            case "suppr":
                $list = explode(";", $_POST['pos_codes']);
                for ($i = 0; $i < count($list); $i++) {
                    $temp_pos_cod = $list[$i];
                    if ($temp_pos_cod) {
                        $req = "delete from murs where mur_pos_cod = " . $temp_pos_cod;
                        $db->query($req);
                        $req = "select init_automap_pos(" . $temp_pos_cod . ")";
                        $db->query($req);
                    }
                }
            case "modif_decor":
                $list = explode(";", $_POST['pos_codes']);
                for ($i = 0; $i < count($list); $i++) {
                    $temp_pos_cod = $list[$i];
                    if ($temp_pos_cod) {
                        $req = "update positions set pos_decor = " . $_POST['decor'] . ",pos_modif_pa_dep = " . $_POST['deplacement'] . " where pos_cod = " . $temp_pos_cod;
                        $db->query($req);
                        $req = "select init_automap_pos(" . $temp_pos_cod . ")";
                        $db->query($req);
                    }
                }
                break;

        }
    }


    ?>
    <p> Création / Modification des mines et des routes / décors </p>
    <hr>
    Choix de l'étage:
    <form method="post">
        Etage : <select name="pos_etage">
            <?php
            $req = "select etage_numero,etage_libelle,etage_reference from etage order by etage_reference desc, etage_numero asc ";
            $db->query($req);
            while ($db->next_record()) {
                $sel = "";
                if ($pos_etage == $db->f("etage_numero")) {
                    $sel = "selected";
                }
                $reference = ($db->f("etage_numero") == $db->f("etage_reference"));
                echo "<option value=\"", $db->f("etage_numero"), "\" $sel>", ($reference ? '' : ' |-- '), $db->f("etage_libelle"), "</option>";
            }
            ?>
        </select><br>
        <input type="submit" value="Valider">
    </form>
    <hr>
    <?php
    $sel_etage = $perso_pos_etage;
    if (isset($_POST['pos_etage'])) {
        $sel_etage = $_POST['pos_etage'];
    }
    ?>
    Ajouter des murs dans un étage
    <form name="action_mur" method="post">
        <select name="methode">
            <option value="aj_cr" <?php if ($_POST['methode'] == "aj_cr") echo "selected"; ?>>Ajouter mur creusable
            </option>
            <option value="aj_non_cr" <?php if ($_POST['methode'] == "aj_non_cr") echo "selected"; ?>>Ajouter mur
                non
                creusable
            </option>
            <option value="suppr" <?php if ($_POST['methode'] == "suppr") echo "selected"; ?>>Supprimer mur</option>
            <option value="modif_decor" <?php if ($_POST['methode'] == "modif_decor") echo "selected"; ?>>modifier
                décor
                et routes (nombre de PA pour déplacement)
            </option>
        </select>
        Usure:<input type="text" name="usure" value="1000">
        Qualité:<input type="text" name="qualite" value="1000">
        <input type="hidden" name="pos_cod" value="">
        <input type="hidden" name="pos_etage" value="<?php echo $sel_etage ?>">
        <input type="hidden" name="pos_codes">
        Blocs modifiés:<input type="text" name="numpos" value="0" readonly><input type="submit" value="Modifier !">
        <br>Valeur du décor<input type="text" name="decor" value="0">
        Modificateur déplacement<input type="text" name="deplacement" value="0"><em>(positif les déplacements
            coutent
            autant de plus, l'inverse en négatif)</em>
        <input type="submit" value="Modifier !">
    </form>
    (<a href="javascript:blocking('aide');">Aide</a>)<br><br>
    <div id="aide" class="tableau2" style="display:none;">
        <table>
            --
            <tr>
                <?php /*$req_decor = 'select pos_decor from positions group by pos_decor order by pos_decor';
		$db->query($req_decor);
		$compteur = 0;
		while($db->next_record())
		{
			$compteur = $compteur + 1;
			if ($compteur%20 == 19)
			{
				echo "</tr><tr>";
			}
			$decor = '<img src="http://jdr-delain.net/images/dec_'. $db->f('pos_decor') .'.gif">';
			echo "<td>". $db->f('pos_decor') ."
			<td>". $decor ."</td>";
		 } */
                ?>
                --
            </tr>
            <?php
            for ($compteur = 0; $compteur < 300; $compteur++) {
                $fich = '../images/dec_' . $compteur . '.gif';

                $decor = '<img src="../images/dec_' . $compteur . '.gif">';
                if ($compteur % 20 == 19) {
                    echo "</tr><tr>";
                }
                if (@file_exists("$fich")) {
                    echo '<td>' . $compteur . '
				<td><img src="' . $fich . '"></td>';
                }
                /*else
                {
                    echo '<td>Compteur : '. $compteur .' / '. $decor .'<td></td>';
                }*/
            }
            ?>
            </tr>

        </table>
    </div>
    <script>
        <!--
        function valActionMur(pcod) {
            document.action_mur.pos_codes.value += pcod + ";";
            document.action_mur.numpos.value++;
            changeBlockColor("pos_" + pcod, "#0000FF");
            //document.action_mur.submit();
        }

        function changeBlockColor(nr, color) {
            if (document.layers) {
                document.layers[nr].background = color;
            }
            else if (document.all) {
                document.all[nr].style.background = color;
            }
            else if (document.getElementById) {
                document.getElementById(nr).style.background = color;
            }
        }

        -->
    </script>

    <!--form method="post">
					<Etage : <select name="pos_etage">
					<?php
    $req = "select etage_numero,etage_libelle from etage order by etage_numero desc ";
    $db->query($req);
    while ($db->next_record()) {
        $sel = "";
        if ($pos_etage == $db->f("etage_numero")) {
            $sel = "selected";
        }
        echo "<option value=\"", $db->f("etage_numero"), "\" $sel>", $db->f("etage_libelle"), "</option>";
    }
    ?>
					</select>
</form>
          <br-->
    <strong>Sélectionner une case sur la carte pour valider</strong>
    <!--input type="submit" value="Valider"-->


    <table border="1">
        <tr>
            <?php
            $req_murs = "select pos_cod, pos_x,pos_y,mur_creusable,mur_usure,mur_richesse,pos_decor,pos_modif_pa_dep from positions "
                . " LEFT JOIN murs ON positions.pos_cod = murs.mur_pos_cod"
                . " where"
//." pos_x > $perso_pos_x-10 and pos_x < $perso_pos_x+10"
//." and pos_y > $perso_pos_y-10 and pos_y < $perso_pos_y+10"
                . " pos_etage = $sel_etage"
                . " order by pos_y desc,pos_x asc";
            $db->query($req_murs);
            while ($db->next_record()){
            if (!isset($p_y)) {
                $p_y = $db->f("pos_y");
            }
            if ($db->f("pos_y") != $p_y){
            ?>
        </tr>
        <tr>
            <?php
            }
            $color = "#FFFFFF";
            if ($db->f("mur_creusable") == 'O') {
                $color = "#00FF00";
            }
            if ($db->f("mur_creusable") == 'N') {
                $color = "#FF0000";
            }
            $decor = '';
            if ($db->f("pos_decor") != '0') {
                $decor = 'background-image:url(http://www.jdr-delain.net/images/dec_' . $db->f('pos_decor') . '.gif);';
            }
            $dep = '';
            if ($db->f("pos_modif_pa_dep") != 0) {
                $dep = $db->f("pos_modif_pa_dep");
            }
            ?>
            <td width="20" height="20">
                <div id="pos_<?php echo $db->f("pos_cod"); ?>"
                     style="width:25px;height:25px;background-color:<?php echo $color ?>;<?php echo $decor ?>"
                     onClick="valActionMur(<?php echo $db->f("pos_cod"); ?>);"><span
                            style="font-size:9px;"><?php echo $db->f("mur_usure"); ?><?php echo $db->f("mur_richesse"); ?><?php echo $dep; ?></span>
                </div>
            </td>
            <?php

            $p_y = $db->f("pos_y");
            }
            ?>
        </tr>
    </table>
    <?php
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";

