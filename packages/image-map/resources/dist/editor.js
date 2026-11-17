/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

// The drawing page of a layer: rectangles, polygons, circles and points on
// the picture, in the picture's own pixels. Nothing is sent until Save; then
// the whole layer goes in one request and comes back with the new ids.

const NS = 'http://www.w3.org/2000/svg';
const SNAP_PX = 8;
const HANDLE_PX = 6;

const root = document.getElementById('ed');
const S = JSON.parse(document.getElementById('ed-state').textContent);
const T = S.t;
const W = S.layer.width;
const H = S.layer.height;
const $ = (sel) => root.querySelector(sel);
const canvas = $('[data-canvas]');
const svg = $('[data-svg]');

let keySeq = 0;
const withKey = (a) => ({ ...a, key: 'k' + ++keySeq });

let areas = S.areas.map(withKey);
let deleted = [];
let selected = null;
let tool = 'select';
let snap = true;
let grid = false;
let draft = null;
let errors = {};
let saving = false;

const view = { k: 1, x: 0, y: 0 };
const past = [];
const future = [];
let saved = snapshot();

// ---- state and history -------------------------------------------------

function snapshot() {
    return JSON.stringify({ areas: areas.map(({ key, ...a }) => a), deleted });
}
function restore(json) {
    const s = JSON.parse(json);
    areas = s.areas.map(withKey);
    deleted = s.deleted;
    selected = null;
}
function commit(before) {
    if (before === snapshot()) return;
    past.push(before);
    if (past.length > 200) past.shift();
    future.length = 0;
    errors = {};
    update();
}
function change(fn) {
    const before = snapshot();
    fn();
    round();
    commit(before);
}
function undo() {
    if (!past.length) return;
    future.push(snapshot());
    restore(past.pop());
    update();
}
function redo() {
    if (!future.length) return;
    past.push(snapshot());
    restore(future.pop());
    update();
}
const dirty = () => snapshot() !== saved;
const editable = (a) => (a.id ? S.can.update : S.can.create);
const find = (key) => areas.find((a) => a.key === key) || null;
const r1 = (n) => Math.round(n * 10) / 10;
const clampX = (x) => Math.min(W, Math.max(0, x));
const clampY = (y) => Math.min(H, Math.max(0, y));

function round() {
    areas.forEach((a) => {
        const g = a.geometry;
        for (const k of ['x', 'y', 'width', 'height', 'cx', 'cy', 'r']) if (k in g) g[k] = r1(g[k]);
        if (g.points) g.points = g.points.map(([x, y]) => [r1(x), r1(y)]);
    });
}

// ---- geometry helpers ----------------------------------------------------

function vertices(a) {
    const g = a.geometry;
    switch (a.shape) {
        case 'rect': return [[g.x, g.y], [g.x + g.width, g.y], [g.x + g.width, g.y + g.height], [g.x, g.y + g.height]];
        case 'polygon': return g.points;
        case 'circle': return [[g.cx, g.cy]];
        default: return [[g.x, g.y]];
    }
}
function center(a) {
    const v = vertices(a);
    return [v.reduce((s, p) => s + p[0], 0) / v.length, v.reduce((s, p) => s + p[1], 0) / v.length];
}
function translate(a, dx, dy) {
    const g = a.geometry;
    const box = bounds(a);
    dx = Math.min(W - box.x2, Math.max(-box.x1, dx));
    dy = Math.min(H - box.y2, Math.max(-box.y1, dy));
    if ('x' in g) { g.x += dx; g.y += dy; }
    if ('cx' in g) { g.cx += dx; g.cy += dy; }
    if (g.points) g.points = g.points.map(([x, y]) => [x + dx, y + dy]);
}
function bounds(a) {
    const g = a.geometry;
    if (a.shape === 'circle') return { x1: g.cx - g.r, y1: g.cy - g.r, x2: g.cx + g.r, y2: g.cy + g.r };
    const v = vertices(a);
    const xs = v.map((p) => p[0]);
    const ys = v.map((p) => p[1]);
    return { x1: Math.min(...xs), y1: Math.min(...ys), x2: Math.max(...xs), y2: Math.max(...ys) };
}

