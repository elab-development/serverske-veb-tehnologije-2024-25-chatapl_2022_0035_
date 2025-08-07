# Commit 3: Kontroleri i API rute za Chat aplikaciju

## Opis komita
Treći komit u projektu - kreiranje kontrolera i API ruta za Chat aplikaciju sa autentifikacijom.

## Šta je urađeno:
- Kreiran AuthController sa metodama za registraciju, login, logout i me
- Kreiran RoomController sa metodama za CRUD operacije i join/leave funkcionalnosti
- Kreiran MessageController sa metodama za CRUD operacije na porukama
- Konfigurisane API rute u routes/api.php
- Instaliran Laravel Sanctum za autentifikaciju
- Publikovane Sanctum migracije i konfiguracija

## Kontroleri:

### AuthController
- `register()` - registracija novog korisnika
- `login()` - prijava korisnika
- `logout()` - odjava korisnika
- `me()` - dohvatanje trenutnog korisnika

### RoomController
- `index()` - lista svih aktivnih soba
- `store()` - kreiranje nove sobe
- `show()` - prikaz sobe sa porukama
- `update()` - izmena sobe (samo admin)
- `destroy()` - brisanje sobe (samo admin)
- `join()` - pridruživanje sobi
- `leave()` - napuštanje sobe

### MessageController
- `index()` - lista poruka u sobi sa paginacijom
- `store()` - slanje nove poruke
- `show()` - prikaz pojedinačne poruke
- `update()` - izmena poruke (samo vlasnik)
- `destroy()` - brisanje poruke (vlasnik ili admin)

## API Rute:
- **Public**: `/api/register`, `/api/login`
- **Protected**: sve ostale rute zahtevaju autentifikaciju

## Autori:
- Masa Stevanovic
- Luka Simic  
- Andrej Djordjevic

## Datum: 7. avgust 2024.

## Napomene:
Sve rute vraćaju JSON odgovore. U sledećim komitima će se dodavati WebSocket funkcionalnosti i frontend interfejs.
