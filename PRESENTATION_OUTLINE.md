# ReValue Hub — Final Year Project Defence Presentation Outline

*12 slides · ~10–15 minutes presentation + ~5 minutes Q&A*

---

## Slide 1 — Title Slide

**Title:** ReValue Hub — A Community Reuse Marketplace

**Subtitle:** Final Year Project Defence

**Content:**
- Project by: *[Your Name]*
- Institution: *[Your Institution]*
- Supervisor: *[Supervisor Name]*
- Date: *[Defence Date]*

**Visual:**
- ReValue Hub logo (`Logo.png` or `assets/logo.png`) centred
- Clean, minimal slide with project name prominent
- Background could use the Deep Blue (#004ac6) primary colour from the design system

---

## Slide 2 — Problem Statement

**Title:** The Problem We Set Out to Solve

**Content:**
- Modern consumption patterns generate vast amounts of reusable goods that are prematurely discarded
- Communities lack a simple, secure, centralised platform to offer or request these items
- Existing solutions (classifieds, social media groups) lack trust mechanisms, request workflows, and administrative oversight
- Result: avoidable waste and missed opportunities for local reuse

**Visual:** 
- Side-by-side comparison: "Before" (cluttered garage/donation bin) → "After" (clean UI of `landing.html` or `browse.html`)
- Or a simple infographic: "Household waste statistics" → "Items that could be reused"

---

## Slide 3 — Aims & Objectives

**Title:** Project Aims & Objectives

**Content:**
- **Aim:** Build a secure platform for listing reusable items, managing requests, and facilitating community reuse
- **Objective 1:** User authentication with role-based access (user vs admin)
- **Objective 2:** Item listing, browsing (with search/filter), and lifecycle management
- **Objective 3:** Request workflow — create, track, approve/reject with ownership validation
- **Objective 4:** Administrative dashboard for moderation (users, items, system stats)

**Visual:**
- Four numbered icons or boxes, one per objective, with a brief label under each
- Could use Material Symbols icons: `person_add`, `inventory_2`, `handshake`, `admin_panel_settings`

---

## Slide 4 — System Architecture

**Title:** System Architecture

**Content:**
- **Three-tier architecture:** Client → Server → Database
- **Client Layer:** Static HTML pages styled with Tailwind CSS (CDN), vanilla JavaScript (`js/app.js`) for dynamic behaviour
- **Server Layer:** Express.js REST API on port 3000, with legacy PHP API for auxiliary endpoints (messages, notifications, volunteers)
- **Data Layer:** MySQL/MariaDB accessed via Sequelize ORM with three core models (User, Item, Request) plus auxiliary tables

**Visual:**
- Architecture diagram showing the three tiers with arrows
- Client box → `landing.html`, `browse.html`, `dashboard.html`, etc.
- Server box → Express (`/api/auth`, `/api/items`, `/api/requests`, `/api/admin`) + PHP (`/api/*.php`)
- Database box → `revaluehub` database with table names listed
- Can use the ASCII diagram from FINAL_REPORT.md section 6.1 or build a cleaner one in Draw.io/LucidChart

---

## Slide 5 — Database Design & ERD

**Title:** Database Design — Entity Relationship Diagram

**Content:**
- Three core Sequelize models: **User**, **Item** (table: `posts`), **Request**
- **User** → **Item**: One-to-many (a user can post many items)
- **User** → **Request**: One-to-many (a user can make many requests)
- **Item** → **Request**: One-to-many (an item can receive many requests)
- Auxiliary tables (messages, notifications, volunteer_applications) handled by PHP backend

**Visual:**
- ERD diagram (from `FYP_PROPOSAL.md` Mermaid or a clean PNG version)
- Three entity boxes connected with crow's-foot notation
- Show key columns: User(id, name, email, role), Item(id, title, category, location, user_id FK), Request(id, status, item_id FK, requester_id FK)

---

## Slide 6 — Authentication & Security

**Title:** Authentication & Security

**Content:**
- **JWT-based authentication** — tokens issued on register/login, expire in 30 days
- Token sent via `Authorization: Bearer <token>` header, verified by `middleware/auth.js` `protect` middleware
- **Passwords hashed** with bcrypt (12 salt rounds) via Sequelize `beforeCreate` hook
- **Role-based access control** — `restrictTo('admin')` middleware on admin routes, ownership checks on item/request mutations
- **File upload restrictions** — Multer enforces file type (JPEG/PNG/WebP/GIF) and size limits (10MB items, 5MB avatars)

**Visual:**
- Flow diagram showing: 
  1. User enters email + password → POST `/api/auth/login`
  2. Server validates with bcrypt → returns JWT
  3. Client stores token in `localStorage`
  4. Subsequent requests include `Bearer <token>`
  5. `protect` middleware verifies → `req.user` attached

---

## Slide 7 — Key Features Demo (with Screenshots)

**Title:** Key Features — Screenshot Walkthrough

**Content:**
- **User Registration & Login:** Register form (`register.html`) → JWT issued → redirected to dashboard
- **Browsing Items:** Item grid (`browse.html`) with search bar and category sidebar filters
- **Donating an Item:** Multi-step form (`list-item.html`) with drag-and-drop image upload (up to 10 images)
- **Request Workflow:** "Request Item" button on detail page → item owner receives request → approves/rejects via PATCH
- **Admin Dashboard:** Stats overview, pending items queue, user management, dark mode toggle

**Visual (use multiple screenshot callouts on one slide or a grid):**
- Screenshot 1: `Register page.png` or `register.html`
- Screenshot 2: `Browse items.png` or `browse.html` with search term entered
- Screenshot 3: `List new item.png` or `list-item.html` with image upload area
- Screenshot 4: `Item detail.png` or `item-detail.html` showing "Request Item" button
- Screenshot 5: `Admin dashboard.png` or `admin-dashboard.html` showing stats cards
- All screenshots exist in the project root directory

---

## Slide 8 — REST API Overview

**Title:** REST API Endpoints

**Content:**
- **Auth** (5 endpoints): `POST /register`, `POST /login`, `POST /logout`, `GET /me`, `PUT /profile`
- **Items** (5 endpoints): `GET /`, `GET /:id`, `GET /user`, `POST /`, `DELETE /:id`
- **Requests** (4 endpoints): `POST /`, `GET /user`, `GET /item/:itemId`, `PATCH /:id`
- **Admin** (3 endpoints): `GET /users`, `DELETE /users/:id`, `GET /stats` — all require admin role
- **Legacy routes** (8 endpoints) mounted at root for backward compatibility with `js/app.js`

**Visual:**
- Table with columns: Method, Path, Auth Required, Description
- Highlight protected routes with a lock icon 🔒
- Group rows by colour: blue for Auth, green for Items, orange for Requests, red for Admin
- Reference the table from `API_DOCUMENTATION.md`

---

## Slide 9 — Technology Stack

**Title:** Technology Stack

**Content:**

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Runtime | Node.js 18+ | Server-side JavaScript |
| Web Framework | Express 4.19 | REST API & static file serving |
| ORM | Sequelize 6.37 | Database modelling & queries |
| Database | MariaDB / MySQL 8 | Relational data storage |
| Auth | JWT + bcryptjs | Token auth & password hashing |
| File Uploads | Multer | Image handling (items + avatars) |
| Frontend CSS | Tailwind CSS 3 (CDN) | Responsive utility-first styling |
| Frontend JS | Vanilla JavaScript | Dynamic client behaviour |
| Icons | Material Symbols (CDN) | UI iconography |
| Typography | Manrope (Google Fonts) | Design system font |

**Visual:**
- Clean two-column table or icon grid with logos
- Logos can be sourced from each technology's brand assets
- Highlight "open source" nature if applicable

---

## Slide 10 — Testing Summary

**Title:** Testing — 70 Test Cases Across 6 Feature Groups

**Content:**
- **Authentication (18 tests):** Registration validation, login, profile update, token expiry, role-based guards
- **Items (18 tests):** CRUD operations, search/filter, ownership checks, admin override, 404 handling
- **Requests (11 tests):** Create, list, approve/reject, duplicate prevention, self-request blocking
- **Admin (9 tests):** User listing, user deletion, stats aggregation, non-admin rejection (403)
- **Messages (6 tests):** Send, receive, inbox, thread, empty validation
- **Edge Cases (8 tests):** Invalid file types, missing fields, duplicate emails, database errors

**Visual:**
- Bar chart or pie chart: "Test Cases by Feature Group"
  - Auth: 18 (dark blue)
  - Items: 18 (green)
  - Requests: 11 (orange)
  - Admin: 9 (red)
  - Messages: 6 (purple)
  - Edge Cases: 8 (grey)
- Or a simple summary table with count per group and pass/fail status columns

---

## Slide 11 — Challenges Faced

**Title:** Challenges Faced During Development

**Content:**
- **Dual-backend architecture:** Express and PHP APIs run in parallel with separate authentication — tokens do not cross-authenticate; PHP uses insecure "dummy tokens"
- **Database synchronisation:** Express uses `sequelize.sync({ alter: true })`, while PHP uses raw schema.sql — the two can drift apart without careful configuration
- **Frontend state management:** No framework (React/Vue) — all DOM updates, routing, and state managed via vanilla JavaScript with `localStorage` for tokens
- **File upload coordination:** Multer middleware in routes vs manual multipart handling — ensuring consistent error responses across image upload paths
- **Ownership validation logic:** Request controller must verify item ownership across multiple levels (request owner, item owner, admin override)

**Visual:**
- Three or four "obstacle" icons with arrows pointing to "solutions"
- Or a timeline showing development phases and where challenges arose
- Could use a simple "Challenge → Mitigation" two-column list

---

## Slide 12 — Future Work & Conclusion

**Title:** Future Work & Conclusion

**Content:**
- **Future Enhancements:**
  - Migrate all PHP endpoints to Express for a unified API
  - Replace message polling with WebSockets (Socket.IO) for real-time chat
  - Add email/SMS notification delivery
  - Implement social login (OAuth), password reset, image compression, mobile app
- **Conclusion:**
  - ReValue Hub delivers a functional reuse marketplace with secure auth, item/request management, and admin oversight
  - Validated through 70 test cases across all feature groups
  - Successfully demonstrates REST API design, JWT authentication, MVC architecture, and responsive frontend development

**Visual:**
- Left half: "Future Roadmap" with arrow pointing right to show progression
- Right half: "What We Built" with checkmark icons next to each completed objective
- Final slide could include a screenshot of the landing page with a "Thank You" overlay

---

## Slide 13 (Bonus) — Q&A

**Title:** Questions & Discussion

**Content:**
- Thank the audience and supervisor
- Open floor for questions
- Contact information / demo availability

**Visual:**
- Clean slide with "Thank You" and project logo
- Optional: QR code linking to running demo or GitHub repository
- Supervisor name and institution displayed

---

## Appendix: Suggested Speaking Notes

| Slide | Key Points to Emphasise (30–60 seconds each) |
|-------|----------------------------------------------|
| 1 | Introduce yourself, project name, brief one-liner |
| 2 | Frame the waste problem — connect to personal relevance (everyone has unused items) |
| 3 | Walk through objectives, note they map to completed features |
| 4 | Highlight the three-tier separation and why Express was chosen over PHP |
| 5 | Point out the clean 3-entity core; mention auxiliary tables exist but are handled by PHP |
| 6 | Emphasise bcrypt (12 rounds) and JWT expiry as security strengths; note admin role guard |
| 7 | Quick walk — don't linger; aim for 5-7 seconds per screenshot |
| 8 | Explain "most routes need auth" pattern; note `/api/admin` double-guard (protect + restrictTo) |
| 9 | Keep brief — let the visual do the work |
| 10 | "70 tests across 6 groups" — highlight specific edge-case tests (own-item request block) |
| 11 | Be honest about the dual-backend complexity; frame it as a migration opportunity |
| 12 | Emphasise the testing validation and conclude confidently |
| 13 | Smile, invite questions, point to demo if running |

---

*End of Presentation Outline*

