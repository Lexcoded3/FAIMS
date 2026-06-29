<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') {
    header('Location: ../auth/'); exit;
}
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'profile.php';

// Load user
$res  = $conn->query("SELECT * FROM users WHERE id=$extension_id");
$user = $res->fetch_assoc();

$success = [];
$errors  = [];
$tab     = $_GET['tab'] ?? 'personal';

// ── Handle POST ──────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Personal info
    if ($action === 'personal') {
        $name     = $conn->real_escape_string(trim($_POST['name']     ?? ''));
        $phone    = $conn->real_escape_string(trim($_POST['phone']    ?? ''));
        $location = $conn->real_escape_string(trim($_POST['location'] ?? ''));
        $loc_name = $conn->real_escape_string(trim($_POST['location_name'] ?? ''));

        if ($name === '') { $errors[] = 'Name is required.'; }
        else {
            $conn->query("UPDATE users SET name='$name', phone='$phone', location='$location', location_name='$loc_name' WHERE id=$extension_id");
            $_SESSION['name'] = $name;
            $extension_name   = $name;
            $success[] = 'Personal info updated.';
            $user = $conn->query("SELECT * FROM users WHERE id=$extension_id")->fetch_assoc();
        }
        $tab = 'personal';
    }

    // Account / email
    if ($action === 'account') {
        $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } else {
            // Check uniqueness
            $check = $conn->query("SELECT id FROM users WHERE email='$email' AND id!=$extension_id");
            if ($check->num_rows > 0) {
                $errors[] = 'That email is already in use by another account.';
            } else {
                $conn->query("UPDATE users SET email='$email' WHERE id=$extension_id");
                $success[] = 'Email address updated.';
                $user = $conn->query("SELECT * FROM users WHERE id=$extension_id")->fetch_assoc();
            }
        }
        $tab = 'account';
    }

    // Password change
    if ($action === 'password') {
        $current  = $_POST['current_password']  ?? '';
        $new      = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hashed = $conn->real_escape_string(password_hash($new, PASSWORD_DEFAULT));
            $conn->query("UPDATE users SET password='$hashed' WHERE id=$extension_id");
            $success[] = 'Password changed successfully.';
        }
        $tab = 'account';
    }

    // Avatar upload
    if ($action === 'avatar' && isset($_FILES['avatar'])) {
        $file    = $_FILES['avatar'];
        $allowed = ['image/jpeg','image/png','image/webp'];
        $max     = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed)) {
            $errors[] = 'Only JPG, PNG or WebP images are allowed.';
        } elseif ($file['size'] > $max) {
            $errors[] = 'Image must be under 2MB.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed. Please try again.';
        } else {
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $extension_id . '_' . time() . '.' . $ext;
            $dest     = '../uploads/avatars/' . $filename;
            if (!is_dir('../uploads/avatars/')) mkdir('../uploads/avatars', 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $sf = $conn->real_escape_string($filename);
                $conn->query("UPDATE users SET image_paths='$sf' WHERE id=$extension_id");
                $success[] = 'Profile photo updated.';
                $user = $conn->query("SELECT * FROM users WHERE id=$extension_id")->fetch_assoc();
            } else {
                $errors[] = 'Could not save the image. Check folder permissions.';
            }
        }
        $tab = 'personal';
    }
}

// Stats for profile card
$reports_count  = $conn->query("SELECT COUNT(*) AS c FROM extension_reports WHERE extension_id=$extension_id")->fetch_assoc()['c'];
$bulletins_count = $conn->query("SELECT COUNT(*) AS c FROM posts WHERE user_id=$extension_id")->fetch_assoc()['c'];
$days_active    = (int)floor((time() - strtotime($user['created_at'])) / 86400);

