<?php
/**
 * CPU opponent - Firing (TACTICAL_AI_PLAN.md section 5.6, stage T2).
 *
 * Every loaded direct-fire weapon the bot understands is offered every enemy it can legally hit
 * (AIView - the engagement table), and the shots are handed out GREEDILY: the best shot on the
 * board is taken, the damage it should do is booked against its target, and the rest are judged
 * again. What makes a shot good, in named terms (principle P7):
 *
 *   worth   the share of the target this volley should remove, times what the target is worth -
 *           so a cheap kill on a dear ship beats the same damage soaked by a fresh one;
 *   focus   a target already booked for damage draws the next gun (AIConfig::FIRE_FOCUS), until
 *           the plan would kill it FIRE_OVERKILL times over - then further fire counts for nothing
 *           there and the remaining guns look elsewhere. A HEAVY gun is drawn far harder
 *           (FIRE_FOCUS_HEAVY; user, 2026-11-19: concentrate the fleet's heavy firepower to take a
 *           ship down rather than spread it), light guns stay free for fighters and the rest;
 *   threat  the more an enemy can do to this fleet, the sooner it is shot (FIRE_THREAT_WEIGHT).
 *
 * SLOW WEAPONS (the user's feedback, 2026-11-15). A shot costs a weapon its reload, so a slow one
 * fires only at FIRE_MIN_CHANCE_BY_LOAD or better (minimumChance) - a slow PULSE weapon
 * FIRE_PULSE_PREMIUM higher still (2026-11-19: its damage grows much faster than its chance) - and
 * waits when a turn closer is clearly better (holdSlow: the real formula again where both units
 * will be, range penalty and plasma range damage included). A target drawing away lowers the floor
 * to FIRE_MIN_CHANCE_PARTING - by loading time too since stage T6a (game 4774: never under 34% for a
 * 3- or 4-turn gun).
 *
 * ACCELERATORS (plan 10.10 G1, 2026-11-27 - holdCharge): a weapon that goes on charging after it can fire waits for the charge that
 * pays, as players fire them - unless it does more a turn firing on the move (the Pulse Accelerator), it is its last shot, it finishes
 * its target off or the target is leaving. A charged one spends its charge: its floors are those of the turns it took.
 *
 * THE ENEMY'S INTERCEPTION (InterceptModel). A shot is worth what it adds to its target's volley
 * once the target's interceptors have been given out as the server gives them - so the bot neither
 * pretends there is none nor assumes every shot is intercepted, and a slow gun's floor holds on the
 * chance it keeps after interception.
 *
 * THE INTERCEPTION RESERVE - its own. A gun with an intercept rating that is given no order is used
 * by the server to intercept (Firing::automateIntercept), so holding one back is simply not ordering it.
 * Since stage T6a (the user's notes on game 4774, holdBack): a gun with an offensive shot of
 * FIRE_INTERCEPTOR_MIN_CHANCE (15%) or better always fires; one under it stays on defence only while
 * interceptable enemy fire can reach its unit (a laser cannot be intercepted) - one for each such order
 * it could stop from its arc, the unit's interceptors with no offensive shot at all covering first.
 *
 * Ballistics are BallisticDecider's (Initial Orders). NOT YET: called shots, and every weapon
 * AIView::weaponBlock turns away.
 *
 * The decision is made on the bot's own MASKED view of the game (principle P2).
 */
class FireDecider
{
    //One line about the last plan made, for tac_ai_log.
    public static $lastSummary = '';

    //The bot's Firing entries, one per unit it owns. $seat is its DBManager::getCpuSeats row.
    public static function orders(TacGamedata $gamedata, $botId, array $seat)
    {
        $gamedata->prepareForPlayer(); //the mask a human in this seat plays behind; also fills what the page reads

        $view = new AIView($gamedata, $botId, AIConfig::seatLevel($seat), AIConfig::seatSeed($seat));
        $entries = self::entries($view, self::plan($view));

        /* Stage T9, M2: the flights that started the battle in a hangar (the Orieni Hunter-Killers) are launched, as far as the bays'
           launch rates allow (HangarDecider) - a Fire-phase order, resolved at the end of the turn. */
        $launches = HangarDecider::launches($view);
        if (count($launches) > 0) {
            foreach ($entries as $index => $entry) {
                if (!isset($launches[(int)$entry['id']])) continue;
                foreach ($launches[(int)$entry['id']] as $hangarId => $system) $entries[$index]['systems'][$hangarId] = $system;
            }
        }
        $line = HangarDecider::describeLaunches();
        if ($line !== '') self::$lastSummary .= '; ' . $line;
        return $entries;
    }

    //A plan as the entries AIOrderBuilder describes: every unit of the bot, with its fire orders.
    public static function entries(AIView $view, array $plan)
    {
        $gamedata = $view->gamedata;
        $entries = array();
        foreach (AIFallback::ownUnits($gamedata, $view->botId) as $unit) {
            $entry = AIOrderBuilder::entry($unit);

            foreach ($plan as $shot) {
                if ($shot['unit']->id !== $unit->id) continue;

                $rows = array();
                for ($gun = 0; $gun < $shot['orders']; $gun++) {
                    $rows[] = AIOrderBuilder::fire($unit->id, $shot['target']->id, $shot['weapon']->id, $gamedata->turn, $shot['mode'], $shot['shots'], $shot['damageclass'],
                        'normal', !empty($shot['split']) ? 'Split' : null); //(stage T9) a split order carries the page's note
                }

                //(stage T9) a weapon Hard splits has a row per target: its orders go in together, after one another
                if ($shot['fighter'] !== null) {
                    $fighterId = $shot['fighter']->id;
                    if (!isset($entry['systems'][$fighterId])) $entry['systems'][$fighterId] = array('id' => $fighterId, 'systems' => array());
                    $at = null;
                    foreach ($entry['systems'][$fighterId]['systems'] as $i => $sub) {
                        if ($sub['id'] === $shot['weapon']->id) $at = $i;
                    }
                    if ($at === null) $entry['systems'][$fighterId]['systems'][] = array('id' => $shot['weapon']->id, 'fireOrders' => $rows);
                    else $entry['systems'][$fighterId]['systems'][$at]['fireOrders'] = array_merge($entry['systems'][$fighterId]['systems'][$at]['fireOrders'], $rows);
                } else if (isset($entry['systems'][$shot['weapon']->id])) {
                    $entry['systems'][$shot['weapon']->id]['fireOrders'] = array_merge($entry['systems'][$shot['weapon']->id]['fireOrders'], $rows);
                } else {
                    $entry['systems'][$shot['weapon']->id] = array('id' => $shot['weapon']->id, 'fireOrders' => $rows);
                }
            }

            $entries[] = $entry;
        }

        return $entries;
    }

    //Set while plan() prices Hard's plan WITHOUT its split (stage T9 - see the end of plan()).
    private static $wholeOnly = false;

