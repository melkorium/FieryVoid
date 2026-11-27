<?php
/**
 * CPU opponent - electronic warfare, in Initial Orders (TACTICAL_AI_PLAN.md section 5.3, stage T3).
 *
 * ⚠️ EW LEGALITY IS THE CLIENT'S, as movement and firing legality are. EW::validateEW answers true
 * to anything, so "more points than the scanner gives", a Restricted EW critical, an LCV's sensors
 * and "who may be locked on to" are all asked by the page alone (ew.js, gamedata.js at commit, the
 * Initial Orders menu). points() and the limits in allocate() are a PORT of those, and
 * tests/ai/ordersOracle.js clicks every row the bot posts through the real ew.js.
 *
 * THE DECISION, per ship. Every point starts as defence (DEW - which is what an unspent point is
 * anyway). One point at a time moves to the offensive use that gains most, while the gain is
 * positive, in named terms (principle P7):
 *
 *   offence  what the ship's loaded guns should do: each gun counted against the enemy it does
 *            most to, with the OEW held on that enemy (CCEW too, on a flight);
 *   defence  what the enemy's guns should do to this ship at its DEW - each gun by the share of
 *            its fire this ship would draw among the bot's units (it fires at one of them).
 *
 * The user's rulings of 2026-11-19 (game 4750, where both Omegas went to 0 DEW at 1-4 hexes) shape
 * it further: within EW_CLOSE_RANGE a lock stays soft (EW_SOFT_LOCK) and, with an enemy SHIP that
 * close, at least EW_CLOSE_MIN_DEW of the points stay in DEW; a ship that can fire at fighters puts
 * up 1 point of CCEW as a deterrent whenever an enemy flight could come within its 10 hexes this
 * turn, and no more than EW_CCEW_CAP unless the enemy is fighter-heavy or the ship's guns are mostly
 * anti-fighter guns.
 *
 * The user's notes on game 5826 (2026-11-22) - two Omegas with heavy guns ready locked no Narn ship on turn 3 and only the
 * Va'Kar on turn 4 - shape it further:
 *   - a split is priced as the engine will see it, every OEW and CCEW row of it on the ship at once: an enemy ELINT's
 *     disruption is shared over all of them (EW::getDistruptionEW), so a lone point on a ship can buy no lock where the
 *     same point beside a CCEW row buys half of one (score());
 *   - where an enemy ELINT disrupts the ship, a step may move several points onto one enemy at once - enough to get past
 *     the disruption - so the lock behind a first point that buys nothing is still found (lockStep());
 *   - within EW_CLOSE_RANGE every enemy SHIP a loaded gun could reach gets a soft lock (the level's skill 'closeLocks');
 *   - and the user's rulings on those notes (same day): at 10-20 hexes no lock over half the points unless no return fire could
 *     reach the ship at all (skill 'mediumLocks'); no OEW on a fighter flight - CCEW covers them - unless the enemy has no ship left
 *     and the flight is beyond CCEW's reach (skill 'noFlightOEW').
 *
 * Both are the REAL hit formula (Weapon::calculateHitBase, through AIView::estimate) asked with
 * the EW tried - never a formula of the bot's own. Two things are guessed, as a player guesses them:
 *   - where the units will be when the guns fire: the bot's ships are looked at where flying
 *     straight on takes them (which is what MoveDecider does unless something is in the way),
 *     the enemy where it stands, and arcs are not asked - facings change in Movement;
 *   - the enemy's EW, which is hidden until both sides have committed: each enemy is taken to
 *     repeat LAST turn's rows (remembered() - they were public then; the mask wipes them now).
 * And one thing is known and used as a player uses it: the turn's INITIATIVE order. Hard locks go
 * on enemies that move before the ship (beyond close range), soft ones (AIConfig::EW_SOFT_LOCK) on
 * those that move after.
 *
 * LEVELS (stage T5f): Easy splits its points half offensive, half DEW, and weighs nothing (the skill 'simpleEW') - since game
 * 5826 its offensive half is the CCEW deterrent and OEW on the nearest SHIP, as Normal would lock fighters and ships; the CCEW
 * deterrent is a skill of its own ('ccewDeterrent').
 *
 * ELINT FUNCTIONS (plan 10.10 G2, the user's notes of 2026-11-27 - "how players typically use ELINT functions"). The ELINT ships
 * decide LAST, after every other ship (exactly as before), most points first, each seeing the fleet's rows stood on the ships and
 * the earlier ELINTs' rows. An ELINT runs the same search twice - BLANKET (OEW / CCEW + BDEW) and SUPPORT (OEW / CCEW + SDEW on one
 * friend + SOEW + at Hard DIST and JAM) - the page refuses BDEW beside SDEW / SOEW / DIST / Detect Stealth - and posts the one that
 * scores more. The score is the same hit formula with the ELINT's rows standing, plus the FLEET's terms: the enemy's fire at every
 * other bot unit (BDEW and SDEW there; DIST cuts the enemy's locks - the engine takes the best ELINT's blanket, so a second one over
 * the same ships is worth nothing) and each SOEW friend's fire with the shared lock. SOEW goes up as a PACKAGE: the ELINT's lock on an
 * enemy ship raised to EW_SOEW_LOCK and shared with 1-2 bot ships that lock it too (the user: "hard-locking an opponent with at least 6
 * OEW and then sharing this lock using SOEW with around 1-2 allies"). JAM (Hard) is priced by HkJamming's table; Detect Stealth (Hard,
 * a stealth unit unseen) takes only the DEW points that buy nothing. Easy runs the same search on an ELINT (the user: "fine if there's
 * not that much variation between the CPU player levels on ELINT use"). Kirishiac ELINT modules keep all their points on DEW.
 *
 * NOT YET: Detect Mines (the user, 2026-11-27: for every ship, with a mine-sweeping behaviour), EW for flights.
 */
class EWDecider
{
    //What the last plan did, for tac_ai_log.
    public static $last = array('ships' => 0, 'points' => 0, 'offensive' => 0, 'targets' => 0, 'elint' => array());

    /* The enemy's EW rows of LAST turn: unit id => list of array(type, amount, targetid). Ask it
       BEFORE the load is masked. Only the turn before this one is read - rows that were on every
       player's screen - never this turn's, which an enemy who committed first already has. */
    public static function remembered(TacGamedata $gamedata, $botId)
    {
        $team = $gamedata->getPlayerTeam();
        $before = (int)$gamedata->turn - 1;
        $rows = array();

        foreach ($gamedata->ships as $unit) {
            if ((int)$unit->userid === (int)$botId || $unit->team == $team) continue;
            foreach ($unit->EW as $entry) {
                if ((int)$entry->turn !== $before) continue;
                $rows[(int)$unit->id][] = array($entry->type, (int)$entry->amount, (int)$entry->targetid);
            }
        }

        return $rows;
    }

    //Whom the page lets a ship lock on to: a targetable enemy that is not riding a docking bay.
    public static function lockable(AIView $view)
    {
        $enemies = array();
        foreach ($view->enemies() as $enemy) {
            if (HangarOps::bayCarrierAttachedTo($enemy, $view->gamedata) !== null) continue;
            $enemies[] = $enemy;
        }
        return $enemies;
    }

    /* Is the enemy weighted towards fighters - EW_FIGHTER_HEAVY or more of its visible value in flights? $enemies: the enemy units to
       weigh (plan(): every one the page lets a ship lock on to). The CCEW rule's measure (the user, 2026-11-19), and since stage T7 the
       trigger of Hard's Interceptor boost too (PowerDecider::interceptors - the user's ruling of 2026-11-21: "the measure the CCEW
       rule uses"). */
    public static function fighterHeavy(AIView $view, array $enemies)
    {
        $flightValue = 0;
        $enemyValue = 0;
        foreach ($enemies as $enemy) {
            $value = $view->value($enemy);
            $enemyValue += $value;
            if ($enemy instanceof FighterFlight) $flightValue += $value;
        }
        return $enemyValue > 0 && $flightValue >= AIConfig::EW_FIGHTER_HEAVY * $enemyValue;
    }