// Snap to the corners of the other areas and to the picture's edges, then
// to the grid when it is on. The distance is in screen pixels.
function snapPoint(x, y, skipKey, e) {
    x = clampX(x);
    y = clampY(y);
    if (!snap || e?.altKey) return [x, y];
    const reach = SNAP_PX / view.k;
    let best = null;
    let bestD = reach;
    areas.forEach((a) => {
        if (a.key === skipKey) return;
        vertices(a).forEach(([vx, vy]) => {
            const d = Math.hypot(vx - x, vy - y);
            if (d < bestD) { bestD = d; best = [vx, vy]; }
        });
    });
    if (best) return best;
    if (x < reach) x = 0; else if (W - x < reach) x = W;
    if (y < reach) y = 0; else if (H - y < reach) y = H;
    if (grid) {
        const step = gridStep();
        x = clampX(Math.round(x / step) * step);
        y = clampY(Math.round(y / step) * step);
    }
    return [x, y];
}
const gridStep = () => Math.max(1, Math.round(Math.max(W, H) / 100));

// ---- view: zoom and pan --------------------------------------------------

function fit() {
    const r = canvas.getBoundingClientRect();
    view.k = Math.min(r.width / W, r.height / H) * 0.94;
    view.x = (r.width - W * view.k) / 2;
    view.y = (r.height - H * view.k) / 2;
    render();
}
function zoomAt(factor, cx, cy) {
    const r = canvas.getBoundingClientRect();
    const fitK = Math.min(r.width / W, r.height / H);
    const k = Math.min(Math.max(view.k * factor, fitK * 0.25), Math.max(8, fitK * 16));
    cx ??= r.width / 2;
    cy ??= r.height / 2;
    view.x = cx - (cx - view.x) * (k / view.k);
    view.y = cy - (cy - view.y) * (k / view.k);
    view.k = k;
    render();
}
function toImage(e) {
    const r = canvas.getBoundingClientRect();
    return [(e.clientX - r.left - view.x) / view.k, (e.clientY - r.top - view.y) / view.k];
}

// ---- drawing -------------------------------------------------------------

function el(tag, attrs, parent) {
    const node = document.createElementNS(NS, tag);
    for (const [k, v] of Object.entries(attrs)) if (v !== null && v !== undefined) node.setAttribute(k, v);
    parent?.append(node);
    return node;
}
const statusOf = (a) => S.statuses.find((s) => s.id === a.status_id) || null;
const DASH = { dashed: '9 5', dotted: '0.1 6' };

function shapeNode(a, parent, attrs) {
    const g = a.geometry;
    switch (a.shape) {
        case 'rect': return el('rect', { x: g.x, y: g.y, width: g.width, height: g.height, ...attrs }, parent);
        case 'circle': return el('circle', { cx: g.cx, cy: g.cy, r: g.r, ...attrs }, parent);
        case 'point': return el('circle', { cx: g.x, cy: g.y, r: Math.max(8, W / 90), ...attrs }, parent);
        default: return el('polygon', { points: g.points.map((p) => p.join(',')).join(' '), ...attrs }, parent);
    }
}

