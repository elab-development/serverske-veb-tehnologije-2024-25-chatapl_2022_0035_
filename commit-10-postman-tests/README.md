# Commit 10: Postman Testovi za Chat API

## 📋 Opis

Ovaj commit sadrži kompletan set Postman testova za Chat API Laravel aplikaciju. Testovi pokrivaju sve funkcionalnosti aplikacije i omogućavaju detaljno testiranje kroz Postman interfejs.

## 📁 Sadržaj

- `POSTMAN_TESTS_CHAT_API.txt` - Kompletan set testova sa svim rutama
- `README.md` - Ova dokumentacija

## 🚀 Funkcionalnosti koje se testiraju

### 1. Autentifikacija
- ✅ Registracija korisnika
- ✅ Login korisnika
- ✅ Logout korisnika
- ✅ Dohvatanje trenutnog korisnika

### 2. Upravljanje sobama (Rooms)
- ✅ Dohvatanje svih soba (sa paginacijom i filtriranjem)
- ✅ Kreiranje nove sobe
- ✅ Dohvatanje specifične sobe
- ✅ Ažuriranje sobe
- ✅ Brisanje sobe
- ✅ Pridruživanje sobi
- ✅ Napuštanje sobe

### 3. Upravljanje porukama (Messages)
- ✅ Dohvatanje poruka (sa filtriranjem)
- ✅ Slanje poruke
- ✅ Dohvatanje specifične poruke
- ✅ Ažuriranje poruke
- ✅ Brisanje poruke
- ✅ Upload fajla
- ✅ Download fajla

### 4. Eksport podataka
- ✅ Eksport poruka u CSV
- ✅ Eksport statistika sobe u CSV

### 5. Statistike
- ✅ Ukupne statistike
- ✅ Statistike soba
- ✅ Statistike korisnika
- ✅ Brisanje keša

### 6. Reset lozinke
- ✅ Zahtev za reset lozinke
- ✅ Reset lozinke
- ✅ Promena lozinke

## 🛠️ Kako koristiti

### 1. Postman Setup
1. Otvorite Postman
2. Kreirajte novu kolekciju "Chat API"
3. Dodajte environment sa varijablama:
   - `BASE_URL`: `http://localhost:8000/api`
   - `TOKEN`: (prazno, popuniće se nakon login-a)

### 2. Pokretanje aplikacije
```bash
cd commit-9-final-overview
php artisan serve
```

### 3. Redosled testiranja
1. Registracija korisnika
2. Login korisnika (sačuvaj token)
3. Kreiranje sobe
4. Pridruživanje sobi
5. Slanje poruke
6. Dohvatanje poruka
7. Upload fajla
8. Eksport podataka
9. Statistike
10. Reset lozinke
11. Logout

## 📊 Očekivani rezultati

- **Status kodovi**: 200, 201, 422, 401, 403, 404
- **JSON response**: Sve rute vraćaju JSON format
- **Token autentifikacija**: Laravel Sanctum
- **Validacija**: Laravel validation rules
- **Paginacija**: Za rooms i messages
- **File upload**: Podržava različite tipove fajlova
- **Caching**: Implementiran za performanse

## 🔧 Napredne funkcionalnosti

### Automatsko postavljanje tokena
```javascript
// Pre-request script
if (pm.response.code === 200) {
    const response = pm.response.json();
    if (response.data && response.data.token) {
        pm.environment.set("TOKEN", response.data.token);
    }
}
```

### Validacija response-a
```javascript
// Test script
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function () {
    const response = pm.response.json();
    pm.expect(response).to.have.property('success');
});
```

## 📝 Napomene

- Sve rute osim `/register` i `/login` zahtevaju Authorization header
- Token se dobija nakon uspešne registracije/login-a
- File upload podržava različite tipove fajlova
- Paginacija je implementirana za rooms i messages
- Caching je implementiran za performanse
- Real-time funkcionalnosti zahtevaju Pusher konfiguraciju

## 👥 Tim

- **Masa Stevanovic**
- **Luka Simic**
- **Andrej Djordjevic**

## 📅 Datum

2025-08-07

## 🎯 Cilj

Kompletan set testova za validaciju svih funkcionalnosti Chat API aplikacije kroz Postman interfejs, omogućavajući detaljno testiranje i dokumentaciju API-ja. 