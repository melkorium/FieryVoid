# Ship Window Redesign & Legacy Retirement Plan

**Status: DESIGNED 2026-07-14. Stage 1 COMPLETE — user-accepted 2026-07-16 after
five feedback rounds tested in gameid 4247 (all of 1a–1e, rolled-ship mirroring,
and the round-1–5 refinements recorded below). Next: Stage 2 (event relay +
game.php legacy delinking). Stages 2–4 not started.**

Stage 1 files: `shipWindow/ShipWindow.js` (grid/watermark/header/rolled),
`shipWindow/ShipSection.js` (header-integrated structure bar),
`shipWindow/ShipWindowEw.js` (footer strip + interactive target names),
`shipWindow/HitChartPanel.js` + `helpers/buildHitChart.js` (+ `ShipInfo.js` swapped
to the helper), `styled/theme.js` (design tokens, roadmap item 6 seed),
`renderer/icon/EWIconContainer.js` (highlightForTarget, legacy bundle).

**Rolled-ship mirroring (user decision 2026-07-16, supersedes the §1 non-goal):**
when `shipManager.movement.isRolled(ship)` the port/starboard grid columns swap
(3↔4, 31↔41, 32↔42 — grid areas and icon mirroring follow the drawn side, section
names keep the true location), and an amber "⟲ ROLLED — port / starboard reversed"
banner renders at the bottom of the window.

