<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use stdClass;
use Workbench\App\Models\Secret;

class SecretSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $obj = new class extends stdClass
        {
            const VAR = 'object';
        };

        Secret::create([
            'encrypted' => 'text',
            'encrypted_array' => ['array'],
            'encrypted_collection' => collect(['collection']),
            'encrypted_object' => $obj,
            'as_encrypted_array_object' => ['array'],
            'as_encrypted_collection' => collect(['collection']),
            'custom' => 'text',
        ]);
    }
}
