<?php
class kirishiacLordship extends BaseShip{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
	$this->pointCost = 3400;
	$this->faction = "Kirishiac Lords";
        $this->phpclass = "kirishiacLordship";
        $this->imagePath = "img/ships/kirishiacLordship2.png";
        $this->shipClass = "Lordship";
        $this->shipSizeClass = 3;
	    $this->isd = "Ancient";
		$this->factionAge = 3; //1 - Young, 2 - Middleborn, 3 - Ancient, 4 - Primordial
	    $this->notes = 'Atmospheric capable.';

        $this->gravitic = true;
		$this->advancedArmor = true;   
		$this->hardAdvancedArmor = true;
		
        $this->forwardDefense = 15;
        $this->sideDefense = 15;
        
        $this->turncost = 0.5;
        $this->turndelaycost = 0.5;
        $this->accelcost = 3;
        $this->rollcost = 4;
        $this->pivotcost = 2;
		$this->iniativebonus = 10;

        $orbitalHitChart = array( 
            6 => "Antigravity Beam",
            20 => "Structure"
            );
	
        $this->addPrimarySystem(new Reactor(7, 35, 0, 0));
        $this->addPrimarySystem(new CnC(8, 24, 0, 0));
		$scanner = new Scanner(7, 24, 0, 14);
		$scanner->markAdvanced();
		$this->addPrimarySystem($scanner);			
        $this->addPrimarySystem(new Engine(7, 25, 0, 12, 4));
        $this->addPrimarySystem(new JumpEngine(8, 25, 6, 8));
        $this->addPrimarySystem(new SelfRepair(7, 12, 6)); //armor, structure, output


		$orbitalA = new KirishiacOrbital(6, 18, 'L', 'A', -7, $orbitalHitChart);
		$beamA = new AntigravityBeam(6, 6, 3, 270, 90, 'A');
		$orbitalA->addMirror($beamA);
		$this->addFrontSystem($orbitalA);
		$this->addFrontSystem($beamA);

		$orbitalB = new KirishiacOrbital(6, 18, 'R', 'B', -7, $orbitalHitChart);
		$beamB = new AntigravityBeam(6, 6, 3, 270, 90, 'B');
		$orbitalB->addMirror($beamB);
		$this->addFrontSystem($orbitalB);
		$this->addFrontSystem($beamB);

        $this->addFrontSystem(new GraviticThruster(7, 15, 0, 4, 1));		
        $this->addFrontSystem(new GraviticThruster(7, 15, 0, 4, 1));       
		$this->addFrontSystem(new HypergravitonBlaster(7, 30, 15, 300, 60));

		$orbitalF = new KirishiacOrbital(6, 18, 'R', 'F', -7, $orbitalHitChart);
		$beamF = new AntigravityBeam(6, 6, 3, 90, 270, 'F');
		$orbitalF->addMirror($beamF);
		$this->addAftSystem($orbitalF);
		$this->addAftSystem($beamF);

		$orbitalE = new KirishiacOrbital(6, 18, 'L', 'E', -7, $orbitalHitChart);
		$beamE = new AntigravityBeam(6, 6, 3, 90, 270, 'E');
		$orbitalB->addMirror($beamE);
		$this->addAftSystem($orbitalE);
		$this->addAftSystem($beamE);
        
        $this->addAftSystem(new GraviticThruster(7, 15, 0, 4, 2));	
        $this->addAftSystem(new GraviticThruster(7, 15, 0, 4, 2));
        $this->addAftSystem(new GraviticThruster(7, 15, 0, 4, 2));
       
		$beamG = new AntigravityBeam(6, 6, 3, 120, 300, 'G');
		$orbitalG = new KirishiacOrbital(6, 18, 'R', 'G', -7, $orbitalHitChart);
		$orbitalG->addMirror($beamG);
		$this->addLeftSystem($beamG);
		$this->addLeftSystem($orbitalG);

		$beamH = new AntigravityBeam(6, 6, 3, 240, 60, 'H');
		$orbitalH = new KirishiacOrbital(6, 18, 'L', 'H', -7, $orbitalHitChart);
		$orbitalH->addMirror($beamH);
		$this->addLeftSystem($beamH);
		$this->addLeftSystem($orbitalH);

        $this->addLeftSystem(new GraviticThruster(7, 25, 0, 7, 3));

		$beamC = new AntigravityBeam(6, 6, 3, 60, 240, 'C');
		$orbitalC = new KirishiacOrbital(6, 18, 'L', 'C', -7, $orbitalHitChart);
		$orbitalC->addMirror($beamC);
		$this->addRightSystem($beamC);
		$this->addRightSystem($orbitalC);

		$beamD = new AntigravityBeam(6, 6, 3, 300, 120, 'D');
		$orbitalD = new KirishiacOrbital(6, 18, 'R', 'D', -7, $orbitalHitChart);
		$orbitalD->addMirror($beamD);
		$this->addRightSystem($beamD);
		$this->addRightSystem($orbitalD);

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
                    4 => "Thruster",
                    8 => "Hypergraviton Blaster",
                    10 => "Kirishiac Orbital",
                    18 => "Structure",
                    20 => "Primary",
            ),
            2=> array(
                    8 => "Thruster",
                    10 => "Kirishiac Orbital",
                    18 => "Structure",
                    20 => "Primary",
            ),
            3=> array(
                    6 => "Thruster",
                    8 => "Kirishiac Orbital",
                    18 => "Structure",
                    20 => "Primary",
            ),
            4=> array(
                    6 => "Thruster",
                    8 => "Kirishiac Orbital",
                    18 => "Structure",
                    20 => "Primary",
            ),
            
        );
    }
}
