<?php
$contenu_page .= '<script language="javascript" src="../scripts/messEnvoi.js"></SCRIPT>';
$req          = 'select cliste_cod,cliste_nom from contact_liste where cliste_perso_cod = ' . $perso_cod;
$stmt         = $pdo->query($req);
if ($stmt->rowCount() == 0)
{
    $contenu_page .= '<p>Vous n\'avez aucune liste de diffusion en ce moment.<br>';
} else
{
    $contenu_page .= '<p>Liste existantes : ';
    while ($result = $stmt->fetch())
    {
        $contenu_page .= '<p><a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '&methode=aliste&liste=' . $result['cliste_cod'] . '">' . $result['cliste_nom'] . '</a>';
    }
}
$contenu_page .= '<br><hr><p style="text-align:center;"><a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '&methode=cree_liste">Créer une liste !</a><br>';

$methode = get_request_var('methode', 'debut');// Pas utilisé ci-dessous ; c’est normal.

switch ($methode)
{
    case "cree_liste":
        $contenu_page .= '
		<form name="liste" method="post" action="' . $_SERVER['PHP_SELF'] . '">
		<input type="hidden" name="methode" value="cree_liste2">
		<input type="hidden" name="m" value="' . $m . '">
		<p>Nom de la liste à créer (max 25 caractères) : <input type="text" name="nom" size="25"><br>
		<p style="text-align:center;"><input type="submit" value="Créer !" class="test"></p>
		</form>';
        break;

    case "cree_liste2":
        $req          = "insert into contact_liste (cliste_perso_cod,cliste_nom) 
        values ($perso_cod,'$nom') ";
        $stmt         = $pdo->query($req);
        $contenu_page .= '<p>La liste a bien été créée !';
        $contenu_page .= '<p style="text-align:center"><a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '">Retour !</a>';
        break;

    case "aliste":
        $req  =
            "select cliste_cod,cliste_nom from contact_liste where cliste_perso_cod = $perso_cod and cliste_cod = $liste ";
        $stmt = $pdo->query($req);
        if ($stmt->rowCount() == 0)
        {
            echo "<p>Vous n'avez pas accès à cette liste !";
            break;
        }
        $result       = $stmt->fetch();
        $nom_liste    = $result['cliste_nom'];
        $req          = "select perso_nom,contact_perso_cod from perso,contact ";
        $req          = $req . "where contact_cliste_cod = $liste ";
        $req          = $req . "and contact_perso_cod = perso_cod ";
        $stmt         = $pdo->query($req);
        $contenu_page .= "<p><strong>Gestion de la liste " . $nom_liste . "</strong>";
        if ($stmt->rowCount() == 0)
        {
            $contenu_page .= '<p>Aucun contact dans cette liste !';
        } else
        {

            $contenu_page .= "<table>";
            while ($result = $stmt->fetch())
            {
                $contenu_page .= "<tr>";
                $contenu_page .= "<td class=\"soustitre2\"><p>" . $result['perso_nom'] . "</td>";
                $contenu_page .= '<td><p><a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '&methode=dcontact&contact=' . $result['contact_perso_cod'] . "&liste=" . $liste . "\">Retirer !</A>";
            }
            $contenu_page .= "</table>";
        }
        if ($stmt->rowCount() < 20)
        {
            $contenu_page .= '<p><a href="' . $_SERVER['PHP_SELF'] . '?methode=aliste_a&liste=' . $liste . '&m=' . $m . '">Ajouter un contact ?</a>';
        }
        $contenu_page .= '<a href="' . $_SERVER['PHP_SELF'] . '?methode=dliste&liste=' . $liste . '&m=' . $m . '"><p>Détruire cette liste ? </a>(opération définitive !)';
        break;

    case "aliste_a":
        $contenu_page .= '
		<form name="nouveau_message" method="post" action="' . $_SERVER['PHP_SELF'] . '">
		<input type="hidden" name="methode" value="aliste_a3">
		<input type="hidden" name="liste" value="' . $liste . '">
		<input type="hidden" name="m" value="' . $m . '">
		<p>Tapez le nom des personnes que vous souhaitez rajouter dans votre liste, séparé par un point virgule :
		<p><input type="text" name="dest" size="80" value=""></p>
		<p style="text-align:center;"><input type="submit" value="Ajouter !" class="test"></p>
		<form>';

        $req_pos      = "select ppos_pos_cod,distance_vue($perso_cod) as dist_vue,pos_etage,pos_x,pos_y
			from perso_position,perso,positions
			where ppos_perso_cod = $perso_cod and perso_cod = $perso_cod and ppos_pos_cod = pos_cod ";
        $stmt         = $pdo->query($req_pos);
        $result       = $stmt->fetch();
        $pos_actuelle = $result['ppos_pos_cod'];
        $v_x          = $result['pos_x'];
        $v_y          = $result['pos_y'];
        $vue          = $result['dist_vue'];
        $etage        = $result['pos_etage'];
        $contenu_page .= '
		<select name="joueur" onChange="changeDestinataire(0);">
		<option value="">---------------</option>';
        $dist_init    = -1;
        $req_vue      = "select perso_nom,distance(ppos_pos_cod,$pos_actuelle) as dist,trajectoire_vue($pos_actuelle,pos_cod) as traj
			from perso, perso_position, positions
			where pos_x >= ($v_x - $vue) and pos_x <= ($v_x + $vue)
				and pos_y >= ($v_y - $vue) and pos_y <= ($v_y + $vue)
				and ppos_perso_cod = perso_cod
				and perso_cod != $perso_cod 
				and perso_type_perso != 2
				and perso_actif = 'O'
				and ppos_pos_cod = pos_cod
				and pos_etage = $etage
			order by dist,perso_type_perso,perso_nom ";
        $stmt         = $pdo->query($req_vue);
        $ch           = '';
        while ($result = $stmt->fetch())
        {
            if ($result['traj'] == 1)
            {
                if ($result['dist'] != $dist_init)
                {
                    $ch        .= '</optgroup><optgroup label="Distance ' . $result['dist'] . '">';
                    $dist_init = $result['dist'];
                }
                $ch .= '<option value="' . $result['perso_nom'] . ';">' . $result['perso_nom'] . '</option>';
            }
        }
        $ch           = substr($ch, 11);
        $contenu_page .= $ch;
        $contenu_page .= '</select>';
        break;

    case "aliste_a3":
        $req  =
            "select cliste_cod,cliste_nom from contact_liste where cliste_perso_cod = $perso_cod and cliste_cod = $liste ";
        $stmt = $pdo->query($req);
        if ($stmt->rowCount() == 0)
        {
            echo "<p>Vous n'avez pas accès à cette liste !";
            break;
        }
        // On commence le traitement des différents noms
        $nom_liste = explode(";", $_POST['dest']);
        foreach ($nom_liste as $cle => $valeur)
        {
            if ($valeur != '')
            {
                $req    = "select f_cherche_perso('" . pg_escape_string($valeur) . "') as resultat ";
                $stmt   = $pdo->query($req);
                $result = $stmt->fetch();
                if ($result['resultat'] == -1)
                {
                    $contenu_page .= "<p>Aucun perso trouvé pour le nom " . $valeur . " !";
                } else
                {
                    $num_contact = $result['resultat'];
                    $req         = "select contact_perso_cod from contact ";
                    $req         = $req . "where contact_perso_cod = $num_contact ";
                    $req         = $req . "and contact_cliste_cod = $liste ";
                    $stmt        = $pdo->query($req);
                    if ($stmt->rowCount() != 0)
                    {
                        $contenu_page .= "<p>Le perso " . $valeur . " <strong> est déjà dans cette liste !</strong><br>";
                    } else
                    {
                        $req          = "insert into contact (contact_cliste_cod,contact_perso_cod) 
                        values ($liste,$num_contact) ";
                        $stmt         = $pdo->query($req);
                        $contenu_page .= "<p>Le perso " . $valeur . " a été rajouté à votre liste.<br>";
                    }
                }
            }
        }
        $contenu_page .= '<p style="text-align:center"><a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '&methode=aliste_a&liste=' . $liste . '">Retour !</a>';
        break;

    case "dliste":
        $req  = "select cliste_nom from contact_liste where cliste_cod = $liste and cliste_perso_cod = $perso_cod";
        $stmt = $pdo->query($req);
        if ($stmt->rowCount() == 0)
        {
            $contenu_page .= '<p>Cette liste ne vous appartient pas ! Attention !';
            break;
        }
        $result       = $stmt->fetch();
        $nom          = $result['cliste_nom'];
        $contenu_page .= '
		<p>Voulez vous vraiment détruire la liste <strong>' . $nom . '</strong> ?<br>
		<a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '&methode=dliste2&liste=' . $liste . '">Oui je le veux !</a><br>
		<a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '">Non, je souhaite la garder !</a>';
        break;

    case "dliste2":
        $req  = "select cliste_nom from contact_liste where cliste_cod = $liste and cliste_perso_cod = $perso_cod";
        $stmt = $pdo->query($req);
        if ($stmt->rowCount() == 0)
        {
            $contenu_page .= '<p>Cette liste ne vous appartient pas ! Attention !';
            break;
        }
        $req  = "delete from contact_liste where cliste_cod = $liste  and cliste_perso_cod = $perso_cod";
        $stmt = $pdo->query($req);

        $contenu_page .= "<p>La liste a bien été détruite !";
        $contenu_page .= '<p style="text-align:center"><a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '">Retour !</a>';
        break;

    case "dcontact":
        $req          = "delete from contact where contact_perso_cod = $contact and contact_cliste_cod = $liste ";
        $stmt         = $pdo->query($req);
        $contenu_page .= "<p>Le contact a été enlevé de votre liste !";

        $req  =
            "select cliste_cod,cliste_nom from contact_liste where cliste_perso_cod = $perso_cod and cliste_cod = $liste ";
        $stmt = $pdo->query($req);
        if ($stmt->rowCount() == 0)
        {
            echo "<p>Vous n'avez pas accès à cette liste !";
            break;
        }
        $result       = $stmt->fetch();
        $nom_liste    = $result['cliste_nom'];
        $req          = "select perso_nom,contact_perso_cod from perso,contact ";
        $req          = $req . "where contact_cliste_cod = $liste ";
        $req          = $req . "and contact_perso_cod = perso_cod ";
        $stmt         = $pdo->query($req);
        $contenu_page .= "<p><strong>Gestion de la liste " . $nom_liste . "</strong>";
        if ($stmt->rowCount() == 0)
        {
            $contenu_page .= '<p>Aucun contact dans cette liste !';
        } else
        {
            $contenu_page .= "<table>";
            while ($result = $stmt->fetch())
            {
                $contenu_page .= "<tr>";
                $contenu_page .= "<td class=\"soustitre2\"><p>" . $result['perso_nom'] . "</td>";
                $contenu_page .= '<td><p><a href="' . $_SERVER['PHP_SELF'] . '?m=' . $m . '&methode=dcontact&contact=' . $result['contact_perso_cod'] . "&liste=" . $liste . "\">Retirer !</A>";
            }
            $contenu_page .= "</table>";
        }
        if ($stmt->rowCount() < 20)
        {
            $contenu_page .= '<p><a href="' . $_SERVER['PHP_SELF'] . '?methode=aliste_a&liste=' . $liste . '&m=' . $m . '">Ajouter un contact ?</a>';
        }
        $contenu_page .= '<a href="' . $_SERVER['PHP_SELF'] . '?methode=dliste&liste=' . $liste . '&m=' . $m . '"><p>Détruire cette liste ? </a>(opération définitive !)';

        break;
}