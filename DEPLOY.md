# Deployment Guide

## Prerequisites

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Node.js 20+ (for building locally)

---

## Automated Deployment (GitHub Actions)

Every push to `main` triggers a GitHub Actions workflow that builds the frontend and deploys the plugin to your WordPress server via FTP.

### 1. Configure GitHub Secrets

Go to **Settings > Secrets and variables > Actions** in your GitHub repository and add these 4 secrets:

| Secret | Description | Example |
|---|---|---|
| `FTP_SERVER` | FTP server hostname | `ftp.asrg.io` |
| `FTP_USERNAME` | FTP username | `deploy@asrg.io` |
| `FTP_PASSWORD` | FTP password | *(your password)* |
| `FTP_REMOTE_PATH` | Remote path to the plugin directory | `wp-content/plugins/asrg-csms-evaluation/` |

> **Note:** `FTP_REMOTE_PATH` must end with a trailing slash. The path is relative to the FTP root, which varies by hosting provider.

### 2. Push to `main`

Once secrets are configured, any push to `main` will automatically:

1. Install frontend dependencies
2. Build the React app (TypeScript compilation + Vite bundle)
3. Upload the plugin files to your WordPress server via FTP

The workflow only deploys runtime files — frontend source code, `node_modules`, and config files are excluded.

### 3. Monitor deployments

Check the **Actions** tab in GitHub to monitor build status. Failed builds will not deploy.

---

## Manual Deployment

If you prefer to deploy manually:

### Build

```bash
cd asrg-csms-evaluation/frontend
npm ci
npm run build
```

This outputs the built assets to `asrg-csms-evaluation/dist/`.

### Upload

Upload the following to `wp-content/plugins/asrg-csms-evaluation/` on your server:

```
asrg-csms-evaluation/
├── asrg-csms-evaluation.php    # Plugin entry point
├── includes/                   # PHP classes
├── data/                       # Evaluation framework JSON
└── dist/                       # Built frontend assets
```

Do **NOT** upload `frontend/node_modules/` or `frontend/src/` — these are development-only files.

---

## WordPress Setup

### First-time setup

1. **Activate the plugin** in WP Admin > Plugins > "ASRG CSMS Evaluation"
   - This automatically creates custom database tables and registers user roles

2. **Create the evaluation page**
   - Go to Pages > Add New
   - Add the shortcode: `[csms_evaluation]`
   - Publish the page

3. **Assign user roles** (optional, for community features)
   - `csms_vendor` — companies who can submit/update their tool evaluations
   - `csms_editor` — ASRG team members who approve vendor submissions and override scores
   - Assign roles in WP Admin > Users > Edit User > Role

### Custom roles created on activation

| Role | Capabilities |
|---|---|
| **Subscriber** (WP default) | View table + vote + comment |
| **csms_vendor** | All above + submit/update own tool |
| **csms_editor** | All above + approve/reject submissions, override scores |
| **Administrator** | Full control |

Anonymous visitors can view the comparison table and read comments but cannot vote or comment.

---

## Updating

Just push to `main`. GitHub Actions handles the rest.

For manual updates, rebuild the frontend and re-upload the changed files.

---

## Troubleshooting

### Plugin not appearing in WP Admin
- Verify files are in `wp-content/plugins/asrg-csms-evaluation/`
- Check that `asrg-csms-evaluation.php` exists at the root of that directory

### Comparison table shows loading spinner indefinitely
- Check browser console for API errors
- Verify the REST API is accessible: visit `https://yoursite.com/wp-json/csms/v1/framework`
- Ensure the plugin is activated

### Styles not loading
- Check that `dist/` contains built assets (`.js` and `.css` files)
- Verify `dist/.vite/manifest.json` exists — the shortcode reads this to find asset filenames

### FTP deploy failing in GitHub Actions
- Verify all 4 secrets are configured correctly
- Check that `FTP_REMOTE_PATH` ends with a trailing slash
- Some hosts require passive FTP — the deploy action uses passive mode by default
