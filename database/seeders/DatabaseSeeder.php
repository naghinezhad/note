<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = Faker::create('fa_IR');

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Category::truncate();
        Product::truncate();

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->command->info('🚀 شروع ساخت دسته‌بندی‌ها...');

        $colors = [
            '#FF6B9D', '#E74C3C', '#8B4513', '#2C3E50', '#3498DB',
            '#27AE60', '#E67E22', '#9B59B6', '#1ABC9C', '#F39C12',
            '#34495E', '#16A085', '#C0392B', '#D35400', '#8E44AD',
            '#2980B9', '#27AE60', '#F1C40F', '#E74C3C', '#95A5A6',
            '#7F8C8D', '#BDC3C7', '#ECF0F1', '#FF5733', '#C70039',
            '#900C3F', '#581845', '#FFC300', '#DAF7A6', '#FF5733',
        ];

        $categoryTypes = [
            'رمان عاشقانه', 'رمان تاریخی', 'رمان جنایی', 'رمان علمی تخیلی',
            'رمان اجتماعی', 'کتاب‌های کمک درسی', 'کتاب‌های فلسفی', 'کتاب‌های روانشناسی',
            'کتاب‌های خودشناسی', 'کتاب‌های تاریخی', 'کتاب‌های هنری', 'کتاب‌های ادبی',
            'شعر و ادبیات', 'رمان ماجراجویی', 'رمان وحشت', 'رمان کمدی',
            'کتاب‌های کودک', 'کتاب‌های نوجوان', 'کتاب‌های اقتصادی', 'کتاب‌های سیاسی',
            'کتاب‌های مذهبی', 'کتاب‌های علمی', 'کتاب‌های فناوری', 'کتاب‌های پزشکی',
            'کتاب‌های حقوقی', 'کتاب‌های معماری', 'کتاب‌های آشپزی', 'کتاب‌های ورزشی',
            'کتاب‌های سفرنامه', 'کتاب‌های زیست‌شناسی', 'کتاب‌های شیمی', 'کتاب‌های فیزیک',
            'کتاب‌های ریاضی', 'کتاب‌های زبان', 'کتاب‌های مدیریت', 'کتاب‌های بازاریابی',
            'کتاب‌های کسب‌وکار', 'کتاب‌های رمان پلیسی', 'کتاب‌های بیوگرافی', 'کتاب‌های سرگرمی',
        ];

        $categoryIds = [];
        for ($i = 1; $i <= 100; $i++) {
            $category = Category::create([
                'name' => $categoryTypes[array_rand($categoryTypes)].' '.$i,
                'color' => $colors[array_rand($colors)],
                'description' => $faker->sentence(10),
                'order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $categoryIds[] = $category->id;

            if ($i % 50 == 0) {
                $this->command->info("   ✓ {$i} دسته‌بندی ایجاد شد...");
            }
        }

        $this->command->info('✅ تمام دسته‌بندی‌ها ایجاد شدند!');
        $this->command->info('');
        $this->command->info('🚀 شروع ساخت محصولات...');
        $this->command->info('⚠️  این فرآیند ممکن است چند دقیقه طول بکشد...');

        $productPrefixes = [
            'کتاب', 'رمان', 'مجموعه', 'مجلد', 'اثر', 'داستان', 'نوشته',
            'ترجمه', 'تالیف', 'اثر برگزیده', 'کتاب برتر', 'بهترین', 'محبوب‌ترین',
        ];

        $batch = [];
        for ($i = 1; $i <= 100000; $i++) {
            $price = $faker->boolean(70) ? $faker->numberBetween(10, 100) * 1000 : 0;

            $randomWidth = $faker->numberBetween(400, 600);
            $randomHeight = $faker->numberBetween(300, 500);

            $highQualityImage = "https://placedog.net/{$randomWidth}/{$randomHeight}?id={$i}";
            $lowQualityImage = 'https://placedog.net/'.intval($randomWidth / 2).'/'.intval($randomHeight / 2)."?id={$i}";

            $batch[] = [
                'name' => $productPrefixes[array_rand($productPrefixes)].' '.$faker->word().' '.$faker->word(),
                'description' => $faker->paragraph(3),
                'price' => $price,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'high_quality_image' => $highQualityImage,
                'low_quality_image' => $lowQualityImage,
                'likes' => $faker->numberBetween(0, 2000),
                'views' => $faker->numberBetween(100, 10000),
                'purchased' => $faker->numberBetween(0, 1000),
                'is_active' => $faker->boolean(90),
                'is_3d' => $faker->boolean(30),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($i % 1000 == 0) {
                Product::insert($batch);
                $batch = [];
                $this->command->info("   ✓ {$i} محصول ایجاد شد...");
            }
        }

        if (! empty($batch)) {
            Product::insert($batch);
        }

        $this->command->info('');
        $this->command->info('🎉 تمام شد!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📊 آمار نهایی:');
        $this->command->info('   • تعداد دسته‌بندی‌ها: '.Category::count());
        $this->command->info('   • تعداد محصولات: '.Product::count());
        $this->command->info('   • محصولات رایگان: '.Product::where('price', 0)->count());
        $this->command->info('   • محصولات 3D: '.Product::where('is_3d', true)->count());
        $this->command->info('   • محصولات فعال: '.Product::where('is_active', true)->count());
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
