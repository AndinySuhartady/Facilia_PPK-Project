param([string]$PhpPath = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
$ScriptPath = Join-Path $PSScriptRoot 'auto-cancel.php'
if (!(Test-Path $PhpPath)) { throw "PHP tidak ditemukan: $PhpPath" }
# Jalankan sekali untuk memastikan konfigurasi database dapat dipakai.
& $PhpPath $ScriptPath
if ($LASTEXITCODE -ne 0) { throw 'Uji auto-cancel gagal; jadwal belum dipasang.' }
$Action = New-ScheduledTaskAction -Execute $PhpPath -Argument ('"' + $ScriptPath + '"')
$Trigger = New-ScheduledTaskTrigger -Daily -At '00:00'
$Settings = New-ScheduledTaskSettingsSet -StartWhenAvailable
$TaskName = 'FACILIA-AutoCancel-' + (Split-Path (Split-Path $PSScriptRoot -Parent) -Leaf)
Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -Description 'Batalkan reservasi PENDING yang mencapai hari reservasi (WIB).' -Force
Write-Host "Jadwal dipasang: $TaskName. Komputer dan MySQL harus aktif; gunakan zona waktu Windows WIB."
