# Commit 2: Modeli za Chat aplikaciju

## Opis komita
Drugi komit u projektu - kreiranje modela i migracija za Chat aplikaciju.

## Šta je urađeno:
- Kreiran model Message sa migracijom
- Kreiran model Room sa migracijom
- Kreirana pivot tabela user_room sa migracijom
- Konfigurisani odnosi između modela (User, Room, Message)
- Pokrenute migracije za kreiranje tabela

## Modeli:
1. **User** - korisnici chat aplikacije (već postojao)
2. **Room** - chat sobe/kanali sa poljima:
   - name, description, type (public/private)
   - max_users, is_active
3. **Message** - poruke u chatu sa poljima:
   - user_id, room_id, content, type (text/image/file)
   - file_path, is_read
4. **UserRoom** - pivot tabela za vezu korisnika i soba sa poljima:
   - user_id, room_id, role (admin/moderator/member)
   - is_online, last_seen_at

## Autori:
- Masa Stevanovic
- Luka Simic  
- Andrej Djordjevic

## Datum: 7. avgust 2024.

## Napomene:
Modeli su konfigurisani sa odgovarajućim odnosima. U sledećim komitima će se dodavati kontroleri, API rute i WebSocket funkcionalnosti.