    /* The bot's EW rows for this turn: ship id => list of AIOrderBuilder::ew rows, DEW last, as
       the page posts them. $view is the bot's MASKED load. */
    public static function plan(AIView $view, array $remembered = array())
    {
        $gamedata = $view->gamedata;
        $turn = $view->turn;
        $plan = array();
        self::$last = array('ships' => 0, 'points' => 0, 'offensive' => 0, 'targets' => 0, 'elint' => array());

        //"No ships can use EW of any kind": the page writes no row at all in a nebula
        if (TacGamedata::$nebulaPresent) return $plan;

        $ships = array();
        foreach (AIFallback::ownUnits($gamedata, $view->botId) as $ship) {
            if ($ship instanceof FighterFlight || $ship->isDestroyed() || $ship->isTerrain()) continue;
            if ($ship->getTurnDeployed($gamedata) > $turn) continue;
            $ships[] = $ship;
        }
        if (count($ships) == 0) return $plan;

        $enemies = self::lockable($view);

        //The enemy's EW as it was last turn, stood in for this turn's.
        foreach ($enemies as $enemy) {
            if (!isset($remembered[(int)$enemy->id])) continue;
            foreach ($remembered[(int)$enemy->id] as $row) {
                $enemy->EW[] = new EWentry(-1, $enemy->id, $turn, $row[0], $row[1], $row[2]);
            }
        }

        /* Is the enemy weighted towards fighters (EW_FIGHTER_HEAVY of its visible value in flights)? Then CCEW may go past
           EW_CCEW_CAP on any ship (the user's ruling, 2026-11-19). */
        $fighterHeavy = self::fighterHeavy($view, $enemies);

        //The bot's own units where flying straight on takes them - and where they stand now. Taken back below.
        $moved = array();
        $mine = $view->myUnits();
        $now = array();
        foreach ($mine as $unit) {
            $now[(int)$unit->id] = $unit->getHexPos();
            $row = self::ahead($unit, $turn);
            if ($row === null) continue;
            $unit->movement[] = $row;
            $moved[] = $unit;
        }

        $kept = array();
        foreach ($ships as $ship) $kept[(int)$ship->id] = $ship->EW;

        /* G2: the ELINT ships decide LAST - their support needs the fleet's rows - most points first (ties by id); every other ship
           first, exactly as before (no ship's rows stand while they decide). */
        $elints = array();
        $plain = array();
        foreach ($ships as $ship) {
            if ($view->skill('elint') && self::elintShip($view, $ship)) $elints[] = $ship;
            else $plain[] = $ship;
        }
        usort($elints, function ($a, $b) use ($turn) {
            $pa = self::points($a, $turn);
            $pb = self::points($b, $turn);
            return ($pa == $pb) ? $a->id - $b->id : (($pa > $pb) ? -1 : 1);
        });

        try {
            $locked = array();
            $first = true;
            foreach (array_merge($plain, $elints) as $ship) {
                $elint = null;
                if (in_array($ship, $elints, true)) {
                    //the fleet's rows stand on its ships before the first ELINT decides (and each ELINT's after it has)
                    if ($first) {
                        foreach ($plain as $other) self::stand($other, $kept[(int)$other->id], $plan[(int)$other->id] ?? array());
                        $first = false;
                    }
                    $elint = self::elintContext($view, $ship, $enemies, $mine, $now, $ships);
                }
                $here = isset($now[(int)$ship->id]) ? $now[(int)$ship->id] : $ship->getHexPos();
                $alloc = self::allocate($view, $ship, $enemies, $mine, $here, $fighterHeavy, $elint);
                $rows = array();
                foreach ($alloc['OEW'] as $targetId => $amount) {
                    if ($amount <= 0) continue;
                    $rows[] = AIOrderBuilder::ew($ship->id, $turn, 'OEW', $amount, $targetId);
                    $locked[$targetId] = true;
                    self::$last['offensive'] += $amount;
                }
                //G2: the ELINT functions, in an order the page's menu takes them (the modes never mix BDEW with the others)
                foreach (self::elintRows($alloc) as $row) $rows[] = AIOrderBuilder::ew($ship->id, $turn, $row[0], $row[1], $row[2]);
                if ($alloc['CCEW'] > 0) {
                    $rows[] = AIOrderBuilder::ew($ship->id, $turn, 'CCEW', $alloc['CCEW']);
                    self::$last['offensive'] += $alloc['CCEW'];
                }
                $rows[] = AIOrderBuilder::ew($ship->id, $turn, 'DEW', $alloc['DEW']); //the page writes this row at commit, 0 included
                $plan[(int)$ship->id] = $rows;
                self::$last['ships']++;
                self::$last['points'] += $alloc['points'];
                if ($elint !== null) {
                    self::stand($ship, $kept[(int)$ship->id], $rows);
                    $text = self::elintText($view, $ship, $alloc);
                    if ($text !== '') self::$last['elint'][] = $text;
                }
            }
            self::$last['targets'] = count($locked);
        } finally {
            foreach ($moved as $unit) array_pop($unit->movement);
            foreach ($ships as $ship) $ship->EW = $kept[(int)$ship->id];
        }

        return $plan;
    }

