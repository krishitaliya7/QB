<?php
include '../includes/db_connect.php';
include '../includes/session.php';

$sql = "CREATE TABLE IF NOT EXISTS pin_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL,
  otp VARCHAR(6) DEFAULT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(iddo it
  ) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table pin_resets created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
