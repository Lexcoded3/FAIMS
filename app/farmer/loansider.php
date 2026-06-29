<?php
// Calculate sidebar specific metrics
 $activeLoan = reset($activeLoans); // Get first active loan if exists
 $nextDue = $activeLoan['first_repayment_due'] ?? null;
 $nextDueAmount = floatval($activeLoan['total_repayable'] ?? 0) - floatval($activeLoan['total_paid'] ?? 0);

 $progressPercent = 0;
if (!empty($activeLoan) && floatval($activeLoan['total_repayable']) > 0) {
    $progressPercent = (floatval($activeLoan['total_paid']) / floatval($activeLoan['total_repayable'])) * 100;
}
?>        <!-- Sidebar Panel -->
        <div class="sidebar-panel" x-data="{ currentPath: window.location.pathname }">
          <div class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750">
            <!-- Sidebar Panel Header -->
            <div class="flex h-18 w-full items-center justify-between pl-4 pr-1">
              <div class="flex items-center">
                <div class="avatar mr-3 hidden size-9 lg:flex">
                  <div class="is-initial rounded-full bg-secondary/10 text-secondary dark:bg-secondary-light/10 dark:text-secondary-light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"></path>
                    </svg>
                  </div>
                </div>
                <p class="text-lg font-medium tracking-wider text-slate-800 line-clamp-1 dark:text-navy-100">
                  LOANS
                </p>
              </div>
              <button @click="$store.global.isSidebarExpanded = false" class="btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:text-accent-light/80 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 xl:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
              </button>
            </div>

            <!-- Sidebar Panel Body -->
            <div class="flex h-[calc(100%-4.5rem)] grow flex-col">
              <div class="is-scrollbar-hidden grow overflow-y-auto">
                <div class="mt-2 px-4">
                  <button class="btn w-full space-x-2 rounded-full border border-slate-200 py-2 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-500 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span> Lock Screen</span>
                  </button>
                </div>
                <!-- Outstanding Balance Card -->
                <div class="rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 p-5 text-white dark:from-navy-600 dark:to-navy-800 shadow-lg">
                  <p class="text-xs text-slate-300 uppercase tracking-wide">Outstanding Balance</p>
                  <p class="text-2xl font-bold mt-2">UGX <?= number_format($totalOwed - $totalPaid) ?></p>
                  
                  <?php if (!empty($activeLoan)): ?>
                    <div class="mt-4">
                      <div class="flex justify-between text-xs text-slate-300 mb-1">
                        <span>Repaid</span>
                        <span><?= round($progressPercent, 1) ?>%</span>
                      </div>
                      <div class="w-full bg-slate-700 rounded-full h-2 dark:bg-navy-500">
                        <div class="bg-success h-2 rounded-full transition-all duration-500" style="width: <?= $progressPercent ?>%"></div>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>

                <div x-data="{expanded:true}">
                  <div class="mt-4 flex items-center justify-between px-4">
                    <span class="text-xs font-medium uppercase">Pages </span>
                    <div class="-mr-1.5 flex">
                      <button class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                      </button>
                      <button @click="expanded =! expanded" class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" :class="expanded && 'rotate-180'" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div x-show="expanded" x-collapse="">
                    <ul class="mt-1 space-y-1.5 px-2 font-inter text-xs+ font-medium">
                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="loans.php" :class="currentPath.includes('loans.php') ? 'bg-slate-200 dark:bg-navy-500' : ''">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-secondary dark:text-secondary-light" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                          </svg>
                          <span>Overview</span>
                        </a>
                      </li>
                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="loan_applications.php" :class="currentPath.includes('loan_applications.php') ? 'bg-slate-200 dark:bg-navy-500' : ''">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-dark dark:text-current-light" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"></path>
                          </svg>
                          <span>Loan Applications</span>
                        </a>
                      </li>
                      <!-- <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="#">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"></path>
                          </svg>
                          <span>Active loans</span>
                        </a>
                      </li> -->
                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="loan_repayments.php" :class="currentPath.includes('loan_repayments.php') ? 'bg-slate-200 dark:bg-navy-500' : ''">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                          </svg>
                          <span>Repayments History</span>
                        </a>
                      </li>

                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="loan_products.php" :class="currentPath.includes('loan_products.php') ? 'bg-slate-200 dark:bg-navy-500' : ''">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.621 9.879a3 3 0 0 0-5.02 2.897l.164.609a4.5 4.5 0 0 1-.108 2.676l-.157.439.44-.22a2.863 2.863 0 0 1 2.185-.155c.72.24 1.507.184 2.186-.155L15 18M8.25 15.75H12m-1.5-13.5H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                          </svg>
                          <span>Loan Plans/Products</span>
                        </a>
                      </li>
                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="loan_borrowers.php" :class="currentPath.includes('loan_borrowers.php') ? 'bg-slate-200 dark:bg-navy-500' : ''">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-primary" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0 0 12 2.25Z" />
                          </svg>
                          <span>Loan Calculator</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
                <!-- Next Payment Due -->
                  <div class="card p-4 border border-slate-200 dark:border-navy-600">
                    <p class="text-xs+ text-slate-400 dark:text-navy-300 flex items-center">
                      <svg class="size-4 mr-1.5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      Next Payment Due
                    </p>
                    <?php if($nextDue): ?>
                      <p class="text-lg font-bold text-slate-800 dark:text-navy-100 mt-1">
                        <?= date('M d, Y', strtotime($nextDue)) ?>
                      </p>
                      <p class="text-sm text-slate-500 dark:text-navy-300 mt-0.5">
                        UGX <?= number_format($nextDueAmount) ?> remaining
                      </p>
                    <?php else: ?>
                      <p class="text-sm text-slate-500 dark:text-navy-300 mt-1">No active dues</p>
                    <?php endif; ?>
                  </div>

              </div>

              <!-- Sidebar Footer -->
              <div class="flex items-center space-x-3 p-4 border-t border-slate-200 dark:border-navy-600">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-success dark:bg-accent">
                  <i class="fas fa-headset text-white text-lg"></i>
                </div>
                <div>
                  <p class="font-medium text-sm text-slate-700 dark:text-navy-100">
                    Need Help?
                  </p>
                  <p class="text-xs text-slate-400 dark:text-navy-300">
                    Chat with our loan officers
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>