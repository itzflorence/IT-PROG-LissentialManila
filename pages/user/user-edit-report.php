<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">

    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/user/create-report.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
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
                <a href="user-my-reports.php">My Reports</a>
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

<!--====== EDIT REPORT FORM ======-->
<div class="main-wrapper">
    <main>
        <form class="create-report-container" action="#" method="POST" enctype="multipart/form-data">

            <div class="form-header">
                <input type="text" class="input-report-title" value="Gutter-deep flooding outside DLSU after sudden downpour" required>
                <input type="text" class="input-report-desc" value="Heavy torrential rain over the last 30 minutes has caused localized flooding along Taft Ave. Light vehicles are slowing down significantly to navigate the water.">
            </div>

            <div class="form-meta-row">
                <div class="meta-pill">
                    <label for="location-input">LOCATION:</label>
                    <input type="text" id="location-input" value="Taft Avenue, Manila" required>
                </div>

                <div class="meta-pill">
                    <i class="fa-solid fa-shapes category-icon"></i>
                    <label for="category-select">CATEGORY:</label>
                    <select id="category-select" required>
                        <option value="Vehicle Accident">Vehicle Accident</option>
                        <option value="Traffic Congestion">Traffic Congestion</option>
                        <option value="Flooding" selected>Flooding</option>
                        <option value="Road Blockage">Road Blockage</option>
                        <option value="Construction">Construction</option>
                        <option value="Stalled Vehicle">Stalled Vehicle</option>
                        <option value="Traffic Light">Traffic Light</option>
                        <option value="Public Transport">Public Transport</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="media-upload-area" style="position: relative; overflow: hidden;">
                <img src="../../assets/report_media/media1-1.jfif" alt="Attached Media" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">

                <input type="file" id="media-file-input" multiple accept="image/*,video/*" hidden>
                <label for="media-file-input" class="media-upload-label" style="position: absolute; background: rgba(0,0,0,0.5); padding: 12px 24px; border-radius: 8px; backdrop-filter: blur(4px);">
                    <span class="upload-text" style="font-size: 1rem; color: #ffffff;"><i class="fa-solid fa-pen"></i> Replace Media</span>
                </label>
            </div>

            <div class="form-submit-wrapper" style="gap: var(--space-small);">
                <button type="button" class="btn-post-report" onclick="window.location.href='user-my-reports.php'" style="background-color: var(--color3); color: var(--colorText);">Cancel</button>
                <button type="submit" class="btn-post-report">Save Changes</button>
            </div>

        </form>
    </main>
</div>
</body>

</html>