<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$data_dir = __DIR__ . '/data';
$uploads_dir = dirname(__DIR__) . '/uploads';

if (!is_dir($data_dir)) mkdir($data_dir, 0777, true);
if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0777, true);

$orders_file = $data_dir . '/orders.json';
$news_file = $data_dir . '/news.json';
$promos_file = $data_dir . '/promos.json';
$gallery_file = $data_dir . '/gallery.json';
$users_file = $data_dir . '/users.json';

// Инициализация файлов, если их нет
if (!file_exists($orders_file)) file_put_contents($orders_file, '[]');
if (!file_exists($news_file)) file_put_contents($news_file, '[]');
if (!file_exists($promos_file)) file_put_contents($promos_file, '[]');
if (!file_exists($gallery_file)) file_put_contents($gallery_file, '[]');

// Читаем пользователей
$users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
if (!is_array($users)) $users = [];

// ГАРАНТИРУЕМ, ЧТО АДМИН ВСЕГДА СУЩЕСТВУЕТ
if (!isset($users['admin'])) {
    $users['admin'] = ['pass' => 'poseidon2025', 'role' => 'admin', 'name' => 'Главный Админ'];
    file_put_contents($users_file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// --- ДОБАВЛЕНИЕ НОВОГО СОТРУДНИКА (Только Админ) ---
$add_user_error = '';
$add_user_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_btn'])) {
    // Проверяем, что запрос делает админ
    if (isset($_SESSION['logged_in']) && $_SESSION['role'] === 'admin') {
        $new_login = trim($_POST['new_user_login']);
        $new_pass = $_POST['new_user_pass'];
        $new_name = trim($_POST['new_user_name']);
        $new_role = $_POST['new_user_role'];

        if (empty($new_login) || empty($new_pass) || empty($new_name)) {
            $add_user_error = 'Заполните все поля формы.';
        } elseif (isset($users[$new_login])) {
            $add_user_error = 'Пользователь с таким логином уже существует.';
        } else {
            $users[$new_login] = [
                'pass' => $new_pass,
                'role' => $new_role,
                'name' => $new_name
            ];
            file_put_contents($users_file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $add_user_success = 'Сотрудник успешно добавлен!';
        }
    }
}

// Регистрация (на странице входа)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $l = trim($_POST['login']);
    if (!isset($users[$l])) {
        $users[$l] = ['pass' => $_POST['password'], 'role' => 'employee', 'name' => trim($_POST['name'])];
        file_put_contents($users_file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $reg_success = 'Успешная регистрация!';
    } else {
        $reg_error = 'Логин уже существует';
    }
}

// Вход
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $l = trim($_POST['login']);
    $p = $_POST['password'];
    
    if (isset($users[$l]) && $users[$l]['pass'] === $p) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $l;
        $_SESSION['role'] = $users[$l]['role'];
        $_SESSION['name'] = $users[$l]['name'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}

// Форма авторизации
if (!isset($_SESSION['logged_in'])) {
    ?>
    <!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Вход — Посейдон</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #ebf8ff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #1a365d; }
        .login-box { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 20px 50px rgba(26, 54, 93, 0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-top: 0; font-weight: 800; margin-bottom: 25px; }
        input { width: 100%; padding: 15px; margin-bottom: 15px; border: 1.5px solid #dce7f0; border-radius: 10px; font-family: inherit; box-sizing: border-box; outline: none; }
        .btn-main { width: 100%; padding: 15px; background: linear-gradient(135deg, #4299e1, #2b6cb0); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 1rem; margin-bottom: 10px; }
        .btn-outline { width: 100%; padding: 15px; background: #fff; color: #2b6cb0; border: 2px solid #2b6cb0; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 1rem; }
        .error { color: #ff4d4f; text-align: center; margin-bottom: 15px; font-weight: 600; background: #fee2e2; padding: 10px; border-radius: 8px;}
        .success { color: #10b981; text-align: center; margin-bottom: 15px; font-weight: 600; background: #dcfce7; padding: 10px; border-radius: 8px;}
        .hidden { display: none; }
    </style></head><body>
        <div class="login-box">
            <div id="login-form">
                <h2>Вход в систему</h2>
                <?php if (!empty($error)): ?><div class="error"><?= $error ?></div><?php endif; ?>
                <?php if (!empty($reg_success)): ?><div class="success"><?= $reg_success ?></div><?php endif; ?>
                <form method="POST">
                    <input type="text" name="login" placeholder="Логин" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <button type="submit" name="login_btn" class="btn-main">Войти</button>
                </form>
                <button type="button" class="btn-outline" onclick="document.getElementById('login-form').classList.add('hidden'); document.getElementById('register-form').classList.remove('hidden');">Регистрация</button>
            </div>
            <div id="register-form" class="hidden">
                <h2>Регистрация</h2>
                <?php if (!empty($reg_error)): ?><div class="error"><?= $reg_error ?></div><?php endif; ?>
                <form method="POST">
                    <input type="text" name="name" placeholder="Имя" required>
                    <input type="text" name="login" placeholder="Логин" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <button type="submit" name="register" class="btn-main">Зарегистрироваться</button>
                </form>
                <button type="button" class="btn-outline" onclick="document.getElementById('register-form').classList.add('hidden'); document.getElementById('login-form').classList.remove('hidden');">Назад</button>
            </div>
        </div>
    </body></html>
    <?php exit; 
}

$role = $_SESSION['role'];
$tab = $_GET['tab'] ?? 'orders';
if ($role === 'editor') $tab = $_GET['tab'] ?? 'content';

// Функция загрузки файлов
function uploadImage($file) {
    global $uploads_dir;
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('img_') . '.webp'; // Всегда сохраняем в webp
        $target_path = $uploads_dir . '/' . $filename;

        // Создаем изображение-ресурс в зависимости от исходного формата
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $src = imagecreatefromjpeg($file['tmp_name']);
        } elseif ($ext === 'png') {
            $src = imagecreatefrompng($file['tmp_name']);
            // Сохраняем прозрачность png при конвертации
            imagepalettetotruecolor($src);
            imagealphablending($src, false);
            imagesavealpha($src, true);
        } elseif ($ext === 'webp') {
            $src = imagecreatefromwebp($file['tmp_name']);
        } else {
            return ''; // Неподдерживаемый формат
        }

        if ($src) {
            // Сохраняем в WebP с качеством 80% (оптимально по PageSpeed)
            imagewebp($src, $target_path, 80);
            imagedestroy($src);
            return 'uploads/' . $filename;
        }
    }
    return '';
}


// Обработка POST запросов (Контент и роли)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $orders = json_decode(file_get_contents($orders_file), true);
        foreach ($orders as &$o) { if ($o['id'] === $_POST['order_id']) $o['status'] = $_POST['status']; }
        file_put_contents($orders_file, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header('Location: ?tab=orders'); exit;
    }
    if (isset($_POST['add_news'])) {
        $img = uploadImage($_FILES['image']);
        $news = json_decode(file_get_contents($news_file), true) ?: [];
        array_unshift($news, ['id' => uniqid(), 'title' => $_POST['title'], 'text' => $_POST['text'], 'date' => date('d.m.Y'), 'image' => $img]);
        file_put_contents($news_file, json_encode($news, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header('Location: ?tab=content'); exit;
    }
    if (isset($_POST['add_promo'])) {
        $img = uploadImage($_FILES['image']);
        $promos = json_decode(file_get_contents($promos_file), true) ?: [];
        array_unshift($promos, ['id' => uniqid(), 'title' => $_POST['title'], 'image' => $img]);
        file_put_contents($promos_file, json_encode($promos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header('Location: ?tab=content'); exit;
    }
    if (isset($_POST['add_gallery'])) {
        $img = uploadImage($_FILES['image']);
        if ($img) {
            $gallery = json_decode(file_get_contents($gallery_file), true) ?: [];
            array_unshift($gallery, ['id' => uniqid(), 'image' => $img]);
            file_put_contents($gallery_file, json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        header('Location: ?tab=content'); exit;
    }
    if (isset($_POST['update_role'])) {
        $u = $_POST['username'];
        if (isset($users[$u]) && $u !== $_SESSION['username']) {
            $users[$u]['role'] = $_POST['new_role'];
            file_put_contents($users_file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        header('Location: ?tab=users'); exit;
    }
}

// Обработка GET (Удаление)
if (isset($_GET['delete_order'])) {
    $orders = json_decode(file_get_contents($orders_file), true);
    $orders = array_values(array_filter($orders, fn($o) => $o['id'] !== $_GET['delete_order']));
    file_put_contents($orders_file, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: ?tab=orders'); exit;
}
if (isset($_GET['delete_news'])) {
    $news = json_decode(file_get_contents($news_file), true);
    $news = array_values(array_filter($news, fn($n) => $n['id'] !== $_GET['delete_news']));
    file_put_contents($news_file, json_encode($news, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: ?tab=content'); exit;
}
if (isset($_GET['delete_promo'])) {
    $promos = json_decode(file_get_contents($promos_file), true);
    $promos = array_values(array_filter($promos, fn($p) => $p['id'] !== $_GET['delete_promo']));
    file_put_contents($promos_file, json_encode($promos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: ?tab=content'); exit;
}
if (isset($_GET['delete_gallery'])) {
    $gallery = json_decode(file_get_contents($gallery_file), true);
    $gallery = array_values(array_filter($gallery, fn($g) => $g['id'] !== $_GET['delete_gallery']));
    file_put_contents($gallery_file, json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: ?tab=content'); exit;
}
if (isset($_GET['delete_user'])) {
    $u = $_GET['delete_user'];
    if (isset($users[$u]) && $u !== $_SESSION['username']) {
        unset($users[$u]);
        file_put_contents($users_file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    header('Location: ?tab=users'); exit;
}

$orders = json_decode(file_get_contents($orders_file), true) ?: [];
$news = json_decode(file_get_contents($news_file), true) ?: [];
$promos = json_decode(file_get_contents($promos_file), true) ?: [];
$gallery = json_decode(file_get_contents($gallery_file), true) ?: [];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Админка — Посейдон</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Обновленная палитра (в цветах нового лендинга) */
        :root { --deep: #1a365d; --blue: #2b6cb0; --aqua: #4299e1; --bg: #f4f7f9; --white: #fff; --gray: #64748b; --border: #e2e8f0; }
        body { font-family: 'Montserrat', sans-serif; background: var(--bg); margin: 0; color: var(--deep); display: flex; height: 100vh; overflow: hidden; }
        
        /* Боковое меню */
        .sidebar { width: 260px; background: var(--deep); color: #fff; display: flex; flex-direction: column; }
        .sidebar h2 { padding: 24px; margin: 0; font-size: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .user-info { padding: 20px 24px; background: rgba(255,255,255,0.05); font-size: 0.85rem; }
        .user-info strong { display: block; color: var(--aqua); font-size: 1rem; margin-bottom: 4px; }
        .nav-menu { flex: 1; padding: 20px 0; }
        .nav-menu a { display: block; padding: 15px 24px; color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 600; border-left: 3px solid transparent; }
        .nav-menu a:hover, .nav-menu a.active { background: rgba(255,255,255,0.1); color: #fff; border-left-color: var(--aqua); }
        .logout { padding: 24px; text-decoration: none; color: #ff4d4f; font-weight: 600; display: block; }
        
        /* Основной контент */
        .main { flex: 1; overflow-y: auto; padding: 30px; }
        .card { background: var(--white); border-radius: 16px; box-shadow: 0 4px 20px rgba(26, 54, 93, 0.05); padding: 24px; margin-bottom: 20px; }
        h1 { margin-top: 0; font-size: 1.5rem; margin-bottom: 20px; }
        h2 { font-size: 1.2rem; margin-top: 0; margin-bottom: 15px; color: var(--deep); }
        
        /* Уведомления */
        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; }
        .alert-error { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }

        /* Таблицы */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; font-size: 0.9rem; vertical-align: middle; }
        th { color: var(--gray); font-size: 0.8rem; text-transform: uppercase; }
        
        /* Формы */
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; box-sizing: border-box; margin-bottom: 10px; outline: none; transition: 0.2s; }
        input:focus, textarea:focus, select:focus { border-color: var(--aqua); }
        button { padding: 10px 20px; background: var(--blue); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        button:hover { background: var(--deep); }
        
        .btn-delete { background: #fee2e2; color: #ef4444; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 600; display: inline-block; transition: 0.2s; }
        .btn-delete:hover { background: #fecaca; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .list-item { border-bottom: 1px solid var(--border); padding: 12px 0; display: flex; justify-content: space-between; align-items: center; }
        
        /* Бейджи ролей */
        .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #ef4444; }
        .role-editor { background: #e0f2fe; color: #0284c7; }
        .role-operator { background: #dcfce7; color: #15803d; }
        .role-employee { background: #f4f7f9; color: #64748b; }
        .img-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
        
        /* Стили для формы добавления юзера */
        .add-user-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .add-user-form .form-group { flex: 1; min-width: 150px; }
        .add-user-form label { display: block; font-size: 0.8rem; color: var(--gray); margin-bottom: 5px; }
        .add-user-form input, .add-user-form select { margin-bottom: 0; }
        .add-user-form button { height: 42px; white-space: nowrap; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Посейдон · CRM</h2>
    <div class="user-info">
        Вы вошли как: <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
        <span class="role-badge role-<?= $role ?>"><?= $role ?></span>
    </div>
    <div class="nav-menu">
        <?php if (in_array($role, ['admin', 'operator'])): ?>
            <a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>">📋 Заявки</a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'editor'])): ?>
            <a href="?tab=content" class="<?= $tab === 'content' ? 'active' : '' ?>">📰 Контент</a>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
            <a href="?tab=users" class="<?= $tab === 'users' ? 'active' : '' ?>">👥 Пользователи</a>
        <?php endif; ?>
    </div>
    <a href="?logout=1" class="logout">Выйти</a>
</div>

<div class="main">
    <!-- ЗАЯВКИ -->
    <?php if ($tab === 'orders' && in_array($role, ['admin', 'operator'])): ?>
        <h1>Управление заявками</h1>
        <div class="card">
            <table>
                <thead><tr><th>Дата</th><th>Клиент</th><th>Телефон</th><th>Адрес</th><th>Статус</th><th>Действия</th></tr></thead>
                <tbody>
                    <?php if (empty($orders)): ?><tr><td colspan="6" style="text-align:center; color:var(--gray);">Заявок нет.</td></tr><?php endif; ?>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><span style="color:var(--gray); font-size:0.85rem;"><?= htmlspecialchars($o['date']) ?></span></td>
                        <td><strong><?= htmlspecialchars($o['name']) ?></strong></td>
                        <td><a href="tel:<?= htmlspecialchars($o['phone']) ?>"><?= htmlspecialchars($o['phone']) ?></a></td>
                        <td><?= htmlspecialchars($o['address']) ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px; margin:0;">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" style="margin:0; padding:5px; width:auto;">
                                    <option value="new" <?= $o['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                                    <option value="processing" <?= $o['status'] == 'processing' ? 'selected' : '' ?>>В работе</option>
                                    <option value="done" <?= $o['status'] == 'done' ? 'selected' : '' ?>>Доставлен</option>
                                </select>
                                <button type="submit" name="update_status" style="padding:5px 10px;">ОК</button>
                            </form>
                        </td>
                        <td><a href="?tab=orders&delete_order=<?= $o['id'] ?>" class="btn-delete" onclick="return confirm('Удалить?')">Удалить</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- КОНТЕНТ -->
    <?php if ($tab === 'content' && in_array($role, ['admin', 'editor'])): ?>
        <h1>Управление контентом</h1>
        
        <div class="grid-2">
            <div class="card">
                <h2>Добавить новость</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="text" name="title" placeholder="Заголовок" required>
                    <textarea name="text" placeholder="Текст новости" rows="3" required></textarea>
                    <input type="file" name="image" accept="image/*" required>
                    <button type="submit" name="add_news">Опубликовать</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Добавить акцию (Баннер)</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="text" name="title" placeholder="Название акции (текст поверх фото)" required>
                    <input type="file" name="image" accept="image/*" required>
                    <button type="submit" name="add_promo">Добавить акцию</button>
                </form>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="card">
                <h2>Добавить фото в галерею</h2>
                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center;">
                    <input type="file" name="image" accept="image/*" required style="flex:1; margin:0;">
                    <button type="submit" name="add_gallery">Загрузить</button>
                </form>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h2>Список новостей</h2>
                <?php foreach ($news as $n): ?>
                    <div class="list-item">
                        <div><strong><?= htmlspecialchars($n['title']) ?></strong><br><small><?= htmlspecialchars($n['date']) ?></small></div>
                        <a href="?tab=content&delete_news=<?= $n['id'] ?>" class="btn-delete">Удалить</a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="card">
                <h2>Список акций</h2>
                <?php foreach ($promos as $p): ?>
                    <div class="list-item">
                        <div><strong><?= htmlspecialchars($p['title']) ?></strong></div>
                        <a href="?tab=content&delete_promo=<?= $p['id'] ?>" class="btn-delete">Удалить</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>Галерея</h2>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <?php foreach ($gallery as $g): ?>
                    <div style="border:1px solid #ddd; padding:10px; border-radius:8px; text-align:center;">
                        <img src="../<?= htmlspecialchars($g['image']) ?>" class="img-preview" style="width:100px; height:100px; margin-bottom:10px;">
                        <br><a href="?tab=content&delete_gallery=<?= $g['id'] ?>" class="btn-delete">Удалить</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ПОЛЬЗОВАТЕЛИ -->
    <?php if ($tab === 'users' && $role === 'admin'): ?>
        <h1>Пользователи</h1>

        <!-- Уведомления об успехе/ошибке -->
        <?php if (!empty($add_user_error)): ?>
            <div class="alert alert-error"><?= $add_user_error ?></div>
        <?php endif; ?>
        <?php if (!empty($add_user_success)): ?>
            <div class="alert alert-success"><?= $add_user_success ?></div>
        <?php endif; ?>

        <!-- ФОРМА ДОБАВЛЕНИЯ СОТРУДНИКА -->
        <div class="card" style="border-left: 4px solid var(--aqua);">
            <h2>Добавить нового сотрудника</h2>
            <form method="POST" class="add-user-form">
                <div class="form-group">
                    <label>Имя сотрудника</label>
                    <input type="text" name="new_user_name" placeholder="Иван Иванов" required>
                </div>
                <div class="form-group">
                    <label>Логин для входа</label>
                    <input type="text" name="new_user_login" placeholder="ivan" required>
                </div>
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="text" name="new_user_pass" placeholder="******" required>
                </div>
                <div class="form-group">
                    <label>Роль</label>
                    <select name="new_user_role">
                        <option value="employee">Сотрудник</option>
                        <option value="operator">Оператор</option>
                        <option value="editor">Редактор</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
                <button type="submit" name="add_user_btn">Создать</button>
            </form>
        </div>

        <!-- ТАБЛИЦА ПОЛЬЗОВАТЕЛЕЙ -->
        <div class="card">
            <h2>Список сотрудников</h2>
            <table>
                <thead><tr><th>Имя</th><th>Логин</th><th>Роль</th><th>Действия</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $login => $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                        <td><?= htmlspecialchars($login) ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px; margin:0;">
                                <input type="hidden" name="username" value="<?= htmlspecialchars($login) ?>">
                                <select name="new_role" style="margin:0; padding:5px; width:auto;" <?= $login === $_SESSION['username'] ? 'disabled' : '' ?>>
                                    <option value="employee" <?= $u['role'] === 'employee' ? 'selected' : '' ?>>Сотрудник</option>
                                    <option value="operator" <?= $u['role'] === 'operator' ? 'selected' : '' ?>>Оператор</option>
                                    <option value="editor" <?= $u['role'] === 'editor' ? 'selected' : '' ?>>Редактор</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                </select>
                                <?php if ($login !== $_SESSION['username']): ?>
                                    <button type="submit" name="update_role" style="padding:5px 10px;">ОК</button>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td>
                            <?php if ($login !== $_SESSION['username']): ?>
                                <a href="?tab=users&delete_user=<?= htmlspecialchars($login) ?>" class="btn-delete" onclick="return confirm('Удалить?')">Удалить</a>
                            <?php else: ?>
                                <span style="color:var(--gray); font-size:0.8rem;">(Вы)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>