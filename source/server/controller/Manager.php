<?php

require_once dirname(__DIR__) . '/varconfig.php'; 

set_error_handler(
    function ($errno, $errstr, $file, $line)
    {
        throw new ErrorException($errstr, $errno, 1, $file, $line);
    }
);

class Manager{

    /**
     * @var DBManager null
     */
    private static $dbManager = null;

    private static function initDBManager() {
        global $database_host;
    	global $database_name;
    	global $database_user;
    	global $database_password;
        if (self::$dbManager == null)
            self::$dbManager = new DBManager($database_host ?? "mariadb", 3306, $database_name, $database_user, $database_password);
    }

    public static function setDBManager(DBManager $dbManager) {
        self::$dbManager = $dbManager;
    }

    private static function deleteOldGames()
    {
        try {
            self::initDBManager();
            self::$dbManager->startTransaction();
            $ids = self::$dbManager->getGamesToBeDeleted();
            self::$dbManager->deleteGames($ids);
                
            self::$dbManager->endTransaction(false);
        }catch(exception $e) {
            self::$dbManager->endTransaction(true);
            throw $e;
        }
    }
    
    public static function leaveLobbySlot($user, $gameid, $slotid = null){
        try {
            self::initDBManager();
            self::$dbManager->leaveSlot($user, $gameid, $slotid);
            self::$dbManager->deleteEmptyGames();
        }
        catch(exception $e) {
            throw $e;
        }
    
    }
    
    public static function getGameLobbyData($userid, $targetGameId = false){
        try {
            self::initDBManager();
            
            if ($targetGameId && is_numeric($targetGameId) && $targetGameId > 0) {
                return self::getTacGamedata($targetGameId, $userid, 0, 0, -1);
            }
        } catch(Exception $e) {
            Debug::error($e);
        }
    
        return null; // Always return *something*
    }
    
    public static function getTacGames($userid){
        
        if (!is_numeric($userid))
			return null;
        
        try {
            self::initDBManager();
        
            return array_merge(
                self::$dbManager->getPlayerGames($userid),
                self::$dbManager->getLobbyGames()
            );
      
        }
        catch(exception $e) {
            throw $e;
        }
        
        return $games;
    }
	
	
	public static function getPlayerName($userid){
		if (!is_numeric($userid)) return 'NONNUMERIC';
		$playerName = '';
        try {
            self::initDBManager();        
            $playerName = self::$dbManager->getPlayerName($userid);
        }
        catch(exception $e) {
            $playerName = 'EXCEPTION';
        }        
        return $playerName;		
	}


    public static function getFirePhaseGames($userid){
        try {
            self::initDBManager();
            
            $games = self::$dbManager->getFirePhaseGames($userid);

            if ($games == null){
                return null;
            }            
        }
        catch(exception $e) {
            throw $e;
        }
        

        return $games;


    }
    
    public static function shouldBeInGame($userid){
		if (!is_numeric($userid))
			return null;
			
        try {
            self::initDBManager();
            return self::$dbManager->shouldBeInGameLobby($userid);
        }
        catch(exception $e) {
            throw $e;
        }
    }
    
    public static function createGame($userid, $data){
        $data = json_decode($data, true);

        //var_export($data);
        
        $gamename = $data["gamename"];
        $background = $data["background"];
        $gamespace = $data["gamespace"];
        $description = $data["description"];
        $slots = array();
        $pointsA = $data["slots"][0]["points"];
        $poinstB = $data["slots"][1]["points"];
        $rules = new GameRules(isset($data["rules"]) ? $data["rules"] : []) ;

        foreach ($data["slots"] as $slot){
            $slots[] = new PlayerSlotFromJSON($slot);
        }
        
        try {
            self::initDBManager();
            self::$dbManager->startTransaction();
            $gameid = self::$dbManager->createGame($gamename, $background, $slots, $userid, $gamespace, $description, json_encode($rules));
            //SystemData::initSystemData(0, $gameid);
            self::takeSlot($userid, $gameid, 1);
            self::$dbManager->endTransaction(false);
            return $gameid;
        }
        catch(exception $e) {
            if (self::$dbManager) {
                self::$dbManager->endTransaction(true);
            }
            throw $e;
        }
    
    }
    
