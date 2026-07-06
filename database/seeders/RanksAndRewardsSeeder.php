<?php

namespace Database\Seeders;

use App\Models\Rank;
use App\Models\Reward;
use Illuminate\Database\Seeder;

class RanksAndRewardsSeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            ['name' => '1 Star', 'slug' => '1-star', 'required_self_cc' => 18000, 'sort_order' => 1, 'reward_description' => 'Company Kit + Catalogue + Uniform'],
            ['name' => 'Bronze', 'slug' => 'bronze', 'required_self_cc' => 45000, 'sort_order' => 2, 'reward_description' => 'Smartwatch'],
            ['name' => 'Silver', 'slug' => 'silver', 'required_self_cc' => 90000, 'sort_order' => 3, 'reward_description' => 'LED TV + Shimla Tour'],
            ['name' => 'Gold', 'slug' => 'gold', 'required_self_cc' => 300000, 'sort_order' => 4, 'reward_description' => 'Bike'],
            ['name' => 'Ruby', 'slug' => 'ruby', 'required_self_cc' => 720000, 'sort_order' => 5, 'reward_description' => 'Car or Car Fund (₹7,00,000)'],
            ['name' => 'Crown', 'slug' => 'crown', 'required_self_cc' => 8100000, 'sort_order' => 6, 'reward_description' => 'House (₹25,00,000)'],
        ];

        $rewards = [
            ['name' => 'Company Kit + Catalogue + Uniform', 'rank_slug' => '1-star', 'reward_type' => 'KIT'],
            ['name' => 'Smartwatch', 'rank_slug' => 'bronze', 'reward_type' => 'SMARTWATCH'],
            ['name' => 'LED TV + Shimla Tour', 'rank_slug' => 'silver', 'reward_type' => 'LED_TV'],
            ['name' => 'Bike', 'rank_slug' => 'gold', 'reward_type' => 'BIKE'],
            ['name' => 'Car or Car Fund (₹7,00,000)', 'rank_slug' => 'ruby', 'reward_type' => 'CAR_FUND'],
            ['name' => 'House (₹25,00,000)', 'rank_slug' => 'crown', 'reward_type' => 'HOUSE'],
        ];

        foreach ($ranks as $rankData) {
            $rank = Rank::create($rankData);

            $rewardData = collect($rewards)->firstWhere('rank_slug', $rankData['slug']);
            if ($rewardData) {
                Reward::create([
                    'rank_id' => $rank->id,
                    'name' => $rewardData['name'],
                    'reward_type' => $rewardData['reward_type'],
                    'description' => $rankData['reward_description'],
                    'value_cc' => $rankData['required_self_cc'],
                ]);
            }
        }

        $this->command->info('Ranks and Rewards seeded successfully!');
    }
}
