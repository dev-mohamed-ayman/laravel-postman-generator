# دليل رفع الحزمة على Packagist

## الخطوات المطلوبة

### 1. إنشاء حساب على Packagist

1. اذهب إلى [packagist.org](https://packagist.org)
2. سجل حساب جديد أو سجل دخول باستخدام GitHub

### 2. إنشاء مستودع Git

1. أنشئ مستودع جديد على GitHub باسم `laravel-postman-generator`
2. ارفع الكود إلى المستودع:

```bash
cd packages/laravel-postman-generator
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/laravel-postman-generator.git
git push -u origin main
```

### 3. إنشاء Tag للإصدار الأول

```bash
git tag -a v1.0.0 -m "First release"
git push origin v1.0.0
```

### 4. إضافة الحزمة على Packagist

1. اذهب إلى [packagist.org/packages/submit](https://packagist.org/packages/submit)
2. أدخل رابط المستودع: `https://github.com/YOUR_USERNAME/laravel-postman-generator`
3. اضغط على "Check" ثم "Submit"

### 5. تفعيل Auto-Update (اختياري)

1. اذهب إلى صفحة الحزمة على Packagist
2. اضغط على "Settings"
3. أضف Webhook URL من Packagist إلى GitHub:
   - اذهب إلى إعدادات المستودع على GitHub
   - Settings > Webhooks > Add webhook
   - URL: `https://packagist.org/api/github?username=YOUR_USERNAME`
   - Content type: `application/json`
   - Events: Just the `push` event

### 6. تحديث composer.json

تأكد من أن `composer.json` يحتوي على:
- `name` صحيح (مثل: `mohamed-ayman/laravel-postman-generator`)
- `description` واضح
- `keywords` مناسبة
- `license` محدد
- `authors` مع معلوماتك

### 7. التحديثات المستقبلية

عند إضافة ميزات جديدة:

```bash
# تحديث رقم الإصدار في composer.json
git add .
git commit -m "Add new feature"
git tag -a v1.1.0 -m "New feature release"
git push origin main
git push origin v1.1.0
```

Packagist سيتحدث تلقائياً إذا قمت بإعداد Webhook.

## ملاحظات مهمة

- تأكد من أن جميع الملفات المطلوبة موجودة
- اختبر الحزمة قبل الرفع
- اكتب README واضح ومفصل
- أضف أمثلة للاستخدام
- تأكد من أن الكود يتبع معايير PSR

