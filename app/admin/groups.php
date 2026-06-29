<?php
session_start();
$required_role = 'admin'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$farmer_id = $_SESSION['id'];

// Fetch farmer products
$stmt = $conn->prepare("SELECT * FROM products WHERE farmer_id=? ORDER BY id DESC");
$stmt->bind_param("i",$farmer_id);
$stmt->execute();
$result = $stmt->get_result();

$sql = "
SELECT 
    c.id, 
    c.name, 
    c.image, 
    0 AS total
FROM categories c
";
$categories = mysqli_query($conn, $sql);

$prd = <<<SQL
SELECT 
    p.id,
    p.name,
    p.description,
    p.price,
    p.quantity,
    p.unit,
    p.harvest_date,
    p.image,
    p.views,
    p.status,
    p.rejection_reason,
    p.reviewed_at,
    CASE 
        WHEN p.status = 'pending'   THEN 'Pending Review'
        WHEN p.status = 'approved'  THEN 'Approved'
        WHEN p.status = 'rejected'  THEN 'Rejected'
        WHEN p.status = 'active'    THEN 'Active'
        WHEN p.status = 'out'       THEN 'Out of Stock'
        WHEN p.status = 'expired'   THEN 'Expired'
        ELSE 'Unknown'
    END AS display_status,
    c.name          AS category_name,
    u.name          AS farmer_name,
    u.phone         AS farmer_phone,
    u.location      AS farmer_location,
    p.created_at
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN users      u ON p.farmer_id  = u.id
WHERE p.farmer_id IS NOT NULL
ORDER BY p.created_at DESC;
SQL;

$result = mysqli_query($conn, $prd);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
if(isset($_GET['ajax'])) { include 'products_table_partial.php'; exit; }

