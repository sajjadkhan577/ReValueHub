# <div align="center">♻️ ReValue Hub</div>

<div align="center">

**A Community Reuse Marketplace for Giving Pre-Loved Items a Second Life**

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)](#)
[![MySQL](https://img.shields.io/badge/Database-MariaDB%20/%20MySQL-4479A1?logo=mysql&logoColor=white)](#)
[![TailwindCSS](https://img.shields.io/badge/Styled%20with-TailwindCSS-38B2AC?logo=tailwind-css&logoColor=white)](#)
[![JavaScript](https://img.shields.io/badge/Frontend-Vanilla%20JS-F7DF1E?logo=javascript&logoColor=black)](#)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](#)

**[Screenshots](#-screenshots) • [Key Features](#-key-features) • [Quick Start](#-quick-start) • [API Docs](#-api-documentation)**

</div>

---

## 🎯 About The Project

ReValue Hub is a **peer-to-peer community reuse marketplace** that empowers local communities to donate, browse, and request pre-owned items instead of throwing them away. Every item listed on ReValue Hub — from sofas to smartphones to textbooks — finds a new owner, reducing household waste while strengthening neighborhood connections.

> **Our Mission:** To build a sustainable circular economy by making item reuse simple, secure, and accessible for everyone. Give your used items a *second life*. ♻️

---

## ✨ Key Features

| Category | Features |
|---|---|
| 🔐 **Authentication** | User registration / login with bcrypt password hashing • Role-based access (User / Admin) • Bearer token sessions • Profile & avatar management |
| 🛋️ **Item Listings** | Create, browse, edit, or delete donations • **11 categories** (Furniture, Electronics, Clothing, Home, Books, Medicine, Health, Garden, Tools, Sports, Other) • Multi-image uploads • Location + condition tagging • Admin approval workflow |
| 🔍 **Search & Discovery** | Full keyword search (title + description) • Category sidebar filtering • Featured / randomised spotlight on landing page • Infinite browse grid |
| 🤝 **Requests & Donations** | Request any listed item (with duplicate protection) • Donor approves / rejects requests • One-tap *Complete Donation* records hand-off in donations ledger |
| 💬 **Messaging** | Peer-to-peer inbox • Auto-polling for new messages • Message threads per item • Unread indicators |
| 🔔 **Notifications** | Database-stored notification system for requests, approvals, and volunteer status • Read/unread tracking |
| 🧑‍💼 **User Dashboard** | My donations / My requests / Inbox shortcuts • Personal donation impact tracker • Profile editing |
| 🛡️ **Admin Panel** | System stats at a glance • Pending item approval queue (Approve / Reject / Delete) • Full user management (View / Flag / Delete) • Volunteer application review • Invite other administrators |
| 🫱‍🫲 **Volunteers** | Public "Become a Volunteer" application form with skills + availability + motivation fields |
| 📱 **Responsive UI** | Fully responsive Tailwind CSS design • Works on phones, tablets, and desktops • Smooth animations & glassmorphism styling |

---

## 🏗️ Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | Static HTML5 • Vanilla JavaScript (ES6+) • [Tailwind CSS v3](https://tailwindcss.com/) • Google Fonts (Manrope) • Material Symbols Icons |
| **Backend** | **PHP 8+** (MySQLi procedural API) • JSON REST endpoints |
| **Database** | MySQL / MariaDB — InnoDB engine with Foreign Key relationships |
| **Auth** | `password_hash()` / `password_verify()` (bcrypt, cost=10) • Bearer `dummy-token-<userId>` tokens |
| **File Uploads** | Native PHP `move_uploaded_file()` to `uploads/` (images up to 10 MB) |
| **Server** | XAMPP (Apache + MariaDB) • Cross-origin-safe JSON responses (UTF-8) |

---

## 📁 Project Structure

```
ReValueHub/
├── api/                          # Backend REST endpoints (all return JSON)
│   ├── auth/                     # Authentication
│   │   ├── register.php          # POST — sign up
│   │   ├── login.php             # POST — sign in (returns JWT-style token)
│   │   ├── me.php                # GET  — current user profile
│   │   ├── profile.php           # PUT  — edit profile / avatar
│   │   ├── invite_admin.php      # POST — admin invites another admin
│   │   └── social-login.php      # POST — (placeholder) OAuth
│   ├── items.php                 # GET list/search/filter  •  POST new item  •  PUT edit
│   ├── pending_items.php         # GET pending items (admin)
│   ├── update_item_status.php    # POST Approve / Reject / Donated (admin + donor)
│   ├── complete_donation.php     # POST finalise handoff → writes donations ledger
│   ├── requests.php              # GET / POST item requests  •  PUT approve/reject
│   ├── messages.php              # GET inbox / thread  •  POST send message
│   ├── notifications.php         # GET list  •  POST mark read
│   ├── stats.php                 # GET public stats (users, items, pending, requests)
│   ├── profile.php               # GET any user's public profile + their listings
│   ├── recent_users.php          # GET newest members
│   ├── items/user.php            # GET listings for a specific donor
│   ├── requests/user.php         # GET requests made / received by a user
│   ├── volunteer/apply.php       # POST submit a volunteer application
│   └── admin/                    # Admin-only endpoints
│       ├── stats.php             # Extended dashboard stats
│       ├── users.php             # List / delete users
│       └── volunteer-applications.php  # List / approve / reject applications
├── uploads/                      # User-uploaded item images & avatars
├── assets/                       # Static brand assets (logo, favicon)
├── js/                           # Frontend scripts
│   ├── app.js                    # Main logic: auth, grid render, messaging, nav
│   ├── admin-dashboard.js        # Admin panel React-style UI logic
│   └── admin-login.js            # Admin sign-in page
├── landing.html                  # Public homepage / discovery feed
├── browse.html                   # Browse items + sidebar category filters
├── discovery.html                # Alternative discovery page (spotlight)
├── item-detail.html              # Single item view, request & message buttons
├── list-item.html                # Donor multi-step "List a new item" form
├── register.html · login.html    # Auth pages
├── dashboard.html                # Logged-in user's personal dashboard
├── profile.html                  # Public donor profile page
├── messages.html                 # User inbox & conversations
├── admin-login.html · admin-dashboard.html
├── about_us.html · mission_page.html · how_it_works.html
├── contact_us.html · help_center.html · safety_center.html
├── become_a_volunteer.html · community_guidelines.html
├── privacy_policy.html · terms_of_service.html · report_an_issue.html
├── db.php                        # Shared database connection (tries 2 credential sets)
├── schema.sql                    # Full CREATE TABLE DDL + seed data
├── setup_db.php                  # One-click installer (runs schema.sql)
├── reseed_pakistani_data.php     # Optional: Seeds 12 Pakistani users + 55 realistic items
└── index.php                     # Entry router (loads landing.html)
```

---

## 🚀 Quick Start

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) *(or any stack with PHP 8+ + MariaDB/MySQL 10+)*
- A modern browser (Chrome, Edge, Firefox, Safari)

### Step 1 — Clone / Copy project
```bash
# Copy the project into your XAMPP htdocs folder
cp -r ReValueHub /c/xampp/htdocs/
# or on Linux/Mac:
cp -r ReValueHub /opt/lampp/htdocs/
```

### Step 2 — Start Apache + MySQL
Open the **XAMPP Control Panel** and click **Start** on *Apache* and *MySQL*.

### Step 3 — Create the database
Run the one-click installer in your browser:
```
http://localhost/ReValueHub/setup_db.php
```
This executes [schema.sql](schema.sql) — it creates the `revalue_hub` database, all tables (`users`, `items`, `requests`, `messages`, `notifications`, `donations`, `volunteer_applications`), and seeds a default admin.

> **Alternative (manual):** Open `http://localhost/phpmyadmin`, create a database named `revalue_hub` with `utf8mb4_unicode_ci` collation, then import `schema.sql`.

### Step 4 — (Optional) Seed demo data
To populate the site with **55 realistic pre-loved items across all 11 categories**, donated by 12 Pakistani users, run:
```
http://localhost/ReValueHub/reseed_pakistani_data.php
```

### Step 5 — Open the website
```
http://localhost/ReValueHub/
```

✅ You're in! Use one of the [demo accounts below](#-demo-accounts) to sign in.

---

## 👤 Demo Accounts

| Role | Email | Password |
|---|---|---|
| 🛡️ **Administrator** | `admin@revalue.com` | `admin123` |
| 👤 Regular User | `ahmed.khan@example.com` | `password123` |
| 👤 Regular User | `ayesha.siddiqui@example.com` | `password123` |
| 👤 Regular User | `zainab.malik@example.com` | `password123` |
| 👤 Regular User | `m.bilal@example.com` | `password123` |
| 👤 Regular User | `fatima.raza@example.com` | `password123` |
| 👤 Regular User | `hassan.javed@example.com` | `password123` |
| 👤 Regular User | `usman.sheikh@example.com` | `password123` |
| 👤 Regular User | `maryam.n@example.com` | `password123` |
| 👤 Regular User | `omar.farooq@example.com` | `password123` |
| 👤 Regular User | `sanaullah.a@example.com` | `password123` |
| 👤 Regular User | `hira.khan@example.com` | `password123` |
| 👤 Regular User | `arslan.m@example.com` | `password123` |

*All passwords use verified **bcrypt** hashes generated with `PASSWORD_BCRYPT, cost=10`.*

---

## 📑 API Documentation

All endpoints live under `/api/` and return JSON with UTF-8 charset. Send authentication as:
```
Authorization: Bearer dummy-token-<userId>
```

### 🔐 Authentication
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/register.php` | No | Create new user → returns `{ token, user }`. Body: `name`, `email`, `password` |
| POST | `/api/auth/login.php` | No | Sign in → returns `{ token, user }`. Body: `email`, `password` |
| GET  | `/api/auth/me.php` | Yes | Current user profile |
| PUT  | `/api/auth/profile.php` | Yes | Update name/email/avatar. Multipart form supports `avatar` upload |
| POST | `/api/auth/invite_admin.php` | Admin | Invite another admin. Body: `name`, `email` (must contain "admin") |

### 🛋️ Items
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET  | `/api/items.php` | No | List items. Query params: `id`, `search`, `category`, `location`, `status` (default `approved`), `limit`, `featured=1` (rotating shuffle) |
| POST | `/api/items.php` | Yes | List new item. Multipart form: `title`, `category`, `description`, `location`, `condition`, `image` upload, optional `mfgDate`/`expDate` (medicine). Creates item with `pending` status |
| PUT  | `/api/items.php` | Yes (Owner) | Edit own item — same fields as POST + `id`. File upload replaces image |
| POST | `/api/update_item_status.php` | Admin / Owner | `{ id, status }` — approve, reject, or mark item as `donated` |
| GET  | `/api/pending_items.php` | Admin | List all items with `status='pending'` for moderation |
| GET  | `/api/items/user.php?id=<donor_id>` | No | Public list of items by a donor |

**Item Categories (11):** `furniture`, `electronics`, `clothing`, `home`, `books`, `medicine`, `health`, `garden`, `tools`, `sports`, `other`

### 🤝 Requests & Donations
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET  | `/api/requests.php` | Yes | Requests related to current user (made or received) |
| POST | `/api/requests.php` | Yes | `{ item_id }` — request an item (rejects self + duplicates) |
| PUT  | `/api/requests.php` | Yes (Donor) | `{ id, status: 'closed' | 'cancelled' }` — approve / reject |
| POST | `/api/complete_donation.php` | Yes (Donor) | Finalise hand-off → writes row to `donations` + auto-updates item + request |
| GET  | `/api/requests/user.php` | Yes | Detailed breakdown (received requests, pending, completed) |

### 💬 Messages & Notifications
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET  | `/api/messages.php?user_id=<peer>` | Yes | Thread with peer; or inbox if no param |
| POST | `/api/messages.php` | Yes | Send message → `{ receiver_id, item_id?, message }` |
| GET  | `/api/notifications.php` | Yes | List notifications |
| POST | `/api/notifications.php` | Yes | Mark as read → `{ id, is_read: true }` |

### 🧮 Stats & Profiles
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET  | `/api/stats.php` | No | Public metrics `{ users, items, items_pending, requests }` |
| GET  | `/api/profile.php?id=<userId>` | No | Full public donor profile + approved item listings |
| GET  | `/api/recent_users.php` | No | Newest registered members list |

### 🛡️ Admin
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET  | `/api/admin/stats.php` | Admin | Extended dashboard statistics |
| GET  | `/api/admin/users.php` | Admin | List all users; POST `{ id, action: 'delete' }` deletes a user |
| GET  | `/api/admin/volunteer-applications.php` | Admin | List applications; POST `{ id, status: 'approved' | 'rejected' }` |
| POST | `/api/volunteer/apply.php` | No | Public: submit volunteer application form |

---

## 🗄️ Database Schema

**Tables:** `users` → `items` → `requests` → `donations` (chain of item lifecycle). Supporting tables: `messages`, `notifications`, `volunteer_applications`.

```
users (1) ──────< items           (FK: items.donor_id → users.id)
                    │
                    ├─< requests   (FK: requests.item_id → items.id)
                    │                 (FK: requests.requester_id → users.id)
                    └─< donations  (FK: donations.item_id → items.id)
                                      (FK: donations.donor_id, recipient_id → users.id)
                                      (FK: donations.request_id → requests.id)
messages      (sender_id, receiver_id, item_id → FK users, items)
notifications (user_id → FK users)
volunteer_applications (user_id, reviewed_by → FK users)
```

See complete DDL + indexes in [schema.sql](schema.sql).

---

## 🖼️ Screenshots

| Page | Purpose |
|---|---|
| **Landing / Discovery Feed** | Hero, featured categories, spotlight carousel, latest donations |
| **Browse Items** | Category sidebar, search bar, responsive item cards grid |
| **Item Detail** | Full description, location preview, donor info, Request & Message CTAs |
| **List New Item** | 3-step form: details → image upload → pickup availability |
| **User Dashboard** | My Donations, My Requests, Donation Impact summary, Inbox shortcut |
| **User Profile** | Public donor profile with avatar, bio, and listings (Logout visible ONLY to owner) |
| **Messages** | Inbox with threads per item, auto-sync, unread badges |
| **Admin Dashboard** | Stats cards + Pending Items queue + Users table + Volunteer approvals |
| **Become a Volunteer** | Application form (skills, availability, motivation) |

---

## 🛣️ Roadmap / Future Improvements

- [ ] **Real-time messaging** with WebSockets (currently uses polling)
- [ ] **Email / SMS notifications** for new requests and messages
- [ ] **Google / Facebook social login** (buttons exist; backend placeholder only)
- [ ] **Reviews & trust system** for donors / requesters
- [ ] **Map-based search** (show markers instead of text locations)
- [ ] **Progressive Web App** + offline support
- [ ] **PDF receipts** for donation impact reports
- [ ] **Mobile apps** (Flutter / React Native)

---

## 🧪 Testing

See [TEST_PLAN.md](TEST_PLAN.md) for 70+ structured test cases covering:
- Authentication (Register, Login, Logout, Protected routes, Admin invite)
- Items CRUD (Create, list/search/filter, read, edit, delete, status transitions)
- Requests (Create, approval flow, duplicate protection)
- Messaging (Send, receive, unread, threading)
- Admin panel (Approve items, user management, volunteer approvals)
- Role boundaries, edge cases, and validation errors

---

## 🧱 Built With Love & Care

- 🔐 Secure by default — `real_escape_string` + `password_verify` + FK `ON DELETE CASCADE`
- 🧩 Modular endpoints in `/api/*` (easy to extend with Express if you prefer Node)
- 🎨 Beautiful Manrope-typed Tailwind UI, fully responsive
- 🇵🇰 Designed & tested with authentic Pakistani donor profiles, cities, and products
- 🚀 Zero npm installs, zero build steps — just drop into XAMPP and run!

---

## 🤝 Contributing

Community contributions are welcome!

1. Fork the repo
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📜 License

Distributed under the **MIT License**. See `LICENSE` file for more information.

---

## 🙏 Acknowledgements

- **Tailwind CSS** — beautiful utility-first styling
- **Material Symbols Icons** — pixel-perfect glyphs
- **Google Fonts (Manrope)** — clean modern typography
- **Unsplash CDN** — realistic item imagery
- All the open-source libraries and communities that made this project possible ♥️

---

<div align="center">

### **Give every item a second life.** ♻️

*Made with 💚 by the ReValue Hub team.*

</div>
