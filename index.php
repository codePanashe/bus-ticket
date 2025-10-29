<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZUPCO Bus Pre-Booking System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1a5f23;
            padding: 0 30px;
            height: 70px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .nav-left {
            display: flex;
            gap: 25px;
        }

        .nav-left a, .nav-right a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .nav-left a:hover, .nav-right a:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-right {
            display: flex;
            gap: 15px;
        }

        .btn-login {
            background: #d32f2f;
        }

        .btn-login:hover {
            background: #b71c1c;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 80px 20px;
            background: linear-gradient(to bottom, #e8f5e9, #f4f6f9);
        }

        .hero h1 {
            font-size: 42px;
            color: #1a5f23;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 18px;
            color: #555;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }

        .btn-book {
            display: inline-block;
            background: #1a5f23;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-book:hover {
            background: #134a1a;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            background: #333;
            color: white;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 15px;
            }

            .nav-left, .nav-right {
                margin: 10px 0;
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }
        }
        .footer {
    background: #1a5f23;
    color: white;
    text-align: center;
    padding: 15px 0;
    margin-top: 40px;
    font-size: 0.9em;
}
.footer a {
    color: white;
    text-decoration: none;
    margin: 0 10px;
}
.footer a:hover {
    text-decoration: underline;
}
    </style>
</head>
<body>
    <!-- Navbar -->
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

    <!-- Hero Section -->
    <section class="hero">
        <h1>Zimbabwe United Passenger Company (ZUPCO)</h1>
        <!-- Welcome Section -->
    <div style="max-width: 900px; margin: 40px auto; padding: 0 20px; text-align: center;">
        <p style="font-size: 1.1em; line-height: 1.6; color: #333; margin-bottom: 15px;">
        Welcome to <strong>ZUPCO’s official Bus Pre-Booking Platform</strong>—your gateway to smarter, faster, and more reliable travel across Zimbabwe. Say goodbye to long queues, uncertain seat availability, and last-minute travel stress. With our secure, easy-to-use digital system, you can book your bus seat anytime, anywhere, and receive instant confirmation via SMS or email. Whether you're traveling from Harare to Bulawayo, Mutare to Masvingo, or any route in between, ZUPCO ensures you board with confidence, comfort, and peace of mind.
    </p>
    <p style="font-size: 1.1em; line-height: 1.6; color: #333;">
        Our mission is simple: to modernize public transport through technology that serves you. Every booking guarantees a <strong>reserved seat</strong>, <strong>transparent fare</strong>, and <strong>real-time updates</strong>—so you’re always informed and in control. Join thousands of satisfied passengers who have already made the switch to a smoother, fairer, and more efficient way to travel with Zimbabwe’s trusted national carrier.
    </p>
</div>
        <p>Book your bus trip in minutes with our secure, reliable, and modern pre-booking system. Enjoy guaranteed seats, real-time updates, and digital tickets delivered to your phone.</p>
        
    </section>

    <!-- Footer -->
    <footer class="footer">
    <div style="max-width: 1200px; margin: 0 auto;">
        <a href="about.php">About ZUPCO</a> |
        <a href="contact.php">Contact Us</a> |
        <a href="register.php">Register</a> |
        <a href="login.php">Login</a>
    </div>
    <div style="margin-top: 10px; opacity: 0.8;">
        &copy; <?= date('Y') ?> Zimbabwe United Passenger Company (ZUPCO). All rights reserved.
    </div>
</footer>
</body>
</html>