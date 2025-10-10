<?php
// Variables available: $username, $link
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Verify your QuantumBank email</title>
</head>
<body>
  <p>Hello <?php echo htmlspecialchars($username); ?>,</p>
  <p>Please verify your email by clicking the link below:</p>
  <p><a href="<?php echo htmlspecialchars($link); ?>">Verify my email</a></p>
  <p>If you did not create an account, ignore this email.</p>
  <p>— QuantumBank</p>
</body>
</html>
