<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PdfService
{
    public function merge_pdfs($files): string
    {
        $pdf = new Fpdi();
        $url = env('STORAGE_URL');

        foreach ($files as $file) {
            $downloadable_file = $url . $file['path'];
            $get_pdf = Http::get($downloadable_file);

            if ($get_pdf->successful()) {
                $file_name = Str::random() . '.pdf';
                $file_path = 'pdf/' . $file_name;
                $pdf_content = $get_pdf->body();

                Storage::disk('public')->put($file_path, $pdf_content);
                $pdf_path = Storage::disk('public')->path($file_path);
            } else {
                $file_path = 'pdf/' . basename($file['path']);
                if (Storage::disk('public')->exists($file_path)) {
                    $pdf_path = Storage::disk('public')->path($file_path);
                } else {
                    abort(400);
                }
            }
            // convert pdf to 1.4 --> code here
            $pageCount =  $pdf->setSourceFile($pdf_path);
            for ($i = 0; $i < $pageCount; $i++) {
                $pdf->AddPage();
                $tplId = $pdf->importPage($i + 1);

                // Get the dimensions of the imported page
                $size = $pdf->getTemplateSize($tplId);
                $width = $size['width'];
                $height = $size['height'];

                // Get the dimensions of the current page
                $pageWidth = $pdf->GetPageWidth();
                $pageHeight = $pdf->GetPageHeight();

                // Calculate the scale factor to fit the imported page within the current page
                $scale = min($pageWidth / $width, $pageHeight / $height);

                // Calculate the new dimensions of the imported page
                $newWidth = $width * $scale;
                $newHeight = $height * $scale;

                // Calculate the position to center the page
                $x = ($pageWidth - $newWidth) / 2;
                $y = ($pageHeight - $newHeight) / 2;

                // Add the imported page centered and scaled
                $pdf->useTemplate($tplId, $x, $y, $newWidth, $newHeight);
            }

            if (Storage::disk('public')->exists($file_path)) Storage::disk('public')->delete($file_path);
        }

        return $pdf->Output('I', 'sales.pdf');
    }
}
