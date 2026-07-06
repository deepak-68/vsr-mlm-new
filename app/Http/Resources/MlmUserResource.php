<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MlmUserResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'track_id' => $this->track_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position_in_sponsor_leg' => $this->position_in_sponsor_leg,
            'membership_type' => $this->membership_type,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'profile_image' => $this->profile_image,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->relationLoaded('detail')) {
            $data['detail'] = $this->detail;
        }

        if ($this->relationLoaded('sponsor')) {
            $data['sponsor'] = [
                'id' => $this->sponsor->id,
                'user_name' => $this->sponsor->user_name,
                'first_name' => $this->sponsor->first_name,
                'last_name' => $this->sponsor->last_name,
            ];
        }

        if ($this->relationLoaded('payoutBalance') && $this->payoutBalance) {
            $data['payout_balance'] = $this->payoutBalance;
        }

        if ($this->relationLoaded('currentRank') && $this->currentRank) {
            $data['current_rank'] = $this->currentRank;
        }

        return $data;
    }
}
