# Plan: rozbudowa modułu `user` (Steam) — biblioteka, wishlist, osiągnięcia, profil

Status na start: zbudowane jest logowanie Steam (yii2-authclient OpenID) i **sync biblioteki** (`user_game`, `SteamLibrarySync`, `steam-user/sync`, `/profile/library`). Wishlist i achievements to wciąż placeholdery (`/profile/{wishlist,achievements}`).

Realizujemy fazami, krok po kroku. Każda faza jest samodzielnie wdrażalna i kończy się działającą funkcją.

## Decyzje (ustalone)
- **Sync osiągnięć**: paced w tle (kolejka per user-game, priorytet gier granych/ostatnio granych, N gier na run) **+ on-demand** odświeżenie przy wejściu na stronę osiągnięć gry (cache ~24h). `GetPlayerAchievements` = 1 wywołanie API na (user × gra).
- **„Sync now"**: jeden przycisk = pełny sync (biblioteka + wishlist + achievements). Manualne wymuszenie **raz / 24h** (pokazujemy „następny za Xh"). Cron robi inkrementalnie pomiędzy.
- **Layout konta**: wspólny shell z paskiem zakładek (Profile / Library / Wishlist / Achievements / Settings), jedna spójna szerokość.
- **Paginacja**: serwerowa (Yii `Pagination`/`LinkPager`), filtry i sort jako parametry URL.

## Zasady przekrojowe
- Steam wołamy **wyłącznie** w cronie/console (paced + `FileMutex`), nigdy w cyklu request/response. On-demand achievements = wyjątek dopuszczalny, ale przez cache i tylko dla pojedynczej gry.
- Strony konta są prywatne → `noindex`, nie trafiają do sitemap.
- Mapowanie do katalogu po `steam_appid`; brakujące appidy kolejkujemy jako stuby (limit/run), linki dopinają się przy kolejnym sync.
- Logika (agregaty, mapowanie, %) w modelach/helperach; widoki tylko renderują.
- Trasa strony gry używa `steam_appid` jako `id` (`game/<id>/<slug>` → `game/game/view`).

---

## Faza 0 — Fundamenty wspólne

### 0.1 Shell konta + zakładki + spójna szerokość  *(poz. 5)*
- Wspólny layout dla modułu `user` (np. `frontend/modules/user/views/layouts/account.php` lub partial `_account-nav.php` wstawiany w widokach) z paskiem zakładek: Profile, Library, Wishlist, Achievements, Settings (= obecny `index`).
- Jedna szerokość kontenera (np. `max-w-6xl`) dla wszystkich podstron konta. Aktywna zakładka podświetlona.
- Refactor istniejących widoków (`index`, `library`, `placeholder`) pod shell.
- **Akceptacja**: wszystkie strony konta tej samej szerokości, spójna nawigacja między sekcjami.

### 0.2 Wspólny komponent siatki gier + paginacji
- Wydzielić partial karty/siatki gry użytej w bibliotece (kafel: header capsule, tytuł, meta) do reużycia w wishliście i profilu.
- Helper/komponent paginacji (Yii `LinkPager` ostylowany Tailwindem) — wspólny dla library/wishlist.
- **Akceptacja**: jeden partial renderuje kafle w library i wishlist; pager spójny wizualnie.

---

## Faza 1 — Sync wishlisty  *(wymagane przez poz. 1 i 7)* — ZROBIONE
**Zrobione:** `user_wishlist` + `common\models\UserWishlist`; `SteamApi::getWishlist` (IWishlistService/GetWishlist), `SteamWishlistSync`; wpięte w `steam-user/sync` (per user: library→wishlist, wspólne `steam_synced_at` i `steam_visibility`). `/profile/wishlist` = pełna strona (paginacja 60, sort priority/added/name, ceny z katalogu na kaflach, `_wishlist-tile`). Live: 145 gier, 128 zmapowanych, priorytety+daty OK.

