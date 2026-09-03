# Walkers of Sigma-957 — Implementation Plan

New Ancient faction. Nine new systems, three of which need machinery FV does not have today.
This document is the long-form record; update it as stages land.

**Status: Stages 0, 1 and 2 BUILT (2026-09-03). Stages 3–10 planned.** Written 2026-09-02 after a
full survey of the existing seams.

**Faction string is `Walkers of Sigma-957`** — plural, hyphenated, exactly as spelled here. It is
the switch key in `gamelobby.js`, the directory-map key in `ShipLoader::getFactionDirMap()`, the
filename of the lobby's per-faction JSON, and (D7) the CPD adaptation key. Getting it wrong is
silent everywhere.

---

## 0. Decisions already taken (user, 2026-09-02)

| # | Question | Ruling |
|---|---|---|
| D1 | EDF attribute drain on capital ships | Thrust roll reduces **Engine output**; Energy roll reduces **Reactor output**. Thrusters untouched. Flights use their real `freethrust`. |
| D2 | Sensor Charge Transceiver interaction | **Hex-target weapon with `canSplitShots`**, shots = manoeuvres available this turn. Each pick is a waypoint; yellow arc sprites draw the confirmed path, blue arcs from the last waypoint show reachable next hexes. No new plotting engine. |
| D3 | Energy Draining Net geometry | **Pairwise links AND closed-area fill.** Reuse the Gravitic Mine zone code. |
| D4 | Control-sheet numbers | **User supplies per system, per stage** — as a **Walker test ship carrying a basic version of that system**, so the stats arrive as a real hull file rather than a table. Classes get complete mechanics with clearly-marked placeholder stat tables until then. |
| D5 | EDF and concealment | **Out of scope.** No Walker hull has a stealth function, so the "a concealed ship discloses itself by projecting a field" case cannot arise. See §2.1 for when it would become live again. |
| D6 | Wide-Beam declaration timing | **Firing mode, chosen in the Fire phase.** The Prepare-Weapons timing deviation is accepted. |
| D7 | CPD adaptation scope | **The raw `$ship->faction` string.** No faction families. |
| D8 | EDN corridor choice | **Deterministic, no UI** — but tie-broken toward the corridor a player would pick: prefer a hex containing an enemy unit. |

Everything below assumes these.

---

## 1. What already exists — the seams we build on

This faction needs remarkably little new machinery, because four of the hardest requirements
already have a proven implementation in the tree. Verified 2026-09-02.

### 1.1 Split shots — already fully wired, server and client
`canSplitShots` + `maxVariableShots` + `specialHitChanceCalculation` is a complete, live feature
used by the Slicer Beams, Ballistic Torpedo and Vorlon Discharge Gun.

