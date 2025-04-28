<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\EventReminder;
use App\Models\Event;

class TestController extends Controller
{
    public function testEmail()
    {
        // Create a test event
        $event = new Event();
        $event->title = "Test Event";
        $event->description = "This is a test email via Brevo";
        $event->start_time = now()->addHour();
        $event->end_time = now()->addHours(2);
        
        try {
            // Send email to your address
            Mail::to('ercmanis3@inbox.lv')->send(new EventReminder($event));
            
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ercmanis3@inbox.lv'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}