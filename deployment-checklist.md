# 📋 Checklist העלאה לשרת

## לפני ההעלאה
- [ ] גבה את האתר הקיים (אם יש)
- [ ] וודא שיש PHP 8.0+ על השרת
- [ ] בדק שמודול mod_rewrite פעיל (Apache)
- [ ] הכן פרטי גישה: FTP/SSH username, password, server

## תהליך ההעלאה
- [ ] העלה את כל הקבצים לתיקיית public_html
- [ ] הגדר הרשאות קבצים (755 לתיקיות, 644 לקבצים)
- [ ] הרשאות מיוחדות: 777 ל-content/, media/, site/cache/, site/sessions/
- [ ] בדק ש-.htaccess קיים ונגיש

## אחרי ההעלאה
- [ ] גש לאתר ובדק שהדף הראשי נטען
- [ ] גש ל-/panel ויצור משתמש אדמין ראשון
- [ ] בדק שכל סוגי השאלות עובדים: /test
- [ ] בדק את הפאנל ויצירת תוכן חדש
- [ ] בדק responsive על מובייל

## אבטחה (מומלץ)
- [ ] החלף את slug של הפאנל מ-/panel למשהו אחר
- [ ] הגדר HTTPS certificate
- [ ] הגדר גיבויים אוטומטיים
- [ ] הגדר monitoring

## בעיות נפוצות ופתרונות
- אם אתר לא נטען: בדק error logs של השרת
- אם פאנל לא עובד: בדק שPHP sessions directory קיים
- אם תמונות לא נטענות: בדק הרשאות תיקיית media/
- שגיאות 500: בדק שכל הקבצים הועלו בשלמותם