# ReValue Hub — Test Plan & Test Cases

## 1. Test Objectives and Scope

### Objectives
- Verify that all REST API endpoints defined in `server.js` (routes under `/api/auth`, `/api/items`, `/api/requests`, `/api/admin`) function correctly according to their controller logic.
- Validate frontend-to-backend integration for the key HTML pages (`register.html`, `login.html`, `browse.html`, `item-detail.html`, `list-item.html`, `dashboard.html`, `messages.html`, `admin-dashboard.html`).
- Confirm authentication guards (`protect` middleware) block unauthorized access.
- Confirm role-based authorization (`restrictTo('admin')`) restricts admin-only routes.
- Verify validation rules enforced at both API level and frontend level.

### Out of Scope
- Legacy PHP API (`api/*.php`) — not mounted by `server.js`.
- Email/social login providers (Google, Facebook) — backend endpoints exist but are not fully wired.
- Real-time messaging via WebSockets (polling only).
- Volunteer application management (PHP-based, out of scope).

---

## 2. Test Environment

| Component | Requirement |
|-----------|-----------|
| **Node.js** | >= 18.0.0 (as per `package.json` engines) |
| **Database** | MySQL or MariaDB (configured via `.env`) |
| **Server** | Express on `http://localhost:3000` |
| **Browser** | Chrome / Firefox / Edge (latest versions) for HTML page testing |
| **API Client** | `curl`, Postman, or browser DevTools for direct API calls |
| **Authentication** | JWT tokens stored in `localStorage` under key `userToken` (regular users) or `adminToken` (admin) |

### Setup
```bash
# Install dependencies
npm install

# Configure .env (create if not exists)
# DB_HOST=localhost
# DB_PORT=3306
# DB_USER=root
# DB_PASS=
# DB_NAME=revaluehub
# JWT_SECRET=your-secret-key

# Start server
npm start
# or with auto-restart
npm run dev
```

---

## 3. Test Case Tables

### 3.1 Authentication & Authorization

