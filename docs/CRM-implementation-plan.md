# CRM Implementation Plan (mapped from SRS)

Summary
-------
This document maps the Wevetel CRM SRS to the existing repository modules and outlines the next-step plan to implement features step-by-step without changing the repository architecture. All commands and workflows follow the project's README and docs/ guidance.

Mapping (SRS → Repo)
---------------------
- Contacts / Contact Management: Modules/CRM (create Contacts model, repository, Livewire CRUD inside `Modules/CRM`)
- Lead Management: Modules/CRM (Leads entity, assignment logic, conversion flows)
- Pipeline Management: Modules/CRM (Pipeline stages, deals table, UI components)
- Communication & Call Management: Modules/CRM + existing Communication/Calls subsystem (implement call logging, click-to-call integration; reuse broadcasting and Reverb)
- Dynamic Form Builder: Modules/CRM or new submodule `Modules/CRM/FormBuilder` (store form configuration in DB, expose Livewire builder UI)
- Reports & Analytics: Reuse existing reporting utilities and add CRM-specific reports under `Modules/CRM/Reports`

Mapping notes:
- Do not create new top-level modules. Implement all CRM functionality inside existing modules such as `Modules/CRM`, `Modules/AccessControl`, and `Modules/OrganisationSetup` following the project's module conventions.
- Reuse existing Livewire components, Flux UI patterns, Reverb/Echo broadcasting, and the project's service providers. Where possible, add features as subfolders or classes within existing modules instead of new modules.

Constraints & Principles
------------------------
- Do not change overall architecture or global providers; implement within `Modules/` following existing patterns.
- Use Eloquent models, factories, and migrations; create Form Request classes for validation.
- Implement UI using Livewire 4 and Flux UI Free components (follow `resources/` and `app/Livewire` patterns).
- Follow testing rules: add Pest feature tests and run `php artisan test --compact` for changed areas.
- Run code style with `vendor/bin/pint` before finalizing changes.

Constraint reminder:
- All CLI and dev commands must follow `README.md` and `docs/*` (Docker compose dev flow, `artisan` wrapper). Do not introduce ad-hoc scripts or alter bootstrap providers.

Module policy:
- **Mandatory core modules**: implement `Base CRM` (contacts, leads, pipeline, basic call logging) and `Form Builder` in the central codebase under `Modules/CRM` (or existing modules). These features must be present in every installation.
- **Optional client modules**: specialized workflows (for example a bespoke `Sales` module) should be implemented as separate modules under `Modules/` and delivered per-client on request. Optional modules must follow repo conventions and must not change global providers or application bootstrap.

Initial Step-by-Step Plan (short)
---------------------------------
1. Create feature branch (done) and confirm work branch.
2. Create backlog: user stories + acceptance criteria (next).
3. Scaffold migrations, models, factories for Contacts and Leads.
4. Implement basic Livewire CRUD for Contacts and Leads.
5. Add call logging model and simple click-to-call action (integrate with existing Reverb/Echo setup).
6. Build FormBuilder scaffolding to persist form JSON and render forms in modules.
7. Write Pest tests for created features and run targeted tests.
8. Update docs/ with setup and usage notes.

Helpful Commands (follow README/docs)
-----------------------------------
Use the repository's Docker-based dev flow or artisan wrapper as appropriate. Typical commands:

docker compose -f docker-compose.dev.yml up -d --build
docker exec -it wevetel_app_dev php artisan migrate
docker exec -it wevetel_app_dev php artisan make:model --migration --factory Modules/CRM/Contact
docker exec -it wevetel_app_dev vendor/bin/pint
docker exec -it wevetel_app_dev php artisan test --compact --filter=Contact

Next Action
-----------
Implementation status (current)
-------------------------------
- Contacts CRUD implemented with Livewire pages and module routes.
- Leads CRUD implemented with Livewire pages and module routes.
- Form Builder skeleton implemented with `crm_forms` migration, model, and Livewire index page.
- Sidebar navigation for CRM (`Contacts`, `Leads`, `Forms`) is guarded by `crm.view`.
- CRM migrations were aligned and de-duplicated to follow repo migration flow.
- Automated CRM tests pass in containerized environment.

Verification command
--------------------

```bash
docker exec -i wevetel_app_dev php artisan test --compact tests/Feature/Crm/
```
