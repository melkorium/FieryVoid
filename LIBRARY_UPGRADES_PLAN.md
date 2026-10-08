# Library Upgrades — PHP, jQuery, THREE, React

A version review of the four libraries Fiery Void is built on, with a plan for the ones that are behind
and a straight answer on whether each upgrade is worth doing.

Status (2026-10-08):
- **Review done.** Everything below was measured, not inferred: throwaway PHP 8.2/8.4/8.5 containers with
  the repo mounted read-only, scratch builds of the THREE shim and the React bundle against newer versions
  (written to a temp folder), and a read-only fetch of the live UI bundle.
- **Live PHP is already 8.4.24** (LiteSpeed LSAPI, alt-php; confirmed by you). That makes most of the
  original PHP plan unnecessary, and it turns the `round()` item (§2.4) into a bug that is live now.
- **BUILT, uncommitted:** local Docker on `php:8.4-fpm` (§2.1), and the React build-mode fix (§5.1).
- Open decisions: §6.

## 1. Verdict at a glance

| | FV runs | Current upstream | Verdict |
|---|---|---|---|
| **PHP** | live 8.4.24; local now 8.4.26 | 8.5.11 (8.6 is at RC3) | **Done** — local matches live. Two follow-ups (§2.3); 8.4 is supported to 31 Dec 2028 |
| **jQuery** | 4.0.0 + jQuery UI 1.14.2 | 4.0.0 (Jan 2026) + UI 1.14.2 (Jan 2026) | **Up to date** |
| **THREE** | r160 (0.160.1, Jan 2024) | r186 (0.186.1, Sep 2026) | **Reasonable to do now, after React** — two known fixes, a visual QA pass (§4) |
| **React** | 18.3.1 (the last 18.x, Apr 2024) | 19.3.0 (Sep 2026) | **Build mode fixed (−45%).** Version bump: cheap, worth doing now (§5) |

## 2. PHP — live is on 8.4, local now matches

### 2.1 Local Docker moved to 8.4 — BUILT 2026-10-08
`docker/php/Dockerfile:1` is now `php:8.4-fpm`. It tracks the latest 8.4.x patch, so it's 8.4.26
locally against live's 8.4.24; patch releases are bug fixes only. Verified after the rebuild:
- apcu, mysqli, zip, curl, mbstring and OPcache are all loaded; memory_limit, APCu and timezone settings
  are unchanged (they come from the bind-mounted `docker/php/php.ini`).
- `fvbuild -Check` gives exactly the 8.2 result: class map up to date, validator PASS (0 new findings),
  replay harness 118 passed with the same five known-bad games.
- game.php (game 4444) and gamelobby.php (lobby 4448) load as real players with no PHP deprecations
  logged.

Recreating the container wiped its `/tmp`, so local logins (session files) were reset.

### 2.2 What was tested before that

| Test | 8.4 | 8.5 |
|---|---|---|
| `php -l` on all 2,863 FV PHP files (random_compat excluded: a PHP 5 polyfill that loads nothing on 7+) | clean | 2 deprecations, both in `Shuttle.php` |
| Replay harness, full corpus | **identical to 8.2** | identical once fixes 3 and 6 below are in |
| phpab class map | identical to the committed map | identical |
| Grep for functions deprecated in 8.3–8.5 | 1 hit | 3 more |
| Harness wall time, same fresh image, two runs each | 40.5 s (8.2: 41.0 s) | 41.0 s |

**Why deprecations matter more in FV than elsewhere.** `global.php:23` and `Manager.php:5` turn every
diagnostic, E_DEPRECATED included, into an ErrorException; Manager's handler ignores
`error_reporting()` altogether. A deprecation that is a log line in most PHP apps is a failed request
here. That includes compile-time ones: under 8.5, a `case 'x';` throws out of the `require`.

### 2.3 Code changes

