import * as React from "react";
import styled from "styled-components";
import { Clickable } from "../styled";

const Container = styled.div`
    display: flex;
    flex-direction: column;
    margin-top: 1px;
    width: 100%;
    min-width: 200px;
    vertical-align: center;
`;

const Header = styled.div`
    padding: 3px;
    background-color: #2b3e51;
    border: 1px solid #496791;
    color: #f2f2f2;
    text-align: center;
    font-size: 12px;
    margin-bottom: 2px;
    font-weight: bold;
`;

const ListContainer = styled.div`
    background-color: rgba(0, 0, 0, 0.9);
    border: 1px solid #496791;
    max-height: 200px;
    overflow-y: auto;
    /* Never show a horizontal scrollbar — the absolutely-positioned dragged row
       can momentarily be a hair wider than the content box. */
    overflow-x: hidden;
    display: block;
    /* Positioning context for the absolutely-positioned dragged row. */
    position: relative;

    /* Always reserve the scrollbar gutter so the box width never changes when the
       scrollbar appears/disappears — e.g. during a drag the height is pinned and
       the content momentarily fits, which would otherwise hide the scrollbar and
       widen the content (Chromium 94+, which the FV client runs on). */
    scrollbar-gutter: stable;

    /* While a row is being dragged, pin the box to its pre-drag height so the
       source-collapse / target-gap margin animations can't resize it. */
    ${props => props.$lockHeight ? `height: ${props.$lockHeight}px; max-height: ${props.$lockHeight}px;` : ''}

    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-track {
        background: #0d1620; 
    }
    &::-webkit-scrollbar-thumb {
        background: #2b3e51; 
    }
    &::-webkit-scrollbar-thumb:hover {
        background: #5a7ea8; 
    }

    @media (max-width: 768px) {
        text-align: center;         
    }    

`;

const ListItem = styled.div`
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3px 5px;
    margin-right: 3px;
    border-bottom: 1px solid #2b3e51;
    font-size: 12px;
    color: #e6e6e6;

    /* Drag-to-reorder: rows are grabbable; touch-action:none stops the touch
       gesture scrolling the list instead of dragging the row. */
    cursor: grab;
    touch-action: none;
    user-select: none;
    position: relative;

    /* A live gap opens where the dragged row will land, so the drop target is
       obvious. The gap height matches the dragged row (gapSize, inline). No
       margin transition: an animating gap makes the rows' measured centres a
       moving target for the drop-slot scan, which feels sticky/jittery while
       dragging across several rows. Snapping the gap open is crisper. */

    /* The row currently being dragged. It is pulled OUT OF FLOW (position:
       absolute, positioned imperatively — see onDragMove) so the list closes up
       behind it automatically and, crucially, gaps opening/closing elsewhere
       never shift its baseline. It floats under the pointer via translateY from
       that fixed origin. z-index lifts it above the rest. */
    ${props => props.$dragging && `
        position: absolute;
        opacity: 0.95;
        z-index: 3;
        cursor: grabbing;
        background-color: rgba(43, 62, 81, 0.92);
        pointer-events: none;
        transition: none;
    `}

    /* MIDDLE drop target: a bold glowing yellow marker line sits at the row's top
       edge WITHOUT opening a gap — so no rows relayout as the pointer crosses
       slots, which is what made the full-gap approach judder. Only the very top
       and very bottom of the list still open a real gap (below). */
    ${props => props.$lineBefore && `
        &::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: -2px;
            height: 3px;
            background-color: #c9a028;
            border-radius: 2px;
            z-index: 4;
            pointer-events: none;
        }
    `}

    /* TOP-of-list drop: open a real gap above the first row (only one row moves,
       so it stays smooth) with the same yellow marker line inside it. */
    ${props => props.$gapBefore && `
        margin-top: ${props.$gapSize}px;
        &::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: -${props.$gapSize}px;
            height: ${props.$gapSize}px;
            box-shadow: inset 0 0 0 2px #ffcc33, 0 0 6px 1px rgba(255, 204, 51, 0.5);
            background-color: rgba(255, 204, 51, 0.12);
            pointer-events: none;
        }
    `}

    /* BOTTOM-of-list drop: open a real gap below the last row. */
    ${props => props.$gapAtEnd && `
        margin-bottom: ${props.$gapSize}px;
        &::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -${props.$gapSize}px;
            height: ${props.$gapSize}px;
            box-shadow: inset 0 0 0 2px #ffcc33, 0 0 6px 1px rgba(255, 204, 51, 0.5);
            background-color: rgba(255, 204, 51, 0.12);
            pointer-events: none;
        }
    `}

    &:last-child {
        border-bottom: none;
    }

    @media (max-width: 768px) {
        flex-direction: column;
        align-items: stretch;
        margin-right: 0px;
    }
`;

