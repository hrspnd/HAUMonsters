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

# 📋 Task Distribution

---

## 🖥️ FRONT END (HTML/CSS)

### 🌐 Web App

#### 🔐 Authentication
| Task | Assignee |
|------|----------|
| Log In | Jin |
| Sign Up | Jin |

#### 🛡️ Admin Dashboard
| Task | Assignee |
|------|----------|
| Admin Dashboard | Amber |
| Manage Players (CRUD) | Amber & Yam |
| Manage Monsters (CRUD) | Yam |
| View Top 10 Monster Hunters | Jin / Amber |
| Show Monster Map | Jin / Yam / Amber |

#### 🎮 Player Dashboard
| Task | Assignee |
|------|----------|
| Player Dashboard | Amber |
| My Monsters | Amber |
| Catch Monsters | Amber & Yam |
| View Top 10 Monster Hunters *(same as Admin)* | Jin / Amber |
| Show Monster Map *(same as Admin)* | Jin / Yam / Amber |

#### ℹ️ About Us 
| Task | Assignee |
|------|----------|
| About Us | Yam |
| Info | Josie |

---

### 🎛️ Lambda Switch Interface
| Task | Assignee |
|------|----------|
| Switch Interface | Amber |

---

## ⚙️ BACK END (PHP)

| Task | Access | Assignee |
|------|--------|----------|
| Login | — | Yam |
| Signup | — | Yam |
| CRUD for Players | 🔒 Admin Only | Yam |
| CRUD for Monsters | 🔒 Admin Only | Yam |
| Top 10 Monster Hunter Leaderboard | 👥 Admin & Player | Yam |
| Catch Monsters | 🎮 Player Only | Yam |
| Monster Map | 👥 Admin & Player | Yam |

---

## 🔗 BACK END (Connection)

| Task | Assignee |
|------|----------|
| Tailscale VPN | Yam |
| Lambda | Yam |
| Connect Switch to EC2 | Yam |
| Connect Controller Play Button to VPN Link | Yam |

---

## 🗄️ DATABASE

**Database:** `haumonstersDB`

| Table | Assignee |
|-------|----------|
| `monsterstbl` | Yam |
| `locationstbl` | Yam |
| `monster_catchestbl` | Yam |
| `playerstbl` | Yam |

---

## 🔑 IAM Credentials

| Task | Assignee |
|------|----------|
| IAM Credentials Setup | Yam |

---

## 🎬 Video Recording

| Task | Assignee |
|------|----------|
| Walkthrough | Yam |
| Script | Josie |
| Voice Over | All |
| Edit | Yam |

---

## 🗺️ Network Diagram

| Task | Assignee |
|------|----------|
| Network Diagram | Josie / Yam |

---

## 📝 Documentation

| Task | Assignee |
|------|----------|
| Pricing and Estimate | Josie / Yam |
| Network Diagram | Josie / Yam |

---

## 👥 Team Summary

| Member | Responsibilities |
|--------|-----------------|
| **Jin** | Log In, Sign Up, View Top 10 Monster Hunters, Show Monster Map |
| **Amber** | Admin Dashboard (Manage Players & Monsters CRUD), Player Dashboard, Lambda Switch Interface |
| **Yam** | Catch Monsters (FE), Back End (PHP), Back End (Connection), Database, IAM Credentials, Video (Walkthrough & Edit), About Us page |
| **Josie** | About Us (Info), Video Script, Network Diagram, Documentation |
| **All** | Voice Over |

