# CRM API

A small CRM backend built with Laravel 12, Sanctum, and a minimal Bootstrap frontend for manually testing the API. Built as an interview assignment — the backend received the majority of the attention; the frontend is intentionally bare.

## Tech Stack

- Laravel 12 / PHP 8.2+
- MySQL
- Laravel Sanctum (token authentication)
- Eloquent ORM, API Resources, Form Requests, Policies
- Plain Blade + Bootstrap (CDN) + vanilla `fetch()` for the frontend — no Vue/Inertia/build step

## Installation

```bash
composer install
copy .env.example .env      # already present in this repo with DB_DATABASE=crm
php artisan key:generate
```

Create a MySQL database matching your `.env` (`DB_DATABASE=crm` by default), then run:

```bash
php artisan migrate --seed
```

Serve the app:

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000/login` for the test UI, or hit `http://127.0.0.1:8000/api/...` directly.

## Migrations

Three domain tables, in addition to Laravel's defaults:

- `users` — adds a `role` column (`manager` | `rep`)
- `leads` — `name`, `email`, `phone`, `company` (nullable), `source`, `status`, `expected_value` (`DECIMAL(12,2)`), `assigned_to` (FK to `users`, nullable)
- `activities` — `lead_id`, `user_id`, `type`, `body`, `occurred_at`

Indexes were added on `leads.status`, `leads.source`, and `leads.expected_value` (plus the automatic index on the `assigned_to` foreign key) since the report and list endpoints filter/sort/aggregate on those columns and are expected to scale to ~100k rows.

## Seeder

`php artisan db:seed` (or `migrate --seed`) creates:

- 1 manager
- 2 reps
- 25 leads split across the two reps (plus 5 unassigned), with a realistic mix of statuses
- Activities on most leads — every lead seeded as `won`/`lost` is guaranteed at least one activity, since the API would otherwise reject that state

### Login credentials

All seeded users share the password `password`.

| Role    | Email             |
|---------|-------------------|
| Manager | manager@crm.test  |
| Rep     | rep1@crm.test     |
| Rep     | rep2@crm.test     |

## API Routes

All routes are prefixed with `/api`. Every route except `/login` requires a Sanctum bearer token (`Authorization: Bearer <token>`).

| Method | Endpoint                        | Description                                             |
|--------|----------------------------------|-----------------------------------------------------------|
| POST   | `/login`                         | Authenticate, returns a Sanctum token                    |
| POST   | `/logout`                        | Revoke the current token                                  |
| GET    | `/leads`                         | List leads (filter/search/sort/paginate, scoped by role)  |
| POST   | `/leads`                         | Create a lead                                              |
| GET    | `/leads/{lead}`                  | View a lead, with its assigned rep and activities          |
| PATCH  | `/leads/{lead}`                  | Update a lead (any field, including status)                |
| POST   | `/leads/{lead}/assign`           | Assign a lead to a rep (manager only)                       |
| POST   | `/leads/{lead}/activities`       | Log an activity on a lead                                   |
| GET    | `/reports/rep-performance`       | Per-rep performance report (all reps for a manager, self only for a rep) |

`GET /leads` query parameters: `status`, `source`, `assigned_to` (manager only), `search` (matches name/email/company), `sort_by` (`created_at` | `expected_value`), `sort_dir` (`asc` | `desc`), `page`, `per_page`.

Every JSON response follows the same envelope:

```json
{ "success": true, "message": "...", "data": ... }
```

Validation failures return Laravel's standard 422 response shape (`message` + `errors`).

## Authorization Rules

Enforced via `LeadPolicy` and `ActivityPolicy`, not repeated in controllers:

- **Managers** can view, update, and assign any lead, and see every rep's report.
- **Reps** can only view/update leads assigned to them, can only log activities on their own leads, and only see their own report row.
- Only managers can assign leads.
- A lead cannot be moved to `won` or `lost` unless it already has at least one activity — enforced in `LeadController::update()`, returns a 422 with a clear message on violation.

## Assumptions & Trade-offs

- **Lead creation** isn't restricted to a role in the prompt, so both managers and reps can create leads.
- **Search** (`GET /leads?search=`) matches a single query against `name`, `email`, and `company` with `LIKE`, rather than three separate filter params — this is the more common CRM UX and simpler to test from the UI.
- **Activity count** in the performance report counts activities logged *by* that rep (`activities.user_id`), not all activity on their leads (which could include a manager's activity) — read as "how active is this rep."
- **Report money fields** are summed in SQL as `DECIMAL` but cast to PHP `float` for the JSON response. Storage stays precise; only the API representation trades precision for simplicity.
- **Assigning a lead** in the frontend takes a raw rep user ID rather than a dropdown of names, since the API doesn't expose a `/users` listing endpoint and adding one wasn't in scope.
- The frontend authenticates with a Sanctum token stored in `localStorage` and sent as a Bearer header on every `fetch()` — it does not use Sanctum's cookie/SPA mode, since the assignment only calls for a way to exercise the API manually.

## Future Improvements

- A `/users` endpoint so the frontend can show a rep picker instead of a raw ID field.
- Soft deletes / archiving for leads instead of hard deletes (no delete endpoint exists today).
- Rate limiting tuned per-endpoint rather than Laravel's default `api` throttle.
- Structured activity timeline filtering (by type, by date range) on the lead detail view.