function render() {
    const r = canvas.getBoundingClientRect();
    svg.setAttribute('width', r.width);
    svg.setAttribute('height', r.height);
    svg.replaceChildren();
    const world = el('g', { transform: `translate(${view.x} ${view.y}) scale(${view.k})` }, svg);
    el('rect', { x: 0, y: 0, width: W, height: H, fill: '#fff' }, world);
    if (S.layer.image) el('image', { href: S.layer.image, x: 0, y: 0, width: W, height: H, preserveAspectRatio: 'none' }, world);
    if (grid) {
        const step = gridStep();
        const defs = el('defs', {}, world);
        const p = el('pattern', { id: 'ed-grid', width: step, height: step, patternUnits: 'userSpaceOnUse' }, defs);
        el('path', { d: `M${step} 0H0V${step}`, fill: 'none', stroke: '#0072b2', 'stroke-opacity': 0.35, 'stroke-width': 1, 'vector-effect': 'non-scaling-stroke' }, p);
        el('rect', { x: 0, y: 0, width: W, height: H, fill: 'url(#ed-grid)', 'pointer-events': 'none' }, world);
    }

    areas.forEach((a) => {
        const st = statusOf(a);
        const color = st?.color || '#0072b2';
        const isSel = a.key === selected;
        const group = el('g', { 'data-key': a.key, class: 'ed-area' + (isSel ? ' is-selected' : '') + (errors[a.key] ? ' is-error' : '') }, world);
        shapeNode(a, group, { class: 'ed-halo' });
        shapeNode(a, group, {
            class: 'ed-shape',
            fill: color,
            'fill-opacity': isSel ? 0.32 : 0.2,
            stroke: isSel ? '#111' : (errors[a.key] ? '#b3261e' : color),
            'stroke-width': isSel ? 3 : (st?.outline === 'thick' ? 4 : 2),
            'stroke-dasharray': isSel ? null : DASH[st?.outline],
            'stroke-linecap': st?.outline === 'dotted' ? 'round' : null,
        });
        const [cx, cy] = a.shape === 'polygon' ? labelPoint(a) : center(a);
        const label = el('text', { x: cx, y: cy, class: 'ed-label', 'font-size': 13 / view.k, 'stroke-width': 3 / view.k }, group);
        label.textContent = a.title;
    });

    const sel = find(selected);
    if (sel && editable(sel)) handles(sel, world);
    if (draft) drawDraft(world);
    $('[data-zoom]').textContent = Math.round(view.k * 100) + '%';
}

function labelPoint(a) {
    const b = bounds(a);
    return [(b.x1 + b.x2) / 2, (b.y1 + b.y2) / 2];
}

function handles(a, world) {
    const g = el('g', { class: 'ed-handles' }, world);
    const size = HANDLE_PX / view.k;
    const v = a.shape === 'circle' ? [[a.geometry.cx + a.geometry.r, a.geometry.cy]] : a.shape === 'point' ? [] : vertices(a);
    if (a.shape === 'polygon') {
        v.forEach(([x1, y1], i) => {
            const [x2, y2] = v[(i + 1) % v.length];
            el('circle', { cx: (x1 + x2) / 2, cy: (y1 + y2) / 2, r: size * 0.7, class: 'ed-mid', 'data-mid': i }, g);
        });
    }
    v.forEach(([x, y], i) => el('rect', { x: x - size, y: y - size, width: size * 2, height: size * 2, class: 'ed-handle', 'data-handle': i }, g));
}

function drawDraft(world) {
    const attrs = { class: 'ed-draft', fill: '#0072b2', 'fill-opacity': 0.15, stroke: '#111', 'stroke-width': 2, 'stroke-dasharray': '6 4' };
    if (draft.shape === 'polygon') {
        const pts = [...draft.points, draft.cursor].filter(Boolean);
        el('polyline', { ...attrs, points: pts.map((p) => p.join(',')).join(' ') }, world);
        draft.points.forEach(([x, y], i) => el('circle', {
            cx: x, cy: y, r: (i === 0 ? 7 : 4) / view.k, class: i === 0 ? 'ed-first' : 'ed-vertex',
        }, world));
    } else if (draft.geometry) {
        shapeNode({ shape: draft.shape, geometry: draft.geometry }, world, attrs);
    }
}

// ---- side panel ------------------------------------------------------------

const list = $('[data-list]');
const props = $('[data-props]');
const titleInput = $('[data-prop="title"]');
const statusSelect = $('[data-prop="status_id"]');
const actionSelect = $('[data-prop="action"]');

statusSelect.append(new Option(T.no_status, ''));
S.statuses.forEach((s) => statusSelect.append(new Option(s.name, String(s.id))));
Object.entries(S.actions).forEach(([value, label]) => actionSelect.append(new Option(label, value)));
$('[data-lang]').textContent = '(' + S.language + ')';

