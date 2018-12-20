<?php
require G_CHE . "ident.php";
include G_CHE . "/includes/classes_monstre.php";
?>
<link rel="stylesheet" type="text/css" href="style.css" title="essai">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
      integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link href="css/delain.css" rel="stylesheet">
<body>
<?php
$db = new base_delain;
$req = "select dcompt_etage from compt_droit where dcompt_compt_cod = $compt_cod ";
$db->query($req);
if ($db->nf() == 0) {
    die("Erreur sur les etages possibles !");
} else {
    $db->next_record();
    $droit['etage'] = $db->f("dcompt_etage");
}
if ($droit['etage'] == 'A') {
    $restrict = '';
    $restrict2 = '';
} else {
    $restrict = 'where etage_numero in (' . $droit['etage'] . ') ';
    $restrict2 = 'and pos_etage in (' . $droit['etage'] . ') ';
}
?>
<div class="bordiv">
    <?php
    $req = "select etage_libelle,etage_numero,etage_reference from etage " . $restrict . "order by etage_reference desc, etage_numero asc";
    $db->query($req);
    echo("<p>");
    while ($db->next_record()) {
        $bold = ($db->f("etage_numero") == $db->f("etage_reference"));
        echo ($bold ? '<p /><strong>' : '') . "<a href=\"jeu/tab_vue_total.php?num_etage=" . $db->f("etage_numero") . "&compt_cod=" . $compt_cod . "\">" . $db->f("etage_libelle") . "</a>" . ($bold ? '</strong>' : '') . "<br />";
    }

    ?>
</div>
