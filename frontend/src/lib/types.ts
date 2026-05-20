export type UserRole = 'Intake' | 'Clinician' | 'Billing' | 'QA' | 'Admin'

export interface User {
  id: number
  full_name: string
  email: string
  role: UserRole
}

export interface SecuritySettings {
  id?: number
  require_mfa: boolean
  session_timeout_minutes: number
  remember_device_days: number
  password_rotation_days: number
  attachment_retention_days: number
  allowed_ip_ranges?: string | null
  enforce_device_attestation: boolean
  created?: string
  modified?: string
}

export interface AuditEvent {
  id: number
  actor_email: string
  action: string
  model: string
  model_id: number
  details?: Record<string, unknown> | string | null
  created: string
}

export interface AppUser {
  id: number
  full_name: string
  email: string
  role: UserRole
  mobile?: string | null
  status?: string
  mfa_enabled?: boolean
  last_login_at?: string | null
  created?: string
  modified?: string
}

export interface SessionActivity {
  user_id: number
  full_name: string
  email: string
  role: UserRole
  status: string
  mfa_enabled: boolean
  last_login_at?: string | null
  activity_state: string
  recent_action?: string | null
  recent_model?: string | null
  recent_at?: string | null
}

export interface DashboardMetrics {
  patients: number
  referrals: number
  episodes: number
  visitsToday: number
  qaTasks: number
  claimsOnHold: number
}

export interface Patient {
  id: number
  first_name: string
  last_name: string
  dob: string
  gender?: string
  payer_type: string
  medicare_number?: string
  insurance_member_id?: string
  phone?: string
  address1?: string
  address2?: string
  city?: string
  state?: string
  postal_code?: string
  emergency_contact_name?: string
  emergency_contact_relationship?: string
  emergency_contact_phone?: string
  status: string
  primary_physician?: string
  responsible_party_name?: string
  responsible_party_relationship?: string
  responsible_party_phone?: string
}

export interface Referral {
  id: number
  patient_id: number
  patient_name: string
  source_name: string
  admission_source?: string
  payer_type: string
  primary_diagnosis: string
  requested_disciplines?: string[] | string
  order_status?: string
  physician_orders_signed?: boolean
  physician_orders_signed_at?: string
  face_to_face_date?: string
  referring_provider_name?: string
  referring_provider_phone?: string
  pcp_name?: string
  pcp_phone?: string
  caregiver_name?: string
  caregiver_relationship?: string
  caregiver_phone?: string
  service_location_type?: string
  service_address1?: string
  service_city?: string
  service_state?: string
  service_postal_code?: string
  planned_soc_date: string
  intake_ready: boolean
  status: string
  notes?: string
}

export interface ReferralDocument {
  id: number
  referral_id: number
  document_type: string
  document_status: string
  source_name?: string
  received_at?: string
  signed_at?: string
  original_file_name?: string
  stored_file_name?: string
  mime_type?: string
  file_size?: number
  attachment_path?: string
  document_note?: string
}

export interface PhysicianOrder {
  id: number
  referral_id?: number
  episode_id: number
  referral_document_id?: number
  order_scope: string
  version_number: number
  order_status: string
  active: boolean
  sent_at?: string
  received_at?: string
  signed_at?: string
  signer_name?: string
  order_summary?: string
  order_note?: string
}

export interface EpisodeAdmissionSnapshot {
  referral_source?: string
  admission_source?: string
  planned_soc_date?: string
  face_to_face_date?: string
  primary_diagnosis?: string
  requested_disciplines?: string[]
  order_status?: string
  physician_orders_signed?: boolean
  physician_orders_signed_at?: string
  referring_provider_name?: string
  referring_provider_phone?: string
  pcp_name?: string
  pcp_phone?: string
  caregiver_name?: string
  caregiver_relationship?: string
  caregiver_phone?: string
  service_location_type?: string
  service_address1?: string
  service_city?: string
  service_state?: string
  service_postal_code?: string
  notes?: string
}

export interface Episode {
  id: number
  patient_id: number
  patient_name: string
  referral_id?: number
  cert_start_date: string
  cert_end_date: string
  start_of_care_date?: string
  episode_status: string
  payer_type: string
  primary_diagnosis: string
  admission_readiness_snapshot?: EpisodeAdmissionSnapshot | string | null
  noa_due_date?: string
  pdgm_group_code?: string
  oasis_version_required?: string
}

