<?php
session_start();
require 'includes/auth.php';
require __DIR__ .'../../config/db.php';


$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Fetch categories for dropdown
$result = $conn->query("SELECT * FROM forum_categories ORDER BY name ASC");
$categories = [];
while($row = $result->fetch_assoc()) $categories[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
  <head>

    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Post</title>
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

  <body x-data="" class="is-header-blur" x-bind="$store.global.documentBody">
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
              <a href="index.htm.html">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>

            <!-- Main Sections Links -->
            <?php include 'sidenav.php';?>
            

            
          </div>
        </div>

        <!-- Sidebar Panel -->
        <?php include 'indexsider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
       <?php if($_SESSION['role']=='admin'){ ?>
        <?php include '../admin/toprightsidenav.php';?>
         <?php }elseif ($_SESSION['role']=='farmer') { ?>
         <?php include '../farmer/toprightsidenav.php';?> 
           <?php }elseif ($_SESSION['role']=='buyer') { ?>
           <?php include '../buyer/toprightsidenav.php';?> 
             <?php }elseif ($_SESSION['role']=='extension') { ?>
             <?php include '../extension/toprightsidenav.php';?> 
                        <?php }?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="flex flex-col items-center justify-between space-y-4 py-5 sm:flex-row sm:space-y-0 lg:py-6">
          <div class="flex items-center space-x-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewbox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h2 class="text-xl font-medium text-slate-700 line-clamp-1 dark:text-navy-50">
              New Post
            </h2>
          </div>
          <div class="flex justify-center space-x-2">
            <button class="btn min-w-[7rem] border border-slate-300 font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
              Preview
            </button>
            <!-- <button class="btn min-w-[7rem] bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
              Save
            </button> -->
          </div>
        </div>

         <form class="col-span-12 lg:col-span-12" method="POST" action="actions/create_topic.php" enctype="multipart/form-data">
        <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">       
          <div class="col-span-12 lg:col-span-8">
            <div class="card">
              <div class="tabs flex flex-col">
                <div class="is-scrollbar-hidden overflow-x-auto">
                  <div class="border-b-2 border-slate-150 dark:border-navy-500">
                    <div class="tabs-list -mb-0.5 flex">
                      <button class="btn h-14 shrink-0 space-x-2 rounded-none border-b-2 border-primary px-4 font-medium text-primary dark:border-accent dark:text-accent-light sm:px-5">
                        <i class="fa-solid fa-layer-group text-base"></i>
                        <span>General</span>
                      </button>
                      <button class="btn h-14 shrink-0 space-x-2 rounded-none border-b-2 border-transparent px-4 font-medium hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100 sm:px-5">
                        <i class="fa-solid fa-tags text-base"></i>
                        <span>Meta Tags</span>
                      </button>
                      <button class="btn h-14 shrink-0 space-x-2 rounded-none border-b-2 border-transparent px-4 font-medium hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100 sm:px-5">
                        <i class="fa-solid fa-bars-staggered text-base"></i>
                        <span> Keywords </span>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="tab-content p-4 sm:p-5">
                  <div class="space-y-5">
                    <label class="block">
                      <span class="font-medium text-slate-600 dark:text-navy-100">Title</span>
                      <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Enter post title" type="text" name="title">
                    </label>
                    <div>
                      <span class="font-medium text-slate-600 dark:text-navy-100">Post Content</span>
                      <div x-ref="editor" class="h-48 border"></div>
                      <input type="hidden" name="content" x-ref="contentInput">
                    <div>
                      <span class="font-medium text-slate-600 dark:text-navy-100">Post Images</span>
                      <div class="filepond fp-bordered fp-grid mt-1.5 [--fp-grid:2]">
                        <input type="file" name="images[]" multiple="">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-span-12 lg:col-span-4">
            <div class="card space-y-5 p-4 sm:p-5">
                              <label class="block">
                                <span class="font-medium text-slate-600 dark:text-navy-100">Category</span>
                                <select class="mt-1.5 w-full" x-init="$el._x_tom = new Tom($el,{create: false,sortField: {field: 'text',direction: 'asc'}})" name="category_id" required>
                                  <?php foreach($categories as $cat): ?>
                                  <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $category_id)?'selected':'' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                                  <?php endforeach; ?>
                                </select>
                              </label>
                              <?php
                                // Fetch all existing tags
                                $tags = $conn->query("SELECT id, name FROM forum_tags ORDER BY name ASC");

                                // Prepare JS array for Tom Select
                                $tagOptions = [];
                                while($tag = $tags->fetch_assoc()){
                                    $tagOptions[] = [
                                        'value' => $tag['id'], // you can also use id if you prefer
                                        'text' => $tag['name'],
                                    ];
                                }
                                $tagOptionsJson = json_encode($tagOptions);
                                ?>

                              <label class="block">
                                <span class="font-medium text-slate-600 dark:text-navy-100">Tags</span>
                                <input 
                                  class="mt-1.5 w-full" 
                                  placeholder="Enter Tags" 
                                  name="tags[]" 
                                  x-init="
                                      new Tom($el, {
                                          create: true,
                                          plugins: ['remove_button'],
                                          
                                          valueField: 'value',
                                          labelField: 'text',
                                          searchField: 'text',
                                          persist: false,
                                      })
                                  "
                              >
                              </label>
                              <button  type="button" @click="clearForm()" class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                               <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                              </svg>
                              <span> Clear </span>
                              </button>
                              <button type="submit" @click="submitForm()" class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round"  stroke-width="2" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"></path>
                                </svg>
                                <span> Save</span>
                              </button>
            </div>
            </div>
      </form>
          </div>
        </div>
      </main>
    </div>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
</svg>


    <!-- 
        This is a place for Alpine.js Teleport feature 
        @see https://alpinejs.dev/directives/teleport
      -->
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
    <script>
function quillForm(){
    return {
        quill: null,

        init(){
            this.quill = new Quill(this.$refs.editor, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote', 'code-block'],
                        [{ header: 1 }, { header: 2 }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ script: 'sub' }, { script: 'super' }],
                        [{ indent: '-1' }, { indent: '+1' }],
                        [{ direction: 'rtl' }],
                        [{ size: ['small', false, 'large', 'huge'] }],
                        [{ header: [1,2,3,4,5,6,false] }],
                        [{ color: [] }, { background: [] }],
                        [{ font: [] }],
                        [{ align: [] }],
                        ['clean'],
                    ],
                },
                placeholder: 'Enter your content...',
            });
        },

        submitForm(){
            // Copy Quill content to hidden input
            this.$refs.contentInput.value = this.quill.root.innerHTML;
            this.$el.submit(); // normal form submit
        },

        clearForm(){
            this.$el.reset();
            this.quill.setContents([]);
            this.$refs.contentInput.value = '';
        }
    }
}
</script>
  </body>
</html>
