<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class QueueManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:manage 
                            {action : Action to perform (clear, retry, prune, stats)}
                            {--queue=default : Queue name to target}
                            {--hours=24 : Hours for pruning old jobs}';

    /**
     * The console command description.
     */
    protected $description = 'Manage queue jobs and maintenance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $queue = $this->option('queue');

        switch ($action) {
            case 'clear':
                return $this->clearQueue($queue);
            case 'retry':
                return $this->retryFailedJobs();
            case 'prune':
                return $this->pruneOldJobs();
            case 'stats':
                return $this->showStats();
            default:
                $this->error("Unknown action: {$action}");
                $this->line('Available actions: clear, retry, prune, stats');
                return Command::FAILURE;
        }
    }

    private function clearQueue(string $queue): int
    {
        $this->info("🧹 Clearing queue: {$queue}");

        $deleted = DB::table('jobs')
            ->where('queue', $queue)
            ->delete();

        $this->info("✅ Cleared {$deleted} jobs from queue '{$queue}'");
        return Command::SUCCESS;
    }

    private function retryFailedJobs(): int
    {
        $this->info('🔄 Retrying failed jobs...');

        try {
            Artisan::call('queue:retry', ['id' => 'all']);
            $this->info('✅ All failed jobs have been retried');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to retry jobs: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function pruneOldJobs(): int
    {
        $hours = (int) $this->option('hours');
        $this->info("🧹 Pruning jobs older than {$hours} hours...");

        $cutoffTime = now()->subHours($hours)->timestamp;

        // Prune completed jobs
        $deletedJobs = DB::table('jobs')
            ->where('created_at', '<', $cutoffTime)
            ->delete();

        // Prune old failed jobs
        $deletedFailed = DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subHours($hours))
            ->delete();

        $this->info("✅ Pruned {$deletedJobs} old jobs and {$deletedFailed} old failed jobs");
        return Command::SUCCESS;
    }

    private function showStats(): int
    {
        $this->info('📊 Queue Statistics:');
        $this->newLine();

        $stats = [
            'Pending Jobs' => DB::table('jobs')->count(),
            'Processing Jobs' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
            'Failed Jobs' => DB::table('failed_jobs')->count(),
            'Job Batches' => DB::table('job_batches')->count(),
        ];

        foreach ($stats as $label => $count) {
            $color = match($label) {
                'Pending Jobs' => 'blue',
                'Processing Jobs' => 'yellow',
                'Failed Jobs' => 'red',
                'Job Batches' => 'green',
                default => 'white'
            };
            $this->line("{$label}: <fg={$color}>{$count}</>");
        }

        // Show queue breakdown
        $this->newLine();
        $this->info('📋 Jobs by Queue:');
        $queueBreakdown = DB::table('jobs')
            ->select('queue', DB::raw('count(*) as count'))
            ->groupBy('queue')
            ->get();

        foreach ($queueBreakdown as $queue) {
            $this->line("  {$queue->queue}: <fg=blue>{$queue->count}</>");
        }

        return Command::SUCCESS;
    }
}







