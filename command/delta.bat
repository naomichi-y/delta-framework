@echo off

rem ############################################################################
rem # delta CLI for Windows
rem ############################################################################

if "%OS%"=="Windows_NT" @setlocal

if "%PHP_COMMAND%"=="" set PHP_COMMAND=php.exe
if "%DELTA_HOME%"=="" set DELTA_HOME=@DELTA_HOME@

%PHP_COMMAND% -d html_errors=off "%DELTA_HOME%\command\delta.php" %1

if "%OS%"=="Windows_NT" @endlocal
