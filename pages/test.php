<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пример секции "Преимущества"</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Общие стили для демонстрации */
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body {
            background-color: #121a2f; /* Фон как на вашем сайте */
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 50px 0;
        }

        /* Стили самой секции */
        .advantages-section {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .advantages-section h2 {
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 50px;
        }

        .advantages-grid {
            display: grid;
            /* Адаптивная сетка на современном CSS Grid */
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .advantage-card {
            background-color: #1a2340; /* Фон карточки как у вас */
            padding: 40px 30px;
            border-radius: 10px;
            border: 1px solid #2a3559; /* Рамка как у вас */
            text-align: left;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .advantage-card:hover {
            transform: translateY(-10px);
            border-color: #007bff; /* Ваш фирменный синий при наведении */
        }

        .advantage-card .icon {
            font-size: 3rem;
            color: #007bff; /* Синий цвет иконки */
            margin-bottom: 20px;
        }

        .advantage-card h3 {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0 0 15px 0;
        }

        .advantage-card p {
            color: #a9b3d0; /* Светло-серый для текста */
            font-size: 1rem;
            line-height: 1.6;
            margin: 0;
        }
    </style>
</head>
<body>

<section class="advantages-section">
    <h2>Наши Преимущества</h2>
    <div class="advantages-grid">
        <div class="advantage-card">
            <div class="icon"><i class="fas fa-shield-halved"></i></div>
            <h3>DDoS-защита</h3>
            <p>Мы обеспечиваем надежную защиту от всех известных типов DDoS-атак, гарантируя стабильность вашего проекта.</p>
        </div>
        <div class="advantage-card">
            <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
            <h3>Высокая скорость</h3>
            <p>Использование NVMe SSD дисков и оптимизированного ПО обеспечивает максимальную производительность сайтов.</p>
        </div>
        <div class="advantage-card">
            <div class="icon"><i class="fas fa-headset"></i></div>
            <h3>Поддержка 24/7</h3>
            <p>Наша экспертная поддержка готова помочь вам в любое время суток, без выходных и праздников.</p>
        </div>
        <div class="advantage-card">
            <div class="icon"><i class="fas fa-database"></i></div>
            <h3>Ежедневные бэкапы</h3>
            <p>Мы автоматически создаем резервные копии ваших данных каждый день, чтобы вы ничего не потеряли.</p>
        </div>
        <div class="advantage-card">
            <div class="icon"><i class="fas fa-server"></i></div>
            <h3>Современное оборудование</h3>
            <p>Используем только серверное оборудование от ведущих мировых производителей для лучшей надежности.</p>
        </div>
        <div class="advantage-card">
            <div class="icon"><i class="fas fa-rocket"></i></div>
            <h3>Быстрая активация</h3>
            <p>Ваш хостинг-аккаунт будет готов к работе уже через несколько минут после оформления заказа.</p>
        </div>
    </div>
</section>

</body>
</html>