    /* One ship's split: array('points', 'DEW', 'CCEW', 'OEW' => target id => amount) - and for an ELINT (G2) 'BDEW', 'SDEW' / 'SOEW' /
       'DIST' / 'JAM' => unit id => amount, 'DS' (Detect Stealth). $units: the bot's units on the board (where flying straight on takes
       them); $here: where this ship stands now; $fighterHeavy: see plan(); $elint: elintContext() for an ELINT ship, else null. */
    private static function allocate(AIView $view, $ship, array $enemies, array $units, OffsetCoordinate $here, $fighterHeavy, $elint = null)
    {
        $turn = $view->turn;
        $points = self::points($ship, $turn);
        $alloc = array('points' => $points, 'DEW' => $points, 'CCEW' => 0, 'OEW' => array());
        if ($elint !== null) $alloc += array('BDEW' => 0, 'SDEW' => array(), 'SOEW' => array(), 'DIST' => array(), 'JAM' => array(), 'DS' => 0);
        if ($points <= 0 || count($enemies) == 0) return $alloc;

        //Units that keep everything on defence: the page gives them no offensive EW, or the bot has no handler for their state (P4).
        if ($view->unitBlock($ship) !== null || !empty($ship->getElintModules())) return $alloc;
        if (HangarOps::bayCarrierAttachedTo($ship, $view->gamedata) !== null) return $alloc;

        //ew.checkRestrictedEW: with that critical on the C&C, at least half the output stays defensive.
        $cnc = self::firstNamed($ship, 'cnC');
        $minDew = ($cnc && empty($ship->osat) && PowerDecider::crits($cnc, 'RestrictedEW', $turn) > 0) ? (int)ceil($points / 2) : 0;
        $pageMinDew = $minDew; //G2: the page's own floor - the only one a blanket (BDEW) point must keep; the close-range floor below binds the rest
        //ew.checkLCVSensors: all but 2 points go to OEW - CCEW does not count as OEW.
        $lcv = $ship->hasSpecialAbility('LCVSensors') && $points > 2;

        /* How near each enemy is: the nearer of where the ship stands and where flying straight on takes it (the bot has
           not moved yet), to where the enemy stands. */
        $ahead = $ship->getHexPos();
        $near = array();
        foreach ($enemies as $enemy) {
            $at = $enemy->getHexPos();
            $near[(int)$enemy->id] = min($here->distanceTo($at), $ahead->distanceTo($at));
        }

        //nearest first, so that equal gains go to the nearest
        $from = $ship->getHexPos();
        usort($enemies, function ($a, $b) use ($from) {
            $da = $from->distanceTo($a->getHexPos());
            $db = $from->distanceTo($b->getHexPos());
            return ($da == $db) ? $a->id - $b->id : (($da < $db) ? -1 : 1);
        });

        $guns = array();
        foreach ($view->mounts($ship) as $mount) {
            $weapon = $mount['weapon'];
            if (!$view->plainGun($weapon) || $weapon->isOfflineOnTurn($turn) || !$view->loaded($weapon)) continue;
            $guns[] = $weapon;
        }

        /* CCEW (user ruling, 2026-11-19). The deterrent: a ship with a gun that can fire at fighters puts up 1 point when an
           enemy flight could be within CCEW's reach at this turn's Fire phase (EW_CCEW_REACH + the flight's speed). More only
           while it gains, and never past EW_CCEW_CAP - unless the enemy is fighter-heavy or most of this ship's guns are
           anti-fighter guns (fire control against fighters at least as good as against capital ships). */
        $flights = false;
        $deterrent = false;
        foreach ($enemies as $enemy) {
            if (!($enemy instanceof FighterFlight)) continue;
            $flights = true;
            $last = $enemy->getLastMovement();
            if ($near[(int)$enemy->id] <= AIConfig::EW_CCEW_REACH + ($last ? max(0, (int)$last->speed) : 0)) $deterrent = true;
        }
        $antiFighter = 0;
        $plain = 0;
        foreach ($view->mounts($ship) as $mount) {
            $weapon = $mount['weapon'];
            if (!$view->plainGun($weapon) || $weapon->isOfflineOnTurn($turn)) continue;
            $fc = $weapon->fireControl;
            if (!isset($fc[0]) && !isset($fc[1]) && !isset($fc[2])) continue; //no offensive fire at all (an Interceptor)
            $plain++;
            //an anti-fighter gun aims better at fighters than at capital ships: a Twin Array (6/5/4) is one, a Standard Particle Beam (4/4/4) is not
            if (isset($fc[0]) && (!isset($fc[2]) || $fc[0] > $fc[2])) $antiFighter++;
        }
        $canShootFighters = false;
        foreach ($guns as $weapon) {
            if (isset($weapon->fireControl[0])) $canShootFighters = true;
        }
        /* (2026-11-26 - the user's tester on live game 7857: "Apollo full CCEW vs fighters instead of 1-2 points as Hard should"; the
           Omega too, once the Sentri closed) A fighter-heavy enemy lifts the cap only on a ship with an anti-fighter gun to use it -
           the Apollo and the Omega have none (their Standard Particle Beams aim no better at a fighter than at a capital ship) - or
           when the enemy has no ship left at all (CCEW is then the only offensive EW there is: no OEW on fighters, game 5826). */
        $noShips = true;
        foreach ($enemies as $enemy) {
            if (!($enemy instanceof FighterFlight) && empty($enemy->mine)) $noShips = false;
        }
        $ccewCap = (($fighterHeavy && ($antiFighter > 0 || $noShips)) || ($plain > 0 && 2 * $antiFighter >= $plain)) ? PHP_INT_MAX : AIConfig::EW_CCEW_CAP;

        /* CLOSE ENGAGEMENT (user ruling, 2026-11-19): an enemy within EW_CLOSE_RANGE gets a soft lock at most, and with an
           enemy SHIP that close at least EW_CLOSE_MIN_DEW of the points stay in DEW. (An LCV has the page's own rule.) */
        $close = array();
        $engaged = false;
        foreach ($enemies as $enemy) {
            if ($near[(int)$enemy->id] > AIConfig::EW_CLOSE_RANGE) continue;
            $close[(int)$enemy->id] = true;
            if (!($enemy instanceof FighterFlight) && empty($enemy->mine)) $engaged = true;
        }
        if ($engaged && !$lcv) $minDew = max($minDew, (int)ceil($points * AIConfig::EW_CLOSE_MIN_DEW));

        $base = array();
        foreach ($ship->EW as $entry) {
            if ((int)$entry->turn !== $turn) $base[] = $entry;
        }
        /* INITIATIVE (user, 2026-11-15). The initiative order is on the board before EW is set. An
           enemy that moves BEFORE this ship will be where the ship can see it when the ship
           moves, so its guns can be brought to bear: a hard lock is worth having. One that moves
           AFTER it (or with it) may leave the arcs again: its fire counts for less
           (EW_MOVES_AFTER_WEIGHT) and the lock stays soft (EW_SOFT_LOCK points). An enemy that
           cannot move at all counts as having moved. */
        $soft = array();
        foreach ($enemies as $enemy) {
            if (!empty($enemy->base) || !empty($enemy->smallBase) || !empty($enemy->osat) || !empty($enemy->mine)) continue;
            if ($enemy->iniative >= $ship->iniative) $soft[(int)$enemy->id] = true;
        }
        //enemy id => the most OEW it may get: a soft lock at most when it moves after the ship, or is close
        $caps = array();
        foreach ($soft + $close as $id => $yes) $caps[$id] = AIConfig::EW_SOFT_LOCK;

        /* MEDIUM RANGE (the user's ruling on game 5826, 2026-11-22: "At range 10-20 hexes I think we should not go full EW with
           Normal or Hard CPU players, unless the danger of return fire is very low. 50% EW max lock would be more appropriate").
           Beyond EW_CLOSE_RANGE and within EW_MEDIUM_RANGE, a lock holds at most EW_MEDIUM_LOCK_SHARE of the points - unless no
           enemy gun or launcher could reach this ship this turn at all, both closing as fast as they can (PowerDecider::reaches,
           Hard's power test). On 5826's turn 3 Omega #1 had put all 7 points on the G'Quan, 17 hexes off: the threats DEW is priced
           against were empty (the Heavy Lasers reloading, the pulse guns short of reach where the Narn ships stood), yet once both
           closed the Va'Kar's Medium Pulses and Ion Torpedo reached it. (A level's skill.) */
        $mediumCaps = array(); //(stage T9) the medium-range caps alone, for when the soft ones are lifted below
        if ($view->skill('mediumLocks') && $points > 1) {
            $medium = array();
            foreach ($enemies as $enemy) {
                $d = $near[(int)$enemy->id];
                if ($d > AIConfig::EW_CLOSE_RANGE && $d <= AIConfig::EW_MEDIUM_RANGE) $medium[] = (int)$enemy->id;
            }
            if (count($medium) > 0 && !self::unthreatened($view, $ship, $enemies)) {
                $half = max(1, (int)floor($points * AIConfig::EW_MEDIUM_LOCK_SHARE));
                foreach ($medium as $id) {
                    $caps[$id] = isset($caps[$id]) ? min($caps[$id], $half) : $half;
                    $mediumCaps[$id] = $half;
                }
            }
        }

        /* NO OEW ON FIGHTERS (the user's ruling on game 5826: "As a rule OEW should never be used on fighter flights, CCEW is generally
           enough by itself. The only caveat would be if there were only enemy fighters left and they are likely to be more than 10
           hexes away"). A flight may take OEW only when the enemy has no ship left and it is further than CCEW's reach from where the
           ship stands and from where it flies straight on (EW_CCEW_REACH). On 5826's turn 3 Omega #2 had put 2 points on the Frazi.
           (A level's skill - Easy's too, since the user's ruling below.) */
        $lockable = $enemies;
        if ($view->skill('noFlightOEW')) {
            $shipsLeft = false;
            foreach ($enemies as $enemy) {
                if (!($enemy instanceof FighterFlight) && empty($enemy->mine)) $shipsLeft = true;
            }
            $lockable = array();
            foreach ($enemies as $enemy) {
                if ($enemy instanceof FighterFlight && ($shipsLeft || $near[(int)$enemy->id] <= AIConfig::EW_CCEW_REACH)) continue;
                $lockable[] = $enemy;
            }
        }

        /* EASY (stage T5f, plan 5.8): half the points offensive, half DEW - nothing weighed. Since the user's ruling on game 5826
           (2026-11-22: "Easy should just follow the same CCEW rule as Normal and not put OEW on fighters ever") the offensive half
           is the CCEW deterrent first, where Normal would put it up, and the rest OEW on the nearest enemy that may take a lock -
           a ship, as for Normal (noFlightOEW); with nothing to lock (only fighters left, within CCEW's reach) the rest goes to CCEW
           as well, no further than Normal's cap. Until then it put its whole half on the nearest enemy, a flight included, and
           never put up CCEW. The page's own limits still hold: a Restricted EW critical's half stays DEW; an LCV keeps its rule below. */
        /* G2: an Easy ELINT runs the search below as well (the user: "fine if there's not that much variation between the CPU player
           levels on ELINT use") - but keeps this split whenever the search finds no ELINT function worth a point ($easySplit). */
        $easySplit = null;
        if ($view->skill('simpleEW') && !$lcv) {
            $half = max(0, min(intdiv($points, 2), $points - $minDew));
            $easy = array('points' => $points, 'DEW' => $points, 'CCEW' => 0, 'OEW' => array());
            $ccewOk = $deterrent && $canShootFighters && $view->skill('ccewDeterrent');
            if ($half > 0 && $ccewOk) $easy['CCEW'] = 1;
            $rest = $half - $easy['CCEW'];
            if ($rest > 0 && count($lockable) > 0) $easy['OEW'][(int)$lockable[0]->id] = $rest;
            else if ($rest > 0 && $ccewOk) $easy['CCEW'] += min($rest, $ccewCap - $easy['CCEW']);
            $easy['DEW'] = $points - $easy['CCEW'] - array_sum($easy['OEW']);
            if ($elint === null) return $easy;
            $easySplit = $easy + array('BDEW' => 0, 'SDEW' => array(), 'SOEW' => array(), 'DIST' => array(), 'JAM' => array(), 'DS' => 0);
        }

        //(game 5826) how many points one step of the search may move onto an enemy: 1 unless an enemy ELINT disrupts this ship
        $step = self::lockStep($view, $ship, $base);
        //stage T9 (M8): the ship's Ballistic Torpedo launchers that launch this turn - their volley counts in a split's worth (score())
        $launchers = array();
        foreach ($view->mounts($ship) as $mount) {
            if ($mount['fighter'] === null && AIView::isTorpedo($mount['weapon']) && $view->launcherNow($ship, $mount['weapon'])) $launchers[] = $mount['weapon'];
        }
        $state = array('view' => $view, 'ship' => $ship, 'base' => $base, 'guns' => $guns, 'enemies' => $enemies,
            'units' => $units, 'threats' => null, 'offence' => array(), 'defence' => array(), 'soft' => $soft, 'disrupted' => ($step > 1),
            'launchers' => $launchers, 'elint' => $elint);

        //the CCEW deterrent first: a point of DEW the greedy below does not have to find a gain for (a level's skill, stage T5f)
        if ($deterrent && $canShootFighters && !$lcv && $alloc['DEW'] > $minDew && $view->skill('ccewDeterrent')) {
            $alloc['DEW']--;
            $alloc['CCEW']++;
        }

        /* CLOSE ENGAGEMENT, A SOFT LOCK ON EVERY SHIP (the user's notes on game 5826, 2026-11-22: "both Omegas should have
           soft-locked the Gquan at these ranges"). The search below prices every gun at the one enemy it does most to, arcs
           not asked - a facing changes in Movement - so with the Va'Kar locked no Omega gun was ever expected to fire at the
           G'Quan beside it, and a point there gained nothing; up close, though, a ship's guns end up on different targets
           (turn 4's Fire phase: 13 Omega guns at the G'Quan, all without a lock). So before the search, one point on every enemy
           SHIP within EW_CLOSE_RANGE that a loaded gun of this ship could reach, nearest first, while DEW stays above its floor;
           the search then adds the second point where it pays. (A level's skill: Easy's simpleEW never gets here.) */
        if ($engaged && !$lcv && $view->skill('closeLocks')) {
            foreach ($enemies as $enemy) {
                if ($alloc['DEW'] <= $minDew) break;
                if (!isset($close[(int)$enemy->id]) || $enemy instanceof FighterFlight || !empty($enemy->mine)) continue;
                if (isset($alloc['OEW'][(int)$enemy->id]) || !self::reaches($view, $ship, $guns, $enemy, $base)) continue;
                $alloc['DEW']--;
                $alloc['OEW'][(int)$enemy->id] = 1;
            }
        }

        /* THE TORPEDO'S LOCK (stage T9, M8 - the user, 2026-11-23: Ballistic Torpedoes "backed by 1 or 2 OEW"). A ship whose torpedoes launch
           this turn puts a point, before the search, on the enemy SHIP they would do most to with it (AIView::torpedoEstimate - the volley the
           bot would send) - where the point adds something (a jammer denies it). The search may add the second: the volley counts in
           score(). A flight is CCEW's (noFlightOEW). Every level the search runs at (Easy's simpleEW puts its OEW on the nearest ship). */
        if (count($launchers) > 0 && !$lcv && $alloc['DEW'] > $minDew) {
            $pick = null;
            $pickWorth = 0;
            foreach ($lockable as $enemy) {
                if ($enemy instanceof FighterFlight || !empty($enemy->mine) || isset($alloc['OEW'][(int)$enemy->id])) continue;
                if (isset($caps[(int)$enemy->id]) && $caps[(int)$enemy->id] < 1) continue;
                $without = self::torpedoWorth($view, $ship, $launchers, $enemy, $base, 0);
                $with = self::torpedoWorth($view, $ship, $launchers, $enemy, $base, 1);
                if ($with > $without && $with > $pickWorth) {
                    $pick = $enemy;
                    $pickWorth = $with;
                }
            }
            if ($pick !== null) {
                $alloc['DEW']--;
                $alloc['OEW'][(int)$pick->id] = 1;
            }
        }

        /* The search: a step at a time, the one that gains most for each point it moves, while that is at least EW_MIN_GAIN a
           point. Every step is one point unless an enemy ELINT disrupts the ship (lockStep) - then a step may also move up to
           $step points onto one enemy at once, its gain counted per point, so it is taken only when it pays better than the
           single points beside it. */
        $current = self::score($state, $alloc);

        /* A JAMMER (stage T9 - the user's note on the Minbari, 2026-11-23: "The Minbari's unique system is the Jammer, which prevents
           OEW locks on their ships when active. This generally means they use more OEW, especially at the start of games, as the
           enemies ability to retaliate is vastly reduced due to low hit chances from having double range penalty"). The pricing
           already knows the jammer - DEW is priced through the real hit formula, the doubled range penalty included - so a jammed ship
           goes all-in on OEW at long range against enemies moving first. Against enemies moving AFTER it the soft lock capped every
           lock at EW_SOFT_LOCK and the rest stayed on DEW (a Sharlin 30 hexes from an Omega and a Hyperion moving after it: 2 OEW,
           10 DEW - with every enemy gun at a few percent). So a ship whose jammer denies EVERY enemy a lock (jammed()) has no soft
           locks beyond EW_CLOSE_RANGE: the split is the pricing's (an enemy moving after still counts for EW_MOVES_AFTER_WEIGHT of
           its fire); the close-range and medium-range caps stay. */
        if (self::jammed($ship, $enemies)) {
            foreach (array_keys($soft) as $id) {
                if (isset($close[$id])) continue;
                if (isset($mediumCaps[$id])) $caps[$id] = $mediumCaps[$id];
                else unset($caps[$id]);
            }
        }

        /* MISSILE SHIPS TURTLE (the user's tester, 2026-11-26, live game 7857 - the Apollo: "missile ships should 'turtle' with high DEW
           at Normal and Hard - missiles don't benefit from OEW"; Weapon::$useOEW is false on every missile). A ship most of whose fire
           is in weapons that use no OEW (missileShip) locks no enemy past EW_TURTLE_LOCK and keeps CCEW to the deterrent's point: the
           rest is DEW. (The skill 'missileTurtle'; after the jammer's lifts, which it overrules.) */
        if ($view->skill('missileTurtle') && self::missileShip($view, $ship)) {
            foreach ($enemies as $enemy) {
                $id = (int)$enemy->id;
                $caps[$id] = isset($caps[$id]) ? min($caps[$id], AIConfig::EW_TURTLE_LOCK) : AIConfig::EW_TURTLE_LOCK;
            }
            $ccewCap = min($ccewCap, 1);
        }

        $ccewLimit = ($flights && !$lcv) ? $ccewCap : null;
        if ($elint === null) {
            list($alloc, $current) = self::climb($state, $alloc, $current, $lockable, $caps, $step, $minDew, $ccewLimit);
        } else {
            /* G2 - an ELINT: the search twice, BLANKET and SUPPORT (the page never lets BDEW stand beside SDEW, SOEW, DIST or Detect
               Stealth), the better kept - a tie to SUPPORT, where an idle point may still go to Detect Stealth. The SOEW package needs
               the friends that lock each enemy ship the ELINT may lock (not at close range, where a lock stays soft; not a missile ship
               turtling). */
            $state['elint']['packages'] = self::packages($state, $lockable, $close, $view->skill('missileTurtle') && self::missileShip($view, $ship));
            $blanket = self::climb($state, $alloc, $current, $lockable, $caps, $step, $minDew, $ccewLimit, 'blanket', $pageMinDew);
            $support = self::climb($state, $alloc, $current, $lockable, $caps, $step, $minDew, $ccewLimit, 'support', $pageMinDew);
            if ($blanket[1] > $support[1]) {
                list($alloc, $current) = $blanket;
            } else {
                list($alloc, $current) = $support;
                /* DETECT STEALTH (Hard - the user: "only allow Hard to consider if it's worth it to detect across more of the map"): with a
                   stealth unit in the game the bot cannot see now, the DEW points that buy less than EW_MIN_GAIN of defence each extend
                   the ELINT's detection 2 hexes, up to EW_DETECT_STEALTH_MAX. */
                if ($view->skill('elintStealth') && !empty($view->gamedata->isStealthPresent) && self::unseenStealth($view)) {
                    while ($alloc['DS'] < AIConfig::EW_DETECT_STEALTH_MAX && $alloc['DEW'] > $minDew) {
                        $try = $alloc;
                        $try['DEW']--;
                        $try['DS']++;
                        $score = self::score($state, $try);
                        if ($current - $score >= AIConfig::EW_MIN_GAIN) break;
                        $alloc = $try;
                        $current = $score;
                    }
                }
            }
            //Easy: the search's split only for its ELINT functions - with none of them worth a point, Easy's own half-and-half split
            if ($easySplit !== null && count(self::elintRows($alloc)) == 0) return $easySplit;
        }

        /* An LCV has no choice: what is left above 2 goes to OEW, wherever it does most (the nearest, when nothing does anything) -
           on a flight only when nothing else may take it (the page's LCV rule comes before the fighter rule). */
        while ($lcv && $alloc['DEW'] > 2) {
            $best = null;
            $bestScore = null;
            foreach (self::moves($alloc, count($lockable) > 0 ? $lockable : $enemies, 0) as $try) {
                $score = self::score($state, $try);
                if ($bestScore === null || $score > $bestScore) {
                    $bestScore = $score;
                    $best = $try;
                }
            }
            if ($best === null) break;
            $alloc = $best;
        }

        return $alloc;
    }

