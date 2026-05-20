# Ethizo Home Health Care System Test Script

## Purpose
Use this script to validate the current working system end to end, including:
- patient intake
- referral intake
- episode creation and readiness
- SOC and OASIS completion
- speech-assisted SOC/OASIS entry
- visit scheduling and field workflow
- QA and documentation release
- EVV and billing workflow
- lifecycle actions
- admin and audit functions

## Environment Setup

### 1. Start backend
From:
`/Users/air/Documents/New project/backend`

Run:
```bash
bin/cake migrations migrate
bin/cake server -p 8765
```

### 2. Start frontend
From:
`/Users/air/Documents/New project/frontend`

Run:
```bash
npm run dev
```

### 3. Open the app
Open the Vite URL shown in the terminal.

Expected:
- app loads successfully
- top-left connectivity light shows connected state
- no fallback to demo mode unless backend is intentionally unavailable

## Test Data

### Patient
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

### Referral
- Source: `Northside Hospital`
- Admission source: `Hospital discharge`
- Diagnosis: `I50.32 Chronic diastolic heart failure`
- Planned SOC: today
- Face-to-face date: today or a recent date
- Referring provider: `Dr. Alexis Monroe`
- Referring phone: `404-555-0190`
- PCP: `Dr. Hayes`
- PCP phone: `404-555-0188`
- Caregiver: `Samuel Bishop`
- Caregiver relationship: `Spouse`
- Caregiver phone: `404-555-0110`
- Service location: `Patient home`
- Requested disciplines: `SN, PT`
- Order status: `received`

## 1. Patient Intake
1. Open `Patients`.
2. Click `Add patient`.
3. Complete the `Identity`, `Coverage`, and `Contacts` steps with the test patient data.
4. Save the patient.

Expected:
- patient appears in `Patient Registry`
- demographic and insurance details display correctly
- phone is formatted correctly
- no validation errors for required Medicare information

## 2. Patient Edit
1. In `Patient Registry`, click `Edit patient`.
2. Change one field such as phone or city.
3. Save changes.

Expected:
- updated value persists
- patient remains visible in the registry

## 3. Referral Intake
1. Open `Referrals`.
2. Click `Add referral`.
3. Complete `Intake`, `Care Team`, and `Service`.
4. Save the referral.

Expected:
- referral appears in `Referral Queue`
- service location, diagnosis, order status, and provider details display correctly

## 4. Referral Edit
1. In `Referral Queue`, click `Edit referral`.
2. Change one field such as notes or service city.
3. Save changes.

Expected:
- updated value persists in the queue

## 5. Referral to Episode Conversion
1. In `Referral Queue`, click `Create episode`.
2. Open `Episodes`.
3. Confirm the new episode appears in `Episode Board`.
4. Click `Use this episode`.

Expected:
- episode is created
- selected episode context is loaded
- inherited admission details are available

## 6. Admission Readiness Review
1. In `Episodes`, switch to `Admission`.
2. Review `Admission Readiness Summary`.

Expected:
- referral source, admission source, caregiver, PCP, and service location are present
- document/order counts are visible
- missing face-to-face or signed orders show as blockers when not completed

## 7. Referral Document Tracking
1. In `Admission Readiness Summary`, click `Manage referral documents`.
2. Add:
   - one `face_to_face` document
   - one `physician_orders` document
3. If available, attach sample files.

Expected:
- documents save successfully
- document list shows status, dates, and attachment metadata
- readiness indicators improve when appropriate

## 8. Physician Order Workflow
1. In `Admission`, click `Manage physician orders`.
2. Review existing order packet or create one if needed.
3. Use `Autofill from chart` if available after assessment/visits exist.
4. Mark an order as signed.

Expected:
- order version information is visible
- signed/unsigned state updates correctly
- active packet remains distinguishable from superseded packets

## 9. SOC and OASIS Manual Entry
1. In `Episodes`, switch to `Clinical`.
2. Click `Open SOC and OASIS`.
3. Fill the assessment manually with:
   - assessment type: `SOC`
   - diagnosis code: `I50.32`
   - functional score: `14`
   - comorbidity: `low`
   - medication reconciliation: `Completed`
   - homebound status: `homebound`
   - fall risk: `high`
   - hospitalization risk: `elevated`
   - emergency preparedness: `Reviewed`
   - care plan goals and clinical summary
4. Save the assessment.

Expected:
- assessment saves successfully
- current-date assessments resolve to `OASIS-E2`
- assessment appears in the clinical list

## 10. SOC and OASIS Speech Capture
1. Reopen `Open SOC and OASIS`.
2. In `Voice capture`, click `Start dictation`.
3. Speak a note such as:

`Start of care. Diagnosis code I50.32. Functional score 14. Medication reconciliation completed. Patient is homebound because she fatigues after 10 feet and requires assistance leaving home. Fall risk high. Hospitalization risk elevated. Emergency preparedness reviewed. Care plan goals improve endurance and medication adherence. Clinical summary patient recently discharged after CHF exacerbation.`

4. Click `Stop dictation`.
5. Click `Apply to form` if needed.

Expected:
- dictated text appears in the voice box
- structured fields auto-populate where detectable
- detected-field feedback appears
- no frontend crash if browser speech API is unavailable

### Speech fallback test
1. Paste a dictated note into the same voice box manually.
2. Click `Apply to form`.

Expected:
- parser still populates fields even without live microphone support

## 11. Scheduling
1. Open `Clinician`.
2. In `Scheduling`, select the current episode.
3. Review `Scheduling Recommendations`.
4. Review `Week-One Frequency Plan`.
5. Schedule:
   - one `SOC` visit
   - optionally the week-one plan