- Migracja `user_wishlist` (`user_id`, `game_id` nullable, `steam_appid`, `priority` nullable, `added_at` nullable, timestamps, unikat `(user_id, steam_appid)`).
- Model `common\models\UserWishlist` (+ helpery prezentacyjne jak w `UserGame`).
- `SteamApi::getWishlist($steamId)` — `IWishlistService/GetWishlist` (appid + priority). **Do weryfikacji w implementacji**: aktualny endpoint i kształt odpowiedzi (fallback: `store.steampowered.com/wishlist/.../wishlistdata`); prywatny profil → pusto.
- `SteamWishlistSync` analogiczny do `SteamLibrarySync` (reconcile, stuby brakujących appidów, usuwanie usuniętych z wishlisty).
- Wpięcie do console sync (Faza 4 scali to w pełny sync; tymczasowo własna ścieżka).
- Widok `/profile/wishlist` na wspólnym shellu + siatce (0.2).
- **Akceptacja**: wishlist usera zsynchronizowana i wyświetlona; placeholder zniknął.

---

## Faza 2 — Paginacja + filtrowanie + sortowanie biblioteki  *(poz. 1, 4)*
- `ProfileController::actionLibrary`: `ActiveDataProvider`/`Pagination` (page size np. 60), parametry URL.
- Filtry: status grania (grane / nigdy), szukajka po nazwie. (Filtr „posiada osiągnięcia" przeniesiony do Fazy 3 — oparty o per-userowe `user_game.ach_total`, bo katalogowe `achievements_total` jest jeszcze puste dla ~wszystkich gier. Gatunek z katalogu — opcjonalnie później.)
- Sortowanie: czas gry, ostatnio grane, nazwa A-Z. % ukończenia dojdzie w Fazie 3.
- **Zrobione**: `LibraryFilter` (formName ''), `ActiveDataProvider` pageSize 60, partiale `_game-tile`/`_pager`, pasek filtrów GET z auto-submit. Filtry/sort w URL, paginacja zachowuje parametry.
- Formularz filtrów (model `LibraryFilter` w `frontend\modules\user\models`) + pasek filtrów w widoku; stan w query stringu.
- To samo podejście do wishlisty (sort: priority, nazwa, data dodania).
- **Akceptacja**: lista stronicowana, filtry/sort działają i są w URL (linkowalne, odświeżalne).

---

## Faza 3 — Osiągnięcia użytkownika  *(poz. 2, 3, 6)* — ZROBIONE
**Zrobione (3.1–3.4):** `user_achievement` + agregaty `user_game.ach_total/ach_unlocked/ach_synced_at`; `SteamApi::getPlayerAchievements`, `SteamAchievementSync`; console `steam-user/sync-achievements [limit]` (paced + mutex, kolejka ach_synced_at NULL→stale 30d, tylko publiczne profile/in-catalog); % ukończenia + złoty pasek/puchar dla 100% na kaflach; filtr (With achievements / Not 100% / 100%) + sort „Completion %"; widok osiągnięć gry pokazuje progres właściciela (pasek, złoto+puchar dla 100%) i oznacza zdobyte/niezdobyte + datę, z on-demand refreshem raz/24h. Dodać `steam-user/sync-achievements` do crona.

### 3.1 Model danych
- Migracja `user_achievement` (`user_id`, `game_id`, `api_name`, `unlocked` bool, `unlocked_at` nullable, timestamps, unikat `(user_id, game_id, api_name)`).
- Agregaty na `user_game`: `ach_total`, `ach_unlocked`, `ach_synced_at` (nullable) — do taniego renderu % w bibliotece i kolejkowania sync per gra.
- Model `common\models\UserAchievement` + relacja do `GameAchievement` (po `game_id`+`api_name`).

### 3.2 Pobieranie i sync (paced w tle)
- `SteamApi::getPlayerAchievements($steamId, $appid)` — `ISteamUserStats/GetPlayerAchievements` (apiname, achieved, unlocktime). Obsługa: gra bez statów / profil prywatny / brak schematu.
- `SteamAchievementSync` (per user-game): pobranie, upsert `user_achievement`, przeliczenie agregatów na `user_game`.
- Kolejka per user-game w cronie: priorytet gier z `playtime>0` i `last_played_at` najnowsze, `ach_synced_at` NULL pierwsze; N gier na run (limit), własny `FileMutex`.
- **Akceptacja**: cron stopniowo wypełnia `ach_unlocked/ach_total` dla gier usera.

### 3.3 % ukończenia w bibliotece  *(poz. 3)*
- Kafel gry pokazuje `ach_unlocked/ach_total` + pasek/pierścień %; gdy brak danych (jeszcze nie zsynchronizowane / gra bez osiągnięć) — stan neutralny.
- Dodać sort „% ukończenia" (Faza 2).
- **Akceptacja**: % widoczny na kaflach, sortowanie działa.

### 3.4 Strona osiągnięć gry — widok użytkownika  *(poz. 6)*
- Gdy zalogowany **posiada** grę: nagłówek ze statystykami usera (zdobyte / total, %, ostatnie odblokowanie), oznaczenie każdego osiągnięcia zdobyte/niezdobyte + **data zdobycia** (`unlocktime`).
- On-demand: jeśli `ach_synced_at` starsze niż próg (np. 24h) lub NULL — odśwież w locie (cache), inaczej z bazy.
- Zachować obecny widok globalny (rzadkość itd.) dla niezalogowanych / nieposiadających.
- **Akceptacja**: posiadacz gry widzi swój postęp i daty; reszta widzi obecny widok.

---

## Faza 4 — Zunifikowany „Sync now" + cooldown 24h  *(poz. 8)* — ZROBIONE
**Zrobione:** `User::enqueueFullSync()` (zeruje steam_synced_at + ach_synced_at wszystkich gier usera), `canRequestSync()`/`getSyncCooldownLabel()` na bazie `user.steam_sync_requested_at` (24h). Jeden globalny przycisk „Sync now" w shellu konta (cooldown → „Sync in Xh"), usunięty z poszczególnych widoków. Crony (steam_synced_at/ach_synced_at NULL) podejmują pracę bez zmian.

