<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mahendra's EC2 Demo App</title>
  <style>
    /* 🌈 Global Styles */
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #1e1f29, #2b2d42);
      color: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }
    .container {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 40px;
      max-width: 600px;
      width: 90%;
      text-align: center;
      box-shadow: 0 0 40px rgba(0, 0, 0, 0.3);
      animation: fadeIn 1.2s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    img {
      width: 120px;
      height: auto;
      margin-bottom: 20px;
      filter: drop-shadow(0px 0px 10px rgba(255, 255, 255, 0.3));
    }

    h1 {
      color: #00d4ff;
      margin-bottom: 10px;
      font-weight: 600;
      font-size: 1.8rem;
    }

    p {
      font-size: 1rem;
      margin: 12px 0;
      color: #dcdcdc;
    }

    b {
      color: #ffcc00;
      font-weight: 600;
    }

    footer {
      margin-top: 25px;
      font-size: 0.85rem;
      color: #aaa;
    }

    /* 💡 Hover and Accent Styles */
    .highlight-box {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 10px;
      padding: 15px;
      margin-top: 10px;
      transition: background 0.3s ease;
    }

    .highlight-box:hover {
      background: rgba(255, 255, 255, 0.15);
    }

    .tag {
      display: inline-block;
      background: #00d4ff;
      color: #1e1f29;
      padding: 2px 8px;
      border-radius: 5px;
      font-size: 0.75rem;
      margin-left: 6px;
    }
  </style>
</head>
<body>
<?php
  // 🌐 Function to handle IMDSv2 with IMDSv1 fallback
  function get_metadata($path) {
    $baseUrl = "http://169.254.169.254/latest/meta-data/";
    $tokenUrl = "http://169.254.169.254/latest/api/token";

    // Attempt IMDSv2
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-aws-ec2-metadata-token-ttl-seconds: 21600"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    $token = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code == 200 && $token) {
      // Use token for IMDSv2
      $ch = curl_init($baseUrl . $path);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 2);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-aws-ec2-metadata-token: $token"]);
      $result = curl_exec($ch);
      curl_close($ch);
      return $result ?: "Unavailable";
    } else {
      // Fallback to IMDSv1
      return @file_get_contents($baseUrl . $path) ?: "Unavailable";
    }
  }

  // 🧠 Retrieve EC2 metadata
  $instance = get_metadata("instance-id");
  $publicip = get_metadata("public-ipv4");
  $localip  = get_metadata("local-ipv4");
  $az       = get_metadata("placement/availability-zone");
  $region   = get_metadata("placement/region");
?>
  <div class="container">
    <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/Amazon_Web_Services_Logo.svg" alt="AWS Logo">
    <h1>Mahendra’s EC2 Metadata Demo</h1>
    <div class="highlight-box">
      <p>Instance ID: <b><?= htmlspecialchars($instance) ?></b></p>
      <p>Availability Zone: <b><?= htmlspecialchars($az) ?></b></p>
      <p>Region: <b><?= htmlspecialchars($region) ?></b></p>
    </div>
    <div class="highlight-box">
      <p>Public IP: <b><?= htmlspecialchars($publicip) ?></b></p>
      <p>Private IP: <b><?= htmlspecialchars($localip) ?></b></p>
    </div>
    <footer>
      Powered by <b>AWS EC2</b> <span class="tag">IMDSv2 Ready</span><br>
      © <?= date('Y') ?> Mahendra’s Demo App
    </footer>
  </div>
</body>
</html>
