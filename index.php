<?php
declare(strict_types=1);
session_name('showit');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#171426">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf']) ?>">
  <title>Show It! — Pokaż to!</title>
  <link rel="icon" type="image/png" href="yupiii_games_small.png">
  <link rel="stylesheet" href="assets/style.css">
  <script src="assets/app.js?v=20261001" defer></script>
  <script src="assets/support.js" defer></script>
</head>
<body>
<main class="app">
<header><a class="brand" href="./"><span class="brand-icon">✳</span> show it<span class="lime">!</span></a><span class="room" id="room">● POKÓJ</span></header>
<div id="notice" role="alert" hidden></div>
<section id="setup" hidden>
<div class="intro"><span class="eyebrow">JEDEN TELEFON. CAŁA EKIPA.</span><h1>Bez słów.<br><span class="lime">Pokaż to!</span> 🎭</h1><p>Pokazuj, zgaduj i zbieraj punkty. Ile haseł odgadnie Twoja drużyna, zanim skończy się czas?</p></div>
<button class="support-button" type="button" data-support>Dobra zabawa? Postaw autorowi kawę ☕</button>
<div class="panel"><div class="section-heading"><h2><span>01</span> Wybierz klimat</h2><button class="text-button" id="select-all">Zaznacz wszystkie</button></div><p class="muted">Z jakich kategorii losujemy hasła?</p><div id="categories" class="categories"></div></div>
<div class="panel"><div class="section-heading"><h2><span>02</span> Zbierz drużyny</h2><span class="count" id="team-count">0 / 20</span></div><p class="muted">Minimum 2 drużyny. W każdej turze pokazuje kolejna osoba z drużyny.</p><form id="team-form"><label class="sr-only" for="team-name">Nazwa drużyny</label><input id="team-name" maxlength="30" placeholder="Jak nazywa się drużyna?" autocomplete="off" required><button class="add-button" aria-label="Dodaj drużynę">＋</button></form><ul id="teams"></ul></div>
<div class="panel"><div class="section-heading"><h2><span>03</span> Czas na pokazywanie</h2></div><p class="muted">Od 15 do 300 sekund na turę.</p><label for="duration">Sekundy</label> <input id="duration" type="number" min="15" max="300" step="1" value="60" required><p class="muted">Sygnał końca tury włącza się po kliknięciu START. Zostaw stronę otwartą i ustaw głośność telefonu.</p><button class="secondary" id="test-sound" type="button">Sprawdź dźwięk <span>♪</span></button></div>
<button class="primary" id="new-game">Nowa gra <span>↗</span></button><p class="footnote">kalambury · drużyny · wyścig z czasem</p>
</section>
<section id="ready" class="play-screen centered" hidden><div class="big-symbol">🎭</div><span class="eyebrow" id="ready-round"></span><h1 id="ready-team"></h1><p>Wybierzcie osobę, która pokazuje, i podajcie jej telefon.<br>Reszta drużyny zgaduje — bez podglądania!</p><p id="ready-time"></p><button class="primary" id="start">START <span>▶</span></button><button class="secondary edit-room">Ustawienia <span>↩</span></button></section>
<section id="running" class="play-screen" hidden><div class="play-heading"><span class="eyebrow" id="active-team"></span><div class="countdown" id="clock" role="timer">01:00</div><p>Pokazuj bez słów. Drużyna zgaduje!</p></div><div class="secret-card revealed word-card"><span><span class="eyebrow">TWOJE HASŁO</span><strong class="secret-word" id="word"></strong></span></div><div class="play-bottom"><button class="primary" id="next">Następne — zgadnięte! <span>＋1</span></button><p class="footnote" id="round-points"></p></div></section>
<section id="finished" class="play-screen centered" hidden><div class="big-symbol">⏰</div><span class="eyebrow">KONIEC CZASU</span><h1>Czas minął<span class="lime">!</span></h1><p id="result" role="status"></p><div class="panel scoreboard"><h2>Wyniki drużyn</h2><ol id="scores"></ol></div><button class="primary" id="advance">Kolejna drużyna <span>→</span></button><button class="secondary edit-room">Powrót do pokoju <span>↩</span></button><p class="footnote">W następnej turze zmieńcie osobę pokazującą.</p></section>
<footer>MAŁA GRA. WIELKIE EMOCJE. <span>✦</span></footer>
</main>
  <dialog id="support-dialog" aria-labelledby="support-title">
    <form method="dialog" class="dialog-close"><button aria-label="Zamknij">×</button></form>
    <h2 id="support-title">Postaw autorowi kawę ☕</h2>
    <p>Podoba Ci się gra? Możesz wesprzeć autora dowolną kwotą. Dzięki za każdą kawę! 💜</p>
    <p>W aplikacji swojego banku wybierz <strong>przelew na telefon BLIK</strong> i wpisz numer:</p>
    <p class="blik-number">692 793 726</p>
    <button class="primary" id="copy-blik" type="button">Kopiuj numer <span aria-hidden="true">⧉</span></button>
    <p id="copy-status" role="status" aria-live="polite"></p>
  </dialog>
</body>
</html>
