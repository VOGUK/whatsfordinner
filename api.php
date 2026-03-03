<?php
// api.php - Backend API for "What's for dinner?!"
session_start();
header('Content-Type: application/json');

// --- PHPMailer Setup ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$phpmailer_ready = false;
if (file_exists(__DIR__ . '/src/Exception.php')) {
    require __DIR__ . '/src/Exception.php';
    require __DIR__ . '/src/PHPMailer.php';
    require __DIR__ . '/src/SMTP.php';
    $phpmailer_ready = true;
}

// --- SQLite Database Setup ---
$db_file = __DIR__ . '/whatsfordinner.sqlite';
$is_new_db = !file_exists($db_file);

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($is_new_db) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE,
                password TEXT,
                is_admin INTEGER DEFAULT 0
            );
            CREATE TABLE IF NOT EXISTS master_list (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                item_name TEXT UNIQUE
            );
            CREATE TABLE IF NOT EXISTS saved_emails (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email_address TEXT UNIQUE
            );
        ");
        
        $pdo->exec("INSERT INTO master_list (item_name) VALUES ('Apples'), ('Bananas'), ('Bread'), ('Chicken Breast'), ('Eggs'), ('Milk'), ('Pasta'), ('Tomato Sauce')");

        // Create default Admin user
        $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 1)");
        $stmt->execute(['admin', $hashed_password]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

$action = $_GET['action'] ?? '';

// --- AUTHENTICATION ---
if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user = trim(strtolower($data['username'] ?? ''));
    $pass = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData && password_verify($pass, $userData['password'])) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['is_admin'] = $userData['is_admin'];
        echo json_encode(['status' => 'success', 'is_admin' => $userData['is_admin']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect details. Try again.']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success']);
    exit;
}

// --- USER MANAGEMENT (Self) ---
if ($action === 'change_password') {
    if (!isset($_SESSION['user_id'])) { echo json_encode(['status' => 'error']); exit; }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $new_pass = password_hash($data['new_password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_pass, $_SESSION['user_id']]);
    echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
    exit;
}

// --- ADMIN MANAGEMENT (Requires Admin Session) ---
if (in_array($action, ['get_users', 'add_user', 'delete_user', 'reset_user_password'])) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Admin access required.']);
        exit;
    }

    if ($action === 'get_users') {
        $stmt = $pdo->query("SELECT id, username, is_admin FROM users ORDER BY username ASC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'add_user') {
        $data = json_decode(file_get_contents('php://input'), true);
        $user = trim(strtolower($data['username'] ?? ''));
        $pass = password_hash($data['password'] ?? '', PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 0)");
            $stmt->execute([$user, $pass]);
            echo json_encode(['status' => 'success', 'message' => 'User added.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Username may already exist.']);
        }
        exit;
    }

    if ($action === 'delete_user') {
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $data['id'] ?? 0;
        
        if ($user_id == $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'You cannot delete yourself.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['status' => 'success', 'message' => 'User deleted.']);
        exit;
    }

    if ($action === 'reset_user_password') {
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $data['id'] ?? 0;
        $new_pass = password_hash($data['new_password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_pass, $user_id]);
        echo json_encode(['status' => 'success', 'message' => 'User password reset.']);
        exit;
    }
}

// --- MASTER LIST ---
if ($action === 'get_master_list') {
    $stmt = $pdo->query("SELECT item_name FROM master_list ORDER BY item_name ASC");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
    exit;
}

if ($action === 'add_master_item') {
    $data = json_decode(file_get_contents('php://input'), true);
    $item_name = trim($data['item_name'] ?? '');
    if ($item_name) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO master_list (item_name) VALUES (?)");
        $stmt->execute([$item_name]);
        echo json_encode(['status' => 'success']);
    }
    exit;
}

if ($action === 'edit_master_item') {
    $data = json_decode(file_get_contents('php://input'), true);
    $old_name = trim($data['old_name'] ?? '');
    $new_name = trim($data['new_name'] ?? '');
    if ($old_name && $new_name) {
        $stmt = $pdo->prepare("UPDATE master_list SET item_name = ? WHERE item_name = ?");
        $stmt->execute([$new_name, $old_name]);
        echo json_encode(['status' => 'success']);
    }
    exit;
}

if ($action === 'delete_master_item') {
    $data = json_decode(file_get_contents('php://input'), true);
    $item_name = trim($data['item_name'] ?? '');
    if ($item_name) {
        $stmt = $pdo->prepare("DELETE FROM master_list WHERE item_name = ?");
        $stmt->execute([$item_name]);
        echo json_encode(['status' => 'success']);
    }
    exit;
}

// --- EMAIL ---
if ($action === 'send_email') {
    if (!$phpmailer_ready) { echo json_encode(['status' => 'error', 'message' => 'PHPMailer is missing.']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $recipientEmail = $data['email'] ?? '';
    
    $mail = new PHPMailer(true);
    try {
        $mail->isMail(); 
        $mail->setFrom('noreply@yourdomain.com', "What's for dinner?!");
        $mail->addAddress($recipientEmail);     
        $mail->isHTML(true);                                  
        $mail->Subject = "Your " . ($data['type'] ?? 'Report');
        $mail->Body = "<h2>Here is your " . htmlspecialchars($data['type'] ?? 'Report') . "</h2><p>" . nl2br(htmlspecialchars($data['content'] ?? '')) . "</p>";
        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Message could not be sent."]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
?>