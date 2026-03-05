/**
 * Mock data for standalone frontend development.
 * 3 sample tools with realistic evaluation scores across all 52 sub-features.
 */

import { SubFeatureRating } from '@/lib/types'
import type { EvaluationFramework, Tool, FeedbackData } from '@/lib/types'

// Import the framework JSON directly (Vite handles JSON imports).
import frameworkJson from '../../../data/evaluation-framework.json'

export const mockFramework: EvaluationFramework = frameworkJson as EvaluationFramework

// --- Helper ---
const FF = SubFeatureRating.FULLY_FULFILLS
const PF = SubFeatureRating.PARTIALLY_FULFILLS
const DN = SubFeatureRating.DOES_NOT_FULFILL

function score(subFeatureId: string, rating: SubFeatureRating, rationale: string, evidenceUrl?: string) {
  return {
    subFeatureId,
    rating,
    rationale,
    evidenceUrl,
    lastReviewed: '2026-02-15',
  }
}

// --- Tool A: "CyberShield Auto" — strong overall tool ---
export const toolCyberShield: Tool = {
  id: 1,
  slug: 'cybershield-auto',
  name: 'CyberShield Auto',
  vendor: 'CyberShield Inc.',
  website: 'https://example.com/cybershield',
  logo_url: '',
  description: 'Enterprise-grade automotive cybersecurity management platform with comprehensive TARA and vulnerability management.',
  isSponsor: false,
  sponsor_tier: '',
  status: 'published',
  scores: [
    // Risk Management
    score('rm-automated-tara', FF, 'Full automated TARA generation from system architecture diagrams with attack tree synthesis.'),
    score('rm-threat-asset-mapping', FF, 'Bi-directional traceability matrix with automatic updates on asset changes.'),
    score('rm-residual-risk', PF, 'Residual risk tracked but acceptance workflow requires manual steps.'),
    score('rm-risk-lifecycle', FF, 'Complete lifecycle management including periodic reassessment scheduling.'),
    score('rm-attack-path', FF, 'AI-assisted attack path analysis with visual graph output.'),
    score('rm-reporting', FF, 'Customizable dashboards and PDF/Excel export for stakeholder reporting.'),
    // Vulnerability Management
    score('vm-cve-feed', FF, 'Real-time CVE/NVD feeds with vendor advisory aggregation.'),
    score('vm-prioritization', FF, 'Risk-based prioritization with automotive-specific CVSS adjustments.'),
    score('vm-remediation', PF, 'Remediation workflow present but verification step is manual.'),
    score('vm-sbom-matching', FF, 'Automatic SBOM-to-CVE matching with CycloneDX and SPDX support.'),
    score('vm-risk-linkage', FF, 'Direct linkage from vulnerabilities to TARA risk items.'),
    // Asset Management
    score('am-data-import', FF, 'Bulk import via CSV, JSON, and REST API.'),
    score('am-hierarchy', FF, 'Unlimited nesting depth with drag-and-drop restructuring.'),
    score('am-relationships', PF, 'Relationships defined but no visual dependency graph.'),
    score('am-sbom', FF, 'Full SPDX and CycloneDX import/export.'),
    score('am-hbom', PF, 'Basic HBOM support, limited format options.'),
    score('am-cbom', DN, 'CBOM not yet supported.'),
    score('am-lifecycle', FF, 'Full lifecycle tracking with status transitions and audit trail.'),
    score('am-versioning', FF, 'Version tracking with diff view and rollback.'),
    // Incident Management
    score('im-detection', FF, 'Automated alerting with configurable rules and thresholds.'),
    score('im-logging', FF, 'Structured logging with STIX format export.'),
    score('im-playbooks', PF, 'Predefined playbooks available but customization is limited.'),
    score('im-escalation', FF, 'SLA-based escalation with email and Slack notifications.'),
    score('im-kpis', FF, 'MTTD and MTTR dashboards with historical trends.'),
    score('im-forensics', PF, 'Basic forensics tools, no evidence chain-of-custody.'),
    score('im-external-ir', PF, 'Auto-ISAC feed ingestion but no bidirectional sync.'),
    // Technology Stack
    score('ts-architecture', FF, 'Microservices architecture on Kubernetes.'),
    score('ts-cloud', FF, 'SaaS with on-premise option, AWS and Azure certified.'),
    score('ts-database', FF, 'PostgreSQL with automated backups and point-in-time recovery.'),
    score('ts-security', FF, 'AES-256 encryption, RBAC, full audit logging.'),
    score('ts-scalability', PF, 'Handles large datasets but fleet-scale performance untested.'),
    // API Capabilities
    score('api-availability', FF, 'Comprehensive REST API with OpenAPI 3.0 spec.'),
    score('api-comprehensiveness', PF, '80% of features accessible via API, reporting not yet covered.'),
    score('api-documentation', FF, 'Interactive Swagger docs with code examples in 4 languages.'),
    score('api-auth', FF, 'OAuth 2.0 with API key fallback, rate limiting, and versioning.'),
    // Integrations
    score('int-jira', FF, 'Native bi-directional Jira integration.'),
    score('int-servicenow', PF, 'ServiceNow connector available but requires configuration.'),
    score('int-cicd', FF, 'GitHub Actions, GitLab CI, and Jenkins plugins.'),
    score('int-threat-intel', FF, 'STIX/TAXII and MISP integration.'),
    score('int-soc-siem', PF, 'Splunk integration, Sentinel connector in beta.'),
    score('int-sbom-tools', FF, 'Syft, Grype, and Snyk integrations.'),
    score('int-vcs', FF, 'GitHub and GitLab integrations with code-level tracing.'),
    score('int-communication', FF, 'Slack and Teams notifications.'),
    score('int-other', PF, 'Confluence wiki connector.'),
    // Certifications
    score('cert-iso27001', FF, 'ISO 27001 certified since 2023.'),
    score('cert-soc2', FF, 'SOC 2 Type II certified.'),
    score('cert-iso21434', PF, 'Self-assessed alignment, third-party audit pending.'),
    score('cert-other', PF, 'TISAX assessment completed.'),
    // Sponsor Disclosure
    score('sd-sponsor-status', DN, 'Not an ASRG sponsor.'),
  ],
  created_at: '2025-06-01',
  updated_at: '2026-02-15',
}

