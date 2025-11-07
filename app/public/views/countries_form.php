<?php
// app/public/views/countries_form.php
// Этот файл генерирует HTML-страницу формы добавления/редактирования
ob_start();
?>

<h1><?= htmlspecialchars($title) ?></h1>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="
    <?php if ($action === 'add'): ?>
        /countries/new
    <?php elseif ($action === 'edit'): ?>
        /countries/<?= urlencode($originalCountry->isoAlpha3 ?? $country->isoAlpha3) ?>/edit
    <?php endif; ?>
">
    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <div class="mb-3">
        <label for="shortName" class="form-label">Short Name</label>
        <input type="text" class="form-control" id="shortName" name="shortName" value="<?= htmlspecialchars($country->shortName ?? '') ?>" required>
    </div>
    <div class="mb-3">
        <label for="fullName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="fullName" name="fullName" value="<?= htmlspecialchars($country->fullName ?? '') ?>" required>
    </div>
    <div class="mb-3">
        <label for="isoAlpha2" class="form-label">ISO Alpha-2 (Read-only)</label>
        <input type="text" class="form-control" id="isoAlpha2" name="isoAlpha2" value="<?= htmlspecialchars($country->isoAlpha2 ?? '') ?>" <?php if ($action === 'edit') echo 'readonly'; ?> required>
    </div>
    <div class="mb-3">
        <label for="isoAlpha3" class="form-label">ISO Alpha-3 (Read-only)</label>
        <input type="text" class="form-control" id="isoAlpha3" name="isoAlpha3" value="<?= htmlspecialchars($country->isoAlpha3 ?? '') ?>" <?php if ($action === 'edit') echo 'readonly'; ?> required>
    </div>
    <div class="mb-3">
        <label for="isoNumeric" class="form-label">ISO Numeric (Read-only)</label>
        <input type="text" class="form-control" id="isoNumeric" name="isoNumeric" value="<?= htmlspecialchars($country->isoNumeric ?? '') ?>" <?php if ($action === 'edit') echo 'readonly'; ?> required>
    </div>
    <div class="mb-3">
        <label for="population" class="form-label">Population</label>
        <input type="number" class="form-control" id="population" name="population" value="<?= htmlspecialchars($country->population ?? '') ?>" min="0" required>
    </div>
    <div class="mb-3">
        <label for="square" class="form-label">Square (km²)</label>
        <input type="number" step="any" class="form-control" id="square" name="square" value="<?= htmlspecialchars($country->square ?? '') ?>" min="0" required>
    </div>
    <button type="submit" class="btn btn-success"><?php if ($action === 'add') echo 'Create'; else echo 'Update'; ?></button>
    <a href="/countries" class="btn btn-secondary">Cancel</a>
</form>

<?php
$form_content = ob_get_clean();

$content = $form_content;

$template_vars_for_base = [
    'title' => $title ?? 'Country Form',
    'content' => $content,
    'flash_message' => $flash_message ?? null,
    'flash_type' => $flash_type ?? null
];
extract($template_vars_for_base); 
include __DIR__ . '/base.php'; 
?>