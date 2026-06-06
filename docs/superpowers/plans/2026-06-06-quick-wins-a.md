# Quick Wins A — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Execute 3 quick wins from code-quality audit: replace `SELECT *` violations, move presentation logic out of model, fix N+1 in `bulkDelete`.

**Architecture:** Pure refactors / focused fixes. No new features. No new dependencies. All work in worktree `quick-wins-a` branched from `main`. 3 commits, one per win.

**Tech Stack:** PHP 7.4+, PHPUnit 11, SQLite in-memory mock DB (per `tests/mocks/Database.php`), PSR-12.

**Spec:** `docs/superpowers/specs/2026-06-06-quick-wins-a-design.md`

**Note for implementer:** During planning, discovered that `Product::delete()` (lines 1307-1318) already performs `checkRelatedData` + `deleteInventory` before the SQL delete. The spec describes Win 3 as "1 IN-clause query"; the actual implementation needs **2 IN-clause queries in a transaction** (tonkho cleanup + hanghoa delete) to preserve existing per-id FK-check semantics. See Task 4 for the correct design.

---

## Task 0: Setup worktree

**Files:**
- Branch: `.worktrees/quick-wins-a/` (new worktree)

- [ ] **Step 1: Verify clean main + worktrees dir**

```bash
cd D:/PHP_WS
git status
ls .worktrees/
```

Expected: clean working tree on `main` (the spec commit may be staged/committed), `.worktrees/` exists.

- [ ] **Step 2: Create worktree**

```bash
cd D:/PHP_WS
git worktree add .worktrees/quick-wins-a -b quick-wins-a main
```

Expected: worktree created, branch `quick-wins-a` checked out at `.worktrees/quick-wins-a/`.

- [ ] **Step 3: Verify spec file is in worktree**

```bash
ls .worktrees/quick-wins-a/docs/superpowers/specs/2026-06-06-quick-wins-a-design.md
```

Expected: file exists (worktree shares the same git history, spec commit is in `main`).

- [ ] **Step 4: Verify composer install state**

```bash
cd .worktrees/quick-wins-a
ls vendor/bin/phpunit 2>&1
```

Expected: `vendor/bin/phpunit` exists. If not, run `composer install --prefer-dist --no-progress`.

- [ ] **Step 5: Run existing test suite to establish baseline**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit --testsuite Unit 2>&1 | tail -10
```

Expected: all existing tests pass (baseline to compare against after each win).

- [ ] **Step 6: Set worktree as working dir for all following tasks**

All subsequent `cd` commands assume `.worktrees/quick-wins-a` as the working dir.

---

## Task 1: Win 1 — Replace `SELECT *` in `api/support_tickets.php`

**Files:**
- Modify: `lequocanh/api/support_tickets.php:79` (in `getUserTickets`)
- Modify: `lequocanh/api/support_tickets.php:123` (in `getAdminTickets`)
- Test: `tests/Unit/Api/SupportTicketsQueryTest.php` (new)

**Reference:** Spec §Win 1. View columns (15 total):
`id, ticket_number, user_id, subject, category, related_review_id, related_order_id, status, assigned_to, created_at, updated_at, user_name, user_phone, user_email, unread_count, message_count`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Api/SupportTicketsQueryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;

class SupportTicketsQueryTest extends TestCase
{
    private const EXPECTED_COLUMNS = [
        'id', 'ticket_number', 'user_id', 'subject', 'category',
        'related_review_id', 'related_order_id', 'status', 'assigned_to',
        'created_at', 'updated_at', 'user_name', 'user_phone', 'user_email',
        'unread_count', 'message_count',
    ];

    public function testSourceFileContainsNoSelectStar(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../lequocanh/api/support_tickets.php');
        $this->assertNotFalse($source, 'support_tickets.php must be readable');

        // Strip comments to avoid false positives
        $code = preg_replace('!/\*.*?\*/!s', '', $source);
        $code = preg_replace('![ \t]*//.*?$!m', '', $code);

        // Look for SELECT * (with optional whitespace) in SQL context
        $this->assertDoesNotMatchRegularExpression(
            '/SELECT\s+\*\s+FROM/i',
            $code,
            'support_tickets.php must not contain SELECT * FROM (AGENTS.md violation)'
        );
    }

    public function testViewColumnsAreExplicitInBothQueries(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../lequocanh/api/support_tickets.php');
        $this->assertNotFalse($source);

        $this->assertStringContainsString('SELECT id, ticket_number, user_id, subject, category', $source);
        $this->assertStringContainsString('unread_count, message_count FROM v_support_tickets_list', $source);
    }

    public function testExpectedColumnsList(): void
    {
        $this->assertCount(15, self::EXPECTED_COLUMNS);
        $this->assertContains('id', self::EXPECTED_COLUMNS);
        $this->assertContains('message_count', self::EXPECTED_COLUMNS);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit tests/Unit/Api/SupportTicketsQueryTest.php
```