    /* Every split one point of DEW away from $alloc - and, with $step over 1, every split moving 2..$step points of it onto one
       enemy (or into CCEW) at once, listed after the single points so that a tie goes to the single point. $enemies: those that
       may take OEW. $ccewRoom: how many more CCEW points may go up (0: none). $caps: enemy id => the most OEW it may hold (a soft
       lock, EW_SOFT_LOCK; half the points at medium range); empty where the ship has no choice (an LCV). */
    private static function moves(array $alloc, array $enemies, $ccewRoom, array $caps = array(), $step = 1)
    {
        $list = array();
        for ($points = 1; $points <= max(1, (int)$step); $points++) {
            foreach ($enemies as $enemy) {
                $has = isset($alloc['OEW'][(int)$enemy->id]) ? $alloc['OEW'][(int)$enemy->id] : 0;
                if (isset($caps[(int)$enemy->id]) && $has + $points > $caps[(int)$enemy->id]) continue;
                $try = $alloc;
                $try['DEW'] -= $points;
                $try['OEW'][(int)$enemy->id] = $has + $points;
                $list[] = $try;
            }
            if ($points <= $ccewRoom) {
                $try = $alloc;
                $try['DEW'] -= $points;
                $try['CCEW'] += $points;
                $list[] = $try;
            }
        }
        return $list;
    }

    /* (2026-11-26) Is most of $ship's fire - EW_MISSILE_SHARE or more, each weapon by its average damage, shots and guns a turn of
       its loading - in weapons that use no OEW (Weapon::$useOEW false: every missile)? */
    public static function missileShip(AIView $view, $ship)
    {
        $all = 0.0;
        $missiles = 0.0;
        foreach ($view->mounts($ship) as $mount) {
            $weapon = $mount['weapon'];
            if ($weapon->isDestroyed() || $weapon instanceof JumpEngine || !empty($weapon->isRammingAttack) || $weapon->maxDamage <= 0) continue;
            if ($weapon->weaponClass === 'Support' || $weapon->isOfflineOnTurn($view->turn)) continue;
            $fc = $weapon->fireControl;
            if (!isset($fc[0]) && !isset($fc[1]) && !isset($fc[2])) continue; //no offensive fire at all (an Interceptor)
            $worth = (($weapon->minDamage + $weapon->maxDamage) / 2) * max(1, (int)$weapon->defaultShots) * max(1, (int)$weapon->guns)
                / max(1, (int)$weapon->loadingtime);
            $all += $worth;
            if (empty($weapon->useOEW)) $missiles += $worth;
        }
        return $all > 0 && $missiles >= AIConfig::EW_MISSILE_SHARE * $all;
    }

