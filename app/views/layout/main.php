<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'WTP', ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <?= $content ?? '' ?>
</body>
</html>
