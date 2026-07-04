<?php
class kirishiacOverlord extends BaseShip{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
	$this->pointCost = 4300;
	$this->faction = "Kirishiac Lords";
        $this->phpclass = "kirishiacOverlord";
        $this->imagePath = "img/ships/kirishiacLordship2.png";
        $this->shipClass = "Overlord";
        $this->shipSizeClass = 3;
	    $this->isd = "Ancient";
		$this->factionAge = 3; //1 - Young, 2 - Middleborn, 3 - Ancient, 4 - Primordial
	    $this->notes = 'Atmospheric capable.';
        $this->variantOf = "Lordship";

        $this->gravitic = true;
		$this->advancedArmor = true;   
		$this->hardAdvancedArmor = true;
		
        $this->forwardDefense = 16;
        $this->sideDefense = 15;
        
        $this->turncost = 0.5;
        $this->turndelaycost = 0.5;
        $this->accelcost = 3;
        $this->rollcost = 4;
        $this->pivotcost = 2;
		$this->iniativebonus = 10;

        $orbitalHitChart = array( //Orbital Hits sub-chart (d20): 1-6 the mounted weapon, 7-20 the orbital itself
            6 => "Weapon",
            20 => "Orbital"
            );

        $heavyOrbitalHitChart = array( //Heavy Orbital Hits sub-chart (d20): 1-6 the mounted weapon, 7-8 the attached Self Repair, 9-20 the orbital itself
            6 => "Weapon",
            8 => "Self Repair",
            20 => "Orbital"
            );

        $this->addPrimarySystem(new Reactor(7, 35, 0, 0));
        $this->addPrimarySystem(new FlagBridge(8, 24, 0, 1, 'Kirishiac Command Bonus', 30,  true, true, true, false));
		$scanner = new Scanner(7, 24, 0, 14);
		$scanner->markAdvanced();
		$this->addPrimarySystem($scanner);			
        $this->addPrimarySystem(new Engine(7, 30, 0, 14, 4));
        $this->addPrimarySystem(new JumpEngine(8, 25, 6, 8));
        $this->addPrimarySystem(new SelfRepair(7, 12, 6)); //armor, structure, output


		//orbital 5th arg (profileAdjust) is legacy/ignored - defence profile is flat per class (8 / Light 7 / Heavy 10)
		$orbitalA = new KirishiacOrbital(6, 18, 'L', 'A', 0, $orbitalHitChart);
		$beamA = new AntigravityBeam(6, 6, 3, 270, 90, 'A');
		$orbitalA->addOrbitalWeapon($beamA);
		$this->addFrontSystem($orbitalA);
		$this->addFrontSystem($beamA);

		$orbitalB = new KirishiacOrbital(6, 18, 'R', 'B', 0, $orbitalHitChart);
		$beamB = new AntigravityBeam(6, 6, 3, 270, 90, 'B');
		$orbitalB->addOrbitalWeapon($beamB);
		$this->addFrontSystem($orbitalB);
		$this->addFrontSystem($beamB);

        $this->addFrontSystem(new GraviticThruster(7, 15, 0, 4, 1));		
        $this->addFrontSystem(new GraviticThruster(7, 15, 0, 4, 1));       
		$this->addFrontSystem(new HypergravitonBlaster(7, 30, 15, 300, 60));

		$orbitalF = new KirishiacOrbital(6, 18, 'L', 'F', 0, $orbitalHitChart);
		$beamF = new AntigravityBeam(6, 6, 3, 90, 270, 'F');
		$orbitalF->addOrbitalWeapon($beamF);
		$this->addAftSystem($orbitalF);
		$this->addAftSystem($beamF);

		$orbitalE = new KirishiacOrbital(6, 18, 'R', 'E', 0, $orbitalHitChart);
		$beamE = new AntigravityBeam(6, 6, 3, 90, 270, 'E');
		$orbitalE->addOrbitalWeapon($beamE);
		$this->addAftSystem($orbitalE);
		$this->addAftSystem($beamE);
        
        $this->addAftSystem(new GraviticThruster(7, 15, 0, 4, 2));	
        $this->addAftSystem(new GraviticThruster(7, 15, 0, 4, 2));
        $this->addAftSystem(new GraviticThruster(7, 15, 0, 4, 2));
       
		//Heavy Orbital C (left section): the mounted Hypergraviton Beam stays operational while
		//docked, at the reduced stowed arc; the attached Self Repair may only service the
		//orbital's own systems (doubled while docked)
		$beamC = new HypergravitonBeam(7, 30, 12, 120, 300, 'C');
		$beamC->setStowedArcs(0, 60); //undeployed (docked) firing arc
        $selfRepairC = new SelfRepair(7, 4, 2); //armor, structure, output
		$orbitalC = new KirishiacHeavyOrbital(7, 42, 'L', 'C', 0, $heavyOrbitalHitChart);
		$orbitalC->addOrbitalWeapon($beamC);
        $orbitalC->addOrbitalSystem($selfRepairC);
		$this->addLeftSystem($beamC);
		$this->addLeftSystem($orbitalC);
		$this->addLeftSystem($selfRepairC);

        $this->addLeftSystem(new GraviticThruster(7, 25, 0, 7, 3));


		$beamD = new HypergravitonBeam(7, 30, 12, 330, 210, 'D');
		$beamD->setStowedArcs(300, 360); //undeployed (docked) firing arc
        $selfRepairD = new SelfRepair(7, 4, 2); //armor, structure, output
		$orbitalD = new KirishiacHeavyOrbital(7, 42, 'R', 'D', 0, $heavyOrbitalHitChart);
		$orbitalD->addOrbitalWeapon($beamD);
        $orbitalD->addOrbitalSystem($selfRepairD);
		$this->addRightSystem($beamD);
		$this->addRightSystem($orbitalD);
		$this->addRightSystem($selfRepairD);

        $this->addRightSystem(new GraviticThruster(7, 25, 0, 7, 4));


        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 8, 72));  
        $this->addAftSystem(new Structure( 8, 72));   
        $this->addLeftSystem(new Structure( 8, 90));  
        $this->addRightSystem(new Structure( 8, 90));  
        $this->addPrimarySystem(new Structure( 8, 72));
        

        $this->hitChart = array(
            0=> array(
                    10 => "Structure",
                    12 => "Self Repair",
                    14 => "Scanner",
                    16 => "Engine",
                    17 => "Jump Engine",
                    19 => "Reactor",
                    20 => "C&C",
            ),
            1=> array(
                    5 => "Thruster",
                    7 => "Hypergraviton Blaster",
                    10 => "Orbital",
                    18 => "Structure",
                    20 => "Primary",
            ),
            2=> array(
                    8 => "Thruster",
                    10 => "Orbital",
                    18 => "Structure",
                    20 => "Primary",
            ),
            3=> array(
                    5 => "Thruster",
                    11 => "Heavy Orbital",
                    18 => "Structure",
                    20 => "Primary",
            ),
            4=> array(
                    5 => "Thruster",
                    11 => "Heavy Orbital",
                    18 => "Structure",
                    20 => "Primary",
            ),
            
        );
    }
}
