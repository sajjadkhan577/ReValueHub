# Software Requirements Specification (SRS)
## ReValue Hub — Community Reuse Marketplace

**Version:** 1.0  
**Date:** 2024  
**Prepared by:** Code Analysis from server.js, routes/\*.js, controllers/\*.js, models/\*.js, schema.sql, and HTML pages

---

## 1. Introduction

### 1.1 Purpose
This document specifies the software requirements for **ReValue Hub**, a community-driven reuse marketplace that enables users to donate, browse, and request reusable items within their local community. The platform facilitates sustainable sharing by connecting donors with people who need items, with an administrative moderation layer for quality control.

### 1.2 Scope
ReValue Hub is a full-stack web application consisting of:
- A Node.js/Express REST API (primary backend, JWT-authenticated, Sequelize ORM to MariaDB/MySQL)
- A PHP REST API (legacy secondary backend, token-based auth, raw MySQLi)
- Static HTML/CSS/Tailwind frontend pages served via Express
- An interactive JavaScript frontend layer (`js/app.js`, `js/admin-dashboard.js`)

The product covers user registration/authentication, item listing/donation, item browsing with search/filter, item requesting, peer-to-peer messaging, user profile management, admin dashboard for moderation, volunteer applications, and notification generation.

### 1.3 Intended Audience
- Development team maintaining the platform
- System administrators deploying and operating the platform
- Quality assurance testers validating functionality
- Project stakeholders evaluating feature scope

### 1.4 Definitions and Acronyms
| Term | Definition |
|------|------------|
| JWT | JSON Web Token — used for Express API authentication |
| Donor | A registered user who lists/donates an item |
| Requester | A registered user who requests an item |
| Admin | A user with elevated privileges for moderation |
| Guest | An unauthenticated visitor |
| Sequelize | Node.js ORM used for MySQL/MariaDB interaction |
| Multer | Express middleware for file upload handling |
| REST | Representational State Transfer API style |

---

## 2. Overall Description

### 2.1 Product Perspective
ReValue Hub is a standalone web application. It uses a **dual-backend architecture**:
1. **Express/Node.js API** — serves routes under `/api/auth`, `/api/items`, `/api/requests`, `/api/admin`. Uses JWT tokens stored in `Authorization: Bearer <token>` header. Manages models (User, Item, Request) via Sequelize ORM against a MySQL/MariaDB database named `revaluehub`.
2. **PHP API** — serves routes under `/api/*.php`. Uses a simpler `dummy-token-{userId}` authentication pattern. Provides additional endpoints for messages, notifications, volunteer applications, stats, and admin operations that are not covered by the Express API.

The frontend consists of static HTML files styled with Tailwind CSS (CDN-loaded) that are served directly by Express's static file middleware. Dynamic behavior is implemented via vanilla JavaScript that makes `fetch()`/`apiRequest()` calls to the appropriate backend API.

### 2.2 User Roles and Characteristics

#### 2.2.1 Guest (Unregistered Visitor)
- Can view landing page, browse items, view item details, and view informational pages
- Cannot list items, request items, send messages, or access dashboard
- Source: Browse pages load items via `itemController.getAllItems` (Express) and `api/items.php` (PHP) — no auth required

#### 2.2.2 Registered User
- Has completed registration via `/api/auth/register` (Express) or `api/auth/register.php` (PHP)
- Can create, view, and delete their own item listings
- Can request items (except their own)
- Can manage requests for their own items (approve/reject)
- Can send and receive messages via the inbox (`messages.html`)
- Can update their profile (name, email, avatar)
- Can view their dashboard with stats, donations, and requests
- Source: All protected routes require `protect` middleware (Express) or valid token (PHP)

#### 2.2.3 Administrator
- Has `role: 'admin'` in the users table
- Can access admin dashboard (`admin-dashboard.html`)
- Can view all users, items, requests
- Can delete any user or item
- Can view system statistics (total users, items, requests)
- Can approve/reject pending items
- Can manage volunteer applications (approve/reject)
- Can invite new admins (requires "admin" in email address)
- Source: `restrictTo('admin')` middleware on admin routes; PHP admin endpoints

