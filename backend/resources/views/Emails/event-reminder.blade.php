<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #333;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .event-details {
            margin: 20px 0;
            padding: 15px;
            background-color: white;
            border-left: 4px solid #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Event Reminder</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>This is a reminder for your upcoming event:</p>
            
            <div class="event-details">
                <h2>{{ $event->title }}</h2>
                <p><strong>Date:</strong> {{ date('l, F j, Y', strtotime($event->start_time)) }}</p>
                <p><strong>Time:</strong> {{ date('h:i A', strtotime($event->start_time)) }} - {{ date('h:i A', strtotime($event->end_time)) }}</p>
                <p><strong>Description:</strong> {{ $event->description }}</p>
            </div>
            
            <p>Thank you for using our application!</p>
        </div>
    </div>
</body>
</html>