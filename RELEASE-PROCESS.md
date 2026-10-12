# Zen by MPTY Release Process

Zen has one free WordPress.org production profile. `develop` is the active development and pre-release validation branch; `main` contains stable/released code. This process validates a candidate from `develop`; it does not merge, publish, tag, commit, or push anything.

1. Declare the exact publication baseline. Do not mix the tagged 0.6.1 artifact with newer uncommitted or later-commit work.
2. Finish [RELEASE-CHECKLIST.md](RELEASE-CHECKLIST.md), including human browser QA.
3. Confirm all CI checks are green on the intended clean `develop` candidate commit.
4. Run `pwsh ./scripts/release.ps1 -Version X.Y.Z -ManualQaAcknowledged`.
5. Record the generated `build/zen-by-mpty-X.Y.Z.zip` SHA-256 value.
6. Run WordPress Plugin Check against the exact unpacked production artifact if CI has not already validated that identical checksum.
7. Review the ZIP file list and install it in a disposable WordPress site.
8. Obtain explicit WordPress.org compliance approval for that exact ZIP, independently from Plugin Check and CI. Record the commit, manifest, filename, SHA-256, reviewer, date, current official guidance consulted, and any unresolved submission prerequisite.
9. Only after approval, promote the validated candidate to `main` through the separately controlled repository process, then perform the existing human-controlled WordPress.org tagging/upload process.

The preflight intentionally requires `develop` and refuses dirty worktrees, mismatched versions, failed source checks, invalid manifests, invalid ZIP layouts, or missing manual-QA acknowledgement. It never merges or publishes automatically.

## WordPress.org publication controls

Zen remains one standalone Free plugin. WordPress.org publication does not require and must not introduce a Free/Pro split, licensing client, entitlement system, paid trial, quota, custom updater, or remote-service dependency.

Before an upload, confirm that the exact approved ZIP is complete and operational, all distributed material is GPL-compatible with required notices and readable source, external calls and privacy claims are accurate, metadata and naming rights are valid, and the intended plugin slug and contributor access are authorized. Green CI and Plugin Check do not replace this semantic approval.

For SVN publication, copy the approved plugin files directly into `trunk/` and the matching `tags/X.Y.Z/`. Do not commit the ZIP itself and do not add an extra `zen-by-mpty/` wrapper directory. Increment the version for every code release, keep the Stable Tag and all other version references consistent, and distinguish any readme-only update from a code release.

WordPress.org and SVN access must use maintained individual contributor accounts with 2FA. Do not share credentials. Monitor the maintainer email and directory review correspondence through completion. If WordPress.org release confirmation is enabled, complete that confirmation before treating the release as live, then verify the published listing, files, version, and Stable Tag.

The tagged 0.6.1 ZIP and newer local UI work are separate possible baselines. This documentation milestone does not decide which will be submitted. If the exact tagged 0.6.1 ZIP is chosen, preserve its identity and do not blend in newer work. If the newer work is retained for publication, give it a later version and run the complete qualification process against a newly approved exact artifact. Do not release or regenerate either artifact merely to resolve this decision.

If Zen later proposes commercial functionality or a substantive external service, complete the pre-implementation distribution review defined in `ENGINEERING-STANDARD.md` before implementation. Add CI enforcement only for a new machine-verifiable boundary that existing manifest, version, package, and Plugin Check controls do not already enforce.

## Fail-safe promotion from PowerShell

Run promotion commands in a PowerShell session that explicitly stops after every failed native command. Do not chain promotion and publication commands in a sequence that can continue after a non-zero exit.

```powershell
function Invoke-CheckedCommand {
    param(
        [Parameter(Mandatory = $true)]
        [scriptblock]$Command,
        [Parameter(Mandatory = $true)]
        [string]$FailureMessage
    )

    $global:LASTEXITCODE = 0
    & $Command
    $CommandSucceeded = $?
    $CommandExitCode = $LASTEXITCODE
    if (-not $CommandSucceeded -or $CommandExitCode -ne 0) {
        throw $FailureMessage
    }
}

Invoke-CheckedCommand { git switch main } 'Could not switch to main.'
Invoke-CheckedCommand { git merge --ff-only develop } 'Develop could not be fast-forwarded to main.'
Invoke-CheckedCommand { composer check-release } 'Release validation failed on the main checkout.'

# Only after every command above succeeds:
Invoke-CheckedCommand { git push origin main } 'Pushing main failed.'
```

If any gate fails, stop immediately, leave `main` and the release state unpublished, correct the candidate on `develop`, and restart the validation and promotion sequence. No push, tag, GitHub Release, or WordPress.org upload may occur after a failed gate. Tagging and distribution remain separate human-controlled steps.

The repository line-ending policy keeps portable source, configuration, and documentation files at LF on every checkout. Windows-oriented PowerShell scripts deliberately use CRLF. This prevents checkout-specific line endings from changing PHPCS results between `develop` and `main`.
