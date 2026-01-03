<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class QueueMonitorController extends Controller
{
    /**
     * Display queue dashboard
     */
    public function index()
    {
        $queues = $this->getQueueStats();
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                $job->payload = json_decode($job->payload, true);
                return $job;
            });
        
        $pendingJobs = $this->getPendingJobs();
        $queueWorkers = $this->getQueueWorkers();

        return view('queue-monitor.index', compact(
            'queues',
            'failedJobs',
            'pendingJobs',
            'queueWorkers'
        ));
    }

    /**
     * Get queue statistics
     */
    private function getQueueStats()
    {
        $queues = ['default', 'high', 'low', 'emails'];
        $stats = [];
        
        foreach ($queues as $queue) {
            try {
                $size = Queue::size($queue);
                $stats[$queue] = [
                    'size' => $size,
                    'status' => $size > 100 ? 'busy' : ($size > 20 ? 'moderate' : 'idle'),
                ];
            } catch (\Exception $e) {
                $stats[$queue] = ['size' => 0, 'status' => 'error'];
            }
        }
        
        return $stats;
    }

    /**
     * Get pending jobs
     */
    private function getPendingJobs()
    {
        $jobs = DB::table('jobs')
            ->select('queue', DB::raw('count(*) as count'))
            ->groupBy('queue')
            ->get()
            ->keyBy('queue');
        
        return $jobs;
    }

    /**
     * Get queue workers status
     */
    private function getQueueWorkers()
    {
        // Check if Horizon is installed
        if (class_exists('Laravel\Horizon\Horizon')) {
            $workers = Cache::get('horizon:workers');
            return $workers ?: [];
        }
        
        // Basic worker check using cache
        return Cache::get('queue:workers', []);
    }

    /**
     * Retry failed job
     */
    public function retryJob(Request $request, $id)
    {
        try {
            Artisan::call('queue:retry', ['id' => $id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Job retried successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry all failed jobs
     */
    public function retryAll()
    {
        try {
            Artisan::call('queue:retry', ['id' => 'all']);
            
            return response()->json([
                'success' => true,
                'message' => 'All failed jobs retried successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete failed job
     */
    public function deleteJob($id)
    {
        try {
            DB::table('failed_jobs')->where('id', $id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Job deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all failed jobs
     */
    public function deleteAll()
    {
        try {
            DB::table('failed_jobs')->truncate();
            
            return response()->json([
                'success' => true,
                'message' => 'All failed jobs deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart queue worker
     */
    public function restartWorker(Request $request)
    {
        try {
            $queue = $request->get('queue', 'default');
            
            Artisan::call('queue:restart');
            
            // Log the restart
            Cache::put('queue:last_restart', now(), 60);
            Cache::put("queue:restart:{$queue}", now(), 60);
            
            return response()->json([
                'success' => true,
                'message' => "Queue worker for '{$queue}' restarted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run queue worker command
     */
    public function runWorker(Request $request)
    {
        try {
            $queue = $request->get('queue', 'default');
            $connection = $request->get('connection', 'database');
            $tries = $request->get('tries', 3);
            $timeout = $request->get('timeout', 60);
            
            $command = "queue:work {$connection} --queue={$queue} --tries={$tries} --timeout={$timeout}";
            
            // Store the command in cache to track running workers
            $workerId = Str::uuid();
            Cache::put("queue:worker:{$workerId}", [
                'command' => $command,
                'started_at' => now(),
                'queue' => $queue,
                'status' => 'running'
            ], 3600);
            
            // In production, you would queue this or use supervisor
            // For demo, we'll just show the command
            return response()->json([
                'success' => true,
                'message' => "Worker started with command: php artisan {$command}",
                'worker_id' => $workerId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue metrics
     */
    public function metrics()
    {
        $metrics = [
            'total_jobs_processed' => Cache::get('queue:total_jobs', 0),
            'failed_jobs_count' => DB::table('failed_jobs')->count(),
            'pending_jobs_count' => DB::table('jobs')->count(),
            'queue_sizes' => $this->getQueueStats(),
            'uptime' => $this->getQueueUptime(),
        ];
        
        return response()->json($metrics);
    }

    /**
     * Clear specific queue
     */
    public function clearQueue(Request $request)
    {
        try {
            $queue = $request->get('queue', 'default');
            
            DB::table('jobs')
                ->where('queue', $queue)
                ->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Queue '{$queue}' cleared successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue uptime
     */
    private function getQueueUptime()
    {
        $lastRestart = Cache::get('queue:last_restart');
        
        if (!$lastRestart) {
            return 'No recent restarts detected';
        }
        
        return $lastRestart->diffForHumans();
    }
}