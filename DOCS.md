# BookStack QMS – Documentation / Dokumentation

[English](#english) · [Deutsch](#deutsch)

---

## English

# BookStack QMS – BookStack with an approval workflow (four-eyes principle)

This add-on is based entirely on the official Home Assistant Community Add-on
[BookStack v4.0.2](https://github.com/hassio-addons/addon-bookstack/tree/v4.0.2)
(BookStack v25.11.3) and adds a **QMS approval workflow** for the standard
operating procedures (SOPs) of your pharmacy or organisation:

- Every **newly created or edited page** is automatically set to the status
  **"In Prüfung"** (under review, yellow banner at the top of the page).
- Only members of the role **"Freigeber"** (approver) or administrators see the
  buttons **"Freigeben"** (approve) and **"Zurückweisen"** (reject).
- **Four-eyes principle:** nobody can approve their own change, not even
  administrators. This is enforced server-side.
- A **rejection** requires a reason; it is shown to the author in the red banner.
- Approved SOPs show a **green banner** with the approver's name and the time of approval.
- All events (created, changed, approved, rejected, deleted) are stored in an
  **audit log**: `http://<your-address>:2665/qms/protokoll`
  (also reachable via the link "Freigabe-Protokoll ansehen" in every banner).

The workflow user interface is in **German**. In addition, `APP_LANG=de` and
`APP_TIMEZONE=Europe/Berlin` are set as defaults; both can be overridden.

---

### 1. Installation

1. Copy the complete folder `bookstack_qms` into the `/addons` folder of your
   Home Assistant installation (easiest via the **Samba add-on**, share
   "addons", or via SSH).
2. In Home Assistant open **Settings → Add-ons → Add-on Store**.
3. Click **⋮ → Check for updates** in the top right corner and reload the page.
4. Under **"Local add-ons"** the add-on **"BookStack QMS (mit Freigabe-Workflow)"**
   appears → install it. (The image is built locally; this takes a few minutes.)

> **Note:** During the build the add-on pulls the official BookStack image
> `ghcr.io/hassio-addons/bookstack/...:4.0.2` and only adds the QMS theme on
> top. The BookStack core is not modified, so your data stays compatible.

### 2. Migrating from the official BookStack add-on

**Important: create a full backup first (Home Assistant full backup)!**

Both add-ons use the same MariaDB database `bookstack`. Your books, pages and
users are therefore available again automatically after the migration. Three
things need to be transferred:

#### a) Take over the app key

1. In the **old** add-on set the option `show_appkey: true`, restart it and
   note the app key from the log (format `base64:...`).
2. Enter this key in the **new** add-on as the option `appkey` (it is removed
   from the configuration automatically after the first start).

#### b) Copy uploaded files and images

The images of your SOPs are stored in the data folder of the old add-on. Copy
them via SSH (e.g. the add-on "Advanced SSH & Web Terminal" with protection
mode disabled), **after the new add-on has been started once**:

```bash
cp -a /usr/share/hassio/addons/data/a0d7b954_bookstack/bookstack/. \
      /usr/share/hassio/addons/data/local_bookstack_qms/bookstack/
```

Then restart the new add-on.

#### c) Stop the old add-on

Both add-ons use port **2665**. Stop the old add-on (and disable its autostart)
before starting the new one. Only uninstall the old add-on once everything has
been verified.

### 3. Setting up the "Freigeber" (approver) role

On the first visit to the web interface the add-on automatically creates:

- the role **"Freigeber"** (under *Settings → Roles* in BookStack),
- the database tables `qms_page_status` and `qms_audit_log`.

Then, in BookStack under **Settings → Users**, assign the role **"Freigeber"**
to everyone allowed to approve SOPs (e.g. pharmacy management, QMS officer).
The role does not need any special permissions; it only marks users for the
approval function.

### 4. Daily workflow

1. An employee creates or edits an SOP → the status is automatically set to
   **"In Prüfung"** (yellow).
2. Another person with the "Freigeber" role opens the page, reviews it and
   clicks **"Freigeben"** (green) or **"Zurückweisen"** with a reason (red).
3. After a rejection the author revises the SOP; saving it sets the status back
   to "In Prüfung" automatically.
4. If an already approved SOP is edited later, it loses its approval and must
   be reviewed again.
5. Existing pages that were last edited before the installation show a grey
   banner "Kein QMS-Status" (no QMS status) and can be approved in bulk by an
   approver.

The complete log for internal audits is available at any time at `/qms/protokoll`.

### 5. Configuration

The options are identical to the original add-on (`log_level`, `ssl`,
`certfile`, `keyfile`, `remote_mysql_*`, `show_appkey`, `appkey`, `envvars`).
Details: see the [original documentation](https://github.com/hassio-addons/addon-bookstack/blob/v4.0.2/bookstack/DOCS.md).

Additional default environment variables (can be overridden via `envvars`):

| Variable       | Default         | Purpose                        |
| -------------- | --------------- | ------------------------------ |
| `APP_THEME`    | `qms-audit`     | Enables the approval workflow  |
| `APP_LANG`     | `de`            | German default language        |
| `APP_TIMEZONE` | `Europe/Berlin` | Correct timestamps             |

> `APP_THEME` must not be overridden, otherwise the approval workflow is disabled.

### 6. Known limitations

- As with the original, **Ingress does not work**; access is via port 2665.
- The banner appears in the normal page view (not in PDF exports).
- The status "In Prüfung" does **not hide** the page: all staff can still read
  it, but see clearly that it has not been approved yet.
- When the original add-on is updated later (e.g. v4.1.x), the version number
  in `build.yaml` must be adjusted and the theme checked against the new
  BookStack version.

### 7. Disclaimer

This add-on is a private extension and is not affiliated with the developers of
BookStack or the Home Assistant Community Add-ons. Check for yourself whether
the function meets the requirements of your QMS (e.g. the German
Apothekenbetriebsordnung). License: MIT (as the original).

---

## Deutsch

# BookStack QMS – BookStack mit Freigabe-Workflow (4-Augen-Prinzip)

Dieses Add-on basiert vollständig auf dem offiziellen Home-Assistant-Community-Add-on
[Bookstack v4.0.2](https://github.com/hassio-addons/addon-bookstack/tree/v4.0.2)
(BookStack v25.11.3) und ergänzt es um eine **QMS-Freigabefunktion** für die
Standardarbeitsanweisungen (SOPs) Ihrer Apotheke:

- Jede **neu erstellte oder geänderte Seite** erhält automatisch den Status
  **„In Prüfung“** (gelbes Banner oben auf der Seite).
- Nur Mitglieder der Rolle **„Freigeber“** (oder Administratoren) sehen die
  Schaltflächen **„Freigeben“** und **„Zurückweisen“**.
- **4-Augen-Prinzip:** Niemand kann die eigene Änderung selbst freigeben – auch
  Administratoren nicht. Das wird serverseitig erzwungen.
- Bei einer **Zurückweisung** ist eine Begründung Pflicht; sie wird der
  Autorin/dem Autor im roten Banner angezeigt.
- Freigegebene SOPs zeigen ein **grünes Banner** mit Name und Zeitpunkt der Freigabe.
- Alle Vorgänge (Erstellt, Geändert, Freigegeben, Zurückgewiesen, Gelöscht)
  werden in einem **Freigabe-Protokoll** gespeichert:
  `http://<ihre-adresse>:2665/qms/protokoll`
  (auch über den Link „Freigabe-Protokoll ansehen“ in jedem Banner erreichbar).

Die gesamte Oberfläche der Freigabefunktion ist auf **Deutsch**. Außerdem sind
`APP_LANG=de` und `APP_TIMEZONE=Europe/Berlin` als Standard gesetzt.

---

## 1. Installation

1. Kopieren Sie den kompletten Ordner `bookstack_qms` in den Ordner `/addons`
   Ihrer Home-Assistant-Installation (am einfachsten über das
   **Samba-Add-on**, Freigabe „addons“, oder per SSH).
2. Öffnen Sie in Home Assistant: **Einstellungen → Add-ons → Add-on Store**.
3. Klicken Sie oben rechts auf **⋮ → Nach Updates suchen** und laden Sie die
   Seite neu.
4. Unter **„Lokale Add-ons“** erscheint **„BookStack QMS (mit Freigabe-Workflow)“**
   → installieren. (Das Image wird lokal gebaut; das dauert wenige Minuten.)

> **Hinweis:** Das Add-on lädt beim Bau das offizielle BookStack-Image
> `ghcr.io/hassio-addons/bookstack/...:4.0.2` und legt nur das QMS-Theme
> darüber. Am BookStack-Kern wird nichts verändert – Ihre Daten bleiben
> kompatibel.

## 2. Umstieg vom bisherigen Bookstack-Add-on

**Wichtig: Erstellen Sie zuerst ein vollständiges Backup (Home Assistant
Vollsicherung)!**

Beide Add-ons nutzen dieselbe MariaDB-Datenbank `bookstack`. Ihre Bücher,
Seiten und Benutzer sind nach dem Umstieg also automatisch wieder da. Drei
Dinge müssen Sie übertragen:

### a) App-Key übernehmen

1. Setzen Sie im **alten** Add-on die Option `show_appkey: true`, starten Sie
   es neu und notieren Sie den App-Key aus dem Protokoll
   (Format `base64:...`).
2. Tragen Sie diesen Key im **neuen** Add-on als Option `appkey` ein (er wird
   nach dem ersten Start automatisch aus der Konfiguration entfernt).

### b) Hochgeladene Dateien/Bilder kopieren

Die Bilder Ihrer SOPs liegen im Datenordner des alten Add-ons. Kopieren Sie
sie per SSH (z. B. Add-on „Advanced SSH & Web Terminal“ mit deaktiviertem
Schutzmodus), **nachdem das neue Add-on einmal gestartet wurde**:

```bash
cp -a /usr/share/hassio/addons/data/a0d7b954_bookstack/bookstack/. \
      /usr/share/hassio/addons/data/local_bookstack_qms/bookstack/
```

Danach das neue Add-on neu starten.

### c) Altes Add-on stoppen

Beide Add-ons verwenden Port **2665**. Stoppen Sie das alte Add-on (und
deaktivieren Sie dessen Autostart), bevor Sie das neue starten. Das alte
Add-on erst dann deinstallieren, wenn alles geprüft ist.

## 3. Einrichtung der Rolle „Freigeber“

Beim ersten Aufruf der Weboberfläche legt das Add-on automatisch an:

- die Rolle **„Freigeber“** (unter *Einstellungen → Rollen* in BookStack),
- die Datenbanktabellen `qms_page_status` und `qms_audit_log`.

Weisen Sie anschließend in BookStack unter **Einstellungen → Benutzer** allen
Personen, die SOPs freigeben dürfen (z. B. Apothekenleitung, QMS-Beauftragte),
die Rolle **„Freigeber“** zu. Die Rolle benötigt keine besonderen
Berechtigungen – sie dient nur als Kennzeichnung für die Freigabefunktion.

## 4. Arbeitsablauf im Alltag

1. Eine Mitarbeiterin erstellt oder ändert eine SOP → Status wird automatisch
   **„In Prüfung“** (gelb).
2. Eine andere Person mit der Rolle „Freigeber“ öffnet die Seite, prüft sie und
   klickt **„Freigeben“** (grün) oder **„Zurückweisen“** mit Begründung (rot).
3. Nach einer Zurückweisung überarbeitet die Autorin die SOP; mit dem Speichern
   wechselt der Status automatisch wieder auf „In Prüfung“.
4. Wird eine bereits freigegebene SOP später geändert, verliert sie die
   Freigabe und muss erneut geprüft werden.
5. Bestandsseiten, die vor der Installation zuletzt geändert wurden, zeigen
   ein graues Banner „Kein QMS-Status“ und können von einem Freigeber pauschal
   freigegeben werden.

Das vollständige Protokoll für interne Audits finden Sie jederzeit unter
`/qms/protokoll`.

## 5. Konfiguration

Die Optionen sind identisch mit dem Original-Add-on (`log_level`, `ssl`,
`certfile`, `keyfile`, `remote_mysql_*`, `show_appkey`, `appkey`, `envvars`).
Details: siehe [Original-Dokumentation](https://github.com/hassio-addons/addon-bookstack/blob/v4.0.2/bookstack/DOCS.md).

Zusätzlich gesetzte Standard-Umgebungsvariablen (per `envvars` übersteuerbar):

| Variable       | Standardwert    | Zweck                          |
| -------------- | --------------- | ------------------------------ |
| `APP_THEME`    | `qms-audit`     | Aktiviert die Freigabefunktion |
| `APP_LANG`     | `de`            | Deutsche Standardsprache       |
| `APP_TIMEZONE` | `Europe/Berlin` | Korrekte Zeitstempel           |

> `APP_THEME` darf nicht überschrieben werden, sonst ist die
> Freigabefunktion deaktiviert.

## 6. Bekannte Einschränkungen

- Wie beim Original funktioniert **kein Ingress**; der Zugriff erfolgt über
  Port 2665.
- Das Banner erscheint in der normalen Seitenansicht (nicht in PDF-Exporten).
- Der Status „In Prüfung“ blendet die Seite **nicht aus** – alle Mitarbeitenden
  können sie weiterhin lesen, sehen aber deutlich, dass sie noch nicht
  freigegeben ist.
- Bei einem späteren Update des Original-Add-ons (z. B. v4.1.x) muss in
  `build.yaml` die Versionsnummer angepasst und das Theme gegen die neue
  BookStack-Version geprüft werden.

## 7. Haftungsausschluss

Dieses Add-on ist eine private Erweiterung und steht in keiner Verbindung zu
den Entwicklern von BookStack oder den Home Assistant Community Add-ons.
Prüfen Sie selbst, ob die Funktion die Anforderungen Ihres QMS
(z. B. Apothekenbetriebsordnung) erfüllt. Lizenz: MIT (wie das Original).
