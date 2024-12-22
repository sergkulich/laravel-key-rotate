<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Casts\CustomCast;

class Secret extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'encrypted' => 'encrypted',
            'encrypted_array' => 'encrypted:array',
            'encrypted_collection' => 'encrypted:collection',
            'encrypted_object' => 'encrypted:object',
            'as_encrypted_array_object' => AsEncryptedArrayObject::class,
            'as_encrypted_collection' => AsEncryptedCollection::class,
            'custom' => CustomCast::class,
        ];
    }
}
