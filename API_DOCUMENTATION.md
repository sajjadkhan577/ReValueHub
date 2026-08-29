# ReValue Hub REST API Reference

Base URL: `http://localhost:3000`

> **Note:** The legacy PHP API under `api/*.php` is **not mounted** by `server.js` and should be ignored.

---

## Authentication

ReValue Hub uses **JWT (JSON Web Token)** for authentication.

### How tokens are issued

- **Registration** (`POST /api/auth/register`) — a new user is created and a JWT is returned immediately.
- **Login** (`POST /api/auth/login`) — valid credentials return a fresh JWT.

The token is signed with `JWT_SECRET` (environment variable) and expires in **30 days**.

```js
// signing (authController.js)
jwt.sign({ id }, process.env.JWT_SECRET, { expiresIn: '30d' });
```

### How tokens are verified

All protected routes use the `protect` middleware (`middleware/auth.js`):

1. The client sends the token in the `Authorization` header using the `Bearer` scheme.
   ```
   Authorization: Bearer <token>
   ```
2. The middleware decodes the token with `jwt.verify()`.
3. It loads the user from the database via `User.findByPk(decoded.id)`.
4. The user object is attached to `req.user` for downstream handlers.

If the token is missing, expired, or invalid the API responds with **401**:
```json
{ "status": "fail", "message": "You are not logged in!" }
```

### Role-based access

Admin-only routes additionally apply the `restrictTo('admin')` middleware, which checks `req.user.role`. Non-admin users receive **403**:
```json
{ "status": "fail", "message": "You do not have permission to perform this action" }
```

---

## Auth Routes

All routes under `/api/auth`.

---

### POST /api/auth/register

Register a new user account.

- **Auth required:** No
- **Content-Type:** `application/json`

**Request body:**

| Field    | Type   | Required | Description                   |
|----------|--------|----------|-------------------------------|
| name     | string | yes      | User display name             |
| email    | string | yes      | Valid email address           |
| password | string | yes      | Minimum 6 characters          |
| phone    | string | no       | Contact phone number          |

**Success response — `201 Created`:**
```json
{
  "status": "success",
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "avatar": null,
    "role": "user"
  }
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 400    | `{ "status": "fail", "message": "Name, email and password are required" }` |
| 400    | `{ "status": "fail", "message": "Please provide a valid email address" }` |
| 400    | `{ "status": "fail", "message": "Password must be at least 6 characters" }` |
| 400    | `{ "status": "fail", "message": "Validation error" }` _(e.g. duplicate email)_ |

**Example:**
```bash
curl -X POST http://localhost:3000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "secret123",
    "phone": "+1234567890"
  }'
```

---

### POST /api/auth/login

Authenticate with email and password.

- **Auth required:** No
- **Content-Type:** `application/json`

**Request body:**

| Field    | Type   | Required | Description          |
|----------|--------|----------|----------------------|
| email    | string | yes      | Registered email     |
| password | string | yes      | Account password     |

**Success response — `200 OK`:**
```json
{
  "status": "success",
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "avatar": null,
    "role": "user"
  }
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 400    | `{ "status": "fail", "message": "Please provide email and password" }` |
| 401    | `{ "status": "fail", "message": "Incorrect email or password" }` |

**Example:**
```bash
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jane@example.com",
    "password": "secret123"
  }'
```

---

### POST /api/auth/logout

Invalidate the current session (stateless — simply returns a success message).

- **Auth required:** No

**Success response — `200 OK`:**
```json
{
  "status": "success",
  "message": "Logged out"
}
```

**Example:**
```bash
curl -X POST http://localhost:3000/api/auth/logout
```

---

### GET /api/auth/me

Return the authenticated user's profile.

- **Auth required:** Yes (`Authorization: Bearer <token>`)

**Success response — `200 OK`:**
```json
{
  "status": "success",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "avatar": null,
    "role": "user"
  }
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |
| 401    | `{ "status": "fail", "message": "Invalid token." }` |

**Example:**
```bash
curl http://localhost:3000/api/auth/me \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

### PUT /api/auth/profile

Update the authenticated user's profile (name, email, phone, avatar).

- **Auth required:** Yes (`Authorization: Bearer <token>`)
- **Content-Type:** `multipart/form-data`

**Request fields:**

| Field  | Type   | Required | Description                            |
|--------|--------|----------|----------------------------------------|
| name   | string | yes      | Updated display name                   |
| email  | string | yes      | Updated email address                  |
| phone  | string | no       | Updated phone (falls back to current)  |
| avatar | file   | no       | Image file (jpeg/png/webp/gif, max 5MB)|

