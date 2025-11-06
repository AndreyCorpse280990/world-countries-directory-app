<?php
// app/public/views/countries_list.php
// Этот файл генерирует *только* HTML-код для тела страницы (списка стран).
// Он НЕ должен включать base.php напрямую.
// Переменные $countries, $title, $flash_message, $flash_type
// должны быть доступны через extract() или $GLOBALS в CountryPagesController.php до вызова include.

// Проверим, определена ли переменная $countries и является ли она массивом
if (!isset($countries) || !is_array($countries)) {
    error_log("ERROR: Variable \$countries is not defined or is not an array in countries_list.php");
    echo '<div class="alert alert-danger">An error occurred while loading the country list (data is not available).</div>';
    return; // ВАЖНО: завершаем выполнение *этого* файла, а не всей страницы
}

// Начинаем буферизацию только для *содержимого* списка
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
// Завершаем буферизацию и сохраняем *только* содержимое списка
$list_content = ob_get_clean();

// Выводим $list_content (таблицу) в буфер контроллера
echo $list_content;
?>