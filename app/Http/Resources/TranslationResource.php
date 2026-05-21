<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'locale' => $this->whenLoaded('locale', fn () => [
                'id'   => $this->locale->id,
                'code' => $this->locale->code,
                'name' => $this->locale->name,
            ]),
            'key'        => $this->key,
            'value'      => $this->value,
            'group'      => $this->group,
            'tags'       => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
