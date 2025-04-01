<div class="header">
    <div class="header-content">
        <nav class="navbar navbar-expand">
            <div class="collapse navbar-collapse justify-content-between">
                <div class="header-left">
                    <div class="dashboard_bar">
                        <div class="input-group search-area d-lg-inline-flex d-none me-5">
                            <span class="input-group-text" id="header-search">
                                <a href="javascript:void(0);">
                                    <i class="flaticon-381-search-2"></i>
                                </a>
                            </span>
                            <input type="text" class="form-control" placeholder="Search here" aria-label="Username" aria-describedby="header-search">
                        </div>

                    </div>
                </div>
                <ul class="navbar-nav header-right">
                    <li class="nav-item dropdown notification_dropdown">
                        <a class="nav-link bell dz-theme-mode" href="javascript:void(0);">
                            <i id="icon-light" class="fas fa-sun"></i>
                            <i id="icon-dark" class="fas fa-moon"></i>

                        </a>
                    </li>
                    <li class="nav-item dropdown notification_dropdown">
                        <a class="nav-link ai-icon show" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="true">
                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M25.4707 19.1862L23.3333 15.9802V11.6667C23.3333 9.19135 22.35 6.81738 20.5997 5.06704C18.8493 3.3167 16.4753 2.33337 14 2.33337C11.5246 2.33337 9.15066 3.3167 7.40033 5.06704C5.64999 6.81738 4.66666 9.19135 4.66666 11.6667V15.9802L2.52932 19.1862C2.41256 19.362 2.34562 19.5661 2.33561 19.7769C2.32559 19.9877 2.37288 20.1972 2.47245 20.3833C2.57201 20.5693 2.72013 20.7249 2.90106 20.8335C3.08199 20.9421 3.28897 20.9997 3.49999 21H24.5C24.711 20.9997 24.918 20.9421 25.0989 20.8335C25.2798 20.7249 25.428 20.5693 25.5275 20.3833C25.6271 20.1972 25.6744 19.9877 25.6644 19.7769C25.6544 19.5661 25.5874 19.362 25.4707 19.1862Z" fill="#A5A5A5"></path>
                                <path d="M14 25.6666C15.0344 25.6675 16.0397 25.324 16.8572 24.6903C17.6748 24.0565 18.258 23.1686 18.515 22.1666H9.485C9.74197 23.1686 10.3252 24.0565 11.1428 24.6903C11.9603 25.324 12.9656 25.6675 14 25.6666Z" fill="#A5A5A5"></path>
                            </svg>

                            <span class="badge light text-white bg-primary rounded-circle unread-messages-count">0</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" data-bs-popper="static">
                            <div id="dlab_W_Notification1" class="widget-media dz-scroll p-3 height380">
                                <ul class="timeline unread-messages-list"></ul>
                            </div>
                            <!-- <a class="all-notification" href="javascript:void(0)">See all notifications <i class="ti-arrow-right"></i></a> -->
                        </div>
                    </li>
                    <li class="nav-item dropdown header-profile">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="../images/avatar.png" width="20" alt="" class="rounded-circle">
                            <div class="header-info">
                                <span class="logged-user-name"></span>
                                <small class="logged-user-role"></small>
                            </div>
                            <i class="fa fa-caret-down ms-3 me-2 " aria-hidden="true"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="settings" class="dropdown-item ai-icon">
                                <svg id="icon-user1" xmlns="http://www.w3.org/2000/svg" class="text-primary" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span class="ms-2">Profile </span>
                            </a>
                            <!-- <a href="#" class="dropdown-item ai-icon">
                                <svg id="icon-inbox" xmlns="http://www.w3.org/2000/svg" class="text-success" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <span class="ms-2">Inbox </span>
                            </a> -->
                            <a href="#" class="dropdown-item ai-icon" onclick="showSignOutMessage();">
                                <svg id=" icon-logout" xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span class="ms-2">Logout </span>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>