<?php
class OcaraD extends MineClass{
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 70*6;
        $this->faction = "Custom Ships";
        $this->phpclass = "OcaraD";
        $this->shipClass = "Ocara-D Mine";
        $this->imagePath = "img/ships/mine.png";
        
		$this->isd = 1950;
       
        $this->forwardDefense = 9;
        $this->sideDefense = 9;
        $this->freethrust = 0;
        $this->offensivebonus = 6; 
        $this->turncost = 0.33; //actually not all that relevant...
        
		$this->hangarRequired = ""; //they don't require any hangars... although of course cannot be used in pickup battle either!
		$this->unitSize = 6; //number of craft in squadron
		
    	$this->iniativebonus = -20; 
//    	$this->superheavy = true;
        $this->maxFlightSize = 6;//this is a superheavy fighter originally intended as single unit, limit flight size to 3
		
        $this->populate();
		
    }
    
    public function populate(){
        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;
        for ($i = 0; $i < $toAdd; $i++){
            $armour = array(2, 2, 2, 2);
            $fighter = new Fighter("Ocara-D", $armour, 8, $this->id);
            $fighter->displayName = "Ocara-D";
            $fighter->imagePath = "img/ships/mine.png";
            $fighter->iconPath = "img/ships/mine_large.png"; 

			$MC1 = new MatterCannonFtr(0, 360, 1);
			$fighter->addFrontSystem($MC1);
			$MC2 = new MatterCannonFtr(0, 360, 1);
			$fighter->addFrontSystem($MC2);
			
        	$this->addSystem($fighter);
       }
    }
    
    
}
?>
