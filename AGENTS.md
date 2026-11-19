# AGENTS.md

## Verbindlicher Einstieg

Vor jeder Entwicklungs-, Architektur-, Dokumentations-, Projekt- oder Git-Aufgabe:

1. Lies vollständig:
   `_docs/Persoenliche_Entwicklungsanweisung_Projektneutral_v2.txt`

2. Lies anschließend:
   `_docs/ai_context/00_BOOTSTRAP.md`

3. Befolge die dort definierte Lesereihenfolge vollständig.

4. Lies `_docs/ai_context/archiv/` nur auf ausdrückliche Anweisung.

5. Prüfe danach die aktuellen Projektstatus- und Reset-Dokumente sowie den tatsächlichen Code und die relevanten Tests.

## Arbeitsregeln

- Erst analysieren, dann ändern.
- Atomar und domänenorientiert arbeiten.
- Code ist die Quelle des implementierten Ist-Stands.
- Tests sind zusätzlich lebende Spezifikation.
- Dokumentation gehört zur Definition of Done.
- Kritische Entscheidungen benötigen ausdrückliche Bestätigung.
- Vor Commit immer anhalten.
- Kein blindes `git add .`.
- Kein Commit oder Push ohne ausdrückliche Freigabe.

## Aktueller Arbeitsmodus

Der Dokumentations-Reset ist abgeschlossen. Lobby-Review und Umbau nach Zielbild v3 (Desktop und mobil) sind umgesetzt (Stand 2026-11-19).

Der nächste Schwerpunkt sind die Lücken im „Drumherum“ vor der GameEngine (`docs/reset_dokumentation/99_OFFENE_PUNKTE.md`). Führungsregel beim Spielstart und Preispool-Wallet betreffen Geld und benötigen vor der Umsetzung eine ausdrückliche Bestätigung.

Hosting, Umgebungen, Deployment und die Spielserver-Architektur (Node.js, Variante B) sind in `docs/HOSTING_UND_BETRIEB.md` festgehalten. Keine Zugangsdaten in Dokumentation oder Repository.
