(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
  const root = document.documentElement;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  // Same breakpoints as the CSS "Touch layouts" block: phones + tablets use the
  // home-screen layout (apps as sheets), everything else gets the real desktop.
  const COMPACT_Q = '(max-width: 760px), (pointer: coarse) and (max-width: 1180px), (pointer: coarse) and (max-height: 520px)';
  const mobileMQ = window.matchMedia(COMPACT_Q);
  const isMobile = () => mobileMQ.matches;
  const isQuick = () => root.classList.contains('quick');
  const esc = (s) => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const DATA = window.VIKAS || {};
  const FEST = DATA.diwali || null; // set by the server during the Diwali season only

  /* ---------- Toast + copy ---------- */
  const toastEl = $('[data-toast]');
  let toastT;
  const toast = (msg) => {
    toastEl.textContent = msg;
    toastEl.classList.add('is-show');
    clearTimeout(toastT);
    toastT = setTimeout(() => toastEl.classList.remove('is-show'), 2800);
  };
  const copyText = async (v, label) => {
    try { await navigator.clipboard.writeText(v); }
    catch (e) {
      const ta = Object.assign(document.createElement('textarea'), { value: v });
      ta.style.cssText = 'position:fixed;opacity:0';
      document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
    }
    toast(label);
  };

  /* ---------- Theme ---------- */
  const metaTheme = $('meta[name="theme-color"]');
  const setTheme = (dark, save) => {
    if (dark) root.setAttribute('data-theme', 'dark'); else root.removeAttribute('data-theme');
    if (metaTheme) metaTheme.setAttribute('content', dark ? '#12161C' : '#DCE6EA');
    if (save) { try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {} }
  };
  setTheme(root.getAttribute('data-theme') === 'dark', false);
  const toggleTheme = () => setTheme(root.getAttribute('data-theme') !== 'dark', true);

  /* ---------- Clocks ---------- */
  const clock = $('[data-clock]');
  const ist = $('[data-ist]');
  const fmtLocal = new Intl.DateTimeFormat(undefined, { weekday: 'short', day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
  const fmtIST = new Intl.DateTimeFormat('en-IN', { hour: 'numeric', minute: '2-digit', timeZone: 'Asia/Kolkata' });
  const tick = () => {
    const now = new Date();
    clock.textContent = isMobile() ? new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' }).format(now) : fmtLocal.format(now).replace(',', '');
    if (ist) ist.textContent = `India time ${fmtIST.format(now)}`;
  };
  tick(); setInterval(tick, 15000);

  /* ---------- Calendar widget ---------- */
  const calGrid = $('[data-cal-grid]');
  if (calGrid) {
    const now = new Date();
    const y = now.getFullYear(), m = now.getMonth(), today = now.getDate();
    $('[data-cal-month]').textContent = now.toLocaleString(undefined, { month: 'long' });
    $('[data-cal-day]').textContent = now.toLocaleString(undefined, { weekday: 'long', day: 'numeric' });
    const first = (new Date(y, m, 1).getDay() + 6) % 7; // Monday first
    const days = new Date(y, m + 1, 0).getDate();
    let html = ['M', 'T', 'W', 'T', 'F', 'S', 'S'].map(d => `<b>${d}</b>`).join('');
    for (let i = 0; i < first; i++) html += '<span></span>';
    for (let d = 1; d <= days; d++) {
      const wd = (first + d - 1) % 7;
      html += `<span class="${d === today ? 'is-today' : wd > 4 ? 'is-weekend' : ''}">${d}</span>`;
    }
    calGrid.innerHTML = html;
  }

  /* ======================================================================
     WINDOW MANAGER
     ====================================================================== */
  const wins = $$('.win');
  const byApp = Object.fromEntries(wins.map(w => [w.dataset.app, w]));
  const dockBtns = Object.fromEntries($$('[data-dock]').map(b => [b.dataset.dock, b]));
  let zTop = 100, cascade = 0;
  // Real menu-bar height (includes any notch / status-bar safe area)
  const MB = () => (document.querySelector('[data-menubar]') || {}).offsetHeight || 34;

  const place = (w) => {
    const [x, y, ww, hh] = (w.dataset.geo || '10,10,600,500').split(',').map(Number);
    const vw = window.innerWidth, vh = window.innerHeight;
    const width = Math.min(ww, vw - 32);
    const height = Math.min(hh, vh - MB() - 120);
    let left = Math.round(vw * x / 100) + cascade * 0;
    let top = Math.round(MB() + (vh - MB()) * y / 100);
    left = clamp(left, 8, Math.max(8, vw - width - 8));
    top = clamp(top, MB() + 8, Math.max(MB() + 8, vh - height - 96));
    Object.assign(w.style, { left: left + 'px', top: top + 'px', width: width + 'px', height: height + 'px' });
    w.dataset.placed = '1';
  };

  const activeLabel = $('[data-active-app]');
  const setActive = (w) => {
    wins.forEach(x => x.classList.toggle('is-active', x === w));
    if (w) w.style.zIndex = ++zTop;
    if (activeLabel) activeLabel.textContent = w ? w.getAttribute('aria-label') : 'Finder';
  };

  const topmostOpen = () => wins.filter(w => !w.hidden).sort((a, b) => (+b.style.zIndex || 0) - (+a.style.zIndex || 0))[0];

  const syncMobileLock = () => {
    if (!isMobile() || isQuick()) { document.body.style.overflow = ''; return; }
    document.body.style.overflow = wins.some(w => !w.hidden) ? 'hidden' : '';
  };

  const open = (app, opts = {}) => {
    const w = byApp[app];
    if (!w) return;
    closeMenus();
    if (isQuick()) {
      w.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
      return;
    }
    if (w.hidden) {
      if (!w.dataset.placed && !isMobile()) place(w);
      w.hidden = false;
      w.classList.remove('is-closing');
      if (!reduceMotion) {
        // Zoom out of the icon that was clicked (desktop), like a real OS
        const src = !isMobile() && opts.from && opts.from.getBoundingClientRect();
        const zoom = src && src.width > 0;
        if (zoom) {
          const r = w.getBoundingClientRect();
          w.style.transformOrigin = `${src.left + src.width / 2 - r.left}px ${src.top + src.height / 2 - r.top}px`;
        }
        const cls = zoom ? 'is-zooming' : 'is-opening';
        w.classList.add(cls);
        setTimeout(() => { w.classList.remove(cls); w.style.transformOrigin = ''; }, 420);
      }
      const d = dockBtns[app];
      if (d) {
        d.classList.add('is-running');
        if (!reduceMotion && !opts.quiet) { d.classList.remove('is-bounce'); void d.offsetWidth; d.classList.add('is-bounce'); setTimeout(() => d.classList.remove('is-bounce'), 700); }
      }
    }
    setActive(w);
    syncMobileLock();
    if (!opts.noFocus) {
      const focusEl = app === 'terminal' ? $('[data-term-in]') : w;
      setTimeout(() => focusEl && focusEl.focus({ preventScroll: true }), 60);
    }
    if (app === 'terminal') setTimeout(() => $('[data-term-in]').focus({ preventScroll: true }), 80);
  };

  const hideWin = (w, keepRunning) => {
    if (w.hidden) return;
    const done = () => {
      w.hidden = true;
      w.classList.remove('is-closing', 'is-active');
      setActive(topmostOpen());
      syncMobileLock();
    };
    if (reduceMotion) done();
    else { w.classList.add('is-closing'); setTimeout(done, 200); }
    const d = dockBtns[w.dataset.app];
    if (d && !keepRunning) d.classList.remove('is-running');
  };

  const closeAll = () => wins.forEach(w => hideWin(w));

  // Focus on interaction
  wins.forEach(w => w.addEventListener('pointerdown', () => { if (!w.classList.contains('is-active')) setActive(w); }));

  // Dragging
  wins.forEach(w => {
    const bar = $('[data-drag]', w);
    let sx = 0, sy = 0, ox = 0, oy = 0, dragging = false;
    bar.addEventListener('pointerdown', (e) => {
      if (e.button !== 0 || isMobile() || isQuick() || w.classList.contains('is-max') || e.target.closest('button')) return;
      dragging = true;
      sx = e.clientX; sy = e.clientY; ox = w.offsetLeft; oy = w.offsetTop;
      bar.setPointerCapture(e.pointerId);
      w.classList.add('is-dragging');
    });
    bar.addEventListener('pointermove', (e) => {
      if (!dragging) return;
      const vw = window.innerWidth, vh = window.innerHeight;
      const nx = clamp(ox + e.clientX - sx, -w.offsetWidth + 120, vw - 120);
      const ny = clamp(oy + e.clientY - sy, MB(), vh - 70);
      w.style.left = nx + 'px'; w.style.top = ny + 'px';
    });
    const end = () => { dragging = false; w.classList.remove('is-dragging'); };
    bar.addEventListener('pointerup', end);
    bar.addEventListener('pointercancel', end);
    bar.addEventListener('dblclick', (e) => { if (!e.target.closest('button') && !isMobile() && !isQuick()) w.classList.toggle('is-max'); });
  });

  /* ---------- Menus ---------- */
  const menus = $$('.menu');
  const closeMenus = () => menus.forEach(m => { $('.menu__list', m).hidden = true; $('.menu__btn', m).setAttribute('aria-expanded', 'false'); });
  const openMenu = (m) => { closeMenus(); $('.menu__list', m).hidden = false; $('.menu__btn', m).setAttribute('aria-expanded', 'true'); };
  menus.forEach(m => {
    const btn = $('.menu__btn', m);
    btn.addEventListener('click', (e) => { e.stopPropagation(); $('.menu__list', m).hidden ? openMenu(m) : closeMenus(); });
    btn.addEventListener('mouseenter', () => { if (menus.some(x => !$('.menu__list', x).hidden) && $('.menu__list', m).hidden) openMenu(m); });
  });

  /* ---------- Quick view ---------- */
  const quickLabel = $('[data-quick-label]');
  const setQuick = (on) => {
    root.classList.toggle('quick', on);
    try { localStorage.setItem('quick', on ? '1' : '0'); } catch (e) {}
    if (quickLabel) quickLabel.textContent = on ? 'Desktop view' : 'Quick view';
    document.body.style.overflow = '';
    window.scrollTo(0, 0);
    if (!on && !wins.some(w => !w.hidden) && !isMobile()) open('about', { quiet: true, noFocus: true });
    syncMobileLock();
    toast(on ? 'Quick view: everything on one page' : 'Back to the desktop');
  };
  if (quickLabel && isQuick()) quickLabel.textContent = 'Desktop view';

  /* ---------- Global click handling ---------- */
  const actions = {
    quick: () => setQuick(!isQuick()),
    theme: toggleTheme,
    closeall: closeAll,
    restart: () => { closeMenus(); closeAll(); runBoot(true); },
    spotlight: () => openSpotlight(),
    wallpaper: () => {
      const order = ['valley', 'sunset', 'forest'];
      const cur = root.getAttribute('data-wp') || 'valley';
      const next = order[(order.indexOf(cur) + 1) % order.length];
      if (next === 'valley') root.removeAttribute('data-wp'); else root.setAttribute('data-wp', next);
      try { localStorage.setItem('wallpaper', next === 'valley' ? '' : next); } catch (e) {}
      toast(`Wallpaper: ${next[0].toUpperCase() + next.slice(1)}`);
    },
    trash: () => toast('Trash is empty. No missed deadlines in here.'),
  };

  document.addEventListener('click', (e) => {
    const t = e.target;
    if (!t.closest('.menu')) closeMenus();

    if (!t.closest('[data-ctx]')) closeCtx();
    const icon = t.closest('.dicon');
    if (icon) $$('.dicon').forEach(d => d.classList.toggle('is-selected', d === icon));
    else if (!t.closest('.win') && !bandMoved) $$('.dicon').forEach(d => d.classList.remove('is-selected'));

    const cp = t.closest('[data-copy]');
    if (cp) { closeMenus(); const v = cp.dataset.copy; copyText(v, v.includes('@') ? `Email copied: ${v}` : 'Link copied'); return; }

    const op = t.closest('[data-open]');
    if (op) { e.preventDefault(); open(op.dataset.open, { from: op }); return; }

    const ac = t.closest('[data-action]');
    if (ac && actions[ac.dataset.action]) { e.preventDefault(); closeMenus(); actions[ac.dataset.action](); return; }

    const w = t.closest('.win');
    if (!w) return;
    if (t.closest('[data-close]')) { hideWin(w); return; }
    if (t.closest('[data-min]')) { hideWin(w, true); return; }
    if (t.closest('[data-max]')) { w.classList.toggle('is-max'); return; }
  });
  /* ---------- Context menu (right-click on desktop) ---------- */
  const ctx = $('[data-ctx]');
  function closeCtx() { if (ctx) ctx.hidden = true; }
  const desktopEl = $('[data-desktop]');
  desktopEl.addEventListener('contextmenu', (e) => {
    if (isMobile() || isQuick() || e.target.closest('.win, .widget, input, textarea')) return;
    e.preventDefault();
    closeMenus();
    ctx.hidden = false;
    const w = ctx.offsetWidth, h = ctx.offsetHeight;
    ctx.style.left = Math.min(e.clientX, window.innerWidth - w - 8) + 'px';
    ctx.style.top = Math.min(e.clientY, window.innerHeight - h - 8) + 'px';
  });
  ctx.addEventListener('click', (e) => { if (e.target.closest('button, a')) setTimeout(closeCtx, 0); });

  /* ---------- Rubber-band selection on the desktop ---------- */
  const band = $('[data-band]');
  let bandStart = null, bandMoved = false;
  desktopEl.addEventListener('pointerdown', (e) => {
    bandMoved = false;
    if (e.button !== 0 || isMobile() || isQuick() || e.pointerType === 'touch') return;
    if (e.target.closest('.win, .dicon, .widget, .icons button, .ctx')) return;
    bandStart = { x: e.clientX, y: e.clientY };
  });
  window.addEventListener('pointermove', (e) => {
    if (!bandStart) return;
    const x = Math.min(bandStart.x, e.clientX), y = Math.min(bandStart.y, e.clientY);
    const w = Math.abs(e.clientX - bandStart.x), h = Math.abs(e.clientY - bandStart.y);
    if (w + h < 6) return;
    bandMoved = true;
    band.hidden = false;
    Object.assign(band.style, { left: x + 'px', top: y + 'px', width: w + 'px', height: h + 'px' });
    $$('.dicon').forEach(d => {
      const r = d.getBoundingClientRect();
      d.classList.toggle('is-selected', r.right > x && r.left < x + w && r.bottom > y && r.top < y + h);
    });
  });
  window.addEventListener('pointerup', () => { bandStart = null; band.hidden = true; setTimeout(() => { bandMoved = false; }, 0); });

  /* ---------- Spotlight ---------- */
  const spot = $('[data-spotlight]');
  const spotIn = $('[data-spot-in]');
  const spotItems = $$('[data-spot-list] li');
  let spotIdx = 0;
  const spotVisible = () => spotItems.filter(li => !li.hidden);
  const spotMark = (i) => { const v = spotVisible(); spotIdx = v.length ? (i + v.length) % v.length : 0; spotItems.forEach(li => li.classList.remove('is-active')); if (v[spotIdx]) { v[spotIdx].classList.add('is-active'); v[spotIdx].scrollIntoView({ block: 'nearest' }); } };
  const spotFilter = () => {
    const q = spotIn.value.trim().toLowerCase();
    spotItems.forEach(li => { li.hidden = q && !li.dataset.spotText.toLowerCase().includes(q) && !li.textContent.toLowerCase().includes(q); });
    spotMark(0);
  };
  function openSpotlight() { closeMenus(); closeCtx(); spot.hidden = false; spotIn.value = ''; spotFilter(); setTimeout(() => spotIn.focus(), 20); }
  const closeSpotlight = () => { spot.hidden = true; };
  const spotRun = (li) => {
    if (!li) return;
    closeSpotlight();
    if (li.dataset.spotOpen) open(li.dataset.spotOpen);
    else if (li.dataset.spotHref) { const a = Object.assign(document.createElement('a'), { href: li.dataset.spotHref, download: '' }); document.body.appendChild(a); a.click(); a.remove(); }
    else if (li.dataset.spotCopy) copyText(li.dataset.spotCopy, `Email copied: ${li.dataset.spotCopy}`);
    else if (li.dataset.spotAction && actions[li.dataset.spotAction]) actions[li.dataset.spotAction]();
  };
  spotIn.addEventListener('input', spotFilter);
  spotIn.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown') { e.preventDefault(); spotMark(spotIdx + 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); spotMark(spotIdx - 1); }
    else if (e.key === 'Enter') { e.preventDefault(); spotRun(spotVisible()[spotIdx]); }
  });
  spotItems.forEach(li => {
    li.addEventListener('click', () => spotRun(li));
    li.addEventListener('mousemove', () => { const i = spotVisible().indexOf(li); if (i !== spotIdx) spotMark(i); });
  });
  spot.addEventListener('click', (e) => { if (e.target === spot) closeSpotlight(); });

  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && (e.code === 'Space' || e.key === 'k' || e.key === 'K')) { e.preventDefault(); spot.hidden ? openSpotlight() : closeSpotlight(); return; }
    if (e.key !== 'Escape') return;
    if (!spot.hidden) { closeSpotlight(); return; }
    if (!ctx.hidden) { closeCtx(); return; }
    if (menus.some(m => !$('.menu__list', m).hidden)) { closeMenus(); return; }
    if (isQuick()) return;
    const w = topmostOpen();
    if (w) hideWin(w);
  });

  /* ---------- Dock magnification ---------- */
  const dock = $('.dock');
  if (dock && finePointer && !reduceMotion) {
    const items = $$('.dock__app', dock);
    dock.addEventListener('mousemove', (e) => {
      if (isMobile()) return;
      items.forEach(it => {
        const ic = $('.appicon', it);
        const r = it.getBoundingClientRect();
        const d = Math.abs(e.clientX - (r.left + r.width / 2));
        const s = 1 + Math.max(0, 1 - d / 130) * .5;
        ic.style.transform = `scale(${s.toFixed(3)})`;
        ic.style.margin = `0 ${((s - 1) * 22).toFixed(1)}px`;
      });
    });
    dock.addEventListener('mouseleave', () => items.forEach(it => { const ic = $('.appicon', it); ic.style.transform = ''; ic.style.margin = ''; }));
  }

  /* ---------- Skills tabs ---------- */
  const skillTabs = $$('[data-skilltab]');
  skillTabs.forEach(b => b.addEventListener('click', () => {
    skillTabs.forEach(x => x.setAttribute('aria-selected', String(x === b)));
    $$('[data-skillpane]').forEach(p => { p.hidden = p.dataset.skillpane !== b.dataset.skilltab; });
  }));

  /* ======================================================================
     TERMINAL
     ====================================================================== */
  const termOut = $('[data-term-out]');
  const termIn = $('[data-term-in]');
  const termForm = $('[data-term-form]');
  const history = [];
  let hIdx = 0;
  const print = (html, cls) => {
    const p = document.createElement('p');
    if (cls) p.className = cls;
    p.innerHTML = html;
    termOut.appendChild(p);
    const body = termOut.parentElement;
    body.scrollTop = body.scrollHeight;
  };
  const appNames = { about: 'about', results: 'results', experience: 'experience', skills: 'skills', fit: 'fit', 'fit-check': 'fit', resume: 'resume', mail: 'mail', terminal: 'terminal' };
  const commands = {
    help: () => print(
      '<b>Available commands</b>\n' +
      '  whoami        who is this?\n  about         short bio\n  experience    work history\n  skills        skills by category\n' +
      '  results       proof of impact\n  notice        availability\n  contact       email & phone\n  resume        open the resume\n' +
      '  open &lt;app&gt;    about | results | experience | skills | fit | resume | mail\n  ls            list files\n  theme         toggle dark mode\n' +
      '  date          current date\n  clear         clear the screen\n' +
      (FEST ? '  <b>diwali</b>        a festive surprise\n  fireworks     light up the sky\n' : '') +
      '  <b>sudo hire vikas</b>   ;)'),
    whoami: () => print(`<b>${esc(DATA.name)}</b>\n${esc(DATA.title)}\nStatus: <span class="ok">open to new roles</span> · ${esc(DATA.notice)}`),
    about: () => print('Digital marketer for B2B & export businesses: SEO, Google & Meta Ads,\nIndiaMART & Alibaba.com lead generation, CRM, and the landing pages that convert.\n<span class="ok">+200% organic traffic</span> for an industrial machinery manufacturer.'),
    experience: () => print((DATA.experience || []).map(x => `<span class="dim">${esc(x[0])}</span>  <b>${esc(x[1])}</b> @ ${esc(x[2])}`).join('\n')),
    skills: () => print(Object.entries(DATA.skills || {}).map(([k, v]) => `<b>${esc(k)}</b>: ${v.map(esc).join(', ')}`).join('\n')),
    results: () => print((DATA.results || []).map(r => `<span class="ok">${esc(r[0]).padEnd(16)}</span> ${esc(r[1])}`).join('\n')),
    notice: () => print(`Availability: <b>${esc(DATA.notice)}</b>\nLocation: ${esc(DATA.location)}`),
    contact: () => print(`Email:    <b>${esc(DATA.email)}</b>\nPhone:    <b>${esc(DATA.phone)}</b>\nOr type <b>open mail</b> to send a message.`),
    resume: () => { print(DATA.resume ? `Opening Resume.pdf… <span class="dim">(download: <a href="${esc(DATA.resume)}" download>${esc(DATA.resume)}</a>)</span>` : 'Opening resume…'); open('resume', { noFocus: true }); },
    ls: () => print('about.txt   results.app   experience/   skills.cfg   fit-check.app   Resume.pdf   mail.app'),
    theme: () => { toggleTheme(); print('Theme toggled.', 'dim'); },
    date: () => print(new Date().toString()),
    clear: () => { termOut.innerHTML = ''; },
    exit: () => hideWin(byApp.terminal),
    hire: () => print('<span class="warn">Permission denied.</span> Try: <b>sudo hire vikas</b>'),
  };
  commands.cv = commands.resume;
  commands.exp = commands.experience;
  commands.email = commands.contact;
  commands['cat about.txt'] = commands.about;

  const run = (raw) => {
    const cmd = raw.trim();
    print(`<span class="cmd">recruiter@vikas-os:~$</span> ${esc(cmd)}`);
    if (!cmd) return;
    const lc = cmd.toLowerCase().replace(/\s+/g, ' ');
    if (lc === 'sudo hire vikas' || lc === 'sudo hire') {
      print('[sudo] password for recruiter: ********', 'dim');
      setTimeout(() => print('<span class="ok">✓ Access granted. Excellent decision.</span>\nOpening Mail so you can say hello…'), 600);
      setTimeout(() => open('mail'), 1300);
      return;
    }
    if (lc.startsWith('hire')) { commands.hire(); return; }
    if (lc.startsWith('open ')) {
      const a = appNames[lc.slice(5).trim()];
      if (a) { print(`Opening ${esc(a)}…`, 'dim'); open(a, { noFocus: a !== 'terminal' }); }
      else print(`open: no such app: ${esc(lc.slice(5))}`, 'warn');
      return;
    }
    if (commands[lc]) { commands[lc](); return; }
    print(`command not found: ${esc(cmd)}. Type <b>help</b>.`, 'warn');
  };
  termForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const v = termIn.value;
    if (v.trim()) { history.push(v); hIdx = history.length; }
    termIn.value = '';
    run(v);
  });
  termIn.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowUp' && history.length) { e.preventDefault(); hIdx = Math.max(0, hIdx - 1); termIn.value = history[hIdx]; }
    if (e.key === 'ArrowDown' && history.length) { e.preventDefault(); hIdx = Math.min(history.length, hIdx + 1); termIn.value = history[hIdx] || ''; }
    if (e.key === 'Escape') { e.stopPropagation(); termIn.blur(); }
  });
  $('[data-term]').addEventListener('click', (e) => { if (!e.target.closest('a, button')) termIn.focus(); });

  /* ======================================================================
     DIWALI SURPRISE (the server only renders its markup in the festive season)
     ====================================================================== */
  // Fireworks on a canvas behind the windows (above them on phones, where apps are full-screen)
  const fireworks = (() => {
    const COLORS = ['#FFD166', '#FF6B6B', '#FF9F1C', '#F15BB5', '#7CF0B5', '#6EC3FF', '#C77DFF'];
    const parts = [], rockets = [];
    let cv, cx, W = 0, H = 0, raf = 0, until = 0, nextLaunch = 0;
    const size = () => {
      const dpr = Math.min(2, window.devicePixelRatio || 1);
      W = window.innerWidth; H = window.innerHeight;
      cv.width = W * dpr; cv.height = H * dpr;
      cx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    const ensure = () => {
      if (cv) return;
      const desk = $('[data-desktop]'), wl = $('[data-windows]');
      const veil = Object.assign(document.createElement('div'), { className: 'fw-veil' });
      cv = Object.assign(document.createElement('canvas'), { className: 'fw' });
      [veil, cv].forEach(el => { el.setAttribute('aria-hidden', 'true'); desk.insertBefore(el, wl); });
      cx = cv.getContext('2d');
      size();
      window.addEventListener('resize', size);
    };
    const burst = (x, y, big) => {
      const c = COLORS[Math.random() * COLORS.length | 0], n = big ? 110 : 70, ring = Math.random() < .3;
      for (let i = 0; i < n; i++) {
        const a = i / n * Math.PI * 2 + Math.random() * .2;
        const v = ring ? 4 + Math.random() * .4 : 1 + Math.random() * (big ? 5.5 : 4.2);
        parts.push({ x, y, vx: Math.cos(a) * v, vy: Math.sin(a) * v, life: 1, decay: .011 + Math.random() * .012, c: Math.random() < .15 ? '#FFF6D8' : c, r: 1.4 + Math.random() * 1.3 });
      }
    };
    const launch = (tx, ty, big) => {
      if (tx == null) { tx = W * (.12 + Math.random() * .76); ty = H * (.1 + Math.random() * .35); }
      const sx = tx + (Math.random() - .5) * 80, sy = H + 10, t = 38 + Math.random() * 14;
      rockets.push({ x: sx, y: sy, vx: (tx - sx) / t, vy: (ty - sy) / t, t, big });
    };
    const frame = (now) => {
      // Fade the previous frame (trails), then draw additively
      cx.globalCompositeOperation = 'destination-out';
      cx.fillStyle = 'rgba(0,0,0,.24)';
      cx.fillRect(0, 0, W, H);
      cx.globalCompositeOperation = 'lighter';
      if (now < until && now > nextLaunch) { launch(); if (Math.random() < .35) launch(); nextLaunch = now + 380 + Math.random() * 420; }
      cx.fillStyle = '#FFE3A3';
      for (let i = rockets.length - 1; i >= 0; i--) {
        const r = rockets[i];
        r.x += r.vx; r.y += r.vy;
        cx.globalAlpha = 1;
        cx.beginPath(); cx.arc(r.x, r.y, 2, 0, 7); cx.fill();
        if (--r.t <= 0) { burst(r.x, r.y, r.big); rockets.splice(i, 1); }
      }
      for (let i = parts.length - 1; i >= 0; i--) {
        const p = parts[i];
        p.vx *= .985; p.vy = p.vy * .985 + .045;
        p.x += p.vx; p.y += p.vy; p.life -= p.decay;
        if (p.life <= 0) { parts.splice(i, 1); continue; }
        cx.globalAlpha = p.life; cx.fillStyle = p.c;
        cx.beginPath(); cx.arc(p.x, p.y, p.r, 0, 7); cx.fill();
      }
      cx.globalAlpha = 1;
      if (now >= until) root.classList.remove('is-fw');
      if (now < until || parts.length || rockets.length) raf = requestAnimationFrame(frame);
      else { raf = 0; cx.clearRect(0, 0, W, H); }
    };
    const show = (ms) => {
      if (reduceMotion) return;
      ensure();
      until = Math.max(until, performance.now() + ms);
      root.classList.add('is-fw');
      if (!raf) { nextLaunch = 0; raf = requestAnimationFrame(frame); }
    };
    // While the sky is lit, tapping the empty desktop launches a rocket right there
    document.addEventListener('pointerdown', (e) => {
      if (!raf || e.target.closest('.win, .dock, .menubar, .dicon, .widget, .spotlight, .ctx, .toast')) return;
      launch(e.clientX, e.clientY, true);
      until = Math.max(until, performance.now() + 1500);
    });
    return { show };
  })();

  let festHint = () => {};
  if (FEST && byApp.diwali) {
    const gift = $('[data-gift]');
    const card = byApp.diwali;
    let opened = false;
    try { opened = localStorage.getItem('diwali-gift') === String(FEST.year); } catch (e) {}
    if (opened && gift) gift.classList.add('is-opened');

    // Toran: string lights hanging under the menu bar (one bulb per dip of the wire)
    const toran = $('[data-toran]');
    const BULBS = ['#FFC93C', '#FF6B6B', '#7CF0B5', '#6EC3FF', '#FF9DE2'];
    const hang = () => {
      const n = Math.ceil(window.innerWidth / 56);
      if (!toran || toran.childElementCount === n) return;
      toran.innerHTML = Array.from({ length: n }, (_, i) => `<i style="left:${i * 56 + 24}px;--c:${BULBS[i % BULBS.length]};--d:${(i % 4) * .4}s"></i>`).join('');
    };
    hang();
    window.addEventListener('resize', hang);

    actions.diwali = () => {
      const first = !opened;
      opened = true;
      try { localStorage.setItem('diwali-gift', String(FEST.year)); } catch (e) {}
      if (gift) {
        gift.classList.add('is-opened', 'is-popping');
        setTimeout(() => gift.classList.remove('is-popping'), 900);
      }
      fireworks.show(first ? 6500 : 3000);
      const from = gift && gift.offsetParent ? gift : null;
      setTimeout(() => open('diwali', { from }), first && !reduceMotion ? 650 : 0);
    };
    actions.fireworks = () => {
      if (reduceMotion) toast('Happy Diwali! Fireworks stay off because your device prefers reduced motion.');
      fireworks.show(4500);
    };
    actions.sharelight = async () => {
      const url = location.origin + location.pathname;
      const text = 'Happy Diwali! Open the little Diwali surprise in Vikas OS:';
      if (navigator.share) {
        try { await navigator.share({ title: 'Happy Diwali from Vikas OS', text, url }); return; }
        catch (e) { if (e.name === 'AbortError') return; }
      }
      copyText(`${text} ${url}`, 'Link copied. Share the light!');
    };

    // Light all five diyas → grand finale
    const diyas = $$('[data-diya]', card);
    const count = $('[data-diya-count]', card);
    diyas.forEach(d => d.addEventListener('click', () => {
      if (d.classList.contains('is-lit')) return;
      d.classList.add('is-lit');
      d.setAttribute('aria-pressed', 'true');
      const lit = diyas.filter(x => x.classList.contains('is-lit')).length;
      count.textContent = lit < diyas.length ? `${lit} of ${diyas.length} diyas lit` : 'All diyas lit. Shubh Deepavali!';
      if (lit === diyas.length) {
        card.classList.add('is-glowing');
        fireworks.show(7000);
        toast('All five diyas lit! May the year ahead shine bright for you.');
      }
    }));

    commands.diwali = () => { print('<span class="warn">शुभ दीपावली · Happy Diwali!</span>\nLighting up the sky…'); actions.diwali(); };
    commands['happy diwali'] = commands.diwali;
    commands.fireworks = () => { print('Boom.', 'dim'); actions.fireworks(); };
    commands.patakhe = commands.fireworks;

    // Once per session, nudge first-time visitors toward the unopened gift
    festHint = () => {
      if (opened) return;
      let seen = false;
      try { seen = sessionStorage.getItem('diwali-hint') === '1'; sessionStorage.setItem('diwali-hint', '1'); } catch (e) {}
      if (!seen) setTimeout(() => { if (!opened) toast('A Diwali gift just landed on your desktop. Open it!'); }, 2600);
    };
  }

  /* ======================================================================
     BOOT + INITIAL STATE
     ====================================================================== */
  const bootEl = $('[data-boot]');
  const startDesktop = () => {
    const app = new URLSearchParams(location.search).get('app');
    festHint();
    if (isQuick()) return;
    if (app && byApp[app]) { open(app, { noFocus: true }); return; }
    if (!isMobile()) open('about', { quiet: true, noFocus: true });
  };
  function runBoot(force) {
    let booted = false;
    try { booted = sessionStorage.getItem('booted') === '1'; } catch (e) {}
    if (reduceMotion || (booted && !force)) { bootEl.classList.add('is-done'); startDesktop(); return; }
    bootEl.classList.remove('is-done');
    const bar = $('[data-boot-bar]');
    bar.style.width = '0';
    let p = 0;
    const step = () => {
      p = Math.min(100, p + 9 + Math.random() * 16);
      bar.style.width = p + '%';
      if (p < 100) setTimeout(step, 90);
      else setTimeout(() => { bootEl.classList.add('is-done'); try { sessionStorage.setItem('booted', '1'); } catch (e) {} startDesktop(); }, 250);
    };
    setTimeout(step, 150);
  }
  bootEl.addEventListener('click', () => { if (!bootEl.classList.contains('is-done')) { bootEl.classList.add('is-done'); startDesktop(); } });
  runBoot(false);

  // Switching layout (rotate a tablet, resize a browser window across a breakpoint)
  mobileMQ.addEventListener('change', () => {
    closeMenus(); closeCtx();
    if (!isMobile() && !isQuick()) {
      wins.forEach(w => { if (!w.hidden && !w.dataset.placed) place(w); });
      if (!wins.some(w => !w.hidden)) open('about', { quiet: true, noFocus: true });
    }
    document.body.style.overflow = '';
    syncMobileLock();
    tick();
  });

  // Tablet: tap the dimmed area around an app sheet to close it
  $('[data-windows]').addEventListener('click', (e) => {
    if (e.target === e.currentTarget && isMobile() && !isQuick()) { const w = topmostOpen(); if (w) hideWin(w); }
  });

  window.addEventListener('resize', () => {
    if (isMobile() || isQuick()) return;
    wins.forEach(w => {
      if (w.hidden || w.classList.contains('is-max')) return;
      const vw = window.innerWidth, vh = window.innerHeight;
      if (w.offsetLeft > vw - 120) w.style.left = Math.max(8, vw - w.offsetWidth - 8) + 'px';
      if (w.offsetTop > vh - 70) w.style.top = Math.max(MB() + 8, vh - w.offsetHeight - 96) + 'px';
    });
  });

  /* ---------- Job match ---------- */
  const REQS = [
    { label: 'SEO (on-page & technical)', keys: ['seo', 'search engine optimi', 'on-page', 'on page', 'technical seo', 'organic traffic', 'organic growth'], ev: '+200% organic traffic at Panchal Plastic Machinery; SEO at Arizone & DG Knitting' },
    { label: 'Keyword research', keys: ['keyword'], ev: 'Core of every SEO engagement at all three companies' },
    { label: 'Competitor & market research', keys: ['competitor', 'competitive analysis', 'market research'], ev: 'Competitor analysis at Arizone & DG Knitting; market research at Shelvin Snacks' },
    { label: 'Off-page SEO / link building', keys: ['off-page', 'off page', 'link building', 'backlink'], ev: 'Off-page SEO in core skill set' },
    { label: 'Search Console & indexing', keys: ['search console', 'indexing', 'de-index', 'deindex', 'crawl'], ev: 'Removed spam URLs from Google\'s index during the Arizone site migration' },
    { label: 'Google Ads', keys: ['google ads', 'adwords', 'ppc', 'search ads', 'paid search', 'display ads', 'pay per click', 'pay-per-click', 'sem'], ev: 'Google Ads campaigns at Arizone, DG Knitting & Panchal' },
    { label: 'Meta Ads (Facebook & Instagram)', keys: ['meta ads', 'facebook ads', 'instagram ads', 'facebook & instagram', 'facebook and instagram', 'meta business'], ev: 'Meta Ads at Arizone, DG Knitting & Panchal' },
    { label: 'Paid campaign optimisation / ROI', keys: ['roi', 'roas', 'campaign optimi', 'paid media', 'paid campaign', 'performance marketing', 'cpl', 'cost per lead'], ev: 'Campaigns managed for lead generation and ROI' },
    { label: 'Social media marketing', keys: ['social media', 'smm', 'instagram', 'facebook page'], ev: 'Social media campaigns at Arizone & Panchal' },
    { label: 'B2B portals (IndiaMART / Alibaba / TradeIndia)', keys: ['indiamart', 'india mart', 'alibaba', 'tradeindia', 'trade india', 'b2b portal', 'marketplace', 'trade portal'], ev: 'IndiaMART lead management, Alibaba.com Gold Supplier Plus, TradeIndia' },
    { label: 'Export & international buyers', keys: ['export', 'international', 'global', 'overseas', 'oem', 'odm'], ev: 'Global B2B leads and OEM/ODM listings at Arizone International' },
    { label: 'Lead generation', keys: ['lead generation', 'lead gen', 'leads', 'inquiry', 'inquiries', 'enquiry', 'enquiries', 'demand generation'], ev: 'B2B lead generation at Arizone, DG Knitting & Panchal' },
    { label: 'CRM & lead management', keys: ['crm', 'lead management', 'lead tracking', 'lead nurturing', 'pipeline'], ev: 'CRM and lead tracking at Arizone and DG Knitting' },
    { label: 'WordPress / CMS', keys: ['wordpress', 'cms'], ev: 'WordPress management and migration at Arizone' },
    { label: 'Website & landing pages', keys: ['landing page', 'html', 'css', 'javascript', 'bootstrap', 'website management', 'web development', 'website design', 'php', 'website'], ev: 'Landing pages & site redesign (HTML/CSS/JS/Bootstrap); PHP website at Arizone' },
    { label: 'Analytics & reporting', keys: ['google analytics', 'ga4', 'analytics', 'reporting', 'reports', 'kpi', 'data analysis'], ev: 'Google Analytics & Meta Business Suite reporting at Panchal' },
    { label: 'Content marketing', keys: ['content', 'blog', 'copywriting', 'copy writing', 'article'], ev: 'Blog, product page and web copy optimisation at Panchal & DG Knitting' },
    { label: 'Email marketing', keys: ['email marketing', 'newsletter', 'email campaign'], ev: 'Email marketing in core skill set' },
    { label: 'Digital marketing strategy', keys: ['strategy', 'strategies', 'marketing plan'], ev: 'Developed and executed digital strategies at Arizone & DG Knitting' },
    { label: 'Brand awareness', keys: ['brand awareness', 'branding', 'brand building'], ev: 'Brand awareness campaigns at Panchal' },
  ];
  const GAPS = [
    { label: 'HubSpot', keys: ['hubspot'] },
    { label: 'Salesforce', keys: ['salesforce'] },
    { label: 'Marketing automation', keys: ['marketing automation', 'automation tool'] },
    { label: 'Ahrefs / SEMrush / Moz', keys: ['ahrefs', 'semrush', 'moz'] },
    { label: 'LinkedIn Ads', keys: ['linkedin ads', 'linkedin advertising'] },
    { label: 'YouTube Ads', keys: ['youtube ads', 'youtube advertising'] },
    { label: 'Google Tag Manager', keys: ['tag manager', 'gtm'] },
    { label: 'Shopify / e-commerce', keys: ['shopify', 'woocommerce', 'e-commerce', 'ecommerce'] },
    { label: 'Amazon marketplace', keys: ['amazon'] },
    { label: 'Team management', keys: ['team management', 'manage a team', 'lead a team', 'people management', 'team lead', 'managing a team'] },
    { label: 'Budget ownership', keys: ['budget'] },
    { label: 'A/B testing tools', keys: ['a/b test', 'ab test', 'split test'] },
    { label: 'Graphic design tools', keys: ['photoshop', 'illustrator', 'figma', 'canva', 'graphic design'] },
    { label: 'Video editing', keys: ['video editing', 'premiere', 'after effects'] },
    { label: 'SQL / Python', keys: ['sql', 'python'] },
  ];
  const PRESETS = {
    seo: 'SEO Executive. Responsibilities: on-page and technical SEO, keyword research, competitor analysis, off-page SEO and link building, Google Search Console, Google Analytics (GA4) reporting, content optimisation for blogs and product pages, WordPress website management. Experience with Ahrefs or SEMrush preferred.',
    perf: 'Performance Marketing Executive. Plan and optimise Google Ads (search & display) and Meta Ads (Facebook & Instagram) campaigns, improve ROI and ROAS, build landing pages, track conversions in GA4 and Google Tag Manager, run A/B tests, weekly reporting. LinkedIn Ads experience is a plus.',
    b2b: 'B2B / Export Marketing Executive. Manage IndiaMART, Alibaba.com and TradeIndia accounts, product listings for OEM/ODM buyers, lead generation and inquiry handling for international and export markets, CRM and lead management, SEO for the company website, email marketing to overseas buyers.',
    dm: 'Digital Marketing Manager. Own the digital marketing strategy: SEO, Google Ads, Meta Ads, social media marketing, content marketing and email marketing. Manage the WordPress website, analytics and reporting, team management, budget ownership and marketing automation (HubSpot).',
  };

  const matcher = $('[data-matcher]');
  if (matcher) {
    const jd = $('[data-jd]', matcher);
    const result = $('[data-result]', matcher);
    const ring = $('[data-ring]', matcher);
    const CIRC = 2 * Math.PI * 52;

    const esc = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const has = (text, key) => new RegExp(`(^|[^a-z0-9])${esc(key)}([^a-z0-9]|$)`, 'i').test(text) || (key.length > 6 && text.includes(key));
    const li = (html) => { const el = document.createElement('li'); el.innerHTML = html; return el; };
    const safe = (s) => s.replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    const analyze = () => {
      const text = ' ' + jd.value.toLowerCase().replace(/\s+/g, ' ') + ' ';
      if (text.trim().length < 20) {
        jd.focus();
        toast('Paste a job description or pick a role first');
        return;
      }
      const hits = REQS.filter(r => r.keys.some(k => has(text, k)));
      const gaps = GAPS.filter(g => g.keys.some(k => has(text, k)));
      const total = hits.length + gaps.length;
      const pct = total ? Math.round(hits.length / total * 100) : 0;

      $('[data-hits]', matcher).replaceChildren(...(hits.length
        ? hits.map(h => li(`<strong>${safe(h.label)}</strong><small>${safe(h.ev)}</small>`))
        : [li('<strong>No specific requirements recognised</strong><small>Try pasting the full job description.</small>')]));
      $('[data-gaps]', matcher).replaceChildren(...(gaps.length
        ? gaps.map(g => li(`${safe(g.label)}<small>Not on my resume yet. Happy to learn and ramp up quickly.</small>`))
        : [Object.assign(li('No gaps found in this description'), { className: 'none' })]));

      const verdict = !total ? 'Not enough detail to score' : pct >= 85 ? 'Strong match' : pct >= 65 ? 'Good match' : pct >= 45 ? 'Partial match' : 'Limited match';
      $('[data-verdict]', matcher).textContent = verdict;
      $('[data-summary]', matcher).textContent = total
        ? `I recognised ${total} requirement${total === 1 ? '' : 's'} in this description. ${hits.length} match Vikas's experience${gaps.length ? `, ${gaps.length} ${gaps.length === 1 ? 'is' : 'are'} new to him` : ''}.`
        : 'Paste the full job description for a more useful result.';

      result.hidden = false;
      ring.style.strokeDashoffset = CIRC;
      const num = $('[data-score]', matcher);
      requestAnimationFrame(() => requestAnimationFrame(() => { ring.style.strokeDashoffset = CIRC * (1 - pct / 100); }));
      const t0 = performance.now(), dur = reduceMotion ? 0 : 1000;
      const step = (now) => {
        const p = dur ? clamp((now - t0) / dur) : 1;
        num.textContent = Math.round(pct * (1 - Math.pow(1 - p, 3)));
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
      result.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'nearest' });
    };

    $('[data-analyze]', matcher).addEventListener('click', analyze);
    jd.addEventListener('keydown', e => { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) analyze(); });
    $$('[data-preset]', matcher).forEach(b => b.addEventListener('click', () => {
      $$('[data-preset]', matcher).forEach(x => x.classList.toggle('is-active', x === b));
      jd.value = PRESETS[b.dataset.preset];
      analyze();
    }));
  }

  /* ---------- Book a Call ---------- */
  const callForm = $('[data-callform]');
  if (callForm) {
    const CFG = Object.assign({ slots: ['10:00', '11:00', '12:00', '14:30', '15:30', '16:30', '17:30'], days: 10, notice: 2, minutes: 30 }, DATA.call || {});
    const IST = 330; // minutes ahead of UTC
    const SLOTS = CFG.slots.map(s => s.split(':').map(Number));
    const LEAD_MS = CFG.notice * 3600 * 1000;
    const daysEl = $('[data-days]', callForm);
    const timesEl = $('[data-times]', callForm);
    const slotInput = $('[data-slot-input]', callForm);
    const slotStartInput = $('[data-slot-start]', callForm);
    const summary = $('[data-call-summary]', callForm);
    const status = $('[data-call-status]', callForm);
    const submitBtn = $('[data-call-submit]', callForm);
    const submitText = $('[data-call-submit-text]', callForm);
    const success = $('[data-call-success]', callForm);
    const sameTZ = new Date().getTimezoneOffset() === -IST;
    const localTZ = (Intl.DateTimeFormat().resolvedOptions().timeZone || 'local').replace(/_/g, ' ');
    $('[data-tz-note]', callForm).textContent = sameTZ ? `(IST, ${CFG.minutes} min)` : `(IST · your time zone: ${localTZ})`;

    const utcFmt = (o) => new Intl.DateTimeFormat('en-GB', Object.assign({ timeZone: 'UTC' }, o));
    const fDay = utcFmt({ weekday: 'short' }), fMon = utcFmt({ month: 'short' });
    const fLocal = new Intl.DateTimeFormat(undefined, { weekday: 'short', hour: 'numeric', minute: '2-digit' });
    const h12 = (h, m) => `${((h + 11) % 12) + 1}:${String(m).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`;
    const instant = (day, h, m) => Date.UTC(day.y, day.mo, day.d, h, m) - IST * 60000; // IST wall clock → UTC ms
    const iso = (ms) => new Date(ms).toISOString().replace('.000Z', 'Z');
    let busy = new Set();
    const isFree = (day, h, m) => { const at = instant(day, h, m); return at > Date.now() + LEAD_MS && !busy.has(iso(at)); };

    let days = [], selDay = null, selTime = null;
    const buildDays = () => {
      days = [];
      const nowIst = new Date(Date.now() + IST * 60000);
      for (let i = 0; days.length < CFG.days && i < 30; i++) {
        const dt = new Date(Date.UTC(nowIst.getUTCFullYear(), nowIst.getUTCMonth(), nowIst.getUTCDate() + i));
        const wd = dt.getUTCDay();
        if (wd === 0 || wd === 6) continue;
        const day = { y: dt.getUTCFullYear(), mo: dt.getUTCMonth(), d: dt.getUTCDate(), date: dt, today: i === 0 };
        if (SLOTS.some(([h, m]) => isFree(day, h, m))) days.push(day);
      }
    };

    const updateSummary = () => {
      if (!selDay || !selTime) {
        slotInput.value = ''; slotStartInput.value = '';
        summary.textContent = 'Choose a day and time above.';
        return;
      }
      const [h, m] = selTime;
      const at = instant(selDay, h, m);
      const label = `${fDay.format(selDay.date)} ${selDay.d} ${fMon.format(selDay.date)} ${selDay.y}, ${h12(h, m)} IST`;
      const local = sameTZ ? '' : ` (${fLocal.format(new Date(at))} your time)`;
      slotInput.value = label + local;
      slotStartInput.value = iso(at);
      summary.innerHTML = `Requested slot: <strong>${label}</strong>${local} · ${CFG.minutes} min. Vikas will confirm by email.`;
    };

    // Radio groups with arrow-key navigation (roving tabindex)
    const radioKeys = (group, onPick) => group.addEventListener('keydown', (e) => {
      const keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
      if (!(e.key in keys) && e.key !== 'Home' && e.key !== 'End') return;
      const items = $$('[role="radio"]:not(:disabled)', group);
      if (!items.length) return;
      e.preventDefault();
      const i = items.indexOf(document.activeElement);
      const next = e.key === 'Home' ? 0 : e.key === 'End' ? items.length - 1 : (i + keys[e.key] + items.length) % items.length;
      items[next].focus();
      onPick(items[next]);
    });
    const setRoving = (group) => {
      const items = $$('[role="radio"]', group);
      const sel = items.find(x => x.getAttribute('aria-checked') === 'true' && !x.disabled) || items.find(x => !x.disabled);
      items.forEach(x => { x.tabIndex = x === sel ? 0 : -1; });
    };

    const renderTimes = () => {
      timesEl.innerHTML = '';
      if (!selDay) { timesEl.innerHTML = '<p class="call__empty">Choose a day first.</p>'; return; }
      SLOTS.forEach(([h, m]) => {
        const at = instant(selDay, h, m);
        const taken = busy.has(iso(at));
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'call__time' + (taken ? ' is-booked' : ''); b.setAttribute('role', 'radio');
        b.disabled = !isFree(selDay, h, m);
        b.dataset.h = h; b.dataset.m = m;
        b.setAttribute('aria-checked', String(!!selTime && selTime[0] === h && selTime[1] === m));
        const note = taken ? 'Booked' : (at <= Date.now() + LEAD_MS ? 'Too soon' : (sameTZ ? `${CFG.minutes} min` : fLocal.format(new Date(at)) + ' your time'));
        b.innerHTML = `<strong>${h12(h, m)} IST</strong><small>${note}</small>`;
        b.setAttribute('aria-label', `${h12(h, m)} IST, ${note}`);
        timesEl.appendChild(b);
      });
      setRoving(timesEl);
    };
    const pickTime = (b) => { selTime = [Number(b.dataset.h), Number(b.dataset.m)]; renderTimes(); updateSummary(); $('[aria-checked="true"]', timesEl)?.focus(); };
    timesEl.addEventListener('click', (e) => { const b = e.target.closest('.call__time'); if (b && !b.disabled) pickTime(b); });
    radioKeys(timesEl, pickTime);

    const renderDays = () => {
      daysEl.innerHTML = '';
      days.forEach((day, i) => {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'call__day'; b.setAttribute('role', 'radio');
        b.dataset.i = i;
        const isSel = !!selDay && selDay.y === day.y && selDay.mo === day.mo && selDay.d === day.d;
        b.setAttribute('aria-checked', String(isSel));
        const free = SLOTS.filter(([h, m]) => isFree(day, h, m)).length;
        b.innerHTML = `<small>${day.today ? 'Today' : fDay.format(day.date)}</small><strong>${day.d}</strong><em>${fMon.format(day.date)}</em><i>${free} free</i>`;
        b.setAttribute('aria-label', `${fDay.format(day.date)} ${day.d} ${fMon.format(day.date)}, ${free} slots free`);
        daysEl.appendChild(b);
      });
      if (!days.length) daysEl.innerHTML = '<p class="call__empty">No free slots in the next two weeks. Please use Mail instead.</p>';
      setRoving(daysEl);
    };
    const pickDay = (b) => {
      selDay = days[Number(b.dataset.i)]; selTime = null;
      renderDays(); renderTimes(); updateSummary();
      $('[aria-checked="true"]', daysEl)?.focus();
    };
    daysEl.addEventListener('click', (e) => { const b = e.target.closest('.call__day'); if (b) pickDay(b); });
    radioKeys(daysEl, pickDay);

    const refresh = () => {
      buildDays();
      if (selDay && !days.some(d => d.y === selDay.y && d.mo === selDay.mo && d.d === selDay.d)) selDay = null;
      if (selDay && selTime && !isFree(selDay, selTime[0], selTime[1])) selTime = null;
      renderDays(); renderTimes(); updateSummary();
    };
    const loadBusy = async () => {
      try {
        const res = await fetch('slots.php', { headers: { Accept: 'application/json' }, cache: 'no-store' });
        const json = await res.json();
        if (json && Array.isArray(json.busy)) busy = new Set(json.busy);
      } catch (e) { /* offline or not configured: show all slots */ }
      refresh();
    };
    refresh();
    loadBusy();
    // Re-check booked slots whenever the app is opened again
    const callWin = callForm.closest('.win');
    if (callWin) new MutationObserver(() => { if (!callWin.hidden) loadBusy(); }).observe(callWin, { attributes: true, attributeFilter: ['hidden'] });

    // Instant booking tab (only when a Calendly / Google appointment link is configured)
    const modeBtns = $$('[data-callmode]');
    modeBtns.forEach(btn => btn.addEventListener('click', () => {
      modeBtns.forEach(x => x.setAttribute('aria-selected', String(x === btn)));
      $$('[data-callpane]').forEach(p => { p.hidden = p.dataset.callpane !== btn.dataset.callmode; });
      const frame = $('[data-callpane="instant"] iframe');
      if (btn.dataset.callmode === 'instant' && frame && !frame.src) frame.src = frame.dataset.src;
    }));

    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    callForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const nameEl = $('input[name="name"]', callForm), mailEl = $('input[name="email"]', callForm);
      $$('.cfield', callForm).forEach(f => f.classList.remove('is-invalid'));
      let err = '';
      if (!slotStartInput.value) { err = 'Please choose a day and a time.'; (daysEl.querySelector('[tabindex="0"]') || daysEl).focus(); }
      else if (!nameEl.value.trim()) { err = 'Please enter your name.'; nameEl.closest('.cfield').classList.add('is-invalid'); nameEl.focus(); }
      else if (!emailRe.test(mailEl.value.trim())) { err = 'Please enter a valid email.'; mailEl.closest('.cfield').classList.add('is-invalid'); mailEl.focus(); }
      if (err) { status.textContent = err; status.classList.add('is-error'); return; }

      status.textContent = ''; status.classList.remove('is-error');
      submitBtn.classList.add('is-loading'); submitBtn.disabled = true; submitText.textContent = 'Checking the slot…';
      try {
        const res = await fetch(callForm.action, { method: 'POST', body: new FormData(callForm), headers: { Accept: 'application/json' } });
        const json = await res.json().catch(() => ({}));
        if (res.status === 409) {               // someone just took this slot
          busy.add(slotStartInput.value);
          selTime = null; refresh();
          throw new Error(json.error || 'That time was just booked. Please choose another slot.');
        }
        if (!res.ok || !json.ok) throw new Error(json.error || 'Something went wrong.');
        busy.add(slotStartInput.value);
        $('[data-call-done-text]', callForm).textContent = `${slotInput.value}. A confirmation is on its way to ${mailEl.value.trim()}, and Vikas will confirm the slot within one working day.`;
        success.hidden = false;
        $('[data-call-reset]', callForm).focus();
      } catch (ex) {
        status.textContent = ex.message + (ex.message.includes('booked') ? '' : ' You can also email vikasr.giri@outlook.com.');
        status.classList.add('is-error');
      } finally {
        submitBtn.classList.remove('is-loading'); submitBtn.disabled = false; submitText.textContent = 'Request this call';
      }
    });
    $('[data-call-reset]', callForm).addEventListener('click', () => {
      success.hidden = true; callForm.reset();
      selDay = null; selTime = null;
      refresh();
    });
  }

  /* ---------- Resume paper ---------- */
  const paper = $('[data-paper]');
  if (paper) {
    const btn = $('[data-paper-toggle]', paper);
    btn.addEventListener('click', () => {
      const open = !paper.classList.contains('is-open');
      paper.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', String(open));
      $('[data-paper-label]', paper).textContent = open ? 'Collapse' : 'Read full resume';
      if (!open) paper.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    });
  }

  /* ---------- Contact form ---------- */
  const form = $('[data-form]');
  if (form) {
    const status = $('[data-form-status]', form);
    const submit = $('[data-submit]', form);
    const submitText = $('[data-submit-text]', form);
    const success = $('[data-form-success]', form);
    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    const check = (el) => {
      const field = el.closest('.field');
      if (!field) return true;
      const v = el.value.trim();
      let ok = true;
      if (el.required && !v) ok = false;
      if (ok && el.type === 'email') ok = emailRe.test(v);
      if (ok && el.tagName === 'TEXTAREA') ok = v.length >= 10;
      field.classList.toggle('is-invalid', !ok);
      el.setAttribute('aria-invalid', String(!ok));
      return ok;
    };
    $$('.field input, .field textarea', form).forEach(el => {
      el.addEventListener('blur', () => { if (el.value) check(el); });
      el.addEventListener('input', () => { if (el.closest('.field.is-invalid')) check(el); });
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const invalid = $$('.field input, .field textarea', form).filter(el => !check(el));
      if (invalid.length) {
        invalid[0].focus();
        status.textContent = 'Please fix the highlighted fields.';
        status.classList.add('is-error');
        return;
      }
      status.textContent = ''; status.classList.remove('is-error');
      submit.classList.add('is-loading'); submitText.textContent = 'Sending…';
      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.ok) throw new Error(json.error || 'Something went wrong.');
        success.hidden = false;
        form.reset();
        $('[data-form-reset]', form).focus();
      } catch (err) {
        status.textContent = `${err.message} You can also email me at vikasr.giri@outlook.com.`;
        status.classList.add('is-error');
      } finally {
        submit.classList.remove('is-loading'); submitText.textContent = 'Send message';
      }
    });
    $('[data-form-reset]', form).addEventListener('click', () => {
      success.hidden = true;
      $$('.field', form).forEach(f => f.classList.remove('is-invalid'));
      $('input[name="name"]', form).focus();
    });
  }

})();
