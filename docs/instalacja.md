<p align="center">
  <img src="images/payu_logo.jpg" alt="PayU" height="56">
</p>

<h1 align="center">Instrukcja instalacji</h1>

<p align="center">Wtyczkę <strong>Bramka płatności PayU by CREHLER</strong> zainstalujesz na dwa sposoby: przez Composer albo z paczki ZIP.</p>

---

## Wymagania

| Komponent | Wersja |
|---|---|
| Shopware | 6.6.x lub 6.7.x |
| PHP | 8.2, 8.3, 8.4 lub 8.5 |
| Konto PayU | aktywne, z danymi punktu płatności (POS): PosId, klucz MD5 oraz dane OAuth (client_id / client_secret) |
| Waluta | sklep musi obsługiwać **PLN** |

---

## Metoda 1 — Composer (zalecana)

**1. Zainstaluj wtyczkę:**

```bash
composer require crehler/payu
```

**2. Aktywuj w Shopware:**

```bash
bin/console plugin:refresh
bin/console plugin:install --activate CrehlerPayU
bin/console cache:clear
```

**3.** Wtyczka pojawi się w **Rozszerzenia → Moje rozszerzenia** jako *„Bramka płatności PayU by CREHLER"*.

➡️ Przejdź do **[Instrukcji konfiguracji](konfiguracja.md)**.

---

## Metoda 2 — Paczka ZIP

**1.** Pobierz wtyczkę **[CrehlerPayU.zip](https://github.com/crehler/payu/releases/latest/download/CrehlerPayU.zip)**.

**2.** W **Rozszerzenia → Moje rozszerzenia** kliknij **„Prześlij rozszerzenie"**.

![Moje rozszerzenia — przycisk „Prześlij rozszerzenie"](images/inst-01-upload-button.png)

**3.** Potwierdź ostrzeżenie o rozszerzeniu spoza Sklepu Shopware — kliknij **„potwierdź"**.

![Ostrzeżenie o rozszerzeniu spoza Sklepu Shopware z przyciskiem „potwierdź"](images/inst-02-warning.png)

**4.** Wskaż pobrany plik **`CrehlerPayU.zip`**.

![Okno wyboru pliku z zaznaczonym CrehlerPayU.zip](images/inst-03-select.png)

**5.** Wtyczka pojawi się na liście jako *„Bramka płatności PayU by CREHLER"* — kliknij **„Zainstaluj"**, a następnie **„Aktywuj"**.

![Wtyczka „Bramka płatności PayU by CREHLER" na liście Moje rozszerzenia z przyciskiem „Zainstaluj"](images/inst-04-install.png)

➡️ Przejdź do **[Instrukcji konfiguracji](konfiguracja.md)**.

---

## Wsparcie

Problem z instalacją? Napisz do nas: **[support@crehler.com](mailto:support@crehler.com)**

<p align="center"><sub>Bramka płatności <strong>PayU by CREHLER</strong> · <a href="https://crehler.com/">crehler.com</a></sub></p>
