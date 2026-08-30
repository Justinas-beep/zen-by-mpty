param(
    [Parameter(Mandatory = $true)]
    [string]$Config
)

$ErrorActionPreference = 'Stop'

function Invoke-ZenStep {
    param(
        [Parameter(Mandatory = $true)]
        [string[]]$Arguments
    )

    & npm exec -- wp-env "--config=$Config" @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "wp-env failed: $($Arguments -join ' ')"
    }
}

try {
    Invoke-ZenStep -Arguments @('start', '--update')
    Invoke-ZenStep -Arguments @('run', 'cli', 'wp', 'plugin', 'activate', 'zen-by-mpty')
    Invoke-ZenStep -Arguments @('run', 'cli', 'wp', 'eval-file', '/var/www/html/wp-content/plugins/zen-by-mpty/tests/integration/smoke.php')
} finally {
    & npm exec -- wp-env "--config=$Config" stop
}
