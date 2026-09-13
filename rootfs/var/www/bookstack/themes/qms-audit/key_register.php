<?php
/**
 * QMS Schlüsselliste – Erweiterung für das qms-audit-Theme
 * -----------------------------------------------------------------------------
 * Fügt eine Schlüssel-Ausgabeliste mit Touch-Unterschrift hinzu. Nutzt das
 * "Logical Theme System" von BookStack – der BookStack-Kern wird NICHT verändert.
 *
 * Routen:
 *   GET  /qms/schluesselliste                    Übersicht + Erfassungsformular
 *   POST /qms/schluesselliste                    Neue Ausgabe speichern
 *   POST /qms/schluesselliste/{id}/rueckgabe     Rückgabe eintragen
 *   GET  /qms/schluesselliste/app.js             JS (same-origin, CSP-sicher)
 *   GET  /qms/schluesselliste/app.css            CSS (same-origin, CSP-sicher)
 *
 * Speicherung: Tabelle qms_key_register in derselben MariaDB.
 * Bestehende QMS-Tabellen (qms_page_status, qms_audit_log) werden NICHT angefasst.
 *
 * Einbindung: in der functions.php des Themes einmalig hinzufügen:
 *     require_once __DIR__ . '/key_register.php';
 */

use BookStack\Facades\Theme;
use BookStack\Theming\ThemeEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;

/* ---------------------------------------------------------------------------
 * Tabelle bei Bedarf anlegen (lazy – wird beim ersten Seitenaufruf ausgeführt)
 * ------------------------------------------------------------------------- */
if (!function_exists('qms_key_ensure_table')) {
    function qms_key_ensure_table(): void
    {
        if (Schema::hasTable('qms_key_register')) {
            return;
        }
        Schema::create('qms_key_register', function (Blueprint $t) {
            $t->increments('id');
            $t->string('key_label');              // Schlüssel-Bezeichnung / Nr.
            $t->string('holder_name');            // Name der aufnehmenden Person
            $t->unsignedInteger('user_id')->nullable();   // erfassender BookStack-Benutzer
            $t->string('recorded_by')->nullable();        // Name des Erfassers (Audit)
            $t->date('issued_at');                // Ausgabedatum
            $t->date('returned_at')->nullable();  // Rückgabedatum (leer = ausgegeben)
            $t->longText('signature_png');        // Unterschrift als PNG-Data-URL
            $t->text('note')->nullable();         // Bemerkung
            $t->timestamps();
        });
    }
}

/* ---------------------------------------------------------------------------
 * Hilfsfunktionen
 * ------------------------------------------------------------------------- */
if (!function_exists('qms_key_valid_signature')) {
    function qms_key_valid_signature($sig): bool
    {
        if (!is_string($sig) || strlen($sig) > 2_000_000) {
            return false;
        }
        return (bool) preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $sig);
    }
}

/* ---------------------------------------------------------------------------
 * Übersichtsseite + Formular
 * ------------------------------------------------------------------------- */
