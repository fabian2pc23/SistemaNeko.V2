<?php

/** @var string $content */
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title><?= APP_NAME ?> - Autopartes y Ferretería</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <!-- Custom -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>

<body>

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="py-4">
        <div class="container">
            <?= $content ?>
        </div>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <?php if (isset($includeCheckoutJS) && $includeCheckoutJS): ?>
        <script src="<?= BASE_URL ?>/assets/js/checkout.js"></script>
    <?php endif; ?>
</body>

</html>