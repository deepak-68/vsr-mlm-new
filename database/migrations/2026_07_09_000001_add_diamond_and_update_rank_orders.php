<?php

use App\Models\Rank;
use App\Models\Reward;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $diamondRank = Rank::where('slug', 'diamond')->first();
        if (!$diamondRank) {
            Rank::where('sort_order', '>=', 6)->increment('sort_order');

            $diamondRank = Rank::create([
                'name' => 'Diamond',
                'slug' => 'diamond',
                'required_self_cc' => 1800000,
                'sort_order' => 6,
                'reward_description' => 'Car Fund Upgrade (₹15,00,000)',
                'is_active' => true,
            ]);

            Reward::create([
                'rank_id' => $diamondRank->id,
                'name' => 'Car Fund Upgrade (₹15,00,000)',
                'reward_type' => 'CAR_FUND_UPGRADE',
                'description' => 'Car Fund Upgrade (₹15,00,000)',
                'value_cc' => 1800000,
            ]);
        }
    }

    public function down(): void
    {
        $diamondRank = Rank::where('slug', 'diamond')->first();
        if ($diamondRank) {
            $diamondRank->reward()->delete();
            $diamondRank->delete();

            Rank::where('sort_order', '>=', 6)->decrement('sort_order');
        }
    }
};
