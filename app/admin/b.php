<?php
session_start();
$required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'extension'])) {
    header('Location: ../../login.php');
    exit;
}

// Fetch borrowers with credit summary
$sql = "
    SELECT 
        u.id,
        u.name,
        u.phone,
        u.location,
        u.image_paths AS avatar,
        COUNT(l.id) AS total_loans,
        COALESCE(SUM(l.requested_amount), 0) AS total_borrowed,
        COALESCE(SUM(l.total_paid), 0) AS total_repaid,
        COALESCE(SUM(l.total_repayable), 0) AS total_due,
        CASE 
            WHEN COALESCE(SUM(l.total_repayable), 0) > 0 
            THEN ROUND((COALESCE(SUM(l.total_paid), 0) / SUM(l.total_repayable)) * 100, 1)
            ELSE 0 
        END AS repayment_percent,
        SUM(CASE WHEN l.status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count,
        MAX(l.application_date) AS last_loan_date
    FROM users u
    LEFT JOIN loans l ON u.id = l.farmer_id
    WHERE u.role = 'farmer'
    GROUP BY u.id
    HAVING total_loans > 0
    ORDER BY repayment_percent DESC, overdue_count ASC, total_borrowed DESC
";

$borrowers_result = $conn->query($sql);
?>

<main class="main-content w-full px-[var(--margin-x)] pb-10">
  <div class="mt-6">
    <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
      Borrowers & Farmers
    </h1>

    <p class="mt-2 text-slate-600 dark:text-navy-200">
      Overview of all farmers who have applied for or taken loans
    </p>

    <!-- Your table template - adapted for borrowers -->
    <div class="mt-6">
      <div class="flex items-center justify-between">
        <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
          Active & Past Borrowers
        </h2>
        <div class="flex">
          <!-- Search toggle -->
          <div class="flex items-center" x-data="{isInputActive:false}">
            <label class="block">
              <input 
                x-effect="isInputActive === true && $nextTick(() => { $el.focus()});" 
                :class="isInputActive ? 'w-32 lg:w-48' : 'w-0'" 
                class="form-input bg-transparent px-1 text-right transition-all duration-100 placeholder:text-slate-500 dark:placeholder:text-navy-200" 
                placeholder="Search farmer..." 
                type="text">
            </label>
            <button @click="isInputActive = !isInputActive" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </button>
          </div>

          <!-- More actions dropdown -->
          <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
            <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
              </svg>
            </button>
            <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
              <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                <ul>
                  <li><a href="#" class="flex h-8 items-center px-3 pr-8 ...">Export List</a></li>
                  <li><a href="#" class="flex h-8 items-center px-3 pr-8 ...">Send Reminder to Overdue</a></li>
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
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Farmer
                </th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Contact / Location
                </th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Total Loans
                </th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Total Borrowed
                </th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Repaid
                </th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Repayment %
                </th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Overdue
                </th>
                <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Last Loan
                </th>
                <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $index = 1;
              while ($farmer = $borrowers_result->fetch_assoc()): 
                $repay_class = ($farmer['repayment_percent'] >= 90) ? 'text-success' : 
               (($farmer['repayment_percent'] >= 70) ? 'text-warning' : 'text-error');
                $overdue_class = $farmer['overdue_count'] > 0 ? 'text-error font-semibold' : 'text-slate-600 dark:text-navy-200';
              ?>
                <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500 hover:bg-slate-50 dark:hover:bg-navy-600/50">
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= $index++ ?></td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    <div class="flex items-center space-x-3">
                      <div class="avatar flex size-10">
                        <img class="mask is-squircle" src="<?= htmlspecialchars($farmer['avatar'] ?? '../images/avatars/default.jpg') ?>" alt="avatar">
                      </div>
                      <span class="font-medium text-slate-700 dark:text-navy-100">
                        <?= htmlspecialchars($farmer['name']) ?>
                      </span>
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-slate-600 dark:text-navy-200">
                    <?= htmlspecialchars($farmer['phone']) ?><br>
                    <?= htmlspecialchars($farmer['location'] ?? '—') ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-center font-medium">
                    <?= $farmer['total_loans'] ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-medium">
                    UGX <?= number_format($farmer['total_borrowed']) ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-medium text-success">
                    UGX <?= number_format($farmer['total_repaid']) ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-right">
                    <span class="<?= $repay_class ?> font-semibold">
                      <?= number_format($farmer['repayment_percent'], 1) ?>%
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-center <?= $overdue_class ?>">
                    <?= $farmer['overdue_count'] > 0 ? $farmer['overdue_count'] : '—' ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    <?= $farmer['last_loan_date'] ? date('d M Y', strtotime($farmer['last_loan_date'])) : '—' ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-right">
                    <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                      <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                        </svg>
                      </button>
                      <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                        <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                          <ul>
                            <li>
                              <a href="farmer-profile.php?id=<?= $farmer['id'] ?>" class="flex h-8 items-center px-3 pr-8 ...">
                                View Profile
                              </a>
                            </li>
                            <li>
                              <a href="#" class="flex h-8 items-center px-3 pr-8 text-primary ...">
                                Send Reminder
                              </a>
                            </li>
                            <?php if ($farmer['overdue_count'] > 0): ?>
                              <li>
                                <a href="#" class="flex h-8 items-center px-3 pr-8 text-error ...">
                                  Mark Action Taken
                                </a>
                              </li>
                            <?php endif; ?>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
              <?php if ($borrowers_result->num_rows === 0): ?>
                <tr>
                  <td colspan="10" class="py-10 text-center text-slate-500 dark:text-navy-300">
                    No borrowers found yet.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination placeholder - paste your original pagination code here -->
        <div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
          <!-- ... your pagination / entries selector ... -->
        </div>
      </div>
    </div>
  </div>
</main>