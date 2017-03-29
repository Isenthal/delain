#!/bin/bash
# TODO : commenter chaque ligne pour expliquer le but

#########################################
# On charge les variables d'env
source `dirname $0`/env
while true 
do
if [ 7 -le `cat /proc/loadavg | awk '{print $1}' | awk -F "." '{print $1}'` ]
then
echo "`date` : Charge systeme trop elevee au lancement général" >> $logdir/ia_auto.log
else
echo "`date` : Debut du traitement" >> $logdir/ia_auto.log
#########################################
# On met à jour les persos actifs
$psql -U webdelain -d delain -q -t << EOF >> /dev/null
update perso set perso_actif = 'N'
where perso_type_perso = 1
and perso_actif = 'O'
and not exists
(select 1 from perso_compte
where pcompt_perso_cod = perso_cod);
select reduc_compt_pvp();
\q
EOF
#########################################
# On créé une table temp qui va prendre
# la liste des monstres à traiter
$psql -U webdelain -d delain -q -t << EOF >> /dev/null
create table IF NOT EXISTS temp_monstres
(code_monstre integer CONSTRAINT firstkey PRIMARY KEY);
# au cas où...
truncate table temp_monstres;
# on la remplit
insert into temp_monstres (code_monstre)
select perso_cod from (select perso_cod from perso
where perso_type_perso = 2
and perso_actif = 'O'
and perso_tangible = 'O'
and perso_der_connex + ((perso_temps_tour/2)::text || ' minutes')::interval < now()
and (perso_dlt < now() or perso_pa >= 4)
and (perso_dirige_admin != 'O' or perso_dirige_admin is null)) t1
union all (select perso_cod from perso where perso_type_perso = 1
and perso_actif = 'O'
and (perso_dlt < now() or perso_pa >= 4)
and perso_quete in ('quete_ratier.php','enchanteur.php','quete_chasseur.php','quete_dispensaire.php','quete_alchimiste.php','quete_groquik.php', 'quete_accompagnateur.php'))
EOF
# on boucle sur ce qu'il reste à faire
afaire=1
while [ $afaire -eq 1 ]
do
    nb_a_faire=`psql -U webdelain -d delain -q -t -c "select count(*) from temp_monstres;"`
    if [ "$nb_a_faire" -eq "0" ]; then
        afaire=0
    else
        # on va lancer
        $psql -U webdelain -d delain -q -t << EOF
        select ia_monstre(code_monstre) from temp_monstres limit 10;
        # TODO : retirer de la table temp_monstres

EOF
    fi
done
$shellroot/liste_monstre.sh  >> $logdir/ia_auto.log
$shellroot/ia_boucle.sh >> $logdir/ia_auto.log
fi
sleep 10
done
