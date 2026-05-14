<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Parsedown;

// Initialize Parsedown for Markdown parsing
$parsedown = new Parsedown();

// Read the Markdown file
$markdownContent = file_get_contents(__DIR__ . '/storage/app/public/manuals/Store_Requisition_Management_User_Manual.md');

// Convert Markdown to HTML
$htmlContent = $parsedown->text($markdownContent);

// Create a complete HTML document with CSS styling
$htmlDocument = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Store Requisition Management System - User Manual</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            font-size: 28px;
        }
        
        h2 {
            color: #34495e;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 8px;
            margin-top: 30px;
            font-size: 22px;
        }
        
        h3 {
            color: #3498db;
            margin-top: 25px;
            font-size: 18px;
        }
        
        h4 {
            color: #e74c3c;
            margin-top: 20px;
            font-size: 16px;
        }
        
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        ul, ol {
            margin-bottom: 15px;
            padding-left: 30px;
        }
        
        li {
            margin-bottom: 5px;
        }
        
        code {
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e74c3c;
        }
        
        blockquote {
            border-left: 4px solid #3498db;
            padding-left: 20px;
            margin: 20px 0;
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-radius: 0 5px 5px 0;
        }
        
        .table-of-contents {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .intro-section {
            background-color: #e8f6f3;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
            border-left: 5px solid #27ae60;
        }
        
        .warning {
            background-color: #fdf2e9;
            padding: 15px;
            border-radius: 5px;
            border-left: 5px solid #e67e22;
            margin: 15px 0;
        }
        
        .note {
            background-color: #eaf2f8;
            padding: 15px;
            border-radius: 5px;
            border-left: 5px solid #3498db;
            margin: 15px 0;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            text-align: center;
            color: #7f8c8d;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        strong {
            color: #2c3e50;
        }
        
        em {
            color: #e74c3c;
            font-style: italic;
        }
        
        hr {
            border: none;
            height: 2px;
            background-color: #ecf0f1;
            margin: 30px 0;
        }
        
        @page {
            margin: 2cm;
            @top-right {
                content: 'Page ' counter(page);
                font-size: 12px;
                color: #7f8c8d;
            }
            @top-left {
                content: 'Store Requisition Management - User Manual';
                font-size: 12px;
                color: #7f8c8d;
            }
        }
    </style>
</head>
<body>
    <div class='intro-section'>
        <h1>📋 Store Requisition Management System</h1>
        <h2>Complete User Manual</h2>
        <p><strong>SmartAccounting System Module</strong></p>
        <p>Version 1.0 | November 2025</p>
    </div>
    
    " . $htmlContent . "
    
    <div class='footer'>
        <hr>
        <p><strong>SmartAccounting System</strong><br>
        Store Requisition Management Module<br>
        Generated on " . date('F j, Y \a\t g:i A') . "</p>
    </div>
</body>
</html>";

// Configure Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', true);

// Initialize Dompdf
$dompdf = new Dompdf($options);

// Load HTML content
$dompdf->loadHtml($htmlDocument);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Create output directory if it doesn't exist
$outputDir = __DIR__ . '/storage/app/public/manuals/';
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Save PDF to file
$pdfContent = $dompdf->output();
$pdfPath = $outputDir . 'Store_Requisition_Management_User_Manual.pdf';
file_put_contents($pdfPath, $pdfContent);

echo "PDF User Manual generated successfully!\n";
echo "Location: {$pdfPath}\n";
echo "File size: " . number_format(filesize($pdfPath) / 1024, 2) . " KB\n";

return $pdfPath;