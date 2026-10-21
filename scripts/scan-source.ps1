
Write-Host "=== QUET MA NGUON PHP ===" -ForegroundColor Cyan

$files = Get-ChildItem -Path ".\src", ".\public" `
    -Filter "*.php" -Recurse -File `
    -ErrorAction SilentlyContinue

if (-not $files) {
    Write-Host "Chua co tep PHP trong src va public." -ForegroundColor Yellow
    exit 0
}

foreach ($file in $files) {
    Write-Host "`nDang kiem tra: $($file.FullName)" -ForegroundColor Cyan

    $phpExe = "C:\xampp\php\php.exe"

    if (Test-Path $phpExe) {
        & $phpExe -l $file.FullName
    } else {
        Write-Host "Khong tim thay PHP CLI tai $phpExe" -ForegroundColor Yellow
        break
    }
}

Write-Host "`n=== HOAN TAT ===" -ForegroundColor Green
