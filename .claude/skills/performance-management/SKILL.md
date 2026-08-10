---
name: performance-management
description: Rules and calculations for Navracar's employee performance management system — KPI targets, monthly scoring, task and behavioral evaluation, manager and self-evaluation, evidence standards, warnings/penalties, and promotion recommendations. Use this skill whenever the task touches employee reviews, performance scores, KPI tracking, monthly evaluations, manager or self-evaluation forms, performance improvement plans (PIPs), warning letters, disciplinary/penalty decisions, or promotion or raise recommendations for Navracar staff (admin or sales roles) — even if the user doesn't say "performance management" explicitly, e.g. "score this employee's month," "should we warn this rep," "write a self-review," or "is this person ready for promotion."
---

# Navracar Employee Performance Management

This skill defines the complete rule set for evaluating employee performance at Navracar. Apply it consistently — the value of a performance system comes from every cycle being scored the same way, not from case-by-case judgment calls. When a request only touches one piece (e.g., "just calculate the KPI score"), still follow the rules for that piece exactly; don't improvise.

Treat every number and threshold below as the current company policy, not a suggestion. If the user wants to change a weight or threshold, update this file so the change persists for future cycles instead of applying a one-off exception.

## When to use this skill

Reach for this skill any time you're asked to:
- Calculate or explain a KPI, task, behavioral, manager, self-, or final monthly score
- Draft or review a manager evaluation, self-evaluation, or evidence log
- Decide whether a warning, penalty, or PIP applies
- Assess promotion or raise readiness
- Answer "what's our policy on X" for any part of the review cycle

If a request is close to this domain but isn't actually about evaluating a person's work (e.g., general HR onboarding, payroll mechanics unrelated to performance), don't force this skill — just note the boundary.

## Scoring model overview

Every employee gets one **Final Monthly Score** (0–100), a weighted blend of five components:

| Component | Weight | Who scores it | Section |
|---|---|---|---|
| KPI Achievement | 40% | System-calculated from role KPIs | §1 |
| Task Execution Quality | 25% | Manager, task-by-task | §2 |
| Behavioral Evaluation | 15% | Manager | §3 |
| Manager Evaluation (holistic) | 15% | Manager | §4 |
| Self-Evaluation | 5% | Employee | §5 |

```
Final Score = 0.40×KPI + 0.25×Task + 0.15×Behavioral + 0.15×Manager + 0.05×Self
```

Round to the nearest whole number (0.5 rounds up). If a component is missing or rejected for lack of evidence, follow §6's fallback rule rather than silently dropping it from the average.

---

## 1. KPI Rules

