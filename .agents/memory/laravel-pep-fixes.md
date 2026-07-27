---
name: Laravel PEP app fixes
description: Pitfalls found and fixed during the PHP/Laravel rebuild of the PEP lost-found system (pep-laravel artifact).
---

## Route name mismatches (fixed)
- Dashboard view name: `view('dashboard')` → `view('dashboard.index')`
- Export Blade form action: `route('export.police.download')` → `route('export.police-csv')`
- Export controller view: `view('export.police-form')` → `view('exports.police-form')`
- Nav links: `route('export.police')` → `route('export.police-form')`
- LostReport show blade status forms: `route('lost-reports.status', ...)` → `route('lost-reports.update-status', ...)`
- LostReport show blade had a delete form for `lost-reports.destroy` which is excluded from resource routes — removed the button.

**Why:** Subagents that built controllers, routes and views in parallel used slightly inconsistent names. Always audit route names via `php artisan route:list` when writing Blade views.

## Models missing HasFactory (fixed)
- `FoundItem` and `LostReport` models did not have `use HasFactory` trait — `::factory()` calls failed.

**Why:** Subagents generating models omitted the trait. Always add `use Illuminate\Database\Eloquent\Factories\HasFactory;` and `use HasFactory;` to every model that has a factory.

## Unit tests extending wrong base class (fixed)
- `MatchingServiceTest`, `ExportServiceTest`, `ManagementNumberServiceTest` extended `PHPUnit\Framework\TestCase` but used Eloquent models. Eloquent's cast system requires the Laravel container (`config` class), which is only available when extending `Tests\TestCase`.
- Fix: change all Unit tests that touch Eloquent (even without DB calls) to extend `Tests\TestCase`.

**Why:** Eloquent casts (`'datetime'`) resolve the config service from the container on first access. Plain PHPUnit TestCase has no container.

## assertUnprocessable() PHPUnit 12 crash workaround
- `$response->assertUnprocessable()` and `assertStatus(422)` both crash with `Call to a member function all() on array` when the actual response is a JSON array and PHPUnit 12 tries to format the failure message.
- Workaround: `$this->assertEquals(422, $response->status())` — bypasses the framework formatting code.
- Better approach: test the `/api/...` endpoint instead of the web endpoint for JSON validation checks.

**Why:** PHPUnit 12 introduced stricter failure message formatting that conflicts with raw PHP array JSON bodies.

## Web vs API routes for JSON validation tests
- `PATCH /found-items/{id}/status` is a web route — when called without a proper session/CSRF token (even with `patchJson`), may redirect (302) instead of returning 422.
- Use `/api/found-items/{id}/status` in tests for JSON validation assertions.

## MatchingService API surface
- `computeScore(FoundItem $found, LostReport $lost)` — returns `['score' => int, 'reasons' => list<string>]`
- `findMatches(FoundItem $found, Collection $lostReports, int $minScore)` — matches for one found item
- `findMatchesForLostReport(LostReport $lost, Collection $foundItems, int $minScore)` — matches for one lost report
- `computeAllMatches(Collection $lostReports, Collection $foundItems, int $minScore)` — all pairs

**How to apply:** When calling from controllers or tests, use the correct method for the direction of the query.

## Test results baseline
82 tests, 264 assertions, all passing. SQLite in-memory via phpunit.xml.
