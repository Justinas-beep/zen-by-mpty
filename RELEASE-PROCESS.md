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