$avatar_path = $user['image_paths'] ? '../uploads/avatars/' . htmlspecialchars($user['image_paths']) : null;
$initials    = strtoupper(substr($user['name'] ?? 'EX', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Profile & Settings — FAIMS Extension</title>
<?php include '_head.php'; ?>
<style>
.tab-btn{padding:8px 16px;font-size:12px;font-weight:500;color:#6b7280;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s;white-space:nowrap}
.tab-btn:hover{color:#374151}
.tab-btn.active{color:#0F6E56;border-bottom-color:#1D9E75}
.avatar-wrap{position:relative;display:inline-block}
.avatar-wrap:hover .avatar-overlay{opacity:1}
.avatar-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;cursor:pointer}
.strength-bar{height:3px;border-radius:2px;transition:width .3s,background .3s}
.section-card{background:white;border:1px solid #f3f4f6;border-radius:12px;padding:20px 24px;margin-bottom:16px}
.section-title{font-size:13px;font-weight:500;color:#374151;margin-bottom:4px}
.section-sub{font-size:12px;color:#9ca3af;margin-bottom:18px}
.info-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f9fafb}
.info-row:last-child{border-bottom:none}
.info-label{font-size:12px;color:#9ca3af}
.info-value{font-size:12px;color:#374151;font-weight:500}
.badge-verified{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 8px;border-radius:20px;background:#E1F5EE;color:#0F6E56}
.badge-unverified{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 8px;border-radius:20px;background:#F1EFE8;color:#5F5E5A}
.danger-zone{border:1px solid #fecaca;border-radius:12px;padding:20px 24px}
</style>
</head>
<body class="bg-gray-50 text-gray-800"
      x-data="{
        tab: '<?= $tab ?>',
        pwStrength: 0,
        pwVal: '',
        avatarPreview: null,
        checkStrength(v) {
            this.pwVal = v;
            let s = 0;
            if (v.length >= 8) s++;
            if (/[A-Z]/.test(v)) s++;
            if (/[0-9]/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            this.pwStrength = s;
        },
        strengthLabel() {
            return ['','Weak','Fair','Good','Strong'][this.pwStrength] || '';
        },
        strengthColor() {
            return ['','#E24B4A','#EF9F27','#378ADD','#1D9E75'][this.pwStrength] || '#e5e7eb';
        },
        previewAvatar(e) {
            const f = e.target.files[0];
            if (!f) return;
            const r = new FileReader();
            r.onload = ev => this.avatarPreview = ev.target.result;
            r.readAsDataURL(f);
        }
      }">

<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">

    <!-- Top bar -->
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500">Profile & settings</h1>
            <p class="text-xs text-gray-400 mt-0.5">Manage your account, photo and password</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if(!empty($success)): ?>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs" style="background:#E1F5EE;color:#0F6E56">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6.5" cy="6.5" r="5.5"/><path d="M4 6.5l2 2 3.5-3.5"/></svg>
                <?= htmlspecialchars($success[0]) ?>
            </div>
            <?php endif; ?>
            <?php if(!empty($errors)): ?>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs" style="background:#FCEBEB;color:#A32D2D">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6.5" cy="6.5" r="5.5"/><path d="M6.5 4v3M6.5 8.5v.5"/></svg>
                <?= htmlspecialchars($errors[0]) ?>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide fade-in">
        <div class="max-w-4xl mx-auto px-6 py-6">
            <div class="grid grid-cols-3 gap-6">

                <!-- LEFT — Profile card -->
                <div class="col-span-1 space-y-4">

                    <!-- Avatar + name card -->
                    <div class="section-card text-center">
                        <!-- Avatar -->
                        <form method="POST" enctype="multipart/form-data" id="avatar-form">
                            <input type="hidden" name="action" value="avatar">
                            <div class="flex justify-center mb-4">
                                <div class="avatar-wrap">
                                    <template x-if="avatarPreview">
                                        <img :src="avatarPreview" class="w-20 h-20 rounded-full object-cover" style="border:3px solid #E1F5EE">
                                    </template>
                                    <template x-if="!avatarPreview">
                                        <?php if ($avatar_path && file_exists($avatar_path)): ?>
                                        <img src="<?= $avatar_path ?>" class="w-20 h-20 rounded-full object-cover" style="border:3px solid #E1F5EE">
                                        <?php else: ?>
                                        <div class="w-20 h-20 rounded-full flex items-center justify-center text-2xl text-white" style="background:#1D9E75;font-weight:500;border:3px solid #E1F5EE">
                                            <?= $initials ?>
                                        </div>
                                        <?php endif; ?>
                                    </template>
                                    <div class="avatar-overlay" onclick="document.getElementById('avatar-input').click()">
                                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="white" stroke-width="1.5"><path d="M3 13.5V15h1.5l8.5-8.5-1.5-1.5L3 13.5z"/><path d="M14.5 3.5l.5.5-1.5 1.5-.5-.5 1.5-1.5z"/></svg>
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp"
                                   class="hidden" @change="previewAvatar($event); $nextTick(()=>{ if(avatarPreview) $el.closest('form').submit() })">
                        </form>

                        <p class="text-sm text-gray-800" style="font-weight:500"><?= htmlspecialchars($user['name']) ?></p>
                        <p class="text-xs text-gray-400 mt-0.5">Extension officer</p>
                        <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($user['location_name'] ?? $user['location'] ?? 'Location not set') ?></p>

                        <!-- Status badge -->
                        <div class="mt-3">
                            <?php if($user['status']==='active'): ?>
                            <span class="badge-verified">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5" cy="5" r="4"/><path d="M3 5l1.5 1.5 2.5-2.5"/></svg>
                                Active account
                            </span>
                            <?php else: ?>
                            <span class="badge-unverified"><?= ucfirst($user['status']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stats card -->
                    <div class="section-card">
                        <p class="section-title">Activity</p>
                        <div class="space-y-1">
                            <div class="info-row">
                                <span class="info-label">Reports filed</span>
                                <span class="info-value mono"><?= $reports_count ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Bulletins posted</span>
                                <span class="info-value mono"><?= $bulletins_count ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Days active</span>
                                <span class="info-value mono"><?= $days_active ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Last login</span>
                                <span class="info-value mono"><?= $user['last_login'] ? date('d M Y', strtotime($user['last_login'])) : '—' ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Member since</span>
                                <span class="info-value mono"><?= date('M Y', strtotime($user['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Role info -->
                    <div class="section-card">
                        <p class="section-title">Account type</p>
                        <div class="space-y-1 mt-1">
                            <div class="info-row">
                                <span class="info-label">Role</span>
                                <span class="info-value">Extension officer</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">User ID</span>
                                <span class="info-value mono">#<?= $extension_id ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT — Settings panels -->
                <div class="col-span-2">

                    <!-- Tabs -->
                    <div class="flex border-b border-gray-100 mb-5 bg-white rounded-t-xl overflow-hidden" style="border:1px solid #f3f4f6;border-bottom:none;border-radius:12px 12px 0 0">
                        <button class="tab-btn" :class="tab==='personal'?'active':''" @click="tab='personal'">Personal info</button>
                        <button class="tab-btn" :class="tab==='account'?'active':''"  @click="tab='account'">Account & security</button>
                        <button class="tab-btn" :class="tab==='location'?'active':''" @click="tab='location'">Location</button>
                    </div>

                    <!-- ── Tab: Personal info ─────────────────────────────── -->
                    <div x-show="tab==='personal'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <form method="POST" class="section-card" style="border-radius:0 0 12px 12px;margin-top:0;border-top:none">
                            <input type="hidden" name="action" value="personal">
                            <p class="section-title">Personal information</p>
                            <p class="section-sub">Your name, phone and district details</p>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="field-label" for="name">Full name</label>
                                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Your full name" required>
                                </div>
                                <div>
                                    <label class="field-label" for="phone">Phone number</label>
                                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+256 7XX XXX XXX">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="field-label" for="location">District / area</label>
                                <input type="text" id="location" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" placeholder="e.g. Wakiso">
                            </div>

                            <div class="mb-5">
                                <label class="field-label" for="location_name">Location display name</label>
                                <input type="text" id="location_name" name="location_name" value="<?= htmlspecialchars($user['location_name'] ?? '') ?>" placeholder="e.g. Wakiso, Uganda">
                                <p class="text-xs text-gray-400 mt-1">This is shown in your profile and dashboard header.</p>
                            </div>

                            <!-- Read-only info -->
                            <div class="rounded-lg px-4 py-3 mb-5" style="background:#f9fafb;border:1px solid #f3f4f6">
                                <p class="text-xs text-gray-400 mb-2" style="font-weight:500">Read-only fields</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="info-label mb-0.5">Role</p>
                                        <p class="info-value">Extension officer</p>
                                    </div>
                                    <div>
                                        <p class="info-label mb-0.5">Account status</p>
                                        <p class="info-value"><?= ucfirst($user['status']) ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" class="btn-primary">
                                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="white" stroke-width="1.6"><path d="M1.5 6.5l4 4 6-8"/></svg>
                                    Save changes
                                </button>
                                <span class="text-xs text-gray-400">Changes take effect immediately</span>
                            </div>
                        </form>
                    </div>

                    <!-- ── Tab: Account & security ──────────────────────── -->
                    <div x-show="tab==='account'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

                        <!-- Email -->
                        <form method="POST" class="section-card" style="border-radius:0 0 0 0;margin-top:0;border-top:none">
                            <input type="hidden" name="action" value="account">
                            <p class="section-title">Email address</p>
                            <p class="section-sub">Used for login and notifications</p>
                            <div class="flex items-end gap-3">
                                <div class="flex-1">
                                    <label class="field-label" for="email">Email</label>
                                    <input type="text" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="you@example.com" required>
                                </div>
                                <button type="submit" class="btn-primary" style="flex-shrink:0;margin-bottom:1px">Update email</button>
                            </div>
                        </form>

                        <!-- Password -->
                        <form method="POST" class="section-card" style="margin-top:12px">
                            <input type="hidden" name="action" value="password">
                            <p class="section-title">Change password</p>
                            <p class="section-sub">Use a strong password you don't use elsewhere</p>

                            <div class="space-y-4 mb-5">
                                <div>
                                    <label class="field-label" for="current_password">Current password</label>
                                    <input type="password" id="current_password" name="current_password" placeholder="••••••••" required
                                           style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;font-family:'DM Sans',sans-serif;background:white;color:#374151;outline:none;transition:border-color .15s"
                                           onfocus="this.style.borderColor='#1D9E75'" onblur="this.style.borderColor='#e5e7eb'">
                                </div>
                                <div>
                                    <label class="field-label" for="new_password">New password</label>
                                    <input type="password" id="new_password" name="new_password" placeholder="••••••••" required
                                           style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;font-family:'DM Sans',sans-serif;background:white;color:#374151;outline:none;transition:border-color .15s"
                                           @input="checkStrength($event.target.value)"
                                           onfocus="this.style.borderColor='#1D9E75'" onblur="this.style.borderColor='#e5e7eb'">
                                    <!-- Strength bar -->
                                    <div class="mt-2 flex items-center gap-2">
                                        <div class="flex gap-1 flex-1">
                                            <template x-for="i in 4" :key="i">
                                                <div class="h-1 flex-1 rounded-full transition-colors duration-300"
                                                     :style="i <= pwStrength ? 'background:'+strengthColor() : 'background:#f3f4f6'"></div>
                                            </template>
                                        </div>
                                        <span class="text-xs" :style="'color:'+strengthColor()" x-text="strengthLabel()" style="min-width:36px"></span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Min 8 characters. Use uppercase, numbers and symbols for a strong password.</p>
                                </div>
                                <div>
                                    <label class="field-label" for="confirm_password">Confirm new password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required
                                           style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;font-family:'DM Sans',sans-serif;background:white;color:#374151;outline:none;transition:border-color .15s"
                                           onfocus="this.style.borderColor='#1D9E75'" onblur="this.style.borderColor='#e5e7eb'">
                                </div>
                            </div>

                            <button type="submit" class="btn-primary">
                                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="white" stroke-width="1.6"><rect x="2.5" y="5.5" width="8" height="6" rx="1"/><path d="M4.5 5.5V4a2 2 0 014 0v1.5"/></svg>
                                Change password
                            </button>
                        </form>

                        <!-- Session info -->
                        <div class="section-card" style="margin-top:12px">
                            <p class="section-title">Session info</p>
                            <div class="space-y-1">
                                <div class="info-row">
                                    <span class="info-label">Last login</span>
                                    <span class="info-value mono"><?= $user['last_login'] ? date('d M Y, H:i', strtotime($user['last_login'])) : 'Unknown' ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Account created</span>
                                    <span class="info-value mono"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="mt-4 pt-4" style="border-top:1px solid #f9fafb">
                                <a href="/logout.php"
                                   class="flex items-center gap-2 text-xs font-500 transition-colors"
                                   style="color:#A32D2D;font-weight:500"
                                   onclick="return confirm('Log out of your account?')">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7h7m0 0l-2.5-2.5M12 7l-2.5 2.5"/><path d="M8.5 4V3a1 1 0 00-1-1H3a1 1 0 00-1 1v8a1 1 0 001 1h4.5a1 1 0 001-1v-1"/></svg>
                                    Sign out of this session
                                </a>
                            </div>
                        </div>

                    </div>

                    <!-- ── Tab: Location ─────────────────────────────────── -->
                    <div x-show="tab==='location'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <form method="POST" class="section-card" style="border-radius:0 0 12px 12px;margin-top:0;border-top:none">
                            <input type="hidden" name="action" value="personal">
                            <p class="section-title">Location details</p>
                            <p class="section-sub">Used to match you with farmers in your district</p>

                            <div class="mb-4">
                                <label class="field-label">District / area</label>
                                <input type="text" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" placeholder="e.g. Wakiso">
                            </div>
                            <div class="mb-4">
                                <label class="field-label">Display name</label>
                                <input type="text" name="location_name" value="<?= htmlspecialchars($user['location_name'] ?? '') ?>" placeholder="e.g. Wakiso, Central Uganda">
                            </div>

                            <!-- Coordinates (read-only or manual entry) -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="field-label">Latitude</label>
                                    <input type="text" value="<?= htmlspecialchars($user['location_lat'] ?? '') ?>" placeholder="e.g. 0.3136" readonly
                                           style="background:#f9fafb;color:#9ca3af;cursor:not-allowed">
                                </div>
                                <div>
                                    <label class="field-label">Longitude</label>
                                    <input type="text" value="<?= htmlspecialchars($user['location_lon'] ?? '') ?>" placeholder="e.g. 32.5811" readonly
                                           style="background:#f9fafb;color:#9ca3af;cursor:not-allowed">
                                </div>
                            </div>

                            <!-- Tip -->
                            <div class="flex gap-3 px-4 py-3 rounded-xl border mb-5" style="background:#E6F1FB22;border-color:#B5D4F4">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="#185FA5" stroke-width="1.4" class="flex-shrink-0 mt-0.5"><circle cx="7" cy="7" r="5.5"/><path d="M7 5.5v3M7 9.5v.5"/></svg>
                                <p class="text-xs leading-relaxed" style="color:#185FA5">Coordinates are set by the admin when your account is created. Contact your administrator to update GPS coordinates.</p>
                            </div>

                            <!-- Also need name to pass validation -->
                            <input type="hidden" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                            <input type="hidden" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">

                            <button type="submit" class="btn-primary">
                                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="white" stroke-width="1.6"><path d="M1.5 6.5l4 4 6-8"/></svg>
                                Save location
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>
</div>
</body>
</html>