    public static function takeSlot($userid, $gameid, $slot){
        
        try {
            self::initDBManager();
            //self::$dbManager->startTransaction();
            return self::$dbManager->takeSlot($userid, $gameid, $slot);
            //self::$dbManager->endTransaction();
            
        }
        catch(exception $e) {
            throw $e;
        }
        
    }
    
    public static function getMapBackgrounds(){
        $handle = opendir("img/maps/");
        $list = array();
        while (false !== ($entry = readdir($handle))) {
        
            if (preg_match("/.*\.(bmp|jpeg|gif|png|jpg)$/i", $entry))
                $list[] = $entry;
        }
        
	sort($list);//alphabetical sort
        return $list;
    }
    
    public static function getAllFactions(){
    	return ShipLoader::getAllFactions();
    }
    
    public static function canCreateGame($userid){
        return true;
    }
    public static function registerPlayer($username, $password) {
        try {
            self::initDBManager();
            $ret =  self::$dbManager->registerPlayer($username, $password);
                      
            return $ret;
        }
        catch(exception $e) {
            Debug::error($e);
            return null;
        }
    }
    
    public static function changePassword($username, $passwordold, $passwordnew) {
        try {
            self::initDBManager();
            $ret =  self::$dbManager->changePassword($username, $passwordold, $passwordnew);
                      
            return $ret;
        }
        catch(exception $e) {
            Debug::error($e);
            return null;
        }
        
    }
    
    
    public static function authenticatePlayer($username, $password) {
        try {
            self::initDBManager();
            $ret =  self::$dbManager->authenticatePlayer($username, $password);
                      
            return $ret;
        }
        catch(exception $e) {
            Debug::error($e);
            return null;
        }
        
    }
    
    public static function getTacGamedata($gameid, $userid, $turn, $phase, $activeship){
    
	    if (!is_numeric($gameid) || (!is_numeric($userid) &&  $userid !== null) || ($turn !== null && !is_numeric($turn)) || !is_numeric($phase) )
            return null;
        
        $gamedata = null;

        try {
            self::initDBManager();
            self::$dbManager->startTransaction();

            if ($turn === null)
                self::deleteOldGames ();

            //Todo: this should propably happen after submit game data...
            self::advanceGameState($userid, $gameid);

            if (self::$dbManager->isNewGamedata($gameid, $turn, $phase, $activeship)){
                //Debug::log("GAME: $gameid Player: $userid requesting gamedata, new found.");
                $gamedata = self::$dbManager->getTacGamedata($userid, $gameid);
                if ($gamedata == null)
                    return null;
                //print(var_dump($gamedata));
                $gamedata->prepareForPlayer();
                //Line below skips deployment Phase on Turn 1 for late-deploying slots - DK                
                if($gamedata->turn == 1 && $gamedata->phase == -1) Manager::updateLateDeployments($gamedata);  

            }else{
                return null;
            }

            self::$dbManager->endTransaction(false);
            return $gamedata;
        }catch(exception $e) {
            self::$dbManager->endTransaction(true);
            $logid = Debug::error($e);
            return '{"error": "' .$e->getMessage() . '", "code":"'.$e->getCode().'", "logid":"'.$logid.'"}';
        }
    }
    
    public static function updateAmmoInfo($shipid, $systemid, $gameid, $firingmode, $ammoAmount, $turn){		
		//as there is no separate ammo tracks - assume always first mode is used for ammo tracking purposes
        //self::$dbManager->submitAmmo($shipid, $systemid, $gameid, $firingmode, $ammoAmount, $turn);
		self::$dbManager->submitAmmo($shipid, $systemid, $gameid, 1, $ammoAmount, $turn);
    }
    
