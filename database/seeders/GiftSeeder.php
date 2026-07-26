<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gift;
use App\Enums\GiftTypeEnum;

class GiftSeeder extends Seeder
{
    public function run(): void
    {
        $gifts = [
            ['name' => 'Pen', 'type' => GiftTypeEnum::Gift],
            ['name' => 'Notebook', 'type' => GiftTypeEnum::Gift],
            ['name' => 'Brochure', 'type' => GiftTypeEnum::LeaveBehind],
            ['name' => 'Product Catalogue', 'type' => GiftTypeEnum::AdditionalFiles],
            ['name' => 'Calendar', 'type' => GiftTypeEnum::Gift],
            ['name' => 'Flyer', 'type' => GiftTypeEnum::LeaveBehind],
            ['name' => 'Samples Guide', 'type' => GiftTypeEnum::AdditionalFiles],
        ];

        foreach ($gifts as $gift) {
            Gift::create($gift);
        }
    }
}