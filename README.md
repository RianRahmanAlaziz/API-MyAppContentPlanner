# 🚀 Content Planner Backend (Laravel 12)

Backend REST API untuk aplikasi **Content Planner** (Instagram, TikTok, YouTube) berbasis **Workspace & Team Collaboration**.

Project ini membantu tim content/marketing untuk:
- menyusun ide konten
- mengatur workflow (kanban)
- menjadwalkan posting
- review & approval
- checklist produksi
- komentar internal tim

---

## ✨ Features

- Authentication (Laravel Sanctum Token)
- Workspace / Team Management
- Role-based Access Control: **Owner / Editor / Reviewer / Viewer**
- **Admin Super User** (akses semua workspace & endpoint)
- Content CRUD (Create, Read, Update, Delete)
- Kanban Workflow:  
  `idea → brief → production → review → scheduled → published`
- Scheduling (Calendar-ready)
- Comments
- Checklist Items
- Approvals (approve / request changes)
- Standard Error Response Format (API-friendly)

---

## 🧩 Tech Stack

- **Laravel 12**
- **Laravel Sanctum** (API Token)
- **MySQL / MariaDB**
- **Policy Authorization**
- **OpenAPI Documentation** (Swagger-ready)

---

## 👤 Roles & Permissions

### Global Role (`users.role`)

| Role  | Hak |
|------|-----|
| admin | Akses penuh semua workspace & endpoint |
| user  | Akses tergantung role di workspace |

### Workspace Role (`workspace_members.role`)

| Role     | Hak |
|----------|-----|
| owner    | Full akses (manage members, content, approval) |
| editor   | Create / update content, checklist, comments |
| reviewer | Comment + approve / request changes |
| viewer   | Read-only |

---

## ⚙️ Installation

### 1) Clone & Install

```bash
git clone https://github.com/yourusername/content-planner-backend.git
cd content-planner-backend
composer install
```

### 2) Setup Environment (.env)

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` sesuai database kamu:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=content_planner
DB_USERNAME=root
DB_PASSWORD=
```

### 3) Run Migration & Server

```bash
php artisan migrate
php artisan serve
```

Server berjalan di:

```
http://127.0.0.1:8000
```

---

## 🔑 Authentication (Sanctum Token)

Semua endpoint API membutuhkan token Bearer.

Gunakan header berikut:

```
Authorization: Bearer <token>
Accept: application/json
```

---

## 🔐 Login Example

Request:

**POST** `/api/auth/login`

```json
{
  "email": "admin@mail.com",
  "password": "admin"
}
```

Response:

```json
{
  "user": {
    "id": 1,
    "name": "admin",
    "email": "admin@mail.com",
    "role": "admin"
  },
  "token": "1|xxxxxxxxxxxx"
}
```

---

## 📦 API Endpoints

### Auth
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET  /api/me`

### Workspaces
- `GET  /api/workspaces`
- `POST /api/workspaces`
- `GET  /api/workspaces/{id}`

### Workspace Members
- `GET    /api/workspaces/{id}/members`
- `POST   /api/workspaces/{id}/members`
- `PATCH  /api/workspaces/{id}/members/{userId}`
- `DELETE /api/workspaces/{id}/members/{userId}`

### Contents
- `GET    /api/workspaces/{id}/contents`
- `POST   /api/workspaces/{id}/contents`
- `GET    /api/contents/{id}`
- `PATCH  /api/contents/{id}`
- `DELETE /api/contents/{id}`
- `PATCH  /api/contents/{id}/move`
- `PATCH  /api/contents/{id}/schedule`

### Comments
- `GET  /api/contents/{id}/comments`
- `POST /api/contents/{id}/comments`

### Checklist
- `GET    /api/contents/{id}/checklist`
- `POST   /api/contents/{id}/checklist`
- `PATCH  /api/checklist-items/{id}`
- `DELETE /api/checklist-items/{id}`

### Approvals
- `GET  /api/contents/{id}/approvals`
- `POST /api/contents/{id}/approve`
- `POST /api/contents/{id}/request-changes`

---

## 🔍 Filtering & Searching Contents

Endpoint:

```
GET /api/workspaces/{workspaceId}/contents
```

Query params:

| Param | Example | Description |
|------|---------|-------------|
| status | status=review | filter by workflow status |
| platform | platform=ig | filter by platform |
| assignee_id | assignee_id=2 | filter by assignee |
| q | q=edukasi | search title/hook/caption |
| from | 2026-02-01 00:00:00 | scheduled_at >= from |
| to | 2026-02-28 23:59:59 | scheduled_at <= to |
| page | page=1 | pagination |

Example:

```
GET /api/workspaces/3/contents?status=scheduled&from=2026-02-01 00:00:00&to=2026-02-28 23:59:59
```

---

## 🧱 Standard Error Format

### Validation Error (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "error": {
    "type": "validation_error",
    "details": {
      "title": [
        "The title field is required."
      ]
    }
  }
}
```

### Forbidden (403)

```json
{
  "success": false,
  "message": "Forbidden",
  "error": {
    "type": "forbidden"
  }
}
```

### Not Found (404)

```json
{
  "success": false,
  "message": "Not found",
  "error": {
    "type": "not_found"
  }
}
```

---

## 📖 API Documentation (OpenAPI / Swagger)

Dokumentasi OpenAPI tersedia dalam file:

```
openapi.yaml
```

Run Swagger UI (Docker):

```bash
docker run -p 8080:8080 \
  -e SWAGGER_JSON=/foo/openapi.yaml \
  -v $(pwd)/openapi.yaml:/foo/openapi.yaml \
  swaggerapi/swagger-ui
```

Lalu buka:

```
http://localhost:8080
```

---


## 📌 Project Status

🚧 MVP (In Progress)

Planned:
- Notifications
- Activity Logs
- Media Upload
- Analytics Report

---

## ✨ Author

Developed by **RianRahmanAlaziz**  
Backend: Laravel 12 
