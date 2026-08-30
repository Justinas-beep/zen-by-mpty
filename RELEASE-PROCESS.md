# Zen by MPTY Release Process

Zen has one free WordPress.org production profile. `develop` is the active development and pre-release validation branch; `main` contains stable/released code. This process validates a candidate from `develop`; it does not merge, publish, tag, commit, or push anything.

1. Finish [RELEASE-CHECKLIST.md](RELEASE-CHECKLIST.md), including human browser QA.
2. Confirm all CI checks are green on the intended clean `develop` candidate commit.
3. Run `pwsh ./scripts/release.ps1 -Version X.Y.Z -ManualQaAcknowledged`.
4. Record the generated `build/zen-by-mpty-X.Y.Z.zip` SHA-256 value.
5. Run WordPress Plugin Check against the exact unpacked production artifact if CI has not already validated that identical checksum.
6. Review the ZIP file list and install it in a disposable WordPress site.
7. Only after approval, promote the validated candidate to `main` through the separately controlled repository process, then perform the existing human-controlled WordPress.org tagging/upload process.

The preflight intentionally requires `develop` and refuses dirty worktrees, mismatched versions, failed source checks, invalid manifests, invalid ZIP layouts, or missing manual-QA acknowledgement. It never merges or publishes automatically.

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
