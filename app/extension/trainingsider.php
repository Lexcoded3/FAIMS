<?php
$edit_id   = (int)($_GET['id'] ?? 0);
$course    = null;
$success   = false;
$errors    = [];

if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM training_courses WHERE id=$edit_id");
    if (!$res || $res->num_rows === 0) { header('Location: training.php'); exit; }
    $course = $res->fetch_assoc();
}

// Fetch lessons for this course (shown in edit mode)
$lessons = [];
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM training_lessons WHERE course_id=$edit_id ORDER BY id ASC");
    while ($r = $res->fetch_assoc()) $lessons[] = $r;
}

// Handle delete lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_lesson') {
    $lid = (int)$_POST['lesson_id'];
    $conn->query("DELETE FROM training_progress WHERE lesson_id=$lid");
    $conn->query("DELETE FROM training_lessons WHERE id=$lid AND course_id=$edit_id");
    header("Location: training_course_edit.php?id=$edit_id&deleted=1"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_course') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category']    ?? '');

    if ($title === '') $errors[] = 'Course title is required.';

    // Thumbnail upload
    $thumb_filename = $course['thumbnail'] ?? null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['thumbnail'];
        $allowed = ['image/jpeg','image/png','image/webp'];
        $max     = 3 * 1024 * 1024;
        if (!in_array($file['type'], $allowed))   $errors[] = 'Thumbnail must be JPG, PNG or WebP.';
        elseif ($file['size'] > $max)              $errors[] = 'Thumbnail must be under 3MB.';
        else {
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = 'thumb_' . time() . '_' . $extension_id . '.' . $ext;
            $dir  = '../uploads/thumbnails/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dir . $name)) $thumb_filename = $name;
            else $errors[] = 'Could not save thumbnail.';
        }
    }

    if (empty($errors)) {
        $st = $conn->real_escape_string($title);
        $sd = $conn->real_escape_string($description);
        $sc = $conn->real_escape_string($category);
        $sf = $conn->real_escape_string($thumb_filename ?? '');

        if ($edit_id > 0) {
            $conn->query("UPDATE training_courses SET title='$st',description='$sd',category='$sc',thumbnail='$sf' WHERE id=$edit_id");
            $success = true;
            $course  = $conn->query("SELECT * FROM training_courses WHERE id=$edit_id")->fetch_assoc();
        } else {
            $conn->query("INSERT INTO training_courses (title,description,category,thumbnail,created_by) VALUES ('$st','$sd','$sc','$sf',$extension_id)");
            $new_id  = $conn->insert_id;
            header("Location: training.php"); exit;
        }
    }
}

$v_title       = $course['title']       ?? ($_POST['title']       ?? '');
$v_description = $course['description'] ?? ($_POST['description'] ?? '');
$v_category    = $course['category']    ?? ($_POST['category']    ?? '');
$thumb_url     = ($course['thumbnail'] ?? '') ? '../uploads/thumbnails/'.htmlspecialchars($course['thumbnail']) : null;

