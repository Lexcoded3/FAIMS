<div class="col-span-12 lg:col-span-8 xl:col-span-9">
  <div class="card mt-3">
                <?php
// ================== Pagination settings ==================
$perPage = 4;                  // how many users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// ================== Get TOTAL count (for pagination math) ==================
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM products"); // change 'users' to your real table name
$totalRow   = mysqli_fetch_assoc($totalQuery);
$total      = (int)$totalRow['total'];

$totalPages = ceil($total / $perPage);

// Protect against invalid high page numbers
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}

// ================== Calculate offset & fetch actual page ==================
$offset = ($page - 1) * $perPage;

// Your main query — add LIMIT + OFFSET
// Assuming you already have WHERE / ORDER BY — just append LIMIT
$userQuery = mysqli_query($conn, "
    SELECT * FROM products 
    -- WHERE ... (your filters if any)
    ORDER BY id asc 
    LIMIT $offset, $perPage
");

// ================== Range of page numbers to show (clean look) ==================
$range = 2; // show 2 pages before & after current → e.g.  ... 4 5 6 7 8 ...
$start  = max(1, $page - $range);
$end    = min($totalPages, $page + $range);

// Stretch if near beginning or end
if ($start === 1) {
    $end = min($totalPages, $start + ($range * 2));
}
if ($end === $totalPages) {
    $start = max(1, $end - ($range * 2));
}
?>
<div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                  <table class="is-hoverable w-full text-left">
                    <thead>
                      <tr class="cursor-pointer hover:bg-slate-100">
                        <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Img
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Details
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Quantity
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Price
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                         Action
                        </th>
                        <!-- th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Action
                        </th> -->
                      </tr>
                    </thead>
                    <tbody>

                      <?php 
                      if (mysqli_num_rows($userQuery) > 0): 
                        while($row = $result->fetch_assoc()): ?>
                      <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <div
      x-data="usePopper({
       offset: 12,
       placement: 'top',
       modifiers: [
          {name: 'preventOverflow', options: {padding: 10}}
       ]                     
    })"
      class="flex"
      @mouseleave="isShowPopper = false"
      @mouseenter="isShowPopper = true"
    >
                          <div x-ref="popperRef" class="avatar flex size-10">
                            <img class="mask is-squircle" src="<?= htmlspecialchars($row['image'] ?: '/img/placeholder.jpg') ?>" alt="avatar">
                          </div>
                          <div
        x-ref="popperRoot"
        class="popper-root"
        :class="isShowPopper && 'show'"
      >
        <div class="popper-box">
          <div
            class="w-72 rounded-md border border-slate-150 bg-white p-3 dark:border-navy-600 dark:bg-navy-700"
          >
            <div class="flex space-x-3">
              <div class="avatar size-10">
                <img
                  class="rounded-full"
                  src="<?= htmlspecialchars($row['image'] ?: '/img/placeholder.jpg') ?>"
                  alt="avatar"
                />
              </div>
              <div class="flex w-full items-start justify-between">
                <div>
                  <p
                    class="text-xs+ font-medium text-slate-700 line-clamp-1 dark:text-navy-100"
                  >
                    <?= htmlspecialchars($row['farmer_name'] ?? '—') ?>
                  </p>
                  <p
                    class="text-xs text-primary line-clamp-1 dark:text-accent-light"
                  >
                    <?= htmlspecialchars($row['farmer_location'] ?? '—') ?>
                  </p>
                </div>

                 <?php if($row['status']=='active'): ?>

                           <div
                            class="badge h-5 space-x-1.5 rounded-full bg-primary/10 px-1.5 text-primary dark:bg-primary/15"
                          >
                            <div class="size-1.5 rounded-full bg-primary"></div>
                            <span>Active</span>
                          </div>

                          <?php elseif($row['status']=='pending'): ?>

                           <div
                            class="badge h-5 space-x-1.5 rounded-full bg-warning/10 px-1.5 text-warning dark:bg-warning/15"
                          >
                            <div class="size-1.5 rounded-full bg-warning"></div>
                            <span>Pending</span>
                          </div>
                        <?php elseif($row['status']=='approved'): ?>

                           <div
                            class="badge h-5 space-x-1.5 rounded-full bg-success/10 px-1.5 text-success dark:bg-success/15"
                          >
                            <div class="size-1.5 rounded-full bg-success"></div>
                            <span>Approved</span>
                          </div>
                        <?php elseif($row['status']=='rejected'): ?>

                           <div
                            class="badge h-5 space-x-1.5 rounded-full bg-secondary/10 px-1.5 text-secondary dark:bg-secondary/15"
                          >
                            <div class="size-1.5 rounded-full bg-secondary"></div>
                            <span>Rejected</span>
                          </div>
                          <?php elseif($row['status']=='out'): ?>

                           <div
                            class="badge h-5 space-x-1.5 rounded-full bg-error/10 px-1.5 text-error dark:bg-error/15"
                          >
                            <div class="size-1.5 rounded-full bg-error"></div>
                            <span>Rejected</span>
                          </div>
                        <?php elseif($row['status']=='expired'): ?>

                           <div
                            class="badge h-5 space-x-1.5 rounded-full bg-dark/10 px-1.5 text-dark dark:bg-dark/15"
                          >
                            <div class="size-1.5 rounded-full bg-dark"></div>
                            <span>Expired</span>
                          </div>
                          <?php else: ?>

                          <div
                            class="badge h-5 space-x-1.5 rounded-full bg-current/10 px-1.5 text-current dark:bg-current/15"
                          >
                            <div class="size-1.5 rounded-full bg-current"></div>
                            <span>Null</span>
                          </div>

                          <?php endif; ?>
                <!-- <div
                  class="badge h-5 space-x-1.5 rounded-full bg-warning/10 px-1.5 text-warning dark:bg-warning/15"
                >

                  <div class="size-1.5 rounded-full bg-current"></div>
                  <span>At work</span>
                </div> -->
              </div>
            </div>
            <p class="pt-2 text-xs text-slate-400 dark:text-navy-300">
              <?= mb_strimwidth($row['description'] ?? '', 0, 80, '...') ?>
            </p>
            <p class="pt-2 text-xs text-slate-400 dark:text-navy-300">
              Harvest Date: <?= $row['harvest_date'] ? date('d M Y', strtotime($row['harvest_date'])) : '—' ?>
            </p>
            <div class="flex justify-end space-x-1 pt-4">
              <button
                class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
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
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                  />
                </svg>
              </button>
              <button
                class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
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
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                  />
                </svg>
              </button>
              <button
                class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
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
                    d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"
                  />
                </svg>
              </button>
            </div>
          </div>
          <div class="size-4" data-popper-arrow>
            <svg
              viewBox="0 0 16 9"
              xmlns="http://www.w3.org/2000/svg"
              class="absolute size-4"
              fill="currentColor"
            >
              <path
                class="text-slate-150 dark:text-navy-600"
                d="M1.5 8.357s-.48.624 2.754-4.779C5.583 1.35 6.796.01 8 0c1.204-.009 2.417 1.33 3.76 3.578 3.253 5.43 2.74 4.78 2.74 4.78h-13z"
              />
              <path
                class="text-white dark:text-navy-700"
                d="M0 9s1.796-.017 4.67-4.648C5.853 2.442 6.93 1.293 8 1.286c1.07-.008 2.147 1.14 3.343 3.066C14.233 9.006 15.999 9 15.999 9H0z"
              />
            </svg>
          </div>
        </div>
      </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-700 dark:text-navy-100 lg:px-5">
                          <p><?= htmlspecialchars($row['name']) ?></p>
                          <p class="text-xs+ text-slate-400 dark:text-navy-300"><?= mb_strimwidth($row['description'] ?? '', 0, 80, '...') ?></p>
                          <p><?= htmlspecialchars($row['category_name'] ?? '—') ?></p>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-700 dark:text-navy-100 lg:px-5">
                          <p><?= number_format($row['quantity'] ?? 0) ?> <?= htmlspecialchars($row['unit'] ?? 'kg') ?></p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <p><?= number_format($row['price'], 0) ?> UGX</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                  </button>

                  <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                    <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                      <ul class="py-1">
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Approve</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Reject</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Edit</a>
                        </li>
                      </ul>
                      <div class="my-1 h-px bg-slate-150 dark:bg-navy-500"></div>
                      <ul>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Delete</a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                        </td>

                      </tr>
                      <?php endwhile; 
                      else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-500 dark:text-gray-400">
                             <div
                                class="alert flex rounded-lg border border-slate-300 px-4 py-4 text-slate-800 dark:border-navy-450 dark:text-navy-50 sm:px-5"
                              >
                                No users found. Please add a few !
                              </div>
                        </td>
                      </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
                                <!-- Pagination footer -->
