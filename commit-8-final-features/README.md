# Commit 8: Finalne funkcionalnosti za Chat aplikaciju

## Opis komita
Osmi komit u projektu - dodavanje finalnih funkcionalnosti za kompletnu Chat aplikaciju.

## Šta je urađeno:
- Implementirana funkcionalnost za resetovanje zaboravljene lozinke
- Dodana funkcionalnost za promenu lozinke autentifikovanog korisnika
- Proširen User model sa poljima za resetovanje lozinke
- Dodane nove API rute za upravljanje lozinkama
- Kompletirana funkcionalnost za višu ocenu

## Funkcionalnosti za resetovanje lozinke:

### PasswordResetController
- **sendResetLink()**: Slanje linka za resetovanje lozinke
- **resetPassword()**: Resetovanje lozinke sa tokenom
- **changePassword()**: Promena lozinke autentifikovanog korisnika

### Sigurnosne mere
- **Token validacija**: 60 karaktera, slučajno generisan
- **Vremensko ograničenje**: Token ističe za 1 sat
- **Validacija trenutne lozinke**: Provera pre promene
- **Hash enkripcija**: Sigurno čuvanje lozinki

### Proces resetovanja
1. Korisnik unosi email adresu
2. Sistem generiše token i čuva ga u bazi
3. Token se šalje korisniku (email u produkciji)
4. Korisnik koristi token za postavljanje nove lozinke
5. Token se briše nakon uspešnog resetovanja

## API rute za upravljanje lozinkama:

### Public rute
- `POST /api/password/reset-link` - Slanje linka za resetovanje
- `POST /api/password/reset` - Resetovanje lozinke sa tokenom

### Protected rute
- `POST /api/password/change` - Promena lozinke (zahtevana autentifikacija)

## Prošireni modeli:
- **User**: Dodana polja password_reset_token i password_reset_expires_at
- **Migracija**: add_password_reset_fields_to_users_table

## Validacija:
- **Email**: Mora postojati u bazi
- **Token**: Mora biti validan i neistekao
- **Lozinka**: Minimum 8 karaktera, potrebna potvrda
- **Trenutna lozinka**: Mora biti tačna za promenu

## Sigurnosne karakteristike:
- **Jednokratni token**: Token se briše nakon korišćenja
- **Vremensko ograničenje**: Automatsko isticanje tokena
- **Hash enkripcija**: Lozinke se čuvaju sigurno
- **Validacija**: Sve unose se validiraju

## Autori:
- Masa Stevanovic
- Luka Simic  
- Andrej Djordjevic

## Datum: 7. avgust 2024.

## Napomene:
Za testiranje funkcionalnosti resetovanja lozinke:
1. Pokrenuti migracije: `php artisan migrate`
2. Testirati slanje reset linka kroz Postman
3. Koristiti vraćeni token za resetovanje lozinke
4. Testirati promenu lozinke autentifikovanog korisnika
5. Proveriti da li stari token ne radi nakon resetovanja

## Kompletna funkcionalnost aplikacije:
✅ **Minimalni zahtevi**: Modeli, migracije, API rute, autentifikacija
✅ **Zahtevi za višu ocenu**: Paginacija, filtriranje, uloge, seeders, export, upload, keširanje, resetovanje lozinke
✅ **Chat funkcionalnosti**: WebSocket, real-time komunikacija, sobe, poruke
✅ **Frontend**: Moderna chat aplikacija sa JavaScript
