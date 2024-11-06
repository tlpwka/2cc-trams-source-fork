@ECHO OFF
REM Windows batch file to quickly build NewGRF on Windows
REM For development purposes only
ECHO [PHP]
bash findversion.sh | cut -f2 | xargs php -f src\generate_nml.php
ECHO [NML]
nmlc -c --grf trams-2cc.grf trams-2cc.nml
