<?php
// ------------------------
//  PHP + SQLite backend
// ------------------------
$db = new SQLite3('reports.db');

// Create table if it doesn’t exist
$db->exec("CREATE TABLE IF NOT EXISTS scam_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scam_url TEXT NOT NULL,
    scam_type TEXT NOT NULL,
    how_received TEXT,
    details TEXT NOT NULL,
    contact_email TEXT,
    date_submitted TEXT NOT NULL
)");

// --------------------
// LINK CHECKER (GEMINI)
// --------------------
$link_check_result = "";

if (isset($_POST['check_link'])) {

    $url_to_check = $_POST['check_url'];

    $api_key = "YOUR_GEMINI_API_KEY"; 

    $payload = [
        "model" => "gemini-1.5-flash",
        "input" => "Analyze this URL for safety and tell me if it's possibly a scam: $url_to_check"
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=".$api_key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $json = json_decode($response, true);
    $link_check_result = $json["candidates"][0]["content"]["parts"][0]["text"] ?? "AI could not analyze this link.";
}

// ------------------------
//  Gemini link checker
// ------------------------
$link_check_result = "";
$link_check_error  = "";

function check_link_with_gemini($url) {
    // 👉 Put your real Gemini API key here
    $apiKey = 'YOUR_GEMINI_API_KEY_HERE';

    // Simple prompt for the model
    $prompt = "You are a cybersecurity assistant. "
            . "Analyze this URL and classify it as either 'Likely safe', 'Suspicious', or 'Likely malicious'. "
            . "Explain briefly why in simple language. URL: " . $url;

    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ["error" => "cURL error: " . $error];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return ["error" => "Unexpected response from Gemini API."];
    }

    $text = $data['candidates'][0]['content']['parts'][0]['text'];
    return ["result" => $text];
}

// ------------------------
//  Handle form submissions
// ------------------------
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // We use a hidden field to know which form was submitted
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'report_scam') {
        // ---- Existing scam report form ----
        $scam_url      = $_POST['scam_url'];
        $scam_type     = $_POST['scam_type'];
        $how_received  = $_POST['how_received'];
        $details       = $_POST['details'];
        $contact_email = $_POST['contact_email'];
        $date          = date("Y-m-d H:i:s");

        $stmt = $db->prepare("INSERT INTO scam_reports
            (scam_url, scam_type, how_received, details, contact_email, date_submitted)
            VALUES (:url, :type, :received, :details, :email, :date)");

        $stmt->bindValue(':url',      $scam_url,      SQLITE3_TEXT);
        $stmt->bindValue(':type',     $scam_type,     SQLITE3_TEXT);
        $stmt->bindValue(':received', $how_received,  SQLITE3_TEXT);
        $stmt->bindValue(':details',  $details,       SQLITE3_TEXT);
        $stmt->bindValue(':email',    $contact_email, SQLITE3_TEXT);
        $stmt->bindValue(':date',     $date,          SQLITE3_TEXT);

        $stmt->execute();
        $success_message = "Your scam report has been submitted!";

    } elseif ($form_type === 'link_check') {
        // ---- New link checker form ----
        $check_url = trim($_POST['check_url'] ?? '');

        if ($check_url === '') {
            $link_check_error = "Please paste a URL to check.";
        } else {
            $res = check_link_with_gemini($check_url);
            if (isset($res['error'])) {
                $link_check_error = $res['error'];
            } else {
                $link_check_result = $res['result'];
            }
        }
    }
}

// Fetch all reports for the table
$results = $db->query("SELECT * FROM scam_reports ORDER BY id DESC");
?>

