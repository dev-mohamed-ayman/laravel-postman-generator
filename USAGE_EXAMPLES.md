# أمثلة الاستخدام

## مثال 1: استخدام أساسي

```bash
php artisan postman:generate
```

سيقوم بإنشاء ملف `storage/app/postman-collection.json` يحتوي على جميع الـ routes.

## مثال 2: تخصيص الإخراج

```bash
php artisan postman:generate \
    --output=storage/app/api-collection.json \
    --name="My API Documentation" \
    --base-url=https://api.example.com
```

## مثال 3: تضمين routes محددة

```bash
# تضمين فقط API routes
php artisan postman:generate --include=api

# تضمين جميع الـ routes
php artisan postman:generate --include=all

# تضمين API و Web routes
php artisan postman:generate --include=api --include=web
```

## مثال 4: استبعاد routes معينة

```bash
php artisan postman:generate \
    --exclude=telescope \
    --exclude=horizon \
    --exclude=_debugbar
```

## مثال 5: التحديث عبر Postman API

```bash
php artisan postman:generate \
    --update-api \
    --collection-id=your-collection-id-here
```

## مثال 6: استخدام برمجي

```php
use MohamedAyman\LaravelPostmanGenerator\PostmanGenerator;

// في Controller أو Service
class ApiDocumentationController extends Controller
{
    public function generate(PostmanGenerator $generator)
    {
        $collection = $generator->generate([
            'collection_name' => 'My API',
            'base_url' => config('app.url'),
            'include_routes' => ['api'],
            'exclude_routes' => ['telescope'],
        ]);

        $generator->saveToFile($collection, storage_path('app/api.json'));

        return response()->json($collection);
    }
}
```

## مثال 7: التحديث التلقائي عبر API

```php
use MohamedAyman\LaravelPostmanGenerator\PostmanGenerator;

$generator = app(PostmanGenerator::class);

$collection = $generator->generate();

// حفظ محلي
$generator->saveToFile($collection);

// تحديث على Postman
$generator->updateViaApi($collection, [
    'collection_id' => env('POSTMAN_COLLECTION_ID'),
]);
```

## مثال 8: في CI/CD Pipeline

```yaml
# .github/workflows/postman-sync.yml
name: Sync Postman Collection

on:
  push:
    branches: [ main ]

jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install
      - name: Generate and sync Postman collection
        run: |
          php artisan postman:generate --update-api --collection-id=${{ secrets.POSTMAN_COLLECTION_ID }}
        env:
          POSTMAN_API_KEY: ${{ secrets.POSTMAN_API_KEY }}
```

## مثال 9: استخدام مع Scheduled Tasks

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // تحديث Postman collection يومياً
    $schedule->command('postman:generate --update-api')
        ->daily()
        ->at('02:00');
}
```

## مثال 10: تخصيص Configuration

```php
// config/postman-generator.php
return [
    'base_url' => env('APP_URL', 'https://api.example.com'),
    'collection_name' => env('APP_NAME') . ' API Collection',
    'include_routes' => ['api'],
    'exclude_routes' => ['telescope', 'horizon'],
    'postman' => [
        'api_key' => env('POSTMAN_API_KEY'),
        'collection_id' => env('POSTMAN_COLLECTION_ID'),
        'workspace_id' => env('POSTMAN_WORKSPACE_ID'),
    ],
];
```

## نصائح مهمة

1. **استخدم Environment Variables**: احفظ API keys في `.env` وليس في الكود
2. **اختبر قبل النشر**: تأكد من أن الـ collection يعمل بشكل صحيح
3. **استخدم Git Tags**: عند إصدار نسخة جديدة، أنشئ Git tag
4. **راجع الـ Collection**: افتح الـ collection في Postman وتأكد من صحة البيانات
5. **استخدم Folders**: الحزمة تنظم الـ routes تلقائياً في folders حسب المسار

