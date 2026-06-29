<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: /login.php'); exit;
}
require_once '../config/db.php';

$farmer_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Farmer';
$active_page    = 'profile.php';

// Load user
$res  = $conn->query("SELECT * FROM users WHERE id=$farmer_id");
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
            $conn->query("UPDATE users SET name='$name', phone='$phone', location='$location', location_name='$loc_name' WHERE id=$farmer_id");
            $_SESSION['name'] = $name;
            $extension_name   = $name;
            $success[] = 'Personal info updated.';
            $user = $conn->query("SELECT * FROM users WHERE id=$farmer_id")->fetch_assoc();
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
            $check = $conn->query("SELECT id FROM users WHERE email='$email' AND id!=$farmer_id");
            if ($check->num_rows > 0) {
                $errors[] = 'That email is already in use by another account.';
            } else {
                $conn->query("UPDATE users SET email='$email' WHERE id=$farmer_id");
                $success[] = 'Email address updated.';
                $user = $conn->query("SELECT * FROM users WHERE id=$farmer_id")->fetch_assoc();
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
            $conn->query("UPDATE users SET password='$hashed' WHERE id=$farmer_id");
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
            $filename = 'avatar_' . $farmer_id . '_' . time() . '.' . $ext;
            $dest     = '../../uploads/avatars/' . $filename;
            if (!is_dir('../../uploads/avatars')) mkdir('../../uploads/avatars', 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $sf = $conn->real_escape_string($filename);
                $conn->query("UPDATE users SET image_paths='$sf' WHERE id=$farmer_id");
                $success[] = 'Profile photo updated.';
                $user = $conn->query("SELECT * FROM users WHERE id=$farmer_id")->fetch_assoc();
            } else {
                $errors[] = 'Could not save the image. Check folder permissions.';
            }
        }
        $tab = 'personal';
    }
}

// Stats for profile card
$products_count  = $conn->query("SELECT COUNT(*) AS c FROM products WHERE farmer_id=$farmer_id")->fetch_assoc()['c'];
$bulletins_count = $conn->query("SELECT COUNT(*) AS c FROM posts WHERE user_id=$farmer_id")->fetch_assoc()['c'];
$days_active    = (int)floor((time() - strtotime($user['created_at'])) / 86400);

