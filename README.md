<p align="center">
  <img src="src/Resources/public/icons/payu_logo.jpg" alt="PayU" height="120">
</p>

<h1 align="center">PayU dla Shopware 6 — by CREHLER</h1>

<p align="center">
  Integracja bramki płatniczej <strong>PayU</strong> ze sklepem <strong>Shopware 6</strong>.<br>
  BLIK (w tym Level&nbsp;0), płatność kartą, pay-by-link, e-portfele, płatności odroczone (raty) oraz zwroty z panelu administratora.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Shopware-6.6%20%7C%206.7-189EFF?logo=shopware&logoColor=white" alt="Shopware 6.6 | 6.7">
  <img src="https://img.shields.io/badge/PHP-8.2%20–%208.5-777BB4?logo=php&logoColor=white" alt="PHP 8.2 – 8.5">
  <img src="https://img.shields.io/badge/wersja-6.0-success" alt="Wersja 6.0">
  <img src="https://img.shields.io/badge/by-CREHLER-ff5c00" alt="by CREHLER">
</p>

---

## Czym jest Shopware?

[Shopware 6](https://www.shopware.com/) to nowoczesna platforma e-commerce open source, na której działają tysiące sklepów internetowych w Europie. Daje pełną kontrolę nad wyglądem sklepu, procesem zakupowym i integracjami, a jej modułowa architektura pozwala rozszerzać sklep o wtyczki — takie jak ta integracja PayU.

Shopware działa też w modelu **headless** — całą logikę sklepu udostępnia przez **Store API**, dzięki czemu warstwę zakupową można zbudować na dowolnym froncie (np. aplikacja Nuxt/PWA, aplikacja mobilna), niezależnie od wbudowanego Storefrontu. **Ta wtyczka obsługuje oba światy** — działa zarówno w klasycznym Storefroncie, jak i w pełni przez Store API (w tym dedykowany endpoint dla BLIK Level 0), więc sprawdzi się także w sklepach headless.

## O wtyczce

**Bramka płatności PayU by CREHLER** podłącza bramkę płatniczą [PayU](https://www.payu.pl/) — jednego z wiodących operatorów płatności online w Polsce i regionie — do Twojego sklepu Shopware 6. Klient płaci tak, jak lubi — kodem BLIK bez wychodzenia ze sklepu, kartą, szybkim przelewem pay-by-link, e-portfelem albo na raty — a sklep automatycznie otrzymuje potwierdzenie płatności i aktualizuje status zamówienia. Zwroty wykonasz jednym kliknięciem z panelu Shopware.

## ✨ Funkcje

- 🟢 **BLIK Level 0** — klient wpisuje 6-cyfrowy kod BLIK bezpośrednio w sklepie i płaci **bez przekierowania** do bramki.
- 💳 **Płatność kartą** — z przekierowaniem na **bezpieczną stronę PayU** (z obsługą **3-D Secure**). Dane karty wpisywane są po stronie PayU i **nie trafiają na serwer sklepu** — najniższy zakres wymogów PCI DSS (typowo SAQ A).
- 🔁 **Zapisane karty (tokeny)** — klient może zapisać kartę do kolejnych zakupów; płatność finalizuje się tokenem po stronie PayU.
- 🏦 **Pay-by-link (przelewy bankowe)** — klient wybiera swój bank z listy i płaci szybkim przelewem online; **wybrany bank zostaje zapamiętany**, więc przy kolejnych zamówieniach płaci jednym kliknięciem.
- 👛 **E-portfele** — szybkie metody portfelowe obsługiwane przez PayU.
- 🗓️ **Płatności odroczone** — raty PayU / „Kup teraz, zapłać później".
- ↩️ **Zwroty z panelu administratora** — zwroty pieniędzy klientowi, w całości lub częściowo, bez logowania do panelu PayU.
- 🎚️ **Konfigurowalny checkout** — wybór pozycji pola BLIK (w checkout / osobna strona / ukryte) oraz wyświetlania sekcji karty.

## 💳 Metody płatności

| Metoda | Opis |
|---|---|
| **BLIK** | Płatność kodem BLIK w sklepie (Level 0) lub z przekierowaniem. |
| **Karta** | Visa / Mastercard — przekierowanie na bezpieczną stronę PayU, 3-D Secure, zapis karty. |
| **Przelew (pay-by-link)** | Wybór banku z listy i szybki przelew online. |
| **E-portfel** | Szybkie metody portfelowe PayU. |
| **Płatności odroczone** | Raty PayU / „Kup teraz, zapłać później". |

## ✅ Wymagania

| Komponent | Wersja |
|---|---|
| Shopware | 6.6.x lub 6.7.x |
| PHP | 8.2, 8.3, 8.4 lub 8.5 |
| Konto PayU | aktywne konto z danymi punktu płatności (PosId, klucz MD5, OAuth client_id / client_secret) |
| Waluta | sklep musi obsługiwać **PLN** |

## 🚀 Szybka instalacja

> To skrócony przebieg (Composer). Pełna instrukcja — **Composer oraz paczka ZIP**, krok po kroku — znajduje się w **[docs/instalacja.md](docs/instalacja.md)**.

**1. Zainstaluj wtyczkę przez Composer:**

```bash
composer require crehler/payu
```

**2. Aktywuj w Shopware:**

```bash
bin/console plugin:refresh
bin/console plugin:install --activate CrehlerPayU
bin/console cache:clear
```

**3. Uzupełnij dane w konfiguracji wtyczki** — panel admina → **Rozszerzenia → Moje rozszerzenia → Bramka płatności PayU by CREHLER → Skonfiguruj**:

- **Id punktu płatności (pos_id)**, **Drugi klucz (MD5)** oraz **Protokół OAuth - client_id / client_secret** — z panelu PayU: *konfiguracja punktu płatności (POS)*,
- na koniec kliknij **przycisk testu połączenia**, aby zweryfikować dane.

> 💡 Do testów włącz **Sandbox** i podaj dane z [testowego konta PayU](https://secure.snd.payu.com/).

📚 **[Pełna dokumentacja → `docs/`](docs/index.md)** — konfiguracja krok po kroku, metody płatności, Store API (headless), zwroty i dane testowe (sandbox) — ze zrzutami ekranu.

## 🛟 Wsparcie

Masz pytanie lub problem? Napisz do nas: **[support@crehler.com](mailto:support@crehler.com)**

---

## O CREHLER

<p align="center">
  <a href="https://crehler.com/"><strong>CREHLER</strong></a> — Twój partner w e-commerce.
</p>

Tworzymy i rozwijamy sklepy internetowe na **Shopware**, budujemy dedykowane integracje, wtyczki i headless‑owe frontendy (Nuxt). Robimy integracje **ERP**, **WMS**, **płatności** i **dostaw**, a także customowe **konfiguratory**, **kalkulatory** i inne rozszerzenia szyte na miarę Twojego sklepu.

Potrzebujesz wdrożenia, integracji albo dedykowanej funkcji w swoim sklepie? **[Porozmawiajmy → crehler.com](https://crehler.com/)**

---

## 📄 Licencja

Oprogramowanie własnościowe (proprietary). © Crehler Sp. z o.o. Wszelkie prawa zastrzeżone.
