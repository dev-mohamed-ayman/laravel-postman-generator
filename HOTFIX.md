# Hotfix: PostmanApiClient null handling

## المشكلة

إذا واجهت هذا الخطأ:
```
TypeError: Cannot assign null to property MohamedAyman\LaravelPostmanGenerator\Services\PostmanApiClient::$apiKey of type string
```

## الحل السريع

### الحل 1: تحديث الحزمة

```bash
composer update mohamed-ayman/laravel-postman-generator
```

### الحل 2: إذا كانت الحزمة من GitHub

```bash
composer require mohamed-ayman/laravel-postman-generator:dev-main
```

### الحل 3: إصلاح يدوي (مؤقت)

إذا لم تتمكن من التحديث فوراً، يمكنك إصلاح الملف يدوياً:

في ملف `vendor/mohamed-ayman/laravel-postman-generator/src/Services/PostmanApiClient.php`:

**قبل:**
```php
$this->apiKey = config('postman-generator.postman.api_key', '');
```

**بعد:**
```php
$this->apiKey = (string) (config('postman-generator.postman.api_key') ?? '');
```

## ملاحظة

هذا الإصلاح موجود في الإصدار 1.0.1+. تأكد من تحديث الحزمة إلى أحدث إصدار.

