# GitHub Secrets Configuration

This document describes the GitHub Secrets required for this project.

## Required Secrets

Configure these secrets in your GitHub repository settings under **Settings > Secrets and variables > Actions**.

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `DB_PASSWORD` | Database password for PostgreSQL | `your_secure_password` |
| `ADMIN_EMAIL` | Administrator email for notifications | `admin@example.com` |
| `DRUPAL_BASE_URL` | Base URL for the Drupal site | `https://example.com` |

## How to Add Secrets

1. Navigate to your repository on GitHub
2. Go to **Settings** > **Secrets and variables** > **Actions**
3. Click **New repository secret**
4. Add each secret with its name and value

## Usage in Workflows

These secrets can be referenced in GitHub Actions workflows:

```yaml
env:
  DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
  ADMIN_EMAIL: ${{ secrets.ADMIN_EMAIL }}
  DRUPAL_BASE_URL: ${{ secrets.DRUPAL_BASE_URL }}
```

## Security Notes

- Never commit actual secret values to the repository
- Rotate secrets periodically
- Use environment-specific secrets for staging/production
