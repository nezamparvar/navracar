@echo off
REM ساخت خودکار فایل اجرایی ویندوز از روی navracar_crawler.py
REM پیش‌نیاز: پایتون نصب و به PATH اضافه شده باشد (python.org/downloads)

echo در حال نصب PyInstaller...
pip install pyinstaller

echo در حال ساخت navracar-crawler.exe ...
pyinstaller --onefile --name navracar-crawler navracar_crawler.py

echo.
echo تمام شد. فایل اجرایی اینجاست: dist\navracar-crawler.exe
echo آن را کنار config.json کپی کنید و اجرا کنید.
pause
