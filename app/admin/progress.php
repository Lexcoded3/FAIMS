<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bio Data Form Wizard</title>
    
    <!-- Tailwind CSS (via CDN for standalone functionality) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome (via CDN for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Configuration for Tailwind to match specific colors if needed -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        navy: {
                            50: '#f0f4ff',
                            100: '#e0eaff',
                            200: '#c7d6fe',
                            300: '#a5b8fc',
                            400: '#8295f9',
                            450: '#6e7fde',
                            500: '#4f5bb3',
                            600: '#3e4691',
                            700: '#363c75',
                            800: '#2f345e',
                            900: '#282c4d',
                        },
                        accent: {
                            light: '#e0f2fe', // Light sky
                            DEFAULT: '#0ea5e9', // Sky 500
                            focus: '#0284c7', // Sky 600
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Styles for Hexagon Mask and Steps that Tailwind utilities might not cover perfectly without plugins */
        
        .mask.is-hexagon {
            /* Hexagon Clip Path */
            clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Vertical Step Line Logic */
        .steps li:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 2.75rem; /* Matches [--size:2.75rem] */
            bottom: -0.75rem; /* Adjust based on padding */
            left: 1.375rem; /* Center of the hexagon (2.75rem / 2) */
            width: 0.5rem; /* Matches [--line:.5rem] */
            background-color: #e2e8f0; /* Slate-200 */
            border-radius: 999px;
            z-index: 0;
            transition: background-color 0.3s ease;
        }

        .dark .steps li:not(:last-child)::after {
            background-color: #334155; /* Navy-500 approximation for dark mode */
        }

        /* Completed Step Line Logic */
        .steps li.is-completed:not(:last-child)::after {
            background-color: #10b981; /* Emerald-500 */
        }
        
        .dark .steps li.is-completed:not(:last-child)::after {
            background-color: #059669; /* Emerald-600 */
        }

        /* Transitions for form inputs */
        .form-input {
            transition: all 0.2s ease-in-out;
        }

        /* Custom File Upload Area */
        .custom-file-drop {
            background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='12' ry='12' stroke='%23CBD5E1FF' stroke-width='2' stroke-dasharray='12%2c 12' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
            transition: all 0.2s ease;
        }
        
        .custom-file-drop:hover, .custom-file-drop:focus-within {
            background-color: #f8fafc;
            background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='12' ry='12' stroke='%2394A3B8FF' stroke-width='2' stroke-dasharray='12%2c 12' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
        }

        .dark .custom-file-drop {
            background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='12' ry='12' stroke='%23475569FF' stroke-width='2' stroke-dasharray='12%2c 12' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
        }
        .dark .custom-file-drop:hover, .dark .custom-file-drop:focus-within {
            background-color: #1e293b;
            background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='12' ry='12' stroke='%2364748BFF' stroke-width='2' stroke-dasharray='12%2c 12' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
        }

        /* Toast Notification Styles */
        .toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 50;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s, bottom 0.3s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }
        
        .toast.success { background-color: #10b981; }
        .toast.error { background-color: #ef4444; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased dark:bg-navy-900 dark:text-slate-200 min-h-screen flex items-center justify-center p-4 sm:p-8">

    <!-- Main Grid Container -->
    <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6 w-full max-w-7xl mx-auto">
        
        <!-- Left Column: The Form -->
        <div class="col-span-12 grid lg:col-span-8">
            <div class="card bg-white dark:bg-navy-800 rounded-xl shadow-lg border border-slate-200 dark:border-navy-700 overflow-hidden">
                
                <!-- Card Header -->
                <div class="border-b border-slate-200 dark:border-navy-700 p-4 sm:px-5 bg-slate-50/50 dark:bg-navy-800/50">
                    <div class="flex items-center space-x-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-accent-light/10 dark:text-accent-light" style="background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="fa-solid fa-layer-group text-sm"></i>
                        </div>
                        <h4 class="text-lg font-medium text-slate-700 dark:text-navy-100">
                            Bio Data
                        </h4>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="space-y-6 p-4 sm:p-6">
                    
                    <!-- Section 1: General Info -->
                    <div class="space-y-4" id="section-general">
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
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                                        <i class="fa-solid fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </label>

                            <!-- District & Phone Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">District</span>
                                    <input id="input-district"
                                        class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none hover:border-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-450 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent dark:focus:ring-accent/20 transition-all"
                                        placeholder="e.g. Kampala"
                                        type="text"
                                        required
                                    >
                                </label>

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
                        </div>
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

        <!-- Right Column: Steps Indicator -->
        <div class="col-span-12 grid lg:col-span-4 lg:place-items-center">
            <div class="w-full max-w-xs">
                <ol class="steps is-vertical space-y-0">
                    
                    <!-- Step 1: General -->
                    <li class="step relative flex items-start space-x-4 pb-12" id="step-item-1">
                        <div id="step-icon-1" class="step-header mask is-hexagon flex h-11 w-11 items-center justify-center bg-slate-200 text-slate-500 transition-all dark:bg-navy-500 dark:text-navy-100">
                            <i class="fa-solid fa-layer-group text-base"></i>
                        </div>
                        <div class="text-left pt-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-300">Step 1</p>
                            <h3 id="step-text-1" class="text-base font-medium text-slate-700 dark:text-navy-100 transition-colors">
                                General
                            </h3>
                        </div>
                    </li>

                    <!-- Step 2: Additional -->
                    <li class="step relative flex items-start space-x-4 pb-12" id="step-item-2">
                        <div id="step-icon-2" class="step-header mask is-hexagon flex h-11 w-11 items-center justify-center bg-slate-200 text-slate-500 transition-all dark:bg-navy-500 dark:text-navy-100">
                            <i class="fa-solid fa-list text-base"></i>
                        </div>
                        <div class="text-left pt-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-300">Step 2</p>
                            <h3 id="step-text-2" class="text-base font-medium text-slate-700 dark:text-navy-100 transition-colors">
                                Additional
                            </h3>
                        </div>
                    </li>

                    <!-- Step 3: Image -->
                    <li class="step relative flex items-start space-x-4 pb-12" id="step-item-3">
                        <div id="step-icon-3" class="step-header mask is-hexagon flex h-11 w-11 items-center justify-center bg-slate-200 text-slate-500 transition-all dark:bg-navy-500 dark:text-navy-100">
                            <i class="fa-solid fa-image text-base"></i>
                        </div>
                        <div class="text-left pt-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-300">Step 3</p>
                            <h3 id="step-text-3" class="text-base font-medium text-slate-700 dark:text-navy-100 transition-colors">
                                Image
                            </h3>
                        </div>
                    </li>

                    <!-- Step 4: Confirm -->
                    <li class="step relative flex items-start space-x-4" id="step-item-4">
                        <div id="step-icon-4" class="step-header mask is-hexagon flex h-11 w-11 items-center justify-center bg-slate-200 text-slate-500 transition-all dark:bg-navy-500 dark:text-navy-100">
                            <i class="fa-solid fa-check text-base"></i>
                        </div>
                        <div class="text-left pt-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-300">Step 4</p>
                            <h3 id="step-text-4" class="text-base font-medium text-slate-700 dark:text-navy-100 transition-colors">
                                Confirm
                            </h3>
                        </div>
                    </li>

                </ol>
            </div>
        </div>          
    </div>

    <!-- Toast Notification Container -->
    <div id="toast" class="toast">Action Successful</div>

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
                    district: '',
                    phone: '',
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
                district: document.getElementById('input-district'),
                phone: document.getElementById('input-phone'),
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

            // --- URL Param Logic (Replacing PHP) ---
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
                    // Pattern: Optional +, then digits/spaces/dashes, 9 to 15 chars
                    if (!/\+?[0-9\s\-]{9,15}/.test(val)) {
                        return 'Please enter a valid phone number';
                    }
                    return '';
                }
            };

            // --- UI Updates ---

            function setInputStatus(input, errorEl, error) {
                const baseClasses = "form-input mt-1.5 w-full rounded-lg border px-3 py-2 text-sm placeholder:text-slate-400/70 outline-none transition-all dark:bg-navy-900 ";
                
                // Reset base classes
                input.className = baseClasses;

                if (state.touched[input.id.split('-')[1]]) {
                    if (error) {
                        // Error State
                        input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20');
                        input.classList.remove('border-slate-300', 'focus:border-primary', 'focus:ring-primary/20', 'border-green-500', 'focus:border-green-500', 'focus:ring-green-500/20');
                        errorEl.textContent = error;
                        errorEl.classList.remove('hidden');
                    } else {
                        // Success State
                        input.classList.add('border-green-500', 'focus:border-green-500', 'focus:ring-green-500/20');
                        input.classList.remove('border-slate-300', 'focus:border-primary', 'focus:ring-primary/20', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20');
                        errorEl.classList.add('hidden');
                    }
                } else {
                    // Neutral State
                    input.classList.add('border-slate-300', 'hover:border-slate-400', 'focus:border-primary', 'focus:ring-primary/20', 'dark:border-navy-450', 'dark:hover:border-navy-400', 'dark:focus:border-accent', 'dark:focus:ring-accent/20');
                    errorEl.classList.add('hidden');
                }
            }

            function renderSteps() {
                // Determine State Logic based on User's requirements
                
                // Step 1: General
                const isGeneralActive = state.visited.general && !state.visited.additional;
                const isGeneralDone = state.visited.additional; // Once we move to step 2, step 1 is done

                // Step 2: Additional
                const isAdditionalActive = state.visited.additional && !state.visited.images;
                const isAdditionalDone = state.visited.images || state.isConfirmReady;

                // Step 3: Images
                const isImagesActive = state.visited.images && !state.isConfirmReady;
                const isImagesDone = state.isConfirmReady;

                // Step 4: Confirm
                const isConfirmDone = state.isConfirmReady;

                // Helper to apply styles
                const applyStyle = (stepObj, isActive, isDone, activeColorClass = 'bg-primary dark:bg-accent', activeTextClass = 'text-primary dark:text-accent-light', doneColorClass = 'bg-emerald-500 dark:bg-emerald-600', doneTextClass = 'text-emerald-600 dark:text-emerald-400') => {
                    const { icon, text, item } = stepObj;

                    // Reset
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
                        // Pending
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
                
                inputEl.addEventListener('focus', () => {
                    sectionHandler();
                });

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
            
            // Role & District don't have custom error UI in the snippet, so simpler logic
            inputs.role.addEventListener('focus', touchAdditional);
            inputs.role.addEventListener('change', (e) => state.values.role = e.target.value);

            inputs.district.addEventListener('focus', touchAdditional);
            inputs.district.addEventListener('input', (e) => state.values.district = e.target.value);

            bindInput('phone', validate.phone, 'phone', touchAdditional);

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
                    
                    // Change dropzone visual
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

            // Button Actions
            buttons.clear.addEventListener('click', () => {
                // Reset Form
                document.querySelectorAll('input').forEach(i => i.value = '');
                inputs.file.files = null; // Clear file input
                inputs.role.value = '';
                updateFileList();
                
                // Reset State
                state.values = { name: '', email: '', role: '', district: '', phone: '', files: [] };
                state.touched = { name: false, email: false, phone: false };
                state.errors = { name: '', email: '', phone: '' };
                
                // Reset Visuals
                Object.keys(inputs).forEach(key => {
                    if(key === 'dropZone' || key === 'fileList' || key === 'role') return;
                    const errEl = document.getElementById(`error-${key}`);
                    if(errEl) setInputStatus(inputs[key], errEl, '');
                });
                
                // Keep visited state? Usually clearing a form might reset progress, 
                // but based on "step logic", let's keep it strictly to the user intent.
                // If they want to fully reset, let's reset visited flags too so they can re-play the wizard.
                state.visited.general = false;
                state.visited.additional = false;
                state.visited.images = false;
                state.isConfirmReady = false;
                
                renderSteps();
                showToast('Form cleared', 'neutral');
            });

                       // ... (previous code) ...

            buttons.save.addEventListener('click', () => {
                // 1. Trigger client-side validation (Keep existing logic)
                state.touched.name = true;
                state.touched.email = true;
                state.touched.phone = true;
                
                state.errors.name = validate.generic(inputs.name.value);
                state.errors.email = validate.email(inputs.email.value);
                state.errors.phone = validate.phone(inputs.phone.value);

                setInputStatus(inputs.name, errors.name, state.errors.name);
                setInputStatus(inputs.email, errors.email, state.errors.email);
                setInputStatus(inputs.phone, errors.phone, state.errors.phone);

                // Check for empty required fields
                const hasErrors = Object.values(state.errors).some(e => e !== '');
                const missingFields = !inputs.role.value || !inputs.district.value;

                if (hasErrors || missingFields) {
                    showToast('Please fix errors before saving', 'error');
                    return;
                }

                // 2. Prepare Data for PHP
                const formData = new FormData();
                formData.append('name', inputs.name.value);
                formData.append('email', inputs.email.value);
                formData.append('role', inputs.role.value);
                formData.append('district', inputs.district.value);
                formData.append('phone', inputs.phone.value);

                // Append files
                const files = inputs.file.files;
                for (let i = 0; i < files.length; i++) {
                    formData.append('user_images[]', files[i]);
                }

                // 3. Visual Loading State
                const originalBtnContent = buttons.save.innerHTML;
                buttons.save.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...`;
                buttons.save.disabled = true;

                // 4. Send to PHP
                fetch('submit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        
                        // Optional: Reset form on success
                        buttons.clear.click(); 
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while saving.', 'error');
                })
                .finally(() => {
                    // Restore Button
                    buttons.save.innerHTML = originalBtnContent;
                    buttons.save.disabled = false;
                });
            });

            // ... (rest of code) ...

                // Success
                console.log('Form Data Submitted:', state.values);
                showToast('Profile saved successfully!', 'success');
                
                // Visual Feedback on Button
                const originalContent = buttons.save.innerHTML;
                buttons.save.innerHTML = `<i class="fa-solid fa-check"></i> <span>Saved</span>`;
                buttons.save.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                buttons.save.classList.remove('bg-primary', 'dark:bg-accent');
                
                setTimeout(() => {
                    buttons.save.innerHTML = originalContent;
                    buttons.save.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                    buttons.save.classList.add('bg-primary', 'dark:bg-accent');
                }, 2000);
            });

            // --- Toast Notification Logic ---
            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast');
                toast.textContent = message;
                toast.className = 'toast show'; // reset class
                
                if (type === 'success') toast.classList.add('success');
                else if (type === 'error') toast.classList.add('error');
                
                // Hide after 3 seconds
                setTimeout(() => {
                    toast.className = toast.className.replace('show', '');
                }, 3000);
            }
        });
    </script>
</body>
</html>