<?php

namespace App\Repositories;

use App\Models\Tag;

class TagRepository
{
    public function firstOrCreateByName(string $name): Tag
    {
        return Tag::firstOrCreate(['name' => strtolower($name)]);
    }
}
