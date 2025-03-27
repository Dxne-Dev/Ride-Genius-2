<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container py-5">
    <h2 class="text-center mb-4">Vérification de l'email</h2>
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="POST" action="index.php?page=verify-email">
        <div class="mb-3">
            <label for="verification_code" class="form-label">Code de vérification</label>
            <input type="text" class="form-control" id="verification_code" name="verification_code" required>
        </div>
        <button type="submit" class="btn btn-primary">Vérifier</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
