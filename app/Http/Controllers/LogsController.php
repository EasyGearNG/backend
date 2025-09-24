<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogsController extends Controller
{
    /**
     * Display logs with search functionality
     */
    public function index(Request $request)
    {
        $logPath = storage_path('logs');
        $logFiles = [];
        
        if (File::exists($logPath)) {
            $files = File::files($logPath);
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $logFiles[] = [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'size' => $file->getSize(),
                        'modified' => Carbon::createFromTimestamp($file->getMTime())
                    ];
                }
            }
        }
        
        // Sort by modified date (most recent first)
        usort($logFiles, function($a, $b) {
            return $b['modified']->timestamp <=> $a['modified']->timestamp;
        });
        
        $selectedLog = $request->get('file', 'laravel.log');
        $search = $request->get('search', '');
        $level = $request->get('level', '');
        
        $logs = $this->parseLogs($selectedLog, $search, $level);
        
        return view('logs.index', compact('logFiles', 'logs', 'selectedLog', 'search', 'level'));
    }
    
    /**
     * Parse log file content
     */
    private function parseLogs($fileName, $search = '', $level = '')
    {
        $logPath = storage_path("logs/{$fileName}");
        $logs = [];
        
        if (!File::exists($logPath)) {
            return $logs;
        }
        
        $content = File::get($logPath);
        $lines = explode("\n", $content);
        
        $currentLog = null;
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)$/';
        
        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $matches)) {
                // Save previous log if exists
                if ($currentLog !== null) {
                    $logs[] = $currentLog;
                }
                
                // Start new log entry
                $currentLog = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2],
                    'level' => strtoupper($matches[3]),
                    'message' => $matches[4],
                    'context' => '',
                    'datetime' => Carbon::createFromFormat('Y-m-d H:i:s', $matches[1])
                ];
            } else if ($currentLog !== null && !empty(trim($line))) {
                // Add to context if it's continuation of previous log
                $currentLog['context'] .= $line . "\n";
            }
        }
        
        // Add the last log
        if ($currentLog !== null) {
            $logs[] = $currentLog;
        }
        
        // Filter by search term
        if (!empty($search)) {
            $logs = array_filter($logs, function($log) use ($search) {
                return stripos($log['message'], $search) !== false || 
                       stripos($log['context'], $search) !== false;
            });
        }
        
        // Filter by level
        if (!empty($level)) {
            $logs = array_filter($logs, function($log) use ($level) {
                return strtolower($log['level']) === strtolower($level);
            });
        }
        
        // Sort by timestamp (most recent first)
        usort($logs, function($a, $b) {
            return $b['datetime']->timestamp <=> $a['datetime']->timestamp;
        });
        
        return array_slice($logs, 0, 500); // Limit to 500 entries for performance
    }
    
    /**
     * Download log file
     */
    public function download($fileName)
    {
        $logPath = storage_path("logs/{$fileName}");
        
        if (File::exists($logPath)) {
            return response()->download($logPath);
        }
        
        abort(404, 'Log file not found');
    }
    
    /**
     * Clear log file
     */
    public function clear($fileName)
    {
        $logPath = storage_path("logs/{$fileName}");
        
        if (File::exists($logPath)) {
            File::put($logPath, '');
            return redirect()->route('logs.index')->with('success', "Log file '{$fileName}' has been cleared.");
        }
        
        return redirect()->route('logs.index')->with('error', 'Log file not found.');
    }
    
    /**
     * Get log statistics
     */
    public function stats($fileName)
    {
        $logs = $this->parseLogs($fileName);
        $stats = [
            'total' => count($logs),
            'levels' => []
        ];
        
        foreach ($logs as $log) {
            $level = strtolower($log['level']);
            if (!isset($stats['levels'][$level])) {
                $stats['levels'][$level] = 0;
            }
            $stats['levels'][$level]++;
        }
        
        return response()->json($stats);
    }
}
