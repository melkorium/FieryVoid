<?php
class Pshulshi extends BaseShip{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 850;
		$this->faction = "Pak'ma'ra Confederacy";
		$this->phpclass = "Pshulshi";
		$this->imagePath = "img/ships/PakmaraPshulshi.png";
		$this->shipClass = "Pshul'shi Dreadnought";
		$this->shipSizeClass = 3;
		$this->limited = 33;
	    
        $this->isd = 2244;

		$this->forwardDefense = 15;
		$this->sideDefense = 17;

		$this->turncost = 1.33;
		$this->turndelaycost = 1.33;
		$this->accelcost = 3;
		$this->rollcost = 2;
		$this->pivotcost = 5;

		$this->iniativebonus = -1*5;

   				

		
		/* let's replace this with appropriate two C&Cs!
		$this->addPrimarySystem(new PakmaraCnC(6, 16, 0, 0));
		*/
		$cnc = new PakmaraCnC(5, 8, 0, 0);
		$cnc->startArc = 0;
		$cnc->endArc = 360;
        $this->addPrimarySystem($cnc);
		$cnc = new SecondaryCnC(5, 8, 0, 0);
		$cnc->startArc = 0;
		$cnc->endArc = 360;
        $this->addPrimarySystem($cnc);
		
		$this->addPrimarySystem(new Reactor(5, 30, 0, 0));
		$this->addPrimarySystem(new Scanner(5, 13, 7, 8));
		$this->addPrimarySystem(new Engine(5, 20, 0, 12, 3));
		$this->addPrimarySystem(new JumpEngine(5, 20, 4, 48));
		$this->addPrimarySystem(new Hangar(3, 3));

		$this->addFrontSystem(new Thruster(3, 6, 0, 2, 1));
		$this->addFrontSystem(new Thruster(3, 8, 0, 3, 1));
		$this->addFrontSystem(new Thruster(3, 8, 0, 3, 1));	
		$this->addFrontSystem(new Thruster(3, 6, 0, 2, 1));
		$this->addFrontSystem(new PakmaraPlasmaWeb(2, 4, 2, 240, 60));
		$this->addFrontSystem(new PakmaraPlasmaWeb(2, 4, 2, 300, 120));
		$this->addFrontSystem(new Fuser(3, 18, 12, 300, 60));
		$this->addFrontSystem(new PlasmaBattery(2, 6, 0, 6));
		$this->addFrontSystem(new PlasmaBattery(2, 6, 0, 6));	


		$this->addAftSystem(new Thruster(3, 15, 0, 6, 2));
		$this->addAftSystem(new Thruster(3, 15, 0, 6, 2));
		$this->addAftSystem(new HeavyPlasma(3, 8, 5, 120, 240));
		$this->addAftSystem(new PakmaraPlasmaWeb(2, 4, 2, 120, 300));
		$this->addAftSystem(new PakmaraPlasmaWeb(2, 4, 2, 60, 240));


		$this->addLeftSystem(new Thruster(4, 15, 0, 6, 3));
		$this->addLeftSystem(new MegaPlasma(5, 10, 8, 300, 360));
		$this->addLeftSystem(new MediumPlasma(2, 5, 3, 180, 300));
		$this->addLeftSystem(new MediumPlasma(2, 5, 3, 180, 360));		
		$this->addLeftSystem(new MediumPlasma(2, 5, 3, 240, 360));		
		$this->addLeftSystem(new CargoBay(3, 12));


		$this->addRightSystem(new Thruster(4, 15, 0, 6, 4));
		$this->addRightSystem(new MegaPlasma(5, 10, 8, 0, 60));
		$this->addRightSystem(new MediumPlasma(2, 5, 3, 60, 180));
		$this->addRightSystem(new MediumPlasma(2, 5, 3, 0, 180));
		$this->addRightSystem(new MediumPlasma(2, 5, 3, 0, 120));				
		$this->addRightSystem(new CargoBay(3, 12));

        
        $this->addFrontSystem(new Structure( 5, 42));
        $this->addAftSystem(new Structure( 3, 42));
        $this->addLeftSystem(new Structure( 4, 56));
        $this->addRightSystem(new Structure( 4, 56));
        $this->addPrimarySystem(new Structure( 6, 60));
		
		
		$this->hitChart = array(
                0=> array(
                        8 => "Structure",
                        10 => "Jump Engine",
                        12 => "Scanner",
                        15 => "Engine",
                        16 => "Hangar",
                        19 => "Reactor",
                        20 => "TAG:C&C",
                ),
                1=> array(
                        4 => "Thruster",
                        7 => "Fuser",
                        9 => "Plasma Web",
                        11 => "Plasma Battery",                     
                        18 => "Structure",
                        20 => "Primary",
                ),
                2=> array(
                        5 => "Thruster",
                        7 => "Heavy Plasma Cannon",
                        9 => "Plasma Web",
                        18 => "Structure",
                        20 => "Primary",
                ),
                3=> array(
                        3 => "Thruster",
                        4 => "Mega Plasma Cannon",
                        7 => "Medium Plasma Cannon",
						9 => "Cargo Bay",
                        18 => "Structure",
                        20 => "Primary",
                ),
                4=> array(
                        3 => "Thruster",
                        4 => "Mega Plasma Cannon",
                        7 => "Medium Plasma Cannon",
						9 => "Cargo Bay",
                        18 => "Structure",
                        20 => "Primary",
                ),
        );

    }
}
?>
