<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | ZUPCO Bus Pre-Booking System</title>
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
            max-width: 900px; margin: 60px auto; background: white;
            padding: 40px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        h1 {color: #1a5f23; margin-bottom: 20px;}
        p {line-height: 1.7; color: #444; margin-bottom: 20px;}
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
       
        <!-- About Section -->
<div style="max-width: 900px; margin: 40px auto; padding: 0 20px; text-align: left;">
    <h1 style="color: #1a5f23; font-size: 2.2em; text-align: center; margin-bottom: 30px;">About ZUPCO</h1>
    
    <p style="font-size: 1.05em; line-height: 1.7; color: #333; margin-bottom: 20px;">
        The <strong>ZUPCO Bus Pre-Booking System</strong> is a homegrown digital solution designed to transform how Zimbabweans access public transportation. Developed in response to the inefficiencies of manual booking—such as overcrowding, overcharging, and lack of seat guarantees—this platform empowers passengers with full control over their travel experience. By leveraging secure web technology, real-time fleet data, and integrated EcoCash payments, we’ve created a system that is not only convenient but also transparent, accountable, and passenger-centered.
    </p>
    
    <p style="font-size: 1.05em; line-height: 1.7; color: #333; margin-bottom: 20px;">
        At the heart of our service is a commitment to <strong>reliability and equity</strong>. When you book with ZUPCO, you’re not just reserving a seat—you’re securing your right to a dignified journey. Our system automatically assigns you a unique seat number and bus fleet, sends timely reminders 24 and 2 hours before departure, and generates a digital ticket that can be verified instantly at boarding. This eliminates guesswork, reduces disputes, and ensures every passenger is treated fairly, regardless of when or how they book.
    </p>
    
    <p style="font-size: 1.05em; line-height: 1.7; color: #333; margin-bottom: 20px;">
        Beyond passenger convenience, the system strengthens ZUPCO’s operational integrity. Real-time dashboards enable management to monitor occupancy, track revenue per route, detect anomalies, and optimize fleet deployment—leading to better service planning and resource use. As part of ZUPCO’s broader digital transformation, this initiative reflects our dedication to innovation, efficiency, and excellence in public service. We’re not just moving people—we’re moving Zimbabwe forward, one smart booking at a time.
    </p>
    <p><strong>Mission:</strong> To provide efficient, affordable, and safe transport services for all.</p>
    <p><strong>Vision:</strong> To be the leading and most trusted passenger transport provider in Southern Africa.</p>
    
</div>
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
