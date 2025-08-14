# Commit 4: WebSocket funkcionalnosti za Chat aplikaciju

## Opis komita
Četvrti komit u projektu - implementacija WebSocket funkcionalnosti za real-time chat komunikaciju.

## Šta je urađeno:
- Kreiran MessageSent event za broadcast poruka
- Kreiran UserJoinedRoom event za broadcast kada korisnik uđe u sobu
- Kreiran UserLeftRoom event za broadcast kada korisnik napusti sobu
- Ažurirani kontroleri da koriste broadcast eventove
- Konfigurisana broadcasting konfiguracija za Pusher
- Dodane WebSocket funkcionalnosti u MessageController i RoomController

## Eventi:

### MessageSent
- Broadcastuje se kada se pošalje nova poruka
- Koristi PresenceChannel za room.{room_id}
- Šalje poruku sa podacima o korisniku

### UserJoinedRoom
- Broadcastuje se kada korisnik uđe u sobu
- Koristi PresenceChannel za room.{room_id}
- Šalje podatke o korisniku i sobi

### UserLeftRoom
- Broadcastuje se kada korisnik napusti sobu
- Koristi PresenceChannel za room.{room_id}
- Šalje podatke o korisniku i sobi

## WebSocket funkcionalnosti:
- Real-time slanje poruka
- Real-time obaveštenja o ulasku/izlasku korisnika
- Presence channels za praćenje online korisnika
- Broadcast samo ostalim korisnicima (toOthers())

## Konfiguracija:
- Broadcasting driver: Pusher
- Presence channels za svaku sobu
- Eventi se šalju preko WebSocket-a

## Autori:
- Masa Stevanovic
- Luka Simic  
- Andrej Djordjevic

## Datum: 7. avgust 2024.

## Napomene:
Za testiranje WebSocket funkcionalnosti potrebno je:
1. Kreirati Pusher nalog
2. Konfigurisati Pusher credentials u .env fajlu
3. Pokrenuti frontend aplikaciju sa WebSocket klijentom
