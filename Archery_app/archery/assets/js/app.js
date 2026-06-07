// assets/js/app.js
// Archery Score Recording — full frontend logic

'use strict';

// ── Constants ────────────────────────────────────────────────────────────────
const API     = 'api.php';
const SCORES  = ['X','10','9','8','7','6','5','4','3','2','1','M'];
const SVAL    = s => s==='X'?10:s==='M'?0:parseInt(s);
const SORD    = s => s==='X'?12:s==='M'?0:parseInt(s);

function arrowStyle(s) {
  if (!s) return {};
  if (s==='X'||s==='10') return {bg:'#FFD700',fg:'#5C4000',border:'#C9A800'};
  if (s==='9' ||s==='8') return {bg:'#E63946',fg:'#fff',   border:'#C1121F'};
  if (s==='7' ||s==='6') return {bg:'#2563EB',fg:'#fff',   border:'#1D4ED8'};
  if (s==='5' ||s==='4') return {bg:'#222',   fg:'#fff',   border:'#444'};
  if (['3','2','1'].includes(s)) return {bg:'#EDEBE4',fg:'#333',border:'#ccc'};
  return {bg:'#6B7280',fg:'#fff',border:'#4B5563'};
}

// ── App state ────────────────────────────────────────────────────────────────
let S = {
  user:        null,
  competitions:[],
  archers:     [],
  categories:  [],
  // session
  competition: null,   // full competition object
  archer:      null,   // full archer object
  category:    null,   // full category object
  distance:    70,     // current round distance (metres)
  // scores: { roundNum: { endNum: ['X','9',…] } }
  savedEnds:   {},
  savedRounds: {},
  // entry
  activeRound: 1,
  activeEnd:   1,
  currentArrows: [],
};

// ── API helper ───────────────────────────────────────────────────────────────
async function api(action, params = {}, body = null) {
  const qs  = new URLSearchParams({ action, ...params });
  const url = `${API}?${qs}`;
  const opt = body
    ? { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) }
    : { method:'GET' };
  const res  = await fetch(url, opt);
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'API error');
  return data;
}

// ── Screen helper ────────────────────────────────────────────────────────────
function show(screenId) {
  document.querySelectorAll('.screen').forEach(el => el.classList.remove('active'));
  document.getElementById(`screen-${screenId}`).classList.add('active');
  document.getElementById('top-bar').style.display =
    screenId === 'login' ? 'none' : 'flex';
}

function $id(id) { return document.getElementById(id); }
function setErr(id, msg) { $id(id).textContent = msg || ''; }

// ════════════════════════════════════════════════════════════════════════════
// LOGIN
// ════════════════════════════════════════════════════════════════════════════
async function tryLogin() {
  const username = $id('inp-username').value.trim();
  const password = $id('inp-password').value;
  setErr('login-error', '');
  if (!username || !password) { setErr('login-error', 'Enter username and password.'); return; }
  $id('btn-login').disabled = true;
  try {
    const data = await api('login', {}, { username, password });
    S.user = data.user;
    $id('top-username').textContent = `${data.user.first_name || data.user.username}`;
    await loadSetup();
    show('setup');
  } catch(e) {
    setErr('login-error', e.message);
  } finally {
    $id('btn-login').disabled = false;
  }
}

$id('btn-login').onclick = tryLogin;
$id('inp-password').addEventListener('keydown', e => { if (e.key==='Enter') tryLogin(); });

$id('btn-logout').onclick = async () => {
  await api('logout', {}, {});
  S.user = null;
  show('login');
};

// ════════════════════════════════════════════════════════════════════════════
// SETUP
// ════════════════════════════════════════════════════════════════════════════
async function loadSetup() {
  const [compData, archerData] = await Promise.all([
    api('competitions'), api('archers')
  ]);
  S.competitions = compData.competitions;
  S.archers      = archerData.archers;

  // Competitions dropdown
  const cSel = $id('sel-competition');
  cSel.innerHTML = S.competitions.map(c =>
    `<option value="${c.competition_id}">${c.name} (${c.start_date})</option>`
  ).join('');
  await onCompetitionChange();

  // Archers dropdown
  const aSel = $id('sel-archer');
  aSel.innerHTML = S.archers.map(a =>
    `<option value="${a.archer_id}">${a.first_name} ${a.last_name} — ${a.club_name}</option>`
  ).join('');

  // If logged-in user is an archer, pre-select them
  if (S.user?.archer_id) {
    aSel.value = S.user.archer_id;
  }
  await onArcherChange();

  cSel.onchange = onCompetitionChange;
  aSel.onchange = onArcherChange;
  $id('sel-category').onchange = () => {
    const cat = S.categories.find(c => c.category_id == $id('sel-category').value);
    S.category = cat || null;
  };
  $id('inp-distance').onchange = () => { S.distance = parseInt($id('inp-distance').value) || 70; };
}

