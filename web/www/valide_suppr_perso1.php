<?php
include "includes/classes.php";
include "ident.php";
$db = new base_delain;
$perso_cible = 1 * $_REQUEST['perso'];
?>
<!DOCTYPE html>
<html>
<link rel="stylesheet" type="text/css" href="../style.css" title="essai">
<head>
    <title>Suppression de perso</title>
</head>
<body background="../images/fond5.gif">
<div class="bordiv">
    <form name="suppr_pers" method="post" action="valide_suppr_perso2.php">
        <input type="hidden" name="perso" value="<?php $perso_cible ?>">");
        <?php
        $db = new base_delain;


        $req = "select perso_nom from perso where perso_cod = $perso_cible";
        $db->query($req);
        $db->next_record();
        $tab[0] = $db->f("perso_nom");
        ?>
        <p><strong>Attention !</strong>Toute suppression de personnage est définitive !<br/>
        <p>Voulez vous vraiment supprimer le perso <strong><?php echo $tab[0] ?></strong> ?
        <p><a href="javascript:document.suppr_pers.submit();"><strong>OUI</strong>, je le veux !</a>
        <p><a href="jeu/switch.php"><strong>NON !</strong>, je souhaite garder ce perso !</a>
</div>
</body>
</html>
