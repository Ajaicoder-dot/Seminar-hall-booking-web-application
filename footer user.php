<!-- Footer styling integrated with your existing gradient theme -->
<style>
    .footer {
        background: linear-gradient(45deg, #6a11cb, #2575fc);
        color: white;
        padding: 20px 40px;
        position: relative;
        width: 100%;
        box-shadow: 0 -2px 15px rgba(0, 0, 0, 0.1);
        font-family: 'Poppins', Arial, sans-serif;
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .footer-logo i {
        font-size: 24px;
        color: #ffde59;
    }

    .footer-logo-text {
        font-size: 18px;
        font-weight: 600;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }

    .footer-links {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
    }

    .footer-links a {
        color: white;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s;
        position: relative;
    }

    .footer-links a::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        background: #fff;
        bottom: -3px;
        left: 0;
        transition: width 0.3s;
    }

    .footer-links a:hover::after {
        width: 100%;
    }

    .footer-bottom {
        text-align: center;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        width: 100%;
        font-size: 14px;
    }

    .footer-social {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
    }

    .footer-social a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        transition: all 0.3s;
    }

    .footer-social a:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px);
    }
    
    .footer-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .footer-logo img {
        height: 45px;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .footer {
            padding: 20px;
        }
        
        .footer-content {
            flex-direction: column;
            text-align: center;
        }

        .footer-left {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .footer-logo {
            justify-content: flex-start;
        }

        .footer-links {
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .footer-social {
            justify-content: center;
        }
    }
</style>

<!-- Footer HTML Structure -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-left">
            <div class="footer-logo">
                <img src="images/logo.png" alt="University Logo">
            </div>
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="view_hall.php">Halls</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <a href="privacy_policy.php">Privacy Policy</a>
            </div>
        </div>
        
        <div class="footer-social">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Pondicherry University. All rights reserved.</p>
    </div>
</footer>