async function onCompetitionChange() {
  const id = parseInt($id('sel-competition').value);
  S.competition = S.competitions.find(c => c.competition_id === id) || null;
  if (S.competition) {
    $id('comp-meta').innerHTML =
      `${S.competition.club_name} &bull; ${S.competition.state || ''} &bull; `+
      `${S.competition.number_of_rounds} round(s) &times; ${S.competition.ends_per_round} ends &bull; `+
      `Format: ${S.competition.format}`;
  }
}

async function onArcherChange() {
  const archerId = parseInt($id('sel-archer').value);
  S.archer = S.archers.find(a => a.archer_id === archerId) || null;
  if (!S.archer) return;

  // Load categories filtered by archer's gender
  const catData  = await api('categories', { gender: S.archer.gender });
  S.categories   = catData.categories;
  const catSel   = $id('sel-category');
  catSel.innerHTML = S.categories.map(c =>
    `<option value="${c.category_id}">${c.bow_type} / ${c.age} / ${c.gender}</option>`
  ).join('');
  S.category = S.categories[0] || null;
}

$id('btn-start-scoring').onclick = async () => {
  setErr('setup-error', '');
  if (!S.competition || !S.archer || !S.category) {
    setErr('setup-error', 'Please select a competition, archer, and category.');
    return;
  }
  S.distance = parseInt($id('inp-distance').value) || 70;
  // Load any existing scores for this session
  try {
    const data = await api('session_data', {
      competition_id: S.competition.competition_id,
      archer_id:      S.archer.archer_id,
      category_id:    S.category.category_id,
    });
    S.savedEnds   = data.ends   || {};
    S.savedRounds = data.rounds || {};
  } catch(_) {
    S.savedEnds   = {};
    S.savedRounds = {};
  }
  renderScoringScreen();
  show('scoring');
};

// ════════════════════════════════════════════════════════════════════════════
// SCORING OVERVIEW
// ════════════════════════════════════════════════════════════════════════════
function renderScoringScreen() {
  const comp    = S.competition;
  const nRounds = parseInt(comp.number_of_rounds);
  const nEnds   = parseInt(comp.ends_per_round);

  $id('session-header').innerHTML =
    `<strong>${S.archer.first_name} ${S.archer.last_name}</strong> &bull; `+
    `${S.category.bow_type} / ${S.category.age} &bull; `+
    `<strong>${comp.name}</strong>`;

  let html = '';
  for (let r = 1; r <= nRounds; r++) {
    const roundData  = S.savedRounds[r];
    const roundDone  = !!roundData;
    const roundTotal = roundData ? roundData.total_score : null;
    const roundDist  = roundData ? roundData.distance : null;
    const distLabel  = roundDist ? `${roundDist}m` : '';

    html += `<div class="round-block">
      <div class="round-header">
        <span>Round ${r} <span class="round-dist">${distLabel}</span></span>
        ${roundDone ? `<span class="round-total-chip">${roundTotal}</span>` : ''}
      </div>`;

    for (let e = 1; e <= nEnds; e++) {
      const arrows = (S.savedEnds[r] || {})[e];
      const isDone = arrows && arrows.length === 6;

      // "next" = first un-scored end in the first incomplete round
      const isNext = !isDone && isNextEnd(r, e, nRounds, nEnds);
      const endTotal = isDone ? arrows.reduce((s,a)=>s+SVAL(a),0) : null;
      const scoreStr = isDone ? arrows.join(' ') : '';

      html += `
        <div class="end-row${isDone?' done':''}${isNext?' next':''}" data-round="${r}" data-end="${e}">
          <div class="end-info">
            <div class="end-label">End ${e}</div>
            ${isDone
              ? `<div class="end-scores">${scoreStr}</div>`
              : `<div class="end-sublabel${isNext?' cta':''}">${isNext?'Tap to score →':'—'}</div>`}
          </div>
          ${isDone ? `<div class="end-total">${endTotal}</div>` : ''}
          <button class="pen-btn" data-round="${r}" data-end="${e}" aria-label="Score end ${e}">&#9998;</button>
        </div>`;
    }
    html += `</div>`;
  }
  $id('round-list').innerHTML = html;

  // Grand total if all rounds done
  const allRoundsDone = Object.keys(S.savedRounds).length === nRounds;
  if (allRoundsDone) {
    const gt = Object.values(S.savedRounds).reduce((s,r)=>s+parseInt(r.total_score),0);
    $id('grand-total-val').textContent = gt;
    $id('grand-total-box').style.display = 'flex';
  } else {
    $id('grand-total-box').style.display = 'none';
  }

  // Attach events
  $id('round-list').querySelectorAll('[data-round]').forEach(el => {
    el.addEventListener('click', () => {
      openEntry(parseInt(el.dataset.round), parseInt(el.dataset.end));
    });
  });
}

