/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

// The image map on the page. Everything is already in the markup — the
// shapes, the text each one opens, the list — so this only switches floors,
// filters by the search box and opens a tooltip, a popover or a modal.

const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select,textarea,iframe,[tabindex]:not([tabindex="-1"])';
const fold = (s) => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

function init(root) {
    if (root.dataset.aimReady) return;
    root.dataset.aimReady = '1';

    const t = (key, count) => (root.dataset['i18n' + key] || '').replace(':count', String(count ?? ''));
    const tabs = [...root.querySelectorAll('[data-aim-tab]')];
    const layers = [...root.querySelectorAll('[data-aim-layer]')];
    const areas = [...root.querySelectorAll('[data-aim-area]')];
    let opener = null;
    let current = null;

    // Floors: a tab list with the arrow keys, as the ARIA pattern says.
    const select = (tab, focus) => {
        tabs.forEach((other) => {
            const on = other === tab;
            other.setAttribute('aria-selected', on ? 'true' : 'false');
            other.tabIndex = on ? 0 : -1;
            document.getElementById(other.getAttribute('aria-controls')).hidden = !on;
        });
        if (focus) tab.focus();
        closeAll();
    };
    if (tabs.length) {
        select(tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') || tabs[0], false);
        tabs.forEach((tab, i) => {
            tab.addEventListener('click', () => select(tab, false));
            tab.addEventListener('keydown', (e) => {
                const next = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
                if (next === undefined) return;
                e.preventDefault();
                select(tabs[(next + tabs.length) % tabs.length], true);
            });
        });
    }
    const layerOf = (el) => el.closest('[data-aim-layer]');
    const showLayerOf = (el) => {
        const tab = tabs.find((tb) => tb.getAttribute('aria-controls') === layerOf(el)?.id);
        if (tab && tab.getAttribute('aria-selected') !== 'true') select(tab, false);
    };

    // Search: the areas that do not match fade, the matches get a dark ring,
    // and each floor says how many it has.
    const search = root.querySelector('[data-aim-search]');
    const found = root.querySelector('[data-aim-found]');
    const items = [...root.querySelectorAll('[data-aim-item]')];
    search?.addEventListener('input', () => {
        const q = fold(search.value);
        let total = 0;
        const perLayer = new Map();
        areas.forEach((area) => {
            const hit = q !== '' && fold(area.dataset.search).includes(q);
            area.classList.toggle('aim-hit', hit);
            area.classList.toggle('aim-dim', q !== '' && !hit);
            if (hit) {
                total++;
                perLayer.set(layerOf(area), (perLayer.get(layerOf(area)) || 0) + 1);
            }
        });
        items.forEach((item) => { item.hidden = q !== '' && !fold(item.dataset.search).includes(q); });
        tabs.forEach((tab) => {
            const count = tab.querySelector('[data-aim-count]');
            const n = perLayer.get(document.getElementById(tab.getAttribute('aria-controls'))) || 0;
            count.hidden = q === '';
            count.textContent = String(n);
        });
        found.textContent = q === '' ? '' : (total ? t('Found', total) : t('None'));
        // Show the floor of the first match when the open one has none.
        const open = layers.find((layer) => !layer.hidden);
        if (total && open && !perLayer.get(open)) {
            showLayerOf(areas.find((area) => area.classList.contains('aim-hit')));
        }
    });

    const content = (id) => document.getElementById(id + '-content')?.content.cloneNode(true);

    // Tooltip: shown on hover and focus, a tap toggles it on a phone.
    const tip = document.createElement('div');
    tip.className = 'aim-tip';
    tip.id = root.id + '-tip';
    tip.setAttribute('role', 'tooltip');
    tip.hidden = true;

    // Popover: a non-modal dialog next to the area, a sheet at the bottom on a phone.
    const pop = document.createElement('div');
    pop.className = 'aim-pop';
    pop.setAttribute('role', 'dialog');
    pop.tabIndex = -1;
    pop.hidden = true;

    // Modal: the native dialog, with Tab kept inside it.
    const modal = document.createElement('dialog');
    modal.className = 'aim-modal';
    root.append(modal);

    const closeButton = () => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'aim-close';
        b.setAttribute('aria-label', t('Close'));
        b.innerHTML = '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 3l10 10M13 3L3 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        return b;
    };
    const fill = (box, area) => {
        box.replaceChildren(closeButton(), content(area.id));
        const title = box.querySelector('[data-aim-title]');
        title.id = area.id + '-title';
        box.setAttribute('aria-labelledby', title.id);
        box.querySelector('.aim-close').addEventListener('click', () => closeAll(true));
    };

    function closeAll(restore) {
        tip.hidden = true;
        areas.forEach((a) => { a.classList.remove('aim-active'); a.removeAttribute('aria-describedby'); a.removeAttribute('aria-expanded'); });
        pop.hidden = true;
        if (modal.open) modal.close();
        if (restore && opener?.isConnected) opener.focus();
        current = null;
    }

    const showTip = (area) => {
        const box = content(area.id);
        tip.replaceChildren(...[...box.querySelectorAll('[data-aim-title],.aim-content-status,.aim-content-desc')].map((n) => {
            const p = document.createElement('p');
            p.textContent = n.textContent;
            p.className = n === box.querySelector('[data-aim-title]') ? 'aim-tip-title' : '';
            return p;
        }));
        area.closest('.aim-stage').append(tip);
        tip.hidden = false;
        place(tip, area);
        area.setAttribute('aria-describedby', tip.id);
        current = area;
    };

    // Below the area when it fits, above it otherwise, kept inside the picture.
    const place = (box, area) => {
        const stage = area.closest('.aim-stage').getBoundingClientRect();
        const r = area.getBoundingClientRect();
        box.style.left = box.style.top = '0px';
        const w = box.offsetWidth;
        const h = box.offsetHeight;
        const gap = 8;
        let top = r.bottom - stage.top + gap;
        if (top + h > stage.height && r.top - stage.top - gap - h >= 0) top = r.top - stage.top - gap - h;
        const left = Math.min(Math.max(0, r.left - stage.left + r.width / 2 - w / 2), Math.max(0, stage.width - w));
        box.style.left = left + 'px';
        box.style.top = Math.max(0, top) + 'px';
    };

    const open = (area, from, forceModal) => {
        const action = forceModal ? 'modal' : area.dataset.action;
        if (current === area && action !== 'modal' && !(action === 'tooltip' ? tip.hidden : pop.hidden)) {
            closeAll(true);
            return;
        }
        closeAll(false);
        opener = from;
        if (action === 'tooltip') {
            showTip(area);
            return;
        }
        area.classList.add('aim-active');
        current = area;
        if (action === 'popover') {
            fill(pop, area);
            area.closest('.aim-stage').append(pop);
            pop.hidden = false;
            area.setAttribute('aria-expanded', 'true');
            if (!matchMedia('(max-width: 480px)').matches) place(pop, area);
            pop.focus();
        } else {
            fill(modal, area);
            modal.showModal();
            modal.querySelector('.aim-close').focus();
        }
    };

    areas.forEach((area) => {
        if (area.dataset.action === 'link') return;
        area.addEventListener('click', (e) => {
            e.stopPropagation();
            // A mouse already shows the tooltip on hover; a click must not hide it.
            if (area.dataset.action === 'tooltip' && e.pointerType === 'mouse') return;
            open(area, area);
        });
        area.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                open(area, area);
            }
        });
        if (area.dataset.action === 'tooltip') {
            area.addEventListener('pointerenter', (e) => { if (e.pointerType === 'mouse') showTip(area); });
            area.addEventListener('pointerleave', (e) => { if (e.pointerType === 'mouse' && current === area) closeAll(false); });
            area.addEventListener('focus', () => showTip(area));
            area.addEventListener('blur', () => { if (current === area) closeAll(false); });
        }
    });

    // The list opens the same text in the modal: the way in without the picture.
    root.querySelectorAll('[data-aim-open]').forEach((button) => {
        button.addEventListener('click', () => open(document.getElementById(button.dataset.aimOpen), button, true));
    });

    document.addEventListener('click', (e) => {
        if (!pop.hidden && !pop.contains(e.target)) closeAll(false);
        else if (!tip.hidden && current && !current.contains(e.target)) closeAll(false);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && (!tip.hidden || !pop.hidden)) closeAll(true);
    });

    modal.addEventListener('close', () => {
        areas.forEach((a) => a.classList.remove('aim-active'));
        current = null;
        if (opener?.isConnected) opener.focus();
    });
    modal.addEventListener('click', (e) => {
        if (e.target !== modal) return;
        const r = modal.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) modal.close();
    });
    modal.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab') return;
        const list = [...modal.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null || el === document.activeElement);
        if (!list.length) return;
        const first = list[0];
        const last = list[list.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });
}

const start = () => document.querySelectorAll('[data-aim]').forEach(init);
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
else start();

export { init };
