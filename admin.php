<?php
session_start();
if (!isset($_SESSION['user']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}
$usersFile = __DIR__ . '/users.json';
$users = json_decode(file_get_contents($usersFile), true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $meal = $_POST['meal'];
    $user = $_POST['user'];
    $monthlyFile = __DIR__ . "/data/" . date('Y-m', strtotime($date)) . ".json";
    if (file_exists($monthlyFile)) {
        $monthlyData = json_decode(file_get_contents($monthlyFile), true);
        $day = date('d', strtotime($date));
        if (!isset($monthlyData[$day]['guests'][$user])) {
            $monthlyData[$day]['guests'][$user] = ['morning' => 0, 'night' => 0];
        }
        $monthlyData[$day]['guests'][$user][$meal] += 1;
        file_put_contents($monthlyFile, json_encode($monthlyData));
        $success = "Guest added successfully.";
    } else {
        $error = "Data file for the selected month not found.";
    }
    
    // Add image handling
    if (isset($_FILES['image'])) {
        $uploadDir = __DIR__ . '/img/';
        $targetSize = 50 * 1024;
        $file = $_FILES['image'];
        
        if (getimagesize($file["tmp_name"])) {
            $image = imagecreatefromstring(file_get_contents($file["tmp_name"]));
            $nextNumber = count(glob($uploadDir . 'image*.webp')) + 1;
            $newFileName = 'image' . $nextNumber . '.webp';
            
            imagewebp($image, $uploadDir . $newFileName, 80);
            $imageSuccess = "Image uploaded successfully";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_user_meal') {
    $cancelDate = $_POST['cancel_date'];
    $cancelMeal = $_POST['cancel_meal'];
    $cancelUser = $_POST['cancel_user'];
    
    $monthlyFile = __DIR__ . "/data/" . date('Y-m', strtotime($cancelDate)) . ".json";
    
    if (file_exists($monthlyFile)) {
        $monthlyData = json_decode(file_get_contents($monthlyFile), true);
        $day = date('d', strtotime($cancelDate));
        
        if (isset($monthlyData[$day][$cancelMeal])) {
            $index = array_search($cancelUser, $monthlyData[$day][$cancelMeal]);
            if ($index !== false) {
                $monthlyData[$day][$cancelMeal][$index] = $cancelUser . '_cancelled';
                file_put_contents($monthlyFile, json_encode($monthlyData));
                $success = "Successfully cancelled " . $cancelUser . "'s " . $cancelMeal . " meal for " . $cancelDate;
            } else {
                $error = "User not found in meal schedule";
            }
        } else {
            $error = "No meal schedule found for this date";
        }
    } else {
        $error = "No data found for this month";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_cancel') {
    $startDate = new DateTime($_POST['start_date']);
    $endDate = new DateTime($_POST['end_date']);
    $username = $_POST['bulk_cancel_user'];
    $mealTypes = $_POST['meal_types'] ?? [];

    while ($startDate <= $endDate) {
        $currentMonth = $startDate->format('Y-m');
        $monthlyFile = __DIR__ . "/data/{$currentMonth}.json";
        
        if (file_exists($monthlyFile)) {
            $monthlyData = json_decode(file_get_contents($monthlyFile), true);
            $day = $startDate->format('d');
            
            if (isset($monthlyData[$day])) {
                foreach ($mealTypes as $mealType) {
                    if (isset($monthlyData[$day][$mealType])) {
                        // Check for both regular and cancelled username
                        $index = array_search($username, $monthlyData[$day][$mealType]);
                        $cancelledIndex = array_search($username . '_cancelled', $monthlyData[$day][$mealType]);
                        
                        // Only cancel if username exists and isn't already cancelled
                        if ($index !== false && $cancelledIndex === false) {
                            $monthlyData[$day][$mealType][$index] = $username . '_cancelled';
                        }
                    }
                }
            }
            
            file_put_contents($monthlyFile, json_encode($monthlyData));
        }
        
        $startDate->modify('+1 day');
    }
    
    $success = "Successfully cancelled meals for the selected date range.";
}

// Handle notice update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_notice') {
    $noticeText = trim($_POST['notice_text']);
    if (empty($noticeText)) {
        unlink('notice.txt');
    } else {
        file_put_contents('notice.txt', $noticeText);
    }
    header('Location: admin.php');
    exit;
}

$existingImages = glob(__DIR__ . '/img/image*.webp');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Manager - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .form-input {
            @apply w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 ease-in-out bg-white/50;
        }
        
        .form-select {
            @apply w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 ease-in-out bg-white/50;
        }
        
        .btn-primary {
            @apply relative px-8 py-3.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold rounded-xl 
            hover:from-blue-700 hover:to-blue-600 transition-all duration-200 ease-in-out 
            focus:ring-4 focus:ring-blue-200 shadow-lg shadow-blue-500/30
            active:scale-95 transform;
        }
        
        .input-label {
            @apply block text-sm font-medium text-gray-700 mb-2 ml-1;
        }
        
        .card-hover {
            @apply transition-transform duration-300 hover:scale-[1.02];
        }
        
        .form-container {
            @apply bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-8 border border-gray-200/50 shadow-sm;
        }
    </style>
</head>
<body class="min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="glass-effect rounded-3xl shadow-xl border border-white/50 p-8 card-hover">
            <!-- Header -->
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                        Meal Manager
                    </h1>
                    <p class="text-gray-500 mt-2">Admin Control Panel</p>
                </div>
                <a href="dashboard.php" 
                   class="flex items-center px-5 py-2.5 text-blue-600 hover:text-blue-700 font-medium rounded-xl 
                          hover:bg-blue-50 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($success)): ?>
            <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200/50 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?php echo $success; ?></p>
                    </div>
                </div>
            </div>
            <?php elseif (isset($error)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200/50 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Guest and Cancel Meal Container -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
                <!-- Guest Meal Section -->
                <div class="rounded-2xl p-8 bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200/50 shadow-lg">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-blue-900">Add Guest Meal</h2>
                    </div>
                    <form method="post" class="space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label for="date" class="input-label text-blue-900">Select Date</label>
                                <input type="date" id="date" name="date" required class="form-input bg-white/70">
                            </div>
                            <div>
                                <label for="meal" class="input-label text-blue-900">Meal Type</label>
                                <select id="meal" name="meal" required class="form-select bg-white/70">
                                    <option value="">Choose meal type...</option>
                                    <option value="morning">Morning</option>
                                    <option value="night">Night</option>
                                </select>
                            </div>
                            <div>
                                <label for="user" class="input-label text-blue-900">Select Whose Guest</label>
                                <select id="user" name="user" required class="form-select bg-white/70">
                                    <option value="">Choose...</option>
                                    <?php foreach ($users as $username => $userInfo): ?>
                                        <option value="<?php echo htmlspecialchars($username); ?>">
                                            <?php echo htmlspecialchars($username); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="btn-primary bg-blue-600 hover:bg-blue-700 group">
                                <span class="flex items-center">
                                    Add Guest Meal
                                    <svg class="ml-2 w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Cancel Meal Section -->
                <div class="rounded-2xl p-8 bg-gradient-to-br from-red-50 to-red-100 border border-red-200/50 shadow-lg">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-red-900">Cancel User Meal</h2>
                    </div>
                    <form method="post" class="space-y-6">
                        <input type="hidden" name="action" value="cancel_user_meal">
                        <div class="space-y-4">
                            <div>
                                <label for="cancel_date" class="input-label text-red-900">Select Date</label>
                                <input type="date" id="cancel_date" name="cancel_date" required class="form-input bg-white/70">
                            </div>
                            <div>
                                <label for="cancel_meal" class="input-label text-red-900">Meal Type</label>
                                <select id="cancel_meal" name="cancel_meal" required class="form-select bg-white/70">
                                    <option value="">Choose meal type...</option>
                                    <option value="morning">Morning</option>
                                    <option value="night">Night</option>
                                </select>
                            </div>
                            <div>
                                <label for="cancel_user" class="input-label text-red-900">Select User</label>
                                <select id="cancel_user" name="cancel_user" required class="form-select bg-white/70">
                                    <option value="">Choose user...</option>
                                    <?php foreach ($users as $username => $userInfo): ?>
                                        <option value="<?php echo htmlspecialchars($username); ?>">
                                            <?php echo htmlspecialchars($username); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="btn-primary bg-red-600 hover:bg-red-700 group">
                                <span class="flex items-center">
                                    Cancel Meal
                                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="max-w-4xl mx-auto mt-12 p-6" x-data="imageUploader()">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                Background Images
            </h2>

            <!-- Upload Zone -->
            <div class="mb-8">
                <div 
                    @dragover.prevent="dragOver = true"
                    @dragleave.prevent="dragOver = false"
                    @drop.prevent="handleDrop($event)"
                    :class="{'border-blue-500 bg-blue-50': dragOver}"
                    class="border-3 border-dashed rounded-xl p-8 text-center transition-all duration-200">
                    
                    <input type="file" 
                           x-ref="fileInput" 
                           @change="handleFileSelect"
                           class="hidden" 
                           accept="image/*" 
                           multiple>

                    <div class="space-y-4">
                        <div class="text-5xl mb-4">📸</div>
                        <h3 class="text-lg font-semibold text-gray-700">
                            Drop images here or click to upload
                        </h3>
                        <p class="text-sm text-gray-500">
                            Supported formats: JPG, PNG (Max 5MB)
                        </p>
                        <button 
                            @click="$refs.fileInput.click()"
                            class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200">
                            Select Files
                        </button>
                    </div>
                </div>
            </div>

            <!-- Image Grid -->
            <div class="grid grid-cols-3 gap-6">
                <template x-for="(preview, index) in previews" :key="index">
                    <div class="relative group">
                        <img :src="preview" 
                             class="w-full h-40 object-cover rounded-lg transition duration-200 group-hover:opacity-75">
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-200">
                            <button 
                                @click="removeImage(index)"
                                class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Bulk Cancellation Section -->
    <div class="max-w-4xl mx-auto mt-12">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Bulk Cancel Meals</h2>
            </div>

            <form method="post" class="space-y-6" onsubmit="return confirm('Are you sure you want to cancel all meals for this date range?');">
                <input type="hidden" name="action" value="bulk_cancel">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="input-label text-gray-700">Start Date</label>
                        <input type="date" name="start_date" required class="form-input bg-white/70">
                    </div>
                    
                    <div>
                        <label class="input-label text-gray-700">End Date</label>
                        <input type="date" name="end_date" required class="form-input bg-white/70">
                    </div>
                </div>

                <div>
                    <label class="input-label text-gray-700">Select User</label>
                    <select name="bulk_cancel_user" required class="form-select bg-white/70">
                        <option value="">Choose user...</option>
                        <?php foreach ($users as $username => $userInfo): ?>
                            <option value="<?php echo htmlspecialchars($username); ?>">
                                <?php echo htmlspecialchars($username); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="input-label text-gray-700">Select Meals to Cancel</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="meal_types[]" value="morning" class="form-checkbox text-red-600">
                            <span class="ml-2">Morning Meals</span>
                        </label>
                        <br>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="meal_types[]" value="night" class="form-checkbox text-red-600">
                            <span class="ml-2">Night Meals</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary bg-red-600 hover:bg-red-700">
                        Bulk Cancel Meals
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notice Board Management -->
    <div class="bg-white rounded-2xl shadow-xl p-8 my-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Notice Board Management</h2>
        <form method="post" action="admin.php">
            <input type="hidden" name="action" value="update_notice">
            <div class="space-y-4">
                <div>
                    <label class="input-label text-gray-700">Notice Text</label>
                    <textarea name="notice_text" rows="3" 
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-red-500 
                        focus:border-transparent transition-all duration-200"
                    ><?php echo file_exists('notice.txt') ? file_get_contents('notice.txt') : ''; ?></textarea>
                </div>
                <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 
                    transition duration-200 font-medium">
                    Update Notice
                </button>
            </div>
        </form>
    </div>
</body>
</html>

<script>
function imageUploader() {
    return {
        dragOver: false,
        previews: [],
        
        handleDrop(e) {
            this.dragOver = false;
            this.handleFiles(e.dataTransfer.files);
        },
        
        handleFileSelect(e) {
            this.handleFiles(e.target.files);
        },
        
        handleFiles(files) {
            [...files].forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        this.previews.push(e.target.result);
                    };
                    reader.readAsDataURL(file);
                    
                    // Upload file
                    const formData = new FormData();
                    formData.append('image', file);
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                }
            });
        },
        
        removeImage(index) {
            this.previews.splice(index, 1);
        }
    }
}
</script>

<style>
.border-3 {
    border-width: 3px;
}
</style>