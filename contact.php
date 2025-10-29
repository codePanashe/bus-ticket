<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | ZUPCO Bus Pre-Booking System</title>
    <style>
        body {font-family: 'Segoe UI', sans-serif; margin: 0; background: #f4f6f9;}
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            background: #1a5f23; padding: 0 30px; height: 70px;
        }
        .nav-left, .nav-right {display: flex; gap: 20px;}
        .nav-left a, .nav-right a {
            color: #fff; text-decoration: none; font-weight: 500; padding: 8px 12px;
        }
        .nav-left a:hover, .nav-right a:hover {background: rgba(255,255,255,0.1); border-radius: 4px;}
        .btn-login {background: #d32f2f; border-radius: 4px;}
        .btn-login:hover {background: #b71c1c;}

        .content {
            max-width: 800px; margin: 60px auto; background: white;
            padding: 40px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        h1 {color: #1a5f23; margin-bottom: 20px;}
        p {color: #444; line-height: 1.6; margin-bottom: 10px;}

        form {
            margin-top: 30px;
            display: flex; flex-direction: column; gap: 15px;
        }

        input, textarea {
            padding: 12px; font-size: 16px;
            border: 1px solid #ccc; border-radius: 5px;
        }

        button {
            background: #1a5f23; color: white;
            padding: 12px; border: none; border-radius: 5px;
            cursor: pointer; font-size: 16px;
        }

        button:hover {background: #134a1a;}

        .footer {
            text-align: center; padding: 20px; background: #333; color: white; margin-top: 60px;
        }
        @media(max-width: 768px) {
            .navbar {flex-direction: column; padding: 15px;}
            .nav-left, .nav-right {justify-content: center; flex-wrap: wrap;}
            .content {margin: 30px 15px; padding: 20px;}
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="nav-right">
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <div class="content">
        <h1>Contact Us</h1>
        <p>If you have any questions or need assistance with your booking, feel free to contact us:</p>
        <p><strong>Address:</strong> ZUPCO Head Office, Belvedere, Harare, Zimbabwe</p>
        <p><strong>Phone:</strong> +263 242 796 611 / 12</p>
        <p><strong>Email:</strong> support@zupco.co.zw</p>

        <form method="POST" action="send_message.php">
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="email" name="email" placeholder="Your Email" required>
            <textarea name="message" rows="5" placeholder="Your Message" required></textarea>
            <button type="submit">Send Message</button>
        </form>
    </div>

    <footer style="
    background: #1a5f23;
    color: white;
    text-align: center;
    padding: 15px 0;
    margin-top: 40px;
    font-size: 0.9em;
">
    <div style="max-width: 1200px; margin: 0 auto;">
        <a href="about.php" style="color: white; text-decoration: none; margin: 0 10px;">About ZUPCO</a> |
        <a href="contact.php" style="color: white; text-decoration: none; margin: 0 10px;">Contact Us</a> |
        <a href="register.php" style="color: white; text-decoration: none; margin: 0 10px;">Register</a> |
        <a href="login.php" style="color: white; text-decoration: none; margin: 0 10px;">Login</a>
    </div>
    <div style="margin-top: 10px; opacity: 0.8;">
        &copy; <?php echo date('Y'); ?> Zimbabwe United Passenger Company (ZUPCO). All rights reserved.
    </div>
</footer>
</body>
</html>
