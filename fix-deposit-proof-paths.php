<?php
// FIX OLD DEPOSIT PROOF IMAGE PATHS
// This script fixes proof_image paths in wallet_transactions table
// from 'products/xxx.jpg' to 'payment-proofs/xxx.jpg' for existing files

require_once __DIR__ . '/config.php';

echo "<h1>🔧 Fix Deposit Proof Image Paths</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#fff;padding:15px;border:1px solid #ddd;border-radius:5px;} .box{background:#fff;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

// Step 1: Find all transactions with proof_image
$stmt = $pdo->query("SELECT id, proof_image FROM wallet_transactions WHERE proof_image IS NOT NULL AND proof_image != ''");
$transactions = $stmt->fetchAll();

echo "<div class='box'>";
echo "<h2>📊 Scan Results</h2>";
echo "<p>Found " . count($transactions) . " transactions with proof images</p>";
echo "</div>";

if (empty($transactions)) {
    echo "<div class='box'><p class='info'>✓ No transactions found. Nothing to fix!</p></div>";
    exit;
}

// Step 2: Analyze paths
$needsFix = [];
$alreadyCorrect = [];
$notFound = [];

foreach ($transactions as $txn) {
    $path = $txn['proof_image'];

    // Check if path starts with 'products/' (wrong path)
    if (strpos($path, 'products/') === 0) {
        $filename = basename($path);
        $correctPath = 'payment-proofs/' . $filename;

        // Check if file exists in correct location
        $correctFullPath = __DIR__ . '/uploads/' . $correctPath;
        $wrongFullPath = __DIR__ . '/uploads/' . $path;

        if (file_exists($correctFullPath)) {
            $needsFix[] = [
                'id' => $txn['id'],
                'old_path' => $path,
                'new_path' => $correctPath,
                'file_exists' => true
            ];
        } elseif (file_exists($wrongFullPath)) {
            // File is in wrong location, need to move
            $needsFix[] = [
                'id' => $txn['id'],
                'old_path' => $path,
                'new_path' => $correctPath,
                'file_exists' => true,
                'needs_move' => true
            ];
        } else {
            $notFound[] = [
                'id' => $txn['id'],
                'path' => $path
            ];
        }
    } elseif (strpos($path, 'payment-proofs/') === 0) {
        $alreadyCorrect[] = $txn['id'];
    } else {
        // Check if path exists as-is
        $fullPath = __DIR__ . '/uploads/' . $path;
        if (file_exists($fullPath)) {
            $alreadyCorrect[] = $txn['id'];
        } else {
            $notFound[] = [
                'id' => $txn['id'],
                'path' => $path
            ];
        }
    }
}

echo "<div class='box'>";
echo "<h2>📋 Analysis</h2>";
echo "<pre>";
echo "Needs Fixing: " . count($needsFix) . "\n";
echo "Already Correct: " . count($alreadyCorrect) . "\n";
echo "File Not Found: " . count($notFound) . "\n";
echo "</pre>";
echo "</div>";

// Show details of records that need fixing
if (!empty($needsFix)) {
    echo "<div class='box'>";
    echo "<h2>🔧 Records to Fix</h2>";
    echo "<table style='width:100%; border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th style='padding:8px;border:1px solid #ddd;'>ID</th><th style='padding:8px;border:1px solid #ddd;'>Old Path</th><th style='padding:8px;border:1px solid #ddd;'>New Path</th><th style='padding:8px;border:1px solid #ddd;'>Action</th></tr>";

    foreach ($needsFix as $fix) {
        $action = isset($fix['needs_move']) ? "Move & Update DB" : "Update DB only";
        echo "<tr>";
        echo "<td style='padding:8px;border:1px solid #ddd;'>" . $fix['id'] . "</td>";
        echo "<td style='padding:8px;border:1px solid #ddd; font-size:11px;'>" . htmlspecialchars($fix['old_path']) . "</td>";
        echo "<td style='padding:8px;border:1px solid #ddd; font-size:11px;'>" . htmlspecialchars($fix['new_path']) . "</td>";
        echo "<td style='padding:8px;border:1px solid #ddd;'>" . $action . "</td>";
        echo "</tr>";
    }

    echo "</table>";
    echo "</div>";
}