    /* The plan: a list of array('unit', 'weapon', 'fighter', 'target', 'mode', 'chance', 'orders',
       'shots', 'damageclass', 'damage', 'score'). One row per weapon that fires - or, for a weapon Hard splits (stage T9), one per
       target it sends guns to, each marked 'split'. */
    public static function plan(AIView $view)
    {
        $enemies = $view->enemies();
        $plan = array();
        self::$lastSummary = 'no target';
        if (count($enemies) == 0) return $plan;

        $maxThreat = 0;
        $state = array(); //per target: what a kill takes, what the target is worth, what is booked
        foreach ($enemies as $enemy) {
            $state[$enemy->id] = array('health' => $view->healthLeft($enemy), 'value' => $view->value($enemy), 'threat' => $view->threat($enemy), 'booked' => 0);
            $maxThreat = max($maxThreat, $state[$enemy->id]['threat']);
        }

        //Every gun's legal shots, best mode against each target.
        $guns = array();
        $held = 0;
        $lastStand = Posture::lastStand($view); //stage T6g: a flight that must fire this turn keeps no gun back to intercept
        foreach ($view->myUnits() as $unit) {
            $unitGuns = array();
            $mounts = $view->weapons($unit);
            foreach ($mounts as $mount) {
                $weapon = $mount['weapon'];
                $options = array();
                $minimum = self::minimumChance($weapon, true, $view->skill('pulsePremium')); //the least it is ever fired at; holdSlow() asks for more
                $modes = $view->modes($weapon);
                $shipsOnly = self::shipsOnly($mount, $mounts);

                foreach ($enemies as $enemy) {
                    if ($shipsOnly && $enemy instanceof FighterFlight) continue;
                    $best = null;
                    foreach ($modes as $mode) {
                        $shot = $view->shot($unit, $weapon, $enemy, $mode);
                        if ($shot === null || $shot['chance'] < $minimum) continue;
                        if ($shot['edge'] && $shot['chance'] < AIConfig::FIRE_MIN_CHANCE_EDGE) continue;
                        if ($best === null || $shot['damage'] > $best['damage']) $best = $shot;
                    }
                    if ($best !== null) $options[$enemy->id] = $best;
                }

                $intercept = self::interceptRating($weapon);
                $unitGuns[] = array('unit' => $unit, 'weapon' => $weapon, 'fighter' => $mount['fighter'], 'options' => $options, 'intercept' => $intercept,
                    'heavy' => self::isHeavy($weapon) && $view->skill('heavyFocus')); //Easy draws its heavy guns as light ones (stage T5f)
            }

            if (!($lastStand && isset(Posture::$mustShoot[(int)$unit->id]))) $held += self::holdBack($view, $unit, $unitGuns);
            foreach ($unitGuns as $gun) {
                if (count($gun['options']) > 0) $guns[] = $gun;
            }
        }

        $charging = self::holdCharge($view, $guns, $lastStand); //G1: accelerators that keep charging (before holdSlow, which reads their marks)
        $kept = self::holdSlow($view, $guns);
        //stage T9 (E1): Hard offers each gun of a split-shot weapon to the greedy pick on its own (the skill 'splitShots')
        $splitWeapons = ($view->skill('splitShots') && !self::$wholeOnly) ? self::splitGuns($view, $guns) : 0;

        /* INTERCEPTION (InterceptModel; the user's feedback, 2026-11-15). Each target's volley is
           valued as the server will leave it once that target's interceptors have taken their pick:
           a shot is worth what it ADDS to its target's volley - which is less than its own damage
           while the target has interceptors to spare, and can be more, when it draws them off a
           shot already planned. The bot's own missiles of this turn are in the volleys from the
           start: they arrive now, and the interceptors that go to them do not go to the guns. */
        $model = new InterceptModel($view);
        $volleys = array();
        foreach ($enemies as $enemy) $volleys[$enemy->id] = array('entries' => array(), 'value' => 0.0);
        foreach (self::ownMissiles($view, $state) as $targetId => $entries) {
            $volleys[$targetId]['entries'] = $entries;
            $volleys[$targetId]['value'] = $model->value($view->gamedata->getShipById($targetId), $entries);
            $state[$targetId]['booked'] += $volleys[$targetId]['value'];
        }

        //Greedy: take the best shot left, book it, judge the rest again.
        $added = array(); //"gun:target" => what the shot adds to its target's volley; forgotten when that volley changes
        $swallowed = array(); //slow guns turned away at some point because the interceptors would have taken too much
        while (count($guns) > 0) {
            $bestScore = 0;
            $bestGun = null;
            $bestTarget = null;
            $bestAdds = 0;

            foreach ($guns as $index => $gun) {
                foreach ($gun['options'] as $targetId => $shot) {
                    $key = $index . ':' . $targetId;
                    if (!isset($added[$key])) $added[$key] = self::adds($model, $view, $volleys[$targetId], $targetId, $gun, $shot);
                    $adds = $added[$key];
                    if ($shot['damage'] <= 0) continue;
                    /* A slow weapon's floor holds on the chance the shot keeps once the interceptors have
                       had it, not before: a reload spent on a shot they will swallow is a reload wasted.
                       A one-turn gun loses nothing by firing, and fires for whatever it adds. */
                    if (isset($shot['floor']) && $shot['chance'] * $adds / $shot['damage'] < $shot['floor']) {
                        $swallowed[$index] = true;
                        continue;
                    }

                    $score = self::score(array('damage' => $adds) + $shot, $state[$targetId], $maxThreat, $gun['heavy'], (float)$view->skill('focus'));
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestGun = $index;
                        $bestTarget = $targetId;
                        $bestAdds = $adds;
                    }
                }
            }

            if ($bestGun === null) break; //nothing left is worth an order: everything in reach is already dead on paper

            $gun = $guns[$bestGun];
            $shot = $gun['options'][$bestTarget];
            $volleys[$bestTarget]['entries'][] = array('unit' => $gun['unit'], 'weapon' => $gun['weapon'], 'shot' => $shot, 'ballistic' => false);
            $volleys[$bestTarget]['value'] += $bestAdds;
            $state[$bestTarget]['booked'] += $bestAdds;
            foreach (array_keys($added) as $key) {
                if (substr($key, strpos($key, ':') + 1) == $bestTarget) unset($added[$key]);
            }
            $plan[] = array(
                'unit' => $gun['unit'], 'weapon' => $gun['weapon'], 'fighter' => $gun['fighter'],
                'target' => $view->gamedata->getShipById($bestTarget),
                'mode' => $shot['mode'], 'chance' => $shot['chance'], 'orders' => $shot['orders'], 'shots' => $shot['shots'],
                'damageclass' => $shot['damageclass'], 'damage' => $bestAdds, 'score' => $bestScore
            ) + (isset($gun['split']) ? array('split' => $gun['split']) : array());
            unset($guns[$bestGun]);
            self::dropConflicts($guns, $gun);
        }

        /* Stage T6g: a flight that must fire this turn (the side down to its fighters, Posture::$mustShoot) fires every gun it has a
           shot for, at the target it would do most to - into the interceptors too, which the greedy pick above declines (a shot they
           swallow adds nothing): the turn must not go by without a shot. Its guns were kept back for none above either. */
        $forced = 0;
        if ($lastStand && count(Posture::$mustShoot) > 0) {
            foreach (array_keys($guns) as $index) {
                if (!isset($guns[$index])) continue; //an exclusive weapon of its craft took its place (dropConflicts)
                $gun = $guns[$index];
                if (!isset(Posture::$mustShoot[(int)$gun['unit']->id])) continue;
                $pick = null;
                foreach ($gun['options'] as $targetId => $shot) {
                    if ($pick === null || $shot['damage'] > $gun['options'][$pick]['damage']) $pick = $targetId;
                }
                if ($pick === null) continue;
                $shot = $gun['options'][$pick];
                $plan[] = array(
                    'unit' => $gun['unit'], 'weapon' => $gun['weapon'], 'fighter' => $gun['fighter'],
                    'target' => $view->gamedata->getShipById($pick),
                    'mode' => $shot['mode'], 'chance' => $shot['chance'], 'orders' => $shot['orders'], 'shots' => $shot['shots'],
                    'damageclass' => $shot['damageclass'], 'damage' => 0, 'score' => 0
                ) + (isset($gun['split']) ? array('split' => $gun['split']) : array());
                unset($guns[$index]);
                self::dropConflicts($guns, $gun);
                $forced++;
            }
        }
        if ($splitWeapons > 0) $plan = self::mergeSplits($plan);