    public static function getTacGamedataJSON($gameid, $userid, $turn, $phase, $activeship, $force = false){
        
        try{
            $gdS = self::getTacGamedata($gameid, $userid, $turn, $phase, $activeship);

            if (!$gdS)
                return "{}";

            //getTacGameData trying to return error string
            if (gettype($gdS) == "string") {
                return $gdS;
            }

            if (!$force && $gdS->waiting && !$gdS->changed && $gdS->status != "LOBBY")
                return "{}";

            $json = json_encode($gdS->stripForJson(), JSON_NUMERIC_CHECK);


            //Debug("GAME: $gameid Player: $userid requesting gamedata. RETURNING NEW JSON");
            return $json;

        }
        catch(Exception $e) {
            $logid = Debug::error($e);
            return '{"error": "' .$e->getMessage() . '", "code":"'.$e->getCode().'", "logid":"'.$logid.'"}';
        }
    
    }

    public static function getReplayGameData($userid, $gameid, $turn) {
        try{
            self::initDBManager();
            $game = self::$dbManager->getTacGame($gameid, 0);
            if (!$game) {
                return null;
            }

            $actualTurn = $game->turn;
            $gamedata = self::$dbManager->getTacGamedata($userid, $gameid, $turn);

            if ($gamedata == null)
                return null;

            $gamedata->prepareForPlayer($actualTurn > $turn);
            $gamedata->turn = $turn;

            $json = json_encode($gamedata->stripForJson(), JSON_NUMERIC_CHECK);
            return $json;

        }
        catch(Exception $e) {
            $logid = Debug::error($e);
            return '{"error": "' .$e->getMessage() . '", "code":"'.$e->getCode().'", "logid":"'.$logid.'"}';
        }
    }
            
    public static function submitTacGamedata($gameid, $userid, $turn, $phase, $activeship, $ships, $status, $slotid = 0){
        try {
        
            //    file_put_contents('/tmp/fierylog', "Gameid: $gameid submitTacGamedata ships:". var_export($ships, true) ."\n\n", FILE_APPEND);
            self::initDBManager();  
            $starttime = time();
            
            $ships = self::getShipsFromJSON($ships);
            
            if (sizeof($ships)==0)
				throw new Exception("Gamedata missing");
            //print(var_dump($ships));
            //$gamedata = new TacGamedata($gameid, $turn, $phase, $activeship, $userid, "", "", 0, "", 0);
            //$gamedata->ships = $ships;
            
            if (!self::$dbManager->getPlayerSubmitLock($gameid, $userid))
                throw new Exception("Failed to get player lock");
            
            //Debug("GAME: $gameid Player: $userid starting submit of phase $phase");
            
            self::$dbManager->startTransaction();
            

            /** @var TacGamedata $gdS */
            $gdS = self::$dbManager->getTacGamedata($userid, $gameid);

            SystemData::initSystemData($gdS->turn, $gdS->id);

            if ($status == "SURRENDERED" && $gdS->status !== "SURRENDERED"){
                self::$dbManager->updateGameStatus($gameid, $status);
            } else if ($gdS->status === "SURRENDERED") {
                return "{}";
            }
            
            if ($gameid != $gdS->id || $turn != $gdS->turn || $phase != $gdS->phase)
                throw new Exception("Unexpected orders");

            $phase = $gdS->getPhase();

            if ($gdS->hasAlreadySubmitted($userid))
                throw new Exception("Turn already submitted or wrong user");
                
            if ($gdS->status == "FINISHED")
                throw new Exception("Game is finished");

            if ($activeship != $gdS->activeship && array_diff($gdS->activeship, $activeship)){
                throw new Exception("Active ship does not match");
            }
            //print(var_dump($ships));

            if ($phase instanceof BuyingGamePhase){
                $phase->process($gdS, self::$dbManager, $ships, $slotid); // slotid passed here
            } else if ($phase instanceof DeploymentGamePhase){
                $phase->process($gdS, self::$dbManager, $ships);
            } else if ($phase instanceof InitialOrdersGamePhase){
                $phase->process($gdS, self::$dbManager, $ships);
            }else if ($phase instanceof MovementGamePhase){
                $phase->process($gdS, self::$dbManager, $ships, $activeship);
            }else if ($phase instanceof FireGamePhase){
                $phase->process($gdS, self::$dbManager, $ships);
            }
                        
            self::$dbManager->endTransaction(false);
            
            self::$dbManager->releasePlayerSubmitLock($gameid, $userid);
            
            //Debug("GAME: $gameid Player: $userid SUBMIT OK");
            
            $endtime = time();
            //Debug::log("SUBMITTING GAMEDATA - GAME: $gameid Time: " . ($endtime - $starttime) . " seconds.");
            return '{}';
            
        }catch(exception $e) {
            self::$dbManager->endTransaction(true);
            self::$dbManager->releasePlayerSubmitLock($gameid, $userid);
            $logid = Debug::error($e);
            return '{"error": "' .$e->getMessage() . '", "code":"'.$e->getCode().'", "logid":"'.$logid.'"}';
        }
       
        
    }

