<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Archery Score Recording</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div id="app">

  <!-- ── Top bar (hidden on login screen) ──────────────────────────────── -->
  <header class="top-bar" id="top-bar" style="display:none">
    <span class="logo">&#127981; Archery Scores</span>
    <div class="top-right">
      <span id="top-username"></span>
      <nav>
        <button class="nav-btn active" data-screen="setup">New Round</button>
        <button class="nav-btn" data-screen="history">History</button>
      </nav>
      <button class="logout-btn" id="btn-logout">Log out</button>
    </div>
  </header>

  <!-- ══════════════════════════════════════════════════════════════════════
       SCREEN: Login
  ═══════════════════════════════════════════════════════════════════════ -->
  <section id="screen-login" class="screen active login-screen">
    <div class="login-card">
      <div class="login-logo">&#127981;</div>
      <h1>Archery Score Recording</h1>
      <p class="login-sub">Sign in to continue</p>
      <div class="field">
        <label for="inp-username">Username</label>
        <input type="text" id="inp-username" autocomplete="username" placeholder="Enter username">
      </div>
      <div class="field">
        <label for="inp-password">Password</label>
        <input type="password" id="inp-password" autocomplete="current-password" placeholder="Enter password">
      </div>
      <button class="btn-primary" id="btn-login">Sign in</button>
      <p class="status-msg" id="login-error"></p>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════════════════════
       SCREEN: Setup — choose competition, archer, category
  ═══════════════════════════════════════════════════════════════════════ -->
  <section id="screen-setup" class="screen">
    <div class="card">
      <h2 class="section-label">Competition</h2>
      <select id="sel-competition"></select>
      <div class="comp-meta" id="comp-meta"></div>
    </div>

    <div class="card">
      <h2 class="section-label">Archer</h2>
      <select id="sel-archer"></select>
    </div>

    <div class="card">
      <h2 class="section-label">Category</h2>
      <select id="sel-category"></select>
    </div>

    <div class="card" id="distance-card">
      <h2 class="section-label">Distance for round 1 (metres)</h2>
      <input type="number" id="inp-distance" min="1" max="300" value="70"
             style="width:100%;padding:10px 12px;font-size:15px;border:.5px solid var(--border);border-radius:8px;">
      <p class="field-hint">You can update distance before each round starts.</p>
    </div>

    <button class="btn-primary" id="btn-start-scoring">Start scoring</button>
    <p class="status-msg" id="setup-error"></p>
  </section>

  <!-- ══════════════════════════════════════════════════════════════════════
       SCREEN: Scoring overview — rounds & ends
  ═══════════════════════════════════════════════════════════════════════ -->
  <section id="screen-scoring" class="screen">
    <div class="breadcrumb" id="bc-scoring">&#8592; Back to setup</div>
    <div class="session-header" id="session-header"></div>
    <div id="round-list"></div>
    <div id="grand-total-box" class="grand-total" style="display:none">
      <span>Grand total</span>
      <span id="grand-total-val"></span>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════════════════════
       SCREEN: Arrow entry for one end
  ═══════════════════════════════════════════════════════════════════════ -->
  <section id="screen-entry" class="screen">
    <div class="breadcrumb" id="bc-entry">&#8592; Score sheet</div>
    <div class="entry-header">
      <div id="entry-archer"></div>
      <div id="entry-comp"></div>
      <div id="entry-round-end"></div>
    </div>
    <div class="arrows-display" id="arrows-display"></div>
    <div class="total-row">End total <span id="end-total">0</span></div>
    <div class="del-row" id="del-row" style="display:none">
      <button id="btn-del-last">&#9003; Remove last</button>
    </div>
    <div class="keypad" id="keypad"></div>
    <div class="action-row">
      <button class="btn-cancel" id="btn-cancel">Cancel</button>
      <button class="btn-primary" id="btn-save" disabled>Save end</button>
    </div>
    <p class="status-msg" id="entry-error"></p>
  </section>

  <!-- ══════════════════════════════════════════════════════════════════════
       SCREEN: Complete
  ═══════════════════════════════════════════════════════════════════════ -->
  <section id="screen-complete" class="screen">
    <div class="complete-box">
      <div class="trophy">&#127981;</div>
      <h2>Competition complete!</h2>
      <div id="complete-archer-name"></div>
      <div id="complete-comp-name"></div>
      <div class="final-score" id="final-score">—</div>
      <div class="complete-sub" id="complete-sub"></div>
      <div id="complete-round-breakdown"></div>
      <button class="btn-primary" id="btn-new-round" style="max-width:200px;margin:1.5rem auto 0">New round</button>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════════════════════
       SCREEN: History
  ═══════════════════════════════════════════════════════════════════════ -->
  <section id="screen-history" class="screen">
    <h2 class="section-label" style="margin:1rem 0 .75rem">Score history</h2>
    <div id="history-list"></div>
  </section>

</div><!-- #app -->

<script src="assets/js/app.js"></script>
</body>
</html>
