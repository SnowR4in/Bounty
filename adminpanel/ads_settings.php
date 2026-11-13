<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_ROUTE . '/login');
    exit;
}

require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

$ads_file = __DIR__ . '/../includes/ads.json';

if (!file_exists($ads_file)) {
    $initial_data = [
        'login_ads' => [],
        'payments_ads' => [],
        'floating_ads' => [],
        'dashboard_level_ads' => [],
        'dashboard_ways_ads' => [],
        'dashboard_top_ads' => [],
        'general_top_ads' => [],
        'general_bottom_ads' => []
    ];
    file_put_contents($ads_file, json_encode($initial_data, JSON_PRETTY_PRINT));
}

$ads_data = json_decode(file_get_contents($ads_file), true);

$zones_to_check = ['floating_ads', 'dashboard_level_ads', 'dashboard_ways_ads', 'dashboard_top_ads', 'general_top_ads', 'general_bottom_ads', 'wheel_top_ads', 'wheel_left_ads', 'wheel_right_ads', 'wheel_bottom_ads', 'faucet_top_ads', 'faucet_left_ads', 'faucet_right_ads', 'faucet_captcha_ads', 'faucet_bottom_ads'];
$updated_json = false;
foreach ($zones_to_check as $z) {
    if (!isset($ads_data[$z])) {
        $ads_data[$z] = [];
        $updated_json = true;
    }
}
if ($updated_json) {
    file_put_contents($ads_file, json_encode($ads_data, JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_settings') {
            $landing_ads_enabled = isset($_POST['landing_ads_enabled']) ? '1' : '0';
            
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'landing_ads_enabled'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'landing_ads_enabled'");
                $stmt->execute([$landing_ads_enabled]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('landing_ads_enabled', ?)");
                $stmt->execute([$landing_ads_enabled]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Settings updated successfully!']);
            exit;
        }

        if ($_POST['action'] === 'update_floating_settings') {
            $floating_ads_landing = isset($_POST['floating_ads_landing_enabled']) ? '1' : '0';
            $floating_ads_dashboard = isset($_POST['floating_ads_dashboard_enabled']) ? '1' : '0';
            
            foreach (['floating_ads_landing_enabled' => $floating_ads_landing, 'floating_ads_dashboard_enabled' => $floating_ads_dashboard] as $key => $val) {
                $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$val, $key]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$key, $val]);
                }
            }
            echo json_encode(['success' => true, 'message' => 'Floating Ads settings updated successfully!']);
            exit;
        }

        if ($_POST['action'] === 'update_dashboard_settings') {
            $dashboard_ads_enabled = isset($_POST['dashboard_ads_enabled']) ? '1' : '0';
            
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'dashboard_ads_enabled'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'dashboard_ads_enabled'");
                $stmt->execute([$dashboard_ads_enabled]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('dashboard_ads_enabled', ?)");
                $stmt->execute([$dashboard_ads_enabled]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Dashboard Ads settings updated successfully!']);
            exit;
        }

        if ($_POST['action'] === 'update_general_settings') {
            $general_ads_enabled = isset($_POST['general_ads_enabled']) ? '1' : '0';
            
            $pages = isset($_POST['general_pages']) && is_array($_POST['general_pages']) ? $_POST['general_pages'] : [];
            $general_ads_pages = json_encode($pages);
            
            foreach (['general_ads_enabled' => $general_ads_enabled, 'general_ads_pages' => $general_ads_pages] as $key => $val) {
                $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$val, $key]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$key, $val]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'General Pages Ads settings updated successfully!']);
            exit;
        }

        if ($_POST['action'] === 'update_wheel_settings') {
            $wheel_ads_enabled = isset($_POST['wheel_ads_enabled']) ? '1' : '0';
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'wheel_ads_enabled'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'wheel_ads_enabled'");
                $stmt->execute([$wheel_ads_enabled]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('wheel_ads_enabled', ?)");
                $stmt->execute([$wheel_ads_enabled]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Wheel of Fortune Ads settings updated successfully!']);
            exit;
        }

        if ($_POST['action'] === 'update_faucet_settings') {
            $faucet_ads_enabled = isset($_POST['faucet_ads_enabled']) ? '1' : '0';
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'faucet_ads_enabled'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'faucet_ads_enabled'");
                $stmt->execute([$faucet_ads_enabled]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('faucet_ads_enabled', ?)");
                $stmt->execute([$faucet_ads_enabled]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Faucets pages ads settings updated successfully!']);
            exit;
        }
        
        if (in_array($_POST['action'], ['add_ad', 'edit_ad', 'delete_ad'])) {
            $zone = $_POST['zone']; 
            
            if (in_array($zone, ['login_ads', 'payments_ads', 'floating_ads', 'dashboard_level_ads', 'dashboard_ways_ads', 'dashboard_top_ads', 'general_top_ads', 'general_bottom_ads', 'wheel_top_ads', 'wheel_left_ads', 'wheel_right_ads', 'wheel_bottom_ads', 'faucet_top_ads', 'faucet_left_ads', 'faucet_right_ads', 'faucet_captcha_ads', 'faucet_bottom_ads'])) {
                if ($_POST['action'] === 'delete_ad') {
                    $id = $_POST['id'];
                    $new_array = [];
                    foreach ($ads_data[$zone] as $ad) {
                        if ($ad['id'] !== $id) {
                            $new_array[] = $ad;
                        }
                    }
                    $ads_data[$zone] = $new_array;
                    $msg = "Ad deleted successfully!";
                } else {
                    $id = isset($_POST['id']) && !empty($_POST['id']) ? $_POST['id'] : uniqid();
                    $ad_item = [
                        'id' => $id,
                        'name' => trim($_POST['name'] ?? ''),
                        'code' => trim($_POST['code'] ?? ''),
                        'size' => trim($_POST['size'] ?? ''),
                        'device' => trim($_POST['device'] ?? 'All'),
                        'priority' => intval($_POST['priority'] ?? 0),
                        'refresh' => trim($_POST['refresh'] ?? 'None'),
                    ];
                    
                    if ($_POST['action'] === 'edit_ad') {
                        foreach ($ads_data[$zone] as $k => $ad) {
                            if ($ad['id'] === $id) {
                                $ads_data[$zone][$k] = $ad_item;
                                break;
                            }
                        }
                        $msg = "Ad updated successfully!";
                    } else {
                        $ads_data[$zone][] = $ad_item;
                        $msg = "Ad added successfully!";
                    }
                }
                
                if (file_put_contents($ads_file, json_encode($ads_data, JSON_PRETTY_PRINT))) {
                    echo json_encode(['success' => true, 'message' => $msg]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save to JSON file.']);
                }
                exit;
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

include 'includes/header.php';
?>

<main class="flex-1 p-4 lg:p-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Recommended Ad Networks -->
        <div class="mb-8">
            <h3 class="text-lg font-bold text-zinc-100 mb-4">Recommended Ad Networks</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="https://bitmedia.io" target="_blank" class="bg-surface-card p-4 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center flex flex-col justify-center min-h-[80px]">
                    <span class="font-bold text-zinc-200 text-sm">BitMedia</span>
                </a>
                <a href="https://cryptocoinsad.com" target="_blank" class="bg-surface-card p-4 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center flex flex-col justify-center min-h-[80px]">
                    <span class="font-bold text-zinc-200 text-sm">CryptoCoinsAd</span>
                </a>
                <a href="https://coinzilla.com" target="_blank" class="bg-surface-card p-4 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center flex flex-col justify-center min-h-[80px]">
                    <span class="font-bold text-zinc-200 text-sm">CoinZilla</span>
                </a>
                <a href="https://aads.com/advertise" target="_blank" class="bg-surface-card p-4 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center flex flex-col justify-center min-h-[80px]">
                    <span class="font-bold text-zinc-200 text-sm">A-ADS</span>
                </a>
            </div>
        </div>

        <!-- Landing Page Ads Category Card -->
        <div class="rounded-lg bg-surface-card border border-white/10 mb-8 overflow-hidden">
            
            <!-- Category Header & Global Toggle -->
            <div class="p-6 border-b border-white/10 bg-white/[0.03] flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-zinc-100 text-xl"><i class="fa-solid fa-house text-primary mr-2"></i>Landing Page Ads</h3>
                    <p class="text-sm text-zinc-400 mt-1">Manage all advertisement rotation zones displayed on the main landing page.</p>
                </div>
                <form id="settingsForm" method="POST" action="" class="flex items-center gap-4 bg-surface-card px-4 py-2 rounded-lg border border-white/10">
                    <input type="hidden" name="action" value="update_settings">
                    <span class="text-sm font-bold text-zinc-300">Enable Category</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="landing_ads_enabled" class="sr-only peer" <?php echo (isset($settings['landing_ads_enabled']) && $settings['landing_ads_enabled'] == '1') ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                    <button type="submit" class="bg-white/5 hover:bg-white/10 text-zinc-200 px-4 py-1.5 rounded-lg text-sm font-bold transition-colors">Save</button>
                </form>
            </div>

            <!-- Zone: Under Login Box -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads under login box</h4>
                        <p class="text-sm text-zinc-400">Displayed immediately under the login box.</p>
                    </div>
                    <button data-open-ad-modal="login_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['login_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['login_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button data-edit-ad="login_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button data-delete-ad="login_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Above Last 10 Payments -->
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads above Last 10 Payments</h4>
                        <p class="text-sm text-zinc-400">Displayed right above the payments table.</p>
                    </div>
                    <button data-open-ad-modal="payments_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['payments_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['payments_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button data-edit-ad="payments_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button data-delete-ad="payments_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Floating Ads Category Card -->
        <div class="rounded-lg bg-surface-card border border-white/10 mb-8 overflow-hidden">
            
            <!-- Category Header & Global Toggles -->
            <div class="p-6 border-b border-white/10 bg-white/[0.03] flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-zinc-100 text-xl"><i class="fa-solid fa-layer-group text-primary mr-2"></i>Floating Ads</h3>
                    <p class="text-sm text-zinc-400 mt-1">Manage floating bottom-left ads displayed over the content.</p>
                </div>
                <form id="floatingSettingsForm" method="POST" action="" class="flex flex-col sm:flex-row items-center gap-4 bg-surface-card px-4 py-2 rounded-lg border border-white/10">
                    <input type="hidden" name="action" value="update_floating_settings">
                    
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-zinc-300">On Landing</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="floating_ads_landing_enabled" class="sr-only peer" <?php echo (isset($settings['floating_ads_landing_enabled']) && $settings['floating_ads_landing_enabled'] == '1') ? 'checked' : ''; ?>>
                            <div class="w-9 h-5 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                    
                    <div class="h-4 w-px bg-white/10 hidden sm:block"></div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-zinc-300">On Dashboard</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="floating_ads_dashboard_enabled" class="sr-only peer" <?php echo (isset($settings['floating_ads_dashboard_enabled']) && $settings['floating_ads_dashboard_enabled'] == '1') ? 'checked' : ''; ?>>
                            <div class="w-9 h-5 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <button type="submit" class="bg-white/5 hover:bg-white/10 text-zinc-200 px-4 py-1.5 rounded-lg text-sm font-bold transition-colors ml-2">Save</button>
                </form>
            </div>

            <!-- Zone: Floating Ads -->
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Floating Ads List</h4>
                        <p class="text-sm text-zinc-400">Ad rotation displayed fixed at the bottom left of the screen.</p>
                    </div>
                    <button data-open-ad-modal="floating_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['floating_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['floating_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button data-edit-ad="floating_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button data-delete-ad="floating_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dashboard Ads Category Card -->
        <div class="rounded-lg bg-surface-card border border-white/10 mb-8 overflow-hidden">
            
            <!-- Category Header & Global Toggles -->
            <div class="p-6 border-b border-white/10 bg-white/[0.03] flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-zinc-100 text-xl"><i class="fa-solid fa-gauge text-primary mr-2"></i>Dashboard Ads</h3>
                    <p class="text-sm text-zinc-400 mt-1">Manage advertisement zones displayed only on the user dashboard page.</p>
                </div>
                <form id="dashboardSettingsForm" method="POST" action="" class="flex items-center gap-4 bg-surface-card px-4 py-2 rounded-lg border border-white/10">
                    <input type="hidden" name="action" value="update_dashboard_settings">
                    <span class="text-sm font-bold text-zinc-300">Enable Category</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="dashboard_ads_enabled" class="sr-only peer" <?php echo (isset($settings['dashboard_ads_enabled']) && $settings['dashboard_ads_enabled'] == '1') ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                    <button type="submit" class="bg-white/5 hover:bg-white/10 text-zinc-200 px-4 py-1.5 rounded-lg text-sm font-bold transition-colors">Save</button>
                </form>
            </div>

            <!-- Zone: Top Bar (Welcome) -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on top page</h4>
                        <p class="text-sm text-zinc-400">Displayed in the header of the dashboard page. Replaces the welcome text. <span class="text-xs font-bold text-amber-400 ml-1"><i class="fa-solid fa-circle-exclamation"></i> Size recommended: 728x90</span></p>
                    </div>
                    <button data-open-ad-modal="dashboard_top_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['dashboard_top_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['dashboard_top_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button data-edit-ad="dashboard_top_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button data-delete-ad="dashboard_top_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Under Level Card -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads under "Level" card</h4>
                        <p class="text-sm text-zinc-400">Displayed below the user level progress card. <span class="text-xs font-bold text-amber-400 ml-1"><i class="fa-solid fa-circle-exclamation"></i> Forced width/size recommended: 300x250, 336x280, 300x100, 320x100, 320x50, 300x50, 250x250</span></p>
                    </div>
                    <button data-open-ad-modal="dashboard_level_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['dashboard_level_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['dashboard_level_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button data-edit-ad="dashboard_level_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button data-delete-ad="dashboard_level_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Under Ways to Earn -->
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads under "Ways to Earn" card</h4>
                        <p class="text-sm text-zinc-400">Displayed below the earnings navigation card.</p>
                    </div>
                    <button data-open-ad-modal="dashboard_ways_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['dashboard_ways_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['dashboard_ways_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button data-edit-ad="dashboard_ways_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button data-delete-ad="dashboard_ways_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Category: General Pages Ads -->
        <div class="rounded-lg bg-surface-card border border-white/10 overflow-hidden mb-8">
            <div class="p-6 border-b border-white/10 bg-white/[0.03] flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-zinc-100 text-xl"><i class="fa-solid fa-copy text-primary mr-2"></i>General Pages Ads</h3>
                    <p class="text-sm text-zinc-400 mt-1">Manage top and bottom ads that are displayed across multiple pages.</p>
                </div>
                <form id="generalSettingsForm" method="POST" action="" class="flex items-center gap-4 bg-surface-card px-4 py-2 rounded-lg border border-white/10">
                    <input type="hidden" name="action" value="update_general_settings">
                    <span class="text-sm font-bold text-zinc-300">Enable Category</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="general_ads_enabled" class="sr-only peer" <?php echo (isset($settings['general_ads_enabled']) && $settings['general_ads_enabled'] == '1') ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                    <button type="submit" class="bg-white/5 hover:bg-white/10 text-zinc-200 px-4 py-1.5 rounded-lg text-sm font-bold transition-colors">Save</button>
                </form>
            </div>
            
            <div class="p-6 border-b border-white/10">
                <h4 class="font-bold text-zinc-100 text-md mb-4">Select pages where these ads will be displayed:</h4>
                <?php
                $all_pages = [
                    '/user/withdraw' => 'Withdraw',
                    '/user/referrals' => 'Referrals',
                    '/user/daily_bonus' => 'Daily Bonus',
                    '/about' => 'About Us',
                    '/faq' => 'FAQ',
                    '/contact' => 'Contact Support',
                    '/terms' => 'Terms of Service',
                    '/privacy' => 'Privacy Policy',
                    '/user/video' => 'Video',
                    '/user/ptc' => 'PTC',
                    '/user/offerwall' => 'Offerwall',
                    '/user/shortlinks' => 'Shortlinks',
                    '/user/history' => 'History',
                    '/user/tasks' => 'Tasks'
                ];
                $active_pages = isset($settings['general_ads_pages']) ? json_decode($settings['general_ads_pages'], true) : [];
                if (!is_array($active_pages)) $active_pages = [];
                ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($all_pages as $path => $name): ?>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="general_pages[]" value="<?php echo $path; ?>" form="generalSettingsForm" class="w-4 h-4 accent-emerald-500 text-primary bg-white/5 border-white/10 rounded focus:ring-primary/20" <?php echo in_array($path, $active_pages) ? 'checked' : ''; ?>>
                        <span class="text-sm font-medium text-zinc-300 group-hover:text-primary transition-colors"><?php echo $name; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Zone: Top Ads -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Top</h4>
                        <p class="text-sm text-zinc-400">Displayed at the top of the content on normal pages, or after the first row of cards on Offer pages.</p>
                    </div>
                    <button type="button" data-open-ad-modal="general_top_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['general_top_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['general_top_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button data-edit-ad="general_top_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button data-delete-ad="general_top_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Zone: Bottom Ads -->
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Bottom</h4>
                        <p class="text-sm text-zinc-400">Displayed at the bottom of the page content.</p>
                    </div>
                    <button type="button" data-open-ad-modal="general_bottom_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['general_bottom_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['general_bottom_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button data-edit-ad="general_bottom_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button data-delete-ad="general_bottom_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Category: Wheel of Fortune Ads -->
        <div class="rounded-lg bg-surface-card border border-white/10 overflow-hidden mb-8">
            <div class="p-6 border-b border-white/10 bg-white/[0.03] flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-zinc-100 text-xl"><i class="fa-solid fa-dharmachakra text-primary mr-2"></i>Wheel of Fortune Ads</h3>
                    <p class="text-sm text-zinc-400 mt-1">Manage advertisement rotation zones displayed on the Wheel of Fortune page.</p>
                </div>
                <form id="wheelSettingsForm" method="POST" action="" class="flex items-center gap-4 bg-surface-card px-4 py-2 rounded-lg border border-white/10">
                    <input type="hidden" name="action" value="update_wheel_settings">
                    <span class="text-sm font-bold text-zinc-300">Enable Category</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="wheel_ads_enabled" class="sr-only peer" <?php echo (isset($settings['wheel_ads_enabled']) && $settings['wheel_ads_enabled'] == '1') ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                    <button type="submit" class="bg-white/5 hover:bg-white/10 text-zinc-200 px-4 py-1.5 rounded-lg text-sm font-bold transition-colors">Save</button>
                </form>
            </div>

            <!-- Zone: Wheel Top Ads -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Top</h4>
                        <p class="text-sm text-zinc-400">Any size and device. Displayed above the wheel area.</p>
                    </div>
                    <button type="button" data-open-ad-modal="wheel_top_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['wheel_top_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['wheel_top_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="wheel_top_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="wheel_top_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Wheel Left Ads -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Left (Desktop Only)</h4>
                        <p class="text-sm text-zinc-400">Displayed on the left side of the wheel. Forced sizes: 300x250 | 300x600 | 120x600 | 160x600.</p>
                    </div>
                    <button type="button" data-open-ad-modal="wheel_left_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['wheel_left_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['wheel_left_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="wheel_left_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="wheel_left_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Wheel Right Ads -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Right (Desktop Only)</h4>
                        <p class="text-sm text-zinc-400">Displayed on the right side of the wheel. Forced sizes: 300x250 | 300x600 | 120x600 | 160x600.</p>
                    </div>
                    <button type="button" data-open-ad-modal="wheel_right_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['wheel_right_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['wheel_right_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="wheel_right_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="wheel_right_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Wheel Bottom Ads -->
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads under the Wheel</h4>
                        <p class="text-sm text-zinc-400">Any size and device. Displayed below the wheel section.</p>
                    </div>
                    <button type="button" data-open-ad-modal="wheel_bottom_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['wheel_bottom_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['wheel_bottom_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?>
                                        <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?>
                                        <span class="text-purple-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?>
                                        <span class="text-zinc-400 font-medium"><i class="fa-solid fa-globe mr-1"></i> All</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="wheel_bottom_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="wheel_bottom_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Faucets Pages Ads -->
        <div class="rounded-lg bg-surface-card border border-white/10 overflow-hidden mt-8">
            <div class="p-6 border-b border-white/10 bg-white/[0.03]">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                            <i class="fa-solid fa-faucet-drip text-purple-400"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-zinc-100">Faucets Pages Ads</h3>
                            <p class="text-sm text-zinc-400">Manage advertisement rotation zones displayed on the Faucet page.</p>
                        </div>
                    </div>
                    <form id="faucetSettingsForm" class="flex items-center gap-3">
                        <input type="hidden" name="action" value="update_faucet_settings">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="faucet_ads_enabled" class="sr-only peer" <?php echo (isset($settings['faucet_ads_enabled']) && $settings['faucet_ads_enabled'] == '1') ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            <span class="ml-3 text-sm font-bold text-zinc-300">Enable Category</span>
                        </label>
                        <button type="submit" class="bg-primary hover:bg-primaryHover text-zinc-950 px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-sm ml-2">
                            Save
                        </button>
                    </form>
                </div>
            </div>

            <!-- Zone: Faucet Top Ads -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Top</h4>
                        <p class="text-sm text-zinc-400">Any size and device. Displayed above the faucet area.</p>
                    </div>
                    <button type="button" data-open-ad-modal="faucet_top_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['faucet_top_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['faucet_top_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?><span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?><span class="text-emerald-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?><span class="text-zinc-400 font-medium"><i class="fa-solid fa-display mr-1"></i> All</span><?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="faucet_top_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="faucet_top_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Faucet Left Ads -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Left (Desktop Only)</h4>
                        <p class="text-sm text-zinc-400">Displayed on the left side of the faucet. Forced sizes: 300x250 | 300x600 | 120x600 | 160x600.</p>
                    </div>
                    <button type="button" data-open-ad-modal="faucet_left_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['faucet_left_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['faucet_left_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="faucet_left_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="faucet_left_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Faucet Right Ads -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Right (Desktop Only)</h4>
                        <p class="text-sm text-zinc-400">Displayed on the right side of the faucet. Forced sizes: 300x250 | 300x600 | 120x600 | 160x600.</p>
                    </div>
                    <button type="button" data-open-ad-modal="faucet_right_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['faucet_right_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['faucet_right_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="faucet_right_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="faucet_right_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Faucet Captcha Ads -->
            <div class="p-6 border-b border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads under Captcha</h4>
                        <p class="text-sm text-zinc-400">Any device. Forced sizes: 300x250 | 250x250 | 300x100 | 320x100 | 320x50.</p>
                    </div>
                    <button type="button" data-open-ad-modal="faucet_captcha_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['faucet_captcha_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['faucet_captcha_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?><span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?><span class="text-emerald-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?><span class="text-zinc-400 font-medium"><i class="fa-solid fa-display mr-1"></i> All</span><?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="faucet_captcha_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="faucet_captcha_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zone: Faucet Bottom Ads -->
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <div>
                        <h4 class="font-bold text-zinc-100 text-lg">Ads on Bottom</h4>
                        <p class="text-sm text-zinc-400">Any size and device. Displayed below the faucet area.</p>
                    </div>
                    <button type="button" data-open-ad-modal="faucet_bottom_ads" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Ad
                    </button>
                </div>
                
                <div class="overflow-x-auto table-scroll border border-white/10 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03]">
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Size</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Device</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Priority</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Refresh</th>
                                <th class="p-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($ads_data['faucet_bottom_ads'])): ?>
                            <tr><td colspan="6" class="p-6 text-center text-zinc-500 font-medium">No ads configured for this zone.</td></tr>
                            <?php else: foreach ($ads_data['faucet_bottom_ads'] as $ad): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-4 font-bold text-zinc-100"><?php echo htmlspecialchars($ad['name']); ?></td>
                                <td class="p-4 text-sm text-zinc-300"><span class="bg-white/5 px-2 py-1 rounded text-xs font-bold"><?php echo htmlspecialchars($ad['size']); ?></span></td>
                                <td class="p-4 text-sm">
                                    <?php if($ad['device'] == 'Desktop'): ?><span class="text-blue-400 font-medium"><i class="fa-solid fa-desktop mr-1"></i> Desktop</span>
                                    <?php elseif($ad['device'] == 'Mobile'): ?><span class="text-emerald-400 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> Mobile</span>
                                    <?php else: ?><span class="text-zinc-400 font-medium"><i class="fa-solid fa-display mr-1"></i> All</span><?php endif; ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-primary"><?php echo $ad['priority']; ?></td>
                                <td class="p-4 text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($ad['refresh']); ?></td>
                                <td class="p-4 text-right">
                                    <button type="button" data-edit-ad="faucet_bottom_ads" data-ad='<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>' class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 transition-colors inline-flex items-center justify-center mr-1">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button type="button" data-delete-ad="faucet_bottom_ads" data-ad-id="<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors inline-flex items-center justify-center">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Ad Modal -->
<div id="adModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm opacity-0 invisible transition-all duration-300 p-4">
    <!-- Wider on desktop as requested: max-w-4xl -->
    <div class="bg-surface-card w-full max-w-4xl max-h-[90vh] rounded-lg shadow-soft flex flex-col overflow-hidden relative transform scale-95 transition-transform duration-300" id="adModalContent">
        
        <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between bg-white/[0.03]">
            <h3 class="font-bold text-lg text-zinc-100" id="adModalTitle">Add Ad</h3>
            <button data-close-ad-modal class="w-8 h-8 bg-white/10 hover:bg-white/20 text-zinc-300 rounded-full flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto">
            <form id="adForm" method="POST" action="">
                <input type="hidden" name="action" id="adAction" value="add_ad">
                <input type="hidden" name="zone" id="adZone" value="">
                <input type="hidden" name="id" id="adId" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Ad Name</label>
                        <input type="text" name="name" id="adName" required placeholder="e.g. A-Ads 728x90" class="w-full bg-white/[0.03] border border-white/10 text-zinc-100 text-sm rounded-lg focus:ring-primary/20 focus:border-primary/60 block p-3">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Ad Size</label>
                        <div id="adSizeContainer">
                            <input type="text" name="size" id="adSize" required placeholder="e.g. 728x90" class="w-full bg-white/[0.03] border border-white/10 text-zinc-100 text-sm rounded-lg focus:ring-primary/20 focus:border-primary/60 block p-3">
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-zinc-300 mb-2">Ad Code (HTML / Scripts / Iframe)</label>
                    <textarea name="code" id="adCode" required rows="5" class="w-full bg-white/[0.03] border border-white/10 text-zinc-100 text-sm rounded-lg focus:ring-primary/20 focus:border-primary/60 block p-3 font-mono"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div id="adDeviceContainer">
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Target Device</label>
                        <select name="device" id="adDevice" class="w-full bg-white/[0.03] border border-white/10 text-zinc-100 text-sm rounded-lg focus:ring-primary/20 focus:border-primary/60 block p-3 cursor-pointer">
                            <option value="All">All Devices</option>
                            <option value="Desktop">Desktop Only</option>
                            <option value="Mobile">Mobile Only</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Priority (Higher = Better)</label>
                        <input type="number" name="priority" id="adPriority" required value="0" class="w-full bg-white/[0.03] border border-white/10 text-zinc-100 text-sm rounded-lg focus:ring-primary/20 focus:border-primary/60 block p-3">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Auto-Refresh</label>
                        <select name="refresh" id="adRefresh" class="w-full bg-white/[0.03] border border-white/10 text-zinc-100 text-sm rounded-lg focus:ring-primary/20 focus:border-primary/60 block p-3 cursor-pointer">
                            <option value="None">None</option>
                            <option value="30 seconds">30 seconds</option>
                            <option value="60 seconds">60 seconds</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-white/10">
                    <button type="button" data-close-ad-modal class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-zinc-300 rounded-lg font-semibold transition-colors text-sm">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primaryHover text-zinc-950 rounded-lg font-bold transition-colors shadow-md text-sm">Save Ad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin/admin-ads.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>

