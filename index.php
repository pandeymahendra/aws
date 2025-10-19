<?php
// ============================================================================
//  AWS Compute EchoServer
//  Version: 2.2
//  Author: Mahendra Pandey
//  Description: IMDSv1/v2-compatible EC2 echo app for demos and load balancers.
//               Auto-detects CLI/cURL to return plain text or JSON output.
//  © 2025 Mahendra Pandey. All rights reserved.
// ============================================================================

date_default_timezone_set('UTC');
header('X-Powered-By: AWS Compute EchoServer by Mahendra Pandey');

// ------------------------------------------------------------
// Helper: Fetch EC2 metadata (IMDSv2 preferred, IMDSv1 fallback)
// ------------------------------------------------------------
function get_ec2_metadata($path) {
    static $cache = [];
    if (isset($cache[$path])) return $cache[$path];

    $base = "http://169.254.169.254/latest";
    $token_url = "$base/api/token";
    $metadata_url = "$base/meta-data/$path";

    // Attempt IMDSv2 token
    $ch = curl_init($token_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 800,
        CURLOPT_HTTPHEADER => ["X-aws-ec2-metadata-token-ttl-seconds: 60"],
        CURLOPT_CUSTOMREQUEST => "PUT"
    ]);
    $token = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $headers = [];
    if ($http_code == 200 && $token) {
        $headers[] = "X-aws-ec2-metadata-token: $token";
    }

    // Get metadata (IMDSv2 or fallback IMDSv1)
    $ch = curl_init($metadata_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 800,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return $cache[$path] = ($response ?: 'N/A');
}

// ------------------------------------------------------------
// Gather metadata and environment info
// ------------------------------------------------------------
$az = get_ec2_metadata("placement/availability-zone");
$metadata = [
    "instance_id"       => get_ec2_metadata("instance-id"),
    "instance_type"     => get_ec2_metadata("instance-type"),
    "availability_zone" => $az,
    "region"            => substr($az, 0, -1),
    "ami_id"            => get_ec2_metadata("ami-id"),
    "local_ipv4"        => get_ec2_metadata("local-ipv4"),
    "public_ipv4"       => get_ec2_metadata("public-ipv4"),
];

$hostname       = gethostname();
$server_ip      = $_SERVER['SERVER_ADDR'] ?? gethostbyname($hostname);
$client_ip      = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$server_time    = gmdate('Y-m-d H:i:s');
$server_software= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$php_version    = PHP_VERSION;
$os_info        = php_uname();
$request_id     = bin2hex(random_bytes(8));

// Request info
$request_info = [
    "request_id"     => $request_id,
    "client_address" => $client_ip,
    "method"         => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
    "uri"            => ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'),
    "query"          => $_SERVER['QUERY_STRING'] ?: 'nil',
    "protocol"       => $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
];

// Headers + Body
$headers = [];
foreach (getallheaders() as $k => $v) $headers[strtolower($k)] = $v;
$body = file_get_contents('php://input') ?: '[empty]';

// Detect if CLI tool (curl, wget, httpie)
$user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$is_cli_request = (
    php_sapi_name() === 'cli' ||
    str_contains($user_agent, 'curl') ||
    str_contains($user_agent, 'wget') ||
    str_contains($user_agent, 'httpie')
);

// ------------------------------------------------------------
// Fast ping
// ------------------------------------------------------------
if (isset($_GET['ping'])) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "ok",
        "request_id" => $request_id,
        "hostname" => $hostname,
        "instance_id" => $metadata["instance_id"],
        "az" => $metadata["availability_zone"],
        "region" => $metadata["region"],
        "server_time_utc" => $server_time
    ]);
    exit;
}

// ------------------------------------------------------------
// Hook endpoints
// ------------------------------------------------------------
if (isset($_GET['hook'])) {
    header('Content-Type: application/json');
    $hook = strtolower($_GET['hook']);
    $sections = [
        'client'   => $request_info,
        'server'   => [
            "server_version"  => $server_software,
            "hostname"        => $hostname,
            "server_ip"       => $server_ip,
            "server_time_utc" => $server_time,
            "php_version"     => $php_version,
            "os_info"         => $os_info
        ],
        'headers'  => $headers,
        'body'     => $body,
        'metadata' => $metadata
    ];
    echo json_encode($sections[$hook] ?? ["error" => "Unknown hook"], JSON_PRETTY_PRINT);
    exit;
}

