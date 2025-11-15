<?php
session_start();
$adminPass = 'codersaofiles'; // CHANGE ME!

if (!isset($_SESSION['admin'])) {
    if ($_POST['pass'] ?? '' === $adminPass) $_SESSION['admin'] = true;
    else { echo '<form method="post" style="text-align:center;margin:100px;"><input type="password" name="pass" placeholder="Admin Password"><button>Login</button></form>'; exit; }
}

$dir = 'uploads/';
$files = glob($dir . '*.meta');

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    foreach (glob($dir . $id . '.*') as $f) @unlink($f);
    header('Location: admin.php'); exit;
}
?>
<!DOCTYPE html><html><head><title>Admin</title><style>table{width:100%;border-collapse:collapse;}th,td{padding:10px;border:1px solid #ccc;}</style></head>
<body style="font-family:Arial;padding:20px;">
<h2>Admin Panel</h2>
<table>
<tr><th>ID</th><th>Name</th><th>Type</th><th>Status</th><th>Action</th></tr>
<?php foreach ($files as $f):
    $id = pathinfo($f, PATHINFO_FILENAME);
    $meta = json_decode(file_get_contents($f), true);
    $name = $meta['original_name'] ?? 'Unknown';
    $type = $meta['is_public'] ? 'Public' : 'Private';
    $status = $meta['is_permanent'] ? 'Permanent' : '24h';
?>
<tr>
    <td><?= htmlspecialchars($id) ?></td>
    <td><?= htmlspecialchars($name) ?></td>
    <td><?= $type ?></td>
    <td><?= $status ?></td>
    <td><a href="?delete=<?= $id ?>" onclick="return confirm('Delete?')">Delete</a></td>
</tr>
<?php endforeach; ?>
</table>
<br><a href="index.php">Upload</a> | <a href="list.php">Public List</a>
</body></html>