| # | Where | Change | Status |
|---|---|---|---|
| 1 | `source/public/pollStats.php:36` | `str_getcsv($line)` → `str_getcsv($line, ',', '"', '\\')` — same behaviour, escape now explicit | **Live bug now:** the page throws on 8.4 once `logs/pollstats.csv` has rows |
| 2 | `source/public/client/mathlib.js:394` (+ optionally `source/server/lib/HexZone.php:63`) | the rounding fix, §2.4 | **Live bug now** |
| 3 | `source/server/model/ships/Shuttle.php:95`, `:97` | `case '…';` → `case '…':` | before an 8.5 move |
| 4 | `source/server/lib/Debug.php:75` | `->attach($e, $logid)` → `->offsetSet($e, $logid)`. On 8.5, `Debug::error` would log and then throw, so every caught-and-reported error becomes a second, uncaught one | before an 8.5 move |
| 5 | `source/server/lib/DiscordNotifier.php:242` | delete `curl_close($ch);` (a no-op since 8.0); on 8.5 it breaks turn notifications | before an 8.5 move |
| 6 | `tests/replay/*.php` — 60 calls in 20 files, including `replayHarness.php:844` and `:867` | delete `->setAccessible(true)` (a no-op since 8.1) | before an 8.5 move (test tooling only) |
| 7 | `docker/php/Dockerfile:1` | `php:8.2-fpm` → `php:8.4-fpm` | **done** |

### 2.4 The round() change — a live mismatch today
PHP 8.4 removed `round()`'s "pre-rounding". `HexZone::line()` — used by the EDF targeting corridor
(`TacGamedata.php:2628`) and by `specialWeapons.php:11795` — rounds interpolated cube coordinates with
`round()`. The client copy, `mathlib.hexLine()`, uses a `phpRound()` that deliberately copies 8.2's
pre-rounding. Live runs 8.4, so on lines through an exact hex corner the client preview and the server
already disagree.

Measured on 100,000 seeded lines:

| | agrees with the 8.2 server | agrees with the 8.4/8.5 server |
|---|---|---|
| Server on 8.4/8.5 | 106 lines differ | — |
| JS mirror today | all 100,000 | **106 differ** |
| JS mirror with the pre-round step removed | 106 differ | all 100,000 |
| Server using the explicit helper below (on 8.2, 8.4 and 8.5 alike) | 106 differ | all 100,000 |

- **A — minimum fix:** remove the pre-rounding step from `phpRound()` (one line plus its comment). This
  makes the client match live, and local now that it's on 8.4.
- **B — A plus future-proofing:** also replace the three `round()` calls in `HexZone::cubeRound()` with
  the helper below. The server's answer then no longer depends on PHP's `round()` at all, so a future PHP
  change can't silently split client and server again. It was measured identical to native 8.4
  `round()`, so it changes nothing on live today.

```php
private static function roundHalfAwayFromZero($x) {
    $f = floor($x);
    $d = $x - $f;
    if ($d > 0.5) return $f + 1;
    if ($d < 0.5) return $f;
    return $x < 0 ? $f : $f + 1;
}
```

Either way, update the comment above `phpRound()` and the Walkers memory note that points at it.

### 2.5 The next hop (8.5 or 8.6)
8.4 is supported to the end of 2028, so there's no hurry. When the time comes, do fixes 3–6 first; they
work on 8.4 too. Also budget for 8.5's new deprecation of `null` as an array key, which fires even in
`isset()`, `??` and `array_key_exists()`. With FV's handler, any request where a key happens to be null
would fail. That depends on the data: grep can't find it, and the harness only covers game logic (it
found none there). The safe route is to run the local Docker on the new version for a while before
switching live.

## 3. jQuery — up to date
`client/lib/jquery-4.0.0.min.js` and `jquery-ui-1.14.2.min.js`, self-hosted, are the current releases.
Nothing to do. Housekeeping only: `client/lib/jquery-ui-1.8.15.custom.min.js` (211 KB, from 2011) is
dead — its only mention is a stale skip entry at `scripts/bundle-legacy.js:73`.

## 4. THREE — r160 → r186

### 4.1 Findings
- All 59 symbols in `three-global-shim.src.js` still exist in r186, and the shim builds with no warnings.
  An r160 build made the same way comes out at exactly the shipped 498.6 KB, so the comparison is
  like-for-like.
