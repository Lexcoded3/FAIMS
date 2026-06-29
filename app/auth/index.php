<?php
session_start(); 

// DB Connection: From app/auth/ go up one level (../) to reach app/config/
require_once '../config/db.php'; 

 $error = "";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in both fields";
    } else {
        $stmt = $conn->prepare("SELECT id, name, role, password, location FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Set Session
                $_SESSION['id']       = $user['id'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['name']     = $user['name'];
                $_SESSION['location'] = $user['location'] ?? '';
                $updateLogin = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateLogin->bind_param("i", $user['id']);
                $updateLogin->execute();
                $updateLogin->close();

                session_regenerate_id(true);

                // --- ROLE SWITCHING (No dashboard.php needed) ---
                // We are currently in app/auth/, so we use ../ to get to app/
                switch ($_SESSION['role']) {
                    case 'farmer':
                        header("Location: ../farmer/index.php?status=success");
                        break;

                    case 'buyer':
                        header("Location: ../buyer/index.php?status=success");
                        break;

                    case 'admin':
                        header("Location: ../admin/index.php?status=success");
                        break;

                    case 'extension':
                        header("Location: ../extension/index.php?status=success");
                        break;

                    default:
                        session_destroy();
                        header("Location: ../auth/");
                }
                exit; // Stop script
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Farmer Access Information Management System</title>
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
      <div class="fixed top-0 hidden p-6 lg:block lg:px-12">
        <a href="#" class="flex items-center space-x-2">
          <img class="size-12 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
          <p class="text-xl font-semibold uppercase text-slate-700 dark:text-navy-100">
            FAIMS - Farmer Access Information Management System
          </p>
        </a>
      </div>
      <div class="hidden w-full place-items-center lg:grid">
        <div class="w-full max-w-lg p-20">
          <!-- <img class="w-full" x-show="!$store.global.isDarkModeEnabled" src="../images/illustrations/dashboard-check.svg" alt="image">
          <img class="w-full" x-show="$store.global.isDarkModeEnabled" src="../images/illustrations/dashboard-check-dark.svg" alt="image"> -->
           <div
                x-init="$nextTick(()=>$el._x_swiper = new Swiper($el, {scrollbar: {el: '.swiper-scrollbar',draggable: true}, navigation: {prevEl: '.swiper-button-prev',nextEl: '.swiper-button-next'},autoplay: {delay: 3000}}))"
                class="swiper rounded-lg"
              >
                <div class="swiper-wrapper">
                  <div class="swiper-slide">
                    <img
                      class="h-full w-full object-cover"
                      src="bg1.jpg"
                      alt=""
                    />
                  </div>
                  <div class="swiper-slide">
                    <img
                      class="h-full w-full object-cover object-top"
                      src="bg3.jpg"
                      alt=""
                    />
                  </div>
                  <div class="swiper-slide">
                    <img
                      class="h-full w-full object-cover object-top"
                      src="bg2.jpg"
                      alt=""
                    />
                  </div>
                  <div class="swiper-slide">
                    <img
                      class="h-full w-full object-cover object-top"
                      src="bg6.jpg"
                      alt=""
                    />
                  </div>
                  <div class="swiper-slide">
                    <img
                      class="h-full w-full object-cover object-center"
                      src="bg7.jpg"
                      alt=""
                    />
                  </div>
                </div>
                <div class="swiper-scrollbar"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
              </div>
        </div>
      </div>
      <main class="flex w-full flex-col items-center bg-white dark:bg-navy-700 lg:max-w-md">
        <div class="flex w-full max-w-sm grow flex-col justify-center p-5">
          <div class="text-center space-x-3">
            <img class="mx-auto size-16 lg:hidden transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
            <div class="mt-4">
              <h2 class="text-2xl font-semibold text-slate-600 dark:text-navy-100">
                Welcome Back
              </h2>
              <p class="text-slate-400 dark:text-navy-300">
                Please sign in to continue
              </p>
            </div>
          </div>
          <?php if(!empty($error)): ?>
             <!-- Added mt-6 mb-2 here to create visual separation -->
             <div class="alert mt-6 mb-2 flex overflow-hidden rounded-lg bg-error/10 text-error dark:bg-error/15">
                <div class="flex flex-1 items-center space-x-3 p-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <div class="flex-1"><?php echo htmlspecialchars($error); ?></div>
                </div>
                <div class="w-1.5 bg-error"></div>
             </div>
          <?php endif; ?>
          <form method="POST" action="">
          <div class="mt-10">
            <label class="relative flex">
              <input class="form-input peer w-full rounded-lg bg-slate-150 px-3 py-2 pl-9 ring-primary/50 placeholder:text-slate-400 hover:bg-slate-200 focus:ring dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300 dark:hover:bg-navy-900 dark:focus:bg-navy-900" placeholder="Username or Email" type="email" name="email" placeholder="Email Address" required>
              <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 transition-colors duration-200" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
              </span>
            </label>
            <label class="relative mt-4 flex">
              <input class="form-input peer w-full rounded-lg bg-slate-150 px-3 py-2 pl-9 ring-primary/50 placeholder:text-slate-400 hover:bg-slate-200 focus:ring dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300 dark:hover:bg-navy-900 dark:focus:bg-navy-900" placeholder="Password"type="password" name="password">
              <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 transition-colors duration-200" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
              </span>
            </label>
            <div class="mt-4 flex items-center justify-between space-x-2">
              <label class="inline-flex items-center space-x-2">
                <input class="form-checkbox is-outline size-5 rounded border-slate-400/70 bg-slate-100 before:bg-primary checked:border-primary hover:border-primary focus:border-primary dark:border-navy-500 dark:bg-navy-900 dark:before:bg-accent dark:checked:border-accent dark:hover:border-accent dark:focus:border-accent" type="checkbox">
                <span class="line-clamp-1">Remember me</span>
              </label>
              <a href="#" class="text-xs text-slate-400 transition-colors line-clamp-1 hover:text-slate-800 focus:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100 dark:focus:text-navy-100">Forgot Password?</a>
            </div>
            <button name="login" type="submit" class="btn mt-10 h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
              Sign In
            </button>
          </form>
            <div class="mt-4 text-center text-xs+">
              <p class="line-clamp-1">
                <span>Don't have Account?</span>

                <a class="text-primary transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent" href="signup.php">Create account</a>
              </p>
            </div>
            <div class="my-7 flex items-center space-x-3">
              <div class="h-px flex-1 bg-slate-200 dark:bg-navy-500"></div>
              <p>~</p>
              <div class="h-px flex-1 bg-slate-200 dark:bg-navy-500"></div>
            </div>
          </div>
        </div>
        <div class="my-5 flex justify-center text-xs text-slate-400 dark:text-navy-300">
          <a href="#">Privacy Notice</a>
          <div class="mx-3 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>
          <a href="#">Term of service</a>
        </div>
      </main>
    </div>

    <!-- 
        This is a place for Alpine.js Teleport feature 
        @see https://alpinejs.dev/directives/teleport
      -->
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
    <div x-data 
     x-init="
        const params = new URLSearchParams(window.location.search);
        if(params.get('status') === 'success') {
            // Fire the notification
            $notification({text:'Logged Out Successfully', variant:'warning', position:'right-top'});
            
            // Clean the URL so it doesn't trigger again on refresh
            const url = new URL(window.location);
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname);
        }
     ">
     <div x-data 
     x-init="
        const params = new URLSearchParams(window.location.search);
        if(params.get('status') === 'registered') {
            // Fire the notification
            $notification({text:'Signin With Your Details', variant:'secondary', position:'right-top'});
            
            // Clean the URL so it doesn't trigger again on refresh
            const url = new URL(window.location);
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname);
        }
     ">
</div>
  </body>
</html>
