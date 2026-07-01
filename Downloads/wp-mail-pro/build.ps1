# build.ps1 — Creates a correctly structured wp-mail-pro.zip for GitHub releases.
#
# Usage:
#   .\build.ps1
#
# Output: dist/wp-mail-pro.zip
# The zip contains a single top-level folder "wp-mail-pro/" with all plugin files
# inside — the structure WordPress expects when installing a plugin.

$ErrorActionPreference = 'Stop'

# Read version from plugin header
$header  = Get-Content "wp-mail-pro.php" -Raw
$version = [regex]::Match($header, 'Version:\s*([\d.]+)').Groups[1].Value

$distDir    = "dist"
$pluginDir  = "$distDir\wp-mail-pro"
$zipPath    = "$distDir\wp-mail-pro.zip"

# Clean previous build
if (Test-Path $distDir) { Remove-Item $distDir -Recurse -Force }
New-Item -ItemType Directory -Path $pluginDir | Out-Null

# Copy plugin files
Copy-Item "wp-mail-pro.php" $pluginDir
Copy-Item "includes"        $pluginDir -Recurse
Copy-Item "assets"          $pluginDir -Recurse

# Create zip
Compress-Archive -Path "$distDir\wp-mail-pro" -DestinationPath $zipPath -Force

Write-Host "Built wp-mail-pro v$version -> $zipPath" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Go to https://github.com/ammar458/wp-mail-pro/releases"
Write-Host "  2. Edit the release for the version you just built"
Write-Host "  3. Drag-and-drop $zipPath as a release asset"
Write-Host "  4. Publish / update the release"
