<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $avatar = $this?->getFirstMedia('avatar');
        return [
            'id' => $this->id,
            'need_pass' => $this->pass_generated,
            'is_subscribed' => $this->is_subscribed,
            'is_accepted' => $this->is_accepted,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $avatar?->id ? [
                'id' => $avatar->id,
                's' => $avatar->getUrl('s'),
                'xs' => $avatar->getUrl('xs')
            ] : null,
        ];
    }
}
