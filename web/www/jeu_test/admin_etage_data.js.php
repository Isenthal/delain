<?php header("Content-type: text/javascript");

$verif_connexion = new verif_connexion();
$verif_connexion->verif();
$perso_cod = $verif_connexion->perso_cod;
$compt_cod = $verif_connexion->compt_cod;
include_once '../includes/images_delain.php';

$num_etage = get_request_var('num_etage', '');
if ($num_etage === '')
{
    die ('alert("Erreur ! Aucun étage déclaré !")');
}

// Données générales de l’étage
$req_etage =
    "SELECT MIN(pos_x) as minx, MIN(pos_y) as miny, MAX(pos_x) as maxx, MAX(pos_y) as maxy from positions where pos_etage = $num_etage";
$stmt = $pdo->query($req_etage);
if (!$result = $stmt->fetch())
    die ('alert("Erreur ! Étage inconnu !")');
?>
//# sourceURL=admin_etage_data.js
Etage.minX = <?php echo $result['minx']; ?>;
Etage.maxX = <?php echo $result['maxx']; ?>;
Etage.minY = <?php echo $result['miny']; ?>;
Etage.maxY = <?php echo $result['maxy']; ?>;
Etage.numero = <?php echo $num_etage; ?>;

<?php
// Type d’étage
$req_style = "select etage_affichage from etage where etage_numero = $num_etage";
$etage = new etage();

if (!$etage->getByNumero($num_etage))
{
    die("Erreur sur chargement étage");
}
$style = $etage->etage_affichage;
?>
Etage.style = "<?php echo $style; ?>";

<?php // Détail des cases
$req_cases = "select coalesce(pmeca_base_pos_decor, pos_decor) as pos_decor, pos_cod, pos_x, pos_y, 
                      coalesce(pmeca_base_pos_type_aff,pos_type_aff) as pos_type_aff, 
                      coalesce(CASE WHEN pmeca_pos_cod IS NOT NULL THEN pmeca_base_mur_type ELSE mur_type END, 0) as mur_type, 
                      coalesce(pmeca_base_pos_decor_dessus, pos_decor_dessus) as pos_decor_dessus, 
                      coalesce(pmeca_base_pos_passage_autorise, pos_passage_autorise) as pos_passage_autorise, 
                      pos_pvp, 
                      pos_entree_arene,
                      coalesce(CASE WHEN pmeca_pos_cod IS NOT NULL THEN pmeca_base_mur_tangible ELSE mur_tangible END, 'O') as mur_tangible, 
                      coalesce(CASE WHEN pmeca_pos_cod IS NOT NULL THEN pmeca_base_mur_illusion ELSE mur_illusion END, 'N') as mur_illusion, 
                      coalesce(CASE WHEN pmeca_pos_cod IS NOT NULL THEN null ELSE mur_creusable END, 'N') as mur_creusable, 
                      coalesce(coalesce(pmeca_base_pos_modif_pa_dep,pos_modif_pa_dep), 0) as pos_modif_pa_dep, 
                      coalesce(coalesce(pmeca_base_pos_ter_cod,pos_ter_cod), 0) as pos_ter_cod
                from positions
                left outer join murs on mur_pos_cod = pos_cod
                left outer join (select distinct pmeca_pos_cod, pmeca_base_pos_decor, pmeca_base_pos_type_aff,pmeca_base_pos_decor_dessus,pmeca_base_pos_passage_autorise,pmeca_base_pos_modif_pa_dep,pmeca_base_pos_ter_cod, pmeca_base_mur_type, pmeca_base_mur_tangible, pmeca_base_mur_illusion from meca_position where pmeca_actif=1 and pmeca_pos_etage = $num_etage) as mpp on pmeca_pos_cod=pos_cod
                where pos_etage = $num_etage 
                order by pos_y desc, pos_x";
$stmt = $pdo->query($req_cases);
$i = 0;
while ($result = $stmt->fetch())
{
    $pos_cod              = $result['pos_cod'];
    $pos_x                = $result['pos_x'];
    $pos_y                = $result['pos_y'];
    $mur_type             = $result['mur_type'];
    $pos_decor            = $result['pos_decor'];
    $pos_decor_dessus     = $result['pos_decor_dessus'];
    $pos_passage_autorise = ($result['pos_passage_autorise'] == 1) ? 'true' : 'false';
    $pos_pvp              = ($result['pos_pvp'] == 'O') ? 'true' : 'false';
    $entree_arene         = ($result['pos_entree_arene'] == 'O') ? 'true' : 'false';
    $mur_tangible         = ($result['mur_tangible'] == 'O') ? 'true' : 'false';
    $mur_illusion         = ($result['mur_illusion'] == 'O') ? 'true' : 'false';
    $mur_creusable        = ($result['mur_creusable'] == 'O') ? 'true' : 'false';
    $pos_type_aff         = $result['pos_type_aff'];
    $pos_modif_pa_dep     = $result['pos_modif_pa_dep'];
    $pos_ter_cod          = $result['pos_ter_cod'];
    echo "Etage.Cases[$i] = { id: $pos_cod, x: $pos_x, y: $pos_y, mur: $mur_type, decor: $pos_decor, decor_dessus: $pos_decor_dessus, fond: $pos_type_aff, passage: $pos_passage_autorise, pvp: $pos_pvp, entree_arene: $entree_arene, tangible: $mur_tangible, illusion: $mur_illusion, creusable: $mur_creusable, ter_cod: $pos_ter_cod, pa_dep:$pos_modif_pa_dep};\n";
    $i++;
}

// Images de murs
$tab_murs = images_delain::Murs($style);
echo "Murs.donnees[0] = { id: 0 };\n";
$i = 1;
foreach ($tab_murs as $unMur)
{
    $numero = $unMur[0];
    echo "Murs.donnees[$i] = { id: $numero };\n";
    $i++;
}

// Images de fonds
$tab_fonds = images_delain::Fonds($style);
$i = 0;
foreach ($tab_fonds as $unFond)
{
    $numero = $unFond[0];
    echo "Fonds.donnees[$i] = { id: $numero };\n";
    $i++;
}

// Images de décors
$tab_decors = images_delain::Decors();
echo "Decors.donnees[0] = { id: 0 };\n";
$i = 1;
foreach ($tab_decors as $unDecor)
{
    $numero = $unDecor[0];
    echo "Decors.donnees[$i] = { id: $numero };\n";
    $i++;
}

?>
