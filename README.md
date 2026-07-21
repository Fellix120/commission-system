# Commission Dashboard — Laravel 10

A Google Workspace–styled web app that replaces `Templete_for_Commission_Working.xlsx`.
Every tab in that workbook maps to a screen, and every formula maps to tested PHP.

---

## The four requirements

| # | Requirement (from the Summary tab) | Where it lives |
|---|---|---|
| 1 | Display as per student details | `/students` — all 27 columns, frozen header, sticky Name column |
| 2 | Student-wise intake commission received (T2-2025, T3-2025, T1-2026) | tinted columns on `/students`; driven by `student_intake_commissions` |
| 3 | Archive option for old settled students | Archive / Restore + bulk archive, with a settled-balance guard |
| 4 | Enrolment comparison against uni/college targets | `/targets` — target vs actual, variance, % achievement bars |

---

## Quick start

Runs on MySQL, so the database is visible in phpMyAdmin.

**1. Create the database.** In phpMyAdmin, click *New*, name it
`commission_workspace`, set collation to `utf8mb4_unicode_ci`, and hit *Create*.
Or from the SQL tab:

```sql
CREATE DATABASE commission_workspace
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**2. Set up the app.**

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve                   # http://localhost:8000
```

`--seed` loads the real sample rows from the workbook (8 raw, 39 CRM, 54 invoice),
then runs the value-paste refresh to build 37 student rows. Refresh phpMyAdmin and
the six tables are there.

