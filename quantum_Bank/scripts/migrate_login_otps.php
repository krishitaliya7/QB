<?php
// Migration script to add login_otps table
include '../includes/db_connect.php';

try {
    // Check if table already exists
    $result = $conn->query("SHOW TABLES LIKE 'login_otps'");
    if ($result->num_rows == 0) {
        // Create the table
        $sql = "
            CREATE TABLE login_otps (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                otp_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                attempts INT DEFAULT 0,
                max_attempts INT DEFAULT 3,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ";

        if ($conn->query($sql) === TRUE) {
            echo "Table 'login_otps' created successfully.\n";

            // Add indexes
            $conn->query("CREATE INDEX idx_login_otps_user_id ON login_otps(user_id)");
            $conn->query("CREATE INDEX idx_login_otps_expires_at ON login_otps(expires_at)");

            echo "Indexes created successfully.\n";
        } else {
            throw new Exception("Error creating table: " . $conn->error);
        }
    } else {
        echo "Table 'login_otps' already exists.\n";
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}
?>
