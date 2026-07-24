<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/shared/post.css">

    <link rel="stylesheet" href="../../style/user/home.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="../shared-js/media-carousel.js" defer></script>
</head>

<body>
<!----------------------------------- NAVIGATION BAR & SIDEBAR ----------------------------------->
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="user-home.php">
                <img src="../../assets/LOGO/logo_normal.png" alt="LissentialManila Logo">
            </a>
        </div>

        <div class="searchbar">
            <input type="search" placeholder="Search what you need...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="icon-button-wrapper">
            <button type="button" class="icon-button">
                <i class="fa-solid fa-bell"></i>
            </button>

            <button type="button" class="icon-button">
                <i class="fa-solid fa-user"></i>
            </button>
        </div>
    </header>

    <aside class="sidebar">
        <div class="create-report">
            <button onclick="window.location.href='user-create-report.php'">CREATE REPORT</button>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">FEED</span>
            <div class="sidebar-options">
                <a href="user-home.php">All Reports</a>
                <a href="#">Reports Near Me</a>
                <a href="#">Official Advisories</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">MY ACTIVITY</span>
            <div class="sidebar-options">
                <a href="user-my-reports.php" style="font-weight: bold;">My Reports</a>
                <a href="#">Reports Near Me</a>
                <a href="#">Saved Locations</a>
                <a href="#">My Comments</a>
                <a href="#">Account Profile</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">CATEGORIES</span>
            <div class="sidebar-options">
                <a href="#">Vehicle Accident</a>
                <a href="#">Traffic Congestion</a>
                <a href="#">Flooding</a>
                <a href="#">Road Blockage</a>
                <a href="#">Construction</a>
                <a href="#">Stalled Vehicle</a>
                <a href="#">Traffic Light</a>
                <a href="#">Public Transport</a>
                <a href="#">Other</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">THREADS</span>
            <div class="sidebar-options">
                <a href="#">Active</a>
                <a href="#">Resolved</a>
                <a href="#">Archived</a>
            </div>
        </div>

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<!--====== THREADS / RIGHT PANEL ======-->
<aside class="threads-wrapper">
</aside>

<!--====== MY REPORTS FEED ======-->
<div class="main-wrapper">
    <main style="display: flex; flex-direction: column; gap: var(--space-medium);">

        <div class="my-reports-header" style="text-align: center; padding: var(--space-medium) 0 var(--space-xsmall) 0;">
            <h1 style="font-size: var(--font-large); font-weight: 700; color: var(--colorText); margin-bottom: 4px;">My Reports</h1>
            <p style="font-size: var(--font-small); color: var(--color4); margin-bottom: 8px;">Manage and track incidents you've reported</p>
            <hr style="width: 140px; margin: 0 auto; border: none; height: 1px; background-color: var(--color3);">
        </div>

        <a href="user-report.php?id=1" class="post-link">
            <section class="post">
                <div class="profile-details">
                    <div class="post-pfp"><img src="../../assets/user_images/user1.jpg" alt="Profile"></div>
                    <span class="username">You</span>
                    <span>•</span>
                    <span class="hours-ago">Just now</span>
                </div>

                <div class="post-details">
                    <div class="post-details-box">
                        <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                        <span>Taft Avenue, Manila</span>
                    </div>

                    <div class="post-details-box post-details-box-category">
                        <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                        <span>Flooding</span>
                    </div>

                    <div class="post-details-box">
                        <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                        <span>July 24, 2026</span> | <span>02:14 PM</span>
                    </div>
                </div>

                <div class="post-title-and-description">
                    <h2><span class="post-title">Gutter-deep flooding outside DLSU after sudden downpour</span></h2>
                    <span class="post-description">Heavy torrential rain over the last 30 minutes has caused localized flooding along Taft Ave. Light vehicles are slowing down significantly to navigate the water.</span>
                </div>

                <div class="post-media-carousel">
                    <div class="carousel-container">
                        <div class="carousel-slide">
                            <img src="../../assets/report_media/media1-1.jfif" alt="">
                        </div>
                    </div>
                </div>

                <div class="post-buttons">
                    <div class="post-buttons-left">
                        <button class="post-upvote">
                            <i class="fa-solid fa-square-caret-up"></i>
                            <span>52</span>
                        </button>
                        <button class="post-comment">
                            <i class="fa-solid fa-comment-dots"></i>
                            <span>2</span>
                        </button>
                        <!-- EDIT REPORT LINK -->
                        <button class="post-edit" onclick="event.preventDefault(); window.location.href='user-edit-report.php?id=1';" style="background-color: var(--color3); color: var(--colorText); cursor: pointer; padding: 4px 12px; font-weight: 600;">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Edit Report
                        </button>
                    </div>

                    <div class="post-buttons-right">
                        <button class="verified">
                            <i class="fa-solid fa-user-check"></i>
                            Verified by Officials
                        </button>
                        <button class="status">Status: RESOLVED</button>
                    </div>
                </div>
            </section>
        </a>

    </main>
</div>
</body>

</html>