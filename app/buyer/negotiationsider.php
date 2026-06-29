<!-- Sidebar Panel -->
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <div class="sidebar-panel">
          <div class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750">
            <!-- Sidebar Panel Header -->
            <div class="flex h-18 w-full items-center justify-between pl-4 pr-1">
              <div class="flex items-center">
                <div class="avatar mr-3 hidden size-9 lg:flex">
                  <div class="is-initial rounded-full bg-info/10 text-info p-2 flex items-center justify-center ">
                      <svg 
                        class="size-8" 
                        fill="currentColor" 
                        viewBox="0 0 256 256" 
                        id="Flat" 
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <g id="SVGRepo_iconCarrier">
                          <path d="M250.86816,105.37207,226.27637,58.27588a20.09815,20.09815,0,0,0-26.67285-8.63233L177.167,60.86133H166.58838L134.377,46.28613a20.01373,20.01373,0,0,0-13.83984-.97851c-.08691.0249-.17383.05127-.25977.07861L77.24023,58.99341,56.39746,48.57275a20.09127,20.09127,0,0,0-26.67285,8.63037L5.13086,104.30078a20.00048,20.00048,0,0,0,8.78516,27.145l23.43652,11.71838,49.57031,41.82215q.16992.14355.3457.28027a19.85611,19.85611,0,0,0,7.46973,3.64844l57.957,14.48926a19.80917,19.80917,0,0,0,4.80469.58984,20.11943,20.11943,0,0,0,14.18848-5.84961l36.79687-36.79785c.04688-.04669.08594-.09979.13184-.14722.13672-.14117.26513-.29138.39551-.44043.18066-.20636.35644-.41583.52294-.634.0542-.07129.1167-.13281.16944-.20563.07519-.10327.13574-.21234.207-.3172.061-.08984.13184-.17279.19043-.26434l10.21826-15.93878L242.084,132.51758a19.9994,19.9994,0,0,0,8.78418-27.14551Zm-54.10253,30.29968-33.708-24.515a12.00056,12.00056,0,0,0-14.25782.10449L136,120.86133a20.10132,20.10132,0,0,1-24,0l-1.73145-1.29785L144.9707,84.86133H163.937c.01856.00006.0376.00293.05615.00293.02735,0,.0542-.00275.08155-.00293H172.729l25.41163,48.66534ZM49.17969,71.7959,59.71,77.061,38.82031,117.06543,28.29,111.80029ZM156.31934,179.57227l-54.84766-13.71192-42.3833-35.75928L84.229,81.9549l41.65381-13.16974.84229.381-36.688,36.6878a20.00026,20.00026,0,0,0,2.1416,30.14209l5.4209,4.06543a44.22818,44.22818,0,0,0,52.80078,0l5.709-4.28222,25.47509,18.527Zm60.86035-61.43506L196.29,78.13281l10.53027-5.26513L227.71,112.87207ZM119.6416,223.77148a11.98529,11.98529,0,0,1-14.55176,8.73145L74.9502,224.96875a20.1014,20.1014,0,0,1-8.27149-4.31055L44.12793,201.05664a11.99968,11.99968,0,1,1,15.74414-18.11328L81.70215,201.918l29.208,7.30176A11.99951,11.99951,0,0,1,119.6416,223.77148Z"></path>
                        </g>
                      </svg>
                    </div>

                </div>
                <p class="text-lg font-medium tracking-wider text-slate-800 dark:text-navy-100">
                  Negotiations
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
                <!-- <div class="mt-2 px-4">
                  <button  class="btn w-full space-x-2 rounded-full border border-slate-200 py-2 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-500 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90" >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span> All Products </span>
                  </button>
                </div> -->
                <ul class="mt-5 space-y-1.5 px-2 font-inter text-xs+ font-medium">
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all <?= ($current_page == 'negotiations.php') ? 'bg-primary/10 text-primary dark:bg-accent-light/10 dark:text-accent-light' : 'text-slate-800 hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600' ?>" href="negotiations.php">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 <?= ($current_page == 'negotiation.php') ? 'text-primary dark:text-accent-light' : 'text-slate-400' ?> transition-colors group-hover:text-slate-500" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"  d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"></path>
                      </svg>
                      <span>All Negotiations</span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all <?= ($current_page == 'negotiation.php' && ($_GET['filter'] ?? '') == 'pending') ? 'bg-primary/10 text-primary dark:bg-accent-light/10 dark:text-accent-light' : 'text-slate-800 hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600' ?>" href="negotiation.php?filter=pending">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5  <?= ($current_page == 'negotiation.php' && ($_GET['filter'] ?? '') == 'pending') ? 'text-primary dark:text-accent-light' : 'text-slate-400' ?> transition-colors group-hover:text-slate-500" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061A1.125 1.125 0 0 1 3 16.811V8.69ZM12.75 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061a1.125 1.125 0 0 1-1.683-.977V8.69Z"></path>
                      </svg>
                      <span>Pending</span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all <?= ($current_page == 'negotiation.php' && ($_GET['filter'] ?? '') == 'accepted') ? 'bg-primary/10 text-primary dark:bg-accent-light/10 dark:text-accent-light' : 'text-slate-800 hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600' ?>" href="negotiation.php?filter=accepted">
                       <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5  <?= ($current_page == 'negotiation.php' && ($_GET['filter'] ?? '') == 'accepted') ? 'text-primary dark:text-accent-light' : 'text-slate-400' ?> transition-colors group-hover:text-slate-500" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <span>Accepted</span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all <?= ($current_page == 'negotiation.php' && ($_GET['filter'] ?? '') == 'rejected') ? 'bg-primary/10 text-primary dark:bg-accent-light/10 dark:text-accent-light' : 'text-slate-800 hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600' ?>" href="negotiation.php?filter=rejected">
                       <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5  <?= ($current_page == 'negotiation.php' && ($_GET['filter'] ?? '') == 'rejected') ? 'text-primary dark:text-accent-light' : 'text-slate-400' ?> transition-colors group-hover:text-slate-500" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                      </svg>
                      <span>Rejected </span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-slate-800 outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600" href="cart.php">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 transition-colors group-hover:text-slate-500 group-focus:text-slate-500 dark:text-navy-300 dark:group-hover:text-navy-200 dark:group-focus:text-navy-200" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                      </svg>
                      <span>Cart</span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all <?= ($current_page == 'analytics.php') ? 'bg-primary/10 text-primary dark:bg-accent-light/10 dark:text-accent-light' : 'text-slate-800 hover:bg-slate-100 focus:bg-slate-100 dark:text-navy-100 dark:hover:bg-navy-600' ?>" href="analytics.php">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 transition-colors group-hover:text-slate-500 group-focus:text-slate-500 dark:text-navy-300 dark:group-hover:text-navy-200 dark:group-focus:text-navy-200" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                      </svg>
                      <span>Favorites</span>
                    </a>
                  </li>
                  <!-- <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide text-error outline-none transition-all hover:bg-error/20 focus:bg-error/20" href="#">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                      <span>Deleted</span>
                    </a>
                  </li> -->
                </ul>
                <div class="my-4 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
                <!-- <div class="flex items-center justify-between px-4">
                  <span class="text-xs font-medium uppercase">Labels</span>
                  <div class="-mr-1.5 flex">
                    <button class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                      </svg>
                    </button>

                    <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                      <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                      </button>

                      <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                        <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                          <ul>
                            <li>
                              <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Action</a>
                            </li>
                            <li>
                              <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Another Action</a>
                            </li>
                            <li>
                              <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Something else</a>
                            </li>
                          </ul>
                          <div class="my-1 h-px bg-slate-150 dark:bg-navy-500"></div>
                          <ul>
                            <li>
                              <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Separated Link</a>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <ul class="mt-1 space-y-1.5 px-2 font-inter text-xs+ font-medium">
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all hover:bg-success/20 focus:bg-success/20" href="#">
                      <svg class="size-4.5 text-success" stroke="currentColor" viewbox="0 0 24 24" stroke-width="1.5" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 6H21M7 12H21M7 18H21" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M3 6H4M3 12H4M3 18H4" stroke-linecap="round" stroke-linejoin="round"></path>
                      </svg>
                      <span class="text-slate-800 dark:text-navy-100">Low</span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all hover:bg-warning/20 focus:bg-warning/20" href="#">
                      <svg class="size-4.5 text-warning" stroke="currentColor" viewbox="0 0 24 24" stroke-width="1.5" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 6H21M7 12H21M7 18H21" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M3 6H4M3 12H4M3 18H4" stroke-linecap="round" stroke-linejoin="round"></path>
                      </svg>
                      <span class="text-slate-800 dark:text-navy-100">Medium</span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all hover:bg-error/20 focus:bg-error/20" href="#">
                      <svg class="size-4.5 text-error" stroke="currentColor" viewbox="0 0 24 24" stroke-width="1.5" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 6H21M7 12H21M7 18H21" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M3 6H4M3 12H4M3 18H4" stroke-linecap="round" stroke-linejoin="round"></path>
                      </svg>
                      <span class="text-slate-800 dark:text-navy-100">High</span>
                    </a>
                  </li>
                  <li>
                    <a class="group flex space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all hover:bg-info/20 focus:bg-info/20" href="#">
                      <svg class="size-4.5 text-info" stroke="currentColor" viewbox="0 0 24 24" stroke-width="1.5" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 6H21M7 12H21M7 18H21" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M3 6H4M3 12H4M3 18H4" stroke-linecap="round" stroke-linejoin="round"></path>
                      </svg>
                      <span class="text-slate-800 dark:text-navy-100">Update</span>
                    </a>
                  </li>
                </ul> -->
              </div>

              <!-- <div class="flex shrink-0 justify-between px-1.5 py-1">
                <a href="apps-mail.html" x-tooltip="'Mail App'" class="btn size-9 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                  </svg>
                </a>
                <a href="apps-kanban.html" x-tooltip="'Kanban App'" class="btn size-9 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                  </svg>
                </a>
                <a href="apps-chat.html" x-tooltip="'Chat App'" class="btn size-9 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                  </svg>
                </a>
                <a href="apps-pos.html" x-tooltip="'POS App'" class="btn size-9 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                </a>
                <a href="apps-filemanager.html" x-tooltip="'File Manager App'" class="btn size-9 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                  </svg>
                </a>
              </div> -->
            </div>
          </div>
        </div>