// Show file not found
if (!empty($notFound)) {
    echo "<div class='box'>";
    echo "<h2>⚠️ Files Not Found</h2>";
    echo "<p class='error'>These records reference files that don't exist:</p>";
    echo "<pre>";
    foreach ($notFound as $nf) {
        echo "ID " . $nf['id'] . ": " . $nf['path'] . "\n";
    }
    echo "</pre>";
    echo "</div>";
}

// Step 3: Fix if requested
if (isset($_GET['apply_fix']) && $_GET['apply_fix'] === 'yes') {
    echo "<div class='box'>";
    echo "<h2>🚀 Applying Fixes...</h2>";

    $fixed = 0;
    $errors = 0;

    try {
        $pdo->beginTransaction();

        foreach ($needsFix as $fix) {
            try {
                // Move file if needed
                if (isset($fix['needs_move'])) {
                    $oldFullPath = __DIR__ . '/uploads/' . $fix['old_path'];
                    $newFullPath = __DIR__ . '/uploads/' . $fix['new_path'];

                    // Create directory if not exists
                    $newDir = dirname($newFullPath);
                    if (!is_dir($newDir)) {
                        mkdir($newDir, 0755, true);
                    }

                    if (rename($oldFullPath, $newFullPath)) {
                        echo "<p class='success'>✓ Moved file: " . basename($fix['old_path']) . "</p>";
                    } else {
                        echo "<p class='error'>✗ Failed to move file: " . basename($fix['old_path']) . "</p>";
                        $errors++;
                        continue;
                    }
                }

                // Update database
                $stmt = $pdo->prepare("UPDATE wallet_transactions SET proof_image = ? WHERE id = ?");
                $stmt->execute([$fix['new_path'], $fix['id']]);

                echo "<p class='success'>✓ Updated DB record ID " . $fix['id'] . "</p>";
                $fixed++;

            } catch (Exception $e) {
                echo "<p class='error'>✗ Error fixing ID " . $fix['id'] . ": " . $e->getMessage() . "</p>";
                $errors++;
            }
        }

        $pdo->commit();

        echo "<hr>";
        echo "<h3 class='success'>✅ DONE!</h3>";
        echo "<p><strong>Fixed:</strong> $fixed records</p>";
        echo "<p><strong>Errors:</strong> $errors records</p>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p class='error'>✗ Transaction failed: " . $e->getMessage() . "</p>";
    }

    echo "</div>";

    echo "<div style='text-align:center;margin:30px 0;'>";
    echo "<a href='fix-deposit-proof-paths.php' style='display:inline-block;padding:12px 24px;background:#10B981;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>🔄 Run Check Again</a> ";
    echo "<a href='member/wallet.php' style='display:inline-block;padding:12px 24px;background:#3B82F6;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>👛 Go to Wallet</a>";
    echo "</div>";

} else {
    // Show apply fix button
    if (!empty($needsFix)) {
        echo "<div class='box' style='text-align:center;'>";
        echo "<h2>⚠️ Ready to Fix?</h2>";
        echo "<p>This will update " . count($needsFix) . " records in the database.</p>";
        echo "<a href='?apply_fix=yes' style='display:inline-block;padding:16px 32px;background:#DC2626;color:white;text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;margin-top:16px;' onclick='return confirm(\"Apply fix to " . count($needsFix) . " records?\")'>⚠️ APPLY FIX NOW</a>";
        echo "</div>";
    } else {
        echo "<div class='box' style='text-align:center;'>";
        echo "<h2 class='success'>✅ All Good!</h2>";
        echo "<p>No fixes needed. All proof image paths are correct!</p>";
        echo "<a href='member/wallet.php' style='display:inline-block;padding:12px 24px;background:#3B82F6;color:white;text-decoration:none;border-radius:8px;font-weight:bold;margin-top:16px;'>👛 Go to Wallet</a>";
        echo "</div>";
    }
}

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;font-size:13px;'>Fix script completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