Expected: FAIL on `testSourceFileContainsNoSelectStar` — current file contains `SELECT * FROM v_support_tickets_list`.

- [ ] **Step 3: Edit `lequocanh/api/support_tickets.php` line 79**

Replace:
```php
            $sql = "SELECT * FROM v_support_tickets_list
                    WHERE user_id = ?
                    ORDER BY updated_at DESC
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
```

With:
```php
            $sql = "SELECT id, ticket_number, user_id, subject, category, related_review_id, related_order_id, status, assigned_to, created_at, updated_at, user_name, user_phone, user_email, unread_count, message_count FROM v_support_tickets_list
                    WHERE user_id = ?
                    ORDER BY updated_at DESC
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
```

- [ ] **Step 4: Edit `lequocanh/api/support_tickets.php` line 123**

Replace:
```php
            $sql = "SELECT * FROM v_support_tickets_list
                    {$where}
                    ORDER BY updated_at DESC
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
```

With:
```php
            $sql = "SELECT id, ticket_number, user_id, subject, category, related_review_id, related_order_id, status, assigned_to, created_at, updated_at, user_name, user_phone, user_email, unread_count, message_count FROM v_support_tickets_list
                    {$where}
                    ORDER BY updated_at DESC
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit tests/Unit/Api/SupportTicketsQueryTest.php
```

Expected: PASS (3 tests).

- [ ] **Step 6: Run full test suite to ensure no regression**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit --testsuite Unit
```

Expected: all tests pass (existing 14+ plus the 3 new = 17+).

- [ ] **Step 7: Commit**

```bash
cd .worktrees/quick-wins-a
git add lequocanh/api/support_tickets.php tests/Unit/Api/SupportTicketsQueryTest.php
git commit -m "fix(api): replace SELECT * with explicit columns in support_tickets

AGENTS.md forbids SELECT * in queries. Replaced both occurrences in
api/support_tickets.php with the 15-column list from v_support_tickets_list
(verified against backups/docker_mysql_backup_20260531.sql:3288-3304).

No behavior change — view was returning all 15 columns before."
```

---

## Task 2: Win 2 — Create `App\Presenters\ProductPresenter` (TDD, test first)

**Files:**
- Create: `lequocanh/app/Presenters/ProductPresenter.php` (new file, new dir)
- Test: `tests/Unit/Presenters/ProductPresenterTest.php` (new)

**Reference:** Spec §Win 2. Pure functions, no state, no DB. Status input strings (Vietnamese) → CSS class / hex color.

- [ ] **Step 1: Verify view usage of `getStatusCssClass` / `getStatusColor`**

```bash
cd .worktrees/quick-wins-a
grep -rn "getStatusCssClass\|getStatusColor" lequocanh/app/Views/ 2>&1
```

Expected: list of view files that call these methods. **Save this output** — Step 5 needs it.

If output is empty: views do NOT call these methods. Safe to delete from `Product.php` (Step 5 below).

If output is non-empty: skip the deletion in Step 5; keep methods on Product as deprecated proxies (move to Win 2.5 follow-up). Stop and ask user.

- [ ] **Step 2: Write the failing test**

Create `tests/Unit/Presenters/ProductPresenterTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Presenters;

