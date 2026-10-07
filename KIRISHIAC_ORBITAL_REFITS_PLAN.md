# Kirishiac Orbital Refits — Advanced Gravitic Shields & ELINT Sensor Modules — Implementation Plan

Status: **DRAFT 2026-10-06 — nothing built.** Sixteen rulings (§2) carry a recommended default each;
building can start from the defaults, but R2, R6, R9 and R13 change numbers a player will see.

Sources: two AoG rules pages supplied by the user (`Downloads/OrbitalGravShields.png`,
`Downloads/OribitalSensorModule.png`), transcribed rule by rule in §1.

Builds on: **`WEAPON_ENHANCEMENTS_PLAN.md`** (the per-system "System Enhancements" track, its
registry, tables, lobby menu and saved-fleet rules — read its §0 D1–D13 first) and
**`KIRISHIAC_ORBITALS_PLAN.md`** (orbital dock/deploy, sub-hit chart, overkill, regeneration).
Related: `SHIP_ENHANCEMENTS_PLAN.md` (harness gates), `WALKERS_OF_SIGMA_PLAN.md` (late EW).

---

## 0. In one paragraph

Three new **System Enhancements** (per-system refits), bought on a Kirishiac **orbital** in the
lobby's "Add Enhancements & Damage" menu: `SYS_AGS` (one Advanced Gravitic Shield generator),
`SYS_AGS2` (two generators) and `SYS_ELINT` (ELINT Sensor Module, Mastership augmenter orbitals
only). Each one **replaces the orbital's mounted system with a system of a different class** —
the first enhancement in FV that changes a system's class rather than its numbers. That needs
three new mechanics: (1) a **mount swap at game load** that keeps every positional id valid and
does not let the client merge the new system onto the old weapon's blueprint; (2) **more than one
system per orbital** (redundant generators); (3) **arc-limited ELINT** with a ring-fenced EW pool,
checked once after movement. Everything is gated so a game with no Kirishiac refit pays one
`empty()` per ship at load and one static boolean per movement phase.

---

## 1. The rules, line by line

### 1.1 Advanced Gravitic Shield Generators