function panel() {
    list.replaceChildren(...areas.map((a, i) => {
        const li = document.createElement('li');
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'ed-item';
        b.dataset.key = a.key;
        b.setAttribute('aria-pressed', a.key === selected ? 'true' : 'false');
        const shape = document.createElement('span');
        shape.className = 'ed-item-shape';
        shape.textContent = T['tool_' + a.shape];
        b.append(document.createTextNode(a.title || '#' + (i + 1)), shape);
        if (errors[a.key]) {
            b.classList.add('is-error');
            b.setAttribute('aria-describedby', 'ed-error');
        }
        li.append(b);
        return li;
    }));
    $('[data-count]').textContent = '(' + areas.length + ')';
    $('[data-empty]').hidden = areas.length > 0;

    const a = find(selected);
    props.hidden = !a;
    if (!a) return;
    const can = editable(a);
    if (document.activeElement !== titleInput) titleInput.value = a.title;
    statusSelect.value = a.status_id ? String(a.status_id) : '';
    actionSelect.value = a.action;
    [titleInput, statusSelect, actionSelect].forEach((input) => { input.disabled = !can; });
    const details = $('[data-details]');
    details.hidden = !a.id;
    if (a.id) details.href = S.urls.edit.replace('__ID__', a.id);
    $('[data-details-later]').hidden = !!a.id;
    $('[data-cmd="delete"]').hidden = !(a.id ? S.can.delete : S.can.create);
    const error = $('[data-error]');
    error.id = 'ed-error';
    error.hidden = !errors[a.key];
    error.textContent = errors[a.key] || '';
}

