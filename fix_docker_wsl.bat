@echo off
echo ========================================
echo Fix Docker WSL2 Error
echo ========================================
echo.
echo This script will enable required Windows features for Docker
echo You need to run this as Administrator
echo.
pause

echo.
echo Step 1: Enabling WSL feature...
dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart

echo.
echo Step 2: Enabling Virtual Machine Platform...
dism.exe /online /enable-feature /featurename:VirtualMachinePlatform /all /norestart

echo.
echo Step 3: Updating WSL...
wsl --update

echo.
echo Step 4: Setting WSL2 as default...
wsl --set-default-version 2

echo.
echo ========================================
echo DONE! Please restart your computer
echo ========================================
echo.
echo After restart:
echo 1. Start Docker Desktop
echo 2. Wait for it to fully start
echo 3. Your Docker should work now
echo.
pause
