<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name_en' => 'Traditional Attire',
                'name_ar' => 'الأزياء التقليدية',
                'slug' => 'traditional-attire',
            ],
            [
                'name_en' => 'Evening Dresses',
                'name_ar' => 'فساتين سهرة',
                'slug' => 'evening-dresses',
            ],
            [
                'name_en' => 'Wedding Dresses',
                'name_ar' => 'فساتين زفاف',
                'slug' => 'wedding-dresses',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