list.addEventListener('click', (e) => {
    const b = e.target.closest('[data-key]');
    if (!b) return;
    select(b.dataset.key, true);
});
list.addEventListener('keydown', (e) => {
    if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
    const items = [...list.querySelectorAll('.ed-item')];
    const i = items.indexOf(document.activeElement);
    if (i < 0) return;
    e.preventDefault();
    items[(i + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length].focus();
});

let titleBefore = null;
titleInput.addEventListener('focus', () => { titleBefore = snapshot(); });
titleInput.addEventListener('input', () => {
    const a = find(selected);
    if (!a) return;
    a.title = titleInput.value;
    render();
    list.querySelector(`[data-key="${a.key}"]`).firstChild.textContent = a.title || '…';
    status();
});
titleInput.addEventListener('change', () => {
    if (titleBefore !== null) commit(titleBefore);
    titleBefore = snapshot();
});
statusSelect.addEventListener('change', () => change(() => { find(selected).status_id = statusSelect.value ? Number(statusSelect.value) : null; }));
actionSelect.addEventListener('change', () => change(() => { find(selected).action = actionSelect.value; }));

function select(key, focusItem) {
    selected = key;
    update();
    if (focusItem) list.querySelector(`[data-key="${key}"]`)?.focus();
}

// ---- toolbar ---------------------------------------------------------------

const statusLine = $('[data-status]');
const saveButton = $('[data-cmd="save"]');

function status(message) {
    if (message !== undefined) statusLine.textContent = message;
    else if (!saving) statusLine.textContent = dirty() ? T.unsaved : '';
    saveButton.disabled = saving || !dirty();
    $('[data-cmd="undo"]').disabled = !past.length;
    $('[data-cmd="redo"]').disabled = !future.length;
}

function setTool(name) {
    if (name !== 'select' && !S.can.create) return;
    tool = name;
    draft = null;
    root.querySelectorAll('[data-tool]').forEach((b) => b.setAttribute('aria-pressed', b.dataset.tool === name ? 'true' : 'false'));
    canvas.dataset.tool = name;
    hint();
    render();
}

function hint() {
    const text = tool === 'polygon' ? (draft ? T.hint_polygon_next : T.hint_polygon) : tool === 'select' ? '' : T['hint_' + tool];
    $('[data-hint]').textContent = text || '';
}

if (!S.can.create) root.querySelectorAll('[data-create]').forEach((b) => { b.hidden = true; });

root.querySelector('[data-tools]').addEventListener('click', (e) => {
    const b = e.target.closest('[data-tool]');
    if (b) setTool(b.dataset.tool);
});

const commands = {
    undo, redo,
    snap: () => { snap = !snap; $('[data-cmd="snap"]').setAttribute('aria-pressed', String(snap)); },
    grid: () => { grid = !grid; $('[data-cmd="grid"]').setAttribute('aria-pressed', String(grid)); render(); },
    'zoom-in': () => zoomAt(1.25),
    'zoom-out': () => zoomAt(0.8),
    fit,
    save,
    delete: remove,
};
root.addEventListener('click', (e) => {
    const b = e.target.closest('[data-cmd]');
    if (b && !b.disabled) commands[b.dataset.cmd]();
});

function remove() {
    const a = find(selected);
    if (!a || !(a.id ? S.can.delete : S.can.create)) return;
    change(() => {
        if (a.id) deleted.push(a.id);
        areas = areas.filter((x) => x !== a);
        selected = null;
    });
    canvas.focus();
}

function update() {
    render();
    panel();
    status();
    hint();
}

// ---- creating shapes -------------------------------------------------------

function add(shape, geometry) {
    const n = areas.length + 1;
    const area = withKey({ id: null, shape, geometry, title: T.new_area.replace(':n', n), status_id: null, action: 'popover', active: true });
    change(() => {
        areas.push(area);
        selected = area.key;
    });
    setTool('select');
    update();
}

function finishPolygon() {
    if (!draft || draft.shape !== 'polygon') return;
    const pts = draft.points;
    if (pts.length >= 3) add('polygon', { points: pts });
    else { draft = null; render(); hint(); }
}

// ---- pointer ---------------------------------------------------------------

const pointers = new Map();
let drag = null;
let spaceDown = false;

canvas.addEventListener('pointerdown', (e) => {
    canvas.focus({ preventScroll: true });
    pointers.set(e.pointerId, [e.clientX, e.clientY]);
    canvas.setPointerCapture(e.pointerId);

    if (pointers.size === 2) {
        // Two fingers: pinch to zoom and move.
        const [a, b] = [...pointers.values()];
        drag = { type: 'pinch', d: Math.hypot(a[0] - b[0], a[1] - b[1]), mid: [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2] };
        draft = tool === 'polygon' ? draft : null;
        return;
    }
    const empty = !e.target.closest('[data-key],[data-handle],[data-mid]');
    if (e.button === 1 || spaceDown || (e.button === 0 && empty && tool === 'select')) {
        drag = { type: 'pan', sx: e.clientX, sy: e.clientY, vx: view.x, vy: view.y };
        if (empty && tool === 'select' && selected && e.button === 0 && !spaceDown) { selected = null; update(); }
        return;
    }
    if (e.button !== 0) return;

    const [x, y] = toImage(e);
    const sel = find(selected);

    if (tool === 'select') {
        const handle = e.target.closest('[data-handle]');
        const mid = e.target.closest('[data-mid]');
        if (sel && editable(sel) && mid) {
            const before = snapshot();
            const i = Number(mid.dataset.mid) + 1;
            sel.geometry.points.splice(i, 0, snapPoint(x, y, sel.key, e));
            drag = { type: 'vertex', area: sel, index: i, before };
            render();
            return;
        }
        if (sel && editable(sel) && handle) {
            const i = Number(handle.dataset.handle);
            if (e.altKey && sel.shape === 'polygon') {
                if (sel.geometry.points.length > 3) change(() => sel.geometry.points.splice(i, 1));
                return;
            }
            drag = { type: 'vertex', area: sel, index: i, before: snapshot() };
            return;
        }
        const hit = e.target.closest('[data-key]');
        if (hit) {
            const a = find(hit.dataset.key);
            if (selected !== a.key) { selected = a.key; update(); }
            if (editable(a)) drag = { type: 'move', area: a, last: [x, y], before: snapshot() };
            return;
        }
        return;
    }

    const [px, py] = snapPoint(x, y, null, e);
    if (tool === 'point') {
        add('point', { x: px, y: py });
    } else if (tool === 'rect' || tool === 'circle') {
        draft = { shape: tool, start: [px, py], geometry: null };
        drag = { type: 'draw' };
    } else if (tool === 'polygon') {
        draft ??= { shape: 'polygon', points: [], cursor: null };
        const first = draft.points[0];
        if (first && draft.points.length >= 3 && Math.hypot(first[0] - x, first[1] - y) * view.k <= 10) {
            finishPolygon();
            return;
        }
        if (draft.points.length >= S.limits.maxPoints) return;
        const last = draft.points[draft.points.length - 1];
        if (!last || last[0] !== px || last[1] !== py) draft.points.push([px, py]);
        hint();
        render();
    }
});

canvas.addEventListener('pointermove', (e) => {
    if (pointers.has(e.pointerId)) pointers.set(e.pointerId, [e.clientX, e.clientY]);
    if (drag?.type === 'pinch' && pointers.size === 2) {
        const [a, b] = [...pointers.values()];
        const d = Math.hypot(a[0] - b[0], a[1] - b[1]);
        const mid = [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2];
        const r = canvas.getBoundingClientRect();
        view.x += mid[0] - drag.mid[0];
        view.y += mid[1] - drag.mid[1];
        zoomAt(d / drag.d, mid[0] - r.left, mid[1] - r.top);
        drag.d = d;
        drag.mid = mid;
        return;
    }
    const [x, y] = toImage(e);
    if (!drag) {
        if (draft?.shape === 'polygon') {
            draft.cursor = snapPoint(x, y, null, e);
            render();
        }
        return;
    }
    if (drag.type === 'pan') {
        view.x = drag.vx + e.clientX - drag.sx;
        view.y = drag.vy + e.clientY - drag.sy;
        render();
    } else if (drag.type === 'move') {
        translate(drag.area, x - drag.last[0], y - drag.last[1]);
        drag.last = [x, y];
        render();
    } else if (drag.type === 'vertex') {
        drag.index = moveVertex(drag.area, drag.index, snapPoint(x, y, drag.area.key, e));
        render();
    } else if (drag.type === 'draw') {
        const [qx, qy] = snapPoint(x, y, null, e);
        const [sx, sy] = draft.start;
        draft.geometry = draft.shape === 'rect'
            ? { x: Math.min(sx, qx), y: Math.min(sy, qy), width: Math.abs(qx - sx), height: Math.abs(qy - sy) }
            : { cx: sx, cy: sy, r: Math.min(Math.hypot(qx - sx, qy - sy), sx, sy, W - sx, H - sy) };
        render();
    }
});

function endPointer(e) {
    pointers.delete(e.pointerId);
    if (!drag) return;
    const d = drag;
    drag = null;
    if (d.type === 'move' || d.type === 'vertex') {
        round();
        commit(d.before);
        update();
    } else if (d.type === 'draw') {
        // Too small to be meant (a click, not a drag): nothing is added.
        const shape = draft?.shape;
        const g = draft?.geometry;
        const min = Math.max(S.limits.minSize, 4 / view.k);
        draft = null;
        if (g && (shape === 'rect' ? g.width >= min && g.height >= min : g.r >= min)) add(shape, g);
        else render();
    }
}
canvas.addEventListener('pointerup', endPointer);
canvas.addEventListener('pointercancel', endPointer);

canvas.addEventListener('dblclick', (e) => {
    if (tool === 'polygon') {
        e.preventDefault();
        // The second click of the double click added a point already.
        if (draft?.points.length > 3) draft.points.pop();
        finishPolygon();
    }
});

canvas.addEventListener('wheel', (e) => {
    e.preventDefault();
    const r = canvas.getBoundingClientRect();
    zoomAt(Math.exp(-e.deltaY * (e.deltaMode === 1 ? 0.05 : 0.0015)), e.clientX - r.left, e.clientY - r.top);
}, { passive: false });

// Returns the handle's number afterwards: a rectangle's corner dragged past
// the opposite one becomes another corner.
function moveVertex(a, i, [x, y]) {
    const g = a.geometry;
    if (a.shape === 'polygon') {
        g.points[i] = [x, y];
    } else if (a.shape === 'circle') {
        g.r = Math.min(Math.hypot(x - g.cx, y - g.cy), g.cx, g.cy, W - g.cx, H - g.cy);
    } else if (a.shape === 'rect') {
        const [ox, oy] = vertices(a)[(i + 2) % 4];
        Object.assign(g, { x: Math.min(x, ox), y: Math.min(y, oy), width: Math.abs(x - ox), height: Math.abs(y - oy) });
        return x > ox ? (y > oy ? 2 : 1) : (y > oy ? 3 : 0);
    }
    return i;
}

// ---- keyboard ----------------------------------------------------------------

const typing = (e) => e.target.matches('input, select, textarea');

document.addEventListener('keydown', (e) => {
    const mod = e.ctrlKey || e.metaKey;
    if (mod && e.key.toLowerCase() === 's') {
        e.preventDefault();
        save();
        return;
    }
    if (typing(e)) return;
    if (mod && e.key.toLowerCase() === 'z') {
        e.preventDefault();
        e.shiftKey ? redo() : undo();
        return;
    }
    if (mod && e.key.toLowerCase() === 'y') {
        e.preventDefault();
        redo();
        return;
    }
    if (mod || e.altKey) return;
    if (e.key === ' ' && e.target === canvas) {
        e.preventDefault();
        spaceDown = true;
        canvas.dataset.pan = '1';
        return;
    }
    if (draft?.shape === 'polygon') {
        if (e.key === 'Enter') { e.preventDefault(); finishPolygon(); return; }
        if (e.key === 'Escape') { e.preventDefault(); draft = null; render(); hint(); return; }
        if (e.key === 'Backspace') { e.preventDefault(); draft.points.pop(); if (!draft.points.length) draft = null; render(); hint(); return; }
    }
    if (e.key === 'Escape') {
        if (tool !== 'select') setTool('select');
        else if (selected) { selected = null; update(); }
        return;
    }
    const tools = { v: 'select', r: 'rect', p: 'polygon', c: 'circle', m: 'point' };
    if (tools[e.key.toLowerCase()]) { setTool(tools[e.key.toLowerCase()]); return; }
    if (e.key.toLowerCase() === 's') return commands.snap();
    if (e.key.toLowerCase() === 'g') return commands.grid();
    if (e.key === '+' || e.key === '=') return zoomAt(1.25);
    if (e.key === '-') return zoomAt(0.8);
    if (e.key === '0') return fit();
    const sel = find(selected);
    if (!sel) return;
    if ((e.key === 'Delete' || e.key === 'Backspace') && !e.target.matches('a, button:not(.ed-item)')) {
        e.preventDefault();
        remove();
        return;
    }
    const arrows = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
    if (arrows[e.key] && editable(sel) && (e.target === canvas)) {
        e.preventDefault();
        const step = e.shiftKey ? 10 : 1;
        change(() => translate(sel, arrows[e.key][0] * step, arrows[e.key][1] * step));
    }
});
document.addEventListener('keyup', (e) => {
    if (e.key === ' ') {
        spaceDown = false;
        delete canvas.dataset.pan;
    }
});

// ---- saving ------------------------------------------------------------------

function token() {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    return cookie
        ? { 'X-XSRF-TOKEN': decodeURIComponent(cookie.slice(11)) }
        : { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content };
}

async function save() {
    if (saving || !dirty()) return;
    if (draft) finishPolygon();
    saving = true;
    status(T.saving);
    const sent = areas.slice();
    try {
        const response = await fetch(S.urls.sync, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...token() },
            body: JSON.stringify({
                areas: sent.map((a) => ({ id: a.id, shape: a.shape, geometry: a.geometry, title: a.title, status_id: a.status_id, action: a.action })),
                deleted,
            }),
        });
        const body = await response.json().catch(() => ({}));
        saving = false;
        if (response.status === 422) {
            errors = {};
            Object.entries(body.errors || {}).forEach(([field, messages]) => {
                const m = field.match(/^areas\.(\d+)\./);
                if (m && sent[Number(m[1])]) errors[sent[Number(m[1])].key] = messages.join(' ');
            });
            const first = Object.keys(errors)[0];
            if (first) selected = first;
            update();
            status(T.save_errors.replace(':count', Object.keys(errors).length || 1));
            return;
        }
        if (!response.ok) {
            status(response.status === 403 ? T.forbidden : (response.status === 419 ? T.expired : T.save_failed));
            return;
        }
        const keepKey = find(selected) ? sent.indexOf(find(selected)) : -1;
        areas = body.areas.map(withKey);
        selected = keepKey >= 0 && areas[keepKey] ? areas[keepKey].key : null;
        deleted = [];
        errors = {};
        past.length = 0;
        future.length = 0;
        saved = snapshot();
        update();
        status(body.message || T.saved);
    } catch {
        saving = false;
        status(T.save_failed);
    }
}

window.addEventListener('beforeunload', (e) => {
    if (dirty()) {
        e.preventDefault();
        e.returnValue = '';
    }
});

new ResizeObserver(() => render()).observe(canvas);
requestAnimationFrame(() => { fit(); update(); });