use PHPUnit\Framework\TestCase;
use App\Presenters\ProductPresenter;

class ProductPresenterTest extends TestCase
{
    // ─── cssClass ───────────────────────────────────────────────

    public function testCssClassActive(): void
    {
        $this->assertSame('status-active', ProductPresenter::cssClass('Đang bán'));
    }

    public function testCssClassDiscontinued(): void
    {
        $this->assertSame('status-discontinued', ProductPresenter::cssClass('Ngừng bán'));
    }

    public function testCssClassOutOfStock(): void
    {
        $this->assertSame('status-outofstock', ProductPresenter::cssClass('Hết hàng'));
    }

    public function testCssClassUnknownDefaultsToUnknown(): void
    {
        $this->assertSame('status-unknown', ProductPresenter::cssClass('Some Other Status'));
        $this->assertSame('status-unknown', ProductPresenter::cssClass(''));
    }

    // ─── color ──────────────────────────────────────────────────

    public function testColorActive(): void
    {
        $this->assertSame('#27ae60', ProductPresenter::color('Đang bán'));
    }

    public function testColorDiscontinued(): void
    {
        $this->assertSame('#e74c3c', ProductPresenter::color('Ngừng bán'));
    }

    public function testColorOutOfStock(): void
    {
        $this->assertSame('#95a5a6', ProductPresenter::color('Hết hàng'));
    }

