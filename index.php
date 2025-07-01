<?php
session_start();

$usersFile = 'users.json';
if (!file_exists($usersFile)) {
    die("Error: users.json file not found. Please create the file with user data.");
}

$usersJson = file_get_contents($usersFile);
if ($usersJson === false) {
    die("Error: Unable to read users.json file. Please check file permissions.");
}

$users = json_decode($usersJson, true);
if ($users === null) {
    die("Error: Invalid JSON in users.json file. Please check the file contents.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['user'] = $username;
        $_SESSION['is_admin'] = $users[$username]['is_admin'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Replace static image array with dynamic loading
function getBackgroundImages() {
    $imageDir = __DIR__ . '/img/';
    $images = glob($imageDir . '*.webp');
    
    // Convert absolute paths to relative URLs
    return array_map(function($path) {
        return 'img/' . basename($path);
    }, $images);
}

// Get random image
$images = getBackgroundImages();
if (empty($images)) {
    // Fallback image if no images found
    $randomImage = 'img/default.webp';
} else {
    $randomImage = $images[array_rand($images)];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Manager - Login</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: url('<?php echo $randomImage; ?>') no-repeat center center;
            background-size: cover;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            background: rgba(255, 255, 255, 0.05); /* Almost fully transparent */
            padding: 2.5rem;
            max-width: 400px;
            width: 90%;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1.8rem;
            color: #000000;
            font-weight: 900;
            text-shadow: 
                -1px -1px 0 #fff,
                 1px -1px 0 #fff,
                -1px  1px 0 #fff,
                 1px  1px 0 #fff;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            font-size: 1.1rem;
            color: #000000;
            font-weight: 700;
            text-shadow: 
                -1px -1px 0 #fff,
                 1px -1px 0 #fff,
                -1px  1px 0 #fff,
                 1px  1px 0 #fff;
        }

        input::placeholder {
            color: #000000;
            font-weight: 600;
            text-shadow: 0 0 2px #fff;
        }

        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
        }

        button {
            width: 100%;
            padding: 0.8rem;
            background: rgba(255, 255, 255, 0.3); /* More opaque background */
            backdrop-filter: blur(10px); /* Add blur effect */
            -webkit-backdrop-filter: blur(10px); /* For Safari support */
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 8px;
            color: #000000;
            font-size: 1.2rem;
            font-weight: 800;
            text-shadow: 
                -1px -1px 0 #fff,
                 1px -1px 0 #fff,
                -1px  1px 0 #fff,
                 1px  1px 0 #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        button:active {
            transform: translateY(0);
        }

        .error {
            margin-top: 1rem;
            padding: 0.8rem;
            background: rgba(255, 0, 0, 0.1);
            border-radius: 8px;
            color: #000000;
            font-size: 0.9rem;
            font-weight: 700;
            border: 2px solid rgba(255, 0, 0, 0.3);
            text-shadow: 
                -1px -1px 0 #fff,
                 1px -1px 0 #fff,
                -1px  1px 0 #fff,
                 1px  1px 0 #fff;
        }

        @media (max-width: 400px) {
            h1 {
                font-size: 1.5rem;
            }

            .container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Meal Manager</h1>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
