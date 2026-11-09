# Zen workspace

- Use Git-linked LocalWP development at `C:/Users/lapej/Local Sites/mpty-release-test` and `http://mpty-release-test.local/`. Verify the Zen junction with `Assert-MptyPluginLink -Product Zen`. Use synthetic notice fixtures.
- Use protected `https://staging.mptyprojects.com/` for release acceptance and website integration. Use the existing authentication gate and synthetic accounts. Test approved release artifacts there.
- Before automated browser interaction, run the shared preflight below and check the browser's actual origin after navigation and redirects. Before package installation or database work, verify the site's document root, WordPress URLs, environment type, and database identity using the [environment runbook](<C:/Users/lapej/Desktop/MPTY projects/MPTY knowledge/mpty-knowledge/infrastructure/mpty-environments.md>).
- Production is `https://mptyprojects.com/`. Ordinary automation targets development or staging. Older LocalWP hostnames and paths in historical evidence are archival references.
- Use isolated repository fixtures and ephemeral CI WordPress instances for automated tests that do not target a shared site.

Run in PowerShell 7 with the selected target before opening or driving the browser.

```powershell
. 'C:/Users/lapej/Desktop/MPTY projects/MPTY Licensing/mpty-licensing-server/tools/mpty-environment.ps1'
Assert-MptyBrowserTarget -Environment Development -Url 'http://mpty-release-test.local/'
# For staging acceptance, run the staging preflight instead.
Assert-MptyBrowserTarget -Environment Staging -Url 'https://staging.mptyprojects.com/'
```

For local WP-CLI operations, load `MPTY Licensing/release-localwp/qa-wp.ps1` from the portfolio directory and use `Invoke-QaWp`. The wrapper verifies the WordPress and database identity before running the command.
