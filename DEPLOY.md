# CSMS Tool Evaluation Platform — Deployment Guide

Complete step-by-step instructions for deploying and configuring the ASRG CSMS Tool Evaluation platform on WordPress with JetEngine, Elementor Pro, and JetReviews.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Server Deployment](#2-server-deployment)
3. [WordPress Plugin Setup](#3-wordpress-plugin-setup)
4. [Verify Meta Fields & Scoring](#4-verify-meta-fields--scoring)
5. [Create a Test Tool Entry](#5-create-a-test-tool-entry)
6. [Build the Single Tool Template (Elementor)](#6-build-the-single-tool-template-elementor)
7. [Build the Comparison Listing Page (Elementor)](#7-build-the-comparison-listing-page-elementor)
8. [Build the Methodology Page](#8-build-the-methodology-page)
9. [Configure JetReviews](#9-configure-jetreviews)
10. [Configure JetSmartFilters](#10-configure-jetsmartfilters)
11. [User Roles & Permissions](#11-user-roles--permissions)
12. [Vendor Submission Workflow](#12-vendor-submission-workflow)
13. [Data Migration (from v1.0)](#13-data-migration-from-v10)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. Prerequisites

### Required Plugins (install and activate BEFORE activating CSMS)

| Plugin | Purpose | Where to get |
|---|---|---|
| **JetEngine** | Custom Post Types & meta fields management | Crocoblock |
| **Elementor Pro** | Theme Builder for dynamic templates | elementor.com |
| **JetSmartFilters** | Search, filter & sort on listing pages | Crocoblock |
| **JetReviews** | Community reviews/ratings on tool pages | Crocoblock |

### Server Requirements

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

---

## 2. Server Deployment

### Option A: Automated via GitHub Actions (recommended)

Every push to `main` on [github.com/ASRG/Tool_Evaluation](https://github.com/ASRG/Tool_Evaluation) triggers FTP deployment.

**Configure GitHub Secrets** (one-time, in Settings > Secrets and variables > Actions):

| Secret | Description | Example |
|---|---|---|
| `FTP_SERVER` | FTP server hostname | `ftp.asrg.io` |
| `FTP_USERNAME` | FTP username | `deploy@asrg.io` |
| `FTP_PASSWORD` | FTP password | *(your password)* |
| `FTP_REMOTE_PATH` | Remote plugin directory (must end with `/`) | `wp-content/plugins/asrg-csms-evaluation/` |

Once set, any push to `main` uploads the plugin. Monitor status at the **Actions** tab on GitHub.

### Option B: Manual Upload

Upload the `asrg-csms-evaluation/` directory to `wp-content/plugins/` on your server via FTP/SFTP:

```
wp-content/plugins/asrg-csms-evaluation/
  asrg-csms-evaluation.php    # Plugin entry point
  includes/                   # PHP classes
  assets/css/                 # Public styles
  assets/js/                  # Vote button JS
  data/                       # evaluation-framework.json
```

---

## 3. WordPress Plugin Setup

### Step 1: Activate Required Plugins

Go to **WP Admin > Plugins** and activate in this order:

1. JetEngine
2. Elementor Pro
3. JetSmartFilters
4. JetReviews
5. **ASRG CSMS Evaluation** (last)

> **Why this order?** The CSMS plugin checks for JetEngine on activation. If JetEngine isn't active yet, the meta field registration will be deferred until the next page load.

### Step 2: Verify Activation

After activating the CSMS plugin, you should see:

- **"CSMS Tools"** menu item in the WP Admin sidebar (shield icon)
- Clicking it shows an empty listing with "No tools found"

What happened behind the scenes on activation:
- Created the `wp_csms_feedback_votes` database table
- Registered the `csms_tool` Custom Post Type
- Created custom roles (`csms_vendor`, `csms_editor`)
- Granted administrators all CSMS capabilities
- Granted subscribers the ability to vote

### Step 3: Flush Permalinks

Go to **WP Admin > Settings > Permalinks** and click **Save Changes** (no changes needed, just save). This ensures the `/csms-tools/` URL pattern works.

---

## 4. Verify Meta Fields & Scoring

### Check JetEngine Meta Boxes

1. Go to **WP Admin > JetEngine > Meta Boxes**
2. You should see **11 meta box groups**, all prefixed with "csms-":

| Meta Box | Fields | Purpose |
|---|---|---|
| Tool Information | 4 | vendor_name, website_url, is_sponsor, sponsor_tier |
| Computed Scores | 20 | _overall_score, _overall_editorial_score, + 9 categories x 2 |
| Risk Management Scores | 18 | 6 sub-features x 3 (rating, rationale, evidence_url) |
| Concept Phase Scores | 15 | 5 sub-features x 3 |
| Product Dev Scores | 21 | 7 sub-features x 3 |
| Cybersecurity Validation Scores | 18 | 6 sub-features x 3 |
| Production & Operations Scores | 18 | 6 sub-features x 3 |
| Incident Response Scores | 15 | 5 sub-features x 3 |
| Updates & Patches Scores | 12 | 4 sub-features x 3 |
| Decommissioning Scores | 9 | 3 sub-features x 3 |
| Sponsor Disclosure Scores | 30 | 10 sub-features x 3 |

**Total: 156 evaluation fields + 4 tool info + 20 computed scores = 180 fields**

> **If meta boxes are missing:** Ensure JetEngine is active, then go to any admin page. The plugin auto-registers fields on the first page load. If still missing, bump the `version` in `data/evaluation-framework.json` to force re-registration.

### Check Scoring Cron

The plugin schedules a WP-Cron job that recomputes all tool scores every 15 minutes (to pick up community vote changes).

**Verify via WP-CLI:**
```bash
wp cron event list | grep csms
```

Expected output:
```
csms_recompute_scores   csms_fifteen_minutes   2026-03-05 13:15:00
```

**Manually trigger a score recompute:**
```bash
wp cron event run csms_recompute_scores
```

---

## 5. Create a Test Tool Entry

Before building templates, create a test tool so you have content to preview:

1. Go to **WP Admin > CSMS Tools > Add New Tool**
2. Fill in:
   - **Title:** "Test Security Tool"
   - **Editor content:** Brief description of the tool
   - **Featured Image:** Upload a logo (used as tool thumbnail)
3. Scroll down to the **Tool Information** meta box:
   - **Vendor Name:** "Test Vendor"
   - **Website URL:** "https://example.com"
   - **ASRG Sponsor:** Off
4. Scroll to **Risk Management Scores** (or any category):
   - For any sub-feature, set the **Rating** dropdown to "Fully Fulfills", "Partially Fulfills", or "Does Not Fulfill"
   - Optionally add a **Rationale** and **Evidence URL**
5. Click **Publish**

After publishing, the scoring engine automatically computes scores and stores them in the "Computed Scores" meta box. Go back to the editor and check — you should see numbers populated in the `_overall_score` and category score fields.

---

## 6. Build the Single Tool Template (Elementor)

This template controls how individual tool pages look at `/csms-tools/tool-name/`.

### Step 1: Create the Template

1. Go to **WP Admin > Elementor > Templates > Theme Builder**
2. Click **Add New**
3. Choose **Single Post** template type
4. Name it "CSMS Tool Single"
5. Click **Create Template**

### Step 2: Set the Display Condition

1. After creating, click the gear icon (bottom left) or go to **Display Conditions**
2. Set: **Include > Post Type is > CSMS Tool**
3. This makes the template apply to all `csms_tool` posts

### Step 3: Build the Layout

Here's the recommended structure (top to bottom):

#### Hero Section
Use a Section with a dark background (`#000` or `#32373C`):

- **Post Title** widget (Dynamic Tag: Title) — white text, Roboto 700, ~32px
- **Dynamic Field** (JetEngine) — meta field `vendor_name` — white text, smaller
- **Dynamic Field** (JetEngine) — meta field `website_url` — styled as a button/link
- **Post Featured Image** widget — tool logo, constrained width (~200px)
- **Score display:** Add a **Shortcode** widget with `[csms_score_bar score=""]` — but it's easier to use a JetEngine Dynamic Field for `_overall_score` and style it with CSS, or see the shortcode method below.

#### Overall Score Bar (via Shortcode)

Add a **Shortcode** widget:
```
[csms_score_bar score="{{_overall_score}}"]
```

**Alternative (more reliable):** Use a JetEngine **Dynamic Field** widget set to the `_overall_score` meta field, and style it as a progress bar with CSS classes.

#### Scoring Grid

Add a **Shortcode** widget:
```
[csms_tool_scores]
```

This renders the full 9-category evaluation grid with:
- Category headers with weight percentages and score bars
- Every sub-feature with its rating badge (FF/PF/DNF), rationale, and evidence link
- Inline agree/disagree vote buttons for logged-in users

#### JetReviews Widget

Add the **JetReviews** widget below the scoring grid for community reviews (see [Section 9](#9-configure-jetreviews)).

#### Sponsor Disclosure

If the tool is an ASRG sponsor, you may want a conditional visibility section. Use Elementor's **Dynamic Visibility** (or JetEngine's Dynamic Visibility) set to show only when `is_sponsor` meta = true:
```html
<div class="csms-sponsor-notice">
  ⚠️ This vendor is an ASRG sponsor. Sponsor disclosure scores are displayed separately
  and do not affect the overall score.
</div>
```

### Step 4: Preview & Publish

1. In the Elementor editor bottom bar, click the eye icon to **Preview**
2. Choose your test tool post from the dropdown
3. Verify the scoring grid renders, vote buttons appear, and scores display
4. Click **Publish**

---

## 7. Build the Comparison Listing Page (Elementor)

This is the main page where visitors compare all tools side by side.

### Step 1: Create a New Page

1. Go to **WP Admin > Pages > Add New**
2. Title: "CSMS Tool Evaluation" (or whatever you prefer)
3. Click **Edit with Elementor**

### Step 2: Add Page Header

Add a Section with the page title, intro text, and link to the Methodology page:

```
ASRG CSMS Tool Evaluation
Community-driven evaluation of cybersecurity management system tools
based on ISO/SAE 21434.

[View Methodology →]
```

### Step 3: Add JetSmartFilters (optional, recommended)

Before the listing grid, add filter widgets (see [Section 10](#10-configure-jetsmartfilters)):

- **Search filter** — search by tool name
- **Checkbox filter** — filter by sponsor status
- **Sorting filter** — sort by overall score, name, date

### Step 4: Create a JetEngine Listing Template

1. Go to **WP Admin > JetEngine > Listings**
2. Click **Add New**
3. Settings:
   - **Listing source:** Posts
   - **From post type:** CSMS Tool
   - **Name:** "CSMS Tool Card"
4. Click **Create**

In the Listing Editor, build a card layout for each tool:

| Element | Source | Notes |
|---|---|---|
| Featured Image | Dynamic — Post Thumbnail | Tool logo, fixed height ~80px |
| Post Title | Dynamic — Post Title | Link to single tool page |
| Vendor Name | JetEngine Dynamic Field — `vendor_name` | Smaller text |
| Overall Score | JetEngine Dynamic Field — `_overall_score` | Style as a badge or progress bar |
| Sponsor Badge | Conditional — show if `is_sponsor` is true | Small "Sponsor" label |

Optionally add category score summaries using dynamic fields for `_cat_*_score` meta.

5. **Publish** the listing template.

### Step 5: Add the Listing Grid to the Page

Back in the main listing page (Elementor editor):

1. Add a **JetEngine Listing Grid** widget
2. Set:
   - **Listing:** "CSMS Tool Card" (the template you just created)
   - **Post Type:** CSMS Tool
   - **Posts per page:** 20 (or -1 for all)
   - **Columns:** 1 (table-like), 2, or 3 (card grid)
3. Under **Query:** ensure Post Status = Published

### Step 6: Publish

Publish the page, then set it as a menu item in **Appearance > Menus**.

---

## 8. Build the Methodology Page

1. Go to **WP Admin > Pages > Add New**
2. Title: "Evaluation Methodology"
3. Edit with Elementor (or just use the block editor)
4. Add a **Shortcode** widget/block:

```
[csms_methodology]
```

This auto-generates:
- **Rating System table** — Fully Fulfills (1.0), Partially Fulfills (0.5), Does Not Fulfill (0.0) with color badges
- **Evaluation Categories table** — all 9 categories with weights, sub-feature counts, and descriptions
- **Scoring Formula** — how sub-feature → category → overall scores are computed
- **Community Influence** — Wilson score lower bound explanation, 5-vote minimum, ±15% max adjustment

5. Add any additional introductory text about ISO/SAE 21434 alignment above the shortcode.
6. Publish the page.

---

## 9. Configure JetReviews

JetReviews handles general tool reviews (star ratings + written reviews). The custom voting system handles per-sub-feature agree/disagree votes separately.

### Step 1: Enable for CSMS Tools

1. Go to **WP Admin > JetReviews > Settings** (or Crocoblock > JetReviews)
2. Under **Post Types**, enable reviews for **CSMS Tool**
3. Configure:
   - **Review structure:** Add review criteria relevant to your evaluation (e.g., "Ease of Use", "Documentation Quality", "Support Responsiveness")
   - **Who can review:** Logged-in users (recommended)
   - **Approval required:** Yes (recommended — editors approve before publishing)

### Step 2: Add to Single Tool Template

If you haven't already:

1. Open the "CSMS Tool Single" Elementor template (Theme Builder > Single)
2. Add the **JetReviews** widget below the `[csms_tool_scores]` shortcode
3. Configure the widget appearance to match ASRG branding (black/white/red)

### Step 3: Style Reviews

Add custom CSS if needed (Elementor > Custom CSS, or in your child theme):

```css
/* Match JetReviews to ASRG branding */
.jet-reviews .jet-reviews__item-header {
    font-family: 'Roboto', sans-serif;
}
.jet-reviews .jet-reviews__submit-review-btn {
    background-color: #E71E25;
    color: #fff;
    border: none;
}
```

---

## 10. Configure JetSmartFilters

Filters let visitors search, sort, and filter the tool listing page.

### Step 1: Create Filters

Go to **WP Admin > Smart Filters**:

#### Search Filter
1. Add New > Filter Type: **Search**
2. Name: "Search Tools"
3. Search by: Title (and optionally Content)
4. Save

#### Sponsor Filter
1. Add New > Filter Type: **Checkboxes** or **Select**
2. Name: "Sponsor Status"
3. Data Source: **Meta Data**
4. Meta Key: `is_sponsor`
5. Options: Yes/No
6. Save

#### Sort by Score
1. Add New > Filter Type: **Sorting**
2. Name: "Sort By"
3. Sorting options:
   - "Highest Score" — orderby: `meta_value_num`, meta_key: `_overall_score`, order: DESC
   - "Lowest Score" — orderby: `meta_value_num`, meta_key: `_overall_score`, order: ASC
   - "Name A–Z" — orderby: `title`, order: ASC
   - "Newest" — orderby: `date`, order: DESC
4. Save

### Step 2: Add Filters to the Listing Page

1. Open the listing page in Elementor
2. Add **JetSmartFilters** widgets above the JetEngine Listing Grid
3. For each filter widget, set:
   - **Provider:** JetEngine
   - **Apply to:** The Listing Grid widget's ID (e.g., `jl-1`)
4. Ensure the Listing Grid has "Enable Filters" turned on in its settings

---

## 11. User Roles & Permissions

The plugin creates two custom roles on activation:

| Role | What they can do |
|---|---|
| **Subscriber** (built-in) | View all published tools, vote agree/disagree on sub-features |
| **csms_vendor** | All above + create new tool evaluations, edit their own tools, upload files (logos) |
| **csms_editor** | All above + edit/publish/delete ALL tools (including other vendors'), moderate comments |
| **Administrator** | Full control over everything |

### Assigning Roles

1. Go to **WP Admin > Users > All Users**
2. Click **Edit** on a user
3. Under **Role**, select the appropriate role
4. Click **Update User**

**For vendor companies:** Create a WP account for each vendor contact and assign the `csms_vendor` role. They can then log in and create/edit their own tool evaluation at **WP Admin > CSMS Tools > Add New**.

---

## 12. Vendor Submission Workflow

The platform uses native WordPress post statuses for vendor submissions:

```
Vendor creates tool → Draft → Vendor submits for review → Pending → Editor reviews → Published
```

| Status | Who sets it | Visibility |
|---|---|---|
| **Draft** | Vendor (auto when saving) | Only the vendor sees it |
| **Pending Review** | Vendor (clicks "Submit for Review") | Vendor + Editors see it |
| **Published** | Editor (clicks "Publish") | Everyone sees it |

### For Vendors
1. Log in to WP Admin
2. Go to **CSMS Tools > Add New**
3. Fill in the title, description, tool information fields, and evaluation ratings
4. Click **Submit for Review**
5. Wait for an ASRG editor to review and publish

### For Editors
1. Go to **WP Admin > CSMS Tools > All Tools**
2. Filter by "Pending" status
3. Review the vendor's submission — check ratings, rationale, evidence links
4. Edit if needed, then click **Publish**

---

## 13. Data Migration (from v1.0)

If you are upgrading from v1.0 (custom database tables + React frontend), a migration script is included.

### What it migrates:
- Rows from `wp_csms_tools` → `csms_tool` posts with all meta fields
- Scores from `wp_csms_tool_scores` → post meta ratings per sub-feature
- Vote records from `wp_csms_feedback_votes` → updates `tool_id` to new post IDs
- Tool logos → downloads as WordPress featured images

### Run the migration:

```bash
# Step 1: Run the migration (non-destructive — old tables are preserved)
wp eval 'require_once ASRG_CSMS_PLUGIN_DIR . "includes/migrations/class-migrate-to-cpt.php"; ASRG_CSMS_Migrate_To_CPT::run();'

# Step 2: Verify the migration
# - Check WP Admin > CSMS Tools — all tools should appear
# - Open a tool, check that ratings and scores are populated
# - Verify vote counts match

# Step 3: Drop old tables (only after verifying!)
wp eval 'require_once ASRG_CSMS_PLUGIN_DIR . "includes/migrations/class-migrate-to-cpt.php"; ASRG_CSMS_Migrate_To_CPT::drop_old_tables();'
```

> **The migration is idempotent:** it's safe to run multiple times. It checks for existing migrated posts and skips them.

---

## 14. Troubleshooting

### Plugin won't activate
- **Check PHP version:** requires 8.0+. Run `php -v` on your server.
- **Check file permissions:** the plugin directory needs to be readable by the web server.
- **Check error log:** `wp-content/debug.log` (enable `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`).

### "CSMS Tools" menu not appearing
- Flush permalinks: **Settings > Permalinks > Save Changes**
- Deactivate and reactivate the plugin
- Check that the plugin is actually activated (not just uploaded)

### Meta fields not showing on tool edit screen
- Ensure JetEngine is installed and activated **before** the CSMS plugin
- Go to **JetEngine > Meta Boxes** — you should see 11 groups prefixed with "csms-"
- If missing, bump the `version` field in `data/evaluation-framework.json` (e.g., `"1.0.0"` → `"1.0.1"`) and reload any admin page

### Scores showing 0 even after setting ratings
- Scores compute on post save. Re-save (Update) the tool post.
- Check that at least one sub-feature has a rating set (not "— Not Evaluated —")
- Manually trigger cron: `wp cron event run csms_recompute_scores`
- Check the "Computed Scores" meta box — values should populate after save

### Vote buttons not working
- Check browser console for JavaScript errors
- Ensure the user is logged in (guests see counts but can't vote)
- Verify the REST API is accessible: visit `https://yoursite.com/wp-json/csms/v1/framework` — should return JSON
- If you get a 403 error, the user's role may not have the `csms_vote` capability

### JetReviews not appearing on tool pages
- Ensure JetReviews is configured for the `csms_tool` post type in its settings
- The JetReviews widget must be added to the Elementor single tool template
- Check that the JetReviews widget's "Post Type" setting matches `csms_tool`

### Scoring cron not running
- WordPress cron depends on site traffic. On low-traffic sites, install **WP Crontrol** to verify:
  ```bash
  wp plugin install wp-crontrol --activate
  ```
  Then go to **WP Admin > Tools > Cron Events** and look for `csms_recompute_scores`
- For reliable cron, set up a real server cron job:
  ```bash
  */15 * * * * curl -s https://yoursite.com/wp-cron.php > /dev/null 2>&1
  ```

### FTP deploy failing in GitHub Actions
- Verify all 4 secrets are configured correctly (no trailing spaces)
- Check that `FTP_REMOTE_PATH` ends with a trailing slash
- Some hosts require passive FTP — the deploy action uses passive mode by default
- Check the Actions tab on GitHub for detailed error output

### CSS styles not loading / wrong colors
- Clear your browser cache and any WordPress caching plugins
- Check that the `assets/css/` directory was uploaded (should contain `csms-public.css` and `vote-buttons.css`)
- The plugin version in the CSS URL query string should match `ASRG_CSMS_VERSION` (currently `2.0.0`)

---

## Available Shortcodes Reference

| Shortcode | Where to use | What it renders |
|---|---|---|
| `[csms_tool_scores]` | Single tool Elementor template | Full 9-category scoring grid with ratings, rationale, evidence links, and vote buttons |
| `[csms_tool_scores post_id="123"]` | Anywhere | Same as above but for a specific tool post ID |
| `[csms_vote tool_id="123" sub_feature_id="rm-automated-tara"]` | Anywhere | Inline agree/disagree buttons for a specific sub-feature |
| `[csms_score_bar score="75.5"]` | Anywhere | Colored progress bar (green ≥75, yellow ≥40, red <40) |
| `[csms_methodology]` | Methodology page | Full framework table with rating system, categories, scoring formula, community influence |

---

## REST API Endpoints

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| `GET` | `/wp-json/csms/v1/framework` | Public | Evaluation framework JSON |
| `GET` | `/wp-json/csms/v1/feedback/{tool_id}/{sub_feature_id}` | Public | Vote counts + current user's vote |
| `POST` | `/wp-json/csms/v1/feedback/vote` | Logged in | Submit or change a vote |
| `DELETE` | `/wp-json/csms/v1/feedback/vote/{id}` | Logged in (owner) | Delete own vote |

---

## ASRG Brand Reference

| Element | Value |
|---|---|
| Black | `#000000` |
| White | `#FFFFFF` |
| Red | `#E71E25` |
| Purple | `#AAA4EF` |
| Dark buttons | `#32373C` |
| Font | Roboto 400/700 |
| Score High (≥75) | Green `#22c55e` |
| Score Mid (≥40) | Yellow `#eab308` |
| Score Low (<40) | Red `#ef4444` |
