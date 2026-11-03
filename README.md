# LIFC Audit CRM - Inspection & Scoring Module

Complete audit inspection and scoring backend system for **Laxmi India Finance Ltd.** built with **Laravel 11** and **MySQL**.

---

## 🚀 Key Modules Implemented

### 1. Section A: Cash and Cheque Management Norms (Carry: 4 Marks)
* **A.1 (Collection to be deposited in T+1) [Carry: 2 Marks]:**
  - Ported directly from Ruby with enhanced performance and validation.
  - Automatically reads uploaded LMS receipts sheet (`.xlsx`, `.xls`, `.csv`).
  - Calculates date gap: `deposit_date - payment_date`.
  - Determines TAT (`T+1`, `T+2`, `T+3`, etc.) and flags `is_over_tat` when delay > 1 day.
  - Assigns active `ScoringRule` (ranges: 0-1 days = 10, 2 days = 7, 3 days = 4, 4 days = 2, 5+ days = 0).
  - Score Formula: `(Total Receipts - Delayed Receipts (> T+1)) / Total Receipts * 2 Marks`.
  - *In sample data: 119 total receipts, 42 delayed receipts => (77 / 119) * 2 = **1.29 Marks** (76%).*

* **A.2 (Collection Amount Deposited in Personal Account) [Carry: 2 Marks]:**
  - Automatically reads Decentro / UPI personal account transactions sheet.
  - Automatically matches employee using Employee ID / Employee Code or Mobile Number / VPA.
  - Links transaction to branch and calculates violation score impact.
  - *In sample data: 15 violations => **1.75 Marks**.*
  - **Sub Total Section A: 1.29 + 1.75 = 3.04 Marks / 4 Marks.**

---

### 2. Sections B Through G: Checklist & Manual Scoring (Carry: 36 Marks)
Master checkpoints and manual evaluation by auditor with comments, immediate actions, and department allocation:
* **Section B: Operational Aspects & App Awareness** (Carry: 5 Marks | Obtained: 4 Marks | 80%)
  - B.1 Software Uses Knowledge (Carry 2)
  - B.2 Updated Product/Credit Policy in Physical format (Carry 1)
  - B.3 Non-refundable charges info pasted (Carry 1)
  - B.4 ID Cards & Dress code adherence (Carry 1)
* **Section C: Customer Management** (Carry: 10 Marks | Obtained: 8 Marks | 80%)
  - C.1 Upfront charges deducted info given (Carry 4)
  - C.2 Customer Complaint Resolutions (Carry 4)
  - C.3 Property Papers and NOC Acknowledgement sent to HO (Carry 2)
* **Section D: Display and Regulatory Requirements** (Carry: 7 Marks | Obtained: 5 Marks | 71.43%)
  - D.1 Mandatory documents on Notice Board (11 items) (Carry 4.9994)
  - D.2 Name board & Outside Board visibility (Carry 0.9996)
  - D.3 Direction Sign Board (Carry 0.9996)
* **Section E: Document Management** (Carry: 3 Marks | Obtained: 3 Marks | 100%)
  - E.1 Registers maintained (Courier, Grievance, NOC) (Carry 0.9999)
  - E.2 Critical documents placed properly & no old files (Carry 0.9999)
  - E.3 No PDC without company name (Carry 0.9999)
* **Section F: Admin & House Keeping** (Carry: 6 Marks | Obtained: 5 Marks | 83.33%)
  - F.1 Rent/Lease agreement available with landlord contact (Carry 0.9996)
  - F.2 Tobacco/Cigarette check (Carry 0.9996)
  - F.3 Cleanliness & housekeeping petty cash (Carry 0.9996)
  - F.4 Major Admin assets matched with HO Records/AIMS (Carry 1.9998)
  - F.5 Scrap assets details in AIMS (Carry 0.9996)
* **Section G: Information Security** (Carry: 5 Marks | Obtained: 5 Marks | 100%)
  - G.1 USB Ports blocked (Carry 1)
  - G.2 Computer/Laptop checked for unethical data (Carry 1)
  - G.3 Anti-virus installed & updated (Carry 1)
  - G.4 IT Assets matched with HO Records/AIMS (Carry 2)

### **Sub Total I (Process - Sections A to G):**
* **Carry:** 40 Marks
* **Obtained:** **33.04 Marks**
* **Score:** **82.60%**
* **Grade:** **Good**

---

## 🛠️ CLI Verification Command

Run the built-in verification command to load user test data and print the inspection score table:
```bash
php artisan audit:test-user-data
```

---

## 🌐 Web Interface

Start the local server:
```bash
php artisan serve
```
Open in browser:
- `http://127.0.0.1:8000/scorecard`
Features:
- View Branch details & Inspection Card
- Upload LMS Receipt Sheet (A.1 auto scoring)
- Upload Personal Account Sheet (A.2 auto scoring)
- Edit manual checkpoints B to G with comments, immediate action, and department tags
- Save & auto-recalculate total scores and final grading!

---

## 📡 REST API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/admin/dashboard/stats` | System stats (Branches, Receipts, Violations) |
| `GET` | `/api/v1/admin/scoring_rules` | List scoring rules with pagination |
| `POST` | `/api/v1/admin/scoring_rules/sync` | Bulk upsert / sync scoring rules |
| `POST` | `/api/v1/admin/cash_cheque_receipts/import` | Upload receipts sheet (A.1 auto-calculation) |
| `POST` | `/api/v1/admin/personal_account_transactions/import` | Upload personal account sheet (A.2 auto-calculation) |
| `GET` | `/api/v1/admin/branch_inspection/{branchId}` | Get Branch Inspection Report Card (A to G) |
| `POST` | `/api/v1/admin/inspections/{inspectionId}/update_scores` | Save manual marks B to G & recalculate |
| `POST` | `/api/v1/admin/inspections/{inspectionId}/re_evaluate` | Re-evaluate auto scores A.1 & A.2 |