- Server reference implementation: `MolecularSlicerBeamL`, [molecular.php:834](source/server/model/weapons/molecular.php#L834) —
  read its class comment before writing any of ours, it is the best-documented weapon in the tree.
- Its `beforeFiringOrderResolution` shows the **allocation-token pattern**: the client encodes a
  per-shot payload into `FireOrder->notes` (`MSB|d:<dice>|s:<set>`), the server re-clamps it against
  the real pool and strips the token. Both the Lightning Array's combined fire and the SCT's
  waypoints use this channel.
- Client entry points: `weaponManager.targetShip` → `doMultipleFireOrders`, and — the one we need
  for the SCT — `weaponManager.targetHex` → **`doMultipleHexFireOrders`**,
  [weaponManager.js:3843](source/public/client/weaponManager.js#L3843). Three weapons already
  implement it (`GravityNet`, `ProximityLaserNew`, `EWGraviticTractingRod`).
- ⚠️ `$this->guns` padding in `beforeFiringOrderResolution` is load-bearing for
  `Firing::automateIntercept`. Copy the Slicer's comment and its **skip for manual `intercept`
  orders** — that exact bug produced 44 defensive shots against 4 missiles in game 4306.

### 1.2 Accelerator weapons — already a pattern, not a feature
Damage keyed off `$this->turnsloaded`, with `$normalload` as the charge ceiling.
Reference: `LaserAccelerator`, [lasers.php:1378](source/server/model/weapons/lasers.php#L1378)
(`loadingtime = 2, normalload = 4`, `getDamage`/`setMinDamage`/`setMaxDamage` switch on
`turnsloaded`, and `stripForJson` re-publishes the min/max arrays so the client tooltip tracks).

**"Does not begin the game fully charged"** is a one-method override:
`Weapon::getStartLoading()` ([weapon.php:1011](source/server/model/weapons/weapon.php#L1011))
returns `new WeaponLoading($this->getNormalLoad(), …)`. Return `0` in the first slot instead.
Nothing else needs to change — `onConstructed` writes that straight into `tac_systemdata`.

### 1.3 Stored, launch-some-or-all ballistics — already a pattern
The Energy Draining Mine's "store up to 3, launch any number" is exactly `BallisticTorpedo`,
[torpedo.php:79](source/server/model/weapons/torpedo.php#L79):
`loadingtime = 1, normalload = N, canSplitShots = true, ballistic = true, hextarget = true`,
with the client's `initializationUpdate` setting `maxVariableShots = this.turnsloaded`
([torpedo.js:15](source/public/client/model/weapon/torpedo.js#L15)) and `checkFinished`
closing the menu when the pool is spent.

### 1.4 A computed hex-set published to the client — already a pattern
`TacGamedata::$blockedHexes` ([TacGamedata.php:55](source/server/model/TacGamedata.php#L55)) is
built once by `setBlockedHexes()` ([TacGamedata.php:1915](source/server/model/TacGamedata.php#L1915))
during `onConstructed`, serialised at [TacGamedata.php:181](source/server/model/TacGamedata.php#L181),
and consumed by BOTH `Weapon::isLoSBlocked` ([weapon.php:3025](source/server/model/weapons/weapon.php#L3025))
and the client's identical `mathlib.isLoSBlocked` inside `targetHex`.

**The EDF hex map is the same shape and should be built the same way.** That gives the client a
free, exact mirror of the to-hit penalty with no new sync mechanism.

### 1.5 Hex-line tracing — already written
`getHexLine(OffsetCoordinate $start, OffsetCoordinate $end)`, a cube-interpolating static on the
Spatial Cutter, [specialWeapons.php:11101](source/server/model/weapons/specialWeapons.php#L11101).
Used by both the EDF targeting penalty and every SCT path segment. **DONE (Stage 0):** it now
lives on `HexZone` (`HexZone::line`), with `SpatialCutter::getHexLine` kept as a delegating alias.

### 1.6 The critical machinery already does everything the three crit tables ask for
`ShipSystem::testCritical` ([ShipSystem.php:1370](source/server/model/systems/ShipSystem.php#L1370))
already rolls `Dice::d(20) + floor(getTotalDamage()) + $add`, which is verbatim
"roll a d20 and add the number of damaged boxes". Successive-roll escalation is free:
`hasCritical($type, $turn)` returns a **count**, not a bool.

Criticals carry a `param` (`tac_critical.param varchar(200)`,
[emptyDatabase.sql:195](db/emptyDatabase.sql#L195)) and `ShipSystem::sumCriticalParam` sums it —
that is the persistence channel for the EDF's rolled drain magnitudes. **No schema change anywhere
in this project.**

### 1.7 The drain's four targets each already have exactly one choke-point
| Rules effect | Where it plugs in |
|---|---|
| Initiative | `BaseShip::getCommonIniModifiers` ([ShipClasses.php:317](source/server/model/ships/ShipClasses.php#L317)) — already reads a stack of crits off `CnC`. ⚠️ **FV initiative is d100: every modifier here is 5× its tabletop value.** |
| Total EW | `EW::getScannerOutput` ([EW.php:42](source/server/handlers/EW.php#L42)) — already subtracts `RestrictedEW` and `SensorLoss`. |
| Energy | `Reactor` ([baseSystems.php:1018](source/server/model/systems/baseSystems.php#L1018)) — `outputType = "power"`, honours `outputMod`. |
| Free thrust | `Engine` ([baseSystems.php:1495](source/server/model/systems/baseSystems.php#L1495)) — `outputType = "thrust"`, honours `outputMod`; flights use `$ship->freethrust`. |

### 1.8 Per-system enhancements — machinery exists, one gate blocks us
`Enhancements::setSystemEnhancementOptions` ([Enhancements.php:3441](source/server/model/ships/Enhancements.php#L3441))
plus the `eligible`/`limit`/`price`/`apply` registry quartet is exactly the right shape for
Wide-Beam and EDF Range — and the per-array pricing the rules demand ("the player pays to enhance
each one separately") is only possible per-system.

⚠️ **[Enhancements.php:3445](source/server/model/ships/Enhancements.php#L3445) is
`if($ship->factionAge > 2) return;`** — a blanket refusal for Ancients. See §3.3 for the fix.

### 1.9 Zone geometry — already written, in the wrong place
`GraviticMine` carries a proven, exhaustively-verified hex-zone implementation:
`convexHull` ([gravitic.php:2796](source/server/model/weapons/gravitic.php#L2796)),
`pointInPolygon` ([gravitic.php:2825](source/server/model/weapons/gravitic.php#L2825)),
`getHexTouchTolerance` ([gravitic.php:2773](source/server/model/weapons/gravitic.php#L2773)) and
`isUnitInShearingZone` ([gravitic.php:2706](source/server/model/weapons/gravitic.php#L2706)).
All `private`. The EDN needs them; Stage 0 extracts them (see §2.3).

### 1.10 Adding the faction itself is cheap
A ships directory `source/server/model/ships/walkers/`, `$this->faction = 'Walkers of Sigma-957'`
on each hull (`ShipLoader::getFactionDirMap` derives the mapping at runtime), one tier line in
`gamelobby.js`'s switch ([gamelobby.js:120](source/public/client/gamelobby.js#L120) neighbourhood —
`'Tier Ancients'`), plus optional prose in `factions-tiers.php` and
`ammo-options-enhancements.php`. `$factionAge = 3` (Ancient).

⚠️ Filename must equal class name or the ship **silently does not exist**.

**As built (Stage 1, 2026-09-03).** It was that cheap: `Traveler.php` already existed, so the whole
stage was the faction string (`Walker of` → `Walkers of Sigma-957`), the `gamelobby.js` tier case,
and a statics regen. Three things worth knowing next time:

- **The lobby reads `source/public/static/json/<faction>.json`**, written by
  `generateStaticShipFile.php` and keyed on the faction string. A faction **rename leaves the old
  file behind**, and the lobby then lists both spellings as separate factions with the same hull in
  each. `source/public/static/` is gitignored, so nothing warns you. Delete the stale file by hand.
- **`fvbuild.ps1 -Statics` is not optional** for a new hull — without it the ship exists on the
  server and is invisible in the lobby.
- **`Traveler`'s hit chart already names Lightning Array, Chromatic Pulse Driver and Energy
  Draining Field**, which do not exist yet, and the ship-data validator fails on all five entries
  ("every hit here is silently rerouted to Structure"). They are **commented out in the hull with
  `//STAGE n` markers**; each of Stages 2, 3 and 4 uncomments its own row when it adds the system.
  Restoring a row is a two-character edit — do not forget it, a system with no hit-chart entry can
  never be hit.

---

## 2. The three new shared abstractions

Deliberately only three. Every system below is built out of these plus existing patterns.

### 2.1 `EdfField` — the field as a published hex map

**A new interface, not a base class:**
```php
interface EdfSource {          // implemented by systems AND by the EDM terrain unit
    public function getEdfRadius($turn);   // 0 for a Net; the crit-reduced radius otherwise
    public function isEdfActive($turn);    // offline / destroyed / voluntarily deactivated
}
```

**`TacGamedata::$edfHexes`** — built once in `onConstructed` immediately after `setBlockedHexes()`,
same shape, same lifecycle, serialised in `stripForJson` alongside it:

```
$edfHexes = [ 'q,r' => [ 'teams' => [teamId => true], ... ], ... ]
```

Keyed by hex so overlapping fields collapse for free — which is precisely the rules requirement
that *"overlapping hexes are only counted once"* for the targeting penalty. `teams` records which
teams' fields cover the hex, which is what makes *"the rest of the fleet of the ship deploying the
EDF is immune"* a single array lookup.

**Why a map and not per-ship distance checks:** the targeting penalty has to be evaluated for
every fire order in the game, and the client has to mirror it exactly to predict hit chance.
`blockedHexes` proves the map-once-publish-once shape works and stays in sync.

**Three consumers:**
1. `Weapon::calculateHitBase` — walk `getHexLine(shooterPos, targetPos)`, count hexes in
   `$edfHexes` not covered by the shooter's own team, `×2` per hex when
   `$this->weaponClass` is `Plasma` or `Antimatter` **and** `$this->factionAge < 3`.
   Client mirror in `weaponManager.calculateHitChange`.
2. `Weapon::doCollateralDamage` ([weapon.php:2344](source/server/model/weapons/weapon.php#L2344)) —
   the flash-suppression and 25%/50% rules.
3. The renderer — a translucent field overlay, reusing `HexagonSprite`.

**Not per-viewer**, exactly like `blockedHexes` — which is correct, because an EDF is a visible
phenomenon. **D5: concealment is out of scope**; no Walker hull has a stealth function, so a
concealed ship disclosing itself through its own published field cannot happen.

⚠️ **That is a property of the FLEET, not of the design.** The moment an EDF is granted to a hull
that can conceal itself — a Walker refit, a custom hull, a `whatif` pack — the map discloses its
position to every viewer, silently and with no error anywhere. If that day comes the answer is that
a concealed ship projects no field, and the test belongs in `setEdfHexes()` next to the
`isReinforcement()` guard `setBlockedHexes()` already carries for exactly this class of leak
([TacGamedata.php:1921](source/server/model/TacGamedata.php#L1921)).

⚠️ **Per-load static reset.** Any memoisation added here must be reset in
`DBManager::getSystemDataForShips`, immediately before the `onIndividualNotesLoaded` sweep — one
HTTP request loads gamedata **twice** (`Manager::advanceGameState`, then the phase's own
`getTacGamedata`) and a guard that is not reset there leaks between the two. This is the single
most expensive trap in this codebase; it cost a whole investigation on the Gravitic Augmenter.

### 2.2 `EdfExposure` — the drain, as param-carrying one-turn criticals

**Where it runs:** `Criticals::setCriticals` pass 2 calls `criticalPhaseEffects($ship, $gamedata)`
on every system of every ship ([criticals.php:96](source/server/handlers/criticals.php#L96)) —
which *is* the rules' "Critical Hit Step". The **EDF system on the Walker ship** owns the sweep, in
the `GraviticMineHandler` shape: one resolver, guarded by a static `$resolvedTurn` so N fields
resolve once and *"additional fields do not provide cumulative modifiers"* is structural rather
than a special case.

**Six new `Critical` subclasses** in `cricialClasses.php`, all `oneturn = true`:

| Class | Lands on | Read by |
|---|---|---|
| `EdfThrustDrain` (param) | victim's `Engine` | `getOutput()` via `sumCriticalParam` |
| `EdfPowerDrain` (param) | victim's `Reactor` | `getOutput()` via `sumCriticalParam` |
| `EdfIniDrain` (param) | victim's `CnC` (flight: sample fighter) | `getCommonIniModifiers` — **×5**, floor −100 (= tabletop −20) |
| `EdfEwDrain` (param) | victim's `CnC` | `EW::getScannerOutput` |
| `EdfFighterGrounded` | flight's sample fighter | fire-order validation — *"will not be able to shoot the next turn"* |
| `EdfExposed` (`forInfo`, param = consecutive-turn count) | victim's `CnC` / sample fighter | the resolver itself |

**The escalation counter needs no new storage.** `EdfExposed` carries the running count in its
`param`; turn N+1 reads turn N's marker and adds 1, or starts at 1 if absent. Enormous units are
clamped: *"the modifiers are limited to the first die and do not increase"* → always roll one die,
never read the marker.

**Fighter dropout** is a separate branch, not a crit: `Fighter::testCritical`
([fighter.php:274](source/server/model/systems/fighter.php#L274)) rolls `Dice::d(10)` and compares
against remaining health. The EDF needs `2d10`, `+1d10` per successive turn. Cleanest without
touching the signature: set `$fighter->critRollMod += Dice::d(10) * $consecutiveTurns` from the
resolver — `critRollMod` is already summed into that roll and is described in-file as the
"one-time penalty to dropout roll".

⚠️ **`advance()` has already set the NEXT phase before its ship loop.** Never branch on
`$gamedata->phase` inside the resolver. `criticalPhaseEffects` is reached from
`FireGamePhase::advance`, which is safe (it re-loads gamedata), but pass an explicit checkpoint if
this ever needs to run from a second site.

### 2.3 `HexZone` — extracting what already works — **BUILT 2026-09-03 (Stage 0)**

Two pure moves into `source/server/lib/HexZone.php`, with the originals delegating:

- `HexZone::line($start, $end)` ← `SpatialCutter::getHexLine` (plus its three private cube
  helpers, which had no other caller)
- `HexZone::containsUnit()`, `::hull()`, `::pointInPolygon()`, `::touchTolerance()`,
  `::pointToSegmentDistance()` ← the five `GraviticMine` privates

⚠️ **This is the only place in the plan that touches existing, working, subtle code**, and the
Gravitic Mine geometry is subtle in ways that cost real investigation time — the per-angle
tolerance (0.866→1.000, not the constant `sqrt(3)/2` it started as) and a `1e-9` epsilon that is
load-bearing on its own (game 7027 T1 sheared on a `+1.1e-15` margin). It was moved **byte-for-byte
with zero logic edits**: only the method names, the `static`/visibility keywords and the `$this->`
→ `self::` calls changed. The comments and local names inside `containsUnit()` still speak of
"mines" for exactly that reason — read "mine" as "any hex position defining the zone".

**How it was proven, and why the harness alone was not enough.** The local replay corpus does
exercise the shearing path, but barely: exactly **one** game touches it — game 4299, with two
`graviticShear` fire orders (`grep -rl graviticShear tests/replay/baseline/`). One mine layout is
one sample of a geometry whose whole difficulty is the awkward cases — collinear mines, a unit
sitting exactly on the tolerance, a corner-clipping line — so a green `check` was necessary and
nowhere near sufficient. The real proof was a throwaway differential test: the pre-move bodies were
pulled out of `git show HEAD:` into a `HexZoneRef` class and run side by side with the moved ones
over 13,504 randomised cases — 4,000 multi-hex lines, 7,504 zone tests and 2,000 direct hull /
point-in-polygon / tolerance / segment-distance comparisons — with **zero** mismatches. The test
asserted its own branch coverage (2-point in *and* out, 3+-point in *and* out, collinear in *and*
out, plus the same-hex and 0/1-point degenerate inputs) and exited `INCONCLUSIVE` if any bucket
stayed empty, because a differential test that only ever takes one path passes while testing
nothing. **Repeat that method for any future move of this code.**

Replay harness before and after: identical — 133 passed, 1 failed (game 4325, a pre-existing
clean-tree failure; the known-failure game recorded in memory has drifted from 4318 to 4325).

---

## 3. Per-system design

### 3.1 / 3.2 Lightning Array + Medium Lightning Array — **BUILT 2026-09-03 (Stage 2)**

`class LightningArray extends Weapon` and `class MediumLightningArray extends LightningArray`, both
in `specialWeapons.php` (server) and `special.js` (client) — that is where Walker weaponry goes,
because it is all `weaponClass = "Electromagnetic"` and there is no `electromagnetic.php`.

**Stats are the real control-sheet numbers** (user, 2026-09-03). Still unconfirmed and marked so
in-file: `$damageType` ("Standard" — switch to Raking if the sheet says otherwise), `$priority` and
`$loadingtime`. Re-statting stays a table edit: no method hard-codes a game number, the tooltip rows
are generated from the tables, and `setMinDamage`/`setMaxDamage` are derived from them too.

⚠️ **`$fireControl` and `$rangePenalty` must equal ROW 1 of their combined tables.** They are the
single-discharge profile and they are what the "Fire control" and "Range penalty" tooltip lines
report. Because the combined tables override them per shot, a mismatch does **not** change what gets
rolled — it just makes the ship window quote numbers the weapon never uses, which is worse than a
visible bug. They were out of step when the control sheet first landed and are now aligned.

**Combined fire, as built — REVISED 2026-09-03 after play testing.** ⚠️ **The allocation dialog is
gone.** These are ordinary split-shot weapons now, exactly like the Vorlon Discharge Gun: a gun IS a
discharge, one click declares one, and in Combined Fire mode a further click on the **same target**
fuses another discharge into the shot already standing there rather than declaring a second one.
Four clicks on one ship produce ONE 4-discharge shot; four clicks on four ships produce four single
ones. Single Shots mode never fuses. (The first build asked with a `confirm.askForMultipleValues`
dialog borrowed from `MolecularSlicerBeamL`; the user's verdict was that the guns/discharges
distinction it implied was confusion, not a feature.)

The count rides to the server in `->shots` **and nowhere else** — the same field every other
split-shot weapon carries its shot count in, and a whitelisted `FireOrder` constructor argument, so
it survives the POST rebuild on both the ship and the fighter branch. ⭐ The old `LA|n:<count>` token
in `->notes` is **removed on both sides**, because `->notes` has a job already: `notes` must read
exactly `"Split"` and `damageclass` exactly `'Sweeping'`, or the shot never appears in the target's
INCOMING list (`weaponManager.getAllBallisticsAgainst` admits a type-`normal` order **only** on
`damageclass === 'Sweeping'`) and its ballistic line is updated rather than re-created
(`BallisticIconContainer` keys that on `notes === 'Split'`). Those two literals are the split-shot
contract, not decoration — a weapon that invents its own values silently vanishes from the list the
shooter uses to see what it has committed (see "AND THAT ROW IS FOR THE SHOOTER" below).
`beforeFiringOrderResolution` re-clamps `n` against the tabled rows and then against what is
left in the pool, stashes it per order id, and sets `->shots = 1`. An order the pool cannot pay for
gets **0 discharges and does 0 damage** — the Slicer's behaviour, chosen because a 0-damage line in
the combat log is a loud failure and a silently-dropped order is not.

⚠️ **Growing a shot works by REPLACING its order, not by mutating it.** `doMultipleFireOrders` splices
the standing order out and returns a replacement carrying one more discharge, so the declaration stays
on `targetShip`'s ordinary push → `checkFinished()` → unselect path. Pushing from inside the hook and
unselecting there — which the dialog version did — splices `gamedata.selectedSystems` while
`targetShip` is still iterating it. The regenerated id is identical, because the array is the same
length again. ⚠️ `checkFinished()` therefore also has to call `updateGunAccounting()`: it is the first
moment the pushed order is visible to the weapon, and `->guns` must be fresh before anything reads the
manual-intercept cap.

**Fusing moves THREE things, off three tables keyed by the fused count:** `$combinedDamageArray`,
`$combinedFireControlArray` and `$combinedRangePenaltyArray`. They are applied by two different
mechanisms, and the difference matters:

- **Fire control — a DELTA on `$fireOrder->needed`, not a swap of `$this->fireControl`.**
  `fireControl` is read in several places inside `parent::calculateHitBase()`, and a
  temporarily-mutated copy would be a per-instance mutation of a property the blueprint shares. So
  the parent runs untouched and `needed` is then adjusted by
  `(combinedFC[n][fcIndex] - fireControl[fcIndex]) * 5` (the ×5 is d20 table → d100 roll), guarded
  by `needed <= 0` so the parent's auto-miss marker is never accidentally un-missed.
- **Range penalty — a real override of `calculateRangePenalty($distance)`, NOT a delta.** ⭐ This one
  cannot be a delta: the parent derives the **no-lock and jammer** modifiers from the range penalty,
  and in the `doubleRangeIfNoLock` branch calls `calculateRangePenalty` a second and third time at
  modified distances ([weapon.php:1787](source/server/model/weapons/weapon.php#L1787)). A flat delta
  on `needed` would have moved the base penalty and left both derivatives computing off the
  single-discharge value. Overriding the method keeps all three consistent for free.

  `calculateRangePenalty` is handed **nothing but a distance** on the server, so the fused count
  reaches it out of band: `calculateHitBase` publishes `$activeCombinedCount` for the duration of the
  parent call and clears it in a **`finally`** — without that, a parent that threw would leave a stale
  count standing and silently apply it to the *next* shot the weapon resolved.

  ⭐ **The client mirror needed the argument threaded instead.** `calculateSpecialRangePenalty` used to
  get only a distance too, which was survivable while the dialog set `pendingCombinedCount` around the
  one call that mattered — but with the dialog gone, the number the player reads *before* clicking has
  to describe the shot the click will produce, and that is only knowable from the target. So
  `weaponManager.calculateRangePenalty(distance, weapon, target, calledid, fireOrder)` now threads all
  three down (`computeJammerNoLock` and `computeShotModifiers` too), and every other weapon simply
  ignores the extra arguments. Both mirrors then resolve the count the SAME way, via
  `resolveCombinedCount`, so the fire-control half and the range half always describe one shot:
  1. `pendingCombinedCount` — set only while a click is being turned into an order;
  2. the fire order passed in — the INCOMING list and `calculataBallisticHitChange` both supply one,
     and a **declared** shot must read at its own count, never at a look-ahead, because the ship being
     shot at is deciding what to spend on interception;
  3. otherwise `getPreviewCombinedCount(target)` — what the NEXT click on that ship would fire.

**The Medium's accelerator rule falls out of the pool.** Its pool is `turnsloaded` capped at
`normalload`, so at one turn of charge there is exactly one discharge and *"may not combine until
charged two turns"* needs no rule of its own. ⚠️ **`getStartLoading()` returns loading `1`, not `0`**
(fixed 2026-09-03, game 4329): "does not begin the scenario **fully** charged" means 1/2, and 0 is
below `getLoadingTime()`, so the array could not fire at all on turn 1 and read "0/2" in the ship
window. The full Array is untouched and still starts ready (`getNormalLoad()` falls back to
`getLoadingTime()` = 1 when `normalload` is 0).

**Two firing modes.** `1 Combined Fire` (default) fuses repeat clicks on one target; `2 Single Shots`
makes every click a separate one-discharge order, which is what you want against a fighter flight
where four small shots beat one large one — and the fire-control table makes that concrete, since
fusing costs 6 points against fighters (8 → 2) while *gaining* 2 against capitals (4 → 6).
`beforeFiringOrderResolution` forces a mode-2 order's count to 1 and **ignores** `->shots` on it
rather than clamping it, so a stale or hand-edited client cannot smuggle a fused shot through the
cheap mode. ⚠️ It reads `$order->firingMode`, never `$this->firingMode`: `prepareFiring` calls
`changeFiringMode` only *after* this method, so the weapon's own mode is still last turn's here —
the same trap the Slicer's class comment records. Wide-Beam therefore becomes mode **3** at Stage 8.

⚠️ **`canSplitShots` is now ALWAYS true** — both modes, every pool size, including a pool of 1. It is
what routes a click through `doMultipleFireOrders` at all; the moment it goes false,
`weaponManager.targetShip` falls through to the ordinary path, which declares `weapon.guns` orders of
`defaultShots` each and stamps neither `'Sweeping'` nor `"Split"` — so a one-discharge Medium Array
declared four shots *and* they were invisible to the target's INCOMING list. The first build turned it
off at a pool of 1 to mean "nothing to divide"; with the dialog gone there is nothing to divide
anyway, and the flag only ever meant "this weapon declares its own orders".

**The INCOMING row COUNTS the discharges.** A combined shot is one fire order carrying several
discharges, so the row would read "1x Lightning Array (Combined Fire)" for a 4-discharge bolt.
`ShipTooltipBallisticsMenu` gained `shotsInGroup()` beside the existing Slicer `diceSuffix()`: a
weapon may implement `getIncomingShotCount(fireOrder)` and everything else keeps counting members. It
now reads "**4x** Lightning Array (Combined Fire)". ⚠️ Opt-in rather than a sum of `->shots`, because
a Molecular Slicer order carries DICE there and must stay one shot; and the group's `amount` stays the
MEMBER count, since that is what the disclosure caret opens into.

⭐⭐ **AND THAT ROW IS FOR THE SHOOTER, NOT THE DEFENDER** (user's ruling, 2026-09-03). A direct-fire
weapon declares and resolves in the same phase, so the opponent's gamedata never carries the order in
time: **they cannot see it and cannot manually intercept it.** Manual interception is a BALLISTIC
affair — declared in Initial Orders, resolved a phase later, visible in between — and that gap is the
whole mechanism. The INCOMING list on an enemy tooltip during Firing is a tally of what YOU have
committed to that ship. **Do not over-build defender-facing explanation into a Firing-phase weapon's
row.** The first pass gave the array a `getIncomingLabelSuffix` hook that wrote " (3 discharges)" so
"the defender could price interception"; that audience does not exist, and it was replaced by the
counting hook above. The `'Sweeping'` damageclass is still required — it is what puts the row there
for the shooter at all.

**Withdrawing a shot PEELS one discharge.** ⭐ "Remove a firing order" on a 4-discharge combined shot
makes it a 3-discharge shot; it does not delete the order. Deleting it would hand four discharges back
for one click on a button that says "remove **a** firing order", and there is no way to put three of
them back except by re-declaring. The order's stored `chance` is re-priced at the new count, because
fusing moves the fire control and the range penalty — a peeled shot still quoting the 4-discharge
number would show a hit chance the server will not roll. A Single Shots order is one discharge
already, so it just goes.

### multiModeSplit — the mode is a per-SHOT choice

`protected $multiModeSplit = true`. Fuse a couple of combined shots, switch to Single Shots, pepper a
flight with the rest, switch back. Without it `weaponManager.onModeClicked` / `onSetModeClicked` and
`SystemInfoButtons.canChangeFiringMode` all lock the firing-mode selector the moment the first order
is declared, which would make the two modes an either/or choice for the whole turn.

⚠️ **It is `protected` on `Weapon` and the base `stripForJson` does NOT publish it** — the override
has to pass `$strippedSystem->multiModeSplit`, or the client never sees the flag and the lock stays
on. Nothing on the server reads it: `Firing::prepareFiring` already calls `changeFiringMode` per ORDER
before resolving each shot, which is exactly why `beforeFiringOrderResolution` must read
`$order->firingMode` and never `$this->firingMode`.

⚠️ **It also hands WITHDRAWAL to the weapon.** `weaponManager.removeFiringOrderMulti` and
`removeFiringOrder` divert to `removeMultiModeSplit(ship, target)` / `removeAllMultiModeSplit(ship)`
and **return immediately** — no `SystemDataChanged`, no `SplitOrderRemoved`, no flight-movement
redraw, so the weapon fires those itself. `Weapon.prototype`'s versions are **no-ops**, so setting the
flag without overriding both silently kills every remove button.

Withdrawal takes the shot from the mode the weapon is sitting in, matching
`weaponManager.hasOrderForMode`, which is what gates the ship-window button. ⚠️ But the ENEMY-TOOLTIP
button is gated on `hasTargetedThisShip` with **no mode test**, so a mode-only search would leave it
doing nothing at all when the shot at that ship was declared in the other mode — hence
`findWithdrawableOrder` prefers the current mode and falls back to any. It filters to `type ===
'normal'`: a `selfIntercept` marker has its own button and must not be eaten by this one.

### ⭐ Interception: the engine counts ORDERS, this weapon spends DISCHARGES

Both arrays have an intercept rating, and that makes the engine's gun accounting wrong unless it is
corrected — because one combined shot of four is a **single fire order** that empties the whole pool.
Left alone, `Firing::isValidInterceptor` and `Firing::automateIntercept` would both read three
discharges as still available. This is the Slicer's `$guns`-padding problem in a different currency.

Both sites do their arithmetic against `$this->guns`, so `beforeFiringOrderResolution` rewrites it
every turn:

```
guns = pool − dischargesSpentOffensively + numberOfOffensiveOrders
```

With `O` offensive orders, `M` manual `intercept` orders, `S` `selfIntercept` markers and
`D` discharges spent offensively, and taking a manual intercept as costing one discharge and a marker
as costing nothing:

| site | expression | with the rewrite |
|---|---|---|
| `isValidInterceptor` refuses when | `O + M >= guns` | ⟺ `M >= pool − D` ⟺ nothing left ✔ |
| `automateIntercept` grants | `guns − O − M` | `= pool − D − M` = discharges left ✔ |

`S` cancels out of both, which is *why* a marker is free. The client mirrors the identical formula in
`updateGunAccounting()`, because `weaponManager`'s manual-intercept cap
(`counts.offensive + counts.intercept >= weapon.guns`) counts orders the same way.

⚠️ **The client cannot wait for `initializationUpdate`** to do that — it only runs when a system icon
renders ([[arch_lazy_window_side_effects]]), and the player reaches the INCOMING list without
necessarily re-rendering anything. So `resolveFireOrder` and `doMultipleSelfIntercept` call it
directly too.

**Consent differs between the two, and only one of them needed hooks.** `isValidInterceptor` demands
a `selfIntercept` marker when `max(loadingtime, normalload) > 1`. That is 1 for the full Array — it is
simply auto-assigned with whatever it did not fire — and **2 for the Medium**, which therefore must
consent. Because `canSplitShots` is true, `weaponManager.canSelfInterceptSingle` routes that question
through `checkSelfInterceptSystem()`, which is **`false` on `ShipSystem.prototype`**: without the
override the Medium could never consent and so could never defend at all. One marker is consent for
the whole weapon (a second buys nothing, since `S` cancels), and defensive orders are stamped Single
Shots mode.


**How it was proven — FIRST BUILD.** Four throwaway functional tests. The two that survive
re-statting are the range-penalty spy test and the client mirror; the other two were re-run after
every change.

- **Server, mechanics (33 checks):** the hull mounts it and `getSystemsByNameLoc("Lightning Array", 1)`
  — the lookup the hit chart itself uses — finds it and does *not* find it in another section; the
  allocation parsed and stripped; `->shots` reset; over-allocation clamped mid-volley; a spent pool
  yields 0; `n=99` clamps to the largest tabled row; an order that never went through
  `beforeFiringOrderResolution` does 0 rather than firing free; the Medium starts uncharged while the
  full Array does not.
- **Server, range penalty (18 checks):** ⭐ **the structural check a table test cannot make** — a spy
  subclass recorded every `calculateRangePenalty` call the *parent* made during a real
  `calculateHitBase` on a two-ship gamedata, and confirmed the parent **does** reach the override,
  **with the order's fused count live**. The test reports INCONCLUSIVE rather than PASS if the parent
  short-circuits before getting there, because a spy that was never called proves nothing. Plus: the
  transient is cleared on return, and cleared again when the parent path aborts.
- **Server, refinements (44 checks):** Single Shots forced to one discharge even when the order claims
  four; mixed-mode turns; and ⭐ **the gun accounting checked against the REAL engine** —
  `Firing::isValidInterceptor` invoked by reflection across ten scenarios (idle, 1–4 single shots,
  combined 2 and 4, manual intercepts, marker-only, marker + combined) with `automateIntercept`'s
  budget expression reproduced verbatim beside it. Both must agree with the discharges actually left.
  A control case proves the rewrite is what fixes it: without it, a combined-4 shot leaves **3 phantom
  intercepts**; with it, 0.
- **Client (62 checks):** `special.js` **evaluated**, not merely parsed, against stubbed globals
  (a parse would not catch a broken prototype chain — `howto_verify_react_bundle`); both classes
  exist as `window` globals, which is what `SystemFactory`'s `new window[name]` needs; **all six JS
  tables and both mode constants compared field-for-field against the PHP side dumped by
  reflection**; the gun accounting replayed across the same eleven server scenarios with the manual-
  intercept cap agreeing every time; the Medium's guns tracking `turnsloaded`; and the consent hooks.

**How the 2026-09-03 REVISIONS were proven.** Two more throwaway tests, same method, grown as the
refinements landed: **39 server checks and 99 client checks**.

- **Server (39 checks):** the count read off `->shots` alone and `->notes` left as the plain `"Split"`;
  clamping against tables then pool; a spent pool giving 0 damage; ⭐ **a MIXED-MODE turn** — two
  combined and two single orders resolved together, each priced by ITS OWN `$order->firingMode`, with
  the mode-2 order claiming four discharges still firing one and the `guns` arithmetic landing on the
  right answer across the lot; `guns` landing on the discharges left for `isValidInterceptor` **and**
  `automateIntercept` at combined-4 and combined-2; the damage bands (⭐ still compared against the
  1-discharge **ceiling**, because rows 1 and 4 overlap); `getStartLoading()` seeding 1 — asserted
  three ways: below `getNormalLoad()`, at or above `getLoadingTime()`, and producing a pool of 1 —
  while the full Array still starts ready; row 1 of each table still equalling the weapon's own
  `fireControl` / `rangePenalty`; and ⭐ **`multiModeSplit` proven to REACH the client** by calling
  `stripForJson()` on the Lightning Array of a real `Traveler` (it needs a hull behind it — the base
  method asks the ship about Hyach Specialists), with an ordinary weapon on the same hull as the
  control that must NOT publish it.
- **Client (99 checks):** `special.js` evaluated again, then **the declaration flow driven exactly the
  way `weaponManager.targetShip` drives it** (call the hook, push what it returns, ask
  `checkFinished()`): three clicks on one ship producing ONE order carrying 3 with `damageclass
  'Sweeping'` and `notes "Split"`; a fourth fusing to 4; a fifth refused with a message; two targets
  staying two orders; Single Shots producing three separate one-discharge orders; ⭐ **both prediction
  mirrors moving together** — the fire-control delta and the range penalty both stepping from row 1 to
  row 2 as the shot grows, with an assertion that they really differ; ⭐ **a declared order reading at
  its OWN count while the look-ahead reads one higher**; another weapon's order ignored; the Medium
  refusing to combine at one turn of charge and fusing at two; the PHP tables re-compared
  field-for-field; and `confirm.askForMultipleValues` stubbed to **throw**, so reaching the end of the
  run is itself the proof that no dialog is opened any more.

  The refinements added: ⭐ **peeling** — 4 → 3 → 2 → 1 → gone, the order keeping its id throughout,
  one discharge handed back each time (not four), `guns` re-derived, and the stored `chance`
  **re-priced at the new count** and compared against what the mirrors give for that count; a Single
  Shots withdrawal removing a whole order; ⭐ **mixed modes coexisting** — a combined shot and a single
  shot standing together, withdrawal preferring the mode the weapon is in and leaving the other alone,
  and fusing resuming when the mode is switched back; the enemy-tooltip fallback firing when the
  current mode has nothing at that target (with a non-vacuity assertion that it really had nothing);
  a `selfIntercept` marker surviving "remove a firing order"; `removeAll` clearing everything; and the
  INCOMING row counting **discharges, not orders**, with `shotsInGroup` reproduced verbatim and a
  hookless weapon as the control so the Slicer's dice-in-`->shots` cannot start counting as shots.

⭐ **Every test asserts it is not vacuous** — that the two classes really carry different tables, that
the scenarios really produce different gun counts, that the mode constants differ, that the fighter
and capital columns differ, that the two prediction mirrors really move. This earned its keep twice in
one sitting:

1. The damage-band check quietly stopped discriminating once the real stats landed, because row 1
   (25–70) and row 4 (40–220) **overlap**. The non-vacuity assertion caught it; the fix was to sample
   400 rolls of each and compare against the 1-discharge ceiling instead.
2. The table comparison caught the JS mirror still holding the old placeholder numbers after the
   control sheet was applied to the PHP only — exactly the silent client/server drift it exists for.

Regression gate after the revisions: `checkShipData.php` PASS (0 new findings), replay harness
131 passed / 1 failed — game 4325, the known pre-existing clean-tree failure, and its diff is
movement/`waiting` fields, nothing to do with weapons.

**Left for the user:** a hull mount for the Medium Array — the Traveler's hit chart never named one,
so it is built and unplaced. `Traveler` carries one `LightningArray` in the front section and its
chart row is restored. Icons landed 2026-09-03 (`LightningArray.png`, `LightningArrayMed.png`).

### 3.3 Wide-Beam Lightning Array (system enhancement)

Two registry entries — `SYS_WBLA` (300 pts) and `SYS_WBMLA` (200 pts) — following the existing
`eligible`/`limit`/`price`/`apply` quartet. `limit` is 1 (single-level refit, so `priceStep` is 0).
`eligible` = `$system instanceof LightningArray` (resp. `MediumLightningArray`) and
`!($ship instanceof FighterFlight)` — the latter is already guaranteed by the caller, but state it.

**The `factionAge > 2` gate** ([Enhancements.php:3445](source/server/model/ships/Enhancements.php#L3445))
must become per-enhancement rather than whole-ship, or no Walker hull is offered anything.
Recommended shape: keep the cheap whole-ship exit, but make it
`if($ship->factionAge > 2 && !self::hasAncientSystemEnhancements($ship)) return;` with a small
allow-list of the Ancient-eligible IDs, and add a `factionAge` test to each existing enhancement's
`eligible` so the five current ones stay young/middleborn-only. **Do not simply delete the gate** —
that opens Gunsights, Hardened Armour and the rest to every Shadow, Vorlon and Kirishiac hull in
the game, which is a balance change nobody asked for.

⚠️ **Wide Beam is mode 3, not mode 2** — Stage 2 took mode 2 for `Single Shots`. Add
`MODE_WIDEBEAM = 3` alongside the existing constants in both `specialWeapons.php` and `special.js`,
and remember `beforeFiringOrderResolution` already branches on `$order->firingMode`.

**Per-turn declaration** is a **firing mode** (`'Wide Beam'`), not a purchase-time flag: the
enhancement buys the capability, the mode is chosen when firing. Effects:
- `-2 per damage die, minimum 1 per die` — override `getDamage()`; ⚠️ the floor is **per die**, so
  it cannot be applied to the total. Roll dice individually.
- `noCollateral = false` with a 50% flash amount (25% when the target hex is in `$edfHexes`) —
  the seam is `doCollateralDamage`'s `$flashDamageAmount` parameter.
- **1-turn cooldown** — bump loading, following the `overloadturns` cooldown machinery.

**Timing deviation, accepted (D6):** the rules put this in Prepare Weapons (Initial Orders); FV
firing modes are chosen in the Fire phase, and that is where it goes. The cooldown cost keeps it a
real decision rather than a free upgrade. The faithful alternative — a phase-1 toggle in a React
menu, precedent `GraviticAugmenterMenu` / `MinorThoughtPulsarMenu` — was considered and rejected as
not worth a menu plus an IndividualNote round trip.

### 3.4 Chromatic Pulse Driver

Accelerator (as §3.2) with a second firing mode, `'Scanning'`.

- Scanning mode: `getDamage()` returns 0, no pulse behaviour, but the to-hit roll resolves normally.
- On a hit against a unit carrying **any** `DefensiveSystem` whose `getDefensiveType()` is a shield
  type, record one point of adaptation.

**"The same race" is the raw `$ship->faction` string (D7)** — no faction families, no normalising.
Minbari Federation and Minbari Protectorate are two races and adapt separately; so does any custom
or `whatif` hull carrying its own faction string. ⚠️ Read `faction` off the **target hull**, never
off the ship directory name — [the two do not always match](source/server/controller/shipLoader.php),
which is the whole reason `getFactionDirMap()` exists.

**Persistence:** an `IndividualNote` on the CPD system, `notekey = "CPDSCAN"`,
`notekey_human = <faction>`, `notevalue = <count>`.
⚠️ **`notekey` and `notekey_human` are `varchar(40)` and overflow is a fatal that aborts the whole
player submission** — a 41-character value killed a movement submit once. `substr($x, 0, 40)`.

**Publication:** `onIndividualNotesLoaded` populates a static
`CpdScanRegistry::$adaptation[teamId][faction] = n`, read by `Shield::getDefensiveHitChangeMod` /
`getDefensiveDamageMod` ([baseSystems.php:937](source/server/model/systems/baseSystems.php#L937))
and their `EMShield` / `GraviticShield` siblings.
⚠️ Reset the registry in `DBManager::getSystemDataForShips` — §2.1's double-load trap.
⚠️ *"starting in the next Adjust Ship Systems segment"* — only count notes whose turn is **strictly
less than** the current turn.

**ShadingField** gets its own hook: adaptation is spent on its shield-type properties first, and
only then on profile reduction. Read `CHAMELEON_SENSORS_PLAN.md` and
`arch_stealth_toggle_forecast` before touching it — the stealth forecast must stay own-team-only.

### 3.5 Energy Draining Field / Variable EDF

`class EnergyDrainingField extends ShipSystem implements SpecialAbility, EdfSource`.

- `$radius` constructor arg (default from control sheet) plus a `$variable` flag.
- **Double power for extra radius** is the existing **boost** mechanism (`PowerManagementEntry`
  type 2, allocated in the Ship Power segment = Initial Orders = exactly where the rules put it).
  `boostable = true`, `maxBoostLevel = 1`, `getEdfRadius()` returns `$radius + ($boosted ? $bonus : 0)`.
  No new power concept.
- `canOffLine` — *"the player may deactivate the field"*, all-or-nothing, which is already what
  offlining a system means.
- Owns the §2.2 resolver.

**Criticals** — new classes + `$possibleCriticals`:
```php
// fixed-radius EDF
protected $possibleCriticals = array(21 => "EdfRadiusReduced");   // floor: radius 1
// variable EDF
protected $possibleCriticals = array(20 => "EdfBoostLost");       // then radius -1 each, floor 0
```
Both escalate on the `hasCritical()` **count**, so no per-crit bookkeeping.

**EDF Range enhancement** (`SYS_EDFR`): `price(level) = 50 * 6 * (currentRadius + level + 1)`,
i.e. 50 × the hexes added. ⚠️ On a Variable EDF it raises the **normal-power** radius only.

### 3.6 Energy Draining Mine

`class EnergyDrainingMine extends AoE implements ...` — the `BallisticTorpedo` storage pattern
(§1.3) over the `EnergyMine` ballistic shape ([AoE.php:140](source/server/model/weapons/AoE.php#L140)).

- `normalload = 3`, `canSplitShots = true`, `hextarget = true`, `ballistic = true`,
  `getStartLoading()` → 0 (*"may not begin the scenario fully loaded"*).
- **Scatter is its own table, not `AoE::fire`'s.** Override `fire()`: `d20` 1–15 on target;
  16–20 → `d10`, 1–6 scatters `d5` hexes along the indicated facing, 7–10 no effect.
- **On landing, spawn a terrain unit** carrying an `EdfSource` with radius 1 (7 hexes), using the
  `spawnHyperspaceWaveform` recipe at
  [specialWeapons.php:11080](source/server/model/weapons/specialWeapons.php#L11080):
  `Manager::insertSingleShip` → `insertSingleMovement` (a `deploy` order) →
  `SystemData::initSystemData` + `insertSystemData` → `unset($gamedata->ships[$id])`.
- **Removed in the Vortex Closure segment one turn later** — the Spatial Cutter's
  `generateIndividualNotes` case 4 is the exact precedent (silent removal, no DamageEntry
  animation).
- Criticals: `20+ → EdfMineRechargeSlowed`, escalating on count (1/2 turns, 1/3, …).
  `IncreasedRecharge1` already exists and may be reusable as-is.

⚠️ Spawned ships: `LAST_INSERT_ID` returns a **string**. Cast it.
⚠️ A new shipid-keyed table would need adding to both `deleteGames()` and `leaveSlot()` — this
feature adds none, but the terrain rows follow the existing spawn cleanup.

### 3.7 Energy Draining Net

`class EnergyDrainingNet extends ShipSystem implements EdfSource` — `getEdfRadius()` returns 0.

Linking runs inside the §2.2 resolver, before the drain sweep, and contributes hexes to `$edfHexes`:

1. **Pairwise:** every pair of active Nets at distance ≤ 3 links; the hexes between them join the
   field. `HexZone::line()` gives the corridor.

   **Ties are resolved deterministically, no UI (D8)** — but toward the corridor a player would
   actually pick, since the field exists to drain enemies and to lengthen the targeting corridor
   through it. Rank candidate corridors by, in order:
   1. **most enemy units standing in the corridor's hexes** (the player's obvious choice);
   2. **most hexes not already in `$edfHexes`** (adds the most new field);
   3. **lowest `(q, r)`** — a pure stability tie-break, never a game rule.

   ⚠️ **The ranking must be a total order.** This runs during the Critical Hit Step, so enemy
   positions are settled and the answer is reproducible — but it runs on **both** of the request's
   two gamedata loads and again on every replay, so any residual tie left unbroken would let the
   corridor flip between them. Rule 3 exists solely to guarantee that it cannot.
2. **Closed-area fill:** `HexZone::hull()` over the linked Net positions; every hex the hull
   contains or touches joins the field, **capped at `2 × (number of Nets) − 1` filled hexes**
   (*"less than double"*). Over the cap, fill nothing and log it — silently filling a partial area
   is worse than filling none.
   ⚠️ `convexHull` discards collinear middles, so three Nets in a line leave a 2-point hull that
   `pointInPolygon` rejects; the edge-walk in `isUnitInShearingZone` handles this and must be
   carried over.
3. *"It is not necessary to count those hexes in an EDF generated by another vessel"* — hexes
   already in `$edfHexes` do not count against the cap.

Criticals: `20+ → EdnPowerDoubled`, escalating on count (×2, ×3, ×4 …). `powerReq` becomes
`base × (1 + count)`.

### 3.8 EW Detector

`class EWDetector extends ShipSystem implements SpecialAbility` — `specialAbilities[] = "EWDetector"`.

**Two stages, because the second one is expensive.**

- **Stage A — the allowance.** A fleet sweep computes the saved-EW budget: detectors 1–4 give 1
  each, 5–8 give ½ each, 9+ give ¼ each; round ¼ and ½ down, ¾ up. Ships must be in range of a
  detector both before and after movement (ELINT included). Display the allowance; nothing yet
  allocates it. Cheap, and immediately makes the system legible.
- **Stage B — late allocation.** EW is submitted **only** in `InitialOrdersGamePhase::process`
  ([InitialOrdersGamePhase.php:219](source/server/Phase/InitialOrdersGamePhase.php#L219)) and the
  client hard-gates the UI on `gamedata.gamephase != 1`
  ([ew.js:512](source/public/client/ew.js#L512)). Making one point allocatable at the end of
  Movement means: an EW write path in `MovementGamePhase::process` (budget-clamped, additive only —
  never allow a phase-2 submission to rewrite phase-1 allocations), the client gate relaxed to
  exactly the saved allowance, and a masking review, since EW visibility is phase-conditional
  (`deleteHiddenData`'s phase-1 guard).

⚠️ **Stage B changes a shared, load-bearing path for every faction in the game.** It is the
highest-blast-radius item in this plan. Budget a full `masking` + `snapshot` harness pass for it
alone. Without it the detector does nothing of value, so it cannot simply be dropped — but it
should land last among the non-SCT work.

### 3.9 Sensor Charge Transceiver

Per D2. Hex-target, split-shot weapon where **each shot is a waypoint**.

**Client — `SensorChargeTransceiver.prototype.doMultipleHexFireOrders(shooter, hexpos)`**
(the existing hook at [weaponManager.js:3843](source/public/client/weaponManager.js#L3843)):
- `maxVariableShots` = manoeuvres available (4 base, +1 per 2 points of extra power allocated in
  the Ship Power segment; the range/manoeuvre split is the player's, per the rules).
- Each accepted pick appends a waypoint fire order with `x`/`y` and a `SCT|w:<index>` note token.
- Reject a pick that would exceed remaining range; `checkFinished()` when manoeuvres are spent.

**Renderer:**
- **Yellow** `LineSprite` arcs along the confirmed path (waypoint → waypoint).
  `LineSprite` ([renderer/sprite/LineSprite.js](source/public/client/renderer/sprite/LineSprite.js))
  takes start/end/width/z/colour/opacity and is the right primitive; `BallisticLineSprite` is the
  styling reference.
- **Blue** arcs from the latest waypoint to every hex still reachable on the remaining range.
- Hook into `FirePhaseStrategy` beside the existing overlay pair
  `onShowTargetedHexagonInArc` / `onRemoveTargetedHexagonInArc`
  ([FirePhaseStrategy.js:176](source/public/client/renderer/phaseStrategy/FirePhaseStrategy.js#L176)).
- ⚠️ **Every sprite add/remove outside the animation list must call `requestRender()`** — the render
  loop is idle-gated and the arcs will simply not appear otherwise.

**Server — `beforeFiringOrderResolution`:**
1. Read the waypoints in index order; re-clamp count against manoeuvres and total length
   (`HexZone::line` per segment) against range. **Client input is never trusted.**
2. Require the last waypoint to hold a **friendly ship with an operational SCT**; if not, the
   charge is lost — no damage this turn, and recharge drops to 1/turn.
3. Collect enemy units standing on path hexes; emit **one damage order per hex** (the rules'
   "only one target per hex"), all at full to-hit with **no range penalty** but all EW and fire
   control applying.
4. Damage: single Standard-mode volley with `noOverkill = true`
   ([weapon.php:177](source/server/model/weapons/weapon.php#L177)).
5. **Receiving-SCT self-damage:** 1 point per 2 full hexes of range remaining, rolling criticals
   normally. Use `howto_create_fire_orders`' attribution pattern so the combat log reads sanely.

**Recharge:** 1 per 3 turns normally; 1 per turn when the charge was lost to interception.
⚠️ A variable recharge rule must read the **cooldown delay**, not `$loadingtime` — the same trap
recorded for `JumpEngine` in `JUMP_GATES_PLAN.md`.

---

## 4. Stages & exit criteria

Ordered so that each stage is independently shippable and the risky shared-path work lands last.

| # | Stage | Exit criterion |
|---|---|---|
| **0** ✅ | `HexZone` extraction (§2.3) — **DONE 2026-09-03** | Replay harness identical before/after; 13,504-case differential test against the pre-move bodies, zero mismatches. |
| **1** ✅ | Faction skeleton — directory, tier line, and the **user's Walker test hull** (D4) — **DONE 2026-09-03** | `Traveler` generates into `Walkers of Sigma-957.json`; `checkShipData.php` clean (0 new findings, down from 5). |
| **2** ✅ | Lightning Array + Medium Lightning Array — **DONE 2026-09-03; targeting REVISED twice the same day after play testing** | 157 checks green on the first build, +138 across the revisions, incl. a JS-vs-PHP comparison of all six tables and both mode constants, and the intercept gun accounting verified against the REAL `Firing::isValidInterceptor` over ten scenarios. Two firing modes (Combined / Single), now `multiModeSplit` so both are usable in one turn; both weapons intercept. Revisions: **no allocation dialog** — one click = one discharge, repeat clicks on one target fuse; count rides in `->shots`; `'Sweeping'` + `"Split"` so the shot shows in the shooter's INCOMING list, which counts DISCHARGES not orders; withdrawing PEELS one discharge off a combined shot; Medium starts 1/2, not 0/2. |
| **3** | Chromatic Pulse Driver | Scanning hit reduces the target race's shields fleet-wide from the **next** turn; survives a reload; does not leak across the double gamedata load. |
| **4** | EDF + Variable EDF (§2.1 + §2.2) | Field map published and drawn; drain lands as crits; targeting penalty matches server↔client to the point; Enormous clamp, own-fleet immunity and multi-field non-stacking all hold. |
| **5** | EDF criticals + EDF Range enhancement + the `factionAge` gate fix (§3.3) | Ancient hulls see **only** the new IDs; the five existing enhancements are unchanged for every young/middleborn hull in the corpus. |
| **6** | Energy Draining Mine | Stores 3, launches any number, scatters on its own table, spawns a 7-hex field, cleans up one turn later. |
| **7** | Energy Draining Net | Pairwise links; closed-area fill respects the `2N−1` cap; three collinear nets still link. |
| **8** | Wide-Beam enhancements | Per-array purchase; per-die floor; cooldown; collateral at 50%/25%. |
| **9** | Sensor Charge Transceiver | Waypoints, arcs, path validation, per-hex targets, receiving-SCT self-damage, dual recharge. |
| **10** | EW Detector — Stage A then Stage B (§3.8) | Allowance correct at 1/4/5/8/9 detectors; phase-2 EW write is additive and budget-clamped; full `masking` + `snapshot` harness pass. |

**Every stage:** run `fvbuild.ps1 -Check` (ship-data validator + replay harness). ⚠️ The baseline
drifts on a clean tree — never read a pre-existing FAIL as your regression, and **never
blind-re-record**. The method that works: capture the check output, `git stash push -- source/`,
re-run, `git stash pop`, diff with timings normalised.

Stages 4, 5 and 10 each change a serialised property or a shared payload, so the harness `check`
is mandatory rather than advisory on those.

**Each stage opens on a control sheet (D4)** — the user lands a Walker hull carrying a basic version
of that stage's system, and the stats are read out of the hull file. Stages **0 and 1 need nothing**:
Stage 0 is a pure code move and Stage 1 *is* the first test hull.

---

## 5. Trap register

Collected from the survey; each one has bitten this codebase before.

1. **The double gamedata load.** `Manager::advanceGameState` loads, then the phase's `advance()`
   loads again — in one request. Any per-load static (§2.1, §3.4) must be reset in
   `DBManager::getSystemDataForShips` before the `onIndividualNotesLoaded` sweep.
2. **POST-side ships have no enhancements and no loaded notes.** Anything reading a Wide-Beam
   purchase or a CPD scan note inside `generateIndividualNotes` / `process()` reads a class default
   and fails **silently, forever**. Server-authoritative checks belong in `advance()`.
3. **`advance()` has already set the next phase** before its ship loop. Never branch on
   `$gamedata->phase` there; pass an explicit checkpoint.
4. **`notekey` / `notekey_human` are `varchar(40)`** and overflow is a fatal that aborts the whole
   submission. `substr($x, 0, 40)`.
5. **FV initiative is d100** — every modifier is 5× tabletop. The EDF's `−20` cap is `−100` here.
6. **Client system fields are shared by reference** across same-phpclass instances. Any per-instance
   mutation (a modified `data` tooltip, a per-array Wide-Beam flag) needs `isModified` handling or
   it bleeds onto every sibling array on the ship.
7. **Positional system ids.** Ids are construction order; a variant needs a **new phpclass**, never
   a reordered constructor.
8. **`ShipCompactor` strips blueprint keys.** Adding a public property to a system is not free —
   check whether the compactor's `$falseKeys` / `$emptyArrayKeys` / `$deadSystemKeys` would remove
   it, and whether every client read behaves identically when it is `undefined`
   (`=== false` does **not**).
9. **`empty array()` encodes as JSON `[]`, not `{}`** — the `$edfHexes` map must never be emitted
   empty as an array where the client expects an object.
10. **Render-loop idle gating** — any scene mutation outside the animation list needs
    `requestRender()`. The SCT arcs will silently not draw.
11. **`splitShots` `$guns` padding** must skip manual `intercept` orders (Slicer, game 4306).
12. **Filename must equal class name** or the ship silently does not exist.
13. **Regenerate autoload** (`fvbuild.ps1 -Autoload`) after every new class; never hand-edit
    `source/autoload.php`. Regenerate **statics** whenever something reaches the client through the
    blueprint rather than gamedata.
14. **Never commit the two `.legacy.bundle.js` files.**

---

## 6. Open questions — ALL RESOLVED 2026-09-02

Kept as the record of what was asked and why; the rulings are D5–D8 in §0 and are folded into the
sections they affect.

**Q1 — EDF and concealment. → D5, out of scope.** The Walkers have no stealth function, so the
question is academic. §2.1 records what would make it live again and where the guard would go.

**Q2 — Wide-Beam declaration timing. → D6, firing mode in the Fire phase.** §3.3.

**Q3 — EDN corridor choice. → D8, deterministic with a player-preferring tie-break.** Prefer the
corridor containing an enemy unit, then the one adding the most new field, then lowest `(q, r)`
for stability. §3.7.

**Q4 — CPD adaptation scope. → D7, the raw `faction` string.** No faction families. §3.4.

**Q5 — Control sheets. → D4, supplied as a Walker test ship per system.** Each stage opens when the
user lands a hull carrying a basic version of that stage's system; the stats are then read out of
the hull file rather than transcribed. Still needed across the whole plan:
damage / range / fire-control / power / RoF for the four weapons, radii and power for the three
field systems, EW-Detector range, and point costs throughout.

---

## 7. What this plan deliberately does not do

- **No database schema change.** Everything persists through `tac_critical.param`,
  `tac_individual_notes` and `tac_systemdata`, all of which already exist.
- **No new phase**, no change to the turn loop.
- **No new plotting engine** — D2's design reuses `doMultipleHexFireOrders`.
- **No rewrite of the Gravitic Mine geometry** — Stage 0 moves it unchanged and proves it with the
  harness.
- **No blanket removal of the `factionAge` enhancement gate** — §3.3 narrows it instead, so no
  existing Ancient hull gains an enhancement it does not have today.