    /* How many points one step of the search may move onto one enemy (the user's notes on game 5826): 1 - the search as it always
       was - unless an enemy ELINT disrupts this ship; then 1 more than the disruption a lone lock would meet, by the engine's own
       sum (EW::getDistruptionEW: each ELINT in range and in sight, DIST / 3, shared over the ship's lock rows). On turn 3 the
       Va'Kar's remembered DIST 3 on each Omega ate a lone first point whole, so that point gained nothing and the point-at-a-time
       search never reached the lock behind it - both Omegas, heavy guns ready, stayed on DEW. */
    private static function lockStep(AIView $view, $ship, array $base)
    {
        $ship->EW = array_merge($base, array(new EWentry(-1, $ship->id, $view->turn, 'OEW', 1, -1)));
        $disruption = EW::getDistruptionEW($view->gamedata, $ship);
        $ship->EW = $base;
        return ($disruption > 0) ? 1 + (int)ceil($disruption) : 1;
    }

    /* (Stage T9) Does $ship's jammer deny every enemy that could shoot at it a lock - the engine's own test (Jammer::
       getSpecialAbilityValue: on, not in a nebula, the shooter of another faction, no Advanced Sensors)? Mines left to Hazards. */
    private static function jammed($ship, array $enemies)
    {
        $any = false;
        foreach ($enemies as $enemy) {
            if (!empty($enemy->mine)) continue;
            if ($ship->getSpecialAbilityValue('Jammer', array('shooter' => $enemy, 'target' => $ship)) <= 0) return false;
            $any = true;
        }
        return $any;
    }

    /* Is the danger of return fire "very low" (the user's ruling on game 5826)? Only when no loaded gun or launcher of any enemy
       (mines left to Hazards) could reach $ship this turn at all, with both closing as fast as they can and the enemy's every EW
       point locked on - the reach test of Hard's power management (PowerDecider::reaches), not the threats DEW is priced against,
       which stand the enemy still and leave ballistics out. */
    private static function unthreatened(AIView $view, $ship, array $enemies)
    {
        $armed = array();
        foreach ($enemies as $enemy) {
            if (empty($enemy->mine)) $armed[] = $enemy;
        }
        $reach = PowerDecider::reaches($view, $ship, $armed);
        return $reach['shots'] == 0;
    }

    //(Stage T9, M8) What $ship's torpedo launchers would do to $enemy with $points of OEW on it (AIView::torpedoEstimate) - the one lock row alone.
    private static function torpedoWorth(AIView $view, $ship, array $launchers, $enemy, array $base, $points)
    {
        $ship->EW = ($points > 0) ? array_merge($base, array(new EWentry(-1, $ship->id, $view->turn, 'OEW', $points, $enemy->id))) : $base;
        $worth = 0;
        foreach ($launchers as $weapon) $worth += $view->torpedoEstimate($ship, $weapon, $enemy);
        $ship->EW = $base;
        return $worth;
    }

    //Could a loaded gun of $ship do anything to $enemy with a point locked on it? Arcs not asked, as score() does not.
    private static function reaches(AIView $view, $ship, array $guns, $enemy, array $base)
    {
        $ship->EW = array_merge($base, array(new EWentry(-1, $ship->id, $view->turn, 'OEW', 1, $enemy->id)));
        $reach = false;
        foreach ($guns as $weapon) {
            if ($view->estimate($ship, $weapon, $enemy, false) > 0) {
                $reach = true;
                break;
            }
        }
        $ship->EW = $base;
        return $reach;
    }

    //What a split is worth to the ship: expected damage dealt less expected damage taken.
    private static function score(array &$state, array $alloc)
    {
        $view = $state['view'];
        $ship = $state['ship'];
        $turn = $view->turn;

        /* The split as the engine will see it (the user's notes on game 5826): every OEW and CCEW row of it on the ship at once.
           An enemy ELINT's disruption is shared over all of them (EW::getDistruptionEW divides it by getOEWTargetNum), so what a
           lock is worth depends on how many the ship holds. Until then each enemy was priced with its own row alone: a lone
           point on the G'Quan met the Va'Kar's whole disruption, where the CCEW row beside it would have halved it, while a
           point on the Frazi was priced beside that CCEW row - so the Omegas locked the fighters and no ship. Nothing else the
           hit formula asks of the shooter's EW depends on its other rows: where nothing disrupts the ship ($state['disrupted']
           false) every answer, and its cache, is what it always was. */
        $rows = $state['base'];
        $locks = 0;
        foreach ($alloc['OEW'] as $targetId => $amount) {
            if ($amount <= 0) continue;
            $rows[] = new EWentry(-1, $ship->id, $turn, 'OEW', $amount, $targetId);
            $locks++;
        }
        if ($alloc['CCEW'] > 0) {
            $rows[] = new EWentry(-1, $ship->id, $turn, 'CCEW', $alloc['CCEW'], -1);
            $locks++;
        }
        //G2: an ELINT's functions stand too - a JAM row is a lock row for the engine's disruption share (BaseShip::getOEWTargetNum)
        $elint = isset($state['elint']) && $state['elint'] !== null;
        if ($elint) {
            foreach (self::elintRows($alloc) as $row) {
                $rows[] = new EWentry(-1, $ship->id, $turn, $row[0], $row[1], $row[2]);
                if ($row[0] === 'JAM') $locks++;
            }
        }
        $standing = false;
        $offence = 0;
        foreach ($state['guns'] as $weapon) {
            $best = 0;
            foreach ($state['enemies'] as $enemy) {
                $oew = isset($alloc['OEW'][(int)$enemy->id]) ? $alloc['OEW'][(int)$enemy->id] : 0;
                $ccew = ($enemy instanceof FighterFlight) ? $alloc['CCEW'] : 0;
                $key = $weapon->id . ':' . $enemy->id . ':' . $oew . ':' . $ccew . ($state['disrupted'] ? ':' . $locks : '');
                if (!isset($state['offence'][$key])) {
                    if (!$standing) {
                        $ship->EW = $rows;
                        $standing = true;
                    }
                    $state['offence'][$key] = $view->estimate($ship, $weapon, $enemy, false)
                        * (isset($state['soft'][(int)$enemy->id]) ? AIConfig::EW_MOVES_AFTER_WEIGHT : 1);
                }
                $best = max($best, $state['offence'][$key]);
            }
            $offence += $best;
        }
        /* (Stage T9, M8) the ship's torpedo launchers: each its volley at the one enemy it does most to, launched now - the arc as the ship
           stands, the launch is before Movement (AIView::torpedoEstimate). Not weighted by who moves first: a torpedo follows its target. */
        foreach (isset($state['launchers']) ? $state['launchers'] : array() as $weapon) {
            $best = 0;
            foreach ($state['enemies'] as $enemy) {
                $oew = isset($alloc['OEW'][(int)$enemy->id]) ? $alloc['OEW'][(int)$enemy->id] : 0;
                $ccew = ($enemy instanceof FighterFlight) ? $alloc['CCEW'] : 0;
                $key = 'torpedo:' . $weapon->id . ':' . $enemy->id . ':' . $oew . ':' . $ccew . ($state['disrupted'] ? ':' . $locks : '');
                if (!isset($state['offence'][$key])) {
                    if (!$standing) {
                        $ship->EW = $rows;
                        $standing = true;
                    }
                    $state['offence'][$key] = $view->torpedoEstimate($ship, $weapon, $enemy);
                }
                $best = max($best, $state['offence'][$key]);
            }
            $offence += $best;
        }

        /* The enemy guns that could reach this ship at all (asked once, with no DEW up), then at the DEW tried. Each gun
           fires at one target: it is counted for this ship by the share of what it could do to the bot's units that this
           ship would take (until 2026-11-19 every gun counted 1 / the number of the bot's units - its fighter flights
           included - which priced a point of DEW at a quarter in game 4750 and left both Omegas on 0 DEW). Only the guns
           loaded this turn count, as on the offence side. A mine's guns are not counted (Hazards prices a mine). */
        if ($state['threats'] === null) {
            $state['threats'] = array();
            $ship->EW = $state['base'];
            foreach ($state['enemies'] as $enemy) {
                if (!empty($enemy->mine)) continue;
                foreach ($view->mounts($enemy) as $mount) {
                    //a gun that fires this turn: loaded, as the ship's own are counted (an enemy's loading is on every player's screen)
                    if (!$view->plainGun($mount['weapon'], $mount['fighter']) || !$view->loaded($mount['weapon'])) continue;
                    $here = $view->estimate($enemy, $mount['weapon'], $ship, false);
                    if ($here <= 0) continue;
                    $all = 0;
                    foreach ($state['units'] as $unit) $all += ((int)$unit->id === (int)$ship->id) ? $here : $view->estimate($enemy, $mount['weapon'], $unit, false);
                    $state['threats'][] = array($enemy, $mount['weapon'], $all > 0 ? $here / $all : 1);
                }
            }
        }
        $dew = $alloc['DEW'];
        //G2: an ELINT's own defence also hangs on its blanket (it covers the ELINT) and its DIST (the enemy's locks on it cut)
        $defenceKey = $elint ? $dew . ':' . $alloc['BDEW'] . ':' . self::distKey($alloc) : $dew;
        if (!isset($state['defence'][$defenceKey])) {
            $rows = $state['base'];
            $rows[] = new EWentry(-1, $ship->id, $turn, 'DEW', $dew, -1);
            if ($elint) {
                if ($alloc['BDEW'] > 0) $rows[] = new EWentry(-1, $ship->id, $turn, 'BDEW', $alloc['BDEW'], -1);
                foreach ($alloc['DIST'] as $id => $amount) if ($amount > 0) $rows[] = new EWentry(-1, $ship->id, $turn, 'DIST', $amount, $id);
            }
            $ship->EW = $rows;
            $taken = 0;
            foreach ($state['threats'] as $threat) $taken += $view->estimate($threat[0], $threat[1], $ship, false) * $threat[2];
            $state['defence'][$defenceKey] = $taken;
        }

        $total = AIConfig::EW_OFFENCE * $offence - AIConfig::EW_DEFENCE * $state['defence'][$defenceKey];
        if (!$elint) return $total;

        /* G2 - THE FLEET'S TERMS, with every row of the split standing on the ELINT: the enemy's fire at the bot's other units (the
           blanket, SDEW and DIST in it), the SOEW friends' fire with the shared lock, and what JAM should take off an HK flight. */
        $all = array();
        foreach ($state['base'] as $entry) $all[] = $entry;
        foreach (self::elintRows($alloc) as $row) $all[] = new EWentry(-1, $ship->id, $turn, $row[0], $row[1], $row[2]);
        foreach ($alloc['OEW'] as $targetId => $amount) if ($amount > 0) $all[] = new EWentry(-1, $ship->id, $turn, 'OEW', $amount, $targetId);
        if ($alloc['CCEW'] > 0) $all[] = new EWentry(-1, $ship->id, $turn, 'CCEW', $alloc['CCEW'], -1);
        $all[] = new EWentry(-1, $ship->id, $turn, 'DEW', $dew, -1);
        $ship->EW = $all;
        return $total + AIConfig::EW_OFFENCE * self::fleetOffence($state, $alloc, $locks, $all)
            - AIConfig::EW_DEFENCE * self::fleetDefence($state, $alloc) + AIConfig::EW_DEFENCE * self::jamWorth($state, $alloc);
    }

