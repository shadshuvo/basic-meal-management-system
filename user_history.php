<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

date_default_timezone_set('Asia/Dhaka');

// Load users data
$usersFile = __DIR__ . '/users.json';
if (!file_exists($usersFile)) {
    die("Users file not found");
}
$users = json_decode(file_get_contents($usersFile), true);

// Get available months from data directory
function getAvailableMonths() {
    $files = glob(__DIR__ . '/data/*.json');
    return array_map(function($file) {
        return basename($file, '.json');
    }, $files);
}

$availableMonths = getAvailableMonths();
rsort($availableMonths);

// Handle form submission
$selectedUser = $_GET['user'] ?? '';
$selectedMonth = $_GET['month'] ?? date('Y-m');

// Get user's meal data for selected month
$userData = [];
if ($selectedUser && $selectedMonth) {
    $dataFile = __DIR__ . "/data/{$selectedMonth}.json";
    if (file_exists($dataFile)) {
        $monthData = json_decode(file_get_contents($dataFile), true);
        foreach ($monthData as $day => $dayData) {
            $userData[$day] = [
                'morning' => in_array($selectedUser, $dayData['morning']) ? 'active' : 
                           (in_array($selectedUser . '_cancelled', $dayData['morning']) ? 'cancelled' : 'inactive'),
                'night' => in_array($selectedUser, $dayData['night']) ? 'active' : 
                         (in_array($selectedUser . '_cancelled', $dayData['night']) ? 'cancelled' : 'inactive'),
                'guests' => isset($dayData['guests'][$selectedUser]) ? $dayData['guests'][$selectedUser] : []
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Meal History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Status badges */
        .meal-status {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .status-inactive {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        
        /* Card styles */
        .day-card {
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        
        /* Card background colors based on status */
        .day-card.has-cancelled {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
        }
        
        .day-card.all-active {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
        }
        
        /* Hover effects */
        .day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        /* Responsive grid */
        @media (min-width: 640px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 768px) {
            .grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">User Meal History</h1>
            <a href="dashboard.php" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                ← Back to Dashboard
            </a>
        </div>

        <!-- Selection Form -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <form method="GET" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                <div class="w-full sm:w-1/3">
                    <label for="user" class="block text-sm font-medium text-gray-700 mb-1">Select User</label>
                    <select name="user" id="user" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Choose a user...</option>
                        <?php foreach ($users as $username => $userInfo): ?>
                            <option value="<?php echo htmlspecialchars($username); ?>" 
                                    <?php echo $selectedUser === $username ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($username); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full sm:w-1/3">
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Select Month</label>
                    <select name="month" id="month" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <?php foreach ($availableMonths as $month): ?>
                            <option value="<?php echo htmlspecialchars($month); ?>"
                                    <?php echo $selectedMonth === $month ? 'selected' : ''; ?>>
                                <?php echo date('F Y', strtotime($month . '-01')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:mt-6">
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        View History
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Display -->
        <?php if ($selectedUser && !empty($userData)): ?>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Meal History for <?php echo htmlspecialchars($selectedUser); ?> - 
                        <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>
                    </h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-6">
                    <?php foreach ($userData as $day => $dayData): 
                        $hasCancelled = $dayData['morning'] === 'cancelled' || $dayData['night'] === 'cancelled';
                        $allActive = $dayData['morning'] === 'active' && $dayData['night'] === 'active';
                        $cardClass = $hasCancelled ? 'has-cancelled' : ($allActive ? 'all-active' : '');
                    ?>
                        <div class="day-card <?php echo $cardClass; ?>">
                            <div class="font-medium text-gray-900 mb-3">
                                <?php echo date('j F', strtotime($selectedMonth . '-' . $day)); ?>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Morning:</span>
                                    <span class="meal-status status-<?php echo $dayData['morning']; ?>">
                                        <?php echo ucfirst($dayData['morning']); ?>
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Night:</span>
                                    <span class="meal-status status-<?php echo $dayData['night']; ?>">
                                        <?php echo ucfirst($dayData['night']); ?>
                                    </span>
                                </div>

                                <?php if (!empty($dayData['guests'])): ?>
                                    <div class="mt-2 pt-2 border-t border-gray-200">
                                        <span class="text-sm font-medium text-gray-900">Guests:</span>
                                        <div class="text-sm text-gray-600">
                                            <?php foreach ($dayData['guests'] as $meal => $count): ?>
                                                <div><?php echo ucfirst($meal) . ': ' . $count; ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($selectedUser): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <p class="text-gray-500 text-center">No meal data available for this month.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>