# Commit 5: Frontend interfejs za Chat aplikaciju

## Opis komita
Peti komit u projektu - kreiranje modernog frontend interfejsa za Chat aplikaciju sa WebSocket funkcionalnostima.

## Šta je urađeno:
- Kreiran moderni chat interfejs sa responsivnim dizajnom
- Implementirana autentifikacija (login/register) sa token-based autentifikacijom
- Dodana funkcionalnost za kreiranje i pridruživanje chat sobama
- Implementiran real-time chat sa WebSocket konekcijom
- Dodane notifikacije za korisničke akcije
- Konfigurisane rute za pristup chat interfejsu

## Frontend funkcionalnosti:

### Autentifikacija
- Login forma sa email i password
- Register forma sa validacijom
- Token-based autentifikacija sa localStorage
- Automatska provera postojeće sesije

### Chat interfejs
- Sidebar sa listom dostupnih soba
- Glavni chat prostor sa porukama
- Real-time slanje i primanje poruka
- Prikaz online korisnika
- Notifikacije o ulasku/izlasku korisnika

### WebSocket integracija
- Pusher.js klijent za WebSocket konekciju
- Presence channels za praćenje online korisnika
- Real-time broadcast poruka
- Automatsko povezivanje sa soba

## Tehnologije:
- HTML5, CSS3, JavaScript (ES6+)
- Axios za HTTP zahteve
- Pusher.js za WebSocket konekciju
- Modern CSS sa flexbox i grid
- Responsive dizajn

## Dizajn:
- Moderna gradijent pozadina
- Kartice sa senkama i zaobljenim ivicama
- Animacije za hover efekte
- Notifikacije sa slide-in animacijom
- Responsive layout za različite veličine ekrana

## Autori:
- Masa Stevanovic
- Luka Simic  
- Andrej Djordjevic

## Datum: 7. avgust 2024.

## Napomene:
Za testiranje aplikacije:
1. Pokrenuti Laravel server: `php artisan serve`
2. Konfigurisati Pusher credentials u .env fajlu
3. Otvoriti aplikaciju u browseru na `http://localhost:8000`
4. Registrovati se ili prijaviti sa postojećim nalogom
5. Kreirati sobu ili pridružiti se postojećoj
6. Testirati real-time chat funkcionalnosti
