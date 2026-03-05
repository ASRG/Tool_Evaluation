# ASRG CSMS Tool Evaluation Platform — Requirements

## 1. Project Overview

### 1.1 Purpose
A public-facing, community-driven evaluation framework and comparison table for automotive Cybersecurity Management System (CSMS) tools, anchored in ISO/SAE 21434.

### 1.2 Hosting
Integrated directly into the existing ASRG WordPress site (self-hosted) as a WordPress plugin with a React-based interactive frontend.

### 1.3 Stakeholders
- **ASRG** — Project owner, editorial oversight
- **Tool Vendors** — Self-service evaluation submission
- **Community Members** — Feedback, voting, comments
- **Public** — Read-only access to all evaluation data

---

## 2. Functional Requirements

### 2.1 Comparison Table (Public)

- **FR-2.1.1**: Display an interactive comparison table of all evaluated CSMS tools
- **FR-2.1.2**: Table organized by 9 evaluation categories, each expandable to show sub-features
- **FR-2.1.3**: Each sub-feature shows a qualitative rating per tool: Fully Fulfills / Partially Fulfills / Does Not Fulfill
- **FR-2.1.4**: Users can click into any score cell to view the detailed rationale and evidence
- **FR-2.1.5**: Category rows display an aggregated quantitative score (0-100) per tool
- **FR-2.1.6**: Overall tool score displayed in the column header (weighted sum of category scores)
- **FR-2.1.7**: Table supports filtering by category
- **FR-2.1.8**: Table supports sorting by overall score or category scores
- **FR-2.1.9**: Table supports searching for tools by name
- **FR-2.1.10**: Responsive design — card-based layout on mobile (below 768px)

### 2.2 Scoring Methodology

- **FR-2.2.1**: Sub-features rated as Fully Fulfills (1.0) / Partially Fulfills (0.5) / Does Not Fulfill (0.0)
- **FR-2.2.2**: Each rating accompanied by a written rationale explaining how the tool implements (or fails to implement) the sub-feature
- **FR-2.2.3**: Scores roll up automatically: sub-features (weighted) -> category score (0-100) -> overall score (0-100)
- **FR-2.2.4**: No false precision at the sub-feature level — qualitative ratings only
- **FR-2.2.5**: Community feedback influences scores by a maximum of +/-15% per sub-feature
- **FR-2.2.6**: Community influence uses Wilson score lower bound with minimum 5-vote threshold
- **FR-2.2.7**: Both editorial and community-adjusted scores are displayed for transparency
- **FR-2.2.8**: Methodology page explains the complete scoring system with worked examples

### 2.3 Community Feedback

- **FR-2.3.1**: Authenticated users can vote agree/disagree on any tool x sub-feature score
- **FR-2.3.2**: One vote per user per tool x sub-feature (changeable)
- **FR-2.3.3**: Authenticated users can post comments on any tool x sub-feature
- **FR-2.3.4**: Comments support threaded replies
- **FR-2.3.5**: Users can delete their own comments (soft delete)
- **FR-2.3.6**: Vote and comment counts displayed on score cells
- **FR-2.3.7**: Anonymous users can read all feedback but cannot interact
- **FR-2.3.8**: Rate limiting: 30 votes/min, 10 comments/min per user

### 2.4 Vendor Self-Service

