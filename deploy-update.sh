#!/bin/bash
# סקריפט עדכון לשרת - Flashcards System Update

echo "🚀 מתחיל עדכון אתר הכרטיסיות..."

# בדיקת תקינות קבצים
echo "📋 בודק תקינות קבצים..."
php -l index.php || exit 1
php -l site/templates/flashcards.php || exit 1
php -l site/templates/test.php || exit 1
php -l site/config/config.php || exit 1

echo "✅ כל הקבצים תקינים!"

# יצירת ארכיון לעדכון
echo "📦 יוצר ארכיון עדכון..."
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
ARCHIVE_NAME="flashcards_update_${TIMESTAMP}.tar.gz"

# קבצים לעדכון
tar -czf "$ARCHIVE_NAME" \
  site/templates/ \
  assets/flashcards/ \
  site/config/ \
  site/snippets/ \
  --exclude="*.DS_Store" \
  --exclude="node_modules" \
  --exclude=".git"

echo "✅ נוצר ארכיון: $ARCHIVE_NAME"

# הוראות העלאה
echo ""
echo "📋 שלבים להעלאה לשרת:"
echo "1. העלה את הקובץ $ARCHIVE_NAME לשרת"
echo "2. חלץ אותו בתיקיית השורש של האתר"
echo "3. וודא הרשאות: chmod 755 -R site/templates/ assets/"
echo "4. בדוק שהאתר עובד"
echo ""
echo "או השתמש בפקודות הבאות:"
echo "# לשרת עם SSH:"
echo "scp $ARCHIVE_NAME username@server:/path/to/website/"
echo "ssh username@server 'cd /path/to/website && tar -xzf $ARCHIVE_NAME'"
echo ""
echo "# עדכון מהיר עם rsync:"
echo "rsync -avz --exclude='.git' --exclude='node_modules' ./ username@server:/path/to/website/"