**Credentials.** `.env` ships with the XAMPP/WAMP default (`root`, no password, port
3306). On MAMP use port `8889` and password `root`. If `127.0.0.1` is refused, try
`DB_HOST=localhost`.

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=commission_workspace
DB_USERNAME=root
DB_PASSWORD=
```

---

## Screens

| Route | Sheet it replaces |
|---|---|
| `/` | Dashboard (new) — totals, commission by intake, top partners |
| `/students` | **Student Details** |
| `/student-details-formula` | **Student Details-Formula** |
| `/students?archived=1` | Archived students |
| `/targets` | Target vs enrolment |
| `/invoices` | **Invoice** |
| `/crm-data` | **CRM Data** |
| `/raw-data` | **Raw Data Export** |
| `/import` | Upload a workbook, and the history of every run |
| `/export/students` | Download Student Details as CSV |

---

## How the formulas were ported

`app/Services/StudentDetailService.php` is a line-by-line port of the
*Student Details-Formula* sheet:

| Excel | Formula on the sheet | PHP |
|---|---|---|
| A–F, H | `XLOOKUP($G2,'CRM Data'!$G:$G, …)` | first matching CRM row |
| J | `TEXTJOIN(" + ", TRUE, FILTER(…))` | `joinedProductNames()` — repeats kept, see below |
| K | `MINIFS('CRM Data'!J:J, G:G, key)` | `min(start_date)` |
| L | `MAXIFS('CRM Data'!K:K, G:G, key)` | `max(end_date)` |
| M | `(End − Start)/365` | `courseDuration()` |
| N | `SUMIFS('CRM Data'!L:L, G:G, key)` | `sum(fee_total)` |
| O | CR fee | `creditFee()` — see below |
| Q/R | Paid fee / bonus from Invoice | `sum()` over invoice rows |
| S | Credit − Paid + Adjustment | `remainingFee()` |
| T | Bonus Due − Paid Bonus | `remainingBonus()` |
| Y/Z/AA | T2-2025, T3-2025, T1-2026 | `student_intake_commissions` rows |

The **Student Details-Formula** screen (`/student-details-formula`) reproduces that
companion tab: every formula quoted verbatim from the workbook next to the PHP that
replaced it and the method that runs. Pick a student and it resolves each formula
against their real CRM rows, so you can see where a figure came from rather than
trusting it. `tests/Unit/FormulaMapServiceTest.php` asserts the quotes stay faithful
to the sheet.

That screen also surfaces the drift described below: if Product Name lists more
courses than there are CRM rows behind it, Fee Total is flagged **understated**
instead of quietly being wrong. On the sample data it flags 7 students and stays
silent on the two whose exports are complete.

### TEXTJOIN does not deduplicate

Column J joins every matching CRM row verbatim; it does not collapse repeats.
The workbook proves it — client 12765881 is pasted as *"Master of Public Health +
Master of Public Health"*, one entry per row.

That matters beyond cosmetics. Product Name is the only visible sign of how many
source rows a student has, so deduplicating would make a two-row student look
like a one-row one and hide a missing enrolment from the shortfall check.

### Value paste is preserved, deliberately

The Summary tab asks for a value paste "to avoid any change in the value during
updation on every month or quarter". Nothing recalculates on a schedule or on
page load — `php artisan schedule` is intentionally empty. Rebuilding is an
explicit **Refresh values** click, and **archived students are skipped**, so a
figure already reported to Head Office cannot move underneath you.

### Two design decisions worth knowing

**Intakes are rows, not columns.** The sheet hardcodes `T2-2025`, `T3-2025`,
`T1-2026` as columns, which means editing the sheet every trimester. Here each
intake is a row in `student_intake_commissions`, so a new intake appears by
itself as soon as an Invoice carries a new *Commission intake* value. No schema
change, no code change.

**CR fee stays editable.** The sheet notes "some college or university allow
commission only on 1st year fee", but the real rule is per-partner contract and
isn't derivable from the export. The app seeds a sensible estimate (full fee for
courses ≤ 1 year, otherwise the pro-rata first-year slice) and then **never
overwrites a value you set** — your figure survives every refresh.

---

## Verification against the workbook

`tests/Unit/StudentDetailServiceTest.php` asserts the ported maths against the
workbook's own numbers — e.g. Meri Akter's duration `1.5041095890410958` and
CR fee `34704.91803278689`, and Tanzim Amin TANU's remaining fee of `0`
(20,500 credit − 21,100 paid − 600 adjustment).

```bash
php artisan test
```

### A data gap found in the sample file

Replaying the whole pipeline over the sample data reproduces **123 of 138**
field checks exactly. The 15 that differ are all the same issue, and it is a gap
in the sample export rather than in the port:

- For clients such as `10706654` and `12630942`, *Student Details* lists a
  packaged degree — "Diploma of Business + Bachelor of Business" — and a fee of
  114,960. The sample *CRM Data* tab contains only **one** of those two courses
  (fee 24,960). The second row was never included in the sample export, so the
  value-pasted figure is stale relative to its own source.
- Where the sample data *is* complete, the match is exact. Both students who
  have both of their CRM rows present (`10704258`, `12755966`) reconcile to the
  cent on `SUMIFS` **and** on `TEXTJOIN`.

So: **export all course rows per student** and the numbers reconcile. This is
exactly the drift the value-paste rule protects against, and the app makes it
visible — the student page lists the CRM rows behind every total, so a missing
row is obvious rather than silent.

---

## Stage 2

`Raw Data Export` is stored whole, and `CrmData::admissionOnly()` already scopes
stage 1 to Admission enrolments. OSHC commission tracking slots in as an
additional workflow scope plus its own intake rows — the intake-as-rows design
means it needs no schema migration.

---

## Importing

**Import workbook** in the sidebar takes an `.xlsx` and reads three tabs:
*Raw Data Export*, *CRM Data* and *Invoice*. Tick **Replace existing data** to
wipe first, or leave it off to add to what is there. Student Details is rebuilt
afterwards; archived students keep their frozen figures either way.

*Student Details* is **not** imported. It is a value paste, and this app rebuilds
it from CRM Data + Invoice, so importing the frozen copy would mean trusting it
over its own source.

It is still read, as a check. If that tab claims a higher Fee Total than CRM Data
can account for, the export is missing course rows and the import says so:

> Student Details claims 556,350 more in fees than CRM Data supports, across 7
> student(s) … 10706654 Zahidul Islam Parvez (short 101,280) — missing: Bachelor
> of Information Technology; 12630942 Mohammad Abdullah (short 90,000) — missing:
> Bachelor of Business; … Re-export CRM Data with every course row per student.

The pattern in the sample is consistent: CRM Data has the pathway course (an
English program or a Diploma) but not the degree that follows it. Without this
check a short export just produces low commission figures with nothing to show
why.

### History

Every run is logged at `/import`: file, time, status, which sheets were found,
rows per sheet, students built, size, duration, and the error if it failed.

- **Delete one entry** removes that log line.
- **Clear failed** drops just the failures.
- **Clear all** empties the history.

Deleting history does **not** delete data. An entry records a run; the rows it
loaded may since have been edited, and later imports may have added to them, so
there is no safe way to unpick one run's contribution. To clear data, use
*Delete all* on the CRM Data, Invoice or Raw Data tabs, or re-import with
*Replace existing data* ticked.

## Editing and deleting

Everything on screen is clickable, and every table row can be deleted.

| Where | What you can do |
|---|---|
| Student Details | open, edit, archive, restore, delete, bulk archive, bulk delete |
| Student page | edit CR fee, bonus due, adjustments and both remarks fields |
| Student Details-Formula | filter, search, trace any student back to its CRM rows |
| Invoice | delete a row, or empty the tab |
| CRM Data | delete a row, or empty the tab |
| Raw Data Export | delete a row, or empty the tab |
| Targets | add, update, delete |
| Import history | delete an entry, clear failed, clear all |

Deletes ask first, and the confirmation says what else moves:

- **Delete a student** clears the reported figures only. The row is a value paste
  rebuilt from CRM Data, so a later refresh brings it back. To remove someone for
  good, delete their CRM rows too.
- **Delete an invoice** re-runs that student straight away, since Paid Fee and the
  intake columns are summed from Invoice.
- **Delete the last CRM row** for a client removes the student row with it —
  nothing is left to rebuild from, so a value paste with no source is not kept.
- **Delete a raw row** changes no figures. Nothing is derived from that tab.

## Stack

Laravel 10 · PHP 8.1+ · MySQL · Blade · hand-written Material Design 3 CSS, no
build step and no Node toolchain.
