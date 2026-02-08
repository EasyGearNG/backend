<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Log Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            position: relative;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .logout-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .controls {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .controls label {
            font-weight: 600;
            color: #495057;
        }
        
        .controls select,
        .controls input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .controls button, .controls a.button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .controls button:hover, .controls a.button:hover {
            background: #5568d3;
        }
        
        .log-content {
            padding: 30px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 600px;
            overflow-y: auto;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        
        .log-line {
            padding: 4px 8px;
            margin: 2px 0;
            border-radius: 3px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .log-line.error {
            background: rgba(255, 66, 66, 0.1);
            color: #ff4242;
            border-left: 3px solid #ff4242;
        }
        
        .log-line.warning {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border-left: 3px solid #ffc107;
        }
        
        .log-line.info {
            background: rgba(33, 150, 243, 0.1);
            color: #2196f3;
            border-left: 3px solid #2196f3;
        }
        
        .log-line.debug {
            background: rgba(156, 39, 176, 0.1);
            color: #9c27b0;
            border-left: 3px solid #9c27b0;
        }
        
        .stats {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }
        
        .stat-value {
            font-weight: 700;
            font-size: 18px;
            color: #495057;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #6c757d;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #667eea;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s;
            opacity: 0;
            pointer-events: none;
        }
        
        .scroll-top.visible {
            opacity: 1;
            pointer-events: all;
        }
        
        .scroll-top:hover {
            background: #5568d3;
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ route('logs.logout') }}" class="logout-btn">🔓 Logout</a>
            <h1>📋 Laravel Log Viewer</h1>
            <p>View and monitor your application logs in real-time</p>
        </div>
        
        <div class="controls">
            <label for="lines">Lines to show:</label>
            <select id="lines" name="lines">
                <option value="50" {{ $lines == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $lines == 100 ? 'selected' : '' }}>100</option>
                <option value="200" {{ $lines == 200 ? 'selected' : '' }}>200</option>
                <option value="500" {{ $lines == 500 ? 'selected' : '' }}>500</option>
                <option value="1000" {{ $lines == 1000 ? 'selected' : '' }}>1000</option>
            </select>
            
            <label for="filter">Filter:</label>
            <select id="filter" name="filter">
                <option value="all">All</option>
                <option value="error">Errors Only</option>
                <option value="warning">Warnings Only</option>
                <option value="info">Info Only</option>
                <option value="debug">Debug Only</option>
            </select>
            
            <button onclick="location.reload()">🔄 Refresh</button>
            <form action="{{ route('logs.clear') }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all logs? This action cannot be undone.')">
                @csrf
                <button type="submit">🗑️ Clear Logs</button>
            </form>
        </div>

        @if(!$logExists)
            <div class="empty-state">
                <h3>No log file found</h3>
                <p>The log file will be created when the application generates its first log entry.</p>
                <p>Path: {{ $logPath }}</p>
            </div>
        @else
            <div class="stats">
                <div class="stat-item"><span class="stat-label">File Size:</span><span class="stat-value">{{ $fileSize }} MB</span></div>
                <div class="stat-item"><span class="stat-label">Total Lines:</span><span class="stat-value">{{ number_format($totalLines) }}</span></div>
                <div class="stat-item"><span class="stat-label">Showing:</span><span class="stat-value">{{ count($logLines) }}</span></div>
                <div class="stat-item"><span class="stat-label">Last Modified:</span><span class="stat-value">{{ $lastModified }}</span></div>
            </div>
            
            <div class="stats">
                <div class="stat-item"><span class="stat-label">🔴 Errors:</span><span class="stat-value">{{ $errorCount }}</span></div>
                <div class="stat-item"><span class="stat-label">🟡 Warnings:</span><span class="stat-value">{{ $warningCount }}</span></div>
                <div class="stat-item"><span class="stat-label">🔵 Info:</span><span class="stat-value">{{ $infoCount }}</span></div>
                <div class="stat-item"><span class="stat-label">🟣 Debug:</span><span class="stat-value">{{ $debugCount }}</span></div>
            </div>
            
            <div class="log-content" id="logContent">
                @if(empty($logLines))
                    <div class="empty-state"><p>No log entries to display</p></div>
                @else
                    @foreach($logLines as $line)
                        @php
                            $class = 'log-line';
                            if (stripos($line, '.ERROR:') !== false) {
                                $class .= ' error';
                            } elseif (stripos($line, '.WARNING:') !== false) {
                                $class .= ' warning';
                            } elseif (stripos($line, '.INFO:') !== false) {
                                $class .= ' info';
                            } elseif (stripos($line, '.DEBUG:') !== false) {
                                $class .= ' debug';
                            }
                        @endphp
                        <div class="{{ $class }}">{{ $line }}</div>
                    @endforeach
                @endif
            </div>
        @endif
    </div>
    
    <div class="scroll-top" id="scrollTop" onclick="scrollToTop()">
        ↑
    </div>

    <script>
        // Auto-scroll to bottom on load
        window.addEventListener('load', function() {
            const logContent = document.getElementById('logContent');
            if (logContent) {
                logContent.scrollTop = logContent.scrollHeight;
            }
        });
        
        // Show/hide scroll to top button
        const logContent = document.getElementById('logContent');
        const scrollTopBtn = document.getElementById('scrollTop');
        
        if (logContent) {
            logContent.addEventListener('scroll', function() {
                if (logContent.scrollTop > 300) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
            });
        }
        
        function scrollToTop() {
            logContent.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Handle lines selection
        document.getElementById('lines').addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('lines', this.value);
            window.location = url;
        });
        
        // Handle filter selection
        document.getElementById('filter').addEventListener('change', function() {
            const logLines = document.querySelectorAll('.log-line');
            const filter = this.value;
            
            logLines.forEach(line => {
                if (filter === 'all') {
                    line.style.display = 'block';
                } else if (filter === 'error' && line.classList.contains('error')) {
                    line.style.display = 'block';
                } else if (filter === 'warning' && line.classList.contains('warning')) {
                    line.style.display = 'block';
                } else if (filter === 'info' && line.classList.contains('info')) {
                    line.style.display = 'block';
                } else if (filter === 'debug' && line.classList.contains('debug')) {
                    line.style.display = 'block';
                } else {
                    line.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
