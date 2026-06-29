<?php $current_page = basename($_SERVER['PHP_SELF']); 
$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'profile.php';

// Load user
$res  = $conn->query("SELECT * FROM users WHERE id=$extension_id");
$user = $res->fetch_assoc();
$avatar_path = $user['image_paths'] ? '../../' . htmlspecialchars($user['image_paths']) : null;
$initials    = strtoupper(substr($user['name'] ?? 'BY', 0, 2));
 ?>
<div class="is-scrollbar-hidden flex grow flex-col space-y-4 overflow-y-auto pt-6">
              <!-- Dashobards -->
              <a href="index.php" class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
   <?= $current_page == 'index.php' 
      ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
      : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
   x-tooltip.placement.right="'Dashboards'">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
              </a>

              <!-- Products -->
              <a href="products.php" class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
   <?= in_array($current_page, ['products.php', 'product_details.php'])
      ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
      : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
   x-tooltip.placement.right="'Products'">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path d="M20.87,8.009a9.67,9.67,0,0,0-5.236,2.306A7.676,7.676,0,0,0,16,8a9.463,9.463,0,0,0-3.375-6.781,1,1,0,0,0-1.25,0A9.463,9.463,0,0,0,8,8a7.681,7.681,0,0,0,.366,2.315A9.673,9.673,0,0,0,3.13,8.009,1,1,0,0,0,2.011,9.148C2.7,13.871,7.6,18,11,18v4a1,1,0,0,0,2,0V18c3.419,0,8.218-4.029,8.989-8.852A1,1,0,0,0,20.87,8.009ZM12,3.391A7.075,7.075,0,0,1,14,8a7.08,7.08,0,0,1-2,4.61A7.08,7.08,0,0,1,10,8,7.075,7.075,0,0,1,12,3.391ZM4.408,10.33a8.215,8.215,0,0,1,5.183,5.248A8.764,8.764,0,0,1,4.408,10.33Zm10,5.248a8.218,8.218,0,0,1,5.183-5.248A8.767,8.767,0,0,1,14.409,15.578Z"></path></svg>
              </a>

              <!-- Orders/Requests -->
              <a href="orders.php" class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
   <?= in_array($current_page, ['order_details.php', 'cart.php', 'orders.php'])
      ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
      : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
   x-tooltip.placement.right="'Orders'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
              </a>

              <!-- Market prices -->
              <a href="analytics.php" class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
   <?= in_array($current_page, ['analytics.php', 'live.php'])
      ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
      : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
   x-tooltip.placement.right="'Analytics/Insights'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
              </a>

              <!-- Payments and Wallet -->
              <a href="wallet.php" class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
   <?= in_array($current_page, ['wallet.php']) 
      ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
      : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
   x-tooltip.placement.right="'Payments and Wallet'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                </svg>

              </a>


              <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>

              <!-- Negotiation-->
              <a href="negotiation.php" class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
                 <?= in_array($current_page, ['negotiation.php']) 
                    ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
                    : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>" x-tooltip.placement.right="'Negotiations'">
                <svg 
                        class="size-6" 
                        fill="currentColor" 
                        viewBox="0 0 256 256" 
                        id="Flat" 
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <g id="SVGRepo_iconCarrier">
                          <path d="M250.86816,105.37207,226.27637,58.27588a20.09815,20.09815,0,0,0-26.67285-8.63233L177.167,60.86133H166.58838L134.377,46.28613a20.01373,20.01373,0,0,0-13.83984-.97851c-.08691.0249-.17383.05127-.25977.07861L77.24023,58.99341,56.39746,48.57275a20.09127,20.09127,0,0,0-26.67285,8.63037L5.13086,104.30078a20.00048,20.00048,0,0,0,8.78516,27.145l23.43652,11.71838,49.57031,41.82215q.16992.14355.3457.28027a19.85611,19.85611,0,0,0,7.46973,3.64844l57.957,14.48926a19.80917,19.80917,0,0,0,4.80469.58984,20.11943,20.11943,0,0,0,14.18848-5.84961l36.79687-36.79785c.04688-.04669.08594-.09979.13184-.14722.13672-.14117.26513-.29138.39551-.44043.18066-.20636.35644-.41583.52294-.634.0542-.07129.1167-.13281.16944-.20563.07519-.10327.13574-.21234.207-.3172.061-.08984.13184-.17279.19043-.26434l10.21826-15.93878L242.084,132.51758a19.9994,19.9994,0,0,0,8.78418-27.14551Zm-54.10253,30.29968-33.708-24.515a12.00056,12.00056,0,0,0-14.25782.10449L136,120.86133a20.10132,20.10132,0,0,1-24,0l-1.73145-1.29785L144.9707,84.86133H163.937c.01856.00006.0376.00293.05615.00293.02735,0,.0542-.00275.08155-.00293H172.729l25.41163,48.66534ZM49.17969,71.7959,59.71,77.061,38.82031,117.06543,28.29,111.80029ZM156.31934,179.57227l-54.84766-13.71192-42.3833-35.75928L84.229,81.9549l41.65381-13.16974.84229.381-36.688,36.6878a20.00026,20.00026,0,0,0,2.1416,30.14209l5.4209,4.06543a44.22818,44.22818,0,0,0,52.80078,0l5.709-4.28222,25.47509,18.527Zm60.86035-61.43506L196.29,78.13281l10.53027-5.26513L227.71,112.87207ZM119.6416,223.77148a11.98529,11.98529,0,0,1-14.55176,8.73145L74.9502,224.96875a20.1014,20.1014,0,0,1-8.27149-4.31055L44.12793,201.05664a11.99968,11.99968,0,1,1,15.74414-18.11328L81.70215,201.918l29.208,7.30176A11.99951,11.99951,0,0,1,119.6416,223.77148Z"></path>
                        </g>
                      </svg>
              </a>
                     <!-- Loans -->
   <!--            <a href="../farmer/loans.php" 
                 class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
                 <--?= in_array($current_page, ['loans.php', 'loan_applications.php', 'loan_repayments.php', 'loan_products.php', 'loan_borrowers.php', 'loan_borrower_profile.php']) 
                    ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
                    : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
                 x-tooltip.placement.right="'Loans'">
                 
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                 </svg>
              </a> -->
            </div>

            <!-- Bottom Links -->
            <div class="flex flex-col items-center space-y-3 py-3">
              <!-- Settings -->
             <!--  <a href="form-layout-5.html" class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200 hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <svg class="size-7" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-opacity="0.3" fill="currentColor" d="M2 12.947v-1.771c0-1.047.85-1.913 1.899-1.913 1.81 0 2.549-1.288 1.64-2.868a1.919 1.919 0 0 1 .699-2.607l1.729-.996c.79-.474 1.81-.192 2.279.603l.11.192c.9 1.58 2.379 1.58 3.288 0l.11-.192c.47-.795 1.49-1.077 2.279-.603l1.73.996a1.92 1.92 0 0 1 .699 2.607c-.91 1.58-.17 2.868 1.639 2.868 1.04 0 1.899.856 1.899 1.912v1.772c0 1.047-.85 1.912-1.9 1.912-1.808 0-2.548 1.288-1.638 2.869.52.915.21 2.083-.7 2.606l-1.729.997c-.79.473-1.81.191-2.279-.604l-.11-.191c-.9-1.58-2.379-1.58-3.288 0l-.11.19c-.47.796-1.49 1.078-2.279.605l-1.73-.997a1.919 1.919 0 0 1-.699-2.606c.91-1.58.17-2.869-1.639-2.869A1.911 1.911 0 0 1 2 12.947Z"></path>
                  <path fill="currentColor" d="M11.995 15.332c1.794 0 3.248-1.464 3.248-3.27 0-1.807-1.454-3.272-3.248-3.272-1.794 0-3.248 1.465-3.248 3.271 0 1.807 1.454 3.271 3.248 3.271Z"></path>
                </svg>
              </a> -->

              <!-- Profile -->
              <div x-data="usePopper({placement:'right-end',offset:12})" @click.outside="isShowPopper && (isShowPopper = false)" class="flex">
                 <?php if ($avatar_path && file_exists($avatar_path)): ?>
                <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="avatar size-12">
                  <img class="rounded-full" src="<?= $avatar_path ?>" alt="avatar">
                  <span class="absolute right-0 size-3.5 rounded-full border-2 border-white bg-success dark:border-navy-700"></span>
                </button>
                <?php else: ?>
                  <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="avatar size-12">
                  <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
                              <?= $initials ?>
                            </div>
                  <span class="absolute right-0 size-3.5 rounded-full border-2 border-white bg-success dark:border-navy-700"></span>
                </button>
                <?php endif; ?>

                <div :class="isShowPopper && 'show'" class="popper-root fixed" x-ref="popperRoot">
                  <div class="popper-box w-64 rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-600 dark:bg-navy-700">
                    <div class="flex items-center space-x-4 rounded-t-lg bg-slate-100 py-5 px-4 dark:bg-navy-800">
                      <?php if ($avatar_path && file_exists($avatar_path)): ?>
                          <div class="avatar size-14">
                        <img class="rounded-full" src="<?= $avatar_path ?>" alt="avatar">
                        </div>
                        <?php else: ?>
                          <div class="avatar size-8 hover:z-10">
                            <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
                              <?= $initials ?>
                            </div>
                          
                            </div>
                            <?php endif; ?>
                      <div>
                        <a href="profile.php" class="text-base font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">
                          <?= htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <p class="text-xs text-slate-400 dark:text-navy-300">
                          FAIMS - Buyer
                        </p>
                      </div>
                    </div>
                    <div class="flex flex-col pt-2 pb-5">
                      <a href="profile.php" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-warning text-white">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                          </svg>
                        </div>

                        <div>
                          <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light">
                            Profile
                          </h2>
                          <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            Your profile setting
                          </div>
                        </div>
                      </a>
                      <a href="#" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-info text-white">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                          </svg>
                        </div>

                        <div>
                          <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light">
                            Messages
                          </h2>
                          <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            Your messages and tasks
                          </div>
                        </div>
                      </a>
                      <a href="#" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-secondary text-white">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                          </svg>
                        </div>

                        <div>
                          <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light">
                            Team
                          </h2>
                          <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            Your team activity
                          </div>
                        </div>
                      </a>
                      <a href="#" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-error text-white">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                          </svg>
                        </div>

                        <div>
                          <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light">
                            Activity
                          </h2>
                          <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            Your activity and events
                          </div>
                        </div>
                      </a>
                      <a href="#" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-success text-white">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                        </div>

                        <div>
                          <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light">
                            Settings
                          </h2>
                          <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            Webapp settings
                          </div>
                        </div>
                      </a>
                      <div class="mt-3 px-4">
                          <a href="../auth/logout.php">
                          <button type="button" class="btn h-9 w-full space-x-2 bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                          </svg>
                          <span>Logout</span>
                          </button>
                          </a>
                          </div>

                    </div>
                  </div>
                </div>
              </div>
            </div>