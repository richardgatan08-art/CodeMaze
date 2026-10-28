<?php
require_once __DIR__ . '/php/db.php';

$dbSlides = [];
try {
    $stmt = $pdo->query('SELECT * FROM slides ORDER BY id DESC');
    $dbSlides = $stmt->fetchAll();
} catch (Exception $e) { }

$dbBlogs = [];
try {
    $stmt = $pdo->query('SELECT * FROM blog_entries ORDER BY week ASC');
    $dbBlogs = $stmt->fetchAll();
} catch (Exception $e) {  }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Maze</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="image/log.png">
    <link rel="stylesheet" href="css/website.css">
</head>

<body>
    <header id="main-header">
        <a href="#" class="logo">Code<span>Maze</span></a>
        <nav>
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#blog">Blog</a>
            <a href="#demo">Demo</a>
            <a href="#Lessons">How to Play</a>
            <a href="#developer">Developer</a>
            
        </nav>
    </header>

    <section class="home" id="home">
    <video autoplay loop muted playsinline class="back-video">
        <source src="videos/awevid.mp4" type="video/mp4">
    </video>

    <div class="home-content">
    <h1 style="font-size: 6rem;">Welcome to <span>Code Maze</span></h1>
    
    <!-- Ginawa nating hiwalay na block ang button gamit ang div container -->
    <div class="button-container">
        <a href="User/index.php"><button class="explore-btn">Start Here</button></a>
    </div>
</div>


    <div class="home-img">
        <div class="img-placeholder">
            <img class="m" src="image/codemaze.png" alt="Code Maze Logo">
        </div>
    </div>
