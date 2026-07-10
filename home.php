<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style/post.css">
</head>

<body>
    <!-- NAVIGATION BAR (HEADER AND ASIDE) -->
    <nav>
        <header class="navbar">
            <div class="logo">LOGO</div>

            <input type="search" placeholder="Search what you need...">

            <button type="icon-button">
                <i class="fa-solid fa-bell"></i>
            </button>

            <button type="icon-button">
                <i class="fa-solid fa-user"></i>
            </button>
        </header>

        <aside class="sidebar">
            <div class="create-report">
                <button>CREATE REPORT</button>
            </div>

            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">THREADS</span>
                <div class="sidebar-options">
                    <a href="#">Active</a>
                    <a href="#">Resolved</a>
                    <a href="#">Archived</a>
                </div>
                <hr>
            </div>

            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">CATEGORIES</span>
                <div class="sidebar-options">
                    <a href="#">Flooding</a>
                    <a href="#">Car Crash</a>
                    <a href="#">Road Blockage</a>
                    <a href="#">Construction</a>
                    <a href="#">Other</a>
                </div>
                <hr>
            </div>

            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">MY ACTIVITY</span>
                <div class="sidebar-options">
                    <a href="#">My Reports</a>
                    <a href="#">Saved Reports</a>
                </div>
            </div>
        </aside>
    </nav>

    <aside class="threads-wrapper">

    </aside>

    <div class="main-wrapper">
        <main>
            <section class="post">
                <div class="profile-details">
                    <div class="post-pfp"><img src="mj.jpg" alt=""></div>
                    <span class="username">Michael Jackson</span> <!-- to be changed -->
                    <span>•</span>
                    <span class="hours-ago">36 mins ago</span>
                </div>

                <div class="post-details">
                    <!-- location -->
                    <div class="post-details-box">
                        <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                        <span>Alabang, Muntinlupa</span>
                    </div>

                    <!-- category -->
                    <div class="post-details-box post-details-box-category">
                        <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                        <span>Other</span>
                    </div>

                    <!-- date and time -->
                    <div class="post-details-box">
                        <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                        <span>July 08, 2026</span> | <span>09:30 AM</span>
                    </div>
                </div>

                <div class="post-title-and-description">
                    <h2><span class="post-title">Traffic congested Near Alabang SLEX Southbound</span></h2>
                    <span class="post-description">Heavy traffic buildup on SLEX Southbound near the Alabang exit. Appears to be caused by a stalled vehicle blocking the rightmost lane. Traffic is backing up approximately 3km. Avoid this route and use alternative roads. MMDA on the scene.</span>
                </div>

                <div class="post-image">
                    <img src="images.jpg" alt="">
                </div>

                <div class="post-buttons">
                    <div class="post-buttons-left">
                        <button class="post-upvote">
                            <i class="fa-solid fa-square-caret-up"></i>
                            <span>34</span>
                        </button>
                        <button class="post-comment">
                            <i class="fa-solid fa-comment-dots"></i>
                            <span>2</span>
                        </button>
                        <button class="post-resolved">
                            <i class="fa-solid fa-circle-check"></i>
                            Resolved | <span>3</span>
                        </button>
                        <button class="post-saved">
                            <i class="fa-solid fa-bookmark"></i>
                        </button>
                    </div>

                    <div class="post-buttons-right">
                        <button class="verified">
                            <i class="fa-solid fa-user-check"></i>
                            Verified by Officials
                        </button>
                        <button class="status">
                            Status: ACTIVE</button>
                    </div>
                </div>
            </section>
        
            <hr>
        
            <section class="post">
                <div class="profile-details">
                    <div class="post-pfp"><img src="mj.jpg" alt=""></div>
                    <span class="username">Michael Jackson</span> <!-- to be changed -->
                    <span>•</span>
                    <span class="hours-ago">36 mins ago</span>
                </div>

                <div class="post-details">
                    <!-- location -->
                    <div class="post-details-box">
                        <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                        <span>Alabang, Muntinlupa</span>
                    </div>

                    <!-- category -->
                    <div class="post-details-box post-details-box-category">
                        <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                        <span>Other</span>
                    </div>

                    <!-- date and time -->
                    <div class="post-details-box">
                        <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                        <span>July 08, 2026</span> | <span>09:30 AM</span>
                    </div>
                </div>

                <div class="post-title-and-description">
                    <h2><span class="post-title">Traffic congested Near Alabang SLEX Southbound</span></h2>
                    <span class="post-description">Heavy traffic buildup on SLEX Southbound near the Alabang exit. Appears to be caused by a stalled vehicle blocking the rightmost lane. Traffic is backing up approximately 3km. Avoid this route and use alternative roads. MMDA on the scene.</span>
                </div>

                <div class="post-image">
                    <img src="images.jpg" alt="">
                </div>

                <div class="post-buttons">
                    <div class="post-buttons-left">
                        <button class="post-upvote">
                            <i class="fa-solid fa-square-caret-up"></i>
                            <span>34</span>
                        </button>
                        <button class="post-comment">
                            <i class="fa-solid fa-comment-dots"></i>
                            <span>2</span>
                        </button>
                        <button class="post-resolved">
                            <i class="fa-solid fa-circle-check"></i>
                            Resolved | <span>3</span>
                        </button>
                        <button class="post-saved">
                            <i class="fa-solid fa-bookmark"></i>
                        </button>
                    </div>

                    <div class="post-buttons-right">
                        <button class="verified">
                            <i class="fa-solid fa-user-check"></i>
                            Verified by Officials
                        </button>
                        <button class="status">
                            Status: ACTIVE</button>
                    </div>
                </div>
            </section>

        </main>
    </div>
</body>

</html>