# Chat Application - Kompletna Laravel aplikacija

## Pregled projekta
Kompletna Chat aplikacija sa real-time WebSocket funkcionalnostima, naprednim API-jem i modernim frontend interfejsom.

## Autori
- **Masa Stevanovic**
- **Luka Simic**  
- **Andrej Djordjevic**

## Datum
11. avgust 2024.

## Komitovi u projektu

### 1. Commit 1: Inicijalna postavka
- Laravel aplikacija (v12.2.0)
- Pusher paket za WebSocket funkcionalnosti
- Osnovna konfiguracija

### 2. Commit 2: Modeli
- **User**: Korisnici aplikacije
- **Room**: Chat sobe/kanali
- **Message**: Poruke u chatu
- **UserRoom**: Pivot tabela za vezu korisnika i soba
- Migracije sa odnosima između modela

### 3. Commit 3: Kontroleri i API rute
- **AuthController**: Registracija, login, logout, me
- **RoomController**: CRUD operacije, join/leave funkcionalnosti
- **MessageController**: CRUD operacije na porukama
- Laravel Sanctum za autentifikaciju
- REST API rute sa JSON odgovorima

### 4. Commit 4: WebSocket funkcionalnosti
- **MessageSent**: Event za broadcast poruka
- **UserJoinedRoom**: Event za ulazak u sobu
- **UserLeftRoom**: Event za izlazak iz sobe
- Pusher konfiguracija za real-time komunikaciju
- Presence channels za praćenje online korisnika

### 5. Commit 5: Frontend interfejs
- Moderna chat aplikacija sa JavaScript
- Responsive dizajn sa CSS3
- Real-time komunikacija sa Pusher.js
- Autentifikacija sa token-based sistemom
- Intuitivan korisnički interfejs

### 6. Commit 6: Napredne funkcionalnosti
- **Seeders i Factories**: Test podaci
- **Paginacija i filtriranje**: Za sobe i poruke
- **Export funkcionalnosti**: CSV format
- **Upload/Download fajlova**: Do 10MB
- Prošireni modeli sa dodatnim poljima

### 7. Commit 7: Keširanje podataka
- Keširanje liste soba (5 minuta)
- Keširanje poruka (1 minuta)
- **StatisticsController**: Keširane statistike
- Automatska invalidacija keša
- Upravljanje kešom

### 8. Commit 8: Finalne funkcionalnosti
- **PasswordResetController**: Resetovanje zaboravljene lozinke
- Promena lozinke autentifikovanog korisnika
- Sigurnosne mere za upravljanje lozinkama
- Token-based resetovanje sa vremenskim ograničenjem

### 9. Commit 9: Kompletna aplikacija
- Sve prethodne funkcionalnosti
- Finalna integracija i testiranje
- Dokumentacija i README

### 10. Commit 10: Osnovne sigurnosne mere
- **Rate Limiting**: Ograničenje zahteva po IP adresi
- **CSRF Protection**: Dodatna zaštita od CSRF napada
- **XSS Protection**: Sanitizacija korisničkih unosa
- **Input Validation**: Napredna validacija svih unosa

### 11. Commit 11: Napredne sigurnosne mere
- **SQL Injection Protection**: Prepared statements i detekcija SQL injection napada
- **Security Headers**: Dodatni HTTP security headers
- **Audit Logging**: Praćenje sigurnosnih događaja

### 12. Commit 12: Osnovne baze podataka funkcionalnosti
- **Database Triggers**: Automatski pokretani SQL blokovi
- **Stored Procedures**: Kompleksne SQL operacije
- **Database Views**: Virtuelne tabele za kompleksne upite
- **Database Transactions**: Atomične operacije
- **Advanced Indexing**: Optimizacija performansi
- **Database Constraints**: Referentni integritet
- **Database Backup**: Automatsko pravljenje backup-a

### 13. Commit 13: Napredne baze podataka funkcionalnosti
- **Advanced Indexing**: Optimizacija performansi
- **Database Constraints**: Referentni integritet
- **Database Backup**: Automatsko pravljenje backup-a

