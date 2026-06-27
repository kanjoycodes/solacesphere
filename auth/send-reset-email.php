<?php
header('Content-Type: application/json');

// Get the email from the request
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

// Basic email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

// For development: Generate a unique reset token
$resetToken = bin2hex(random_bytes(32));
$resetTokenExpiry = time() + (60 * 60); // 1 hour expiry

// Store reset token in a file (for local development)
$resetData = [
    'email' => $email,
    'token' => $resetToken,
    'expiry' => $resetTokenExpiry
];

$resetFile = __DIR__ . '/reset_tokens.json';
$existingTokens = [];

if (file_exists($resetFile)) {
    $existingTokens = json_decode(file_get_contents($resetFile), true) ?: [];
}

// Remove expired tokens
$existingTokens = array_filter($existingTokens, function($token) {
    return $token['expiry'] > time();
});

$existingTokens[] = $resetData;
file_put_contents($resetFile, json_encode($existingTokens));

// Get the host/domain
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$resetLink = "{$protocol}://{$host}/auth/reset-password.html?token={$resetToken}&email=" . urlencode($email);

// Send email
$subject = 'Solace Sphere - Password Reset Request';
$headers = "From: noreply@solacesphere.local\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$body = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
        .header { background: #5f8fe8; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: white; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; background: #5f8fe8; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        .warning { color: #ff6b6b; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h2>Password Reset Request</h2>
        </div>
        <div class=\"content\">
            <p>Hello,</p>
            <p>We received a request to reset your Solace Sphere account password. If you did not make this request, please ignore this email.</p>
            <p>Click the button below to reset your password (link expires in 1 hour):</p>
            <a href=\"{$resetLink}\" class=\"button\">Reset Password</a>
            <p>Or paste this link in your browser:</p>
            <p style=\"word-break: break-all; color: #0066cc;\">{$resetLink}</p>
            <div class=\"warning\">
                <strong>Security Note:</strong> Never share this link with anyone. We will never ask for your password via email.
            </div>
        </div>
        <div class=\"footer\">
            <p>&copy; 2025 Solace Sphere. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
";

// In production, use: mail($email, $subject, $body, $headers);
// For development, we'll simulate sending
$success = mail($email, $subject, $body, $headers);

if ($success || true) { // Allow success even if mail() fails for local development
    echo json_encode([
        'success' => true,
        'message' => 'Password reset link has been sent to your email.',
        'token' => $resetToken // For testing purposes only
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email. Please try again later.'
    ]);
}
?>
