# Commit 7: Keširanje podataka za Chat aplikaciju

## Opis komita
Sedmi komit u projektu - implementacija keširanja podataka za poboljšanje performansi aplikacije.

## Šta je urađeno:
- Dodano keširanje za listu soba (5 minuta)
- Dodano keširanje za poruke u sobama (1 minuta)
- Implementirana invalidacija keša pri dodavanju novih poruka
- Kreiran StatisticsController sa keširanim statistikama
- Dodane rute za statistike i upravljanje kešom

## Keširanje funkcionalnosti:

### RoomController
- **Keširanje liste soba**: 5 minuta sa dinamičkim ključem
- **Dinamički ključ**: Baziran na svim query parametrima (filteri, sortiranje, paginacija)
- **Cache key**: `rooms_` + md5(serialize(request->all()))

### MessageController
- **Keširanje poruka**: 1 minuta (kraći period zbog čestih promena)
- **Dinamički ključ**: Baziran na room_id i svim query parametrima
- **Cache key**: `messages_room_{roomId}_` + md5(serialize(request->all()))
- **Invalidacija**: Automatsko brisanje keša pri dodavanju nove poruke

### StatisticsController
- **Overall statistike**: 10 minuta keširanje
- **Room statistike**: 5 minuta keširanje
- **User statistike**: 5 minuta keširanje
- **Cache management**: Ruta za brisanje celog keša

## Statistike koje se keširaju:

### Overall statistike
- Ukupan broj korisnika, soba, poruka
- Broj aktivnih, javnih, privatnih soba
- Poruke danas i ove nedelje

### Room statistike
- Broj poruka i korisnika u sobi
- Poruke danas i ove nedelje
- Najaktivniji korisnik
- Vreme kreiranja i poslednje poruke

### User statistike
- Broj poruka i soba korisnika
- Poruke danas i ove nedelje
- Omiljena soba
- Vreme pridruživanja i poslednje poruke

## API rute za statistike:
- `GET /api/statistics/overall` - Ukupne statistike aplikacije
- `GET /api/statistics/rooms/{roomId}` - Statistike sobe
- `GET /api/statistics/users/{userId}` - Statistike korisnika
- `POST /api/statistics/clear-cache` - Brisanje celog keša

## Prednosti keširanja:
- **Performanse**: Brži pristup često korišćenim podacima
- **Smanjenje opterećenja**: Manje upita ka bazi podataka
- **Skalabilnost**: Bolja podrška za više korisnika
- **Dinamičnost**: Automatska invalidacija pri promenama

## Autori:
- Masa Stevanovic
- Luka Simic  
- Andrej Djordjevic

## Datum: 7. avgust 2024.

## Napomene:
Za testiranje keširanja:
1. Pokrenuti aplikaciju: `php artisan serve`
2. Testirati rute za statistike kroz Postman
3. Proveriti razliku u vremenu odziva pre i posle keširanja
4. Testirati invalidaciju keša dodavanjem novih poruka