| ID | Feature | Precondition | Test Steps | Expected Result | Actual Result | Status |
|----|---------|-------------|-----------|----------------|---------------|--------|
| TC-01 | Register — valid | No user logged in, server running | 1. Open `register.html`<br>2. Fill: Name="John Test", Email="john@test.com", Password="secret123"<br>3. Check "I agree" terms checkbox<br>4. Click "Create Account" | 201 response with `{ status: "success", token: "...", user: { name: "John Test", email: "john@test.com", role: "user" } }`.<br>Redirected to `dashboard.html`. Token stored in localStorage as `userToken`. | | |
| TC-02 | Register — missing required fields | Not logged in | 1. Open `register.html`<br>2. Leave Name empty, fill valid email/password<br>3. Click submit | Frontend alerts "Please fill in all required fields."<br>No API call sent. | | |
| TC-03 | Register — invalid email format | Not logged in | 1. Send POST to `/api/auth/register` with `{ "name": "Bad", "email": "notanemail", "password": "secret123" }` | 400 response: `{ "status": "fail", "message": "Please provide a valid email address" }` | | |
| TC-04 | Register — short password | Not logged in | 1. Send POST to `/api/auth/register` with `{ "name": "Short", "email": "short@test.com", "password": "12345" }` | 400 response: `{ "status": "fail", "message": "Password must be at least 6 characters" }` | | |
| TC-05 | Register — duplicate email | User already registered with email "john@test.com" | 1. Send POST to `/api/auth/register` with same email | 400 response: `{ "status": "fail", "message": "Validation error" }` (Sequelize unique constraint) | | |
| TC-06 | Login — valid credentials | User "john@test.com" exists | 1. Open `login.html`<br>2. Enter email="john@test.com", password="secret123"<br>3. Click "Sign In" | 200 response with `{ status: "success", token: "...", user: {...} }`.<br>Redirected to `dashboard.html` (or admin dashboard if role=admin). | | |
| TC-07 | Login — wrong password | User exists | 1. Send POST to `/api/auth/login` with `{ email: "john@test.com", password: "wrongpassword" }` | 401 response: `{ "status": "fail", "message": "Incorrect email or password" }` | | |
| TC-08 | Login — missing fields | Not logged in | 1. Open `login.html`<br>2. Leave email or password empty<br>3. Click "Sign In" | Frontend alerts "Please enter both email and password."<br>No API call sent. | | |
| TC-09 | Get current user (me) — authenticated | User logged in, token in localStorage | 1. Call `GET /api/auth/me` with `Authorization: Bearer <token>` | 200 response: `{ status: "success", user: { id, name, email, phone, avatar, role } }` | | |
| TC-10 | Get current user — no token | Not logged in | 1. Call `GET /api/auth/me` without Authorization header | 401 response: `{ "status": "fail", "message": "You are not logged in!" }` | | |
| TC-11 | Get current user — invalid token | Token expired or tampered | 1. Call `GET /api/auth/me` with `Authorization: Bearer invalidtoken123` | 401 response: `{ "status": "fail", "message": "Invalid token." }` | | |
| TC-12 | Update profile — valid | Authenticated user | 1. Call `PUT /api/auth/profile` with form data: name="John Updated", email="john.new@test.com" | 200 response: `{ status: "success", user: { name: "John Updated", email: "john.new@test.com", ... } }` | | |
| TC-13 | Update profile — missing name or email | Authenticated user | 1. Call `PUT /api/auth/profile` with empty name/email fields | 400 response: `{ "status": "fail", "message": "Name and email are required" }` | | |
| TC-14 | Update profile — invalid email | Authenticated user | 1. Call `PUT /api/auth/profile` with email="notvalid" | 400 response: `{ "status": "fail", "message": "Please provide a valid email address" }` | | |
| TC-15 | Update profile — with avatar upload | Authenticated user | 1. Call `PUT /api/auth/profile` with multipart form including avatar file (JPEG, <5MB) | 200 response. `user.avatar` is set to `/uploads/avatar-<timestamp>-<random>.jpg` | | |
| TC-16 | Logout | Authenticated user | 1. Call `POST /api/auth/logout` | 200 response: `{ "status": "success", "message": "Logged out" }` (stateless — no server-side invalidation) | | |
| TC-17 | Admin route — non-admin user blocked | Regular user (role="user") logged in | 1. Call `GET /api/admin/users` with user token | 403 response: `{ "status": "fail", "message": "You do not have permission to perform this action" }` | | |
| TC-18 | Admin route — not logged in | No token | 1. Call `GET /api/admin/users` without Authorization header | 401 response: `{ "status": "fail", "message": "You are not logged in!" }` | | |

---

### 3.2 Items (Donations)

