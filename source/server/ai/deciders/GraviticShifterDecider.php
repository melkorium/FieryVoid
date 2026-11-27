<?php
/**
 * CPU opponent - W10: the GRAVITIC SHIFTER, fired in Pre-Firing (TACTICAL_AI_PLAN.md 10.10, "W10 as built").
 *
 * The Brakiri Shakara (2), Tashkat (3) and Tashkava (3) carry them (the Ishtaka base too - bases are not flown yet). A shifter that hits
 * TURNS its target 60 degrees where it stands, before the Fire phase - facing and heading both, clockwise in mode 1, anti-clockwise in
 * mode 2 (GraviticShifter::onDamagedSystem: a 'prefire' movement row). Only the FIRST shifter tried at a unit in a turn may turn it - any
 * other misses by itself (GraviticShifter::calculateHitBase) - so the bot fires one at a unit at most. No size rule; never at a mine or an
 * Enormous unit, which it does not turn.
 *
 * The plan's measure (row W10: "a damaged side to the guns, a locked enemy's guns away") is the Gravity Nets' - GravityNetDecider::trade,
 * the "best trade" ShipDecider moves by: the damage the bot's units should deal this Fire phase, less the level's shipDanger weight times
 * what the turned unit should deal them, each gun priced by the engine's own hit formula (AIView::estimate - a lock, the range penalty,
 * the arcs, the profile turned to each shooter) and each target's health counted from the side the shots arrive on (healthLeft: a lost
 * or damaged section turned to the guns makes each point worth more) - with the unit TURNED instead of moved. For each unit a shifter can
 * hit, both turns are priced against the unit as it stands; each shifter then goes, greedily, to the unit and turn that add most - the
 * gain times its chance, a unit once - while that is at least AIConfig::SHIFTER_MIN_GAIN, at a chance of at least SHIFTER_MIN_CHANCE.
 *
 * The user's rulings (2026-11-28): "Same as nets" - every level turns ENEMIES, HARD also its own and its teammates' units (the skill
 * 'shiftAllies' - a friend turned so its guns bear, or a fresh side to the enemy: GravityNetDecider::friendTrade's measure); "This turn
 * only" - the HEADING the unit is turned to (where it flies next turn) is not priced; "Higher gain floor" - SHIFTER_MIN_GAIN 15 (a
 * shifter reloads in three turns).
 *
 * Legality is the page's (AIView::shiftShot, judged by fireOracle.js replayShifts). Not priced either: a Gravity Net or another player's
 * shifter at the same unit this turn, the bot's own missiles already in flight at it; no OEW is put on a unit to make shifting it likelier
 * (Initial Orders knows nothing of shifters). The decision is made on the bot's own MASKED view (principle P2); in Pre-Firing every unit
 * has moved.
 */
class GraviticShifterDecider
{
    //What the last plan() priced: units, turns priced, whether the budget cut it short, and the CPU seconds it took.
    public static $last = array('targets' => 0, 'turns' => 0, 'cut' => false, 'seconds' => 0.0);
    //CPU seconds the pricing may take (AIConfig::SHIFTER_DECISION_SECONDS); 0 = no limit - a harness sets it, for determinism.
    public static $budget = AIConfig::SHIFTER_DECISION_SECONDS;

    /* The plan: a list of array('unit', 'shifter', 'target', 'mode' (1 clockwise, 2 anti-clockwise), 'chance' (percent), 'gain' (the
       trade the turn buys, if it hits), 'added' (gain times chance)) - one row a shifter that fires. */
    public static function plan(AIView $view)
    {
        self::$last = array('targets' => 0, 'turns' => 0, 'cut' => false, 'seconds' => 0.0);
        if (!$view->skill('graviticShifters')) return array();
        $shifters = self::shifters($view);
        if (count($shifters) == 0) return array();

        $started = AIConfig::cpuSeconds();
        $deadline = (self::$budget > 0) ? $started + self::$budget : null;
        $allies = (bool)$view->skill('shiftAllies');
        $board = GravityNetDecider::board($view, $allies);

        //each shifter's chance at each unit it can turn, and the unit's better turn: shifter index => unit id => array('target', 'mode', 'gain', 'chance')
        $options = array();
        foreach (self::targets($view, $shifters, $allies) as $target) {
            $shooting = array();
            foreach ($shifters as $n => $shifter) {
                $shot = $view->shiftShot($shifter['unit'], $shifter['weapon'], $target, $allies);
                if ($shot === null || $shot['chance'] < AIConfig::SHIFTER_MIN_CHANCE) continue;
                /* From exactly between two of the unit's sections the page prices the shot on one profile, up to 15 off the engine's
                   mean (AIView::onEdge): a low one there may be a click the page would refuse - as FireDecider and the nets. */
                if ($view->onEdge($shifter['unit'], $target) && $shot['chance'] < AIConfig::FIRE_MIN_CHANCE_EDGE) continue;
                $shooting[$n] = (int)$shot['chance'];
            }
            if (count($shooting) == 0) continue;
            if ($deadline !== null && AIConfig::cpuSeconds() > $deadline) {
                self::$last['cut'] = true;
                break;
            }
            self::$last['targets']++;

            $base = GravityNetDecider::trade($view, $board, $target);
            $best = null;
            foreach (array(1, 2) as $mode) {
                $gain = self::turnedTrade($view, $board, $target, $mode) - $base;
                self::$last['turns']++;
                if ($best === null || $gain > $best['gain']) $best = array('mode' => $mode, 'gain' => $gain);
            }
            foreach ($shooting as $n => $chance) {
                $options[$n][(int)$target->id] = array('target' => $target, 'mode' => $best['mode'], 'gain' => $best['gain'], 'chance' => $chance);
            }
        }

        //greedy: the shifter and unit that add most, until nothing adds SHIFTER_MIN_GAIN - a unit once (a second shifter misses by itself)
        $plan = array();
        $used = array();
        $turned = array();
        while (true) {
            $best = null;
            foreach ($options as $n => $byTarget) {
                if (isset($used[$n])) continue;
                foreach ($byTarget as $id => $option) {
                    if (isset($turned[$id]) || $option['gain'] <= 0) continue;
                    $added = $option['chance'] / 100 * $option['gain'];
                    if ($best === null || $added > $best['added']) $best = array('n' => $n, 'id' => $id, 'option' => $option, 'added' => $added);
                }
            }
            if ($best === null || $best['added'] < AIConfig::SHIFTER_MIN_GAIN) break;
            $used[$best['n']] = true;
            $turned[$best['id']] = true;
            $shifter = $shifters[$best['n']];
            $plan[] = array('unit' => $shifter['unit'], 'shifter' => $shifter['weapon'], 'target' => $best['option']['target'], 'mode' => $best['option']['mode'],
                'chance' => $best['option']['chance'], 'gain' => $best['option']['gain'], 'added' => $best['added']);
        }
        self::$last['seconds'] = AIConfig::cpuSeconds() - $started;
        return $plan;
    }