    /* //Old method that didn't skip if no deployed ships - DK 2/6/25
    public static function advanceGameState($playerid, $gameid){
        try{
            if (!self::$dbManager->checkIfPhaseReady($gameid))
                return;
		
            if (!self::$dbManager->getGameSubmitLock($gameid))
            {
                //Debug::log("Advance gamestate, Did not get lock. playerid: $playerid");
                return;
            }



            
            $starttime = time();
            
            //Debug("GAME: $gameid Starting to advance gamedata. playerid: $playerid");
            
            self::$dbManager->startTransaction();
		
            $gamedata = self::$dbManager->getTacGamedata($playerid, $gameid);

            SystemData::initSystemData($gamedata->turn, $gamedata->id);

            $phase = $gamedata->getPhase();

            if ($phase instanceof BuyingGamePhase){
                $phase->advance($gamedata, self::$dbManager);
                self::changeTurn($gamedata);
            } else if ($phase instanceof DeploymentGamePhase){
                $phase->advance($gamedata, self::$dbManager);
            } else if ($phase instanceof InitialOrdersGamePhase){
                $phase->advance($gamedata, self::$dbManager);
            }else if ($phase instanceof MovementGamePhase){
                $phase->advance($gamedata, self::$dbManager);
            }else if ($phase instanceof FireGamePhase){
                $phase->advance($gamedata, self::$dbManager);
                self::changeTurn($gamedata);
            }
            
            if (TacGamedata::$currentPhase > 0){
                foreach ($gamedata->ships as $ship){
                    foreach ($ship->systems as $system){
                        $system->onAdvancingGamedata($ship, $gamedata);
                    }
                }
                
                self::$dbManager->updateSystemData(SystemData::getAndPurgeAllSystemData());
            }
            self::$dbManager->endTransaction(false);
            self::$dbManager->releaseGameSubmitLock($gameid);
        }
        catch(Exception $e)
        {
            self::$dbManager->endTransaction(true);
            self::$dbManager->releaseGameSubmitLock($gameid);
            throw $e;
        }
    }
*/

