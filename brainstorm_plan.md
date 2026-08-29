# API Documentation Plan

## Files to analyze (already read)
- server.js
- routes/auth.js, routes/items.js, routes/requests.js, routes/admin.js
- controllers/authController.js, controllers/itemController.js, controllers/requestController.js, controllers/adminController.js
- middleware/auth.js
- models/User.js, models/Item.js, models/Request.js

## Information Gathered

### Authentication (JWT)
- Token issued on register (201) and login (200) using `jwt.sign({ id }, JWT_SECRET, { expiresIn: '30d' })`
- Token verified via `protect` middleware: checks `Authorization: Bearer <token>` header, decodes with `jwt.verify`, fetches user via `User.findByPk(decoded.id)`
- Role-based access via `restrictTo('admin')`
- Logout is stateless (just returns success message)

### Route Groups

#### Auth (`/api/auth`)
| Method | Path | Auth | Body/Params | Response |
|--------|------|------|-------------|----------|
| POST | /api/auth/register | No | name, email, password, phone (opt) | 201: { status, token, user } |
| POST | /api/auth/login | No | email, password | 200: { status, token, user } |
| POST | /api/auth/logout | No | - | 200: { status, message } |
| GET | /api/auth/me | Yes (protect) | - | 200: { status, user } |
| PUT | /api/auth/profile | Yes (protect) | name, email, phone (opt), avatar (file) | 200: { status, user } |

#### Items (`/api/items`)
| Method | Path | Auth | Body/Params | Response |
|--------|------|------|-------------|----------|
| GET | /api/items | No | query: search, category, location, limit | 200: Item[] |
| GET | /api/items/user | Yes (protect) | - | 200: Item[] (own) |
| GET | /api/items/:id | No | - | 200: Item |
| POST | /api/items | Yes (protect) | title, description, category, location, condition (opt), mfgDate, expDate (opt), image (file array) | 201: Item |
| DELETE | /api/items/:id | Yes (protect) | - | 204: no content |

#### Requests (`/api/requests`)
| Method | Path | Auth | Body/Params | Response |
|--------|------|------|-------------|----------|
| POST | /api/requests | Yes (protect) | item_id | 201: Request |
| GET | /api/requests/user | Yes (protect) | - | 200: Request[] (own) |
| GET | /api/requests/item/:itemId | Yes (protect) | - | 200: Request[] |
| PATCH | /api/requests/:id | Yes (protect) | status | 200: Request |

#### Admin (`/api/admin`) — all require protect + restrictTo('admin')
| Method | Path | Auth | Body/Params | Response |
|--------|------|------|-------------|----------|
| GET | /api/admin/users | Admin | - | 200: User[] |
| DELETE | /api/admin/users/:id | Admin | - | 204: no content |
| GET | /api/admin/stats | Admin | - | 200: { users, items, requests } |

#### Legacy Routes (mounted on root in server.js, duplicates)
| Method | Path | Auth | Notes |
|--------|------|------|-------|
| POST | /register | No | Same as /api/auth/register |
| POST | /login | No | Same as /api/auth/login |
| POST | /logout | No | Same as /api/auth/logout |
| POST | /create-post | Yes (protect) | Same as POST /api/items (but single image) |
| GET | /get-posts | No | Same as GET /api/items |
| GET | /get-post/:id | No | Same as GET /api/items/:id |
| DELETE | /delete-post/:id | Yes (protect) | Same as DELETE /api/items/:id |
| POST | /request-item | Yes (protect) | Same as POST /api/requests |

These legacy routes exist for backward compatibility with the static HTML pages in `js/app.js`.

### Error Responses
All controllers return errors as: `{ status: 'fail', message: '<error message>' }`
- 400: Validation error / bad request
- 401: Not authenticated (missing/invalid token, bad credentials)
- 403: Forbidden (not owner or not admin)
- 404: Resource not found

## Plan
1. Create `API_DOCUMENTATION.md` in project root with:
   - Title and overview
   - Authentication section (JWT flow, how to obtain token, how to use it)
   - Auth routes group
   - Items routes group
   - Requests routes group
   - Admin routes group
   - Each route: method, path, auth requirement, request body/params, success response, error responses, curl example

