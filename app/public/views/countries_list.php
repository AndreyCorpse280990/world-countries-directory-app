<?php

// Этот файл генерирует HTML-страницу списка стран
ob_start();
?>

<h1>All Countries</h1>

<?php if (isset($flash_message) && isset($flash_type)): ?>
    <div class="alert alert-<?= htmlspecialchars($flash_type, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash_message, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<table class="table table-striped">
    <thead>
    <tr>
        <th>Short Name</th>
        <th>Full Name</th>
        <th>ISO Alpha-2</th>
        <th>ISO Alpha-3</th>
        <th>ISO Numeric</th>
        <th>Population</th>
        <th>Square (km²)</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php if (!empty($countries)): ?>
        <?php foreach ($countries as $country): ?>
            <?php if (!isset($country->shortName)): ?>
                <?php error_log("ERROR: Property shortName not found on object in countries_list.php"); ?>
                <tr><td colspan="8">Error: Invalid country object (missing shortName)</td></tr>
                <?php continue; ?>
            <?php endif; ?>
            <tr>
                <td><?= htmlspecialchars($country->shortName ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($country->fullName ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($country->isoAlpha2 ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($country->isoAlpha3 ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($country->isoNumeric ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format($country->population ?? 0, 0, '.', ',') ?></td>
                <td><?= number_format($country->square ?? 0.0, 2, '.', ',') ?></td>
                <td>
                    <a href="/countries/<?= urlencode($country->isoAlpha3 ?? '') ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="post" action="/countries/<?= urlencode($country->isoAlpha3 ?? '') ?>/delete" style="display: inline-block;"
                          onsubmit="return confirm('Are you sure you want to delete this country?');">
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-center">No countries found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<a href="/countries/new" class="btn btn-primary">Add New Country</a>

<?php
$list_content = ob_get_clean();


$content = $list_content;


$template_vars_for_base = [
    'title' => $title ?? 'Countries List',
    'content' => $content,
    'flash_message' => $flash_message ?? null,
    'flash_type' => $flash_type ?? null
];
extract($template_vars_for_base); 
include __DIR__ . '/base.php';
// include base.php сам выведет HTML
?>