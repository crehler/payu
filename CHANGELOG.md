# Changelog

Wszystkie istotne zmiany w **Bramka płatności PayU by CREHLER** (`crehler/payu`).

Format oparty na [Keep a Changelog](https://keepachangelog.com/pl/1.1.0/); wersjonowanie wg [SemVer](https://semver.org/lang/pl/).

## [6.0.0]

Pierwsze publiczne wydanie wtyczki jako pakiet `crehler/payu`.

### Dodane

- **BLIK Level 0** — płatność kodem BLIK bezpośrednio w sklepie, bez przekierowania (dedykowany endpoint Store API).
- **Płatność kartą** — przekierowanie na bezpieczną stronę PayU z obsługą 3-D Secure; **zapisane karty (tokeny)**.
- **Pay-by-link** — wybór banku z listy; wybrany bank zapamiętywany na kolejne zamówienia.
- **E-portfele** oraz **płatności odroczone (raty)** PayU.
- **Zwroty z panelu administratora** — pełne i częściowe, wprost z widoku zamówienia Shopware.
- **Wsparcie Store API (headless)** — BLIK Level 0, lista sub-metod (banki), sprawdzanie statusu płatności.
- **Weryfikacja powiadomień (webhooków)** podpisem opartym o drugi klucz (MD5) punktu płatności.
- Pełna dokumentacja (MkDocs): instalacja, konfiguracja, Store API, zwroty.

### Wymagania

- `crehler/payment-bundle`: `^6.0 >=6.0.2`
- `openpayu/openpayu`: `2.3.*`
- Shopware 6.6 / 6.7, PHP 8.2–8.5