// --- Tool B: "AutoSec Platform" — mid-range tool, ASRG sponsor ---
export const toolAutoSec: Tool = {
  id: 2,
  slug: 'autosec-platform',
  name: 'AutoSec Platform',
  vendor: 'AutoSec Solutions',
  website: 'https://example.com/autosec',
  logo_url: '',
  description: 'Cloud-native CSMS platform designed for Tier 1 suppliers with strong vulnerability management focus.',
  isSponsor: true,
  sponsor_tier: 'Gold',
  status: 'published',
  scores: [
    // Risk Management
    score('rm-automated-tara', PF, 'Semi-automated TARA with template-based generation.'),
    score('rm-threat-asset-mapping', FF, 'Full threat-to-asset mapping with visual graph.'),
    score('rm-residual-risk', PF, 'Residual risk displayed but no formal acceptance workflow.'),
    score('rm-risk-lifecycle', PF, 'Basic lifecycle tracking, no automated reassessment.'),
    score('rm-attack-path', DN, 'Attack path analysis not available yet.'),
    score('rm-reporting', FF, 'Executive dashboards with scheduled PDF exports.'),
    // Vulnerability Management
    score('vm-cve-feed', FF, 'NVD and vendor advisory feeds with 15-minute refresh.'),
    score('vm-prioritization', FF, 'CVSS-based with exploitability and asset criticality weighting.'),
    score('vm-remediation', FF, 'Full remediation workflow with SLA tracking.'),
    score('vm-sbom-matching', FF, 'CycloneDX matching with dependency tree analysis.'),
    score('vm-risk-linkage', PF, 'Manual linkage from vulnerabilities to risk register.'),
    // Asset Management
    score('am-data-import', FF, 'CSV, API, and SPDX/CycloneDX import.'),
    score('am-hierarchy', PF, 'Two-level hierarchy only (system → component).'),
    score('am-relationships', PF, 'Basic dependency tracking.'),
    score('am-sbom', FF, 'Full SBOM lifecycle management.'),
    score('am-hbom', DN, 'HBOM not supported.'),
    score('am-cbom', DN, 'CBOM not supported.'),
    score('am-lifecycle', PF, 'Lifecycle statuses without full audit trail.'),
    score('am-versioning', PF, 'Version numbers tracked but no diff capability.'),
    // Incident Management
    score('im-detection', PF, 'Manual incident creation with basic alerting.'),
    score('im-logging', FF, 'Structured incident logs with regulatory templates.'),
    score('im-playbooks', FF, 'Rich playbook library with customizable workflows.'),
    score('im-escalation', FF, 'Multi-level escalation with PagerDuty integration.'),
    score('im-kpis', PF, 'Basic KPI display, no historical trending.'),
    score('im-forensics', DN, 'No forensics capabilities.'),
    score('im-external-ir', PF, 'Auto-ISAC feed ingestion only.'),
    // Technology Stack
    score('ts-architecture', PF, 'Modular monolith architecture.'),
    score('ts-cloud', FF, 'Multi-cloud SaaS (AWS, GCP).'),
    score('ts-database', PF, 'MySQL with daily backups.'),
    score('ts-security', FF, 'TLS 1.3, AES-256, SOC-monitored infrastructure.'),
    score('ts-scalability', FF, 'Auto-scaling for 100K+ assets tested.'),
    // API Capabilities
    score('api-availability', FF, 'REST API available for all modules.'),
    score('api-comprehensiveness', FF, 'Full CRUD for all data via API.'),
    score('api-documentation', PF, 'API docs available but missing code examples.'),
    score('api-auth', FF, 'OAuth 2.0 and API keys with rate limiting.'),
    // Integrations
    score('int-jira', FF, 'Native Jira Cloud integration.'),
    score('int-servicenow', FF, 'Full ServiceNow ITSM integration.'),
    score('int-cicd', PF, 'Jenkins plugin only.'),
    score('int-threat-intel', PF, 'STIX/TAXII ingestion, no MISP support.'),
    score('int-soc-siem', FF, 'Splunk, Sentinel, and QRadar integrations.'),
    score('int-sbom-tools', PF, 'Grype integration, no Syft or Snyk.'),
    score('int-vcs', PF, 'GitHub integration, no GitLab or Bitbucket.'),
    score('int-communication', FF, 'Slack, Teams, and email integrations.'),
    score('int-other', DN, 'No additional integrations.'),
    // Certifications
    score('cert-iso27001', FF, 'ISO 27001 certified.'),
    score('cert-soc2', PF, 'SOC 2 Type I only.'),
    score('cert-iso21434', DN, 'No ISO/SAE 21434 assessment.'),
    score('cert-other', FF, 'TISAX AL3 and ISO 9001 certified.'),
    // Sponsor Disclosure
    score('sd-sponsor-status', FF, 'Gold ASRG sponsor since 2025.'),
  ],
  created_at: '2025-08-01',
  updated_at: '2026-01-20',
}

