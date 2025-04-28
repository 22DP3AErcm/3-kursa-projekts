<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reminder;
use Illuminate\Support\Facades\Mail;
use App\Mail\EventReminder;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReminderService
{
    /**
     * Send reminders that are due
     * 
     * @param bool $debug Enable detailed logging
     * @return int Number of reminders successfully sent
     */
    public function sendDueReminders($debug = false)
    {
        if ($debug) {
            Log::info('Starting reminder check at ' . now()->toDateTimeString());
        }
        
        $reminders = Reminder::with('event.user')
            ->where('sent', false)
            ->where('remind_at', '<=', now())
            ->get();
            
        if ($debug) {
            Log::info("Found {$reminders->count()} reminders due for sending");
            
            // Show all pending reminders for troubleshooting
            $pendingCount = Reminder::where('sent', false)->count();
            Log::info("Total unsent reminders in system: {$pendingCount}");
            
            if ($pendingCount > 0 && $reminders->count() == 0) {
                // This means we have reminders but none are due yet
                $sample = Reminder::where('sent', false)
                    ->orderBy('remind_at', 'asc')
                    ->first();
                    
                if ($sample) {
                    $timeUntilDue = now()->diffForHumans($sample->remind_at);
                    Log::info("Next reminder due {$timeUntilDue} (at {$sample->remind_at})");
                }
            }
        }
        
        $sentCount = 0;
        foreach ($reminders as $reminder) {
            $result = $this->sendReminder($reminder, $debug);
            if ($result) {
                $sentCount++;
            }
        }
        
        if ($debug || $sentCount > 0) {
            Log::info("Successfully sent {$sentCount} reminders");
        }
        
        return $sentCount;
    }
    
    /**
     * Send a specific reminder
     * 
     * @param Reminder $reminder
     * @param bool $debug Enable detailed logging
     * @return bool Success status
     */
    public function sendReminder(Reminder $reminder, $debug = false)
    {
        $event = $reminder->event;
        
        if (!$event) {
            if ($debug) Log::warning("Reminder {$reminder->id}: Event not found");
            return false;
        }
        
        $user = $event->user;
        
        if (!$user) {
            if ($debug) Log::warning("Reminder {$reminder->id}: User not found for event {$event->id}");
            return false;
        }
        
        // Skip if user has disabled this type of notification
        if ($this->shouldSkipBasedOnUserPreferences($user, $reminder->type)) {
            if ($debug) {
                Log::info("Reminder {$reminder->id}: User has disabled {$reminder->type} notifications");
            }
            $reminder->update(['sent' => true]);
            return false;
        }
        
        try {
            if ($debug) {
                Log::info("Sending {$reminder->type} reminder for event '{$event->title}' to user {$user->email}");
                Log::info("Event starts at: {$event->start_time}, reminder minutes before: {$reminder->minutes_before}");
            }
            
            $sent = false;
            if ($reminder->type === 'email') {
                $sent = $this->sendEmailReminder($user, $event, $reminder, $debug);
            } elseif ($reminder->type === 'sms') {
                $sent = $this->sendSmsReminder($user, $event, $reminder, $debug);
            }
            
            if ($sent) {
                // Mark as sent
                $reminder->update(['sent' => true]);
                if ($debug) Log::info("Marked reminder {$reminder->id} as sent");
                return true;
            } else {
                if ($debug) Log::warning("Failed to send reminder {$reminder->id}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error sending reminder {$reminder->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if we should skip sending based on user preferences
     */
    private function shouldSkipBasedOnUserPreferences($user, $type)
    {
        if ($type === 'email') {
            return !$user->email || ($user->email_notifications === false);
        } elseif ($type === 'sms') {
            return !$user->telephone || ($user->phone_notifications === false);
        }
        return true; // Skip unknown notification types
    }
    
    /**
     * Send email reminder
     */
    private function sendEmailReminder($user, $event, $reminder, $debug = false)
    {
        if (!$user->email) {
            if ($debug) Log::warning("User {$user->id} has no email address");
            return false;
        }
        
        try {
            if ($debug) Log::info("Sending email to {$user->email}");
            
            Mail::to($user->email)->send(new EventReminder($event, $reminder));
            
            if ($debug) Log::info("Email sent successfully");
            return true;
        } catch (\Exception $e) {
            Log::error("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send SMS reminder
     */
    private function sendSmsReminder($user, $event, $reminder, $debug = false)
    {
        if (!$user->telephone) {
            if ($debug) Log::warning("User {$user->id} has no telephone number");
            return false;
        }
        
        // Twilio integration
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilioNumber = env('TWILIO_NUMBER');
        
        if (!$sid || !$token || !$twilioNumber) {
            Log::error('Twilio credentials not configured');
            return false;
        }
        
        try {
            if ($debug) Log::info("Sending SMS to {$user->telephone}");
            
            $client = new \Twilio\Rest\Client($sid, $token);
            $message = $client->messages->create(
                $user->telephone,
                [
                    'from' => $twilioNumber,
                    'body' => "Reminder: {$event->title} at " . 
                        date('h:i A', strtotime($event->start_time)) . 
                        " on " . date('M j, Y', strtotime($event->start_time))
                ]
            );
            
            if ($debug) Log::info("SMS sent successfully");
            return true;
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a test reminder for debugging purposes
     * 
     * @param int $eventId
     * @param int $minutesFromNow When to trigger the reminder
     * @return Reminder|null
     */
    public function createTestReminder($eventId, $minutesFromNow = 2)
    {
        // Cast the parameter to integer to fix the Carbon error
        $minutesFromNow = (int) $minutesFromNow;
        
        $event = Event::find($eventId);
        if (!$event) {
            Log::error("Cannot create test reminder: Event {$eventId} not found");
            return null;
        }
        
        try {
            $reminder = new Reminder([
                'event_id' => $event->id,
                'type' => 'email',
                'minutes_before' => 5, // Doesn't matter for the test
                'remind_at' => Carbon::now()->addMinutes($minutesFromNow),
                'sent' => false
            ]);
            
            $reminder->save();
            Log::info("Created test reminder ID {$reminder->id} for event '{$event->title}'");
            Log::info("Will trigger at: " . $reminder->remind_at);
            
            return $reminder;
        } catch (\Exception $e) {
            Log::error("Failed to create test reminder: " . $e->getMessage());
            return null;
        }
    }
}