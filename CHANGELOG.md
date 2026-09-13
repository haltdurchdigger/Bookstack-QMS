# Changelog

## 1.0.0 (2026-06-10)

- Erste Version, basierend auf hassio-addons/addon-bookstack v4.0.2
  (BookStack v25.11.3)
- QMS-Freigabe-Workflow als BookStack-Theme „qms-audit“:
  - Automatischer Status „In Prüfung“ bei Erstellung/Änderung
  - Rolle „Freigeber“ mit Freigeben/Zurückweisen (4-Augen-Prinzip)
  - Status-Banner auf jeder Seite
  - Freigabe-Protokoll unter `/qms/protokoll`
- Standard: `APP_LANG=de`, `APP_TIMEZONE=Europe/Berlin`