export interface Assessment {
  id: number
  episode_id: number
  assessment_type: string
  completed_at: string
  oasis_version: string
  status: string
  principal_diagnosis_code: string
  functional_score: number
  comorbidity_level: string
  medication_reconciliation_completed?: boolean
  homebound_status?: string
  homebound_narrative?: string
  fall_risk_level?: string
  hospitalization_risk?: string
  emergency_preparedness_reviewed?: boolean
  care_plan_goals?: string
  clinical_summary?: string
  answers?: Record<string, string>
  assessment_payload?: AssessmentClinicalPayload | string | null
}

export interface AssessmentClinicalPayload {
  medication_review?: {
    issues?: string
    high_risk_meds?: string
  }
  wounds?: {
    present?: boolean
    notes?: string
  }
  caregiver_support?: {
    availability?: string
    notes?: string
  }
  risk_notes?: string
}

export interface VisitDocumentationPayload {
  visit_focus?: string
  visit_narrative?: string
  interventions?: string
  patient_response?: string
  vitals?: string
  pain_level?: string
  teaching_topics?: string
  medication_review?: string
  wound_care?: string
  mobility_status?: string
  adl_support?: string
  psychosocial_notes?: string
  abnormal_findings?: string
  physician_contact_needed?: boolean
  follow_up_plan?: string
  next_visit_focus?: string
}

export interface Visit {
  id: number
  episode_id: number
  patient_id: number
  patient_name: string
  visit_type: string
  discipline: string
  scheduled_start: string
  scheduled_end: string
  actual_start?: string
  actual_end?: string
  clinician_name: string
  status: string
  requires_evv: boolean
  documentation_summary?: string
  documentation_status?: string
  documentation_payload?: VisitDocumentationPayload | string | null
  qa_review_notes?: string
  reassigned_from_clinician?: string
  missed_reason?: string
  follow_up_plan?: string
  sync_status: string
}

export interface EvvRecord {
  id: number
  visit_id: number
  state_code: string
  vendor_name: string
  status: string
  exception_reason?: string
  submitted_at?: string
  submission_reference?: string
  reconciled_at?: string
}

export interface Claim {
  id: number
  episode_id: number
  claim_type: string
  status: string
  amount?: number | string
  hold_reason?: string
  submission_reference?: string
  submitted_at?: string
  payer_claim_number?: string
  accepted_at?: string
  rejected_at?: string
  rejection_reason?: string
  payment_amount?: number | string
  remittance_reference?: string
  paid_at?: string
  voided_at?: string
  void_reason?: string
  corrected_from_claim_id?: number
  correction_reason?: string
}

export interface QaTask {
  id: number
  episode_id?: number
  visit_id?: number
  assessment_id?: number
  task_type: string
  priority: string
  base_priority?: string
  status: string
  title: string
  details?: string
  assigned_role?: string
  assigned_user_name?: string
  assigned_at?: string
  assignment_history?: Array<Record<string, string>>
  escalation_status?: string
  escalation_reason?: string
  escalation_note?: string
  last_escalated_at?: string
  is_overdue?: boolean
  due_at?: string
}

export interface EpisodeReadiness {
  episode_id: number
  soc_visit_completed: boolean
  finalized_assessment_exists: boolean
  open_qa_tasks: number
  pending_evv_records: number
  claim_holds: number
  ready_to_activate: boolean
  primary_blocker?: string | null
  blockers: string[]
}

export interface EpisodeReviewSummary {
  episode_id: number
  patient_name: string
  episode_status: string
  ready_to_activate: boolean
  ready_to_bill: boolean
  activation_blockers: string[]
  billing_blockers: string[]
  open_qa_tasks: number
  pending_evv_records: number
  unsigned_active_orders: number
  completed_visits: number
  locked_visits: number
  hold_reasons: string[]
  open_task_titles: string[]
  active_order_summaries: string[]
  recent_visit_highlights: string[]
}

export interface AppDataset {
  metrics: DashboardMetrics
  patients: Patient[]
  referrals: Referral[]
  referralDocuments: ReferralDocument[]
  physicianOrders: PhysicianOrder[]
  episodes: Episode[]
  assessments: Assessment[]
  visits: Visit[]
  evvRecords: EvvRecord[]
  claims: Claim[]
  qaTasks: QaTask[]
  securitySettings: SecuritySettings
  auditEvents: AuditEvent[]
  adminUsers: AppUser[]
  sessionActivity: SessionActivity[]
}

export interface OfflineAction {
  id: string
  action: 'check-in' | 'check-out'
  visitId: number
  payload: Record<string, unknown>
  createdAt: string
}