### 14. Commit 14: Osnovni web servisi
- **External API Integration**: Integracija sa spoljnim servisima
- **Weather API**: Prikaz vremena u chat sobi
- **Translation API**: Automatski prevod poruka
- **News API**: Prikaz vesti u sobi

### 15. Commit 15: Napredni web servisi
- **Currency API**: Konvertor valuta
- **Email Integration**: Slanje email obaveštenja
- **SMS Integration**: SMS obaveštenja
- **Push Notifications**: Push obaveštenja
- **Social Media Integration**: Deljenje na društvenim mrežama

### 16. Commit 16: Osnovne notifikacije
- **Email Notifications**: Automatska email obaveštenja
- **SMS Notifications**: SMS obaveštenja preko Twilio
- **Push Notifications**: Web push obaveštenja
- **In-App Notifications**: Unutrašnja obaveštenja


## Funkcionalnosti aplikacije

### ✅ Minimalni zahtevi
- [x] Laravel aplikacija sa 3+ povezana modela
- [x] 5+ različitih tipova migracija
- [x] API rute sa REST konvencijom
- [x] JSON odgovori za sve rute
- [x] Autentifikacija (login, logout, register)
- [x] Zaštita ruta za autentifikovane korisnike

### ✅ Zahtevi za višu ocenu (3+ implementirana)
- [x] **Paginacija i filtriranje** - Za sobe i poruke
- [x] **Resetovanje zaboravljene lozinke** - Token-based sistem
- [x] **3 uloge u sistemu** - admin, moderator, member
- [x] **Upload fajlova** - Do 10MB sa validacijom
- [x] **Keširanje podataka** - Redis/file cache
- [x] **Seeders i Factories** - Test podaci
- [x] **Napredna pretraga** - Po sadržaju, datumu, korisniku
- [x] **Export podataka** - CSV format

### ✅ Chat funkcionalnosti
- [x] **Real-time WebSocket komunikacija** - Pusher integracija
- [x] **Chat sobe/kanali** - Public i private
- [x] **Presence channels** - Praćenje online korisnika
- [x] **Broadcast poruka** - Instant slanje
- [x] **File sharing** - Upload i download fajlova
- [x] **User management** - Join/leave sobe

## API Endpoints

### Autentifikacija
- `POST /api/register` - Registracija
- `POST /api/login` - Prijava
- `POST /api/logout` - Odjava (protected)
- `GET /api/me` - Trenutni korisnik (protected)

### Upravljanje lozinkama
- `POST /api/password/reset-link` - Slanje reset linka
- `POST /api/password/reset` - Resetovanje lozinke
- `POST /api/password/change` - Promena lozinke (protected)

### Sobe
- `GET /api/rooms` - Lista soba (sa filtriranjem i paginacijom)
- `POST /api/rooms` - Kreiranje sobe (protected)
- `GET /api/rooms/{id}` - Detalji sobe (protected)
- `PUT /api/rooms/{id}` - Izmena sobe (protected, admin)
- `DELETE /api/rooms/{id}` - Brisanje sobe (protected, admin)
- `POST /api/rooms/{id}/join` - Pridruživanje sobi (protected)
- `POST /api/rooms/{id}/leave` - Napuštanje sobe (protected)

### Poruke
- `GET /api/messages` - Lista poruka (sa filtriranjem i paginacijom)
- `POST /api/messages` - Slanje poruke (protected)
- `GET /api/messages/{id}` - Detalji poruke (protected)
- `PUT /api/messages/{id}` - Izmena poruke (protected, vlasnik)
- `DELETE /api/messages/{id}` - Brisanje poruke (protected, vlasnik/admin)
- `POST /api/messages/upload` - Upload fajla (protected)
- `GET /api/messages/{id}/download` - Download fajla (protected)

### Export
- `GET /api/export/messages/{roomId}` - Export poruka u CSV (protected)
- `GET /api/export/room-stats/{roomId}` - Export statistika sobe (protected)

### Statistike
- `GET /api/statistics/overall` - Ukupne statistike (protected)
- `GET /api/statistics/rooms/{roomId}` - Statistike sobe (protected)
- `GET /api/statistics/users/{userId}` - Statistike korisnika (protected)
- `POST /api/statistics/clear-cache` - Brisanje keša (protected)

