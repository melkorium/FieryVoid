# Officers — Implementation Plan

Status: **RULED 2026-10-09 — nothing built.** Written against `dab951bf5`. The user ruled D1–D12 and
QX1–QX5 the same day; three rulings changed the draft (D3 kill roll, D9 eligibility and one officer per
flight, D10 filter chips → new D13), logged at the top of §1. Each officer's own rules questions (§6)
still only need ruling when that officer's stage starts. Next step: Stage 0's baselines, then Stage 1.

> ### Relationship to WEAPON_ENHANCEMENTS_PLAN.md §11
> §11 concluded that officers "are an ordinary ship-level enhancement … and need nothing from this
> plan", because every officer was assumed to sit in a fixed system by type. Two facts in the new
> brief change that: officers can be **killed** by damage to their system, and two of them (Expert
> Gunner, and the Matter Weapons Expert when mounted on a weapon) are **placed by the player** on a
> system of their choice. The rest of §11 still holds. The fixed-post officers really are
> `IMPR_ENG`-shaped and are built that way here. §11.3's warning about speculative machinery is why
> this plan adds no table, no column and no stored "assignment" state.

---

## 0. Summary

**What an officer is.** An enhancement with a **post**: the one system on the unit that he lives in.
While the post stands, his bonus applies. If the post is destroyed he is **disabled** for the rest of
the battle, even if Self Repair later revives the system. Every time the post takes damage, its normal
critical roll also decides whether he is **killed**: 20 or more, modifiers included (D3). His own team
sees a gold rank insignia on the post and a line in its System Info tooltip. Nobody else sees anything
until the game is over (or, in a later stage, an Expert Analyst looks).

**How it is built.**