**Feedback round 1 (gameid 4247, 2026-07-16) — applied, supersedes §8.1:**
1. Hit chart left the header: "⊕ Hit Chart" labelled button top-left of the window
   body (the SCS grid's empty corner cell). Click opens, click anywhere outside
   closes (document `pointerdown` + containment check); no hover-show, no pin ✕.
2. New "✎ Notes" button beneath it — popup reuses `ShipInfo` with a new
   `hideHitChart` prop (notes / attached units / enhancements).
3. Watermark brightened: opacity 0.15→0.32, brightness 1.75; section glass
   `panelBgGlass` 0.78→0.55 alpha.
4. Ship-level hover tooltip suppressed everywhere in the window (underlay, image;
   header name inert) — clicks (`SystemClicked`) kept. Unrevealed-mine "?" hover
   kept (only way to inspect a mine).
5. Sections without a Structure system render no header bar.
6. Sizing: big-base quarter sections render `wide` (156px, 4-wide `pickOuter`
   ordering, no mirror needed — symmetric rows); six-sided quarters get
   `min-height: 84px` so sparse hulls (Vree Xill) keep presence.
7. EW became a vertical panel top-right, as a grid item: row 1 of the template is
   now always `"ctrl fwd ew"` — chrome row grows with content, overlap impossible.

**Feedback round 2 (gameid 4247, 2026-07-16) — applied:**
1. Primary section border recoloured to the shared line colour (still 2px solid).
2. Transparency pass: watermark opacity 0.45, `panelBgGlass` 0.40 alpha, idle
   SystemIcon background black → rgba(0,0,0,0.7) (state colours stay opaque;
   also affects WeaponList/FighterIcon — visually nil on dark panels).
3. Alignment/stretch fix: grid columns `auto auto auto` → `1fr auto 1fr` so the
   two side tracks always resolve equal (centre column stays on the window
   midline even with asymmetric chrome); watermark now sized square from the
   body HEIGHT (`height:80%; aspect-ratio:1/1; max-width:80%`) — short windows
   (satellites) no longer centre-crop the art into a "stretched" wide slice.
4. Mines/terrain compact windows use the watermark + click underlay too — the
   old 114px `ShipImage` thumbnail is gone entirely; `CompactBody` got
   `min-height: 120px` so sparse mine windows show the art.
5. Text brightened: header class name + Hit Chart/Notes buttons + EW panel
   title now `textAccent` (buttons go white when active).

**Feedback round 3 (gameid 4247, 2026-07-16) — applied (+ user hand-tweaks kept:
Primary border 1px, idle SystemIcon alpha 0.4):**
1. Hit Chart/Notes buttons centred in their grid column (`justify-self: center`).
2. Hit chart popup arranged geographically: Port column | Front/Primary/Aft
   column | Starboard column, side columns vertically centred (HitChartPanel
   LEFT/CENTRE/RIGHT_LOCATIONS).
3. Compact windows (mines/terrain): buttons render as a centred full-width ROW
   above the sections (`ControlsArea $row`); popup anchors at top 30px there.
4. Watermark stronger still: opacity 0.55, brightness 1.9, `panelBgGlass` down
   to 0.22 alpha (sections nearly borders-only over the art).
5. Header: name 11px + ellipsis (flex-shrink 1), class 9px + ellipsis
   (flex-shrink 3, gives way first), `title` attrs carry full text — long
   flight names no longer clip past the ✕.

**Feedback round 4 (gameid 4247, 2026-07-16) — applied:**
1. Popup un-caged: window container `overflow: visible` (watermark clipping
   moved onto SectionGrid/CompactBody), PopupHolder is now a direct child of
   the container (grid top 78px, compact 72px) so it can extend below the
   window; cap 70vh with the site-standard scrollbar (10px, #0d1620 track,
   #3c5574 thumb — same as #gameinfo/log panel) + 10px bottom padding.
2. Notes button hover-peeks the notes popup on desktop (150ms grace timer so
   the pointer can cross into the popup); click still pins, click-outside
   still closes, clicked panel always wins over hover.
3. Compact windows (mines/terrain): buttons back to a vertical centred list
   above the sections (`ControlsArea $compact`) with 5px bottom margin.
4. `windowBg` #0a3340 → #152029 (darker, desaturated) so the brightened
   grayscale watermark pops.

**Feedback round 5 (gameid 4247, 2026-07-16) — applied:**
1. Notes popup sizes to content (`PopupHolder $fit`: fit-content width, capped
   at the window); hit chart keeps the full-width span.
2. `CtrlButton min-width: 120px` — matches the EW panel opposite and equalises
   Hit Chart/Notes in compact windows too.
3. EW title = edge-to-edge bar with the buttons' `panelBg` fill, nowrap single
   line (letter-spacing 0.5px).
4. Header `align-items: baseline` + name `line-height: 26px` — name and class
   share a baseline, vertically centred in the bar.
5. Hit chart section names = shaded header bars (rgba(73,103,145,0.25),
   edge-to-edge via negative margins).

**Final tweaks + acceptance (2026-07-16):** EW panel `justify-self: center`
(matches the Hit Chart/Notes stack; noticed on the Orion base). Rolled-ship
follow-up after in-game test: swapped port/starboard sections also flip their
icon ART horizontally (`SystemIcon $mirror` — art moves to a scaleX(-1)
`::after` layer with its own stacking context, so text/health bar/state
overlays stay unflipped; direction-specific thruster art reads correctly).
**User marked Stage 1 complete.**
Covers roadmap item 5 (retire legacy ship-window DOM) plus two extensions the
design review added: unify gamelobby onto the React ShipWindow, and redesign the
window's appearance in the spirit of the original B5Wars Ship Control Sheets
(SCS). Written from the perspective of: "one window codebase, one design
language, laid out like the paper game aid players already know."

---

## 1. Goals & non-goals

### Goals
1. **One ship window implementation** — the React `ShipWindow` stack — used by
   both `game.php` and `gamelobby.php`. Legacy `UI/shipwindow.js` (1648 lines),
   `UI/flightwindow.js`, the `#shipwindowtemplatecontainer` HTML templates and
   `styles/shipwindow.css` are deleted at the end.
2. **SCS-inspired redesign**: monochrome ship art as the window's background
   watermark (not a corner thumbnail); sections arranged geographically
   (Forward top, Port left, Starboard right, Aft bottom, Primary centre) like
   the paper SCS; six-sided ships get their four side sections at side-column
   height instead of sharing a row with Aft.
3. **Hit chart as a first-class control**: hover/click button in the window's
   top-left opening a per-section hit table (replaces both the legacy
   "Display Hit Chart" button and the buried hover-the-ship-image path).
4. **EW readability**: larger, structured EW panel in game.php.
5. **Lobby gains clickable/hoverable system icons** (info popups only — the
   same SystemInfo players see in game), Notes/loadout panel that no longer
   overlaps systems.
6. **Zero functional regressions in game.php** — every interaction that works
   today (fire selection, called shots, hangar/LCV dialogs, power via React
   menus, thrust assignment, mine reveal, terrain windows, touch long-press)
   works identically after the redesign. One deliberate *addition* (user
   decision, §8.4): OEW target names in the EW strip become interactive —
   click scrolls the map to the target, hover highlights its EW line sprite.

### Non-goals
- No new *game* functionality in the lobby. Anything phase-dependent stays
  gated: `gamedata.gamephase === -2` (lobby buy phase) renders the read-only
  variant. SystemIcon's action branches are already gated on phases 3/1/5/-1,
  so the lobby falls through naturally; the lobby event relay simply never
  routes action events.
- No rework of SystemInfo / SystemInfoMenu content (reuse as-is).
- ~~Rolled-ship section mirroring: the React window has never mirrored
  port/starboard for rolled ships (legacy CSS `.shipwindow.rolled` existed but
  the code that applied the class is commented out). Parity, not a fix — noted
  as a possible follow-up.~~ **Promoted to a feature and built with Stage 1
  (user request 2026-07-16)** — see the status block at the top.

---

## 2. Current state (verified 2026-07-14)

### game.php — two stacks coexist
- **Visible window = React.** `renderer/shipWindowManager.js` defines
  `window.ShipWindowManager` (capital S), instantiated in `PhaseDirector`
  (line 23) with a `UIManager` rendering `ShipWindowsContainer` into
  `#shipWindowsReact` (game.php:708). Opened from `PhaseStrategy`
  (`onShipClicked` → `.open(ship)`, lines 39/294), max one own-team + one
  enemy-team window (filter in `ShipWindowManager.open`).
- **React components** (`client/UI/reactJs/shipWindow/`):
  - `ShipWindow.js` (383) — container, header (name/class/✕), 114×114 rotated
    ship thumbnail top-left, `ShipWindowEw` box top-right, three flex "Column"
    rows of sections: `[img, 1, EW] / [3, 31, 0, 4, 41] / [32, 2, 42]`;
    flight variant renders `FighterList`; unrevealed-mine variant renders a
    "?" icon. jQuery-UI `draggable()` on mount. Mouse/touch handlers fire
    `webglScene.customEvent('SystemMouseOver'/'SystemClicked'/...)`.
  - `ShipSection.js` (348) — `SystemIcon` grid + section structure bar
    (`hp/max A n`); the `pick`/`pickOuter` grouping keeps identical weapons
    adjacent and symmetric (worth preserving).
  - `SystemIcon.js` (815) — the complete interaction surface: firing/called
    shot/loading/selected/offline/boosted/docked visuals, click routing
    (weapon select, hangar/catapult/rail dialogs, LCV rails, augmenter
    special cases), right-click select-all, touch long-press. Everything
    routes through `webglScene.customEvent`.
  - `ShipWindowEw.js` (134) — 114px box, 8.5px font: DEW/CCEW/BDEW/detect +
    per-target OEW/DIST/SOEW/SDEW rows. The readability complaint.
  - `FighterList/FighterIcon` — flight windows incl. Hangar-Ops overlays.
- **Legacy `window.shipWindowManager`** (lowercase, `UI/shipwindow.js`) is
  still loaded and *lazily* built (`ShipIcon.createShipWindow` only re-links
  pre-existing DOM; nothing eagerly builds). In game.php it is reachable via:
  - `fleetList.js:600-604` — docked flights open the **legacy flight window**
    (`flightWindowManager.open`) because a docked flight has no map icon.
    This is the last *visible* legacy window in game.php.
  - `botPanel.js:65-68` — `shipWindowManager.addEW(ship, $("#botPanel"))`
    fills DEW/CCEW spans inside the bot panel.
  - **Assign-thrust logic that is not DOM styling**: `assignThrust`
    (fires the `AssignThrust` customEvent the React `ShipThrust` panel
    consumes; movement.js calls it from ~10 sites), `doneAssignThrust`
    (commits the move, `amendContractValue` for contraction) and
    `cancelAssignThrust(Event)` (reverts + splices the move) — called by React
    `ShipThrust.js` (140/144/150-159/260-266) and `movement.js`.
  - ~40 `setData`/`setDataForSystem` call sites across `power.js` (14),
    `weaponManager.js` (7), `movement.js` (7), `gamedata.js:410`,
    `model/system/defensive.js` (2), `ships.js`, `FlightIcon.js`,
    `ShipIcon.js` — all no-op while the window DOM is unbuilt, but all would
    `ReferenceError` if `window.shipWindowManager` disappeared.
  - `ships.js` canvas-era `initShips` path (lines 45-50) is dead — nothing
    calls `drawShips` since the WebGL renderer; treat as delete-on-sight.
- Both pages load `styles/shipwindow.css`; `lobby.css` adds overrides.

### gamelobby.php — legacy only
- **No React at all**: no `UI.bundle.js`, no `UIManager`, no `webglScene`, no
  PhaseDirector. `window.ShipWindowManager` doesn't exist.
- Right-click a ship row → `gamedata.onShipContextMenu` (gamelobby.js:3644) →
  legacy `createShipWindow` + `lobbyEnhancements.setEnhancementsShip/Fighter`
  (mutates ship/systems so the window shows enhanced stats) → `setData` →
  `open`. Enhancement changes destroy the window (gamelobby.js:3136-3139) so
  it rebuilds with fresh numbers.
- Lobby stubs (gamelobby.php inline, ~lines 150-272): a fake `weaponManager`
  (`hasFiringOrder:false`, `isLoaded:true`, `isSelectedWeapon:false`,
  `getWeaponCurrentLoading` reads `normalload`, hover →
  `systemInfo.showSystemInfo`), `movement.isRolled → false`,
  `shipWindowManager.addEW → noop`. Legacy `UI/systemInfo.js` is
  **lobby-only** (its own header comment says so).
- Lobby `gamedata` is a *different object* (gamelobby.js): `gamephase` = -2,
  `getPlayerTeam(id)` **takes a slot argument** (game.php's takes none),
  `isTerrain(shipSizeClass, userid)` exists, `getShip(phpclass, faction)` /
  `getFleetShipById(id)`. Blueprints arrive in `gamedata.allShips` via
  `gamelobbyloader.php` (see [[arch_gamelobby_static_ship_access]]);
  `window.staticShips` does NOT exist here.
- The `.notes` column (template col3) carries: TC/TD, profile F/S, Ini,
  Acc/Pivot/Roll, agile, full fighter complement (incl. restricted-bay and
  default-shuttle rendering, shipwindow.js:423-530), `ship.notes`, limited %,
  variant-of, ISD, CUSTOM/SEMI-CUSTOM flags. **This content must survive** —
  it is the fleet-selection datasheet. Its overlap with system grids on
  big/six-sided ships is one of the driving complaints.
- Hit chart is currently *suppressed* in the lobby (`gamephase > -2` gate at
  shipwindow.js:166). `ship.hitChart` data itself is present on client ships
  in game.php (React `ShipInfo.js` already renders it as text); **verify** the
  lobby blueprint Ships also carry `hitChart` before promising the lobby hit
  chart (expected yes — same static JSON — but confirm in DevTools).

### Layout bug being fixed (user report)
Six-sided ships (e.g. Kirishiac Conqueror): React row 3 renders
`[32 | 2 | 42]`, putting Port-Aft and Starboard-Aft on the same horizontal
level as Aft, which reads wrong against the hull. Bases via the legacy base
template stack 31/32 and 41/42 into tall side sections instead.

**Stop-gap APPLIED 2026-07-14** (ahead of Stage 1): for `ship.SixSidedShip`
hulls, `ShipWindow.js` renders everything between the Forward and Aft rows as
**one flex band** (`MiddleRow`, `align-items: center`): two `SideStack`
columns (Port-Front over Port-Aft, Starboard-Front over Starboard-Aft;
30% wide, stretched to the band, `justify-content: space-between` so their
sections sit at the band's top/bottom edges) flanking the Primary section,
which centres vertically in the band; Aft renders alone on a centred row
below. `ShipSection` gained an `inStack` prop (width `auto` → fills the
stack). Non-six-sided ships and bases render the original two rows,
untouched, until Stage 1's grid supersedes everything. Needs
`yarn watch`/`yarn build` + an in-game look at a six-sided ship (Conqueror /
Mind's Eye) to confirm.

Two earlier attempts, rejected on look — don't retry at Stage 1:
`align-content: center` inside a stretched section (centred the icon lines,
not the box); `align-items: center` on the row Columns (centred Primary only
within its own row — with 32/42 on a *separate* flex row, that row's height
IS Primary's height, so Primary still hugged the Forward row). The lesson
Stage 1 inherits: Primary must share one grid/flex band with all four side
sections to centre between Forward and Aft.

---

## 3. Design vision — "digital SCS"

Reference: AoG Ship Control Sheets (Omega example). What the paper sheet gets
right: the *silhouette anchors the layout* — you find a system by looking
where it physically is on the ship. What we keep from the current window:
compactness, live health bars, one-窗-per-side, draggable.

### 3.1 Window anatomy (game.php, capital ship)

```
┌────────────────────────────────────────────────────┐
│ ⊕  EAS AGAMEMNON            Omega Destroyer      ✕ │  header bar
├────────────────────────────────────────────────────┤
│                 ┌─ FORWARD ── 63/63 ▮▮▮▮▮ A7 ─┐    │
│                 │  [icons, 3-4 wide]           │    │
│ ┌─ PORT ──────┐ └──────────────────────────────┘   │
│ │ 60/60 A6    │ ┌─ PRIMARY ─── 84/84 A8 ──────┐ ┌─ STARBOARD ─┐
│ │ [icons]     │ │ [icons over the monochrome  │ │ 60/60 A6    │
│ │             │ │  ship watermark, nose-up]   │ │ [icons]     │
│ └─────────────┘ └──────────────────────────────┘ └─────────────┘
│                 ┌─ AFT ────── 58/58 A5 ────────┐   │
│                 │  [icons]                     │    │
│                 └──────────────────────────────┘    │
├────────────────────────────────────────────────────┤
│ EW   DEW 10   CCEW 0   BDEW 2  │ OEW → Sharlin 4   │  EW strip
└────────────────────────────────────────────────────┘
```

- **Watermark**: the existing `ship.imagePath` art, centred, nose-up
  (`rotate(-90deg)` like today's thumbnail), `filter: grayscale(1)
  brightness(1.6); opacity: 0.12-0.18`, `pointer-events: none`, sized to the
  primary/centre cell (spilling under adjacent cells is fine — sections have
  translucent panel backgrounds so icons stay legible). Clicking/hovering the
  *ship itself* (name in header + watermark area not covered by icons) keeps
  today's behaviour (ship-level SystemMouseOver/SystemClicked) via an
  underlay hit area in the centre cell.
- **CSS grid, not flex rows** — the structural fix:

  ```
  grid-template-areas:   (5-section ship)      (six-sided / base)
      ".    fwd   ."         ".    fwd   ."
      "port prim  stbd"      "pfwd prim  sfwd"
      ".    aft   ."         "paft prim2 saft"
                             ".    aft   ."
  ```
  Locations map: 1→fwd, 0→prim, 2→aft, 3→port, 4→stbd, 31→pfwd, 32→paft,
  41→sfwd, 42→saft. Six-sided ships thus get Port-Aft/Starboard-Aft in the
  side columns *above* the Aft row — directly fixing the reported layout.
  Sections that don't exist collapse (grid auto). Terrain/OSAT/mine keep the
  compact single-panel variant.
- **Section header** replaces today's bottom structure bar: dotted panel
  border retained, header line reads `PORT  60/60  A6` with the health bar as
  the header's background fill (green→orange-with-crits, exactly today's
  colour semantics from `ShipSection`'s `StructureContainer`). Icon grouping
  (`pick`/`pickOuter`) is kept.
- **Header bar**: `⊕` hit-chart button (left), ship name + class, ✕ close.
  Faction/team tint on the header respects the team-colour gate
  ([[arch_team_colour_logic]]) — reuse whatever palette call the fleet list
  uses rather than inventing a 7th site.
- **Hit-chart flyout** (`HitChartPanel.js`, new): hover on `⊕` shows it while
  hovered; click pins it open for longer reading (✕/re-click unpins); touch
  devices get the click path only (decided, §8.1). Renders one small table
  per section — rows `system | %` sorted descending, retargeting prefix
  stripped — i.e. the legacy `hitChartSetup` tables rebuilt in React (share
  the percent-building code with `ShipInfo.js` by extracting a
  `buildHitChart(ship)` helper so the two never drift). Anchored below the
  header, scrolls internally if tall.
- **EW strip** (game.php only): full-width footer, 11px, one labelled chip
  per value (`DEW 10`, `CCEW 0`, `BDEW n`, detect values), then per-target
  rows (`OEW → <target> n`, SDEW/DIST/SOEW with today's ConstrainedEW maths —
  reuse `ShipWindowEw`'s `getEW`/`getAmount` functions unchanged, restyle).
  "DEPLOYS ON TURN n" replaces the strip pre-deployment, as today.
  **Target names are interactive** (decided, §8.4): click scrolls the map to
  the target — fire the existing `ScrollToShip` custom event, and respect
  `shipManager.shouldBeHidden(ship)` exactly like `fleetList.doScrollToShip`
  so stealthed/undeployed targets don't leak position; hover highlights that
  entry's EW line sprite on the map via a new small `EWIconContainer` API
  (e.g. `highlightForTarget(shooter, target, on)`), cleared on mouse-out and
  guarded so a missing sprite (hidden target) no-ops.
- **Flights**: header + `FighterList` unchanged inside the new chrome; EW
  strip only if the flight has EW today (it doesn't — omit).
- **Width**: capitals ~480px (up from 400), grid collapses to today's
  footprint for small ships; ≤1024px media query keeps the full-screen
  scroll behaviour.
- Visual language: keep the established palette (#0a3340 body, #04161c
  panels, #496791 lines, #C6E2FF text accents) so the window still belongs to
  the current game skin — the SCS feel comes from layout, watermark,
  uppercase microtype and aligned columns, not a new colour scheme. Define
  the palette once as CSS variables / a `styled` theme object → this is the
  seed for roadmap item 6 (visual unification of remaining jQuery windows).
  Load the `frontend-design` skill when implementing this stage.

### 3.2 Lobby variant (gamephase === -2)

Same component, `lobby` mode differences only:
- **No EW strip** (meaningless pre-game — matches today's hidden EW div).
- **Right rail: "Datasheet" panel** (new `ShipNotesPanel.js`) replacing the
  overlap-prone `.notes` column: manoeuvre block (TC/TD, Acc/Pivot/Roll,
  Profile, Ini), complement block (fighters incl. restricted-bay merge +
  default shuttles — port `shipwindow.js:423-530` logic into a shared helper),
  metadata block (ISD, variant-of, limited %, CUSTOM/SEMI-CUSTOM, agile,
  ship.notes), enhancements block (from `ship.enhancementTooltip`, already
  maintained by lobbyEnhancements). On narrow screens the rail wraps below
  the grid — overlap becomes impossible by construction.
- **Icons hover/click → SystemInfo popup only.** This is the "added
  functionality" ask: lobby players get the same React `SystemInfo` content
  game players see (weapon stats, hangar capacity incl. default-shuttle
  fold-in, ammo). No menus, no selection.
- **Hit-chart flyout enabled** if blueprint `hitChart` is confirmed present
  (§2 verify step); otherwise hide the `⊕`.

---

## 4. Architecture changes

### 4.1 Shared event routing (the webglScene problem)
React shipWindow components call `webglScene.customEvent(...)` ~20×. In
game.php that relays to PhaseDirector. The lobby has neither.

**Do not** shim a fake `webglScene` per page. Extract a tiny relay:

- New legacy file `client/uiEventRelay.js`: `window.uiEvents =
  { relay(name, payload){ handler && handler(name, payload) }, setHandler }`.
- game.php: `webglScene.customEvent` becomes `uiEvents.relay` + its existing
  `requestRender()`; `uiEvents.setHandler` → `phaseDirector.relayEvent`.
  (Or simpler: keep `webglScene.customEvent` as-is and have React components
  call `window.uiEvents.relay`, with game.php wiring
  `uiEvents.setHandler((n,p) => webglScene.customEvent(n,p))` — smaller diff,
  render-gating invariant untouched. **Preferred.**)
- gamelobby.php: `uiEvents.setHandler(lobbyUiHandler)` — a ~60-line handler in
  gamelobby.js mapping `SystemMouseOver/ShowInfo → uiManager.showSystemInfo`,
  `SystemMouseOut → hideSystemInfo`, `CloseShipWindow →
  shipWindowManager.close(ship)`, everything else → ignore.
- React components change `webglScene.customEvent(` →
  `window.uiEvents.relay(` (mechanical, ~20 sites in shipWindow/ + SystemIcon).

### 4.2 Lobby React bootstrap
- gamelobby.php: add `<div id="shipWindowsReact">`, `<div id="systemInfoReact">`,
  load `UI.bundle.js` + `renderer/shipWindowManager.js` script tags (React/
  styled-components are self-contained in the bundle; jQuery+jQuery-UI already
  load — draggable works).
- gamelobby.js boot: `window.shipWindowManagerReact = new
  window.ShipWindowManager(new window.UIManager($("body")[0]))` and route
  `onShipContextMenu` through it (keep the lobbyEnhancements mutation call
  *before* `.open(ship)`; enhancement changes call `.update()` instead of
  destroying DOM — React re-renders from mutated ship state, killing the
  remove/rebuild dance at gamelobby.js:3136).
- **Lobby gamedata surface** used by the React stack — provide once in
  gamelobby.js, don't patch components: `getPlayerTeam()` no-arg wrapper
  (window placement), `isMyShip(ship)` (`ship.userid == thisplayer`),
  `waiting=false`, `replay=false`, `rules` stub, `getShip(id)` falling back to
  `getFleetShipById`. The existing fake `weaponManager` already satisfies
  SystemIcon's render path; extend only if a render throws (e.g.
  `isLoadedAlternate`, `getCalledShotInfo`, `getFiringOrder` → add
  false/null stubs).
- `ShipWindowManager.open`'s team filter calls `getPlayerTeam()` — the no-arg
  wrapper covers it; in the lobby both windows may be your own picks, so relax
  the filter in lobby mode (one window per `fleetList` flag instead of per
  team — mirrors today's owned/enemy split at `turn 0`, shipwindow.js:56-102).

### 4.3 Assign-thrust extraction (the real logic hiding in shipwindow.js)
Move `assignThrust`, `doneAssignThrust`, `cancelAssignThrustEvent`,
`cancelAssignThrust` into **movement.js** (they mutate `ship.movement` and
fire movement events — that's movement domain), dropping the legacy-DOM
styling block in `assignThrust` (React ShipThrust already renders allocation
state from the `AssignThrust` event). Callers to update:
`movement.js` (~10 internal sites), `ShipThrust.js` (4 sites),
`shipwindow.js` internal calls (die with the file). Keep the
`webglScene.customEvent("AssignThrust", ...)` payloads byte-identical.

### 4.4 botPanel EW
Replace `shipWindowManager.addEW(ship, $("#botPanel"))` with a small
`ew.fillEWSummary(ship, container)` helper in `ew.js` (writes the DEW/CCEW
spans; 15 lines lifted from legacy `addEW`), or mount the restyled EW strip
component. The helper is less machinery for a bot-only panel — **preferred**.

### 4.5 Docked flights in fleetList
`fleetList.js:600-604` switches from `flightWindowManager.open(ship)` to the
React manager (game.php: `phaseDirector.shipWindowManager` is not globally
reachable — expose it as `window.shipWindowManagerReact` in PhaseDirector, or
fire a `OpenShipWindow` custom event PhaseStrategy already knows how to relay
(`onShipClicked` handler path). Event is cleaner. The React window already
renders docked-state overlays (FighterIcon DOCKED labels), so no visual loss.

### 4.6 Retirement sweep (only after both pages are on React)
Delete: `UI/shipwindow.js`, `UI/flightwindow.js`, `UI/systemInfo.js` (legacy,
lobby-only), `#shipwindowtemplatecontainer` + `#hitChartTable` templates from
both PHP files, `styles/shipwindow.css` links + file, lobby `.shipwindow`
rules in `lobby.css`, the gamelobby.php `shipWindowManager.addEW` stub and the
fake-weaponManager hover half (keep the predicate stubs SystemIcon needs).
Strip the ~40 guarded `setData/setDataForSystem` call sites (power.js,
weaponManager.js, movement.js, gamedata.js:410, defensive.js, ships.js:45-68
dead block, ShipIcon/FlightIcon `createShipWindow` re-link code and their
`prepare()` callers). grep-verify: `shipwindow|shipStatusWindow|
flightWindowManager|shipWindowManager\.` must return only the React manager.

---

## 5. Staging & order of work

Ordering rationale: redesign first (player-visible win, and the lobby then
migrates onto the *final* component, not the old one); lobby unification
second; retirement last (legacy call sites live in files **shared** by both
pages, so nothing legacy can be deleted until the lobby is off it).

### Stage 1 — SCS redesign of the React window (game.php)   [~2-3 sessions]
1a. Grid layout engine in `ShipWindow.js`/`ShipSection.js` (5-section,
    six-sided, base, terrain/mine, flight chrome), watermark, section
    headers with integrated structure bars, width bump, mobile query.
1b. `HitChartPanel.js` (hover-shows / click-pins, §8.1) + shared
    `buildHitChart` helper (also swap `ShipInfo.js` to the helper).
1c. EW strip restyle (reuse getEW logic).
1d. Design tokens (CSS vars / theme object) — seed for roadmap item 6.
1e. EW strip target-name interactivity (§8.4): `ScrollToShip` on click with
    the `shouldBeHidden` guard; hover → `EWIconContainer.highlightForTarget`
    (new API in `renderer/icon/EWIconContainer.js` — legacy file, so this
    sub-step touches `yarn watch:legacy` territory too). The one intentional
    behaviour addition; everything else in Stage 1 is visual-only.
Exit: game.php pixel-check across: Omega (5-section), Conqueror (six-sided),
a base, OSAT, mine (hidden + revealed), terrain, flight, docked flight,
enemy ship (redacted power), pre-deployment ship ("DEPLOYS ON TURN").
Mostly `yarn watch`; 1e also touches `EWIconContainer.js` (legacy watch).
**No behaviour diffs except 1e.**

### Stage 2 — event relay + game.php legacy delinking   [~1-2 sessions]
2a. `uiEventRelay.js` + React components → `uiEvents.relay` (game.php wiring
    delegates to webglScene.customEvent; render gating preserved).
2b. Assign-thrust extraction to movement.js (§4.3).
2c. botPanel EW helper (§4.4).
2d. fleetList docked flights → React window via event (§4.5).
Exit: with DevTools breakpoint on legacy `ensureShipWindow`: never hit during
a full game.php session (all phases + thrust + docked flight + bot game).
ForcedOffline regression check (§6). Legacy files still loaded (lobby parity)
but unreachable in game.php.

### Stage 3 — lobby unification   [~2 sessions]
3a. gamelobby.php: bundle + mounts + manager bootstrap (§4.2), lobby
    gamedata surface, lobby event handler → React SystemInfo.
3b. Lobby mode in ShipWindow: EW strip off, `ShipNotesPanel` datasheet
    (port notes/fighter-complement logic into shared helper), hit-chart
    verify-then-enable, enhancement `.update()` re-render loop.
3c. Retire lobby usage: `onShipContextMenu` → React; delete the legacy-open
    path, `systemInfo.js` hover glue.
Exit: lobby side-by-side audit vs live for: capital, six-sided, base,
fighter flight, LCV, ship with enhancements (buy one → window updates),
restricted-hangar ship (Suom/Roka reserved lines), default shuttles,
variant/ISD/custom flags. No overlap at any fleet size. **No purchase
functionality moved or added.**

### Stage 4 — retirement sweep + polish   [~1 session]
§4.6 deletions, grep-verify, `yarn build` + statics untouched, dead-file
sweep also catches `ships.js` initShips block. Update
`arch_forcedoffline_powerentry_lazywindow` memory (the "legacy window" it
references no longer exists) and the roadmap.

Each stage is independently shippable; live can sit on any stage boundary.
Bundles regenerate per deploy as usual (never committed).

---

## 6. Risk register

| Risk | Mitigation |
|---|---|
| **ForcedOffline cooldown** — enforcement is already window-independent (`power.js applyForcedOfflineEntry` via `copyLastTurnPower`, server authoritative since 2026-06-04), but `setPowerClasses` still runs it redundantly from the legacy render path | Before Stage 2d lands: fire a SurgeBlaster, never open the ship's window, verify next-turn cooldown + server rejection of re-enable. Audit `setSystemData`/`setPowerClasses` for any *other* state writes before deleting (known: none, but verify) |
| Legacy `shipWindowManager` global vanishing while shared files still reference it | Retirement of call sites only in Stage 4, after both pages migrated; Stage 2 leaves all guards in place |
| Lobby `getPlayerTeam(id)` vs game `getPlayerTeam()` signature clash | No-arg wrapper in lobby bootstrap; never edit the game version |
| Blueprint ships share static-data references ([[arch_client_system_shared_reference]]) — watermark/notes helpers must not mutate ship/systems | Helpers are read-only; enhancement mutations stay in lobbyEnhancements |
| Lobby blueprint `hitChart` possibly absent from static JSON | Verify first; feature-detect (`ship.hitChart && Object.keys(...).length`) and hide `⊕` otherwise |
| SystemIcon render calling a weaponManager fn the lobby stub lacks | Render every unit type in lobby during 3a with console open; extend stub with inert defaults |
| Six-sided/base grid regressions on unusual hulls (UnevenBaseFourSections, SmallStarBase*, Vorlon/Vree/Mindrider capitals) | Stage 1 exit checklist includes one of each `ShipClasses.php` layout family |
| Touch behaviour (long-press info, ghost-click suppression) subtly broken by new DOM | Don't restructure SystemIcon's handler logic — restyle around it; test on a touch device per stage |
| Draggable + new grid sizing fighting (jQuery UI sets inline top/left) | Drag handle = header bar only (matches most windowing conventions; smaller hit-test surface) |
| `uiEvents` relay dropping `requestRender()` — stale WebGL frame after window interaction ([[arch_render_loop_idle_gating]]) | game.php handler delegates INTO `webglScene.customEvent` so gating/rendering path is unchanged |
| Replay screen (`replay.php`) also mounts ship windows via PhaseDirector | Include replay in Stage 1/2 checklists (`gamedata.replay` guards already exist in SystemIcon) |

---

## 7. Test checklist (run per stage; full sweep at Stage 4)

game.php: open own + enemy windows every phase (-1/1/2/5/3/waiting/replay);
weapon select/deselect + right-click select-all + called shot ⊕; ballistic in
phase 1; hangar launch / deploy-dock / LCV rail dialogs; power on/off/boost via
React menus incl. forced-offline grey-out; thrust assignment (auto + manual +
cancel + contraction), six-sided ship, base, OSAT, mine reveal flow, terrain;
flight window incl. DOCKED/DROPOUT overlays; docked flight from fleet list;
EW strip vs old values incl. ELINT ship (SDEW/DIST maths) and ConstrainedEW
Mindrider; OEW name click scrolls to target (and does NOT scroll to a
stealthed/hidden one), hover highlights the right EW line and clears on
mouse-out; hit-chart flyout: hover shows, click pins, unpin works, touch
click-only, vs rulebook SCS for Omega; deploy-turn ship; touch: tap select,
long-press info, drag. gamelobby.php: everything in Stage 3
exit list. Cross-cutting: no `shipwindow` selectors in DevTools DOM at
Stage 4; bundle sizes noted before/after (shipwindow.css + 2 legacy files
removed should offset the grid CSS growth).

---

## 8. Resolved questions (user decisions, 2026-07-14)

1. **Hit-chart flyout trigger — both.** Hover shows the flyout while
   hovered; click pins it open for longer viewing and is the only path on
   touch devices (no hover there). ✕/re-click unpins.
2. **Watermark contrast — start universal.** One conservative
   grayscale/brightness/opacity value for all factions; revisit per-faction
   auto-tune only if dark (Shadow) or bright (Minbari) hulls prove
   unreadable in practice.
3. **Lobby window placement — unchanged.** Keep the left/right split by
   `userid == 0`.
4. **OEW target names are interactive — yes (game.php only).** Click scrolls
   the map to the target (existing `ScrollToShip` event + `shouldBeHidden`
   guard); hover highlights that target's EW line sprite on the map (new
   `EWIconContainer.highlightForTarget` API). Implemented as Stage 1e; the
   redesign's single intentional functionality addition. Absent from the
   lobby (no EW strip there).
