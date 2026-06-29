<div class="col-span-12 lg:col-span-8 xl:col-span-9">
            <div class="mt-4 sm:mt-5 lg:mt-6">
              <div class="flex items-center justify-between">
                <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                  All Users
                </h2>
                <div class="flex">
                  <div class="flex items-center" x-data="{isInputActive:false}">
                     <label class="relative flex w-full">
                  <input class="form-input peer h-9 w-full rounded-l-lg bg-white px-3 py-2 shadow-soft ring-primary/50 placeholder:text-slate-400 focus:ring dark:bg-navy-700 dark:shadow-none dark:ring-accent/50 dark:placeholder:text-navy-300 lg:pl-9" placeholder="Name, Role or Location..." x-model="search"
                  @input.debounce.500ms="performSearch()"
                  placeholder="Search here..."
                  type="text">
                  <span class="pointer-events-none absolute hidden h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent lg:flex">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 transition-colors duration-200" fill="currentColor" viewbox="0 0 24 24">
                      <path d="M3.316 13.781l.73-.171-.73.171zm0-5.457l.73.171-.73-.171zm15.473 0l.73-.171-.73.171zm0 5.457l.73.171-.73-.171zm-5.008 5.008l-.171-.73.171.73zm-5.457 0l-.171.73.171-.73zm0-15.473l-.171-.73.171.73zm5.457 0l.171-.73-.171.73zM20.47 21.53a.75.75 0 101.06-1.06l-1.06 1.06zM4.046 13.61a11.198 11.198 0 010-5.115l-1.46-.342a12.698 12.698 0 000 5.8l1.46-.343zm14.013-5.115a11.196 11.196 0 010 5.115l1.46.342a12.698 12.698 0 000-5.8l-1.46.343zm-4.45 9.564a11.196 11.196 0 01-5.114 0l-.342 1.46c1.907.448 3.892.448 5.8 0l-.343-1.46zM8.496 4.046a11.198 11.198 0 015.115 0l.342-1.46a12.698 12.698 0 00-5.8 0l.343 1.46zm0 14.013a5.97 5.97 0 01-4.45-4.45l-1.46.343a7.47 7.47 0 005.568 5.568l.342-1.46zm5.457 1.46a7.47 7.47 0 005.568-5.567l-1.46-.342a5.97 5.97 0 01-4.45 4.45l.342 1.46zM13.61 4.046a5.97 5.97 0 014.45 4.45l1.46-.343a7.47 7.47 0 00-5.568-5.567l-.342 1.46zm-5.457-1.46a7.47 7.47 0 00-5.567 5.567l1.46.342a5.97 5.97 0 014.45-4.45l-.343-1.46zm8.652 15.28l3.665 3.664 1.06-1.06-3.665-3.665-1.06 1.06z"></path>
                    </svg>
                  </span>
                </label>
                <button class="btn h-9 rounded-l-none bg-primary px-3 font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 lg:px-5">
                  <span class="hidden lg:inline-flex">Search</span>
                  <svg class="size-4.5 lg:hidden" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </button>
                  </div>
                </div>
              </div>
              <div class="card mt-3">
                <?php
                    // ================== Pagination settings ==================
                    $perPage = 6;                  // how many users per page
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    if ($page < 1) $page = 1;

                    // ================== Get TOTAL count (for pagination math) ==================
                    $totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM users"); // change 'users' to your real table name
                    $totalRow   = mysqli_fetch_assoc($totalQuery);
                    $total      = (int)$totalRow['total'];

                    $totalPages = ceil($total / $perPage);

                    // Protect against invalid high page numbers
                    if ($page > $totalPages && $totalPages > 0) {
                        $page = $totalPages;
                    }

                    // ================== Calculate offset & fetch actual page ==================
                    $offset = ($page - 1) * $perPage;

                    // Your main query — add LIMIT + OFFSET
                    // Assuming you already have WHERE / ORDER BY — just append LIMIT
                    $userQuery = mysqli_query($conn, "
                        SELECT * FROM users 
                        -- WHERE ... (your filters if any)
                        ORDER BY id asc 
                        LIMIT $offset, $perPage
                    ");

                    // ================== Range of page numbers to show (clean look) ==================
                    $range = 2; // show 2 pages before & after current → e.g.  ... 4 5 6 7 8 ...
                    $start  = max(1, $page - $range);
                    $end    = min($totalPages, $page + $range);

                    // Stretch if near beginning or end
                    if ($start === 1) {
                        $end = min($totalPages, $start + ($range * 2));
                    }
                    if ($end === $totalPages) {
                        $start = max(1, $end - ($range * 2));
                    }
                    ?>
                <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                  <table class="is-hoverable w-full text-left">
                    <thead>
                      <tr id="user-row-<?= $user['id'] ?>"
                        data-id="<?= $user['id'] ?>"
                        data-name="<?= htmlspecialchars($user['name']) ?>"
                        data-email="<?= htmlspecialchars($user['email']) ?>"
                        data-phone="<?= htmlspecialchars($user['phone']) ?>"
                        data-role="<?= htmlspecialchars($user['role']) ?>"
                        data-location="<?= htmlspecialchars($user['location']) ?>"
                        data-image_paths="<?= htmlspecialchars($user['image_paths']) ?>"
                        @click="setUser($event)"
                        class="cursor-pointer hover:bg-slate-100">
                        <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          #
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Avatar
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Name
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Role
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Status
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Action
                        </th>
                      </tr>
                    </thead>
                    <tbody>

                      <?php
                    if (mysqli_num_rows($userQuery) > 0): 
                    
                      while($row = mysqli_fetch_assoc($userQuery)): ?>
                      <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                         <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= htmlspecialchars($row['id']) ?></td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <div class="avatar flex size-10">
                            <?php 
                              // Get initials from name
                              $nameParts = explode(' ', trim($row['name']));
                              $initials = '';
                              foreach (array_slice($nameParts, 0, 2) as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                              }
                              
                              // Check if image exists in DB and file actually exists on server
                              $imagePath = $row['image_paths'] ?? '';
                              $hasImage = !empty($imagePath) && file_exists(__DIR__ . '/../../' . $imagePath);
                              
                              // Safe initials for use in inline HTML
                              $safeInitials = htmlspecialchars(!empty($initials) ? $initials : 'U', ENT_QUOTES, 'UTF-8');
                            ?>
                            
                        <?php if($hasImage): ?>
                              <!-- Show profile picture from database -->
                              <img class="mask is-octagon w-full h-full object-cover" 
                                   src="<?= htmlspecialchars('../../' . ltrim($imagePath, '/'), ENT_QUOTES, 'UTF-8') ?>" 
                                   alt="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>"
                                   onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display='flex');">
                            <?php endif; ?>
                            
                            <!-- Always render initials div (hidden if image loads) -->
                            <div class="mask is-octagon w-full h-full flex items-center justify-center font-semibold text-sm text-white transition-all" 
                                 style="background: linear-gradient(135deg, #1D9E75 0%, #16a34a 100%); letter-spacing: 0.5px; <?= $hasImage ? 'display: none;' : '' ?>">
                              <?= $safeInitials ?>
                            </div>
                          </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-700 dark:text-navy-100 lg:px-5" x-tooltip="'<?= htmlspecialchars($row['email']) ?>'">
                          <p><?= htmlspecialchars($row['name']) ?></p>
                          <p><?= htmlspecialchars(substr($row['email'], 0, 8)) ?>..</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <?php if($row['role']=='admin'): ?>
                          <div class="badge bg-success text-white shadow-soft shadow-success/50">
                              Admin
                            </div>
                          <?php elseif($row['role']=='farmer'): ?>
                            <div class="badge bg-info text-white shadow-soft shadow-info/50">
                              Farmer
                            </div>
                          <?php elseif($row['role']=='buyer'): ?>
                            <div class="badge bg-primary text-white shadow-soft shadow-primary/50">
                             Buyer
                            </div>
                          <?php else: ?>
                            <div class="badge bg-secondary text-white shadow-soft shadow-secondary/50">
                             Extension
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <?php if($row['status']=='active'): ?>

                           <div class="badge space-x-2.5 text-xs+ text-success">
                          <div class="size-2 rounded-full bg-current"></div>
                          <span>Active</span>
                          </div>

                          <?php elseif($row['status']=='suspended'): ?>

                           <div class="badge space-x-2.5 text-xs+ text-warning">
                          <div class="size-2 rounded-full bg-current"></div>
                          <span>Suspended</span>
                          </div>
                          <?php else: ?>

                          <div class="badge space-x-2.5 text-xs+ text-error">
                          <div class="size-2 rounded-full bg-current"></div>
                          <span>Banned</span>
                          </div>

                          <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                    <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                      </svg>
                    </button>
                    <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                      <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                        <ul>
                          <li>
                              <div class="flex justify-center space-x-2">

                                <!-- DETAIL -->
                                <button @click="viewDetail(<?= $row['id'] ?>)" 
                                    class="btn size-8 p-0 text-success hover:bg-success/20">
                                    <i class="fa fa-eye"></i>
                                </button>

                                <!-- EDIT -->
                                <button @click="openEdit(<?= $row['id'] ?>)" 
                                    class="btn size-8 p-0 text-info hover:bg-info/20">
                                    <i class="fa fa-edit"></i>
                                </button>

                                <!-- DELETE -->
                                <button @click="openDelete(<?= $row['id'] ?>)" 
                                    class="btn size-8 p-0 text-error hover:bg-error/20">
                                    <i class="fa fa-trash-alt"></i>
                                </button>

                              </div>
                            </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                        </td>
                      </tr>
                      <?php endwhile; 
                      else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-500 dark:text-gray-400">
                             <div
                                class="alert flex rounded-lg border border-slate-300 px-4 py-4 text-slate-800 dark:border-navy-450 dark:text-navy-50 sm:px-5"
                              >
                                No users found. Please add a few !
                              </div>
                        </td>
                      </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
                <div x-show="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-80">
                    <h2 class="text-lg font-semibold mb-4">Delete User?</h2>

                    <button @click="deleteUser()" class="btn bg-error text-white">Yes, Delete</button>
                    <button @click="showDeleteModal=false" class="btn ml-2">Cancel</button>
                </div>
            </div>
            <div x-show="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-96">
                    <h2 class="text-lg font-semibold mb-4">Edit User</h2>

                    <input type="text" x-model="editUser.name" class="input mb-2 w-full">
                    <input type="email" x-model="editUser.email" class="input mb-2 w-full">
                    <input type="text" x-model="editUser.phone" class="input mb-2 w-full">

                    <button @click="updateUser()" class="btn bg-primary text-white">Save</button>
                    <button @click="showEditModal=false" class="btn ml-2">Cancel</button>
                </div>
            </div>
                            <!-- Pagination footer -->
            <div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
                <div class="text-xs+">
                    <?php 
                    $from = $offset + 1;
                    $to   = min($offset + $perPage, $total);
                    echo $total > 0 ? "$from - $to of $total entries" : "0 entries";
                    ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <ol class="pagination space-x-1.5">
                    <!-- Previous -->
                    <li>
                        <a href="?page=<?= $page > 1 ? $page-1 : 1 ?>" 
                           class="flex size-8 items-center justify-center rounded-full <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'bg-slate-150 hover:bg-slate-300' ?> text-slate-500 transition-colors dark:bg-navy-500 dark:text-navy-200 dark:hover:bg-navy-450">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    </li>

                    <?php 
                    // Page numbers
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li>
                            <a href="?page=<?= $i ?>" 
                               class="flex h-8 min-w-[2rem] items-center justify-center rounded-full px-3 leading-tight transition-colors <?= $i === $page ? 'bg-primary text-white dark:bg-accent' : 'bg-slate-150 hover:bg-slate-300 dark:bg-navy-500 dark:hover:bg-navy-450' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li>
                        <a href="?page=<?= $page < $totalPages ? $page+1 : $totalPages ?>" 
                           class="flex size-8 items-center justify-center rounded-full <?= $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : 'bg-slate-150 hover:bg-slate-300' ?> text-slate-500 transition-colors dark:bg-navy-500 dark:text-navy-200 dark:hover:bg-navy-450">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                </ol>
                <?php endif; ?>
            </div>
              </div>
            </div>
          </div>