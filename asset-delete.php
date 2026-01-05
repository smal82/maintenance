<?php
// asset-delete.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

$assetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($assetId === 0) {
    header('Location: ' . BASE_URL . '/assets.php');
    exit;
}

$asset = $db->fetchOne("SELECT * FROM assets WHERE id = :id", ['id' => $assetId]);

if (!$asset) {
    header('Location: ' . BASE_URL . '/assets.php');
    exit;
}

try {
    // Delete asset image if exists
    if (!empty($asset['image'])) {
        $imagePath = UPLOAD_PATH . '/assets/' . $asset['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    // Delete QR code if exists
    if (!empty($asset['qr_code'])) {
        $qrPath = QR_CODE_PATH . '/' . $asset['qr_code'];
        if (file_exists($qrPath)) {
            unlink($qrPath);
        }
    }
    
    // Delete asset
    $db->delete('assets', 'id = :id', ['id' => $assetId]);
    
    $_SESSION['success_message'] = 'Asset eliminato con successo';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Errore durante l\'eliminazione: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/assets.php');
exit;
?>