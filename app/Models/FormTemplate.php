<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
    protected $fillable = ['name', 'version', 'html', 'schema', 'is_active', 'uploaded_by'];

    protected function casts(): array
    {
        return ['schema' => 'array', 'is_active' => 'boolean'];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->latest('id')->first();
    }

    public function fields(): array
    {
        return $this->schema['fields'] ?? [];
    }

    public function ratingFields(): array
    {
        return array_values(array_filter($this->fields(), fn ($f) => $f['rating']));
    }
}