- **FR-2.4.1**: Users with "Vendor" role can submit a new tool evaluation
- **FR-2.4.2**: Vendor fills a structured form: for each sub-feature, selects rating and writes rationale
- **FR-2.4.3**: Vendor provides tool metadata: name, vendor, website, logo, description, certifications
- **FR-2.4.4**: Vendor submissions have "pending review" status until approved by an editor
- **FR-2.4.5**: Vendors can update their existing tool evaluation (creates new pending version)
- **FR-2.4.6**: Vendors can view the status of their submissions (draft, pending, published, archived)
- **FR-2.4.7**: Only vendors can edit their own tool data (not other vendors' tools)

### 2.5 Editorial Workflow

- **FR-2.5.1**: Users with "CSMS Editor" role can approve or reject vendor submissions
- **FR-2.5.2**: Editors can override any score with their own rating and rationale
- **FR-2.5.3**: Editors can provide rejection feedback to vendors
- **FR-2.5.4**: All editorial changes are tracked with timestamps and user attribution

### 2.6 Authentication & Authorization

- **FR-2.6.1**: Uses existing WordPress user system (no separate auth)
- **FR-2.6.2**: Custom WordPress roles: "Vendor", "CSMS Editor"
- **FR-2.6.3**: Anonymous: view all public data
- **FR-2.6.4**: WordPress Subscriber: vote and comment
- **FR-2.6.5**: Vendor: submit and update own tool evaluations
- **FR-2.6.6**: CSMS Editor: approve/reject submissions, override scores
- **FR-2.6.7**: WordPress Administrator: full control

### 2.7 Tool Profile Pages

- **FR-2.7.1**: Each tool has a dedicated profile view accessible from the comparison table
- **FR-2.7.2**: Profile displays: vendor info, description, certifications, sponsor status
- **FR-2.7.3**: Visual scorecard (bar chart or radar chart) showing category-level scores
- **FR-2.7.4**: Full list of sub-feature scores with rationale
- **FR-2.7.5**: Sponsor disclosure badge displayed prominently if the vendor is an ASRG sponsor

---

## 3. Evaluation Categories

### 3.1 Risk Management (weight: 0.15)
Sub-features:
- Automated TARA generation
- Threat-to-asset mapping (bi-directional traceability)
- Residual risk tracking with acceptance workflows
- Risk lifecycle management (identification through monitoring)
- Attack path analysis
- Risk reporting and dashboards

### 3.2 Vulnerability Management (weight: 0.15)
Sub-features:
- CVE/NVD feed integration (automated ingestion)
- Vulnerability prioritization (CVSS, asset criticality, exploitability)
- Remediation tracking (discovery through verification)
- SBOM-based vulnerability matching
- Vulnerability-to-risk linkage

### 3.3 Asset Management (weight: 0.15)
Sub-features:
- Data import capabilities (bulk, API, manual)
- Hierarchical asset structuring
- Asset relationship mapping
- SBOM management
- HBOM management
- CBOM management
- Asset lifecycle tracking
- Version management

### 3.4 Incident Management (weight: 0.15)
Sub-features:
- Incident detection and alerting
- Logging and reporting
- Playbook management
- Escalation workflows
- KPI tracking (MTTD, MTTR)
- Forensics and root cause analysis
- Integration with external IR platforms

### 3.5 Technology Stack (weight: 0.10)
Sub-features:
- Underlying architecture (monolith, microservices, etc.)
- Cloud infrastructure and deployment model
- Database technology
- Security architecture (encryption, access control)
- Scalability and performance

### 3.6 API Capabilities (weight: 0.10)
Sub-features:
- API availability (REST, GraphQL, etc.)
- Comprehensiveness of data access via API
- API documentation quality
- Authentication mechanisms (OAuth, API keys, etc.)
- Rate limiting and versioning

### 3.7 Integrations (weight: 0.10)
Checklist-style evaluation:
- Jira
- ServiceNow
- CI/CD pipelines (Jenkins, GitLab CI, GitHub Actions)
- Threat intelligence platforms
- SOC/SIEM tools
- SBOM tools
- Version control systems
- Communication tools (Slack, Teams)
- Other (vendor-specified)

### 3.8 Certifications (weight: 0.10)
Sub-features:
- ISO 27001
- SOC 2
- ISO/SAE 21434 compliance certification
- Other relevant certifications (vendor-specified)

### 3.9 Sponsor Disclosure (weight: 0.00 — no score impact)
- ASRG sponsor status (yes/no)
- Sponsor tier (if applicable)
- Displayed for full transparency

---

## 4. Non-Functional Requirements

### 4.1 Performance
- **NFR-4.1.1**: Comparison table loads within 2 seconds on desktop
- **NFR-4.1.2**: Score computation completes within 500ms
- **NFR-4.1.3**: Feedback operations respond within 1 second

### 4.2 Accessibility
- **NFR-4.2.1**: WCAG 2.1 AA compliance
- **NFR-4.2.2**: Full keyboard navigation of comparison table
- **NFR-4.2.3**: Screen reader compatible (ARIA labels)

### 4.3 Security
- **NFR-4.3.1**: All REST API mutations require WordPress nonce validation
- **NFR-4.3.2**: Role-based access control on all endpoints
- **NFR-4.3.3**: Input sanitization on all user-submitted content
- **NFR-4.3.4**: XSS protection on comment rendering
- **NFR-4.3.5**: Rate limiting on feedback endpoints to prevent abuse

### 4.4 Visual Design
- **NFR-4.4.1**: Follows ASRG brand identity (colors, fonts, layout)
- **NFR-4.4.2**: Roboto font family (400/700 weights)
- **NFR-4.4.3**: Color palette: black (#000), white (#FFF), red (#E71E25), purple (#AAA4EF)
- **NFR-4.4.4**: Clean, minimal aesthetic with generous whitespace
- **NFR-4.4.5**: Maximum content width 1200px

### 4.5 Compatibility
- **NFR-4.5.1**: Works with WordPress 6.x (self-hosted)
- **NFR-4.5.2**: Compatible with Elementor page builder
- **NFR-4.5.3**: Supports modern browsers (Chrome, Firefox, Safari, Edge — last 2 versions)
- **NFR-4.5.4**: Responsive: desktop (1200px+), tablet (768-1199px), mobile (<768px)

### 4.6 Maintainability
- **NFR-4.6.1**: Evaluation framework (categories, sub-features, weights) defined in a single JSON file
- **NFR-4.6.2**: Adding a new category or sub-feature requires only JSON changes
- **NFR-4.6.3**: Adding a new tool requires only vendor form submission or manual DB entry

---

## 5. Out of Scope

- Pricing information for tools
- Automotive-specific technical protocols (CAN bus, UDS, OTA mechanisms)
- Automated tool testing or verification
- Real-time integration with vendor tool APIs
- Multi-language support (English only for v1)

---

## 6. Technical Architecture

### 6.1 WordPress Plugin
- PHP 8.0+ WordPress plugin
- Custom database tables for tools, scores, votes, comments
- REST API endpoints under `/wp-json/csms/v1/`
- Custom roles (vendor, csms_editor) registered on activation
- Shortcode `[csms_evaluation]` loads React app

### 6.2 React Frontend
- Built with Vite + React 18 + TypeScript
- Tailwind CSS with ASRG brand tokens
- Bundled into plugin's `/dist` directory
- Communicates with WordPress REST API (nonce-based auth)
- Client-side scoring engine (mirrors PHP server-side computation)

### 6.3 Data Storage
- Evaluation framework: JSON file shipped with plugin
- Tool evaluations: WordPress custom database tables
- Community feedback: WordPress custom database tables
- User auth: WordPress native user system

---

## 7. Acceptance Criteria

1. Anonymous users can view the full comparison table with all tools and scores
2. Logged-in users can vote agree/disagree and post comments on any score
3. Vendors can submit and update their own tool evaluations via a web form
4. Editors can approve/reject vendor submissions and override any score
5. Scores correctly roll up from sub-features to categories to overall
6. Community feedback visibly influences scores (within bounded limits)
7. Sponsor disclosure is clearly visible for sponsored tools
8. UI matches ASRG visual identity
9. Table is usable on mobile devices
10. Plugin installs and activates without errors on WordPress 6.x
