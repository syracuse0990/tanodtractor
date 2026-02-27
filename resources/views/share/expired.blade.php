<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Link Expired</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .expired-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
            padding: 48px 40px;
            text-align: center;
            max-width: 420px;
        }
        .expired-card i {
            font-size: 56px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .expired-card h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
        }
        .expired-card p {
            font-size: 14px;
            color: #888;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="expired-card">
        <i class="fa-solid fa-clock"></i>
        <h2>Share Link Expired</h2>
        <p>This share link is no longer valid. Share links are valid for 1 hour after creation. Please request a new link from the device owner.</p>
    </div>
</body>
</html>