function isNextEnd(r, e, nRounds, nEnds) {
  // Walk all ends in order; return true for the first one without saved arrows
  for (let ri = 1; ri <= nRounds; ri++) {
    for (let ei = 1; ei <= nEnds; ei++) {
      const a = (S.savedEnds[ri]||{})[ei];
      if (!a || a.length < 6) return ri === r && ei === e;
    }
  }
  return false;
}

$id('bc-scoring').onclick = () => show('setup');

// ════════════════════════════════════════════════════════════════════════════
// ARROW ENTRY
// ════════════════════════════════════════════════════════════════════════════
function openEntry(roundNum, endNum) {
  S.activeRound   = roundNum;
  S.activeEnd     = endNum;
  S.currentArrows = [...((S.savedEnds[roundNum]||{})[endNum] || [])];

  $id('entry-archer').textContent    = `${S.archer.first_name} ${S.archer.last_name}`;
  $id('entry-comp').textContent      = `${S.competition.name} · ${S.category.bow_type} / ${S.category.age}`;
  $id('entry-round-end').textContent = `Round ${roundNum}  ·  End ${endNum} of ${S.competition.ends_per_round}`;

  renderEntryKeypad();
  show('entry');
}

function renderEntryKeypad() {
  const cur     = S.currentArrows;
  const maxOrd  = cur.length === 0 ? 13 : SORD(cur.at(-1));
  const endTotal = cur.reduce((s,a)=>s+SVAL(a),0);

  // Chips
  let chips = '';
  for (let i = 0; i < 6; i++) {
    const s = cur[i];
    if (s) {
      const st = arrowStyle(s);
      chips += `<div class="arrow-chip" style="background:${st.bg};color:${st.fg};border-color:${st.border}">${s}</div>`;
    } else {
      chips += `<div class="arrow-chip empty">${i+1}</div>`;
    }
  }
  $id('arrows-display').innerHTML = chips;
  $id('end-total').textContent    = endTotal;
  $id('del-row').style.display    = cur.length ? 'flex' : 'none';

  // Keypad
  let khtml = '';
  SCORES.forEach(s => {
    const dis = (cur.length >= 6 || SORD(s) > maxOrd) ? 'disabled' : '';
    khtml += `<button class="key-btn key-${s}" data-score="${s}" ${dis}>${s}</button>`;
  });
  $id('keypad').innerHTML = khtml;
  $id('keypad').querySelectorAll('.key-btn:not(:disabled)').forEach(btn => {
    btn.onclick = () => pushArrow(btn.dataset.score);
  });

  $id('btn-save').disabled = cur.length !== 6;
  setErr('entry-error', '');
}

function pushArrow(score) {
  if (S.currentArrows.length >= 6) return;
  const maxOrd = S.currentArrows.length === 0 ? 13 : SORD(S.currentArrows.at(-1));
  if (SORD(score) > maxOrd) return;
  S.currentArrows.push(score);
  renderEntryKeypad();
}

$id('btn-del-last').onclick = () => {
  if (S.currentArrows.length > 0) { S.currentArrows.pop(); renderEntryKeypad(); }
};

$id('btn-cancel').onclick = () => { renderScoringScreen(); show('scoring'); };

