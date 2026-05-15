# ReValue Hub Functional Web App Implementation TODO

Status: ✅ **PLAN APPROVED** - Proceeding with edits.

## Breakdown of Approved Plan (Logical Steps)

### Phase 1: Backend Setup (Dependencies & Server)
- [✅] 1. Create package.json with deps (express, sequelize, bcryptjs, jsonwebtoken, cors, multer, dotenv, nodemon)
- [✅] 2. Create .env.example + server.js (Express app, MariaDB connect, middleware, static serve, basic routes mount)
- [✅] 3. `npm install` → Test server starts on :3000

### Phase 2: Database Models
- [✅] 4. models/User.js (email, pwdHash, name, role)
- [✅] 5. models/Item.js (title,desc,category,images[],userId,location,status)
- [✅] 6. models/Request.js (itemId,userId,status)

### Phase 3: API Routes
- [✅] 7. middleware/auth.js (JWT verify)
- [✅] 8. routes/auth.js (POST /register, /login)
- [✅] 9. routes/items.js (GET/POST /items, GET /items/:id)
- [✅] 10. routes/requests.js (POST /request)
- [✅] 11. routes/admin.js (GET /admin/stats, POST /admin/approve/:id, DELETE /admin/item/:id) - protected

### Phase 4: Frontend Integration (HTML Edits)
- [⏳] 12. Add global <script> to all HTML (auth helpers, fetch wrapper, nav guards)
- [⏳] 13. Landing + nav linking/auth checks
- [⏳] 14. Login/Register forms → API + redirect
- [ ] 15. Browse/Discovery/Item detail → dynamic fetch/render
- [ ] 16. List new item form → POST /items
- [ ] 17. User/Admin dashboards → protected fetches
- [⏳] 18. Test full flow: register → list → browse → request → dashboards
  - [✅] Server fix: try-catch DB sync crash

**Backend API complete. Frontend dynamic next. Server fixed & restarted.**

### Phase 5: Testing & Polish
- [ ] 19. Multer for images (cloudinary? or local)
- [ ] 20. Error handling, validation
- [ ] 21. Seed sample data
- [ ] 22. Full E2E test + `npm run dev` (nodemon)

**Next: Frontend dynamic integration (Phase 4). Current: Server running, pages connected via js/app.js nav/API stubs.**

