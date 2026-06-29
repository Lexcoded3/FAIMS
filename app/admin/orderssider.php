 <!-- Sidebar Panel -->
        <div class="sidebar-panel">
          <div class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750">
            <!-- Sidebar Panel Header -->
            <div class="flex h-18 w-full items-center justify-between pl-4 pr-1">
              <div class="flex items-center">
                <div class="avatar mr-3 hidden size-9 lg:flex">
                  <div class="is-initial rounded-full bg-secondary/10 text-secondary dark:bg-secondary-light/10 dark:text-secondary-light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"></path>
                    </svg>
                  </div>
                </div>
                <p class="text-lg font-medium tracking-wider text-slate-800 line-clamp-1 dark:text-navy-100">
                  Orders
                </p>
              </div>
              <button @click="$store.global.isSidebarExpanded = false" class="btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:text-accent-light/80 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 xl:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
              </button>
            </div>

            <!-- Sidebar Panel Body -->
            <!-- Sidebar Panel Body -->
                <div class="flex h-[calc(100%-4.5rem)] grow flex-col">
                  <div class="is-scrollbar-hidden grow overflow-y-auto">
                    <!-- Quick Actions -->
                    <div class="mt-2 px-4">
                      <a href="orders.php" class="btn w-full space-x-2 rounded-full border border-primary bg-primary/5 py-2 font-medium text-primary hover:bg-primary/10 focus:bg-primary/10 active:bg-primary/20 dark:border-accent dark:bg-accent/5 dark:text-accent dark:hover:bg-accent/10 dark:focus:bg-accent/10 dark:active:bg-accent/20">
                        <i class="fa-solid fa-list text-base"></i>
                        <span>All Orders</span>
                      </a>
                    </div>

                    <!-- Status Filters -->
                    <div x-data="{ expanded: true }">
                      <div class="mt-4 flex items-center justify-between px-4">
                        <span class="text-xs font-medium uppercase text-slate-500 dark:text-slate-400">Status Filters</span>
                        <button @click="expanded = !expanded" class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                          </svg>
                        </button>
                      </div>
                      <div x-show="expanded" x-collapse="">
                        <ul class="mt-1 space-y-1.5 px-2 font-inter text-xs+ font-medium">
                          <li>
                            <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders.php?status=pending">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-warning transition-colors" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                              </svg>
                              <span>Pending</span>
                            </a>
                          </li>
                          <li>
                            <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders.php?status=completed">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-success transition-colors" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                              </svg>
                              <span>Completed</span>
                            </a>
                          </li>
                          <li>
                            <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders.php?status=cancelled">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-error transition-colors" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                              </svg>
                              <span>Cancelled</span>
                            </a>
                          </li>
                          <li>
                            <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders.php?status=confirmed">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-secondary transition-colors" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                              </svg>
                              <span>Confirmed</span>
                            </a>
                          </li>
                          <li>
                            <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders.php?status=processing">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-info transition-colors" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                              </svg>
                              <span>Processing</span>
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>

                    <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>

                    <!-- Quick Links -->
                    <ul class="space-y-1.5 px-2 font-inter text-xs+ font-medium">
                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders_api.php?action=stats">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-primary transition-colors dark:text-accent-light" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                          </svg>
                          <span class="text-slate-700 dark:text-navy-100">Statistics</span>
                        </a>
                      </li>
                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders_api.php?action=export">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-secondary transition-colors dark:text-secondary-light" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33A3 3 0 0116.5 19.5H6.75z"></path>
                          </svg>
                          <span class="text-slate-700 dark:text-navy-100">Export Data</span>
                        </a>
                      </li>
                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="activity_logs.php?table=orders">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-info transition-colors dark:text-info-light" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                          <span class="text-slate-700 dark:text-navy-100">Activity Log</span>
                        </a>
                      </li>
                    </ul>

                    <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>

                    <!-- Payment Methods -->
                    <div x-data="{ expanded: true }">
                      <div class="mt-2 flex items-center justify-between px-4">
                        <span class="text-xs font-medium uppercase text-slate-500 dark:text-slate-400">Payment Methods</span>
                        <button @click="expanded = !expanded" class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                          </svg>
                        </button>
                      </div>
                      <div x-show="expanded" x-collapse="">
                        <ul class="mt-1 space-y-1.5 px-2 font-inter text-xs+ font-medium">
                          <li>
                            <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders.php?payment=mobile_money">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-success transition-colors" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                              </svg>
                              <span>Mobile Money</span>
                            </a>
                          </li>
                          <li>
                            <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders.php?payment=bank">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-warning transition-colors" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                              </svg>
                              <span>Bank Transfer</span>
                            </a>
                          </li>
                          <li>
                            <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-700 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-200 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="orders.php?payment=cash">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-secondary transition-colors" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                              </svg>
                              <span>Cash Payment</span>
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>

                    <!-- Stats Section -->
                    <div class="mt-4 px-4">
                      <div class="rounded-lg bg-gradient-to-br from-primary/10 to-primary/5 p-4 dark:from-accent/10 dark:to-accent/5">
                        <p class="text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">Today's Orders</p>
                        <p class="mt-2 text-2xl font-bold text-primary dark:text-accent-light">
                          <?php 
                            // Count today's orders
                            $today_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()";
                            $today_result = $conn->query($today_query);
                            $today_count = $today_result->fetch_assoc()['count'] ?? 0;
                            echo $today_count;
                          ?>
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">orders placed</p>
                      </div>

                      <div class="mt-3 rounded-lg bg-gradient-to-br from-success/10 to-success/5 p-4 dark:from-emerald-500/10 dark:to-emerald-500/5">
                        <p class="text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">Today's Revenue</p>
                        <p class="mt-2 text-xl font-bold text-success dark:text-emerald-400">
                          UGX <?php 
                            $revenue_query = "SELECT SUM(amount) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
                            $revenue_result = $conn->query($revenue_query);
                            $revenue = $revenue_result->fetch_assoc()['total'] ?? 0;
                            echo number_format($revenue);
                          ?>
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">completed orders</p>
                      </div>
                    </div>
                  </div>

                  <!-- Footer -->
                  <div class="border-t border-slate-200 p-4 dark:border-navy-500">
                    <div class="flex items-center space-x-3 rounded-lg bg-slate-50 p-3 dark:bg-navy-600">
                      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary dark:bg-accent">
                        <i class="fa-solid fa-chart-bar text-lg text-white"></i>
                      </div>
                      <div>
                        <p class="text-xs font-semibold text-slate-700 dark:text-navy-100">
                          View Dashboard
                        </p>
                        <a href="index.php" class="text-xs text-primary hover:underline dark:text-accent-light">
                          Go to main dashboard →
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
          </div>
        </div>