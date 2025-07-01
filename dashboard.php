<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

date_default_timezone_set('Asia/Dhaka');

$currentMonth = date('Y-m');
$dataFile = __DIR__ . "/data/{$currentMonth}.json";
$usersFile = __DIR__ . '/users.json';

// Error handling function
function handleError($message) {
    die(json_encode(['error' => $message]));
}

// Load users data
if (!file_exists($usersFile)) handleError("Users file not found");
$users = json_decode(file_get_contents($usersFile), true) ?? handleError("Invalid users data");

// Load or create monthly data
if (!file_exists($dataFile)) {
    $data = array_fill_keys(
        array_map(fn($i) => sprintf('%02d', $i), range(1, date('t'))),
        ['morning' => array_keys($users), 'night' => array_keys($users), 'guests' => []]
    );
    file_put_contents($dataFile, json_encode($data)) === false && handleError("Unable to create data file");
}

$data = json_decode(file_get_contents($dataFile), true) ?? handleError("Invalid monthly data");

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = handlePostRequest($_POST, $data, $currentMonth, $dataFile);
    echo json_encode($response);
    exit;
}

function handlePostRequest($post, &$data, $currentMonth, $dataFile) {
    $date = $post['date'];
    $meal = $post['meal'] ?? null;
    $action = $post['action'];
    $guestCount = (int)($post['guest_count'] ?? 0);

    $currentTime = new DateTime();
    $deadlines = [
        'morning' => new DateTime("$currentMonth-$date 10:00:00"),
        'night' => new DateTime("$currentMonth-$date 18:00:00")
    ];

    if ($action !== 'undo_cancel' && $currentTime > $deadlines[$meal]) {
        return ['success' => false, 'message' => 'Deadline passed'];
    }

    if (strtotime("$currentMonth-$date") < strtotime(date('Y-m-d'))) {
        return ['success' => false, 'message' => 'Cannot modify past dates'];
    }

    switch ($action) {
        case 'cancel':
            $index = array_search($_SESSION['user'], $data[$date][$meal]);
            if ($index !== false) {
                $data[$date][$meal][$index] = $_SESSION['user'] . '_cancelled';
            }
            break;
        case 'undo_cancel':
            $morningIndex = array_search($_SESSION['user'] . '_cancelled', $data[$date]['morning']);
            if ($morningIndex !== false) {
                $data[$date]['morning'][$morningIndex] = $_SESSION['user'];
            }
            $nightIndex = array_search($_SESSION['user'] . '_cancelled', $data[$date]['night']);
            if ($nightIndex !== false) {
                $data[$date]['night'][$nightIndex] = $_SESSION['user'];
            }
            break;
        case 'add_guest':
            $data[$date]['guests'][$_SESSION['user']][$meal] = $guestCount;
            break;
    }

    file_put_contents($dataFile, json_encode($data));
    return ['success' => true];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Manager - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 15px;
}

.header-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.header-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 15px;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-links .logout {
    background-color: #ef4444;
}

.header-links .logout:hover {
    background-color: #dc2626;
}

.header-links .admin {
    background-color: #6366f1;
}

.header-links .admin:hover {
    background-color: #4f46e5;
}

.header-links .summary {
    background-color: #10b981;
}

.header-links .summary:hover {
    background-color: #059669;
}

.header-links .daily-summary {
    background-color: #0ea5e9;
}

.header-links .daily-summary:hover {
    background-color: #0284c7;
}

/* Meal Buttons from Here */

.guest-control {
    display: none;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    background: #f0f4f8; /* Light gray-blue background */
    padding: 8px;
    border-radius: 6px;
    color: #000000; /* Black text for clarity */
}

.guest-control.active {
    display: flex;
}

.guest-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-guest {
    padding: 4px 8px;
    border: 1px solid #d1e7ff;
    background: #eff6ff; /* Soft blue for guest buttons */
    border-radius: 4px;
    cursor: pointer;
    color: #000000; /* Black text */
}

