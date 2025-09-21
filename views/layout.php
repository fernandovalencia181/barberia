<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Salón</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;700;900&display=swap" rel="stylesheet"> 
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="/css/fullcalendar.min.css" />
    <link rel="stylesheet" href="/build/css/app.css">
</head>
<body>
    <div class="contenedor">
        <div class="app">
            <?php echo $contenido; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="/build/js/menu.js"></script>
    <script src="/build/js/confirmar.js"></script>
    <?php echo $script ?? ""; ?>
</body>
</html>