| # | Rule (paraphrased) | FV today | Plan |
|---|---|---|---|
| A1 | Do not need a Shield Generator to function | FV's `GraviticShield` has no generator coupling at all ([baseSystems.php:1086](source/server/model/systems/baseSystems.php#L1086)) | Subclass `GraviticShield`; nothing to remove |
| A2 | Exposed to space; only the smallest amount of armour | — | Class constants, **R2** |
| A3 | Small; usually more than one per orbital for redundancy; **redundant shields do not accumulate** | The defensive bucket keeps only the STRONGEST source per type ([ShipClasses.php:2926](source/server/model/ships/ShipClasses.php#L2926)) | Free. Two generators = two systems; only one rating ever counts |
| A4 | Orbitals only; function whether the orbital is deployed or docked | A docked orbital's weapon is `stowed` (orbitals plan D4) | Generators are never stowed |
| A5 | Lost when the generator or its orbital is destroyed; regenerated with the orbital | Orbital death destroys `pairedWeapon`; regeneration heals orbital + `pairedWeapon` ([baseSystems.php:11216](source/server/model/systems/baseSystems.php#L11216), [:11247](source/server/model/systems/baseSystems.php#L11247)) | Generalise both to every mounted system (D8) |
| A6 | 150 points per generator × shield rating; only vessels not already equipped | No Kirishiac hull carries them today | 150 per rating level (300 for two) |
| A7 | Standard orbital: loses its weapon/system, takes up to two. Light orbital: exactly one. Heavy: never | `KirishiacOrbital` / `KirishiacOrbitalLight` / `KirishiacHeavyOrbital` | Eligibility by class |
| A8 | Primordial: rating ≤ 3. Ancient: rating ≤ 5, but only 25% of the fleet's vessels may have shields at all | Conqueror is `factionAge` 4, the rest 3 | Limit by hull age; 25% as a Fleet Check row (**R6**) |
| A9 | All generators have the same structure and armour, however many are on an orbital | — | Class constants, not divided by count |

### 1.2 ELINT Sensor Module

| # | Rule (paraphrased) | FV today | Plan |
|---|---|---|---|
| E1 | Deployed on a standard orbital; typically a 180° arc | The Mastership's four augmenters are exactly 180° each: A 270–90, J 90–270, D 180–360, G 0–180 ([kirishiacMastership.php:59](source/server/model/ships/kirishiac/kirishiacMastership.php#L59), [:88](source/server/model/ships/kirishiac/kirishiacMastership.php#L88), [:102](source/server/model/ships/kirishiac/kirishiacMastership.php#L102), [:124](source/server/model/ships/kirishiac/kirishiacMastership.php#L124)) | Module inherits the augmenter's arc (**R3**) |
| E2 | The ship acts as an ELINT ship, but only for vessels within normal range **and** the module's arc | `isElint()` = special ability "ELINT" from any live, online system ([ShipClasses.php:2330](source/server/model/ships/ShipClasses.php#L2330)); ranges are checked at fire time ([EW.php:217](source/server/handlers/EW.php#L217) onward) | The module carries the ability; an arc rule is added on top (§8) |
| E3 | The module's rating may be used for any ELINT function, on top of normal EW. Linking costs EW, "normally taken into account on the SCS" | Pool = sum of every system with `outputType` "EW" ([EW.php:42](source/server/handlers/EW.php#L42), [ew.js:4](source/public/client/ew.js#L4)) | A **separate** pool (D10); the scanner loses 2 per module (E8) |
| E4 | Modules add together only where arcs overlap and the target lies in the overlap; a target that leaves the arc loses the extra points | — | Per-module coverage in the validation (§8.4) |
| E5 | Allocate as normal including module points, then check validity **after all movement** | EW is allocated in Initial Orders; `EW::validateEW` is a no-op ([EW.php:5](source/server/handlers/EW.php#L5)) | Client pool at allocation; one server pass at the end of Movement |
| E6 | Worked example: Citadel, 10 normal + 2 modules × 4, 12 OEW on a Shadow + 1 SOEW to each of four friendlies; the Shadow leaves both arcs → OEW 10, SOEW stands, 4 points lost | — | **The Stage 6 exit test** (§8.5) |
| E7 | Module or orbital destroyed → ELINT lost in that arc; regenerated modules work | — | Live-module checks; regeneration generalised (D8) |
| E8 | 700 points: a Gravitic Augmenter becomes a 4-point module; normal EW −2 per module, cumulative; two needed for 360°; at most four per Mastership; any Ancient-timeframe Mastership; "a ship upgrade, not an enhancement" | Only the Mastership carries orbital augmenters (four) | Eligible = augmenter orbital on an Ancient hull; the cap of four is automatic. The Fleet Check "Enhancement(s) present" caution reads ship-level options only ([gamelobby.js:1579](source/public/client/gamelobby.js#L1579)), so a refit never trips it — which matches "not an enhancement" |

---

## 2. Rulings needed — each with a recommended default

| # | Question | Recommended default | Why / alternative |
|---|---|---|---|
| **R1** | Is the shield rating per orbital or one rating for the whole ship? | **Per orbital** (each orbital's own ticker) | The text prices "per generator × rating" and says nothing about uniformity. Alternative: one ship-wide rating, enforced in sanitise |
| **R2** | Generator armour and structure (A2, A9) | **Armour 1, structure 6** | "Smallest amount of armour"; 6 is FV's standard Gravitic Shield structure (Abbai: `GraviticShield(0, 6, 0, 2, …)`). Replace with the fleet-book SCS values if they exist |
| **R3** | Arc of a replacement system | **The replaced mount's arc**, identical docked and deployed | E1 and E8's "two for 360°" only work with the augmenters' 180° arcs. Alternative: the orbital's own section arc |
| **R4** | Two generators: two systems, or one system with doubled boxes? | **Two systems** (one in place, one appended) | Faithful to A3/A9: one can die and the other keeps the shield up. The alternative halves the machinery (no appended system) but changes how damage and criticals land |
| **R5** | Sub-chart with two generators | **1–3 first generator, 4–6 second**, 7–20 orbital | One die roll, no extra randomness. Alternative: random live generator |
| **R6** | 25% cap (A8) | **Advisory Fleet Check row**; counts ships (not flights) carrying any AGS refit; Ancient hulls only; rounds down | FV's composition rules are all Fleet Check rows, not buy-time blocks. Open: round up or down, and whether Primordial hulls count |
| **R7** | Which EW types may module points pay for? | **OEW, SOEW, SDEW, DIST, JAM, BDEW, Detect Stealth.** Never DEW, CCEW, Detect Mines | E6 puts module points on OEW, so OEW must be payable |
| **R8** | Do NORMAL points spent on an ELINT function also need a live module arc? | **Yes** | E2 and E7 ("loses its ELINT abilities in that arc") |
| **R9** | After movement, who pays when module points no longer cover an entry? | **DEW absorbs it first**, then entries shrink in reverse allocation order; points freed by a dropped ELINT function are lost, never DEW | Reproduces E6 exactly (§8.5). Alternative: DEW is untouchable, so the Citadel's OEW would fall to 8, not 10 |
| **R10** | BDEW and Detect Stealth have no single subject | Effect applies **per protected or detected unit** that lies in a live module's arc | The only per-unit reading that honours E2 |
| **R11** | Does an ELINT module work while its orbital is docked? | **Yes** | Mirrors A4. Alternative: stowed like a beam (orbitals plan D4) |
| **R12** | ELINT module armour, structure, criticals | **Armour 7, structure 12** (the augmenter's), Scanner-style output criticals | Not stated in the text |
| **R13** | Power of a replacement | **Power-neutral**: the in-place replacement inherits the replaced mount's power requirement (beam 3, Medium beam 2, augmenter 7) and cannot be switched off; an appended second generator draws 0 | Otherwise every swap frees that power for boosting — a Mastership with four modules would gain 28 power the text never mentions |
| **R14** | Is the −2 EW per module permanent? | **Yes**, even after the module dies | "Taken into account on the SCS" |
| **R15** | How faithful must the lobby preview be? | **Full swap** in the lobby ship window (§9.3) | Alternative: a badge on the old weapon and the swap only in game — less code, but the lobby shows a weapon that will not exist |
| **R16** | Block out-of-arc ELINT allocations at Initial Orders? | **No**; show a passive hint | E5: the ship may still turn, and validity is judged after movement |

---

## 3. Design decisions (fixed here)

| # | Decision | Rationale |
|---|---|---|
| **D1** | **Purchases attach to the ORBITAL** (`systemid` = orbital id), never to the mounted weapon | The weapon is `isTargetable = false`, so both `systemMayBeEnhanced` ([Enhancements.php:3832](source/server/model/ships/Enhancements.php#L3832)) and the lobby's `isPseudoSystem` ([SystemInfoButtons.js:883](source/public/client/UI/reactJs/system/SystemInfoButtons.js#L883)) already refuse it. And the weapon's identity changes under the swap; the orbital's never does, so the D13 name check stays valid |
| **D2** | **Three enhIDs, each self-priced.** `SYS_AGS` (count = rating, 150/level), `SYS_AGS2` (count = rating, 300/level), `SYS_ELINT` (limit 1, 700). One mutual-exclusion group per orbital | Count-as-rating reuses the existing ticker. No price depends on another row, so the offer tuple, `priceStep` and the sanitiser work unchanged. All IDs ≤ 10 characters (`enhid` is `varchar(10)`) |
| **D3** | The registry gains two optional slots: **`mount`** (structural half, runs early) and **`group`** (mutual exclusion). `apply` keeps the stat half (the scanner's −2) | One literal still defines one refit, and the structural half cannot be forgotten. A missing slot already reads as "nothing to do" (`regCall` returns null) |
| **D4** | **Mount at load, early**: a new `Enhancements::mountSystemEnhancementSystems($ship)` called in `DBManager::getEnhancementsForShips` right after the per-system rows are validated ([DBManager.php:3265](source/server/controller/DBManager.php#L3265)) | Same reason as Extra Tendrils ([Enhancements.php:2016](source/server/model/ships/Enhancements.php#L2016)): damage, criticals, power, fire orders and notes all resolve by `getSystemById` afterwards and silently drop what does not resolve |
| **D5** | **In place keeps the id; extras are APPENDED**, swap rows processed in ascending orbital id. New `BaseShip::replaceEnhancementSystem($id, $system)` beside `addEnhancementSystem` ([ShipClasses.php:2441](source/server/model/ships/ShipClasses.php#L2441)) | Nothing already on the hull moves; the same rows rebuild the same ids on every load. A named method keeps the second post-constructor mutation greppable |
| **D6** | **Client: never merge a live system onto a blueprint system of a different `name`.** One string compare in `SystemFactory.createSystemsFromJson` ([systemFactory.js:16](source/public/client/model/systemFactory.js#L16)); replacements send their full blueprint fields (`addedByEnhancement = true` + `addBlueprintFieldsForJson`, [ShipSystem.php:362](source/server/model/systems/ShipSystem.php#L362)) | Today the factory does `Object.assign(copy of blueprint[id], payload)`, so a shield at a beam's id would inherit `weapon: true`, `fireControl`, `range`, `loadingtime` and the beam's `data`. Only fighters ever change `name` at runtime ([fighter.php:75](source/server/model/systems/fighter.php#L75)), and flights use a different factory path |
| **D7** | **POST-side ships are NOT swapped.** Constraint: a replacement takes no per-system player input — no power toggle, no notes, no fire orders | `Manager::getShipsFromJSON` resolves POSTed system data by id and drops what does not resolve ([Manager.php:2631](source/server/controller/Manager.php#L2631)); `validateEW` is a no-op; a fire order whose weaponid maps to a non-weapon is already rejected ([firing.php:8](source/server/handlers/firing.php#L8)). So nothing POSTed is lost. If a future change gives a replacement player input, POST-side mounting becomes mandatory — say so in the class docblock |
| **D8** | **The orbital's single `pairedWeapon` generalises to a mounted list** (`getMountedSystems()`); `pairedWeapon` stays as its first entry | Sub-chart, death coupling, regeneration and docked state all read it; one list keeps them in step |
| **D9** | **The new classes declare every property the orbital writes** (`linkedOrbital`, `stowed`), and the orbital only writes `stowed`/`canOffLine` on a `Weapon` | `stowed` is declared on `Weapon` only ([weapon.php:253](source/server/model/weapons/weapon.php#L253)) and `linkedOrbital` per weapon class. PHP 8.2 ([docker/php/Dockerfile](docker/php/Dockerfile)) deprecates dynamic properties, and Manager's global handler throws on every error ([Manager.php:5](source/server/controller/Manager.php#L5)) — **one undeclared write kills the game load** |
| **D10** | **The ELINT module is NOT a `Scanner`/`ElintScanner` subclass**; `outputType = 'ELINT'` | A Scanner subclass would join the normal pool, the stealth-detection scanner read ([baseSystems.php:266](source/server/model/systems/baseSystems.php#L266)) and the jump-exit scatter's sensor rating |
| **D11** | **Validation runs once, at the end of Movement, and rewrites `tac_ew`.** Rows that reach 0 are deleted | Every downstream reader — fire-time SOEW/SDEW/DIST, HK jamming, the client, replays — then reads validated values with no change. A zero OEW row must go: the client counts OEW rows as targets whatever their amount ([ew.js:253](source/public/client/ew.js#L253)) |
| **D12** | **Use-time arc gates only where an entry has no single subject**: BDEW and Detect Stealth, plus the late-EW window | Subject-based functions are settled by D11 |
| **D13** | **Static gate `TacGamedata::$elintModulesPresent`**, derived in `markUnavailableSetMarkers` ([TacGamedata.php:481](source/server/model/TacGamedata.php#L481)) from each ship's module list | The `$chameleonPresent` pattern ([[arch_defensive_mod_aggregation]]) |
| **D14** | **Per-ship and per-orbital lists are non-public** | The static blueprint serialises PUBLIC properties; an array of system objects would ride every Kirishiac blueprint |

---

## 4. What exists — the code this touches

| Concern | Where |
|---|---|
| Per-system refit registry, eligibility, prices, offers | `$systemEnhancementRegistry` [Enhancements.php:3645](source/server/model/ships/Enhancements.php#L3645); `systemEnhancementAllowsAge` [:3809](source/server/model/ships/Enhancements.php#L3809) (the `ages` slot); `systemMayBeEnhanced` [:3832](source/server/model/ships/Enhancements.php#L3832); `setSystemEnhancementOptions` [:3918](source/server/model/ships/Enhancements.php#L3918) |
| Refit apply / JSON / buy-time sanitiser | `setSystemEnhancements` [:4300](source/server/model/ships/Enhancements.php#L4300); `addSystemEnhancementsOwnForJSON` [:4363](source/server/model/ships/Enhancements.php#L4363); `sanitiseSystemEnhancements` [:4402](source/server/model/ships/Enhancements.php#L4402) |
| Systems an enhancement mounts (precedent) | `addEnhancementSystems` [:2035](source/server/model/ships/Enhancements.php#L2035), `addedByEnhancement` [ShipSystem.php:195](source/server/model/systems/ShipSystem.php#L195) |
| Game load order | `getTacShips` [DBManager.php:2997](source/server/controller/DBManager.php#L2997): enhancements → initiative → moves → EW → power → criticals → damage → fire orders → notes; then `onConstructed` |
| Kirishiac ship-level set (only `IMPR_SR`) | `nonstandardEnhancementSet` [Enhancements.php:95](source/server/model/ships/Enhancements.php#L95) |
| Orbitals | `KirishiacOrbital` [baseSystems.php:11094](source/server/model/systems/baseSystems.php#L11094) — `addOrbitalWeapon` :11118, `resolveSubHitChart` :11175, `criticalPhaseEffects` :11216, `performRegeneration` :11247, `startRegeneration` :11288, `onIndividualNotesLoaded` :11421, `stripForJson` :11514; Light :11540 (`canRegenerate = false`); Heavy :11586 |
| Orbital weapon hooks to copy | `AntigravityBeam` [gravitic.php:2171](source/server/model/weapons/gravitic.php#L2171) — `getOverkillDestination`, the linkedOrbital `stripForJson` block |
| Replaced systems | `GraviticAugmenter` [supportWeapons.php:1320](source/server/model/weapons/supportWeapons.php#L1320) (defaults: structure 12, power 7) |
| Shields | `Shield` [baseSystems.php:956](source/server/model/systems/baseSystems.php#L956), `GraviticShield` :1086; client [defensive.js:51](source/public/client/model/system/defensive.js#L51), :99; aggregation `getHitChanceMod` / `checkIsValidAffectingSystem` [ShipClasses.php:2926](source/server/model/ships/ShipClasses.php#L2926), :2985; client `getHitChangeMod` [ship.js:162](source/public/client/model/ship.js#L162) |
| EW, server | `EW.php`: `getScannerOutput` :42, `getBlanketDEW` :217, `getSupportedOEW` :242, `getSupportedDEW` :314, `getDistruptionEW` :348, `submitLateEw` :614; [HkJamming.php:61](source/server/handlers/HkJamming.php#L61); stealth detection ELINT multiplier [baseSystems.php:275](source/server/model/systems/baseSystems.php#L275); `adjustEwAmount` [DBManager.php:2120](source/server/controller/DBManager.php#L2120) |
| EW, client | `ew.js`: `getEwLeftFor` :124, `convertUnusedToDEW` :275, `getEWLeft` :354, `AssignOEW` :542, `assignEW` :617, `getSupportedBDEW` :911; ELINT buttons [shipTooltipInitialOrdersMenu.js:43](source/public/client/UI/shipTooltipInitialOrdersMenu.js#L43); DEW row [ShipWindowEw.js:411](source/public/client/UI/reactJs/shipWindow/ShipWindowEw.js#L411) |
| Movement → Pre-Firing advance | [MovementGamePhase.php:111](source/server/Phase/MovementGamePhase.php#L111) (`setPhase(5)`) |
| Lobby refits | [systemEnhancements.js](source/public/client/systemEnhancements.js) (`LABELS` :46, `revert` :324, `apply` :352); menu gate [SystemInfoButtons.js:867](source/public/client/UI/reactJs/system/SystemInfoButtons.js#L867); Fleet Check `checkChoices` [gamelobby.js:1005](source/public/client/gamelobby.js#L1005) |

---

## 5. Data model

### 5.1 Registry entries

```php
'SYS_AGS'   => array('label' => 'Adv. Gravitic Shield', 'eligible' => 'sysEnhEligibleAGS', 'price' => 'sysEnhPriceAGS',
                     'limit' => 'sysEnhLimitAGS', 'mount' => 'sysEnhMountAGS',
                     'group' => 'orbitalMount', 'ages' => array(3, 4), 'serialise' => array()),
'SYS_AGS2'  => array('label' => 'Adv. Gravitic Shield x2', /* standard orbitals only; 300 per level; mount appends a 2nd generator */
                     'group' => 'orbitalMount', 'ages' => array(3, 4), 'serialise' => array()),
'SYS_ELINT' => array('label' => 'ELINT Sensor Module', 'eligible' => 'sysEnhEligibleELINT', 'price' => 'sysEnhPriceELINT',
                     'limit' => 'sysEnhLimitOne', 'mount' => 'sysEnhMountELINT', 'apply' => 'sysEnhApplyELINT',
                     'group' => 'orbitalMount', 'ages' => array(3), 'serialise' => array()),
```

* **Eligible:** `SYS_AGS` — a `KirishiacOrbital`, not Heavy, with a mounted system. `SYS_AGS2` — the
  same, not Light either. `SYS_ELINT` — a standard orbital whose mounted system is a `GraviticAugmenter`.
* **Limit:** AGS `factionAge >= 4 ? 3 : 5`; ELINT 1.
* **`serialise` stays empty.** Nothing on the ORBITAL changes, and the scanner's reduced `output` is
  always sent ([ShipSystem.php:319](source/server/model/systems/ShipSystem.php#L319)).
* ⚠️ `ages` array(3, 4) makes these the **first refits open to Primordial hulls**, so
  `hullAgeHasAnySystemEnhancement` stops short-circuiting for age-4 hulls. That costs a per-system loop
  at static generation only, and only orbitals pass `eligible`.

### 5.2 New system classes

| | `KirishiacAdvGravShield extends GraviticShield` | `KirishiacElintModule extends ShipSystem implements SpecialAbility` |
|---|---|---|
| Output | rating (1–5), set by the factory | 4 ("4-point module"), crit-reducible |
| Arc | the replaced mount's (R3) | the replaced augmenter's 180° (R3) |
| Armour / structure | R2 | R12 |
| Power | R13 (inherit; `canOffLine = false`) | R13 |
| Abilities | `getDefensiveType()` "Shield" (inherited) | `specialAbilities = ['ELINT']`, `outputType = 'ELINT'` (D10) |
| Docked | functions; never stowed | functions (R11) |
| Overkill | → orbital while deployed; lost if the orbital is gone (copy `AntigravityBeam::getOverkillDestination`) | same |
| JSON | parent + the linkedOrbital block (`isTargetable`, `repairPriority`, `privateRepairOnly`, `structureHomeLocation`) + `addBlueprintFieldsForJson` | same |
| Declares | `linkedOrbital`, `stowed` (D9) | same |
| Factory | `forOrbital($orbital, $replaced, $rating, $ordinal)` | `forOrbital($orbital, $replaced)` |
| Client class | `KirishiacAdvGravShield` extending client `GraviticShield` (its Abbai branch is faction-gated) | `KirishiacElintModule` extending `ShipSystem` |

Display names follow the orbital letter: "Adv. Gravitic Shield A" / "A2", "ELINT Sensor Module A".
Icons: reuse `shield.png` / `elintArray.png` in v1; new art is a drop-in later. The client factory
picks the JS class from `name` with the first letter upper-cased, so `name` must equal the JS class
name.

### 5.3 Ship and orbital fields

* `BaseShip`: `protected $elintModules = array()` + `getElintModules()` (D14);
  `public $systemEnhancementSystemsMounted = false` (once-per-object guard, like `enhancementSystemsAdded`).
* `KirishiacOrbital`: `protected $mountedSystems = array()`, `getMountedSystems()`,
  `replaceOrbitalWeapon()`, `addMountedSystem()`.
* Static JSON (lobby only, R15): `public $systemEnhancementSwapPreviews = array()` — per eligible
  orbital, the replacement system(s) serialised exactly as blueprint systems are. Emitted only on the
  `$offerSystemEnhancements` path; add it to ShipCompactor's drop-when-empty list beside
  `systemEnhancementOffers` ([ShipCompactor.php:351](source/server/lib/ShipCompactor.php#L351)).

---

## 6. The swap

### 6.1 Server, step by step (`mountSystemEnhancementSystems`)

1. `if (empty($ship->systemEnhancements)) return;` — every ship without a refit stops here.
2. Collect the rows whose registry entry has a `mount` slot, keyed by orbital id; `ksort` (D5).
3. Per row: resolve the orbital and take `$old = $orbital->getOrbitalWeapon()`. Skip if null, or
   (ELINT) if it is not an augmenter.
4. Build the replacement with the class factory. It copies `id`, `location`,
   `structureHomeLocation`, `startArc`/`endArc` and (R13) `powerReq` from `$old`, and sets
   `addedByEnhancement = true`.
5. `$ship->replaceEnhancementSystem($old->id, $new)`: same id, `setUnit`, `$this->systems[$id] = $new`.
   ⚠️ Not `addSystem`: that stamps a section arc on a 0/0 arc and has the LCV and structure side
   effects ([ShipClasses.php:2336](source/server/model/ships/ShipClasses.php#L2336)).
6. `$orbital->replaceOrbitalWeapon($new)` wires `linkedOrbital`, `isTargetable = false`,
   `repairPriority = 0`.
7. `SYS_AGS2`: a second generator, `addEnhancementSystem($gen2, $orbital->location)` (appended), then
   `$orbital->addMountedSystem($gen2)`.
8. `SYS_ELINT`: push the module onto the ship's module list.

`apply` (in `onConstructed`, before the per-system loop) then does the stat half: `sysEnhApplyELINT`
takes 2 off the strongest Scanner per module (reuse `strongestSystem`,
[Enhancements.php:2452](source/server/model/ships/Enhancements.php#L2452)).

### 6.2 Why the ids stay safe

* No hull constructor changes, so no existing game's ids move.
* Purchase rows name the **orbital** (D1), whose id and name never change.
* The in-place replacement exists from turn 0, so every damage, critical and power row written for
  that id belongs to it.
* Pre-battle damage cannot reach a mounted system (pseudo-system, D1); destroying the ORBITAL
  pre-battle drops the refit through the existing D11 sweep.
* Appended ids are rebuilt identically each load (D5). Ship-level appends (Extra Tendrils) run
  first, at [DBManager.php:3241](source/server/controller/DBManager.php#L3241), and never occur on
  Kirishiac hulls.
* ⚠️ The general hazard remains: a contributor inserting a system mid-constructor into a Kirishiac
  hull shifts the orbital ids; D13 then drops the row, and a running game reverts that orbital to
  its weapon. That is the standing "never restructure a hull in use" rule
  ([[arch_positional_system_id_trap]]).

### 6.3 Client

* `SystemFactory.createSystemsFromJson`: if the blueprint system at this id has a different `name`,
  use the payload alone (D6). The appended generator already has no blueprint entry, which works
  today for tendrils.
* Every other client blueprint read is keyed by phpclass, not system id (`fleetList.js`,
  `systems.js` `findFighterBlueprint`) — checked by grep.

### 6.4 Paths that need no change, and why

| Path | Why it is safe |
|---|---|
| Static blueprints, game.php `staticShips`, BlueprintCache | Built from pristine hulls; no purchase rows |
| POST-side ships | D7 |
| Replays | Same DB load per turn, same mount |
| Saved fleets | Rows are on the orbital and re-validated by the existing §4.7.1 machinery |
| Fire validation and load | A non-weapon weaponid is rejected at submit and skipped at load ([firing.php:8](source/server/handlers/firing.php#L8)) |

---

## 7. Advanced Gravitic Shield in play

* **Hit chance and damage:** inherited `Shield` maths through the existing bucket; the arc test is
  `checkIsValidAffectingSystem` ([ShipClasses.php:2985](source/server/model/ships/ShipClasses.php#L2985)).
  Two generators, or two orbitals covering one bearing, count once (A3). Fighters at range 0 fly
  under it, as with every Shield. The Advanced Sensors clause only zeroes defences of
  `factionAge < 3` targets, so it never touches these.
* **Docked:** keeps working (A4). A docked standard orbital folds every hit into its block, so a
  generator cannot be hit while docked — the same as today's stowed beam.
* **Destroyed:** a generator destroyed this turn still works until the end of the turn
  (`isDestroyed($turn-1)`); the orbital's death destroys every mounted system in the critical phase (D8).
* **Regenerated** with a standard orbital (Light orbitals cannot regenerate).
* **Sub-chart** (R5): the "Weapon" band maps to the mounted list, split 1–3 / 4–6 when there are two.
* **Client mirror:** the client shield class returns `output` as the defence mod, and the client
  bucket mirrors the arc test. Verify in Stage 4 against the server's real `calculateHitBase`.

---

## 8. ELINT Sensor Module — arc-limited ELINT (the new mechanic)

### 8.1 Pools

* **N** — normal EW: `getScannerOutput`, already −2 per module (E8).
* **M** — module points: the sum of live modules' `output`. Never joins N (D10). Never becomes DEW.

### 8.2 Allocation (Initial Orders, client)

* `ew.getElintModulePool(ship)` and `ew.isModulePayable(type)` (R7), both behind a cached
  `ship.hasElintModules` test.
* `getEWLeft(ship)` for a module ship = `N − nonPayableUsed − max(0, payableUsed − M)`. Module points
  are spent first, so the player keeps the most DEW.
* `getEwLeftFor(ship, type)`: a payable type adds `max(0, M − payableUsed)`. Every assign path already
  goes through it.
* `convertUnusedToDEW` is unchanged; it writes N's remainder only.
* ⚠️ **After validation the DEW row is the truth.** Outside Initial Orders a module ship's
  `getDefensiveEW` must read the committed DEW row, not recompute: in E6 the recompute says 4 and the
  row says 0 (§8.5). This affects the EW panel and any client preview of shots at the ship.
* Ship window: an "ELINT" row — `unused / M` during Initial Orders, the points lost after validation.
* R16: no blocking. The tooltip menu's ELINT buttons may carry a passive hint when the subject is
  outside every live arc at the current facing ([[feedback_passive_notices]]).

### 8.3 What counts as covered

A module covers a unit when the module is not destroyed and
`Mathlib::isInArc($host->getBearingOnUnit($unit), startArc, endArc)` holds — the weapon-arc helpers,
so same-hex cases behave as they do for weapons. BDEW and Detect Stealth have no subject: any live
module may pay for them, and their effect is gated per unit (§8.6).

### 8.4 Validation after movement (server, once per turn)

Hook, just before `setPhase(5)` in [MovementGamePhase.php:111](source/server/Phase/MovementGamePhase.php#L111):

```php
if (TacGamedata::$elintModulesPresent) ElintModules::validateAfterMovement($latestgameData, $dbManager);
```

New handler `source/server/handlers/ElintModules.php`. Per ship with modules, not destroyed, deployed:

```
N        = EW::getScannerOutput(ship)                   // already includes the -2 per module
live     = modules not destroyed; capacity = getOutput()
entries  = this turn's EW rows, by id (= allocation order)
1. SOEW / SDEW / DIST / JAM whose subject no live module covers  -> lost in full        (R8)
2. X     = sum of non-payable rows (CCEW, Detect Mines)           -> always normal points
3. F     = max-flow( payable rows -> covering live modules -> capacity )
           augmenting in row-id order, modules in system-id order (deterministic)
4. need  = sum(payable) - F                                       -> must come from normal points
5. avail = max(0, N - X)
   if need <= avail : DEW' = min(committed DEW, avail - need)     (R9: DEW absorbs first;
                                                                   freed points never become DEW)
   else             : DEW' = 0; shrink the uncovered parts of payable rows,
                      latest-allocated first, by (need - avail)
6. write: reduced rows via adjustEwAmount; rows that reach 0 are DELETED (D11); DEW row = DEW'
7. note the total lost (IndividualNote on the first module, "ELINT lost: n") for the panel and log
```

* Idempotent: a second run sees a feasible allocation and writes nothing.
* Max-flow over at most four modules is a few dozen operations, and runs only for module ships.
* New DB helper `deleteEwEntry(gameid, shipid, turn, type, targetid)`: `adjustEwAmount` clamps at 0
  and keeps the row ([DBManager.php:2120](source/server/controller/DBManager.php#L2120)).

### 8.5 The worked example (E6) through the algorithm — the Stage 6 exit test

N = 10; two modules of 4 cover the four friendlies but not the Shadow. Rows: OEW 12 on the Shadow and
SOEW 1 on each friendly. At commit the client spent module points first, so DEW = 10 − (16 − 8) = 2.

1. All four SOEW subjects are covered, so nothing is lost here.
2. X = 0.
3. F = 4 (only the SOEW rows can use module points).
4. need = 16 − 4 = 12.
5. avail = 10, which is less than 12, so DEW' = 0 and the OEW row shrinks by 2.

Result: **OEW 10, SOEW stands, DEW 0, 4 module points unused** — the rulebook's outcome exactly. Had
the Shadow stayed in arc, F = 8, need = 8 and DEW' = 2, so nothing is written.

### 8.6 Use-time gates (D12)

| Site | Gate |
|---|---|
| `EW::getBlanketDEW` [EW.php:217](source/server/handlers/EW.php#L217) + client `ew.getSupportedBDEW` [ew.js:911](source/public/client/ew.js#L911) | Skip a module ship's BDEW for a target none of its live modules covers |
| Stealth detection [baseSystems.php:275](source/server/model/systems/baseSystems.php#L275) | ×3 and +2 per Detect Stealth point only toward a covered unit; otherwise ×2 |
| `EW::submitLateEw` [EW.php:614](source/server/handlers/EW.php#L614) | Refuse a late ELINT-function delta whose subject is uncovered; late points are normal points only |

Each costs one property read on an ELINT ship, and only ELINT ships reach these loops. SOEW, SDEW,
DIST and JAM need nothing (D11). The jump-vortex "ELINT on the team" bonus stays fleet-wide.

### 8.7 Display (Stage 7, optional polish)

* The module's arc drawn on hover, as weapon arcs are.
* EW target rows flagged while the subject is outside every live arc (Initial Orders and Movement).
* Data window: rating, arc, the −2 penalty, and "functions only toward units in this arc".

---

## 9. Lobby

### 9.1 Offers and labels

Offers come from the registry automatically, on the orbital's icon. Add the three IDs to
`systemEnhancements.LABELS` (mirror pair). Labels must ellipsize: the menu's 300px max-width is
load-bearing.

### 9.2 Mutual exclusion

`systemEnhancements.set` zeroes the other rows of the same `group` on that orbital, with a passive
notice. The server's `sanitiseSystemEnhancements` keeps the first row per (orbital, group) and
notices the rest (precedence `SYS_ELINT` > `SYS_AGS2` > `SYS_AGS`).

### 9.3 Preview (R15)

`apply` gains a swap branch, and `revert` undoes it:

* Stash the original system object per id, and the list of appended ids, on the ship.
* Build replacements with `SystemFactory.createSystemFromJson` from a **deep clone** of the ship's
  `systemEnhancementSwapPreviews` entry; set the AGS `output` to the count.
* Replace `ship.systems[id]`; append the second generator at the id the server will give it (same order).
* Scanner −2 per module through `rememberBase`.
* ⚠️ Duck-type: lobby ships are jQuery.extend clones and `instanceof` fails ([[arch_lobby_ship_objects]]).
* ⚠️ Stage 3 must prove two identical hulls do not share a `systems` array: replacing an element on
  one must not show on the other. `copyShip` re-applies refits
  ([gamelobby.js:4041](source/public/client/gamelobby.js#L4041)).

### 9.4 Fleet Check (R6)

One row in `checkChoices`: "Advanced Gravitic Shields: k of n ships (max 25%)" with OK or TOO MANY!.

### 9.5 Buy path, saved fleets, docs

* Buy path and saved fleets: unchanged machinery; the new rows ride the existing tables.
* Docs: `docs/ammo-options.html` "Available Refits" gains three entries; `docs/factions-tiers.html`
  gets a Kirishiac note. Use `data-anchor`, never `id=` ([[project_document_viewer]]).

---

## 10. Gates — what an ordinary game pays

| Path | Cost without these refits |
|---|---|
| Mount at load | one `empty()` per ship |
| Apply in `onConstructed` | the existing early exit |
| `stripForJson` | the existing early exit; the new classes exist only on refitted ships |
| Client factory (D6) | one string compare per system per build |
| Client EW pool | one cached boolean per pool call |
| Movement advance | one static boolean |
| BDEW, stealth detection, late EW | one property read per ELINT ship, inside loops only ELINT ships reach |

Test every gate by forcing it false with modules present and asserting the un-gated result.

---

## 11. Stages and exit tests

Never commit — the user reviews and commits ([[feedback_fv_workflow]]). Run `php -l` against
`/usr/src/current`, not `/usr/src/fieryvoid` ([[howto_docker_db_access]]).

| Stage | Content | Exit test |
|---|---|---|
| **0** | Rulings; baselines | Replay harness `check` green apart from the five known-bad games ([[arch_replay_corpus_known_failures]]); `enhancementsHarness.php` and `enhancementsDifferential.js` green |
| **1** | Both classes (server + client); orbital generalisation (D8, D9); no refit wiring | Scratch PHP: a Lordship orbital hand-swapped through the factory — sub-chart 30/70, and 1–3 / 4–6 with two; orbital death destroys both; regeneration heals both; docked fold; the payload carries blueprint fields. Node: the client factory builds the shield at a beam's id with `weapon` undefined. **Replay `check` unchanged** (the generalisation must be behaviour-neutral) |
| **2** | Registry + offers + sanitiser (groups) + mount at load + `replaceEnhancementSystem` + scanner −2; `fvbuild.ps1 -Autoload`, statics regen | Offer counts on fresh statics: Lordship 16, Kingship 6, Overlord 8, Mastership 28 (24 AGS + 4 ELINT), Conqueror 6, Knightship 4; none on Heavy orbitals or weapons. Scratch game with planted rows: ids swapped, appended ids identical over two loads, a damage row on the swapped id lands on the generator, the enemy payload carries it. A doctored POST with two group rows, ELINT on a beam orbital, or rating 5 on the Conqueror is dropped or clamped |
| **3** | Lobby: labels, exclusion, preview, previews JSON, Fleet Check row | Node lobby harness: buy/unbuy/rebuy restores the originals; two identical hulls stay independent; a copy does not share; destroying the orbital pre-battle refunds and reverts; the 25% row |
| **4** | AGS in play | Server `calculateHitBase` on an in-memory TacGamedata (the SHIP_ENHANCEMENTS Stage 5 `coatShot` pattern): the rating comes off hit and damage inside the arc only, once for two generators, docked and deployed, and not after destruction. The client mirror agrees |
| **5** | ELINT pool (client) + panel row + DEW-row read | Node: `getEWLeft` / `getEwLeftFor` / `convertUnusedToDEW` across payable mixes; module points never reach DEW |
| **6** | Validation + DB writes + use-time gates + lost-points note | PHP harness: E6 exactly (§8.5); four overlapping modules; a destroyed module; idempotence; a non-module ship untouched; the gate forced false runs nothing |
| **7** | Polish and docs (§8.7, §9.5), icons | Visual check in the lobby and in game |
| **8** | Docker play-test matrix (§13) | User-run |

---

## 12. Risk register

| Risk | Mitigation |
|---|---|
| The client merges the new system onto the old weapon's blueprint (`weapon: true` on a shield) | D6; Stage 1 Node test |
| An undeclared property write throws on PHP 8.2 and kills the game load | D9; Stage 1 loads a docked and a deployed refitted orbital |
| A system mounted after damage loads silently loses its damage | D4; Stage 2 damage-row test |
| Appended ids drift between loads, or between lobby and game | D5 ordering, mirrored in the lobby; Stage 2/3 tests |
| Module points leak into DEW, or the panel shows the recompute after validation | §8.2; Stage 5/6 tests |
| A zero OEW row keeps counting as a target | D11 delete; Stage 6 |
| Late EW bypasses the arc rule | §8.6 gate |
| The lobby preview shares a `systems` array between two hulls | Stage 3 two-hull test |
| A POST-side ship carries the old weapon class | D7 analysis; the constraint in the class docblock |
| The orbital generalisation changes existing games | Stage 1 replay `check` |
| Swaps free power for boosting | R13 |
| The Mastership's jump-exit scatter worsens with the −2 scanner | Intended (it reads sensor rating); say so in the data window |

---

## 13. Docker play-test matrix (Stage 8)

| Scenario | Expect |
|---|---|
| Lordship, AGS 3 on two front orbitals | Front shots −15% and −3 damage, counted once; side shots unaffected |
| AGS x2, one generator killed | Shield stays up; the second kill drops it; overkill to the orbital |
| Orbital destroyed (deployed) | Both generators destroyed in the critical phase; no shield next turn |
| Dock and regenerate for 5 turns | Generators restored with the orbital |
| Mastership, ELINT on A + J | Scanner 14 → 10; ELINT row 8; SOEW/DIST/SDEW/JAM offered |
| E6 re-enacted | OEW 12 → 10 after movement; SOEW stands; ELINT lost 4 |
| BDEW with a friendly outside both arcs | That friendly gets no BDEW |
| Enemy view | Sees the generators and their effect; no ✦ badge, no summary line |
| Saved fleet with refits | Reloads with the same refits and points; the preview shows the swap |
| Replay of a refitted game | Identical on re-watch |
| Ordinary game | No change (replay harness) |

---

## 14. Out of scope

* Heavy orbitals (A7).
* AGS on any non-Kirishiac hull.
* Real per-viewer masking of the generators — they must reach the enemy for hit-chance previews
  (WEAPON_ENHANCEMENTS_PLAN.md §6.3).
* A hard buy-time block for the 25% rule (the R6 alternative).
* Any change to `isElint()` for ships without modules.