### 2.3 Constraints
- **Database**: Requires MySQL/MariaDB 8+ with InnoDB engine support
- **Node.js**: Version 18+ required (per `package.json` engines field)
- **PHP**: Version 8+ recommended for PHP API endpoints
- **File uploads**: Image uploads limited to 10MB (items) and 5MB (avatar); only JPEG, PNG, WebP, GIF allowed
- **Authentication**: Two parallel auth systems (JWT for Express, dummy-token for PHP) — tokens do not cross-authenticate
- **Social login**: Google and Facebook login buttons exist in UI but endpoint returns 501 Not Implemented (`api/auth/social-login.php`)

---

## 3. Functional Requirements

### FR-01: User Registration
| Field | Value |
|-------|-------|
| **ID** | FR-01 |
| **Description** | A guest can create a new user account by providing name, email, and password. |
| **Actors** | Guest |
| **Preconditions** | Guest is on the registration page (`register.html`); email not already in use. |
| **Main Flow** | 1. User navigates to `register.html` and fills in name, email, and password fields. 2. User submits the form. 3. Frontend sends POST request to `/api/auth/register` (Express) or `api/auth/register.php` (PHP). 4. Backend validates: name, email, password required; email format must match `^\S+@\S+\.\S+$`; password minimum 6 characters. 5. Backend creates user record with bcrypt-hashed password. 6. Backend returns JWT token (Express) or dummy-token (PHP) with user data. 7. Frontend stores token in `localStorage` and redirects to dashboard. |
| **Exceptions** | • Missing fields → 400 "Name, email and password are required" • Invalid email format → 400 "Please provide a valid email address" • Password < 6 chars → 400 "Password must be at least 6 characters" • Duplicate email → 400 "Email already exists" • Backend validation errors → 400 with error message |
| **Source** | `controllers/authController.js` (register), `routes/auth.js` (POST /register), `api/auth/register.php` |

### FR-02: User Login
| Field | Value |
|-------|-------|
| **ID** | FR-02 |
| **Description** | A registered user can authenticate with email and password to receive an access token. |
| **Actors** | Registered User |
| **Preconditions** | User has a registered account. |
| **Main Flow** | 1. User navigates to `login.html` and enters email and password. 2. User submits the form. 3. Frontend sends POST request to `/api/auth/login` (Express) or `api/auth/login.php` (PHP). 4. Backend looks up user by email. 5. Backend verifies password using `bcrypt.compare`. 6. Backend returns token (JWT with 30-day expiry) and user data. 7. Frontend stores token and redirects to dashboard. |
| **Exceptions** | • Missing email or password → 400 "Please provide email and password" • Invalid credentials → 401 "Incorrect email or password" • User not found → 401 |
| **Source** | `controllers/authController.js` (login), `routes/auth.js` (POST /login), `api/auth/login.php` |

### FR-03: Browse Items (with Search and Filter)
| Field | Value |
|-------|-------|
| **ID** | FR-03 |
| **Description** | Any visitor can browse a paginated list of available items with search, category, and location filtering. |
| **Actors** | Guest, Registered User, Admin |
| **Preconditions** | Items exist in the database. |
| **Main Flow** | 1. User navigates to `browse.html` or `discovery.html`. 2. Frontend fetches items via GET `/api/items` (Express) or `GET /api/items.php` (PHP). 3. Backend queries items with optional filters: `search` (title LIKE), `category` (exact match), `location` (LIKE), `limit`. 4. Results are ordered by `created_at DESC`. 5. Frontend renders items in a CSS grid with cards showing title, category, location, donor info. |
| **Exceptions** | • Database error → 400 with error message • Empty results → empty array returned (gracefully handled by frontend) |
| **Source** | `controllers/itemController.js` (getAllItems), `routes/items.js` (GET /), `api/items.php` |

### FR-04: View Item Details
| Field | Value |
|-------|-------|
| **ID** | FR-04 |
| **Description** | Any visitor can view detailed information about a specific item including images, description, donor info, condition, and location. |
| **Actors** | Guest, Registered User, Admin |
| **Preconditions** | Item exists with given ID. |
| **Main Flow** | 1. User clicks on an item card, navigating to `item-detail.html?id=X`. 2. Frontend fetches item via GET `/api/items/:id` (Express) or `GET /api/items.php?id=X` (PHP). 3. Backend returns item with donor information (name, email). 4. Frontend displays: breadcrumb navigation, main image + thumbnails, category/status chips, title, description, donor profile card (avatar, name, rating badge), item details (condition, posted date), location with map placeholder, "Request Item" and "Message Donor" buttons, related items section. |
| **Exceptions** | • Item not found → 404 "Item not found" • Missing ID parameter → error handled by frontend |
| **Source** | `controllers/itemController.js` (getItemById), `routes/items.js` (GET /:id), `api/items.php` |