$avatar_path = $user['image_paths'] ? '../../uploads/avatars/' . htmlspecialchars($user['image_paths']) : null;
$initials    = strtoupper(substr($user['name'] ?? 'EX', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Farmer Profile</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="../css/app.css">

    <!-- Javascript Assets -->
    <script src="../js/app.js" defer=""></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
      /**
       * THIS SCRIPT REQUIRED FOR PREVENT FLICKERING IN SOME BROWSERS
       */
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
    <style>
      body
      {
        .mono{font-family:'DM Mono',monospace}
        .stat-card{transition:transform .15s}
.stat-card:hover{transform:translateY(-1px)}
.fade-in{animation:fadeIn .3s ease forwards}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.tag{display:inline-flex;align-items:center;font-size:11px;font-weight:500;padding:2px 8px;border-radius:20px}
.tag-disease{background:#FCEBEB;color:#A32D2D}
.tag-yield{background:#EAF3DE;color:#3B6D11}
.tag-soil{background:#FAEEDA;color:#854F0B}
.tag-water{background:#E6F1FB;color:#185FA5}
.tag-general{background:#F1EFE8;color:#5F5E5A}
.tag-pending{background:#FAEEDA;color:#854F0B}
.tag-approved,.tag-active{background:#E1F5EE;color:#0F6E56}
.tag-rejected{background:#FCEBEB;color:#A32D2D}
      }
    </style>
  </head>

  <body 
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
      }" class="is-header-blur is-sidebar-open" x-bind="$store.global.documentBody">
    <!-- App preloader-->
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
      <div class="app-preloader-inner relative inline-block size-48"></div>
    </div>

    <!-- Page Wrapper -->
    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak="">
      <!-- Sidebar -->
      <div class="sidebar print:hidden">
        <!-- Main Sidebar -->
        <div class="main-sidebar">
          <div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
            <!-- Application Logo -->
            <div class="flex pt-4">
              <a href="index.php">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>

            <!-- Main Sections Links -->
            <?php include 'sidenav.php';?>            
          </div>
        </div>

        <!-- Sidebar Panel -->
        <?php include 'profilesider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

        <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="flex flex-col items-center justify-between space-y-4 py-5 sm:flex-row sm:space-y-0 lg:py-6">
          <div class="flex items-center space-x-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewbox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            <h2 class="text-xl font-medium text-slate-700 line-clamp-1 dark:text-navy-50">
              Profile & settings
            </h2>
          </div>
          <div class="flex justify-center space-x-2">
            <button class="btn min-w-[7rem] border border-slate-300 font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
              Preview
            </button>
            <button class="btn min-w-[7rem] bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
              Save
            </button>
          </div>
        </div>
        <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-12">
          <div class="col-span-12 lg:col-span-4 space-y-6">
          <div class="card grow items-center p-4 sm:p-5">
            <div class="avatar size-20">
              <img class="rounded-full" src="../images/avatar/avatar-20.jpg" alt="avatar">
              <div class="absolute right-0 m-1 size-4 rounded-full border-2 border-green bg-primary dark:border-navy-700 dark:bg-accent"></div>
            </div>
            <h3 class="pt-3 text-lg font-medium text-slate-700 dark:text-navy-100">
              <?= htmlspecialchars($user['name']) ?>
            </h3>
            <p class="text-xs+">Extension officer</p>
            <div class="my-4 h-px w-full bg-slate-200 dark:bg-navy-500"></div>
            <div class="grow space-y-4">
              <div class="flex items-center space-x-4">
                <div class="flex h-7 w-7 items-center rounded-lg bg-primary/10 p-2 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                  <i class="fa fa-phone text-xs"></i>
                </div>
                <p><?= htmlspecialchars($user['phone'] ?? '') ?></p>
              </div>
              <div class="flex items-center space-x-4">
                <div class="flex h-7 w-7 items-center rounded-lg bg-primary/10 p-2 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                  <i class="fa fa-envelope text-xs"></i>
                </div>
                <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
              </div>
              <!-- <div class="flex items-center space-x-4">
                <div class="flex h-7 w-7 items-center rounded-lg bg-primary/10 p-2 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                  <i class="fa fa-link text-xs"></i>
                </div>
                <p>www.baymax.com</p>
              </div> -->
            </div>
            <!-- Status badge -->
                        <div class="mt-3">
                            <?php if($user['status']==='active'): ?>
                            <div
                              class="badge space-x-2.5 rounded-full bg-success/10 text-success dark:bg-success/15"
                            >
                              <div class="size-2 rounded-full bg-current"></div>
                              <span>Active Account</span>
                            </div>
                            <?php else: ?>
                            <span class="badge space-x-2.5 rounded-full bg-success/10 text-success dark:bg-success/15"><?= ucfirst($user['status']) ?></span>
                            <?php endif; ?>
                        </div>
          </div>
          <div class="rounded-lg bg-info/10 px-4 pb-5 dark:bg-navy-800 sm:px-5">
                <div class="flex items-center justify-between py-3">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Activity
                  </h2>
                  <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                    <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn -mr-1.5 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                      </svg>
                    </button>

                    <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                      <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                        <ul>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Action</a>
                          </li>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Another Action</a>
                          </li>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Something else</a>
                          </li>
                        </ul>
                        <div class="my-1 h-px bg-slate-150 dark:bg-navy-500"></div>
                        <ul>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Separated Link</a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="space-y-4">
                  <div class="space-y-3 text-xs+">
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Products
                      </p>
                      <p class="text-right"><?= $products_count ?></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Bulletins Posted
                      </p>
                      <p class="text-right"><?= $bulletins_count ?></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Days Active
                      </p>
                      <p class="text-right"><?= $days_active ?></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Last login
                      </p>
                      <p class="text-right"><?= $user['last_login'] ? date('d M Y', strtotime($user['last_login'])) : '—' ?></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Member Since
                      </p>
                      <p class="text-right"><?= date('M Y', strtotime($user['created_at'])) ?></p>
                    </div>
                  </div>
                </div>
              </div>
          </div>
          <div class="col-span-12 lg:col-span-8">
            <div class="card">
              <div x-data="{activeTab:'tabProfile'}" class="tabs flex flex-col">
                <div class="is-scrollbar-hidden overflow-x-auto">
                  <div class="border-b-2 border-slate-150 dark:border-navy-500">
                    <div class="tabs-list -mb-0.5 flex">
                      <button
                        @click="activeTab = 'tabProfile'"
                        :class="activeTab === 'tabProfile' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                        class="btn h-14 shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                      >
                        <i class="fa-solid fa-user-circle text-base"></i>
                                      <span>Personal info</span>
                      </button>
                      <button
                        @click="activeTab = 'tabAccount'"
                        :class="activeTab === 'tabAccount' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                        class="btn h-14 shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                      >
                        <i class="fa-solid fa-lock text-base"></i>
                                      <span>Account & Security</span>
                      </button>
                      <button
                        @click="activeTab = 'tabLocation'"
                        :class="activeTab === 'tabLocation' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                        class="btn h-14 shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                      >
                        <i class="fa-solid fa-map-pin text-base"></i>
                                      <span>Location </span>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="tab-content p-4 sm:p-5" x-show="activeTab === 'tabProfile'"
                  x-transition:enter="transition-all duration-500 easy-in-out"
                  x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                  x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]">
                  <div class="flex flex-col">
                  <div>
                      <p>
                        Personal information
                      </p>

                      <p class="pt-3 text-xs text-slate-400 dark:text-navy-300">
                        Your name, phone and district details
                      </p>
                    </div>
                </div>
                  <div class="space-y-5">
                    <form method="POST" action="">
                      <input type="hidden" name="action" value="personal">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">Full name</span>
                      <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Your full name" required>
                    </label>
                    <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">Phone number</span>
                      <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Enter post caption"  type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+256 7XX XXX XXX">
                    </label>
                  </div>
                  <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">District / area</span>
                      <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" id="location" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" placeholder="e.g. Wakiso">
                    </label>
                    <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">Location display name</span>
                      <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" id="location_name" name="location_name" value="<?= htmlspecialchars($user['location_name'] ?? '') ?>" placeholder="e.g. Wakiso, Uganda">
                    </label>
                    <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">Read-only fields</span>
                    <div class="rounded-lg bg-info/10 px-4 pb-5 dark:bg-navy-800 sm:px-5">
                                <div class="grid grid-cols-2 gap-3 space-y-4">
                                    <div>
                                        <p class="info-label mb-0.5">Role</p>
                                        <p class="info-value">Farmer</p>
                                    </div>
                                    <div>
                                        <p class="info-label mb-0.5">Account status</p>
                                        <p class="info-value"><?= ucfirst($user['status']) ?></p>
                                    </div>
                                </div>
                            </div>
                            </label>
                  </div>
                  <div class="flex flex-col items-center space-y-4 border-b border-slate-200 p-4 dark:border-navy-500 sm:flex-row sm:justify-between sm:space-y-0 sm:px-5">
                <span class="text-xs text-gray-400">Changes take effect immediately</span>
                <div class="flex justify-center space-x-2">
                  <button class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                    Cancel
                  </button>
                  <button type="submit" class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                    Save
                  </button>
                </div>
                
              </div>
            </form>

                </div>
                <div class="tab-content p-4 sm:p-5" x-show="activeTab === 'tabAccount'"
                  x-transition:enter="transition-all duration-500 easy-in-out"
                  x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                  x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]">
                  
                  <div class="card p-4 sm:p-5">
                    <p class="text-base font-medium text-slate-700 dark:text-navy-100">
                      Email Address
                    </p>
                    <h6 class="text-sm font-light">Used for login and notifications</h6>
                    <form method="POST">
                    <div class="space-y-5">
                    <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">Email</span>
                    <div class="flex items-center justify-between space-x-3 sm:space-x-5">
              <div class="flex w-full max-w-lg">
                <label class="relative flex w-full">
                  <input class="form-input  peer h-9 w-full rounded-l-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="you@example.com" required style="text-indent: 15px;">
                  <span class="pointer-events-none absolute hidden h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent lg:flex">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 transition-color duration-200" fill="currentColor" viewbox="0 0 24 24">
                      <path d="M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 1 0-2.636 6.364M16.5 12V8.25"></path>
                    </svg>
                  </span>
                </label>
                <button type="submit" class="btn h-9 rounded-l-none bg-primary px-3 font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 lg:px-5">
                  <span class="hidden lg:inline-flex">Update</span>
                  <svg class="size-4.5 lg:hidden" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </button>
                </form>               
              </div>
            </div>
                                </label>
                  </div>
<div class="my-3 mx-6 h-px bg-slate-200 dark:bg-navy-500"></div>

              <p class="text-base font-medium text-slate-700 dark:text-navy-100">
                      Change password
                    </p>
                    <h6 class="text-sm font-light">Use a strong password you don't use elsewhere</h6>
                    <form method="POST">
              <div class="mt-4 space-y-5">                
                  <input type="hidden" name="action" value="password">
                <label class="block">
                  <span>CurrentPassword</span>
                  <span class="relative mt-1.5 flex">
                    <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="password" id="current_password" name="current_password" placeholder="••••••••" required>
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                      <i class="fa fa-lock"></i>
                    </span>
                  </span>
                </label>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <label class="block">
                    <span>New Password</span>
                    <span class="relative mt-1.5 flex">
                      <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="password" id="new_password" name="new_password" placeholder="••••••••" required
                                           @input="checkStrength($event.target.value)">
                      <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <i class="fa fa-lock"></i>
                      </span>
                    </span>
                  </label>

                  <label class="block">
                    <span>Confirm Password</span>
                    <span class="relative mt-1.5 flex">
                      <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                      <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <i class="fa fa-lock"></i>
                      </span>
                    </span>
                  </label>
                </div>
                <div class="mt-2 flex items-center gap-2">
                                        <div class="flex gap-1 flex-1">
                                            <template x-for="i in 4" :key="i">
                                                <div class="h-2 flex-1 rounded-full transition-colors duration-300"
                                                     :style="i <= pwStrength ? 'background:'+strengthColor() : 'background:#f3f4f6'"></div>
                                            </template>
                                        </div>
                                        <span class="text-xs" :style="'color:'+strengthColor()" x-text="strengthLabel()" style="min-width:20px"></span>
                                    </div>
                <div class="flex justify-end space-x-2">
                  <button class="btn space-x-2 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewbox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" clip-rule="evenodd"></path>
                    </svg>
                    <span>Clear</span>
                  </button>
                  <button class="btn space-x-2 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                    <span>Change password</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewbox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" clip-rule="evenodd"></path>
                    </svg>
                  </button>
                   </form>
                </div>
                 <!-- Session info -->
                        <div class="section-card" style="margin-top:12px">
                            <div class="mt-4 pt-4" style="border-top:1px solid gray;">
                                <a href="../auth/logout.php"
                                   class="flex items-center gap-2 text-xs font-500 transition-colors"
                                   style="color:#A32D2D;font-weight:500"
                                   onclick="return confirm('Log out of your account?')">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7h7m0 0l-2.5-2.5M12 7l-2.5 2.5"/><path d="M8.5 4V3a1 1 0 00-1-1H3a1 1 0 00-1 1v8a1 1 0 001 1h4.5a1 1 0 001-1v-1"/></svg>
                                    Sign out of this session
                                </a>
                            </div>
                        </div>
              </div>
            </div>
                </div>
                <div class="tab-content p-4 sm:p-5" x-show="activeTab === 'tabLocation'"
                  x-transition:enter="transition-all duration-500 easy-in-out"
                  x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                  x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]">
                  <div class="space-y-5">
                    <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">District / area</span>
                      <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" placeholder="e.g. Wakiso">
                    </label>
                    <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">Display name</span>
                      <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" name="location_name" value="<?= htmlspecialchars($user['location_name'] ?? '') ?>" placeholder="e.g. Wakiso, Central Uganda">
                    </label>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <label class="block">
                    <span>Latitude</span>
                    <span class="relative mt-1.5 flex">
                      <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" value="<?= htmlspecialchars($user['location_lat'] ?? '') ?>" placeholder="e.g. 0.3136" readonly
                                           style="cursor:not-allowed">
                      <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                      </span>
                    </span>
                  </label>

                  <label class="block">
                    <span>Longitude</span>
                    <span class="relative mt-1.5 flex">
                      <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" value="<?= htmlspecialchars($user['location_lon'] ?? '') ?>" placeholder="e.g. 32.5811" readonly
                                           style="cursor:not-allowed">
                      <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                      </span>
                    </span>
                  </label>
                </div>
                <!-- Tip -->
                            <div class="flex gap-3 px-4 py-3 rounded-xl border mb-5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-info">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                              </svg>

                                <p class="text-xs leading-relaxed text-info">Coordinates are set by the admin when your account is created. Contact your administrator to update GPS coordinates.</p>
                            </div>
                            <div class="flex justify-end space-x-2">
                  <button class="btn space-x-2 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                    <span>Save location</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewbox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" clip-rule="evenodd"></path>
                    </svg>
                  </button>
                   
                </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
        </div>
      </main>
    </div>
    <!-- 
        This is a place for Alpine.js Teleport feature 
        @see https://alpinejs.dev/directives/teleport
      -->
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
  </body>
</html>
