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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard | Product Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="dashboard-page <?= $editProduct ? 'modal-open' : '' ?>">

    <main class="dashboard-grid">
        <?php if ($message = flash('error')): ?>
            <div class="alert alert-error"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($message = flash('success')): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="panel panel-table">
            <h2>Products Inventory</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Details</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$products): ?>
                        <tr>
                            <td colspan="5" class="empty-state">No products found. Start by adding one.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td data-label="Image">
                                    <img class="thumb" src="../<?= e((string) $product['image_path']) ?>" alt="<?= e((string) $product['name']) ?>" loading="lazy">
                                </td>
                                <td data-label="Details" class="details-cell">
                                    <strong><?= e((string) $product['name']) ?></strong>
                                    <span class="meta-text">ID: <?= (int) $product['id'] ?> | Updated: <?= e(date('M d, Y', strtotime((string) $product['updated_at']))) ?></span>
                                </td>
                                <td data-label="Price" class="price-cell">
                                    <?= $product['price'] !== null ? 'LKR ' . number_format((float)$product['price'], 2) : '-' ?>
                                </td>
                                <td data-label="Status">
                                    <span class="status <?= (int) $product['is_active'] === 1 ? 'status-active' : 'status-hidden' ?>">
                                        <?= (int) $product['is_active'] === 1 ? 'Active' : 'Hidden' ?>
                                    </span>
                                </td>
                                <td data-label="Actions" class="actions-cell">
                                    <div class="actions">
                                        <button class="btn btn-small btn-ghost admin-open-form" type="button" data-edit-url="dashboard.php?edit=<?= (int) $product['id'] ?>">Edit</button>
                                        <form method="post" onsubmit="return confirm('Delete this product permanently?');">
                                            <?= csrf_field('product_manage') ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                            <button class="btn btn-small btn-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="admin-form-modal <?= $editProduct ? 'is-open' : '' ?>" id="admin-form-modal" aria-hidden="<?= $editProduct ? 'false' : 'true' ?>" role="dialog" aria-modal="true">
        <div class="admin-form-modal__backdrop" data-close-modal></div>
        <section class="panel panel-form admin-form-modal__panel" role="document">
            <div class="admin-form-modal__header">
                <h2 id="admin-form-modal-title"><?= $editProduct ? 'Update Product' : 'Add New Product' ?></h2>
                <button type="button" class="admin-form-modal__close" aria-label="Close form" data-close-modal>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <form method="post" enctype="multipart/form-data" class="admin-form" id="admin-product-form" novalidate>
                <?= csrf_field('product_manage') ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editProduct['id'] ?? 0) ?>">

                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required maxlength="180" value="<?= e((string) ($editProduct['name'] ?? '')) ?>">

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Optional product details"><?= e((string) ($editProduct['description'] ?? '')) ?></textarea>

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

                <label for="image">Product Image (JPG, PNG, WEBP)</label>
                <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" <?= $editProduct ? '' : 'required' ?>>

                <?php if ($editProduct && !empty($editProduct['image_path'])): ?>
                    <img class="edit-preview" src="../<?= e((string) $editProduct['image_path']) ?>" alt="Current product image">
                <?php endif; ?>

                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?= !isset($editProduct['is_active']) || (int) $editProduct['is_active'] === 1 ? 'checked' : '' ?>>
                    Visible on website
                </label>

                <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; flex-direction: column;">
                    <button class="btn btn-primary" type="submit"><?= $editProduct ? 'Update Product' : 'Save Product' ?></button>
                    <?php if ($editProduct): ?>
                        <button type="button" class="btn btn-ghost" data-close-modal style="width: 100%;">Cancel</button>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>

    <div class="admin-bottom-actions" role="toolbar" aria-label="Admin quick actions">
        <button type="button" class="btn btn-ghost" onclick="window.location.href='dashboard.php'" aria-label="Refresh">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            <span>Refresh</span>
        </button>
        
        <button type="button" class="btn btn-primary action-highlight" id="admin-action-add">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Add New</span>
        </button>

        <a class="btn btn-danger" href="logout.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
        </a>
    </div>

    <script>
        (function initAdminDashboardActions() {
            const modal = document.getElementById('admin-form-modal');
            const form = document.getElementById('admin-product-form');
            const addButton = document.getElementById('admin-action-add');
            const closeButtons = document.querySelectorAll('[data-close-modal]');
            const editButtons = document.querySelectorAll('.admin-open-form');
            const title = document.getElementById('admin-form-modal-title');
            const idField = form.querySelector('input[name="id"]');
            const fileField = form.querySelector('input[name="image"]');
            const activeField = form.querySelector('input[name="is_active"]');
            const submitBtn = form.querySelector('button[type="submit"]');

            function openModal() {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            }

            function closeModal() {
                // If we are in edit mode, closing should clear the URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('edit')) {
                    window.location.href = 'dashboard.php';
                    return;
                }
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            }

            function setAddNewMode() {
                form.reset();
                if (idField) idField.value = '0';
                if (title) title.textContent = 'Add New Product';
                if (fileField) fileField.required = true;
                if (activeField) activeField.checked = true;
                if (submitBtn) submitBtn.textContent = 'Save Product';

                const preview = form.querySelector('.edit-preview');
                if (preview) preview.remove();
            }

            addButton.addEventListener('click', function () {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('edit')) {
                    // Redirect without edit param so form clears fully
                    window.location.href = 'dashboard.php?add_new=true';
                } else {
                    setAddNewMode();
                    openModal();
                }
            });

            // Auto-open if coming back from an edit redirect logic
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('add_new')) {
                setAddNewMode();
                openModal();
                // Clean URL visually without reload
                window.history.replaceState({}, document.title, "dashboard.php");
            }

            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            editButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const url = button.getAttribute('data-edit-url');
                    if (url) window.location.href = url;
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>