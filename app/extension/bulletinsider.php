<?php
// 1. Prevent "Undefined variable" errors
$success = false;
$errors  = [];
$v_title = '';
$v_content = '';
$total = 0; // Set a default value

// 2. Check session safely
$extension_id   = $_SESSION['id'] ?? 0;
$extension_name = $_SESSION['name'] ?? 'Extension Worker';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($conn)) {
    $title    = trim($_POST['title']    ?? '');
    $content  = trim($_POST['content']  ?? '');
    
    if ($title === '')   $errors[] = 'Title is required.';
    if ($content === '') $errors[] = 'Content is required.';

    if (empty($errors)) {
        $st = $conn->real_escape_string($title);
        $sc = $conn->real_escape_string($content);
        $sql = "INSERT INTO posts (user_id, title, content) VALUES ($extension_id, '$st', '$sc')";
        
        if ($conn->query($sql)) {
            $success = true;
        } else {
            $errors[] = "Query failed: " . $conn->error;
        }
    }
}

// Update values for the form fields
if (!$success && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $v_title = $_POST['title'] ?? '';
    $v_content = $_POST['content'] ?? '';
}
?>
        <div class="sidebar-panel">
          <div class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750">
            <!-- Sidebar Panel Header -->
            <div class="flex h-18 w-full items-center justify-between pl-4 pr-1">
              <div class="flex items-center">
                <div class="avatar mr-3 hidden size-9 lg:flex">
                  <div class="is-initial rounded-full bg-success/10 text-success dark:bg-success-light/10 dark:text-success-light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"></path>
                    </svg>
                  </div>
                </div>
                <p class="text-lg font-medium tracking-wider text-slate-800 line-clamp-1 dark:text-navy-100">
                  Bulletins
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
                  <div x-data="{showModal:false}">
                  <button @click="showModal = true" class="btn w-full space-x-2 rounded-full border border-slate-200 py-2 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-500 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                    </svg>
                    <span> New Bulletin</span>
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
                        x-data="{ 
                          title: <?= json_encode($v_title) ?>, 
                          content: <?= json_encode($v_content) ?>, 
                          category: 'General info' 
                        }"
                      >
                        <div
                          class="flex justify-between rounded-t-lg bg-slate-200 px-4 py-3 dark:bg-navy-800 sm:px-5"
                        >
                          <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
                            Post agri bulletin
                          </h3>
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
                        <form method="POST" action="">
                        <div class="px-4 py-4 sm:px-5">
                          <input type="hidden" name="category" :value="category">
                          <div class="flex flex-wrap gap-2 mb-5">
                            <a
                            href="#"
                            class="tag rounded-full border border-primary/30 bg-primary/10 text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:border-accent-light/30 dark:bg-accent-light/10 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25"
                          >
                            General info
                          </a>
                          <a
                            href="#"
                            class="tag rounded-full border border-info/30 bg-info/10 text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25"
                          >
                            Market Info
                          </a>

                          <a
                            href="#"
                            class="tag rounded-full border border-success/30 bg-success/10 text-success hover:bg-success/20 focus:bg-success/20 active:bg-success/25"
                          >
                            Seasonsal advisory
                          </a>

                          <a
                            href="#"
                            class="tag rounded-full border border-warning/30 bg-warning/10 text-warning hover:bg-warning/20 focus:bg-warning/20 active:bg-warning/25"
                          >
                            Best practices
                          </a>

                          <a
                            href="#"
                            class="tag rounded-full border border-error/30 bg-error/10 text-error hover:bg-error/20 focus:bg-error/20 active:bg-error/25"
                          >
                            Disease alert
                          </a>
                          </div>
                          <p>
                            Share disease alerts, best practices, seasonal advice
                          </p>
                          <div class="mt-4 space-y-4">
                            <label class="block">
                              <span>Bulletin title:</span>
                              <input
                                  x-model="title"
                                  name="title"
                                  class="form-input w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                                  placeholder="Bulletin Title"
                                  type="text"
                                />
                            </label>
                            <label class="block">
                              <span>Content:</span>
                              <textarea
                                  x-model="content"
                                  name="content"
                                  rows="4"
                                  placeholder="Write your bulletin content here..."
                                  class="form-textarea w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                                ></textarea>
                            </label>

                            
                            <label class="inline-flex items-center space-x-2">
                             <!--  <input
                                class="form-switch is-outline h-5 w-10 rounded-full border border-slate-400/70 bg-transparent before:rounded-full before:bg-slate-300 checked:border-primary checked:before:bg-primary dark:border-navy-400 dark:before:bg-navy-300 dark:checked:border-accent dark:checked:before:bg-accent"
                                type="checkbox"
                              /> -->
                              <span class="text-xs text-gray-400">Be specific — name crop types, sub-counties, and actionable advice.</span>
                            </label>
                            <!-- Live preview -->
                            <div class="rounded-xl border border-gray-100 p-4 bg-white" x-show="title.length>0||content.length>0">
                                <p class="text-xs text-gray-400 mb-2">Preview</p>
                                <p class="text-sm text-gray-800 leading-snug" style="font-weight:500" x-text="title||'—'"></p>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed" x-text="content?content.substring(0,160)+(content.length>160?'…':''):''"></p>
                                <p class="text-xs text-gray-400 mt-2"><?= htmlspecialchars($extension_name) ?> · <?= date('d M Y') ?></p>
                            </div>
                            <div class="space-x-2 text-right">
                              <button
                                @click="showModal = false"
                                class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90"
                              >
                                Cancel
                              </button>
                              <button
                                type="submit"
                                class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
                              >
                               Publish
                              </button>
                            </form>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
                </div>

                <div x-data="{expanded:true}">
                  <div class="mt-4 flex items-center justify-between px-4">
                    <span class="text-xs font-medium uppercase">channels </span>
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
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="bulletins.php">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-secondary dark:text-secondary-light" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"></path>
                          </svg>
                          <span>All Bulletins</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
                <div class="mt-4 px-4">
                  <p class="text-xs+ font-semibold uppercase text-slate-400 dark:text-navy-300">
                        Quick Filters
                    </p>
                    <ul class="mt-2 space-y-1.5 font-inter font-medium text-slate-600 dark:text-navy-200">
                        <li>
                            <a href="bulletins.php?mine=1" class="group flex items-center space-x-2 rounded-lg px-2 py-1.5 transition-all hover:bg-slate-100 dark:hover:bg-navy-600">
                                <i class="fa-solid fa-user-pen text-sm transition-colors group-hover:text-primary"></i>
                                <span>My Publications</span>
                            </a>
                        </li>
                        <li>
                            <a href="bulletins.php?search=disease" class="group flex items-center space-x-2 rounded-lg px-2 py-1.5 transition-all hover:bg-slate-100 dark:hover:bg-navy-600">
                                <i class="fa-solid fa-virus text-sm text-error"></i>
                                <span>Disease Alerts</span>
                            </a>
                        </li>
                        <li>
                            <a href="bulletins.php?search=price" class="group flex items-center space-x-2 rounded-lg px-2 py-1.5 transition-all hover:bg-slate-100 dark:hover:bg-navy-600">
                                <i class="fa-solid fa-chart-line text-sm text-success"></i>
                                <span>Market Trends</span>
                            </a>
                        </li>
                    </ul>

                    <div class="my-3 h-px bg-slate-150 dark:bg-navy-500"></div>

                    <p class="text-xs+ font-semibold uppercase text-slate-400 dark:text-navy-300">
                        Tools
                    </p>
                    <ul class="mt-2 space-y-1.5 font-inter font-medium text-slate-600 dark:text-navy-200">
                        <li>
                            <a href="bulletin_stats.php" class="group flex items-center space-x-2 rounded-lg px-2 py-1.5 transition-all hover:bg-slate-100 dark:hover:bg-navy-600">
                                <i class="fa-solid fa-gauge-high text-sm"></i>
                                <span>Engagement Stats</span>
                            </a>
                        </li>
                        <li>
                            <button @click="$dispatch('open-modal', {name: 'guidelines'})" class="group flex items-center space-x-2 rounded-lg px-2 py-1.5 transition-all hover:bg-slate-100 dark:hover:bg-navy-600">
                                <i class="fa-solid fa-circle-info text-sm"></i>
                                <span>Writing Guide</span>
                            </button>
                        </li>
                    </ul>
                </div>

              </div>
              <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
                <div class="p-4">
                  <p>Total</p>
                  <p class="mt-1 text-base font-medium text-slate-700 dark:text-navy-100">
                    <?= $total ?> bulletins<?= $total!=1?'s':'' ?>
                  </p>
                </div>
            </div>
          </div>
        </div>