.btn-save-guest {
    padding: 4px 12px;
    background: #34d399; /* Soft green for Save button */
    color: #000000; /* Black text */
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-save-guest:hover {
    background: #059669; /* Darker green on hover */
    color: #ffffff; /* White text on hover for contrast */
}

.btn-cancel-guest {
    padding: 4px 12px;
    background: #f87171; /* Bright red for Cancel button */
    color: #000000; /* Black text */
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-cancel-guest:hover {
    background: #e11d48; /* Darker red on hover */
    color: #ffffff; /* White text on hover for contrast */
}

.guest-count-display {
    min-width: 20px;
    text-align: center;
    color: #000000; /* Black text */
}

#calendar {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.day {
    border: 1px solid #cbd5e1;
    padding: 15px;
    border-radius: 8px;
    background: #ffffff;
    color: #000000; /* Black text */
}

.day.past {
    opacity: 0.5;
}

.day.cancelled {
    background: #fef2f2; /* Light red for cancelled days */
    color: #000000; /* Black text */
}

.meal {
    padding: 10px;
    margin: 5px 0;
    border-radius: 4px;
    cursor: pointer;
    background: #f0fdfa; /* Mint green for meal cards */
    color: #000000; /* Black text */
}

.meal.active {
    background: #d1fae5; /* Soft green for active meal cards */
    color: #000000; /* Black text */
}

.meal-buttons {
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.meal-buttons button {
    padding: 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    background: #93c5fd; /* Soft blue for meal action buttons */
    color: #000000; /* Black text */
    transition: background-color 0.2s;
}

.meal-buttons button:hover {
    background: #3b82f6; /* Bright blue on hover */
    color: #ffffff; /* White text on hover for contrast */
}

.guest-indicator {
    float: right;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.9em;
    color: #000000; /* Black text */
}

/* Modern Header Styles */
.welcome-header {
    background: linear-gradient(135deg, #f6f8ff 0%, #f1f4ff 100%);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.clock-widget {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    font-size: 0.65rem;
    color: #6b7280;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.4rem 0.6rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    backdrop-filter: blur(4px);
    line-height: 1;
    z-index: 10;
}

.main-title {
    font-size: 3.5rem;
    font-weight: 900;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0 auto 0.5rem;
    animation: glow 2s ease-in-out infinite alternate;
    transition: all 0.3s ease;
    max-width: max-content; /* Ensure proper centering */
}

.welcome-text {
    font-size: 1.5rem;
    color: #4b5563;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

/* Neon Effects */
.neon-blue {
    color: #2563eb;
    text-shadow: 
        0 0 7px rgba(37, 99, 235, 0.3),
        0 0 10px rgba(37, 99, 235, 0.3),
        0 0 21px rgba(37, 99, 235, 0.3),
        0 0 42px rgba(37, 99, 235, 0.3);
    animation: neonBlueGlow 1.5s ease-in-out infinite alternate;
}

.neon-purple {
    color: #7c3aed;
    text-shadow: 
        0 0 7px rgba(124, 58, 237, 0.3),
        0 0 10px rgba(124, 58, 237, 0.3),
        0 0 21px rgba(124, 58, 237, 0.3);
    animation: neonPurpleGlow 1.5s ease-in-out infinite alternate;
}

@keyframes neonBlueGlow {
    from {
        text-shadow: 
            0 0 7px rgba(37, 99, 235, 0.3),
            0 0 10px rgba(37, 99, 235, 0.3),
            0 0 21px rgba(37, 99, 235, 0.3),
            0 0 42px rgba(37, 99, 235, 0.3);
    }
    to {
        text-shadow: 
            0 0 10px rgba(37, 99, 235, 0.5),
            0 0 21px rgba(37, 99, 235, 0.5),
            0 0 42px rgba(37, 99, 235, 0.5),
            0 0 82px rgba(37, 99, 235, 0.5);
    }
}

@keyframes neonPurpleGlow {
    from {
        text-shadow: 
            0 0 7px rgba(124, 58, 237, 0.3),
            0 0 10px rgba(124, 58, 237, 0.3),
            0 0 21px rgba(124, 58, 237, 0.3);
    }
    to {
        text-shadow: 
            0 0 10px rgba(124, 58, 237, 0.5),
            0 0 21px rgba(124, 58, 237, 0.5),
            0 0 42px rgba(124, 58, 237, 0.5);
    }
}

/* Responsive */
@media (max-width: 640px) {
    .main-title {
        font-size: 2rem;
    }
    .welcome-text {
        font-size: 1.125rem;
    }
    .clock-widget {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
    }
    .notice-text {
        animation: scrollText 15s linear infinite; /* Faster on mobile */
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        transform: translateY(-10px);
        opacity: 0;
    }
    to { 
        transform: translateY(0);
        opacity: 1;
    }
}

/* Add these styles to the existing <style> section */
.welcome-section {
    position: relative;
    z-index: 1;
    text-align: center; /* Center align the content */
}

/* Update the notice-board styles */
.notice-board {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(254, 226, 226, 0.5);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 0.75rem;
    position: relative;
    overflow: hidden;
    white-space: nowrap; /* Keep text in single line */
}

.notice-text {
    color: #dc2626;
    font-weight: 600;
    display: inline-block; /* Required for animation */
    animation: scrollText 20s linear infinite;
    padding-left: 100%; /* Start from right side */
}

/* Add new scrolling animation */
@keyframes scrollText {
    from {
        transform: translateX(0%);
    }
    to {
        transform: translateX(-100%);
    }
}

    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-header">
    <div class="header-content">
        <div class="welcome-section">
            <h1 class="main-title neon-blue">Meal Manager v2.0</h1>
            <p class="welcome-text neon-purple">Welcome back, 
                <span class="username"><?php echo htmlspecialchars($_SESSION['user']); ?></span>
            </p>
            
            <div class="notice-board">
                <p class="notice-text">
                    <?php 
                    $notice = file_exists('notice.txt') ? file_get_contents('notice.txt') : 'No current notices';
                    echo htmlspecialchars($notice);
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>
        <div class="header-links">
            <a href="logout.php" class="logout">Logout</a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="admin.php" class="admin">Admin Panel</a>
            <?php endif; ?>
            <a href="monthly_summary.php" class="summary">Monthly Summary</a>
            <a href="daily_summary.php?date=<?php echo date('Y-m-d'); ?>" class="daily-summary">View Today's Summary</a>
            <a href="market.php" class="summary">Add Bazar</a>
            <a href="user_history.php" class="summary">Meal History</a>
        </div>

        <div id="calendar">
            <?php
            $daysInMonth = date('t');
            $today = date('d');
            for ($day = 1; $day <= $daysInMonth; $day++):
                $date = sprintf('%02d', $day);
                $isPast = $day < $today;
                $dayData = $data[$date];
                $isCancelled = in_array($_SESSION['user'] . '_cancelled', $dayData['morning']) || 
                              in_array($_SESSION['user'] . '_cancelled', $dayData['night']);
                $morningActive = in_array($_SESSION['user'], $dayData['morning']);
                $nightActive = in_array($_SESSION['user'], $dayData['night']);
                $morningGuests = $dayData['guests'][$_SESSION['user']]['morning'] ?? 0;
                $nightGuests = $dayData['guests'][$_SESSION['user']]['night'] ?? 0;
                
                $currentTime = new DateTime();
                $morningDeadline = new DateTime("$currentMonth-$date 08:00:00");
                $nightDeadline = new DateTime("$currentMonth-$date 14:00:00");
            ?>
                <div class="day <?php echo $isPast ? 'past' : ''; ?> <?php echo $isCancelled ? 'cancelled' : ''; ?>">
                    <h3><?php echo $day; ?> <?php echo date('F', strtotime($currentMonth)); ?></h3>
                    
                    <!-- Morning Meal Section -->
                    <div class="meal morning <?php echo $morningActive ? 'active' : ''; ?>" 
                         data-date="<?php echo $date; ?>" data-meal="morning">
                        Morning Meal
                        <?php if ($morningGuests > 0): ?>
                            <span class="guest-indicator">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/>
                                </svg>
                                +<?php echo $morningGuests; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="guest-control" id="morning-guest-<?php echo $date; ?>">
                        <div class="guest-buttons">
                            <button class="btn-guest btn-minus" data-meal="morning">-</button>
                            <span class="guest-count-display" data-meal="morning"><?php echo $morningGuests; ?></span>
                            <button class="btn-guest btn-plus" data-meal="morning">+</button>
                        </div>
                        <button class="btn-save-guest" data-date="<?php echo $date; ?>" data-meal="morning">Save</button>
                    </div>

                    <!-- Night Meal Section -->
                    <div class="meal night <?php echo $nightActive ? 'active' : ''; ?>" 
                         data-date="<?php echo $date; ?>" data-meal="night">
                        Night Meal
                        <?php if ($nightGuests > 0): ?>
                            <span class="guest-indicator">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/>
                                </svg>
                                +<?php echo $nightGuests; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="guest-control" id="night-guest-<?php echo $date; ?>">
                        <div class="guest-buttons">
                            <button class="btn-guest btn-minus" data-meal="night">-</button>
                            <span class="guest-count-display" data-meal="night"><?php echo $nightGuests; ?></span>
                            <button class="btn-guest btn-plus" data-meal="night">+</button>
                        </div>
                        <button class="btn-save-guest" data-date="<?php echo $date; ?>" data-meal="night">Save</button>
                    </div>

                    <?php if (!$isPast): ?>
                        <div class="meal-buttons">
                            <?php if ($morningActive && $currentTime <= $morningDeadline): ?>
                                <button class="cancel-meal" data-date="<?php echo $date; ?>" data-meal="morning">
                                    Cancel Morning
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($nightActive && $currentTime <= $nightDeadline): ?>
                                <button class="cancel-meal" data-date="<?php echo $date; ?>" data-meal="night">
                                    Cancel Night
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($isCancelled): ?>
                                <button class="undo-cancel" data-date="<?php echo $date; ?>">
                                    Undo Cancel
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn-add-guest" data-date="<?php echo $date; ?>">
                                Manage Guests
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Cancel meal handler
            $('.cancel-meal').click(function() {
                const date = $(this).data('date');
                const meal = $(this).data('meal');
                if (confirm('Are you sure you want to cancel the ' + meal + ' meal for this day?')) {
                    $.post('dashboard.php', {
                        date: date,
                        meal: meal,
                        action: 'cancel'
                    }, function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                location.reload();
                            } else {
                                alert(result.message || 'Failed to cancel meal');
                            }
                        } catch (e) {
                            alert('Invalid response from server');
                        }
                    });
                }
            });

// Undo cancel handler
$('.undo-cancel').click(function() {
    const date = $(this).data('date');
    if (confirm('Are you sure you want to undo the cancellation for this day?')) {
        $.post('dashboard.php', {
            date: date,
            action: 'undo_cancel'
        }, function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to undo cancellation');
                }
            } catch (e) {
                alert('Invalid response from server');
            }
        });
    }
});

            // Previous guest management handlers remain the same
            $('.btn-add-guest').click(function() {
                const date = $(this).data('date');
                const morningControl = $(`#morning-guest-${date}`);
                const nightControl = $(`#night-guest-${date}`);
                
                morningControl.toggleClass('active');
                nightControl.toggleClass('active');
                
                if (!morningControl.hasClass('active')) {
                    resetGuestCounts(date);
                }
            });

            $('.btn-minus, .btn-plus').click(function() {
                const meal = $(this).data('meal');
                const countDisplay = $(this).closest('.guest-buttons').find('.guest-count-display');
                let count = parseInt(countDisplay.text());
                
                if ($(this).hasClass('btn-minus')) {
                    count = Math.max(0, count - 1);
                } else {
                    count = Math.min(10, count + 1);
                }
                
                countDisplay.text(count);
            });

            $('.btn-save-guest').click(function() {
                const date = $(this).data('date');
                const meal = $(this).data('meal');
                const count = parseInt($(this).closest('.guest-control').find('.guest-count-display').text());

                $.ajax({
                    url: 'dashboard.php',
                    type: 'POST',
                    data: {
                        date: date,
                        meal: meal,
                        action: 'add_guest',
                        guest_count: count
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                location.reload();
                            } else {
                                alert(result.message || 'Failed to update guests');
                            }
                        } catch (e) {
                            alert('Invalid response from server');
                        }
                    },
                    error: function() {
                        alert('Server error occurred');
                    }
                });
            });

            function resetGuestCounts(date) {
                $(`#morning-guest-${date} .guest-count-display`).text('0');
                $(`#night-guest-${date} .guest-count-display`).text('0');
            }
        });
    </script>
</body>
</html>