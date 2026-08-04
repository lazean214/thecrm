param(
    [string]$Message = "Backup"
)

$repo = Resolve-Path "."
$backupRemote = "backup"

Write-Host "Creating backup commit..."
git add .
git commit -m $Message

Write-Host "Pushing to local backup remote..."
git push $backupRemote main

Write-Host "Backup complete."