        /* Stage T6d (built at T8): LAST-RESORT RAMMING - a ship Posture::rammer lets ram (the Desperate rule; no usable weapon, or its
           primary down to RAM_PRIMARY_SHARE) rams the enemy SHIP in its own hex it does most to (Posture::ramValue: the ram's damage
           against what comes back), when that is worth anything. Its guns have fired above as any ship's do. */
        $rams = 0;
        $designedRams = 0; //stage T9: rams by units designed to ram (Posture::rammer)
        foreach ($view->myUnits() as $unit) {
            $ram = Posture::rammer($view, $unit);
            if ($ram === null) continue;
            $worth = $view->value($unit) / $view->healthLeft($unit);
            $best = null;
            foreach ($enemies as $enemy) {
                if ($enemy instanceof FighterFlight || !empty($enemy->mine) || $enemy->isTerrain()) continue;
                $value = Posture::ramValue($view, $unit, $ram, $enemy, array($state[$enemy->id]['health'], $state[$enemy->id]['value'] / $state[$enemy->id]['health'],
                    $view->healthLeft($unit), $worth, (float)$view->skill('shipDanger')));
                if ($value > 0 && ($best === null || $value > $best[1])) $best = array($enemy, $value);
            }
            if ($best === null) continue;
            $plan[] = array(
                'unit' => $unit, 'weapon' => $ram, 'fighter' => null, 'target' => $best[0],
                'mode' => 1, 'chance' => (int)round($view->chanceOf($unit, $ram, $best[0], false)), 'orders' => 1, 'shots' => 1,
                'damageclass' => strtolower((string)$ram->weaponClass), 'damage' => $best[1], 'score' => $best[1]
            );
            $rams++;
            if ($ram->designedToRam) $designedRams++;
        }

        /* Stage T9, M2: a flight DESIGNED to ram (the Orieni Hunter-Killers) rams whatever of its quarry shares its hex - every craft with
           its own Ramming Attack, an order each, all at one unit (as AutomatedMovement::createAutomatedRamOrders rams for an Uncontrolled
           one): the largest enemy ship there (Posture::ramClass), the most it does breaking a tie; a fighter flight only once the enemy has
           no ship left (the user, 2026-11-24). An Uncontrolled flight is the server's (Posture::flightRams gives it no craft). */
        $enemyShips = false;
        foreach ($enemies as $enemy) {
            if (!($enemy instanceof FighterFlight) && empty($enemy->mine) && !$enemy->isTerrain()) $enemyShips = true;
        }
        foreach ($view->myUnits() as $unit) {
            $craft = Posture::flightRams($view, $unit);
            if (count($craft) == 0) continue;
            $ram = $craft[0]['ram'];
            $best = null;
            foreach ($enemies as $enemy) {
                if (!empty($enemy->mine) || $enemy->isTerrain()) continue;
                if ($enemy instanceof FighterFlight && $enemyShips) continue;
                $value = Posture::flightRamValue($view, $unit, $ram, $enemy, count($craft), $state[$enemy->id]['health'], $state[$enemy->id]['value'] / $state[$enemy->id]['health']);
                if ($value <= 0) continue;
                $key = array(($enemy instanceof FighterFlight) ? -1 : Posture::ramClass($enemy), $value);
                if ($best === null || $key > $best[1]) $best = array($enemy, $key);
            }
            if ($best === null) continue;
            $target = $best[0];
            foreach ($craft as $one) {
                $plan[] = array(
                    'unit' => $unit, 'weapon' => $one['ram'], 'fighter' => $one['fighter'], 'target' => $target,
                    'mode' => 1, 'chance' => (int)round($view->chanceOf($unit, $one['ram'], $target, false)), 'orders' => 1, 'shots' => 1,
                    'damageclass' => strtolower((string)$one['ram']->weaponClass), 'damage' => $best[1][1] / count($craft), 'score' => $best[1][1] / count($craft)
                );
            }
            $rams++;
            $designedRams++;
        }

        //each weapon back in the mode its order is in: the volleys were valued with every candidate in turn
        foreach ($plan as $shot) {
            if ((int)$shot['weapon']->firingMode !== (int)$shot['mode']) $shot['weapon']->changeFiringMode((int)$shot['mode']);
        }

        $targets = array();
        $splitting = array(); //stage T9: the weapons ordered in their split mode (Hard)
        foreach ($plan as $shot) {
            $targets[$shot['target']->id] = true;
            if (!empty($shot['split'])) $splitting[spl_object_id($shot['weapon'])] = true;
        }
        $intercepted = 0; //slow guns left unfired because the interceptors would have swallowed their shot
        foreach (array_keys($swallowed) as $index) {
            if (isset($guns[$index])) $intercepted++;
        }
        self::$lastSummary = count($plan) . ' weapons at ' . count($targets) . ' of ' . count($enemies) . ' enemies; ' . $held . ' held to intercept'
            . ($charging > 0 ? '; ' . $charging . ' accelerators charging' : '')
            . ($kept > 0 ? '; ' . $kept . ' slow weapons kept for a better shot' : '')
            . ($intercepted > 0 ? '; ' . $intercepted . ' slow weapons not fired into the interceptors' : '')
            . ($forced > 0 ? '; ' . $forced . ' fired by flights that must fire (the side down to its fighters)' : '')
            . ($rams > 0 ? '; ' . $rams . ' RAMMING (' . ($designedRams == $rams ? 'designed to ram' : 'last resort, Desperate rule') . ')' : '')
            . (count($splitting) > 0 ? '; ' . count($splitting) . ' weapons splitting their shots' : '');

        /* Hard's split is kept only where it pays (the user: "whether there's any advantage in damage potential by splitting"): the
           same decision is made again with every weapon whole, and the split plan stands only when it is worth more (planWorth). The
           greedy pick is no optimiser - on one harness board in eight its finer pieces came out worse. */
        if ($splitWeapons > 0) {
            $summary = self::$lastSummary;
            self::$wholeOnly = true;
            try {
                $whole = self::plan($view);
            } finally {
                self::$wholeOnly = false;
            }
            if (self::planWorth($view, $whole) >= self::planWorth($view, $plan)) return $whole; //its weapons already in its modes
            foreach ($plan as $shot) {
                if ((int)$shot['weapon']->firingMode !== (int)$shot['mode']) $shot['weapon']->changeFiringMode((int)$shot['mode']);
            }
            self::$lastSummary = $summary;
        }

