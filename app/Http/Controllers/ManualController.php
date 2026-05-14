<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ManualController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Generate a new manual (admin only)
     */
    public function generateManual()
    {
        // Check if user has admin permissions
        if (!auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized');
        }

        try {
            // Run the manual generation script
            $output = shell_exec('cd ' . base_path() . ' && php generate_manual.php 2>&1');
            
            return response()->json([
                'success' => true,
                'message' => 'Manual generated successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate manual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual management page
     */
    public function index()
    {
        $manuals = [];

        return view('manuals.index', compact('manuals'));
    }

    private function getFileSize($filename)
    {
        $filePath = storage_path('app/public/manuals/' . $filename);
        if (file_exists($filePath)) {
            $bytes = filesize($filePath);
            if ($bytes >= 1024 * 1024) {
                return number_format($bytes / (1024 * 1024), 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' bytes';
            }
        }
        return 'Unknown';
    }

    private function getLastModified($filename)
    {
        $filePath = storage_path('app/public/manuals/' . $filename);
        if (file_exists($filePath)) {
            return date('M j, Y \a\t g:i A', filemtime($filePath));
        }
        return 'Unknown';
    }
}