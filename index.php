<html>
<title> MAHI's DEMO </title>
<body>
<h1>
This is a Demo Site
<br>
<IMG SRC="https://logos.textgiraffe.com/logos/logo-name/Mahendra-designstyle-colors-m.png" ALT="Demo Logo"><br>
<center>
<?php
$eip = file_get_contents('http://169.254.169.254/latest/meta-data/public-ipv4');
$privip = file_get_contents('http://169.254.169.254/latest/meta-data/local-ipv4');
$lhst = file_get_contents('http://169.254.169.254/latest/meta-data/local-hostname');
$hst = file_get_contents('http://169.254.169.254/latest/meta-data/public-hostname');
echo "Public IP: $eip\n";
echo "<br>";
echo "Private IP: $privip\n";
echo "<br>";
echo "Local Hostname: $lhst\n";
echo "<br>";
echo "Public Hostname: $hst\n";
echo "<br>";
?>
</center>
</h1>
</body>
</html>
