# CRM Backlog — User Stories & Acceptance Criteria

Overview
--------
This backlog implements SRS features using the repository's existing modules and conventions. Each story follows the project's patterns (Eloquent, migrations, factories, Livewire, Flux UI, Pest tests) and uses the Docker/dev commands in `README.md` and `docs/`.

Epics
-----
- Contacts (contact management)
- Leads (capture, assignment, conversion)
- Pipeline (deal stages, visualization)
- Calls (call logging, click-to-call integration)
- Form Builder (dynamic forms storage and render)
- Reports (lead/call analytics)

Module policy
-------------
- **Mandatory core modules**: `Base CRM` (contacts, leads, pipeline, basic call logging) and `Form Builder` are required core features and must be implemented in the central repository under `Modules/CRM` (or existing relevant modules).
- **Optional per-client modules**: Additional specialized modules (for example a dedicated `Sales` module with bespoke workflows) are delivered per-client as separate modules inside `Modules/` and must follow repository module conventions. Optional modules should not change global bootstrap providers.

Stories
-------

1) Contacts: CRUD
- As a user, I can create, read, update, and delete contacts.
- Acceptance:
  - Contacts stored in `contacts` table with `name`, `email`, `phone`, `company`, `owner_id`.
  - Livewire CRUD UI available inside existing module (`Modules/CRM` or existing contacts area).
  - Factory and migration provided; Pest feature tests cover create/read/update/delete.

2) Leads: Capture & Assign
- As a user, I can create leads and assign them to agents.
- Acceptance:
  - `leads` table with `source`, `status`, `contact_id`, `owner_id`, `score`.
  - Assignment UI with role-based access (reuse `spatie/permission`).
  - Conversion action to create a Contact from a Lead.

3) Pipeline: Stages & Deals
- As a sales user, I can move deals through pipeline stages.
- Acceptance:
  - `deals` (or `opportunities`) model with `stage`, `value`, `close_date`.
  - Drag-and-drop stage UI implemented with Livewire (follow existing patterns).

4) Calls: Logging & Click-to-Call
- As an agent, I can log calls and initiate click-to-call.
- Acceptance:
  - `calls` table with `call_sid`, `direction`, `duration`, `contact_id`, `user_id`, `recording_url`.
  - Incoming-call popup integrates with existing Reverb/Echo broadcast channels.
  - Click-to-call initiates a broadcast event that the telephony adapter handles (no new global providers).

5) Form Builder: Save & Render
- As an admin, I can design a form and assign it to modules.
- Acceptance:
  - `forms` table stores JSON schema and assignment metadata.
  - Builder UI implemented as a Livewire component within `Modules/CRM`.
  - Rendered forms validate based on stored rules and persist collected data to module-specific tables or a generic `form_submissions` table.

6) Reports: CRM Analytics
- As an admin, I can view lead, pipeline, and call reports.
- Acceptance:
  - Reuse existing reporting utilities; add CRM-specific queries.
  - Provide export endpoints (CSV/Excel) using existing export pipeline.

Non-functional Acceptance
------------------------
- All new code must include migrations, factories, and Pest tests.
- Follow coding standards and formatting: run `vendor/bin/pint`.
- Use existing modules and services; do not alter bootstrap providers.

Priority & Next Steps
---------------------
- Priority 1: Contacts CRUD, Leads capture, Calls logging (basic)
- Priority 2: Pipeline UI, Form Builder basic save/render
- Priority 3: Advanced reports, exports, call recordings

Developer Notes
---------------
- Use `php artisan make:model --migration --factory` inside the container per README.
- Run tests selectively: `php artisan test --compact --filter=Contact`.
- Keep changes limited to `Modules/CRM` or existing relevant modules.