| ID | Feature | Precondition | Test Steps | Expected Result | Actual Result | Status |
|----|---------|-------------|-----------|----------------|---------------|--------|
| TC-19 | Browse items — list all | Items exist in database | 1. Open `browse.html`<br>2. Observe item grid | All items are displayed as cards with title, location, image, and donor info.<br>Cards are clickable, navigate to `item-detail.html?id=N`. | | |
| TC-20 | Browse items — filter by search | Items with varying titles exist | 1. Open `browse.html`<br>2. Type "chair" in search box | Grid filters to show only items whose title contains "chair" (case-insensitive LIKE). | | |
| TC-21 | Browse items — filter by category | Items in "furniture" and "electronics" exist | 1. Open `browse.html`<br>2. Click "Furniture" category in sidebar | Grid shows only items with `category === "furniture"`. Active category is highlighted blue. | | |
| TC-22 | Browse items — filter by category + search | Multiple items | 1. Open `browse.html`<br>2. Select "furniture" category<br>3. Type "desk" in search | Grid shows only items matching both filters. | | |
| TC-23 | Get item by ID — found | Item with ID=1 exists | 1. Call `GET /api/items/1` | 200 response with full item object including `User` (donor) relation. | | |
| TC-24 | Get item by ID — not found | Item with ID=999 does not exist | 1. Call `GET /api/items/999` | 404 response: `{ "status": "fail", "message": "Item not found" }` | | |
| TC-25 | Create item — valid | Authenticated user | 1. Open `list-item.html`<br>2. Fill: title="Office Chair", category="furniture", description="Good condition", location="New York"<br>3. Upload 1 image file<br>4. Click "Publish Listing" | 201 response. Item created with `user_id = current user's ID`.<br>Redirected to `dashboard.html`. Item appears in "My Donations" section. | | |
| TC-26 | Create item — missing required fields | Authenticated user | 1. Call `POST /api/items` with body missing `title` or `description` or `category` or `location` | 400 response: `{ "status": "fail", "message": "Title, description, category and location are required" }` | | |
| TC-27 | Create item — guest (not logged in) | No token | 1. Open `list-item.html` | Frontend redirects to `register.html` with a post-auth redirect to `list-item.html`. | | |
| TC-28 | Create item — medicine category with dates | Authenticated user | 1. Call `POST /api/items` with category="medicine", mfgDate="2024-01-01", expDate="2025-01-01" | Item created. `mfgDate` and `expDate` are stored. `medicine-fields` section shows in `list-item.html` when category=medicine is selected. | | |
| TC-29 | Get user items | Authenticated user with at least 1 item | 1. Call `GET /api/items/user` with token | 200 response with array of items where `user_id` equals current user's ID. | | |
| TC-30 | Delete own item — valid | Authenticated user owns item ID=5 | 1. Call `DELETE /api/items/5` with user's token | 204 No Content. Item removed from database. | | |
| TC-31 | Delete item — not owner | Authenticated user A, item belongs to user B | 1. Call `DELETE /api/items/<item-owned-by-B>` with user A's token | 403 response: `{ "status": "fail", "message": "Unauthorized" }` | | |
| TC-32 | Delete item — admin deleting someone else's item | Admin user logged in, item belongs to user B | 1. Call `DELETE /api/items/<item-owned-by-B>` with admin token | 204 No Content. Admin can delete any item. | | |
| TC-33 | Delete item — not found | Item ID=999 does not exist | 1. Call `DELETE /api/items/999` with user token | 404 response: `{ "status": "fail", "message": "Item not found" }` | | |
| TC-34 | Item detail page — view as owner | Item belongs to current user | 1. Open `item-detail.html?id=<own-item-id>` | "Request Item" and "Message Donor" buttons are hidden.<br>"Edit Item" button is shown. | | |
| TC-35 | Item detail page — view as other user | Item belongs to another user | 1. Open `item-detail.html?id=<other-user-item-id>` | "Request Item" and "Message Donor" buttons are visible.<br>"Edit Item" button is hidden. | | |
| TC-36 | Item detail page — guest (not logged in) | No token | 1. Navigate to `item-detail.html?id=1` | Frontend redirects to `register.html` with a post-auth redirect back to `item-detail.html?id=1`. | | |

---

### 3.3 Requests

| ID | Feature | Precondition | Test Steps | Expected Result | Actual Result | Status |
|----|---------|-------------|-----------|----------------|---------------|--------|
| TC-37 | Request item — valid | Authenticated user A, item belongs to user B | 1. Open `item-detail.html?id=<item-of-B>`<br>2. Click "Request Item" button | 201 response. Request created with `status: "pending"`.<br>Frontend shows alert "Request sent successfully!". | | |
| TC-38 | Request item — request own item | Authenticated user, viewing own item | 1. Call `POST /api/requests` with `{ item_id: <own-item-id> }` | 400 response: `{ "status": "fail", "message": "You cannot request your own item" }` | | |
| TC-39 | Request item — duplicate request | User already requested the same item | 1. Call `POST /api/requests` with same `item_id` again | 400 response: `{ "status": "fail", "message": "You have already requested this item" }` | | |
| TC-40 | Request item — item does not exist | Item ID=999 does not exist | 1. Call `POST /api/requests` with `{ item_id: 999 }` | 404 response: `{ "status": "fail", "message": "Item not found" }` | | |
| TC-41 | Request item — guest (no token) | Not logged in | 1. Click "Request Item" on item-detail page without being logged in | Frontend alerts "Join ReValue Hub to request items! Please create an account to continue."<br>Redirected to `register.html`. | | |
| TC-42 | Get user requests | Authenticated user who has made requests | 1. Call `GET /api/requests/user` with token | 200 response with array of user's requests, including nested `Item` and `User` (donor) data. | | |
| TC-43 | Get requests by item | Item ID=1 exists with requests | 1. Call `GET /api/requests/item/1` with token | 200 response with array of requests for item 1, each including the requester's `User` data. | | |
| TC-44 | Approve request — item owner | Authenticated user owns item, request exists for that item | 1. Call `PATCH /api/requests/5` with `{ status: "approved" }` and item owner's token | 200 response. Request `status` changed to `"approved"`. | | |
| TC-45 | Reject request — item owner | Same as TC-44 | 1. Call `PATCH /api/requests/5` with `{ status: "rejected" }` and item owner's token | 200 response. Request `status` changed to `"rejected"`. | | |
| TC-46 | Update request status — not item owner | User A made the request, user B owns the item | 1. User A calls `PATCH /api/requests/5` with `{ status: "approved" }` | 403 response: `{ "status": "fail", "message": "Unauthorized" }`<br>Only item owner can update status. | | |
| TC-47 | Update request status — request not found | Request ID=999 does not exist | 1. Call `PATCH /api/requests/999` with any valid token | 404 response: `{ "status": "fail", "message": "Request not found" }` | | |

