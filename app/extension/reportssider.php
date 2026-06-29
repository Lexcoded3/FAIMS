<?php
 $extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'submit_report.php';

$edit_id   = (int)($_GET['edit'] ?? 0);
$edit_data = null;
$success   = false;
$errors    = [];

if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM extension_reports WHERE id=$edit_id AND extension_id=$extension_id");
    if (!$res || $res->num_rows === 0) { header('Location: reports.php'); exit; }
    $edit_data = $res->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $district = trim($_POST['district'] ?? '');
    $title    = trim($_POST['title']    ?? '');
    $report   = trim($_POST['report']   ?? '');

    if ($district === '') $errors[] = 'District is required.';
    if ($title === '')    $errors[] = 'Title is required.';
    if ($report === '')   $errors[] = 'Report content is required.';

    if (empty($errors)) {
        $sd = $conn->real_escape_string($district);
        $st = $conn->real_escape_string($title);
        $sr = $conn->real_escape_string($report);
        if ($edit_id > 0) {
            $conn->query("UPDATE extension_reports SET district='$sd',title='$st',report='$sr' WHERE id=$edit_id AND extension_id=$extension_id");
        } else {
            $conn->query("INSERT INTO extension_reports (extension_id,district,title,report) VALUES ($extension_id,'$sd','$st','$sr')");
        }
        $success  = true;
        $edit_data = null;
    }
}

$v_district = $edit_data['district'] ?? ($_POST['district'] ?? '');
$v_title    = $edit_data['title']    ?? ($_POST['title']    ?? '');
$v_report   = $edit_data['report']   ?? ($_POST['report']   ?? '');
if ($success && !$edit_id) { $v_district = $v_title = $v_report = ''; }
?>
<?php
// Get the current page filename (e.g., 'reports.php')
$current_page = basename($_SERVER['PHP_SELF']);
?>

        <div class="sidebar-panel">
          <div class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750">
            <!-- Sidebar Panel Header -->
            <div class="flex h-18 w-full items-center justify-between pl-4 pr-1">
              <div class="flex items-center">
                <div class="avatar mr-3 hidden size-9 lg:flex">
                  <div class="is-initial rounded-full bg-secondary/10 text-secondary dark:bg-secondary-light/10 dark:text-secondary-light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"></path>
                    </svg>
                  </div>
                </div>
                <p class="text-lg font-medium tracking-wider text-slate-800 line-clamp-1 dark:text-navy-100">
                  Reports
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
                    <span> New Report</span>
                  </button>
                  <template x-teleport="#x-teleport-target">
                    <div
                      class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
                      x-show="showModal"
                      role="dialog"
                      @keydown.window.escape="showModal = false"
                      x-data="{ reportLen: <?= strlen($v_report) ?> }">
                    
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
                          <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
                            <?= $edit_id?'Edit report':'Submit field report' ?>
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
                        <div class="px-4 py-4 sm:px-5">
                          <p>
                            <?= $edit_id?'Update your filed report':'Document your field observations' ?>
                          </p>
                          <form method="POST">
                          <div class="mt-4 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                              <label class="block">
                                <span>District</span>
                                <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" id="district" name="district" value="<?= htmlspecialchars($v_district) ?>" placeholder="e.g. Wakiso" required>
                              </label>
                              <label class="block">
                                <span>Report title</span>
                                <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" id="district" type="text" id="title" name="title" value="<?= htmlspecialchars($v_title) ?>" placeholder="e.g. Fall armyworm outbreak in Kakiri" required>
                              </label>
                            </div>
                            <label class="block">
                              <span>Report content:</span>
                              <textarea
                                rows="4"
                                id="report" name="report" @input="reportLen=$el.value.length"
                                class="form-textarea w-full resize-none rounded-lg bg-slate-150 p-2.5 placeholder:text-slate-400 dark:bg-navy-900 dark:placeholder:text-navy-30"
                                placeholder="Describe your observations — affected areas, severity, crop types, recommendations…" required><?= htmlspecialchars($v_report) ?>
                              </textarea>
                              <p class="text-right mt-1" style="font-size:11px;color:#9ca3af"><span x-text="reportLen"></span> characters</p>
                            </label>
                            <!-- Tip -->
                              <div class="space-y-4">
                              <div
                                x-data="{isShow:true}"
                                :class="!isShow && 'opacity-0 transition-opacity duration-300'"
                                class="alert flex items-center justify-between overflow-hidden rounded-lg border border-warning text-warning"
                              >
                                <div class="flex">
                                  <div class="bg-warning p-3 text-white">
                                    <svg
                                      xmlns="http://www.w3.org/2000/svg"
                                      class="size-5"
                                      fill="none"
                                      viewBox="0 0 24 24"
                                      stroke="currentColor"
                                    >
                                      <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                      />
                                    </svg>
                                  </div>
                                  <div class="px-4 py-3 sm:px-5">
                                    <p>Include the sub-county, crop type, estimated % affected. For disease/pest alerts, name the pathogen if known.</p></div>
                                </div>
                                <div class="px-2">
                                  <button
                                    @click="isShow = false; setTimeout(()=>$root.remove(),300)"
                                    class="btn size-7 rounded-full p-0 font-medium text-warning hover:bg-warning/20 focus:bg-warning/20 active:bg-warning/25"
                                  >
                                    <svg
                                      xmlns="http://www.w3.org/2000/svg"
                                      class="size-4"
                                      fill="none"
                                      viewBox="0 0 24 24"
                                      stroke="currentColor"
                                    >
                                      <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                      />
                                    </svg>
                                  </button>
                                </div>
                              </div>
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
                                <?= $edit_id?'Update report':'Submit report' ?>
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
                    <span class="text-xs font-medium uppercase">pages </span>
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
                        <a href="reports.php" 
                           class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all 
                           <?= $current_page == 'reports.php' ? 'bg-slate-100 dark:bg-navy-600 text-primary dark:text-accent-light' : 'text-slate-800 dark:text-navy-100 hover:bg-slate-100 dark:hover:bg-navy-600' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-secondary dark:text-secondary-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"></path>
                            </svg>
                            <span>All Reports</span>
                        </a>
                    </li>
                      <li>
                          <a href="print.php" 
                             class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all 
                             <?= $current_page == 'print.php' ? 'bg-slate-100 dark:bg-navy-600 text-primary dark:text-accent-light' : 'text-slate-800 dark:text-navy-100 hover:bg-slate-100 dark:hover:bg-navy-600' ?>">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"></path>
                              </svg>
                              <span>Print Reports</span>
                          </a>
                      </li>

                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="#">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"></path>
                          </svg>
                          <span>Share</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>

                <ul class="space-y-1.5 px-2 font-inter text-xs+ font-medium">
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="#">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 transition-colors group-hover:text-slate-500 group-focus:text-slate-500 dark:text-navy-300 dark:group-hover:text-navy-200 dark:group-focus:text-navy-200" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"></path>
                      </svg>
                      <span class="text-slate-800 dark:text-navy-100">Top Reports</span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="#">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 transition-colors group-hover:text-slate-500 group-focus:text-slate-500 dark:text-navy-300 dark:group-hover:text-navy-200 dark:group-focus:text-navy-200" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      <span class="text-slate-800 dark:text-navy-100">History</span>
                    </a>
                  </li>
                </ul>
                </div>
                 <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
                <div class="p-4">
                  <p>Total</p>
                  <p class="mt-1 text-base font-medium text-slate-700 dark:text-navy-100">
                    <?= $total ?> report<?= $total!=1?'s':'' ?>
                  </p>
                </div>
            </div>
          </div>
        </div>
      