--
-- Name: ea_projection(integer, integer, text, integer, character, text, numeric, text, json); Type: FUNCTION; Schema: public; Owner: delain
--

create or replace function ea_projection(integer, integer, text, integer, character, text, numeric, text, json) RETURNS text
LANGUAGE plpgsql
AS $_$/**************************************************/
/* ea_projection                             */
/* Applique les bonus et effectue les actions     */
/* spécifiées lors de l’activation d’une DLT.     */
/* On passe en paramètres:                        */
/*   $1 = source (perso_cod du monstre)           */
/*   $2 = Perso ciblé                             */
/*   $3 = force de projection(format dé roliste)  */
/*   $4 = distance (-1..n)                        */
/*   $5 = cibles (Type SAERTPCO)                  */
/*   $6 = cibles nombre, au format rôliste        */
/*   $7 = Probabilité de déclenchement            */
/*   $8 = Message d’événement associé             */
/*   $9 = Paramètre additionnels                  */
/**************************************************/
declare
  -- Parameters
  v_source alias for $1;
  v_cible_donnee alias for $2;
  v_force alias for $3;
  v_distance alias for $4;
  v_cibles_type alias for $5;
  v_cibles_nombre alias for $6;
  v_proba alias for $7;
  v_texte_evt alias for $8;
  v_params alias for $9;

  -- initial data
  v_x_source integer;          -- source X position
  v_y_source integer;          -- source Y position
  v_et_source integer;         -- etage de la source
  v_type_source integer;       -- Type perso de la source
  v_cibles_nombre_max integer; -- Nombre calculé de cibles
  v_race_source integer;       -- La race de la source
  v_position_source integer;   -- Le pos_cod de la source
  v_cible_du_monstre integer;  -- La cible actuelle du monstre
  v_source_nom text;           -- nom du perso source
  v_dist_proj integer;         -- distance de projection
  v_pos_projection integer;    -- la case sur laquelle la cible atterrie
	v_perso_fam integer;		     -- Familier de la cible
	v_degats_portes integer;		 -- dégat de la projection
	v_event_txt text;	           -- pour autres evenements
  -- Output and data holders
  ligne record;                -- Une ligne d’enregistrements
  i integer;                   -- Compteur de boucle
  v_niveau_attaquant integer;  -- Resistance magique
  code_retour text;

  v_compagnon integer;         -- cod perso du familier si aventurier et de l'aventurier si familier
  v_distance_min integer;      -- distance minimum requis pour la cible
  v_exclure_porteur text;      -- le porteur et les compagnons sont inclus dans le ciblage
  v_equipage integer;          -- le partenaire d'équipage: cavalier/monture

