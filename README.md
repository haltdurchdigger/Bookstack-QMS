# BookStack QMS – BookStack with an approval workflow (four-eyes principle)

**Unofficial Home Assistant add-on.** Not affiliated with BookStack, the Home Assistant project or the Home Assistant Community Add-ons.

[English](#english) · [Deutsch](#deutsch)

---

## English

BookStack QMS is a Home Assistant add-on that packages [BookStack](https://www.bookstackapp.com/) (v25.11.3) and adds a **document approval workflow** for quality management. It was built to manage standard operating procedures (SOPs) in a pharmacy, but works for any organisation that needs reviewed and approved documentation.

The add-on is a thin layer on top of the official [Home Assistant Community Add-on for BookStack v4.0.2](https://github.com/hassio-addons/addon-bookstack/tree/v4.0.2). The BookStack core is untouched; the workflow is implemented as a BookStack theme (`qms-audit`), so your data stays fully compatible with BookStack.

### Features

- Every new or edited page is automatically set to **"Under review"** (yellow banner).
- A dedicated **"Approver"** role (German: *Freigeber*). Only approvers and administrators can approve or reject a page.
- **Four-eyes principle**, enforced server-side: nobody can approve their own change, not even administrators.
- Rejections require a written **reason**, shown to the author in a red banner.
- Approved pages show a **green banner** with approver name and timestamp.
- Editing an approved page removes the approval; the page must be reviewed again.
- **Audit log** of all events (created, changed, approved, rejected, deleted) at `/qms/protokoll`, suitable for internal audits.
- Configuration options identical to the official add-on (SSL, remote MySQL, app key, environment variables).

The workflow user interface is currently in **German only**. BookStack itself supports many languages via `APP_LANG`.

### Requirements

| | |
|---|---|
| Home Assistant | Home Assistant OS or Supervised (the add-on system is required; Container and Core installs cannot run add-ons) |
| Architecture | `amd64` (x86-64 mini PCs, NUCs, Home Assistant Blue/Yellow x86 variants, VMs) and `aarch64` (Raspberry Pi 4/5, Home Assistant Green, Home Assistant Yellow, ODROID) |
| Database | The **MariaDB** add-on, or an external MySQL/MariaDB server via the `remote_mysql_*` options |
| Resources | Roughly the same as the official BookStack add-on; the image is built locally on first install, which takes a few minutes |
| Network | Web UI on port **2665**; Ingress is not supported (same as the official add-on) |

### Installation

This repository currently ships the add-on as a **local add-on**:

1. Copy the whole repository folder into `/addons/bookstack_qms` on your Home Assistant host (Samba share `addons`, SSH, or the Studio Code Server add-on).
2. In Home Assistant open **Settings → Add-ons → Add-on Store**, choose **⋮ → Check for updates** and reload the page.
3. Install **"BookStack QMS (mit Freigabe-Workflow)"** from the **Local add-ons** section.
4. Start the add-on, open the web UI and assign the **"Freigeber"** role to the people allowed to approve documents.

Migration from the official BookStack add-on, role setup, daily workflow and configuration are described in [DOCS.md](DOCS.md).

### Limitations

- Workflow UI in German only.
- No Ingress; access via port 2665.
- Status banners are shown in the web view, not in PDF exports.
- Pages under review remain readable by everyone; the banner makes the status visible but does not hide the page.
- Updating to a newer BookStack release requires adjusting `build.yaml` and checking the theme against the new version.

### Disclaimer

This is a private extension. Check for yourself whether it meets the requirements of your quality management system (for example the German *Apothekenbetriebsordnung*). Provided as-is, without warranty. License: MIT (as the original add-on).

---

## Deutsch

BookStack QMS ist ein Home-Assistant-Add-on, das [BookStack](https://www.bookstackapp.com/) (v25.11.3) um einen **Freigabe-Workflow** für das Qualitätsmanagement erweitert. Entwickelt wurde es für die Verwaltung von Standardarbeitsanweisungen (SOPs) in einer Apotheke, es eignet sich aber für jede Organisation, die geprüfte und freigegebene Dokumentation braucht.

Das Add-on ist eine dünne Schicht über dem offiziellen [Home Assistant Community Add-on für BookStack v4.0.2](https://github.com/hassio-addons/addon-bookstack/tree/v4.0.2). Der BookStack-Kern bleibt unverändert; der Workflow ist als BookStack-Theme (`qms-audit`) umgesetzt, Ihre Daten bleiben also vollständig BookStack-kompatibel.

### Funktionen

- Jede neue oder geänderte Seite erhält automatisch den Status **„In Prüfung“** (gelbes Banner).
- Eigene Rolle **„Freigeber“**: Nur Freigeber und Administratoren können freigeben oder zurückweisen.
- **4-Augen-Prinzip**, serverseitig erzwungen: Niemand kann die eigene Änderung selbst freigeben, auch Administratoren nicht.
- Bei einer Zurückweisung ist eine **Begründung** Pflicht; sie wird der Autorin oder dem Autor im roten Banner angezeigt.
- Freigegebene Seiten zeigen ein **grünes Banner** mit Name und Zeitpunkt der Freigabe.
- Wird eine freigegebene Seite geändert, verliert sie die Freigabe und muss erneut geprüft werden.
- **Freigabe-Protokoll** aller Vorgänge (Erstellt, Geändert, Freigegeben, Zurückgewiesen, Gelöscht) unter `/qms/protokoll`, geeignet für interne Audits.
- Konfigurationsoptionen identisch mit dem offiziellen Add-on (SSL, externe MySQL-Datenbank, App-Key, Umgebungsvariablen).

Die Oberfläche des Workflows ist derzeit **nur auf Deutsch** verfügbar. BookStack selbst unterstützt über `APP_LANG` viele Sprachen.

### Voraussetzungen

| | |
|---|---|
| Home Assistant | Home Assistant OS oder Supervised (das Add-on-System wird benötigt; Container- und Core-Installationen können keine Add-ons ausführen) |
| Architektur | `amd64` (x86-64-Mini-PCs, NUCs, Thin Clients, virtuelle Maschinen) und `aarch64` (Raspberry Pi 4/5, Home Assistant Green, Home Assistant Yellow, ODROID) |
| Datenbank | Das **MariaDB**-Add-on oder ein externer MySQL/MariaDB-Server über die Optionen `remote_mysql_*` |
| Ressourcen | Etwa wie das offizielle BookStack-Add-on; das Image wird bei der ersten Installation lokal gebaut, das dauert wenige Minuten |
| Netzwerk | Weboberfläche auf Port **2665**; Ingress wird nicht unterstützt (wie beim offiziellen Add-on) |

### Installation

Dieses Repository liefert das Add-on derzeit als **lokales Add-on**:

1. Den kompletten Repository-Ordner nach `/addons/bookstack_qms` auf Ihrem Home-Assistant-System kopieren (Samba-Freigabe „addons“, SSH oder das Add-on Studio Code Server).
2. In Home Assistant **Einstellungen → Add-ons → Add-on Store** öffnen, **⋮ → Nach Updates suchen** wählen und die Seite neu laden.
3. Unter **„Lokale Add-ons“** das Add-on **„BookStack QMS (mit Freigabe-Workflow)“** installieren.
4. Add-on starten, Weboberfläche öffnen und die Rolle **„Freigeber“** den Personen zuweisen, die Dokumente freigeben dürfen.

Umstieg vom offiziellen BookStack-Add-on, Einrichtung der Rolle, Arbeitsablauf und Konfiguration sind in [DOCS.md](DOCS.md) beschrieben.

### Einschränkungen

- Workflow-Oberfläche nur auf Deutsch.
- Kein Ingress; Zugriff über Port 2665.
- Status-Banner erscheinen in der Webansicht, nicht in PDF-Exporten.
- Seiten „In Prüfung“ bleiben für alle lesbar; das Banner macht den Status sichtbar, blendet die Seite aber nicht aus.
- Ein Update auf eine neuere BookStack-Version erfordert eine Anpassung von `build.yaml` und eine Prüfung des Themes gegen die neue Version.

### Haftungsausschluss

Dies ist eine private Erweiterung. Prüfen Sie selbst, ob die Funktion die Anforderungen Ihres QMS (z. B. Apothekenbetriebsordnung) erfüllt. Bereitstellung ohne Gewähr. Lizenz: MIT (wie das Original-Add-on).
