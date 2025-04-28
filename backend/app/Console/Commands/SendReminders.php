<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Log;

class SendReminders extends Command
{
    protected $signature = 'app:send-reminders {--debug : Output debug information}';
    protected $description = 'Send due reminders for events';

    protected $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    public function handle()
    {
        Log::info('SendReminders command running at ' . now());
        
        $debug = $this->option('debug');
        $count = $this->reminderService->sendDueReminders($debug);
        
        $this->info("Processed {$count} reminders");
        Log::info("Processed {$count} reminders");
        
        return Command::SUCCESS;
    }
}