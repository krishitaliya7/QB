<?php
// Variables available: $username, $link
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Password reset</title>
</head>
<body>
  <p>Hello <?php echo htmlspecialchars($username); ?>,</p>
  <p>Click the link below to reset your password:</p>
  <p><a href="<?php echo htmlspecialchars($link); ?>">Reset my password</a></p>
  <p>If you didn't request a reset, ignore this email.</p>
  <p>— QuantumBank</p>
</body>
</html>
