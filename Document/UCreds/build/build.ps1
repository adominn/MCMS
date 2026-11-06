# Builds a PDF from a folder of HTML fragments (needs Node with @playwright/cli, Edge, and Python with pypdf).
param(
  [string]$Src = (Join-Path (Split-Path $PSScriptRoot -Parent) 'src'),
  [string]$Out = (Join-Path (Split-Path $PSScriptRoot -Parent) 'UCreds_Public_Portal_Audit_v1.0.pdf'),
  [string]$Ref = 'UCREDS_AUDIT_V1.0_Oct_2026'
)
$ErrorActionPreference = 'Stop'
if (-not [IO.Path]::IsPathRooted($Src)) { $Src = [IO.Path]::GetFullPath((Join-Path (Get-Location) $Src)) }
if (-not [IO.Path]::IsPathRooted($Out)) { $Out = [IO.Path]::GetFullPath((Join-Path (Get-Location) $Out)) }
$name = [IO.Path]::GetFileNameWithoutExtension($Out)
$tmp = Join-Path $PSScriptRoot "out\$name"
New-Item -ItemType Directory -Force $tmp | Out-Null

$files = [string[]](Get-ChildItem $Src -Filter *.html | ForEach-Object FullName)
[Array]::Sort($files, [StringComparer]::Ordinal)
$content = ($files | ForEach-Object { Get-Content $_ -Raw -Encoding utf8 }) -join "`n"
$html = (Get-Content (Join-Path $PSScriptRoot 'template.html') -Raw -Encoding utf8).Replace('<!--CONTENT-->', $content)
$page = Join-Path $tmp "$name.html"
[IO.File]::WriteAllText($page, $html, [Text.UTF8Encoding]::new($false))

$env:DOC_REF = $Ref
$pass1 = Join-Path $tmp 'pass1.pdf'
node (Join-Path $PSScriptRoot 'render.js') $page $pass1
if ($LASTEXITCODE) { exit 1 }
py (Join-Path $PSScriptRoot 'toc.py') $pass1 ($pass1 -replace '\.pdf$', '.headings.json') (Join-Path $tmp 'toc.json')
if ($LASTEXITCODE) { exit 1 }
node (Join-Path $PSScriptRoot 'render.js') $page $Out (Join-Path $tmp 'toc.json')