- Every role has a written KPI set defined at the **start of each quarter** by the role's manager. Mid-cycle KPI changes are not allowed except for a role change, which prorates the two KPI sets by days active in each.
- Each KPI must be **SMART**: a named metric, a data source (e.g., CRM export, lead log, invoice count), a numeric target, a weight within the KPI bucket, and the measurement window.
- Per-KPI achievement: `achievement% = actual / target × 100`, floored at 0%, capped at 150% (over-achievement beyond 150% is recognized as a bonus note, not extra score, so one exceptional metric can't mask failure on the rest).
- Composite KPI score = weighted sum of each KPI's achievement%, then capped at 100 for the purpose of the Final Score formula.
- Typical Navracar KPI examples by role — adapt, don't copy blindly:
  - **Sales (`sales`)**: qualified leads contacted within SLA, lead-to-pre-invoice conversion rate, pre-invoices issued, response time to new CRM leads, customer follow-up completion rate.
  - **Admin (`admin`)**: request-resolution turnaround, CRM data quality/completeness, kanban pipeline hygiene (no stale unclassified leads), template/system-maintenance tasks completed.
- KPI numbers come from the system of record (CRM/admin panel data), never from self-reported estimates. If a KPI's data source is unavailable for the month, that KPI is excluded from the composite and its weight is redistributed proportionally across the remaining KPIs — do not silently zero it out.

## 2. Task Evaluation

Score each task, then roll up to a monthly Task Execution score (0–100):

- **Completion rate**: tasks completed / tasks assigned.
- **On-time rate**: tasks completed by their due date / tasks completed. A task fixed after rework still counts its original due-date slip toward timeliness — rework doesn't erase the delay, but also isn't double-penalized once corrected.
- **Quality rating** (1–5 rubric per task, set by the reviewing manager): correctness/accuracy, adherence to instructions, and amount of rework needed.
- **Complexity weighting**: harder or higher-stakes tasks (manager-flagged at assignment time) count more toward the rollup than routine tasks — a missed simple task and a missed complex task are not equivalent.

Roll these four signals into one 0–100 Task Execution score using consistent judgment (e.g., completion and on-time rate as pass/fail multipliers on top of the complexity-weighted quality average). Document the rollup logic used if it isn't the obvious weighted average, so next month's reviewer can reproduce it.

## 3. Behavioral Evaluation

Manager rates the employee 1–5 on each dimension, then converts the average to a 0–100 scale (`(avg − 1) / 4 × 100`):

- **Teamwork & collaboration**
- **Communication**
- **Initiative & ownership**
- **Reliability & discipline** (attendance, punctuality, policy adherence)
- **Customer/client conduct** (for customer-facing roles like sales)

Rules:
- Every dimension needs **at least one dated, specific example** (§6) — "good attitude" is not a rating, "resolved the Aug 14 pricing complaint calmly after the customer escalated" is.
- A single incident should not swing a dimension score unless it's severe (see §9 for misconduct that bypasses normal scoring entirely). Normal ratings reflect a pattern across the month.

## 4. Manager Evaluation

This is the manager's holistic judgment call, distinct from the mechanical KPI/task/behavioral scores above — it exists to capture context numbers can't.

- Score 0–100, plus a short written narrative: strengths, risks, and one concrete development action for next month.
- The manager score must not simply restate the KPI achievement number. If it deviates from the KPI composite by **more than 15 points** in either direction, the narrative must explain why (e.g., KPI target was miscalibrated, employee absorbed unplanned work, external blocker outside employee's control).
- To keep managers calibrated against each other, manager scores are reviewed in a **quarterly calibration meeting** across managers before being finalized for that quarter's cycles. This exists specifically to catch grade inflation/deflation between teams, not to override any one manager unilaterally.

## 5. Employee Self-Evaluation

- Employee scores themselves 0–100 on the same dimensions as §2–§4, and must list specific accomplishments and evidence, not just numbers — same evidence bar as the manager's evaluation.
- Self-evaluation is weighted at only 5% of the Final Score by design — it's a data point and a perception check, not a lever the employee controls unilaterally.
- If the self-score and the manager's overall assessment differ by **more than 20 points**, this doesn't get quietly averaged away: it must trigger a calibration conversation between employee and manager, documented in the evidence log, before the cycle is closed.

## 6. Evidence Requirements

No score component is valid without evidence. Minimum bar per component:

| Component | Acceptable evidence |
|---|---|
| KPI | System-of-record export/report (CRM data, invoice counts, timestamps) |
| Task | Task-log entry with completion timestamp, linked deliverable, reviewer sign-off |
| Behavioral | ≥2 dated, specific observations per rated dimension per month |
| Manager | Written narrative citing at least one piece of evidence per scored dimension |
| Self | Specific accomplishments/evidence listed alongside each self-rated number |

**Missing-evidence fallback**: a component submitted without its minimum evidence is not accepted as-is. Send it back for revision. If it's still unresolved by the scoring deadline (§7), that component defaults to the **lower of** (a) last month's score for that component, or (b) 50 (top of "Needs Improvement," §8) — and the record is flagged for audit. Never let an unevidenced score quietly become the employee's actual number.

## 7. Monthly Scoring Process

Cycle = calendar month, scored the following month on this timeline:

1. **Day 1–3**: Employee submits self-evaluation + evidence.
2. **Day 4–7**: Manager submits task, behavioral, and manager evaluation + evidence. KPI numbers are pulled automatically from the system.
3. **Day 8**: Final Score calculated via the §0 formula and rounded.
4. **Day 9–10**: Score released to the employee.
5. **Within 3 business days of release**: employee may file a written dispute citing specific evidence against a specific component. A dispute is reviewed by the manager's own supervisor and can only adjust the disputed component, not the whole score.
6. Once resolved (or once the dispute window lapses), the score is **locked** into the employee's record.

**Late submissions**: if a manager misses the Day 7 deadline, escalate to their supervisor. Until resolved, the employee's score for that month is marked **provisional**, using the prior month's KPI/behavioral trend as a placeholder — never leave an employee with no score because their manager was late.

## 8. Score Bands & Interpretation

| Final Score | Band | Meaning |
|---|---|---|
| 90–100 | Outstanding | Exceptional, consistently exceeds expectations |
| 75–89 | Strong | Reliable, meets and often exceeds expectations |
| 60–74 | Meets Expectations | Solid, acceptable performance |
| 45–59 | Needs Improvement | Below bar on multiple components |
| 0–44 | Unsatisfactory | Serious, immediate concern |

Bands drive both the compensation multiplier and the warning ladder (§9): Outstanding ×1.2, Strong ×1.0, Meets Expectations ×0.8, Needs Improvement ×0.5 (plus mandatory coaching), Unsatisfactory ×0 (plus warning ladder).

## 9. Warnings and Penalties

Apply this ladder based on the monthly band (§8), independent of the compensation multiplier:

1. **Needs Improvement (45–59), first occurrence**: Manager holds a documented coaching conversation. No formal warning yet.
2. **Unsatisfactory (<45) once, OR Needs Improvement for 2 consecutive months**: Formal **Written Warning #1**. Manager and employee agree a Performance Improvement Plan (PIP) with specific KPIs/behaviors, evidence checkpoints, and a 30/60/90-day review window.
3. **Still below Meets Expectations (60) during the PIP window**: **Written Warning #2 (Final Warning)**, tighter PIP, 30-day review window.
4. **Fails to reach ≥60 by the end of the Final Warning window**: Escalate to a termination review by the manager's supervisor and HR — this is a committee decision, never a single manager's unilateral call.
5. **Warning expiry**: any active warning is cleared after **6 consecutive months at Strong (≥75) or better** with no new warning issued in that window.

**Serious misconduct bypasses this ladder entirely** — a single evidenced incident of policy violation, dishonesty, data fraud (e.g., falsifying CRM records), or harassment goes straight to HR/management escalation regardless of the current score or band.

## 10. Promotion Recommendations

An employee is eligible to be considered for promotion only when **all** of the following hold:

- Final Score at Strong (≥75) or better for the **last 3 consecutive months**.
- No active warning (§9) in the last 6 months.
- Behavioral score at Meets Expectations or better every month in that window — strong KPIs alone don't qualify someone whose conduct is weak.
- At least 2 full KPI cycles fully evidenced (not provisional, no missing-evidence fallbacks).

Process:
1. Manager nominates in writing, citing the evidence log above and comparing the employee against the **target role's** KPI bar, not just their current role's.
2. Reviewed by a small committee (nominating manager + one level above + HR) — no single manager promotes unilaterally, mirroring the calibration principle in §4.
3. Document the outcome either way (approved / deferred with named gap / denied) so there's an audit trail for the next attempt.

---

## Quick reference checklist

When asked to score or evaluate someone this month, work through in order:
1. Pull KPI actuals from the system → composite KPI score (§1).
2. Roll up task-level data → Task Execution score (§2).
3. Confirm each behavioral dimension has evidence → Behavioral score (§3).
4. Get/write the manager's holistic score + narrative, check the 15-point deviation rule (§4).
5. Get the self-evaluation, check the 20-point gap rule (§5).
6. Verify evidence exists for every component (§6); apply the fallback if not.
7. Compute the Final Score, assign a band (§8).
8. Check the warning ladder (§9) and promotion eligibility (§10) against the band and history.
