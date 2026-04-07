# HAUMonsters

> A Pokémon-inspired monster-catching web application built on AWS cloud infrastructure — final project for **6CloudCom** at Holy Angel University, School of Computing.

---

## Overview

HAUMonsters is a location-based monster-catching game where players roam the map, detect nearby monsters via GPS, and compete on a global leaderboard. The backend is hosted on **AWS EC2** across two VPC zones (Paris & North Virginia), secured with **Tailscale VPN** and connected via **VPC Peering**.

---

## File Structure

```
HAUMonsters/
├── about.php               # About page
├── admin_dashboard.php     # Admin home dashboard
├── catch.php               # Catch monster interface
├── catch_monsters.php      # GPS-based monster detection logic
├── leaderboard.php         # Top 10 monster hunters
├── login.php               # Player login
├── logout.php              # Session logout (no front end)
├── map.php                 # Monster spawn map
├── monsters.php            # Monster management (Admin)
├── player_dashboard.php    # Player home dashboard
├── player_monsters.php     # Player's caught monsters
├── players.php             # Player management (Admin)
└── signup.php              # Player registration
```

---

## Project Info

| | |
|---|---|
| **Subject** | 6CloudCom |
| **Type** | Final Project |

---
