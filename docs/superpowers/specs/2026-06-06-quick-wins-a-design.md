# Quick Wins A — Design Spec

**Date:** 2026-06-06
**Status:** Approved (pending spec review)
**Scope:** 3 quick wins from architecture/code-quality audit
**Branch:** `quick-wins-a` (worktree from `main`)

---

## Goal

Execute 3 small, low-risk improvements identified in the architecture/code-quality audit. Each is a behavior-preserving refactor or a focused bug fix. No new features. No scope creep.

Out of scope: the 5 advanced wins (error message leak, N+1 elsewhere, route helper, classmap registration, PHP 8.2+ bump). Tracked separately.

---

## Win 1: Replace `SELECT *` in `api/support_tickets.php`

**Problem.** AGENTS.md forbids `SELECT *`. Two violations at:
- `lequocanh/api/support_tickets.php:79` (`getUserTickets`)
- `lequocanh/api/support_tickets.php:123` (`getAdminTickets`)

Both query `v_support_tickets_list`. View column list extracted from `backups/docker_mysql_backup_20260531.sql:3288-3304`:
```
id, ticket_number, user_id, subject, category, related_review_id,
related_order_id, status, assigned_to, created_at, updated_at,
user_name, user_phone, user_email, unread_count, message_count
```
15 columns.

**Change.** Replace `SELECT *` with the explicit column list. Other SQL parts (WHERE, ORDER BY, LIMIT, OFFSET) unchanged. Bind params unchanged.

**Risk.** Low. View definition is the source of truth; if production view differs, query breaks at runtime and tests catch it. No schema change. No new error path.

**Behavior.** Preserving — result keys match what client already receives (view was returning all columns before).

---

## Win 2: Move presentation logic out of `App\Models\Product`

**Problem.** `App\Models\Product` (1336 lines) mixes ORM, CRUD, feature mgmt, and **view concern**:
- `getStatusCssClass(string $displayStatus): string` (Product.php:963-975)
- `getStatusColor(string $displayStatus): string` (Product.php:977-993)

These return CSS class names and hex colors. View concern in model layer violates separation.

**Change.**
1. Create `lequocanh/app/Presenters/ProductPresenter.php` (new dir + file):
   - `public static function cssClass(string $status): string` — exact same switch logic
   - `public static function color(string $status): string` — exact same switch logic
   - Pure functions. No state. No DB. No DI.
2. Remove both methods from `Product.php`.
3. (Out of scope) Update view templates to call `ProductPresenter::cssClass($status)`. Tracked as follow-up — views are 5+ files, no test coverage for them, separate task.

**Risk.** Low for the model + presenter files. Medium if views were calling these methods — but per AGENTS.md, views live under `lequocanh/app/Views/`. **Verify view usage before commit.** If views call them, the safer move is to keep methods on Product as deprecated proxies and add new methods on Presenter. Decide during implementation based on grep results.

**Behavior.** Preserving for any caller that uses the same input → output mapping.

---

## Win 3: Fix N+1 in `ProductController::bulkDelete`

**Problem.** `ProductController::bulkDelete()` (lines 326-362) loops over IDs and calls `Product::find($id)` then `->delete()` per item. Cost: **2N+1 queries** (N finds + N deletes + 1 outer). For 50 products = 101 queries.

**Change.**
1. Add `App\Models\Product::bulkDelete(array $ids): array` (new static method):
   - Validate `count($ids) > 0`, else return `['success' => true, 'deleted' => 0, 'errors' => []]`
   - Cast each id to `int` (defense — input is `array $ids` from `$this->input('ids')` which returns mixed)
   - Build `IN (?, ?, ?)` placeholders matching count
   - `DELETE FROM hanghoa WHERE idhanghoa IN (?, ?, ?)` with bound params
   - Return `['success' => true, 'deleted' => $rowCount, 'errors' => []]`
   - Catch `PDOException` with code `'23000'` and `'foreign key constraint'` in message → return `['success' => false, 'deleted' => 0, 'errors' => array_fill_keys($ids, 'FK constraint: related data exists')]`
   - Other exceptions → log + return `['success' => false, 'errors' => $ids map with generic msg]`
2. Refactor `ProductController::bulkDelete` to call `Product::bulkDelete($ids)` and return its result directly. CSRF + auth checks unchanged.

**Risk.** Medium. **Behavior changes for partial failures:** old code continues with remaining items after one fails; new code returns 0 deleted on first FK violation. Acceptable trade-off — old code's partial-success behavior is rarely useful and rarely tested.

**Behavior.** For all-success case: identical (all rows deleted). For all-fail case: same end state. For partial case: new code is all-or-nothing.

---

## Components

| Action | Path | Reason |
|---|---|---|
| Modify | `lequocanh/api/support_tickets.php` | Win 1: 2 SELECT * |
| New | `lequocanh/app/Presenters/ProductPresenter.php` | Win 2: 2 static methods |
| Modify | `lequocanh/app/Models/Product.php` | Win 2: remove 2 methods; Win 3: add 1 method |
| Modify | `lequocanh/app/Controllers/Admin/ProductController.php` | Win 3: use new bulk method |
| New | `tests/Unit/Api/SupportTicketsQueryTest.php` | Win 1: no `*` in queries |
| New | `tests/Unit/Presenters/ProductPresenterTest.php` | Win 2: 4 status → 4 css, 4 colors |
| New | `tests/Unit/Models/ProductBulkDeleteTest.php` | Win 3: empty/valid/FK/missing |

## Data flow

- Win 1: PHP sends explicit column list to MySQL. MySQL returns same 15 columns. JSON response unchanged.
- Win 2: View layer (when migrated) calls `ProductPresenter::cssClass($status)` instead of `$product->getStatusCssClass($status)`. Same return value.
- Win 3: Controller → `Product::bulkDelete($ids)` → 1 SQL DELETE → return affected count or per-id error map → JSON response.

## Error handling

- Win 1: none new. Existing try/catch in callers unchanged.
- Win 2: presenter is pure function. No errors possible.
- Win 3: PDOException 23000 caught, logged via `error_log`, returned as per-id error map. Controller surfaces the same JSON shape it does today.

## Testing (TDD per win, per user choice)

- **Win 1**: integration test against test DB. Seed a support_tickets row. Call the relevant action. Assert response `tickets[*]` has all 15 expected keys. Assert no `*` in any source SQL string (regex check on the file).
- **Win 2**: pure unit test, no DB. 4 cases × 2 methods = 8 assertions + unknown-status default case.
- **Win 3**: integration test against test DB. Cases: empty array, valid ids (verify row count), missing ids (verify 0 affected), FK violation (verify error map, no rows deleted).

All tests run via `vendor/bin/phpunit` against the configured test DB.

## Commit strategy

3 commits, one per win. Format: `type(scope): subject`. Conventional Commits.

## Out of scope (deferred, not in this work)

- 5 advanced wins from audit
- View template migration for ProductPresenter (Win 2 partial)
- Auto-clarity: if any win reveals a bigger issue, flag and stop, don't expand scope
