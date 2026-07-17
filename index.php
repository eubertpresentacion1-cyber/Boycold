<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <link rel="icon" type="image/png" href="picture/icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold Café</title>
</head>
<body>
    <p><a href="checkout.php">Go to checkout</a></p>

    <div class="main-nav">
        <nav id="mainNav">
            <div class="nav-left-group">
                <div class="logo">
                    <img src="picture/BoyCold Logo 2.png" alt="BoyCold logo">
                </div>
                <ul class="nav-left">
                    <li><a href="register.php">MENU</a></li>
                </ul>
            </div>

            <div class="nav-right-group">
                <ul class="nav-right">
                    <li><a href="register.php"><i class="fa-solid fa-location-dot"></i> FIND A STORE</a></li>
                </ul>
                <button class="btn-signin" onclick="window.location.href='login.php'">Sign in</button>
                <button class="btn-join" onclick="window.location.href='register.php'">Join now</button>
            </div>
        </nav>
    </div>
    <header>
        <div class="background">
            <div class="box">
                <p>Ready to sweeten your day?</p>
                <button class="btn" onclick="window.location.href='register.php'">Start an order</button>
            </div>
            <h1>BOYCOLD<br>CAFE</h1>
            <div class="tag-line">
                <p>
                    Fresh brews, cozy vibes, and pastries to sweeten your day.
                    <span>At BoyCold Cafe, we believe great coffee is best enjoyed at your own pace.</span>
                    <span>Whether you're starting your morning, taking a break, or winding down,</span>
                    our space is made for comfort, connection, and calm.
                </p>
            </div>
        </div>
    </header>

    <section>
        <div class="hero-section">
            <div class="top-rectangle">
                <div class="top-content">
                    <h2>Made especially for banana pudding lovers.</h2>
                    <p>Show off your love for this creamy classic with a treat inspired by layers of sweet bananas, smooth pudding, and comforting homemade goodness.</p>
                    <button class="hero-btn" onclick="window.location.href='register.php'">View menu</button>
                </div>
            </div>
            <div class="mid-rectangle">
                <img src="picture/Layer 2 1.png" alt="">
                <img class="img2" src="picture/dasdasd 1.png" alt="">
            </div>

            <div class="bottom-rectangle">
                <img src="picture/Rectangle 24.png" alt="">
                <div class="bottom-content">
                    <h2>Your favorites just got even better.</h2>
                    <p>Introducing our 4 signature pasta flavors and our Mango <br> Sticky Rice with 3 delightful new twists. </br> Comfort food made the BoyCold way, crafted to satisfy every craving.</p>
                    <button class="hero-btn" onclick="window.location.href='register.php'">Find a store</button>
                </div>
            </div>
        </div>
        
    </section>
    
    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="picture/icon2.png" alt="BoyCold logo">
                <h1>BOYCOLD CAFE</h1>
                <p>© 2026 BoyCold Cafe. All rights reserved.</p>
            </div>
            <div class="footer-links">
                <ul>
                    <li><a href="#">Contact Information</a></li>
                    <li><a href="#">Customer Links</a></li>
                    <li><a href="#">Company Information</a></li>
                    <li><a href="#">Legal Links</a></li>
                    <li><a href="#">Social Media Links</a></li>
                </ul>
            </div>
        </div>
    </footer>

</body>
</html>