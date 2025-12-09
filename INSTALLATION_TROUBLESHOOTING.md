# حل مشاكل التثبيت

## المشكلة: Security Advisories Blocking Installation

إذا واجهت هذه الرسالة:
```
but these were not loaded, because they are affected by security advisories
```

### الحل 1: إضافة المستودع من GitHub (مؤقت حتى النشر على Packagist)

في ملف `composer.json` للمشروع المستهدف، أضف:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/dev-mohamed-ayman/laravel-postman-generator"
        }
    ],
    "require": {
        "mohamed-ayman/laravel-postman-generator": "^1.0"
    }
}
```

ثم قم بتشغيل:
```bash
composer require mohamed-ayman/laravel-postman-generator
```

### الحل 2: تجاهل Security Advisories مؤقتاً

إذا كنت تريد تثبيت الحزمة رغم وجود security advisories (غير موصى به للإنتاج):

في ملف `composer.json`:

```json
{
    "config": {
        "audit": {
            "ignore": ["PKSA-365x-2zjk-pt47"]
        }
    }
}
```

أو قم بتشغيل:
```bash
composer require mohamed-ayman/laravel-postman-generator --ignore-platform-reqs
```

### الحل 3: تحديث Symfony (الأفضل)

قم بتحديث `symfony/http-foundation` إلى إصدار آمن:

```bash
composer update symfony/http-foundation --with-all-dependencies
```

### الحل 4: استخدام Path Repository (للتطوير المحلي)

إذا كانت الحزمة في نفس الجهاز:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-postman-generator"
        }
    ],
    "require": {
        "mohamed-ayman/laravel-postman-generator": "@dev"
    }
}
```

## المشكلة: Package Not Found

إذا رأيت:
```
Could not find package mohamed-ayman/laravel-postman-generator
```

### الحل:

1. تأكد من أن الحزمة موجودة على Packagist، أو
2. أضف المستودع من GitHub كما في الحل 1 أعلاه

## المشكلة: Version Conflict

إذا واجهت تعارض في الإصدارات:

```bash
composer require mohamed-ayman/laravel-postman-generator --with-all-dependencies
```

## بعد النشر على Packagist

بعد نشر الحزمة على Packagist، يمكنك التثبيت مباشرة:

```bash
composer require mohamed-ayman/laravel-postman-generator
```

بدون الحاجة لإضافة repositories.