    // ------------------------------------------------------------------ G2: ELINT functions

    /* Is $ship an ELINT this decider plans as one: the page's isElint (a system with the ability, neither destroyed nor switched off -
       ships.js getSpecialAbilitySystem; a Jealous ELINT array only when designated) and the engine's (BaseShip::isElint, which its ELINT
       sums ask); points to spend; not a docking rider, an LCV (the page's own rule for it) or a unit refused by unitBlock; and no Kirishiac
       ELINT modules (their two pools stay on DEW, as before). */
    private static function elintShip(AIView $view, $ship)
    {
        if (!$ship->isElint() || !empty($ship->getElintModules()) || $ship->hasSpecialAbility('LCVSensors')) return false;
        if ($view->unitBlock($ship) !== null || HangarOps::bayCarrierAttachedTo($ship, $view->gamedata) !== null) return false;
        if (self::points($ship, $view->turn) <= 0) return false;
        foreach ($ship->systems as $system) {
            if (!isset($system->specialAbilities) || !is_array($system->specialAbilities) || !in_array('ELINT', $system->specialAbilities, true)) continue;
            if ($system->isDestroyed() || $system->isOfflineOnTurn($view->turn)) continue;
            if (method_exists($system, 'isJealousElint') && $system->isJealousElint() && !$system->isElintOnTurn($view->turn)) continue;
            return true;
        }
        return false;
    }

    /* What an ELINT may do this turn, by the page's menu (shipTooltipInitialOrdersMenu.js), every range from where the units stand NOW
       (the page's test) and an Expert ELINT Officer's longer ones (Officers::elintRange):
         'friends'  the bot's other units on the board, mines aside - priced for the enemy's fire at them (flights too: a blanket covers them)
         'near'     unit id => whether it is within the blanket's reach (20) where the units are priced - a cache key's part only
         'soew'     the bot's other SHIPS (not flights or mines, targetable, not docking riders) within 30 - SOEW's targets
         'sdew'     the same within 50 - SDEW's
         'dist'     (Hard) enemy SHIPS within 30, not mines, by the Advanced Sensors rule (the ELINT has them, or the target has not)
         'jam'      (Hard) enemy remoteControl flights within 30, by the same rule
       $ships: the bot's ships planned this turn; $now: unit id => where the bot's units stand now. */
    private static function elintContext(AIView $view, $ship, array $enemies, array $mine, array $now, array $ships)
    {
        $from = isset($now[(int)$ship->id]) ? $now[(int)$ship->id] : $ship->getHexPos();
        $at = function ($unit) use ($now) {
            return isset($now[(int)$unit->id]) ? $now[(int)$unit->id] : $unit->getHexPos();
        };
        $range30 = Officers::elintRange($ship, 30);
        $range50 = Officers::elintRange($ship, 50);
        $blanket = Officers::elintRange($ship, 20);
        $advanced = $ship->hasSpecialAbility('AdvancedSensors');

        $context = array('friends' => array(), 'near' => array(), 'soew' => array(), 'sdew' => array(), 'dist' => array(), 'jam' => array(),
            'byId' => array(), 'distStep' => 3 + ($ship->hasSpecialAbility('ConstrainedEW') ? 1 : 0), 'packages' => array(),
            'threats' => null, 'taken' => array(), 'fire' => array(), 'fireBase' => array(), 'guns' => array(), 'jamThreat' => array(),
            'enemyElint' => false);
        foreach ($mine as $unit) {
            if ((int)$unit->id === (int)$ship->id || !empty($unit->mine) || $unit->isDestroyed() || $unit->isTerrain()) continue;
            $context['friends'][] = $unit;
            $context['byId'][(int)$unit->id] = $unit;
            $context['near'][(int)$unit->id] = Mathlib::getDistanceHex($unit, $ship) <= $blanket;
        }
        foreach ($ships as $unit) {
            if ((int)$unit->id === (int)$ship->id || $unit instanceof FighterFlight || !empty($unit->mine) || !$unit->isTargetableBy()) continue;
            if (HangarOps::bayCarrierAttachedTo($unit, $view->gamedata) !== null) continue;
            $d = $from->distanceTo($at($unit));
            if ($d <= $range30) $context['soew'][] = $unit;
            if ($d <= $range50) $context['sdew'][] = $unit;
        }
        foreach ($enemies as $enemy) {
            if ($enemy->isElint()) $context['enemyElint'] = true;
            if (!empty($enemy->mine) || $from->distanceTo($enemy->getHexPos()) > $range30) continue;
            if (!$advanced && $enemy->hasSpecialAbility('AdvancedSensors')) continue;
            if ($enemy instanceof FighterFlight) {
                if (!empty($enemy->remoteControl) && $view->skill('elintJam')) $context['jam'][] = $enemy;
                continue;
            }
            if ($view->skill('elintDisrupt')) $context['dist'][] = $enemy;
        }
        return $context;
    }

    /* The SOEW packages (the user: "hard-locking an opponent with at least 6 OEW and then sharing this lock using SOEW with around 1-2
       allies who also plan to target that specific enemy"): array(enemy id, list of friend ids) - each enemy SHIP the ELINT may lock
       (not within close range, where its lock stays soft; none at all for a turtling missile ship) with one or two of the SOEW friends
       that hold OEW on it in their own rows (stood on them). */
    private static function packages(array $state, array $lockable, array $close, $turtle)
    {
        $out = array();
        if ($turtle) return $out;
        $turn = $state['view']->turn;
        foreach ($lockable as $enemy) {
            if ($enemy instanceof FighterFlight || !empty($enemy->mine) || isset($close[(int)$enemy->id])) continue;
            $lockers = array();
            foreach ($state['elint']['soew'] as $friend) {
                foreach ($friend->EW as $entry) {
                    if ((int)$entry->turn === (int)$turn && $entry->type === 'OEW' && (int)$entry->targetid === (int)$enemy->id && (int)$entry->amount > 0) {
                        $lockers[] = (int)$friend->id;
                        break;
                    }
                }
            }
            foreach ($lockers as $i => $a) {
                if (AIConfig::EW_SOEW_FRIENDS >= 1) $out[] = array((int)$enemy->id, array($a));
                if (AIConfig::EW_SOEW_FRIENDS < 2) continue;
                for ($j = $i + 1; $j < count($lockers); $j++) $out[] = array((int)$enemy->id, array($a, $lockers[$j]));
            }
        }
        return $out;
    }

