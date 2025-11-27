<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class QueueMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:monitor 
                            {--show-failed : Show failed jobs}
                            {--show-pending : Show pending jobs}
                            {--show-processing : Show jobs currently being processed}
                            {--limit=10 : Limit number of results}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor queue status and jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Queue Monitor - Paystack Project');
        $this->newLine();

        // Show pending jobs
        if ($this->option('show-pending') || (!$this->option('show-failed') && !$this->option('show-processing'))) {
            $this->showPendingJobs();
        }

        // Show processing jobs
        if ($this->option('show-processing')) {
            $this->showProcessingJobs();
        }

        // Show failed jobs
        if ($this->option('show-failed')) {
            $this->showFailedJobs();
        }

        // Show queue statistics
        $this->showQueueStats();

        return Command::SUCCESS;
    }

    private function showPendingJobs(): void
    {
        $limit = (int) $this->option('limit');
        
        $pendingJobs = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'attempts', 'available_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($pendingJobs->isEmpty()) {
            $this->info('✅ No pending jobs');
            return;
        }

        $this->info("📋 Pending Jobs ({$pendingJobs->count()}):");
        $this->newLine();

        $headers = ['ID', 'Queue', 'Job Class', 'Attempts', 'Available At', 'Created At'];
        $rows = [];

        foreach ($pendingJobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['data']['commandName'] ?? 'Unknown';
            
            $rows[] = [
                $job->id,
                $job->queue,
                class_basename($jobClass),
                $job->attempts,
                date('Y-m-d H:i:s', $job->available_at),
                date('Y-m-d H:i:s', $job->created_at)
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    private function showProcessingJobs(): void
    {
        $limit = (int) $this->option('limit');
        
        $processingJobs = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'created_at')
            ->whereNotNull('reserved_at')
            ->orderBy('reserved_at', 'desc')
            ->limit($limit)
            ->get();

        if ($processingJobs->isEmpty()) {
            $this->info('✅ No jobs currently being processed');
            return;
        }

        $this->info("⚙️ Processing Jobs ({$processingJobs->count()}):");
        $this->newLine();

        $headers = ['ID', 'Queue', 'Job Class', 'Attempts', 'Started At', 'Created At'];
        $rows = [];

        foreach ($processingJobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['data']['commandName'] ?? 'Unknown';
            
            $rows[] = [
                $job->id,
                $job->queue,
                class_basename($jobClass),
                $job->attempts,
                date('Y-m-d H:i:s', $job->reserved_at),
                date('Y-m-d H:i:s', $job->created_at)
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    private function showFailedJobs(): void
    {
        $limit = (int) $this->option('limit');
        
        $failedJobs = DB::table('failed_jobs')
            ->select('id', 'uuid', 'connection', 'queue', 'failed_at')
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get();

        if ($failedJobs->isEmpty()) {
            $this->info('✅ No failed jobs');
            return;
        }

        $this->info("❌ Failed Jobs ({$failedJobs->count()}):");
        $this->newLine();

        $headers = ['ID', 'UUID', 'Connection', 'Queue', 'Failed At'];
        $rows = [];

        foreach ($failedJobs as $job) {
            $rows[] = [
                $job->id,
                substr($job->uuid, 0, 8) . '...',
                $job->connection,
                $job->queue,
                $job->failed_at
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    private function showQueueStats(): void
    {
        $totalPending = DB::table('jobs')->count();
        $totalProcessing = DB::table('jobs')->whereNotNull('reserved_at')->count();
        $totalFailed = DB::table('failed_jobs')->count();
        $totalBatches = DB::table('job_batches')->count();

        $this->info('📊 Queue Statistics:');
        $this->newLine();
        
        $this->line("Pending Jobs: <fg=blue>{$totalPending}</>");
        $this->line("Processing Jobs: <fg=yellow>{$totalProcessing}</>");
        $this->line("Failed Jobs: <fg=red>{$totalFailed}</>");
        $this->line("Job Batches: <fg=green>{$totalBatches}</>");
        
        $this->newLine();
        
        // Show queue breakdown by queue name
        $queueBreakdown = DB::table('jobs')
            ->select('queue', DB::raw('count(*) as count'))
            ->groupBy('queue')
            ->get();

        if ($queueBreakdown->isNotEmpty()) {
            $this->info('📋 Jobs by Queue:');
            foreach ($queueBreakdown as $queue) {
                $this->line("  {$queue->queue}: <fg=blue>{$queue->count}</>");
            }
        }
    }
}







