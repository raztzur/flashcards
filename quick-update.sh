#!/bin/bash
# עדכון מהיר לשרת קיים - Flashcards System

echo "🚀 מכין עדכון לשרת..."

# בדיקת תקינות קבצים חשובים
echo "📋 בודק תקינות..."
php -l site/templates/flashcards.php || exit 1
php -l site/templates/test.php || exit 1
php -l site/templates/subcategory.php || exit 1
php -l assets/flashcards/app.js > /dev/null 2>&1 || echo "⚠️ בדוק JS syntax"

echo "✅ הקבצים תקינים"

# רשימת קבצים שהשתנו לאחרונה
echo ""
echo "📦 קבצים לעדכון:"
echo "- site/templates/flashcards.php (כותרת חדשה)"
echo "- site/templates/test.php (תיקונים)"
echo "- site/templates/subcategory.php (ניווט מעודכן)"
echo "- assets/flashcards/style.css (סטיילינג מעודכן)"
echo ""

# הוראות העלאה
echo "📋 שלבים לעדכון השרת:"
echo "1. התחבר לשרת (SSH או פאנל ניהול)"
echo "2. עדכן את הקבצים הבאים:"
echo ""
echo "   site/templates/flashcards.php"
echo "   site/templates/test.php" 
echo "   site/templates/subcategory.php"
echo "   assets/flashcards/style.css"
echo ""
echo "3. אם יש cPanel File Manager:"
echo "   - גש לתיקיית האתר"
echo "   - העלה את הקבצים המעודכנים"
echo "   - החלף את הקיימים"
echo ""
echo "4. אם יש SSH:"
echo "   scp site/templates/*.php username@SERVER_IP:/path/to/website/site/templates/"
echo "   scp assets/flashcards/* username@SERVER_IP:/path/to/website/assets/flashcards/"
echo ""
echo "5. בדוק שהאתר עובד אחרי העדכון"

TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
echo ""
echo "⏰ עדכון הוכן ב: $TIMESTAMP"