    /* The search (allocate()'s): a step at a time, the one that gains most for each point it moves, while that is at least EW_MIN_GAIN a
       point. Every step is one point unless an enemy ELINT disrupts the ship (lockStep) - then a step may also move up to $step points
       onto one enemy at once, its gain counted per point, so it is taken only when it pays better than the single points beside it.
       $ccewLimit: the most CCEW (null: none). G2 - $mode 'blanket' / 'support' adds an ELINT's moves (elintMoves); a blanket point keeps
       only the page's floor ($pageMinDew). Returns array(split, score). */
    private static function climb(array &$state, array $alloc, $current, array $lockable, array $caps, $step, $minDew, $ccewLimit, $mode = null, $pageMinDew = 0)
    {
        $floor = ($mode === 'blanket') ? min($minDew, $pageMinDew) : $minDew;
        while ($alloc['DEW'] > $floor) {
            $best = null;
            $bestTotal = null;
            $bestScore = $current + AIConfig::EW_MIN_GAIN; //what a 1-point step must beat - exactly the rule before game 5826
            $bestRate = AIConfig::EW_MIN_GAIN;            //...and a step of more points, a point at a time
            $list = array();
            if ($alloc['DEW'] > $minDew) {
                $ccewRoom = ($ccewLimit !== null) ? $ccewLimit - $alloc['CCEW'] : 0;
                $list = self::moves($alloc, $lockable, $ccewRoom, $caps, min($step, $alloc['DEW'] - $minDew));
            }
            if ($mode !== null) $list = array_merge($list, self::elintMoves($state, $alloc, $mode, $minDew, $pageMinDew));
            foreach ($list as $try) {
                $score = self::score($state, $try);
                $spent = $alloc['DEW'] - $try['DEW'];
                $rate = ($score - $current) / $spent;
                if ($spent == 1 ? $score > $bestScore : $rate > $bestRate) {
                    $best = $try;
                    $bestTotal = $score;
                    $bestRate = $rate;
                    $bestScore = ($spent == 1) ? $score : $current + $rate;
                }
            }
            if ($best === null) break;
            //an SOEW package's lock is a hard one (the user's "at least 6"): the soft and medium-range caps on it are lifted from here on
            if (isset($best['lift'])) {
                unset($caps[$best['lift']]);
                unset($best['lift']);
            }
            $alloc = $best;
            $current = $bestTotal;
        }
        return array($alloc, $current);
    }

    /* An ELINT's moves from $alloc (G2), each one point of DEW (an SOEW package or a DIST step: several) into an ELINT function:
         blanket  BDEW +1, down to the page's own floor
         support  SDEW +1 on ONE friend (the user: "more efficient on a single valuable target"); the SOEW package while no SOEW stands,
                  then SOEW on one more friend up to EW_SOEW_FRIENDS; at Hard DIST + a step on an enemy ship and JAM +1 on an HK flight */
    private static function elintMoves(array $state, array $alloc, $mode, $minDew, $pageMinDew)
    {
        $e = $state['elint'];
        $list = array();
        if ($mode === 'blanket') {
            if ($alloc['DEW'] > $pageMinDew) {
                $try = $alloc;
                $try['DEW']--;
                $try['BDEW']++;
                $list[] = $try;
            }
            return $list;
        }
        $room = $alloc['DEW'] - $minDew;
        if ($room < 1) return $list;

        $sdewOn = null;
        foreach ($alloc['SDEW'] as $id => $amount) if ($amount > 0) $sdewOn = $id;
        foreach ($e['sdew'] as $friend) {
            if ($sdewOn !== null && (int)$friend->id !== $sdewOn) continue;
            if (isset($alloc['SDEW'][(int)$friend->id]) && $alloc['SDEW'][(int)$friend->id] >= AIConfig::EW_SDEW_MAX) continue; //"typically 5-6 EW"
            $try = $alloc;
            $try['DEW']--;
            $try['SDEW'][(int)$friend->id] = (isset($alloc['SDEW'][(int)$friend->id]) ? $alloc['SDEW'][(int)$friend->id] : 0) + 1;
            $list[] = $try;
        }

        if (count($alloc['SOEW']) == 0) {
            foreach ($e['packages'] as $package) {
                list($enemyId, $friends) = $package;
                $has = isset($alloc['OEW'][$enemyId]) ? $alloc['OEW'][$enemyId] : 0;
                $need = max(0, AIConfig::EW_SOEW_LOCK - $has) + count($friends);
                if ($need > $room) continue;
                $try = $alloc;
                $try['DEW'] -= $need;
                $try['OEW'][$enemyId] = max($has, AIConfig::EW_SOEW_LOCK);
                foreach ($friends as $id) $try['SOEW'][$id] = 1;
                $try['lift'] = $enemyId;
                $list[] = $try;
            }
        } else if (count($alloc['SOEW']) < AIConfig::EW_SOEW_FRIENDS) {
            foreach ($e['soew'] as $friend) {
                if (isset($alloc['SOEW'][(int)$friend->id])) continue;
                $try = $alloc;
                $try['DEW']--;
                $try['SOEW'][(int)$friend->id] = 1;
                $list[] = $try;
            }
        }

        if ($e['distStep'] <= $room) {
            foreach ($e['dist'] as $enemy) {
                $try = $alloc;
                $try['DEW'] -= $e['distStep'];
                $try['DIST'][(int)$enemy->id] = (isset($alloc['DIST'][(int)$enemy->id]) ? $alloc['DIST'][(int)$enemy->id] : 0) + $e['distStep'];
                $list[] = $try;
            }
        }
        foreach ($e['jam'] as $flight) {
            $try = $alloc;
            $try['DEW']--;
            $try['JAM'][(int)$flight->id] = (isset($alloc['JAM'][(int)$flight->id]) ? $alloc['JAM'][(int)$flight->id] : 0) + 1;
            $list[] = $try;
        }
        return $list;
    }

    //G2: a split's ELINT rows as array(type, amount, target id) - BDEW, SOEW, SDEW, DIST, JAM, Detect Stealth; none on a plain split
    private static function elintRows(array $alloc)
    {
        $rows = array();
        if (!isset($alloc['BDEW'])) return $rows;
        if ($alloc['BDEW'] > 0) $rows[] = array('BDEW', $alloc['BDEW'], -1);
        foreach (array('SOEW', 'SDEW', 'DIST', 'JAM') as $type) {
            foreach ($alloc[$type] as $id => $amount) if ($amount > 0) $rows[] = array($type, $amount, $id);
        }
        if ($alloc['DS'] > 0) $rows[] = array('Detect Stealth', $alloc['DS'], -1);
        return $rows;
    }

    //G2: the DIST part of a split as a cache key
    private static function distKey(array $alloc)
    {
        if (empty($alloc['DIST'])) return '';
        $dist = $alloc['DIST'];
        ksort($dist);
        $parts = array();
        foreach ($dist as $id => $amount) if ($amount > 0) $parts[] = $id . '=' . $amount;
        return implode(',', $parts);
    }

    //G2: $unit's rows of earlier turns ($kept) and the rows just decided for it (AIOrderBuilder::ew shape), stood on it
    private static function stand($unit, array $kept, array $rows)
    {
        $unit->EW = $kept;
        foreach ($rows as $row) $unit->EW[] = new EWentry(-1, $unit->id, $row['turn'], $row['type'], $row['amount'], $row['targetid']);
    }

    /* G2: what the enemy's guns should do to the bot's other units at the split tried - each gun by the share of its fire that unit would
       draw among the bot's units, as the ELINT's own threats are counted; the units at their decided DEW (stood on them). One answer per
       unit and gun is kept, keyed by what of the split can change it: the blanket (within its reach), SDEW on that unit, and DIST on the
       gun's ship - on every enemy when the enemy has an ELINT of its own (DIST on it cuts the SOEW it gives). */
    private static function fleetDefence(array &$state, array $alloc)
    {
        $view = $state['view'];
        $e = &$state['elint'];
        if ($e['threats'] === null) {
            $e['threats'] = array();
            $ship = $state['ship'];
            $standing = $ship->EW;
            $ship->EW = $state['base'];
            foreach ($e['friends'] as $friend) {
                $list = array();
                foreach ($state['enemies'] as $enemy) {
                    if (!empty($enemy->mine)) continue;
                    foreach ($view->mounts($enemy) as $mount) {
                        if (!$view->plainGun($mount['weapon'], $mount['fighter']) || !$view->loaded($mount['weapon'])) continue;
                        $here = $view->estimate($enemy, $mount['weapon'], $friend, false);
                        if ($here <= 0) continue;
                        $all = 0;
                        foreach ($state['units'] as $unit) $all += ((int)$unit->id === (int)$friend->id) ? $here : $view->estimate($enemy, $mount['weapon'], $unit, false);
                        $list[] = array($enemy, $mount['weapon'], $all > 0 ? $here / $all : 1);
                    }
                }
                $e['threats'][(int)$friend->id] = $list;
            }
            $ship->EW = $standing;
        }

        $fullDist = $e['enemyElint'] ? self::distKey($alloc) : null;
        $taken = 0;
        foreach ($e['friends'] as $friend) {
            $id = (int)$friend->id;
            $bdew = $e['near'][$id] ? $alloc['BDEW'] : 0;
            $sdew = isset($alloc['SDEW'][$id]) ? $alloc['SDEW'][$id] : 0;
            foreach ($e['threats'][$id] as $k => $threat) {
                $dist = ($fullDist !== null) ? $fullDist : (isset($alloc['DIST'][(int)$threat[0]->id]) ? $alloc['DIST'][(int)$threat[0]->id] : 0);
                $key = $id . ':' . $k . ':' . $bdew . ':' . $sdew . ':' . $dist;
                if (!isset($e['taken'][$key])) $e['taken'][$key] = $view->estimate($threat[0], $threat[1], $friend, false) * $threat[2];
                $taken += $e['taken'][$key];
            }
        }
        return $taken;
    }