    public static function advanceGameState($playerid, $gameid){
        try{
           if (!self::$dbManager->checkIfPhaseReady($gameid))
                return;
            
            if (!self::$dbManager->getGameSubmitLock($gameid))
            {
                //Debug::log("Advance gamestate, Did not get lock. playerid: $playerid");
                return;
            }
           
            $starttime = time();
            
            //Debug("GAME: $gameid Starting to advance gamedata. playerid: $playerid");
            
            self::$dbManager->startTransaction();
		
            $gamedata = self::$dbManager->getTacGamedata($playerid, $gameid);

            SystemData::initSystemData($gamedata->turn, $gamedata->id);

            $phase = $gamedata->getPhase();
            if (!$gamedata->areDeployedShips()) {               
                while (!$gamedata->areDeployedShips() && $gamedata->status != "FINISHED") {
                    $phase = $gamedata->getPhase();

                    if ($phase instanceof BuyingGamePhase){
                        $phase->advance($gamedata, self::$dbManager);
                        self::changeTurn($gamedata);                                            
                    } else if ($phase instanceof DeploymentGamePhase){
                        $phase->advance($gamedata, self::$dbManager);
                    } else if ($phase instanceof InitialOrdersGamePhase){
                        $phase->advance($gamedata, self::$dbManager);
                    } else if ($phase instanceof MovementGamePhase){
                        $phase->advance($gamedata, self::$dbManager);
                    } else if ($phase instanceof FireGamePhase){
                        $phase->advance($gamedata, self::$dbManager);
                        self::changeTurn($gamedata);
                    }

                    $phase = $gamedata->getPhase();

                    if ($gamedata->turn > 200) break; //Safety break just in case.
                }
            } else {
                if ($phase instanceof BuyingGamePhase){
                    $phase->advance($gamedata, self::$dbManager);
                    self::changeTurn($gamedata);
                } else if ($phase instanceof DeploymentGamePhase){
                    $phase->advance($gamedata, self::$dbManager);
                } else if ($phase instanceof InitialOrdersGamePhase){
                    $phase->advance($gamedata, self::$dbManager);
                }else if ($phase instanceof MovementGamePhase){
                    $phase->advance($gamedata, self::$dbManager);
                }else if ($phase instanceof FireGamePhase){
                    $phase->advance($gamedata, self::$dbManager);
                    self::changeTurn($gamedata);
                }
            }  
            //if (TacGamedata::$currentPhase > 0){
            //Keep original logic here for Turn 1, but then adjust to accommodate Deployment phases AFTER Turn 1
            if ($gamedata->turn == 1 && TacGamedata::$currentPhase > 0 || $gamedata->turn > 1 && TacGamedata::$currentPhase >= -1){            
                foreach ($gamedata->ships as $ship){
                    foreach ($ship->systems as $system){
                        $system->onAdvancingGamedata($ship, $gamedata);
                    }
                }
                
                self::$dbManager->updateSystemData(SystemData::getAndPurgeAllSystemData());
            }
            self::$dbManager->endTransaction(false);
            self::$dbManager->releaseGameSubmitLock($gameid);
        }
        catch(Exception $e)
        {
            self::$dbManager->endTransaction(true);
            self::$dbManager->releaseGameSubmitLock($gameid);
            throw $e;
        }
    }
  

    //New function called in Manager::getTacGamedata() to search for slots that skip Deployment on Turn 1 - DK July 2025 
    public static function updateLateDeployments($gamedata){
        foreach($gamedata->slots as $slot){    
            if($slot->depavailable > 1){
                $depTurn = $gamedata->getMinTurnDeployedSlot($slot->slot, $slot->depavailable);
                if($depTurn > 1){ //Bases and Terrain will need to deploy on Turn 1 still
                    //Set lastphase, and lastTurn for slot to intial phase on next turn.                
                    self::$dbManager->updatePlayerStatusSlot($gamedata->id, $slot->playerid, $slot->slot, -1, 1);
                }        
            }    
        }           
    }    


    private static function changeTurn($gamedata){
    
        $gamedata->setTurn( $gamedata->turn+1 );
        
        /* //Old method which only create Deployment Phases on Turn 1.
        if ($gamedata->turn === 1)
        {
            $gamedata->setPhase(-1); 
        }else{
            $gamedata->setPhase(1); 
        }
        */
        //Now we always try and make a Deployment Phase, but slots will be set to skip it in FireGamePhase if they are not are scheduled to deploy.        
        $gamedata->setPhase(-1); 

        $gamedata->setActiveship(-1);

        if (($gamedata->turn > 1 && $gamedata->isFinished()) || ($gamedata->status === "SURRENDERED")){
            $gamedata->status = "FINISHED";
        }
        else{
            $gamedata->status = "ACTIVE";
        }
            
        self::generateIniative($gamedata);
        self::$dbManager->updateGamedata($gamedata);

       // if ($gamedata->turn > 1){
         //   self::checkRegen($gamedata);
        //}   
               
        $servergamedata = self::$dbManager->getTacGamedata($gamedata->forPlayer, $gamedata->id);
        
        foreach ($servergamedata->ships as $key=>$ship){
            $movement = Movement::setPreturnMovementStatusForShip($ship, $servergamedata->turn);
            self::$dbManager->submitMovement($servergamedata->id, $ship->id, $servergamedata->turn, $movement, true);
        }
    }

