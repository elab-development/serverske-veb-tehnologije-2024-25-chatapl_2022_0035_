<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova poruka</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }
        .message-box {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .sender-info {
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 10px;
        }
        .message-content {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #4F46E5;
        }
        .room-info {
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nova poruka</h1>
    </div>
    
    <div class="content">
        <p>Zdravo {{ $notifiable->name }}!</p>
        
        <div class="sender-info">
            {{ $sender->name }} vam je poslao novu poruku.
        </div>
        
        <div class="room-info">
            <strong>Soba:</strong> {{ $message->room->name }}
        </div>
        
        <div class="message-box">
            <div class="message-content">
                {{ $message->content }}
            </div>
        </div>
        
        <a href="{{ url('/rooms/' . $message->room_id) }}" class="button">
            Pogledaj poruku
        </a>
        
        <div class="footer">
            <p>Hvala što koristite našu aplikaciju!</p>
            <p>Ova poruka je automatski generisana. Molimo ne odgovarajte na nju.</p>
        </div>
    </div>
</body>
</html> 