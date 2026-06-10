<?php
/**
 * Contact form handler for AIPU Unlimited (aipuunlimited.com)
 */
declare(strict_types=1);

function redirect(string $path): void {
    header('Location: ' . $path, true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/contact.html');
}

// Honeypot
if (!empty($_POST['website'] ?? '')) {
    redirect('/contact.html?sent=1');
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$topic = trim((string)($_POST['topic'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$consent = isset($_POST['consent']);

if ($name === '' || $email === '' || $topic === '' || $message === '' || !$consent) {
    redirect('/contact.html?error=' . rawurlencode('Please complete all required fields.'));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/contact.html?error=' . rawurlencode('Please enter a valid email address.'));
}

if (strlen($message) > 10000) {
    redirect('/contact.html?error=' . rawurlencode('Message is too long.'));
}

$allowedTopics = ['support', 'billing', 'refund', 'privacy', 'partnership', 'other'];
if (!in_array($topic, $allowedTopics, true)) {
    $topic = 'other';
}

$entry = [
    'time' => gmdate('c'),
    'name' => $name,
    'email' => $email,
    'topic' => $topic,
    'message' => $message,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
];

$dataDir = __DIR__ . '/data';
$file = $dataDir . '/contacts.jsonl';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0750, true);
}

$line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

// Optional: attempt mail if configured
$to = 'support@aipuunlimited.com';
$subject = '[AIPU Unlimited Contact] ' . $topic . ' — ' . $name;
$body = "Name: $name\nEmail: $email\nTopic: $topic\n\n$message\n";
$headers = 'From: noreply@aipuunlimited.com' . "\r\n" . 'Reply-To: ' . $email;
@mail($to, $subject, $body, $headers);

redirect('/contact.html?sent=1');
