-- Postavljanje masa@example.com kao admin u svim sobama

-- Prvo pronađi ID korisnika masa@example.com
SELECT id, name, email FROM users WHERE email = 'masa@example.com';

-- Dodaj korisnika u sve sobe kao admin ili ažuriraj postojeću ulogu
INSERT OR REPLACE INTO user_room (user_id, room_id, role, is_online, last_seen_at, created_at, updated_at)
SELECT 
    u.id as user_id,
    r.id as room_id,
    'admin' as role,
    0 as is_online,
    NULL as last_seen_at,
    datetime('now') as created_at,
    datetime('now') as updated_at
FROM users u, rooms r
WHERE u.email = 'masa@example.com';

-- Proveri rezultat
SELECT 
    u.name as user_name,
    u.email as user_email,
    r.name as room_name,
    ur.role as user_role
FROM user_room ur
JOIN users u ON ur.user_id = u.id
JOIN rooms r ON ur.room_id = r.id
WHERE u.email = 'masa@example.com'; 