    private static function generateIniative(TacGamedata $gamedata){
        if ($gamedata->rules->hasRule("generateIniative")) {
            $gamedata->rules->callRule("generateIniative", $gamedata);
        } else {
            foreach ($gamedata->ships as $key=>$ship){
                $mod =  $ship->getCommonIniModifiers( $gamedata );
                $iniBonus =  $ship->getInitiativebonus($gamedata);
                $ship->iniative = Dice::d(100) + $iniBonus + $mod;    
            }
	    /*Initiative ties are displayed wrongly in the interface due to changes intended for simultaneous movement.
	    	to prevent this from happening, forcefully break ties here - sort ships appropriately and make their Ini different by adding appropriate value
	    	side effect would be that displayed Ini rolled would be wrong (possibly even out of bounds), but then absolute Ini has little meaning in game - and relative one will be correct 
	    */
	    $gamedata->doSortShips(); //make sure they're in proper order
            $addIni = 0;
	    $prevIni = null;
	    foreach ($gamedata->ships as $key=>$ship){
		    if(($prevIni !== null) && ($ship->iniative > $prevIni)){ //ship initiative is greater than previous even without modifier - modifier no longer needed, reset
			$addIni = 0;    
		    }
		    $ship->iniative += $addIni;
		    if ($ship->iniative == $prevIni){ //actual tie! increase Ini of tied unit (and all further ones) by 1
			$ship->iniative++;	
			$addIni++;
		    }
		    $prevIni = $ship->iniative;
            }
		
        }
	    
        self::$dbManager->submitIniative($gamedata->id, $gamedata->turn, $gamedata->ships);
    }
    
