--
-- Name: pot_con_moyenne(integer,integer); Type: FUNCTION; Schema: potions; Owner: delain
--

CREATE or replace FUNCTION potions.pot_con_moyenne(integer,integer) RETURNS text
LANGUAGE plpgsql
AS $_$/*********************************************************/
/* function pot_force_moyenne                            */
/* parametres :                                          */
/*  $1 = personnage qui utilise la potion                */
/*  $2 = personnage qui boit la potion                   */
/* Sortie :                                              */
/*  code_retour = texte exploitable par php              */
/*********************************************************/
/**************************************************************/
/* 	       			                              */
/**************************************************************/
declare
  personnage alias for $1;	-- perso_cod
  cible alias for $2;	-- perso_cod
  code_retour text;				-- code retour
  v_gobj_cod integer;			-- code de l'objet générique
  duree integer;	-- Duree de l'effet
  effet integer;	-- Force de l'effet



begin
  /*********************************************************/
  /*        I N I T I A L I S A T I O N S                  */
  /*********************************************************/
  v_gobj_cod := 531;
  code_retour := '';
  /*Partie générique pour toutes les potions*/
  select into code_retour potions.potion_generique(personnage,cible,v_gobj_cod);
  if not found then
    code_retour := code_retour || 'Erreur ! Fonction générique non trouvée ';
  elsif substring(code_retour,1,1) != '0' then
    return split_part(code_retour,';',2);
  /*Tous les controles sont OK, on passe alors aux effets de la potion uniquement*/
  else
    code_retour := split_part(code_retour,';',2);
    duree := lancer_des(1,10) + 23;
    effet := lancer_des(1,3) + 1;
    perform f_modif_carac(cible,'CON',duree,effet);
	if cible = personnage then
		code_retour := code_retour || '<br>Sans exercice, vous êtes devenu temporairement plus résistant. Faites attention, car plus dure sera la chute. D''ici là, ce sont vos adversaires qui risquent d''être surpris ...';
	else
		code_retour := code_retour || '<br>Sans exercice, le buveur est devenu temporairement plus résistant. Qu''il fasse attention, car plus dure sera la chute. D''ici là, ce sont ses adversaires qui risquent d''être surpris ...';
	end if;
  end if;
  return code_retour;
end;	$_$;


ALTER FUNCTION potions.pot_con_moyenne(integer,integer) OWNER TO delain;