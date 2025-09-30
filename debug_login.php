<?php
// Максимально детальний звіт про помилки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SECURE_ACCESS', true);

echo "<pre>";
echo "--- Фінальний тест життєздатності з'єднання ---\n\n";

echo "Етап 1: Підключення файлу db_connect.php...\n";
require_once './includes/db_connect.php';
echo "Статус: Файл підключено.\n\n";

echo "Етап 2: Перевірка змінної \$conn...\n";
if (isset($conn)) {
    echo "Статус: Змінна \$conn ІСНУЄ.\n";
    if (is_object($conn)) {
        echo "Статус: Змінна \$conn є ОБ'ЄКТОМ.\n";
    } else {
        die("ПОМИЛКА: Змінна \$conn існує, але це НЕ ОБ'ЄКТ. Її тип: " . gettype($conn));
    }
} else {
    die("КРИТИЧНА ПОМИЛКА: Змінна \$conn НЕ ІСНУЄ після підключення db_connect.php.");
}
echo "\n";

echo "Етап 3: Перевірка на помилки підключення...\n";
if ($conn->connect_error) {
    die("ПОМИЛКА: Властивість connect_error повідомляє про помилку: " . $conn->connect_error);
}
echo "Статус: Помилок у властивості connect_error немає.\n\n";


echo "Етап 4: Спроба виконати найпростіший запит 'SELECT 1'...\n";
$result = $conn->query("SELECT 1");

if ($result) {
    echo "СТАТУС: УСПІХ! Запит 'SELECT 1' виконано успішно.\n\n";
    echo "--- ВИСНОВОК ---\n";
    echo "Об'єкт з'єднання \$conn повністю робочий. Проблема криється десь у логіці самого login.php або в даних.\n";
} else {
    echo "СТАТУС: КРИТИЧНА ПОМИЛКА!\n";
    echo "Не вдалося виконати навіть найпростіший запит 'SELECT 1'.\n";
    echo "Помилка MySQL: " . htmlspecialchars($conn->error) . "\n\n";
    echo "--- ВИСНОВОК ---\n";
    echo "Проблема знаходиться всередині об'єкта \$conn або в налаштуваннях MySQL.\n";
    echo "Можливі причини: невірні права доступу у користувача БД, проблеми з кодуванням або конфігурацією сервера MySQL.\n";
}

$conn->close();
echo "\n--- Тест завершено ---";
echo "</pre>";

?>