    public function testColorUnknownDefaultsToDark(): void
    {
        $this->assertSame('#34495e', ProductPresenter::color('Some Other Status'));
        $this->assertSame('#34495e', ProductPresenter::color(''));
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit tests/Unit/Presenters/ProductPresenterTest.php
```

Expected: FAIL — class `App\Presenters\ProductPresenter` does not exist (autoloader can't find it).

- [ ] **Step 4: Create the presenter**

Create `lequocanh/app/Presenters/ProductPresenter.php`:

```php
<?php

declare(strict_types=1);

namespace App\Presenters;

/**
 * Pure view-layer formatting for Product status.
 *
 * Status strings are the display values produced by Product::getProductStatus()
 * (Vietnamese). This class is pure: no state, no DB, no DI. Safe to call from
 * any view layer.
 */
class ProductPresenter
{
    /**
     * Map a display status string to a CSS class name.
     */
    public static function cssClass(string $displayStatus): string
    {
        return match ($displayStatus) {
            'Đang bán' => 'status-active',
            'Ngừng bán' => 'status-discontinued',
            'Hết hàng' => 'status-outofstock',
            default => 'status-unknown',
        };
    }

    /**
     * Map a display status string to a hex color.
     */
    public static function color(string $displayStatus): string
    {
        return match ($displayStatus) {
            'Đang bán' => '#27ae60',
            'Ngừng bán' => '#e74c3c',
            'Hết hàng' => '#95a5a6',
            default => '#34495e',
        };
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit tests/Unit/Presenters/ProductPresenterTest.php
```

Expected: PASS (8 tests).

- [ ] **Step 6: Decide what to do with old methods in `Product.php`**

Re-check Step 1 output. If views use `getStatusCssClass`/`getStatusColor`:
- **Skip this step** — keep methods on Product, file a follow-up to migrate views

If views do NOT use them:
- Continue to Step 7 (remove from Product)

- [ ] **Step 7: Remove the two methods from `Product.php` (only if Step 6 says safe)**

Edit `lequocanh/app/Models/Product.php`:
- Delete lines 959-994 (the two methods + their docblock comments)
- The block to delete starts at the `/**` before `Get status CSS class.` and ends at the closing `}` of `getStatusColor`

- [ ] **Step 8: Run full test suite**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit --testsuite Unit
```

Expected: all tests pass (25+ now).

- [ ] **Step 9: Commit**

```bash
cd .worktrees/quick-wins-a
git add lequocanh/app/Presenters/ProductPresenter.php tests/Unit/Presenters/ProductPresenterTest.php
git add lequocanh/app/Models/Product.php 2>/dev/null  # only if Step 7 ran
git commit -m "refactor(product): move status CSS/color to App\\Presenters\\ProductPresenter

getStatusCssClass() and getStatusColor() are view concerns (return CSS
class names and hex colors) — they did not belong in App\\Models\\Product.

Moved to new App\\Presenters\\ProductPresenter with the same mapping
verified by unit tests. Pure functions: no state, no DB.

[If Step 7 ran, append:]
Also removed the two methods from App\\Models\\Product. Grep confirmed
no view template references them. View migration is a follow-up task."
```

---

## Task 3: Win 3 — Add `Product::bulkDelete()` and refactor controller (TDD, test first)

**Files:**
- Modify: `lequocanh/app/Models/Product.php` (add static method `bulkDelete`)
- Modify: `lequocanh/app/Controllers/Admin/ProductController.php:326-362` (use new method)
- Test: `tests/Unit/Models/ProductBulkDeleteTest.php` (new)

**Reference:** Spec §Win 3 (corrected during planning).

**Behavior contract (corrected from spec):**
- Input: `array $ids`
- Output: `['success' => bool, 'deleted' => int, 'errors' => array<int, string>]`
- For each id: check related data (FK check via existing `checkRelatedData()`). If related data exists, add to errors. If safe, add to safe list.
- If safe list is non-empty: in 1 transaction, delete from `tonkho` (1 query) and `hanghoa` (1 query) using IN-clauses.
- FK violation during SQL (race condition or missed check): catch PDOException, rollback, return generic error map.

This preserves the old code's per-id error granularity while reducing queries from `2N+1` to `2` (or `2+1` for the related-data precheck).

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Models/ProductBulkDeleteTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Product;
use Database;

class ProductBulkDeleteTest extends TestCase
{
    private \PDO $db;

    protected function setUp(): void
    {
        // Reset mock DB to a fresh in-memory SQLite
        $this->db = Database::getInstance()->getConnection();
        $this->db->exec('PRAGMA foreign_keys = ON');

        // Create minimal schema for tests
        $this->db->exec('DROP TABLE IF EXISTS hanghoa');
        $this->db->exec('DROP TABLE IF EXISTS tonkho');
        $this->db->exec('DROP TABLE IF EXISTS chitietgiohang');
        $this->db->exec('CREATE TABLE hanghoa (idhanghoa INTEGER PRIMARY KEY, tenhanghoa TEXT)');
        $this->db->exec('CREATE TABLE tonkho (id INTEGER PRIMARY KEY AUTOINCREMENT, idhanghoa INTEGER, soLuong INTEGER DEFAULT 0)');
        $this->db->exec('CREATE TABLE chitietgiohang (id INTEGER PRIMARY KEY AUTOINCREMENT, idhanghoa INTEGER)');
    }

    public function testBulkDeleteMethodExists(): void
    {
        $this->assertTrue(
            method_exists(Product::class, 'bulkDelete'),
            'Product::bulkDelete must be defined'
        );
    }

    public function testBulkDeleteEmptyArrayReturnsNoOp(): void
    {
        $result = Product::bulkDelete([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['deleted']);
        $this->assertSame([], $result['errors']);
    }

    public function testBulkDeleteValidIdsDeletesRows(): void
    {
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (1, 'A')");
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (2, 'B')");
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (3, 'C')");

        $result = Product::bulkDelete([1, 2]);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['deleted']);
        $this->assertSame([], $result['errors']);

        $remaining = $this->db->query('SELECT idhanghoa FROM hanghoa ORDER BY idhanghoa')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame([3], $remaining);
    }

    public function testBulkDeleteMissingIdsReturnsZeroDeleted(): void
    {
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (1, 'A')");

        $result = Product::bulkDelete([99, 100]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['deleted']);
        $this->assertSame([], $result['errors']);
    }

    public function testBulkDeleteRelatedDataBlocksThatId(): void
    {
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (1, 'A')");
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (2, 'B')");
        $this->db->exec("INSERT INTO chitietgiohang (idhanghoa) VALUES (1)");

        $result = Product::bulkDelete([1, 2]);

        // id=1 blocked (has cart items), id=2 deleted
        $this->assertIsArray($result);
        $this->assertSame(1, $result['deleted']);
        $this->assertArrayHasKey(1, $result['errors']);
        $this->assertStringContainsString('Related data', $result['errors'][1]);

        $remaining = $this->db->query('SELECT idhanghoa FROM hanghoa ORDER BY idhanghoa')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame([1], $remaining);
    }

    public function testBulkDeleteAllBlockedReturnsFailure(): void
    {
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (1, 'A')");
        $this->db->exec("INSERT INTO chitietgiohang (idhanghoa) VALUES (1)");

        $result = Product::bulkDelete([1]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['deleted']);
        $this->assertArrayHasKey(1, $result['errors']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit tests/Unit/Models/ProductBulkDeleteTest.php
```

Expected: FAIL — `Product::bulkDelete` does not exist.

- [ ] **Step 3: Add `bulkDelete` static method to `Product.php`**

Add to `lequocanh/app/Models/Product.php` (insert near the existing `delete()` method, around line 1307 — or anywhere in the class body; recommend placing it right after `delete()`):

```php
    /**
     * Bulk-delete products by ID. Respects per-id FK check via checkRelatedData().
     *
     * Returns ['success' => bool, 'deleted' => int, 'errors' => array<int, string>].
     * IDs in `errors` were not deleted (related data or DB error). Other IDs were.
     *
     * @param array<int|string> $ids
     * @return array{success: bool, deleted: int, errors: array<int, string>}
     */
    public static function bulkDelete(array $ids): array
    {
        if (empty($ids)) {
            return ['success' => true, 'deleted' => 0, 'errors' => []];
        }

        $errors = [];
        $safeIds = [];

        foreach ($ids as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }
            $related = self::checkRelatedData($id);
            if (!empty($related)) {
                $errors[$id] = 'Related data exists: ' . implode(', ', array_keys($related));
            } else {
                $safeIds[] = $id;
            }
        }

        if (empty($safeIds)) {
            return ['success' => false, 'deleted' => 0, 'errors' => $errors];
        }

        $placeholders = implode(',', array_fill(0, count($safeIds), '?'));
        $db = self::db();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("DELETE FROM tonkho WHERE idhanghoa IN ($placeholders)");
            $stmt->execute($safeIds);

            $stmt = $db->prepare("DELETE FROM hanghoa WHERE idhanghoa IN ($placeholders)");
            $stmt->execute($safeIds);
            $deleted = $stmt->rowCount();

            $db->commit();
            return ['success' => true, 'deleted' => $deleted, 'errors' => $errors];
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Product::bulkDelete error: ' . $e->getMessage());
            return [
                'success' => false,
                'deleted' => 0,
                'errors' => array_merge(
                    $errors,
                    array_fill_keys($safeIds, 'DB error: ' . $e->getMessage())
                ),
            ];
        }
    }
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit tests/Unit/Models/ProductBulkDeleteTest.php
```

Expected: PASS (6 tests).

- [ ] **Step 5: Refactor `ProductController::bulkDelete` to use the new method**

Edit `lequocanh/app/Controllers/Admin/ProductController.php`, replace lines 326-362 (the entire `bulkDelete` method body) with:

```php
    /**
     * Bulk delete products (AJAX).
     */
    public function bulkDelete(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $ids = $this->input('ids');
        if (empty($ids) || !is_array($ids)) {
            $this->json(['success' => false, 'message' => 'Product IDs required'], 400);
            return;
        }

        try {
            $result = Product::bulkDelete($ids);

            $this->json([
                'success' => $result['success'],
                'message' => $result['success']
                    ? "Deleted {$result['deleted']} products"
                    : 'Some products could not be deleted',
                'deleted' => $result['deleted'],
                'errors' => $result['errors'],
            ]);
        } catch (Exception $e) {
            error_log("ProductController::bulkDelete error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 6: Run full test suite**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit --testsuite Unit
```

Expected: all tests pass (31+ now).

- [ ] **Step 7: Run static analysis**

```bash
cd .worktrees/quick-wins-a
composer analyse 2>&1 | tail -20
```

Expected: 0 errors. If new errors appear, fix them before commit. Do not suppress with new `ignoreErrors` entries unless absolutely necessary — flag and ask.

- [ ] **Step 8: Commit**

```bash
cd .worktrees/quick-wins-a
git add lequocanh/app/Models/Product.php lequocanh/app/Controllers/Admin/ProductController.php tests/Unit/Models/ProductBulkDeleteTest.php
git commit -m "perf(product): fix N+1 in bulkDelete via single IN-clause query

Old: 2N+1 queries (N finds + N deletes). For 50 products = 101 queries.
New: 2 queries (tonkho + hanghoa) inside a transaction, plus per-id
FK check via existing checkRelatedData().

Preserves per-id error granularity: an id blocked by FK is reported in
the errors map, not silently skipped.

Behavior changes for partial-failure case: all-or-nothing within a
batch. The old per-item continuation was rarely useful and untested."
```

---

## Task 4: Final verification + handoff

**Files:** none (verification only)

- [ ] **Step 1: Run full test suite**

```bash
cd .worktrees/quick-wins-a
vendor/bin/phpunit --testsuite Unit 2>&1 | tail -10
```

Expected: all tests pass. Count should be baseline + 17 (3 win1 + 8 win2 + 6 win3).

- [ ] **Step 2: Run static analysis**

```bash
cd .worktrees/quick-wins-a
composer analyse 2>&1 | tail -10
```

Expected: 0 errors.

- [ ] **Step 3: Run code style check**

```bash
cd .worktrees/quick-wins-a
composer cs-check 2>&1 | tail -10
```

Expected: 0 violations on the new/modified files. Pre-existing violations are OK.

- [ ] **Step 4: Verify 3 commits in worktree**

```bash
cd .worktrees/quick-wins-a
git log --oneline main..HEAD
```

Expected: 3 commits, one per win, in order.

- [ ] **Step 5: Verify no `SELECT *` remains in `api/support_tickets.php`**

```bash
cd .worktrees/quick-wins-a
grep -n "SELECT \*" lequocanh/api/support_tickets.php
```

Expected: no output (or no SQL `SELECT *` matches).

- [ ] **Step 6: Report to user**

Show:
- Test results
- Static analysis result
- Code style result
- 3 commit subjects
- Path to worktree
- Diff stat: `git diff --stat main..HEAD`

Then ask user how to proceed (merge to main, push, PR, etc.).

---

## Self-review

**Spec coverage:**
- Win 1 (replace SELECT *) → Task 1 ✅
- Win 2 (move presentation) → Task 2 ✅
- Win 3 (fix N+1) → Task 3 (with corrected design noted in task header) ✅
- Final verification → Task 4 ✅

**Placeholder scan:** No "TBD", "TODO", "implement later". All code blocks complete.

**Type consistency:**
- `Product::bulkDelete(array $ids): array` referenced consistently across Task 3 steps.
- Return shape `['success' => bool, 'deleted' => int, 'errors' => array<int, string>]` consistent between test, impl, and controller refactor.
- `ProductPresenter::cssClass` and `::color` signatures consistent.

**Deviations from spec, noted transparently:**
- Task 3 implementation uses 2 IN-clause queries (tonkho + hanghoa) instead of 1, to respect existing `Product::delete()` semantics (FK check + inventory delete). Spec's "1 query" simplified this; the real implementation needs 2.
- Task 2 includes a `Step 1` view-usage check that wasn't in the spec — discovered during planning that views might still call the old methods, requiring a follow-up migration task if so.
