# دليل الاختبار

## اختبار الحزمة محلياً قبل النشر

### 1. إضافة الحزمة إلى المشروع الحالي

في ملف `composer.json` الرئيسي للمشروع، أضف:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/laravel-postman-generator"
        }
    ],
    "require": {
        "mohamed-ayman/laravel-postman-generator": "@dev"
    }
}
```

ثم قم بتشغيل:

```bash
composer update mohamed-ayman/laravel-postman-generator
```

### 2. تسجيل Service Provider

في `config/app.php` أو `bootstrap/providers.php` (حسب إصدار Laravel):

```php
MohamedAyman\LaravelPostmanGenerator\LaravelPostmanGeneratorServiceProvider::class,
```

أو إذا كان Laravel 11+، سيتم التسجيل تلقائياً.

### 3. نشر ملف الإعدادات

```bash
php artisan vendor:publish --tag=postman-generator-config
```

### 4. اختبار الأمر

```bash
php artisan postman:generate
```

يجب أن ترى ملف `storage/app/postman-collection.json` تم إنشاؤه.

### 5. فتح الملف في Postman

1. افتح Postman
2. اضغط على Import
3. اختر الملف الذي تم إنشاؤه
4. راجع الـ collection وتحقق من:
   - جميع الـ routes موجودة
   - الـ headers صحيحة
   - الـ request bodies تحتوي على بيانات
   - الـ authentication مضبوطة

### 6. اختبار مع Routes حقيقية

أنشئ بعض الـ routes للاختبار:

```php
// routes/api.php
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
```

```php
// app/Http/Controllers/UserController.php
class UserController extends Controller
{
    public function index()
    {
        // ...
    }

    public function store(StoreUserRequest $request)
    {
        // ...
    }

    public function show($id)
    {
        // ...
    }
}
```

```php
// app/Http/Requests/StoreUserRequest.php
class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ];
    }
}
```

ثم قم بتشغيل:

```bash
php artisan postman:generate --include=api
```

### 7. اختبار التحديث عبر API

```bash
php artisan postman:generate --update-api --collection-id=your-collection-id
```

تأكد من إضافة `POSTMAN_API_KEY` في `.env`.

## نصائح للاختبار

1. **اختبر مع routes مختلفة**: web, api, resource routes
2. **اختبر مع Form Requests**: تأكد من استخراج الفاليديشن
3. **اختبر مع Middleware**: تأكد من إضافة الـ headers الصحيحة
4. **اختبر مع Route Parameters**: تأكد من إضافة variables
5. **راجع الـ JSON**: تأكد من صحة البنية

## حل المشاكل الشائعة

### المشكلة: لا تظهر الـ routes

**الحل**: تأكد من:
- الـ routes مسجلة بشكل صحيح
- خيار `--include` صحيح
- الـ routes ليست مستبعدة

### المشكلة: لا يتم استخراج الفاليديشن

**الحل**: تأكد من:
- Form Request موجود ويحتوي على `rules()` method
- الـ controller يستخدم Form Request بشكل صحيح

### المشكلة: خطأ في Postman API

**الحل**: تأكد من:
- API key صحيح
- Collection ID صحيح
- الاتصال بالإنترنت يعمل

## اختبارات تلقائية (اختياري)

يمكنك إضافة PHPUnit tests:

```php
// tests/Feature/PostmanGeneratorTest.php
use Tests\TestCase;
use MohamedAyman\LaravelPostmanGenerator\PostmanGenerator;

class PostmanGeneratorTest extends TestCase
{
    public function test_can_generate_collection()
    {
        $generator = app(PostmanGenerator::class);
        $collection = $generator->generate();
        
        $this->assertArrayHasKey('info', $collection);
        $this->assertArrayHasKey('item', $collection);
    }
}
```

