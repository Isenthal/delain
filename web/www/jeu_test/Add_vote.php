<?php
include_once "verif_connexion.php";
$db = new base_delain;

if ($compt_cod != null)
{


    // on test s'il y a un vote a cette journée

    //error_log('debut de l\'ajout de vote compte:'.$compt_cod);
    $IP = $_POST['IP'];
    //error_log('vote, ip client :'.$IP);
    if ($db->verification_vote($compt_cod))
    {
        // get client IP

        $IpReq = "SELECT icompt_cod FROM public.compte_ip WHERE icompt_compt_cod = " . $compt_cod . " ORDER BY icompt_compt_date DESC LIMIT 1;"; // rechercher la dernière connexion.
        $db->query($IpReq);
        $db->next_record();
        $Code_ip_table = $db->f('icompt_cod');

        $pdo = new bddpdo;  //#LAG ici, pdo est préférable pour protéger de l'injection sql 
        $add
            = "INSERT INTO public.compte_vote_ip(
                compte_vote_icompt_cod,compte_vote_compte_cod,compte_vote_ip_compte)
                VALUES (" . $Code_ip_table . "," . $compt_cod . ",:ip_address);";
        //error_log('Insertion vote requete:'.$add);
        //$db->query($add);         //#LAG: Injection SQL possible, il vaut mieux utiliser pdo (car $IP = $_POST['IP'] on ne peut pas faire confiance à son contenu)      
        $stmt = $pdo->prepare($add);
        $stmt = $pdo->execute(array(":ip_address" => $IP ), $stmt);
                
        echo "Tout s'est bien passé!";
    }
    else
    {
        //error_log('Le compte a deja voté');
        echo "Tu as déjà voté petit malin!";
    }
    // error_log('Fin de l\'ajout de vote');


}