- Size: 498.6 → 565.0 KB raw, 126.8 → 141.8 KB gzipped (+15 KB gzipped). That is now more than covered
  by the React build-mode saving (§5.1).
- Of the changes listed in the r161–r186 migration notes, three affect FV:
  - **r163 turns the stencil buffer off by default.** `webglScene.js:82` builds
    `WebGLRenderer({ alpha: true, antialias: true })`, and `DeploymentIcon.js:25` uses the stencil to cut
    holes in deployment zones. The holes would silently stop being cut. Fix: add `stencil: true`.
  - **r169 always generates mipmaps when `generateMipmaps` is true.** `PlainSprite.js:44` sets a
    `LinearFilter` but leaves `generateMipmaps` on, which would mean wasted work. Fix:
    `texture.generateMipmaps = false`, as `BallisticIconContainer.js:2102` already does.
  - **r163 drops WebGL 1.** Devices without WebGL2 lose the map (very few in 2026).
- Checked and clear: `updateRange`, `encoding`/`outputEncoding`, legacy lights, render targets,
  `ShaderChunk` and `onBeforeCompile` are not used at all. The custom shaders only include
  `colorspace_fragment` and `tonemapping_fragment`, which both still exist. Multiply/Subtractive
  blending is never set on a material. `ImageBitmapLoader` is used through its callback, so the r184
  change doesn't matter, and `TextureLoader.load()` still returns the texture.
- The memory note saying "don't bump THREE, the offscreen render target is r160-calibrated" refers to a
  `BloomPipeline.js` that isn't on any local branch, so it doesn't block anything today.

### 4.2 Is it worth it?
Players gain nothing visible. FV draws sprites, lines and a few shader materials in about 5 draw calls,
and three's work since r160 has gone elsewhere (WebGPU/TSL, PBR, loaders, XR). The case for doing it is
upkeep: the fix list is known and short now, and being on a maintained release means that a future
browser or driver bug costs a small bump rather than a 26-release jump made under pressure. The cost is
a visual QA pass across deployment zones, weapon FX, EW links, ballistics, the hex grid, the starfield
and replay. That is the same size whether you jump 1 release or 26, which is why chasing every release
isn't worth it either.

### 4.3 Steps
Bump `three` in `package.json` to ^0.186, make the two fixes, run `yarn build:three`, then do the visual
pass: headless before/after screenshots of a deployment phase and a replay with weapon fire, plus a look
by eye. Do it as a separate step after React, so a regression points at one change.

## 5. React

### 5.1 Build mode — FIXED 2026-10-08 (uncommitted)
`vite.config.js` defined `'process.env': {}` (a polyfill), and Vite's library mode leaves
`process.env.NODE_ENV` for the consumer to set. So every React package check in the bundle compiled to
`var MD={};MD.NODE_ENV==="production" ? production : development`, which always picked development. Live
players were running development React plus styled-components' development mode, and the bundle carried
both builds (confirmed in the file live serves).

Fix: `'process.env.NODE_ENV': JSON.stringify('production')` added to `define` in `vite.config.js`, then
the bundle was rebuilt with `vite build`.

| `UI.bundle.js` | raw | gzip |
|---|---|---|
| Before (React 18, development at runtime) | 787.1 KB | 226.7 KB |
| **After (React 18, production)** | **452.1 KB** | **123.8 KB** |
| For reference: React 19.3, production | 530.3 KB | 146.4 KB |

