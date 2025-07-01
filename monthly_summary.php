<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

function getAvailableMonths($dataDir) {
    $files = glob($dataDir . '/*.json');
    $months = array_map(function($file) {
        return basename($file, '.json');
    }, $files);
    sort($months);
    return $months;
}

$dataDir = __DIR__ . '/data';
$availableMonths = getAvailableMonths($dataDir);
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Check if today is the first day of the month and the time is before 1:00 AM
$today = new DateTime();
$firstDayOfMonth = new DateTime('first day of this month');
if ($today->format('Y-m-d') === $firstDayOfMonth->format('Y-m-d') && $today->format('H:i') < '01:00') {
    $currentMonth = date('Y-m', strtotime('-1 month'));
}

$dataFile = "{$dataDir}/{$currentMonth}.json";
$usersFile = __DIR__ . '/users.json';

// If the data file for the current month does not exist, fall back to the previous month
if (!file_exists($dataFile)) {
    $currentMonth = date('Y-m', strtotime('-1 month'));
    $dataFile = "{$dataDir}/{$currentMonth}.json";
}

if (!file_exists($dataFile)) {
    die("Error: Monthly data file not found. Please ensure the current month's data has been initialized.");
}
if (!file_exists($usersFile)) {
    die("Error: users.json file not found. Please check the file location and permissions.");
}

$monthlyData = json_decode(file_get_contents($dataFile), true);
if ($monthlyData === null) {
    die("Error: Invalid JSON in monthly data file. Please check the file contents.");
}

$users = json_decode(file_get_contents($usersFile), true);
if ($users === null) {
    die("Error: Invalid JSON in users.json file. Please check the file contents.");
}

$summary = [];
foreach ($users as $username => $userInfo) {
    $summary[$username] = [
        'meals' => 0,
        'guest_meals' => 0
    ];
}

foreach ($monthlyData as $day => $meals) {
    foreach ($meals as $mealType => $participants) {
        if ($mealType === 'guests') {
            foreach ($participants as $host => $guestMeals) {
                foreach ($guestMeals as $meal => $count) {
                    $summary[$host]['guest_meals'] += $count;
                }
            }
        } else {
            foreach ($participants as $participant) {
                // Check if the participant is not canceled
                if (strpos($participant, '_cancel') === false) {
                    $summary[$participant]['meals'] += 1;
                }
            }
        }
    }
}

function formatMonth($month) {
    return date('F Y', strtotime($month . '-01'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Summary</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .custom-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        .stat-card {
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-8">
            <a href="dashboard.php" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition-all duration-200 ease-in-out transform hover:-translate-y-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-xl p-8 mb-8 transition-all duration-300">
            <h1 class="text-4xl font-bold text-gray-900 mb-8">Monthly Summary</h1>
            
            <!-- Month Selector -->
            <form method="GET" action="monthly_summary.php" class="mb-10">
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                    <label for="month" class="text-gray-700 font-semibold text-lg">Select Month:</label>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <select name="month" id="month" 
                                class="custom-select block w-full sm:w-64 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5 pl-4 text-base transition-colors duration-200">
                            <?php foreach ($availableMonths as $month): ?>
                                <option value="<?php echo htmlspecialchars($month); ?>" <?php echo $month === $currentMonth ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(formatMonth($month)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition-all duration-200 ease-in-out transform hover:-translate-y-0.5">
                            View Summary
                        </button>
                    </div>
                </div>
            </form>

            <!-- Summary Header -->
            <h2 class="text-2xl font-bold text-gray-900 mb-6 pb-2 border-b border-gray-200">
                Summary for <?php echo htmlspecialchars(formatMonth($currentMonth)); ?>
            </h2>

            <!-- Summary Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Total Meals
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Guest Meals
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($summary as $username => $data): ?>
                            <tr class="hover:bg-gray-50 transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                            <span class="text-indigo-600 font-medium">
                                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($username); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo htmlspecialchars($data['meals']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($data['guest_meals']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>