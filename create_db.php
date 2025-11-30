<?php
$db = new SQLite3('scams.db');

$query = "CREATE TABLE IF NOT EXISTS scam_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scam_url TEXT NOT NULL,
    scam_type TEXT NOT NULL,
    how_received TEXT,
    details TEXT NOT NULL,
    contact_email TEXT,
    date_reported DATETIME DEFAULT CURRENT_TIMESTAMP
)";

$db->exec($query);

echo "Database and table created.";
?>
