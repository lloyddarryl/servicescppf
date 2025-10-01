<?php
// database/seeders/DebugHeadersSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class DebugHeadersSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/imports/DarLot1.xlsx');
        $data = Excel::toArray([], $filePath)[0];
        
        $headers = $data[0];
        
        $this->command->info("=== EN-TÊTES DU FICHIER ===");
        foreach ($headers as $index => $header) {
            $this->command->info("[$index] = '" . $header . "' (longueur: " . strlen($header) . ")");
        }
        
        $this->command->info("\n=== PREMIÈRE LIGNE DE DONNÉES ===");
        if (isset($data[1])) {
            foreach ($data[1] as $index => $value) {
                $this->command->info("[$index] ($headers[$index]) = '$value'");
            }
        }
    }
}