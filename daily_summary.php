<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$month = date('Y-m', strtotime($date));
$dataFile = "data/{$month}.json";
if (!file_exists($dataFile)) {
    $error = "Data file for the selected month not found.";
} else {
    $dataJson = file_get_contents($dataFile);
    if ($dataJson === false) {
        $error = "Unable to read data file. Please check file permissions.";
    } else {
        $monthlyData = json_decode($dataJson, true);
        if ($monthlyData === null) {
            $error = "Invalid JSON in data file. Please check the file contents.";
        } else {
            $day = date('d', strtotime($date));
            $dailyData = isset($monthlyData[$day]) ? $monthlyData[$day] : ['morning' => [], 'night' => [], 'guests' => []];
            $morningMeals = isset($dailyData['morning']) ? $dailyData['morning'] : [];
            $nightMeals = isset($dailyData['night']) ? $dailyData['night'] : [];
            $guestMeals = isset($dailyData['guests']) ? $dailyData['guests'] : [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Meal Summary - <?php echo htmlspecialchars($date); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        
        .card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .summary-table th {
            background-color: #f8fafc;
            font-weight: 600;
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .summary-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .summary-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-active {
            color: #059669;
            background-color: #ecfdf5;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-cancelled {
            color: #dc2626;
            background-color: #fef2f2;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            
            .summary-table th,
            .summary-table td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="min-h-screen p-4 md:p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Daily Meal Summary</h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($date); ?></p>
                </div>
                <a href="dashboard.php" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                    ← Back to Dashboard
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-lg">
                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Morning Meals Card -->
                    <div class="card p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Morning Meals</h2>
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($morningMeals as $user): ?>
                                    <tr>
                                        <td class="text-gray-700"><?php echo htmlspecialchars($user); ?></td>
                                        <td>
                                            <span class="<?php echo strpos($user, '_cancelled') !== false ? 'status-cancelled' : 'status-active'; ?>">
                                                <?php echo strpos($user, '_cancelled') !== false ? 'Cancelled' : 'Active'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($morningMeals)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-gray-500 py-4">No morning meals scheduled</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Night Meals Card -->
                    <div class="card p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Night Meals</h2>
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nightMeals as $user): ?>
                                    <tr>
                                        <td class="text-gray-700"><?php echo htmlspecialchars($user); ?></td>
                                        <td>
                                            <span class="<?php echo strpos($user, '_cancelled') !== false ? 'status-cancelled' : 'status-active'; ?>">
                                                <?php echo strpos($user, '_cancelled') !== false ? 'Cancelled' : 'Active'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($nightMeals)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-gray-500 py-4">No night meals scheduled</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Guest Meals Card -->
                    <div class="card p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Guest Meals</h2>
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Host</th>
                                    <th>Meal</th>
                                    <th>Guests</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guestMeals as $host => $meals): ?>
                                    <?php foreach ($meals as $meal => $count): ?>
                                        <tr>
                                            <td class="text-gray-700"><?php echo htmlspecialchars($host); ?></td>
                                            <td class="text-gray-700"><?php echo htmlspecialchars($meal); ?></td>
                                            <td class="text-gray-700"><?php echo htmlspecialchars($count); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                <?php if (empty($guestMeals)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-gray-500 py-4">No guest meals scheduled</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>