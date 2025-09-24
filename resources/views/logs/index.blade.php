<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyGear - Application Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .log-entry {
            transition: all 0.3s ease;
        }
        .log-entry:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .level-emergency { @apply bg-red-100 border-red-500 text-red-800; }
        .level-alert { @apply bg-orange-100 border-orange-500 text-orange-800; }
        .level-critical { @apply bg-red-100 border-red-500 text-red-800; }
        .level-error { @apply bg-red-50 border-red-400 text-red-700; }
        .level-warning { @apply bg-yellow-50 border-yellow-400 text-yellow-700; }
        .level-notice { @apply bg-blue-50 border-blue-400 text-blue-700; }
        .level-info { @apply bg-green-50 border-green-400 text-green-700; }
        .level-debug { @apply bg-gray-50 border-gray-400 text-gray-700; }
        
        .search-highlight {
            background-color: yellow;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg mb-8 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-blue-500 text-white p-3 rounded-full">
                        <i class="fas fa-file-alt text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">EasyGear Application Logs</h1>
                        <p class="text-gray-600">Monitor and analyze application logs in real-time</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Last Updated</div>
                    <div class="text-lg font-semibold text-gray-800">{{ date('M d, Y H:i:s') }}</div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-lg mb-8 p-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Log File Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-file mr-1"></i> Log File
                        </label>
                        <select name="file" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($logFiles as $logFile)
                                <option value="{{ $logFile['name'] }}" {{ $selectedLog === $logFile['name'] ? 'selected' : '' }}>
                                    {{ $logFile['name'] }} 
                                    ({{ number_format($logFile['size'] / 1024, 2) }} KB)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Log Level Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-layer-group mr-1"></i> Log Level
                        </label>
                        <select name="level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Levels</option>
                            <option value="emergency" {{ $level === 'emergency' ? 'selected' : '' }}>Emergency</option>
                            <option value="alert" {{ $level === 'alert' ? 'selected' : '' }}>Alert</option>
                            <option value="critical" {{ $level === 'critical' ? 'selected' : '' }}>Critical</option>
                            <option value="error" {{ $level === 'error' ? 'selected' : '' }}>Error</option>
                            <option value="warning" {{ $level === 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="notice" {{ $level === 'notice' ? 'selected' : '' }}>Notice</option>
                            <option value="info" {{ $level === 'info' ? 'selected' : '' }}>Info</option>
                            <option value="debug" {{ $level === 'debug' ? 'selected' : '' }}>Debug</option>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-search mr-1"></i> Search
                        </label>
                        <input type="text" name="search" value="{{ $search }}" 
                               placeholder="Search in logs..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Filter Button -->
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition duration-200 flex items-center justify-center space-x-2">
                            <i class="fas fa-filter"></i>
                            <span>Filter</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Log Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            @php
                $totalLogs = count($logs);
                $levels = ['error' => 0, 'warning' => 0, 'info' => 0, 'debug' => 0];
                foreach($logs as $log) {
                    $logLevel = strtolower($log['level']);
                    if(isset($levels[$logLevel])) {
                        $levels[$logLevel]++;
                    }
                }
            @endphp
            
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $totalLogs }}</div>
                <div class="text-gray-600">Total Logs</div>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-red-600">{{ $levels['error'] }}</div>
                <div class="text-gray-600">Errors</div>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-yellow-600">{{ $levels['warning'] }}</div>
                <div class="text-gray-600">Warnings</div>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-green-600">{{ $levels['info'] }}</div>
                <div class="text-gray-600">Info</div>
            </div>
        </div>

        <!-- Log Actions -->
        <div class="bg-white rounded-lg shadow-lg mb-8 p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Log Actions</h2>
                <div class="space-x-3">
                    <a href="{{ route('logs.download', $selectedLog) }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md transition duration-200">
                        <i class="fas fa-download mr-2"></i> Download
                    </a>
                    <button onclick="refreshLogs()" 
                            class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md transition duration-200">
                        <i class="fas fa-sync-alt mr-2"></i> Refresh
                    </button>
                    <button onclick="clearLogs('{{ $selectedLog }}')" 
                            class="inline-flex items-center px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md transition duration-200">
                        <i class="fas fa-trash mr-2"></i> Clear Logs
                    </button>
                </div>
            </div>
        </div>

        <!-- Log Entries -->
        <div class="space-y-4">
            @if(count($logs) > 0)
                @foreach($logs as $index => $log)
                    <div class="log-entry level-{{ strtolower($log['level']) }} bg-white rounded-lg shadow-lg border-l-4 p-6 fade-in" 
                         style="animation-delay: {{ $index * 0.1 }}s">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-4 mb-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium level-{{ strtolower($log['level']) }}">
                                        @switch(strtolower($log['level']))
                                            @case('error')
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                @break
                                            @case('warning')
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                @break
                                            @case('info')
                                                <i class="fas fa-info-circle mr-1"></i>
                                                @break
                                            @case('debug')
                                                <i class="fas fa-bug mr-1"></i>
                                                @break
                                            @default
                                                <i class="fas fa-circle mr-1"></i>
                                        @endswitch
                                        {{ strtoupper($log['level']) }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $log['datetime']->format('M d, Y H:i:s') }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-server mr-1"></i>
                                        {{ $log['environment'] }}
                                    </span>
                                </div>
                                
                                <div class="text-gray-800 mb-3">
                                    <strong>Message:</strong>
                                    <div class="mt-1 font-mono text-sm bg-gray-50 p-3 rounded">
                                        {!! $search ? str_ireplace($search, '<span class="search-highlight">' . $search . '</span>', e($log['message'])) : e($log['message']) !!}
                                    </div>
                                </div>
                                
                                @if(!empty(trim($log['context'])))
                                    <div class="mt-4">
                                        <button onclick="toggleContext({{ $index }})" 
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            <i class="fas fa-chevron-down mr-1" id="chevron-{{ $index }}"></i>
                                            Show Context
                                        </button>
                                        <div id="context-{{ $index }}" class="hidden mt-2 font-mono text-sm bg-gray-100 p-3 rounded overflow-x-auto">
                                            <pre>{!! $search ? str_ireplace($search, '<span class="search-highlight">' . $search . '</span>', e($log['context'])) : e($log['context']) !!}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="bg-white rounded-lg shadow-lg p-12 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-search text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No logs found</h3>
                    <p class="text-gray-500">
                        @if($search || $level)
                            No logs match your current filters. Try adjusting your search criteria.
                        @else
                            No logs are available for the selected file.
                        @endif
                    </p>
                </div>
            @endif
        </div>

        <!-- Auto-refresh notice -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <i class="fas fa-info-circle mr-1"></i>
            Logs are automatically limited to 500 entries for performance. Use search and filters to find specific entries.
        </div>
    </div>

    <script>
        function toggleContext(index) {
            const context = document.getElementById(`context-${index}`);
            const chevron = document.getElementById(`chevron-${index}`);
            
            if (context.classList.contains('hidden')) {
                context.classList.remove('hidden');
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
            } else {
                context.classList.add('hidden');
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            }
        }

        function refreshLogs() {
            window.location.reload();
        }

        function clearLogs(fileName) {
            if (confirm(`Are you sure you want to clear the log file "${fileName}"? This action cannot be undone.`)) {
                window.location.href = `/logs/clear/${fileName}`;
            }
        }

        // Auto-refresh every 30 seconds (optional)
        // setInterval(refreshLogs, 30000);
    </script>
</body>
</html>