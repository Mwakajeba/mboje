<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Dompdf\FontMetrics;
use Dompdf\Options;
use Dompdf\Adapter\CPDF;
use FontLib\Font;

class LoadDomPdfFonts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dompdf:load-fonts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load and register DejaVu Sans fonts for DomPDF';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Loading DomPDF fonts...');
        
        $fontDir = storage_path('fonts');
        
        // Ensure font directory exists
        if (!is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
            $this->info("Created font directory: {$fontDir}");
        }
        
        // Copy DejaVu Sans fonts from vendor to storage/fonts
        $vendorFontDir = base_path('vendor/dompdf/dompdf/lib/fonts');
        $fontsToCopy = [
            'DejaVuSans.ttf',
            'DejaVuSans.ufm',
            'DejaVuSans-Bold.ttf',
            'DejaVuSans-Bold.ufm',
            'DejaVuSans-Oblique.ttf',
            'DejaVuSans-Oblique.ufm',
            'DejaVuSans-BoldOblique.ttf',
            'DejaVuSans-BoldOblique.ufm',
        ];
        
        $copied = 0;
        foreach ($fontsToCopy as $font) {
            $source = $vendorFontDir . '/' . $font;
            $dest = $fontDir . '/' . $font;
            
            if (file_exists($source)) {
                if (!file_exists($dest) || filemtime($source) > filemtime($dest)) {
                    copy($source, $dest);
                    $copied++;
                    $this->line("Copied: {$font}");
                }
            }
        }
        
        if ($copied > 0) {
            $this->info("Copied {$copied} font file(s) to storage/fonts");
        } else {
            $this->info("All fonts are already up to date");
        }
        
        // Verify fonts are accessible
        $this->info("\nVerifying fonts...");
        $fontFiles = glob($fontDir . '/DejaVuSans*.ttf');
        $this->info("Found " . count($fontFiles) . " DejaVu Sans font file(s)");
        
        foreach ($fontFiles as $fontFile) {
            $this->line("  - " . basename($fontFile));
        }
        
        $this->info("\nFonts loaded successfully! DomPDF should now be able to use 'dejavu sans' font.");
        
        return 0;
    }
}