</section>

    <section class="about" id="about">
        <h1>About the Project</h1>
        <p style="font-size: 2rem; max-width: 900px; margin-bottom: 3rem; text-align: center; margin:0 auto;">Code
            Runner is designed to assist first-year computer science students reinforce their knowledge of Python, PHP,
            HTML, and JavaScript through a gamified maze environment</p>

        <div class="blog-timeline">
            <div class="blog-post">
                <h3 style="font-size:2.2rem; color:#00FFFF;">Objective</h3>
                <p>Navigate through a maze from start to finish.
                    Solve quizzes at each block to unlock doors and proceed to the next level.
                    Wrong answers add extra bugs or obstacles, making the maze harder.
                    Complete all quiz blocks to reach the final exit of each level.</p>
            </div>

            <div class="blog-post">
                <h3 style="font-size:2.2rem; color:#00FFFF;">Key Features</h3>
                <ul class="blog-list">
                    <li>Grid-Based Movement</li>
                    <li>User-friendly Interface</li>
                    <li>Quest Terminal Logic</li>
                    <li>Bug AI Behavior</li>
                </ul>
            </div>

            <div class="blog-post">
                <h3 style="font-size:2.2rem; color:#00FFFF;">DESIGN PHASES</h3>
                <p style="font-size:2.2rem; color:#00FFFF;">Font Chosen</p>
                <p>"JetBrains Mono", "Fira Code", Consolas, monospace</p>
                <p style="font-size:2.2rem; color:#00FFFF;">Color Palette</p>
                <img src="image/pallete.jpg" width="75%" alt="Code Maze Color Palette">
            </div>

            <div class="blog-post">
                <h3 style="font-size:2.2rem; color:#00FFFF;">CODING PHASES</h3>
                <p><img src="image/javascript.jpg" width="75%" alt="JavaScript Documentation"></p>
                <p><img src="image/html.webp" width="75%" alt="HTML Documentation"></p>
                <p><img src="image/css.jpg" width="75%" alt="CSS Documentation"></p>
            </div>

            <div class="blog-post">
                <h3 style="font-size:2.2rem; color:#00FFFF;">TARGET USER PROFILE</h3>
                <p>Primary: First-year Computer Science of North Negros
                    College.</p>
                <p>Knowledge Level: Beginners familiar with basic syntax but
                    requiring practice in "thinking under pressure."</p>
            </div>

        </div>
    </section>
    <section class="blog" id="blog">
        <h1>Progress Blog</h1>
        <div class="blog-timeline">
            <?php if (!empty($dbBlogs)): ?>
                <?php
                $delays = [0, 0, 0, 0, 100, 200, 300];
                foreach ($dbBlogs as $idx => $entry):
                    $delay = $delays[$idx] ?? 300;
                ?>
                <div class="blog-post" data-delay="<?= $delay ?>">
                    <div class="blog-meta">
                        <span class="blog-week">Week <?= (int)$entry['week'] ?></span>
                        <span class="blog-date">📅 <?= htmlspecialchars(date('M d, Y', strtotime($entry['created_at']))) ?></span>
                    </div>
                    <div class="blog-content">
                        <p style="font-size:1.8rem; line-height:1.7; white-space:pre-line;"><?= htmlspecialchars($entry['text']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback static blog when DB has no entries yet -->
                <div class="blog-post" data-delay="0">
                    <div class="blog-meta">
                        <span class="blog-week">Week 1</span>
                        <span class="blog-date">📅 Feb 12 - Feb 16</span>
                    </div>
                    <h3 style="font-size:2.2rem; color:#00FFFF;">🚀 Documentation &amp; Data Gathering Preparation</h3>
                    <div class="blog-content">
                        <span class="blog-status completed"><i class="fas fa-check-circle"></i> Completed</span>
                        <ul class="blog-list">
                            <li>✅ Weekly Accomplishment Report</li>
                            <li>✅ Initial SRS in Google Docs</li>
                            <li>✅ Google Form (Survey)</li>
                            <li>✅ Hi-Fi Design in Landscape PDF format</li>
                            <li>✅ Scanned Signed Letter of Approval</li>
                        </ul>
                    </div>
                </div>
                <div class="blog-post" data-delay="0">
                    <div class="blog-meta">
                        <span class="blog-week">Week 2</span>
                        <span class="blog-date">📅 Feb 17 - Feb 23</span>
                    </div>
                    <h3 style="font-size:2.2rem; color:#00FFFF;">🚀 Data Gathering &amp; Design Finalization</h3>
                    <div class="blog-content">
                        <span class="blog-status completed"><i class="fas fa-check-circle"></i> Completed</span>
                        <ul class="blog-list">
                            <li>✅ Weekly Accomplishment Report</li>
                            <li>✅ Final SRS in .pdf format</li>
                            <li>✅ Survey Results in Google Sheets</li>
                        </ul>
                    </div>
                </div>
                <div class="blog-post" data-delay="100">
                    <div class="blog-meta">
                        <span class="blog-week">Week 3</span>
                        <span class="blog-date">📅 Feb 24 - Mar 1</span>
                    </div>
                    <h3 style="font-size:2.2rem; color:#00FFFF;">🚀 Begin Website Development</h3>
                    <div class="blog-content">
                        <span class="blog-status completed"><i class="fas fa-check-circle"></i> Completed</span>
                        <ul class="blog-list">
                            <li>✅ Weekly Accomplishment Report</li>
                            <li>✅ Compiled Screenshot of Website Development in .pdf</li>
                            <li>✅ Initial Website Design Mockups</li>
                            <li>✅ Initial Website Content</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="demons" id="demo">
        <h1>System Demo</h1>
        <div class="demo-list">
            <p style="font-size:2rem; margin-bottom:2rem;"> A glimpse into our system </p>
            <div class="demo-slider">
                <div class="slider-container">
                    <div class="slider-wrapper" id="sliderWrapper">

                        <?php if (!empty($dbSlides)): ?>
                            <?php foreach ($dbSlides as $i => $s): ?>
                            <div class="slide">
                                <img src="uploads/<?= rawurlencode($s['image']) ?>" alt="<?= htmlspecialchars($s['title'] ?: 'Slide ' . ($i + 1)) ?>">
                                <div class="slide-caption">
                                    <span><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                                    — <?= htmlspecialchars($s['description'] ?: $s['title'] ?: 'Demo Slide') ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback static slides when DB has no entries yet -->
                            <div class="slide">
                                <img src="image/bugs.png" alt="Student Dashboard">
                                <div class="slide-caption"><span>01</span> — Student Dashboard</div>
                            </div>
                            <div class="slide">
                                <img src="image/codemaze.png" alt="Progress Tracker">
                                <div class="slide-caption"><span>02</span> — Progress Tracker</div>
                            </div>
                            <div class="slide">
                                <img src="image/sprite.png" alt="Documentation Hub">
                                <div class="slide-caption"><span>03</span> — Documentation Hub</div>
                            </div>
                            <div class="slide">
                                <img src="image/GAMES.png" alt="Mobile Responsive">
                                <div class="slide-caption"><span>04</span> — Mobile Responsive</div>
                            </div>
                        <?php endif; ?>

                    </div>
                    <div class="slider-btn prev" id="prevSlide"><i class="fas fa-chevron-left"></i></div>
                    <div class="slider-btn next" id="nextSlide"><i class="fas fa-chevron-right"></i></div>
                </div>
                <div class="slider-dots" id="sliderDots"></div>
            </div>
            <p style="max-width:700px; margin-top:3rem; font-size:1.9rem;">Key usability considerations include
                user-friendly UI and mobile responsiveness. Use the arrows to explore screens.</p>
        </div>
    </section>

    <section class="lessons" id="Lessons">
        <h1 style="text-align:center; color:#00FFFF; ">🎮 How to Play</h1>   
        <div class="lesson-body">
            
            <br><p style="font-size:1.6rem;">📝 How to Answer the Questions</p></br>

          <p style="font-size:2rem; color:#00FFFF;">Step 1 – Read the Question</p>
         <p style="font-size:1.6rem;"> Read the question carefully before choosing your answer.</p></br>
        
          <p style="font-size:2rem; color:#00FFFF;">Step 2 – Check the Choices</p>
          <p style="font-size:1.6rem;">Read all the available choices and think about which one is correct.</p></br>
          
          <p style="font-size:2rem; color:#00FFFF;">Step 3 – Select Your Answer</p>
          <p style="font-size:1.6rem;">Click on the answer you believe is correct.</p></br>

          <p style="font-size:2rem; color:#00FFFF;">Step 4 – Submit Your Answer</p>
          <p style="font-size:1.6rem;">Click the Submit button to check your answer.</p></br>

          <br><p style="font-size:1.6rem;">✅ If Your Answer Is Correct
         The question will be marked as correct. The door will unlock. You can proceed to the next part of the maze. You may receive points, rewards, or power-ups.</p><br>
     
         <p style="font-size:1.6rem;">❌ If Your Answer Is Wrong Your answer will be marked as incorrect. An obstacle will be added to the maze. The maze becomes more challenging.

         Think carefully and continue solving the next questions.</p><br>
 
         <p style="font-size:1.6rem;">🧩 Navigating the Maze

         Follow the available paths. 
 
         Look for doors and question areas.
         Avoid obstacles.

          Explore the maze to find the correct route.
  
         Use your rewards and power-ups when needed.</p><br>
 
         <p style="font-size:1.6rem;">🏆 Your Goal
          Answer the questions correctly, unlock the doors, avoid obstacles, collect rewards, and reach the end of the maze!</p><br>

         <p style="font-size:1.6rem;">💡 Tip: Read each question carefully and think before answering. Every decision can affect your journey through the maze.</p><br>
        </div>
    </section>

    <section class="developer" id="developer">
        <h1>Game Developers</h1>
        <div class="contact-list">
            <p>👥 Group Members</p>
            <div class="card-container">
                <div class="photo-card">
                    <div class="card-img">
                        <img src="image/richard.jpg" alt="Richard Gatan">
                    </div>
                    <h3>Gatan, Richard</h3>
                    <p>Project Manager</p>
                    <p><i class="fas fa-envelope"></i> richardgatan@gmail.com</p>
                </div>

                <div class="photo-card">
                    <div class="card-img">
                        <img src="image/ex_nival.jpg" alt="Julie Grace Gepanaga">
                    </div>
                    <h3>Gepanaga, Julie Grace</h3>
                    <p>Back-end Developer</p>
                    <p><i class="fas fa-envelope"></i> julie_grace@gmail.com</p>
                </div>

                <div class="photo-card">
                    <div class="card-img">
                        <img src="image/bago_yanggaw.jpg" alt="Nicole Jade Ferrer">
                    </div>
                    <h3>Ferrer, Nicole Jade</h3>
                    <p>Front-end Developer</p>
                    <p><i class="fas fa-envelope"></i> nicolejade1@gmail.com</p>
                </div>

                <div class="photo-card">
                    <div class="card-img">
                        <img src="image/wetewew.jpg" alt="Cristy Marie Pintor">
                    </div>
                    <h3>Pintor, Cristy Marie</h3>
                    <p>Front-end Developer</p>
                    <p><i class="fas fa-envelope"></i> pintor.dc@gmail.com</p>
                </div>

                
            </div>
        </div>
    </section>

    <script src="js/website.js"></script>
</body>

</html>
