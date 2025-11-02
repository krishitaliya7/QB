<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIN Reset - QuantumBank</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #3b82f6;
            color: #ffffff;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3b82f6;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>QuantumBank</h1>
            <p>PIN Reset Request</p>
        </div>
        <div class="content">
            <p>Hello <?php echo htmlspecialchars($username); ?>,</p>
            <p>You have requested to reset your PIN. Click the link below to proceed with the reset:</p>
            <a href="<?php echo htmlspecialchars($link); ?>" class="button">Reset PIN</a>
            <p>If you did not request this, please ignore this email.</p>
            <p>This link will expire in 2 minutes.</p>
        </div>
        <div class="footer">
            <p>&copy; 2023 QuantumBank. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
