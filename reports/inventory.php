<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';

session_start();
requireAdmin();

// Fetch all products ordered by category then name (flat list)
$products = Database::fetchAll("SELECT * FROM products ORDER BY category ASC, name ASC");

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-chart-bar"></i> Inventory Report</h2>
</div>

<!-- Search Bar -->
<div class="card" style="margin-bottom:1rem;">
    <div class="pos-search">
        <input type="text" id="inventorySearch" placeholder="🔍  Search product name or category..." oninput="filterInventory()">
    </div>
</div>

<!-- Flat Table -->
<div class="card" style="margin-bottom:1rem;">
    <table class="table inventory-table" id="inventoryTable">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="inventoryBody">
        <?php foreach ($products as $p): ?>
        <tr class="<?= $p['stock'] == 0 ? 'row-danger' : ($p['stock'] <= 5 ? 'row-warning' : '') ?>"
            data-name="<?= strtolower(e($p['name'])) ?>"
            data-category="<?= strtolower(e($p['category'])) ?>">
            <td><?= e($p['name']) ?></td>
            <td><?= e($p['category']) ?></td>
            <td><?= peso((float)$p['price']) ?></td>
            <td><?= (int)$p['stock'] ?></td>
            <td><span class="badge badge-<?= $p['status'] === 'available' ? 'success' : 'danger' ?>"><?= e($p['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p id="noResults" style="display:none; text-align:center; color:var(--text-muted); margin-top:2rem;">
    <i class="fa-solid fa-circle-xmark"></i> No products found.
</p>

<script>
function filterInventory() {
    const query = document.getElementById('inventorySearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#inventoryBody tr');
    let visible = 0;

    rows.forEach(row => {
        const name     = row.dataset.name     || '';
        const category = row.dataset.category || '';
        const match    = name.includes(query) || category.includes(query);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>