// ------------------------------------------------------------
// JSON output
// ------------------------------------------------------------
if (isset($_GET['json']) || $is_cli_request) {
    header('Content-Type: application/json');
    echo json_encode([
        "app" => [
            "name"    => "AWS Compute EchoServer",
            "author"  => "Mahendra Pandey",
            "version" => "2.2"
        ],
        "request_id"      => $request_id,
        "ec2_metadata"    => $metadata,
        "client_values"   => $request_info,
        "server_values"   => [
            "server_version"  => $server_software,
            "hostname"        => $hostname,
            "server_ip"       => $server_ip,
            "server_time_utc" => $server_time,
            "php_version"     => $php_version,
            "os_info"         => $os_info
        ],
        "headers_received" => $headers,
        "body"             => $body
    ], JSON_PRETTY_PRINT);
    exit;
}

// ------------------------------------------------------------
// Web Theme definitions
// ------------------------------------------------------------
$themes = [
    'default' => [
        '--bg' => '#10141a', '--card' => '#1b222c',
        '--accent' => '#00bcd4', '--accent2' => '#ffc107',
        '--text' => '#e4e6eb', '--muted' => '#9da5b4', '--border' => '#30363d'
    ],
    'light' => [
        '--bg' => '#f8f9fa', '--card' => '#ffffff',
        '--accent' => '#0078d4', '--accent2' => '#e88a05',
        '--text' => '#222', '--muted' => '#555', '--border' => '#ddd'
    ],
    'matrix' => [
        '--bg' => '#000', '--card' => '#001b00',
        '--accent' => '#00ff66', '--accent2' => '#39ff14',
        '--text' => '#00ff66', '--muted' => '#00aa44', '--border' => '#003300'
    ],
    'solarized' => [
        '--bg' => '#002b36', '--card' => '#073642',
        '--accent' => '#b58900', '--accent2' => '#268bd2',
        '--text' => '#eee8d5', '--muted' => '#93a1a1', '--border' => '#586e75'
    ]
];

$theme_name = strtolower($_GET['theme'] ?? 'default');
if (!array_key_exists($theme_name, $themes)) $theme_name = 'default';
$theme_vars = $themes[$theme_name];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AWS Compute EchoServer by Mahendra Pandey</title>
<style>
:root {
<?php foreach ($theme_vars as $k => $v) echo "  $k: $v;\n"; ?>
  --mono: 'Fira Code', 'Consolas', monospace;
}
body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--mono);
  padding: 40px;
  line-height: 1.6;
  font-size: 1.05em;
}
h1 {
  color: var(--accent);
  font-size: 1.8em;
  margin-bottom: 0.2em;
}
.section {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 22px;
  margin-top: 25px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}
.title {
  font-weight: 700;
  color: var(--accent2);
  margin-bottom: 8px;
  font-size: 1.1em;
  text-transform: uppercase;
}
pre { font-weight: 600; font-size: 1em; white-space: pre-wrap; }
footer {
  margin-top: 40px;
  color: var(--muted);
  font-size: 0.95em;
  border-top: 1px solid var(--border);
  padding-top: 10px;
}
</style>
</head>
<body>
<h1>AWS Compute EchoServer</h1>
<p>IMDSv2-compatible EC2 echo app for compute demos and load balancers.</p>

<div class="section">
  <div class="title">EC2 INSTANCE METADATA</div>
  <pre><?php foreach ($metadata as $k => $v) echo "$k=$v\n"; ?></pre>
</div>

<div class="section">
  <div class="title">CLIENT VALUES</div>
  <pre><?php foreach ($request_info as $k => $v) echo "$k=$v\n"; ?></pre>
</div>

<div class="section">
  <div class="title">SERVER VALUES</div>
  <pre>
server_version=<?= htmlspecialchars($server_software) ?> 
hostname=<?= htmlspecialchars($hostname) ?> 
server_ip=<?= htmlspecialchars($server_ip) ?> 
server_time_utc=<?= htmlspecialchars($server_time) ?> 
php_version=<?= htmlspecialchars($php_version) ?> 
os_info=<?= htmlspecialchars($os_info) ?> 
  </pre>
</div>

<div class="section">
  <div class="title">HEADERS RECEIVED</div>
  <pre><?php foreach ($headers as $k => $v) echo "$k=$v\n"; ?></pre>
</div>

<div class="section">
  <div class="title">BODY</div>
  <pre><?= htmlspecialchars($body) ?></pre>
</div>

<footer>
💡 Tips:<br>
• <code>?json=1</code> → JSON output<br>
• <code>?ping=1</code> → Fast health check<br>
• <code>?hook=client|server|headers|body|metadata</code> → Section-only JSON<br>
• <code>?theme=light|matrix|solarized|default</code> → Change theme<br>
<br>
<strong>AWS Compute EchoServer</strong> © 2025 Mahendra Pandey
</footer>
</body>
</html>
