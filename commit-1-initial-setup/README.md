# Commit 6: Napredne funkcionalnosti za Chat aplikaciju

## Opis komita
Šesti komit u projektu - dodavanje naprednih funkcionalnosti za višu ocenu.

## Šta je urađeno:
- Kreirani seeders i factories za test podatke
- Dodana paginacija i filtriranje za sobe i poruke
- Implementiran export podataka u CSV formatu
- Dodana funkcionalnost za upload i download fajlova
- Prošireni modeli sa dodatnim poljima
- Dodane nove API rute za napredne funkcionalnosti

## Napredne funkcionalnosti:

### Seeders i Factories
- **UserSeeder**: Kreira test korisnike (Masa, Luka, Andrej) + 10 random korisnika
- **RoomSeeder**: Kreira 4 test sobe (General Chat, Tech Talk, Gaming Room, Private Team Room)
- **MessageFactory**: Generiše test poruke sa faker podacima
- **DatabaseSeeder**: Pokreće sve seedere i kreira 50 test poruka

### Paginacija i Filtriranje
- **Sobe**: Filtriranje po tipu, pretraga po nazivu/opisu, sortiranje
- **Poruke**: Filtriranje po korisniku, tipu, datumu, pretraga po sadržaju
- **Paginacija**: Konfigurabilna broj stavki po stranici

### Export funkcionalnosti
- **ExportController**: Export poruka i statistika soba u CSV formatu
- **Rute**: `/api/export/messages/{roomId}`, `/api/export/room-stats/{roomId}`
- **CSV format**: Strukturirani podaci sa zaglavljima

### Upload/Download fajlova
- **Upload**: Podrška za fajlove do 10MB
- **Storage**: Fajlovi se čuvaju u public/uploads direktorijumu
- **Validacija**: Provera tipa fajla i veličine
- **Broadcast**: Real-time obaveštenja o upload-ovanim fajlovima
- **Download**: Siguran pristup fajlovima samo članovima sobe

### Prošireni modeli
- **Message**: Dodana polja file_name, file_size, file_type
- **Migracija**: add_file_upload_to_messages_table

## API rute za napredne funkcionalnosti:
- `POST /api/messages/upload` - Upload fajla
- `GET /api/messages/{id}/download` - Download fajla
- `GET /api/export/messages/{roomId}` - Export poruka u CSV
- `GET /api/export/room-stats/{roomId}` - Export statistike sobe

## Autori:
- Masa Stevanovic
- Luka Simic  
- Andrej Djordjevic

## Datum: 7. avgust 2024.

## Napomene:
Za testiranje naprednih funkcionalnosti:
1. Pokrenuti seeders: `php artisan db:seed`
2. Kreirati storage link: `php artisan storage:link`
3. Testirati export funkcionalnosti kroz Postman
4. Testirati upload fajlova kroz Postman ili frontend
5. Proveriti filtriranje i paginaciju sa query parametrima
