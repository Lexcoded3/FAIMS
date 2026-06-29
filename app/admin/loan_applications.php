<?php
session_start();
$required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';
// Filters (GET)
$status_filter = $_GET['status'] ?? '';
$date_from     = $_GET['date_from'] ?? '';
$date_to       = $_GET['date_to'] ?? '';
$min_amount    = $_GET['min_amount'] ?? '';
$max_amount    = $_GET['max_amount'] ?? '';
$search        = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT 
        l.id,
        l.requested_amount,
        l.approved_amount,
        l.application_date,
        l.status,
        l.purpose,
        lp.name AS product_name,
        u.name AS farmer_name,
        u.phone,
        u.location,
        u.image_paths AS farmer_image
    FROM loans l
    JOIN loan_products lp ON l.product_id = lp.id
    JOIN users u ON l.farmer_id = u.id
    WHERE 1=1
";

$params = [];
$types  = '';

if ($status_filter) {
    $sql .= " AND l.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($date_from) {
    $sql .= " AND l.application_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to) {
    $sql .= " AND l.application_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}
if ($min_amount !== '') {
    $sql .= " AND l.requested_amount >= ?";
    $params[] = (float)$min_amount;
    $types .= 'd';
}
if ($max_amount !== '') {
    $sql .= " AND l.requested_amount <= ?";
    $params[] = (float)$max_amount;
    $types .= 'd';
}
if ($search) {
    $like = "%$search%";
    $sql .= " AND (u.name LIKE ? OR u.phone LIKE ? OR l.purpose LIKE ?)";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}

$sql .= " ORDER BY l.application_date DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$loans = [];
while ($row = $result->fetch_assoc()) {
    $row['status_class'] = match($row['status']) {
        'pending'    => 'bg-warning/20 text-warning',
        'approved'   => 'bg-success/20 text-success',
        'rejected'   => 'bg-error/20 text-error',
        'disbursed'  => 'bg-info/20 text-info',
        'active'     => 'bg-primary/20 text-primary',
        'overdue'    => 'bg-error/30 text-error font-semibold',
        'repaid'     => 'bg-gray-200 text-gray-700 dark:bg-navy-600',
        default      => 'bg-slate-100 text-slate-600 dark:bg-navy-600'
    };
    $loans[] = $row;
}

      
?>
<!DOCTYPE html>
<html lang="en">
  <head> 
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Loan Applications</title>
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

  <body x-data="" x-bind="$store.global.documentBody">
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
        <?php include 'loanssider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

       <!-- Main Content Wrapper -->
        <main class="main-content pos-app w-full px-[var(--margin-x)] pb-6 transition-all duration-[.25s]">
            <div class="mt-6 flex flex-col items-center justify-between space-y-2 text-center sm:flex-row sm:space-y-0 sm:text-left">
          <div>
            <h3 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
              Loan Applications
            </h3>
            <p class="mt-1 hidden sm:block">List of Loan Apllications</p>
          </div>
            <div class="flex -space-x-px">
              <button
                class="btn rounded-l-full rounded-r-none border border-primary font-medium text-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary/90"
              >
                First
              </button>
              <button
                class="btn rounded-none bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
              >
                Second
              </button>
              <div x-data="{showModal:false}">
              <button @click="showModal = true"
                class="btn rounded-l-none rounded-r-full border border-primary font-medium text-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary/90"
              >
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-indigo-50" fill="none" viewbox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span> New Product </span>
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

          

             <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <select name="status" class="form-select rounded-lg border-slate-300 bg-white px-3 py-2 dark:bg-navy-700 dark:border-navy-600">
        <option value="">All Statuses</option>
        <option value="pending"    <?= $status_filter === 'pending'    ? 'selected' : '' ?>>Pending</option>
        <option value="approved"   <?= $status_filter === 'approved'   ? 'selected' : '' ?>>Approved</option>
        <option value="rejected"   <?= $status_filter === 'rejected'   ? 'selected' : '' ?>>Rejected</option>
        <option value="disbursed"  <?= $status_filter === 'disbursed'  ? 'selected' : '' ?>>Disbursed</option>
        <option value="active"     <?= $status_filter === 'active'     ? 'selected' : '' ?>>Active</option>
        <option value="overdue"    <?= $status_filter === 'overdue'    ? 'selected' : '' ?>>Overdue</option>
        <option value="repaid"     <?= $status_filter === 'repaid'     ? 'selected' : '' ?>>Repaid</option>
        <option value="defaulted"  <?= $status_filter === 'defaulted'  ? 'selected' : '' ?>>Defaulted</option>
      </select>

      <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="form-input rounded-lg border-slate-300 bg-white px-3 py-2 dark:bg-navy-700 dark:border-navy-600" placeholder="From Date">
      <input type="date" name="date_to"   value="<?= htmlspecialchars($date_to) ?>"   class="form-input rounded-lg border-slate-300 bg-white px-3 py-2 dark:bg-navy-700 dark:border-navy-600" placeholder="To Date">
      <input type="number" name="min_amount" value="<?= htmlspecialchars($min_amount) ?>" placeholder="Min Amount (UGX)" class="form-input rounded-lg border-slate-300 bg-white px-3 py-2 dark:bg-navy-700 dark:border-navy-600">
      <input type="number" name="max_amount" value="<?= htmlspecialchars($max_amount) ?>" placeholder="Max Amount (UGX)" class="form-input rounded-lg border-slate-300 bg-white px-3 py-2 dark:bg-navy-700 dark:border-navy-600">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search farmer / purpose" class="form-input col-span-full sm:col-span-2 lg:col-span-1 rounded-lg border-slate-300 bg-white px-3 py-2 dark:bg-navy-700 dark:border-navy-600">
    </div>


    <!-- Your new table template - adapted for loans -->
    <div class="mt-6">
      <div class="flex items-center justify-between">
        <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
          Applications List
        </h2>
        <div class="flex">
          <!-- Search toggle (from your template) -->
          <div class="flex items-center" x-data="{isInputActive:false}">
            <label class="block">
              <input 
                x-effect="isInputActive === true && $nextTick(() => { $el.focus()});" 
                :class="isInputActive ? 'w-32 lg:w-48' : 'w-0'" 
                class="form-input bg-transparent px-1 text-right transition-all duration-100 placeholder:text-slate-500 dark:placeholder:text-navy-200" 
                placeholder="Search..." 
                type="text">
            </label>
            <button @click="isInputActive = !isInputActive" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 ...">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </button>
          </div>

          <!-- More actions dropdown (from your template) -->
          <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
            <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                      </svg>
                    </button>
            <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
               <div
        class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700"
      >
                <ul>
          <li>
            <a
              href="#"
              class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide text-primary outline-none transition-all hover:bg-primary/20 focus:bg-primary/20"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25h-.75m0-3-3-3m0 0-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25v-.75" 
                />
              </svg>
              <span> Export CSV</span></a
            >
          </li>
          <li>
            <a
              href="#"
              class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide text-info outline-none transition-all hover:bg-info/20 focus:bg-info/20"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"
                />
              </svg>
              <span> Print list</span></a
            >
          </li>
        </ul>

              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  #
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Applicant
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Product
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Amount (UGX)
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Applied
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Status
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Action
                </th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($loans)): $index = 1; ?>
                <?php foreach ($loans as $loan): ?>
                  <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500 hover:bg-slate-50 dark:hover:bg-navy-600/50">
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= $index++ ?></td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <div class="flex items-center space-x-3">
                        <div class="avatar flex size-10">
                          <img class="mask is-squircle" src="<?= htmlspecialchars($loan['farmer_image'] ?? '../images/avatars/default.jpg') ?>" alt="avatar">
                        </div>
                        <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($loan['farmer_name']) ?><br>
                        <?= htmlspecialchars($loan['phone']) ?><br>
                      <?= htmlspecialchars($loan['location'] ?? '—') ?></span>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= htmlspecialchars($loan['product_name']) ?></td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-medium">
                      <?= number_format($loan['requested_amount']) ?>
                      <?php if ($loan['approved_amount'] && $loan['approved_amount'] != $loan['requested_amount']): ?>
                        <span class="text-xs text-slate-500">(Appr: <?= number_format($loan['approved_amount']) ?>)<br>
                          Purpose: <?= htmlspecialchars($loan['purpose'] ?? '—') ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <?= date('d M Y', strtotime($loan['application_date'])) ?>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <div class="<?= $loan['status_class'] ?> badge rounded-full px-3 py-1 text-xs font-medium">
                        <?= ucfirst($loan['status']) ?>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-right">
                      <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                        <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"></path>
                          </svg>
                        </button>

                        
                        <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
               <div
        class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700"
      >
                <ul>
          <li>
            <a
              href="loan-view.php?id=<?= $loan['id'] ?>"
              class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mt-px size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"
                />
              </svg>
              <span> View</span></a
            >
          </li>
          <?php if ($loan['status'] === 'pending'): ?>
          <li>
            <a
              href="approve_loan.php?id=<?= $loan['id'] ?>"
              class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                />
              </svg>
              <span> Approve</span></a
            >
          </li>
          <li>
            <a
              href="reject_loan.php?id=<?= $loan['id'] ?>"
              class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 18 18 6M6 6l12 12"
                />
              </svg>
              <span> Reject</span></a
            >
          </li>
          <?php endif; ?>
          <li>
            <a
              href="#"
              class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide text-error outline-none transition-all hover:bg-error/20 focus:bg-error/20"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
              <span> Delete item</span></a
            >
          </li>
        </ul>
              </div>
            </div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="9" class="py-10 text-center text-slate-500 dark:text-navy-300">
                    No applications found.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination from your template -->
        <div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
          <!-- Keep your original pagination / entries selector here -->
          <!-- ... paste your pagination code ... -->
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
