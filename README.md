# MOBA Tropz League Manager

Isang web-based tournament management system na idinisenyo para sa eSports (Mobile Legends, Wild Rift, etc.). Ang application na ito ay tumutulong sa mga organizers na i-manage ang tournaments, teams, player stats, at leaderboards nang madali.

## Mga Tampok (Features)
- **Pro Dashboard:** Visual na overview ng mga aktibong tournament.
- **Tournament Creation:** Suporta para sa iba't ibang formats gaya ng Round Robin at Elimination.
- **Player Statistics:** Detalyadong input para sa KDA, Hero Damage, at Gold.
- **MVP Leaderboard:** Automatic na kalkulasyon ng MVP base sa performance score.
- **Standings:** Real-time na update ng rankings ng bawat team.

## Paano i-install (Local Setup)
1. I-clone ang repository na ito sa iyong `xampp/htdocs` folder.
2. Gumawa ng database sa phpMyAdmin (halimbawa: `moba_db`).
3. I-import ang `database.sql` file na nasa root folder.
4. I-copy ang `config/db.php.example` at i-rename ito bilang `config/db.php`, pagkatapos ay ilagay ang iyong database credentials.
5. Buksan ang browser at pumunta sa `http://localhost/moba-tropz-league-v1/`.

---
*Developed with a modern eSports aesthetic.*