<div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
    <div class="text-xs+">
        <?php 
        $from = $offset + 1;
        $to   = min($offset + $perPage, $total);
        echo $total > 0 ? "$from - $to of $total entries" : "0 entries";
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <ol class="pagination space-x-1.5">
        <!-- Previous -->
        <li>
            <a href="?page=<?= $page > 1 ? $page-1 : 1 ?>" 
               class="flex size-8 items-center justify-center rounded-full <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'bg-slate-150 hover:bg-slate-300' ?> text-slate-500 transition-colors dark:bg-navy-500 dark:text-navy-200 dark:hover:bg-navy-450">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        </li>

        <?php 
        // Page numbers
        for ($i = $start; $i <= $end; $i++): ?>
            <li>
                <a href="?page=<?= $i ?>" 
                   class="flex h-8 min-w-[2rem] items-center justify-center rounded-full px-3 leading-tight transition-colors <?= $i === $page ? 'bg-primary text-white dark:bg-accent' : 'bg-slate-150 hover:bg-slate-300 dark:bg-navy-500 dark:hover:bg-navy-450' ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>

        <!-- Next -->
        <li>
            <a href="?page=<?= $page < $totalPages ? $page+1 : $totalPages ?>" 
               class="flex size-8 items-center justify-center rounded-full <?= $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : 'bg-slate-150 hover:bg-slate-300' ?> text-slate-500 transition-colors dark:bg-navy-500 dark:text-navy-200 dark:hover:bg-navy-450">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </li>
    </ol>
    <?php endif; ?>
</div>
              </div>
</div>