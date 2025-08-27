<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Model Testing - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white">
    <div class="min-h-screen py-8">
        <div class="max-w-6xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    🤖 AI Model Testing
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Test all available AI models to check their availability and functionality
                </p>
                <div class="mt-4 flex items-center gap-4">
                    <a href="/chat" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        ← Back to Chat
                    </a>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        API Key: {{ $apiKey ? '✅ Configured (' . substr($apiKey, 0, 10) . '...)' : '❌ Not configured' }}
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold mb-2">Test Configuration</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Found {{ $models->count() }} active models in database
                        </p>
                    </div>
                    <button 
                        id="runTests" 
                        onclick="runAllTests()"
                        class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-medium transition-colors"
                    >
                        🚀 Run All Tests
                    </button>
                </div>
                
                <div id="progress" class="hidden mt-4">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <div id="progressText" class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        Starting tests...
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div id="results" class="space-y-4">
                @foreach($models as $model)
                    <div id="model-{{ $model->id }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-gray-300">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold">{{ $model->name }}</h3>
                                    @if($model->supports_images)
                                        <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-1 rounded text-xs font-medium">
                                            🖼️ Vision
                                        </span>
                                    @endif
                                    <div id="status-{{ $model->id }}" class="status-badge bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded text-xs font-medium">
                                        ⏳ Waiting
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $model->model_id }}</code>
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $model->description }}
                                </p>
                                <div id="response-{{ $model->id }}" class="hidden mt-3 p-3 bg-gray-50 dark:bg-gray-900 rounded border">
                                    <div class="response-content text-sm"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Summary -->
            <div id="summary" class="hidden mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">📊 Test Summary</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <div id="successCount" class="text-2xl font-bold text-green-600 dark:text-green-400">0</div>
                        <div class="text-sm text-green-600 dark:text-green-400">Successful</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 dark:bg-red-900/30 rounded-lg">
                        <div id="failedCount" class="text-2xl font-bold text-red-600 dark:text-red-400">0</div>
                        <div class="text-sm text-red-600 dark:text-red-400">Failed</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <div id="totalCount" class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $models->count() }}</div>
                        <div class="text-sm text-blue-600 dark:text-blue-400">Total</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let testRunning = false;

        async function runAllTests() {
            if (testRunning) return;
            
            testRunning = true;
            const button = document.getElementById('runTests');
            const progress = document.getElementById('progress');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const summary = document.getElementById('summary');
            
            // Reset UI
            button.disabled = true;
            button.textContent = '⏳ Running Tests...';
            progress.classList.remove('hidden');
            summary.classList.add('hidden');
            
            // Reset all model statuses
            document.querySelectorAll('[id^="model-"]').forEach(el => {
                el.className = 'bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-gray-300';
            });
            document.querySelectorAll('[id^="status-"]').forEach(el => {
                el.className = 'status-badge bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded text-xs font-medium';
                el.textContent = '⏳ Testing...';
            });
            document.querySelectorAll('[id^="response-"]').forEach(el => {
                el.classList.add('hidden');
            });

            try {
                const response = await fetch('/test-models', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                
                if (data.error) {
                    progressText.textContent = '❌ Error: ' + data.error;
                    return;
                }

                // Update results
                let successCount = 0;
                let failedCount = 0;

                data.results.forEach((result, index) => {
                    const modelEl = document.querySelector(`[id^="model-"][id*="${result.model_id.replace(/[^a-zA-Z0-9]/g, '')}"]`) || 
                                   document.querySelectorAll('[id^="model-"]')[index];
                    const statusEl = document.querySelector(`[id^="status-"][id*="${result.model_id.replace(/[^a-zA-Z0-9]/g, '')}"]`) || 
                                    document.querySelectorAll('[id^="status-"]')[index];
                    const responseEl = document.querySelector(`[id^="response-"][id*="${result.model_id.replace(/[^a-zA-Z0-9]/g, '')}"]`) || 
                                      document.querySelectorAll('[id^="response-"]')[index];

                    if (result.status === 'success') {
                        successCount++;
                        if (modelEl) modelEl.className = 'bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-green-500';
                        if (statusEl) {
                            statusEl.className = 'status-badge bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-1 rounded text-xs font-medium';
                            statusEl.textContent = '✅ Success';
                        }
                        if (responseEl) {
                            responseEl.classList.remove('hidden');
                            responseEl.querySelector('.response-content').innerHTML = `
                                <div class="font-medium text-green-600 dark:text-green-400 mb-1">Response:</div>
                                <div class="text-gray-700 dark:text-gray-300">${result.response}</div>
                            `;
                        }
                    } else {
                        failedCount++;
                        if (modelEl) modelEl.className = 'bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-red-500';
                        if (statusEl) {
                            statusEl.className = 'status-badge bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-2 py-1 rounded text-xs font-medium';
                            statusEl.textContent = '❌ Failed';
                        }
                        if (responseEl) {
                            responseEl.classList.remove('hidden');
                            responseEl.querySelector('.response-content').innerHTML = `
                                <div class="font-medium text-red-600 dark:text-red-400 mb-1">Error:</div>
                                <div class="text-gray-700 dark:text-gray-300 text-sm">${result.error}</div>
                            `;
                        }
                    }

                    // Update progress
                    const progressPercent = ((index + 1) / data.results.length) * 100;
                    progressBar.style.width = progressPercent + '%';
                    progressText.textContent = `Testing model ${index + 1} of ${data.results.length}: ${result.model}`;
                });

                // Show summary
                document.getElementById('successCount').textContent = successCount;
                document.getElementById('failedCount').textContent = failedCount;
                summary.classList.remove('hidden');
                
                progressText.textContent = `✅ Testing completed! ${successCount} successful, ${failedCount} failed`;

            } catch (error) {
                progressText.textContent = '❌ Error running tests: ' + error.message;
            } finally {
                testRunning = false;
                button.disabled = false;
                button.textContent = '🚀 Run All Tests';
                progressBar.style.width = '100%';
            }
        }
    </script>
</body>
</html>