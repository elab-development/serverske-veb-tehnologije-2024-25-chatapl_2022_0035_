# 🚀 Laravel Chat Aplikacija - Kompletna dokumentacija

## 📋 Sadržaj
- [Opis projekta](#opis-projekta)
- [Autori](#autori)
- [Instalacija](#instalacija)
- [Pokretanje](#pokretanje)
- [API dokumentacija](#api-dokumentacija)
- [Funkcionalnosti](#funkcionalnosti)
- [Arhitektura](#arhitektura)

## 🎯 Opis projekta

**Laravel Chat Aplikacija** je napredna real-time chat aplikacija sa sledećim funkcionalnostima:

- ✅ **Real-time chat** sa WebSocket komunikacijom
- ✅ **Korisnički sistem** sa autentifikacijom
- ✅ **Chat sobe** sa porukama, fajlovima i slikama
- ✅ **REST API** sa 50+ endpoint-a
- ✅ **Napredne sigurnosne mere**
- ✅ **Integracija sa spoljnim API-jevima**
- ✅ **Sistem notifikacija**
- ✅ **Analitika i izveštaji**

## 👥 Autori

**Tim članovi:**
- **Maša Stevanović**
- **Andrej Đorđević** 
- **Luka Simić**

**Datum:** 20.08.2025.

## 🛠️ Instalacija

### Preduslovi
- PHP 8.2+
- Composer
- Node.js 18+
- SQLite (ili MySQL/PostgreSQL)

### Koraci instalacije

```bash
# 1. Kloniraj projekat
git clone <repository-url>

# 2. Instaliraj PHP dependencije
composer install

# 3. Kopiraj environment fajl
cp .env.example .env

# 4. Generiši aplikacijski ključ
php artisan key:generate

# 5. Kreiraj SQLite bazu podataka
touch database/database.sqlite

# 6. Pokreni migracije
php artisan migrate

# 7. Instaliraj frontend dependencije
npm install

# 8. Pokreni development server
php artisan serve
```

## 🚀 Pokretanje

### Kako se tačno pokreće aplikacija

#### **Korak 1: Otvori terminal i idi u projekat**
```bash
cd /PUTANJA DO PROJEKTA
```

#### **Korak 2: Proveri da li imaš sve potrebne fajlove**
```bash
ls -la
# Treba da vidiš: artisan, composer.json, .env, database/ folder
```

#### **Korak 3: Instaliraj PHP dependencije (ako nisi ranije)**
```bash
composer install
```

#### **Korak 4: Generiši app key (ako nisi ranije)**
```bash
php artisan key:generate
```

#### **Korak 5: Kreiraj SQLite bazu (ako ne postoji)**
```bash
touch database/database.sqlite
```

#### **Korak 6: Pokreni migracije**
```bash
php artisan migrate
```

#### **Korak 7: Pokreni Laravel server**
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

#### **Korak 8: U NOVOM terminalu, pokreni frontend (opciono)**
```bash
cd /Users/masastevanovic/Desktop/STEH\ PROJEKAT/Commit-20
npm run dev
```

### **Kako proveriti da li radi:**

#### **Opcija 1: Kroz browser**
- Otvori: `http://127.0.0.1:8000`
- Treba da vidiš Laravel welcome stranicu

#### **Opcija 2: Kroz Postman**
- Otvori Postman
- Testiraj: `GET http://127.0.0.1:8000/api/rooms`

#### **Opcija 3: Kroz terminal**
```bash
curl -X GET http://127.0.0.1:8000/api/rooms
```

### **Ako nešto ne radi:**

#### **Problem: "Class not found"**
```bash
composer dump-autoload
```

#### **Problem: "Permission denied"**
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

#### **Problem: "Database connection failed"**
```bash
# Proveri da li SQLite fajl postoji
ls -la database/database.sqlite

# Ako ne postoji, kreiraj ga
touch database/database.sqlite
```

#### **Problem: "Server already in use"**
```bash
# Proveri koji proces koristi port 8000
lsof -i :8000

# Ubij proces
kill -9 <PID>
```

### **Koraci za testiranje API-ja:**

#### **1. Registracija korisnika**
```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Korisnik",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### **2. Prijava korisnika**
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

#### **3. Kreiranje chat sobe**
```bash
# Prvo se uloguj i sačuvaj token
TOKEN="your_auth_token_here"

curl -X POST http://127.0.0.1:8000/api/rooms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "Test Soba",
    "description": "Test opis sobe",
    "type": "public"
  }'
```

### **Koraci za Postman:**

1. **Otvorite Postman**
2. **Kreirajte novu kolekciju** - "Laravel Chat API"
3. **Dodajte base URL:** `http://127.0.0.1:8000`
4. **Dodajte rute:**
   - `POST {{base_url}}/api/register`
   - `POST {{base_url}}/api/login`
   - `GET {{base_url}}/api/rooms`
   - `POST {{base_url}}/api/rooms`
   - `GET {{base_url}}/api/messages`
   - `POST {{base_url}}/api/messages`
   - `GET {{base_url}}/api/statistics/overall`
   - `GET {{base_url}}/api/external/weather?city=Belgrade`

### **Koraci za produkciju**
```bash
# Optimizuj aplikaciju
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend
npm run build
```

## 📚 API dokumentacija

### Autentifikacija

| Metoda | Endpoint | Opis |
|--------|----------|------|
| POST | `/api/register` | Registracija korisnika |
| POST | `/api/login` | Login korisnika |
| POST | `/api/logout` | Logout korisnika |
| GET | `/api/me` | Dobavi informacije o korisniku |

### Chat sobe

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/api/rooms` | Lista svih soba |
| POST | `/api/rooms` | Kreiraj novu sobu |
| GET | `/api/rooms/{id}` | Detalji sobe |
| PUT | `/api/rooms/{id}` | Ažuriraj sobu |
| DELETE | `/api/rooms/{id}` | Obriši sobu |
| POST | `/api/rooms/{id}/join` | Pridruži se sobi |
| POST | `/api/rooms/{id}/leave` | Napusti sobu |

### Poruke

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/api/messages` | Lista poruka |
| POST | `/api/messages` | Pošalji poruku |
| GET | `/api/messages/{id}` | Detalji poruke |
| PUT | `/api/messages/{id}` | Ažuriraj poruku |
| DELETE | `/api/messages/{id}` | Obriši poruku |

### Spoljni API servisi

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/api/external/weather` | Vremenska prognoza |
| POST | `/api/external/translate` | Prevod teksta |
| GET | `/api/external/news` | Aktuelne vesti |
| POST | `/api/external/currency` | Konverzija valuta |
| GET | `/api/external/crypto-price` | Cene kriptovaluta |

### Statistike i analitika

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/api/statistics/overall` | Opšte statistike |
| GET | `/api/statistics/room-stats` | Statistike soba |
| GET | `/api/statistics/user-stats` | Statistike korisnika |

### Eksport podataka

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/api/export/messages/{roomId}` | Eksport poruka u CSV |
| GET | `/api/export/room-stats/{roomId}` | Eksport statistika sobe |

### Notifikacije

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/api/notifications` | Lista notifikacija |
| GET | `/api/notifications/unread-count` | Broj nepročitanih |
| PATCH | `/api/notifications/{id}/read` | Označi kao pročitano |
| DELETE | `/api/notifications/{id}` | Obriši notifikaciju |

## 🔧 Funkcionalnosti

### Sigurnost
- **Rate Limiting** - Ograničenje zahteva po IP adresi
- **XSS Protection** - Sanitizacija korisničkih unosa
- **CSRF Protection** - Zaštita od CSRF napada
- **SQL Injection Protection** - Detekcija SQL injection napada
- **Security Headers** - HTTP security headers
- **Audit Logging** - Praćenje svih događaja

### Baza podataka
- **Database Triggers** - Automatski pokretani SQL blokovi
- **Database Views** - Virtuelne tabele za kompleksne upite
- **Advanced Indexing** - Optimizacija performansi
- **Database Constraints** - Referentni integritet
- **Soft Deletes** - Logičko brisanje podataka

### Web servisi
- **Weather API** - Integracija sa OpenWeatherMap
- **Translation API** - Automatski prevod poruka
- **News API** - Prikaz vesti u sobi
- **Crypto API** - Informacije o kriptovalutama

### Napredne funkcionalnosti
- **File Upload** - Slanje slika i fajlova
- **Caching** - Keširanje podataka za bolje performanse
- **Search & Filtering** - Napredna pretraga i filtriranje
- **Pagination** - Straničenje rezultata
- **Real-time Updates** - Trenutna ažuriranja kroz WebSocket

## 🏗️ Arhitektura

### Tehnologije
- **Backend**: Laravel 12.2.0
- **Database**: SQLite (produkcija: MySQL/PostgreSQL)
- **Authentication**: Laravel Sanctum
- **Frontend**: Vue.js + Vite
- **Styling**: Tailwind CSS

### Struktura projekta
```
Commit-20/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # API kontroleri
│   │   └── Middleware/      # Sigurnosni middleware-i
│   ├── Models/              # Eloquent modeli
│   ├── Services/            # Poslovna logika
│   ├── Events/              # Event-ovi
│   └── Notifications/       # Notifikacije
├── database/
│   ├── migrations/          # Migracije baze podataka
│   ├── seeders/             # Seed podaci
│   └── factories/           # Factory klase
├── routes/
│   └── api.php             # API rute
└── resources/
    └── views/              # Blade template-ovi
```

### Modeli i relacije
- **User** - Korisnici sistema
- **Room** - Chat sobe
- **Message** - Poruke u sobama
- **AuditLog** - Log aktivnosti
- **Pivot tabela** - user_room (many-to-many relacija)

## 📊 Implementirane funkcionalnosti

### Minimalni zahtevi ✅
- ✅ Laravel aplikacija
- ✅ 3+ povezana modela (User, Room, Message, AuditLog)
- ✅ 5+ tipova migracija (kreiranje, izmena, dodavanje, brisanje, indeksi)
- ✅ REST API konvencija
- ✅ JSON format za sve odgovore
- ✅ Autentifikacija (login, logout, register)
- ✅ Zaštita ruta (middleware)

### Dodatni zahtevi za višu ocenu ✅
- ✅ **Paginacija i filtriranje** - StatisticsController
- ✅ **Reset lozinke** - PasswordResetController
- ✅ **3+ uloge** - admin, user, guest
- ✅ **Upload fajlova** - FileController
- ✅ **Keširanje** - CacheService
- ✅ **Seeders/Factories** - Svi modeli
- ✅ **Pretraga** - Search funkcionalnost
- ✅ **Eksport** - CSV, PDF, ICS format
- ✅ **Javni servis** - ExternalApiController

### Zahtevi za višu ocenu ✅
- ✅ **4+ povezane tabele** - users, rooms, messages, audit_logs, user_room
- ✅ **MVC pattern** - Laravel framework
- ✅ **Sigurnost (2+ kriterijuma)** - CSRF, XSS, SQL injection zaštita
- ✅ **Napredna manipulacija** - JOIN-ovi, agregacija, trigeri
- ✅ **REST servis sa 4 metode** - POST, GET, PUT, DELETE
- ✅ **Ugnježdene rute** - `/rooms/{id}/messages`
- ✅ **2+ javna REST servisa** - Weather, Translation, News, Crypto API

## 🎯 Karakteristične funkcionalnosti

### 1. Real-time Chat System
Implementiran kroz Laravel Events i WebSocket komunikaciju, omogućavajući trenutnu razmenu poruka između korisnika.

### 2. Napredna sigurnost
Implementiran sistem zaštite od XSS, CSRF, SQL injection napada kroz custom middleware klase.

### 3. Integracija sa spoljnim API-jevima
Sistem automatski dohvata vremenske informacije, prevodi tekst i prikazuje aktuelne vesti.

### 4. Audit logging
Sve akcije korisnika se automatski beleže u audit log za sigurnosne potrebe.

## 📄 Licenca

Ovaj projekat je licenciran pod MIT licencom.

---

**Napomena**: Ova aplikacija je projekat iz predmeta serverske web tehnologije.

**Datum završetka:** 20.08.2025.
