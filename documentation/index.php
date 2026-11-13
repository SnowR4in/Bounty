<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USDTFaucet - Installation Guide</title>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="doc-body">

<div class="doc-container">
    <div class="doc-header">
        <h1>USDTFaucet Installation Guide</h1>
        <p>A high-performance Tether USDT Faucet and Rewards Platform, powered by FaucetPay &amp; OfferwallApi.</p>
    </div>

    <h2>Step 1: Upload Files</h2>
    <ol class="doc-list">
        <li>Unpack the script ZIP archive on your computer.</li>
        <li>Log in to your hosting control panel (cPanel, DirectAdmin, etc.) and open the <strong>File Manager</strong>.</li>
        <li>Navigate to your website's root folder (usually <code class="doc-inline-code">public_html</code>).</li>
        <li>Upload the downloaded ZIP file and <strong>Extract</strong> it directly into the root folder.</li>
    </ol>

    <h2>Step 2: Database Creation &amp; Import</h2>
    <ol class="doc-list">
        <li>In your hosting control panel, open the <strong>MySQL Databases</strong> tool.</li>
        <li>Create a new Database and a new Database User.</li>
        <li>Assign the User to the Database and grant <strong>All Privileges</strong>. <em>(Save the database name, username, and password securely, you will need them in the next step).</em></li>
        <li>Go back to your control panel and open <strong>phpMyAdmin</strong>.</li>
        <li>Select your newly created database from the left sidebar.</li>
        <li>Click on the <strong>Import</strong> tab at the top.</li>
        <li>Click <strong>Choose File</strong> and select the <code class="doc-inline-code">database.sql</code> file found in your extracted script files.</li>
        <li>Click the <strong>Go</strong> (or Import) button at the bottom to upload the tables.</li>
    </ol>

    <h2>Step 3: Database Configuration</h2>
    <p>Now that your files are uploaded and your database is ready, you need to connect them.</p>
    <ol class="doc-list">
        <li>In your File Manager, navigate to the <code class="doc-inline-code">config</code> folder.</li>
        <li>Find the file named <code class="doc-inline-code">database.php</code>.</li>
        <li>Edit this file and update the database credentials with the ones you created in Step 2:</li>
    </ol>
    <pre><code>$host = "localhost";
$user = "YOUR_DATABASE_USERNAME";
$pass = "YOUR_DATABASE_PASSWORD";
$db   = "YOUR_DATABASE_NAME";</code></pre>

    <h2>Step 4: Admin Panel Access</h2>
    <p>Your installation is now complete! You can access the powerful Admin Panel to configure your website settings, shortlinks, offerwalls, and ad networks.</p>

    <div class="doc-note">
        <strong>Admin URL:</strong> <code class="doc-inline-code">yourdomain.com/adminpanel/</code>
    </div>

    <p>Use the following default credentials to log in for the first time:</p>
    <ul class="doc-list">
        <li><strong>Username:</strong> <code class="doc-inline-code">admin</code></li>
        <li><strong>Password:</strong> <code class="doc-inline-code">admin</code></li>
    </ul>

    <div class="doc-note">
        <strong>Important:</strong> Please change the default admin password immediately after your first login for security reasons!
    </div>

    <p>After your first login, go to <strong>Admin Panel &rarr; General Settings</strong> and paste your <strong>FaucetPay API key</strong>, then configure your <strong>OfferwallApi keys</strong> in Earning Modules (PTC settings). Set your OfferwallApi postback URL to <code class="doc-inline-code">https://yourdomain.com/postback.php</code>.</p>

    <h2>Important: Script Updates &amp; Backups</h2>
    <div class="doc-warning">
        <strong>&#9888;&#65039; Warning regarding .json files:</strong> Certain settings (such as Ad Configurations and Site Settings) are stored locally in <code class="doc-inline-code">.json</code> files to ensure maximum performance without constantly querying the database. <br><br>
        Whenever you update the script in the future, <strong>ALWAYS make a backup of your existing .json files</strong>. If you overwrite your old .json files with the blank ones from a new update, you will lose your ad configurations and specific settings!
    </div>

    <div class="doc-footer">
        &copy; 2026 USDTFaucet. All rights reserved.
    </div>
</div>

</body>
</html>