- `User::enqueueFullSync()` — kolejkuje bibliotekę + wishlist + (re)sync achievements granych gier; zapis `steam_sync_requested_at`.
- Gate 24h: kolumna `steam_sync_requested_at`; akcja `profile/sync` odrzuca/ignoruje gdy < 24h, UI pokazuje „następny sync za Xh".
- Console (`steam-user/sync`) orkiestruje per user: biblioteka → wishlist → porcja achievements (paced), spójnie z kolejkami.
- Przycisk „Sync now" w shellu konta (jeden, globalny) z countdownem.
- **Akceptacja**: jeden przycisk odświeża wszystko; wymuszenie max raz/24h z czytelnym feedbackiem.

---

## Faza 5 — Profil użytkownika ze statystykami  *(poz. 7)* — ZROBIONE
**Zrobione:** `/profile` (index) = dashboard, ustawienia przeniesione do `Settings` (`actionSettings`/`settings.php`), zakładka „Profile" dodana. `ProfileStats` (agregaty SQL cache'owane). Karty: gry, godziny, osiągnięcia, perfect, ultra-rare, backlog %, est. value (Steam), pierścień % ukończenia. Sekcje: most played / recently played / 100% (siatki `_game-tile`), recent unlocks feed (daty), top gatunki **wg godzin**, rarest unlock, **wishlist deals** (na promocji), **recommended for you** (`_catalog-tile`, wg top-gatunków, nieposiadane). Collation moich user_* tabel wyrównana do `utf8mb4_0900_ai_ci` (fix join po api_name).

