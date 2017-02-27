@echo off
for /f "tokens=2-4 delims=/ " %%i in ('date /t') do set fldt=%%i-%%j-%%k
for /f "tokens=1-4 delims=/:." %%a in ("%TIME%") do (
    SET HH24=%%a
    SET MI=%%b
    SET SS=%%c
)
set HH24=%HH24: =%
set fltm=%HH24%h%MI%m%SS%s

echo cleaning...
echo.
rem following line should read 'php FILEPATH OF PHP FILE date=%fldt% time=%fltm% item_id=SHOPIFY ITEM ID
php c:\ date=%fldt% time=%fltm% item_id=00000000
pause