<?php
// database/seeders/ConvertExcelToCsvSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ConvertExcelToCsvSeeder extends Seeder
{
    public function run(): void
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);
        
        $excelPath = storage_path('app/imports/enfants_fur.xlsx');
        $csvPath = storage_path('app/imports/enfants_fur.csv');
        
        if (!file_exists($excelPath)) {
            $this->command->error("Fichier Excel introuvable !");
            return;
        }
        
        $this->command->info("Conversion Excel → CSV...");
        
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($excelPath);
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setLineEnding("\r\n");
        $writer->setSheetIndex(0);
        $writer->save($csvPath);
        
        $this->command->info("✅ Conversion terminée : $csvPath");
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
}