        return $plan;
    }

    /* Stage T9 (E9, exclusive weapons - the Sitara's Ion Bolt, the Phalan's Plasma Gun): do two guns exclude each other? On one craft (or
       one ship) a weapon marked exclusive cannot fire beside another weapon of the same kind - direct fire or ballistic - with an order
       (weaponManager.checkConflictingFireOrder). Two pieces of one split weapon never do. */
    public static function conflicts(array $a, array $b)
    {
        return $a['unit'] === $b['unit'] && self::excludes($a['weapon'], $a['fighter'], $b['weapon'], $b['fighter']);
    }

    //The same, for two weapons of one unit: each with the craft carrying it (null on a ship).
    private static function excludes(Weapon $a, $fighterA, Weapon $b, $fighterB)
    {
        if ($a === $b || $fighterA !== $fighterB) return false;
        if (!$a->exclusive && !$b->exclusive) return false;
        return (bool)$a->ballistic === (bool)$b->ballistic;
    }

    //The guns $picked now rules out (conflicts): off the list.
    private static function dropConflicts(array &$guns, array $picked)
    {
        foreach ($guns as $index => $gun) {
            if (self::conflicts($picked, $gun)) unset($guns[$index]);
        }
    }

    /* The user, 2026-11-23: "these more powerful guns will be fired at ships, and the normal paired guns will be fired at fighters or when
       the larger gun is recharging". An exclusive weapon is therefore offered no FLIGHT while its craft has another gun it could fire this
       turn instead ($mounts: the unit's usable weapons, AIView::weapons); with none (the Sitara carries the Ion Bolt alone, or the paired
       guns are lost) it takes a flight as any gun does. While it reloads it is not in $mounts, and the paired guns take every target. */
    private static function shipsOnly(array $mount, array $mounts)
    {
        if (!$mount['weapon']->exclusive) return false;
        foreach ($mounts as $other) {
            if (self::excludes($mount['weapon'], $mount['fighter'], $other['weapon'], $other['fighter'])) return true;
        }
        return false;
    }

    /* What a plan is worth (stage T9 - Hard's split against the whole weapons): each target's planned damage (its volley, interception
       counted: the rows' 'damage') up to FIRE_OVERKILL times what it has left, as score() counts it, times what a point of it is worth
       and the target's threat weight. Rams are left out (their 'damage' is a value, and both plans hold the same rams). */
    public static function planWorth(AIView $view, array $plan)
    {
        $dealt = array();
        foreach ($plan as $shot) {
            if ($shot['weapon']->isRammingAttack) continue;
            $id = (int)$shot['target']->id;
            $dealt[$id] = (isset($dealt[$id]) ? $dealt[$id] : 0) + $shot['damage'];
        }
        $maxThreat = 0;
        foreach ($view->enemies() as $enemy) $maxThreat = max($maxThreat, $view->threat($enemy));
        $worth = 0;
        foreach ($dealt as $id => $damage) {
            $target = $view->gamedata->getShipById($id);
            $health = $view->healthLeft($target);
            if ($health <= 0) continue;
            $threat = 1 + ($maxThreat > 0 ? AIConfig::FIRE_THREAT_WEIGHT * $view->threat($target) / $maxThreat : 0);
            $worth += min($damage, $health * AIConfig::FIRE_OVERKILL) / $health * $view->value($target) * $threat;
        }
        return $worth;
    }

    /* Stage T9, enabler E1 - HARD'S SPLIT (the skill 'splitShots'; the user, 2026-11-23: "Hard could consider whether there's any
       advantage in damage potential by splitting"). Every gun of a split-shot weapon the bot has a port of (AIView::splitShot) that
       has a shot this turn is replaced, in $guns (by reference), by one candidate per gun: the weapon's split mode, one order, at any
       target the whole weapon had a shot at. The greedy pick then gives each gun the target it does most for - a second gun goes
       elsewhere when its first target is dead on paper, or is not fired at all when it would add nothing. mergeSplits() puts the
       weapon back together. Returns how many weapons were split up. */
    private static function splitGuns(AIView $view, array &$guns)
    {
        $count = 0;
        foreach (array_keys($guns) as $index) {
            $gun = $guns[$index];
            $weapon = $gun['weapon'];
            if (!in_array(get_class($weapon), AIView::SPLIT_FAMILIES, true) || count($gun['options']) == 0) continue;
            $mode = null;
            foreach (array_keys($weapon->firingModes) as $m) {
                if ($view->splitsIn($weapon, (int)$m)) { $mode = (int)$m; break; }
            }
            if ($mode === null) continue;

            $options = array();
            $parts = 0;
            foreach ($gun['options'] as $targetId => $whole) {
                $shot = $view->splitShot($gun['unit'], $weapon, $view->gamedata->getShipById($targetId), $mode);
                if ($shot === null) continue;
                if (isset($whole['floor'])) $shot['floor'] = $whole['floor'];
                $options[$targetId] = $shot;
                $parts = max($parts, (int)$weapon->guns); //splitShot left the weapon in the split mode: its gun count there
            }
            if (count($options) == 0 || $parts < 2) continue;

            unset($guns[$index]);
            for ($g = 0; $g < $parts; $g++) {
                $guns[] = array('options' => $options, 'split' => array('key' => $index, 'guns' => $parts, 'whole' => $gun['options'])) + $gun;
            }
            $count++;
        }
        return $count;
    }

    /* Hard's split weapons put back together (splitGuns): a weapon whose every gun went to ONE target is ordered in its plain mode,
       exactly as Normal orders it (the page's Normal mode: one order a gun, all at that unit); any other is ordered in its split
       mode - for each target, one order for each gun sent there (the page: one click each). */
    private static function mergeSplits(array $plan)
    {
        $out = array();
        $groups = array();
        foreach ($plan as $row) {
            if (!isset($row['split'])) { $out[] = $row; continue; }
            $groups[$row['split']['key']][] = $row;
        }
        foreach ($groups as $rows) {
            $byTarget = array();
            foreach ($rows as $row) $byTarget[(int)$row['target']->id][] = $row;
            $first = $rows[0];
            if (count($byTarget) == 1 && count($rows) == $first['split']['guns']) {
                $whole = $first['split']['whole'][(int)$first['target']->id];
                $out[] = array(
                    'unit' => $first['unit'], 'weapon' => $first['weapon'], 'fighter' => $first['fighter'], 'target' => $first['target'],
                    'mode' => $whole['mode'], 'chance' => $whole['chance'], 'orders' => $whole['orders'], 'shots' => $whole['shots'],
                    'damageclass' => $whole['damageclass'], 'damage' => array_sum(array_column($rows, 'damage')), 'score' => array_sum(array_column($rows, 'score'))
                );
                continue;
            }
            foreach ($byTarget as $list) {
                $row = $list[0];
                $row['orders'] = count($list);
                $row['damage'] = array_sum(array_column($list, 'damage'));
                $row['score'] = array_sum(array_column($list, 'score'));
                $row['split'] = true;
                $out[] = $row;
            }
        }
        return $out;
    }

    //What a shot adds to its target's volley, interception counted.
    private static function adds(InterceptModel $model, AIView $view, array $volley, $targetId, array $gun, array $shot)
    {
        $entries = $volley['entries'];
        $entries[] = array('unit' => $gun['unit'], 'weapon' => $gun['weapon'], 'shot' => $shot, 'ballistic' => false);
        return $model->value($view->gamedata->getShipById($targetId), $entries) - $volley['value'];
    }

    /* The bot's own missiles arriving this turn - its ballistic orders of Initial Orders, at a unit
       it may shoot at - as volley entries by target: what each should do where its target now
       stands, priced from its launch hex (AIView::launched). */
    private static function ownMissiles(AIView $view, array $state)
    {
        $volleys = array();
        foreach ($view->myUnits() as $unit) {
            foreach ($view->mounts($unit) as $mount) {
                $weapon = $mount['weapon'];
                if (!$weapon->ballistic) continue;
                $byTarget = array(); //target id => array(mode, orders)
                foreach ($weapon->fireOrders as $fire) {
                    if ((int)$fire->turn !== $view->turn || $fire->type !== 'ballistic' || !isset($state[(int)$fire->targetid])) continue;
                    $targetId = (int)$fire->targetid;
                    if (!isset($byTarget[$targetId])) $byTarget[$targetId] = array((int)$fire->firingMode, 0);
                    $byTarget[$targetId][1]++;
                }
                foreach ($byTarget as $targetId => $launch) {
                    //(stage T9, M8: a Ballistic Torpedo launcher's orders at one unit are one volley - AIView::launched prices them together)
                    $shot = $view->launched($unit, $weapon, $view->gamedata->getShipById($targetId), $launch[0], $launch[1]);
                    if ($shot === null || $shot['chance'] < 1) continue;
                    $shot['orders'] = $launch[1]; //one order is one missile
                    $volleys[$targetId][] = array('unit' => $unit, 'weapon' => $weapon, 'shot' => $shot, 'ballistic' => true);
                }
            }
        }
        return $volleys;
    }

    /* The least hit chance (percent) a weapon is fired at. A shot costs the weapon its reload, so
       a slow weapon asks for more (AIConfig::FIRE_MIN_CHANCE_BY_LOAD). $parting: the floor for a
       slow weapon whose target is drawing away - nothing better is coming. $pulsePremium: the level's skill (stage T5f - Easy
       fires slow pulse weapons at the plain floors). $load (G1): the turns of loading the shot spends, when that is not the weapon's
       loading time - an accelerator's charge (holdCharge marks it): a Plasma Accelerator charged three turns spends three. */
    public static function minimumChance(Weapon $weapon, $parting = false, $pulsePremium = true, $load = null)
    {
        $load = ($load !== null) ? (int)$load : (int)$weapon->getLoadingTime();
        if ($load <= 1) return AIConfig::FIRE_MIN_CHANCE;
        /* a slow PULSE weapon's damage grows much faster than its chance: both its floors are higher (user ruling, 2026-11-19) - and so
           does a slow weapon whose every hit grows with the roll (stage T9, M4 - the user, 2026-11-23, on the Antimatter Converter: "Its
           damage does benefit from better hit chances though, so just add a hit chance premium like we do with Pulse weapons") */
        $premium = ($pulsePremium && ($weapon->damageType === 'Pulse' || AIView::rollScaled($weapon))) ? AIConfig::FIRE_PULSE_PREMIUM : 0;

        //the parting floors by loading time too (stage T6a, game 4774: never below the user's line for a slow gun)
        $floor = AIConfig::FIRE_MIN_CHANCE;
        foreach (($parting ? AIConfig::FIRE_MIN_CHANCE_PARTING : AIConfig::FIRE_MIN_CHANCE_BY_LOAD) as $turns => $chance) {
            if ($load >= $turns) $floor = $chance;
        }
        return $floor + $premium;
    }

    /* A HEAVY gun (user, 2026-11-19 - "concentrate a fleet's heavy firepower to try and take down an enemy ship"): one
       that takes more than a turn to load, or does FIRE_HEAVY_DAMAGE a shot in its current mode. */
    public static function isHeavy(Weapon $weapon)
    {
        if ((int)$weapon->getLoadingTime() > 1) return true;
        return ($weapon->minDamage + $weapon->maxDamage) / 2 >= AIConfig::FIRE_HEAVY_DAMAGE;
    }

    /* FIRE NOW, OR A TURN CLOSER? (user, 2026-11-15.) A weapon that loads in one turn loses
       nothing by firing, so this is only about the slow ones. Each of a slow gun's shots is
       priced a second time where shooter and target will be if both fly straight on - the real
       hit formula again, so the range penalty comes into it, and for a plasma weapon the damage
       the range takes off as well. The arc is not asked next turn: a player expects to keep his
       guns on the target he is closing with. Then, per shot:

         - next turn is enough better (AIConfig::FIRE_HOLD_CONFIDENCE and the weapon's loading
           time - a turn's wait is half a 2-turn gun's fire and a quarter of a 4-turn gun's): wait;
         - not worth waiting for, but under the weapon's floor (minimumChance): wait - unless the
           target is drawing away, when the parting floor is all that is asked.

       Only the SAME target a turn on is looked at, so a gun never waits for an enemy it cannot
       bear on. Empties the options of the guns that wait (by reference); returns how many.

       G1 (2026-11-27): "slow" is per SHOT - the turns of loading it spends (spends()): an accelerator's charge, as holdCharge marked
       it, so a Plasma Accelerator fired at its full three turns waits for a 34% shot like any 3-turn gun, while one cycling at a
       one-turn charge fires as a one-turn gun. A last shot (holdCharge) is never held: there is no next turn for it. */
    private static function holdSlow(AIView $view, array &$guns)
    {
        $slow = array();
        foreach ($guns as $index => $gun) {
            foreach ($gun['options'] as $shot) {
                if (self::spends($gun['weapon'], $shot) > 1 && empty($shot['lastShot'])) {
                    $slow[] = $index;
                    break;
                }
            }
        }
        if (count($slow) == 0) return 0;

        $gamedata = $view->gamedata;
        $price = function () use ($view, $gamedata, &$guns, $slow) {
            $worth = array();
            foreach ($slow as $index) {
                $gun = $guns[$index];
                foreach ($gun['options'] as $targetId => $shot) {
                    if ((int)$gun['weapon']->firingMode !== (int)$shot['mode']) $gun['weapon']->changeFiringMode((int)$shot['mode']);
                    $worth[$index][$targetId] = $view->estimate($gun['unit'], $gun['weapon'], $gamedata->getShipById($targetId), false);
                }
            }
            return $worth;
        };
        $now = $price();
        $next = $view->ahead($price);

        $kept = 0;
        foreach ($slow as $index) {
            $weapon = $guns[$index]['weapon'];

            foreach ($guns[$index]['options'] as $targetId => $shot) {
                $load = self::spends($weapon, $shot);
                if ($load <= 1 || !empty($shot['lastShot'])) continue; //a one-turn shot, or a last one: fired as any gun's
                $wait = AIConfig::FIRE_HOLD_CONFIDENCE * (1 - 1 / $load);
                $floor = self::minimumChance($weapon, false, $view->skill('pulsePremium'), $load);
                $a = $now[$index][$targetId];
                $b = $next[$index][$targetId];
                if ($a <= 0 && $b <= 0) $hold = ($shot['chance'] < $floor);     //nothing to compare (a shot the estimate will not price): the floor alone
                else if ($a < $b * $wait) $hold = true;                          //a turn closer is worth the wait
                else $hold = ($shot['chance'] < $floor && $b >= $a);            //a poor shot that is not getting worse
                if ($hold) unset($guns[$index]['options'][$targetId]);
                //the floor the shot was let through on, which plan() holds a slow gun to again once interception is counted
                else $guns[$index]['options'][$targetId]['floor'] = ($shot['chance'] < $floor) ? self::minimumChance($weapon, true, $view->skill('pulsePremium'), $load) : $floor;
            }

            if (count($guns[$index]['options']) == 0) {
                unset($guns[$index]);
                $kept++;
            }
        }

        return $kept;
    }

    //The turns of loading a shot spends (holdSlow, minimumChance): its weapon's loading time - an accelerator's charge where holdCharge marked it (G1).
    private static function spends(Weapon $weapon, array $shot)
    {
        return isset($shot['load']) ? max(1, (int)$shot['load']) : (int)$weapon->getLoadingTime();
    }

    //A harness override of AIConfig::FIRE_FORECAST_SECONDS (G1): null = AIConfig's, 0 = no limit (levels / fire-rules harnesses).
    public static $forecastBudget = null;

    /* PLAN 10.10 G1 - ACCELERATOR CHARGE TIMING (the user's general item, 2026-11-26; rulings 2026-11-27). An accelerator (AIView::
       accelerator - the Plasma, Particle, Pulse and Laser Accelerators, the Molecular Pulsar...) can fire once it has loaded and goes on
       charging while it does not: its shot grows with the charge (turnsloaded, up to its normal load). Until G1 the bot fired one the
       moment it could - a Plasma Accelerator every turn at 1d10+4, armour off every small hit, never 4d10+12. Now, per shot, at the
       level's skill 'chargeTiming' ('none' - Easy: on load, as before):
         1. The charge ladder where both stand (AIView::chargeValue - a copy of the weapon at each charge, the usual estimate, armour off
            every hit): it fires as any gun once it holds the charge it CYCLES at against this target (cycleCharge, on what the target's
            interceptors would leave of each charge - afterInterception - the lowest whose
            damage per turn of charging is at least AIConfig::FIRE_SUSTAINED_SHARE of the best charge's: the user, 2026-11-27, "some
            Accelerator weapons CAN be worth firing every [turn] for sustained damage (mainly the Molecular Pulsar and Pulse
            Accelerator)" - a Pulse Accelerator does more a turn on its 1-turn shot; a Molecular Pulsar at a poor chance too).
         2. Below that charge it fires anyway (the user's cases): a LAST SHOT (lastShot - its ship expected destroyed this turn, or the
            section it sits on, or the ship leaving through its jump point), FINISHING OFF its target or the section it hits
            (finishes), or a target LEAVING its reach - which the comparison below finds: no shot next turn, nothing to wait for.
         3. Otherwise the plan's rule: it waits while what a charge more does NEXT turn, everybody a turn straight on (AIView::ahead,
            arcs not asked - holdSlow's convention), is worth more than FIRE_HOLD_CONFIDENCE x this turn's shot ('step' - Normal). 'full'
            (Hard) looks to every charge up to full, FIRE_HOLD_CONFIDENCE a turn, and stands a target SHIP where its own next move
            should take it (EnemyForecast::nextTurn - the user, 2026-11-27: "Plus escape forecast") with the gun's arc asked, its own ship
            a turn straight on and turned up to one hex side (turnedValue): a target whose likely move takes it out of arc or range is
            fired at now.
       Re-decided every turn, so a gun charges a step at a time. Each option is marked with the charge it spends ('load' -
       min(charge, cycling charge): holdSlow and the floors hold a Plasma Accelerator fired at full to a 3-turn gun's 34%) and a last
       shot 'lastShot' (holdSlow leaves it alone). Units of a last stand (Posture::$mustShoot) fire every gun, as before. Empties the
       options of the accelerators that charge (by reference); returns how many charge. */
    private static function holdCharge(AIView $view, array &$guns, $lastStand)
    {
        $timing = $view->skill('chargeTiming');
        if ($timing === 'none') return 0;
        $hard = ($timing === 'full');
        $gamedata = $view->gamedata;
        $lastShots = array();
        $escapes = array();   //Hard: target id => its forecast row next turn, or null (straight on)
        $hazards = null;
        $started = AIConfig::cpuSeconds();
        $budget = (self::$forecastBudget === null) ? AIConfig::FIRE_FORECAST_SECONDS : max(0, (float)self::$forecastBudget);
        $model = null;        //the targets' interceptors (InterceptModel), once an accelerator is found
        $charging = 0;

        foreach (array_keys($guns) as $index) {
            $weapon = $guns[$index]['weapon'];
            $unit = $guns[$index]['unit'];
            if (!AIView::accelerator($weapon)) continue;
            if ($lastStand && isset(Posture::$mustShoot[(int)$unit->id])) continue;
            $least = AIView::leastLoad($weapon);
            $full = max($least, (int)$weapon->getNormalLoad());
            $charge = max($least, min($full, (int)$weapon->turnsloaded));

            foreach ($guns[$index]['options'] as $targetId => $shot) {
                $target = $gamedata->getShipById($targetId);
                $ladder = array();  //charge => what the shot should do (for the comparison below, as holdSlow prices: no interception)
                for ($level = $least; $level <= $full; $level++) $ladder[$level] = $view->chargeValue($unit, $weapon, $target, $level);
                if ($model === null) $model = new InterceptModel($view);
                $cycle = self::cycleFor($view, $model, $unit, $weapon, $target);
                $guns[$index]['options'][$targetId]['load'] = min($charge, $cycle);
                if ($charge >= $cycle) continue; //charged as far as pays against this target: it fires as any gun

                if (self::lastShot($view, $unit, $weapon, $lastShots)) {
                    $guns[$index]['options'][$targetId]['lastShot'] = true;
                    continue;
                }
                if (self::finishes($view, $unit, $target, $shot, $ladder[$charge])) continue;

                $rows = array();
                $needArc = false;
                if ($hard) {
                    if (!array_key_exists($targetId, $escapes)) {
                        if ($budget > 0 && AIConfig::cpuSeconds() - $started > $budget) $escapes[$targetId] = null;
                        else {
                            if ($hazards === null) $hazards = new Hazards($view);
                            $escapes[$targetId] = EnemyForecast::nextTurn($view, $target, $hazards);
                        }
                    }
                    if ($escapes[$targetId] !== null) {
                        $rows[$targetId] = $escapes[$targetId];
                        $needArc = true;
                    }
                }
                $levels = $hard ? range($charge + 1, $full) : array($charge + 1);
                $next = $view->ahead(function () use ($view, $unit, $weapon, $target, $levels, $needArc) {
                    $values = array();
                    foreach ($levels as $level) $values[$level] = $needArc ? self::turnedValue($view, $unit, $weapon, $target, $level) : $view->chargeValue($unit, $weapon, $target, $level);
                    return $values;
                }, $rows);

                //(a target leaving its reach - out of range, or out of arc where it is forecast - is worth 0 next turn: it is fired at now)
                $worthWaiting = 0;
                foreach ($next as $level => $value) $worthWaiting = max($worthWaiting, pow(AIConfig::FIRE_HOLD_CONFIDENCE, $level - $charge) * $value);
                if ($ladder[$charge] < $worthWaiting) unset($guns[$index]['options'][$targetId]);
            }

            if (count($guns[$index]['options']) == 0) {
                unset($guns[$index]);
                $charging++;
            }
        }
        return $charging;
    }

    /* Hard's escape test (holdCharge, inside ahead()): what $weapon fired at $charge does to $target with the gun's ARC asked, $unit where
       ahead() stood it (a turn straight on) facing as it does or turned one hex side either way - the best of the three. A player keeps his
       guns on the target he closes with (holdSlow's convention); one turn's worth of it is all the bot claims. */
    private static function turnedValue(AIView $view, $unit, Weapon $weapon, $target, $charge)
    {
        $last = $unit->getLastMovement();
        if (!$last || !$last->position) return $view->chargeValue($unit, $weapon, $target, $charge, true);
        $best = 0;
        foreach (array(0, -1, 1) as $side) {
            $facing = Mathlib::addToHexFacing((int)$last->facing, $side);
            $heading = Mathlib::addToHexFacing((int)$last->heading, $side);
            $unit->movement[] = new MovementOrder(-1, 'move', new OffsetCoordinate($last->position), 0, 0, (int)$last->speed, $heading, $facing, false, $view->turn, 0, 0);
            try {
                $best = max($best, $view->chargeValue($unit, $weapon, $target, $charge, true));
            } finally {
                array_pop($unit->movement);
            }
        }
        return $best;
    }

    /* What $weapon of $unit fired at $charge should do to $target once the target's interceptors have had it, were it the only shot at
       the target (G1 - InterceptModel, every interceptor of the target that could stop it on it: the lone shot's worst case). A pulse
       weapon loses its EXTRA pulses to interception first, so against an interceptor-armed ship (an EA one) a Molecular Pulsar's
       charged shot keeps less than its 1-turn one a turn, and it fires on the move (game test, 2026-11-27: at 4 hexes from an Omega
       2.4 a turn kept against 2.1). 0 where it has no shot at that charge. */
    private static function afterInterception(AIView $view, InterceptModel $model, $unit, Weapon $weapon, $target, $charge)
    {
        $shot = $view->chargeShot($unit, $weapon, $target, $charge);
        if ($shot === null) return 0;
        return $model->value($target, array(array('unit' => $unit, 'weapon' => $view->charged($weapon, $charge), 'shot' => $shot, 'ballistic' => false)));
    }

    /* The charge accelerator $weapon of $unit CYCLES at against $target where both stand (holdCharge, G1): cycleCharge over what the
       target's interceptors would leave of each charge, from the least loading time to the normal load (afterInterception). */
    public static function cycleFor(AIView $view, InterceptModel $model, $unit, Weapon $weapon, $target)
    {
        $least = AIView::leastLoad($weapon);
        $full = max($least, (int)$weapon->getNormalLoad());
        $kept = array();
        for ($level = $least; $level <= $full; $level++) $kept[$level] = self::afterInterception($view, $model, $unit, $weapon, $target, $level);
        return self::cycleCharge($kept);
    }

    /* The charge an accelerator CYCLES at against a target (holdCharge, G1): of its $ladder (charge => what the shot should do, the
       target's interceptors counted), the lowest whose damage per turn of charging (the shot / the charge) is at least
       AIConfig::FIRE_SUSTAINED_SHARE of the best charge's - the lowest when none has a shot. */
    public static function cycleCharge(array $ladder)
    {
        $best = 0;
        foreach ($ladder as $charge => $value) $best = max($best, $value / max(1, $charge));
        if ($best > 0) {
            foreach ($ladder as $charge => $value) {
                if ($value / max(1, $charge) >= AIConfig::FIRE_SUSTAINED_SHARE * $best - 1e-9) return (int)$charge;
            }
        }
        return (int)min(array_keys($ladder));
    }

    /* Is this $weapon's shot its LAST (G1 - "the shooter or the weapon's section expected lost this turn - JumpDecider's 'expected kill'
       measure - or jumping out / surrendering")? Per ship, remembered in $cache (lastShots). A flight's craft never: JumpDecider asks
       no flight, and every gun of a flight could be the target of every enemy gun at once. (A surrendering side is one that can damage
       nobody - Posture: it has no shot to take.) */
    private static function lastShot(AIView $view, $unit, Weapon $weapon, array &$cache)
    {
        if ($unit instanceof FighterFlight) return false;
        $id = (int)$unit->id;
        if (!isset($cache[$id])) $cache[$id] = self::lastShots($view, $unit);
        $state = $cache[$id];
        return $state['leaving'] || $state['doomed'] || !empty($state['sections'][(int)$weapon->location]);
    }

    /* What $ship has coming this Fire phase, as JumpDecider::reason measures an "expected kill" - but as the units now stand, their EW
       set (everybody has moved): every loaded plain gun of every enemy that has a shot at it (fire is simultaneous - whom each will
       choose is not known), shared out over the sections its hits land on (AIView::sectionEstimate). array('leaving' => the ship has a
       jump point of its own on the board (JumpDecider::pending - it is going), 'doomed' => its primary structure down to
       AIConfig::JUMP_DAMAGED_SHARE and the fire at least what is left of it from any side (healthLeft), 'sections' => location => a
       section down to that share with at least its structure coming at it - whatever it carries goes with it). */
    public static function lastShots(AIView $view, $ship)
    {
        $state = array('leaving' => JumpDecider::pending($view, $ship) !== null, 'doomed' => false, 'sections' => array());
        $incoming = array();
        $total = 0;
        $left = null;
        foreach ($view->enemies() as $enemy) {
            $fire = 0;
            foreach ($view->mounts($enemy) as $mount) {
                $gun = $mount['weapon'];
                if (!$view->plainGun($gun, $mount['fighter']) || !$view->loaded($gun)) continue;
                foreach ($view->sectionEstimate($enemy, $gun, $ship, true) as $loc => $damage) {
                    $incoming[$loc] = (isset($incoming[$loc]) ? $incoming[$loc] : 0) + $damage;
                    $fire += $damage;
                }
            }
            if ($fire <= 0) continue;
            $total += $fire;
            $health = $view->healthLeft($ship, $enemy);
            $left = ($left === null) ? $health : min($left, $health);
        }

        $share = AIConfig::JUMP_DAMAGED_SHARE;
        $primary = $ship->getStructureSystem(0);
        if ($primary && $left !== null && $primary->getRemainingHealth() <= $share * (int)$primary->maxhealth && $total >= $left) $state['doomed'] = true;
        foreach ($incoming as $loc => $damage) {
            if ((int)$loc === 0) continue;
            $structure = $ship->getStructureSystem((int)$loc);
            if (!$structure || $structure->isDestroyed()) continue;
            $remaining = $structure->getRemainingHealth();
            if ($remaining <= $share * (int)$structure->maxhealth && $damage >= $remaining) $state['sections'][(int)$loc] = true;
        }
        return $state;
    }

    /* Does a shot at its present charge FINISH OFF its target (G1 - "this charge likely kills the target or a section")? At least
       AIConfig::FIRE_FINISH_CHANCE to hit, and what a hit should do ($value - what the shot should do - over its chance) at least what is
       left of the target from the shooter's side (healthLeft) or of the section it hits; at a flight, one hit (the shot's 'perHit') at
       least the weakest craft has left - a hit takes one craft. */
    private static function finishes(AIView $view, $unit, $target, array $shot, $value)
    {
        $chance = (int)$shot['chance'];
        if ($chance < AIConfig::FIRE_FINISH_CHANCE || $value <= 0) return false;
        if ($target instanceof FighterFlight) {
            $weakest = null;
            foreach ($target->systems as $fighter) {
                if (!($fighter instanceof Fighter) || $fighter->isDestroyed()) continue;
                $weakest = ($weakest === null) ? $fighter->getRemainingHealth() : min($weakest, $fighter->getRemainingHealth());
            }
            return $weakest !== null && $shot['perHit'] >= $weakest;
        }
        $onHit = $value * 100 / $chance;
        if ($onHit >= $view->healthLeft($target, $unit)) return true;
        $loc = isset($shot['hitLoc']) ? (int)$shot['hitLoc'] : 0;
        if ($loc === 0) return false;
        $structure = $target->getStructureSystem($loc);
        return $structure && !$structure->isDestroyed() && $onHit >= $structure->getRemainingHealth();
    }

    /* What a shot is worth, given what is already booked on its target. Damage beyond
       FIRE_OVERKILL times the target's remaining health counts for nothing. A heavy gun is drawn to a
       target already booked far more than a light one (FIRE_FOCUS_HEAVY): the fleet's heavy fire goes
       in together, to take a ship down. $focusShare: the level's (stage T5f - Easy has no focus-fire bonus at all). */
    public static function score(array $shot, array $target, $maxThreat, $heavy = false, $focusShare = 1.0)
    {
        $room = $target['health'] * AIConfig::FIRE_OVERKILL - $target['booked'];
        $useful = min($shot['damage'], max(0, $room));
        if ($useful <= 0) return 0;

        $worth = $useful / $target['health'] * $target['value'];
        $focus = 1 + $focusShare * ($heavy ? AIConfig::FIRE_FOCUS_HEAVY : AIConfig::FIRE_FOCUS) * min(1, $target['booked'] / $target['health']);
        $threat = 1 + ($maxThreat > 0 ? AIConfig::FIRE_THREAT_WEIGHT * $target['threat'] / $maxThreat : 0);

        return $worth * $focus * $threat;
    }

    /* The intercept rating the server would use this gun with if it is left alone - 0 when it
       would not: no rating, or a weapon that takes more than a turn to load (that needs a
       selfIntercept order, which the bot does not give). */
    private static function interceptRating(Weapon $weapon)
    {
        if ($weapon->intercept <= 0) return 0;
        if (max($weapon->getLoadingTime(), $weapon->normalload) > 1) return 0;
        return (int)$weapon->intercept;
    }

    /* The interception reserve for one unit: empties the offensive options of the guns it holds
       back (by reference). Returns how many were held.

       Stage T6a (the user's notes on game 4774, AIConfig::FIRE_INTERCEPTOR_MIN_CHANCE): a gun with an offensive shot of
       FIRE_INTERCEPTOR_MIN_CHANCE or better always fires it, against missiles too - "where Defensive and Offensive priorities are
       relatively balanced, choose offence". A gun under that stays on defence only while there is something for it to stop:
       one interceptor for each interceptable enemy order that can reach the unit (interceptableIncoming) - the unit's
       interceptors with no offensive shot at all cover what they can first (they are on defence anyway: an Omega's
       Interceptors before any of its Particle Beams), then the guns under the bar, best intercept rating first, each one only
       for an order it could really stop: a shot from inside its arc (the server's own test, Firing::isLegalIntercept - a port
       battery cannot help against fire from starboard). */
    private static function holdBack(AIView $view, $unit, array &$unitGuns)
    {
        $idle = array();       //interceptors with no offensive shot: they intercept whatever is decided here
        $candidates = array();
        foreach ($unitGuns as $index => $gun) {
            if ($gun['intercept'] <= 0) continue;
            if (count($gun['options']) == 0) {
                $idle[] = $gun['weapon'];
                continue;
            }
            $bestChance = 0;
            foreach ($gun['options'] as $shot) $bestChance = max($bestChance, $shot['chance']);
            if ($bestChance < AIConfig::FIRE_INTERCEPTOR_MIN_CHANCE) $candidates[] = $index;
        }
        if (count($candidates) == 0) return 0;

        $open = self::interceptableIncoming($view, $unit); //the orders nothing covers yet
        if (count($open) == 0) return 0;
        foreach ($idle as $weapon) {
            for ($gun = 0; $gun < max(1, (int)$weapon->guns) && count($open) > 0; $gun++) self::cover($view, $weapon, $open);
        }
        if (count($open) == 0) return 0;

        usort($candidates, function ($a, $b) use ($unitGuns) {
            if ($unitGuns[$a]['intercept'] !== $unitGuns[$b]['intercept']) return $unitGuns[$b]['intercept'] - $unitGuns[$a]['intercept'];
            return $a - $b;
        });
        $held = 0;
        foreach ($candidates as $index) {
            if (count($open) == 0) break;
            $weapon = $unitGuns[$index]['weapon'];
            if (!self::cover($view, $weapon, $open)) continue; //nothing it could stop: it fires
            for ($gun = 1; $gun < max(1, (int)$weapon->guns) && count($open) > 0; $gun++) self::cover($view, $weapon, $open);
            $unitGuns[$index]['options'] = array();
            $held++;
        }
        return $held;
    }

    //Take the first order of $open that $weapon could intercept off the list (by reference); false when there is none.
    private static function cover(AIView $view, Weapon $weapon, array &$open)
    {
        foreach ($open as $key => $order) {
            if (!Firing::isLegalIntercept($view->gamedata, $weapon, $order)) continue;
            unset($open[$key]);
            return true;
        }
        return false;
    }

    /* The enemy ORDERS this Fire phase that $unit's interceptors could stop - what a player looks at before keeping a gun
       back: every gun of a visible enemy that is loaded, can be intercepted (a laser cannot: Weapon::$uninterceptable, nor a
       ram or a field: $doNotIntercept), and has a shot at the unit as both stand (arc, range and the real formula -
       AIView::estimate), as a throwaway order a gun; and every ballistic launched at the unit this turn that can be
       intercepted, the order itself. Fire is simultaneous, so whom each enemy gun will choose is not known: any gun that could
       is counted. A list of FireOrders. */
    public static function interceptableIncoming(AIView $view, $unit)
    {
        $orders = array();
        foreach ($view->enemies() as $enemy) {
            foreach ($view->mounts($enemy) as $mount) {
                $weapon = $mount['weapon'];
                if (!$view->plainGun($weapon, $mount['fighter']) || !$view->loaded($weapon)) continue;
                if (!self::interceptable($weapon)) continue;
                if ($view->estimate($enemy, $weapon, $unit, true) <= 0) continue;
                for ($gun = 0; $gun < max(1, (int)$weapon->guns); $gun++) {
                    $orders[] = new FireOrder(-1, 'normal', $enemy->id, $unit->id, $weapon->id, -1, $view->turn, $weapon->firingMode, 0, 0, $weapon->defaultShots, 0, 0, null, null, null);
                }
            }
        }
        foreach ($view->gamedata->ships as $shooter) {
            if ($shooter->team == $unit->team) continue;
            foreach ($shooter->getAllFireOrders($view->turn) as $fire) {
                if ($fire->type !== 'ballistic' || (int)$fire->targetid !== (int)$unit->id) continue;
                $weapon = $shooter->getSystemById($fire->weaponid);
                if ($weapon instanceof Weapon && !self::interceptable($weapon)) continue;
                for ($shot = 0; $shot < max(1, (int)$fire->shots); $shot++) $orders[] = $fire;
            }
        }
        return $orders;
    }

    /* Can a shot of this weapon, in its current mode, be intercepted at all (Firing::isLegalIntercept's first tests)? Public since
       stage T7: Hard's Interceptor boost keeps Interceptors for the enemy ships' interceptable fire (PowerDecider::interceptors). */
    public static function interceptable(Weapon $weapon)
    {
        return !$weapon->doNotIntercept && !$weapon->uninterceptable && !$weapon->hextarget;
    }
}
