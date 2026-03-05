# Deployment Guide

## Prerequisites

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- **JetEngine** plugin (installed and activated)
- **Elementor Pro** plugin (installed and activated)
- **JetSmartFilters** plugin (for listing page filtering/sorting)
- **JetReviews** plugin (for community tool reviews)

---

## Automated Deployment (GitHub Actions)

Every push to `main` triggers a GitHub Actions workflow that deploys the plugin to your WordPress server via FTP. No build step is needed — the plugin is pure PHP.

### 1. Configure GitHub Secrets

Go to **Settings > Secrets and variables > Actions** in your GitHub repository and add these 4 secrets:

| Secret | Description | Example |
|---|---|---|
| `FTP_SERVER` | FTP server hostname | `ftp.asrg.io` |
| `FTP_USERNAME` | FTP username | `deploy@asrg.io` |
| `FTP_PASSWORD` | FTP password | *(your password)* |
| `FTP_REMOTE_PATH` | Remote path to the plugin directory | `wp-content/plugins/asrg-csms-evaluation/` |

> **Note:** `FTP_REMOTE_PATH` must end with a trailing slash.

### 2. Push to `main`

Once secrets are configured, any push to `main` will automatically upload plugin files to your WordPress server.

### 3. Monitor deployments

Check the **Actions** tab in GitHub to monitor deploy status.

---

## Manual Deployment

Upload the `asrg-csms-evaluation/` directory to `wp-content/plugins/` on your server:

```
asrg-csms-evaluation/
├── asrg-csms-evaluation.php    # Plugin entry point
├── includes/                   # PHP classes
├── assets/                     # CSS and JS
├── data/                       # Evaluation framework JSON
└── templates/                  # Elementor template exports
```

---

## WordPress Setup

### First-time setup

1. **Install required plugins** (if not already installed)
   - JetEngine, Elementor Pro, JetSmartFilters, JetReviews

2. **Activate the plugin** in WP Admin > Plugins > "ASRG CSMS Evaluation"
   - This creates the `csms_feedback_votes` table, registers the `csms_tool` CPT, custom roles, and JetEngine meta fields

3. **Configure JetReviews**
   - Attach JetReviews to the `csms_tool` post type for community reviews

4. **Create Elementor templates**

   **Comparison Listing Page:**
   - Create a new page (e.g., "CSMS Tool Evaluation")
   - Edit with Elementor
   - Add a JetEngine Listing Grid widget displaying `csms_tool` posts
   - Add JetSmartFilters for search, sponsor filter, and sort by score

   **Single Tool Template:**
   - Go to Elementor > Templates > Theme Builder > Single
   - Create a new template for `csms_tool` post type
   - Add hero section with dynamic fields (title, `vendor_name`, `website_url`, featured image)
   - Add `[csms_tool_scores]` shortcode widget for the scoring grid
   - Add JetReviews widget for community reviews

   **Methodology Page:**
   - Create a new page
   - Add `[csms_methodology]` shortcode to auto-generate the framework table

5. **Assign user roles** (optional)
   - `csms_vendor` — companies who can submit/update their tool evaluations
   - `csms_editor` — ASRG team members who approve vendor submissions
   - Assign roles in WP Admin > Users > Edit User > Role

### Available Shortcodes

| Shortcode | Description |
|---|---|
| `[csms_tool_scores]` | Full scoring grid for the current tool (use on single tool template) |
| `[csms_vote tool_id=X sub_feature_id=Y]` | Inline agree/disagree vote buttons |
| `[csms_score_bar score=X]` | Score progress bar |
| `[csms_methodology]` | Auto-generated evaluation framework table |

### Custom Roles

| Role | Capabilities |
|---|---|
| **Subscriber** | View tools + vote |
| **csms_vendor** | All above + create/edit own tools |
| **csms_editor** | All above + edit/publish all tools, moderate |
| **Administrator** | Full control |

---

## Data Migration (from v1.0)

If upgrading from v1.0 (custom tables + React frontend), run the migration:

```bash
wp eval 'require_once ASRG_CSMS_PLUGIN_DIR . "includes/migrations/class-migrate-to-cpt.php"; ASRG_CSMS_Migrate_To_CPT::run();'
```

After verifying the migration:

```bash
wp eval 'require_once ASRG_CSMS_PLUGIN_DIR . "includes/migrations/class-migrate-to-cpt.php"; ASRG_CSMS_Migrate_To_CPT::drop_old_tables();'
```

---

## Updating

Just push to `main`. GitHub Actions handles the rest. No build step needed.

---

## Troubleshooting

### Plugin not appearing in WP Admin
- Verify files are in `wp-content/plugins/asrg-csms-evaluation/`
- Check that `asrg-csms-evaluation.php` exists at the root

### Meta fields not showing in tool edit screen
- Ensure JetEngine is installed and activated
- Go to WP Admin > CSMS Tools > Add New — fields should appear in 9 groups
- If fields are missing, bump the version in `data/evaluation-framework.json` to trigger re-registration

### Scores not computing
- Verify the scoring cron is scheduled: `wp cron event list | grep csms`
- Manually trigger: `wp cron event run csms_recompute_scores`
- Check that at least one sub-feature has a rating set

### FTP deploy failing in GitHub Actions
- Verify all 4 secrets are configured correctly
- Check that `FTP_REMOTE_PATH` ends with a trailing slash
- Some hosts require passive FTP — the deploy action uses passive mode by default
