# Workers Survey Showcase

This repository contains an extracted **Survey module** from a larger HRM platform.

## HRM Platform Overview

The original HRM system is an internal workforce management platform used to support core people operations, including:

- employee lifecycle processes (onboarding, active work period, offboarding)
- organizational structure and assignment (users, workers, departments)
- periodic HR workflows and notifications
- reporting and exports for HR analytics

In that broader context, the Survey module is responsible for collecting structured employee feedback and delivering actionable HR insights.

## Survey Module Overview

The Survey module supports end-to-end survey lifecycle management for HR teams and employees.

### Functional capabilities

- create and update surveys through aggregate DTO/request flows
- manage survey templates and production surveys
- support multiple survey scenarios:
  - regular HR surveys
  - offboarding surveys
  - burnout surveys
  - impulse (pulse-like) surveys
- support question/answer modeling:
  - typed answers (text, numeric scales, selectable variants)
  - required questions
  - question variants and answer validation
  - page-based survey composition
- track worker assignments and completions
- calculate and expose survey statistics for API consumers
- export survey results for reporting workflows
- trigger reminders and automated survey-related cron processes

### Core services included

- `app/Services/Survey/SurveyVariantService.php`
- `app/Services/Survey/SurveyService.php`
- `app/Services/Survey/SurveyQuestionService.php`
- `app/Services/Survey/SurveyPageService.php`
- `app/Services/Survey/SurveyCompletionService.php`
- `app/Services/Survey/SurveyBurnoutService.php`
- `app/Services/Survey/SurveyAggregateService.php`
- `app/Services/Survey/ImpulseTemplateService.php`
- `app/Services/Survey/ImpulseService.php`

## Repository Structure

The extraction preserves the original layer-oriented structure for easier navigation:

- `app/Services/Survey` — business logic for survey flows
- `app/Http/Controllers/Api` — survey API entry points
- `app/Http/Requests/Survey` — request validation and constraints
- `app/Http/Resources/Survey` — API resource transformers
- `app/Models/Survey` — survey domain models and relations
- `app/Rules`, `app/Filters`, `app/Exceptions/Survey` — domain rules and validation behavior
- `app/Console/Commands` + `app/Console/Kernel.php` — survey-related scheduled automation
- `routes/api/surveys.php` (+ minimal route bootstrap fragment) — API routing
- `database/migrations`, `database/seeds/Survey`, `database/factories/Survey` — persistence layer and test data
- `tests/Feature/...Survey...` — feature and integration-oriented tests

## Notes

- This repository is intended as a **code showcase** of the Survey domain.
- It reflects survey-focused extraction from a larger HRM codebase.
- Some workflows (notifications, broader auth/role ecosystem, external integrations) depend on components that exist in the full platform.