### Obaveštenja
- `GET /api/notifications` - Lista obaveštenja (protected)
- `GET /api/notifications/unread-count` - Broj nepročitanih obaveštenja (protected)
- `PATCH /api/notifications/{id}/read` - Označi kao pročitano (protected)
- `PATCH /api/notifications/mark-all-read` - Označi sva kao pročitana (protected)
- `DELETE /api/notifications/{id}` - Obriši obaveštenje (protected)
- `GET /api/notifications/preferences` - Podešavanja obaveštenja (protected)
- `PUT /api/notifications/preferences` - Ažuriraj podešavanja (protected)
- `POST /api/notifications/test` - Pošalji test obaveštenje (protected)
- `POST /api/notifications/bulk` - Masovno slanje obaveštenja (protected)
- `GET /api/notifications/statistics` - Statistike obaveštenja (protected)

## Modeli i odnosi

### User
- `hasMany` Message
- `belongsToMany` Room (pivot: user_room)

### Room
- `hasMany` Message
- `belongsToMany` User (pivot: user_room)

### Message
- `belongsTo` User
- `belongsTo` Room

### UserRoom (pivot)
- `user_id`, `room_id`
- `role` (admin, moderator, member)
- `is_online`, `last_seen_at`

## WebSocket Events

### MessageSent
- Channel: `presence-room.{roomId}`
- Data: Poruka sa korisnikom

### UserJoinedRoom
- Channel: `presence-room.{roomId}`
- Data: Korisnik i soba

### UserLeftRoom
- Channel: `presence-room.{roomId}`
- Data: Korisnik i soba

## Tehnologije

### Backend
- **Laravel 12.2.0** - PHP framework
- **Laravel Sanctum** - API autentifikacija
- **Pusher** - WebSocket server
- **SQLite** - Baza podataka
- **Laravel Cache** - Keširanje

### Frontend
- **HTML5/CSS3** - Markup i stilizovanje
- **JavaScript (ES6+)** - Interaktivnost
- **Axios** - HTTP klijent
- **Pusher.js** - WebSocket klijent

## Instalacija i pokretanje

1. **Kloniranje projekta**
   ```bash
   git clone <repository-url>
   cd chat-application
   ```

2. **Instalacija zavisnosti**
   ```bash
   composer install
   npm install
   ```

3. **Konfiguracija**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Baza podataka**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Storage link**
   ```bash
   php artisan storage:link
   ```

6. **Pokretanje**
   ```bash
   php artisan serve
   ```

7. **Pristup aplikaciji**
   - URL: `http://localhost:8000`
   - Test korisnici: masa@example.com, luka@example.com, andrej@example.com
   - Lozinka: `password123`

## Testiranje

### Postman kolekcija
- Importujte Postman kolekciju za testiranje API-ja
- Testirajte sve endpointove sa autentifikacijom
- Proverite WebSocket funkcionalnosti

### Frontend testiranje
- Otvorite aplikaciju u browseru
- Registrujte se ili prijavite
- Testirajte real-time chat funkcionalnosti
- Proverite upload/download fajlova

## Sigurnosne mere

- **Token-based autentifikacija** - Laravel Sanctum
- **Hash enkripcija** - Sigurno čuvanje lozinki
- **Validacija** - Sve unose se validiraju
- **CSRF zaštita** - Laravel CSRF tokeni
- **Rate limiting** - Ograničenje zahteva
- **File validation** - Provera upload-ovanih fajlova

## Performanse

- **Keširanje** - Redis/file cache za česte upite
- **Paginacija** - Ograničenje broja rezultata
- **Eager loading** - Optimizovani upiti
- **Indexing** - Database indeksi za brže pretrage

## Zaključak

Chat aplikacija je kompletno implementirana sa svim zahtevanim funkcionalnostima. Aplikacija podržava real-time komunikaciju, napredne API funkcionalnosti, sigurnosne mere i moderni korisnički interfejs. Svi zahtevi za minimalnu i višu ocenu su ispunjeni.