**Success response — `200 OK`:**
```json
{
  "status": "success",
  "user": {
    "id": 1,
    "name": "Jane Updated",
    "email": "jane.new@example.com",
    "phone": "+9876543210",
    "avatar": "/uploads/avatar-1700000000-123456789.png",
    "role": "user"
  }
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 400    | `{ "status": "fail", "message": "Name and email are required" }` |
| 400    | `{ "status": "fail", "message": "Please provide a valid email address" }` |
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |

**Example:**
```bash
curl -X PUT http://localhost:3000/api/auth/profile \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..." \
  -F "name=Jane Updated" \
  -F "email=jane.new@example.com" \
  -F "phone=+9876543210" \
  -F "avatar=@/path/to/avatar.jpg"
```

---

## Items Routes

All routes under `/api/items`.

---

### GET /api/items

List all available items with optional filtering.

- **Auth required:** No

**Query parameters:**

| Parameter | Type   | Required | Description                                |
|-----------|--------|----------|--------------------------------------------|
| search    | string | no       | Partial title search (case-insensitive)    |
| category  | string | no       | Exact category match                       |
| location  | string | no       | Partial location match                     |
| limit     | number | no       | Maximum number of results                  |

**Success response — `200 OK`:**
```json
[
  {
    "id": 1,
    "title": "Wooden Chair",
    "description": "Gently used wooden chair in good condition.",
    "category": "furniture",
    "location": "Austin, TX",
    "image": ["/uploads/1700000000-123456789.jpg"],
    "condition": "good",
    "mfgDate": null,
    "expDate": null,
    "created_at": "2025-01-15T10:00:00.000Z",
    "updated_at": "2025-01-15T10:00:00.000Z",
    "user_id": 2,
    "User": {
      "id": 2,
      "name": "John Smith",
      "email": "john@example.com"
    }
  }
]
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 400    | `{ "status": "fail", "message": "Database error message" }` |

**Example:**
```bash
# List all items
curl http://localhost:3000/api/items

# Search & filter
curl "http://localhost:3000/api/items?search=chair&category=furniture&location=Austin&limit=5"
```

---

### GET /api/items/user

List items posted by the authenticated user.

- **Auth required:** Yes (`Authorization: Bearer <token>`)

**Success response — `200 OK`:**
```json
[
  {
    "id": 1,
    "title": "Wooden Chair",
    "description": "Gently used wooden chair...",
    "category": "furniture",
    "location": "Austin, TX",
    "image": ["/uploads/1700000000-123456789.jpg"],
    "condition": "good",
    "mfgDate": null,
    "expDate": null,
    "created_at": "2025-01-15T10:00:00.000Z",
    "updated_at": "2025-01-15T10:00:00.000Z",
    "user_id": 1
  }
]
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |

**Example:**
```bash
curl http://localhost:3000/api/items/user \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

### GET /api/items/:id

Get a single item by its ID.

- **Auth required:** No

**Path parameters:**

| Parameter | Type   | Description |
|-----------|--------|-------------|
| id        | number | Item ID     |

