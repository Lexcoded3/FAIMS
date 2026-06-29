<?php
session_start();
$required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';

$admin_id = $_SESSION['id'];

// Optional: search filter
$search = $_GET['search'] ?? '';

// Fetch users (for reference if needed)
if ($search) {
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, role, status, location, created_at
        FROM users
        WHERE name LIKE ? OR email LIKE ?
        ORDER BY created_at DESC
    ");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $userQuery = $stmt->get_result();
} else {
    $userQuery = $conn->query("
        SELECT id, name, email, phone, role, status, location, created_at
        FROM users
        ORDER BY created_at DESC
    ");
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Add User</title>
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

  <body x-data="" x-bind="$store.global.documentBody" class="is-header-blur is-sidebar-open">
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
        <?php include 'userssider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
     <main class="main-content w-full px-[var(--margin-x)] pb-8">
      
        <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6 mt-6">
          <div class="col-span-12 grid lg:col-span-8">
            <div class="card">
              <div class="border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                <div class="flex items-center space-x-2">
                  <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 p-1 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                    <i class="fa-solid fa-layer-group"></i>
                  </div>
                  <h4 class="text-lg font-medium text-slate-700 dark:text-navy-100">
                    Bio Data
                  </h4>
                </div>
              </div>
              <div class="space-y-4 p-4 sm:p-5">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:gap-8">
                            <!-- Name -->
                            <label class="block flex-1 group">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">User's name</span>
                                <input id="input-name"
                                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all"
                                    placeholder="Enter full name (e.g. Alex Joe)"
                                    type="text"
                                    required
                                >
                                <span class="text-xs text-red-500 mt-1 hidden" id="error-name"></span>
                            </label>

                            <!-- Email -->
                            <label class="block flex-1 group">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Email address</span>
                                <input id="input-email"
                                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all"
                                    placeholder="Enter email (e.g. alex@example.com)"
                                    type="email"
                                    required
                                >
                                <span class="text-xs text-red-500 mt-1 hidden" id="error-email"></span>
                            </label>
                        </div>
                        <!-- Section 2: Additional Info -->
                    <div class="space-y-4 pt-2" id="section-additional">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Role -->
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Role</span>
                                <div class="relative">
                                    <select id="input-role"
                                        class="mt-1.5 w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all cursor-pointer"
                                        required
                                    >
                                        <option value="" disabled selected>-- Select role --</option>
                                        <option value="admin">Admin</option>
                                        <option value="farmer">Farmer</option>
                                        <option value="buyer">Buyer</option>
                                        <option value="extension">Extension Officer</option>
                                    </select>
                                </div>
                            </label>

                            <!-- Phone -->
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Phone</span>
                                <input id="input-phone"
                                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all"
                                    placeholder="+256 712 345 678"
                                    type="tel"
                                    required
                                >
                                <span class="text-xs text-red-500 mt-1 hidden" id="error-phone"></span>
                            </label>
                        </div>

                        <!-- Company Name & Location -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Company Name (optional)</span>
                                <input id="input-company"
                                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all"
                                    placeholder="e.g. Green Farm Ltd"
                                    type="text"
                                >
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Location Name</span>
                                <input id="input-location-name"
                                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all"
                                    placeholder="e.g. Kampala Central"
                                    type="text"
                                >
                            </label>
                        </div>

                        <!-- TIN & Business Type -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">TIN (optional)</span>
                                <input id="input-tin"
                                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all"
                                    placeholder="Tax ID"
                                    type="text"
                                >
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Business Type (optional)</span>
                                <select id="input-business-type"
                                    class="mt-1.5 w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all cursor-pointer"
                                >
                                    <option value="">-- Select type --</option>
                                    <option value="wholesaler">Wholesaler</option>
                                    <option value="processor">Processor</option>
                                    <option value="exporter">Exporter</option>
                                    <option value="retailer">Retailer</option>
                                    <option value="cooperative">Cooperative</option>
                                    <option value="other">Other</option>
                                </select>
                            </label>
                        </div>

                        <!-- Preferred Districts -->
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Preferred Districts (optional)</span>
                            <input id="input-districts"
                                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all"
                                placeholder="e.g. Kampala, Wakiso, Entebbe"
                                type="text"
                            >
                        </label>
                    </div>

                <!-- Section 3: Images -->
                    <div class="pt-2" id="section-images">
                        <span class="text-sm font-medium text-slate-700 dark:text-navy-100 block mb-2">Profile / Supporting Images (optional)</span>
                        
                        <!-- Custom File Drop Zone -->
                        <div id="file-drop-zone" class="custom-file-drop relative group cursor-pointer rounded-xl p-6 flex flex-col items-center justify-center text-center transition-colors min-h-[150px]">
                            <input type="file" name="user_images[]" id="input-file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            
                            <div class="bg-primary/10 dark:bg-accent-light/10 rounded-full p-3 mb-3 text-primary dark:text-accent-light transition-transform group-hover:scale-110">
                                <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                                <span class="text-primary dark:text-accent-light">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-slate-400 mt-1">SVG, PNG, JPG or GIF (max. 800x400px)</p>
                            
                            <!-- File List Container -->
                            <div id="file-list" class="mt-4 w-full max-w-xs text-left hidden">
                                <!-- JS will populate this -->
                            </div>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="flex justify-center space-x-3 pt-6 border-t border-slate-100 dark:border-navy-700">
                        <button type="button" id="btn-clear" class="btn flex items-center space-x-2 rounded-lg border border-transparent bg-slate-150 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200 hover:text-slate-800 focus:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-200/50 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90 transition-all">
                            <i class="fa-solid fa-trash-can text-red-500"></i>
                            <span>Clear</span>
                        </button>
                        <button type="button" id="btn-save" class="btn flex items-center space-x-2 rounded-lg border border-transparent bg-primary px-6 py-2 text-sm font-medium text-white hover:bg-primary-focus focus:bg-primary-focus focus:outline-none focus:ring-2 focus:ring-primary/50 active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 transition-all shadow-md shadow-primary/20 dark:shadow-accent/20">
                            <span>Save Profile</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                
              </div>
            </div>
          </div>
          <div class="col-span-12 grid lg:col-span-4 lg:place-items-center">
            <div>
              <ol class="steps is-vertical line-space [--size:2.75rem] [--line:.5rem]">
  <!-- Step 1: General -->
  <li class="step space-x-4 pb-12 before:bg-slate-200 dark:before:bg-navy-500" id="step-item-1">
    <div id="step-icon-1" class="step-header mask is-hexagon flex h-11 w-11 items-center justify-center bg-slate-200 text-slate-500 transition-all dark:bg-navy-500 dark:text-navy-100">
      <i class="fa-solid fa-layer-group text-base"></i>
    </div>
    <div class="text-left">
      <p class="text-xs text-slate-400 dark:text-navy-300">Step 1</p>
      <h3 id="step-text-1" class="text-base font-medium text-slate-700 dark:text-navy-100 transition-colors">
        General
      </h3>
    </div>
  </li>

  <!-- Step 2: Additional -->
  <li class="step space-x-4 pb-12 before:bg-slate-200 dark:before:bg-navy-500" id="step-item-2">
    <div id="step-icon-2" class="step-header mask is-hexagon flex h-11 w-11 items-center justify-center bg-slate-200 text-slate-500 transition-all dark:bg-navy-500 dark:text-navy-100">
      <i class="fa-solid fa-list text-base"></i>
    </div>
    <div class="text-left">
      <p class="text-xs text-slate-400 dark:text-navy-300">Step 2</p>
      <h3 id="step-text-2" class="text-base font-medium text-slate-700 dark:text-navy-100 transition-colors">
        Additional
      </h3>
    </div>
  </li>

  <!-- Step 3: Image -->
  <li class="step space-x-4 pb-12 before:bg-slate-200 dark:before:bg-navy-500" id="step-item-3">
    <div id="step-icon-3" class="step-header mask is-hexagon flex h-11 w-11 items-center justify-center bg-slate-200 text-slate-500 transition-all dark:bg-navy-500 dark:text-navy-100">
      <i class="fa-solid fa-image text-base"></i>
    </div>
    <div class="text-left">
      <p class="text-xs text-slate-400 dark:text-navy-300">Step 3</p>
      <h3 id="step-text-3" class="text-base font-medium text-slate-700 dark:text-navy-100 transition-colors">
        Image
      </h3>
    </div>
  </li>

  <!-- Step 4: Confirm – always green when reached -->
  <li class="step space-x-4 before:bg-slate-200 dark:before:bg-navy-500" id="step-item-4">
    <div id="step-icon-4" class="step-header mask is-hexagon flex h-11 w-11 items-center justify-center bg-slate-200 text-slate-500 transition-all dark:bg-navy-500 dark:text-navy-100">
      <i class="fa-solid fa-check text-base"></i>
    </div>
    <div class="text-left">
      <p class="text-xs text-slate-400 dark:text-navy-300">Step 4</p>
      <h3 id="step-text-4" class="text-base font-medium text-slate-700 dark:text-navy-100 transition-colors">
        Confirm
      </h3>
    </div>
  </li>
</ol>
            </div>
          </div>          
        </div>
      </main>
    </div>
    <!-- Teleport target for Alpine.js -->
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
    <!-- Application Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // --- State Management ---
            const state = {
                visited: {
                    general: false,
                    additional: false,
                    images: false
                },
                isConfirmReady: false,
                values: {
                    name: '',
                    email: '',
                    role: '',
                    phone: '',
                    company_name: '',
                    location_name: '',
                    tin: '',
                    business_type: '',
                    preferred_districts: '',
                    files: []
                },
                touched: {
                    name: false,
                    email: false,
                    phone: false
                },
                errors: {
                    name: '',
                    email: '',
                    phone: ''
                }
            };

            // --- DOM Elements ---
            const inputs = {
                name: document.getElementById('input-name'),
                email: document.getElementById('input-email'),
                role: document.getElementById('input-role'),
                phone: document.getElementById('input-phone'),
                company: document.getElementById('input-company'),
                locationName: document.getElementById('input-location-name'),
                tin: document.getElementById('input-tin'),
                businessType: document.getElementById('input-business-type'),
                districts: document.getElementById('input-districts'),
                file: document.getElementById('input-file'),
                dropZone: document.getElementById('file-drop-zone'),
                fileList: document.getElementById('file-list')
            };

            const errors = {
                name: document.getElementById('error-name'),
                email: document.getElementById('error-email'),
                phone: document.getElementById('error-phone')
            };

            const buttons = {
                clear: document.getElementById('btn-clear'),
                save: document.getElementById('btn-save')
            };

            const steps = [
                { id: 1, icon: document.getElementById('step-icon-1'), text: document.getElementById('step-text-1'), item: document.getElementById('step-item-1') },
                { id: 2, icon: document.getElementById('step-icon-2'), text: document.getElementById('step-text-2'), item: document.getElementById('step-item-2') },
                { id: 3, icon: document.getElementById('step-icon-3'), text: document.getElementById('step-text-3'), item: document.getElementById('step-item-3') },
                { id: 4, icon: document.getElementById('step-icon-4'), text: document.getElementById('step-text-4'), item: document.getElementById('step-item-4') },
            ];

            // --- URL Param Logic ---
            const urlParams = new URLSearchParams(window.location.search);
            const roleParam = urlParams.get('role');
            if (roleParam && ['admin', 'farmer', 'buyer', 'extension'].includes(roleParam)) {
                inputs.role.value = roleParam;
                state.values.role = roleParam;
            }

            // --- Validators ---
            const validate = {
                generic: (val) => {
                    if (!val.trim()) return 'This field is required';
                    return '';
                },
                email: (val) => {
                    if (!val.trim()) return 'This field is required';
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                        return 'Please enter a valid email address';
                    }
                    return '';
                },
                phone: (val) => {
                    if (!val.trim()) return 'This field is required';
                    if (!/\+?[0-9\s\-]{9,15}/.test(val)) {
                        return 'Please enter a valid phone number';
                    }
                    return '';
                }
            };

            // --- UI Updates ---
            function setInputStatus(input, errorEl, error) {
                const baseClasses = "form-input mt-1.5 w-full rounded-lg border px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none transition-all dark:bg-navy-900 ";
                
                input.className = baseClasses;

                if (state.touched[input.id.split('-')[1]]) {
                    if (error) {
                        input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20');
                        input.classList.remove('border-slate-300', 'focus:border-primary', 'focus:ring-primary/20', 'border-green-500', 'focus:border-green-500', 'focus:ring-green-500/20');
                        errorEl.textContent = error;
                        errorEl.classList.remove('hidden');
                    } else {
                        input.classList.add('border-green-500', 'focus:border-green-500', 'focus:ring-green-500/20');
                        input.classList.remove('border-slate-300', 'focus:border-primary', 'focus:ring-primary/20', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20');
                        errorEl.classList.add('hidden');
                    }
                } else {
                    input.classList.add('border-slate-300', 'hover:border-slate-400', 'focus:border-primary', 'focus:ring-primary/20', 'dark:border-navy-450', 'dark:hover:border-navy-400', 'dark:focus:border-accent', 'dark:focus:ring-accent/20');
                    errorEl.classList.add('hidden');
                }
            }

            function renderSteps() {
                const isGeneralActive = state.visited.general && !state.visited.additional;
                const isGeneralDone = state.visited.additional;

                const isAdditionalActive = state.visited.additional && !state.visited.images;
                const isAdditionalDone = state.visited.images || state.isConfirmReady;

                const isImagesActive = state.visited.images && !state.isConfirmReady;
                const isImagesDone = state.isConfirmReady;

                const isConfirmDone = state.isConfirmReady;

                const applyStyle = (stepObj, isActive, isDone) => {
                    const { icon, text, item } = stepObj;
                    const activeColorClass = 'bg-primary dark:bg-accent';
                    const activeTextClass = 'text-primary dark:text-accent-light';
                    const doneColorClass = 'bg-emerald-500 dark:bg-emerald-600';
                    const doneTextClass = 'text-emerald-600 dark:text-emerald-400';

                    icon.className = 'step-header mask is-hexagon flex h-11 w-11 items-center justify-center text-white transition-all';
                    text.className = 'text-base font-medium text-slate-700 dark:text-navy-100 transition-colors';
                    item.classList.remove('is-completed');

                    if (isDone) {
                        icon.classList.add(...doneColorClass.split(' '));
                        text.classList.add(...doneTextClass.split(' '));
                        item.classList.add('is-completed');
                    } else if (isActive) {
                        icon.classList.add(...activeColorClass.split(' '));
                        text.classList.add(...activeTextClass.split(' '));
                    } else {
                        icon.classList.add('bg-slate-200', 'text-slate-500', 'dark:bg-navy-500', 'dark:text-navy-100');
                    }
                };

                applyStyle(steps[0], isGeneralActive, isGeneralDone);
                applyStyle(steps[1], isAdditionalActive, isAdditionalDone);
                applyStyle(steps[2], isImagesActive, isImagesDone);
                applyStyle(steps[3], false, isConfirmDone);
            }

            // --- Event Handlers ---
            function touchGeneral() {
                if (!state.visited.general) {
                    state.visited.general = true;
                    renderSteps();
                }
            }

            function touchAdditional() {
                if (!state.visited.additional) {
                    state.visited.additional = true;
                    renderSteps();
                }
            }

            function touchImages() {
                if (!state.visited.images) {
                    state.visited.images = true;
                    state.isConfirmReady = true;
                    renderSteps();
                }
            }

            // Input Binding Helper
            function bindInput(inputKey, validationFn, errorKey, sectionHandler) {
                const inputEl = inputs[inputKey];
                
                inputEl.addEventListener('focus', sectionHandler);
                inputEl.addEventListener('blur', () => {
                    state.touched[inputKey] = true;
                    const err = validationFn(inputEl.value);
                    state.errors[errorKey] = err;
                    state.values[inputKey] = inputEl.value;
                    setInputStatus(inputEl, errors[errorKey], err);
                });

                inputEl.addEventListener('input', () => {
                    state.values[inputKey] = inputEl.value;
                    if (state.touched[inputKey]) {
                        const err = validationFn(inputEl.value);
                        state.errors[errorKey] = err;
                        setInputStatus(inputEl, errors[errorKey], err);
                    }
                });
            }

            // Bind Fields
            bindInput('name', validate.generic, 'name', touchGeneral);
            bindInput('email', validate.email, 'email', touchGeneral);
            bindInput('phone', validate.phone, 'phone', touchAdditional);

            inputs.role.addEventListener('focus', touchAdditional);
            inputs.role.addEventListener('change', (e) => state.values.role = e.target.value);

            inputs.locationName.addEventListener('focus', touchAdditional);
            inputs.locationName.addEventListener('input', (e) => state.values.location_name = e.target.value);

            // Optional fields
            inputs.company.addEventListener('input', (e) => state.values.company_name = e.target.value);
            inputs.tin.addEventListener('input', (e) => state.values.tin = e.target.value);
            inputs.businessType.addEventListener('change', (e) => state.values.business_type = e.target.value);
            inputs.districts.addEventListener('input', (e) => state.values.preferred_districts = e.target.value);

            // File Handling
            function updateFileList() {
                const files = Array.from(inputs.file.files);
                if (files.length > 0) {
                    inputs.fileList.classList.remove('hidden');
                    inputs.fileList.innerHTML = files.map(f => `
                        <div class="flex items-center justify-between bg-white dark:bg-navy-900 p-2 rounded border border-slate-200 dark:border-navy-600 mb-1 shadow-sm text-xs text-slate-700 dark:text-slate-300">
                            <span class="truncate max-w-[150px]"><i class="fa-regular fa-file-image mr-2"></i>${f.name}</span>
                            <span class="text-slate-400">${(f.size / 1024).toFixed(1)} KB</span>
                        </div>
                    `).join('');
                    
                    inputs.dropZone.classList.add('bg-slate-50', 'dark:bg-navy-800');
                } else {
                    inputs.fileList.classList.add('hidden');
                    inputs.dropZone.classList.remove('bg-slate-50', 'dark:bg-navy-800');
                }
            }

            inputs.file.addEventListener('focus', touchImages);
            inputs.file.addEventListener('change', () => {
                state.values.files = Array.from(inputs.file.files);
                updateFileList();
            });

            // Drag & Drop
            inputs.dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                inputs.dropZone.classList.add('bg-primary/5');
            });

            inputs.dropZone.addEventListener('dragleave', () => {
                inputs.dropZone.classList.remove('bg-primary/5');
            });

            inputs.dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                inputs.dropZone.classList.remove('bg-primary/5');
                inputs.file.files = e.dataTransfer.files;
                state.values.files = Array.from(inputs.file.files);
                updateFileList();
                touchImages();
            });

            // Button Actions
            buttons.clear.addEventListener('click', () => {
                document.querySelectorAll('input').forEach(i => i.value = '');
                document.querySelectorAll('select').forEach(s => s.value = '');
                inputs.file.value = '';
                updateFileList();
                
                state.values = { 
                    name: '', email: '', role: '', phone: '', 
                    company_name: '', location_name: '', tin: '', 
                    business_type: '', preferred_districts: '', files: [] 
                };
                state.touched = { name: false, email: false, phone: false };
                state.errors = { name: '', email: '', phone: '' };
                state.visited = { general: false, additional: false, images: false };
                state.isConfirmReady = false;
                
                Object.keys(errors).forEach(key => {
                    if(errors[key]) setInputStatus(inputs[key.split('-')[1]] || inputs[key], errors[key], '');
                });
                
                renderSteps();
                showToast('Form cleared', 'neutral');
            });

            buttons.save.addEventListener('click', () => {
                // Validate
                state.touched.name = true;
                state.touched.email = true;
                state.touched.phone = true;
                
                state.errors.name = validate.generic(inputs.name.value);
                state.errors.email = validate.email(inputs.email.value);
                state.errors.phone = validate.phone(inputs.phone.value);

                setInputStatus(inputs.name, errors.name, state.errors.name);
                setInputStatus(inputs.email, errors.email, state.errors.email);
                setInputStatus(inputs.phone, errors.phone, state.errors.phone);

                const hasErrors = Object.values(state.errors).some(e => e !== '');
                const missingFields = !inputs.role.value || !inputs.locationName.value;

                if (hasErrors || missingFields) {
                    showToast('Please fix errors before saving', 'error');
                    return;
                }

                // Prepare FormData
                const formData = new FormData();
                formData.append('name', inputs.name.value);
                formData.append('email', inputs.email.value);
                formData.append('role', inputs.role.value);
                formData.append('phone', inputs.phone.value);
                formData.append('company_name', inputs.company.value || '');
                formData.append('location_name', inputs.locationName.value);
                formData.append('tin', inputs.tin.value || '');
                formData.append('business_type', inputs.businessType.value || '');
                formData.append('preferred_districts', inputs.districts.value || '');

                const files = inputs.file.files;
                for (let i = 0; i < files.length; i++) {
                    formData.append('user_images[]', files[i]);
                }

                // Show loading
                const originalBtnContent = buttons.save.innerHTML;
                buttons.save.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...`;
                buttons.save.disabled = true;

                // Send to PHP
                fetch('create_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        
                        // Success visual
                        buttons.save.innerHTML = `<i class="fa-solid fa-check"></i> <span>Saved</span>`;
                        buttons.save.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                        buttons.save.classList.remove('bg-primary', 'dark:bg-accent');
                        
                        setTimeout(() => {
                            buttons.clear.click();
                            buttons.save.innerHTML = originalBtnContent;
                            buttons.save.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                            buttons.save.classList.add('bg-primary', 'dark:bg-accent');
                            buttons.save.disabled = false;
                        }, 1500);
                    } else {
                        showToast(data.message || 'Error saving user', 'error');
                        buttons.save.innerHTML = originalBtnContent;
                        buttons.save.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error occurred', 'error');
                    buttons.save.innerHTML = originalBtnContent;
                    buttons.save.disabled = false;
                });
            });

            // Toast Notification
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg text-success z-50 animate-fade-in ${
                    type === 'success' ? 'bg-emerald-500' :
                    type === 'error' ? 'bg-red-500' :
                    'bg-slate-500'
                }`;
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }

            // Initial render
            renderSteps();
        });
    </script>
  </body>
</html>