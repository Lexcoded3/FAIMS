<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$admin_id = $_SESSION['id'];


$search = $_GET['search'] ?? '';

if (!empty($search)) {

    $stmt = $conn->prepare("
        SELECT id, name, email, phone, role, status, location, created_at
        FROM users
        WHERE name LIKE ? OR email LIKE ?
        ORDER BY created_at DESC
    ");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $like = "%{$search}%";

    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();

    $userQuery = $stmt->get_result();

} else {

    $userQuery = $conn->query("
        SELECT id, name, email, phone, role, status, location, image_paths, created_at
        FROM users
        ORDER BY created_at DESC
    ");
}

// Arrays for badge colors
$roleColors = [
    'admin' => 'green',
    'buyer' => 'yellow',
    'farmer' => 'blue',
    'extension' => 'red'
];

$statusColors = [
    'active' => 'green',
    'suspended' => 'yellow',
    'banned' => 'red'
];
if(isset($_GET['ajax'])) { include 'users_table_partial.php'; exit; }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Users</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="../css/app.css">

    <!-- Javascript Assets -->
    <script src="../js/app.js" defer=""></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <script>
      /**
       * THIS SCRIPT REQUIRED FOR PREVENT FLICKERING IN SOME BROWSERS
       */
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
  </head>

  <body x-data="" x-bind="$store.global.documentBody" class="is-header-blur is-sidebar-open">
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
        <?php include 'userssider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>
        <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
<!-- <div class="rounded-lg bg-info/10 px-4 pb-5 dark:bg-navy-800 sm:px-5"> -->
        <div class="mt-4 grid grid-cols-12 gap-4 lg:mt-6 lg:gap-2" x-data="userManager()" x-init="init()">

          
            <div id="usersContainer" class="col-span-12 lg:col-span-8 xl:col-span-9">
            <!-- Your table here -->
            <?php include 'users_table_partial.php';?>
            <!-- Pagination footer -->
            
        </div>
          <div class="col-span-12 lg:col-span-4 xl:col-span-3 mt-5">
            <!-- <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">
              </div>  -->
              <div class="rounded-lg bg-success/10 px-4 pb-5 dark:bg-navy-800 sm:px-5">
                <div class="flex items-center justify-between py-3">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    User Detail
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
                  <div class="flex justify-between">
                    <div class="avatar size-16 relative">
                      <!-- Image (hidden if missing) -->
                      <img class="rounded-full w-full h-full object-cover" 
                           :src="selectedUser.image_paths ? '../' + selectedUser.image_paths.split('/').filter(Boolean).join('/') : ''"
                           :alt="selectedUser.name"
                           onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display='flex');">
                      
                      <!-- Initials fallback (shown if image missing) -->
                      <div class="rounded-full w-full h-full flex items-center justify-center font-semibold text-2xl text-white absolute inset-0" 
                           x-show="!selectedUser.image_paths || selectedUser.image_paths.trim() === ''"
                           style="background: linear-gradient(135deg, #1D9E75 0%, #16a34a 100%);">
                        <span x-text="getInitials(selectedUser.name)"></span>
                      </div>
                    </div>
                    <script>
                        function getInitials(name) {
                          if (!name) return 'U';
                          const parts = name.trim().split(/\s+/).slice(0, 2);
                          return parts.map(p => p.charAt(0).toUpperCase()).join('');
                        }
                        </script>
                    <div>
                      <p>Login</p>
                      <p class="text-xl font-medium text-slate-700 dark:text-navy-100" x-text="timeAgo(selectedUser.last_login)">
                        
                      </p>
                    </div>
                  </div>
                  <div>
                    <h3 x-text="selectedUser.name" class="text-lg font-medium text-slate-700 dark:text-navy-100">
                    --                      
                    </h3>
                    <p  class="text-xs text-slate-400 dark:text-navy-300">                     
                    </p>
                  </div>
                  <div class="space-y-3 text-xs+">
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Role
                      </p>
                      <p class="text-right" x-text="selectedUser.role">N/A</p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Phone
                      </p>
                      <p class="text-right" x-text="selectedUser.phone"></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Location
                      </p>
                      <p class="text-right" x-text="selectedUser.location"></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Last login
                      </p>
                      <p class="text-right" x-text="timeAgo(selectedUser.last_login)"></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Register Date
                      </p>
                      <p class="text-right" x-text="formatDate(selectedUser.created_at)"></p>
                    </div>
                  </div>
                </div>
              </div>
                  <?php
                      // Fetch the 2 most recent users by created_at
                      $stmt = $conn->prepare("
                          SELECT id, name, email, phone, role, location, image_paths, created_at
                          FROM users
                          ORDER BY created_at DESC
                          LIMIT 4
                      ");
                      $stmt->execute();
                      $recentUsers = $stmt->get_result();
                      ?>
              <div class="sm:mt-5 col-span-2 lg:col-span-1">
                <div class="flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    New users
                  </h2>
                  <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">View All</a>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-x-5 lg:grid-cols-1">
                  <?php while($user = $recentUsers->fetch_assoc()): ?>
                    <?php 
                      // Get initials from name
                      $nameParts = explode(' ', trim($user['name']));
                      $initials = '';
                      foreach (array_slice($nameParts, 0, 2) as $part) {
                        $initials .= strtoupper(substr($part, 0, 1));
                      }
                      
                      // Check if image exists in DB and file actually exists on server
                      $imagePath = $user['image_paths'] ?? '';
                      $hasImage = !empty($imagePath) && file_exists(__DIR__ . '/..' . $imagePath);
                      
                      // Safe initials for use in inline HTML
                      $safeInitials = htmlspecialchars(!empty($initials) ? $initials : 'U', ENT_QUOTES, 'UTF-8');
                    ?>
                  <div class="card p-3">
                    <div class="flex items-center justify-between space-x-3">
                      <div class="flex items-center space-x-3">
                        <div class="avatar size-10 relative">
                          <?php if($hasImage): ?>
                            <!-- Show profile picture from database -->
                            <img class="rounded-full w-full h-full object-cover" 
                                 src="<?= htmlspecialchars('../' . ltrim($imagePath, '/'), ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>"
                                 onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display='flex');">
                          <?php endif; ?>
                          
                          <!-- Always render initials div (hidden if image loads) -->
                          <div class="rounded-full w-full h-full flex items-center justify-center font-semibold text-sm text-white transition-all" 
                               style="background: linear-gradient(135deg, #1D9E75 0%, #16a34a 100%); letter-spacing: 0.5px; <?= $hasImage ? 'display: none;' : '' ?>">
                            <?= $safeInitials ?>
                          </div>
                          
                          <!-- Online status indicator -->
                          <div class="absolute right-0 bottom-0 size-3 rounded-full border-2 border-white bg-primary dark:border-navy-700 dark:bg-accent"></div>
                        </div>
                        <div>
                          <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                            <?= htmlspecialchars($user['name']) ?>
                          </p>
                          <p class="mt-0.5 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            <?= htmlspecialchars($user['role']) ?>
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <?php endwhile; ?>
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
    <script>
document.addEventListener("click", function(e) {
    if (e.target.closest(".pagination a")) {
        e.preventDefault();

        let link = e.target.closest("a");
        let url = link.getAttribute("href");

        fetch(url + "&ajax=1")
            .then(response => response.text())
            .then(data => {
                document.getElementById("usersContainer").innerHTML = data;
                window.scrollTo({ top: 0, behavior: "smooth" });
            });
    }
});
</script>
<script>
function userManager() {
    return {
        selectedUser: {},
        editUser: {},
        deleteId: null,
        showEditModal: false,
        showDeleteModal: false,

        init() {},

        viewDetail(id) {
            fetch('get_user.php?id=' + id)
            .then(res => res.json())
            .then(data => {
                this.selectedUser = data;
            });
        },
        timeAgo(dateString) {
    if (!dateString) return 'Never';

    let now = new Date();
    let date = new Date(dateString);
    let seconds = Math.floor((now - date) / 1000);

    if (seconds < 60)
        return 'Just now';

    let minutes = Math.floor(seconds / 60);
    if (minutes < 60)
        return minutes + (minutes === 1 ? ' minute ago' : ' minutes ago');

    let hours = Math.floor(minutes / 60);
    if (hours < 24)
        return hours + (hours === 1 ? ' hour ago' : ' hours ago');

    let days = Math.floor(hours / 24);
    if (days === 1)
        return 'Yesterday';

    if (days < 7)
        return days + ' days ago';

    // If older than 7 days, show normal formatted date
    return this.formatDate(dateString);
},
formatDate(dateString) {
    if (!dateString) return '';

    let date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
},
search: '',

performSearch() {
    fetch('users.php?search=' + this.search + '&ajax=1')
    .then(res => res.text())
    .then(data => {
        document.getElementById('usersContainer').innerHTML = data;

        // Re-select first user after search
        this.loadFirstUser();
    });
},

        openEdit(id) {
            fetch('get_user.php?id=' + id)
            .then(res => res.json())
            .then(data => {
                this.editUser = data;
                this.showEditModal = true;
            });
        },

        openDelete(id) {
            this.deleteId = id;
            this.showDeleteModal = true;
        },

        updateUser() {
            fetch('update_user.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(this.editUser)
            })
            .then(res => res.text())
            .then(() => {
                this.showEditModal = false;

                this.$notification({
                    text: 'User updated successfully',
                    variant: 'success',
                    position: 'center-top'
                });

                // OPTIONAL: refresh table dynamically later
            });
        },

              deleteUser() {
    fetch('delete_user.php?id=' + this.deleteId)
    .then(res => res.json())
    .then(data => {

        if (data.success) {

            // Close modal
            this.showDeleteModal = false;

            // Remove row from DOM
            let row = document.getElementById('user-row-' + this.deleteId);
            if (row) row.remove();

            // Show notification
            this.$notification({
                text: 'User deleted successfully',
                variant: 'error',
                position: 'center-top'
            });

        } else {
            this.$notification({
                text: 'Delete failed',
                variant: 'info',
                position: 'center-top'
            });
        }
    });
}
    }
}
</script>
  </body>
</html>
