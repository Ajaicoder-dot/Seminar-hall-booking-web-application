<?php
session_start();
include('config.php');
include('header.php'); 



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $query = "INSERT INTO contact_us (name, email, phone, city, remarks) 
              VALUES ('$name', '$email', '$phone', '$city', '$remarks')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Your message has been submitted successfully!');</script>";
    } else {
        echo "<script>alert('Error submitting message. Please try again.');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Native Event Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { display: flex; max-width: 1400px; margin:70px auto; background: white; padding: 70px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .contact-form { flex: 1; padding: 20px; }
        .contact-form h2 { margin-bottom: 20px; }
        .contact-form input, .contact-form textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        .contact-form button { background: linear-gradient(45deg, #6a11cb, #2575fc); color: white; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; }
        .contact-form button:hover { background: linear-gradient(45deg, #5a0fb4, #1e5edc); }
        .contact-info { flex: 1; padding: 20px; background: #f9f9f9; border-left: 2px solid #ddd; }
        .contact-info h2 { margin-bottom: 20px; }
        .contact-info p { margin-bottom: 10px; }
        .map-container { margin-top: 20px; }
        iframe { width: 100%; height: 250px; border: none; }

        .social-icons a {
            text-decoration: none;
            color: #333;
            font-size: 24px;
            margin: 0 10px;
            transition: 0.3s;
        }
        .social-icons a:hover { color: #2575fc; }

        .footer {
            text-align: center;
            position: fixed;
            width: 100%;
            bottom: 0;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            padding: 15px 30px;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
</body>

<div class="container">
    <div class="contact-form">
        <h2>Contact Us</h2>
        <form method="POST">
    <input type="text" name="name" placeholder="Your Name" required>
    <input type="email" name="email" placeholder="Your Email" required>
    <input type="tel" name="phone" placeholder="Your Phone" required>
    <input type="text" name="city" placeholder="Your City" required>
    <textarea name="remarks" placeholder="Your Message" rows="5" required></textarea>
    <button type="submit">Submit</button>
</form>

    </div>

    <div class="contact-info">
        <h2>Get in Touch</h2>
        <p><strong>Address:</strong>Main road puducherry </p>
        <p><strong>Phone:</strong> +91 9361685137</p>
        <p><strong>Email:</strong> contact@pondiuni.ac.in</p>
        <p><strong>Follow Us:</strong></p>
        <div class="social-icons">
            <a href="#"><i class="fa-brands fa-facebook"></i></a>
            <a href="#"><i class="fa-brands fa-twitter"></i></a>
            <a href="#"><i class="fa-brands fa-instagram"></i></a>
        </div>
        <div class="map-container">
        <iframe 
    width="100%" height="250" style="border:0;" loading="lazy"
    allowfullscreen referrerpolicy="no-referrer-when-downgrade"
    src="https://www.google.com/maps/embed/v1/place?key=AIzaSyC0vB_ncE-Sv1wq6Q9n3LT5kCitmal6-y8&q=Pondicherry+University&markers=12.018866026524984, 79.85659281419723&markers=12.01766348497001, 79.85559170565429">
</iframe>

</div>

    </div>
</div>

<div class="footer">&copy; <?php echo date("Y"); ?> Native Event Management Platform. All Rights Reserved.</div>

</body>
</html>