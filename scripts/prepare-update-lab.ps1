[CmdletBinding()]
param(
    [string]$Destination = 'C:\tmp\laundryShop-update-lab'
)

$ErrorActionPreference = 'Stop'

$sourceRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

if (-not (Test-Path $sourceRoot)) {
    throw "Source root not found: $sourceRoot"
}

New-Item -ItemType Directory -Force -Path $Destination | Out-Null

$excludeDirs = @(
    '.git',
    '.github',
    '.idea',
    '.vscode',
    'storage\framework\cache',
    'storage\framework\sessions',
    'storage\framework\testing',
    'storage\framework\views',
    'storage\framework\temp',
    'storage\logs'
)

$robocopyArgs = @(
    $sourceRoot,
    $Destination,
    '/E',
    '/R:1',
    '/W:1',
    '/NFL',
    '/NDL',
    '/NJH',
    '/NJS',
    '/NP',
    '/XD'
) + $excludeDirs

& robocopy @robocopyArgs | Out-Null

if ($LASTEXITCODE -gt 7) {
    throw "robocopy failed with exit code $LASTEXITCODE"
}

$nextSteps = @"
Update lab is ready at:
  $Destination

Recommended test workflow:
  1. Open a new terminal in the lab copy.
  2. Run: php artisan optimize:clear
  3. Run: php artisan serve --host=0.0.0.0 --port=8001
  4. Open:
     http://demo-north-laundry.localhost:8001
     http://demo-south-laundry.localhost:8001

This lab copy can be overwritten by updater tests without touching:
  $sourceRoot
"@

Write-Host $nextSteps
