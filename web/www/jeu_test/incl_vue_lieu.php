<?php 
$req_vue_joueur = "select lieu_nom,tlieu_libelle,distance(pos_cod,$pos_cod) as distance,pos_x,pos_y,pos_cod,lieu_cod,lieu_refuge ";
$req_vue_joueur = $req_vue_joueur . "from lieu,lieu_type,lieu_position,positions ";
$req_vue_joueur = $req_vue_joueur . "where pos_x between ($x-$distance_vue) and ($x+$distance_vue) ";
$req_vue_joueur = $req_vue_joueur . "and pos_y between ($y-$distance_vue) and ($y+$distance_vue) ";
$req_vue_joueur = $req_vue_joueur . "and pos_etage = $etage ";
$req_vue_joueur = $req_vue_joueur . "and lpos_pos_cod = pos_cod ";
$req_vue_joueur = $req_vue_joueur . "and lpos_lieu_cod = lieu_cod ";
$req_vue_joueur = $req_vue_joueur . "and lieu_tlieu_cod = tlieu_cod ";
$req_vue_joueur = $req_vue_joueur . "and tlieu_cod != 19 ";
$req_vue_joueur = $req_vue_joueur . "and not exists(select 1 from murs where mur_pos_cod = pos_cod) ";
$req_vue_joueur = $req_vue_joueur . "order by distance,pos_x,pos_y";
$stmt = $pdo->query($req_vue_joueur);
$nb_lieux_en_vue = $stmt->rowCount();
if ($nb_lieux_en_vue != 0)
{

	?>
	<table width="100%" cellspacing="2" cellapdding="2"><tr><td colspan="5" class="soustitre"><p class="soustitre">Sites</td></tr>
	<tr><td class="soustitre2" width="50"><p><strong>Dist.</strong></td>
	<td class="soustitre2"><p><strong>Nom</strong></td>
	<td class="soustitre2"><p><strong>Type</strong></td>
	<td class="soustitre2"><p style="text-align:center;"><strong>X</strong></td>
	<td class="soustitre2"><p style="text-align:center;"><strong>Y</strong></td>
	</tr>
	<?php 
	$i = 0;
	while($result = $stmt->fetch())
	{
		$refuge = ($result['lieu_refuge'] == 'O') ? 'refuge' : 'non protégé';
		$nom = $result['lieu_nom'] . " <em>($refuge)</em>";
		$type = $result['tlieu_libelle'];
		$style = "soustitre2";

		$ch_style = 'onMouseOver="changeStyles(\'cell' . $result['pos_cod'] . '\',\'llieu' . $result['lieu_cod'] . '\',\'vu\',\'surligne\');" onMouseOut="changeStyles(\'cell' . $result['pos_cod'] . '\',\'llieu' . $result['lieu_cod'] . '\',\'pasvu\',\'' . $style . '\');"';

		echo '<tr>
			<td ' . $ch_style . '><p style="text-align:center;">' . $result['distance'] . '</p></td>
			<td ' . $ch_style . 'id="llieu' . $result['lieu_cod'] . '" class="soustitre2"><p>' . $nom . '</p></td>
			<td ' . $ch_style . '><p>' .$type . '</p></td>
			<td ' . $ch_style . ' nowrap><p style="text-align:center;">' . $result['pos_x'] . '</p></td>
			<td ' . $ch_style . ' nowrap><p style="text-align:center;">' . $result['pos_y'] . '</p></td>
			</tr>';
	}
}
?>