Expected:
- visit records are created
- recommendations reflect requested disciplines

## 12. Field Actions
1. Switch to `Field Actions`.
2. Find the SOC visit.
3. Click `Check in`.
4. Click `Document`.
5. Add documentation and save.
6. Click `Check out`.

Expected:
- visit status updates correctly
- sync state is shown
- EVV record is generated after checkout

## 13. Visit Documentation and QA Submission
1. In `Clinician`, switch to `Documentation`.
2. Load the SOC visit.
3. Complete structured note fields.
4. Click `Submit to QA review`.

Expected:
- documentation status changes to QA review state
- visit appears in QA documentation review queue

## 14. Episode Activation
1. Return to `Episodes`.
2. Switch to `Review`.
3. Open `Episode Readiness`.
4. Click `Activate episode`.

Expected:
- activation only succeeds after SOC and finalized OASIS are present
- episode status becomes active
- NOA due date is populated
- PDGM group code is populated

## 15. Pre-Bill Review
1. In `Episodes`, review `Pre-Bill Episode Summary`.
2. Confirm the episode story is visible:
   - activation blockers
   - billing blockers
   - active order packets
   - chart highlights
   - claim hold reasons

Expected:
- summary is populated
- `Resolve next blocker` appears when appropriate

## 16. QA Work Queue
1. Open `QA`.
2. Review open tasks.
3. Use `Assign to me` on one task.
4. Add escalation note and escalate it.
5. Resolve at least one non-documentation QA task.

Expected:
- ownership updates persist
- escalation note/history appear
- resolved task clears from open state

## 17. Documentation QA Release
1. In `QA`, locate `Documentation QA Release`.
2. Open the SOC visit review item.
3. Click `Lock documentation`.

Expected:
- documentation moves to locked state
- billing blocker related to chart lock clears when appropriate

## 18. EVV Workflow
1. Open `Billing`.
2. Review:
   - `Unified Billing Follow-Up`
   - `EVV Operations Queue`
3. Submit or reconcile EVV based on the visit state.

Expected:
- EVV record is visible
- EVV actions update lifecycle state
- EVV follow-up disappears from the queue when resolved

## 19. Claim Workflow
1. In `Billing`, locate the episode claim.
2. Try to submit before all blockers are cleared.

Expected:
- blocked claim should not move forward incorrectly

3. Clear blockers through QA/order/readiness workflows.
4. Submit the claim again.

Expected:
- claim progresses appropriately

### Claim lifecycle follow-up
1. Use `Claim Lifecycle Update`.
2. Test one or more:
   - accept claim
   - reject claim
   - create corrected claim
   - post payment
   - void claim

Expected:
- lifecycle metadata updates correctly
- denial/rework queue reflects corrected-claim logic

## 20. Billing and Denial Queues
1. Review:
   - `Claim Status Lanes`
   - `Denial and Rework Queue`
   - `Billing Readiness Queue`

Expected:
- claim categorization is accurate
- corrected claims show lineage
- readiness blockers point back to the right workflow

## 21. Lifecycle Actions
1. Return to `Episodes`.
2. Open `Lifecycle Actions`.
3. Test:
   - `Recertify`
   - `Resume of Care`
   - `Transfer` or `Discharge`

Expected:
- workflow-specific status changes occur
- related tasks/visits/holds appear

## 22. Overview Dashboard
1. Open `Overview`.
2. Confirm:
   - spotlight KPI cards render
   - work buckets render with counts
   - workflow timeline is visible
   - live episode focus displays the selected episode cleanly

Expected:
- dashboard loads without layout issues
- role-based work items remain actionable

## 23. Admin Workspace
1. Open `Admin`.
2. Review:
   - `User Access`
   - `Security Policy`
   - `Access Snapshot`
   - `Operational Reporting`
   - `Session Activity`
   - `Audit Workspace`
3. Create or edit a user.
4. Change one security setting and save.
5. Run one CSV export.

Expected:
- admin data loads
- saves persist
- exports trigger successfully

## 24. Responsive and Interaction Checks
1. Test on desktop width.
2. Test on tablet width.
3. Collapse and expand the left sidebar.
4. Open and close modals across Patients, Referrals, and Episodes.
5. Confirm toasts show in the top-right and clear automatically.

Expected:
- no broken layouts
- no unreadable wrapping
- left rail remains usable in collapsed state

## 25. Error and Fallback Checks
1. Open SOC/OASIS speech capture in a browser without supported speech recognition if possible.
2. Confirm manual paste-and-apply still works.
3. Try opening the app with backend down.

Expected:
- speech panel degrades gracefully
- app falls back to demo mode only when backend is unavailable
- no fatal UI errors

## Pass Criteria
The test run passes if:
- all major modules load
- patient, referral, episode, assessment, visit, QA, EVV, billing, and admin flows complete successfully
- SOC/OASIS speech capture fills structured fields from dictated narrative
- lifecycle and billing blockers behave correctly
- no major frontend crashes or broken layouts are observed

## Suggested Result Log
Record results like this:

```text
1. Patient Intake - Pass
2. Patient Edit - Pass
3. Referral Intake - Pass
4. Referral Edit - Pass
5. Referral to Episode Conversion - Pass
6. Admission Readiness Review - Pass
7. Referral Document Tracking - Pass
8. Physician Order Workflow - Pass
9. SOC/OASIS Manual Entry - Pass
10. SOC/OASIS Speech Capture - Pass
...
Issues:
- Example: speech capture did not detect functional score from one phrasing
- Example: EVV reconcile action needed two clicks
```