Verified: the bundle has no `NODE_ENV` checks or dev-only text. A headless run as real players on the
local site, with all non-GET requests blocked, behaved as follows:
- game.php mounted every React panel, opened React ship windows (Omega, G'Quan), and showed system info
  when a thruster was hovered.
- gamelobby.php loaded `UIManager`.
- There were no console errors, warnings or exceptions, and the screenshot shows normal styling.

Notes:
- Live gets this once `UI.bundle.js` and `vite.config.js` are deployed.
- `yarn watch` (`vite build --watch`) now also builds production React, so React's dev warnings no longer
  appear locally. If you want them back, add `--mode development` to the watch script and make the
  define depend on the mode — but then be careful not to commit a watch-built bundle.

### 5.2 Version 18 → 19
- 18.3.1 is the last 18.x and has had no patches since April 2024. The serious React vulnerabilities of
  the past year (CVE-2025-55182 and the follow-ups) were all in React Server Components. FV's React is
  client-only and unaffected.
- Compatibility: 55 files, 46 class components, no hooks, `createRoot` already, the automatic JSX runtime
  already, and none of React 19's removed APIs. The UI builds cleanly against 19.3 with **no code
  changes**.
- Gain: nothing direct. Everything 19 adds is for function components and hooks. The real value is
  staying on the maintained line, so that a library that later needs 19 doesn't force the upgrade at a
  bad moment.
- Cost: +23 KB gzipped over React 18 in production mode, which the build-mode fix more than pays for,
  plus the same headless smoke run used for §5.1.
- **Verdict: worth doing now** — it's about as cheap as an upgrade gets. Steps: bump react and react-dom
  to ^19.3, `vite build`, run the smoke test, then click through ship windows and the lobby by hand.

## 6. Decisions for you
1. **Round fix (§2.4), live bug:** A (JS-only) or B (A plus the server helper)?
2. **pollStats fix (§2.3 #1), live bug:** do it?
3. **React 19 now (§5.2)?** Recommended.
4. **THREE r186 after React (§4)?** Reasonable; the cost is the visual QA.
5. **Fixes 3–6** now, or when an 8.5 move comes up?

## 7. Noticed, not in scope
- Dead vendored files: `client/lib/three.min.js` (670 KB) and the jQuery UI 1.8.15 file above.
  `THREE.MeshLine.js` is still loaded by `game.php:133`, but its only user, `LineMeshSprite.js`, is
  never instantiated — and `MeshLineMaterial` would throw if anything did call it
  (`THREE.Material.call(this)` on an ES6 class).
- Local MariaDB is 10.3.39 (end of life May 2023) and nginx is 1.15. Both are local only; the live DB
  version is recorded nowhere.
- Local Node is 23.8.0, an odd-numbered release out of support since mid-2025. Node 24 LTS is the
  natural choice if the toolchain is touched.
- Vite 5.4.21 is out of support (current: 8.3.4). FV only runs `vite build`, so 2025's dev-server CVEs
  don't apply.

## 8. Reproducing the evidence
- PHP images: `fv-phpcheck:<v>` is `php:<v>-cli` plus `docker-php-ext-install mysqli`. Harness on
  another version:
  `docker run --rm --network fieryvoid_default -v C:/FV_env/FieryVoid:/usr/src/current:ro fv-phpcheck:8.5 php -d memory_limit=2048M -d date.timezone=Europe/Helsinki /usr/src/current/tests/replay/replayHarness.php check`
  (Git Bash needs `MSYS_NO_PATHCONV=1`). Its stderr carries `FV_DEBUG` lines that the fpm container
  sends to its log file; filter them before diffing.
- Lint: `find … -name '*.php' -print0 | xargs -0 -n1 -P8 sh -c 'php -l "$0"; exit 0'`. The
  `exit 0` matters — xargs aborts the whole run on PHP's exit status 255.
- Rounding: 100,000 `mt_srand(20261008)` lines, `q∈[-45,45]`, `r∈[-30,30]`, ends within ±30 of the
  start, compared hex for hex.

## Sources
- [PHP supported versions](https://www.php.net/supported-versions.php) ·
  [PHP 8.4 other changes (round())](https://www.php.net/manual/en/migration84.other-changes.php)
- [three.js migration guide](https://github.com/mrdoob/three.js/wiki/Migration-Guide)
- npm registry (`react`, `three`, `jquery`, `jquery-ui`, `vite`, `styled-components`) for versions and dates
- [CVE-2025-55182 (React Server Components)](https://osv.dev/vulnerability/CVE-2025-55182)
