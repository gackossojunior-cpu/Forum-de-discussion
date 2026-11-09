<?php
$assetPrefix = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin' ? '../' : '';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f5f7">
    <meta name="description" content="Un espace simple pour poser vos questions, partager vos idées et apprendre ensemble.">
    <script>
        (() => {
            try {
                const savedTheme = localStorage.getItem('forum-theme');
                const theme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.dataset.theme = theme;
            } catch {
                document.documentElement.dataset.theme = window.matchMedia('(prefers-color-scheme: dark)').matches
                    ? 'dark'
                    : 'light';
            }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $assetPrefix; ?>assets/style.css?v=4">
    <script src="<?php echo $assetPrefix; ?>assets/theme.js" defer></script>
    <title><?php echo forum_escape($pageTitle ?? 'Forum — La communauté s’entraide'); ?></title>
</head>