---

### 3.4 Admin

| ID | Feature | Precondition | Test Steps | Expected Result | Actual Result | Status |
|----|---------|-------------|-----------|----------------|---------------|--------|
| TC-48 | Admin — get all users | Admin user logged in | 1. Call `GET /api/admin/users` with admin token | 200 response with array of users (id, name, email, role, created_at).<br>Password field is NOT returned (excluded via `attributes`). | | |
| TC-49 | Admin — get stats | Admin user logged in | 1. Call `GET /api/admin/stats` with admin token | 200 response: `{ users: <count>, items: <count>, requests: <count> }` | | |
| TC-50 | Admin — delete user | Admin user logged in, user ID=5 exists | 1. Call `DELETE /api/admin/users/5` with admin token | 204 No Content. User removed from database. | | |
| TC-51 | Admin — delete user not found | User ID=999 does not exist | 1. Call `DELETE /api/admin/users/999` with admin token | 404 response: `{ "status": "fail", "message": "User not found" }` | | |
| TC-52 | Admin dashboard — stats display | Admin user logged in | 1. Open `admin-dashboard.html`<br>2. Check "Total Users", "Pending Items", "Total Requests", "Impact" cards | Dashboard cards display real-time counts fetched from backend stats endpoint. | | |
| TC-53 | Admin dashboard — pending items queue | Admin user logged in, pending items exist | 1. Check pending items table on dashboard | Table shows items with columns: Item, Donor, Category, Action. Approve/Reject buttons work for each item. | | |
| TC-54 | Admin dashboard — view items inventory | Admin logged in | 1. Click "Items" in sidebar | Grid of all items loads with images, titles, categories, and status badges. | | |
| TC-55 | Admin dashboard — view items requests | Admin logged in | 1. Click "Requests" in sidebar | Table of all requests loads with Requester, Item, Status columns. | | |
| TC-56 | Admin dashboard — profile settings | Admin logged in | 1. Click "Settings" in sidebar<br>2. Update name and email<br>3. Click "Update Admin Profile" | Profile updates successfully. Changes visible in header avatar/name. | | |

---

### 3.5 Messages

| ID | Feature | Precondition | Test Steps | Expected Result | Actual Result | Status |
|----|---------|-------------|-----------|----------------|---------------|--------|
| TC-57 | Messages — access inbox (unauthenticated) | Not logged in | 1. Open `messages.html` | Alert: "Please login or create an account to access messages."<br>Redirected to `register.html`. | | |
| TC-58 | Messages — send message from item detail | Authenticated user viewing another user's item | 1. Open `item-detail.html?id=<other-item>`<br>2. Click "Message Donor" | Message modal opens with donor name.<br>After sending, redirects to `messages.html?userId=<donor-id>`. | | |
| TC-59 | Messages — send message to self | Authenticated user viewing own item | 1. Click "Message Donor" on own item | Alert: "This listing belongs to you!"<br>No API call sent. | | |
| TC-60 | Messages — inbox loads conversations | Authenticated user with existing conversations | 1. Open `messages.html`<br>2. Observe left sidebar | Conversations list shows partner names, latest message preview, timestamps, and unread badges. | | |
| TC-61 | Messages — open conversation | Authenticated user | 1. Click on a conversation in the sidebar | Chat pane opens on the right. Messages are displayed with chat bubbles (blue for outgoing, gray for incoming). Scrolls to bottom automatically. | | |
| TC-62 | Messages — send new message | Authenticated user, conversation selected | 1. Type message in textarea<br>2. Press Enter or click Send button | Message appears as outgoing bubble immediately. API call succeeds. Inbox updates with the new message preview. | | |

