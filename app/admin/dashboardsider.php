<div class="sidebar-panel">
          <div class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750">
            <!-- Sidebar Panel Header -->
            <div class="flex h-18 w-full items-center justify-between pl-4 pr-1">
              <p class="text-base tracking-wider text-slate-800 dark:text-navy-100">
                Dashboards
              </p>
              <button @click="$store.global.isSidebarExpanded = false" class="btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:text-accent-light/80 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 xl:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
              </button>
            </div>

            <!-- Sidebar Panel Body -->
            <div x-data="{expandedItem:null}" class="h-[calc(100%-4.5rem)] overflow-x-hidden pb-6" x-init="$el._x_simplebar = new SimpleBar($el);">
              <ul class="flex flex-1 flex-col px-4 font-inter">
                <li>
                  <a x-data="navLink" href="index.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Main
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="orders.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Orders
                  </a>
                </li>
              </ul>
              <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              <ul class="flex flex-1 flex-col px-4 font-inter">
                <li x-data="accordionItem('menu-item-1')">
                  <a :class="expanded ? 'text-slate-800 font-semibold dark:text-navy-50' : 'text-slate-600 dark:text-navy-200  hover:text-slate-800  dark:hover:text-navy-50'" @click="expanded = !expanded" class="flex items-center justify-between py-2 text-xs+ tracking-wide outline-none transition-[color,padding-left] duration-300 ease-in-out" href="javascript:void(0);">
                    <span>Users</span>
                    <svg :class="expanded && 'rotate-90'" xmlns="http://www.w3.org/2000/svg" class="size-4 text-slate-400 transition-transform ease-in-out" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                  </a>
                  <ul x-collapse="" x-show="expanded">
                    <li>
                      <a x-data="navLink" href="users.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex items-center justify-between p-2 text-xs+ tracking-wide outline-none transition-[color,padding-left] duration-300 ease-in-out hover:pl-4">
                        <div class="flex items-center space-x-2">
                          <div class="size-1.5 rounded-full border border-current opacity-40"></div>
                          <span>Users Page</span>
                        </div>
                      </a>
                    </li>
                    <li>
                      <a x-data="navLink" href="add_user.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex items-center justify-between p-2 text-xs+ tracking-wide outline-none transition-[color,padding-left] duration-300 ease-in-out hover:pl-4">
                        <div class="flex items-center space-x-2">
                          <div class="size-1.5 rounded-full border border-current opacity-40"></div>
                          <span>Users Add</span>
                        </div>
                      </a>
                    </li>
                  </ul>
                </li>
                <li x-data="accordionItem('menu-item-2')">
                  <a :class="expanded ? 'text-slate-800 font-semibold dark:text-navy-50' : 'text-slate-600 dark:text-navy-200  hover:text-slate-800  dark:hover:text-navy-50'" @click="expanded = !expanded" class="flex items-center justify-between py-2 text-xs+ tracking-wide outline-none transition-[color,padding-left] duration-300 ease-in-out" href="javascript:void(0);">
                    <span>Loans</span>
                    <svg :class="expanded && 'rotate-90'" xmlns="http://www.w3.org/2000/svg" class="size-4 text-slate-400 transition-transform ease-in-out" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                  </a>
                  <ul x-collapse="" x-show="expanded">
                    <li>
                      <a x-data="navLink" href="loan_applications.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex items-center justify-between p-2 text-xs+ tracking-wide outline-none transition-[color,padding-left] duration-300 ease-in-out hover:pl-4">
                        <div class="flex items-center space-x-2">
                          <div class="size-1.5 rounded-full border border-current opacity-40"></div>
                          <span>Loan Applications</span>
                        </div>
                      </a>
                    </li>
                    <li>
                      <a x-data="navLink" href="loan_products" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex items-center justify-between p-2 text-xs+ tracking-wide outline-none transition-[color,padding-left] duration-300 ease-in-out hover:pl-4">
                        <div class="flex items-center space-x-2">
                          <div class="size-1.5 rounded-full border border-current opacity-40"></div>
                          <span>Loan Plans</span>
                        </div>
                      </a>
                    </li>
                  </ul>
                </li>
                <li>
                  <a x-data="navLink" href="reports.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Reports
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="../forum/index.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Forum
                  </a>
                </li>
               <!--  <li>
                  <a x-data="navLink" href="dashboards-influencer.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Influencer
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="dashboards-travel.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Travel
                  </a>
                </li> -->
                <!-- <li>
                  <a x-data="navLink" href="dashboards-teacher.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Teacher
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="dashboards-education.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Education
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="dashboards-authors.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Authors
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="dashboards-doctor.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Doctors
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="dashboards-employees.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Employees
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="dashboards-workspace.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Workspaces
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="dashboards-meeting.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Meetings
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="dashboards-project-boards.html" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Project Boards
                  </a>
                </li> -->
              </ul>
              <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              <ul class="flex flex-1 flex-col px-4 font-inter">
                <li>
                  <a x-data="navLink" href="groups.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Groups
                  </a>
                </li>
                <li>
                  <a x-data="navLink" href="training.php" :class="isActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-600 hover:text-slate-900 dark:text-navy-200 dark:hover:text-navy-50'" class="flex py-2 text-xs+ tracking-wide outline-none transition-colors duration-300 ease-in-out">
                    Training
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>