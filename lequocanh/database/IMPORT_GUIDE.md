# Ward/Commune Data Import - Execution Guide

## 📋 Overview

This guide explains how to import 3,325 ward/commune records from the CSV file into the database for Phase 1 of the shipping system.

## 🔧 Prerequisites

- PHP 7.4+ installed
- MySQL database `trainingdb` accessible
- CSV file: `d:\PHP_WS\Danh-muc-Phuong-xa_moi.csv`
- Tables created from `shipping_system_schema.sql`

## 📁 Files Created

| File | Purpose |
|------|---------|
| `check_db.php` | Quick database connectivity check |
| `analyze_csv_data.php` | Analyze CSV structure and create mappings |
| `import_provinces_districts.php` | Import provinces and districts first |
| `import_wards_from_csv.php` | Main ward import script |
| `validate_ward_import.php` | Validation and reporting |

## 🚀 Execution Steps

### Step 1: Verify Database Connection

```bash
php d:\PHP_WS\lequocanh\database\check_db.php
```

**Expected Output:**
```
Database connection: OK
Table provinces: EXISTS (X records)
Table districts: EXISTS (X records)
Table wards: EXISTS (X records)
```

---

### Step 2: Analyze CSV Data (Optional)

```bash
php d:\PHP_WS\lequocanh\database\analyze_csv_data.php
```

This creates `csv_analysis.json` with statistics about the CSV data.

---

### Step 3: Import Provinces & Districts

**Dry Run (Preview):**
```bash
php d:\PHP_WS\lequocanh\database\import_provinces_districts.php --dry-run
```

**Execute Import:**
```bash
php d:\PHP_WS\lequocanh\database\import_provinces_districts.php --execute
```

**What it does:**
- Extracts unique provinces from CSV
- Extracts unique districts from CSV
- Checks for existing records (no duplicates)
- Creates mapping file `mapping.json` for ward import
- Uses transactions (can rollback on error)

**Expected Output:**
```
[timestamp] [INFO] === Province/District Import Started ===
[timestamp] [INFO] Mode: EXECUTE
...
[timestamp] [INFO] Found XX unique provinces
[timestamp] [INFO] Found XXX unique districts
[timestamp] [INFO] Provinces - New: XX, Existing: X
[timestamp] [INFO] Districts - New: XXX, Existing: X
[timestamp] [INFO] === Import Completed Successfully ===
```

---

### Step 4: Import Wards/Communes

**Dry Run (Preview):**
```bash
php d:\PHP_WS\lequocanh\database\import_wards_from_csv.php --dry-run
```

**Execute Import:**
```bash
php d:\PHP_WS\lequocanh\database\import_wards_from_csv.php --execute
```

**What it does:**
- Reads all 3,325+ ward records from CSV
- Maps districts using `mapping.json`
- Imports in batches of 100 (for performance)
- Updates existing records or inserts new ones
- Logs special notes/warnings from CSV
- Shows progress percentage

**Expected Output:**
```
[timestamp] [INFO] === Ward/Commune Import Started ===
[timestamp] [INFO] Batch size: 100
[timestamp] [INFO] Loaded mapping for XXX districts
[timestamp] [INFO] Total wards parsed: 3325
[timestamp] [INFO] Batch 1/34 completed (3%) - Inserted: 100, Updated: 0
[timestamp] [INFO] Batch 2/34 completed (6%) - Inserted: 200, Updated: 0
...
[timestamp] [INFO] === Import Summary ===
[timestamp] [INFO] Total wards in CSV: 3325
[timestamp] [INFO] Inserted: 3325
[timestamp] [INFO] Updated: 0
[timestamp] [INFO] === Import Completed Successfully ===
```

---

### Step 5: Validate Import & Generate Report

```bash
php d:\PHP_WS\lequocanh\database\validate_ward_import.php
```

**What it does:**
- Counts total provinces, districts, wards
- Checks for orphaned wards (should be 0)
- Checks for duplicate codes
- Shows top 10 provinces by ward count
- Generates HTML report: `ward_import_report.html`

**Expected Output:**
```
=== Ward Import Validation ===

Total Counts:
  Provinces: XX
  Districts: XXX
  Wards: 3325

Data Integrity:
  Orphaned wards (no district): 0
  Duplicate ward codes: 0

Top 10 Provinces by Ward Count:
  Hà Nội                            : 127 wards
  Hồ Chí Minh                       : XXX wards
  ...

=== Validation Completed ===
HTML report generated: ward_import_report.html
```

**View Report:**
Open `d:\PHP_WS\lequocanh\database\ward_import_report.html` in your browser.

---

## 📊 Verification Queries

After import, run these SQL queries to verify:

```sql
-- Check total counts
SELECT COUNT(*) as total_wards FROM wards;
SELECT COUNT(*) as total_districts FROM districts;
SELECT COUNT(*) as total_provinces FROM provinces;

-- Verify Hanoi data (should have ~127 wards)
SELECT w.code, w.name, d.name as district_name, p.name as province_name
FROM wards w
JOIN districts d ON w.district_id = d.id
JOIN provinces p ON d.province_id = p.id  
WHERE p.code = '101' OR p.name LIKE '%Hà Nội%'
LIMIT 20;

-- Check for orphaned wards (should be 0)
SELECT COUNT(*) as orphaned_wards 
FROM wards w
LEFT JOIN districts d ON w.district_id = d.id
WHERE d.id IS NULL;

-- Verify specific ward from CSV (line 4: "Phường Hoàn Kiếm")
SELECT w.code, w.name, d.name as district, p.name as province
FROM wards w
JOIN districts d ON w.district_id = d.id
JOIN provinces p ON d.province_id = p.id
WHERE w.code = '10105001';
-- Expected: code=10105001, name="Phường Hoàn Kiếm", 
--           district="Quận Hoàn Kiếm", province="Hà Nội"
```

---

## 🐛 Troubleshooting

### Issue: "Mapping file not found"
**Solution:** Run Step 3 (`import_provinces_districts.php`) first to create `mapping.json`

### Issue: "District mapping not found for TMS code: XXXXX"
**Solution:** This district is missing from the database. Check if CSV has valid district codes.

### Issue: "Transaction rolled back"
**Solution:** Check `import.log` for error details. Database remains unchanged (safe rollback).

### Issue: Orphaned wards found
**Solution:** Re-run province/district import to ensure all districts exist.

---

## 📝 Log Files

All scripts generate logs in:
```
d:\PHP_WS\lequocanh\database\import.log
```

Review this file for detailed information about what was imported/skipped.

---

## ✅ Success Criteria

After successful import:
- ✅ Total wards: ~3,325 (matching CSV row count - 3 header rows)
- ✅ Orphaned wards: 0
- ✅ Duplicate codes: 0
- ✅ All provinces have wards assigned
- ✅ HTML report shows no errors
- ✅ Sample queries return expected data

---

## 🔄 Re-running Import

The scripts are **idempotent** - safe to run multiple times:
- Existing records will be **updated** (not duplicated)
- New records will be **inserted**
- Transactions ensure data consistency

To **re-import from scratch**:
```sql
-- WARNING: This deletes all data!
TRUNCATE TABLE wards;
TRUNCATE TABLE districts;
TRUNCATE TABLE provinces;
```

Then run Steps 3-5 again.

---

## 📞 Support

If you encounter issues:
1. Check `import.log` for detailed errors
2. Verify database tables exist
3. Ensure CSV file encoding is UTF-8
4. Check PHP version (7.4+)
5. Verify MySQL credentials in each script
