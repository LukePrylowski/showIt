# Show It! — Pokaż to!

Kalambury dla 2–20 drużyn na jednym przekazywanym telefonie. PHP 8.1+, JavaScript, kategorie w JSON, bez bazy danych i zależności. Wygląd pochodzi z WeaselWords.

Uruchomienie: `php -S 0.0.0.0:8000`, następnie otwórz `http://localhost:8000`. Na telefonie w tej samej sieci użyj adresu IP komputera. Na hostingu użyj PHP i HTTPS.

Wybierz kategorie, dodaj drużyny i ustaw 15–300 sekund. START odkrywa pierwsze hasło i rozpoczyna odliczanie. Telefon trzyma wyłącznie osoba pokazująca. Po odgadnięciu kliknij „Następne — zgadnięte!”: drużyna otrzymuje punkt i nowe hasło. Po czasie pojawiają się wyniki. Drużyny grają kolejno; wybór kolejnych osób pokazujących odbywa się poza aplikacją. Punkty sumują się, dopóki z ustawień nie rozpoczniesz nowej gry.

Pokój jest osobną sesją przeglądarki, tak jak w WeaselWords. Kod identyfikuje pokój; nie służy do dołączania z innych telefonów. Odświeżenie zachowuje wyniki i termin końca tury. W osobnej przeglądarce powstaje osobny pokój. Sesja Show It! jest oddzielona od WeaselWords.

Dźwięk końca tury korzysta z Web Audio aktywowanego przyciskiem START lub „Sprawdź dźwięk”. Ustaw głośność i zostaw stronę na ekranie. Przeglądarki mobilne mogą wstrzymać dźwięk i JavaScript po zablokowaniu telefonu lub przejściu do innej aplikacji; po powrocie stan zostaje zsynchronizowany z serwerem. Sam timer i punktacja są sprawdzane na serwerze. Przed grą warto przetestować dźwięk na urządzeniu.

## Licznik gier

`stats.php` zwraca publiczną liczbę gier jako `gamesPlayed`. Licznik zwiększa się przy pierwszym START nowej gry, a kolejne tury i odświeżenia go nie zmieniają. Dane zapisują się w `data/games-played.json` z blokadą pliku; katalog `data` musi być zapisywalny przez PHP. Zachowaj ten plik przy aktualizacji aplikacji.

## Kategorie

Każdy plik `data/categories/*.json` to osobna kategoria:

```json
{"name":"Zwierzęta","emoji":"🐾","words":["Kot","Pingwin","Żyrafa"]}
```

Dodaj plik lub dopisz hasła i odśwież ustawienia. Błędne i puste pliki są pomijane. Hasła losują się bez powtórzeń do wyczerpania puli; później talia jest tasowana ponownie. Początkowe kategorie i hasła pochodzą z WeaselWords. Trwająca gra zachowuje własną pulę.