const ItemInfo = styled.div`
    flex-grow: 1;
    display: flex;
    flex-direction: column;

    @media (max-width: 768px) {
        margin-bottom: 4px;
        text-align: center;          
    }
`;

const ItemName = styled.span`
    font-weight: bold;
`;

const ItemStatus = styled.span`
    font-size: 9px;
    color: #c8d5ea;
    margin-top: 2px;
    margin-right: 10px;    
    margin-left: 1px;

    @media (max-width: 768px) {
        text-align: center;
        margin-right: 0px;    
        margin-left: 0px;                  
    }

`;

const CriticalItemName = styled(ItemName)`
    color: #ffb833;
    font-weight: normal;    
`;

const DestroyedItemName = styled(ItemName)`
    color: #ff3333;
    font-weight: normal;    
`;

const ActionButtons = styled.div`
    display: flex;
    gap: 2px;

    @media (max-width: 768px) {
        justify-content: center;       
    }
`;

const ActionButton = styled.div`
    width: 18px;
    height: 18px;
    background-image: url(${props => props.img});
    background-size: cover;
    cursor: pointer;
    opacity: 0.9;
    margin-left: 3px;
    &:hover {
        opacity: 1;
    }
    
     ${Clickable}
`;

const Footer = styled.div`
    padding: 4px;
    background-color: rgba(0, 0, 0, 0.9);
    border: 1px solid #496791;
    border-top: none;
    text-align: center;
`;

const PropagateButton = styled.div`
    cursor: pointer;
    background-color: #2b3e51;
    border: 1px solid #496791;
    padding: 3px 8px;
    font-size: 12px;
    color: #f2f2f2;
    font-weight: normal;       
    display: inline-block;
    
    &:hover {
        background-color: #496791;
        color: #ffffff;
    }
`;

const Divider = styled.span`
    display: inline-block;
    width: 1px;
    height: 10px;
    background-color: #496791;
    margin: 0 4px;
    font-weight: bold;     
    vertical-align: middle;
    opacity: 0.7;
`;

const CenteredListItem = styled(ListItem)`
    justify-content: center;
    font-style: italic;
    opacity: 0.7;
`;

const InputField = styled.input`
    width: 20px;
    height: 16px;
    background: rgba(0,0,0,0.5);
    border: 1px solid #496791;
    color: #e6e6e6;
    text-align: center;
    font-size: 11px; 
    margin: 0 2px;
    
    // Hide spinner
    &::-webkit-inner-spin-button, 
    &::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    -moz-appearance: textfield;
`;

