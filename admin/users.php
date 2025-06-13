<?php
$page_title = "Registered Users";
include 'includes/admin_header.php';

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = (int) $_POST['id'];
    $b_form = $conn->real_escape_string($_POST['b_form']);
    $email = $conn->real_escape_string($_POST['email']);
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;

    $update_sql = "UPDATE registered_users SET b_form = '$b_form', email = '$email', is_verified = '$is_verified' WHERE id = $id";
    if ($conn->query($update_sql)) {
        $success = "User updated successfully.";
    } else {
        $error = "Error updating user: " . $conn->error;
    }
}

// Fetch users
$sql = "SELECT * FROM registered_users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Check if edit is triggered
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Registered Users</h5>
  </div>
  <div class="card-body">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table id="usersTable" class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>B-Form</th>
                    <th>Email</th>
                    <th>Registered At</th>
                    <th>Verified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): 
                    
                    while ($row = $result->fetch_assoc()):
                ?>
                <?php if ($edit_id === (int)$row['id']): ?>
                    <!-- Edit row -->
                    <tr class="table-warning">
                        <form method="POST">
                            <td><?= $row['id']; ?></td>
                            <td><input type="text" name="b_form" class="form-control" value="<?= htmlspecialchars($row['b_form']); ?>" required></td>
                            <td><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($row['email']); ?>" required></td>
                            <td><?= date('d M Y h:i A', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_verified" value="1" <?= $row['is_verified'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Verified</label>
                                </div>
                            </td>
                            <td>
                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                <button type="submit" name="update_user" class="btn btn-sm btn-success">Save</button>
                                <a href="users.php" class="btn btn-sm btn-secondary">Cancel</a>
                            </td>
                        </form>
                    </tr>
                <?php else: ?>
                    <!-- Normal row -->
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['b_form']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= date('d M Y h:i A', strtotime($row['created_at'])); ?></td>
                        <td>
                            <?php
                                if (!isset($row['is_verified'])) {
                                    echo '<span class="text-muted">N/A</span>';
                                } else {
                                    echo $row['is_verified'] ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-secondary">Pending</span>';
                                }
                            ?>
                        </td>
                        <td>
                            <a href="users.php?edit=<?= $row['id']; ?>" class="btn btn-sm btn-warning"> <i class="fas fa-edit"></i></a>
                            <a href="delete_user.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">  <i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" class="text-center">No users found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