**Success response — `200 OK`:**
```json
{
  "id": 1,
  "title": "Wooden Chair",
  "description": "Gently used wooden chair in good condition.",
  "category": "furniture",
  "location": "Austin, TX",
  "image": ["/uploads/1700000000-123456789.jpg"],
  "condition": "good",
  "mfgDate": null,
  "expDate": null,
  "created_at": "2025-01-15T10:00:00.000Z",
  "updated_at": "2025-01-15T10:00:00.000Z",
  "user_id": 2,
  "User": {
    "id": 2,
    "name": "John Smith",
    "email": "john@example.com"
  }
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 404    | `{ "status": "fail", "message": "Item not found" }` |

**Example:**
```bash
curl http://localhost:3000/api/items/1
```

---

### POST /api/items

Create a new item listing.

- **Auth required:** Yes (`Authorization: Bearer <token>`)
- **Content-Type:** `multipart/form-data`

**Request fields:**

| Field       | Type       | Required | Description                                 |
|-------------|------------|----------|---------------------------------------------|
| title       | string     | yes      | Item title                                  |
| description | string     | yes      | Item description                            |
| category    | string     | yes      | Item category (e.g. "furniture", "medicine")|
| location    | string     | yes      | Pickup / item location                      |
| condition   | string     | no       | Condition (default: "good")                 |
| mfgDate     | string     | no       | Manufacture date (YYYY-MM-DD, medicine only)|
| expDate     | string     | no       | Expiry date (YYYY-MM-DD, medicine only)     |
| image       | file[]     | no       | Up to 10 image files (jpeg/png/webp/gif, max 10MB each) |

**Success response — `201 Created`:**
```json
{
  "id": 3,
  "title": "Office Desk",
  "description": "Sturdy wooden office desk.",
  "category": "furniture",
  "location": "Austin, TX",
  "image": ["/uploads/1700000000-987654321.jpg"],
  "condition": "like new",
  "mfgDate": null,
  "expDate": null,
  "created_at": "2025-02-01T12:00:00.000Z",
  "updated_at": "2025-02-01T12:00:00.000Z",
  "user_id": 1
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 400    | `{ "status": "fail", "message": "Title, description, category and location are required" }` |
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |

**Example:**
```bash
curl -X POST http://localhost:3000/api/items \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..." \
  -F "title=Office Desk" \
  -F "description=Sturdy wooden office desk." \
  -F "category=furniture" \
  -F "location=Austin, TX" \
  -F "condition=like new" \
  -F "image=@/path/to/desk.jpg"
```

---

### DELETE /api/items/:id

Delete an item listing. The item owner or any admin can delete.

- **Auth required:** Yes (`Authorization: Bearer <token>`)

**Path parameters:**

| Parameter | Type   | Description |
|-----------|--------|-------------|
| id        | number | Item ID     |

**Success response — `204 No Content`** (no body).

**Error responses:**

| Status | Example body |
|--------|-------------|
| 404    | `{ "status": "fail", "message": "Item not found" }` |
| 403    | `{ "status": "fail", "message": "Unauthorized" }` |
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |

**Example:**
```bash
curl -X DELETE http://localhost:3000/api/items/3 \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

## Requests Routes

All routes under `/api/requests`.

---

### POST /api/requests

Create a request to claim an item.

- **Auth required:** Yes (`Authorization: Bearer <token>`)
- **Content-Type:** `application/json`

**Request body:**

| Field   | Type   | Required | Description      |
|---------|--------|----------|------------------|
| item_id | number | yes      | ID of the item   |

**Success response — `201 Created`:**
```json
{
  "id": 5,
  "item_id": 1,
  "requester_id": 3,
  "status": "pending",
  "created_at": "2025-02-10T08:30:00.000Z",
  "updated_at": "2025-02-10T08:30:00.000Z"
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 404    | `{ "status": "fail", "message": "Item not found" }` |
| 400    | `{ "status": "fail", "message": "You cannot request your own item" }` |
| 400    | `{ "status": "fail", "message": "You have already requested this item" }` |
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |

**Example:**
```bash
curl -X POST http://localhost:3000/api/requests \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..." \
  -H "Content-Type: application/json" \
  -d '{"item_id": 1}'
```

---

### GET /api/requests/user

Get all requests made by the authenticated user.

- **Auth required:** Yes (`Authorization: Bearer <token>`)

**Success response — `200 OK`:**
```json
[
  {
    "id": 5,
    "item_id": 1,
    "requester_id": 3,
    "status": "pending",
    "created_at": "2025-02-10T08:30:00.000Z",
    "updated_at": "2025-02-10T08:30:00.000Z",
    "Item": {
      "id": 1,
      "title": "Wooden Chair",
      "category": "furniture",
      "image": ["/uploads/1700000000-123456789.jpg"],
      "location": "Austin, TX"
    },
    "User": {
      "name": "John Smith",
      "email": "john@example.com"
    }
  }
]
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |

**Example:**
```bash
curl http://localhost:3000/api/requests/user \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

### GET /api/requests/item/:itemId

Get all requests for a specific item (useful for item owners to see who requested).

- **Auth required:** Yes (`Authorization: Bearer <token>`)

**Path parameters:**

| Parameter | Type   | Description |
|-----------|--------|-------------|
| itemId    | number | Item ID     |

**Success response — `200 OK`:**
```json
[
  {
    "id": 5,
    "item_id": 1,
    "requester_id": 3,
    "status": "pending",
    "created_at": "2025-02-10T08:30:00.000Z",
    "updated_at": "2025-02-10T08:30:00.000Z",
    "User": {
      "name": "Jane Doe",
      "email": "jane@example.com"
    }
  }
]
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |

**Example:**
```bash
curl http://localhost:3000/api/requests/item/1 \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

### PATCH /api/requests/:id

Update the status of a request (approve or reject). Only the item owner can update.

- **Auth required:** Yes (`Authorization: Bearer <token>`)
- **Content-Type:** `application/json`

**Path parameters:**

| Parameter | Type   | Description    |
|-----------|--------|----------------|
| id        | number | Request ID     |

**Request body:**

| Field  | Type   | Required | Description                        |
|--------|--------|----------|------------------------------------|
| status | string | yes      | New status: `"approved"` or `"rejected"` |

**Success response — `200 OK`:**
```json
{
  "id": 5,
  "item_id": 1,
  "requester_id": 3,
  "status": "approved",
  "created_at": "2025-02-10T08:30:00.000Z",
  "updated_at": "2025-02-10T09:00:00.000Z"
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 404    | `{ "status": "fail", "message": "Request not found" }` |
| 403    | `{ "status": "fail", "message": "Unauthorized" }` |
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |

**Example:**
```bash
curl -X PATCH http://localhost:3000/api/requests/5 \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..." \
  -H "Content-Type: application/json" \
  -d '{"status": "approved"}'
```

---

## Admin Routes

All routes under `/api/admin`. Every route requires **authentication** (`protect`) and **admin role** (`restrictTo('admin')`).

---

### GET /api/admin/users

List all registered users (admin only).

- **Auth required:** Yes (admin role)

**Success response — `200 OK`:**
```json
[
  {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "role": "user",
    "created_at": "2025-01-10T10:00:00.000Z"
  },
  {
    "id": 2,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin",
    "created_at": "2025-01-01T08:00:00.000Z"
  }
]
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |
| 403    | `{ "status": "fail", "message": "You do not have permission to perform this action" }` |

**Example:**
```bash
curl http://localhost:3000/api/admin/users \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

### DELETE /api/admin/users/:id

Delete a user account (admin only).

- **Auth required:** Yes (admin role)

**Path parameters:**

| Parameter | Type   | Description |
|-----------|--------|-------------|
| id        | number | User ID     |

**Success response — `204 No Content`** (no body).

**Error responses:**

| Status | Example body |
|--------|-------------|
| 404    | `{ "status": "fail", "message": "User not found" }` |
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |
| 403    | `{ "status": "fail", "message": "You do not have permission to perform this action" }` |

**Example:**
```bash
curl -X DELETE http://localhost:3000/api/admin/users/1 \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

### GET /api/admin/stats

Get aggregate statistics (admin only).

- **Auth required:** Yes (admin role)

**Success response — `200 OK`:**
```json
{
  "users": 25,
  "items": 102,
  "requests": 47
}
```

**Error responses:**

| Status | Example body |
|--------|-------------|
| 401    | `{ "status": "fail", "message": "You are not logged in!" }` |
| 403    | `{ "status": "fail", "message": "You do not have permission to perform this action" }` |

**Example:**
```bash
curl http://localhost:3000/api/admin/stats \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

## Legacy Routes

For backward compatibility with the static HTML frontend (`js/app.js`), the following legacy routes are also mounted on the root path. They are functionally identical to their `/api/*` counterparts.

| Method | Path              | Auth required | Equivalent API route       |
|--------|-------------------|---------------|---------------------------|
| POST   | `/register`       | No            | `POST /api/auth/register` |
| POST   | `/login`          | No            | `POST /api/auth/login`    |
| POST   | `/logout`         | No            | `POST /api/auth/logout`   |
| POST   | `/create-post`    | Yes (protect) | `POST /api/items`         |
| GET    | `/get-posts`      | No            | `GET /api/items`          |
| GET    | `/get-post/:id`   | No            | `GET /api/items/:id`      |
| DELETE | `/delete-post/:id`| Yes (protect) | `DELETE /api/items/:id`   |
| POST   | `/request-item`   | Yes (protect) | `POST /api/requests`      |

> ⚠️ Note: `POST /create-post` uses `upload.single('image')` (one image), while `POST /api/items` uses `upload.array('image', 10)` (up to 10 images).

---

## Common Error Responses

| Status | Meaning                     | Typical body                                          |
|--------|-----------------------------|-------------------------------------------------------|
| 400    | Bad request / validation    | `{ "status": "fail", "message": "..." }`              |
| 401    | Not authenticated           | `{ "status": "fail", "message": "You are not logged in!" }` |
| 403    | Forbidden (wrong role/owner)| `{ "status": "fail", "message": "Unauthorized" }` / role message |
| 404    | Resource not found          | `{ "status": "fail", "message": "Item not found" }`   |
| 201    | Created successfully        | Resource JSON (with `status` for auth routes)          |
| 200    | OK                          | Resource JSON / array                                   |
| 204    | No content (deletion)       | *(empty body)*                                         |

