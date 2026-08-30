param(
    [Parameter(Mandatory = $true)]
    [ValidatePattern('^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$')]
    [string]$Version,

    [switch]$ManualQaAcknowledged
)

$ErrorActionPreference = 'Stop'
$Repository = Split-Path -Parent $PSScriptRoot
$BuildRoot = Join-Path $Repository 'build'
$Archive = Join-Path $BuildRoot "zen-by-mpty-$Version.zip"
$Checksum = "$Archive.sha256"

function Invoke-ZenReleaseStep {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Name,
        [Parameter(Mandatory = $true)]
        [scriptblock]$Action
    )

    Write-Host "[$Name]"
    & $Action
    if ($LASTEXITCODE -ne 0) {
        throw "$Name failed."
    }
}

if (-not $ManualQaAcknowledged) {
    throw 'Release preflight requires -ManualQaAcknowledged after completing RELEASE-CHECKLIST.md.'
}

Push-Location $Repository
try {
    $CurrentBranch = (& git branch --show-current).Trim()
    if ($LASTEXITCODE -ne 0 -or $CurrentBranch -ne 'develop') {
        throw "Release preflight requires the pre-release branch 'develop'; current branch is '$CurrentBranch'."
    }

    $Status = & git status --porcelain
    if ($LASTEXITCODE -ne 0 -or $Status) {
        throw 'Release preflight requires a clean working tree, including no untracked files.'
    }

    $PluginSource = Get-Content -LiteralPath 'zen-by-mpty.php' -Raw
    $Readme = Get-Content -LiteralPath 'readme.txt' -Raw
    $Package = Get-Content -LiteralPath 'package.json' -Raw | ConvertFrom-Json
    if ($PluginSource -notmatch "(?m)^\s*\* Version: $([regex]::Escape($Version))\s*$" -or
        $PluginSource -notmatch "define\( 'MPTY_ZEN_VERSION', '$([regex]::Escape($Version))' \);" -or
        $Readme -notmatch "(?m)^Stable tag: $([regex]::Escape($Version))\s*$" -or
        $Package.version -ne $Version) {
        throw 'Plugin header, runtime constant, readme Stable Tag, and package version must match the requested release.'
    }

    Invoke-ZenReleaseStep -Name 'Source validation' -Action { composer check }
    Invoke-ZenReleaseStep -Name 'Production build' -Action { php scripts/build-production.php build --source=. --output=build }
    Invoke-ZenReleaseStep -Name 'Artifact validation' -Action { php scripts/build-production.php validate --source=. "--zip=$Archive" }

    if (-not (Test-Path -LiteralPath $Archive) -or -not (Test-Path -LiteralPath $Checksum)) {
        throw 'Expected release ZIP or checksum is missing.'
    }
    $Recorded = ((Get-Content -LiteralPath $Checksum -Raw).Trim() -split '\s+')[0]
    $Actual = (Get-FileHash -LiteralPath $Archive -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($Recorded -ne $Actual) {
        throw 'Release checksum does not match the built ZIP.'
    }

    Write-Host "Zen $Version preflight passed. No publication was performed."
    Write-Host "Artifact: $Archive"
    Write-Host "SHA-256: $Actual"
} finally {
    Pop-Location
}