class SelfRepairList extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            priorityInputs: {},
            // Drag-to-reorder visual state (null when no drag is active).
            drag: null // { keyId, dropIdx, dy }
        };
        this.lastOrder = [];

        // Mutable, non-render drag bookkeeping (kept off state to avoid
        // re-render churn on every pointermove). Mirrors the jQuery engine's
        // `drag` object but drives a React re-render only when the drop-gap or
        // translate changes materially.
        this.dragRef = null; // { keyId, pointerId, startY, startIdx, order, started }

        // Stable bound refs so we can add/remove the same fn on `window`
        // (gotcha: move/up live on window for the drag's duration).
        this.onDragMove = this.onDragMove.bind(this);
        this.onDragEnd = this.onDragEnd.bind(this);
    }

    componentWillUnmount() {
        // Defensive: never leave window listeners attached if the window closes
        // mid-drag.
        this.removeDragListeners();
    }

    removeDragListeners() {
        window.removeEventListener('pointermove', this.onDragMove);
        window.removeEventListener('pointerup', this.onDragEnd);
        window.removeEventListener('pointercancel', this.onDragEnd);
    }

    getRepairableSystems() {
        const { ship, system } = this.props;
        const systems = [];
        const criticals = [];

        // DEBUG: Trace start of calculation
        // console.log("SelfRepairList: Calculating systems...");

        // Ensure systems is iterable (handle Object vs Array)
        const shipSystems = Array.isArray(ship.systems) ? ship.systems : Object.values(ship.systems);

        for (const sys of shipSystems) {

            if (sys.repairPriority === 0) continue; // Priority 0 cannot be repaired

            //if Structure - skip if destroyed (can't be repaired anyway)
            if ((sys.name == 'structure') && (shipManager.systems.isDestroyed(sys.ship, sys))) continue;
            //if fitted to destroyed Structure - skip (can't be repaired anyway)
            if ((sys.name != 'structure') && (sys.location != 0)) {
                var stru = shipManager.systems.getStructureSystem(sys.ship, sys.location);
                if (stru && shipManager.systems.isDestroyed(sys.ship, stru)) continue;
            }

            // Calculate effective priority for the SYSTEM
            // Start with base priority
            let basePriority = sys.repairPriority;

            // Apply override if it exists
            let isOverridden = false;
            if (system.priorityChanges && (sys.id in system.priorityChanges) && system.priorityChanges[sys.id] >= 0) {
                basePriority = system.priorityChanges[sys.id];
                isOverridden = true;
            }

            // Apply implicit +10 for destroyed systems (ONLY if not overridden)
            if (!isOverridden && shipManager.systems.isDestroyed(ship, sys) && basePriority <= 10) {
                basePriority += 10;
            }

            // --- CRITICALS ---
            if (!shipManager.systems.isDestroyed(ship, sys) && sys.criticals) {
                //Ensure criticals is iterable
                const sysCriticals = Array.isArray(sys.criticals) ? sys.criticals : Object.values(sys.criticals);

                for (const crit of sysCriticals) {

                    if (crit.repairPriority === 0) continue; // Cannot be repaired
                    if (crit.turn >= gamedata.turn) {
                        continue; // Caused this turn
                    }
                    if (crit.oneturn || (crit.turnend > 0)) {
                        continue; // Temporary or already fixed
                    }

                    // START CRITICAL PRIORITY LOGIC
                    let critPriority = crit.repairPriority || 0;
                    const compositeKey = sys.id + '-' + crit.id;
                    let critOverridden = false;

                    // Check for Critical Override
                    if (system.priorityChanges && (compositeKey in system.priorityChanges) && system.priorityChanges[compositeKey] >= 0) {
                        critPriority = system.priorityChanges[compositeKey];
                        critOverridden = true;
                        // console.log(`SelfRepairList: Found override for ${compositeKey}: ${critPriority}`);
                    } else {
                        // Standard Logic (if not overridden): Add NATIVE System Priority if < 10
                        if (critPriority < 10) {
                            critPriority += sys.repairPriority;
                        }
                    }

                    criticals.push({
                        type: 'critical',
                        sys: sys,
                        crit: crit,
                        priority: critPriority,
                        cost: crit.repairCost,
                        id: sys.id, // For stable sort,
                        subId: crit.id, // For deterministic tie-breaking
                        keyId: compositeKey // For action handlers
                    });
                }
            }


            // --- SYSTEM DAMAGE ---
            // Only damaged systems
            const damage = shipManager.systems.getTotalDamage(sys);
            if (damage > 0) {
                systems.push({
                    type: 'system',
                    sys: sys,
                    priority: basePriority,
                    damage: damage,
                    maxHealth: sys.maxhealth,
                    id: sys.id,
                    subId: 0, // Ensure System comes before Criticals (if same priority)
                    keyId: sys.id // For action handlers
                });
            }
        }

        // Concatenate first
        const allItems = [...criticals, ...systems];

        // Sort Unified List: Priority (Desc) -> Visual Stability (Previous Order) -> ID/SubID (Asc)
        allItems.sort((a, b) => {
            if (a.priority !== b.priority) return b.priority - a.priority; // Higher priority first

            // Visual Stability: Maintain relative order of items with equal priority
            if (this.lastOrder && this.lastOrder.length > 0) {
                const indexA = this.lastOrder.indexOf(a.keyId);
                const indexB = this.lastOrder.indexOf(b.keyId);

                // Only use previous order if BOTH items were previously rendered
                if (indexA !== -1 && indexB !== -1) {
                    return indexA - indexB;
                }
            }

            // Fallback / Deterministic Sort
            if (a.id !== b.id) return a.id - b.id;
            return a.subId - b.subId;
        });

        // Update lastOrder for next comparison
        this.lastOrder = allItems.map(item => item.keyId);

        return allItems;
    }

    handleInputChange(e, targetId, currentPriority) {
        const value = e.target.value;

        // Allow empty string for typing
        if (value === '') {
            this.setState(prevState => ({
                priorityInputs: {
                    ...prevState.priorityInputs,
                    [targetId]: ''
                }
            }));
            return;
        }

        const newValue = parseInt(value, 10);
        if (isNaN(newValue)) return;

        // Update state to reflect input immediately (for smooth typing)
        this.setState(prevState => ({
            priorityInputs: {
                ...prevState.priorityInputs,
                [targetId]: newValue
            }
        }));

        this.setPriority(targetId, newValue);
    }

    handleWheel(e, targetId, currentPriority) {
        e.preventDefault();
        const delta = e.deltaY < 0 ? 1 : -1;
        const newPrio = currentPriority + delta;

        if (newPrio < 1) return; // Minimum priority 1?

        this.setPriority(targetId, newPrio);
    }

    componentDidUpdate(prevProps) {
        // Re-assert the dragged row's absolute geometry after every commit. A
        // dropIdx re-render (gap opening) can reset the node's inline style; the
        // $dragging class also only takes effect via className, so on the FIRST
        // drag render the position:absolute + geometry must be (re)applied here so
        // the row doesn't snap back into flow. Kept out of React state on purpose
        // (see onDragMove) so the follow stays lag-free.
        if (this.dragRef && this.dragRef.started && this.listRef) {
            const el = this.listRef.querySelector('[data-keyid="' + this.dragRef.keyId + '"]');
            if (el) this.positionDraggedEl(el, this.dragRef, this.dragRef.dy || 0);
        }

        // Sync state with props if they change externally (e.g. from server or other components)
        // We only want to update state if the PROP value is different from the STATE value
        // AND the user is not currently editing this specific input (optional, but good for UX)

        // For simplicity, we can just clear inputs that match the prop value to save memory, 
        // or ensure they match. 
        // Let's iterate repairable systems? No, that's expensive.
        // We can just check if any priority changed. 
        // Re-calculating repairable systems in componentDidUpdate might be heavy?
        // Let's just rely on render to pass `value` effectively.

        // Actually, for a controlled input that allows temporary deviation while typing (like "1" then "0" for "10"),
        // we need local state.

        // Let's refresh our idea of "current" priorities
        // We can't easily get "all current priorities" without calling getRepairableSystems.
        // Let's try to match ShieldGeneratorList's approach of syncing in componentDidUpdate.

        const systems = this.getRepairableSystems();
        const currentInputs = this.state.priorityInputs;
        const newInputs = {};
        let changed = false;

        systems.forEach(item => {
            const id = item.keyId;
            const prio = item.priority;

            // If state doesn't match prop, and we aren't focused?
            // Or just always sync if different?
            if (currentInputs[id] !== undefined && currentInputs[id] !== prio && document.activeElement !== document.getElementById(`prio-input-${id}`)) {
                newInputs[id] = prio;
                changed = true;
            }
        });

        if (changed) {
            this.setState(prevState => ({
                priorityInputs: {
                    ...prevState.priorityInputs,
                    ...newInputs
                }
            }));
        }
    }

    handleTop(e, targetId) {
        e.stopPropagation();

        const systems = this.getRepairableSystems();
        if (systems.length === 0) return;

        const maxPrio = systems[0].priority;
        const myItem = systems.find(s => s.keyId === targetId); // Use keyId

        if (!myItem) return;

        // If I am already at the top priority, do nothing
        if (myItem.priority === maxPrio) {
            return;
        }

        let newPrio = maxPrio + 1;

        this.setPriority(targetId, newPrio);
    }

    handleUp(e, targetId, currentPriority) {
        e.stopPropagation();

        let newPrio = currentPriority + 1;

        if (newPrio !== currentPriority) {
            this.setPriority(targetId, newPrio);
        }
    }

    handleDown(e, targetId, currentPriority) {
        e.stopPropagation();

        if (currentPriority <= 1) return; // Min priority
        this.setPriority(targetId, currentPriority - 1);
    }

    handleReset(e, targetId) {
        e.stopPropagation();
        // Resetting simply means removing the override. 
        // We can pass -1 or handle it in setPriority.
        // The previous implementation looked up default priority.
        // For Criticals, default is implied. For Systems, default is on the object.
        // Simplest way: set override to -1 to trigger "unset" logic in server/client.

        this.setPriority(targetId, -1);
    }

    setPriority(targetId, newPriority) {
        const { ship, system } = this.props;

        system.setOverride(targetId, newPriority);
        webglScene.customEvent('SystemDataChanged', { ship: ship, system: system });
    }

    // ---- Pointer-based drag-to-reorder (mouse + touch + pen) --------------
    // Adapts the DORMANT jQuery engine in confirm.js (hBlasterTransferList) to
    // React. We keep its hard-won gotchas — Pointer Events for mouse+touch+pen,
    // move/up listeners on window, NO setPointerCapture, touch-action:none on
    // rows, preventDefault on pointerdown, a ~4px move threshold, and skipping
    // drags that start on an interactive child so buttons/input still work —
    // but because React owns the DOM we only translate the grabbed row + show a
    // drop indicator, then recompute priorities on drop (React re-renders the
    // reordered list). See reference_jquery_pointer_drag_reorder memory.

    onRowPointerDown(e, keyId, startIdx, order) {
        // Primary button / touch only.
        if (e.button != null && e.button !== 0) return;
        // Don't start a drag from the action buttons or the number input — let
        // them click/type. (They also stopPropagation, but guard here too.)
        if (e.target && e.target.closest &&
            e.target.closest('input, .sr-action-button')) return;

        // Row height drives how big a gap opens at the drop target.
        const rowEl = e.currentTarget;
        const gapSize = rowEl ? rowEl.offsetHeight : 24;
        // Freeze the container to its current pixel height for the drag so the
        // source-collapse / target-gap margin animations (which don't net to zero
        // height mid-transition) can't resize the auto-sized box.
        const lockHeight = this.listRef ? this.listRef.offsetHeight : 0;
        // Capture the row's geometry so we can pull it OUT OF FLOW (absolute) for
        // the drag: pinned at its start offset + width, it follows the pointer via
        // translateY from a FIXED origin — so gaps opening/closing elsewhere can
        // never shift its baseline (which made it "detach" and snap home).
        const anchorTop = rowEl ? rowEl.offsetTop : 0;   // offset within the scroll container
        const anchorWidth = rowEl ? rowEl.offsetWidth : 0;

        this.dragRef = {
            keyId: keyId,
            pointerId: e.pointerId,
            startY: e.clientY,   // pointer Y when the row last took its baseline
            startIdx: startIdx,  // index in the visible (descending) list
            order: order,        // snapshot of the ordered items at grab time
            gapSize: gapSize,    // px of empty space to open at the drop target
            lockHeight: lockHeight, // px height to pin the container to while dragging
            anchorTop: anchorTop,   // start offsetTop for the absolute drag
            anchorWidth: anchorWidth, // start width for the absolute drag
            started: false       // true once past the move threshold
        };

        e.preventDefault(); // suppress text-selection + synthetic click chain
        window.addEventListener('pointermove', this.onDragMove);
        window.addEventListener('pointerup', this.onDragEnd);
        window.addEventListener('pointercancel', this.onDragEnd);
    }

    onDragMove(e) {
        const d = this.dragRef;
        if (!d || e.pointerId !== d.pointerId) return;

        const dy = e.clientY - d.startY;

        // Small threshold so a tap/click isn't treated as a drag.
        if (!d.started) {
            if (Math.abs(dy) < 4) return;
            d.started = true;
        }
        e.preventDefault();

        // Which slot is the pointer currently over? Compare the pointer Y against
        // each settled row's vertical centre (the dragged row is out of flow, so
        // we SKIP it). dropIdx is produced directly in "grabbed-row-removed" index
        // space, which the render mapping expects.
        //
        // Measure the SETTLED (resting) layout, not the live one. In the HYBRID
        // scheme only the TOP gap (dropIdx === 0) displaces the rows we measure:
        // its margin-top pushes ALL settled rows down by gapSize. The middle
        // marker LINE opens no gap (no displacement), and the bottom gap is below
        // everything (displaces nothing above it). So the only correction needed
        // is to subtract gapSize from every row while the top gap is open —
        // otherwise opening it would move the measurement target and flip dropIdx
        // back, the oscillation that read as juddery dragging.
        const rows = this.listRef
            ? this.listRef.querySelectorAll('[data-keyid]')
            : [];
        const topGapOpen = this.state.drag && this.state.drag.dropIdx === 0;
        const topGapOffset = topGapOpen ? d.gapSize : 0;
        let dropIdx = 0;
        let draggedEl = null;
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].getAttribute('data-keyid') === String(d.keyId)) {
                draggedEl = rows[i];            // the row we're dragging
                continue;                       // skip it in the slot scan
            }
            const r = rows[i].getBoundingClientRect();
            const restingCentre = r.top - topGapOffset + r.height / 2;
            if (e.clientY < restingCentre) break; // land above this row
            dropIdx++;
        }

        // Position the dragged row IMPERATIVELY so it tracks the pointer with zero
        // React latency (routing this through setState/render makes it visibly lag
        // on fast moves). The row is absolute (see $dragging CSS); we pin it to its
        // captured start offset/width and follow the pointer via translateY. dy +
        // geometry are stashed so componentDidUpdate can re-assert them after a
        // dropIdx re-render.
        d.dy = dy;
        if (draggedEl) this.positionDraggedEl(draggedEl, d, dy);

        const cur = this.state.drag;
        if (!cur || cur.keyId !== d.keyId || cur.dropIdx !== dropIdx) {
            this.setState({ drag: { keyId: d.keyId, startIdx: d.startIdx, dropIdx: dropIdx, gapSize: d.gapSize, lockHeight: d.lockHeight } });
        }
    }

    // Pin the absolute dragged row to its captured start geometry and follow the
    // pointer via translateY. Fixed origin => gaps opening/closing elsewhere can't
    // shift it. Used by onDragMove and re-asserted by componentDidUpdate.
    // The row's visual top (anchorTop + dy) is CLAMPED to the container's content
    // range so it can't slide up under the header (or past the bottom edge) — at
    // the extremes it snaps to sit exactly in the top/bottom gap that opens there.
    positionDraggedEl(el, d, dy) {
        let top = d.anchorTop + dy;
        const maxTop = this.listRef
            ? Math.max(0, this.listRef.scrollHeight - d.gapSize)
            : d.anchorTop + dy;
        if (top < 0) top = 0;
        else if (top > maxTop) top = maxTop;

        el.style.top = top + 'px';
        el.style.left = '0px';
        el.style.width = d.anchorWidth + 'px';
        el.style.transform = 'none';
    }

    onDragEnd(e) {
        const d = this.dragRef;
        if (!d) return;
        if (e && e.pointerId != null && e.pointerId !== d.pointerId) return;

        this.removeDragListeners();

        // Clear the imperative drag geometry off the node so nothing survives the
        // drop re-render (React doesn't manage these — we set them directly).
        if (this.listRef) {
            const draggedEl = this.listRef.querySelector('[data-keyid="' + d.keyId + '"]');
            if (draggedEl) {
                draggedEl.style.transform = '';
                draggedEl.style.top = '';
                draggedEl.style.left = '';
                draggedEl.style.width = '';
            }
        }
        this.dragRef = null;

        const dropState = this.state.drag;
        this.setState({ drag: null });

        // No real drag (a click) or dropped back in the same slot -> no writes.
        if (!d.started || !dropState) return;
        if (dropState.dropIdx === d.startIdx) return;

        this.applyDropReorder(d.order, d.keyId, dropState.dropIdx);
    }

    // Cascade-insert. The dropped row takes (priority of the row now directly
    // BELOW it) + 1 — so a 7 dropped just above a 9 becomes 10. Then we walk UP
    // the list resolving collisions: any row above whose priority isn't strictly
    // greater than the value we just assigned gets bumped +1, cascading until
    // the descending order is restored (the old 10 becomes 11, etc). Rows below
    // the drop are untouched. At the very bottom there is no row below, so the
    // dropped row goes one below the row above it (floored at 1).
    applyDropReorder(order, keyId, dropIdx) {
        const { ship, system } = this.props;

        // Build the post-drop visual order (the grabbed row moved to dropIdx).
        const fromIdx = order.findIndex(it => it.keyId === keyId);
        if (fromIdx === -1) return;
        const items = order.slice();
        const [moved] = items.splice(fromIdx, 1);
        items.splice(dropIdx, 0, moved);

        // items[dropIdx] is the moved row itself; read its neighbours, not it.
        const below = items[dropIdx + 1]; // row now directly below the drop
        const above = items[dropIdx - 1]; // row now directly above the drop

        let assign;
        if (below) {
            assign = below.priority + 1;      // land just above the row below
        } else if (above) {
            assign = Math.max(1, above.priority - 1); // dropped at the bottom
        } else {
            assign = 1;                        // only row in the list
        }

        system.setOverride(keyId, assign);

        // Walk upward, bumping each colliding row so priorities stay strictly
        // descending above the drop point.
        for (let i = dropIdx - 1; i >= 0; i--) {
            if (items[i].priority > assign) break; // already strictly above
            assign += 1;
            system.setOverride(items[i].keyId, assign);
        }

        // Single re-render / server sync for the whole cascade.
        webglScene.customEvent('SystemDataChanged', { ship: ship, system: system });
    }

    handlePropagate(e) {
        e.stopPropagation();
        const { ship, system } = this.props;

        // Propagate current system's priority changes to all other SelfRepair systems
        for (const sys of ship.systems) {
            if (sys.name === 'SelfRepair' && sys.id !== system.id) {

                // 1. Clear overrides on target that are NOT in source (or are unset in source)
                // We iterate target's priorityChanges to find what to remove
                if (sys.priorityChanges) {
                    for (const key in sys.priorityChanges) {
                        // If source doesn't have it, or source has it as -1 (though source should just not have it if -1)
                        // But let's be safe: if key not in source.priorityChanges, unset it on target
                        if (!system.priorityChanges || !(key in system.priorityChanges)) {
                            sys.setOverride(key, -1);
                        }
                    }
                }

                // 2. Copy all valid overrides from source to target
                if (system.priorityChanges) {
                    for (const key in system.priorityChanges) {
                        const prio = system.priorityChanges[key];
                        if (prio >= 0) {
                            sys.setOverride(key, prio);
                        }
                    }
                }
            }
        }
        webglScene.customEvent('SystemDataChanged', { ship: ship, system: system });
    }

    render() {
        const { ship } = this.props;
        const repairableSystems = this.getRepairableSystems();

        // Count number of SelfRepair systems
        let selfRepairCount = 0;
        const shipSystems = Array.isArray(ship.systems) ? ship.systems : Object.values(ship.systems);
        for (const sys of shipSystems) {
            if (sys.name === 'SelfRepair') selfRepairCount++;
        }

        return (
            <Container>
                <Header>
                    Manage Repair Queue
                </Header>

                <ListContainer
                    ref={el => { this.listRef = el; }}
                    $lockHeight={this.state.drag ? this.state.drag.lockHeight : 0}
                >
                    {repairableSystems.length === 0 && <CenteredListItem>No damaged systems</CenteredListItem>}
                    {repairableSystems.map((item, index) => {
                        const drag = this.state.drag;
                        const isDragging = drag && drag.keyId === item.keyId;
                        // Drop-target indicator, in "grabbed-row-removed" index
                        // space mapped back to the rendered list. HYBRID: middle
                        // drops show a yellow MARKER LINE (no gap => no per-row
                        // relayout as you cross slots => no judder); only the very
                        // TOP and very BOTTOM open a real gap (a single row moves,
                        // so it stays smooth) to disambiguate "above/below all".
                        let gapBefore = false, gapAtEnd = false, lineBefore = false;
                        if (drag && !isDragging) {
                            const settledCount = repairableSystems.length - 1; // rows minus dragged
                            const nonDraggedBefore = index > drag.startIdx ? index - 1 : index;
                            if (drag.dropIdx === 0 && nonDraggedBefore === 0) {
                                gapBefore = true;   // above everything -> top gap
                            } else if (drag.dropIdx === settledCount &&
                                       nonDraggedBefore === settledCount - 1) {
                                gapAtEnd = true;    // below everything -> bottom gap (on last SETTLED row)
                            } else if (drag.dropIdx === nonDraggedBefore) {
                                lineBefore = true;  // middle -> marker line
                            }
                        }
                        return (
                        <ListItem
                            key={`${item.type}-${item.sys.id}${item.crit ? '-' + item.crit.id : ''}`}
                            data-keyid={item.keyId}
                            $dragging={isDragging}
                            $gapBefore={gapBefore}
                            $gapAtEnd={gapAtEnd}
                            $lineBefore={lineBefore}
                            $gapSize={drag ? drag.gapSize : 0}
                            onPointerDown={(e) => this.onRowPointerDown(e, item.keyId, index, repairableSystems)}
                        >
                            <ItemInfo>
                                {item.type === 'critical' ? (
                                    <>
                                        <CriticalItemName>{item.sys.displayName} ({item.crit.description || item.crit.phpclass})</CriticalItemName>
                                        <ItemStatus>
                                            Cost: {item.cost} <Divider /> Id: {item.sys.id}
                                        </ItemStatus>
                                    </>
                                ) : (
                                    <>
                                        {shipManager.systems.isDestroyed(ship, item.sys) ? (
                                            <DestroyedItemName>{item.sys.displayName}</DestroyedItemName>
                                        ) : (
                                            <ItemName>{item.sys.displayName}</ItemName>
                                        )}
                                        <ItemStatus>
                                            HP: {shipManager.systems.getRemainingHealth(item.sys)} / {item.sys.maxhealth} <Divider /> Id: {item.sys.id}
                                        </ItemStatus>
                                    </>
                                )}
                            </ItemInfo>
                            <ActionButtons>
                                <ActionButton className="sr-action-button" title="Reset Default" onClick={(e) => this.handleReset(e, item.keyId)} img="./img/iconSRCancel.png" />
                                <ActionButton className="sr-action-button" title="Decrease Priority" onClick={(e) => this.handleDown(e, item.keyId, item.priority)} img="./img/systemicons/AAclasses/iconMinus.png" />
                                <InputField
                                    id={`prio-input-${item.keyId}`}
                                    type="number"
                                    value={this.state.priorityInputs[item.keyId] !== undefined ? this.state.priorityInputs[item.keyId] : item.priority}
                                    onChange={(e) => this.handleInputChange(e, item.keyId, item.priority)}
                                    onClick={(e) => e.stopPropagation()}
                                    onWheel={(e) => this.handleWheel(e, item.keyId, item.priority)}
                                />
                                <ActionButton className="sr-action-button" title="Increase Priority" onClick={(e) => this.handleUp(e, item.keyId, item.priority)} img="./img/systemicons/AAclasses/iconPlus.png" />
                                <ActionButton className="sr-action-button" title="Move to Top" onClick={(e) => this.handleTop(e, item.keyId)} img="./img/iconSRHigh.png" />
                            </ActionButtons>
                        </ListItem>
                        );
                    })}
                </ListContainer>
                {selfRepairCount > 1 && (
                    <Footer>
                        <PropagateButton onClick={(e) => this.handlePropagate(e)}>Set all Self Repair systems</PropagateButton>
                    </Footer>
                )}
            </Container>
        );
    }
}

export default SelfRepairList;


