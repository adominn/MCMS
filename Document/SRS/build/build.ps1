# Builds a PDF from a folder of HTML fragments (needs Node with @playwright/cli, Edge, and Python with pypdf).
# Default builds the SRS; e.g. for the glossary:
#   SRS\build\build.ps1 -Src 'Decision log\src' -Out 'Decision log\MCMS_Glossary_and_Decision_Log_v0.3.pdf' -Ref MCMS_FND_V0.3_Oct_2026
# (relative paths resolve against the current directory)
param(
  [string]$Src = (Join-Path (Split-Path $PSScriptRoot -Parent) 'src'),
  [string]$Out = (Join-Path (Split-Path $PSScriptRoot -Parent) 'MCMS_SRS_v1.0.pdf'),
  [string]$Ref = 'MCMS_SRS_V1.0_Oct_2026'
)
$ErrorActionPreference = 'Stop'
if (-not [IO.Path]::IsPathRooted($Src)) { $Src = [IO.Path]::GetFullPath((Join-Path (Get-Location) $Src)) }
if (-not [IO.Path]::IsPathRooted($Out)) { $Out = [IO.Path]::GetFullPath((Join-Path (Get-Location) $Out)) }
$name = [IO.Path]::GetFileNameWithoutExtension($Out)
$tmp = Join-Path $PSScriptRoot "out\$name"
New-Item -ItemType Directory -Force $tmp | Out-Null

$files = [string[]](Get-ChildItem $Src -Filter *.html | ForEach-Object FullName)
[Array]::Sort($files, [StringComparer]::Ordinal)  # culture sort ignores hyphens and misorders 03b- before 03-
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
