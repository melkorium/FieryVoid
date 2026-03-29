<?php
class dewMineBalista extends Mine{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 40;
        $this->faction = "Orieni Imperium";	
        $this->phpclass = "dewMineBalista";
        $this->imagePath = "img/ships/orieniMine.png";
        $this->shipClass = "Balista DEW Mine";
		$this->occurence = "common";
		$this->variantOf = "Archer DEW Mine";
        $this->isd = 2200;
        
        $this->forwardDefense = 12;
        $this->sideDefense = 12;
        $this->signature = 0;
        $this->detectedSignature = -3;           
        
        $this->turncost = 0;
        $this->turndelaycost = 0;
        $this->accelcost = 0;
        $this->rollcost = 0;
        $this->pivotcost = 0;	
        $this->iniativebonus = -200; 
        $this->mineType = 'DEW';         
       		    	    	    	    
        //Block all enhancements for Mine units when bought
		Enhancements::nonstandardEnhancementSet($this, 'Mines');	 

        $this->addPrimarySystem(new OSATCnC(0, 1, 0, 0));
        $this->addPrimarySystem(new MagGravReactorTechnical(0, 1, 0, 2));
        $this->addPrimarySystem(new mineStealth(0, 1, 1));
        $this->addPrimarySystem(new MineControllerDEW(0, 1, 0, 12, 4)); //$armour, $maxhealth, $powerReq, $startArc, $endArc, $range/output, $accuracy 
        $this->addPrimarySystem(new OrieniGatlingRG(0, 1, 0, 0, 360));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addPrimarySystem(new Structure(4, 15));
        
        	//d20 hit chart
        $this->hitChart = array(
            0=> array(
                    20 => "Structure",
            )
        );
        
    }
}
?>
