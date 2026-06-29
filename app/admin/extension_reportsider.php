<!-- Sidebar Panel -->
        <div class="sidebar-panel">
          <div class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750">
            <!-- Sidebar Panel Header -->
            <div class="flex h-18 w-full items-center justify-between pl-4 pr-1">
              <div class="flex items-center">
                <div class="avatar mr-3 hidden size-9 lg:flex">
                  <div class="is-initial rounded-full bg-warning/10 text-warning">
                    <svg class="size-5" viewbox="0 0 24 24" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-width="2" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                    </svg>
                  </div>
                </div>
                <p class="text-lg font-medium tracking-wider text-slate-800 dark:text-navy-100">
                  EXT Reports
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span> New Task </span>
                  </button>
                </div>
                <ul class="mt-5 space-y-1.5 px-2 font-inter text-xs+ font-medium">
                  <li>
                    <a class="group flex space-x-2 rounded-lg bg-primary/10 p-2 tracking-wide text-primary outline-none transition-all dark:bg-accent-light/10 dark:text-accent-light" href="extension_reports.php">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"></path>
                      </svg>
                      <span>All reports</span>
                    </a>
                  </li>
                </ul>
                <div class="my-4 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
                    <!-- Quick Type Filters -->
                          <div>
                            <p class="text-xs font-medium uppercase text-slate-400 dark:text-navy-400 mb-3 px-3">Filter by Type</p>
                            <ul class="mt-5 space-y-1.5">
                              <li>
                                <a href="?type=disease" class="group flex items-center justify-between rounded-lg p-1 transition-all hover:bg-slate-100 dark:hover:bg-navy-600 <?= (($_GET['type'] ?? '') == 'disease') ? 'bg-slate-100 dark:bg-navy-600' : '' ?>">
                                  <div class="flex items-center space-x-3">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-error/10 text-error"><i class="fas fa-biohazard text-sm"></i></div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Disease</span>
                                  </div>
                                  <span class="text-xs font-bold text-error"><?= $stats['disease'] ?></span>
                                </a>
                              </li>
                              <li>
                                <a href="?type=yield" class="group flex items-center justify-between rounded-lg p-1 transition-all hover:bg-slate-100 dark:hover:bg-navy-600 <?= (($_GET['type'] ?? '') == 'yield') ? 'bg-slate-100 dark:bg-navy-600' : '' ?>">
                                  <div class="flex items-center space-x-3">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-success/10 text-success"><i class="fas fa-chart-line text-sm"></i></div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Yield</span>
                                  </div>
                                  <span class="text-xs font-bold text-success"><?= $stats['yield'] ?></span>
                                </a>
                              </li>
                              <li>
                                <a href="?type=soil" class="group flex items-center justify-between rounded-lg p-1 transition-all hover:bg-slate-100 dark:hover:bg-navy-600 <?= (($_GET['type'] ?? '') == 'soil') ? 'bg-slate-100 dark:bg-navy-600' : '' ?>">
                                  <div class="flex items-center space-x-3">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-warning/10 text-warning"><i class="fas fa-mountain text-sm"></i></div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Soil</span>
                                  </div>
                                  <span class="text-xs font-bold text-warning"><?= $stats['soil'] ?></span>
                                </a>
                              </li>
                              <li>
                                <a href="?type=water" class="group flex items-center justify-between rounded-lg p-1 transition-all hover:bg-slate-100 dark:hover:bg-navy-600 <?= (($_GET['type'] ?? '') == 'water') ? 'bg-slate-100 dark:bg-navy-600' : '' ?>">
                                  <div class="flex items-center space-x-3">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-info/10 text-info"><i class="fas fa-tint text-sm"></i></div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Water</span>
                                  </div>
                                  <span class="text-xs font-bold text-info"><?= $stats['water'] ?></span>
                                </a>
                              </li>
                            </ul>
                          </div>

                          <div class="my-2 mx-1 h-px bg-slate-200 dark:bg-navy-600"></div>
                                </div>
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400 dark:text-navy-400 mb-3 px-1">Reports by District</p>
                    <div class="space-y-2.5">
                      <?php if(empty($district_counts)): ?>
                        <p class="text-sm text-slate-400 dark:text-navy-300 px-1">No district data available.</p>
                      <?php else: ?>
                        <?php foreach(array_slice($district_counts, 0, 6) as $dist => $count): ?>
                          <?php 
                            $isActiveFilter = (($_GET['district'] ?? '') == $dist) ? 'bg-slate-100 dark:bg-navy-600 font-bold' : '';
                            $barWidth = min(($count / ($stats['total'] ?: 1)) * 100, 100); // Calculate percentage for visual bar
                          ?>
                          <a href="?district=<?= urlencode($dist) ?>" class="block rounded-lg p-2.5 transition-all hover:bg-slate-100 dark:hover:bg-navy-600 <?= $isActiveFilter ?>">
                            <div class="flex items-center justify-between mb-1.5">
                              <span class="text-sm text-slate-700 dark:text-navy-100 truncate pr-2"><?= htmlspecialchars($dist) ?></span>
                              <span class="text-xs font-bold text-slate-500 dark:text-navy-300 shrink-0"><?= $count ?></span>
                            </div>
                            <!-- Mini Progress Bar -->
                            <div class="w-full bg-slate-100 dark:bg-navy-700 rounded-full h-1.5">
                              <div class="bg-primary dark:bg-accent h-1.5 rounded-full" style="width: <?= $barWidth ?>%"></div>
                            </div>
                          </a>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                  <!-- Sidebar Footer -->
                <div class="flex items-center space-x-3 p-4 border-t border-slate-200 dark:border-navy-600">
                  <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-warning dark:bg-accent">
                    <i class="fas fa-file-export text-white text-lg"></i>
                  </div>
                  <div>
                            <a href="#" class="generate-pdf-btn font-medium text-sm text-slate-700 dark:text-navy-100 hover:text-primary dark:hover:text-accent-light">
                              Export Summary
                          </a>
                    <p class="text-xs text-slate-400 dark:text-navy-300">
                      Download PDF report
                    </p>
                  </div>
                </div>
            </div>
          </div>
        </div>