<!DOCTYPE HTML>
<html>
    <head>
        <title>CySafe Portal | Report a Scam</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <meta name="description" content="Report suspicious links and see the latest scams submitted to CySafe Portal." />
        <meta name="keywords" content="CySafe, cybersecurity, phishing, scam report, client portal" />
        <link rel="stylesheet" href="assets/css/main.css" />
    </head>
    <body class="is-preload">

        <!-- Header -->
        <header id="header">
            <a class="logo" href="index.html">
                <img src="images/cysafe.png" alt="CySafe Portal" style="height: 48px;">
            </a>
            <nav>
                <a href="#menu">Menu</a>
            </nav>
        </header>

        <!-- Nav -->
        <nav id="menu">
            <ul class="links">
                <li><a href="index.html">Home</a></li>
                <li><a href="elements.php">Report Scam</a></li>
                <li><a href="generic.html">Know it all</a></li>
            </ul>
        </nav>

        <!-- Heading -->
        <div id="heading" class="cysafe-hero">
            <div class="cysafe-hero-inner">
                <h1>Report a Scam & Stay Informed</h1>
            </div>
        </div>

        <!-- Main -->
        <section id="main" class="wrapper cysafe-main">
            <div class="inner">
                <div class="content">
                    <div class="row">

                       <!-- LEFT COLUMN: Info / Resources -->
<div class="col-6 col-12-medium">

    <!-- EXISTING INFO CARD -->
    <div class="cysafe-card cysafe-card--info">
        <h3>How CySafe Helps You</h3>
        <p>Our goal is to make online safety simple. This page lets you report scams and learn how to recognize common phishing patterns so you can protect yourself and others.</p>

        <h4>Quick Safety Checklist</h4>
        <ul class="cysafe-list">
            <li>Always check the full URL before entering any password.</li>
            <li>Be suspicious of “urgent” messages demanding immediate action.</li>
            <li>Never download unexpected attachments from unknown senders.</li>
            <li>When in doubt, contact the company directly using an official site.</li>
        </ul>

        <h4>Key Terms</h4>
        <dl class="cysafe-definitions">
            <dt>Phishing</dt>
            <dd>Scams that pretend to be trusted services to steal your passwords or personal information.</dd>

            <dt>Malicious Link</dt>
            <dd>A link designed to send you to a fake site, install malware, or trick you into sharing sensitive data.</dd>

            <dt>Smishing / Vishing</dt>
            <dd>Scams sent by text message (smishing) or phone call/voicemail (vishing) to pressure you into acting quickly.</dd>
        </dl>

        <div class="cysafe-tip">
            <span class="cysafe-tip-label">Tip</span>
            <p>If a message makes you feel rushed, anxious, or scared, pause. Scammers rely on emotion to override your judgment.</p>
        </div>
    </div>

    <!-- NEW LINK CHECKER CARD (BOTTOM LEFT) -->
    <div class="cysafe-card cysafe-card--info" style="margin-top: 25px;">
        <h3 class="cysafe-card-title">
            🔍 Link Checker
        </h3>
        <p>Paste a link to check if it may be dangerous using Google Gemini AI.</p>

        <form method="POST" action="elements.php">
            <input type="hidden" name="check_link" value="1">

            <label for="check_url">Enter URL</label>
            <input type="text" name="check_url" id="check_url" placeholder="https://example.com" required />

            <ul class="actions">
                <li><input type="submit" value="Check Link" class="button primary" /></li>
            </ul>
        </form>

        <?php if (!empty($link_check_result)): ?>
            <div style="background:#eef7ff; padding:10px; margin-top:10px; border-radius:8px;">
                <strong>AI Result:</strong>
                <p><?= nl2br(htmlspecialchars($link_check_result)) ?></p>
            </div>
        <?php endif; ?>
    </div>

