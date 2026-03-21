'use strict';

/**
 * MineDeployment — handles the "Deploy Mines" mode during the Deployment Phase.
 *
 * When active, the player can click-drag a rectangle on the map canvas.
 * On release, a confirm dialog shows how many of their available mines they
 * want to place. Mines are then randomly deployed across valid hexes inside
 * the drawn area, respecting validateMineDeploymentHex() (mine zone rules).
 *
 * Called by gamedata.drawIniGUI() — button appears if areMinesPresent &&
 * player owns at least one deployable mine.
 */
window.MineDeployment = (function () {

    var _active = false;
    var _deploymentSprites = null; // Set by DeploymentPhaseStrategy when it activates

    // Drag state
    var _dragging = false;
    var _startX = 0;          // container-relative (for overlay div)
    var _startY = 0;
    var _currentX = 0;
    var _currentY = 0;
    var _startClientX = 0;    // viewport-absolute (for coordinate conversion)
    var _startClientY = 0;
    var _currentClientX = 0;
    var _currentClientY = 0;
    var _rectEl = null;

    // ─── Pointer event handlers (stored so we can removeEventListener) ───────────

    function _onPointerDown(e) {
        if (!_active) return;
        // Only handle primary pointer / left mouse button
        if (e.button !== undefined && e.button !== 0) return;

        _dragging = true;

        var pageContainer = document.getElementById('pagecontainer');
        var rect = pageContainer.getBoundingClientRect();
        // Store both: container-relative for the overlay div, and viewport-absolute for coordinate conversion
        _startX = e.clientX - rect.left;
        _startY = e.clientY - rect.top;
        _currentX = _startX;
        _currentY = _startY;
        // Viewport-absolute coords for coordinate conversion
        _startClientX = e.clientX;
        _startClientY = e.clientY;
        _currentClientX = e.clientX;
        _currentClientY = e.clientY;

        // Create the visual rectangle overlay
        if (!_rectEl) {
            _rectEl = document.createElement('div');
            _rectEl.id = 'mineDeployRect';
            pageContainer.appendChild(_rectEl);
        }
        _rectEl.style.display = 'block';
        _updateRect();

        e.preventDefault();
        e.stopPropagation();
    }

    function _onPointerMove(e) {
        if (!_active || !_dragging) return;

        var pageContainer = document.getElementById('pagecontainer');
        var rect = pageContainer.getBoundingClientRect();
        _currentX = e.clientX - rect.left;
        _currentY = e.clientY - rect.top;
        _currentClientX = e.clientX;
        _currentClientY = e.clientY;
        _updateRect();

        e.preventDefault();
    }

    function _onPointerUp(e) {
        if (!_active || !_dragging) return;
        _dragging = false;

        if (_rectEl) {
            _rectEl.style.display = 'none';
        }

        var dx = Math.abs(_currentX - _startX);
        var dy = Math.abs(_currentY - _startY);

        // Ignore accidental tiny drags (treat as clicks — don't open dialog)
        if (dx < 8 && dy < 8) return;

        _onAreaSelected();

        e.preventDefault();
        e.stopPropagation();
    }

    function _updateRect() {
        if (!_rectEl) return;
        var x = Math.min(_startX, _currentX);
        var y = Math.min(_startY, _currentY);
        var w = Math.abs(_currentX - _startX);
        var h = Math.abs(_currentY - _startY);
        _rectEl.style.left   = x + 'px';
        _rectEl.style.top    = y + 'px';
        _rectEl.style.width  = w + 'px';
        _rectEl.style.height = h + 'px';
    }

    // ─── Core logic ──────────────────────────────────────────────────────────────

    /**
     * Convert viewport-space rectangle into a list of valid mine-deployment hex positions.
     * coordinateConverter.fromViewPortToGame expects viewport coords (clientX/clientY).
     */
    function _getValidHexesInRect() {
        // Game-space corners of the drawn rectangle
        var gameTopLeft     = window.coordinateConverter.fromViewPortToGame({
            x: Math.min(_startClientX, _currentClientX),
            y: Math.min(_startClientY, _currentClientY)
        });
        var gameBottomRight = window.coordinateConverter.fromViewPortToGame({
            x: Math.max(_startClientX, _currentClientX),
            y: Math.max(_startClientY, _currentClientY)
        });

        // Note: fromViewPortToGame flips the Y axis (screen Y increases down,
        // game Y increases up), so bottom-right in screen = lowest Y in game space.
        var gameMinX = Math.min(gameTopLeft.x, gameBottomRight.x);
        var gameMaxX = Math.max(gameTopLeft.x, gameBottomRight.x);
        var gameMinY = Math.min(gameTopLeft.y, gameBottomRight.y);
        var gameMaxY = Math.max(gameTopLeft.y, gameBottomRight.y);

        var hexWidth     = window.HexagonMath.getHexWidth();
        var hexRowHeight = window.HexagonMath.getHexRowHeight();

        var validHexes = [];
        var seen = {};

        // Step through game-space points and snap each to the nearest hex
        var stepX = hexWidth  * 0.6;
        var stepY = hexRowHeight * 0.6;

        for (var gx = gameMinX; gx <= gameMaxX; gx += stepX) {
            for (var gy = gameMinY; gy <= gameMaxY; gy += stepY) {
                var hex = window.coordinateConverter.fromGameToHex({ x: gx, y: gy });
                if (!hex) continue;

                var key = hex.q + '_' + hex.r;
                if (seen[key]) continue;
                seen[key] = true;

                // Double-check hex centre is inside the game bounds
                var hexGame = window.coordinateConverter.fromHexToGame(hex);
                if (hexGame.x < gameMinX || hexGame.x > gameMaxX ||
                    hexGame.y < gameMinY || hexGame.y > gameMaxY) {
                    continue;
                }

                // Validate against mine deployment rules
                if (_deploymentSprites && window.validateMineDeploymentHex) {
                    if (!window.validateMineDeploymentHex(hex, _deploymentSprites)) continue;
                }

                validHexes.push(hex);
            }
        }

        return validHexes;
    }

    /**
     * Collect the player's own un-destroyed mines, sorted so un-deployed ones come first.
     */
    function _getPlayerMines() {
        return gamedata.ships.filter(function (ship) {
            return ship.mine &&
                   ship.userid == gamedata.thisplayer &&
                   !shipManager.isDestroyed(ship) &&
                   shipManager.getTurnDeployed(ship) <= gamedata.turn;
        }).sort(function (a, b) {
            // Prefer mines without a deploy move (not yet placed)
            var aHasDeploy = !!a.deploymove ? 1 : 0;
            var bHasDeploy = !!b.deploymove ? 1 : 0;
            return aHasDeploy - bHasDeploy;
        });
    }

    /**
     * Called when the player finishes drawing the rectangle.
     */
    function _onAreaSelected() {
        var validHexes = _getValidHexesInRect();

        if (validHexes.length === 0) {
            _showToast('No valid mine deployment hexes in drawn area.');
            return;
        }

        var mines = _getPlayerMines();

        if (mines.length === 0) {
            _showToast('You have no mines available to deploy.');
            return;
        }

        _showCountDialog(mines, validHexes);
    }

    /**
     * Show a minimal styled dialog asking how many mines to deploy.
     */
    function _showCountDialog(mines, validHexes) {
        var maxCount = mines.length;

        // Remove any existing dialog
        var existing = document.getElementById('mineDeployDialog');
        if (existing) existing.parentNode.removeChild(existing);

        var dialog = document.createElement('div');
        dialog.id = 'mineDeployDialog';

        dialog.innerHTML =
            '<div class="mine-deploy-dialog-inner">' +
            '<p class="mine-deploy-title">DEPLOY MINES</p>' +
            '<p class="mine-deploy-sub">Valid hexes in area: <strong>' + validHexes.length + '</strong></p>' +
            '<p class="mine-deploy-sub">Available mines: <strong>' + maxCount + '</strong></p>' +
            '<p class="mine-deploy-sub">How many to place?</p>' +
            '<div class="mine-deploy-controls">' +
            '<button id="mineDeployMinus" class="mine-deploy-btn-sm">&#8722;</button>' +
            '<span id="mineDeployCount" class="mine-deploy-count">' + Math.min(maxCount, validHexes.length) + '</span>' +
            '<button id="mineDeployPlus"  class="mine-deploy-btn-sm">&#43;</button>' +
            '</div>' +
            '<div class="mine-deploy-actions">' +
            '<button id="mineDeployConfirm" class="mine-deploy-btn">Confirm</button>' +
            '<button id="mineDeployCancel"  class="mine-deploy-btn mine-deploy-btn-cancel">Cancel</button>' +
            '</div>' +
            '</div>';

        document.body.appendChild(dialog);

        var countEl = document.getElementById('mineDeployCount');
        var cap = Math.min(maxCount, validHexes.length);
        var currentCount = cap;
        countEl.textContent = currentCount;

        document.getElementById('mineDeployMinus').addEventListener('click', function () {
            if (currentCount > 1) { currentCount--; countEl.textContent = currentCount; }
        });
        document.getElementById('mineDeployPlus').addEventListener('click', function () {
            if (currentCount < cap) { currentCount++; countEl.textContent = currentCount; }
        });
        document.getElementById('mineDeployCancel').addEventListener('click', function () {
            _closeDialog();
        });
        document.getElementById('mineDeployConfirm').addEventListener('click', function () {
            var n = parseInt(countEl.textContent, 10);
            _closeDialog();
            _deployMines(mines, validHexes, n);
        });
    }

    function _closeDialog() {
        var d = document.getElementById('mineDeployDialog');
        if (d) d.parentNode.removeChild(d);
    }

    /**
     * Place `count` mines randomly across `validHexes`, respecting no-stack preference.
     */
    function _deployMines(mines, validHexes, count) {
        // Shuffle validHexes (Fisher-Yates)
        var shuffled = validHexes.slice();
        for (var i = shuffled.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = shuffled[i]; shuffled[i] = shuffled[j]; shuffled[j] = tmp;
        }

        // Build list of hexes to use (avoid stacking where possible)
        var usedKeys = {};
        var hexAssignments = []; // one entry per mine to deploy

        for (var m = 0; m < count; m++) {
            var assigned = null;

            // First pass: find a hex not yet used
            for (var h = 0; h < shuffled.length; h++) {
                var key = shuffled[h].q + '_' + shuffled[h].r;
                if (!usedKeys[key]) {
                    usedKeys[key] = true;
                    assigned = shuffled[h];
                    break;
                }
            }

            // Second pass: area too small — allow stacking, pick any
            if (!assigned) {
                assigned = shuffled[m % shuffled.length];
            }

            hexAssignments.push(assigned);
        }

        // Deploy each mine; mines without an existing deploy move are used first (already sorted)
        var minesToDeploy = mines.slice(0, count);
        for (var k = 0; k < minesToDeploy.length; k++) {
            var mine = minesToDeploy[k];
            var hex  = hexAssignments[k];

            shipManager.movement.deploy(mine, hex);

            // Notify the active phase strategy so it can refresh the ship's movement UI
            var strategy = window.webglScene &&
                           window.webglScene.phaseDirector &&
                           window.webglScene.phaseDirector.phaseStrategy;
            if (strategy && strategy.onShipMovementChanged) {
                strategy.onShipMovementChanged({ ship: mine });
            }
        }

        // Only show the commit button when ALL ships are validly deployed —
        // mirrors the check in DeploymentPhaseStrategy.onHexClicked
        var strategy = window.webglScene &&
                       window.webglScene.phaseDirector &&
                       window.webglScene.phaseDirector.phaseStrategy;
        var sprites = strategy && strategy.deploymentSprites;

        if (sprites && window.validateAllDeploymentGlobal) {
            if (window.validateAllDeploymentGlobal(gamedata, sprites)) {
                gamedata.showCommitButton();
            }
        }

        _showToast(minesToDeploy.length + ' mine(s) deployed. You can still move them individually.');
    }

    /**
     * Quick non-blocking status toast.
     */
    function _showToast(msg) {
        var existing = document.getElementById('mineDeployToast');
        if (existing) existing.parentNode.removeChild(existing);

        var toast = document.createElement('div');
        toast.id = 'mineDeployToast';
        toast.textContent = msg;
        document.body.appendChild(toast);

        setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 3500);
    }

    // ─── Public API ───────────────────────────────────────────────────────────────

    /**
     * Set the deploymentSprites reference (called by DeploymentPhaseStrategy.activate).
     */
    function setDeploymentSprites(sprites) {
        _deploymentSprites = sprites;
    }

    /**
     * Toggle mine-deployment mode on/off.
     */
    function toggle() {
        if (_active) {
            deactivate();
        } else {
            activate();
        }
    }

    function activate() {
        if (_active) return;
        _active = true;

        var btn = document.getElementById('mineDeployBtn');
        if (btn) btn.classList.add('active');

        var pageContainer = document.getElementById('pagecontainer');
        if (pageContainer) {
            pageContainer.addEventListener('pointerdown', _onPointerDown, { capture: true });
            pageContainer.addEventListener('pointermove', _onPointerMove, { capture: true, passive: false });
            pageContainer.addEventListener('pointerup',   _onPointerUp,   { capture: true });
        }
    }

    function deactivate() {
        if (!_active) return;
        _active = false;
        _dragging = false;

        var btn = document.getElementById('mineDeployBtn');
        if (btn) btn.classList.remove('active');

        var pageContainer = document.getElementById('pagecontainer');
        if (pageContainer) {
            pageContainer.removeEventListener('pointerdown', _onPointerDown, { capture: true });
            pageContainer.removeEventListener('pointermove', _onPointerMove, { capture: true });
            pageContainer.removeEventListener('pointerup',   _onPointerUp,   { capture: true });
        }

        if (_rectEl) {
            _rectEl.style.display = 'none';
        }

        _closeDialog();
    }

    function isActive() {
        return _active;
    }

    return {
        activate: activate,
        deactivate: deactivate,
        toggle: toggle,
        isActive: isActive,
        setDeploymentSprites: setDeploymentSprites
    };

}());
