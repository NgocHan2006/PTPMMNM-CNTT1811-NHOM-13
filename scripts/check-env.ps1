
Write-Host "=== KIEM TRA MOI TRUONG DU AN ===" -ForegroundColor Cyan

# Kiem tra PHP
$php = Get-Command php -ErrorAction SilentlyContinue

if ($php) {
    Write-Host "[OK] PHP da duoc cai dat" -ForegroundColor Green
    php --version
} else {
    Write-Host "[CAN KIEM TRA] Khong tim thay PHP trong PATH" -ForegroundColor Yellow
    Write-Host "Neu dung XAMPP, thu kiem tra C:\xampp\php\php.exe"
}

# Kiem tra Git
$git = "C:\Program Files\Git\cmd\git.exe"

if (Test-Path $git) {
    Write-Host "[OK] Git da duoc cai dat" -ForegroundColor Green
    & $git --version
} else {
    Write-Host "[CAN KIEM TRA] Khong tim thay Git tai duong dan mac dinh" -ForegroundColor Yellow
}

# Kiem tra tep chinh
if (Test-Path ".\public\index.php") {
    Write-Host "[OK] Tim thay public/index.php" -ForegroundColor Green
} else {
    Write-Host "[THIEU] Chua co public/index.php" -ForegroundColor Red
}

Write-Host "=== KET THUC KIEM TRA ===" -ForegroundColor Cyan