if (!function_exists('qms_key_index')) {
    function qms_key_index(Request $request)
    {
        qms_key_ensure_table();

        $rows = DB::table('qms_key_register')
            ->orderByRaw('returned_at IS NOT NULL')   // offene zuerst
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();

        $flash = '';
        if ($request->query('ok'))  { $flash = '<div class="qms-flash ok">Eintrag gespeichert.</div>'; }
        if ($request->query('ret')) { $flash = '<div class="qms-flash ok">Rückgabe eingetragen.</div>'; }
        if ($request->query('err')) { $flash = '<div class="qms-flash err">Bitte alle Pflichtfelder ausfüllen und unterschreiben.</div>'; }

        $tableRows = '';
        foreach ($rows as $r) {
            $status = $r->returned_at
                ? '<span class="qms-badge back">zurückgegeben</span>'
                : '<span class="qms-badge out">ausgegeben</span>';

            $returnCell = $r->returned_at
                ? e($r->returned_at)
                : '<form method="POST" action="/qms/schluesselliste/' . (int) $r->id . '/rueckgabe" class="qms-return-form">'
                    . csrf_field()
                    . '<button class="qms-btn small" type="submit">Rückgabe</button></form>';

            $sig = qms_key_valid_signature($r->signature_png)
                ? '<img class="qms-sig" src="' . $r->signature_png . '" alt="Unterschrift">'
                : '<em>–</em>';

            $tableRows .= '<tr>'
                . '<td>' . e($r->key_label) . '</td>'
                . '<td>' . e($r->holder_name) . '</td>'
                . '<td>' . e($r->issued_at) . '</td>'
                . '<td>' . $returnCell . '</td>'
                . '<td>' . $sig . '</td>'
                . '<td>' . ($r->note ? e($r->note) : '') . '</td>'
                . '<td>' . $status . '</td>'
                . '<td class="qms-muted">' . e($r->recorded_by ?? '') . '</td>'
                . '</tr>';
        }
        if ($tableRows === '') {
            $tableRows = '<tr><td colspan="8" class="qms-muted">Noch keine Einträge.</td></tr>';
        }

        $today = date('Y-m-d');
        $csrf  = csrf_field();

        // BookStack nutzt eine strenge CSP (script-src ... 'strict-dynamic').
        // Eigene Skripte laufen nur mit dem serverseitig erzeugten Nonce.
        $nonce = '';
        try {
            if (class_exists(\BookStack\Util\CspService::class)) {
                $nonce = app(\BookStack\Util\CspService::class)->getNonce();
            }
        } catch (\Throwable $e) {
            $nonce = '';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>QMS – Schlüsselliste</title>
<link rel="stylesheet" href="/qms/schluesselliste/app.css">
</head>
<body>
<div class="qms-wrap">
  <div class="qms-head">
    <h1>Schlüsselliste Betriebsräume</h1>
    <a class="qms-link" href="/">&larr; Zurück zu BookStack</a>
  </div>
  {$flash}

  <div class="qms-card">
    <h2>Schlüssel ausgeben / aufnehmen</h2>
    <form id="keyform" method="POST" action="/qms/schluesselliste">
      {$csrf}
      <div class="qms-grid">
        <label>Schlüssel-Bezeichnung / Nr. *
          <input type="text" name="key_label" required maxlength="190" placeholder="z. B. Haupteingang / Schlüssel 3">
        </label>
        <label>Name der Person *
          <input type="text" name="holder_name" required maxlength="190" placeholder="Vor- und Nachname">
        </label>
        <label>Ausgabedatum *
          <input type="date" name="issued_at" value="{$today}" required>
        </label>
        <label>Bemerkung
          <input type="text" name="note" maxlength="250" placeholder="optional">
        </label>
      </div>

      <div class="qms-sigblock">
        <div class="qms-sighead">
          <span>Unterschrift (mit Finger/Stift auf dem Touch-Display) *</span>
          <button type="button" id="sigclear" class="qms-btn ghost small">Löschen</button>
        </div>
        <canvas id="sigpad"></canvas>
        <input type="hidden" id="signature" name="signature" value="">
      </div>

      <button class="qms-btn" type="submit">Speichern</button>
    </form>
  </div>

  <div class="qms-card">
    <h2>Übersicht</h2>
    <div class="qms-tablewrap">
      <table class="qms-table">
        <thead>
          <tr>
            <th>Schlüssel</th><th>Person</th><th>Ausgabe</th><th>Rückgabe</th>
            <th>Unterschrift</th><th>Bemerkung</th><th>Status</th><th>Erfasst von</th>
          </tr>
        </thead>
        <tbody>
          {$tableRows}
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="/qms/schluesselliste/app.js" nonce="{$nonce}"></script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}

/* ---------------------------------------------------------------------------
 * Neue Ausgabe speichern
 * ------------------------------------------------------------------------- */
if (!function_exists('qms_key_store')) {
    function qms_key_store(Request $request)
    {
        qms_key_ensure_table();

        $key    = trim((string) $request->input('key_label', ''));
        $holder = trim((string) $request->input('holder_name', ''));
        $issued = $request->input('issued_at') ?: date('Y-m-d');
        $note   = trim((string) $request->input('note', ''));
        $sig    = (string) $request->input('signature', '');

        if ($key === '' || $holder === '' || !qms_key_valid_signature($sig)) {
            return redirect('/qms/schluesselliste?err=1');
        }

        $user = auth()->user();

        DB::table('qms_key_register')->insert([
            'key_label'     => mb_substr($key, 0, 190),
            'holder_name'   => mb_substr($holder, 0, 190),
            'user_id'       => $user->id ?? null,
            'recorded_by'   => $user->name ?? null,
            'issued_at'     => $issued,
            'returned_at'   => null,
            'signature_png' => $sig,
            'note'          => $note !== '' ? mb_substr($note, 0, 250) : null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect('/qms/schluesselliste?ok=1');
    }
}

/* ---------------------------------------------------------------------------
 * Rückgabe eintragen
 * ------------------------------------------------------------------------- */
if (!function_exists('qms_key_return')) {
    function qms_key_return(Request $request, $id)
    {
        qms_key_ensure_table();

        DB::table('qms_key_register')
            ->where('id', (int) $id)
            ->whereNull('returned_at')
            ->update(['returned_at' => date('Y-m-d'), 'updated_at' => now()]);

        return redirect('/qms/schluesselliste?ret=1');
    }
}

/* ---------------------------------------------------------------------------
 * Statische Assets (same-origin -> von der CSP als 'self' erlaubt)
 * ------------------------------------------------------------------------- */
if (!function_exists('qms_key_js')) {
    function qms_key_js(): string
    {
        return <<<'JS'
(function () {
  var canvas = document.getElementById('sigpad');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var dirty = false, drawing = false, last = null;

  function resize() {
    var ratio = window.devicePixelRatio || 1;
    var rect = canvas.getBoundingClientRect();
    canvas.width = Math.round(rect.width * ratio);
    canvas.height = Math.round(rect.height * ratio);
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#14213d';
  }
  window.addEventListener('resize', resize);
  resize();

  function pos(e) {
    var rect = canvas.getBoundingClientRect();
    var p = (e.touches && e.touches[0]) ? e.touches[0] : e;
    return { x: p.clientX - rect.left, y: p.clientY - rect.top };
  }
  function start(e) { drawing = true; last = pos(e); e.preventDefault(); }
  function move(e) {
    if (!drawing) return;
    var p = pos(e);
    ctx.beginPath();
    ctx.moveTo(last.x, last.y);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last = p; dirty = true;
    e.preventDefault();
  }
  function end() { drawing = false; }

  canvas.addEventListener('mousedown', start);
  canvas.addEventListener('mousemove', move);
  window.addEventListener('mouseup', end);
  canvas.addEventListener('touchstart', start, { passive: false });
  canvas.addEventListener('touchmove', move, { passive: false });
  canvas.addEventListener('touchend', end);

  var clearBtn = document.getElementById('sigclear');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      dirty = false;
    });
  }

  var form = document.getElementById('keyform');
  if (form) {
    form.addEventListener('submit', function (e) {
      if (!dirty) {
        e.preventDefault();
        alert('Bitte zuerst unterschreiben.');
        return;
      }
      document.getElementById('signature').value = canvas.toDataURL('image/png');
    });
  }

  var returnForms = document.querySelectorAll('.qms-return-form');
  for (var i = 0; i < returnForms.length; i++) {
    returnForms[i].addEventListener('submit', function (e) {
      if (!confirm('Rückgabe dieses Schlüssels eintragen?')) {
        e.preventDefault();
      }
    });
  }
})();
JS;
    }
}

if (!function_exists('qms_key_css')) {
    function qms_key_css(): string
    {
        return <<<'CSS'
* { box-sizing: border-box; }
body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: #1f2933;
  background: #f4f6f9;
}
.qms-wrap { max-width: 1000px; margin: 0 auto; padding: 24px 16px 60px; }
.qms-head { display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.qms-head h1 { font-size: 22px; margin: 0 0 4px; }
.qms-link { color: #206bc4; text-decoration: none; font-size: 14px; }
.qms-link:hover { text-decoration: underline; }
.qms-card {
  background: #fff; border: 1px solid #e3e8ee; border-radius: 10px;
  padding: 18px 18px 22px; margin-top: 18px;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
}
.qms-card h2 { font-size: 16px; margin: 0 0 14px; }
.qms-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; }
@media (max-width: 640px) { .qms-grid { grid-template-columns: 1fr; } }
.qms-grid label { display: flex; flex-direction: column; font-size: 13px; font-weight: 600; color: #52606d; gap: 6px; }
.qms-grid input {
  font-size: 15px; padding: 9px 10px; border: 1px solid #cbd2d9;
  border-radius: 7px; background: #fff; font-weight: 400; color: #1f2933;
}
.qms-grid input:focus { outline: none; border-color: #206bc4; box-shadow: 0 0 0 3px rgba(32,107,196,.15); }
.qms-sigblock { margin-top: 16px; }
.qms-sighead { display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 600; color: #52606d; margin-bottom: 6px; }
#sigpad {
  width: 100%; height: 190px; border: 2px dashed #cbd2d9; border-radius: 8px;
  background: #fff; touch-action: none; display: block;
}
.qms-btn {
  margin-top: 16px; background: #206bc4; color: #fff; border: 0;
  padding: 10px 18px; border-radius: 7px; font-size: 15px; font-weight: 600; cursor: pointer;
}
.qms-btn:hover { background: #1a5aa6; }
.qms-btn.small { margin: 0; padding: 6px 12px; font-size: 13px; }
.qms-return-form { margin: 0; }
.qms-btn.ghost { background: #eef2f6; color: #334; }
.qms-btn.ghost:hover { background: #e2e8f0; }
.qms-tablewrap { overflow-x: auto; }
.qms-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.qms-table th, .qms-table td { text-align: left; padding: 9px 10px; border-bottom: 1px solid #eef2f6; vertical-align: middle; }
.qms-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .03em; color: #7b8794; }
.qms-table tbody tr:hover { background: #f8fafc; }
.qms-sig { height: 40px; max-width: 160px; background: #fff; border: 1px solid #eef2f6; border-radius: 4px; }
.qms-muted { color: #9aa5b1; }
.qms-badge { font-size: 12px; font-weight: 700; padding: 3px 9px; border-radius: 20px; white-space: nowrap; }
.qms-badge.out { background: #fff4e0; color: #b45309; }
.qms-badge.back { background: #e3f6e8; color: #1a7f37; }
.qms-flash { margin-top: 16px; padding: 10px 14px; border-radius: 8px; font-size: 14px; }
.qms-flash.ok { background: #e3f6e8; color: #1a7f37; border: 1px solid #b7e3c3; }
.qms-flash.err { background: #fdeaea; color: #b42318; border: 1px solid #f3c0c0; }
CSS;
    }
}

/* ---------------------------------------------------------------------------
 * Routen registrieren (logisches Theme-System, seit BookStack v23.12)
 *  - ROUTES_REGISTER_WEB_AUTH: Seiten, die eine Anmeldung erfordern
 *    (Session + Auth-Middleware werden von BookStack korrekt gesetzt)
 *  - ROUTES_REGISTER_WEB: öffentlich (hier nur die statischen Assets)
 * ------------------------------------------------------------------------- */
Theme::listen(ThemeEvents::ROUTES_REGISTER_WEB_AUTH, function ($router) {
    $router->get('/qms/schluesselliste', function (Request $request) {
        return qms_key_index($request);
    });

    $router->post('/qms/schluesselliste', function (Request $request) {
        return qms_key_store($request);
    });

    $router->post('/qms/schluesselliste/{id}/rueckgabe', function (Request $request, $id) {
        return qms_key_return($request, $id);
    });
});

Theme::listen(ThemeEvents::ROUTES_REGISTER_WEB, function ($router) {
    $router->get('/qms/schluesselliste/app.js', function () {
        return response(qms_key_js(), 200, ['Content-Type' => 'application/javascript; charset=utf-8']);
    });

    $router->get('/qms/schluesselliste/app.css', function () {
        return response(qms_key_css(), 200, ['Content-Type' => 'text/css; charset=utf-8']);
    });
});