- Officers with a fixed or random post, and every fighter officer, are ordinary **ship-level
  enhancement rows** with an `OFF_` id: `enhancementOptions`, `tac_enhancements`, `tac_saved_enh`,
  and the buy dialog's **Officers** section, which is already reserved and stays hidden while empty
  ([confirm.js:837](source/public/client/UI/confirm.js#L837)). Their post is never stored. One
  resolver picks it from the blueprint at every load, the way `IMPR_ENG` picks its Engine.
- The two officers the player places are entries in the existing **per-system** registry
  ([Enhancements.php:3645](source/server/model/ships/Enhancements.php#L3645)), so they get
  `tac_sys_enhancements`, the D13 name check, saved-fleet re-validation and the destroyed-system
  refund for free.
- Both tracks feed one runtime list, `$ship->officers`, holding *(officer, post, out-of-action turn,
  disabled or killed)*. Every effect, insignia, tooltip line and visibility rule reads that list and
  nothing else.
- **Disabled** needs no storage. The server loads every damage row of every turn
  ([DBManager.php:3391](source/server/controller/DBManager.php#L3391)), so "the post was destroyed
  on turn N" is re-derived at each load. **Killed** is a dice result, so it is stored: one
  `OffKilled` individual note per death, taken out of the note stream at load so that no system class
  ever sees it (§4.4).
- **No schema change anywhere.**

**Ranking at a glance** (reasons in §3):

| Easy | Medium | Hard |
|---|---|---|
| Expert Helmsman | Expert Engineer | Expert Quartermaster |
| Expert Scanner | Expert Scavenger | Breaching Expert |
| Expert Technician ¹ | Expert Navigator | Expert Evangelist |
| Matter Weapons Expert (C&C) | Expert Jump Officer | Expert Laser Technician |
| Expert Geneticist | Expert Analyst | Expert Software Engineer |
| Expert Dogfighter ² | Expert Coordinator ² | Expert Ion Technician |
| Expert Missileer ² | Expert Gunner, core ³ | Expert Surge Officer |
| Expert Evader ² ⁴ | Matter Weapons Expert (weapon) ³ | Expert Ballistic Officer |
| | Loading time −1, shared ⁵ | Expert Anticipator |
| | | Expert Gunner, once-per-battle abilities |
| | | Expert Electrician ² |

¹ On the "best of the three, automatically" reading (§6, Technician). ² After the fighter-officer framework
(Stage 4). ³ After the placed-officer framework (Stage 8). ⁴ If always on; Medium as a per-turn
toggle. ⁵ Medium-Hard; one build serves both Gunner and the weapon-mounted Matter Weapons Expert.

**Recommended order:** the framework, with Expert Helmsman as its test vehicle (Stages 1–2); the rest
of the Easy tier (3); fighters (4); then the Medium tier one officer per stage. Each Hard officer gets
its own short plan and ruling round when it is reached. Most of them have a cheap half that could
ship earlier (§3.3, right-hand column). The buy dialog's **filter chips** (D13) depend on nothing
here and can be built first, on their own (Stage 2a).

---

## 1. Decisions

> **Rulings, 2026-10-09.** D1, D4–D8, D11 and D12 accepted as drafted; D2 and QX5 accepted without
> comment. Changed or added:
> - **D3**: the rulebook's own text. A destroyed post **disables** the officer. Any damage to the post
>   triggers its normal critical roll, and 20 or more (modifiers included) **kills** him. The roll is
>   made even when the system is destroyed.
> - **D9 / QX3 / QX4**: no officers on Ancient hulls, OSATs (mines included) or terrain. Bases only
>   where the post exists. Ships: one of each type, "except as noted". Flights: one officer in total.
> - **D10**: plus filter chips in the buy dialogs → new **D13**.
> - **QX1**: the Evangelist's −2 is tabletop, so −10 in FV. The others are FV units as drafted.
> - **QX2**: yes, a critical roll of 20+ kills (D3).
>
> D11 and D12 were accepted before D3 changed; their rows below now show D3's two states.

| # | Decision | Why |
|---|---|---|
| **D1** | **Two purchase tracks, one runtime list.** Fixed-post and random-post officers, and all fighter officers, are ship-level rows. Officers whose post the player picks are per-system registry rows. `Officers::resolve()` merges both into `$ship->officers`. | 22 of the 25 officers have a post the rules set, by system type or by random draw. Putting them on the per-system track would mean buying a Helmsman by finding the C&C in the ship window, while the reserved Officers section stays empty. Putting the 2 placed officers on ship-level rows would mean a weapon dropdown per Gunner slot (three choice-valued rows on a capital, the `CHAM_DISG` widget shape) and re-inventing the D13 name check. *Alternative, not recommended:* everything per-system. One track, but worse purchase UX and a per-weapon offer tuple for every officer in every static blueprint. Breaching Expert is the 25th and fits neither track (§3.3). |
| **D2** | **Track-1 posts are resolved, never stored.** They are picked from **blueprint** values at the top of `BaseShip::onConstructed`, before any enhancement moves an output (§4.3). A post destroyed by pre-battle damage leaves its officer disabled from the start (§4.9). | `ADV_ENG` set the precedent ([Enhancements.php:2516](source/server/model/ships/Enhancements.php#L2516)). Poor Crew takes thrust off the strongest Engine, so on a two-engine hull "strongest" depends on row order unless it is asked before any row runs. The lobby mirror picks the same way, so lobby and game agree. |
| **D3** | **RULED: two ways out of action.** The rulebook: *"Officers are considered disabled (and unusable for the rest of the scenario) if the system they are in is destroyed, but there is also a chance they will be killed by any damage to the system. Use the normal critical hit roll to determine this (make a roll even if the system is totally destroyed). If this roll results in a '20' or greater (including any modifications), the officer is killed. This is in addition to any other critical hit effects."*<br>**Disabled** = the post is destroyed, by its own boxes or by its Structure block. **Killed** = the post took damage through its armour this turn, and its critical roll came to 20 or more. That is the roll the post makes anyway, or one made for him when the post was just destroyed (§4.4). The weapon-mounted Matter Weapons Expert is killed by **any** damage through armour, as his own text says. Both states are **permanent** (Self Repair reviving the post changes nothing) and both take effect **next turn**. | Next-turn is the convention every support system follows, `isDestroyed($turn - 1)` ([baseSystems.php:20494](source/server/model/systems/baseSystems.php#L20494)), and it keeps simultaneous fire simultaneous: the Evangelist's +1 cannot vanish halfway through the shots of the turn he falls. FV's critical roll is d20 + the system's **total** damage + both crit modifiers ([ShipSystem.php:1421](source/server/model/systems/ShipSystem.php#L1421)), so on a battered post any further hit is close to certain death. *Disabled* can be derived from the complete damage history. *Killed* is a dice result, so it has to be stored (§4.4). The two states differ for the Evangelist, whose penalty is worded "if killed" (§6). |
| **D4** | **Own team only; the game's end discloses everything; no public log line.** The insignia, the tooltip and the `officers` payload reach the owner and teammates (`isRevealedToCurrentViewer`), and everybody once `TacGamedata::$currentGameFinished`. Officer names never enter the public `enhancementTooltip`. | Same rule and same mechanism as the ✦ ([ShipClasses.php:985](source/server/model/ships/ShipClasses.php#L985)). ⚠️ **This is cosmetic, exactly like the ✦** (WEAPON_ENHANCEMENTS_PLAN.md D8 / §6.3). A few officers change numbers that reach the enemy anyway. §4.6 lists them, so the tooltip never claims a secrecy the feature does not deliver. |
| **D5** | **Effects run in ONE post-pass**, `Officers::apply()`, after enhancements and after the per-system `onConstructed` loop, and before `iniativeadded` is filled ([ShipClasses.php:1779-1789](source/server/model/ships/ShipClasses.php#L1779)). Never as a `case` in `setEnhancementsShip`. | (a) That switch runs rows in `enhid` order, and an order-sensitive effect belongs in a fixed post-pass on both ends ([[arch_enhancement_storage_and_order]]). (b) `isDestroyed()` needs `structureSystem`, which `ShipSystem::onConstructed` fills ([ShipSystem.php:473](source/server/model/systems/ShipSystem.php#L473)). (c) None of the 25 has to precede `Shield::onConstructed`, which is the reason system refits run earlier. |
| **D6** | **A new class, `Officers`** (`source/server/model/ships/Officers.php`), holds the registry and the resolver. `Enhancements.php` gains hook calls only. | `Enhancements.php` is 4,789 lines. WEAPON plan D12's argument applies: one literal per officer, so nothing gets added in three places out of four. |
| **D7** | **ids are `OFF_*`, at most 10 characters** (table in §4.2). | `enhid` is `varchar(10)` in both tables. The prefix routes a row to the Officers section, and keeps it out of the public tooltip, with one test each. It also avoids the existing fighter `NAVIGATOR`. |
| **D8** | **Price = ceil(pct × blueprint `pointCost`)**, the base `ELITE_CREW` uses ([Enhancements.php:244](source/server/model/ships/Enhancements.php#L244)). **Fighter officers are priced per FLIGHT**, not per craft. **Fleet-% officers** (Quartermaster, Evangelist) are priced off the **game's points limit**, not the live fleet total. | The lobby multiplies every fighter enhancement by flight size ([confirm.js:215](source/public/client/UI/confirm.js#L215), [gamelobby.js:4203](source/public/client/gamelobby.js#L4203) and five more sites). "1 per flight, 50 % of one fighter" is not that shape, so it needs a per-row exception (§4.10). Evader's "× number of fighters" is the one fighter officer that already fits. A price on the live fleet total is circular (the officer is part of the fleet) and changes with every purchase. The game's limit is fixed and known in the lobby. |
| **D9** | **RULED.** **Ships: one officer of each type**, several different types per ship, "except as noted" (Gunner up to 3 / 2 / 1 by size; Breaching Expert up to 20 % of pods). **Flights: one officer in total.** **Never on Ancient hulls (factionAge ≥ 3), OSATs (mines extend OSAT) or terrain.** Bases, LCVs and everything else only where the post exists, so a base without a jump drive is never offered a Jump Officer. Faction gates use the faction string. | User ruling (QX3, QX4) and the rulebook: a unit "may not benefit from more than one expert officer of the same type (except as noted)". The flight cap makes a flight's officer rows mutually exclusive in the buy dialog (§4.10). ⚠️ FV_factions.txt:438 still promises Shadow ships "enhancements relating to special officers"; that line now contradicts the ruling. Corillani CPN and OSF exist only in `$notes` prose ([VostovaAMCPN.php:15](source/server/model/ships/corillani/VostovaAMCPN.php#L15)), so the Evangelist needs a per-hull opt-in (`enhancementOptionsEnabled[] = 'OFF_EVAN'`), which is a contributor-area edit. |
| **D10** | **Where they are bought:** track-1 officers in the buy dialog's Officers section; placed officers in the system's own *Add Enhancements & Damage* menu, under an **Officers** sub-heading of the gold section. **Plus filter chips in the buy dialogs (D13).** | One `buySectionOf` change lights the reserved section ([confirm.js:852](source/public/client/UI/confirm.js#L852)). A placed officer is bought where his post is. |
| **D11** | **The insignia** is a small gold double chevron (inline SVG, `theme.colors.enhTitle`) in a top-left **badge row** shared with the ✦. One insignia per post, however many officers are posted there. **Disabled** = grey; **killed** = grey with a red slash. Both stay shown. | Top-right belongs to the meteor badge ([SystemIcon.js:69](source/public/client/UI/reactJs/system/SystemIcon.js#L69)). A shared row keeps ✦ and insignia from overlapping on a gun with both Gunsights and a Gunner. Showing a fallen officer is what tells the player why a bonus stopped. |
| **D12** | **The tooltip** gets a gold **Officers** block in `SystemInfo`, after *Special* and before *Criticals*: one line per officer with his name and a one-line effect, plus "(disabled, turn N)" or "(killed, turn N)" once he is out of action. The text comes from a client mirror of the registry labels. | The lobby has no server round trip, so the text must exist client-side anyway. `system.data` is the wrong channel: it is rebuilt per turn, replaced wholesale by a server `data`, and shared by reference across same-class systems ([[arch_system_info_tooltip_data_flow]]). |
| **D13** | **Filter chips in the buy dialogs** (user request with D10). A row of chips above the sections: **All**, **Ordnance**, **Enhancements**, **Options**, **Officers**, with a chip only for a section that has rows, and no chip row at all when fewer than two sections do. Picking a chip shows only that section; **All** shows them all. The picked chip wears its section's own colours: the `--buy-sec-*` tokens its head already uses (gunmetal, bronze, verdigris, and the default blue that Officers keeps). Built in `confirm.js` and `confirm.css` only, so it does not depend on officers. | A chip **hides** sections and never removes a row. That is the promise the text filter already makes ([confirm.js:811-813](source/public/client/UI/confirm.js#L811)): a hidden row is still bought, counted and read back by gamelobby.js. Reusing the section tokens keeps one colour definition per section ([confirm.css:1885-1914](source/public/styles/confirm.css#L1885)). The text filter box is currently commented out of the shell ([confirm.js:965-967](source/public/client/UI/confirm.js#L965)), so the chips take its place above the sections; if the box ever returns, the two combine. |

---

## 2. What already exists

Every officer below reuses at least one of these. Line numbers verified against `dab951bf5` plus the
current working tree.

| Need | Existing machinery | Where |
|---|---|---|
| A buy-dialog section | `confirm.BUY_SECTIONS` already holds `officers`; a section with no rows stays hidden; rows are filed by `buySectionOf` | [confirm.js:833-857](source/public/client/UI/confirm.js#L833) |
| "The strongest X on the hull" | `Enhancements::strongestSystem`; ADV_ENG's pick-before-the-loop | [Enhancements.php:2452](source/server/model/ships/Enhancements.php#L2452), [:2516](source/server/model/ships/Enhancements.php#L2516) |
| Per-system purchases | the registry; `sanitiseSystemEnhancements` (D13 name check); the D11 destroyed-system refund; own-team ✦ | [Enhancements.php:3645](source/server/model/ships/Enhancements.php#L3645), [:4620](source/server/model/ships/Enhancements.php#L4620), [:4743](source/server/model/ships/Enhancements.php#L4743); [SystemIcon.js:612](source/public/client/UI/reactJs/system/SystemIcon.js#L612) |
| Damage history | every turn's damage rows load server-side; criticals load only while in effect | [DBManager.php:3391](source/server/controller/DBManager.php#L3391), [:3423](source/server/controller/DBManager.php#L3423) |
| The critical roll (D3) | `ShipSystem::testCritical`: d20 + floor(total damage) + system and ship `critRollMod`. Scanner and ELINT Scanner wrap it and call the parent; a missile launcher calls the parent unless its magazine explodes; a fighter's override is a dropout roll instead. Pass 1 skips a destroyed system | [ShipSystem.php:1392-1443](source/server/model/systems/ShipSystem.php#L1392); [criticals.php:84-113](source/server/handlers/criticals.php#L84) |
| Storing a dice result per system | individual notes, distributed to systems in ONE bulk loop at load. ⚠️ Some systems misread a note they do not know: a Jump Engine "takes any note it does not recognise for the pre-jump combat value" | [DBManager.php:3797-3806](source/server/controller/DBManager.php#L3797); [baseSystems.php:8539](source/server/model/systems/baseSystems.php#L8539) |
| Own-team and post-mortem gates | `isRevealedToCurrentViewer`, `isDisclosedToCurrentViewer` | [ShipSystem.php:404-423](source/server/model/systems/ShipSystem.php#L404) |
| Per-game markers | static booleans, reset and set in `markUnavailableSetMarkers()` once every ship is constructed | [TacGamedata.php:426](source/server/model/TacGamedata.php#L426), [:486](source/server/model/TacGamedata.php#L486) |
| Initiative | the d100 roll and tie separator; the sort comparator; common modifiers | [Manager.php:2598](source/server/controller/Manager.php#L2598); [TacGamedata.php:597](source/server/model/TacGamedata.php#L597); [ShipClasses.php:397](source/server/model/ships/ShipClasses.php#L397) |
| Late EW | the EW Detector's saved-EW allowance, whose rules text **is** Expert Scanner | [baseSystems.php:20447](source/server/model/systems/baseSystems.php#L20447); [EW.php:547](source/server/handlers/EW.php#L547), [:615](source/server/handlers/EW.php#L615); [ew.js:1417](source/public/client/ew.js#L1417) |
| +1 damage per die, capped | `Dice::$perDieBonus`, set around one `getDamage()` call | [weapon.php:3103-3132](source/server/model/weapons/weapon.php#L3103); [dice.php:25](source/server/lib/dice.php#L25) |
| Flat bonus damage | `Weapon::getBonusDamage` | [weapon.php:1372](source/server/model/weapons/weapon.php#L1372) |
| +1 fire control | `sysEnhApplyGSGT`: `fireControl` and `fireControlArray`, plus `isModified` | [Enhancements.php:4185](source/server/model/ships/Enhancements.php#L4185) |
| Repairs | `ShipSystem::repairCritical`; Self Repair's C&C-costs-4 rule; setCriticals Pass 2 | [ShipSystem.php:1456](source/server/model/systems/ShipSystem.php#L1456); [baseSystems.php:14433](source/server/model/systems/baseSystems.php#L14433); [criticals.php:115](source/server/handlers/criticals.php#L115) |
| Jump drives | `JumpEngine::$range`; jump-failure % computed at three sites; the arrival scatter and its initiative penalty | [baseSystems.php:6125](source/server/model/systems/baseSystems.php#L6125), [:9648](source/server/model/systems/baseSystems.php#L9648), [:10419](source/server/model/systems/baseSystems.php#L10419), [:10856](source/server/model/systems/baseSystems.php#L10856), [:8481](source/server/model/systems/baseSystems.php#L8481); [ShipClasses.php:497](source/server/model/ships/ShipClasses.php#L497) |
| Power balance | client only: "there is no server twin of getReactorPower anywhere in the tree" | [power.js:555](source/public/client/power.js#L555), [:713](source/public/client/power.js#L713) |
| Weapon families | `$weaponClass` (Laser 49 classes, Ion 17, Matter 33, Electromagnetic 57, Ballistic 46), per mode through `weaponClassArray` | [weapon.php:175](source/server/model/weapons/weapon.php#L175), [:3362](source/server/model/weapons/weapon.php#L3362) |
| Flight modifiers | `FighterFlight::getDamageMod` / `getHitChanceMod`; `isModified` publishes OB and thrust | [FighterFlight.php:374](source/server/model/ships/FighterFlight.php#L374), [:188](source/server/model/ships/FighterFlight.php#L188) |
| Ramming | every non-flight unit already carries a `RammingAttack`; using it is by consent | [ShipClasses.php:2433](source/server/model/ships/ShipClasses.php#L2433); [specialWeapons.php:2438](source/server/model/weapons/specialWeapons.php#L2438) |
| Capture | Marines' `CaptureShip` missions | [ShipSystem.php:951](source/server/model/systems/ShipSystem.php#L951) |
| Buy-dialog sections and colours (D13) | one shell for every buy, edit and bulk dialog; each section's colour as scoped `--buy-sec-*` tokens; a text filter that only hides rows (its box is commented out today) | [confirm.js:833-857](source/public/client/UI/confirm.js#L833), [:920-998](source/public/client/UI/confirm.js#L920), [:1265-1351](source/public/client/UI/confirm.js#L1265); [confirm.css:1880-1914](source/public/styles/confirm.css#L1880) |

---

## 3. The ranking

**Easy:** one or two hooks into a choke point that already exists. No new player input, no new
persisted state, and a client mirror of one line or none.

**Medium:** several hooks, or a rule the client must mirror (movement, to-hit), or a new automated
end-of-turn process, or per-viewer disclosure. Still no new per-turn player input.

**Hard:** needs at least one of: a new per-turn player decision (UI plus a persisted choice), lobby
pricing beyond a flat price, fleet-wide state, a new movement or firing capability, or a post inside
a fighter flight.

Every ranking assumes the framework (Stages 1–2) already exists. The framework itself is roughly a
Medium-sized job (§3.4).

### 3.1 Easy

| Officer | Post, cost | What it touches | Why it is easy |
|---|---|---|---|
| **Expert Helmsman** | C&C, 5 % | +5 `iniativebonus`; one clause in `TacGamedata::sortShips` so a live Helmsman sorts ahead on equal initiative | Initiative is rolled server-side ([Manager.php:2605](source/server/controller/Manager.php#L2605)), and `generateIniative` already turns sort order into distinct values ([:2611-2624](source/server/controller/Manager.php#L2611)). So the tie-break needs no client change. |
| **Expert Scanner** | Sensor Array, 7 % | `EW::getDetectorAllowance` and its JS twin become max(detector ladder, 1 for a live officer). The "no detectors on the board → return" early exits (two server-side, one client-side) must not skip a ship that has one | FV already built this exact rule. The EW Detector's own text says it grants "the enhancement of Expert Scanner" ([baseSystems.php:20447](source/server/model/systems/baseSystems.php#L20447)). The late-EW window, budget and DB write all key off `getSavedEwAllowance`, so they need nothing. |
| **Expert Technician** | Reactor, 9 % | The reactor's `output` at load: + max(2, ⅓ of the largest `powerReq` among systems destroyed before this turn) | Server-side `output` already reaches every client ([ShipSystem.php:319](source/server/model/systems/ShipSystem.php#L319)) and power.js reads `reactor.output`, so there is no client logic. Easy **only** on the best-of reading: under it the "+1 from a deactivated system" branch can never win. If the player chooses each turn, it becomes Medium (a live mirror in power.js). |
| **Matter Weapons Expert, C&C** | C&C, 10 % | Every Matter-class weapon: fire control +1 (`fireControl` **and** `fireControlArray`, `isModified`), and +1 damage in `getBonusDamage` | A copy of `SYS_GSGT`, including its trap: a bump to `fireControl` alone evaporates on the first mode switch ([weapon.php:3349](source/server/model/weapons/weapon.php#L3349)). |
| **Expert Geneticist** | C&C, 5 %, scenario only | `toHitBonus` −1 (exists). +2 to be hit: one public field, one term in `calculateHitBase` and one in weaponManager's mirror. Ramming: a tooltip line | Ramming is already allowed against anything by consent ([specialWeapons.php:2438](source/server/model/weapons/specialWeapons.php#L2438)), so that clause needs no code. "Scenario only" = never offered unless a hull opts in. |
| **Expert Dogfighter** | flight, 50 % of one craft | The flight's `iniativebonus` +5 | The line `ELITE_SW` already uses ([Enhancements.php:2282](source/server/model/ships/Enhancements.php#L2282)). Easy once the fighter framework exists. |
| **Expert Missileer** | flight, 50 % + 1 per missile carried | +N to the OB that the fighter-ballistic branch grants ([weapon.php:1927](source/server/model/weapons/weapon.php#L1927)), plus the weaponManager twin | One term on each side. The price needs the magazine capacity, which `setEnhancementOptionsFighter` already computes ([Enhancements.php:1930](source/server/model/ships/Enhancements.php#L1930)). "+5 OB" needs a ruling (§6, Fighter officers). |
| **Expert Evader** | flight, 100 % × craft count | The flight's OB −1; +1 in `FighterFlight::getDamageMod` | That is the defensive choke-point for flights ([FighterFlight.php:374](source/server/model/ships/FighterFlight.php#L374)), and no client damage mirror exists. Easy if always on; Medium as a per-turn toggle like jinking. |

### 3.2 Medium

| Officer | Post, cost | What it touches | Why it is Medium |
|---|---|---|---|
| **Expert Engineer** | Engine, 5 % | A new automated end-of-turn repair pass after setCriticals Pass 2: one critical per turn, a d20 of 16+ for a C&C critical | `repairCritical` exists. What is new is the pass itself and choosing deterministically. The replay harness does not cover `criticalPhaseEffects` (it stops at Pass 1), so this needs its own tests. Build together with the Scavenger. |
| **Expert Scavenger** | Engine, 25 % | The same pass: one critical, else 4 boxes, never on a destroyed system | As above. |
| **Expert Navigator** | C&C, 7 % | Turn cost one step lower; pivot and roll −1 (min 1); accel/decel −25 % | Every one of these costs is used twice, in movement.js and movement.php, plus the ship window and tooltip readouts and the lobby. Turn cost has no defined "step" ladder in the code (§6, Navigator). |
| **Expert Jump Officer** | Jump Drive, 5 % | Opening range +1; failure chance −20 points; arrival scatter −1 hex and −1 facing step, initiative penalty −20 | Three separate, well-localised hooks. The failure % is computed at three sites ([:9648](source/server/model/systems/baseSystems.php#L9648), [:10419](source/server/model/systems/baseSystems.php#L10419), [:10856](source/server/model/systems/baseSystems.php#L10856)) that should become one helper first. `JUMP_ACC` already shows how to publish a changed range ([Enhancements.php:3497](source/server/model/ships/Enhancements.php#L3497)). |
| **Expert Analyst** | Sensors, 10 %, capitals and ELINT | A per-load "the viewer's team has a live Analyst" static that widens `isDisclosedToCurrentViewer()` (ammo types, hangar contents) and the officer payload | The masks exist. Per-viewer state has to be computed per load and tested per viewer. Two of his clauses are moot in FV: weapon arming is already sent to everyone ([weapon.php:453](source/server/model/weapons/weapon.php#L453)), and cargo contents are not modelled (`CargoBay` is a plain box). |
| **Expert Coordinator** | flight, 50 % | A combat pivot costs 1; the −1 to-hit only from 180° | The cost lives in movement.js `doPivot` ([:1436](source/public/client/movement.js#L1436)) and the penalty in [weapon.php:1872](source/server/model/weapons/weapon.php#L1872) plus its client twin. "180°" needs net rotation counted, not pivot rows. |
| **Expert Gunner, core** | a chosen weapon, 10 %, max 3 / 2 / 1 by size | +1 fire control; +1 per die through `Dice::$perDieBonus` | The effects are Easy. The cost is that he is the first **placed** officer: a registry entry, a ship-wide cap in sanitise and on the client, the menu sub-heading, and the insignia instead of the ✦. |
| **Matter Weapons Expert, weapon** | a chosen weapon | Dies on any penetrating damage; loading −1 (next row) | A placed officer plus the "any damage" kill rule. |
| **Loading time −1** (Gunner and the weapon-mounted MWE) | — | — | Medium-Hard. `loadingtime` is overwritten from the stored loading row on every load ([weapon.php:1189](source/server/model/weapons/weapon.php#L1189)) and from `loadingtimeArray` on every mode switch ([:3350](source/server/model/weapons/weapon.php#L3350)). The client reads it from the blueprint, because `stripForJson` does not send it. Losing the officer mid-charge changes whether the weapon is loaded. One build serves both officers. |

### 3.3 Hard

| Officer | Why it is hard | The cheap half, if wanted early |
|---|---|---|
| **Expert Quartermaster** | Makes this ship's other enhancements and ammo free, which touches every price display, total and the saved-fleet bucket split; a fleet-% price; a random post; "+10 turn delay" is unclear | −30 initiative; the C&C critical modifier |
| **Breaching Expert** | His post is **one pod inside a flight**, and per-craft ids do not exist in the lobby (WEAPON plan D7). Adds a fleet-wide "20 % of all pods" cap, a change to the capture roll ([ShipSystem.php:951](source/server/model/systems/ShipSystem.php#L951)), and "+1 thrust" for one craft of a flight that moves as one | — |
| **Expert Evangelist** | Fleet-wide state: +1 to hit while he serves, then −10 initiative (QX1) and −1 to hit if he is **killed**, for every ship, with a client to-hit mirror. Also a fleet-% price, a random post, and the CPN/OSF gate exists only in notes prose | — |
| **Expert Laser Technician** | "+1 damage per power point sent, up to +50 %" needs a new per-turn allocation UI, persistence and a cap | +3 Spinal / +2 Blast / +1 other lasers, through `getBonusDamage` (`SpinalLaser`, `BlastLaser`, `ImprovedBlastLaser`, `LtBlastLaser` exist) |
| **Expert Software Engineer** | Bonus fire-control points spendable in the Firing phase need a new allocation window (`HyachComputer` only allocates in Initial Orders, [baseSystems.php:12862](source/server/model/systems/baseSystems.php#L12862)). Also a "first point of damage ignored" rule in the damage path, a submarine detection range, and a self-contradictory specialist clause (§6, Software Engineer) | — |
| **Expert Ion Technician** | "3 thrust → 1d10+1 on one ion weapon" is a per-turn spend with UI | +1 per die and +1 flat on Ion-class weapons; +4 on C&C crits; the Rad Cannon's flat 12 and forced crits are Medium |
| **Expert Surge Officer** | Six unrelated hooks: EM damage, combined Surge Cannon shots, capacitor power by ship size, EM-hardening −3, a d6 to resist deactivation, Spark Field −1 | +1 damage on Electromagnetic-class weapons |
| **Expert Ballistic Officer** | "+1 magazine round per launcher" moves the lobby's ammo limits, which are derived from magazine capacity at offer time. Free missile swaps are lobby ammo pricing | +5 launch range (`range` **and** `rangeArray` — the same mode-switch trap — and sent to the client) |
| **Expert Anticipator** | A free **turn** for a ship in the Firing phase is a new phase-3 movement for ships (today only flights move there, as combat pivots), with masking, validation and animation | Rolling initiative twice is Easy. Re-rolling the lowest die is Medium: a `Dice`-level flag, which the replay harness's own `Dice` stub must mirror ([[project_replay_harness]]) |
| **Expert Gunner, once-per-battle abilities** | A Piercing mode injected into a weapon that lacks one, and +15 % on one called shot, each once per battle and not combinable: firing-mode injection, a persisted "used", and UI | — |
| **Expert Electrician** | Per turn: deactivate the flight's weapons, convert their max damage ÷ flight size into thrust or OB, then a d6 to see whether they stay offline | — |

### 3.4 Framework costs (the part that is not any one officer)

| Framework | Size | Content |
|---|---|---|
| Ship officers (Stages 1–2) | Medium, the upper end since D3's ruling | Registry, offers, post resolver, the *disabled* derivation, the **kill roll** (capture the post's critical roll, roll for a just-destroyed post, write and intercept the `OffKilled` note), apply post-pass, payload, insignia, tooltip, buy section, lobby mirror, tests |
| Fighter officers (Stage 4) | Medium | The per-flight price exception at seven lobby sites; **one officer per flight**, so a flight's officer rows are mutually exclusive in the buy dialog; post = the lead craft; loss that counts dropout but not docking or split-launch ([fighter.php:220](source/server/model/systems/fighter.php#L220) reports all three as "destroyed"); the insignia on the craft's icon |
| Placed officers (Stage 8) | Medium | An `officer` flag on registry entries, ship-wide caps, insignia instead of ✦, the menu sub-heading, and their exclusion from the "System Enhancements (n)" count |
| Buy-dialog filter chips (Stage 2a, D13) | Easy | `confirm.js` + `confirm.css` only: a chip row in the shell, show/hide by section, chips coloured by the section tokens. Independent of officers |

---

## 4. Design

### 4.1 Data

| | Shape | Storage |
|---|---|---|
| Track 1 row (fixed or random post, fighters) | `enhancementOptions`: `[OFF_HELM, label, 1, limit, price, 0, false]` | `tac_enhancements` / `tac_saved_enh`, unchanged |
| Track 2 row (placed) | `systemEnhancements`: `[OFF_GUN, label, 1, 1, total, 0, systemid, sysname]` | `tac_sys_enhancements` / `tac_saved_sysenh`, unchanged |
| Runtime | `$ship->officers`: list of `array('id' => 'OFF_HELM', 'post' => 12, 'out' => null, 'how' => null)`; `how` is `'disabled'` or `'killed'` | rebuilt every load |
| Kill record | individual note `OffKilled` (value = officer id) on the post | `tac_individual_notes`, unchanged (§4.4) |
| Payload | `officers: [{id, post, out?, how?}]`, own team only, omitted when empty | `BaseShip::stripForJson` |

`$officers` is **protected**, with a getter. A public empty default would add a dead key to roughly
2,500 static blueprints. That is the `$crewQuality` precedent ([[project_crew_quality_enhancements]]).

### 4.2 The registry

One literal per officer in `Officers::$registry`: `label`, `post` (a key into the resolver table in
§4.3), `pct` (or a `price` callable), `limit`, `available` (faction, size, "has the post"), `apply`,
`killedByAnyDamage` (true only for `OFF_MWEW`), `placed` (lives in the system registry instead),
`flight`. The D9 exclusions (factionAge ≥ 3, OSATs, terrain) are one shared test in front of every
`available`, not something each entry repeats.

| Officer | id | Post | Track |
|---|---|---|---|
| Expert Helmsman | `OFF_HELM` | C&C | 1 |
| Expert Engineer | `OFF_ENG` | Engine | 1 |
| Expert Scanner | `OFF_SCAN` | Scanner | 1 |
| Expert Navigator | `OFF_NAV` | C&C | 1 |
| Expert Technician | `OFF_TECH` | Reactor | 1 |
| Expert Jump Officer | `OFF_JUMP` | Jump Drive | 1 |
| Expert Quartermaster | `OFF_QM` | random primary | 1 |
| Expert Geneticist | `OFF_GENE` | C&C | 1 |
| Expert Scavenger | `OFF_SCAV` | Engine | 1 |
| Breaching Expert | `OFF_BRCH` | one pod | — (§3.3) |
| Expert Analyst | `OFF_ANLY` | Scanner | 1 |
| Expert Evangelist | `OFF_EVAN` | random primary | 1 |
| Expert Laser Technician | `OFF_LASR` | Reactor | 1 |
| Expert Software Engineer | `OFF_SOFT` | Computer | 1 |
| Expert Ion Technician | `OFF_ION` | Engine | 1 |
| Expert Surge Officer | `OFF_SURG` | Mag-Grav Reactor | 1 |
| Expert Ballistic Officer | `OFF_BALL` | C&C | 1 |
| Expert Anticipator | `OFF_ANTI` | C&C | 1 |
| Matter Weapons Expert, C&C | `OFF_MWE` | C&C | 1 |
| Matter Weapons Expert, weapon | `OFF_MWEW` | chosen weapon | 2 |
| Expert Gunner | `OFF_GUN` | chosen weapon | 2 |
| Expert Dogfighter | `OFF_DOG` | lead craft | 1 (flight) |
| Expert Missileer | `OFF_MSL` | lead craft | 1 (flight) |
| Expert Evader | `OFF_EVAD` | lead craft | 1 (flight) |
| Expert Coordinator | `OFF_COOR` | lead craft | 1 (flight) |
| Expert Electrician | `OFF_ELEC` | lead craft | 1 (flight) |

Offers come from one call, `Officers::addOffers($ship)`, made at the end of
`setEnhancementOptionsShip` and of `setEnhancementOptionsFighter`. That puts them in the static
blueprints, so `fvbuild.ps1 -Statics` follows any offer change. Measure the faction-JSON growth at
Stage 1, the way WEAPON plan §3.2 did. Rough estimate: +1 % on `Earth Alliance.json`. If it is over
budget, drop the label from the wire, since the client holds the labels anyway.

### 4.3 Posts

| Post in the rules | Resolver | Notes |
|---|---|---|
| C&C | the first `instanceof CnC` that is not a `FlagBridge`; failing that, the first `CnC` of any kind | `SecondaryCnC` is not a `CnC` subclass ([baseSystems.php:3837](source/server/model/systems/baseSystems.php#L3837)), so it is never picked. `ShadowPilot` is one (:15079), but it only sits on Ancient hulls, which D9 excludes. |
| Engine | the strongest `Engine` by `output` | `strongestSystem()` semantics: the first in construction order wins a tie |
| Sensor Array, Sensors | the strongest `Scanner` by `output` | includes `ElintScanner` |
| Reactor | the biggest `Reactor` by `maxhealth` | `ELITE_CREW`'s rule ([Enhancements.php:2485](source/server/model/ships/Enhancements.php#L2485)); `MagGravReactor` extends `Reactor` |
| Mag-Grav Reactor | the first `MagGravReactor` | Surge Officer |
| Jump Drive | the first engine `JumpEngine::getUnitJumpEngines($ship)` returns that is neither legacy nor a gate | never a bare sweep of `$ship->systems` ([[project_jump_points]]) |
| Computer | the `HyachComputer` | |
| Random primary system | a hash of (game id, ship id, officer id), **salted with `$secret_phrase`**, over location-0 systems that are targetable, not Structure and not pseudo-systems | FV is open source: an unsalted formula would let an opponent work out where the Evangelist sits and call-shot it. In the lobby there is no game id yet, so it reads "posted at battle start". Positional-id caveat in §7. |
| A fighter | the flight's first craft | §4.10 |
| Placed | the stored `systemid`, verified by `sysname` | the existing D13 path |

Posts are picked **before** `Enhancements::setEnhancements` runs (D2), by `Officers::pickPosts($ship)`
at the top of `BaseShip::onConstructed`. The lobby picks with the same rules, from the same blueprint
values, **before** `lobbyEnhancements.apply` ([[arch_lobby_ship_objects]]: duck-type there, never
`instanceof`).

### 4.4 Out of action: disabled or killed

Two states, both permanent and both effective from the next turn (D3). Each runtime entry carries
`out` (the turn) and `how` (`'disabled'` or `'killed'`). He serves on turn T if
`out === null || out >= T`. When both happen in one turn, `how` is `'killed'`.

**Disabled: derived at every load, nothing stored.** `out` is the earliest of:

1. a damage row on the post with `destroyed` set → that row's turn;
2. unless the post is a Structure block, or `survivesStructureDestruction`: the turn its Structure
   block was destroyed (for an array location, the turn the last of its blocks fell);
3. for a lead-craft post: that craft's `destroyed` damage row, or the turn of a `DisengagedFighter`
   critical. **Not** `DockedFighter` or `SplitLaunchedFighter`, which `Fighter::isDestroyed` also
   reports as destroyed ([fighter.php:220-229](source/server/model/systems/fighter.php#L220)).

The weapon-mounted Matter Weapons Expert is also derived, but as **killed**: the first damage row on
his post with damage greater than armour. No roll is involved, so nothing needs storing.

**Killed: rolled, then stored.**

- **When.** A new step in `Criticals::setCriticals`, `Officers::rollKills($activeShips, $gamedata)`,
  right after Pass 1 and before Pass 2's Self Repair ([criticals.php:83-118](source/server/handlers/criticals.php#L83)),
  behind `TacGamedata::$officersPresent`.
- **Who rolls.** Every post that still holds a serving officer and took damage through its armour
  this turn (`isDamagedOnTurn`, the same trigger Pass 1 uses).
- **Which roll.** The post's own critical roll from Pass 1. `ShipSystem::testCritical` keeps it in a
  protected `$lastCritRoll`: one assignment beside [ShipSystem.php:1421](source/server/model/systems/ShipSystem.php#L1421),
  protected so no blueprint ever carries it. Scanner and ELINT Scanner wrap the parent, so their Hyach
  halving is included ("including any modifications"). When the post made no roll this turn, the step
  rolls the same formula itself: d20 + floor(total damage) + the system's and the ship's
  `critRollMod`. That happens when Pass 1 skipped a destroyed system ([criticals.php:86](source/server/handlers/criticals.php#L86))
  and when a missile launcher's magazine exploded before it reached the parent. It is the rulebook's
  "make a roll even if the system is totally destroyed".
- **Result.** 20 or more kills every officer posted there.
- **Storage.** One individual note per death, written straight through with
  `Manager::insertIndividualNote`, the pattern for a sweep inside `advance()`
  ([[arch_individual_notes_and_phase_hooks]]). `notekey` is `OffKilled`, `notevalue` the officer id,
  and `notekey_human` "Expert Engineer killed". The longest, "Expert Software Engineer killed", is 31
  of the 40 characters allowed. Hosted on the post.
- ⚠️ **Read back by interception, never delivered.** In DBManager's bulk note loop
  ([DBManager.php:3800-3804](source/server/controller/DBManager.php#L3800)), an `OffKilled` note is
  recorded onto its ship and **not** handed to the system. Delivery would be unsafe: a Jump Engine,
  the Jump Officer's post, reads any note it does not recognise as its pre-jump combat value
  ([baseSystems.php:8539](source/server/model/systems/baseSystems.php#L8539)), and
  `HyachComputer::onIndividualNotesLoaded`, the Software Engineer's post, never calls its parent. The
  cost is one string compare per note, and no system class has to know officers exist.
- **Fighter officers make no kill roll** unless ruled otherwise (§6). The lead craft has no d20
  critical roll: its override is a d10 dropout test ([fighter.php:274](source/server/model/systems/fighter.php#L274)).
  A fighter officer is lost (disabled) when his craft is destroyed or drops out.

**For both states:**

- Computed **server-side only**. The client derives neither. `stripForJson` folds damage older than
  turn − 1 into one "Historical" row ([ShipSystem.php:262](source/server/model/systems/ShipSystem.php#L262)),
  which loses the turn, and notes never reach the client at all. The payload carries `out` and `how`.
- Pre-battle damage rows sit before turn 1. A post wrecked before battle leaves its officer disabled
  from the start, with no kill roll, since there was no battle turn to roll in.
- Replays load damage and notes up to the viewed turn, so a replay shows each officer as he was.
- `undestroyed` rows (Self Repair revival) are deliberately ignored.
- The replay harness's `damage` check mirrors Pass 1 with seeded dice. The new assignment in
  `testCritical` uses no dice, so no corpus line moves, and the harness never runs the kill step.
  `officersHarness` covers it (§5, Stage 1).

### 4.5 Applying effects

- `Officers::pickPosts($ship)` runs at the top of `BaseShip::onConstructed` (D2).
- `Officers::apply($ship, $gamedata)` runs after the per-system loop and before `iniativeadded`
  (D5). It walks live officers in **registry order**, calling each one's `apply`.
- Rules that are evaluated elsewhere (the EW allowance, jump failure, the repair pass, the initiative
  roll, the die bonus) ask `Officers::isActive($ship, 'OFF_X', $turn)`. On a hot path (to-hit,
  damage) that question goes behind **`TacGamedata::$officersPresent`**, reset and set in
  `markUnavailableSetMarkers()` the way `$chameleonPresent` is ([TacGamedata.php:488](source/server/model/TacGamedata.php#L488),
  [:519](source/server/model/TacGamedata.php#L519)). An ordinary game then pays one static read per
  shot ([[arch_defensive_mod_aggregation]], "gate it on a static boolean").
- POST-side ships (`Manager::getShipsFromJSON`) never run this path, so `officers` is empty on them.
  Nothing in a phase's `process()` may read officers
  ([[arch_post_side_ship_reconstruction]], [[arch_individual_notes_and_phase_hooks]]).

### 4.6 Visibility, and what still leaks

`BaseShip::stripForJson` sends `officers` only when `isRevealedToCurrentViewer()`, or
`TacGamedata::$currentGameFinished`, or (from the Analyst's stage) the viewer's team has a live
Analyst. That is the shape of the existing `systemEnhancements` send
([ShipClasses.php:985](source/server/model/ships/ShipClasses.php#L985)).

Officer rows are kept out of the public ship tooltip: add the `OFF_` prefix to the skip lists at
[Enhancements.php:2540](source/server/model/ships/Enhancements.php#L2540) (ships) and
[:2258](source/server/model/ships/Enhancements.php#L2258) (flights). `OffKilled` notes need no
masking: individual notes never reach any client.

| Officer | Changed number | Reaches the enemy? |
|---|---|---|
| Helmsman | `iniativebonus` | Send own-team only (the roll is server-side; the enemy only ever sees initiative itself) |
| Navigator | turn, pivot, roll and accel costs | Own-team only (an enemy client never budgets your thrust) |
| Technician | reactor `output` | **Yes.** `output` is sent unconditionally. Accept, as the ✦ does |
| Matter Weapons Expert, Gunner | `fireControl` via `isModified` | **Yes**, exactly like Gunsights |
| Geneticist | +2 to be hit | **Must**: the shooter's own hit-chance preview needs it (the Stealth Coating precedent) |
| Every officer | `pointCostEnh` | **Yes**, as for every enhancement already |

### 4.7 UI

- **Insignia (D11).** In `SystemIcon`, `renderBadges` becomes a small top-left flex row holding the
  ✦ and the insignia. `renderBadges` already serves both return paths, the destroyed short-circuit
  and the interactive one ([SystemIcon.js:604-616](source/public/client/UI/reactJs/system/SystemIcon.js#L604)),
  so a destroyed post keeps its insignia. Three looks: **serving** gold; **disabled** grey;
  **killed** grey with a red slash. When one post holds officers in different states, the insignia
  shows the best of them (any serving officer keeps it gold). Use an inline SVG,
  `pointer-events: none`, with a black `drop-shadow` filter for legibility over light hull art. The
  `title` names each officer and his state.
- **Placed officers must not light the ✦.** `systemEnhancements.hasAny`, `systemsEnhanced` and the
  "System Enhancements (n)" summary must skip registry entries flagged `officer`.
- **SystemInfo (D12).** A gold **Officers** block after *Special*, before *Criticals*
  ([SystemInfo.js:183-188](source/public/client/UI/reactJs/system/SystemInfo.js#L183)), built from
  `officers.listFor(ship)` filtered to this post.
- **Ship-level.** An own-team **Officers** list in `ShipInfo` and in the ship window's gold
  Enhancements box. Cheap, and it is the only place a random-post officer's post can be read off at a
  glance.
- **Buy dialog (D10).** `buySectionOf` returns `'officers'` for an `OFF_` id. Optionally, an icon:
  the user's art as `img/Officers.png`, masked like the other three sections
  (SHIP_ENHANCEMENTS_PLAN.md §5).
- **System menu (D10).** `SystemEnhancementsSection` renders officer rows under an **Officers**
  sub-heading.
- **Filter chips (D13).** §4.12.

### 4.8 Lobby

- A new legacy file, `client/officers.js` → `window.officers`, added to the debug script lists of
  **both** `game.php` and `gamelobby.php` (the `battleDamage.js` precedent). It holds:
  - `LABELS` / `SUMMARIES`, a mirror of the registry;
  - `pickPosts(ship)`, a mirror of §4.3;
  - `listFor(ship)`: the payload in game, derived from `enhancementOptions` plus `systemEnhancements`
    in the lobby;
  - `isActive(ship, id)`.
- `lobbyEnhancements` skips `OFF_` rows in the tooltip it builds, and previews only the officer
  effects a lobby window actually displays (fire control, turn costs, initiative). Each preview is a
  mirror pair and goes into `enhancementsDifferential.js` ([[project_ship_enhancements]]).

### 4.9 Saved fleets and pre-battle damage

- **Track 1** uses the `tac_saved_enh` path unchanged. ⚠️ Never withdraw an `OFF_` offer without a
  conversion or refund in `loadSavedFleet`: an unmatched saved row drops silently while its cost stays
  inside `pointCostEnh` ([[arch_retiring_ship_enhancement]]).
- **Track 2** uses `tac_saved_sysenh`, the D13 name check and re-pricing, unchanged.
- **Pre-battle damage (QX5, default accepted).** A track-2 officer on a destroyed post is removed and
  refunded by the existing D11 sweep, for free. A track-1 officer whose post is destroyed is
  **disabled from the start**: the lobby shows a warning on the post and in his tooltip, and the
  player un-buys him through Edit. There is no automatic refund, because changing `pointCostEnh` from
  outside the buy dialog is exactly what WEAPON plan D5 warns against.

### 4.10 Fighters

- The post is the flight's **first craft**. The insignia sits on that craft's `FighterIcon`.
- He is lost (disabled) by §4.4's rule 3, with no kill roll unless §6 rules one in.
- **One officer per flight (D9).** In the buy and edit dialogs a flight's officer rows are mutually
  exclusive: **+** on a second officer is refused with a passive notice ("A flight may carry only one
  officer"), never a confirm ([[feedback_passive_notices]]). The server does not trust that: on a
  flight, `Officers::resolve()` serves only the first officer row in registry order, so a doctored
  payload buys nothing extra.
- **The price exception.** A per-flight officer row must not be multiplied by flight size at
  [confirm.js:215](source/public/client/UI/confirm.js#L215) or at
  [gamelobby.js:4203, 4209, 4414, 4420, 4634, 4640](source/public/client/gamelobby.js#L4203). Use
  one predicate, `officers.isPerFlight(row)`, at all seven sites, never seven copies of an id test.
- Hangar split-launch can carry the lead craft onto a "- Split" row. §6 (Fighter officers) decides
  whether he goes with it.

### 4.12 Buy-dialog filter chips (D13)

Pure `confirm.js` + `confirm.css`. No officer code is involved, so this can be built first.

- **Markup.** `buyDialogShell` gets a chip row, `<div class="buyChips" hidden>`, above
  `.buyDialogSections`, in the slot the commented-out filter box occupies
  ([confirm.js:965-968](source/public/client/UI/confirm.js#L965)). It holds **All** first, then one
  `<button class="buyChip" data-section="…" aria-pressed>` per entry of `BUY_SECTIONS`, in that
  order. `BUY_SECTIONS` gains a `chip` label per section, since the Ammo section's title is
  "Ammo & Ordnance" and its chip says **Ordnance**.
- **Which chips show.** `openBuySections` already computes `used`, the sections that got rows
  ([confirm.js:1250-1259](source/public/client/UI/confirm.js#L1250)). Only their chips show, and the
  whole row stays hidden when fewer than two sections are used: a chip that filters down to the only
  section there is does nothing.
- **Behaviour.** A section chip sets `hidden` on every other used section; **All** clears it.
  Sections are hidden, never emptied. Every row stays in the DOM, so the `.shpenh<N>` spinners, the
  totals and gamelobby.js's read-back are untouched — the text filter's own promise. The choice lasts
  for that dialog only.
- **Colour.** The four section colour blocks in confirm.css
  ([:1895-1914](source/public/styles/confirm.css#L1895)) move from
  `.confirm.buyDialog .buySection[data-section="ammo"]` to `.confirm.buyDialog [data-section="ammo"]`,
  so one set of `--buy-sec-*` properties colours both the section and its chip. A picked chip uses
  the head's own recipe: `--buy-sec-bar` border, `rgba(var(--buy-sec-rgb), 0.22)` fill,
  `--buy-sec-title` text. An unpicked chip is neutral. **All** uses the dialog's neutral accent, and
  **Officers** keeps the default blue its section already falls back to.
  ⚠️ Move only the colour tokens to the shared selector. A layout rule written against a bare
  `[data-section]` would start hitting the chips too.
- **All three dialogs** (`showShipBuy`, `showShipEdit`, `showBuyBulk`) share the shell, so all of
  them get the chips with no per-dialog code.
- Chips wrap at phone width. Check at 360 px, headless ([[howto_headless_chrome_phone_width]]).

### 4.11 Placed officers

- Registry entries in `$systemEnhancementRegistry` gain `'officer' => true`, `'ages' => array(1, 2)`
  (D9 rules out Ancients, which is also the registry's own default), and a `shipLimit` callable.
  Their `eligible` must also refuse OSATs: `systemMayBeEnhanced` keeps out flights and mines but lets
  an OSAT through. `sanitiseSystemEnhancements` enforces `shipLimit` after its group pass
  ([Enhancements.php:4688](source/server/model/ships/Enhancements.php#L4688)), and the client's
  `systemEnhancements.set()` refuses past the cap.
- Offers go out per eligible weapon, which grows the static JSON. Measure it before signing the stage
  off.
- `systemMayBeEnhanced` already keeps flights and mines out
  ([Enhancements.php:3890](source/server/model/ships/Enhancements.php#L3890)), which is right: no
  placed officer is a fighter officer.

---

## 5. Stages and exit criteria

Every stage keeps these green: replay harness `check` (125 / 0 on 2026-10-08,
[[arch_replay_corpus_known_failures]]); `enhancementsHarness.php` and `enhancementsDifferential.js`
([[project_ship_enhancements]]); and, from Stage 1, a new `tests/replay/officersHarness.php`.
React edits are verified by bundle-and-evaluate, not an esbuild parse ([[howto_verify_react_bundle]]).
`fvbuild.ps1 -Autoload` follows the new class; `-Statics` follows any offer change. Nothing is
committed.

| Stage | Content | Exit test |
|---|---|---|
| **0** | ~~Rule D1–D12 and QX1–QX5~~ (done 2026-10-09). Rule the Helmsman's question (§6). Record the three baselines | Numbers written down |
| **1** | Server framework with **Expert Helmsman** | `officersHarness`: ids ≤ 10; offers (no C&C → no Helmsman; an Ancient hull, an OSAT, a mine, terrain → none); post picks (two equal Engines with Poor Crew bought → the same engine as the lobby picks); **disabled** (post destroyed on turn N → serves N, out N+1; block cascade → disabled, no roll; Self Repair revival changes nothing; destroyed pre-battle → never serves); **killed** (a damaged post rolling 20+ → killed, 19 → not, through a seeded `Dice`; a post destroyed this turn still rolls; the Hyach scanner halving reaches the roll; the note is written, read back on the next load, and never reaches the post's own `onIndividualNotesLoaded`, proven on a Jump Engine post); the Helmsman's +5 and tie-break on a forced tie; payload per viewer (owner, teammate, enemy, spectator, finished game). Replay `check` byte-identical, since no corpus game has an officer |
| **2a** | Buy-dialog filter chips (D13), independent of everything else here, so it can go first | Every buy, edit and bulk dialog: chips only for sections with rows, none at all with a single section; a picked chip shows its section's colours; hiding a section changes no total and no saved row (buy with **Enhancements** picked, then reload the fleet); 360 px wrap. Headless |
| **2** | Client framework | Lobby: buy a Helmsman, the Officers section shows, points move, the insignia lands on the C&C, the tooltip line appears. Destroy the C&C in the pre-battle editor → the warning shows. Game: own team sees the insignia in all three looks (serving, disabled, killed), an enemy browser sees nothing, a finished game shows all. Headless, driving the real site ([[howto_headless_chrome_phone_width]]) |
| **3** | Easy ship officers: Scanner, Technician, Matter Weapons Expert (C&C), Geneticist | Scanner: the late-EW window opens on a ship with no EW Detector on the board. MWE: switch modes and back, the +1 stays. Technician: power balance after a system dies |
| **4** | Fighter framework; Dogfighter, Missileer, Evader | A per-flight price is identical across buy, edit, copy and save/reload on 1-, 3- and 6-craft flights. A second officer on one flight is refused in the dialog, and a doctored payload with two serves only one. A lead-craft dropout loses him; a dock does not |
| **5** | Repair pass: Engineer and Scavenger | Deterministic pick; the C&C d20; no repair of this turn's crits (if so ruled); no revival of a destroyed system; runs after Self Repair |
| **6** | Navigator | Server and client thrust agree on every cost change; the ship window shows the new numbers own-team only |
| **7** | Jump Officer | Range +1 (with and without the Jump Accelerator); failure −20 at all three sites through one helper; arrival scatter and initiative |
| **8** | Placed-officer framework; Gunner core; weapon-mounted Matter Weapons Expert (without loading) | The cap per size; a Gunner and Gunsights on one gun show ✦ and insignia side by side; "any damage" kills; the destroyed-host refund |
| **9** | Coordinator; Analyst | The Analyst's disclosure per viewer, and its harness `masking` check |
| **10** | Loading time −1 | A mode switch keeps it; the officer lost mid-charge; the client's `isLoaded` agrees with the server |
| **11+** | Hard officers, one at a time, each starting with its own short plan and ruling round | — |

---

## 6. Rules questions

### Cross-cutting — RULED 2026-10-09

| # | Question | Ruling |
|---|---|---|
| **QX1** | Initiative units. FV rolls d100 and writes tabletop × 5: Elite Crew's "+5" is one tabletop point. Are the brief's numbers FV units? | Yes: Helmsman +5, Dogfighter +5, Quartermaster −30 and the Jump Officer's "−20" are FV units. The Evangelist's "−2" is tabletop, so **−10** in FV |
| **QX2** | Does a critical on his post kill an officer? | **Yes**, on a critical roll of 20 or more, modifiers included (D3) |
| **QX3** | A cap on officers per unit, beyond one of each? | None for ships. **A flight may buy one officer in total** (D9) |
| **QX4** | Which units may carry officers: bases, OSATs, LCVs, Ancient hulls? | **Not** Ancient hulls, OSATs (mines included) or terrain. Bases and LCVs only where the officer's post exists, so a base without a jump drive gets no Jump Officer (D9) |
| **QX5** | Track-1 officer whose post was destroyed pre-battle: disabled with a warning, or refunded automatically? | Disabled from the start, with a warning (§4.9). Default accepted without comment |

### Per officer (needed when that stage starts)

| Officer | Questions |
|---|---|
| Helmsman | Does the tie-break also apply under the simultaneous-movement initiative categories? (Default: no; ties there are categories, not orderings.) |
| Engineer | Reading: "once per turn, repair one critical anywhere; a C&C critical needs 16+ on a d20"? Which critical: the highest `repairPriority` system's oldest? Never a critical from this turn, as Self Repair? On a failed roll, try another or stop? |
| Scanner | Cumulative with EW Detectors, or the same point? (Default: max(1, ladder), not cumulative.) |
| Navigator | (a) The turn-cost ladder. The hulls use 0.25, 0.33, 0.5, 0.66, 0.75, 1, 1.25, 1.33, 1.5, 1.66, 1.75, 2 and 3. Which of those are "steps"? (b) Turn delay unchanged? (c) Accel/decel: × 0.75 per change, or on the turn's total? Which rounding? |
| Technician | "Or" = the best of the three automatically, the player's choice each turn, or a fixed priority? How is 33 % rounded? |
| Jump Officer | (a) +1 over the drive's own range (4 → 5, or 6 → 7 with the Jump Accelerator)? (b) −20 before or after the Accelerator's doubling? (c) On arrival: the officer of the ship that OPENED the doorway, or each arriving rider's own? (d) Does −20 also cover the hyperspace-jump failure roll? |
| Quartermaster | "Free" = this ship's own other enhancements and ammo? What is "+10 turn delay"? How much on C&C crits? Post: random primary? Price: 5 % of the game's points limit? |
| Geneticist | "Scenario only" = never offered unless a hull opts in, or a Create Game rule? "−1 to hit other ships" = this ship's own weapons? |
| Scavenger | A critical first, else 4 boxes? Which system first? May he repair Structure? |
| Breaching Expert | "+1 thrust" for one pod of a flight that moves as one? "20 % of total pods": fleet-wide, rounded down? What are "3 attack rolls on the first boarding turn" in FV's capture-mission terms? |
| Analyst | Does he need range, line of sight or an OEW lock? Every enemy ship, or only those in range? Weapon arming is already public in FV: leave it so? |
| Evangelist | The fleet = this player's ships, or the whole team? His penalty is worded "if killed": does a merely **disabled** Evangelist (post destroyed, kill roll survived) cost the fleet its +1 without the −10 initiative / −1 to hit? (Default: yes, the penalty needs a kill.) Is the penalty permanent? Do reinforcements that arrive later get the bonus? |
| Laser Technician | Lasers = `weaponClass` Laser (49 classes)? Is the power boost declared in Initial Orders? Is the +50 % cap on base damage? |
| Software Engineer | "Lets the computer specialist use their ability twice" and "Incompatible with Computer Specialist" contradict each other. Which one stands? "Submarine" = the Hyach stealth hulls (Alichi Kav and Tal)? Whose detection range gets +5? |
| Ion Technician | Rad Cannons always score 12, with no roll? Forced crits on Sensors, Engine and Reactor on every hit? When is the thrust boost declared? Is "+4 on C&C crits" a penalty? |
| Surge Officer | Which systems are the "caps" that get +2 / +1 power? Is "EMHardened gives −3" a change for the whole ship? |
| Ballistic Officer | "+5 distance range" = launch range of every ballistic weapon, torpedoes included? How is "+1 total magazine rounds per missile weapon" counted? How are the free swaps priced (+2 % each)? |
| Anticipator | Free turn: a full turn (not a pivot) in the Firing phase, paying its thrust? "Half price for some factions": which factions? |
| Matter Weapons Expert | May a ship carry both variants? Does the weapon variant also give that weapon the +1 fire control and damage? |
| Gunner | Size caps for LCVs and bases? Which weapons are eligible: any with fire control, ballistics too? Does he stack with Gunsights? |
| Fighter officers | Post = the first craft? A craft has no d20 critical roll (its test is a d10 dropout), so: no kill roll at all, or a d20 + damage roll on any damage to his craft, 20+ kills? (Default: none; he is lost with his craft.) Does he go with that craft on a split-launch? Missileer's "+5 OB": is that +1 OB (5 %)? Evader: always on, or toggled like jinking? Coordinator's "180°+" = three combat pivots in one turn? |

---

## 7. Risks

| Risk | Mitigation |
|---|---|
| **The insignia implies a secrecy it does not deliver** | D4 plus the §4.6 table, which is honest about what leaks; tooltip wording must not overclaim |
| **Two tracks drift**: an effect, insignia or tooltip that reads one track but not the other | Everything reads `$ship->officers` / `officers.listFor()`; nothing reads the rows directly after `resolve()` |
| **Positional ids**: a hull edited mid-game moves a random post, or makes a stored placed post point at another system | Placed: D13 drops a mismatched row. Random: acceptable, and the same exposure every positional id already has ([[arch_positional_system_id_trap]]) |
| **Order-dependent effects** differ between lobby and game | D5's fixed post-pass, the same order on both ends; the differential harness |
| **Out-of-action read off the wrong data**: the aggregated client damage, or the in-effect-only criticals | Disabled is derived server-side from the full damage history, killed from the stored note (§4.4); the client only reads `out` and `how` |
| **The kill note reaches a system that misreads it**: a Jump Engine takes an unknown note for its pre-jump combat value | Intercepted in DBManager's bulk note loop and never delivered (§4.4); the Stage 1 test puts the note on a Jump Engine post |
| **The kill roll is not the post's real roll**: an override that skips the parent leaves `$lastCritRoll` stale | Read `$lastCritRoll` only when it was set **this turn**; otherwise roll the formula (§4.4). Overrides today: Scanner and ELINT Scanner call the parent; a missile launcher skips it only when its magazine explodes; a fighter never posts a ship officer |
| **Hot-path cost** in to-hit, damage and the sort comparator | `TacGamedata::$officersPresent`; test the gate by forcing it false |
| **The fighter price exception** missed at one of seven sites | One tuple flag, and a Stage 4 exit test across buy, edit, copy and save/reload |
| **A Dice-level change** (Anticipator's re-roll) breaks every damage line in the harness | Add the same static to the harness's own `Dice` stub ([[project_replay_harness]]) |
| **Lobby re-application compounds** a stat bump | Idempotent from a remembered blueprint value, as `systemEnhancements.apply` is |
| **Shared client system references**: bumping one Matter gun bumps every gun of that class | Copy on first write ([[arch_client_system_shared_reference]]) |
| **A withdrawn offer** silently drops saved officers while keeping their cost | §4.9; never retire an `OFF_` id without a `loadSavedFleet` conversion |
| **Static JSON growth** | Measured at Stages 1 and 8 |
| **A filter chip loses a purchase**: a section hidden by a chip drops out of the totals or the read-back | Chips set `hidden` only; no row ever leaves the DOM. Stage 2a buys with one chip picked and reloads the fleet |

---

## 8. Out of scope

- Officers gaining experience, being promoted, or being swapped between posts mid-battle.
- Buying or moving officers in game. Lobby only, like every other enhancement.
- A distinct insignia per officer type (one insignia in v1; the tooltip names them).
- A combat-log line announcing an officer's death (D4).
- Updating FV_factions.txt:438, which still offers Shadow ships officer enhancements that D9 now
  rules out. Flagged to the user, not changed here.
- Enforcing ramming consent. FV leaves it to the players today (the Geneticist only adds a tooltip line).
