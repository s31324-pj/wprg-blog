<?php
require_once 'config.php';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && $subject && $message) {
        $stmt = $db->prepare('INSERT INTO contacts (email, subject, message) VALUES (?,?,?)');
        $stmt->execute([$email, $subject, $message]);
        $db->prepare('INSERT INTO logs (message) VALUES (?)')->execute(["Contact form submitted by $email"]);
        $success = true;
    } else {
        $error = 'Please fill all fields correctly';
    }
}

include 'header.php';
?>


<section class="contact-page">
    <div class="content">
        <h2>Contact</h2>
        <?php if(isset($success)) echo '<p>Message sent.</p>'; ?>
        <?php if(isset($error)) echo '<p style="color:red">'.htmlspecialchars($error).'</p>'; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div class="form-segment">
                <label for="email">Email:</label>
                <input type="email" name="email" placeholder="Your email" required>
            </div>
            <div class="form-segment">
                <label for="subject">Subject:</label>
                <input type="text" name="subject" placeholder="Subject" required>
            </div>
            <div class="form-segment">
                <label for="message">Message:</label>
                <textarea name="message" placeholder="Type your message here..." required></textarea>
            </div>
            <button type="submit">Send</button>
        </form>
    </div>
</section>


<?php include 'footer.php'; ?>
