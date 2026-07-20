<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../style/shared/post.css">

    <!-- IMAGE CAROUSEL -->
    <script src="../shared-js/media-carousel.js" defer></script>
</head>

<body>
    <!----------------------------------- NAVIGATION BAR (AND SIDE BAR) ----------------------------------->
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
                <button>CREATE REPORT</button>
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
                    <a href="#">My Reports</a>
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
                <!-- <hr> -->
            </div>

            <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
        </aside>
    </nav>

    <!--====== THREADS ======-->
    <aside class="threads-wrapper">

    </aside>

    <!--====== POSTS ======-->
    <div class="main-wrapper">
        <main>
            <!--============================== POST 1 ==============================-->
            <a href="user-report.php" class="post-link">
                <section class="post">
                    <div class="profile-details">
                        <div class="post-pfp"><img src="../../assets/user_images/user1.jpg" alt=""></div>
                        <span class="username">GreenArcher_01</span>
                        <span>•</span>
                        <span class="hours-ago">Just now</span>
                    </div>

                    <div class="post-details">
                        <!-- location -->
                        <div class="post-details-box">
                            <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                            <span>Taft Avenue, Manila</span>
                        </div>

                        <!-- category -->
                        <div class="post-details-box post-details-box-category">
                            <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                            <span>Flooding</span>
                        </div>

                        <!-- date and time -->
                        <div class="post-details-box">
                            <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                            <span>July 21, 2026</span> | <span>02:14 PM</span>
                        </div>
                    </div>

                    <!---------- title and description---------->
                    <div class="post-title-and-description">
                        <h2><span class="post-title">Gutter-deep flooding outside DLSU after sudden downpour</span></h2>
                        <span class="post-description">Heavy torrential rain over the last 30 minutes has caused
                            localized
                            flooding along Taft Ave, specifically northbound in front of De La Salle University. Light
                            vehicles are slowing down significantly to navigate the water. Gutter-deep, passable but
                            moving
                            very slowly.</span>
                    </div>

                    <!---------- MEDIA ATTACHMENTS ---------->
                    <div class="post-media-carousel">
                        <!-- scroll container -->
                        <div class="carousel-container">

                            <!-- slide 1: Image -->
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
                            <button class="post-resolved">
                                <i class="fa-solid fa-circle-check"></i>
                                Resolved | <span>12</span>
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
                                Status: RESOLVED</button>
                        </div>
                    </div>
                </section>
            </a>
            <hr>

            <!--============================== POST 2 ==============================-->
            <a href="user-report.php?id=2" class="post-link">
                <section class="post">
                    <div class="profile-details">
                        <div class="post-pfp"><img src="../../assets/user_images/user2.jpg" alt=""></div>
                        <span class="username">MichaelJackson</span> <!-- BACKEND PHP LOGIC HERE -->
                        <span>•</span>
                        <span class="hours-ago">36 mins ago</span>
                    </div>

                    <!------------ POST DETAILS ------------>
                    <div class="post-details">
                        <!-- location -->
                        <div class="post-details-box">
                            <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                            <span>Alabang, Muntinlupa</span>
                        </div>

                        <!-- category -->
                        <div class="post-details-box post-details-box-category">
                            <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                            <span>Traffic Congestion</span>
                        </div>

                        <!-- date and time -->
                        <div class="post-details-box">
                            <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                            <span>July 08, 2026</span> | <span>09:30 AM</span>
                        </div>
                    </div>

                    <!---------- TITLE & DESCRIPTION ---------->
                    <div class="post-title-and-description">
                        <!-- title -->
                        <h2><span class="post-title">Traffic congested Near Alabang SLEX Southbound</span></h2>
                        <!-- description -->
                        <span class="post-description">Heavy traffic buildup on SLEX Southbound near the Alabang exit.
                            Appears to be caused by a stalled vehicle blocking the rightmost lane. Traffic is backing up
                            approximately 3km. Avoid this route and use alternative roads. MMDA on the scene.</span>
                    </div>

                    <!---------- MEDIA ATTACHMENTS ---------->
                    <div class="post-media-carousel">
                        <!-- scroll container -->
                        <div class="carousel-container">

                            <!-- slide 1: Image -->
                            <div class="carousel-slide">
                                <img src="../../assets/report_media/media2-1.jpg" alt="">
                            </div>

                            <!-- slide 2: GIF (Handled identically to images) -->
                            <div class="carousel-slide">
                                <img src="../../assets/report_media/media2-2.jfif" alt="">
                            </div>

                            <!-- slide 3: Video Media -->
                            <div class="carousel-slide">
                                <img src="../../assets/report_media/media2-3.jpg" alt="">
                            </div>

                            <!-- slide 4: Video -->
                            <div class="carousel-slide">
                                <video src="../../assets/report_media/media2-4.mp4" controls muted playsinline></video>
                            </div>

                        </div>

                        <!-- Navigation Arrows -->
                        <button class="carousel-btn prev" aria-label="Previous slide" onclick="moveCarousel(this, -1)">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button class="carousel-btn next" aria-label="Next slide" onclick="moveCarousel(this, 1)">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                        <script src="../shared-js/media-carousel.js" defer></script>
                    </div>

                    <!---------- post buttons ---------->
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
            </a>

            <hr>

            <!--============================== POST 3 ==============================-->
            <a href="user-report.php?id=3" class="post-link">
                <section class="post">
                    <div class="profile-details">
                        <div class="post-pfp"><img src="../../assets/user_images/user3.jpg" alt=""></div>
                        <span class="username">ManilaCommuter</span>
                        <span>•</span>
                        <span class="hours-ago">2 hours ago</span>
                    </div>

                    <div class="post-details">
                        <div class="post-details-box">
                            <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                            <span>Ortigas Avenue, Pasig City</span>
                        </div>

                        <div class="post-details-box post-details-box-category">
                            <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                            <span>Vehicle Accident</span>
                        </div>

                        <div class="post-details-box">
                            <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                            <span>July 20, 2026</span> |
                            <span>07:45 AM</span>
                        </div>
                    </div>

                    <div class="post-title-and-description">
                        <h2>
                            <span class="post-title">
                                Multi-vehicle collision near Meralco Avenue intersection
                            </span>
                        </h2>

                        <span class="post-description">
                            A three-car fender bender has blocked two center lanes eastbound on
                            Ortigas Ave right before Meralco Ave. Major bottleneck forming all the
                            way back to EDSA-Ortigas flyover. Enforcers are currently redirecting
                            flow, but expect at least a 20-30 minute delay.
                        </span>
                    </div>

                    <!-- MEDIA ATTACHMENTS -->
                    <div class="post-media-carousel">

                        <div class="carousel-container">
                            <div class="carousel-slide">
                                <img src="../../assets/report_media/media3-1.jfif" alt="">
                            </div>
                        </div>

                        <button class="carousel-btn prev" aria-label="Previous slide" onclick="moveCarousel(this, -1)">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <button class="carousel-btn next" aria-label="Next slide" onclick="moveCarousel(this, 1)">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                    </div>

                    <!-- POST BUTTONS -->
                    <div class="post-buttons">
                        <div class="post-buttons-left">
                            <button class="post-upvote">
                                <i class="fa-solid fa-square-caret-up"></i>
                                <span>87</span>
                            </button>

                            <button class="post-comment">
                                <i class="fa-solid fa-comment-dots"></i>
                                <span>3</span>
                            </button>

                            <button class="post-resolved">
                                <i class="fa-solid fa-circle-check"></i>
                                Resolved | <span>0</span>
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
                                Status: ACTIVE
                            </button>
                        </div>
                    </div>
                </section>
            </a>

            <hr>

            <!--============================== POST 4 ==============================-->
            <a href="user-report.php?id=4" class="post-link">
                <section class="post">
                    <div class="profile-details">
                        <div class="post-pfp"><img src="../../assets/user_images/user4.jpg" alt=""></div>
                        <span class="username">NightOwl_Driver</span>
                        <span>•</span>
                        <span class="hours-ago">Yesterday</span>
                    </div>

                    <div class="post-details">
                        <div class="post-details-box">
                            <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                            <span>Katipunan, Quezon City</span>
                        </div>

                        <div class="post-details-box post-details-box-category">
                            <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                            <span>Road Blockage</span>
                        </div>

                        <div class="post-details-box">
                            <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                            <span>July 20, 2026</span> |
                            <span>10:15 PM</span>
                        </div>
                    </div>

                    <div class="post-title-and-description">
                        <h2>
                            <span class="post-title">
                                Emergency re-blocking near Ateneo Gate 3 Northbound
                            </span>
                        </h2>

                        <span class="post-description">
                            DPWH has unexpectedly blocked off the leftmost lane for urgent asphalt
                            repairs just past the flyover. Heavy machinery is occupying the lane.
                            Tailback is already reaching Aurora Boulevard underpass. Expect
                            slow-moving traffic until early morning.
                        </span>
                    </div>

                    <!-- MEDIA ATTACHMENTS -->
                    <div class="post-media-carousel">

                        <div class="carousel-container">

                            <div class="carousel-slide">
                                <img src="../../assets/report_media/media4-1.png" alt="">
                            </div>
                        </div>

                        <button class="carousel-btn prev" aria-label="Previous slide" onclick="moveCarousel(this, -1)">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <button class="carousel-btn next" aria-label="Next slide" onclick="moveCarousel(this, 1)">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                    </div>

                    <div class="post-buttons">
                        <div class="post-buttons-left">
                            <button class="post-upvote">
                                <i class="fa-solid fa-square-caret-up"></i>
                                <span>18</span>
                            </button>

                            <button class="post-comment">
                                <i class="fa-solid fa-comment-dots"></i>
                                <span>2</span>
                            </button>

                            <button class="post-resolved">
                                <i class="fa-solid fa-circle-check"></i>
                                Resolved | <span>1</span>
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
                                Status: IN PROGRESS
                            </button>
                        </div>
                    </div>
                </section>
            </a>
        </main>
    </div>
</body>

</html>