// Fetch all groups
$groups = $conn->query("
    SELECT g.*, u.name AS leader_name, 
           COUNT(gm.user_id) AS member_count
    FROM groups g
    LEFT JOIN users u ON g.leader_id = u.id
    LEFT JOIN group_members gm ON gm.group_id = g.id
    GROUP BY g.id
    ORDER BY g.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Groups</title>
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

  <body x-data="" x-bind="$store.global.documentBody" class="is-header-blur">
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
        <?php include 'groupsider.php';?>
      </div>

     <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
        <main class="main-content pos-app w-full px-[var(--margin-x)] pb-6 transition-all duration-[.25s]">
            <div class="mt-6 flex flex-col items-center justify-between space-y-2 text-center sm:flex-row sm:space-y-0 sm:text-left">
          <div>
            <h3 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
              Groups Management
            </h3>
            <p class="mt-1 hidden sm:block">List of Groups</p>
          </div>
            <div class="flex -space-x-px">
              <div x-data="{showModal:false}">
              <button @click="showModal = true"
                class="btn rounded-l-none rounded-r-full border border-primary font-medium text-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary/90"
              >
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-indigo-50" fill="none" viewbox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span> New Group </span>
              </button>
              <template x-teleport="#x-teleport-target">
      <div
        class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
        x-show="showModal"
        role="dialog"
        @keydown.window.escape="showModal = false"
      >
        <div
          class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300"
          @click="showModal = false"
          x-show="showModal"
          x-transition:enter="ease-out"
          x-transition:enter-start="opacity-0"
          x-transition:enter-end="opacity-100"
          x-transition:leave="ease-in"
          x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0"
        ></div>
        <div
          class="relative w-full max-w-lg origin-top rounded-lg bg-white transition-all duration-300 dark:bg-navy-700"
          x-show="showModal"
          x-transition:enter="easy-out"
          x-transition:enter-start="opacity-0 scale-95"
          x-transition:enter-end="opacity-100 scale-100"
          x-transition:leave="easy-in"
          x-transition:leave-start="opacity-100 scale-100"
          x-transition:leave-end="opacity-0 scale-95"
        >
          <div
            class="flex justify-between rounded-t-lg bg-slate-200 px-4 py-3 dark:bg-navy-800 sm:px-5"
          >
           <div class="flex items-center space-x-2">
                  <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 p-1 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                    <i class="fa-solid fa-layer-group"></i>
                  </div>
                  <h4 class="text-lg font-medium text-slate-700 dark:text-navy-100">
                    Add Product
                  </h4>
                </div>
            <button
              @click="showModal = !showModal"
              class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </button>
          </div>
        <form method="POST" action="add_product.php" enctype="multipart/form-data">
          <div class="space-y-4 p-4 sm:p-5">
                <label class="block">
                  <span>Product name</span>

                  <input name="name" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Enter product name" type="text">
                </label>
                <?php
                $cats = mysqli_query($conn,"SELECT * FROM categories");

                  if(!$cats){
                      die(mysqli_error($conn));
                  }
                  ?>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <label class="block">
                    <span>Category</span>
                    <select name="category_id" class="mt-1.5 w-full form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"  required >
                      <?php while($cat = mysqli_fetch_assoc($cats)): ?>
                      <option value="<?= $cat['id'] ?>">
                      <?= htmlspecialchars($cat['name']) ?>
                      </option>
                      <?php endwhile; ?>
                      </select>
                  </label>

                  <div class="grid grid-cols-2 gap-4">
                    <label class="block">
                      <span>Price</span>
                      <input name="price" type="number" step="0.01" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Price"  required >
                    </label>
                    <label class="block">
                      <span>Quantity</span>
                      <input  name="quantity" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Qty" type="number" required >
                    </label>
                  </div>
                </div>
                <div>
                  <span>Images</span>
                  <div class="filepond fp-bordered fp-grid mt-1.5 [--fp-grid:2]">
                    <input name="image" type="file" x-init="$el._x_filepond = FilePond.create($el)" multiple="">
                  </div>
                </div>
              </div>
            <div class="px-4 py-4 sm:px-5">
              
            <!-- <div class="mt-4 space-y-4"> -->
              <div class="space-x-2 text-right">
                <button
                  @click="showModal = false"
                  class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90"
                >
                  Cancel
                </button>
                <button button type="submit" name="add_product"
                  class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
                >
                  Save
                </button>
              </div>
              </form>
            <!-- </div> -->
          </div>
        </div>
      </div>
    </template>
            
  </div>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
          <div class="col-span-12 sm:col-span-6 lg:col-span-8">
            <div class="mt-4">
          <h3 class="text-base font-medium text-slate-600 dark:text-navy-100">
            Today
          </h3>
          <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-2 lg:gap-6">
            <!-- <div class="card justify-between bg-primary p-4 dark:bg-accent sm:p-5">
              <div class="flex items-center space-x-3 text-white">
                <img class="size-10 shrink-0 rounded-lg object-cover" src="../images/others/product-box.jpg" alt="image">
                <div>
                  <h3 class="text-base font-medium">Product Roadmap Q4</h3>
                  <p class="text-xs text-indigo-100">
                    Lorem ipsum dolor sit amet, consectetur.
                  </p>
                </div>
              </div>
              <div class="mt-4">
                <p class="text-xs+ text-indigo-100">Today</p>
                <p class="text-xl font-medium text-white">11:30 - 13:00</p>
                <div class="badge mt-2 rounded-full bg-white/20 text-white">
                  13 Members
                </div>
                <div class="mt-5 flex items-center justify-between space-x-2">
                  <div class="flex -space-x-3">
                    <div class="avatar size-8 hover:z-10">
                      <img class="rounded-full ring-1 ring-primary dark:ring-accent" src="../images/avatar/avatar-18.jpg" alt="avatar">
                    </div>
                    <div class="avatar size-8 hover:z-10">
                      <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
                        qa
                      </div>
                    </div>
                    <div class="avatar size-8 hover:z-10">
                      <img class="rounded-full ring-1 ring-primary dark:ring-accent" src="../images/avatar/avatar-5.jpg" alt="avatar">
                    </div>
                  </div>
                  <button class="btn size-8 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 rotate-45" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div> -->
            <?php foreach ($groups as $group): ?>
            <div class="card justify-between p-4 sm:p-5">
              <div class="flex items-center space-x-3">
                <img class="size-10 shrink-0 rounded-lg object-cover" src="../images/others/design-sm.jpg" alt="image">
                <div>
                  <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
                    <?= htmlspecialchars($group['name']) ?>
                  </h3>
                  <p class="text-xs"><?= htmlspecialchars($group['type']) ?></p>
                </div>
              </div>
              <div class="mt-4">
                <p class="text-xs+">Created</p>
                <p class="text-xm font-medium text-slate-700 dark:text-navy-100">
                  <?= date('d M Y', strtotime($group['created_at'])) ?>
                </p>
                <div class="flex flex-wrap space-x-1">
                        <div class="badge space-x-1 bg-slate-150 py-1 px-1.5 text-slate-800 dark:bg-navy-500 dark:text-navy-100">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z">
                          </svg>
                          <span> <?= htmlspecialchars($group['location'] ?: 'Not set') ?></span>
                        </div>
                      </div>


                <div class="flex flex-wrap space-x-1 pt-1">
                        <div class="badge space-x-1 bg-primary-150 py-1 px-1.5 text-slate-800 dark:bg-navy-500 dark:text-navy-100">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" ></path>
                          </svg>
                          <span> <?= $group['member_count'] ?> Members</span>
                        </div>
                        <div class="badge space-x-1 bg-warning/10 py-1 px-1.5 text-warning dark:bg-warning/15">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path>
                          </svg>
                          <span>Leader: <?= htmlspecialchars($group['leader_name'] ?: 'None') ?></span>
                        </div>
                      </div>
                <div class="mt-5 flex items-center justify-between space-x-2">
                  <div class="flex -space-x-3">
                    <div class="avatar size-8 hover:z-10">
                      <img class="rounded-full ring ring-white dark:ring-navy-700" src="../images/avatar/avatar-1.jpg" alt="avatar">
                    </div>
                    <div class="avatar size-8 hover:z-10">
                      <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring ring-white dark:ring-navy-700">
                        iu
                      </div>
                    </div>
                    <div class="avatar size-8 hover:z-10">
                      <img class="rounded-full ring ring-white dark:ring-navy-700" src="../images/avatar/avatar-2.jpg" alt="avatar">
                    </div>
                  </div>
                  <div class="flex items-center space-x-2 text-xs text-slate-400 dark:text-navy-300">
                  <div x-data="{ showModal: false, group: {} }">
                    <button @click="
                        fetch('group_edit.php?id=<?= $group['id'] ?>')
                          .then(res => res.json())
                          .then(data => group = data.group)
                          .finally(() => showModal = true)
                      " class="btn size-9 rounded-full bg-info/10 p-0 font-medium text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25" x-tooltip.info="'Edit'">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
                      </svg>
                    </button>
                  <template x-teleport="#x-teleport-target">
                    <div class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5" x-show="showModal" role="dialog" @keydown.window.escape="showModal = false">
                      <div class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300" @click="showModal = false" x-show="showModal" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
                      <div class="relative flex w-full max-w-lg origin-top flex-col overflow-hidden rounded-lg bg-white transition-all duration-300 dark:bg-navy-700" x-show="showModal" x-transition:enter="easy-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="easy-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                        <div class="flex justify-between rounded-t-lg bg-slate-200 px-4 py-3 dark:bg-navy-800 sm:px-5">
                          <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
                            Edit Group: [<?= htmlspecialchars($group['name'] ?? '') ?>]
                          </h3>
                          <button @click="showModal = !showModal" class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                          </button>
                        </div>
                        <div class="flex flex-col overflow-y-auto px-4 py-4 sm:px-5">
                          <!-- <p>
                            Lorem ipsum dolor sit amet, consectetur adipisicing
                            elit. Assumenda incidunt
                          </p> -->
                          <div class="mt-4 space-y-4">                            
                            <label class="block">
                              <span>Group name</span>
                              <input type="text" name="name" x-model="group.name" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" required>
                              </label>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                             <label class="block">
                              <span>Group Type</span>
                              <select name="type" x-model="group.type" class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent">
                                <option value="farmer_coop">Farmer Cooperative</option>
                                <option value="buyer_group">Buyer Group</option>
                                <option value="village">Village Group</option>
                                <option value="other">Other</option>
                              </select>
                            </label>

                  
                            <label class="block">
                              <span>Group Leader</span>
                              <select name="leader_id" class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="SKU" type="text">
                                <option value="">No leader assigned</option>
                                <?php foreach ($leaders as $user): ?>
                                  <option value="<?= $user['id'] ?>" <?= ($group['leader_id'] ?? 0) == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?> (ID: <?= $user['id'] ?>)
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </label>
                  
                            </div>
                            <label class="block">
                              <span>Location / District:</span>
                              <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" type="text" name="location"
                              value="<?= htmlspecialchars($group['location'] ?? '') ?>">
                            </label>
                            <label class="block">
                              <span>Description:</span>
                              <textarea  x-model="group.description" name="description" rows="3" class="form-textarea mt-1.5 w-full resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent">                               
                              </textarea>
                            </label>
                            
                            <label class="inline-flex items-center space-x-2">
                              <input name="is_active" x-model="group.is_active"  class="form-switch is-outline h-5 w-10 rounded-full border border-slate-400/70 bg-transparent before:rounded-full before:bg-slate-300 checked:border-primary checked:before:bg-primary dark:border-navy-400 dark:before:bg-navy-300 dark:checked:border-accent dark:checked:before:bg-accent" type="checkbox">
                              <span>Group is active</span>
                            </label>
                            <div class="space-x-2 text-right">
                              <button @click="showModal = false" class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                                Cancel
                              </button>
                              <button 
                              @click.prevent="
                                fetch('ajax/group_save.php', {
                                  method: 'POST',
                                  headers: {'Content-Type':'application/json'},
                                  body: JSON.stringify(group)
                                })
                                .then(res => res.json())
                                .then(data => {
                                  $notification({
                                    text: data.success ? 'Group updated successfully' : data.message,
                                    variant: data.success ? 'success' : 'error',
                                    position: 'center-top'
                                  });
                                  if (data.success) showModal = false;
                                })
                              "
                              class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white ..."
                            >
                              Apply
                            </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
                
                <a href="group_members.php?id=<?= $group['id'] ?>">
                  <button class="btn size-8 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90"  x-tooltip="'Members'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 rotate-45" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                  </button>
                </a>
                </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
          </div>
          <?php
          $pending = $conn->query("
              SELECT g.*, u.name AS creator_name 
              FROM groups g 
              LEFT JOIN users u ON g.created_by = u.id 
              WHERE g.approved = 0
              ORDER BY g.created_at DESC LIMIT 4
          ")->fetch_all(MYSQLI_ASSOC);
          ?>
          <div class="hidden sm:col-span-6 sm:block lg:col-span-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-1">
                <p class="relative flex">
                  <span class="text-base font-medium text-slate-700 dark:text-navy-100">Pending Requests</span>
                  <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        <?= count($pending) ?>
                      </div>
                </p>

                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>

                  <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                    <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                      <ul>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #001</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #002</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #005</a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex space-x-1">
                 <button  class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path>
                  </svg>
                </button>

                <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-error focus:bg-slate-300/20 focus:text-error active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
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
            </div>
            <?php if (!empty($pending)): ?>
            <div class="card mt-5 p-4 sm:p-5">
              
                <?php foreach ($pending as $p): ?>
              <div class="flex flex-col space-y-3.5">
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          <?= htmlspecialchars($p['name']) ?>
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        <?= htmlspecialchars($p['creator_name'] ?: 'Unknown') ?>[<?= htmlspecialchars($p['type']) ?>]
                      </p>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                         <?= date('d M Y', strtotime($p['created_at'])) ?>
                      </p>
                     
                    </div>
                  </div>
                  <!-- <p class="font-inter font-semibold">$12.00</p> -->
                  <div class="flex gap-3">
                  <form action="ajax/group-reject.php" method="POST" class="inline">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" onclick="return confirm('Reject this group request?')" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-error focus:bg-slate-300/20 focus:text-error active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25" x-tooltip.error="'Reject'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </form>
              <form action="ajax/group-approve.php" method="POST" class="inline">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-success focus:bg-slate-300/20 focus:text-success active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25" x-tooltip.success="'Accept'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                  </svg>
                </button>
              </form>
            </div>
                </div>
              </div>
              <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              <?php endforeach; ?>
              <?php endif; ?>
              <button class="btn mt-5 h-11 justify-center bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                <span>Approve All</span>
                <!-- <span>$88.00</span> -->
              </button>
            </div>
  

              <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>
            </div>
          </div>
        </div>
      </main>

      <div x-data="{showDrawer:false}" x-show="showDrawer" x-effect="$store.breakpoints.smAndUp && (showDrawer = false)" @show-drawer.window="($event.detail.drawerId === 'pos-card-drawer') && (showDrawer = true)" @keydown.window.escape="showDrawer = false">
        <div class="fixed inset-0 z-[100] bg-slate-900/60 transition-opacity duration-200" @click="showDrawer = false" x-show="showDrawer" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed right-0 bottom-0 z-[101] h-[calc(100%-2.5rem)] w-full">
          <div class="flex h-full w-full flex-col rounded-t-2xl bg-white px-4 py-3 transition-transform duration-200 dark:bg-navy-700" x-show="showDrawer" x-transition:enter="ease-out transform-gpu" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="ease-in transform-gpu" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
            <div class="flex items-center justify-between">
              <div class="-ml-1 flex items-center space-x-1.5">
                <button @click="showDrawer=false" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
                <div class="flex items-center space-x-1">
                  <p>
                    <span class="text-base font-medium text-slate-700 dark:text-navy-100">Draft</span>
                    <span>#001</span>
                  </p>

                  <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                    <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                      </svg>
                    </button>

                    <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                      <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                        <ul>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #001</a>
                          </li>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #002</a>
                          </li>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #005</a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="-mr-1.5 flex space-x-1">
                <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path>
                  </svg>
                </button>
                <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-error focus:bg-slate-300/20 focus:text-error active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
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
            </div>

            <div class="flex grow flex-col overflow-y-auto">
              <div class="mt-4 flex grow flex-col space-y-3.5">
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../../images/foods/food-4.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        2
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Roast beef
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Lorem ipsum dolor sit.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$12.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-5.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        1
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Tuna salad
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Amet consectetur adip.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$14.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-6.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        3
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Salmon
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Adipisicing elit. Quos?
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$45.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-7.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        1
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          California roll
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Lorem, ipsum dolor.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$22.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-10.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        2
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Duck carpaccio
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Amet consectetur adip.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$18.00</p>
                </div>
              </div>
              <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              <div class="space-y-2 font-inter">
                <div class="flex justify-between text-slate-600 dark:text-navy-100">
                  <p>Subtotal</p>
                  <p class="font-medium tracking-wide">55.00$</p>
                </div>
                <div class="flex justify-between text-xs+">
                  <p>Tax</p>
                  <p class="font-medium tracking-wide">5.00$</p>
                </div>
                <div class="flex justify-between text-base font-medium text-primary dark:text-accent-light">
                  <p>Total</p>
                  <p>60.00$</p>
                </div>
              </div>
              <div class="mt-5 grid grid-cols-3 gap-4 text-center">
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Cash
                  </span>
                </button>
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Debit
                  </span>
                </button>
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Scan
                  </span>
                </button>
              </div>
              <button class="btn mt-5 h-11 w-full justify-between bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                <span>Checkout</span>
                <span>$88.00</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="fixed right-3 bottom-3 rounded-full bg-white dark:bg-navy-700">
        <button @click="$dispatch('show-drawer', { drawerId: 'pos-card-drawer' })" class="btn size-14 rounded-full bg-warning p-0 font-medium text-white hover:bg-warning-focus focus:bg-warning-focus active:bg-warning-focus/90 sm:hidden">
          $60
        </button>
      </div>
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
                document.getElementById("productsContainer").innerHTML = data;
                window.scrollTo({ top: 0, behavior: "smooth" });
            });
    }
});
</script>
  </body>
</html>
