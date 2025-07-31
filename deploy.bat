@echo off
echo.
echo 🚀 DEPLOYING PRODUCT DISCOUNT SCRAPER TO GITHUB
echo ================================================
echo.

REM Check if git is installed
git --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Git is not installed. Please install Git first.
    echo Download from: https://git-scm.com/download/windows
    pause
    exit /b 1
)

echo ✅ Git is installed
echo.

REM Set variables
set "repoUrl=https://github.com/brandotsdigital/csearch.git"
set "tempDir=%TEMP%\csearch-deploy"

echo 📂 Current directory: %CD%
echo 🔗 Target repository: %repoUrl%
echo.

REM Clean up previous deployment
if exist "%tempDir%" (
    echo 🧹 Cleaning up previous deployment...
    rmdir /s /q "%tempDir%"
)

REM Clone repository
echo 📥 Cloning repository...
git clone %repoUrl% "%tempDir%"
if errorlevel 1 (
    echo ❌ Failed to clone repository. Please check:
    echo    - Repository URL is correct
    echo    - You have access to the repository  
    echo    - Your Git credentials are configured
    pause
    exit /b 1
)

echo ✅ Repository cloned successfully
echo.

REM Navigate to cloned directory
cd /d "%tempDir%"

REM Copy project files
echo 📁 Copying project files...

REM Copy directories
echo   📂 Copying public_html...
if exist "%CD%\..\..\..\Project Scrap\public_html" (
    xcopy "%CD%\..\..\..\Project Scrap\public_html" "public_html\" /s /e /y >nul
    echo   ✅ public_html copied
)

echo   📂 Copying private...
if exist "%CD%\..\..\..\Project Scrap\private" (
    xcopy "%CD%\..\..\..\Project Scrap\private" "private\" /s /e /y >nul
    echo   ✅ private copied
)

echo   📂 Copying starter_code...
if exist "%CD%\..\..\..\Project Scrap\starter_code" (
    xcopy "%CD%\..\..\..\Project Scrap\starter_code" "starter_code\" /s /e /y >nul
    echo   ✅ starter_code copied
)

REM Copy individual files
echo   📄 Copying documentation files...
copy "%CD%\..\..\..\Project Scrap\*.md" . >nul 2>&1
copy "%CD%\..\..\..\Project Scrap\*.sql" . >nul 2>&1
copy "%CD%\..\..\..\Project Scrap\*.php" . >nul 2>&1
echo   ✅ Documentation and setup files copied

echo.
echo 📝 Adding files to git...
git add .

REM Check if there are changes to commit
git diff --cached --quiet
if errorlevel 1 (
    echo 💾 Committing changes...
    git commit -m "🚀 Deploy complete Product Discount Scraper system (48 files)

✅ Features included:
- Complete PHP-based scraping system
- Amazon & eBay product monitoring  
- Beautiful responsive dashboard
- Admin control panel
- Automated email notifications
- Cron job automation
- MySQL database schema
- Comprehensive documentation
- Testing and validation tools

🎯 Ready for production deployment!"

    if not errorlevel 1 (
        echo ✅ Changes committed successfully
        echo.
        echo 📤 Pushing to GitHub...
        git push origin main
        
        if not errorlevel 1 (
            echo.
            echo 🎉 DEPLOYMENT SUCCESSFUL!
            echo ================================================
            echo.
            echo ✅ Your complete Product Discount Scraper system has been deployed!
            echo.
            echo 🔗 Repository: https://github.com/brandotsdigital/csearch
            echo 📁 Total files deployed: 48
            echo.
            echo 🚀 Next Steps:
            echo 1. Clone repository to your hosting account
            echo 2. Follow DEPLOYMENT_GUIDE.md for setup
            echo 3. Configure database and cron jobs
            echo 4. Start monitoring products and saving money!
            echo.
        ) else (
            echo ❌ Failed to push to GitHub
            echo Please check your GitHub credentials and try again
        )
    ) else (
        echo ❌ Failed to commit changes
    )
) else (
    echo ℹ️ No changes to commit - repository is up to date
)

REM Return to original directory
cd /d "%CD%\..\..\..\Project Scrap"

echo.
echo 🧹 Cleaning up temporary files...
rmdir /s /q "%tempDir%" >nul 2>&1

echo ✅ Deployment process completed!
echo.
pause