---

### 3.6 Edge Cases & Error Handling

| ID | Feature | Precondition | Test Steps | Expected Result | Actual Result | Status |
|----|---------|-------------|-----------|----------------|---------------|--------|
| TC-63 | Image upload — invalid file type | Authenticated user | 1. Upload a `.txt` or `.pdf` file to `POST /api/items` image field | Multer rejects with error: "Only image files are allowed". 400 response (or Multer error). | | |
| TC-64 | Image upload — file too large (>10MB for items) | Authenticated user | 1. Upload a file >10MB to `POST /api/items` | Multer rejects with file size limit error. | | |
| TC-65 | Image upload — avatar too large (>5MB) | Authenticated user | 1. Upload a file >5MB to `PUT /api/auth/profile` avatar field | Multer rejects with file size limit error for auth profile endpoint (5MB limit). | | |
| TC-66 | Server — 404 on unknown route | Server running | 1. Call `GET /api/nonexistent` | Server returns the `landing.html` page (catch-all handler in server.js). | | |
| TC-67 | Server — health check | Server running | 1. Call `GET /api/ping` | 200 response: `{ "message": "ReValue Hub API running!" }` | | |
| TC-68 | Legacy routes — `/register`, `/login`, `/logout`, `/get-posts`, `/get-post/:id`, `/create-post`, `/delete-post/:id`, `/request-item` | Server running | 1. Call each legacy route | Each behaves identically to its equivalent `/api/*` counterpart (see API_DOCUMENTATION.md for mapping). Note: `/create-post` uses single image upload vs `/api/items` uses array upload. | | |
| TC-69 | Frontend — guest redirected to register | Not logged in, accessing browse.html or item-detail.html | 1. Navigate to `browse.html` or `item-detail.html` without login | Frontend (`js/app.js`) checks `currentUser`. If null, saves the URL to `postAuthRedirect` and redirects to `register.html`. After registration, user is redirected back to the original page. | | |
| TC-70 | Frontend — already logged in redirects away from login/register | User logged in | 1. Navigate to `login.html` or `register.html` | Frontend `initApp()` checks if `currentUser` exists. If yes, redirects to `dashboard.html` (or `admin-dashboard.html` if admin role). | | |

---

## 4. Test Summary

| Feature Group | Total Test Cases |
|--------------|----------------|
| Authentication & Authorization | 18 (TC-01 to TC-18) |
| Items (Donations) | 18 (TC-19 to TC-36) |
| Requests | 11 (TC-37 to TC-47) |
| Admin | 9 (TC-48 to TC-56) |
| Messages | 6 (TC-57 to TC-62) |
| Edge Cases & Error Handling | 8 (TC-63 to TC-70) |
| **Total** | **70** |

### Key Validation Rules Verified

1. **Auth:** Email format regex, password min 6 chars, required fields, duplicate email check, invalid credentials
2. **Items:** Required fields (title, description, category, location), owner-only delete, admin-can-delete-any, 404 handling
3. **Requests:** Cannot request own item, cannot duplicate request, item-owner-only status update, 404 handling
4. **Admin:** Role-based guard (`restrictTo('admin')`), user deletion, stats aggregation
5. **Frontend:** Guest redirects, logged-in user redirects away from auth pages, profile avatar upload, image filtering & size limits via Multer

### Instructions for Manual Testing

1. Start the server (`npm start` or `npm run dev`).
2. Open `http://localhost:3000` in a browser.
3. Execute each test case step by step.
4. For API-specific cases not easily testable via UI, use `curl` commands (see `API_DOCUMENTATION.md` for examples) or browser DevTools Fetch/XHR panel.
5. Fill in the **Actual Result** and **Status** columns after testing.
6. Log any bugs or unexpected behavior separately for triage.