begin

  -- Chances de déclencher l’effet
  if random() > v_proba then
    -- return 'Pas d’effet automatique de « projection/attraction ».';
    return '';
  end if;
  -- Initialisation des conteneurs
  code_retour := '';

  -- Position et type perso
  select into v_x_source, v_y_source, v_et_source, v_type_source, v_race_source, v_position_source, v_cible_du_monstre, v_source_nom, v_niveau_attaquant
    pos_x, pos_y, pos_etage, perso_type_perso, perso_race_cod, pos_cod, perso_cible, perso_nom, perso_niveau
  from perso_position, positions, perso
  where ppos_perso_cod = v_source
        and pos_cod = ppos_pos_cod
        and perso_cod = v_source;

  -- on recupère le code de son compagnon (0 si pas de compagnon)
  if v_type_source=1 then
  	select into v_compagnon pfam_familier_cod from perso_familier inner join perso on perso_cod = pfam_familier_cod where pfam_perso_cod = v_source and perso_actif = 'O';
  	if not found then
  	    v_compagnon:=0;
    end if;
  else
  	select into v_compagnon pfam_perso_cod from perso_familier inner join perso on perso_cod = pfam_perso_cod where pfam_familier_cod = v_source and perso_actif = 'O';
  	if not found then
  	    v_compagnon:=0;
    end if;
  end if;

  -- Cibles
  v_cibles_nombre_max := f_lit_des_roliste(v_cibles_nombre);

  -- Si le ciblage est limité par la VUE on ajuste la distance max
  if (v_params->>'fonc_trig_vue')::text = 'O' then
      v_distance := CASE WHEN  v_distance=-1 THEN distance_vue(v_source) ELSE LEAST(v_distance, distance_vue(v_source)) END ;
  end if;
  v_distance_min := CASE WHEN COALESCE((v_params->>'fonc_trig_min_portee')::text, '')='' THEN 0 ELSE ((v_params->>'fonc_trig_min_portee')::text)::integer END ;
  -- ciblage du porteur et de ses compagnons (familier/cavalier/monture)
  v_exclure_porteur := COALESCE((v_params->>'fonc_trig_exclure_porteur')::text, 'N')  ;
  v_equipage = COALESCE(f_perso_cavalier(v_source), COALESCE(f_perso_monture(v_source),0));
  if v_compagnon=0 and v_equipage != 0  then
      v_compagnon:=v_equipage ;
  end if;

  -- Et finalement on parcourt les cibles.
  for ligne in (select perso_cod , perso_type_perso , perso_race_cod, perso_nom, perso_niveau, perso_int, perso_con, pos_cod, perso_pv
                from perso
                  inner join perso_position on ppos_perso_cod = perso_cod
                  inner join positions on pos_cod = ppos_pos_cod
                  left outer join lieu_position on lpos_pos_cod = pos_cod
                  left outer join lieu on lieu_cod = lpos_lieu_cod
                where perso_actif = 'O'
                      and perso_tangible = 'O'
                      -- À portée
                      and ((pos_x between (v_x_source - v_distance) and (v_x_source + v_distance)) or v_distance=-1)
                      and ((pos_y between (v_y_source - v_distance) and (v_y_source + v_distance)) or v_distance=-1)
                      and ((v_distance_min = 0) or (abs(pos_x-v_x_source) >= v_distance_min) or (abs(pos_y-v_y_source) >= v_distance_min))
                      and pos_etage = v_et_source
                      and ( trajectoire_vue(pos_cod, v_position_source) = '1' or (v_params->>'fonc_trig_vue')::text != 'O')
                      -- Hors refuge si on le souhaite
                      and (v_cibles_type = 'P' or coalesce(lieu_refuge, 'N') = 'N')
                      -- cas d'exclusion du porteur d'ea et de ses compagnos
                      and (perso_cod not in (v_source, v_equipage, v_compagnon) or (v_exclure_porteur='N') or (v_cibles_type = 'S') )
                      -- Parmi les cibles spécifiées
                      and
                      ((v_cibles_type = 'S' and perso_cod = v_source) or
                       (v_cibles_type = 'A' and perso_type_perso!=2 and v_type_source!=2) or
                       (v_cibles_type = 'A' and perso_type_perso=2  and v_type_source=2) or
                       (v_cibles_type = 'E' and perso_type_perso!=2 and v_type_source=2) or
                       (v_cibles_type = 'E' and perso_type_perso=2  and v_type_source!=2) or
                       (v_cibles_type = 'R' and perso_race_cod = v_race_source) or
                       (v_cibles_type = 'V' and f_est_dans_la_liste(perso_race_cod, (v_params->>'fonc_trig_races')::json)) or
                       (v_cibles_type = 'J' and perso_type_perso = 1) or
                       (v_cibles_type = 'L' and perso_cod = v_compagnon) or
                       (v_cibles_type = 'P' and perso_type_perso in (1, 3)) or
                       (v_cibles_type = 'C' and perso_cod = v_cible_donnee) or
                       (v_cibles_type = 'O' and perso_cod = v_cible_donnee) or
                       (v_cibles_type = 'M' and perso_cod = COALESCE(f_perso_cavalier(v_cible_donnee), COALESCE(f_perso_monture(v_cible_donnee),0))) or
                       (v_cibles_type = 'T'))
                -- cas spécifique de la projection:
                      and (perso_cod!= v_source or v_cibles_type = 'S')                                                 -- on ne projette soit même que si c'est la seule cible (ça supprime les locks de combats)) !!!
                      and (perso_type_perso!=3)                                                                         -- on ne projette pas les familiers
                      and (perso_cod!=coalesce(v_cible_du_monstre,0) or (v_params->>'fonc_trig_cible_combat')::text !='O')   -- sauf la cible de combat si demandé
                -- Dans les limites autorisées
                order by random()
                limit v_cibles_nombre_max)
  loop
      -- On peut maintenant appliquer le bonus ou l’action sur une cible unique.

      code_retour := code_retour || '<br />'|| ' « projection/attraction »  de ' || ligne.perso_nom || '.' ;

      v_dist_proj := f_lit_des_roliste(v_force);   -- force de projection


      if v_dist_proj > 0 then

          if coalesce(v_params->>'ancien_pos_cod'::text, '') != '' then

              -- cas de déplacement, la projection se fait dans le la direction du déplacement
              if (v_params->>'fonc_trig_sens'::text = '1') then
                  v_pos_projection := trajectoire_projection(ligne.pos_cod, (v_params->>'ancien_pos_cod'::text)::integer, v_position_source, v_dist_proj) ;
              else
                  v_pos_projection := trajectoire_projection(ligne.pos_cod, v_position_source, (v_params->>'ancien_pos_cod'::text)::integer, v_dist_proj) ;
              end if;

          elseif v_position_source = ligne.pos_cod then
              -- ce n'est pas un déplacement, et la cible est sur la case du perso ou il s'agit du perso lui-même (pas d'implosion possible, explosion dans une direction aléatoire)
               if (v_params->>'fonc_trig_sens'::text = '1') then
                    -- explosion (on prend une des 8 cases autour de la source pour etablir le sens de la projection)
                    select into v_pos_projection lancer_position from lancer_position(v_position_source,1) limit 1;
                    v_pos_projection := trajectoire_projection(ligne.pos_cod, v_position_source, v_pos_projection, v_dist_proj) ;
              else
                    -- implosion (pas de changement, le perso reste là où il est)
                    v_pos_projection := v_position_source ;
              end if;

          else
              -- ce n'est pas un déplacement, la direction est celle du perso vers la cible
              if (v_params->>'fonc_trig_sens'::text = '1') then
                  -- explosion
                  v_pos_projection := trajectoire_projection(ligne.pos_cod, v_position_source, ligne.pos_cod, v_dist_proj) ;
              else
                  -- implosion (ATTENTION: au max on rapproche sur la cible sur la source)
                  v_pos_projection := trajectoire_projection(ligne.pos_cod, ligne.pos_cod, v_position_source, LEAST(v_dist_proj, distance(v_position_source, ligne.pos_cod)) ) ;
              end if;

          end if;
      end if;

      -- la nouvelle position a été calculée, faire le deplacement --
      if v_dist_proj = 0 then
          v_pos_projection = ligne.pos_cod ;    -- projection de distance 0, on ne bouge pas la cible mais retire ses locks de combat
          if ligne.perso_cod=v_source then
              -- le perso c'est auto-projetté, on lui retire tous ses locks
              delete from lock_combat where lock_cible = ligne.perso_cod;
              delete from lock_combat where lock_attaquant = ligne.perso_cod;
          else
              -- le perso a  projetté a 0 case, on lui retire juste les locks avec le projetteur
              delete from lock_combat where lock_cible = ligne.perso_cod and lock_attaquant=v_source;
              delete from lock_combat where lock_attaquant = ligne.perso_cod and lock_cible=v_source;
          end if;

      elseif abs(v_pos_projection) != ligne.pos_cod then
          -- déplacer la cible (et eventuellement son familier avec lui)
          update perso_position set ppos_pos_cod = abs(v_pos_projection) where ppos_perso_cod = ligne.perso_cod;
          delete from lock_combat where lock_cible = ligne.perso_cod;
          delete from lock_combat where lock_attaquant = ligne.perso_cod;
          delete from riposte where riposte_attaquant =ligne.perso_cod;
          delete from transaction	where tran_vendeur = ligne.perso_cod;
          delete from transaction	where tran_acheteur = ligne.perso_cod;
          select into v_perso_fam max(pfam_familier_cod) from perso_familier INNER JOIN perso ON perso_cod=pfam_familier_cod WHERE perso_actif='O' and pfam_perso_cod=ligne.perso_cod;
          if found then
              update perso_position	set ppos_pos_cod = abs(v_pos_projection)  where ppos_perso_cod = v_perso_fam;
              delete from lock_combat where lock_cible = v_perso_fam;
              delete from lock_combat where lock_attaquant = v_perso_fam;
              delete from riposte where riposte_attaquant = v_perso_fam;
              delete from transaction	where tran_vendeur = v_perso_fam;
              delete from transaction	where tran_acheteur = v_perso_fam;
          end if;

      end if;

      -- faire des dégats si projection sur un mur
      if v_pos_projection < 0 and  v_params->>'fonc_trig_degats'::text != '' then
          v_degats_portes := f_lit_des_roliste(v_params->>'fonc_trig_degats'::text);

          if v_degats_portes >= ligne.perso_pv then -- la cible a été tuée......
                code_retour := code_retour || '<br />' || ligne.perso_nom || ' a été projeté sur un mur, subissant '||trim(to_char(v_degats_portes,'9999'))||' points de dégats, le tuant sur le coup !';

                /* evts pour coup porté */
                v_event_txt := '[attaquant] a projeté [cible] sur un mur lui infligeant '||trim(to_char(v_degats_portes,'9999'))||' points de dégats, le tuant sur le coup !';
                perform insere_evenement(v_source, ligne.perso_cod, 66, v_event_txt, 'O', 'N', null);

                code_retour := code_retour || tue_perso_final(v_source,ligne.perso_cod);

          else
                code_retour := code_retour|| '<br />' || ligne.perso_nom || ' a été projeté sur un mur, subissant '||trim(to_char(v_degats_portes,'9999'))||' points de dégats.';

                /* evts pour coup porté */
                v_event_txt := '[attaquant] a projeté [cible] sur un mur lui infligeant '||trim(to_char(v_degats_portes,'9999'))||' points de dégats.';
                perform insere_evenement(v_source, ligne.perso_cod, 66, v_event_txt, 'O', 'N', null);

                update perso set perso_pv = perso_pv - v_degats_portes where perso_cod = ligne.perso_cod;

          end if;

      end if;

      -- On rajoute la ligne d’événements
      if v_texte_evt != '' then
          if strpos(v_texte_evt , '[cible]') != 0 then
            perform insere_evenement(v_source, ligne.perso_cod, 54, v_texte_evt, 'O', 'N', null);
          else
            perform insere_evenement(v_source, ligne.perso_cod, 54, v_texte_evt, 'O', 'O', null);
          end if;
      end if;

      ---------------------------
      -- les EA liés au déplacement (la projection est considéré comme un déplacement si la cible a été projeté ailleurs que sur sa case d'origine)
      ---------------------------
      if v_dist_proj > 0 then
        code_retour := code_retour || execute_fonctions(ligne.perso_cod, v_source, 'DEP', json_build_object('ancien_pos_cod',ligne.pos_cod,'ancien_etage',v_et_source, 'nouveau_pos_cod',abs(v_pos_projection),'nouveau_etage',v_et_source)) ;
      end if;

  end loop;

  -- if code_retour = '' then
  --   code_retour := 'Aucune cible éligible pour « projection/attraction »';
  -- end if;

  return code_retour;
end;$_$;


ALTER FUNCTION public.ea_projection(integer, integer, text, integer, character, text, numeric, text, json) OWNER TO delain;

