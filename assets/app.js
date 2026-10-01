const $ = id => document.getElementById(id);
let state, teams = [], selected = new Set(), busy = false, deadline = 0, finishing = false, audioContext, alarmTimer, sounded = false;
const node = (tag, text, cls) => { const n = document.createElement(tag); n.textContent = text; if (cls) n.className = cls; return n; };
async function api(action = 'state', body) {
  const response = await fetch(`api.php?action=${action}`, {method: body === undefined ? 'GET' : 'POST', cache: 'no-store', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content}, body: body === undefined ? undefined : JSON.stringify(body)});
  const data = await response.json(); if (!response.ok) throw new Error(data.error || 'Nie udało się połączyć. Spróbuj ponownie.'); return data;
}
async function run(fn) {
  if (busy) return; busy = true; $('notice').hidden = true; document.body.classList.add('busy');
  try { await fn(); } catch (e) { $('notice').textContent = e.message; $('notice').hidden = false; }
  finally { busy = false; document.body.classList.remove('busy'); }
}
function save() { try { sessionStorage.setItem('showit-draft', JSON.stringify({teams, categories: [...selected], duration: $('duration').value})); } catch {} }
function renderSetup() {
  $('categories').replaceChildren();
  state.catalog.forEach(c => { const b = node('button', '', 'category'); b.type = 'button'; b.setAttribute('aria-pressed', selected.has(c.id)); b.append(node('span', c.emoji, 'category-emoji'), node('span', c.name), node('span', selected.has(c.id) ? '✓' : '+', 'check')); b.onclick = () => { selected.has(c.id) ? selected.delete(c.id) : selected.add(c.id); save(); renderSetup(); }; $('categories').append(b); });
  $('teams').replaceChildren();
  teams.forEach((name, i) => { const li = node('li', ''); li.append(node('span', String(i + 1).padStart(2, '0'), 'player-number'), node('span', name, 'player-label')); const b = node('button', '×'); b.setAttribute('aria-label', `Usuń drużynę ${name}`); b.onclick = () => { teams.splice(i, 1); save(); renderSetup(); }; li.append(b); $('teams').append(li); });
  $('team-count').textContent = `${teams.length} / 20`; $('new-game').disabled = teams.length < 2 || !selected.size;
  $('select-all').textContent = selected.size === state.catalog.length ? 'Odznacz wszystkie' : 'Zaznacz wszystkie';
}
async function unlockAudio() {
  try { audioContext ??= new (window.AudioContext || window.webkitAudioContext)(); await audioContext.resume(); } catch { $('notice').textContent = 'Dźwięk niedostępny. Obserwuj licznik czasu.'; $('notice').hidden = false; }
}
function alarm() {
  if (audioContext?.state !== 'running') return;
  // Three rising and falling sweeps imitate a fire-engine siren.
  const oscillator = audioContext.createOscillator(), gain = audioContext.createGain();
  const at = audioContext.currentTime, duration = 3.6;
  oscillator.type = 'triangle';
  oscillator.frequency.setValueAtTime(500, at);
  for (let i = 0; i < 3; i++) {
    oscillator.frequency.linearRampToValueAtTime(1100, at + i * 1.2 + .6);
    oscillator.frequency.linearRampToValueAtTime(500, at + (i + 1) * 1.2);
  }
  gain.gain.setValueAtTime(0, at);
  gain.gain.linearRampToValueAtTime(.25, at + .05);
  gain.gain.setValueAtTime(.25, at + duration - .15);
  gain.gain.linearRampToValueAtTime(0, at + duration);
  oscillator.connect(gain); gain.connect(audioContext.destination);
  oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); };
  oscillator.start(at); oscillator.stop(at + duration);
  navigator.vibrate?.([150, 80, 150]);
}
function render() {
  clearTimeout(alarmTimer);
  ['setup', 'ready', 'running', 'finished'].forEach(id => $(id).hidden = true);
  $(state.phase).hidden = false; $('room').textContent = `● POKÓJ ${state.code}`; document.body.classList.toggle('playing', state.phase !== 'setup');
  if (state.phase === 'setup') { renderSetup(); return; }
  const team = state.teams[state.turn];
  if (state.phase === 'ready') { sounded = false; $('ready-round').textContent = `RUNDA ${state.round} · DRUŻYNA ${state.turn + 1} / ${state.teams.length}`; $('ready-team').textContent = team.name; $('ready-time').textContent = `Macie ${state.duration} sekund. Każde zgadnięte hasło to 1 punkt.`; }
  if (state.phase === 'running') { deadline = performance.now() + state.remaining * 1000; $('active-team').textContent = `${team.name} · RUNDA ${state.round}`; $('word').textContent = state.word; $('round-points').textContent = `Punkty w tej turze: ${state.roundPoints} · Łącznie: ${team.score}`; tick(); alarmTimer = setTimeout(expire, Math.max(0, deadline - performance.now())); }
  if (state.phase === 'finished') { $('word').textContent = ''; $('result').textContent = `${team.name}: +${state.roundPoints} pkt w tej turze`; $('scores').replaceChildren(); [...state.teams].sort((a, b) => b.score - a.score).forEach(t => { const li = node('li', ''); li.append(node('span', t.name, 'player-label'), node('strong', `${t.score} pkt`)); $('scores').append(li); }); $('advance').firstChild.textContent = `Teraz: ${state.teams[(state.turn + 1) % state.teams.length].name} `; }
}
async function change(action, body = {}) { state = await api(action, body); render(); }
function tick() {
  if (state?.phase !== 'running') return;
  const s = Math.max(0, Math.ceil((deadline - performance.now()) / 1000)); $('clock').textContent = `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`; $('clock').classList.toggle('urgent', s <= 10); $('next').disabled = s === 0;
  if (s === 0) expire();
}
async function expire() {
  if (state?.phase !== 'running' || finishing) return;
  finishing = true; if (!sounded) { alarm(); sounded = true; } $('next').disabled = true;
  try { const fresh = await api(); state = fresh; render(); }
  catch { $('word').textContent = ''; $('notice').textContent = 'Czas minął. Czekamy na połączenie z serwerem…'; $('notice').hidden = false; }
  finally { setTimeout(() => { finishing = false; }, 1000); }
}
$('team-form').onsubmit = e => { e.preventDefault(); const name = $('team-name').value.trim(); if (!name) return; if (teams.length >= 20 || teams.some(t => t.toLocaleLowerCase('pl') === name.toLocaleLowerCase('pl'))) { $('team-name').setCustomValidity('Wpisz inną nazwę. Maksymalnie 20 drużyn.'); $('team-name').reportValidity(); return; } teams.push(name); $('team-name').value = ''; save(); renderSetup(); $('team-name').focus(); };
$('team-name').oninput = () => $('team-name').setCustomValidity(''); $('duration').oninput = save;
$('select-all').onclick = () => { selected = selected.size === state.catalog.length ? new Set() : new Set(state.catalog.map(c => c.id)); save(); renderSetup(); };
$('new-game').onclick = () => { if (!$('duration').reportValidity()) return; run(() => change('new', {teams, categories: [...selected], duration: Number($('duration').value)})); };
$('start').onclick = () => { unlockAudio(); run(() => change('start')); };
$('test-sound').onclick = async () => { await unlockAudio(); alarm(); };
$('next').onclick = () => run(() => change('next', {revision: state.revision}));
$('advance').onclick = () => run(() => change('advance'));
document.querySelectorAll('.edit-room').forEach(b => b.onclick = () => run(() => change('setup')));
document.addEventListener('visibilitychange', () => { if (!document.hidden && state?.phase === 'running') run(async () => { const wasRunning = state.phase === 'running'; state = await api(); if (wasRunning && state.phase === 'finished' && !sounded) { alarm(); sounded = true; } render(); }); });
setInterval(tick, 100);
run(async () => {
  state = await api(); teams = state.teams.map(t => t.name); selected = new Set(state.categories); $('duration').value = state.duration;
  if (state.phase === 'setup') { try { const draft = JSON.parse(sessionStorage.getItem('showit-draft')); if (draft && Array.isArray(draft.teams) && Array.isArray(draft.categories)) { teams = draft.teams.filter(t => typeof t === 'string' && t.trim()).slice(0, 20); selected = new Set(draft.categories); const d = Number(draft.duration); if (Number.isInteger(d) && d >= 15 && d <= 300) $('duration').value = d; } } catch {} }
  selected = new Set([...selected].filter(id => state.catalog.some(c => c.id === id))); render(); if (!state.catalog.length) throw new Error('Brak kategorii. Dodaj pliki do data/categories.');
});