$id('bc-entry').onclick = () => { renderScoringScreen(); show('scoring'); };

$id('btn-save').onclick = async () => {
  setErr('entry-error', '');
  $id('btn-save').disabled = true;
  try {
    const res = await api('save_end', {}, {
      competition_id: S.competition.competition_id,
      archer_id:      S.archer.archer_id,
      category_id:    S.category.category_id,
      round_number:   S.activeRound,
      end_number:     S.activeEnd,
      arrows:         S.currentArrows,
      distance:       S.distance,
      ends_per_round: parseInt(S.competition.ends_per_round),
    });

    // Update local state
    if (!S.savedEnds[S.activeRound]) S.savedEnds[S.activeRound] = {};
    S.savedEnds[S.activeRound][S.activeEnd] = [...S.currentArrows];
    S.savedRounds = res.rounds || S.savedRounds;

    if (res.all_complete) {
      showComplete(res.grand_total, res.total_xs);
    } else {
      // Advance to next unsaved end if possible
      advanceToNextEnd();
      renderScoringScreen();
      show('scoring');
    }
  } catch(e) {
    setErr('entry-error', e.message);
    $id('btn-save').disabled = false;
  }
};

function advanceToNextEnd() {
  const nR = parseInt(S.competition.number_of_rounds);
  const nE = parseInt(S.competition.ends_per_round);
  for (let r = 1; r <= nR; r++) {
    for (let e = 1; e <= nE; e++) {
      const a = (S.savedEnds[r]||{})[e];
      if (!a || a.length < 6) { S.activeRound = r; S.activeEnd = e; return; }
    }
  }
}

// ════════════════════════════════════════════════════════════════════════════
// COMPLETE
// ════════════════════════════════════════════════════════════════════════════
function showComplete(grandTotal, totalXs) {
  $id('complete-archer-name').textContent = `${S.archer.first_name} ${S.archer.last_name} · ${S.category.bow_type}`;
  $id('complete-comp-name').textContent   = S.competition.name;
  $id('final-score').textContent          = grandTotal;
  const tens = Object.values(S.savedEnds).flatMap(r=>Object.values(r)).flat().filter(s=>s==='10').length;
  $id('complete-sub').textContent = `${totalXs} X's · ${tens} 10's`;

  let brk = '<div class="complete-round-breakdown">';
  Object.entries(S.savedRounds).forEach(([rn, rd]) => {
    brk += `<div class="crb-row"><span class="crb-label">Round ${rn} (${rd.distance}m)</span><span class="crb-val">${rd.total_score}</span></div>`;
  });
  brk += '</div>';
  $id('complete-round-breakdown').innerHTML = brk;
  show('complete');
}

$id('btn-new-round').onclick = () => show('setup');

// ════════════════════════════════════════════════════════════════════════════
// HISTORY
// ════════════════════════════════════════════════════════════════════════════
async function loadHistory() {
  const archerId = S.user?.archer_id;
  if (!archerId) {
    $id('history-list').innerHTML = '<p class="empty-msg">Log in as an archer to see history.</p>';
    return;
  }
  try {
    const data = await api('history', { archer_id: archerId });
    if (!data.history.length) {
      $id('history-list').innerHTML = '<p class="empty-msg">No completed rounds yet.</p>';
      return;
    }
    $id('history-list').innerHTML = data.history.map(h =>
      `<div class="history-row">
        <div>
          <div class="h-name">${h.competition_name}</div>
          <div class="h-meta">${h.bow_type} / ${h.age} &bull; Round ${h.round_number} &bull; ${h.distance}m &bull; ${h.start_date}<br>${h.x_number} X's</div>
        </div>
        <div class="h-score">${h.total_score}</div>
      </div>`
    ).join('');
  } catch(e) {
    $id('history-list').innerHTML = `<p class="empty-msg">${e.message}</p>`;
  }
}

// ── Nav ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('.nav-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const target = btn.dataset.screen;
    if (target === 'history') await loadHistory();
    show(target);
  });
});

// ── Boot: check if already logged in ────────────────────────────────────────
(async () => {
  try {
    const data = await api('me');
    S.user = data.user;
    $id('top-username').textContent = data.user.first_name || data.user.username;
    await loadSetup();
    show('setup');
  } catch(_) {
    show('login');
  }
})();
