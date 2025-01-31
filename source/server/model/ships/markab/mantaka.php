<?php
class Mantaka extends BaseShip{
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        $this->pointCost = 475;
        $this->faction = "Markab Theocracy";
        $this->phpclass = "Mantaka";
        $this->isd = 2005;        
        $this->imagePath = "img/ships/MarkabAssaultShip.png"; //needs to be changed
        $this->shipClass = "Mantaka Assault Ship";
        $this->shipSizeClass = 3;
        $this->canvasSize = 200;
        $this->forwardDefense = 16;
        $this->sideDefense = 17;
        $this->turncost = 1;
        $this->turndelaycost = 0.66;
        $this->accelcost = 4;
        $this->rollcost = 3;
        $this->pivotcost = 6;
        $this->variantOf = 'Mafka Transport Cruiser';
        $this->occurence = "common";
        $this->fighters = array("assault shuttles"=>9);


		$this->enhancementOptionsEnabled[] = 'MARK_FERV'; //To activate Religious Fervor attributes.   
        
        $this->addPrimarySystem(new Reactor(4, 18, 0, 0));
        $this->addPrimarySystem(new CnC(4, 12, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 16, 6, 7));
        $this->addPrimarySystem(new Engine(4, 14, 0, 8, 3));
        $this->addPrimarySystem(new Hangar(4, 8));
        
        $this->addFrontSystem(new Thruster(3, 10, 0, 3, 1));
        $this->addFrontSystem(new Thruster(3, 10, 0, 3, 1));
        $this->addFrontSystem(new PlasmaWaveTorpedo(3, 0, 0, 240, 360));
        $this->addFrontSystem(new HeavyPlasma(3, 8, 5, 300, 60));
        $this->addFrontSystem(new PlasmaWaveTorpedo(3, 0, 0, 0, 120));
        
        $this->addAftSystem(new Thruster(3, 13, 0, 4, 2));
        $this->addAftSystem(new Thruster(3, 13, 0, 4, 2));
        $this->addAftSystem(new ScatterGun(2, 8, 3, 120, 360));
        $this->addAftSystem(new ScatterGun(2, 8, 3, 0, 240));
        
	$this->addLeftSystem(new ScatterGun(2, 0, 0, 180, 60));
        $this->addLeftSystem(new Thruster(3, 13, 0, 3, 3));
        
        $this->addRightSystem(new ScatterGun(2, 0, 0, 300, 180));
        $this->addRightSystem(new Thruster(3, 13, 0, 3, 4));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 4, 48));
        $this->addAftSystem(new Structure( 4, 40));
        $this->addLeftSystem(new Structure( 4, 56));
        $this->addRightSystem(new Structure( 4, 56));
        $this->addPrimarySystem(new Structure( 4, 40));
    
        $this->hitChart = array(
            0=> array(
                    9 => "Structure",
                    12 => "Scanner",
                    15 => "Engine",
                    17 => "Hangar",
                    19 => "Reactor",
                    20 => "C&C",
            ),
            1=> array(
                    5 => "Thruster",
            		8 => "Plasman Wave",
            		10 => "Heavy Plasma Cannon",
                    18 => "Structure",
                    20 => "Primary",
            ),
            2=> array(
                    6 => "Thruster",
            		8 => "Scattergun",
                    18 => "Structure",
                    20 => "Primary",
            ),
            3=> array(
                    5 => "Thruster",
                    7 => "Scattergun",
                    18 => "Structure",
                    20 => "Primary",
            ),
            4=> array(
                    5 => "Thruster",
                    7 => "Scattergun",
                    18 => "Structure",
                    20 => "Primary",
            ),
        );
    }
}
