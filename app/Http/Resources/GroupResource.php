<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'color' => $this->color,
            'coordinates' => $this->coordinates,
            'city' => $this->city,
            'governorate' => $this->governorate,
            'is_active' => $this->is_active,
        ];
    }
}

