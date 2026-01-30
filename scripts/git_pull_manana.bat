@echo off
cd /d c:\wamp64\www\nuevo
echo [%date% %time%] Iniciando git pull automatico... >> scripts\git_log.txt

git pull

echo [%date% %time%] Git pull completado >> scripts\git_log.txt
