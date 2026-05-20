# Ethizo Home Health Care

Responsive home health operations platform for Ethizo with a `React + Vite` frontend and a `CakePHP + MySQL` backend. The current codebase implements a working foundation for Medicare-first intake, episode management, OASIS versioning, visit workflows, EVV handling, QA tasks, and claim release logic.

## Project Layout

- `frontend/`: React/Vite client for office users and mobile/tablet clinician workflows.
- `backend/`: CakePHP API, workflow services, database schema, and tests.

## Backend Features Included

- Bearer-token API with role-aware demo login flow.
- Core domain endpoints for:
  - `/api/v1/auth`
  - `/api/v1/patients`
  - `/api/v1/referrals`
  - `/api/v1/episodes`
  - `/api/v1/assessments`
  - `/api/v1/visits`
  - `/api/v1/evv`
  - `/api/v1/claims`
  - `/api/v1/qa`
- Workflow services for:
  - referral to episode conversion
  - OASIS-E1 vs OASIS-E2 selection based on assessment date
  - episode activation after SOC + finalized assessment
  - NOA due-date creation
  - PDGM grouping scaffold
  - EVV record creation on visit checkout
  - QA and audit event generation

## Frontend Features Included

- Responsive Ethizo workspace with modules for Overview, Patients, Referrals, Episodes, Clinician, Billing, and QA.
- Mobile/tablet-friendly clinician workspace with offline queue storage for visit check-in and check-out.
- API-first bootstrap with automatic fallback to local demo mode when the backend is unavailable.
- UI coverage for:
  - patient registration
  - incoming referrals
  - episode creation
  - SOC and OASIS completion
  - visit scheduling
  - location-aware check-in/out
  - Georgia EVV review
  - NOA and episode billing
  - QA workflow resolution
  - recertification, transfer, ROC, and death-at-home readiness guidance

## Local Setup

### Backend

1. `cd /Users/air/Documents/New project/backend`
2. Copy `config/app_local.example.php` to `config/app_local.php` if you want custom settings.
3. Point the default datasource at MySQL in `config/app_local.php`.
4. Run migrations:
   - `bin/cake migrations migrate`
5. Start the API:
   - `bin/cake server -p 8765`

### Frontend

1. `cd /Users/air/Documents/New project/frontend`
2. Install dependencies:
   - `npm install`
3. Start Vite:
   - `npm run dev`
4. Optionally point the client at the API:
   - `VITE_API_BASE_URL=http://localhost:8765/api/v1`

## Verification

- Backend tests pass:
  - `cd /Users/air/Documents/New project/backend && composer test`
- Frontend verification passes:
  - `cd /Users/air/Documents/New project/frontend && npm run lint`
  - `cd /Users/air/Documents/New project/frontend && npm run build`