    //The bot's Gravitic Shifters on the board, array('unit', 'weapon'), in the game's unit order and each ship's systems' (shiftShot judges each).
    private static function shifters(AIView $view)
    {
        $list = array();
        foreach ($view->myUnits() as $unit) {
            if ($unit instanceof FighterFlight) continue;
            foreach ($unit->systems as $system) {
                if ($system instanceof GraviticShifter) $list[] = array('unit' => $unit, 'weapon' => $system);
            }
        }
        return $list;
    }

    /* The units a shifter might turn: the enemies - and with $allies (Hard's 'shiftAllies') the friendly ones (GravityNetDecider::friends)
       - not mines, not Enormous; nearest to the shifters first (a budget cut leaves the farthest unpriced). */
    private static function targets(AIView $view, array $shifters, $allies = false)
    {
        $list = array();
        foreach (array_merge($view->enemies(), $allies ? GravityNetDecider::friends($view) : array()) as $unit) {
            if (!empty($unit->mine) || !empty($unit->Enormous)) continue;
            $near = PHP_INT_MAX;
            foreach ($shifters as $shifter) $near = min($near, $shifter['unit']->getHexPos()->distanceTo($unit->getHexPos()));
            $list[] = array($near, (int)$unit->id, $unit);
        }
        usort($list, function ($a, $b) { return ($a[0] !== $b[0]) ? $a[0] - $b[0] : $a[1] - $b[1]; });
        return array_map(function ($row) { return $row[2]; }, $list);
    }

    /* The trade with $target turned as a hit in $mode leaves it (GraviticShifter::onDamagedSystem: its last row's facing AND heading one
       hex side round - +1 clockwise, -1 anti-clockwise - the hex and speed kept), priced by the nets' measure. */
    private static function turnedTrade(AIView $view, array $board, $target, $mode)
    {
        $last = $target->getLastMovement();
        $step = ((int)$mode === 1) ? 1 : -1;
        $target->movement[] = new MovementOrder(-1, 'prefire', new OffsetCoordinate($last->position->q, $last->position->r), 0, 0, (int)$last->speed,
            Mathlib::addToHexFacing((int)$last->heading, $step), Mathlib::addToHexFacing((int)$last->facing, $step), false, $view->turn, 0, 0);
        try {
            return GravityNetDecider::trade($view, $board, $target);
        } finally {
            array_pop($target->movement);
        }
    }

    /* Unit id => shifter id => its system row: the one order the page's click makes (weaponManager.targetShip's plain loop - the
       weapon's one gun): 'prefiring', at the unit, in the turn's mode, the weapon's shots, damage class 'gravitic', no called shot. */
    public static function systems(array $plan, $turn)
    {
        $out = array();
        foreach ($plan as $row) {
            $unitId = (int)$row['unit']->id;
            $shifterId = (int)$row['shifter']->id;
            $order = AIOrderBuilder::fire($unitId, (int)$row['target']->id, $shifterId, $turn, (int)$row['mode'], (int)$row['shifter']->defaultShots, 'gravitic', 'prefiring');
            $out[$unitId][$shifterId] = array('id' => $shifterId, 'fireOrders' => array($order));
        }
        return $out;
    }

    //One line about a plan, for tac_ai_log.
    public static function describe(array $plan)
    {
        $parts = array();
        foreach ($plan as $row) {
            $parts[] = $row['unit']->name . ' shifter #' . $row['shifter']->id . ' at ' . $row['target']->name . ' '
                . (((int)$row['mode'] === 1) ? 'clockwise' : 'anti-clockwise') . ' (' . $row['chance'] . '%, trade +' . round($row['gain']) . ')';
        }
        return 'Gravitic Shifters: ' . implode('; ', $parts) . ' (' . self::$last['targets'] . ' units, ' . self::$last['turns'] . ' turns priced, '
            . number_format(self::$last['seconds'], 2) . ' s' . (self::$last['cut'] ? ', cut by the budget' : '') . ')';
    }
}
