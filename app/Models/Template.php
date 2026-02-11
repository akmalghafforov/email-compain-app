<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Enums\TemplateEngine;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'engine',
        'subject_template',
        'body_content',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'engine' => TemplateEngine::class,
            'metadata' => 'array',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
