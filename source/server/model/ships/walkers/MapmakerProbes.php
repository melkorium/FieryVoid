<?php
class MapmakerProbes extends FighterFlight{

    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 210*6;
		$this->faction = "Walkers of Sigma-957";
		$this->phpclass = "MapmakerProbes";
		$this->shipClass = "Mapmaker Sensor Probes";
		$this->imagePath = "img/ships/WalkerMapmaker.png";
	    
		$this->isd = 'Ancient';
		$this->factionAge = 3; //1 - Young, 2 - Middleborn, 3 - Ancient, 4 - Primordial
		//$this->variantOf = "NONE";
        
		/*Vorlons use their own enhancement set */		
		Enhancements::nonstandardEnhancementSet($this, 'WalkerFighter');

		/* WALKERS OF SIGMA-957 (WALKERS_OF_SIGMA_PLAN.md 3.11, Stage 12) - the ONE flight in the
		   game with an EW pool. 3 points per FLIGHT per turn, split freely between OEW and DEW,
		   exactly as a ship spends its scanner output (D11: "like a ship", and every EW path in FV
		   spends one per-unit pool - a 6-craft flight does not get 18). Unspent points become DEW at
		   the Initial Orders commit, again exactly as a ship's do.
		   See FighterFlight::$ewCapacity for why this is the whole gate. */
		$this->ewCapacity = 3;

		//<br> between notes, as every other hull in the tree does - without it the four lines ran
		//together as one paragraph in the buy list.
		$this->notes = "Does not require hangar space.";
		$this->notes .= "Can use 3 EW points per turn (OEW and/or DEW).";
		$this->notes .= "<br>Carries a Jump Engine.";

		$this->forwardDefense = 5;
		$this->sideDefense = 8;
		$this->freethrust = 15;
		$this->offensivebonus = 8;
		$this->jinkinglimit = 8; //heavy fighter
		$this->turncost = 0.33;
        
		
	    $this->advancedArmor = true; 
        $this->gravitic = true;
        $this->maxFlightSize = 6;//this is very powerful craft, let's not overdo on its durability, limit flight size to 6

		/* FLEET CHECK ONLY, and the two flags say two different things (D14, and the user's
		   ruling of 2026-09-10).

		   $hangarRequired names the CUSTOM HANGAR CATEGORY these probes draw from, which is the
		   same string the Walker hulls declare their capacity in ($this->fighters =
		   array("Mapmaker Probes" => N) on Traveler / Waymarker / Pathfinder / Guideship). Without
		   it a Mapmaker classified itself off its jinking limit as an ordinary HEAVY fighter and
		   the two halves of the rule never met.

		   $noHangarRequired lifts the MAXIMUM only: a fleet with no carrier at all may still buy
		   them. It does NOT lift the 50% minimum - a Walker hull that declares Mapmaker Probes
		   capacity must still fill half of it, exactly like any other hangar in the game.

		   ⚠️ BOTH ARE FLEET-BUILDING FLAGS AND NOTHING ELSE. HangarOps is deliberately not taught
		   about either, because a Mapmaker still fills boxes the moment Stage 15's Traveler bay
		   carries one. */
		$this->hangarRequired = "Mapmaker Probes";
		$this->noHangarRequired = true;

		$this->iniativebonus = 20 *5;
		$this->populate();
    }


    public function populate(){

        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;

        for ($i = 0; $i < $toAdd; $i++){			
			$armour = array(3, 5, 3, 3);
			$fighter = new Fighter("MapmakerProbes", $armour, 13, $this->id);
			$fighter->displayName = "Mapmaker";
			$fighter->imagePath = "img/ships/WalkerMapmaker.png";
			$fighter->iconPath = "img/ships/WalkerMapmaker_large.png";
						
			//main weapon
			$fighter->addFrontSystem(new LightChromaticPulsar(330, 30));
			/* Medium Lightning Array, fighter mount (WALKERS_OF_SIGMA_PLAN.md 3.13, Stage 14).
			   THREE OR SIX PROBES FIRE AS ONE GUN - a lone array cannot fire at all, and the
			   whole combining rule lives in MedLightningArrayFtr::planFlightVolley, which walks
			   the FLIGHT rather than the craft. D16 also makes it exclusive with the Light
			   Chromatic Pulsar above, flight-wide, for the whole turn.

			   ⚠️ POSITIONAL SYSTEM IDS (trap 7). Adding a mount here shifts the id of every
			   system constructed after it on EVERY Mapmaker in EVERY live game. That is safe
			   only because Stages 12, 13 and 14 land in ONE deploy - the deployed tree still
			   has both this line and the Jump Engine below commented out. Anything landing
			   after that deploy must be APPENDED, never inserted. */
			$fighter->addFrontSystem(new MedLightningArrayFtr(330, 30)); //arcfrom, arcto

			//ramming attack 			
			$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack			
			/* Jump Engine (WALKERS_OF_SIGMA_PLAN.md 3.12, Stage 13). The 4th argument is $delay - the
			   B5W jump delay, which the class turns into loadingtime/turnsloaded - so this IS the
			   "recharge time of 10 turns" the rules ask for. ⚠️ A recharge rule must read $delay,
			   never $loadingtime (JUMP_GATES finding, and it applies verbatim here). The 5th argument
			   (vortex projection range) is left at the B5W standard 4.

			   ⭐ EVERY CRAFT CARRIES ONE AND THE FLIGHT HAS EXACTLY ONE. The charge is per SYSTEM
			   and the rule is per FLIGHT, so every read and write is routed through
			   FighterFlight::getFlightJumpEngine() - the SAMPLE fighter's engine - and the siblings
			   mirror its charge in JumpEngine::stripForJson. A second declaration in one turn is
			   refused by Firing::getVortexDeclarationBlock's one-vortex-per-SHOOTER rule, and a
			   fighter's shooter IS the flight. */
            $fighter->addAftSystem(new JumpEngine(0, 1, 0, 10));
			//Advanced Sensors w/ 3 EW
            $fighter->addAftSystem(new Fighteradvsensors(0, 1, 0));	//Need to modify this so it also provide the 3 EW Mapmakers have		
			
			
			$this->addSystem($fighter);			
		}	
    }//endof function populate



}



?>
