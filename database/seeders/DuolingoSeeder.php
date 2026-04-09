<?php

namespace Database\Seeders;

use App\Console\Commands\ImportDuolingoDataCommand;
use Illuminate\Database\Seeder;

class DuolingoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->call(ImportDuolingoDataCommand::class);

        $this->call(DuolingoSectionNameSeeder::class);
    }
}
