# Release 1 End-to-End Test Script

## Start Services

```bash
cd "/Users/air/Documents/New project/backend"
bin/cake migrations migrate
bin/cake server -p 8765
```

```bash
cd "/Users/air/Documents/New project/frontend"
npm run dev
```

Open the frontend URL and confirm the app shows `API connected`.

## 1. Patient Intake

1. Open `Patients`.
2. Create a patient with:
   - First name: `Eleanor`
   - Last name: `Bishop`
   - DOB: `1946-02-14`
   - Gender: `Female`
   - Address: `125 Peachtree View`
   - City: `Atlanta`
   - State: `GA`
   - ZIP: `30309`
   - Phone: `404-555-0101`
   - Insurance: `Medicare`
   - Medicare number: `1EG4TE5MK73`
   - Physician: `Dr. Hayes`
   - Emergency contact: `Samuel Bishop`
   - Emergency relationship: `Spouse`
   - Emergency phone: `404-555-0110`
   - Responsible party: `Samuel Bishop`
3. Confirm the patient appears in the registry.
4. Click `Edit patient`, change the city or phone, save, and confirm the update sticks.

## 2. Referral Intake

1. Open `Referrals`.
2. Create a referral for that patient:
   - Source: `Northside Hospital`
   - Admission source: `Hospital discharge`
   - Diagnosis: `I50.32 Chronic diastolic heart failure`
   - Planned SOC: today
   - Requested disciplines: `SN, PT`
   - Referring provider: `Dr. Alexis Monroe`
   - PCP: `Dr. Hayes`
   - Caregiver: `Samuel Bishop`
   - Service location: `Patient home`
   - Service address: patient home address
   - Intake ready: `Yes`
3. Save it.
4. Click `Edit referral`, change one field like service city or notes, save, and confirm the queue reflects the update.

## 3. Referral Documents

1. Convert the referral to an episode.
2. In `Referral Document Tracker`, add:
   - one `face_to_face` document
   - one `physician_orders` document
3. Optionally upload sample attachments.
4. Confirm the documents show in the tracker and readiness reflects them.

## 4. Episode Admission + Readiness

1. In `Episodes`, click `Use this episode`.
2. Review `Admission Readiness Summary`.
3. Confirm inherited referral details appear:
   - admission source
   - requested disciplines
   - referrer
   - caregiver
   - service address
4. Edit one admission detail from the episode screen, save, and confirm it updates.
5. In `Episode Readiness`, confirm blockers are shown before SOC/OASIS are done.

## 5. Assessment / OASIS

1. In `SOC and OASIS`, create an assessment:
   - completed after `2026-04-01`
   - diagnosis code: `I50.32`
   - functional score: `14`
   - comorbidity: `low`
   - medication reconciliation: `Yes`
   - homebound status: `homebound`
   - homebound narrative filled
   - care plan goals filled
   - clinical summary filled
2. Save it.
3. Confirm it resolves to `OASIS-E2`.
4. Edit the assessment once and save again to confirm updates work.

## 6. Visit Scheduling

1. Open `Clinician`.
2. Choose the episode.
3. Review `Scheduling Recommendations`.
4. Review `Week-One Frequency Plan`.
5. Click `Load into form` on one recommendation and schedule a `soc` visit.
6. Optionally click `Schedule week-one plan` and confirm multiple visits are created.

## 7. Field Workflow

1. In `Field Visit Actions`, find the SOC visit.
2. Click `Check in`.
3. Click `Document`, enter structured documentation.
4. Click `Save documentation`.
5. Reopen the same visit and click `Submit to QA review`.
6. Click `Check out`.
7. Confirm:
   - visit status progresses correctly
   - documentation status changes
   - visit summary appears
   - EVV record is created after checkout

## 8. Episode Activation

1. Return to `Episodes`.
2. Confirm readiness now shows:
   - SOC completed
   - finalized OASIS exists
3. Click `Activate this episode`.
4. Confirm:
   - episode becomes `active`
   - `NOA due date` is populated
   - `PDGM group` is populated

## 9. Physician Orders

1. In `Physician Order Workflow`, click `Autofill from chart`.
2. Confirm summary/note are generated from assessment + visit charting.
3. Create a new order packet if needed.
4. Leave one active order unsigned and confirm it blocks billing.
5. Then update/sign the order and confirm the related blocker clears.

## 10. QA Queue

1. Open `QA`.
2. Confirm tasks exist for assessment review, documentation review, or order review.
3. Use `Assign to me` on one task.
4. Add an escalation note and click `Escalate`.
5. Confirm:
   - owner changes
   - escalation note appears
   - history shows assignment and escalation
6. Resolve a task and confirm it disappears from open work.

## 11. Documentation Lock

1. In `QA`, use `Documentation QA Release`.
2. Open a submitted visit.
3. Click `Lock documentation`.
4. Confirm the visit documentation status becomes `locked`.

## 12. Billing + EVV

1. Open `Billing`.
2. Confirm:
   - claim exists
   - readiness queue shows blockers if any remain
   - EVV section shows the record
3. If EVV is pending, submit it.
4. If QA/order blockers remain, use `Resolve next blocker`.
5. After blockers are cleared, submit the claim.
6. Confirm claim status moves forward and hold reasons clear.

## 13. Lifecycle Actions

Test at least these from `Episodes`:

1. `Recertify`
   - confirm status changes
   - recert visit is created
   - related QA/order work appears
2. `Resume of Care`
   - confirm ROC visit is created
3. `Transfer` or `Discharge`
   - confirm future visits are held
   - billing gets a hold
4. Optional: `Death at Home`
   - confirm episode closes as `deceased`

## 14. Role / Queue Checks

1. Open `Overview`.
2. Confirm work is grouped into:
   - `Do Now`
   - `Due Today`
   - `Watch Next`
3. Confirm assigned/escalated tasks show up with the right owner and urgency.
4. Open:
   - `Intake Documentation Queue`
   - `QA Work Queue`
   - `Billing Readiness Queue`
5. Confirm the same blocker story is consistent across all three.

## Pass Criteria

- patient, referral, episode, assessment, visit, QA, EVV, claim, and lifecycle flows all save successfully
- readiness blockers appear before actions and clear after completion
- `OASIS-E2` is used for current assessments
- activation only works after SOC + finalized assessment
- visit documentation can be submitted and locked
- unsigned orders and QA issues block billing
- assignment and escalation history work
- claim can submit only after blockers are resolved