Dashboard `/profile` (Profile tab) liczony z zsynchronizowanych danych (helpery/agregaty, cache per user):
- **Liczby**: gry łącznie, łączny czas grania, grane vs nigdy, gry w 100%, ogólny % ukończenia osiągnięć, suma zdobytych osiągnięć.
- **Top**: najczęściej grane (N), ostatnio grane (N), „perfect games" (100%).
- **Rozkłady**: gatunki (z katalogowanych gier), rozkład % ukończenia, najrzadsze zdobyte osiągnięcie.
- Inspiracja profilem Steam (np. fearlycan), ale czytelniej/ładniej; karty statystyk wg skilli (fade-up, tabular-nums, pierścienie %).
- **Do analizy/rozbudowy**: streak ostatniej aktywności, „value" (brak cen owned — pomijamy lub szacujemy z katalogu), odznaki, oś czasu.
- **Akceptacja**: profil pokazuje zestaw statystyk i top-listy, szybki (cache), bez zapytań N+1.

---

## Faza 6 — Polish poza danymi usera

### Faza 6 — ZROBIONE
- **6.1**: `section-nav` na stronie gry → sticky (`top-16`), jeden przewijany rząd, **scrollspy** (reużyty `[data-scrollspy]` z `main.js`) + podświetlenie aktywnej sekcji; kotwice `scroll-mt-32`. Profil też dostał sticky jump-nav (Overview/Games/Achievements/Discover) na tym samym scrollspy.
- **6.2**: ceny w wyszukiwarce top-menu (`AutocompleteController::searchGames` → `getDisplayPrice` z ofert, waluta w kluczu cache; render w `main.js` z przeceną).

### 6.1 Reorg nawigacji kotwic na stronie gry  *(poz. 9)*
- `section-nav` (`view.php` ~49-58) rośnie. Zrobić sticky scrollspy (podświetlanie aktywnej sekcji przy scrollu) + zwijanie do „On this page" (dropdown) na wąskich ekranach / przy wielu sekcjach.
- Uporządkować kolejność i nazewnictwo sekcji; spójny komponent.
- **Akceptacja**: nawigacja sekcji czytelna niezależnie od liczby sekcji; aktywna sekcja podświetlona.

### 6.2 Ceny w wyszukiwarce top-menu  *(poz. 10)*
- `AutocompleteController::searchGames`: dołożyć cenę wyświetlaną (najlepsza oferta w walucie odwiedzającego / Steam) do itemu gry. Uwaga: cache wyników musi uwzględniać walutę w kluczu; cena wymaga doładowania ofert (nie czyste `asArray`).
- `main.js` (render wyników): pokazać cenę przy grze.
- **Akceptacja**: gry w podpowiedziach mają cenę, spójną z kartami; cache poprawny per waluta.

---

## Otwarte punkty / ryzyka do pilnowania
- **Endpoint wishlisty** — zweryfikować aktualny `IWishlistService/GetWishlist` vs starszy `wishlistdata`; oba wymagają publicznego profilu.
- **Koszt achievements API** — twardo limitować liczbę gier/run; monitorować rate-limity; rozważyć dłuższy próg „stale" dla achievements niż dla biblioteki.
- **Prywatny profil** — wszystkie pulle (lib/wishlist/ach) zależą od publicznego profilu; spójny komunikat (mamy `steam_visibility`).
- **Wydajność profilu** — statystyki liczyć zapytaniami agregującymi + cache per user (inwalidacja po sync), nie w pętli po grach.
- **Migracje** — uruchamiać w kontenerze (`games-frontend`, PHP 8.3).

## Kolejność realizacji (sugerowana)
0.1 → 0.2 → 1 → 2 → 3.1 → 3.2 → 3.3 → 3.4 → 4 → 5 → 6.1 → 6.2

(6.1 i 6.2 są niezależne od reszty — można wcisnąć w dowolnym momencie jako „szybkie wygrane".)

## Backlog / do dołożenia przy okazji
- **Staggered `fade-up`** — wdrożone na stronach konta (profil, biblioteka, wishlist, settings, achievements). Przy każdej kolejnej edycji innych widoków (homepage, strona gry, listy gier/gatunków/tagów, developer/publisher/company, kontakt) **dokładać ten sam efekt** kluczowym sekcjom/kartom: `class="… fade-up"` + rosnący inline `animation-delay` (.04s, .1s, .16s…). Wzorzec opisany w pamięci `feedback-staggered-fade-up`.