$course_categories = ['agronomy','pest management','irrigation','soil health','business','post-harvest','nutrition','climate'];
?>
   <!-- Sidebar Panel -->
        <div class="sidebar-panel" x-data="{ currentPath: window.location.pathname }">
          <div class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750">
            <!-- Sidebar Panel Header -->
            <div class="flex h-18 w-full items-center justify-between pl-4 pr-1">
              <div class="flex items-center">
                <div class="avatar mr-3 hidden size-9 lg:flex">
                  <div class="is-initial rounded-full bg-secondary/10 text-secondary dark:bg-secondary-light/10 dark:text-secondary-light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605"></path>
                    </svg>
                  </div>
                </div>
                <p class="text-lg font-medium tracking-wider text-slate-800 line-clamp-1 dark:text-navy-100">
                  Training
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                    </svg>
                    <span> New Course</span>
                  </button>
                   <template x-teleport="#x-teleport-target">
                    <div
                      class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
                      x-show="showModal"
                      role="dialog"
                      @keydown.window.escape="showModal = false"
                      x-data="{ thumbPreview: null, dragging: false,
        previewThumb(e){ const f=e.target.files[0]; if(!f) return; const r=new FileReader(); r.onload=ev=>this.thumbPreview=ev.target.result; r.readAsDataURL(f); } }"
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
                          <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
                            New course
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
                            Set up a new training course for farmers
                          </p>
                          <div class="mt-4 space-y-4">
                            <form method="POST" enctype="multipart/form-data">
                             <input type="hidden" name="action" value="save_course">
                             <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block">
                              <span>Course title <span style="color:#A32D2D">*</span></span>
                              <input
                                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                                type="text" id="title" name="title" value="<?= htmlspecialchars($v_title) ?>"
                                   placeholder="e.g. Modern maize farming techniques" required
                              />
                            </label>
                            <label class="block">
                              <span>Course category :</span>
                              <select id="category" name="category"
                                class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent"
                              >
                                <option value="">— Select category —</option>
                                <?php foreach($course_categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($v_category===$cat)?'selected':'' ?>><?= ucfirst($cat) ?></option>
                                <?php endforeach; ?>
                              </select>
                            </label>
                          </div>
                            <label class="block">
                              <span>Description</span>
                              <textarea
                                rows="2"
                                class="form-textarea mt-1.5 w-full resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                              id="description" name="description"
                                      placeholder="What will farmers learn? Who is this course for?"><?= htmlspecialchars($v_description) ?></textarea>
                            </label>                            

                            <!-- Thumbnail -->
                            <div class="bg-navy-600 rounded-xl border border-gray-100 p-5">
                                <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">Course thumbnail</p>

                                <div class="drop-zone p-5 text-center"
                                     :class="dragging?'dragging':''"
                                     @dragover.prevent="dragging=true" @dragleave="dragging=false"
                                     @drop.prevent="dragging=false; previewThumb({target:{files:$event.dataTransfer.files}})"
                                     @click="$refs.thumbInput.click()">
                                    <template x-if="thumbPreview">
                                        <img :src="thumbPreview" class="mx-auto rounded-lg mb-2" style="max-height:100px;max-width:50%;object-fit:cover">
                                    </template>
                                    <template x-if="!thumbPreview">
                                        <?php if($thumb_url && file_exists($thumb_url)): ?>
                                        <img src="<?= $thumb_url ?>" class="mx-auto rounded-lg mb-2" style="max-height:100px;max-width:50%;object-fit:cover">
                                        <?php else: ?>
                                        <div class="py-6">
                                            <svg class="mx-auto mb-2" width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="#9ca3af" stroke-width="1.3"><rect x="3" y="5" width="22" height="16" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M3 18l6-5 4 4 3-3 6 5"/></svg>
                                            <p class="text-xs text-gray-400">Click or drag to upload thumbnail</p>
                                            <p class="text-xs text-gray-400 mt-0.5">JPG, PNG or WebP · max 3MB</p>
                                        </div>
                                        <?php endif; ?>
                                    </template>
                                    <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="hidden" x-ref="thumbInput" @change="previewThumb($event)">
                                </div>
                            </div>
                            <div class="space-x-2 text-right mt-6">
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
                                Create
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
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="training.php" :class="currentPath.includes('training.php','training_lesson_edit.php') ? 'bg-slate-200 dark:bg-navy-500' : ''">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-secondary dark:text-secondary-light" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"  d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5"></path>
                          </svg>
                          <span>Courses</span>
                        </a>
                      </li>
                      <li>
                        <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="training_progress.php" :class="currentPath.includes('training_progress.php') ? 'bg-slate-200 dark:bg-navy-500' : ''">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-dark dark:text-current-light" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" ></path>
                          </svg>
                          <span>Farmer Progress</span>
                        </a>
                      </li>
                      
                    </ul>
                  </div>
                </div>

                <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              </div>

              <!-- <div class="flex items-center space-x-3 p-4">
                <div class="flex h-11 w-7 shrink-0 items-center justify-center rounded-full bg-primary dark:bg-accent">
                  <i class="fa-brands fa-bluetooth-b text-2xl text-white"></i>
                </div>
                <div>
                  <div class="flex items-center space-x-2">
                    <p class="font-medium text-slate-700 dark:text-navy-100">
                      Card reader
                    </p>
                    <div class="flex size-4.5 shrink-0 items-center justify-center rounded-full bg-success text-white">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                      </svg>
                    </div>
                  </div>
                  <p class="text-xs text-slate-400 dark:text-navy-300">
                    Device are connected
                  </p>
                </div>
              </div> -->
            </div>
          </div>
        </div>