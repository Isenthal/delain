<?php


$param = new parametres();
//Interface pour rendre un objet enchantable. Risque de détruire l'objet
if (!isset($methode)) {
    $methode = "debut";
}
switch ($methode) {
    case "debut":
//On regarde les objets enchantables du perso en fonction de leur type, ainsi que si ils sont identifiés
        $req = "select gobj_tobj_cod,gobj_distance,obj_enchantable,gobj_chance_enchant,obj_nom,obj_nom_generique,obj_cod
				from objet_generique,objets,perso_objets
				where obj_gobj_cod = gobj_cod
				and perobj_obj_cod = obj_cod
				and gobj_tobj_cod in (1,2,4,6,7,27,40,41) and gobj_chance_enchant > 0
				and perobj_identifie = 'O'
				and perobj_perso_cod = $perso_cod";
        $req2 = $req . ' and obj_enchantable = 2
										order by gobj_tobj_cod,obj_nom desc';
        $stmt = $pdo->query($req2);
        $contenu_page .= '<p align="left"><strong>Les objets suivants sont déjà enchantés :</strong>
													<table>';
        if ($stmt->rowCount() == 0) {
            $contenu_page .= '<td><em>Aucun objet enchanté à votre disposition</em></td>';
        } else {
            while ($result = $stmt->fetch())//Cas des objets enchantés
            {
                $contenu_page .= '<td>' . $result['obj_nom'] . ' <em>(' . $result['obj_nom_generique'] . ')</em></td>';
            }
        }
        $contenu_page .= '</table></p>';
        $contenu_page .= '<p align="left"><strong>Les objets suivants sont déjà enchantables :</strong>
													<table>';
        $req1 = $req . ' and obj_enchantable = 1
										order by gobj_tobj_cod,obj_nom desc';
        $db2->query($req1);
        if ($stmt2->rowCount() == 0) {
            $contenu_page .= '<td><em>Aucun objet enchantable à votre disposition</em></td>';
        } else {
            while ($result2 = $stmt2->fetch()) //Cas des objets enchantables
            {
                $contenu_page .= '<td>' . $result2['obj_nom'] . ' <em>(' . $result2['obj_nom_generique'] . ')</em></td>';
            }
        }
        $contenu_page .= '</table></p>';
        $contenu_page .= '<p align="left"><strong>Liste des objets qui doivent être manipulés pour devenir enchantables : </strong>
													<table>';
        $req4 = $req . ' and obj_enchantable = 0 and gobj_chance_enchant > 0
										order by gobj_tobj_cod,obj_nom desc';           // Marlyza 2019-04-09 - Cas particulier: ne pas laisser la possibilité d'anchante rles objet à 0% de chance
        $db3->query($req4);
        if ($stmt3->rowCount() == 0) {
            $contenu_page .= '<td><em>Aucun objet non enchanté et enchantable à votre disposition</em></td>';
        } else {
            $compt = 0;
            while ($result3 = $stmt3->fetch()) //Cas des objets non enchantés et non enchantables pour l'instant
            {
                if ($compt == 0) {
                    $contenu_page .= '<td class="titre">Objet</td><td class="titre">Chances de rendre <br>l\'objet enchantable : </td>
													<td class="titre">Action</td>
													<td><em><strong>Lisez bien l\'aide avant de valider votre action !<br>Celle-ci est définitive ! Son coût est de ' . $param->getparm(115) . 'PA</strong></em></td>';
                }
                $chance = $result3['gobj_chance_enchant'];
                if ($chance == 0) {
                    $chance_indic = "Nulles";
                } else if ($chance <= 10) {
                    $chance_indic = "Faibles";
                } else if ($chance <= 20) {
                    $chance_indic = "Moyennes";
                } else if ($chance <= 30) {
                    $chance_indic = "Fortes";
                } else {
                    $chance_indic = "Exceptionnelles";
                }
                $contenu_page .= '<tr><td class="soustitre2">' . $result3['obj_nom'] . ' <em>(' . $result3['obj_nom_generique'] . ')</em></td>
										<td class="soustitre2" style="text-align:center"><strong>' . $chance_indic . '</strong></td>
										<td class="soustitre2"><a href="' . $PHP_SELF . '?methode=enc&obj=' . $result3['obj_cod'] . '&t_ench=2"><em>Procéder au forgeamage de cet objet</em></a></td></tr>';
                $compt = $compt + 1;
            }
        }
        $contenu_page .= '</table></p>';

        break;

    case "enc":
        //On vérifie l'objet
        $req = "select gobj_tobj_cod,gobj_distance,obj_enchantable,gobj_chance_enchant,obj_nom,obj_nom_generique,obj_cod
				from objet_generique,objets,perso_objets
				where obj_gobj_cod = gobj_cod
				and perobj_obj_cod = obj_cod
				and obj_cod = $obj
				and gobj_tobj_cod in (1,2,4,6,7,27,40,41) and gobj_chance_enchant > 0
				and perobj_identifie = 'O'
				and perobj_perso_cod = $perso_cod
				order by obj_enchantable,gobj_tobj_cod desc";
        $stmt = $pdo->query($req);
        $result = $stmt->fetch();
        if ($stmt->rowCount() == 0) // L'objet n'est pas relié au perso
        {
            $contenu_page .= 'Ceci n\'est pas très malin ...';
            break;
        }
        if ($result['obj_enchantable'] != '0')//Cas des objets non enchantés et non enchantables pour l'instant
        {
            $contenu_page .= 'Ceci n\'est pas très malin ...';
            break;
        }
        $chance_enchant = $result['gobj_chance_enchant'];
        //On récupère les compétences du perso
        $req_comp = "select pcomp_pcomp_cod,pcomp_modificateur from perso_competences
									where pcomp_perso_cod = $perso_cod and pcomp_pcomp_cod in (88,102,103)";
        $stmt = $pdo->query($req_comp);
        $result = $stmt->fetch();
        $forgeamage = $result['pcomp_pcomp_cod'];
        $forgeamage_pourcent = $result['pcomp_modificateur'];
        if ($forgeamage == 88) {
            $competence_facteur = 1;
        } else if ($forgeamage == 102) {
            $competence_facteur = 2;
        } else if ($forgeamage == 103) {
            $competence_facteur = 3;
        } else //Le perso n'est pas un enchanteur
        {
            $contenu_page .= 'Ceci n\'est pas très malin ...';
            break;
        }
        $gain_renommee = 2;

        $req_comp = "select perso_energie,perso_pa from perso
										where perso_cod = " . $perso_cod;
        $stmt = $pdo->query($req_comp);
        $result = $stmt->fetch();
        $perso_energie = $result['perso_energie'];
        $pa = $result['perso_pa'];

        //On vérifie les pa
        if ($pa < $param->getparm(115)) {
            $contenu_page .= '<strong>Vous ne possédez pas suffisamment de PA pour réaliser cette opération !</strong><br>';
            break;
        }

        //On regarde l'énergie de la case : niveau minimum nécessaire, et risque si le niveau est trop élevé. 1000 est un niveau mini
        $req_comp = "select pos_magie,pos_cod from positions,perso_position
									where ppos_perso_cod = $perso_cod and ppos_pos_cod = pos_cod";
        $stmt = $pdo->query($req_comp);
        $result = $stmt->fetch();
        $position = $result['pos_cod'];
        $puissance_case = $result['pos_magie'];
        if ($puissance_case < $param->getparm(114)) {
            $contenu_page .= '<strong>La puissance magique à cet endroit n\'est pas suffisante pour tenter de rendre enchantable un objet à cet endroit !
													<br>Rien ne se produit</strong><br>';
            break;
        }
        $perte_pa = $param->getparm(115);
        $energie_necessaire = 50;
        //On va regarder si l'énergie du perso est compatible avec celle de la pierre
        if ($energie_necessaire > $perso_energie) {
            $contenu_page .= 'Vous échouez dans le forgeamage de cet objet !
													<br>Vous perdez votre énergie accumulée, gachée dans cette action sans résultat<br>';

            $req = 'update perso set perso_energie = 0, perso_renommee_artisanat = perso_renommee_artisanat - 0.5 where perso_cod = ' . $perso_cod;
            $stmt = $pdo->query($req);
            $result = $stmt->fetch();
            break;
        }

        //On teste si le perso arrive enchanter l'objet en utilisant sa compétence en enchantement
        $concentration = 0;
        $req = 'select concentration_perso_cod from concentrations
							where concentration_perso_cod = ' . $perso_cod;
        $stmt = $pdo->query($req);
        if ($stmt->rowCount() != 0) {
            $contenu_page .= '<strong>Vous vous êtes concentré avant cette action</strong><br>';
            $concentration = 20;
            $req = 'delete from concentrations where concentration_perso_cod = ' . $perso_cod;
            $stmt = $pdo->query($req);
        }
        $px_gagne = 0;
        $de = rand(1, 100);
        $limite_comp = $param->getparm(1);
        $chance = $chance_enchant + (floor($forgeamage_pourcent / 10) * $competence_facteur) + $concentration;
        $contenu_page .= '<br>Vos chances de réussite modifiées sont de <strong>' . $chance . '</strong>, tenant compte de la difficulté de cette opération et de l\'objet concerné. Votre lancer de dé est de <strong>' . $de . '</strong>.<br>';
        if ($de <= 5) {
            $reussite = 1;
            $contenu_page .= 'Vous avez donc fait une réussite critique !';
            $perte_pa = $perte_pa - 2;
        } else if ($de <= $chance) {
            $reussite = 1;
        } else {
            $contenu_page .= '<strong>Vous échouez dans le forgeamage de cet objet.</strong><br>';
            $reussite = 0;
            if ($forgeamage_pourcent <= $limite_comp) //Amélioration si < 40%
            {
                $req = 'select ameliore_competence_px(' . $perso_cod . ',' . $forgeamage . ',' . $forgeamage_pourcent . ') as resultat';
                $stmt = $pdo->query($req);
                $result = $stmt->fetch();
                $jet_ameliore = explode(';', $result['resultat'], 3);
                $jet = $jet_ameliore[0];
                $ameliore = $jet_ameliore[1];
                $nouvelle_valeur = $jet_ameliore[2];
                $contenu_page .= '<br>Votre jet d’amélioration est de ' . $jet . '<br>'; // pos 7 8 9 10
                if ($ameliore == '1') {
                    $contenu_page .= 'vous avez donc <strong>amélioré</strong> cette compétence. <br>';
                    $contenu_page .= 'Sa nouvelle valeur est ' . $nouvelle_valeur . '<br><br>';
                    $px_gagne = $px_gagne + 1;
                } else {
                    $contenu_page .= 'vous n’avez pas amélioré cette compétence.<br><br> ';
                }
            }

            $gain_renommee = -0.5;
            // Conséquences négatives (PV, dégradation de l'objet, déflagration, Vue, ...)
            $req = 'select enchantement_rate(' . $perso_cod . ') as resultat';
            $stmt = $pdo->query($req);
            $result = $stmt->fetch();
            $contenu_page .= $result['resultat'];
            $req = 'update objets set obj_etat = obj_etat * 0.9, obj_etat_max = obj_etat_max * 0.9 where obj_cod = ' . $obj;
            $stmt = $pdo->query($req);
            $result = $stmt->fetch();
            $contenu_page .= '<br><br>L’objet que vous avez tenté de rendre enchantable a été détérioré par votre manipulation ratée.<br>';
        }
        if ($reussite == 1) {
            $contenu_page .= '<strong>Vous parvenez enchanter votre objet.</strong>
												<br>Vous devez maintenant regarder si vous pouvez l\'associer à des composants pour réaliser un enchantement<br>';
            //On modifie l'objet pour le rendre enchantable
            $req = 'update objets set obj_enchantable = 1 where obj_cod = ' . $obj;
            $stmt = $pdo->query($req);
            $result = $stmt->fetch();
            $req = 'select ameliore_competence_px(' . $perso_cod . ',' . $forgeamage . ',' . $forgeamage_pourcent . ') as resultat';
            $stmt = $pdo->query($req);
            $result = $stmt->fetch();
            $jet_ameliore = explode(';', $result['resultat'], 3);
            $jet = $jet_ameliore[0];
            $ameliore = $jet_ameliore[1];
            $nouvelle_valeur = $jet_ameliore[2];
            $contenu_page .= '<br>Votre jet d\'amélioration est de <strong>' . $jet . '</strong><br>'; // pos 7 8 9 10
            if ($ameliore == '1') {
                $contenu_page .= 'vous avez donc <strong>amélioré</strong> cette compétence. <br>';
                $contenu_page .= 'Sa nouvelle valeur est <strong>' . $nouvelle_valeur . '</strong><br><br>';
                $px_gagne = $px_gagne + 1;
            } else {
                $contenu_page .= 'vous n\'avez pas amélioré cette compétence.<br><br> ';
            }
            $px_gagne = $px_gagne + 4;
        }
        // Diminution de la puissance magique de la case & mise à jour de l'énergie du perso et des PA et des px

        // Temporaire : vents infinis pour le marché de Léno 2013
        if ($position != 152794)    // == -6 / -7 dans la Halle Merveilleuse
        {
            $req = 'update positions set pos_magie = pos_magie - 500 where pos_cod = ' . $position;
            $stmt = $pdo->query($req);
            $result = $stmt->fetch();
            $contenu_page .= '<br><br>La puissance magique de ce lieu a fortement diminué.';
        }
        //On diminue les PA, on augmente les pxs, on diminue l'énergie du perso
        $req = 'select perso_pa from perso where perso_cod = ' . $perso_cod;
        $stmt = $pdo->query($req);
        $result = $stmt->fetch();
        $pa = $result['perso_pa'];
        if ($pa < $perte_pa) {
            $perte_pa = $pa;
        }
        $req = 'update perso set perso_energie = perso_energie - ' . $energie_necessaire . ',
                perso_pa = perso_pa - ' . $perte_pa . ',perso_px = perso_px + ' . $px_gagne . ', 
                perso_renommee_artisanat = perso_renommee_artisanat + ' . $gain_renommee . '
                where perso_cod = ' . $perso_cod;
        $stmt = $pdo->query($req);
        $result = $stmt->fetch();
        $contenu_page .= '<br>Vous vous sentez assez épuisé, il va vous falloir récupérer un peu d\'énergie avant de pouvoir espérer tenter d\'enchanter un autre objet.';
        if ($puissance_case > 5000) {
            $contenu_page .= '<br><br>Malheureusement, la puissance a cet endroit était trop importante. Vous en subissez quelques ratées<br>';
            $req = 'select enchantement_rate(' . $perso_cod . ') as resultat';
            $stmt = $pdo->query($req);
            $result = $stmt->fetch();
            $contenu_page .= $result['resultat'];
        }
        break;
}