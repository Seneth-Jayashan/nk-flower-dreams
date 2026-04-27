<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/security.php';

require_admin_login();
$pdo = Database::connection();

function normalize_checkbox(string $field): int
{
    return isset($_POST[$field]) ? 1 : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $csrf = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf($csrf, 'product_manage')) {
        flash('error', 'Security token mismatch. Please try again.');
        redirect('dashboard.php');
    }

    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('Invalid product selected for deletion.');
            }

            $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute([':id' => $id]);
            flash('success', 'Product deleted successfully.');
            redirect('dashboard.php');
        }

        if ($action !== 'save') {
            throw new RuntimeException('Unsupported action.');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $priceInput = (string) ($_POST['price'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = normalize_checkbox('is_active');

        if ($name === '' || mb_strlen($name) > 180) {
            throw new RuntimeException('Product name is required and must be under 180 characters.');
        }

        $price = validate_price($priceInput);
        if ($priceInput !== '' && $price === null) {
            throw new RuntimeException('Price format is invalid. Example: 2500 or 2500.50');
        }

        $uploadedPath = process_product_image_upload($_FILES['image'] ?? []);

        if ($id > 0) {
            $query = 'UPDATE products
                      SET name = :name,
                          slug = :slug,
                          description = :description,
                          price = :price,
                          sort_order = :sort_order,
                          is_active = :is_active';

            $params = [
                ':name' => $name,
                ':slug' => slugify($name) . '-' . $id,
                ':description' => $description !== '' ? $description : null,
                ':price' => $price,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ];

            if ($uploadedPath !== null) {
                $query .= ', image_path = :image_path';
                $params[':image_path'] = $uploadedPath;
            }

            $query .= ' WHERE id = :id';
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            flash('success', 'Product updated successfully.');
            redirect('dashboard.php');
        }

        if ($uploadedPath === null) {
            throw new RuntimeException('Product image is required for new products.');
        }

        $slug = slugify($name) . '-' . substr(bin2hex(random_bytes(5)), 0, 6);
        $stmt = $pdo->prepare(
            'INSERT INTO products (name, slug, description, price, image_path, is_active, sort_order)
             VALUES (:name, :slug, :description, :price, :image_path, :is_active, :sort_order)'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description !== '' ? $description : null,
            ':price' => $price,
            ':image_path' => $uploadedPath,
            ':is_active' => $isActive,
            ':sort_order' => $sortOrder,
        ]);

        flash('success', 'Product added successfully.');
        redirect('dashboard.php');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
        redirect('dashboard.php');
    }
}

$products = $pdo->query(
    'SELECT id, name, description, price, image_path, is_active, sort_order, updated_at
     FROM products
     ORDER BY sort_order ASC, id DESC'
)->fetchAll();

$editId = (int) ($_GET['edit'] ?? 0);
$editProduct = null;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $editProduct = $stmt->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | NK Flower Dreams</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <div>
            <h1>Product Management</h1>
            <p>Welcome, <?= e((string) ($_SESSION['admin_name'] ?? 'Admin')) ?></p>
        </div>
        <div class="header-actions">
            <a class="btn btn-ghost" href="../index.php" target="_blank" rel="noopener">View Website</a>
            <a class="btn btn-danger" href="logout.php">Logout</a>
        </div>
    </header>

    <main class="dashboard-grid">
        <section class="panel panel-form">
            <h2><?= $editProduct ? 'Update Product' : 'Add Product' ?></h2>

            <?php if ($message = flash('error')): ?>
                <div class="alert alert-error"><?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="admin-form" novalidate>
                <?= csrf_field('product_manage') ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editProduct['id'] ?? 0) ?>">

                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required maxlength="180" value="<?= e((string) ($editProduct['name'] ?? '')) ?>">

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Optional product details"><?= e((string) ($editProduct['description'] ?? '')) ?></textarea>

                <div class="field-row">
                    <div>
                        <label for="price">Price (LKR)</label>
                        <input type="text" id="price" name="price" placeholder="2500.00" value="<?= e((string) ($editProduct['price'] ?? '')) ?>">
                    </div>
                    <div>
                        <label for="sort_order">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" step="1" value="<?= (int) ($editProduct['sort_order'] ?? 0) ?>">
                    </div>
                </div>

                <label for="image">Product Image (JPG, PNG, WEBP, max 3MB)</label>
                <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" <?= $editProduct ? '' : 'required' ?>>

                <?php if ($editProduct && !empty($editProduct['image_path'])): ?>
                    <img class="edit-preview" src="../<?= e((string) $editProduct['image_path']) ?>" alt="Current product image">
                <?php endif; ?>

                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?= !isset($editProduct['is_active']) || (int) $editProduct['is_active'] === 1 ? 'checked' : '' ?>>
                    Visible on website
                </label>

                <button class="btn btn-primary" type="submit"><?= $editProduct ? 'Update Product' : 'Add Product' ?></button>

                <?php if ($editProduct): ?>
                    <a class="btn btn-ghost" href="dashboard.php">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="panel panel-table">
            <h2>Products</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$products): ?>
                        <tr>
                            <td colspan="7">No products yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= (int) $product['id'] ?></td>
                                <td>
                                    <img class="thumb" src="../<?= e((string) $product['image_path']) ?>" alt="<?= e((string) $product['name']) ?>">
                                </td>
                                <td><?= e((string) $product['name']) ?></td>
                                <td><?= $product['price'] !== null ? 'Rs. ' . e((string) $product['price']) : '-' ?></td>
                                <td>
                                    <span class="status <?= (int) $product['is_active'] === 1 ? 'status-active' : 'status-hidden' ?>">
                                        <?= (int) $product['is_active'] === 1 ? 'Active' : 'Hidden' ?>
                                    </span>
                                </td>
                                <td><?= e((string) $product['updated_at']) ?></td>
                                <td class="actions">
                                    <a class="btn btn-small btn-ghost" href="dashboard.php?edit=<?= (int) $product['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this product?');">
                                        <?= csrf_field('product_manage') ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                        <button class="btn btn-small btn-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
