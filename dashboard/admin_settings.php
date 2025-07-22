<?php
include '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $login_attempt_limit = (int)$_POST['login_attempt_limit'];
        
        // Update global setting
        $stmt = $db->prepare("UPDATE users SET login_attempt_limit = ? WHERE role = 'admin'");
        $stmt->execute([$login_attempt_limit]);
        
        $success_message = "Settings updated successfully!";
    } elseif (isset($_POST['unlock_account'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Unlock the account
        $stmt = $db->prepare("UPDATE users SET account_locked = 0, failed_login_attempts = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $success_message = "Account unlocked successfully!";
    }
}

// Get current settings
$settings = $db->query("SELECT login_attempt_limit FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Get locked accounts
$locked_accounts = $db->query("SELECT id, name, email, last_failed_login FROM users WHERE account_locked = 1")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - ASD Academy</title>
    <?php include '../header.php'; ?>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div class="flex-1 ml-0 md:ml-64 min-h-screen">
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fas fa-cog text-blue-500"></i>
                <span>Admin Settings</span>
            </h1>
        </header>

        <div class="p-4 md:p-6">
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Login Settings -->
                <div class="bg-white p-5 rounded-xl shadow">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Login Security Settings</h2>
                    <form method="POST">
                        <div class="mb-4">
                            <label for="login_attempt_limit" class="block text-sm font-medium text-gray-700 mb-1">
                                Maximum Login Attempts
                            </label>
                            <input type="number" id="login_attempt_limit" name="login_attempt_limit" 
                                   min="1" max="10" value="<?php echo htmlspecialchars($settings['login_attempt_limit'] ?? 5); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <p class="text-xs text-gray-500 mt-1">Number of failed attempts before account is locked (1-10)</p>
                        </div>
                        <button type="submit" name="update_settings" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Update Settings
                        </button>
                    </form>
                </div>
                
                <!-- Locked Accounts -->
                <div class="bg-white p-5 rounded-xl shadow">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Locked Accounts</h2>
                    
                    <?php if (empty($locked_accounts)): ?>
                        <p class="text-gray-500">No accounts are currently locked.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Failed Attempt</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($locked_accounts as $account): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($account['name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($account['email']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $account['last_failed_login'] ? date('M j, Y g:i A', strtotime($account['last_failed_login'])) : 'Never'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $account['id']; ?>">
                                                    <button type="submit" name="unlock_account" class="text-blue-500 hover:text-blue-700">
                                                        Unlock Account
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../footer.php'; ?>
</body>
</html>