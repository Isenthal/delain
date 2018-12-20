<?php /* Affichage de tous les styles de murs et fonds */

include "blocks/_header_page_jeu.php";
ob_start();
$contenu = '';
$erreur = 0;
$req = "select dcompt_modif_carte from compt_droit where dcompt_compt_cod = $compt_cod ";
$db->query($req);

if ($db->nf() == 0) {
    $droit['carte'] = 'N';
} else {
    $db->next_record();
    $droit['carte'] = $db->f("dcompt_modif_carte");
}
if ($droit['carte'] != 'O') {
    die("<p>Erreur ! Vous n’avez pas accès à cette page !</p>");
}
if ($erreur == 0) {

    $pdo = new bddpdo;            // 2018-05-22 - Marlyza - pour traiter les requêtes secondaires

    // Récupération des images existantes
    // On y va à la bourrin : on parcourt tous les fichiers du répertoire images.
    $patron_fond = '/^f_(?P<affichage>[0-9a-zA-Z]+)_(?P<type>\d+)\.png$/';
    $patron_mur = '/^t_(?P<affichage>[0-9a-zA-Z]+)_mur_(?P<type>\d+)\.png$/';
    $patron_fig = '/^t_(?P<affichage>[0-9a-zA-Z]+)_(?P<type>enn|per|lie|obj)\.png$/';
    $chemin = '../../images/';

    $tableau_styles = array();
    $tableau_figs = array();
    $js_tab_fonds = "\nvar tab_fonds = new Array();";
    $js_tab_murs = "\nvar tab_murs = new Array();";
    $js_tab_figs = "\nvar tab_figs = new Array();";
    $js_usage = "\nvar tab_usage = new Array();";

    $rep = opendir($chemin);
    while (false !== ($fichier = readdir($rep))) {
        $correspondances = array();
        $flagNouveauStyle = "";
        if (1 === preg_match($patron_fond, $fichier, $correspondances)) {
            if (!isset($tableau_styles[$correspondances['affichage']])) {
                $flagNouveauStyle = $correspondances['affichage'];
                $tableau_styles[$correspondances['affichage']] = $correspondances['affichage'];
                $js_tab_fonds .= "\ntab_fonds['" . $correspondances['affichage'] . "'] = new Array();";
                $js_tab_murs .= "\ntab_murs['" . $correspondances['affichage'] . "'] = new Array();";
                if (!isset($tableau_figs[$correspondances['affichage']]['fig'])) {
                    $tableau_figs[$correspondances['affichage']]['fig'] = $correspondances['affichage'];
                    $js_tab_figs .= "\ntab_figs['" . $correspondances['affichage'] . "'] = new Array();";
                }
            }
            $js_tab_fonds .= "\ntab_fonds['" . $correspondances['affichage'] . "'][" . $correspondances['type'] . "] = " . $correspondances['type'] . ";";
        }

        $correspondances = array();
        if (1 === preg_match($patron_mur, $fichier, $correspondances)) {
            if (!isset($tableau_styles[$correspondances['affichage']])) {
                $flagNouveauStyle = $correspondances['affichage'];
                $tableau_styles[$correspondances['affichage']] = $correspondances['affichage'];
                $js_tab_fonds .= "\ntab_fonds['" . $correspondances['affichage'] . "'] = new Array();";
                $js_tab_murs .= "\ntab_murs['" . $correspondances['affichage'] . "'] = new Array();";
                if (!isset($tableau_figs[$correspondances['affichage']]['fig'])) {
                    $tableau_figs[$correspondances['affichage']]['fig'] = $correspondances['affichage'];
                    $js_tab_figs .= "\ntab_figs['" . $correspondances['affichage'] . "'] = new Array();";
                }
            }
            $js_tab_murs .= "\ntab_murs['" . $correspondances['affichage'] . "'][" . $correspondances['type'] . "] = " . $correspondances['type'] . ";";
        }

        $correspondances = array();
        if (1 === preg_match($patron_fig, $fichier, $correspondances)) {
            if (!isset($tableau_figs[$correspondances['affichage']]['fig'])) {
                $tableau_figs[$correspondances['affichage']]['fig'] = $correspondances['affichage'];
                $js_tab_figs .= "\ntab_figs['" . $correspondances['affichage'] . "'] = new Array();";
            }
            $js_tab_figs .= "\ntab_figs['" . $correspondances['affichage'] . "']['" . $correspondances['type'] . "'] = '" . $correspondances['type'] . "';";
            $tableau_figs[$correspondances['affichage']][$correspondances['type']] = $correspondances['type'];
        }

        if ($flagNouveauStyle != "") {
            // Pour chque nouveau style on calcul ne nombre d'étage l'utilisant.
            $req_style = "select count(distinct etage_numero) count from etage where etage_affichage = ?;";
            $stmt = $pdo->prepare($req_style);
            $stmt = $pdo->execute(array($flagNouveauStyle), $stmt);
            $row = $stmt->fetch();
            $style_usage = $row['count'];
            $js_usage .= "\ntab_usage['" . $flagNouveauStyle . "'] = " . $style_usage . ";";
        }

    }

    echo "<script type='text/javascript'>
		$js_tab_fonds
		$js_tab_murs
		$js_tab_figs
		$js_usage
		function afficherStyles()
		{
			var div_images = document.getElementById('images');
			var chaine_contenu = '';

			for (var style in tab_fonds) 
			    if (( tab_fonds[style].length > 0 ) || ( tab_murs[style].length >0 ) )
                {
                    chaine_contenu +='<p><a href=\"modif_etage3_styles.php?&style='+style+'\"><input type=\"submit\" value=\"Editer\" class=\"test\"></a>&nbsp&nbsp';
                    chaine_contenu += '<strong>Style ' + style + '</strong>&nbsp&nbsp';
                    chaine_contenu +='<em>Nombre d\'étage l\'utilisant:</em>&nbsp;<strong>'+tab_usage[style]+'</strong></p>\\n';
                    chaine_contenu += '<p>Fonds :</p>';
                    chaine_contenu += '\\n	<div style=\"width:600px; overflow:auto\" class=\"bordiv\">';
                    
                    for (var i in tab_fonds[style])
                    {
                        var nom = '" . G_IMAGES . "f_' + style + '_' + i + '.png';
                        chaine_contenu += '\\n		<div style=\"float:left; width:28px; height:38px; margin:6px;\"><img src=\"' + nom + '\"/><br />' + i + '</div>';
                    }
    
                    chaine_contenu += '</div><p>Murs :</p>';
                    chaine_contenu += '\\n	<div style=\"width:600px; overflow:auto\" class=\"bordiv\">';
                    for (var i in tab_murs[style])
                    {
                        var nom = '" . G_IMAGES . "t_' + style + '_mur_' + i + '.png';
                        chaine_contenu += '\\n		<div style=\"float:left; width:28px; height:38px; margin:6px;\"><img src=\"' + nom + '\"/><br />' + i + '</div>';
                    }
                    
                    chaine_contenu += '</div><p>Figurines :</p>';
                    chaine_contenu += '\\n	<div style=\"width:600px; overflow:auto\" class=\"bordiv\">';
                    for (var i in tab_figs[style])
                    {
                        var nom = '" . G_IMAGES . "t_' + style + '_' + i + '.png';
                        chaine_contenu += '\\n		<div style=\"float:left; width:28px; height:38px; margin:6px;\"><img src=\"' + nom + '\"/><br />' + i + '</div>';
                    }
    
                    chaine_contenu += '</div></div><hr />';
                }
			div_images.innerHTML = chaine_contenu;
		}
		</script>";
    echo '<div class="barrTitle">Visu de tous les styles définis</div><div id="images"></div>
	<script type="text/javascript">afficherStyles();</script>';
}
$contenu_page = ob_get_contents();
ob_end_clean();
include "blocks/_footer_page_jeu.php";