    /* G2: what SOEW adds to the friends it supports - each one's loaded guns, each at the enemy it does most to (an enemy that moves after
       the friend counting EW_MOVES_AFTER_WEIGHT, as the ship's own), with the support standing less without it (this ELINT's rows off).
       The engine gives SOEW only to a friend holding its own lock, so a friend's OEW is what it decided. $rows: the split standing. */
    private static function fleetOffence(array &$state, array $alloc, $locks, array $rows)
    {
        if (empty($alloc['SOEW'])) return 0;
        $view = $state['view'];
        $ship = $state['ship'];
        $e = &$state['elint'];
        $oew = $alloc['OEW'];
        ksort($oew);
        $oewKey = json_encode($oew) . ':' . $locks . ':' . self::distKey($alloc);
        $gain = 0;
        foreach ($alloc['SOEW'] as $id => $one) {
            if ($one <= 0 || !isset($e['byId'][$id])) continue;
            $friend = $e['byId'][$id];
            if (!isset($e['fireBase'][$id])) {
                $ship->EW = $state['base'];
                $e['fireBase'][$id] = self::friendFire($state, $friend);
                $ship->EW = $rows;
            }
            $key = $id . ':' . $oewKey;
            if (!isset($e['fire'][$key])) $e['fire'][$key] = self::friendFire($state, $friend);
            $gain += $e['fire'][$key] - $e['fireBase'][$id];
        }
        return $gain;
    }

    //G2: a friend's loaded guns, each at the enemy it does most to, arcs not asked (as score() counts the ship's own)
    private static function friendFire(array &$state, $friend)
    {
        $view = $state['view'];
        $e = &$state['elint'];
        $id = (int)$friend->id;
        if (!isset($e['guns'][$id])) {
            $e['guns'][$id] = array();
            foreach ($view->mounts($friend) as $mount) {
                $weapon = $mount['weapon'];
                if ($mount['fighter'] !== null || !$view->plainGun($weapon) || $weapon->isOfflineOnTurn($view->turn) || !$view->loaded($weapon)) continue;
                $e['guns'][$id][] = $weapon;
            }
        }
        $fire = 0;
        foreach ($e['guns'][$id] as $weapon) {
            $best = 0;
            foreach ($state['enemies'] as $enemy) {
                $soft = empty($enemy->base) && empty($enemy->smallBase) && empty($enemy->osat) && empty($enemy->mine) && $enemy->iniative >= $friend->iniative;
                $best = max($best, $view->estimate($friend, $weapon, $enemy, false) * ($soft ? AIConfig::EW_MOVES_AFTER_WEIGHT : 1));
            }
            $fire += $best;
        }
        return $fire;
    }

    /* G2 (Hard): what JAM should take off the enemy's Hunter-Killers (HkJamming: d20 + 1 for each point past the first, in the Critical
       phase after this turn's fire - 19+ Control Lost next turn, 21 one craft drops out, 22+ two): for each jammed flight, the chance of
       Control Lost x its ram threat next turn, plus the craft expected to drop out x one craft's threat. */
    private static function jamWorth(array &$state, array $alloc)
    {
        if (empty($alloc['JAM'])) return 0;
        $worth = 0;
        foreach ($alloc['JAM'] as $id => $points) {
            if ($points <= 0) continue;
            $flight = null;
            foreach ($state['elint']['jam'] as $unit) if ((int)$unit->id === (int)$id) $flight = $unit;
            if ($flight === null) continue;
            list($craft, $each) = self::jamThreat($state, $flight);
            $lost = min(1, ($points + 1) / 20);
            $dropped = min(1, max(0, $points - 1) / 20) + min(1, max(0, $points - 2) / 20);
            $worth += $lost * $craft * $each + min($craft, $dropped) * $each;
        }
        return $worth;
    }

    /* G2: an HK flight's ram threat, array(live craft, one craft's expected ram damage): the best of its ram (loaded, its RammingAttack
       priced by the engine's own formula from the bot ship's hex) at a bot ship it could reach by the end of next turn - twice its
       greatest move and POWER_REACH_SLACK - but not THIS turn: jamming lands in the Critical phase, after this turn's rams, and a
       Hunter-Killer is spent by its ram - so one already within reach of a bot ship is taken to ram now, and JAM buys nothing on it.
       0 when it has no ram ready. */
    private static function jamThreat(array &$state, $flight)
    {
        $e = &$state['elint'];
        $id = (int)$flight->id;
        if (isset($e['jamThreat'][$id])) return $e['jamThreat'][$id];
        $view = $state['view'];
        $turn = $view->turn;
        $ram = null;
        foreach ($flight->systems as $fighter) {
            if (!($fighter instanceof Fighter) || $fighter->isDestroyed()) continue;
            foreach ($fighter->systems as $system) {
                if ($system instanceof RammingAttack && $system->designedToRam && !$system->isDestroyed() && $view->loaded($system)) $ram = $system;
            }
            if ($ram !== null) break;
        }
        $craft = (int)$flight->countActiveCraft($turn);
        $each = 0;
        $last = $flight->getLastMovement();
        if ($ram !== null && $craft > 0 && $last) {
            $now = PowerDecider::maxMove($flight) + AIConfig::POWER_REACH_SLACK;
            $reach = 2 * PowerDecider::maxMove($flight) + AIConfig::POWER_REACH_SLACK;
            $targets = array();
            foreach ($state['units'] as $unit) {
                if ($unit instanceof FighterFlight || !empty($unit->mine) || $unit->isDestroyed()) continue;
                $d = $flight->getHexPos()->distanceTo($unit->getHexPos());
                if ($d <= $now) return $e['jamThreat'][$id] = array($craft, 0); //it rams this turn, before the jamming roll
                if ($d <= $reach) $targets[] = $unit;
            }
            foreach ($targets as $unit) {
                $flight->movement[] = new MovementOrder(-1, 'move', new OffsetCoordinate($unit->getHexPos()), 0, 0, $last->speed, $last->heading, $last->facing, false, $turn, 0, 0);
                try {
                    $each = max($each, $view->estimate($flight, $ram, $unit, false));
                } finally {
                    array_pop($flight->movement);
                }
            }
        }
        return $e['jamThreat'][$id] = array($craft, $each);
    }

    //G2: is there an enemy stealth unit the bot cannot see now (hideStealthShipMovement stands an undetected one off the map, at -10000)?
    private static function unseenStealth(AIView $view)
    {
        foreach ($view->gamedata->ships as $unit) {
            if ($unit->team == $view->team || empty($unit->trueStealth) || !empty($unit->mine) || $unit->isDestroyed()) continue;
            $last = $unit->getLastMovement();
            if ($last && (int)$last->position->q <= -9999) return true;
        }
        return false;
    }

    //G2: an ELINT's functions, for the Initial Orders log line ('' when it used none)
    private static function elintText(AIView $view, $ship, array $alloc)
    {
        $parts = array();
        $name = function ($id) use ($view) {
            $unit = $view->gamedata->getShipById((int)$id);
            return $unit ? $unit->name : (string)$id;
        };
        foreach (self::elintRows($alloc) as $row) {
            $parts[] = $row[0] . ' ' . $row[1] . ($row[2] > 0 ? ' ' . $name($row[2]) : '');
        }
        if (count($parts) == 0) return '';
        if (count($alloc['SOEW']) > 0) {
            foreach ($alloc['OEW'] as $id => $amount) if ($amount >= AIConfig::EW_SOEW_LOCK) $parts[] = 'shared lock OEW ' . $amount . ' ' . $name($id);
        }
        return $ship->name . ': ' . implode(', ', $parts);
    }

    /* The EW points the page gives this ship (ew.getScannerOutput): 0 for a ship adrift, else
       what its sensors put out now, less 2 for each Restricted EW and 1 for each "sensors down" on
       its primary C&C. ⚠️ Not EW::getScannerOutput: the two differ (the server calls a base or an
       OSAT with a disabled C&C sensorless, the page does not; "sensors down" the server counts too since
       2026-11-16), nothing on the server holds a commit to its own figure, and the row a
       player's page writes is the page's. */
    public static function points($ship, $turn)
    {
        if (empty($ship->osat) && empty($ship->base)) {
            foreach ($ship->systems as $system) {
                if (PowerDecider::crits($system, 'ShipDisabledOneTurn', $turn) > 0 || PowerDecider::crits($system, 'ShipDisabled', $turn) > 0) return 0;
            }
            $cnc = self::firstNamed($ship, 'cnC');
            if ($cnc && $cnc->isDestroyed()) return 0;
        }

        $output = 0;
        $primary = null;
        $bsgHybrid = null;
        foreach ($ship->systems as $system) {
            if ($system->outputType === 'EW') $output += max(0, (int)$system->getOutput($turn));
            //shipManager.getPrimaryCnC: the C&C with least left of it
            if ($system->displayName === 'C&C' && ($primary === null || $system->getRemainingHealth() < $primary->getRemainingHealth())) $primary = $system;
            if ($bsgHybrid === null && $system->name === 'BSGHybrid') $bsgHybrid = $system; //the page's getSystemByName: the first by name
        }
        if ($primary) $output -= 2 * PowerDecider::crits($primary, 'RestrictedEW', $turn) + PowerDecider::crits($primary, 'tmpsensordown', $turn);
        //a BSG hybrid's Sensor Loss, -3 each - the page subtracts it since 2026-11-16 (ew.js; it never did before), as the server does
        if ($bsgHybrid) $output -= 3 * PowerDecider::crits($bsgHybrid, 'SensorLoss', $turn);

        return max(0, $output);
    }

    //A unit one turn straight on from where it stands, as a movement row - or null for one that will not move.
    private static function ahead($unit, $turn)
    {
        return AIView::straightOn($unit, $turn);
    }

    //shipManager.systems.getSystemByName: the first system of that name.
    private static function firstNamed($ship, $name)
    {
        foreach ($ship->systems as $system) {
            if ($system->name === $name) return $system;
        }
        return null;
    }
}
