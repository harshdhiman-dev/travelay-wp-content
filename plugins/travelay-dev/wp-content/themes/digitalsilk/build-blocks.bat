@echo off
cd /d "%~dp0"
echo Building Gutenberg blocks...
call npm run build:blocks
echo.
echo Build complete!
pause