    private static function getShipsFromJSON($json) {
        $ships = array();
        $array = json_decode($json, true);
        if (!is_array($array)) return $ships;
    
        foreach ($array as $value) {
            $movements = array();
            if (isset($value["movement"]) && is_array($value["movement"])) {
                foreach($value["movement"] as $i => $move) {
                    $movement = new MovementOrder(
                        $move["id"] ?? -1,
                        $move["type"] ?? null,
                        new OffsetCoordinate($move["position"] ?? [0, 0]),
                        $move["xOffset"] ?? 0,
                        $move["yOffset"] ?? 0,
                        $move["speed"] ?? 0,
                        $move["heading"] ?? 0,
                        $move["facing"] ?? 0,
                        $move["preturn"] ?? false,
                        $move["turn"] ?? 0,
                        $move["value"] ?? 0,
                        $move["at_initiative"] ?? false
                    );
                    $movement->requiredThrust = $move["requiredThrust"] ?? 0;
                    $movement->assignedThrust = $move["assignedThrust"] ?? 0;
    
                    $movements[$i] = $movement;
                }
            }
    
            $EW = array();
            if (isset($value["EW"]) && is_array($value["EW"])) {
                foreach($value["EW"] as $i => $EWdata) {
                    $EWentry = new EWentry(
                        -1,
                        $EWdata["shipid"] ?? -1,
                        $EWdata["turn"] ?? 0,
                        $EWdata["type"] ?? "",
                        $EWdata["amount"] ?? 0,
                        $EWdata["targetid"] ?? null
                    );
                    $EW[$i] = $EWentry;
                }
            }
    
            $className = $value["phpclass"] ?? null;
            if (!$className) continue; // skip if class not defined
    
            /** @var BaseShip $ship */
            $ship = new $className(
                $value["id"] ?? -1,
                $value["userid"] ?? -1,
                $value["name"] ?? "Unnamed",
                $value["slot"] ?? 0
            );
    
            $ship->pointCostEnh = ($value["pointCostEnh"] ?? 0) + ($value["pointCostEnh2"] ?? 0);
            $ship->setMovements($movements);
            $ship->EW = $EW;
    
            if ($ship instanceof FighterFlight) {
                $ship->flightSize = $value["flightSize"] ?? 1;
                $ship->populate();
            }
    
            $ship->enhancementOptions = $value["enhancementOptions"] ?? [];
    
            $systems = $value["systems"] ?? [];
            foreach ($systems as $i => $system) {
                $sys = $ship->getSystemById($i);
    
                if (isset($system["power"]) && is_array($system["power"])) {
                    foreach ($system["power"] as $power) {
                        $powerEntry = new PowerManagementEntry(
                            $power["id"] ?? -1,
                            $power["shipid"] ?? -1,
                            $power["systemid"] ?? -1,
                            $power["type"] ?? "",
                            $power["turn"] ?? 0,
                            $power["amount"] ?? 0
                        );
                        if ($sys) {
                            $sys->setPower($powerEntry);
                        }
                    }
                }
    
                if (isset($system["fireOrders"]) && is_array($system["fireOrders"])) {
                    $fires = [];
                    foreach($system["fireOrders"] as $fo) {
                        $fireOrder = new FireOrder(
                            -1,
                            $fo["type"] ?? "",
                            $fo["shooterid"] ?? -1,
                            $fo["targetid"] ?? -1,
                            $fo["weaponid"] ?? -1,
                            $fo["calledid"] ?? -1,
                            $fo["turn"] ?? 0,
                            $fo["firingMode"] ?? 1,
                            0, 0, $fo["shots"] ?? 0, 0, 0,
                            $fo["x"] ?? 0,
                            $fo["y"] ?? 0,
                            $fo["damageclass"] ?? null
                        );
                        if ($sys) {
                            $fires[] = $fireOrder;
                        }
                    }
                    if ($sys) $sys->setFireOrders($fires);
                }
    
                if (isset($system["systems"]) && is_array($system["systems"])) {
                    foreach ($system["systems"] as $fightersys) {
                        $fig = $sys ? $sys->getSystemById($fightersys["id"] ?? -1) : null;
                        if (!$fig) continue;
    
                        if (isset($fightersys["fireOrders"]) && is_array($fightersys["fireOrders"])) {
                            $fires = [];
                            foreach($fightersys["fireOrders"] as $fo) {
                                $fireOrder = new FireOrder(
                                    -1,
                                    $fo["type"] ?? "",
                                    $fo["shooterid"] ?? -1,
                                    $fo["targetid"] ?? -1,
                                    $fo["weaponid"] ?? -1,
                                    $fo["calledid"] ?? -1,
                                    $fo["turn"] ?? 0,
                                    $fo["firingMode"] ?? 1,
                                    0, 0, $fo["shots"] ?? 0, 0, 0,
                                    $fo["x"] ?? 0,
                                    $fo["y"] ?? 0,
                                    $fo["damageclass"] ?? null
                                );
                                $fires[] = $fireOrder;
                            }
    
                            // ammo transfer
                            if (isset($fightersys["ammo"])) {
                                foreach ($fightersys["ammo"] as $i => $ammo) {
                                    if (isset($ammo)) {
                                        $fig->setAmmo($i, $ammo);
                                    }
                                }
                            }
    
                            $fig->setFireOrders($fires);
                        }
    
                        if (isset($fightersys["individualNotesTransfer"])) {
                            $fig->individualNotesTransfer = $fightersys["individualNotesTransfer"];
                            $fig->doIndividualNotesTransfer();
                        }
                    }
                }
    
                if (isset($system["individualNotesTransfer"])) {
                    if ($sys) {
                        $sys->individualNotesTransfer = $system["individualNotesTransfer"];
                        $sys->doIndividualNotesTransfer();
                    }
                }
            }
    
            $ships[(int)($value["id"] ?? count($ships))] = $ship;
        }
    
        return $ships;
    }


    public static function insertSingleFiringOrder($gamedata, $fireOrder)
    {          
		self::$dbManager->submitSingleFireorder($gamedata->id, $fireOrder);
		
    }
    
    
    public static function retrieveFiringOrdersForWeapon($gamedata, $shooterid, $weaponid)
    {	
		$fireOrders = self::$dbManager->getFireOrdersForWeapon($gamedata->id, $shooterid, $weaponid, $gamedata->turn);	
		return $fireOrders;
    }    


    
}
