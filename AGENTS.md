# Zen workspace

## Mandatory governance

- Read [ENGINEERING-STANDARD.md](ENGINEERING-STANDARD.md) before every change. It defines Zen's product boundary, risk levels, conservative classifier contract and required evidence. `MUST KEEP` and `AMBIGUOUS KEEP` content remains visible; false-positive suppression is the priority risk.
- Read [ZEN-PARKED-STATUS.md](ZEN-PARKED-STATUS.md) for the current product, data, privacy and release identity. Zen remains a minimal, local-only, standalone Free plugin. Do not invent licensing, entitlements, telemetry, remote classification or a Free/Pro split.
- Release work must also follow [RELEASE-PROCESS.md](RELEASE-PROCESS.md) and [RELEASE-CHECKLIST.md](RELEASE-CHECKLIST.md). Exact artifact identity, manual acceptance, CI and publication authorization are separate gates.
- UI work consumes Loop's [UI-SYSTEM.md](<C:/Users/lapej/Desktop/MPTY projects/Loop/plugin/loop-by-mpty/UI-SYSTEM.md>) read-only and the canonical MPTY [admin UX release standard](<C:/Users/lapej/Desktop/MPTY projects/MPTY knowledge/mpty-knowledge/product/admin-ux-release-standard.md>). Preserve Zen's deliberate simplicity and document any justified product-specific exception instead of adding dashboard or navigation complexity.

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
