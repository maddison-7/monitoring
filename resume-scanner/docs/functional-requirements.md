# AI-Powered Resume Screening System Functional Requirements

## Corrected Actor Definitions

- Recruiter: Primary user for screening, ranking, shortlisting, and interview progression.
- Admin: System administrator for recruiter management, analytics, audit, and configuration.

## Module Map

### Module 1: Authentication & Access Control
- FR-01 to FR-05

### Module 2: Job Description Management (Recruiter)
- FR-06 to FR-12

### Module 3: Resume Upload & Management (Recruiter)
- FR-13 to FR-20

### Module 4: Resume Text Extraction
- FR-21 to FR-25

### Module 5: Candidate Information Extraction (via AI API)
- FR-26 to FR-32

### Module 6: Personal Information Anonymization (Bias Reduction)
- FR-33 to FR-38

### Module 7: Candidate-Job Matching & Scoring
- FR-39 to FR-46

### Module 8: Candidate Ranking & Screening Display (Recruiter)
- FR-47 to FR-54

### Module 9: Shortlisting & Interview Management (Recruiter)
- FR-55 to FR-60

### Module 10: Admin Functions
- FR-61 to FR-73

### Module 11: API & Error Handling
- FR-74 to FR-77

## Current Implementation Alignment in This Codebase

- Actor model updated to Admin and Recruiter (legacy `hr_manager` is mapped to Admin for backward compatibility).
- Role-based routes separated into:
  - Recruiter operations: job management, upload, ranking.
  - Admin operations: recruiter management, global jobs, analytics, reports, audits, API usage, system settings.
- Dashboard and sidebar navigation now render role-specific modules.
- Authentication registration role options limited to Admin and Recruiter.

## Notes

- The application now exposes structural placeholders for Admin modules (FR-61 to FR-73) with pages and route protection.
- Advanced AI processing, scoring pipelines, and reporting engines for FR-21 onward require backend service implementation and data model extensions beyond UI/routing scaffolding.

## Gemini AI Integration

- The system uses the Gemini API (LLM) for:
  - Extraction of candidate information from resumes (flexible, multi-language parsing)
  - Semantic skill and qualification matching (meaning-based, not just keyword)
  - Accurate candidate scoring (skills, experience, education)
  - Smart candidate ranking (best fit ordering)
  - AI-generated recruiter-friendly summaries
  - Automated, scalable, and reliable screening
- The AI compares job descriptions with resumes for more accurate, fair, and efficient hiring.