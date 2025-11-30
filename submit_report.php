<?php
// Connect to SQLite database
$db = new SQLite3('scams.db');

// Read POST data
$scam_url = $_POST['scam_url'];
$scam_type = $_POST['scam_type'];
$how_received = $_POST['how_received'];
$details = $_POST['details'];
$contact_email = $_POST['contact_email'];

// Insert into database
$stmt = $db->prepare("INSERT INTO scam_reports (scam_url, scam_type, how_received, details, contact_email)
                      VALUES (:url, :type, :received, :details, :email)");

$stmt->bindValue(':url', $scam_url, SQLITE3_TEXT);
$stmt->bindValue(':type', $scam_type, SQLITE3_TEXT);
$stmt->bindValue(':received', $how_received, SQLITE3_TEXT);
$stmt->bindValue(':details', $details, SQLITE3_TEXT);
$stmt->bindValue(':email', $contact_email, SQLITE3_TEXT);

$stmt->execute();

// Redirect after success
header("Location: elements.html?success=1");
exit();
?>
