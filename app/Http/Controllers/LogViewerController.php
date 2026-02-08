<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogViewerController extends Controller
{
    private const LOG_PASSWORD = 'DevEazy26';
    
    /**
     * Show login form
     */
    public function showLogin()
    {
        // If already authenticated, redirect to logs
        if (session('log_viewer_authenticated')) {
            return redirect()->route('logs.index');
        }
        
        return view('log-viewer-login');
    }
    
    /**
     * Handle login attempt
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);
        
        if ($request->password === self::LOG_PASSWORD) {
            session(['log_viewer_authenticated' => true]);
            return redirect()->route('logs.index');
        }
        
        return back()->with('error', 'Invalid password. Please try again.');
    }
    
    /**
     * Handle logout
     */
    public function logout()
    {
        session()->forget('log_viewer_authenticated');
        return redirect()->route('logs.login')->with('success', 'You have been logged out.');
    }
    
    /**
     * Display log viewer
     */
    public function index(Request $request)
    {
        // Check authentication
        if (!session('log_viewer_authenticated')) {
            return redirect()->route('logs.login');
        }
        
        $logPath = storage_path('logs/laravel.log');
        $lines = $request->get('lines', 100);
        $lines = (int) $lines;
        
        $data = [
            'logPath' => $logPath,
            'lines' => $lines,
            'logExists' => false,
            'fileSize' => 0,
            'totalLines' => 0,
            'lastModified' => 'N/A',
            'logLines' => [],
            'errorCount' => 0,
            'warningCount' => 0,
            'infoCount' => 0,
            'debugCount' => 0,
        ];
        
        if (!file_exists($logPath)) {
            return view('log-viewer', $data);
        }
        
        $data['logExists'] = true;
        
        // Get file info
        $fileSize = filesize($logPath);
        $data['fileSize'] = number_format($fileSize / 1024 / 1024, 2);
        $data['lastModified'] = date('Y-m-d H:i:s', filemtime($logPath));
        
        // Read the last N lines
        $file = new \SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key() + 1;
        $data['totalLines'] = $totalLines;
        
        $startLine = max(0, $totalLines - $lines);
        $logLines = [];
        
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = $file->current();
            if (trim($line)) {
                $logLines[] = $line;
            }
            $file->next();
        }
        
        $data['logLines'] = $logLines;
        
        // Count log levels
        foreach ($logLines as $line) {
            if (stripos($line, '.ERROR:') !== false) {
                $data['errorCount']++;
            } elseif (stripos($line, '.WARNING:') !== false) {
                $data['warningCount']++;
            } elseif (stripos($line, '.INFO:') !== false) {
                $data['infoCount']++;
            } elseif (stripos($line, '.DEBUG:') !== false) {
                $data['debugCount']++;
            }
        }
        
        return view('log-viewer', $data);
    }
    
    /**
     * Clear log file
     */
    public function clear()
    {
        // Check authentication
        if (!session('log_viewer_authenticated')) {
            return redirect()->route('logs.login');
        }
        
        $logPath = storage_path('logs/laravel.log');
        
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
        }
        
        return redirect()->route('logs.index')->with('success', 'Logs cleared successfully.');
    }
}