### FR-05: Request an Item
| Field | Value |
|-------|-------|
| **ID** | FR-05 |
| **Description** | A registered user can request an item listed by another user. |
| **Actors** | Registered User |
| **Preconditions** | User is logged in (valid token). Item exists. User is not the donor of the item. User has not already requested this item. |
| **Main Flow** | 1. Authenticated user clicks "Request Item" on item detail page. 2. Frontend sends POST `/api/requests` with `item_id` (Express) or `api/requests.php` (PHP). 3. Backend validates: item exists, user is not the owner, no duplicate request. 4. Backend creates request with status `pending` (Express) or `open` (PHP). 5. PHP backend additionally creates a notification for the donor: "{RequesterName} has requested your item: {ItemTitle}". 6. Frontend shows success message. |
| **Exceptions** | • Not authenticated → 401 • Item not found → 404 • Requesting own item → 400 "You cannot request your own item" • Duplicate request → 400 "You have already requested this item" |
| **Source** | `controllers/requestController.js` (createRequest), `routes/requests.js` (POST /), `api/requests.php` |

### FR-06: Send Message to Donor
| Field | Value |
|-------|-------|
| **ID** | FR-06 |
| **Description** | A registered user can send a direct message to another user (e.g., a donor of an item they're interested in). |
| **Actors** | Registered User |
| **Preconditions** | Both sender and receiver are registered users. Sender is authenticated. |
| **Main Flow** | 1. Authenticated user navigates to `messages.html?userId=X` or opens chat from item detail. 2. User types a message and submits. 3. Frontend sends POST to `api/messages.php` with `receiver_id`, `message`, and optional `item_id`. 4. Backend validates: receiver exists, non-empty message, sender ≠ receiver. 5. Backend inserts message record into `messages` table. 6. Backend creates a notification for the receiver: "New message from {SenderName}: {preview}". 7. Frontend appends message to chat thread. 8. Frontend polls every 3 seconds via `setInterval` for new messages (`pollInboxAndActiveThread`). |
| **Exceptions** | • Not authenticated → 401 • Missing receiver or empty message → 400 • Sending to self → 400 "You cannot send a message to yourself" • Database error → 500 |
| **Source** | `api/messages.php` (GET inbox, GET thread, POST send), `messages.html` (frontend) |

### FR-07: Donate / List a New Item
| Field | Value |
|-------|-------|
| **ID** | FR-07 |
| **Description** | A registered user can create a new item listing (donation) with title, description, category, photos, and pickup location. |
| **Actors** | Registered User |
| **Preconditions** | User is authenticated. |
| **Main Flow** | 1. User navigates to `list-item.html` and fills the multi-step form. 2. Section 1: Item title, category (dropdown with "medicine" option), description. If "Medicine" selected, extra fields for manufacturing and expiry dates appear. 3. Section 2: Photo upload (drag-and-drop zone, up to 10 images, max 10MB each, JPEG/PNG/WebP/GIF). 4. Section 3: Pickup location (text input), availability checkboxes. 5. User submits form. 6. Frontend sends POST to `/api/items` with multipart form data (Express) or `api/items.php` (PHP). 7. Backend creates item with status `pending` (requires admin approval). 8. Backend stores uploaded images to `uploads/` directory. 9. Frontend redirects to item detail or dashboard. |
| **Exceptions** | • Not authenticated → 401 • Missing title, description, category, or location → 400 • Invalid file type → multer error "Only image files are allowed" • File too large (>10MB) → multer error • Database error → 400/500 |
| **Source** | `controllers/itemController.js` (createItem), `routes/items.js` (POST /), `api/items.php`, `list-item.html` |

### FR-08: View and Edit Own Items
| Field | Value |
|-------|-------|
| **ID** | FR-08 |
| **Description** | A registered user can view a list of their own donated items and delete them. |
| **Actors** | Registered User |
| **Preconditions** | User is authenticated. |
| **Main Flow** | 1. User navigates to dashboard and clicks "My Donations" section. 2. Frontend sends GET to `/api/items/user` (Express) or `api/items.php?user=me`. 3. Backend returns all items owned by the authenticated user, ordered by `created_at DESC`. 4. Items are displayed in a grid with title, category, image, and status. 5. User can delete an item — sends DELETE to `/api/items/:id` (Express) or POST to `api/update_item_status.php` with `action=delete` (PHP). 6. Backend validates ownership (non-admin users can only delete their own items). |
| **Exceptions** | • Not authenticated → 401 • Item not found → 404 • User tries to delete another user's item → 403 "Unauthorized" |
| **Source** | `controllers/itemController.js` (getUserItems, deleteItem), `routes/items.js` (GET /user, DELETE /:id), `api/update_item_status.php` |

### FR-09: User Profile Management
| Field | Value |
|-------|-------|
| **ID** | FR-09 |
| **Description** | A registered user can view and update their profile including name, email, and avatar photo. |
| **Actors** | Registered User |
| **Preconditions** | User is authenticated. |
| **Main Flow** | 1. User navigates to dashboard "Settings" section. 2. Profile form is pre-populated with current user data via GET `/api/auth/me` (Express) or `api/auth/me.php` (PHP). 3. User can update name, email, and upload a new avatar image. 4. User submits — frontend sends PUT to `/api/auth/profile` (Express, multipart with avatar) or POST to `api/auth/profile.php` (PHP). 5. Backend validates name and email (required, valid format), updates user record, stores avatar to `uploads/` directory. 6. Updated user data is returned and displayed. |
| **Exceptions** | • Not authenticated → 401 • Missing name or email → 400 • Invalid email format → 400 • Avatar file error → handled by multer/file upload |
| **Source** | `controllers/authController.js` (getMe, updateProfile), `routes/auth.js` (GET /me, PUT /profile), `api/auth/me.php`, `api/auth/profile.php` |

### FR-10: Notifications
| Field | Value |
|-------|-------|
| **ID** | FR-10 |
| **Description** | Users receive notifications for key events: new messages, item requests, and volunteer application status changes. |
| **Actors** | Registered User, Admin |
| **Preconditions** | User is authenticated. Relevant events have occurred. |
| **Main Flow** | 1. Notifications are automatically created by backend PHP endpoints when: a. A message is sent (receiver gets notified) — `api/messages.php` b. An item is requested (donor gets notified) — `api/requests.php` c. A volunteer application status changes (applicant gets notified) — `api/admin/volunteer-applications.php` d. A new volunteer application is submitted (all admins get notified) — `api/volunteer/apply.php` 2. Frontend fetches notifications via GET `api/notifications.php`. 3. Notifications are stored in the `notifications` table with `user_id`, `message`, `is_read`, `created_at`. 4. Users can mark a notification as read via POST `api/notifications.php` with the notification ID. |
| **Exceptions** | • Not authenticated → 401 • Database error → 500 |
| **Source** | `api/notifications.php`, `api/messages.php`, `api/requests.php`, `api/admin/volunteer-applications.php`, `api/volunteer/apply.php` |

### FR-11: Admin Moderation — Item Approval
| Field | Value |
|-------|-------|
| **ID** | FR-11 |
| **Description** | Admin users can view pending items and approve or reject them, or delete/reject items from the inventory. |
| **Actors** | Admin |
| **Preconditions** | Admin is authenticated. Items with `status = 'pending'` exist. |
| **Main Flow** | 1. Admin navigates to `admin-dashboard.html` and sees the "Pending Items Queue" on the dashboard view. 2. Admin dashboard fetches pending items via `api/pending_items.php`. 3. Each pending item row shows item name, donor, category, and action buttons. 4. Admin can open item detail modal (approve/reject/delete). 5. Approve/Reject: frontend sends POST to `api/update_item_status.php` with `id`, `status` ('approved'/'rejected'). 6. Delete: frontend sends POST to `api/update_item_status.php` with `id`, `action='delete'`. 7. Backend updates `items.status` or deletes the row. |
| **Exceptions** | • Missing ID → 400 • Item not found → 404 • Invalid action → handled by backend |
| **Source** | `api/pending_items.php`, `api/update_item_status.php`, `admin-dashboard.html`, `js/admin-dashboard.js` |

### FR-12: Admin Moderation — User Management
| Field | Value |
|-------|-------|
| **ID** | FR-12 |
| **Description** | Admin users can view all registered users and delete users from the system. |
| **Actors** | Admin |
| **Preconditions** | Admin is authenticated. |
| **Main Flow** | 1. Admin clicks "Users" in admin sidebar. 2. Frontend fetches users via GET `/api/admin/users` (Express) or `api/admin/users.php` (PHP). 3. Table displays name, email, status (Active/Flagged/Pending), and join date. 4. Admin can delete a user — sends DELETE `/api/admin/users/:id` (Express). 5. Backend deletes user (cascading to their items and requests). |
| **Exceptions** | • Not authorized (non-admin) → 403 "You do not have permission to perform this action" • User not found → 404 |
| **Source** | `controllers/adminController.js` (getAllUsers, deleteUser), `routes/admin.js` (GET /users, DELETE /users/:id), `api/admin/users.php` |

### FR-13: Admin Dashboard — Statistics
| Field | Value |
|-------|-------|
| **ID** | FR-13 |
| **Description** | Admin users can view system-wide statistics showing total users, items, requests, and estimated environmental impact. |
| **Actors** | Admin |
| **Preconditions** | Admin is authenticated. |
| **Main Flow** | 1. Admin views dashboard — four stat cards display: Total Users, Pending Items, Total Requests, Impact (kg). 2. Frontend fetches stats via GET `/api/admin/stats` (Express) or `api/stats.php` (PHP). 3. Express backend returns: `{ users, items, requests }`. 4. PHP backend returns: `{ users, items, items_pending, requests, impact }`. |
| **Exceptions** | • Database error → 500 |
| **Source** | `controllers/adminController.js` (getStats), `routes/admin.js` (GET /stats), `api/stats.php`, `api/admin/stats.php` |

### FR-14: Volunteer Applications
| Field | Value |
|-------|-------|
| **ID** | FR-14 |
| **Description** | Registered users can submit volunteer applications; admins can review and approve/reject them. |
| **Actors** | Registered User, Admin |
| **Preconditions** | User is authenticated (to apply). Admin is authenticated (to review). |
| **Main Flow** | **User Flow:** 1. User navigates to `become_a_volunteer.html` and fills in full name, email, skills, availability, motivation. 2. Frontend sends POST to `api/volunteer/apply.php` with JSON body. 3. Backend validates: name and email required, valid email format, no duplicate pending application. 4. Backend inserts into `volunteer_applications` table with status `pending`. 5. Backend creates notifications for all admin users: "New volunteer application received from {name}". **Admin Flow:** 1. Admin views "Volunteers" section in admin dashboard. 2. Frontend fetches applications via GET `api/admin/volunteer-applications.php`. 3. Table shows applicant, skills, availability, status, application date. 4. Admin approves/rejects — frontend sends POST to `api/admin/volunteer-applications.php` with `id` and `status`. 5. Backend updates application status, sets `reviewed_by` and `reviewed_at`. 6. Backend creates notification for the applicant with result message. |
| **Exceptions** | • Not authenticated → 401 • Missing name or email → 400 • Invalid email → 400 • Duplicate pending application → 409 "You already have a pending application" • Invalid status update → 400 |
| **Source** | `api/volunteer/apply.php`, `api/admin/volunteer-applications.php`, `js/admin-dashboard.js` |

### FR-15: Invite Administrator
| Field | Value |
|-------|-------|
| **ID** | FR-15 |
| **Description** | An existing admin can invite a new administrator by providing an email address containing "admin". |
| **Actors** | Admin |
| **Preconditions** | Inviting user is an authenticated admin (email contains "admin"). |
| **Main Flow** | 1. Admin clicks "Invite Admin" button in admin dashboard sidebar. 2. Modal opens with email input field. 3. Admin enters email address containing "admin" (e.g., "newadmin@example.com"). 4. Frontend sends POST to `api/auth/invite_admin.php`. 5. Backend verifies: admin token valid, inviting user's email contains "admin". 6. Backend checks if email already exists. 7. Backend creates new user with a randomly generated 8-char temp password, returns it for secure sharing. |
| **Exceptions** | • Not authenticated → 401 • Not an admin → 403 "Forbidden: Only administrators can invite new admins" • Email missing "admin" → 400 "must contain 'admin'" • Email already exists → 409 "already exists" |
| **Source** | `api/auth/invite_admin.php`, `admin-dashboard.html` |

---

## 4. Non-Functional Requirements

### 4.1 Security

| Requirement | Details | Source |
|-------------|---------|--------|
| **NFR-SEC-01: JWT Authentication** | Express API uses JSON Web Tokens with 30-day expiry (`expiresIn: '30d'`). Tokens are verified on all protected routes via `protect` middleware. | `middleware/auth.js`, `controllers/authController.js` (signToken) |
| **NFR-SEC-02: Password Hashing** | User passwords are hashed using bcrypt with 12 salt rounds (`bcrypt.hash(user.password, 12)`) before storage. Password comparison uses `bcrypt.compare`. | `models/User.js` (beforeCreate hook) |
| **NFR-SEC-03: Role-Based Access Control** | Admin-only routes are protected by `restrictTo('admin')` middleware that checks `req.user.role`. Non-admin users receive 403 on admin endpoints. Non-admin users cannot delete other users' items. | `middleware/auth.js` (restrictTo), `routes/admin.js`, `controllers/itemController.js` (deleteItem) |
| **NFR-SEC-04: Input Validation** | Backend validates: required fields, email format (`^\S+@\S+\.\S+$`), password minimum length (6 chars), file types (image only), file sizes (10MB items, 5MB avatars). | `controllers/authController.js`, `controllers/itemController.js`, multer configs |
| **NFR-SEC-05: File Upload Restrictions** | Only JPEG, PNG, WebP, GIF accepted. Max 10 images per item listing. Max 10MB per file (items), 5MB (avatar). | `routes/items.js` (multer), `routes/auth.js` (multer) |
| **NFR-SEC-06: CORS Enabled** | Cross-Origin Resource Sharing is enabled via `cors()` middleware, allowing API access from different origins. | `server.js` (app.use(cors())) |
| **NFR-SEC-07: Legacy Token Pattern** | PHP API uses a simpler `dummy-token-{userId}` pattern extracted from Authorization header via regex. Not cryptographically secure; relies on obscurity. | `api/db.php` (auth header parsing across multiple PHP endpoints) |

### 4.2 Performance

| Requirement | Details | Source |
|-------------|---------|--------|
| **NFR-PERF-01: Polling for Messages** | Messages page polls inbox and active thread every 3 seconds (`setInterval(pollInboxAndActiveThread, 3000)`) for near-real-time updates. | `messages.html` |
| **NFR-PERF-02: Image Optimization** | No server-side image resizing or optimization detected. Images are served as uploaded. File size limits mitigate large uploads. | Upload logic in multer configs and PHP file handlers |
| **NFR-PERF-03: Pagination/Record Limits** | Item listings default to 50 records (PHP) and support an optional `limit` query parameter (Express). Pending items limited to 10. | `api/items.php`, `controllers/itemController.js`, `api/pending_items.php` |

### 4.3 Usability

| Requirement | Details | Source |
|-------------|---------|--------|
| **NFR-UI-01: Responsive Design** | All HTML pages use Tailwind CSS responsive utilities (grid-cols-1 md:grid-cols-2 xl:grid-cols-3, etc.) for mobile/tablet/desktop layouts. | All HTML files |
| **NFR-UI-02: Dark Mode Toggle** | Admin dashboard includes dark mode toggle via `toggleDarkMode()` function, switching `class="dark"` on HTML element. Tailwind config has `darkMode: "class"`. | `admin-dashboard.html` |
| **NFR-UI-03: Form Validation (Client-Side)** | Registration form includes terms checkbox. Login form has "Remember Me" checkbox. Item form has condition checkboxes. Medicine category shows/hides date fields dynamically. | `register.html`, `login.html`, `list-item.html` |
| **NFR-UI-04: Drag-and-Drop Uploads** | Item listing form supports drag-and-drop photo uploads with preview grid. | `list-item.html` |

### 4.4 Availability

| Requirement | Details | Source |
|-------------|---------|--------|
| **NFR-AVAIL-01: Graceful Degradation** | Server continues running even if database connection fails (`console.error` but does not crash). Static HTML pages remain accessible. | `server.js` (async IIFE with catch) |
| **NFR-AVAIL-02: Database Auto-Creation** | On startup, Express API attempts to create the database automatically if it doesn't exist (`CREATE DATABASE IF NOT EXISTS`). Tables are synced with `{ alter: true }`. | `server.js` (ensureDatabase, sequelize.sync) |
| **NFR-AVAIL-03: Multiple DB Configs** | PHP API attempts multiple database configurations (revalue_user then root) for fallback connectivity. | `db.php` |

---

## 5. External Interface Requirements

### 5.1 REST API Summary

#### 5.1.1 Express/Node.js API (Primary, Port 3000)

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/auth/register` | POST | No | Create new user account |
| `/api/auth/login` | POST | No | Authenticate and receive JWT |
| `/api/auth/logout` | POST | No | Logout (stateless, returns success) |
| `/api/auth/me` | GET | JWT | Get current authenticated user |
| `/api/auth/profile` | PUT | JWT | Update profile (name, email, avatar) |
| `/api/items` | GET | No | List all items (search/category/location filters) |
| `/api/items/:id` | GET | No | Get single item by ID with donor info |
| `/api/items` | POST | JWT | Create new item listing (multipart, up to 10 images) |
| `/api/items/user` | GET | JWT | Get current user's items |
| `/api/items/:id` | DELETE | JWT | Delete item (owner or admin only) |
| `/api/requests` | POST | JWT | Create item request |
| `/api/requests/user` | GET | JWT | Get current user's requests |
| `/api/requests/item/:itemId` | GET | JWT | Get requests for a specific item |
| `/api/requests/:id` | PATCH | JWT | Update request status (item owner only) |
| `/api/admin/users` | GET | JWT+Admin | List all users |
| `/api/admin/users/:id` | DELETE | JWT+Admin | Delete a user |
| `/api/admin/stats` | GET | JWT+Admin | Get system statistics |
| `/api/ping` | GET | No | Health check endpoint |

#### 5.1.2 PHP API (Legacy, via Apache)

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `api/auth/register.php` | POST | No | Create new user (returns dummy-token) |
| `api/auth/login.php` | POST | No | Login (returns dummy-token) |
| `api/auth/me.php` | GET | Token | Get current user |
| `api/auth/profile.php` | POST | Token | Update profile with avatar upload |
| `api/auth/social-login.php` | POST | No | Social login stub (returns 501) |
| `api/auth/invite_admin.php` | POST | Token+Admin | Invite new admin by email |
| `api/items.php` | GET | No | List items with search/filter; single item by ID |
| `api/items.php` | POST | Token | Create item (multipart) |
| `api/items.php` | PUT | Token | Update own item |
| `api/pending_items.php` | GET | No | List pending items (for admin) |
| `api/update_item_status.php` | POST | No | Approve/reject/edit/delete item |
| `api/requests.php` | GET | Token | Get requests (own or all for admin) |
| `api/requests.php` | POST | Token | Create request (auto-notifies donor) |
| `api/messages.php` | GET | Token | Get inbox conversations or thread with specific user |
| `api/messages.php` | POST | Token | Send message (auto-notifies receiver) |
| `api/notifications.php` | GET | Token | Get notifications for current user |
| `api/notifications.php` | POST | Token | Mark notification as read |
| `api/stats.php` | GET | No | Get platform statistics |
| `api/recent_users.php` | GET | No | Get 5 most recent users |
| `api/profile.php` | GET | No | Get public user profile + items by user ID |
| `api/volunteer/apply.php` | POST | Token | Submit volunteer application |
| `api/admin/stats.php` | GET | No | Get admin stats (no auth enforced) |
| `api/admin/users.php` | GET | No | List all users with inferred roles |
| `api/admin/volunteer-applications.php` | GET | Token | List volunteer applications |
| `api/admin/volunteer-applications.php` | POST | Token | Approve/reject application |

### 5.2 Database

The system uses MySQL/MariaDB with the following schema:

**Table: `users`**
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| name | VARCHAR(100) | NOT NULL |
| email | VARCHAR(150) | NOT NULL, UNIQUE |
| password | VARCHAR(255) | NOT NULL |
| role | ENUM('user','admin') | DEFAULT 'user' |
| status | ENUM('Active','Flagged','Pending') | DEFAULT 'Active' |
| avatar | VARCHAR(255) | NULLABLE |
| bio | TEXT | NULLABLE |
| phone | VARCHAR(255) | NULLABLE |
| joined_at / created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |

**Table: `items` / `posts`**
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| title | VARCHAR(200) | NOT NULL |
| category | VARCHAR(50) | NOT NULL |
| description | TEXT | NULLABLE |
| location | VARCHAR(200) | NULLABLE |
| condition | VARCHAR(50) | NULLABLE |
| image_url / image | VARCHAR(255) / TEXT | NULLABLE (JSON array in Express) |
| donor_id / user_id | INT | FK → users(id) ON DELETE CASCADE |
| status | ENUM('pending','approved','rejected') | DEFAULT 'pending' |
| mfgDate | DATE | NULLABLE |
| expDate | DATE | NULLABLE |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |

**Table: `requests`**
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| item_id | INT | FK → items(id) ON DELETE CASCADE |
| requester_id | INT | FK → users(id) ON DELETE CASCADE |
| status | ENUM('open','closed','cancelled') / ENUM('pending','approved','rejected') | DEFAULT 'open' or 'pending' |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |

**Table: `messages`**
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| sender_id | INT | FK → users(id) ON DELETE CASCADE |
| receiver_id | INT | FK → users(id) ON DELETE CASCADE |
| item_id | INT | FK → items(id) ON DELETE SET NULL, NULLABLE |
| message | TEXT | NOT NULL |
| is_read | BOOLEAN | DEFAULT FALSE |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |

**Table: `notifications`**
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| user_id | INT | FK → users(id) ON DELETE CASCADE |
| message | TEXT | NOT NULL |
| is_read | BOOLEAN | DEFAULT FALSE |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |

**Table: `volunteer_applications`** (created via `db_migrate_volunteers.sql`)
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| user_id | INT | FK → users(id) |
| full_name | VARCHAR(255) | |
| email | VARCHAR(255) | |
| skills | TEXT | |
| availability | VARCHAR(255) | |
| motivation | TEXT | |
| status | ENUM('pending','approved','rejected') | DEFAULT 'pending' |
| reviewed_by | INT | FK → users(id), NULLABLE |
| reviewed_at | DATETIME | NULLABLE |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |

---

## 6. Assumptions and Dependencies

### 6.1 Assumptions
1. **Dual Backend Coexistence**: The Express and PHP APIs operate independently and may have overlapping but not synchronized data. The JWT tokens from Express do not authenticate PHP endpoints, and dummy-tokens from PHP do not authenticate Express endpoints. Frontend code (`js/app.js`) likely handles both.
2. **Database Synchronization**: Express uses `sequelize.sync({ alter: true })` which automatically creates/alters tables on startup. The PHP schema.sql file defines a separate `revalue_hub` database name (vs Express using `revaluehub`). This may lead to two separate databases unless configured identically.
3. **Item Status Moderation**: Items created via Express API route (`POST /api/items`) do not explicitly set `status` — the Sequelize model defaults may differ from PHP where status is explicitly set to `'pending'`.
4. **Static File Serving**: All HTML pages are served as static files by Express. There is no server-side rendering or template engine.
5. **Token Security**: Dummy tokens in the PHP API are not cryptographically signed; authentication relies on the token format `dummy-token-{userId}` which is easily guessable.
6. **Email/Notification Delivery**: Notifications are stored in-database only. There is no email/SMS sending infrastructure implemented.

### 6.2 Dependencies

| Dependency | Version/Scope | Purpose |
|------------|--------------|---------|
| Node.js | >=18.0.0 | JavaScript runtime for Express server |
| Express | ^4.19.2 | Web framework for REST API |
| Sequelize | ^6.37.8 | ORM for MySQL/MariaDB |
| MySQL2 | ^3.22.3 | Database driver |
| bcryptjs | ^2.4.3 | Password hashing |
| jsonwebtoken | ^9.0.2 | JWT generation and verification |
| multer | ^1.4.5-lts.1 | File upload handling |
| cors | ^2.8.5 | Cross-Origin Resource Sharing |
| dotenv | ^16.4.5 | Environment variable management |
| PHP | 8+ | Legacy backend API |
| MariaDB/MySQL | 8+ with InnoDB | Relational database |
| Tailwind CSS | CDN (3.x) | Utility-first CSS framework |
| Material Symbols | CDN | Icon library |
| Google Fonts (Manrope) | CDN | Typography |

---

*End of Software Requirements Specification*