// --- Tool C: "VulnTrack OEM" — newer tool, weaker but promising ---
export const toolVulnTrack: Tool = {
  id: 3,
  slug: 'vulntrack-oem',
  name: 'VulnTrack OEM',
  vendor: 'VulnTrack GmbH',
  website: 'https://example.com/vulntrack',
  logo_url: '',
  description: 'Emerging CSMS platform for OEM-level vulnerability and asset management with growing feature set.',
  isSponsor: false,
  sponsor_tier: '',
  status: 'published',
  scores: [
    // Risk Management
    score('rm-automated-tara', DN, 'TARA generation not available; manual risk register only.'),
    score('rm-threat-asset-mapping', PF, 'Basic mapping via tagging, no graph view.'),
    score('rm-residual-risk', PF, 'Residual risk field present per risk item.'),
    score('rm-risk-lifecycle', PF, 'Risk statuses tracked but no reassessment triggers.'),
    score('rm-attack-path', DN, 'Not available.'),
    score('rm-reporting', PF, 'Basic CSV export and table view.'),
    // Vulnerability Management
    score('vm-cve-feed', FF, 'NVD feed with hourly sync.'),
    score('vm-prioritization', PF, 'CVSS scoring shown but no asset-aware prioritization.'),
    score('vm-remediation', PF, 'Basic status tracking (open/in-progress/resolved).'),
    score('vm-sbom-matching', PF, 'CycloneDX matching only, no SPDX.'),
    score('vm-risk-linkage', DN, 'No linkage to risk management module.'),
    // Asset Management
    score('am-data-import', PF, 'CSV import and manual entry.'),
    score('am-hierarchy', FF, 'Flexible tree structure for asset organization.'),
    score('am-relationships', DN, 'No relationship mapping between assets.'),
    score('am-sbom', PF, 'CycloneDX import, no SPDX.'),
    score('am-hbom', DN, 'Not supported.'),
    score('am-cbom', DN, 'Not supported.'),
    score('am-lifecycle', PF, 'Basic status field per asset.'),
    score('am-versioning', DN, 'No version tracking.'),
    // Incident Management
    score('im-detection', DN, 'No automated detection.'),
    score('im-logging', PF, 'Manual incident logging with basic fields.'),
    score('im-playbooks', DN, 'No playbook support.'),
    score('im-escalation', DN, 'No escalation workflows.'),
    score('im-kpis', DN, 'No KPI tracking.'),
    score('im-forensics', DN, 'No forensics capabilities.'),
    score('im-external-ir', DN, 'No external IR integration.'),
    // Technology Stack
    score('ts-architecture', PF, 'Monolithic application, refactor planned.'),
    score('ts-cloud', PF, 'SaaS only, single cloud provider.'),
    score('ts-database', PF, 'PostgreSQL, basic backup strategy.'),
    score('ts-security', PF, 'TLS and basic RBAC, no audit logging.'),
    score('ts-scalability', DN, 'Untested beyond small deployments.'),
    // API Capabilities
    score('api-availability', PF, 'REST API for core features only.'),
    score('api-comprehensiveness', DN, 'Read-only API, no write operations.'),
    score('api-documentation', DN, 'Minimal documentation, no interactive docs.'),
    score('api-auth', PF, 'API key authentication, no OAuth.'),
    // Integrations
    score('int-jira', PF, 'Basic Jira issue creation via webhook.'),
    score('int-servicenow', DN, 'Not available.'),
    score('int-cicd', DN, 'Not available.'),
    score('int-threat-intel', DN, 'Not available.'),
    score('int-soc-siem', DN, 'Not available.'),
    score('int-sbom-tools', PF, 'Grype CLI integration.'),
    score('int-vcs', DN, 'Not available.'),
    score('int-communication', PF, 'Email notifications only.'),
    score('int-other', DN, 'No additional integrations.'),
    // Certifications
    score('cert-iso27001', DN, 'In progress, expected Q3 2026.'),
    score('cert-soc2', DN, 'Not pursued.'),
    score('cert-iso21434', DN, 'No assessment.'),
    score('cert-other', DN, 'No certifications.'),
    // Sponsor Disclosure
    score('sd-sponsor-status', DN, 'Not an ASRG sponsor.'),
  ],
  created_at: '2025-11-01',
  updated_at: '2026-02-01',
}

export const mockTools: Tool[] = [toolCyberShield, toolAutoSec, toolVulnTrack]

// --- Mock feedback for a sample cell ---
export const mockFeedback: FeedbackData = {
  votes: { agree: 12, disagree: 3, userVote: null },
  comments: [
    {
      id: 1,
      userId: 10,
      userName: 'Alice Engineer',
      avatar: '',
      body: 'This matches our experience deploying CyberShield. The TARA generation saves significant manual effort.',
      parentId: null,
      isDeleted: false,
      createdAt: '2026-01-15T10:30:00Z',
      updatedAt: null,
    },
    {
      id: 2,
      userId: 20,
      userName: 'Bob Security',
      avatar: '',
      body: 'Agreed, though the attack tree synthesis could use more granularity for ECU-level threats.',
      parentId: 1,
      isDeleted: false,
      createdAt: '2026-01-16T14:20:00Z',
      updatedAt: null,
    },
    {
      id: 3,
      userId: 30,
      userName: 'Charlie OEM',
      avatar: '',
      body: 'We evaluated this for our Tier 1 supplier requirements. Solid TARA automation overall.',
      parentId: null,
      isDeleted: false,
      createdAt: '2026-02-01T09:00:00Z',
      updatedAt: null,
    },
  ],
}
