@echo off
cd /d c:\wamp64\www\nuevo
echo [%date% %time%] Iniciando git push automatico... >> scripts\git_log.txt

git add -A
git commit -m "Commit automatico - %date% %time%"
git push

echo [%date% %time%] Git push completado >> scripts\git_log.txt
