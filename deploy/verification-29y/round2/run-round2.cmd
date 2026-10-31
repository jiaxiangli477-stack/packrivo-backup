@echo off
cd /d E:\Ai\ARTEX-Portable\app\data\tasks\67\i970
echo ROUND2-START %DATE% %TIME% > round2-master.log
"C:\Users\21996\AppData\Local\Programs\Python\Python313\python.exe" round2-rescan-970.py >> round2-master.log 2>&1
echo ROUND2-MASTER-EXIT=%ERRORLEVEL% >> round2-master.log
