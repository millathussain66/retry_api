<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>Queue Monitor - Laravel</title>

        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- Font Awesome -->
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        />

        <!-- Alpine.js for interactivity -->
        <script
            defer
            src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"
        ></script>

        <style>
            .status-badge {
                @apply px-2 py-1 rounded-full text-xs font-semibold;
            }
            .status-idle {
                @apply bg-green-100 text-green-800;
            }
            .status-moderate {
                @apply bg-yellow-100 text-yellow-800;
            }
            .status-busy {
                @apply bg-red-100 text-red-800;
            }
            .status-error {
                @apply bg-gray-100 text-gray-800;
            }

            .job-card {
                @apply bg-white rounded-lg shadow-md p-4 mb-3 border-l-4;
            }
            .job-success {
                @apply border-green-500;
            }
            .job-failed {
                @apply border-red-500;
            }
            .job-pending {
                @apply border-blue-500;
            }

            .progress-bar {
                @apply h-2 bg-gray-200 rounded-full overflow-hidden;
            }
            .progress-fill {
                @apply h-full bg-blue-500 transition-all duration-300;
            }

            .refresh-animation {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from {
                    transform: rotate(0deg);
                }
                to {
                    transform: rotate(360deg);
                }
            }
        </style>
    </head>
    <body class="bg-gray-50" x-data="queueMonitor()" x-init="init()">
        <div class="min-h-screen">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center py-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                <i class="fas fa-tasks mr-2"></i>Queue Monitor
                            </h1>
                            <p class="text-sm text-gray-600 mt-1">
                                Monitor and manage your Laravel queues in
                                real-time
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button
                                @click="refreshData()"
                                :class="{ 'refresh-animation': refreshing }"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center"
                            >
                                <i class="fas fa-sync-alt mr-2"></i>
                                <span
                                    x-text="refreshing ? 'Refreshing...' : 'Refresh'"
                                ></span>
                            </button>
                            <div class="text-sm text-gray-600">
                                Last updated: <span x-text="lastUpdated"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Stats Cards -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
                >
                    <!-- Total Jobs -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">
                                    Total Jobs Processed
                                </p>
                                <p
                                    class="text-3xl font-bold text-gray-900"
                                    x-text="metrics.total_jobs_processed || '0'"
                                ></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i
                                    class="fas fa-database text-blue-600 text-xl"
                                ></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Jobs -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">
                                    Pending Jobs
                                </p>
                                <p
                                    class="text-3xl font-bold text-gray-900"
                                    x-text="metrics.pending_jobs_count || '0'"
                                ></p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i
                                    class="fas fa-clock text-yellow-600 text-xl"
                                ></i>
                            </div>
                        </div>
                    </div>

                    <!-- Failed Jobs -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Failed Jobs</p>
                                <p
                                    class="text-3xl font-bold text-gray-900"
                                    x-text="metrics.failed_jobs_count || '0'"
                                ></p>
                            </div>
                            <div class="bg-red-100 p-3 rounded-full">
                                <i
                                    class="fas fa-exclamation-triangle text-red-600 text-xl"
                                ></i>
                            </div>
                        </div>
                    </div>

                    <!-- Queue Uptime -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">
                                    Queue Uptime
                                </p>
                                <p
                                    class="text-lg font-bold text-gray-900"
                                    x-text="metrics.uptime || 'N/A'"
                                ></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i
                                    class="fas fa-play-circle text-green-600 text-xl"
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Queue Status Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Queue Stats & Controls -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Queue Statistics -->
                        <div class="bg-white rounded-xl shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-chart-bar mr-2"></i>Queue
                                    Statistics
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <template
                                        x-for="(queue, name) in queueStats"
                                        :key="name"
                                    >
                                        <div
                                            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                                        >
                                            <div class="flex items-center">
                                                <div
                                                    class="w-3 h-3 rounded-full mr-3"
                                                    :class="{
                                                     'bg-green-500': queue.status === 'idle',
                                                     'bg-yellow-500': queue.status === 'moderate',
                                                     'bg-red-500': queue.status === 'busy',
                                                     'bg-gray-500': queue.status === 'error'
                                                 }"
                                                ></div>
                                                <div>
                                                    <span
                                                        class="font-medium text-gray-900"
                                                        x-text="name"
                                                    ></span>
                                                    <div
                                                        class="text-sm text-gray-600"
                                                    >
                                                        <span
                                                            x-text="queue.size"
                                                        ></span>
                                                        jobs in queue
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-center space-x-2"
                                            >
                                                <span
                                                    class="status-badge"
                                                    :class="{
                                                      'status-idle': queue.status === 'idle',
                                                      'status-moderate': queue.status === 'moderate',
                                                      'status-busy': queue.status === 'busy',
                                                      'status-error': queue.status === 'error'
                                                  }"
                                                    x-text="queue.status"
                                                ></span>
                                                <button
                                                    @click="clearQueue(name)"
                                                    class="text-red-600 hover:text-red-800"
                                                    title="Clear Queue"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Queue Controls -->
                        <div class="bg-white rounded-xl shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-cogs mr-2"></i>Queue
                                    Controls
                                </h2>
                            </div>
                            <div class="p-6">
                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <!-- Restart Worker -->
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <h3
                                            class="font-medium text-blue-900 mb-2"
                                        >
                                            Restart Queue Worker
                                        </h3>
                                        <div class="space-y-3">
                                            <select
                                                x-model="controlParams.queue"
                                                class="w-full border border-blue-200 rounded-lg px-3 py-2 bg-white"
                                            >
                                                <option value="default">
                                                    default
                                                </option>
                                                <option value="high">
                                                    high
                                                </option>
                                                <option value="low">low</option>
                                                <option value="emails">
                                                    emails
                                                </option>
                                            </select>
                                            <button
                                                @click="restartWorker()"
                                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center"
                                            >
                                                <i class="fas fa-redo mr-2"></i>
                                                Restart Worker
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Run Worker -->
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <h3
                                            class="font-medium text-green-900 mb-2"
                                        >
                                            Start New Worker
                                        </h3>
                                        <div class="space-y-2">
                                            <input
                                                type="number"
                                                x-model="controlParams.tries"
                                                placeholder="Max Tries (default: 3)"
                                                class="w-full border border-green-200 rounded-lg px-3 py-2"
                                            />
                                            <input
                                                type="number"
                                                x-model="controlParams.timeout"
                                                placeholder="Timeout (seconds)"
                                                class="w-full border border-green-200 rounded-lg px-3 py-2"
                                            />
                                            <button
                                                @click="runWorker()"
                                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center"
                                            >
                                                <i class="fas fa-play mr-2"></i>
                                                Start Worker
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Failed Jobs -->
                    <div class="space-y-8">
                        <!-- Failed Jobs Panel -->
                    
                        <div
                            class="bg-white rounded-xl shadow-lg overflow-hidden"
                        >
                            <div
                                class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                                >
                                    <div class="flex items-center">
                                        <div
                                            class="bg-red-100 p-2 rounded-lg mr-3"
                                        >
                                            <i
                                                class="fas fa-exclamation-circle text-red-600 text-lg"
                                            ></i>
                                        </div>
                                        <div>
                                            <h2
                                                class="text-xl font-bold text-gray-900"
                                            >
                                                Failed Jobs
                                            </h2>
                                          
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            @click="retryAllFailed()"
                                            class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md transform hover:-translate-y-0.5"
                                        >
                                            <i
                                                class="fas fa-redo mr-2 text-sm"
                                            ></i>
                                            <span>Retry All</span>
                                        </button>
                                        <button
                                            @click="deleteAllFailed()"
                                            class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md transform hover:-translate-y-0.5"
                                        >
                                            <i
                                                class="fas fa-trash mr-2 text-sm"
                                            ></i>
                                            <span>Clear All</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div
                                    x-show="failedJobs.length === 0"
                                    class="text-center py-12"
                                >
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4"
                                    >
                                        <i
                                            class="fas fa-check-circle text-3xl text-green-600"
                                        ></i>
                                    </div>
                                    <h3
                                        class="text-lg font-semibold text-gray-900 mb-2"
                                    >
                                        No failed jobs
                                    </h3>
                                    
                                </div>

                    
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white rounded-xl shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-bolt mr-2"></i>Quick
                                    Actions
                                </h2>
                            </div>
                            <div class="p-4">
                                <div class="space-y-3">
                                    <button
                                        @click="runCommand('queue:work --stop-when-empty')"
                                        class="w-full bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-3 rounded-lg flex items-center justify-center"
                                    >
                                        <i class="fas fa-forward mr-2"></i>
                                        Process Queue Work
                                    </button>

                                    <button
                                        @click="runCommand('queue:flush')"
                                        class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-lg flex items-center justify-center"
                                    >
                                        <i class="fas fa-broom mr-2"></i> Flush
                                        All Failed Jobs
                                    </button>

                                    <button
                                        @click="runCommand('queue:prune-failed')"
                                        class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-lg flex items-center justify-center"
                                    >
                                        <i class="fas fa-cut mr-2"></i> Prune
                                        Old Failed Jobs
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Toast Notifications -->
        <div
            class="fixed bottom-4 right-4 space-y-2 z-50"
            x-show="toasts.length > 0"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    :class="{
                'bg-green-100 border-green-400 text-green-700': toast.type === 'success',
                'bg-red-100 border-red-400 text-red-700': toast.type === 'error',
                'bg-blue-100 border-blue-400 text-blue-700': toast.type === 'info'
            }"
                    class="border px-4 py-3 rounded-lg shadow-lg max-w-sm flex items-center justify-between"
                >
                    <div class="flex items-center">
                        <i
                            :class="{
                        'fas fa-check-circle': toast.type === 'success',
                        'fas fa-exclamation-circle': toast.type === 'error',
                        'fas fa-info-circle': toast.type === 'info'
                    }"
                            class="mr-2"
                        ></i>
                        <span x-text="toast.message"></span>
                    </div>
                    <button
                        @click="removeToast(toast.id)"
                        class="ml-4 text-gray-500 hover:text-gray-700"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </template>
        </div>

        <script>
            function queueMonitor() {
                return {
                    refreshing: false,
                    lastUpdated: "Never",
                    queueStats: {},
                    failedJobs: [],
                    metrics: {},
                    controlParams: {
                        queue: "default",
                        tries: 3,
                        timeout: 60,
                        connection: "database",
                    },
                    toasts: [],
                    toastCounter: 0,
                    refreshInterval: null,

                    init() {
                        this.loadData();
                        // Auto-refresh every 30 seconds
                        this.refreshInterval = setInterval(
                            () => this.loadData(),
                            30000
                        );
                    },

                    async loadData() {
                        try {
                            this.refreshing = true;

                            // Load queue statistics
                            const statsResponse = await fetch(
                                '{{ route("queue-monitor.metrics") }}'
                            );
                            this.metrics = await statsResponse.json();

                            if (this.metrics.queue_sizes) {
                                this.queueStats = this.metrics.queue_sizes;
                            }

                            // Load failed jobs
                            const failedResponse = await fetch(
                                "/queue-monitor"
                            );
                            const html = await failedResponse.text();
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(
                                html,
                                "text/html"
                            );

                            // Update last updated time
                            this.lastUpdated = new Date().toLocaleTimeString();
                            this.refreshing = false;

                            this.showToast(
                                "Data refreshed successfully",
                                "success"
                            );
                        } catch (error) {
                            console.error("Failed to load queue data:", error);
                            this.showToast("Failed to refresh data", "error");
                            this.refreshing = false;
                        }
                    },

                    refreshData() {
                        clearInterval(this.refreshInterval);
                        this.loadData();
                        this.refreshInterval = setInterval(
                            () => this.loadData(),
                            30000
                        );
                    },

                    async retryJob(jobId) {
                        if (!confirm("Retry this failed job?")) return;

                        try {
                            const response = await fetch(
                                `/queue-monitor/retry/${jobId}`,
                                {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                    },
                                }
                            );

                            const result = await response.json();
                            if (result.success) {
                                this.showToast(result.message, "success");
                                this.loadData();
                            } else {
                                this.showToast(result.message, "error");
                            }
                        } catch (error) {
                            this.showToast("Failed to retry job", "error");
                        }
                    },

                    async retryAllFailed() {
                        if (!confirm("Retry all failed jobs?")) return;

                        try {
                            const response = await fetch(
                                '{{ route("queue-monitor.retry-all") }}',
                                {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                    },
                                }
                            );

                            const result = await response.json();
                            this.showToast(
                                result.message,
                                result.success ? "success" : "error"
                            );
                            if (result.success) this.loadData();
                        } catch (error) {
                            this.showToast("Failed to retry all jobs", "error");
                        }
                    },

                    async deleteJob(jobId) {
                        if (!confirm("Delete this failed job?")) return;

                        try {
                            const response = await fetch(
                                `/queue-monitor/delete/${jobId}`,
                                {
                                    method: "DELETE",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                    },
                                }
                            );

                            const result = await response.json();
                            this.showToast(
                                result.message,
                                result.success ? "success" : "error"
                            );
                            if (result.success) this.loadData();
                        } catch (error) {
                            this.showToast("Failed to delete job", "error");
                        }
                    },

                    async deleteAllFailed() {
                        if (
                            !confirm(
                                "Delete ALL failed jobs? This cannot be undone."
                            )
                        )
                            return;

                        try {
                            const response = await fetch(
                                '{{ route("queue-monitor.delete-all") }}',
                                {
                                    method: "DELETE",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                    },
                                }
                            );

                            const result = await response.json();
                            this.showToast(
                                result.message,
                                result.success ? "success" : "error"
                            );
                            if (result.success) this.loadData();
                        } catch (error) {
                            this.showToast(
                                "Failed to delete all jobs",
                                "error"
                            );
                        }
                    },

                    async restartWorker() {
                        try {
                            const response = await fetch(
                                '{{ route("queue-monitor.restart-worker") }}',
                                {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                    },
                                    body: JSON.stringify({
                                        queue: this.controlParams.queue,
                                    }),
                                }
                            );

                            const result = await response.json();
                            this.showToast(
                                result.message,
                                result.success ? "success" : "error"
                            );
                        } catch (error) {
                            this.showToast("Failed to restart worker", "error");
                        }
                    },

                    async runWorker() {
                        try {
                            const response = await fetch(
                                '{{ route("queue-monitor.run-worker") }}',
                                {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                    },
                                    body: JSON.stringify(this.controlParams),
                                }
                            );

                            const result = await response.json();
                            this.showToast(
                                result.message,
                                result.success ? "success" : "error"
                            );
                        } catch (error) {
                            this.showToast("Failed to start worker", "error");
                        }
                    },

                    async clearQueue(queueName) {
                        if (
                            !confirm(
                                `Clear all jobs from '${queueName}' queue?`
                            )
                        )
                            return;

                        try {
                            const response = await fetch(
                                '{{ route("queue-monitor.clear-queue") }}',
                                {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                    },
                                    body: JSON.stringify({ queue: queueName }),
                                }
                            );

                            const result = await response.json();
                            this.showToast(
                                result.message,
                                result.success ? "success" : "error"
                            );
                            if (result.success) this.loadData();
                        } catch (error) {
                            this.showToast("Failed to clear queue", "error");
                        }
                    },

                    async runCommand(command) {
                        try {
                            const response = await fetch(
                                "{{ url('/queue-monitor/run-command') }}",
                                {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                    },
                                    body: JSON.stringify({ command: command }),
                                }
                            );

                            const result = await response.json();
                            this.showToast(
                                result.message,
                                result.success ? "success" : "error"
                            );
                            if (result.success) this.loadData();
                        } catch (error) {
                            this.showToast("Failed to run command", "error");
                        }
                    },

                    showToast(message, type = "info") {
                        const id = ++this.toastCounter;
                        this.toasts.push({ id, message, type });

                        // Auto-remove toast after 5 seconds
                        setTimeout(() => {
                            this.removeToast(id);
                        }, 5000);
                    },

                    removeToast(id) {
                        this.toasts = this.toasts.filter(
                            (toast) => toast.id !== id
                        );
                    },

                    formatDate(dateString) {
                        if (!dateString) return "Unknown";
                        const date = new Date(dateString);
                        return date.toLocaleString();
                    },
                };
            }
        </script>
    </body>
</html>
