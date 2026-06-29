      <!-- App Header Wrapper-->
      <nav class="header before:bg-white dark:before:bg-navy-750 print:hidden">
        <!-- App Header  -->
        <div class="header-container relative flex w-full bg-white dark:bg-navy-750 print:hidden">
          <!-- Header Items -->
          <div class="flex w-full items-center justify-between">
            <!-- Left: Sidebar Toggle Button -->
            <div class="size-7">
              <button class="menu-toggle ml-0.5 flex size-7 flex-col justify-center space-y-1.5 text-primary outline-none focus:outline-none dark:text-accent-light/80" :class="$store.global.isSidebarExpanded && 'active'" @click="$store.global.isSidebarExpanded = !$store.global.isSidebarExpanded">
                <span></span>
                <span></span>
                <span></span>
              </button>
            </div>

            <!-- Right: Header buttons -->
            <div class="-mr-1.5 flex items-center space-x-2">

              <!-- Dark Mode Toggle -->
              <button @click="$store.global.isDarkModeEnabled = !$store.global.isDarkModeEnabled" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <svg x-show="$store.global.isDarkModeEnabled" x-transition:enter="transition-transform duration-200 ease-out absolute origin-top" x-transition:enter-start="scale-75" x-transition:enter-end="scale-100 static" class="size-6 text-amber-400" fill="currentColor" viewbox="0 0 24 24">
                  <path d="M11.75 3.412a.818.818 0 01-.07.917 6.332 6.332 0 00-1.4 3.971c0 3.564 2.98 6.494 6.706 6.494a6.86 6.86 0 002.856-.617.818.818 0 011.1 1.047C19.593 18.614 16.218 21 12.283 21 7.18 21 3 16.973 3 11.956c0-4.563 3.46-8.31 7.925-8.948a.818.818 0 01.826.404z"></path>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" x-show="!$store.global.isDarkModeEnabled" x-transition:enter="transition-transform duration-200 ease-out absolute origin-top" x-transition:enter-start="scale-75" x-transition:enter-end="scale-100 static" class="size-6 text-amber-400" viewbox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                </svg>
              </button>
              <!-- Monochrome Mode Toggle -->
              <button @click="$store.global.isMonochromeModeEnabled = !$store.global.isMonochromeModeEnabled" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <i class="fa-solid fa-palette bg-gradient-to-r from-sky-400 to-blue-600 bg-clip-text text-lg font-semibold text-transparent"></i>
              </button>

              <!-- Notification-->
              <div x-effect="if($store.global.isSearchbarActive) isShowPopper = false"
                   x-data="usePopper({placement:'bottom-end',offset:12})"
                   @click.outside="isShowPopper && (isShowPopper = false)"
                   class="flex">
                               <!-- Alpine component definition (place once per page, ideally before </body>) -->
                      <script>
                        document.addEventListener('alpine:init', () => {
                          Alpine.data('farmerAlerts', () => ({
                            alerts: [],
                            total: 0,
                            activeTab: 'tabAll',
                            refreshTimer: null,

                            init() {
                              this.fetchAlerts();
                              if (!this.refreshTimer) {
                                this.refreshTimer = setInterval(() => this.fetchAlerts(), 3000);
                              }
                            },

                            async fetchAlerts() {
                              try {
                                const res = await fetch('ajax/alerts.php');
                                if (!res.ok) return;
                                const data = await res.json();
                                this.alerts = data.alerts || [];
                                this.total  = data.total  || 0;
                              } catch (e) {
                                console.error(e);
                              }
                            },

                            unreadExtension() {
                              return this.alerts.filter(a => a.type === 'extension' && !a.is_read).length;
                            },

                            alertIcon(type, subtype) {
                              if (type === 'order') return 'fa fa-shopping-cart text-primary';
                              if (type === 'stock') return 'fa fa-exclamation-triangle text-warning';
                              if (type === 'negotiation') return 'fas fa-handshake text-success';
                              if (type === 'comment') return 'fa fa-comment text-info';
                              if (type === 'like') return 'fa fa-heart text-error';
                              if (type === 'weather') return 'fa fa-cloud-sun-rain text-blue-400';
                              if (type === 'extension') {
                              if (subtype === 'report') return 'fa fa-flag text-warning dark:text-warning-light';
                                return 'fa fa-newspaper text-secondary dark:text-secondary-light';
                              }
                              return 'fa fa-bell';
                            },

                            alertClass(type, subtype) {
                              if (type === 'order') return 'bg-primary/10 dark:bg-primary/15';
                              if (type === 'stock') return 'bg-warning/10 dark:bg-warning/15';
                              if (type === 'negotiation') return 'bg-success/10 dark:bg-success/15';
                              if (type === 'comment') return 'bg-info/10 dark:bg-info/15';
                              if (type === 'like') return 'bg-error/10 dark:bg-error/15';
                              if (type === 'weather') return 'bg-blue-50 dark:bg-blue/15';
                              if (type === 'extension') {
                                if (subtype === 'report') return 'bg-warning/10 dark:bg-warning/15';
                                return 'bg-secondary/10 dark:bg-secondary-light/15';
                              }
                              return 'bg-info/10';
                            },

                          }));
                        });
                        </script>
                <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="btn relative size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-slate-500 dark:text-navy-100" stroke="currentColor" fill="none" viewbox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.375 17.556h-6.75m6.75 0H21l-1.58-1.562a2.254 2.254 0 01-.67-1.596v-3.51a6.612 6.612 0 00-1.238-3.85 6.744 6.744 0 00-3.262-2.437v-.379c0-.59-.237-1.154-.659-1.571A2.265 2.265 0 0012 2c-.597 0-1.169.234-1.591.65a2.208 2.208 0 00-.659 1.572v.38c-2.621.915-4.5 3.385-4.5 6.287v3.51c0 .598-.24 1.172-.67 1.595L3 17.556h12.375zm0 0v1.11c0 .885-.356 1.733-.989 2.358A3.397 3.397 0 0112 22a3.397 3.397 0 01-2.386-.976 3.313 3.313 0 01-.989-2.357v-1.111h6.75z"></path>
                  </svg>

                  <span class="absolute -top-px -right-px flex size-3 items-center justify-center">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-secondary opacity-80"></span>
                    <span class="inline-flex size-2 rounded-full bg-secondary"></span>
                  </span>
                </button>
                <div :class="isShowPopper && 'show'" class="popper-root" x-ref="popperRoot">
                  <div x-data="farmerAlerts" x-init="init()" class="popper-box mx-4 mt-1 flex max-h-[calc(100vh-6rem)] w-[calc(100vw-2rem)] flex-col rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-800 dark:bg-navy-700 dark:shadow-soft-dark sm:m-0 sm:w-80">
                    <div class="rounded-t-lg bg-slate-100 text-slate-600 dark:bg-navy-800 dark:text-navy-200">
                      <div class="flex items-center justify-between px-4 pt-2">
                        <div class="flex items-center space-x-2">
                              <h3 class="font-medium text-slate-700 dark:text-navy-100">Notifications</h3>
                              <div class="badge h-5 rounded-full bg-primary/10 px-1.5 text-primary dark:bg-accent-light/15 dark:text-accent-light"
                                    x-text="total"></div>
                            </div>

                        <button class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                        </button>
                      </div>

                      <!-- Tab bar — now has 5 tabs -->
                      <div class="is-scrollbar-hidden flex shrink-0 overflow-x-auto px-3">
                        <!-- All -->
                        <button @click="activeTab = 'tabAll'"
                                :class="activeTab === 'tabAll' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                                class="btn shrink-0 rounded-none border-b-2 px-3.5 py-2.5">All</button>
                        <!-- Alerts -->
                        <button @click="activeTab = 'tabExtension'"
                                :class="activeTab === 'tabExtension' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                                class="btn shrink-0 rounded-none border-b-2 px-3.5 py-2.5">Alerts</button>
                                <!-- Negotiations -->
                        <button @click="activeTab = 'tabNeg'"
                                :class="activeTab === 'tabNeg' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                                class="btn shrink-0 rounded-none border-b-2 px-3.5 py-2.5">Negotiations</button>
                        <!-- Events -->
                        <button @click="activeTab = 'tabEvents'"
                                :class="activeTab === 'tabEvents' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                                class="btn shrink-0 rounded-none border-b-2 px-3.5 py-2.5">Weather</button>                       
                      </div>
                    </div>

                    <div class="tab-content flex flex-col overflow-hidden">
                      <div x-show="activeTab === 'tabAll'" class="space-y-4 overflow-y-auto px-4 py-4">

                            <template x-for="alert in alerts" :key="alert.id + '-' + alert.timestamp">
                              <div class="flex items-center space-x-3">
                                
                                <div class="size-10 flex items-center justify-center rounded-lg"
                                     :class="alertClass(alert.type, alert.subtype)">
                                  <i :class="alertIcon(alert.type, alert.subtype)"></i>
                                </div>

                                <div>
                                  <p class="font-medium" x-text="alert.title"></p>
                                  <p class="text-xs text-slate-400" x-text="alert.message"></p>
                                  <p class="text-xs text-slate-300" x-text="alert.time"></p>
                                </div>

                              </div>
                            </template>

                            <div x-show="alerts.length === 0" class="text-center text-gray-400">
                              No alerts available
                            </div>

                          </div>

                      <div x-show="activeTab === 'tabExtension'" x-transition:enter="transition-all duration-300 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden space-y-4 overflow-y-auto px-4 py-4 mx-4 mt-1 flex max-h-[calc(100vh-6rem)] w-[calc(100vw-2rem)] flex-col rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-800 dark:bg-navy-700 dark:shadow-soft-dark sm:m-0 sm:w-80">
                        <!-- Sub-filter chips: All / Bulletins / Field reports -->
                          <div x-data="{ extFilter: 'all' }" class="flex flex-col h-full" x-transition:enter="transition-all duration-300 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]">
                            <div
                              class="is-scrollbar-hidden overflow-x-auto rounded-lg bg-slate-200 text-slate-600 dark:bg-navy-800 dark:text-navy-200"
                            >
                            <div class="tabs-list flex px-1.5 py-1">
                              <button
                                  @click="extFilter = 'all'"
                                  :class="extFilter === 'all' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                                  class="btn shrink-0 px-3 py-1.5 font-medium"
                                >
                                  All
                                </button>
                                <button
                                  @click="extFilter = 'bulletin'"
                                  :class="extFilter === 'bulletin' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                                  class="btn shrink-0 px-3 py-1.5 font-medium"
                                >
                                  Bulletins
                                </button>
                                <button
                                  @click="extFilter = 'report'"
                                  :class="extFilter === 'report' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                                  class="btn shrink-0 px-3 py-1.5 font-medium"
                                >
                                  Field reports
                                </button>
                            </div>
                          </div>

                            <!-- Alert list -->
                            <div class="is-scrollbar-hidden space-y-1 overflow-y-auto px-3 py-3">
                              <template x-for="alert in alerts.filter(a => a.type === 'extension' && (extFilter === 'all' || a.subtype === extFilter))" :key="alert.id">
                                
                                <div class="flex items-center space-x-3" :class="alert.is_read ? 'hover:bg-slate-50 dark:hover:bg-navy-600' : 'bg-emerald-50 dark:bg-emerald-900/15'">
                                  <div class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="alertClass(alert.type, alert.subtype)">
                                    <i :class="alertIcon(alert.type, alert.subtype)"></i>
                                  </div>
                                  <div>
                                    <p class="font-medium text-slate-600 dark:text-navy-100" x-text="alert.title">
                                      
                                    </p>
                                    <div class="mt-1 text-xs text-slate-400 line-clamp-1 dark:text-navy-300" x-text="alert.message">
                                      
                                    </div>
                                    <div class="mt-1 flex text-xs text-slate-400 dark:text-navy-300">
                                    <span class="shrink-0" x-text="alert.time"></span>
                                    <div class="mx-2 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>

                                    <!-- <span class="line-clamp-1">Frontend Conf</span> -->
                                    <span x-show="!alert.is_read"
                                            class="mt-1 size-1.5 rounded-full bg-error flex-shrink-0 line-clamp-1"></span>
                                    </div>
                                  </div>
                                </div>
                              </template>

                              <!-- Empty state -->
                              <template x-if="alerts.filter(a => a.type === 'extension' && (extFilter === 'all' || a.subtype === extFilter)).length === 0">
                                <div class="py-10 text-center">
                                  <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/20">
                                   <!--  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="2">
                                      <path d="M11 2C7 2 2 6 2 11s5 9 9 9 9-5 9-9" stroke-linecap="round"/>
                                      <path d="M11 7v4l2.5 2.5" stroke-linecap="round"/>
                                    </svg> -->
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6 text-success">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>

                                  </div>
                                  <p class="text-sm font-medium text-slate-600 dark:text-navy-200">No extension updates</p>
                                  <p class="mt-1 text-xs text-slate-400 dark:text-navy-400">
                                    Bulletins and field reports from your extension officer will appear here
                                  </p>
                                </div>
                              </template>
                            </div>
                          </div>
                      </div>
                      <div x-show="activeTab === 'tabNeg'" x-transition:enter="transition-all duration-300 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden space-y-4 overflow-y-auto px-4 py-4">
                        <template x-for="alert in alerts.filter(a => a.type === 'negotiation')" :key="alert.id">
                          <div class="flex items-center space-x-3">
                          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="alertClass(alert.type, alert.subtype)">
                            <i :class="alertIcon(alert.type)"></i>
                          </div>
                          <div>
                            <p class="font-medium text-slate-600 dark:text-navy-100" x-text="alert.title">
                              
                            </p>
                            <div class="mt-1 text-xs text-slate-400 line-clamp-1 dark:text-navy-300" x-text="alert.message">                              
                            </div>
                            <div class="mt-1 flex text-xs text-slate-400 dark:text-navy-300">
                              <span class="shrink-0" x-text="alert.time"></span>
                              <div class="mx-2 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>
                              <template x-if="alert.link">
                                <a :href="alert.link">
                              <span class="line-clamp-1">View</span>
                            </a>
                            </template>
                            </div>
                          </div>
                        </div>
                        </template>

                        <template x-if="alerts.filter(a => a.type === 'negotiation').length === 0">
                          <div class="mt-8 pb-8 text-center">
                            <img class="mx-auto w-36" src="../images/illustrations/empty-girl-box.svg" alt="image">
                            <div class="mt-5">
                              <p class="text-base font-semibold text-slate-700 dark:text-navy-100">
                                No new negotiations
                              </p>
                              <p class="text-slate-400 dark:text-navy-300">
                                New offers or replies will appear here
                              </p>
                            </div>
                          </div>
                        </template>
                      </div>
                      <!-- <div x-show="activeTab === 'tabEvents'" x-transition:enter="transition-all duration-300 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden space-y-4 overflow-y-auto px-4 py-4">
                        <div class="flex items-center space-x-3">
                          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info/10 dark:bg-info/15">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                          </div>
                          <div>
                            <p class="font-medium text-slate-600 dark:text-navy-100">
                              Mon, June 14, 2021
                            </p>
                            <div class="mt-1 flex text-xs text-slate-400 dark:text-navy-300">
                              <span class="shrink-0">08:00 - 09:00</span>
                              <div class="mx-2 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>

                              <span class="line-clamp-1">Frontend Conf</span>
                            </div>
                          </div>
                        </div>
                        <div class="flex items-center space-x-3">
                          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info/10 dark:bg-info/15">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                          </div>
                          <div>
                            <p class="font-medium text-slate-600 dark:text-navy-100">
                              Wed, June 21, 2021
                            </p>
                            <div class="mt-1 flex text-xs text-slate-400 dark:text-navy-300">
                              <span class="shrink-0">16:00 - 20:00</span>
                              <div class="mx-2 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>

                              <span class="line-clamp-1">UI/UX Conf</span>
                            </div>
                          </div>
                        </div>
                        <div class="flex items-center space-x-3">
                          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/10 dark:bg-warning/15">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                            </svg>
                          </div>
                          <div>
                            <p class="font-medium text-slate-600 dark:text-navy-100">
                              THU, May 11, 2021
                            </p>
                            <div class="mt-1 flex text-xs text-slate-400 dark:text-navy-300">
                              <span class="shrink-0">10:00 - 11:30</span>
                              <div class="mx-2 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>
                              <span class="line-clamp-1">Interview, Konnor Guzman
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="flex items-center space-x-3">
                          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info/10 dark:bg-info/15">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                          </div>
                          <div>
                            <p class="font-medium text-slate-600 dark:text-navy-100">
                              Mon, Jul 16, 2021
                            </p>
                            <div class="mt-1 flex text-xs text-slate-400 dark:text-navy-300">
                              <span class="shrink-0">06:00 - 16:00</span>
                              <div class="mx-2 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>

                              <span class="line-clamp-1">Laravel Conf</span>
                            </div>
                          </div>
                        </div>
                        <div class="flex items-center space-x-3">
                          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/10 dark:bg-warning/15">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                            </svg>
                          </div>
                          <div>
                            <p class="font-medium text-slate-600 dark:text-navy-100">
                              Wed, Jun 16, 2021
                            </p>
                            <div class="mt-1 flex text-xs text-slate-400 dark:text-navy-300">
                              <span class="shrink-0">15:30 - 11:30</span>
                              <div class="mx-2 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>
                              <span class="line-clamp-1">Interview, Jonh Doe
                              </span>
                            </div>
                          </div>
                        </div>
                      </div> -->
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Sidebar Toggle -->
              <button @click="$store.global.isRightSidebarExpanded = true" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5.5 text-slate-500 dark:text-navy-100" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </nav>

    