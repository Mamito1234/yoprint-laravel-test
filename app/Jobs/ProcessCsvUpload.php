<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $upload;

    public function __construct(Upload $upload)
    {
        $this->upload = $upload;
    }

    public function handle()
    {
        $this->upload->update(['status' => 'processing']);

        try {
            // Correctly resolve file path
            $path = Storage::disk('local')->path('private/uploads/' . $this->upload->filename);
            logger("Resolved CSV path: " . $path);

            // Read the CSV using League\Csv
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0); // use the first row as headers
            $records = Statement::create()->process($csv);

            foreach ($records as $record) {
                logger("Processing row: " . json_encode($record));

                $cleaned = array_map(function ($value) {
                    return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }, $record);

                Product::updateOrCreate(
                    ['unique_key' => $cleaned['UNIQUE_KEY']],
                    [
                        'product_title' => $cleaned['PRODUCT_TITLE'] ?? null,
                        'product_description' => $cleaned['PRODUCT_DESCRIPTION'] ?? null,
                        'style' => $cleaned['STYLE#'] ?? null,
                        'sanmar_mainframe_color' => $cleaned['SANMAR_MAINFRAME_COLOR'] ?? null,
                        'size' => $cleaned['SIZE'] ?? null,
                        'color_name' => $cleaned['COLOR_NAME'] ?? null,
                        'piece_price' => $cleaned['PIECE_PRICE'] ?? null,
                    ]
                );
            }

            $this->upload->update(['status' => 'completed', 'completed_at' => now()]);
        } catch (\Exception $e) {
            logger('CSV processing failed: ' . $e->getMessage());
            $this->upload->update(['status' => 'failed']);
        }
    }
}
