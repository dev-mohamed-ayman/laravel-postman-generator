# دليل إدارة الإصدارات على Packagist

## ⚠️ مهم جداً

**لا تضيف حقل `version` في `composer.json`!**

Composer لا يستخدم هذا الحقل. الإصدارات تُحدد تلقائياً من **Git tags** فقط.

## كيفية إدارة الإصدارات

### 1. إنشاء Git Tag

```bash
# للإصدار الأول
git tag -a v1.0.0 -m "First release"
git push origin v1.0.0

# للإصدارات التالية
git tag -a v1.0.1 -m "Hotfix: Fix null handling"
git push origin v1.0.1

git tag -a v1.1.0 -m "New features"
git push origin v1.1.0
```

### 2. تنسيق Tags

استخدم تنسيق Semantic Versioning:
- `v1.0.0` - Major.Minor.Patch
- `v1.0.1` - Patch (إصلاحات)
- `v1.1.0` - Minor (ميزات جديدة)
- `v2.0.0` - Major (تغييرات كبيرة)

### 3. Packagist سيكتشف Tags تلقائياً

بعد رفع Tag على GitHub:
- Packagist سيكتشفه تلقائياً (إذا كان webhook مضبوط)
- أو يمكنك الضغط على "Update" في صفحة الحزمة

### 4. التحقق من الإصدارات

```bash
# في مشروع آخر
composer show mohamed-ayman/laravel-postman-generator

# أو
composer info mohamed-ayman/laravel-postman-generator
```

## ❌ أخطاء شائعة

### خطأ 1: إضافة version في composer.json
```json
// ❌ خطأ - لا تفعل هذا
{
    "version": "1.0.1"
}
```

### خطأ 2: استخدام tags بدون v
```bash
# ❌ خطأ
git tag 1.0.0

# ✅ صحيح
git tag v1.0.0
```

### خطأ 3: عدم رفع tags
```bash
# ❌ خطأ - Tag موجود محلياً فقط
git tag v1.0.0

# ✅ صحيح - رفع Tag
git tag v1.0.0
git push origin v1.0.0
```

## ✅ الخطوات الصحيحة

1. **تأكد من أن composer.json صحيح** (بدون حقل version)
2. **أنشئ Git tag:**
   ```bash
   git tag -a v1.0.1 -m "Release version 1.0.1"
   ```
3. **ارفع Tag:**
   ```bash
   git push origin v1.0.1
   ```
4. **Packagist سيكتشفه تلقائياً** (أو اضغط Update)

## 📝 ملاحظات

- Git tags هي المصدر الوحيد للإصدارات
- Packagist يقرأ tags من GitHub
- تأكد من رفع tags على GitHub
- استخدم تنسيق `vX.Y.Z` دائماً