</div>

                        <!-- RIGHT COLUMN: Link Checker + Report Scam + Table -->
                        <div class="col-6 col-12-medium">


                            <!-- REPORT SCAM CARD -->
                            <div class="cysafe-card cysafe-card--form">
                                <h3 class="cysafe-card-title">
                                    <span class="cysafe-icon-circle">!</span>
                                    Report a Scam
                                </h3>
                                <p>Use this form to report suspicious links, emails, or messages. Your report helps raise awareness for other users.</p>

                                <?php if (!empty($success_message)): ?>
                                    <p class="cysafe-note" style="background:#daf5d7; border-radius:8px; padding:10px; margin-bottom:15px;">
                                        ✅ <?php echo htmlspecialchars($success_message); ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Form posts back to this same file -->
                                <form method="post" action="elements.php" class="cysafe-form">
                                    <input type="hidden" name="form_type" value="report_scam" />
                                    <div class="row gtr-uniform">
                                        <div class="col-12">
                                            <label for="scam_url">Suspicious Link or Sender</label>
                                            <input type="text" name="scam_url" id="scam_url"
                                                   placeholder="Paste the link, sender, or handle here" required />
                                        </div>

                                        <div class="col-6 col-12-xsmall">
                                            <label for="scam_type">Type of Scam</label>
                                            <select name="scam_type" id="scam_type" required>
                                                <option value="">- Select Type -</option>
                                                <option value="phishing_email">Phishing Email</option>
                                                <option value="fake_website">Fake Website/Login Page</option>
                                                <option value="sms_scam">Text/SMS Scam</option>
                                                <option value="social_media">Social Media Message</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>

                                        <div class="col-6 col-12-xsmall">
                                            <label for="how_received">How did you receive it?</label>
                                            <select name="how_received" id="how_received">
                                                <option value="">Optional</option>
                                                <option value="email">Email</option>
                                                <option value="sms">Text Message</option>
                                                <option value="social">Social Media</option>
                                                <option value="direct">Direct Message</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label for="details">What happened?</label>
                                            <textarea name="details" id="details" rows="5"
                                                      placeholder="Describe what the message said, if you clicked anything, and any other details..."
                                                      required></textarea>
                                        </div>

                                        <div class="col-12">
                                            <label for="contact_email">Your Email (optional)</label>
                                            <input type="email" name="contact_email" id="contact_email"
                                                   placeholder="Only if you want a follow-up" />
                                        </div>

                                        <div class="col-12">
                                            <ul class="actions">
                                                <li><input type="submit" value="Submit Scam Report" class="button primary cysafe-btn" /></li>
                                                <li><input type="reset" value="Clear Form" class="button alt" /></li>
                                            </ul>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- LATEST SCAMS CARD -->
                            <div class="cysafe-card cysafe-card--table">
                                <h3 class="cysafe-card-title">
                                    <span class="cysafe-icon-circle cysafe-icon-circle--green">✓</span>
                                    Latest Reported Scams
                                </h3>
                                <p>These examples are shared for awareness. Do not visit these links — they may be dangerous.</p>

                                <div class="table-wrapper cysafe-table-wrapper">
                                    <table class="cysafe-table">
                                        <thead>
                                            <tr>
                                                <th>Scam Link / Sender</th>
                                                <th>Type</th>
                                                <th>How Received</th>
                                                <th>Details</th>
                                                <th>Contact Email</th>
                                                <th>Reported</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['scam_url']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['scam_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['how_received']); ?></td>
                                                    <td><?php echo nl2br(htmlspecialchars($row['details'])); ?></td>
                                                    <td><?php echo htmlspecialchars($row['contact_email']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['date_submitted']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <p class="cysafe-note">
                                    If something you receive looks similar to these examples, treat it with caution and verify before clicking.
                                </p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="footer">
            <div class="inner">
                <div class="content">
                    <section>
                        <h3>About Us</h3>
                        <p>We’re a student team dedicated to exploring cybersecurity and building practical tools that help people stay safe online.
                            CyberSafe Portal began as a simple idea: create a place where anyone can submit suspicious links and quickly understand potential risks.
                            By combining basic AI with clear, accessible design, we aim to make online security less confusing and more approachable.
                            Our goal is to support digital awareness, encourage safer browsing habits, and offer a small but meaningful contribution to a safer internet for everyone.</p>
                    </section>
                    <section>
                    </section>
                    <section>
                        <h4>Our Social Medias</h4>
                        <ul class="plain">
                            <li><a href="#"><i class="icon fa-twitter">&nbsp;</i>Twitter</a></li>
                            <li><a href="#"><i class="icon fa-facebook">&nbsp;</i>Facebook</a></li>
                            <li><a href="#"><i class="icon fa-instagram">&nbsp;</i>Instagram</a></li>
                            <li><a href="https://github.com/Cysafe-Inc/Cysafe-Portal"><i class="icon fa-github">&nbsp;</i>Github</a></li>
                        </ul>
                    </section>
                </div>
                <div class="copyright">
                    &copy; Cysafe Portal Nelsi Valdovinos | Brandon Solorio
                </div>
            </div>
        </footer>

        <!-- Scripts -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/browser.min.js"></script>
        <script src="assets/js/breakpoints.min.js"></script>
        <script src="assets/js/util.js"></script>
        <script src="assets/js/main.js"></script>

    </body>
</html>
