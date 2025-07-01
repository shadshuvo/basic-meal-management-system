<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

date_default_timezone_set('Asia/Dhaka');
$currentMonth = date('Y-m');
$marketDataFile = __DIR__ . "/market_data/{$currentMonth}.json";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['details'])) {
    $details = $_POST['details'];
    $totalAmount = $_POST['total_amount'];

    $marketData = [];
    if (file_exists($marketDataFile)) {
        $marketData = json_decode(file_get_contents($marketDataFile), true);
    }

    $marketData[] = [
        'user' => $_SESSION['user'],
        'details' => $details,
        'total_amount' => $totalAmount,
        'date' => date('Y-m-d H:i:s')
    ];

    file_put_contents($marketDataFile, json_encode($marketData));
    $_SESSION['success_message'] = "Market data saved successfully!";
    header('Location: market.php');
    exit;
}

$marketData = [];
if (file_exists($marketDataFile)) {
    $marketData = json_decode(file_get_contents($marketDataFile), true);
}

$availableMonths = [];
$marketDataDir = __DIR__ . "/market_data";
if (is_dir($marketDataDir)) {
    $files = scandir($marketDataDir);
    foreach ($files as $file) {
        if (preg_match('/^\d{4}-\d{2}\.json$/', $file)) {
            $availableMonths[] = basename($file, '.json');
        }
    }
    rsort($availableMonths);
}

$selectedMonthData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_month'])) {
    $selectedMonth = $_POST['selected_month'];
    $selectedMonthFile = __DIR__ . "/market_data/{$selectedMonth}.json";
    if (file_exists($selectedMonthFile)) {
        $selectedMonthData = json_decode(file_get_contents($selectedMonthFile), true);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Market Entry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-4 md:p-6">
        <div class="max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Monthly Bazar Entry</h1>
                    <a href="dashboard.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        ← Back to Dashboard
                    </a>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Entry Form -->
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label for="details" class="block mb-2 text-sm font-medium text-gray-700">Bazar Details:</label>
                        <textarea id="details" name="details" required
                            class="block w-full p-4 text-sm text-gray-900 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            rows="4"></textarea>
                    </div>
                    <div>
                        <label for="total_amount" class="block mb-2 text-sm font-medium text-gray-700">Total Amount:</label>
                        <input type="number" id="total_amount" name="total_amount" required
                            class="block w-full p-4 text-sm text-gray-900 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                    <button type="submit" 
                        class="w-full sm:w-auto px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors">
                        Save Bazar Data
                    </button>
                </form>
            </div>

            <!-- Current Month Data -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Current Month's Bazar Data</h2>
                <?php if (!empty($marketData)): ?>
                    <div class="space-y-4">
                        <?php foreach ($marketData as $entry): ?>
                            <div class="p-4 rounded-lg bg-blue-50 border border-blue-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-blue-800">
                                        <?php echo date('j F', strtotime($entry['date'])); ?>
                                    </span>
                                    <span class="text-sm font-medium text-gray-600">
                                        <?php echo htmlspecialchars($entry['user']); ?>
                                    </span>
                                </div>
                                <p class="text-gray-700 mb-2"><?php echo nl2br(htmlspecialchars($entry['details'])); ?></p>
                                <div class="text-sm font-medium text-gray-800">
                                    Total Amount: <?php echo htmlspecialchars($entry['total_amount']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No Bazar data available for the current month.</p>
                <?php endif; ?>
            </div>

            <!-- Monthly Data Selection -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <form method="POST" action="" class="mb-6">
                    <label for="selected_month" class="block mb-2 text-sm font-medium text-gray-700">Select Month to View:</label>
                    <select id="selected_month" name="selected_month" onchange="this.form.submit()"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <option value="">--Select Month--</option>
                        <?php foreach ($availableMonths as $month): ?>
                            <option value="<?php echo htmlspecialchars($month); ?>" 
                                <?php echo isset($_POST['selected_month']) && $_POST['selected_month'] === $month ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(date('F Y', strtotime($month))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if (!empty($selectedMonthData)): ?>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">
                        <?php echo htmlspecialchars(date('F Y', strtotime($_POST['selected_month']))); ?> Bazar Data
                    </h2>
                    <div class="space-y-4">
                        <?php foreach ($selectedMonthData as $entry): ?>
                            <div class="p-4 rounded-lg bg-gray-50 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-800">
                                        <?php echo htmlspecialchars($entry['user']); ?>
                                    </span>
                                    <span class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($entry['date']); ?>
                                    </span>
                                </div>
                                <p class="text-gray-700 mb-2"><?php echo nl2br(htmlspecialchars($entry['details'])); ?></p>
                                <div class="text-sm font-medium text-gray-800">
                                    Total Amount: <?php echo htmlspecialchars($entry['total_amount']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
</body>
</html>