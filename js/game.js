 const canvas = document.getElementById('game-canvas');
        const ctx = canvas.getContext('2d');
        
        const scoreElement = document.getElementById('score');
        const livesElement = document.getElementById('lives');
        const levelElement = document.getElementById('level');
        const questsCompletedElement = document.getElementById('quests-completed');
        const timerElement = document.getElementById('timer');
        const difficultyElement = document.getElementById('difficulty');
        const levelCompleteMessage = document.getElementById('level-complete-message');
        const storyLoadingMessage = document.getElementById('story-loading-message');
        const gameOverMessage = document.getElementById('game-over-message');
        const nextLevelBtn = document.getElementById('next-level-btn');
        const restartBtn = document.getElementById('restart-btn');
        const questListElement = document.getElementById('quest-list');
        
        const totalStories = 5;
        const requestedStoryId = Math.max(1, parseInt(new URLSearchParams(window.location.search).get('story'), 10) || 1);
        const unlockedStoryLevel = Math.max(1, parseInt(localStorage.getItem('unlockedStoryLevel'), 10) || 1);
        const maxPlayableStory = Math.min(unlockedStoryLevel, totalStories);
        const storyId = Math.max(1, Math.min(requestedStoryId, maxPlayableStory));

        if (requestedStoryId !== storyId) {
            window.history.replaceState(null, '', `game.php?story=${storyId}`);
        }
        const MAP_ROWS = 15;
        const MAP_COLS = 24;

        const storyThemes = {
            1: { name: 'office', image: '', floor: '#1c16100f', wall: '#30bed18b', overlay: 'rgba(40, 28, 12, 0.04)' },
            2: { name: 'cave', image: '../image/town.jpg', floor: '#121018', wall: '#4a3f55', overlay: 'rgba(20, 12, 32, 0.45)' },
            3: { name: 'trap', image: '../image/excited.jpg', floor: '#102018', wall: '#2e7d32', overlay: 'rgba(12, 36, 24, 0.4)' },
            4: { name: 'maze', image: '../image/4.jpg', floor: '#0e1418', wall: '#37474f', overlay: 'rgba(8, 20, 28, 0.4)' },
            5: { name: 'core', image: '../image/box.jpg', floor: '#140c10', wall: '#6a1b9a', overlay: 'rgba(40, 8, 24, 0.45)' }
        };
        const activeTheme = storyThemes[storyId] || storyThemes[1];
        const themeBgImage = new Image();
        themeBgImage.src = activeTheme.image;
        
        const pauseMenu = document.getElementById('pause-menu');
        const resumeBtn = document.getElementById('resume-btn');
        const pauseRestartBtn = document.getElementById('pause-restart-btn');
        const quitBtn = document.getElementById('quit-btn');
        const pauseLevelElement = document.getElementById('pause-level');
        const pauseScoreElement = document.getElementById('pause-score');
        const pauseLivesElement = document.getElementById('pause-lives');
        const pauseTimeElement = document.getElementById('pause-time');
        const pauseDifficultyElement = document.getElementById('pause-difficulty');
        
        const questPopup = document.getElementById('quest-popup');
        const questQuestion = document.getElementById('quest-question');
        const questCode = document.getElementById('quest-code');
        const questOptions = document.getElementById('quest-options');
        const questResult = document.getElementById('quest-result');
        const questSubmitBtn = document.getElementById('quest-submit-btn');
        const questCloseBtn = document.getElementById('quest-close-btn');
        const questExitBtn = document.getElementById('quest-exit-btn');
        const questHintBtn = document.getElementById('quest-hint-btn');
        const questNumberElement = document.getElementById('quest-number');
        const questTimeLeftElement = document.getElementById('quest-time-left');
        const languageBadge = document.getElementById('language-badge');
        
        const backgroundMusic = document.getElementById('background-music');
        const collectSound = document.getElementById('collect-sound');
        const hitSound = document.getElementById('hit-sound');
        const doorSound = document.getElementById('door-sound');
        const levelCompleteSound = document.getElementById('level-complete-sound');
        const gameOverSound = document.getElementById('game-over-sound');
        const pauseSound = document.getElementById('pause-sound');
        const tickSound = document.getElementById('tick-sound');
        const wrongAnswerSound = document.getElementById('wrong-answer-sound');
        
        const soundToggleBtn = document.getElementById('sound-toggle');
        const musicToggleBtn = document.getElementById('music-toggle');
        
        let score = 0;
        let lives = 3;
        let level = storyId;
        let lightModeCheatActive = false;
        let questsCompleted = 0;
        let questsRequired = 3;
        let gameRunning = true;
        let gamePaused = false;
        let doorOpen = false;
        let questActive = false;
        let currentQuestTerminal = null;
        let gameFinished = false;
        let finishAnimationTimer = 0;
        
        let timeLeft = 120;
        let timerInterval = null;
        
        let questTimeLeft = 30;
        let questTimerInterval = null;
        
        let soundEnabled = true;
        let musicEnabled = true;
        
        const GRID_SIZE = 50;
        const GRID_COLS = 20;
        const GRID_ROWS = 20;
        
        let currentMap = [];
        let walls = [];

        const difficultyLevels = {
            1: { name: "VERY EASY", color: "#4caf50", timeMultiplier: 1.2 },
            2: { name: "EASY", color: "#2196f3", timeMultiplier: 1.0 },
            3: { name: "NORMAL", color: "#ff9800", timeMultiplier: 0.85 },
            4: { name: "HARD", color: "#f44336", timeMultiplier: 0.65 },
            5: { name: "VERY HARD", color: "#9c27b0", timeMultiplier: 0.5 }
        };

        function shuffleDirs(dirs) {
            for (let i = dirs.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [dirs[i], dirs[j]] = [dirs[j], dirs[i]];
            }
            return dirs;
        }

        function generateRandomMaze() {
            const rows = MAP_ROWS;
            const cols = MAP_COLS;
            const maze = Array.from({ length: rows }, () => Array(cols).fill(1));

            function inBounds(r, c) {
                return r > 0 && r < rows - 1 && c > 0 && c < cols - 1;
            }

            function carve(r, c) {
                maze[r][c] = 0;
                const dirs = shuffleDirs([[0, 2], [0, -2], [2, 0], [-2, 0]]);
                dirs.forEach(([dr, dc]) => {
                    const nr = r + dr;
                    const nc = c + dc;
                    if (inBounds(nr, nc) && maze[nr][nc] === 1) {
                        maze[r + dr / 2][c + dc / 2] = 0;
                        carve(nr, nc);
                    }
                });
            }

            const oddRows = [];
            const oddCols = [];
            for (let r = 1; r < rows - 1; r += 2) oddRows.push(r);
            for (let c = 1; c < cols - 1; c += 2) oddCols.push(c);
            const startR = oddRows[Math.floor(Math.random() * oddRows.length)] || 1;
            const startC = oddCols[Math.floor(Math.random() * oddCols.length)] || 1;
            carve(startR, startC);

            const extraOpenings = 18 + Math.floor(Math.random() * 16);
            let added = 0;
            let guard = 0;
            while (added < extraOpenings && guard < 400) {
                guard++;
                const r = 1 + Math.floor(Math.random() * (rows - 2));
                const c = 1 + Math.floor(Math.random() * (cols - 2));
                if (maze[r][c] === 1) {
                    const openNeighbors = [[r - 1, c], [r + 1, c], [r, c - 1], [r, c + 1]]
                        .filter(([nr, nc]) => maze[nr] && maze[nr][nc] === 0);
                    if (openNeighbors.length >= 1) {
                        maze[r][c] = 0;
                        added++;
                    }
                }
            }

            maze[startR][startC] = 2;
            return maze;
        }

        function applyStoryTheme() {
            document.body.classList.add('story-themed');
            document.body.dataset.storyTheme = activeTheme.name;
            document.body.style.backgroundImage = `linear-gradient(${activeTheme.overlay}, ${activeTheme.overlay}), url('${activeTheme.image}')`;
            document.body.style.backgroundSize = 'cover';
            document.body.style.backgroundPosition = 'center';
            document.body.style.backgroundAttachment = 'fixed';

            const video = document.getElementById('bg-video');
            if (video) {
                video.style.opacity = '0.12';
            }

            const canvasEl = document.getElementById('game-canvas');
            if (canvasEl) {
                canvasEl.style.background = activeTheme.floor;
                canvasEl.style.borderColor = activeTheme.wall;
            }
        }
        
        const playerSprite = new Image();
        playerSprite.src = '../image/sprites.png';

        // Holder para sa lain-lain nga bug image taga-level
        const bugImages = {
            1: '../image/bugs.png',     // Level 1 bug image (pwede mo ilisan sang file path mo)
            2: '../image/monster_core_noBG.png', // Level 2 bug image 
            3: '../image/monster_trap-Final.jpg', // Level 3 bug image 
            4: '../image/monster-4.png', // Level 4 bug image 
            5: '../image/moster_boss.jpg'  // Level 5 bug image 
        };

        const bugSprite = new Image();
        bugSprite.src = bugImages[storyId] || '../image/bugs.png';

        const boxSprite = new Image();
        boxSprite.src = '../image/sprite.png';

        const doorSprite = new Image();
        doorSprite.src = '../image/do.png';
        let doorSpriteLoaded = false;
        
        doorSprite.onload = function() {
            if (doorSprite.complete && doorSprite.naturalHeight !== 0) {
                doorSpriteLoaded = true;
            }
        };

        const wallSprite = new Image();
        wallSprite.src = '../picture/wall.png';
        let wallSpriteLoaded = false;
        
        wallSprite.onload = function() {
            if (wallSprite.complete && wallSprite.naturalHeight !== 0) {
                wallSpriteLoaded = true;
            }
        };


        const SPRITE_COLS = 19;  
        const SPRITE_ROWS = 4; 

        let spriteSheetLoaded = false;
        let bugSpriteLoaded = false;
        let boxSpriteLoaded = false;
        
        let bugFrameWidth = 50;
        let bugFrameHeight = 50;
        let boxFrameWidth = 20;
        let boxFrameHeight = 50;
        
        let actualFrameWidth = 50;
        let actualFrameHeight = 22;
        
        const BUG_SPRITE_COLS = 4;
        const BUG_SPRITE_ROWS = 3.9;
        const BOX_SPRITE_COLS = 1;

        playerSprite.onload = function() {
            if (playerSprite.complete && playerSprite.naturalHeight !== 0) {
              
                actualFrameWidth = playerSprite.naturalWidth / SPRITE_COLS;
                actualFrameHeight = playerSprite.naturalHeight / SPRITE_ROWS;
                spriteSheetLoaded = true;
                console.log('Sprite sheet loaded:', playerSprite.naturalWidth, 'x', playerSprite.naturalHeight);
                console.log('Auto-fitted frame dimensions:', actualFrameWidth, 'x', actualFrameHeight);
            }
        };

       
        let actualBugFrameWidth = 30;
        let actualBugFrameHeight = 20;

        bugSprite.onload = function() {
            if (bugSprite.complete && bugSprite.naturalHeight !== 0) {
                actualBugFrameWidth = bugSprite.naturalWidth / BUG_SPRITE_COLS;
                actualBugFrameHeight = bugSprite.naturalHeight / BUG_SPRITE_ROWS;
                bugSpriteLoaded = true;
                console.log('Bug sprite sheet loaded:', bugSprite.naturalWidth, 'x', bugSprite.naturalHeight);
                console.log('Auto-fitted bug frame dimensions:', actualBugFrameWidth, 'x', actualBugFrameHeight);
            }
        };

        const player = {
            x: 90,
            y: 80,
            width: 30, 
            height: 30,
            speed: 4,
            moving: false,
            direction: 'down',
            isInvulnerable: false,
            frameX: 0,
            frameY: 0,
            frameWidth: actualFrameWidth,
            frameHeight: actualFrameHeight,
            animationSpeed: 0,
            animationCounter: 0,
            bobOffset: 0,
            bobSpeed: 0.3,
            bobCounter: 0,
            draw: function() {
                let frameW = 80;
                let frameH = 92;
                
                if (playerSprite.complete && playerSprite.naturalHeight !== 0 && spriteSheetLoaded) {
                    frameW = actualFrameWidth;
                    frameH = actualFrameHeight;
                }
                
              
                let verticalBob = 0;
                let horizontalTilt = 0;
                
                if (this.moving) {
                    this.bobCounter += this.bobSpeed;
                    const bobAmount = Math.sin(this.bobCounter) * 5;
                    
                    switch(this.direction) {
                        case 'up':
                        case 'down':
                            verticalBob = bobAmount;
                            break;
                        case 'left':
                            horizontalTilt = bobAmount;
                            break;
                        case 'right':
                            horizontalTilt = -bobAmount;
                            break;
                    }
                } else {
                    this.bobCounter = 0;
                    this.bobOffset = 0;
                }
                
                if (playerSprite.complete && playerSprite.naturalHeight !== 0) {
                    let spriteRow = 0;
                    switch(this.direction) {
                        case 'up': spriteRow = 2; break;
                        case 'down': spriteRow = 0; break;
                        case 'left': spriteRow = 3; break;
                        case 'right': spriteRow = 1; break;
                    }

                  
                    const squareSize = Math.max(frameW, frameH);
                    
                    const scaleX = this.width / squareSize;
                    const scaleY = this.height / squareSize;
                    
                    const drawWidth = squareSize * scaleX;
                    const drawHeight = squareSize * scaleY;
                    
                   
                    const drawX = this.x - (drawWidth - this.width) / 2 + horizontalTilt;
                    const drawY = this.y - (drawHeight - this.height) / 2 + verticalBob;

                    ctx.drawImage(
                        playerSprite,
                        this.frameX * frameW,
                        spriteRow * frameH,
                        frameW,
                        frameH,
                        drawX,
                        drawY,
                        drawWidth,
                        drawHeight
                    );

                    if (this.moving) {
                        this.animationCounter += this.animationSpeed;
                        if (this.animationCounter >= 1) {
                            this.animationCounter = 0;
                            this.frameX++;
                            if (this.frameX >= 4) {
                                this.frameX = 0;
                            }
                        }
                    } else {
                       
                        this.frameX = 0;
                        this.animationCounter = 0;
                    }
                } else {
                    const squareSize = Math.max(this.width, this.height);
                    const drawWidth = squareSize;
                    const drawHeight = squareSize;
                    const drawX = this.x - (drawWidth - this.width) / 2 + horizontalTilt;
                    const drawY = this.y - (drawHeight - this.height) / 2 + verticalBob;
                    
                    ctx.fillStyle = '#4fc3f7';
                    ctx.fillRect(drawX, drawY, drawWidth, drawHeight);

                    ctx.fillStyle = '#e3f2fd';
                    ctx.fillRect(drawX + 5, drawY - 8, drawWidth - 10, 12);

                    ctx.fillStyle = '#0d47a1';
                    ctx.fillRect(drawX + 8, drawY - 5, 4, 4);
                    ctx.fillRect(drawX + drawWidth - 12, drawY - 5, 4, 4);

                    ctx.fillStyle = '#0d1520';
                    ctx.font = 'bold 12px Courier New';
                    ctx.fillText('{}', drawX + 10, drawY + 18);
                }
            }
        };
        
        let questTerminals = [];
        let bugs = [];
        let door = {};
        
        const keys = {
            ArrowUp: false,
            ArrowDown: false,
            ArrowLeft: false,
            ArrowRight: false,
            w: false,
            a: false,
            s: false,
            d: false
        };
        
        const normalQuestions = [
            {
                id: 1,
                language: "Python",
                question: "What is the output of print(2**3)?",
                code: 'print(2**3)',
                options: ["6", "8", "9", "5"],
                correctAnswer: 1,
                hint: "** is the exponent operator in Python."
            },
            {
                id: 2,
                language: "JavaScript",
                question: "Which method removes the last element from an array?",
                code: 'let arr = [1, 2, 3];\narr.pop();',
                options: ["shift()", "pop()", "remove()", "delete()"],
                correctAnswer: 1,
                hint: "pop() removes from the end, shift() removes from the beginning."
            },
            {
                id: 3,
                language: "HTML",
                question: "Which tag creates a line break?",
                code: 'Hello<br>World',
                options: [" break ", " lb ", " br ", " newline "],
                correctAnswer: 2,
                hint: "<br> is a self-closing tag for line breaks."
            },
            {
                id: 4,
                language: "PHP",
                question: "How do you start a PHP block?",
                code: '<?php\n  echo "Hello";\n?>',
                options: [" &lt;?php&gt; ", " &lt;?php&gt; ", " &lt;??&gt; ", "Both A and C"],
                correctAnswer: 3,
                hint: "PHP blocks can start with <?php or just <? if short tags are enabled."
            },
            {
                id: 5,
                language: "Python",
                question: "What does len() function do?",
                code: 'len([1, 2, 3])',
                options: ["Returns the sum", "Returns the length", "Returns the max", "Returns the min"],
                correctAnswer: 1,
                hint: "len() returns the number of items in a container."
            },
            {
                id: 6,
                language: "JavaScript",
                question: "How do you check if a variable is an array?",
                code: 'Array.isArray(arr)',
                options: ["typeof arr === 'array'", "arr instanceof Array", "Array.isArray(arr)", "Both B and C"],
                correctAnswer: 3,
                hint: "Array.isArray() is the proper way to check for arrays."
            },
            {
                id: 7,
                language: "HTML",
                question: "Which attribute makes a link open in a new tab?",
                code: '<a href="page.html" target="_blank">Link</a>',
                options: ["newtab", "blank", "target='_blank'", "open"],
                correctAnswer: 2,
                hint: "target='_blank' opens the link in a new tab."
            },
            {
                id: 8,
                language: "PHP",
                question: "Which function includes a file?",
                code: 'include "file.php";',
                options: ["require()", "include()", "load()", "import()"],
                correctAnswer: 1,
                hint: "include() includes and evaluates the specified file."
            },

            {
        id: 9,
        language: "HTML",
        question: "What does HTML stand for?",
        code: "<!DOCTYPE html>",
        options: [
            "Hyper Text Markup Language",
            "High Text Machine Language",
            "Hyperlink Text Management Language",
            "Home Tool Markup Language"
        ],
        correctAnswer: 0,
        hint: "It is the standard language used to format documents on the World Wide Web."
    },
    {
        id: 10,
        language: "HTML",
        question: "Which element represents the root of an HTML document?",
        code: "<html>...</html>",
        options: ["(<.head>)", "(<.body>)", "(<.html>)", "(<.root>)"],
        correctAnswer: 2,
        hint: "All other elements must be descendants of this container element."
    },
    {
        id: 15,
        language: "HTML",
        question: "Which HTML attribute specifies an alternate text for an image if it cannot be displayed?",
        code: '<img src="pic.jpg" alt="Description">',
        options: ["title", "src", "alt", "description"],
        correctAnswer: 2,
        hint: "Short for 'alternative'."
    },
    {
        id: 16,
        language: "HTML",
        question: "Which tag is used to embed an external JavaScript file?",
        code: '<script src="app.js"></script>',
        options: ["<js>", "<script>", "<javascript>", "<code >"],
        correctAnswer: 1,
        hint: "This container tag can hold internal code or reference external files."
    },
    {
        id: 17,
        language: "HTML",
        question: "Which HTML input type defines a checkbox control?",
        code: '<input type="checkbox">',
        options: ['type="check"', 'type="box"', 'type="checkbox"', 'type="square"'],
        correctAnswer: 2,
        hint: "It allows users to select one or more options."
    },
    
    {
        id: 20,
        language: "HTML",
        question: "Which HTML tag is used to create a line break?",
        code: "First Line<br>Second Line",
        options: ["break", "lb", "hr", "br"],
        correctAnswer: 3,
        hint: "It is an empty tag standing for 'break'."
    },
    {
        id: 21,
        language: "HTML",
        question: "Which attribute opens a linked document in a new tab or window?",
        code: '<a href="url" target="_blank">Link</a>',
        options: ['target="_self"', 'target="_blank"', 'target="_new"', 'target="_parent"'],
        correctAnswer: 1,
        hint: "The target starts with an underscore followed by 'blank'."
    },
    {
        id: 23,
        language: "HTML",
        question: "Which meta tag ensures proper responsive scaling on mobile devices?",
        code: '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
        options: ["charset", "viewport", "keywords", "author"],
        correctAnswer: 1,
        hint: "It sets the user's viewable area of the page."
    },
    {
        id: 24,
        language: "HTML",
        question: "Which HTML element is used to play audio files?",
        code: '<audio controls src="sound.mp3"></audio>',
        options: ["(<sound>)", "(<audio>)", "(<music>)", "(<media>)"],
        correctAnswer: 1,
        hint: "Introduced in HTML5 for native audio support."
    },
    {
        id: 25,
        language: "HTML",
        question: "Which tag creates a horizontal rule/line across the page?",
        code: "<hr>",
       options: ["&lt;line&gt;", "&lt;br&gt;", "&lt;hr&gt;", "&lt;rule&gt;"],
        correctAnswer: 2,
        hint: "Stands for 'horizontal rule'."
    },
    {
        id: 27,
        language: "HTML",
        question: "Which attribute specifies that an input field must be filled out before submitting?",
        code: '<input type="text" required>',
        options: ["validate", "required", "mandatory", "important"],
        correctAnswer: 1,
        hint: "A boolean attribute added to form fields."
    },
    {
        id: 28,
        language: "HTML",
        question: "Which HTML element is used to group form controls and labels?",
        code: "<fieldset><legend>Title</legend></fieldset>",
        options: ["(<group>)", "(<form>)", "(<fieldset>)", "(<section>)"],
        correctAnswer: 2,
        hint: "Draws a box around related elements in a form."
    },
    {
        id: 31,
        language: "HTML",
        question: "Which element holds metadata and scripts not displayed on the page?",
        code: "<head><title>Page Title</title></head>",
        options: ["(<body>)", "(<head>)", "(<header>)", "(<meta>)"],
        correctAnswer: 1,
        hint: "Placed between <html> and <body> tags."
    },
    {
        id: 32,
        language: "HTML",
        question: "Which HTML attribute specifies an inline CSS style for an element?",
        code: '<div style="color: red;">Text</div>',
        options: ["css", "style", "class", "font"],
        correctAnswer: 1,
        hint: "Allows applying CSS rules directly inside a tag."
    },
    {
        id: 35,
        language: "HTML",
        question: "Which character entity represents the space character in HTML?",
        code: "Word1&nbsp;Word2",
        options: ["(&space;)", "(&blank;)", "(&nbsp;)", "(&s;)"],
        correctAnswer: 2,
        hint: "Stands for 'non-breaking space'."
    },
    {
        id: 36,
        language: "HTML",
        question: "Which attribute defines pre-filled text in an input field?",
        code: '<input type="text" value="Default">',
        options: ["placeholder", "value", "text", "default"],
        correctAnswer: 1,
        hint: "Sets the initial value of the input element."
    },
     {
        id: 37,
        language: "CSS",
        question: "What does CSS stand for?",
        code: "body { color: black; }",
        options: [
            "Cascading Style Sheets",
            "Colorful Style Sheets",
            "Computer Style Sheets",
            "Creative Style Sheets"
        ],
        correctAnswer: 0,
        hint: "The name highlights how style rules flow down to child elements."
    },
    {
        id: 38,
        language: "CSS",
        question: "Which CSS property changes the background color of an element?",
        code: "div { background-color: blue; }",
        options: ["color", "bg-color", "background-color", "canvas-color"],
        correctAnswer: 2,
        hint: "Combines 'background' and 'color'."
    },
    {
        id: 39,
        language: "CSS",
        question: "Which symbol is used to target an element by its ID in CSS?",
        code: "#header { color: red; }",
        options: [".", "#", "*", "&"],
        correctAnswer: 1,
        hint: "Also known as the hash or pound symbol."
    },
    {
        id: 40,
        language: "CSS",
        question: "Which symbol is used to target elements by class in CSS?",
        code: ".btn { display: block; }",
        options: ["#", "@", ".", "$"],
        correctAnswer: 2,
        hint: "A simple dot prefix."
    },
    {
        id: 41,
        language: "CSS",
        question: "Which CSS property controls the text size?",
        code: "p { font-size: 16px; }",
        options: ["text-style", "font-weight", "text-size", "font-size"],
        correctAnswer: 3,
        hint: "Controls font dimensions."
    },
    {
        id: 42,
        language: "CSS",
        question: "What is the default value of the position property in CSS?",
        code: "div { position: static; }",
        options: ["relative", "fixed", "absolute", "static"],
        correctAnswer: 3,
        hint: "Elements follow the normal page flow by default."
    },
    {
        id: 43,
        language: "CSS",
        question: "Which CSS box model property adds space inside an element's border?",
        code: "button { padding: 10px; }",
        options: ["margin", "padding", "border", "spacing"],
        correctAnswer: 1,
        hint: "Space between content and border."
    },
    {
        id: 44,
        language: "CSS",
        question: "Which CSS property adds space outside an element's border?",
        code: "div { margin: 20px; }",
        options: ["padding", "outline", "margin", "gap"],
        correctAnswer: 2,
        hint: "Controls external spacing around the box."
    },
    {
        id: 45,
        language: "CSS",
        question: "How do you make text bold in CSS?",
        code: "p { font-weight: bold; }",
        options: [
            "text-decoration: bold;",
            "font-weight: bold;",
            "font-style: bold;",
            "text-transform: bold;"
        ],
        correctAnswer: 1,
        hint: "Property controlling font thickness."
    },
    {
        id: 46,
        language: "CSS",
        question: "Which display property value turns an element into a flex container?",
        code: ".container { display: flex; }",
        options: ["grid", "flex", "block", "inline-flex"],
        correctAnswer: 1,
        hint: "Activates 1D flexbox layout model."
    },
    {
        id: 47,
        language: "CSS",
        question: "Which CSS property hides an element without removing it from the layout flow?",
        code: "div { visibility: hidden; }",
        options: [
            "display: none;",
            "visibility: hidden;",
            "opacity: 0;",
            "overflow: hidden;"
        ],
        correctAnswer: 1,
        hint: "Maintains original space in the document while invisible."
    },
    {
        id: 48,
        language: "CSS",
        question: "Which CSS property completely removes an element from the document layout?",
        code: "div { display: none; }",
        options: [
            "visibility: hidden;",
            "display: none;",
            "opacity: 0;",
            "clear: both;"
        ],
        correctAnswer: 1,
        hint: "The element releases its space entirely."
    },
    {
        id: 49,
        language: "CSS",
        question: "Which pseudo-class applies when a user hovers over an element?",
        code: "a:hover { color: red; }",
        options: [":active", ":focus", ":hover", ":visited"],
        correctAnswer: 2,
        hint: "Triggers on mouse pointer enter."
    },
    {
        id: 50,
        language: "CSS",
        question: "Which CSS unit is relative to the root element's font size?",
        code: "h1 { font-size: 2rem; }",
        options: ["em", "px", "vh", "rem"],
        correctAnswer: 3,
        hint: "'r' stands for root."
    },
    {
        id: 51,
        language: "CSS",
        question: "Which property controls the stacking order of positioned elements?",
        code: ".modal { z-index: 100; }",
        options: ["depth", "z-index", "order", "stack"],
        correctAnswer: 1,
        hint: "Refers to the Z-axis in 3D space."
    },
    {
        id: 52,
        language: "CSS",
        question: "Which property centers flex items along the main axis?",
        code: ".container { justify-content: center; }",
        options: ["align-items", "justify-content", "align-content", "text-align"],
        correctAnswer: 1,
        hint: "Controls main-axis alignment in Flexbox."
    },
    {
        id: 53,
        language: "CSS",
        question: "Which property centers flex items along the cross axis?",
        code: ".container { align-items: center; }",
        options: ["justify-content", "align-items", "place-self", "center-items"],
        correctAnswer: 1,
        hint: "Controls cross-axis alignment in Flexbox."
    },
    {
        id: 54,
        language: "CSS",
        question: "How do you remove underlines from hyperlinks in CSS?",
        code: "a { text-decoration: none; }",
        options: [
            "text-style: none;",
            "text-decoration: none;",
            "text-line: none;",
            "underline: none;"
        ],
        correctAnswer: 1,
        hint: "Controls visual decorations like strike-through and underline."
    },
    {
        id: 55,
        language: "CSS",
        question: "Which property forces long words to break and wrap to the next line?",
        code: "p { overflow-wrap: break-word; }",
        options: ["word-break", "text-wrap", "overflow-wrap", "white-space"],
        correctAnswer: 2,
        hint: "Prevents text overflow in narrow containers."
    },
    {
        id: 56,
        language: "CSS",
        question: "Which CSS rule is used to apply styles based on screen resolution or width?",
        code: "@media (max-width: 600px) { ... }",
        options: ["@import", "@media", "@supports", "@keyframes"],
        correctAnswer: 1,
        hint: "Essential for media queries and responsive design."
    },
    {
        id: 57,
        language: "CSS",
        question: "Which value of box-sizing includes padding and border in an element's total width?",
        code: "* { box-sizing: border-box; }",
        options: ["content-box", "padding-box", "border-box", "total-box"],
        correctAnswer: 2,
        hint: "Simplifies sizing calculation for elements."
    },
    {
        id: 58,
        language: "CSS",
        question: "Which property makes corners rounded in CSS?",
        code: "div { border-radius: 8px; }",
        options: ["corner-radius", "border-radius", "box-radius", "curve"],
        correctAnswer: 1,
        hint: "Controls border curvature."
    },
    {
        id: 59,
        language: "CSS",
        question: "Which shorthand property sets top, right, bottom, and left margins in clockwise order?",
        code: "div { margin: 10px 20px 30px 40px; }",
        options: ["padding", "border", "margin", "spacing"],
        correctAnswer: 2,
        hint: "Follows TRBL (Top, Right, Bottom, Left)."
    },
    {
        id: 60,
        language: "CSS",
        question: "Which CSS property sets image/element transparency?",
        code: "img { opacity: 0.5; }",
        options: ["filter", "transparency", "opacity", "alpha"],
        correctAnswer: 2,
        hint: "Values range from 0.0 to 1.0."
    },
    {
        id: 61,
        language: "CSS",
        question: "Which CSS Grid property defines template columns?",
        code: ".grid { grid-template-columns: 1fr 1fr; }",
        options: [
            "grid-columns",
            "grid-template-columns",
            "columns-template",
            "grid-auto-columns"
        ],
        correctAnswer: 1,
        hint: "Sets column track sizes."
    },
    {
        id: 62,
        language: "CSS",
        question: "Which property specifies transition effects between states?",
        code: "button { transition: all 0.3s ease; }",
        options: ["transform", "animation", "transition", "keyframes"],
        correctAnswer: 2,
        hint: "Smoothly changes CSS values over duration."
    },
    {
        id: 63,
        language: "CSS",
        question: "Which CSS selector selects all direct children of an element?",
        code: "div > p { color: blue; }",
        options: ["div p", "div + p", "div > p", "div ~ p"],
        correctAnswer: 2,
        hint: "Uses the greater-than sign combinator."
    },
    {
        id: 64,
        language: "CSS",
        question: "Which property specifies how overflow content is handled?",
        code: "div { overflow: scroll; }",
        options: ["overflow", "scroll-behavior", "clip", "display"],
        correctAnswer: 0,
        hint: "Can be hidden, visible, or auto/scroll."
    },
    {
        id: 65,
        language: "CSS",
        question: "Which declaration grants highest priority to a style rule regardless of specificity?",
        code: "p { color: red !important; }",
        options: ["!important", "#override", "priority: high;", "force: true;"],
        correctAnswer: 0,
        hint: "Appended at the end of property values."
    },
    {
        id: 66,
        language: "CSS",
        question: "Which CSS rule defines animation keyframes?",
        code: "@keyframes slide { ... }",
        options: ["@animation", "@keyframes", "@frames", "@motion"],
        correctAnswer: 1,
        hint: "Defines steps in a complex CSS animation sequence."
    },
    {
        id: 67,
        language: "CSS",
        question: "Which property is used to align text horizontally?",
        code: "h1 { text-align: center; }",
        options: ["align-content", "vertical-align", "text-align", "text-position"],
        correctAnswer: 2,
        hint: "Can be left, right, center, or justify."
    },
    {
        id: 68,
        language: "CSS",
        question: "Which CSS property applies visual effects like blur or grayscale to an element?",
        code: "img { filter: blur(5px); }",
        options: ["effect", "filter", "backdrop", "transform"],
        correctAnswer: 1,
        hint: "Used for graphical transformations."
    },
    {
        id: 69,
        language: "JavaScript",
        question: "Which keyword declares a block-scoped variable that can be reassigned?",
        code: "let x = 10;\nx = 20;",
        options: ["var", "let", "const", "static"],
        correctAnswer: 1,
        hint: "Introduced in ES6 to fix variable scope issues with 'var'."
    },
    {
        id: 70,
        language: "JavaScript",
        question: "Which keyword declares a block-scoped constant variable?",
        code: "const PI = 3.14159;",
        options: ["let", "immutable", "var", "const"],
        correctAnswer: 3,
        hint: "Cannot be reassigned after declaration."
    },
    {
        id: 71,
        language: "JavaScript",
        question: "What is the output of 'typeof NaN' in JavaScript?",
        code: "console.log(typeof NaN);",
        options: ['"number"', '"nan"', '"undefined"', '"object"'],
        correctAnswer: 0,
        hint: "Stands for 'Not a Number', but its technical type is numeric."
    },
    {
        id: 72,
        language: "JavaScript",
        question: "Which operator checks both value and type equality?",
        code: "5 === '5' // false",
        options: ["==", "=", "===", "=="],
        correctAnswer: 2,
        hint: "Strict equality operator without implicit coercion."
    },
    {
        id: 73,
        language: "JavaScript",
        question: "How do you write a single-line comment in JavaScript?",
        code: "// This is a comment",
        options: ["comment", "/* comment */", "# comment", "// comment"],
        correctAnswer: 3,
        hint: "Uses double forward slashes."
    },
    {
        id: 74,
        language: "JavaScript",
        question: "Which built-in method converts a JSON string into a JavaScript object?",
        code: 'const obj = JSON.parse(\'{"a": 1}\');',
        options: ["JSON.stringify()", "JSON.parse()", "JSON.toObject()", "JSON.convert()"],
        correctAnswer: 1,
        hint: "Parses text to recreate structures."
    },
    {
        id: 75,
        language: "JavaScript",
        question: "Which method adds one or more elements to the end of an array?",
        code: "const arr = [1, 2];\narr.push(3);",
        options: ["pop()", "push()", "unshift()", "append()"],
        correctAnswer: 1,
        hint: "Pushes items to the end and returns new array length."
    },
    {
        id: 76,
        language: "JavaScript",
        question: "Which method removes the last element from an array?",
        code: "const arr = [1, 2, 3];\narr.pop();",
        options: ["shift()", "pop()", "remove()", "splice()"],
        correctAnswer: 1,
        hint: "Pops off the last item."
    },
    {
        id: 77,
        language: "JavaScript",
        question: "Which array method creates a new array with results of calling a function on every element?",
        code: "const doubled = [1, 2].map(x => x * 2);",
        options: ["forEach()", "filter()", "map()", "reduce()"],
        correctAnswer: 2,
        hint: "Maps each item to a transformed value."
    },
    {
        id: 78,
        language: "JavaScript",
        question: "Which method selects an HTML element by its unique ID?",
        code: 'document.getElementById("main");',
        options: [
            "document.querySelector()",
            "document.getElement()",
            "document.getElementById()",
            "document.get()",
        ],
        correctAnswer: 2,
        hint: "Target element by ID string."
    },
    {
        id: 79,
        language: "JavaScript",
        question: "What is the return value of 'Boolean(\"\")'?",
        code: 'console.log(Boolean(""));',
        options: ["true", "false", "undefined", "null"],
        correctAnswer: 1,
        hint: "An empty string is a falsy value in JavaScript."
    },
    {
        id: 80,
        language: "JavaScript",
        question: "Which event fires when a user clicks an HTML element?",
        code: 'button.addEventListener("click", handleClick);',
        options: ["onhover", "click", "mouseclick", "press"],
        correctAnswer: 1,
        hint: "Standard pointer click event."
    },
    {
        id: 81,
        language: "JavaScript",
        question: "Which function delays execution of a callback by specified milliseconds?",
        code: "setTimeout(() => console.log('Hi'), 1000);",
        options: ["setInterval()", "setTimeout()", "delay()", "wait()"],
        correctAnswer: 1,
        hint: "Executes once after timer expires."
    },
    {
        id: 82,
        language: "JavaScript",
        question: "How do you check the length of an array in JavaScript?",
        code: "const len = [1, 2, 3].length;",
        options: ["arr.size", "arr.length", "arr.count()", "arr.length()"],
        correctAnswer: 1,
        hint: "A property, not a method invocation."
    },
    {
        id: 83,
        language: "JavaScript",
        question: "Which ES6 feature allows embedding variables inside string literals?",
        code: "const msg = `Hello ${name}`;",
        options: ["String Interpolation", "Template Literals", "Dynamic Strings", "Format Strings"],
        correctAnswer: 1,
        hint: "Enclosed by backticks (`)."
    },
    {
        id: 84,
        language: "JavaScript",
        question: "What does the 'typeof' operator return for 'null'?",
        code: "console.log(typeof null);",
        options: ['"null"', '"undefined"', '"object"', '"boolean"'],
        correctAnswer: 2,
        hint: "A long-standing legacy bug in JS typing system."
    },
    {
        id: 85,
        language: "JavaScript",
        question: "Which array method tests whether at least one element passes a implemented test?",
        code: "[1, 2, 3].some(x => x > 2); // true",
        options: ["every()", "some()", "find()", "includes()"],
        correctAnswer: 1,
        hint: "Returns true if any item satisfies condition."
    },
    {
        id: 86,
        language: "JavaScript",
        question: "Which keyword creates an instance of a class or object?",
        code: "const date = new Date();",
        options: ["create", "new", "instanceof", "construct"],
        correctAnswer: 1,
        hint: "Invokes constructor function."
    },
    {
        id: 87,
        language: "JavaScript",
        question: "Which statement stops loop execution completely?",
        code: "for(let i=0; i<10; i++) { if(i===5) break; }",
        options: ["continue", "stop", "exit", "break"],
        correctAnswer: 3,
        hint: "Breaks out of current loop construct."
    },
    {
        id: 88,
        language: "JavaScript",
        question: "Which array method returns a new array with all elements that pass a test?",
        code: "const evens = [1, 2, 3, 4].filter(x => x % 2 === 0);",
        options: ["filter()", "map()", "slice()", "reduce()"],
        correctAnswer: 0,
        hint: "Filters elements based on boolean predicate."
    },
    {
        id: 89,
        language: "JavaScript",
        question: "Which operator unpacks elements of an array or properties of an object?",
        code: "const arr2 = [...arr1, 4, 5];",
        options: ["Rest operator", "Spread operator", "Destructuring", "Expansion operator"],
        correctAnswer: 1,
        hint: "Represented by three consecutive dots (...)."
    },
    {
        id: 90,
        language: "JavaScript",
        question: "Which method turns a JavaScript object into a JSON string?",
        code: 'const json = JSON.stringify({ a: 1 });',
        options: ["JSON.parse()", "JSON.stringify()", "JSON.encode()", "JSON.toString()"],
        correctAnswer: 1,
        hint: "Converts structures to string representations."
    },
    {
        id: 91,
        language: "JavaScript",
        question: "What is the output of '1' + 1 in JavaScript?",
        code: "console.log('1' + 1);",
        options: ["2", "'11'", "NaN", "TypeError"],
        correctAnswer: 1,
        hint: "String concatenation takes precedence over addition."
    },
    {
        id: 92,
        language: "JavaScript",
        question: "What is the output of '1' - 1 in JavaScript?",
        code: "console.log('1' - 1);",
        options: ["0", "'0'", "NaN", "TypeError"],
        correctAnswer: 0,
        hint: "Subtraction coerces the string operand to a number."
    },
    {
        id: 93,
        language: "JavaScript",
        question: "Which API handles asynchronous HTTP requests using promises?",
        code: 'fetch("https://api.example.com/data")',
        options: ["XMLHttpRequest", "fetch", "axios", "http"],
        correctAnswer: 1,
        hint: "Built-in modern browser web API for network requests."
    },
    {
        id: 94,
        language: "JavaScript",
        question: "Which keyword returns a value from inside a function?",
        code: "function add(a, b) { return a + b; }",
        options: ["yield", "export", "return", "send"],
        correctAnswer: 2,
        hint: "Stops execution and sends output back to caller."
    },
    {
        id: 95,
        language: "JavaScript",
        question: "Which method adds an element to the start of an array?",
        code: "const arr = [2, 3];\narr.unshift(1);",
        options: ["push()", "unshift()", "shift()", "prepend()"],
        correctAnswer: 1,
        hint: "Opposite of push."
    },
    {
        id: 96,
        language: "JavaScript",
        question: "Which method removes the first element from an array?",
        code: "const arr = [1, 2, 3];\narr.shift();",
        options: ["pop()", "shift()", "splice()", "unshift()"],
        correctAnswer: 1,
        hint: "Shifts remaining items down index positions."
    },
    {
        id: 97,
        language: "JavaScript",
        question: "Which mechanism executes code after an asynchronous Promise settles?",
        code: "promise.then(data => console.log(data));",
        options: [".then()", ".done()", ".next()", ".after()"],
        correctAnswer: 0,
        hint: "Chained handler on Promise objects."
    },
    {
        id: 98,
        language: "JavaScript",
        question: "Which property is used to set or get inline HTML inside an element?",
        code: 'element.innerHTML = "<span>Text</span>";',
        options: ["innerText", "textContent", "innerHTML", "outerText"],
        correctAnswer: 2,
        hint: "Parses input as HTML Markup."
    },
    {
        id: 99,
        language: "JavaScript",
        question: "Which scope is accessible from anywhere in a JavaScript program?",
        code: "var globalVar = 'I am everywhere';",
        options: ["Block Scope", "Function Scope", "Global Scope", "Module Scope"],
        correctAnswer: 2,
        hint: "Top level scope outside functions."
    },
    {
        id: 100,
        language: "JavaScript",
        question: "Which function parses a string and returns an integer?",
        code: 'const num = parseInt("42");',
        options: ["Number.float()", "parseInt()", "Math.floor()", "toInteger()"],
        correctAnswer: 1,
        hint: "Parses integer numbers from strings."
    },
    {
        id: 101,
        language: "JavaScript",
        question: "Which logical operator returns true if both operands are true?",
        code: "if (a && b) { ... }",
        options: ["||", "&&", "!", "??"],
        correctAnswer: 1,
        hint: "Double ampersand."
    },
    {
        id: 102,
        language: "JavaScript",
        question: "Which operator returns the remainder of dividing two numbers?",
        code: "const rem = 10 % 3; // 1",
        options: ["/", "%", "**", "mod"],
        correctAnswer: 1,
        hint: "Percent sign (modulo)."
    },
    {
        id: 103,
        language: "JavaScript",
        question: "Which modern syntax simplifies writing functions in ES6?",
        code: "const add = (a, b) => a + b;",
        options: ["Arrow Functions", "Inline Functions", "Named Functions", "Lambda Loops"],
        correctAnswer: 0,
        hint: "Uses the arrow operator (=>)."
    },
    {
        id: 104,
        language: "JavaScript",
        question: "Which keyword pauses execution inside an async function until a Promise settles?",
        code: "const response = await fetch(url);",
        options: ["wait", "pause", "await", "defer"],
        correctAnswer: 2,
        hint: "Used in combination with 'async'."
    },
    {
        id: 105,
        language: "JavaScript",
        question: "Which method combines array elements into a single string separator-delimited?",
        code: "['a', 'b'].join('-'); // 'a-b'",
        options: ["concat()", "toString()", "join()", "merge()"],
        correctAnswer: 2,
        hint: "Joins array elements with specified delimiter."
    },
    {
        id: 106,
        language: "JavaScript",
        question: "Which statement handles exceptions in JavaScript?",
        code: "try { ... } catch (err) { ... }",
        options: ["if / else", "try / catch", "do / while", "switch / case"],
        correctAnswer: 1,
        hint: "Catches thrown errors during runtime."
    },
    {
        id: 107,
        language: "JavaScript",
        question: "Which browser storage persists data with no expiration time?",
        code: "localStorage.setItem('key', 'val');",
        options: ["sessionStorage", "cookie", "localStorage", "indexedCache"],
        correctAnswer: 2,
        hint: "Key-value client storage persisting across sessions."
    },
    {
        id: 108,
        language: "JavaScript",
        question: "Which operator returns the right-hand value when left side is null or undefined?",
        code: "const val = input ?? 'default';",
        options: ["||", "&&", "??", "?."],
        correctAnswer: 2,
        hint: "Nullish coalescing operator."
    },
        ];
        
        const easyQuestions = [
            {
                id: 5,
                language: "Python",
                question: "Which function converts a string to integer?",
                code: 'num = int("10")',
                options: ["str()", "int()", "float()", "parse()"],
                correctAnswer: 1,
                hint: "int() converts to integer, str() converts to string."
            },
            {
                id: 6,
                language: "JavaScript",
                question: "How do you declare a constant variable?",
                code: 'const PI = 3.14;',
                options: ["var", "let", "constant", "const"],
                correctAnswer: 3,
                hint: "const creates a constant (unchanging) variable."
            },
            {
                id: 7,
                language: "HTML",
                question: "Which tag is used for hyperlinks?",
                code: '<a href="page.html">Click</a>',
                options: ["<link>", "<a>", "<href>", "<url>"],
                correctAnswer: 1,
                hint: "<a> stands for anchor and creates hyperlinks."
            },
            {
                id: 8,
                language: "PHP",
                question: "Which symbol starts a PHP variable?",
                code: '$name = "John";',
                options: ["#", "$", "@", "&"],
                correctAnswer: 1,
                hint: "PHP variables start with $ followed by the name."
            },
            {
                id: 9,
                language: "Python",
                question: "What is the result of 5 // 2?",
                code: 'result = 5 // 2',
                options: ["2.5", "2", "3", "5"],
                correctAnswer: 1,
                hint: "// is floor division in Python."
            },
            {
                id: 10,
                language: "JavaScript",
                question: "Which method adds an element to the end of an array?",
                code: 'arr.push("new item")',
                options: ["unshift()", "push()", "add()", "append()"],
                correctAnswer: 1,
                hint: "push() adds to the end, unshift() adds to the beginning."
            },
            {
                id: 11,
                language: "HTML",
                question: "Which tag creates a numbered list?",
                code: '<ol>\n  <li>Item 1</li>\n</ol>',
                options: ["<ul>", "<ol>", "<list>", "<nl>"],
                correctAnswer: 1,
                hint: "<ol> creates ordered (numbered) lists."
            },
            {
                id: 12,
                language: "PHP",
                question: "How do you comment a single line in PHP?",
                code: '// This is a comment',
                options: ["/* */", "//", "#", "<!-- -->"],
                correctAnswer: 1,
                hint: "// is used for single-line comments in PHP."
            }
        ];
        
        const mediumQuestions = [
            {
                id: 9,
                language: "Python",
                question: "What does 'self' refer to in Python class methods?",
                code: 'class MyClass:\n  def method(self):\n    return self.value',
                options: ["The class itself", "The current instance", "The parent class", "The module"],
                correctAnswer: 1,
                hint: "'self' refers to the instance of the class."
            },
            {
                id: 10,
                language: "JavaScript",
                question: "Which operator is used for strict equality comparison?",
                code: '5 === "5" // returns false',
                options: ["==", "===", "=", "!=="],
                correctAnswer: 1,
                hint: "=== compares both value and type."
            },
            {
                id: 11,
                language: "HTML",
                question: "Which attribute makes an input field required?",
                code: '<input type="text" required>',
                options: ["mandatory", "require", "necessary", "required"],
                correctAnswer: 3,
                hint: "The 'required' attribute prevents form submission if empty."
            },
            {
                id: 12,
                language: "PHP",
                question: "Which function outputs text in PHP?",
                code: 'echo "Hello World";',
                options: ["print()", "echo", "output()", "Both A and B"],
                correctAnswer: 3,
                hint: "Both echo and print() can output text."
            }
        ];
        
        const hardQuestions = [
            {
                id: 13,
                language: "Python",
                question: "What does @property decorator do?",
                code: 'class MyClass:\n  @property\n  def value(self):\n    return self._value',
                options: ["Makes a method private", "Creates a getter method", "Caches the result", "Converts to static method"],
                correctAnswer: 1,
                hint: "@property allows a method to be accessed like an attribute."
            },
            {
                id: 14,
                language: "JavaScript",
                question: "What is hoisting in JavaScript?",
                code: 'console.log(x); // undefined\nvar x = 5;',
                options: ["Moving declarations to the top", "Variable type conversion", "Memory optimization", "Function overloading"],
                correctAnswer: 0,
                hint: "Hoisting moves variable and function declarations to the top of their scope."
            },
            {
                id: 15,
                language: "HTML",
                question: "What does the <meta charset='UTF-8'> tag do?",
                code: '<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="UTF-8">\n</head>',
                options: ["Sets page title", "Defines character encoding", "Creates metadata", "Links CSS file"],
                correctAnswer: 1,
                hint: "UTF-8 is a character encoding standard that supports most characters."
            },
            {
                id: 16,
                language: "PHP",
                question: "What is the difference between == and === in PHP?",
                code: '$a = 5;\n$b = "5";\n// $a == $b is true\n// $a === $b is false',
                options: [
                    "== checks value, === checks value and type",
                    "== checks type, === checks value",
                    "Both are the same",
                    "== is for strings, === is for numbers"
                ],
                correctAnswer: 0,
                hint: "== compares values after type juggling, === compares values AND types."
            }
        ];
        
        function getQuestionsForLevel(levelNum) {
            let allQuestions;
            switch(levelNum) {
                case 1:
                    allQuestions = [...normalQuestions];
                    break;
                case 2:
                    allQuestions = [...easyQuestions];
                    break;
                case 3:
                    allQuestions = [...mediumQuestions];
                    break;
                case 4:
                    allQuestions = [...hardQuestions];
                    break;
                case 5:
                    allQuestions = [...hardQuestions];
                    break;
                default:
                    allQuestions = [...normalQuestions];
            }
            
            const shuffled = [...allQuestions].sort(() => 0.5 - Math.random());
            return shuffled.slice(0, 3);
        }
        
        function getDifficultyName(levelNum) {
            return difficultyLevels[levelNum]?.name || "NORMAL";
        }
        
        function getRandomEmptyPosition(occupiedPositions = []) {
            let emptyCells = [];
            
            for (let row = 0; row < currentMap.length; row++) {
                for (let col = 0; col < currentMap[row].length; col++) {
                    const cellValue = currentMap[row][col];
                    const x = col * GRID_SIZE;
                    const y = row * GRID_SIZE;
                    
                    if (cellValue === 0) {
                        let isOccupied = false;
                        for (const pos of occupiedPositions) {
                            if (pos.x === x && pos.y === y) {
                                isOccupied = true;
                                break;
                            }
                        }
                        
                        if (!isOccupied) {
                            emptyCells.push({x: x, y: y, row: row, col: col});
                        }
                    }
                }
            }
            
            if (emptyCells.length === 0) return null;
            
            return emptyCells[Math.floor(Math.random() * emptyCells.length)];
        }
        
        let gameStartRecorded = false;

        function initLevel() {
            // Record that a game round was started (only once per page session)
            if (!gameStartRecorded && window.CodeMazeTracker) {
                gameStartRecorded = true;
                window.CodeMazeTracker.gameStart();
            }

            questsCompleted = 0;
            questsRequired = 3;
            
            const difficulty = difficultyLevels[storyId] || difficultyLevels[1];
            timeLeft = Math.floor(120 * difficulty.timeMultiplier);
            updateTimerDisplay();
            
            difficultyElement.textContent = getDifficultyName(storyId);
            difficultyElement.style.color = difficulty.color;
            
            doorOpen = false;
            
            currentMap = generateRandomMaze();
            applyStoryTheme();
            
            questTerminals = [];
            bugs = [];
            walls = [];
            
            updateQuestList();
            
            let occupiedPositions = [];
            
            for (let row = 0; row < currentMap.length; row++) {
                for (let col = 0; col < currentMap[row].length; col++) {
                    const cellValue = currentMap[row][col];
                    const x = col * GRID_SIZE;
                    const y = row * GRID_SIZE;
                    
                    switch(cellValue) {
                        case 1:
                            walls.push({
                                x: x,
                                y: y,
                                width: GRID_SIZE,
                                height: GRID_SIZE
                            });
                            break;
                            
                        case 2:
                            player.x = x;
                            player.y = y;
                            occupiedPositions.push({x: x, y: y});
                            break;
                            
                    }
                }
            }
            
            const levelQuestions = getQuestionsForLevel(storyId);
            
            for (let i = 0; i < 3; i++) {
                const randomPos = getRandomEmptyPosition(occupiedPositions);
                if (randomPos) {
                    questTerminals.push({
                        x: randomPos.x + 5,
                        y: randomPos.y + 5,
                        width: 40,
                        height: 40,
                        color: '#9c27b0',
                        completed: false,
                        id: i + 1,
                        question: levelQuestions[i],
                        draw: function() {
                            if (!this.completed) {
                                const pulse = Math.sin(Date.now() / 500) * 0.2 + 0.8;
                                ctx.fillStyle = `rgba(156, 39, 176, ${pulse})`;
                                ctx.fillRect(this.x, this.y, this.width, this.height);

                                ctx.fillStyle = '#e1bee7';
                                ctx.fillRect(this.x + 5, this.y + 5, this.width - 10, this.height - 10);

                                ctx.fillStyle = '#4a148c';
                                ctx.font = 'bold 16px Arial';
                                ctx.textAlign = 'center';
                                ctx.fillText(this.id.toString(), this.x + 20, this.y + 25);

                                ctx.fillStyle = this.getLanguageColor();
                                ctx.font = 'bold 12px Arial';
                                ctx.fillText(this.question.language.charAt(0), this.x + 20, this.y + 40);

                                ctx.strokeStyle = '#7b1fa2';
                                ctx.lineWidth = 3;
                                ctx.strokeRect(this.x, this.y, this.width, this.height);
                            } else {
                                ctx.fillStyle = '#4caf50';
                                ctx.fillRect(this.x, this.y, this.width, this.height);

                                ctx.fillStyle = '#1b5e20';
                                ctx.font = 'bold 18px Arial';
                                ctx.textAlign = 'center';
                                ctx.fillText('✓', this.x + 20, this.y + 25);
                            }
                        },
                        getLanguageColor: function() {
                            switch(this.question.language) {
                                case "Python": return "#3776ab";
                                case "JavaScript": return "#f7df1e";
                                case "HTML": return "#e34c26";
                                case "PHP": return "#777bb4";
                                default: return "#ffffff";
                            }
                        }
                    });
                    occupiedPositions.push({x: randomPos.x, y: randomPos.y});
                }
            }

            const doorPos = getRandomEmptyPosition(occupiedPositions);
            if (doorPos) {
                door = {
                    x: doorPos.x,
                    y: doorPos.y,
                    width: 70,
                    height: 60 * 2,
                    color: '#ff9800',
                    open: false,
                    animating: false,
                    animationFrame: 0,
                    animationCounter: 0,
                    animationSpeed: 0.15,
                    frameWidth: 100,
                    frameHeight: 100,
                    spriteCols: 4,
                    draw: function() {

                        if (this.animating && this.animationFrame < this.spriteCols - 1) {
                            this.animationCounter += this.animationSpeed;
                            if (this.animationCounter >= 1) {
                                this.animationCounter = 0;
                                this.animationFrame++;
                            }
                        } else if (this.animating && this.animationFrame >= this.spriteCols - 1) {
                            this.animating = false;
                            this.open = true;
                        }

                        if (doorSpriteLoaded && doorSprite.complete && doorSprite.naturalHeight !== 0) {

                            let frameX = 0;
                            let frameY = 0;

                            if (this.open) {
                                frameX = this.spriteCols - 1;
                            } else if (this.animating) {
                                frameX = this.animationFrame;
                            }


                            const frameW = doorSprite.naturalWidth / this.spriteCols;
                            const frameH = doorSprite.naturalHeight;


                            const scaleX = this.width / frameW;
                            const scaleY = this.height / frameH;
                            const drawWidth = frameW * scaleX;
                            const drawHeight = frameH * scaleY;


                            const drawX = this.x - (drawWidth - this.width) / 2;
                            const drawY = this.y - (drawHeight - this.height) / 2;

                            ctx.drawImage(
                                doorSprite,
                                frameX * frameW,
                                frameY,
                                frameW,
                                frameH,
                                drawX,
                                drawY,
                                drawWidth,
                                drawHeight
                            );
                        } else {

                            if (this.open) {

                                const pulse = Math.sin(Date.now() / 300) * 0.3 + 0.7;
                                ctx.fillStyle = `rgba(255, 152, 0, ${pulse})`;
                                ctx.fillRect(this.x, this.y, this.width, this.height);

                             
                                ctx.strokeStyle = '#5d4037';
                                ctx.lineWidth = 3;
                                ctx.strokeRect(this.x, this.y, this.width, this.height);

                               
                                ctx.fillStyle = '#0d1520';
                                ctx.font = 'bold 14px Arial';
                                ctx.textAlign = 'center';
                                ctx.fillText('EXIT', this.x + 25, this.y + this.height / 2);


                                ctx.strokeStyle = '#4caf50';
                                ctx.lineWidth = 2;
                                ctx.strokeRect(this.x + 2, this.y + 2, this.width - 4, this.height - 4);
                            } else if (this.animating) {

                                const progress = this.animationFrame / (this.spriteCols - 1);

                               
                                const r = Math.floor(121 + (255 - 121) * progress);
                                const g = Math.floor(85 + (152 - 85) * progress);
                                const b = Math.floor(72 + (0 - 72) * progress);

                               
                                ctx.fillStyle = `rgb(${r}, ${g}, ${b})`;
                                ctx.fillRect(this.x, this.y, this.width, this.height);

                               
                                ctx.fillStyle = `rgba(0, 0, 0, ${0.3 * (1 - progress)})`;
                                ctx.fillRect(this.x + 5, this.y + 5, this.width - 10, 20);
                                ctx.fillRect(this.x + 5, this.y + 35, this.width - 10, 20);


                                ctx.strokeStyle = '#5d4037';
                                ctx.lineWidth = 2;
                                ctx.strokeRect(this.x, this.y, this.width, this.height);


                                const glow = Math.sin(Date.now() / 100) * 0.5 + 0.5;
                                ctx.strokeStyle = `rgba(255, 152, 0, ${glow})`;
                                ctx.lineWidth = 3;
                                ctx.strokeRect(this.x, this.y, this.width, this.height);
                            } else {

                                ctx.fillStyle = '#8d6e63';
                                ctx.fillRect(this.x, this.y, this.width, this.height);

                               
                                ctx.strokeStyle = '#5d4037';
                                ctx.lineWidth = 4;
                                ctx.strokeRect(this.x, this.y, this.width, this.height);

                               
                                ctx.strokeStyle = '#6d4c41';
                                ctx.lineWidth = 2;
                                ctx.strokeRect(this.x + 3, this.y + 3, this.width - 6, this.height - 6);


                                ctx.fillStyle = '#795548';
                                ctx.fillRect(this.x + 8, this.y + 8, this.width - 16, 25);
                                ctx.strokeStyle = '#5d4037';
                                ctx.lineWidth = 1;
                                ctx.strokeRect(this.x + 8, this.y + 8, this.width - 16, 25);


                                ctx.fillStyle = '#795548';
                                ctx.fillRect(this.x + 8, this.y + 38, this.width - 16, 25);
                                ctx.strokeRect(this.x + 8, this.y + 38, this.width - 16, 25);


                                ctx.beginPath();
                                ctx.arc(this.x + this.width - 12, this.y + this.height / 2, 5, 0, Math.PI * 2);
                                ctx.fillStyle = '#ffd700';
                                ctx.fill();
                                ctx.strokeStyle = '#b8860b';
                                ctx.lineWidth = 2;
                                ctx.stroke();


                                ctx.fillStyle = '#e0e0e0';
                                ctx.font = 'bold 10px Arial';
                                ctx.textAlign = 'center';
                                ctx.fillText('LOCKED', this.x + 20, this.y + this.height - 8);
                            }
                        }
                    },
                    startAnimation: function() {
                        this.animating = true;
                        this.animationFrame = 0;
                        this.animationCounter = 0;
                    }
                };
                occupiedPositions.push({x: doorPos.x, y: doorPos.y});
            }
           // In initLevel() function, replace the bug creation section with:

const bugCount = 7 + Math.floor(Math.random() * 3);
for (let i = 0; i < bugCount; i++) {
    const randomPos = getRandomEmptyPosition(occupiedPositions);
    if (randomPos) {
        const bugTypes = [
            { color: '#f44336', speed: 1.8, pattern: 'aggressive' },
            { color: '#ff5722', speed: 2.0, pattern: 'aggressive' },
            { color: '#d32f2f', speed: 2.2, pattern: 'aggressive' },
            { color: '#c2185b', speed: 1.9, pattern: 'aggressive' }
        ];
        
        const bugType = bugTypes[i % bugTypes.length];
        
        bugs.push({
            x: randomPos.x,
            y: randomPos.y,
            width: 30,
            height: 40,
            color: bugType.color,
            speed: bugType.speed + (level * 0.3), // Faster in higher levels
            chaseRange: 500, // Increased range - bugs detect player from很远
            attackRange: 40, // Distance at which bugs catch player
            directionX: 0,
            directionY: 0,
            moveTimer: 0,
            changeDirectionTime: 20 + Math.random() * 20,
            id: i + 1,
            frameX: 0,
            frameY: 0,
            animationCounter: 0,
            animationSpeed: 0.2,
            lastPlayerPos: { x: 0, y: 0 },
            memoryTime: 60, // Increased memory time - remember player longer
            isAggressive: true, // Always aggressive
            
            draw: function() {
                let frameW = 10;
                let frameH = 10;
                
                if (bugSpriteLoaded && bugSprite.complete && bugSprite.naturalHeight !== 0) {
                    frameW = actualBugFrameWidth;
                    frameH = actualBugFrameHeight;
                }
                
                const scaleX = this.width / frameW;
                const scaleY = this.height / frameH;
                
                const drawWidth = frameW * scaleX;
                const drawHeight = frameH * scaleY;
                
                let spriteRow = 0;

                // Determine sprite row based on movement direction
                if (Math.abs(this.directionY) > Math.abs(this.directionX)) {
                    if (this.directionY < 0) {
                        spriteRow = 3; // Up
                    } else {
                        spriteRow = 2; // Down
                    }
                } else {
                    if (this.directionX < 0) {
                        spriteRow = 1; // Left
                    } else {
                        spriteRow = 0; // Right
                    }
                }
                
                const drawX = this.x - (drawWidth - this.width) / 2;
                const drawY = this.y - (drawHeight - this.height) / 2;
                
                if (bugSpriteLoaded && bugSprite.complete && bugSprite.naturalHeight !== 0) {
                    ctx.drawImage(
                        bugSprite,
                        this.frameX * frameW,
                        spriteRow * frameH,
                        frameW,
                        frameH,
                        drawX,
                        drawY,
                        drawWidth,
                        drawHeight
                    );
                    
                    this.animationCounter += this.animationSpeed;
                    if (this.animationCounter >= 1) {
                        this.animationCounter = 0;
                        this.frameX++;
                        if (this.frameX >= 4) {
                            this.frameX = 0;
                        }
                    }
                } else {
                    // Fallback drawing
                    ctx.fillStyle = this.color;
                    ctx.fillRect(this.x, this.y, this.width, this.height);
                    
                    // Eyes (angry eyes when chasing)
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(this.x + 5, this.y + 8, 6, 6);
                    ctx.fillRect(this.x + this.width - 11, this.y + 8, 6, 6);
                    
                    // Pupils (follow player)
                    const playerCenterX = player.x + player.width/2;
                    const playerCenterY = player.y + player.height/2;
                    const bugCenterX = this.x + this.width/2;
                    const bugCenterY = this.y + this.height/2;
                    
                    const dx = playerCenterX - bugCenterX;
                    const dy = playerCenterY - bugCenterY;
                    
                    // Make pupils look towards player
                    ctx.fillStyle = '#000000';
                    ctx.fillRect(this.x + 7 + (dx > 0 ? 1 : -1), this.y + 10, 3, 3);
                    ctx.fillRect(this.x + this.width - 9 + (dx > 0 ? 1 : -1), this.y + 10, 3, 3);
                    
                    // Angry eyebrows
                    ctx.strokeStyle = '#000000';
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    ctx.moveTo(this.x + 4, this.y + 5);
                    ctx.lineTo(this.x + 9, this.y + 3);
                    ctx.moveTo(this.x + this.width - 4, this.y + 5);
                    ctx.lineTo(this.x + this.width - 9, this.y + 3);
                    ctx.stroke();
                    
                    // Antennae
                    ctx.strokeStyle = this.color;
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    ctx.moveTo(this.x + 8, this.y);
                    ctx.lineTo(this.x + 3, this.y - 10);
                    ctx.moveTo(this.x + this.width - 8, this.y);
                    ctx.lineTo(this.x + this.width - 3, this.y - 10);
                    ctx.stroke();
                }
            },
            
                        update: function() {
                this.moveTimer++;
                
                // 1. Check for Catch / Collision sa Player (Mananatili ito para matalo ang player kapag nagkabanggaan)
                const playerCenterX = player.x + player.width/2;
                const playerCenterY = player.y + player.height/2;
                const bugCenterX = this.x + this.width/2;
                const bugCenterY = this.y + this.height/2;
                const dx = playerCenterX - bugCenterX;
                const dy = playerCenterY - bugCenterY;
                const distanceToPlayer = Math.sqrt(dx * dx + dy * dy);
                
                if (distanceToPlayer < this.attackRange) {
                    
            
                   
                    
                    
                   for (let row = 0; row < currentMap.length; row++) { 
                               for (let col = 0; col < currentMap[row].length; col++) { 
                                   if (currentMap[row][col] === 1) { 
                                     startX = col * GRID_SIZE; 
                                     startY = row * GRID_SIZE; 
                                      break; 
                                    } 
                               } 
                            }
                    
                    player.x = startX;
                    player.y = startY;
                    
                    canvas.style.boxShadow = '0 0 30px rgba(244, 67, 54, 0.8)';
                    setTimeout(() => {
                        canvas.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.7)';
                    }, 200);
                    
                    this.x = startX + (Math.random() * 100 - 50);
                    this.y = startY + (Math.random() * 100 - 50);
                    
                    this.x = Math.max(10, Math.min(canvas.width - this.width - 10, this.x));
                    this.y = Math.max(10, Math.min(canvas.height - this.height - 10, this.y));
                    
                    if (lives <= 0) {
                        gameOver();
                    }
                    return;
                }
                
                // PURE GRID MOVEMENT ONLY — Tinanggal ang dobleng bawas-buhay logic dito
                if (this.moveTimer >= this.changeDirectionTime || (this.directionX === 0 && this.directionY === 0)) {
                    this.moveTimer = 0;
                    this.changeDirectionTime = 40 + Math.random() * 40;
                    
                    const randDir = Math.floor(Math.random() * 4);
                    switch(randDir) {
                        case 0: this.directionX = 0; this.directionY = -1; break; // Up
                        case 1: this.directionX = 0; this.directionY = 1; break;  // Down
                        case 2: this.directionX = -1; this.directionY = 0; break; // Left
                        case 3: this.directionX = 1; this.directionY = 0; break;  // Right
                    }
                }
                
                let newX = this.x + this.directionX * this.speed;
                let newY = this.y + this.directionY * this.speed;
                
                const newRect = {
                    x: newX,
                    y: newY,
                    width: this.width,
                    height: this.height
                };
                
                let hitWall = checkWallCollision(newRect);
                
                if (newX <= 0 || newX + this.width >= canvas.width ||
                    newY <= 0 || newY + this.height >= canvas.height) {
                    hitWall = true;
                }
                
                if (hitWall) {
                    // WALL FIX: Lalayuan ang pader ng 3px para hindi mag-clip o mag-stuck kailanman
                    this.x -= this.directionX * 3;
                    this.y -= this.directionY * 3;
                    
                    this.directionX *= -1;
                    this.directionY *= -1;
                    this.moveTimer = this.changeDirectionTime; 
                } else {
                    this.x = newX;
                    this.y = newY;
                }
            },

            
            // Helper method to try alternative paths when direct path is blocked
            tryAlternativePath: function(targetX, targetY, speed) {
                // Try different angles to go around obstacles
                const angles = [0.3, -0.3, 0.6, -0.6, 0.9, -0.9, 1.2, -1.2];
                
                for (let angle of angles) {
                    // Rotate direction
                    const rotatedX = targetX * Math.cos(angle) - targetY * Math.sin(angle);
                    const rotatedY = targetX * Math.sin(angle) + targetY * Math.cos(angle);
                    
                    let newX = this.x + rotatedX * speed;
                    let newY = this.y + rotatedY * speed;
                    
                    const newRect = {
                        x: newX,
                        y: newY,
                        width: this.width,
                        height: this.height
                    };
                    
                    if (!checkWallCollision(newRect) && 
                        newX > 0 && newX + this.width < canvas.width &&
                        newY > 0 && newY + this.height < canvas.height) {
                        
                        this.x = newX;
                        this.y = newY;
                        this.directionX = rotatedX;
                        this.directionY = rotatedY;
                        return;
                    }
                }
                
                // If all paths blocked, try moving along walls
                const directions = [
            { x: 0, y: -1 }, // Up
            { x: 0, y: 1 },  // Down
            { x: -1, y: 0 }, // Left
            { x: 1, y: 0 }   // Right
        ]
                
                for (let dir of directions) {
                    let newX = this.x + dir[0] * speed * 0.7;
                    let newY = this.y + dir[1] * speed * 0.7;
                    
                    const newRect = {
                        x: newX,
                        y: newY,
                        width: this.width,
                        height: this.height
                    };
                    
                    if (!checkWallCollision(newRect) && 
                        newX > 0 && newX + this.width < canvas.width &&
                        newY > 0 && newY + this.height < canvas.height) {
                        
                        this.x = newX;
                        this.y = newY;
                        this.directionX = dir[0];
                        this.directionY = dir[1];
                        return;
                    }
                }
            }
        });
        occupiedPositions.push({x: randomPos.x, y: randomPos.y});
    }
}
            
            startTimer();
            
            updateUI();
        }
        
        function checkCollision(rect1, rect2) {
            return rect1.x < rect2.x + rect2.width &&
                   rect1.x + rect1.width > rect2.x &&
                   rect1.y < rect2.y + rect2.height &&
                   rect1.y + rect1.height > rect2.y;
        }
        
        function checkWallCollision(rect) {
            for (const wall of walls) {
                if (checkCollision(rect, wall)) {
                    return true;
                }
            }
            return false;
        }
        
        function startTimer() {
            clearInterval(timerInterval);
            
            timerInterval = setInterval(() => {
                if (!gamePaused && !questActive && gameRunning) {
                    timeLeft--;
                    updateTimerDisplay();
                    
                    if (timeLeft % 10 === 0 && timeLeft > 0) {
                        playSound(tickSound);
                    }
                    
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        gameOver();
                    }
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 20) {
                timerElement.className = 'timer-critical';
            } else if (timeLeft <= 40) {
                timerElement.className = 'timer-warning';
            } else {
                timerElement.className = '';
            }
        }
        
        function startQuestTimer() {
            clearInterval(questTimerInterval);
            questTimeLeft = 30;
            questTimeLeftElement.textContent = questTimeLeft;
            
            questTimerInterval = setInterval(() => {
                if (!gamePaused && questActive) {
                    questTimeLeft--;
                    questTimeLeftElement.textContent = questTimeLeft;
                    
                    if (questTimeLeft <= 10) {
                        questTimeLeftElement.style.color = '#f44336';
                    } else if (questTimeLeft <= 20) {
                        questTimeLeftElement.style.color = '#ff9800';
                    } else {
                        questTimeLeftElement.style.color = '#4caf50';
                    }
                    
                    if (questTimeLeft <= 0) {
                        clearInterval(questTimerInterval);
                        submitQuestAnswer(false, "Time's up!");
                    }
                }
            }, 1000);
        }
        
        function updateQuestList() {
            questListElement.innerHTML = '';
            
            const questions = getQuestionsForLevel(storyId);
            
            for (let i = 0; i < questsRequired; i++) {
                const questBlock = document.createElement('div');
                questBlock.className = 'code-block';
                questBlock.id = `quest-${i + 1}`;
                
                const isCompleted = i < questsCompleted;
                const question = questions[i];
                
                let badgeClass = '';
                switch(question?.language) {
                    case "Python": badgeClass = "python-badge"; break;
                    case "JavaScript": badgeClass = "javascript-badge"; break;
                    case "HTML": badgeClass = "html-badge"; break;
                    case "PHP": badgeClass = "php-badge"; break;
                }
                
                if (isCompleted) {
                    questBlock.classList.add('collected');
                }
                
                questBlock.innerHTML = `
                    <div class="code-text">Quest #${i + 1} 
                        ${question ? `<span class="language-badge ${badgeClass}">${question.language}</span>` : ''}
                    </div>
                    <div class="code-purpose">${isCompleted ? 'Completed!' : 'Click to answer'}</div>
                `;
                
                questListElement.appendChild(questBlock);
            }
        }
        
        function updateUI() {
            scoreElement.textContent = score;
            livesElement.textContent = lives;
            levelElement.textContent = storyId;
            questsCompletedElement.textContent = `${questsCompleted}/${questsRequired}`;
            
            updateQuestList();
        }
        
        function playSound(soundElement) {
            if (!soundEnabled) return;
            
            try {
                soundElement.currentTime = 0;
                soundElement.play().catch(e => console.log("Audio play failed:", e));
            } catch (e) {
                console.log("Sound error:", e);
            }
        }
        
        function toggleSound() {
            soundEnabled = !soundEnabled;
            soundToggleBtn.classList.toggle('active', soundEnabled);
            soundToggleBtn.innerHTML = soundEnabled ? 
                '<i class="fas fa-volume-up"></i> Sound: ON' : 
                '<i class="fas fa-volume-mute"></i> Sound: OFF';
        }
        
        function toggleMusic() {
            musicEnabled = !musicEnabled;
            musicToggleBtn.classList.toggle('active', musicEnabled);
            musicToggleBtn.innerHTML = musicEnabled ? 
                '<i class="fas fa-music"></i> Music: ON' : 
                '<i class="fas fa-music"></i> Music: OFF';
            
            if (musicEnabled) {
                backgroundMusic.volume = 0.3;
                if (!gamePaused) {
                    backgroundMusic.play().catch(e => console.log("Music play failed:", e));
                }
            } else {
                backgroundMusic.pause();
            }
        }
        
        function togglePause() {
            if (!gameRunning || questActive) return;
            
            gamePaused = !gamePaused;
            
            if (gamePaused) {
                pauseMenu.style.display = 'block';
                updatePauseStats();
                playSound(pauseSound);
                backgroundMusic.pause();
            } else {
                pauseMenu.style.display = 'none';
                if (musicEnabled) {
                    backgroundMusic.play().catch(e => console.log("Music play failed:", e));
                }
            }
        }
        
        function updatePauseStats() {
            pauseLevelElement.textContent = level;
            pauseScoreElement.textContent = score;
            pauseLivesElement.textContent = lives;
            pauseTimeElement.textContent = timeLeft + 's';
            pauseDifficultyElement.textContent = getDifficultyName(level);
        }
        
        function showQuest(terminal) {
            questActive = true;
            currentQuestTerminal = terminal;
            
            questSelectedOption = null;
            questHintUsed = false;
            
            updateLanguageBadge(terminal.question.language);
            
            questNumberElement.textContent = terminal.id;
            questQuestion.textContent = terminal.question.question;
            questCode.textContent = terminal.question.code;
            
            questOptions.innerHTML = '';
            
            const optionLetters = ['A', 'B', 'C', 'D'];
            terminal.question.options.forEach((option, index) => {
                const optionElement = document.createElement('div');
                optionElement.className = 'quest-option';
                optionElement.dataset.index = index;
                
                optionElement.innerHTML = `
                    <div class="option-letter">${optionLetters[index]}</div>
                    <div class="option-text">${option}</div>
                `;
                
                optionElement.addEventListener('click', () => selectQuestOption(optionElement, index));
                questOptions.appendChild(optionElement);
            });
            
            questResult.style.display = 'none';
            questResult.className = 'quest-result';
            
            questSubmitBtn.disabled = true;
            questHintBtn.disabled = false;
            questHintBtn.innerHTML = '<i class="fas fa-lightbulb"></i> Hint (-20pts)';
            
            startQuestTimer();
            
            questPopup.style.display = 'block';
        }
        
        function updateLanguageBadge(language) {
            languageBadge.textContent = language;
            
            languageBadge.className = 'language-badge';
            
            switch(language) {
                case "Python":
                    languageBadge.classList.add('python-badge');
                    break;
                case "JavaScript":
                    languageBadge.classList.add('javascript-badge');
                    break;
                case "HTML":
                    languageBadge.classList.add('html-badge');
                    break;
                case "PHP":
                    languageBadge.classList.add('php-badge');
                    break;
            }
        }
        
        let questSelectedOption = null;
        let questHintUsed = false;
        
        function selectQuestOption(optionElement, index) {
            document.querySelectorAll('.quest-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            optionElement.classList.add('selected');
            questSelectedOption = index;
            
            questSubmitBtn.disabled = false;
        }
        
        function submitQuestAnswer(forceIncorrect = false, forceMessage = "") {
            clearInterval(questTimerInterval);

            const question = currentQuestTerminal.question;
            const isCorrect = !forceIncorrect && questSelectedOption === question.correctAnswer;

            questResult.style.display = 'block';

            if (isCorrect) {
                questResult.className = 'quest-result result-correct';
                questResult.innerHTML = `
                    <h3><i class="fas fa-check-circle"></i> Correct!</h3>
                    <p>Quest #${currentQuestTerminal.id} (${question.language}) completed!</p>
                    <p><strong>+100 points</strong></p>
                `;

                document.querySelectorAll('.quest-option')[question.correctAnswer].classList.add('correct');

                score += 100;

                if (bugs.length > 1) {
                        bugs.pop();
                }

                // Record the completed quest for daily progress + weak-quest tracking
                if (window.CodeMazeTracker) {
                    window.CodeMazeTracker.questComplete({
                        language: question.language,
                        question: question.question,
                        code: question.code,
                        hint: question.hint
                    });
                }

                playSound(collectSound);

                setTimeout(() => {
                    completeQuest(currentQuestTerminal);
                }, 1500);
            } else {
                // Record the wrong answer for weak-quest tracking
                if (window.CodeMazeTracker) {
                    window.CodeMazeTracker.questWrong({
                        language: question.language,
                        question: question.question,
                        code: question.code,
                        hint: question.hint
                    });
                }
                questResult.className = 'quest-result result-incorrect';

                let message = forceMessage || "Wrong answer! Loading new question...";
                questResult.innerHTML = `
                    <h3><i class="fas fa-times-circle"></i> ${forceMessage ? "Time's Up!" : "Wrong Answer!"}</h3>
                    <p>${message}</p>
                    <p><strong>-50 points penalty!</strong></p>
                    <p>Loading new question...</p>
                `;

                if (questSelectedOption !== null && questSelectedOption !== question.correctAnswer) {
                    document.querySelectorAll('.quest-option')[questSelectedOption].classList.add('incorrect');
                }

                score -= 50;
                
                addExtraBugs(1);

                // BUG: Add 2 more bugs when answer is wrong!

                setTimeout(() => {
                  
                    const levelQuestions = getQuestionsForLevel(storyId);
                    let newQuestion;
                    do {
                        newQuestion = levelQuestions[Math.floor(Math.random() * levelQuestions.length)];
                    } while (newQuestion.id === currentQuestTerminal.question.id); 

                    currentQuestTerminal.question = newQuestion;

                    
                    updateLanguageBadge(newQuestion.language);
                    questNumberElement.textContent = currentQuestTerminal.id;
                    questQuestion.textContent = newQuestion.question;
                    questCode.textContent = newQuestion.code;

                    questOptions.innerHTML = '';

                    const optionLetters = ['A', 'B', 'C', 'D'];
                    newQuestion.options.forEach((option, index) => {
                        const optionElement = document.createElement('div');
                        optionElement.className = 'quest-option';
                        optionElement.dataset.index = index;

                        optionElement.innerHTML = `
                            <div class="option-letter">${optionLetters[index]}</div>
                            <div class="option-text">${option}</div>
                        `;

                        optionElement.addEventListener('click', () => selectQuestOption(optionElement, index));
                        questOptions.appendChild(optionElement);
                    });

                    questResult.style.display = 'none';

                    questSelectedOption = null;
                    questHintUsed = false;
                    questHintBtn.disabled = false;
                    questHintBtn.innerHTML = '<i class="fas fa-lightbulb"></i> Hint (-20pts)';
                    questSubmitBtn.disabled = true;

                    startQuestTimer();
                }, 2000);
            }

            if (isCorrect) {
                updateUI();

                questSubmitBtn.disabled = true;
                questHintBtn.disabled = true;
            }
        }
        
        function closeQuestPopup() {
    clearInterval(questTimerInterval); // Ititigil ang timer ng quest
    questPopup.style.display = 'none'; // Itatago ang panel popup
    questActive = false;               // Papayagan muli ang player at bugs na gumalaw
    currentQuestTerminal = null;       // I-re-reset ang kasalukuyang terminal target
    
    startTimer(); 
}


        function completeQuest(terminal) {
            terminal.completed = true;
            questsCompleted++;
            player.speed += 0.5;

          
            killBugsWithAnimation();

            updateUI();

            questPopup.style.display = 'none';
            questActive = false;

            if (questsCompleted >= questsRequired) {
                unlockNextLevel();
                door.startAnimation();
                if (difficultyElement) {
                    difficultyElement.title = 'Next level unlocked';
                }

                playSound(doorSound);

                setTimeout(() => {
                    door.open = true;
                    doorOpen = true;
                }, 1000); 
            }
        }
  
        function killBugsWithAnimation() {
            const bugsToKill = [...bugs];
            bugs = [];

            bugsToKill.forEach((bug, index) => {
                setTimeout(() => {
                    
                    for (let i = 0; i < 20; i++) {
                        setTimeout(() => {
                            ctx.fillStyle = `rgba(255, 255, 255, ${1 - i/20})`;
                            ctx.beginPath();
                            ctx.arc(bug.x + bug.width/2, bug.y + bug.height/2, (20 - i) * 2, 0, Math.PI * 2);
                            ctx.fill();
                        }, i * 25);
                    }
                }, index * 100);
            });

          
            ctx.fillStyle = '#ffff00';
            ctx.font = 'bold 40px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('BUGS ELIMINATED!', canvas.width / 2, canvas.height / 2);

            setTimeout(() => {
                
                
                initialBugCount = 5;
                respawnBugs(initialBugCount);
            }, 1000);
        }

        function respawnBugs(count) {
            let occupiedPositions = [];
            occupiedPositions.push({
                x: Math.floor(player.x / GRID_SIZE) * GRID_SIZE,
                y: Math.floor(player.y / GRID_SIZE) * GRID_SIZE
            });

            for (const wall of walls) {
                occupiedPositions.push({x: wall.x, y: wall.y});
            }

            for (const terminal of questTerminals) {
                occupiedPositions.push({
                    x: Math.floor(terminal.x / GRID_SIZE) * GRID_SIZE,
                    y: Math.floor(terminal.y / GRID_SIZE) * GRID_SIZE
                });
            }

            occupiedPositions.push({x: door.x, y: door.y});

            for (const bug of bugs) {
                occupiedPositions.push({
                    x: Math.floor(bug.x / GRID_SIZE) * GRID_SIZE,
                    y: Math.floor(bug.y / GRID_SIZE) * GRID_SIZE
                });
            }

            for (let i = 0; i < count; i++) {
                const randomPos = getRandomEmptyPosition(occupiedPositions);
                if (randomPos) {
                    const bugTypes = [
                        { color: '#f44336', speed: 1.5, pattern: 'up' },
                        { color: '#ff5722', speed: 1.2, pattern: 'down' },
                        { color: '#d32f2f', speed: 1.8, pattern: 'left' },
                        { color: '#c2185b', speed: 1.0, pattern: 'right' }
                    ];

                    const bugType = bugTypes[Math.floor(Math.random() * bugTypes.length)];

                    bugs.push({
                        x: randomPos.x,
                        y: randomPos.y,
                        width: 30,
                        height: 40,
                        color: bugType.color,
                        speed: bugType.speed + level * 0.2,
                        pattern: bugType.pattern,
                        directionX: Math.random() > 0.5 ? 1 : -1,
                        directionY: Math.random() > 0.5 ? 1 : -1,
                        moveTimer: 0,
                        changeDirectionTime: 60 + Math.random() * 60,
                        id: bugs.length + 1,
                        frameX: 0,
                        frameY: 0,
                        animationCounter: 0,
                        animationSpeed: 0.15,
                        draw: function() {
                            let frameW = 10;
                            let frameH = 10;

                            if (bugSpriteLoaded && bugSprite.complete && bugSprite.naturalHeight !== 0) {
                                frameW = actualBugFrameWidth;
                                frameH = actualBugFrameHeight;
                            }

                            const scaleX = this.width / frameW;
                            const scaleY = this.height / frameH;

                            const drawWidth = frameW * scaleX;
                            const drawHeight = frameH * scaleY;

                            let spriteRow = 0;

                            if (this.directionY < 0) {
                                spriteRow = 3;
                            } else if (this.directionY > 0) {
                                spriteRow = 2;
                            } else if (this.directionX < 0) {
                                spriteRow = 1;
                            } else if (this.directionX > 0) {
                                spriteRow = 0;
                            }


                            const drawX = this.x - (drawWidth - this.width) / 2;
                            const drawY = this.y - (drawHeight - this.height) / 2;

                            if (bugSpriteLoaded && bugSprite.complete && bugSprite.naturalHeight !== 0) {
                                ctx.drawImage(
                                    bugSprite,
                                    this.frameX * frameW,
                                    spriteRow * frameH,
                                    frameW,
                                    frameH,
                                    drawX,
                                    drawY,
                                    drawWidth,
                                    drawHeight
                                );

                                this.animationCounter += this.animationSpeed;
                                if (this.animationCounter >= 1) {
                                    this.animationCounter = 0;
                                    this.frameX++;
                                    if (this.frameX >= BUG_SPRITE_COLS) {
                                        this.frameX = 0;
                                    }
                                }
                            } else {
                                ctx.fillStyle = this.color;
                                ctx.fillRect(this.x, this.y, this.width, this.height);

                                ctx.fillStyle = '#0d1520';
                                ctx.fillRect(this.x + 7, this.y + 7, 5, 5);
                                ctx.fillRect(this.x + this.width - 12, this.y + 7, 5, 5);

                                ctx.strokeStyle = this.color;
                                ctx.lineWidth = 2;
                                ctx.beginPath();
                                ctx.moveTo(this.x + 8, this.y);
                                ctx.lineTo(this.x + 3, this.y - 8);
                                ctx.moveTo(this.x + this.width - 8, this.y);
                                ctx.lineTo(this.x + this.width - 3, this.y - 8);
                                ctx.stroke();
                            }
                        },
                        update: function() {
                            this.moveTimer++;

                            const dx = player.x + player.width/2 - (this.x + this.width/2);
                            const dy = player.y + player.height/2 - (this.y + this.height/2);
                            const distance = Math.sqrt(dx * dx + dy * dy);

                            if (distance < 300 && !questActive) {
                                const speed = this.speed;
                                this.directionX = dx / distance;
                                this.directionY = dy / distance;

                                let newX = this.x + this.directionX * speed;
                                let newY = this.y + this.directionY * speed;

                                const newRect = {
                                    x: newX,
                                    y: newY,
                                    width: this.width,
                                    height: this.height
                                };

                                let hitWall = checkWallCollision(newRect);

                                if (newX <= 0 || newX + this.width >= canvas.width ||
                                    newY <= 0 || newY + this.height >= canvas.height) {
                                    hitWall = true;
                                }

                                if (!hitWall) {
                                    this.x = newX;
                                    this.y = newY;
                                }
                            } else {
                                if (this.moveTimer >= this.changeDirectionTime || (this.directionX === 0 && this.directionY === 0)) {
                                this.moveTimer = 0;
                                this.changeDirectionTime = 40 + Math.random() * 40;

                                const randDir = Math.floor(Math.random() * 4);
                                switch(randDir) {
                                    case 0: this.directionX = 0; this.directionY = -1; break; // Up
                                    case 1: this.directionX = 0; this.directionY = 1; break;  // Down
                                    case 2: this.directionX = -1; this.directionY = 0; break; // Left
                                    case 3: this.directionX = 1; this.directionY = 0; break;  // Right
                                }
                            }

                            let newX = this.x + this.directionX * this.speed;
                            let newY = this.y + this.directionY * this.speed;

                            const newRect = {
                                x: newX,
                                y: newY,
                                width: this.width,
                                height: this.height
                            };

                            let hitWall = checkWallCollision(newRect);

                            if (newX <= 0 || newX + this.width >= canvas.width ||
                                newY <= 0 || newY + this.height >= canvas.height) {
                                hitWall = true;
                            }

                            if (hitWall) {
                                // ANTI-STUCK WALL PATH RATIO
                                this.x -= this.directionX * 3;
                                this.y -= this.directionY * 3;

                                this.directionX *= -1;
                                this.directionY *= -1;
                                this.moveTimer = this.changeDirectionTime;
                            } else {
                                this.x = newX;
                                this.y = newY;
                            }
                        }
                        }
                    });
                    occupiedPositions.push({x: randomPos.x, y: randomPos.y});
                }
            }
        }
                             
         function updateBugMovement(bug) {
                            this.moveTimer++;

                            // Random movement selection (Up, Down, Left, Right lang)
                            if (this.moveTimer >= this.changeDirectionTime || (this.directionX === 0 && this.directionY === 0)) {
                                this.moveTimer = 0;
                                this.changeDirectionTime = 40 + Math.random() * 40;

                                const randDir = Math.floor(Math.random() * 4);
                                switch(randDir) {
                                    case 0: // Up
                                        this.directionX = 0;
                                        this.directionY = -1;
                                        break;
                                    case 1: // Down
                                        this.directionX = 0;
                                        this.directionY = 1;
                                        break;
                                    case 2: // Left
                                        this.directionX = -1;
                                        this.directionY = 0;
                                        break;
                                    case 3: // Right
                                        this.directionX = 1;
                                        this.directionY = 0;
                                        break;
                                }
                            }

                            let newX = this.x + this.directionX * this.speed;
                            let newY = this.y + this.directionY * this.speed;

                            const newRect = {
                                x: newX,
                                y: newY,
                                width: this.width,
                                height: this.height
                            };

                            let hitWall = checkWallCollision(newRect);

                            if (newX <= 0 || newX + this.width >= canvas.width ||
                                newY <= 0 || newY + this.height >= canvas.height) {
                                hitWall = true;
                            }

                            if (hitWall) {
                                // WALL FIX: Konting atras para iwas pasok sa pader
                                this.x -= this.directionX * 2;
                                this.y -= this.directionY * 2;

                                this.directionX *= -1;
                                this.directionY *= -1;
                                this.moveTimer = this.changeDirectionTime;
                            } else {
                                this.x = newX;
                                this.y = newY;
                            }
                        }


        // BUG: Add extra bugs when answer is wrong
        function addExtraBugs(count) {
            let occupiedPositions = [];
            occupiedPositions.push({
                x: Math.floor(player.x / GRID_SIZE) * GRID_SIZE,
                y: Math.floor(player.y / GRID_SIZE) * GRID_SIZE
            });

            for (const wall of walls) {
                occupiedPositions.push({x: wall.x, y: wall.y});
            }

            for (const terminal of questTerminals) {
                occupiedPositions.push({
                    x: Math.floor(terminal.x / GRID_SIZE) * GRID_SIZE,
                    y: Math.floor(terminal.y / GRID_SIZE) * GRID_SIZE
                });
            }

            occupiedPositions.push({x: door.x, y: door.y});

            for (const bug of bugs) {
                occupiedPositions.push({
                    x: Math.floor(bug.x / GRID_SIZE) * GRID_SIZE,
                    y: Math.floor(bug.y / GRID_SIZE) * GRID_SIZE
                });
            }

            for (let i = 0; i < count; i++) {
                const randomPos = getRandomEmptyPosition(occupiedPositions);
                if (randomPos) {
                    const bugTypes = [
                        { color: '#f44336', speed: 1.5, pattern: 'up' },
                        { color: '#ff5722', speed: 1.2, pattern: 'down' },
                        { color: '#d32f2f', speed: 1.8, pattern: 'left' },
                        { color: '#c2185b', speed: 1.0, pattern: 'right' }
                    ];

                    const bugType = bugTypes[Math.floor(Math.random() * bugTypes.length)];

                    bugs.push({
                        x: randomPos.x,
                        y: randomPos.y,
                        width: 30,
                        height: 40,
                        color: bugType.color,
                        speed: bugType.speed + level * 0.2,
                        pattern: bugType.pattern,
                        directionX: Math.random() > 0.5 ? 1 : -1,
                        directionY: Math.random() > 0.5 ? 1 : -1,
                        moveTimer: 0,
                        changeDirectionTime: 60 + Math.random() * 60,
                        id: bugs.length + 1,
                        frameX: 0,
                        frameY: 0,
                        animationCounter: 0,
                        animationSpeed: 0.15,
                        draw: function() {
                            let frameW = 10;
                            let frameH = 10;

                            if (bugSpriteLoaded && bugSprite.complete && bugSprite.naturalHeight !== 0) {
                                frameW = actualBugFrameWidth;
                                frameH = actualBugFrameHeight;
                            }

                            const scaleX = this.width / frameW;
                            const scaleY = this.height / frameH;

                            const drawWidth = frameW * scaleX;
                            const drawHeight = frameH * scaleY;

                            let spriteRow = 0;

                            if (this.directionY < 0) {
                                spriteRow = 3;
                            } else if (this.directionY > 0) {
                                spriteRow = 2;
                            } else if (this.directionX < 0) {
                                spriteRow = 1;
                            } else if (this.directionX > 0) {
                                spriteRow = 0;
                            }

                            const drawX = this.x - (drawWidth - this.width) / 2;
                            const drawY = this.y - (drawHeight - this.height) / 2;

                            if (bugSpriteLoaded && bugSprite.complete && bugSprite.naturalHeight !== 0) {
                                ctx.drawImage(
                                    bugSprite,
                                    this.frameX * frameW,
                                    spriteRow * frameH,
                                    frameW,
                                    frameH,
                                    drawX,
                                    drawY,
                                    drawWidth,
                                    drawHeight
                                );

                                this.animationCounter += this.animationSpeed;
                                if (this.animationCounter >= 1) {
                                    this.animationCounter = 0;
                                    this.frameX++;
                                    if (this.frameX >= BUG_SPRITE_COLS) {
                                        this.frameX = 0;
                                    }
                                }
                            } else {
                                ctx.fillStyle = this.color;
                                ctx.fillRect(this.x, this.y, this.width, this.height);

                                ctx.fillStyle = '#0d1520';
                                ctx.fillRect(this.x + 7, this.y + 7, 5, 5);
                                ctx.fillRect(this.x + this.width - 12, this.y + 7, 5, 5);

                                ctx.strokeStyle = this.color;
                                ctx.lineWidth = 2;
                                ctx.beginPath();
                                ctx.moveTo(this.x + 8, this.y);
                                ctx.lineTo(this.x + 3, this.y - 8);
                                ctx.moveTo(this.x + this.width - 8, this.y);
                                ctx.lineTo(this.x + this.width - 3, this.y - 8);
                                ctx.stroke();
                            }
                        },
                        update: function() {
                            this.moveTimer++;

                            const dx = player.x + player.width/2 - (this.x + this.width/2);
                            const dy = player.y + player.height/2 - (this.y + this.height/2);
                            const distance = Math.sqrt(dx * dx + dy * dy);

                            if (distance < 300 && !questActive) {
                                const speed = this.speed;
                                this.directionX = dx / distance;
                                this.directionY = dy / distance;

                                let newX = this.x + this.directionX * speed;
                                let newY = this.y + this.directionY * speed;

                                const newRect = {
                                    x: newX,
                                    y: newY,
                                    width: this.width,
                                    height: this.height
                                };

                                let hitWall = checkWallCollision(newRect);

                                if (newX <= 0 || newX + this.width >= canvas.width ||
                                    newY <= 0 || newY + this.height >= canvas.height) {
                                    hitWall = true;
                                }

                                if (!hitWall) {
                                    this.x = newX;
                                    this.y = newY;
                                }
                            } else {
                                if (this.moveTimer >= this.changeDirectionTime) {
                                    this.moveTimer = 0;
                                    this.changeDirectionTime = 60 + Math.random() * 60;

                                    switch(this.pattern) {
                                        case 'up':
                                            this.directionX = Math.random() > 0.5 ? 1 : -1;
                                            this.directionY = 0;
                                            break;
                                        case 'down':
                                            this.directionY = Math.random() > 0.5 ? 1 : -1;
                                            this.directionX = 0;
                                            break;
                                        case 'left':
                                            this.directionX = Math.random() > 0.5 ? 1 : -1;
                                            this.directionY = Math.random() > 0.5 ? 1 : -1;
                                            break;
                                        case 'right':
                                            this.directionX = Math.random() > 0.5 ? 1 : -1;
                                            this.directionY = Math.random() > 0.5 ? 1 : -1;
                                            break;
                                    }
                                }

                                let newX = this.x + this.directionX * this.speed;
                                let newY = this.y + this.directionY * this.speed;

                                const newRect = {
                                    x: newX,
                                    y: newY,
                                    width: this.width,
                                    height: this.height
                                };

                                let hitWall = checkWallCollision(newRect);

                                if (newX <= 0 || newX + this.width >= canvas.width ||
                                    newY <= 0 || newY + this.height >= canvas.height) {
                                    hitWall = true;
                                }

                                if (hitWall) {
                                    this.directionX *= -1;
                                    this.directionY *= -1;
                                    this.moveTimer = this.changeDirectionTime - 10;
                                } else {
                                    this.x = newX;
                                    this.y = newY;
                                }
                            }
                        }
                    });
                    occupiedPositions.push({x: randomPos.x, y: randomPos.y});
                }
            }
            
            // Show warning message
            ctx.fillStyle = '#ff0000';
            ctx.font = 'bold 30px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('WARNING: MORE BUGS ADDED!', canvas.width / 2, canvas.height / 2);
        }
        
        function showHint() {
            const question = currentQuestTerminal.question;
            
            questHintUsed = true;
            questHintBtn.disabled = true;
            questHintBtn.innerHTML = '<i class="fas fa-lightbulb"></i> Hint Used';
            
            score -= 20;
            updateUI();
            
            questResult.style.display = 'block';
            questResult.className = 'quest-result result-correct';
            questResult.innerHTML = `
                <h3><i class="fas fa-lightbulb"></i> Hint (-20 points)</h3>
                <p>${question.hint}</p>
            `;
            
            setTimeout(() => {
                questResult.style.display = 'none';
            }, 3000);
        }
        
        function drawWalls() {
            walls.forEach(wall => {
                if (wallSpriteLoaded && wallSprite.complete && wallSprite.naturalHeight !== 0) {
                    const frameW = wallSprite.naturalWidth;
                    const frameH = wallSprite.naturalHeight;
                    const scaleX = wall.width / frameW;
                    const scaleY = wall.height / frameH;
                    const drawWidth = frameW * scaleX;
                    const drawHeight = frameH * scaleY;
                    const drawX = wall.x - (drawWidth - wall.width) / 2;
                    const drawY = wall.y - (drawHeight - wall.height) / 2;
                    ctx.drawImage(wallSprite, drawX, drawY, drawWidth, drawHeight);
                    ctx.fillStyle = activeTheme.wall;
                    ctx.globalAlpha = 0.28;
                    ctx.fillRect(wall.x, wall.y, wall.width, wall.height);
                    ctx.globalAlpha = 1;
                } else {
                    ctx.fillStyle = activeTheme.wall;
                    ctx.fillRect(wall.x, wall.y, wall.width, wall.height);
                    
                    ctx.fillStyle = '#455a64';
                    ctx.fillRect(wall.x, wall.y, wall.width, 2);
                    ctx.fillRect(wall.x, wall.y, 2, wall.height);
                    
                    ctx.fillStyle = '#263238';
                    ctx.fillRect(wall.x + wall.width - 2, wall.y, 2, wall.height);
                    ctx.fillRect(wall.x, wall.y + wall.height - 2, wall.width, 2);
                    
                    ctx.strokeStyle = '#2c3e50';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(wall.x, wall.y, wall.width, wall.height);
                }
            });
        }
        
        function update() {
            if (gameFinished) {
                finishAnimationTimer++;
                if (finishAnimationTimer >= 180) {
                    gameFinished = false;
                    finishAnimationTimer = 0;
                    level = 1;
                    score = 0;
                    lives = 3;
                    initLevel();
                    gameRunning = true;
                }
                return;
            }

            if (!gameRunning || gamePaused || questActive) return;

            let newX = player.x;
            let newY = player.y;
            let moved = false;

            if (keys.ArrowUp || keys.w) {
                newY -= player.speed;
                player.direction = 'up';
                moved = true;
            }
            if (keys.ArrowDown || keys.s) {
                newY += player.speed;
                player.direction = 'down';
                moved = true;
            }
            if (keys.ArrowLeft || keys.a) {
                newX -= player.speed;
                player.direction = 'left';
                moved = true;
            }
            if (keys.ArrowRight || keys.d) {
                newX += player.speed;
                player.direction = 'right';
                moved = true;
            }

            player.moving = moved;

            const testRect = {
                x: newX,
                y: newY,
                width: player.width,
                height: player.height
            };

            if (!checkWallCollision(testRect)) {
                player.x = newX;
                player.y = newY;
            } else {
                let canMoveX = false;
                let canMoveY = false;

                const testRectX = {
                    x: newX,
                    y: player.y,
                    width: player.width,
                    height: player.height
                };
                if (!checkWallCollision(testRectX)) {
                    canMoveX = true;
                }

                const testRectY = {
                    x: player.x,
                    y: newY,
                    width: player.width,
                    height: player.height
                };
                if (!checkWallCollision(testRectY)) {
                    canMoveY = true;
                }

                if (canMoveX) {
                    player.x = newX;
                }
                if (canMoveY) {
                    player.y = newY;
                }
            }

            player.x = Math.max(0, Math.min(canvas.width - player.width, player.x));
            player.y = Math.max(0, Math.min(canvas.height - player.height, player.y));

bugs = bugs.filter(bug => {
    if (!questActive && !gamePaused) {
        bug.update(); 
    }
    
    if (checkCollision(player, bug)) {
    // ONE LIFE PER BUG TOUCH
    // Every bug touch = exactly 1 life deducted.
    // The bug is removed immediately after hitting the player.

    if (lives > 0) {
        lives--;

        if (lives < 0) lives = 0;

        updateUI();
        playSound(hitSound);

        let startX = 100, startY = 100;

        for (let row = 0; row < currentMap.length; row++) {
            for (let col = 0; col < currentMap[row].length; col++) {
                if (currentMap[row][col] === 2) {
                    startX = col * GRID_SIZE;
                    startY = row * GRID_SIZE;
                    break;
                }
            }
        }

        player.x = startX;
        player.y = startY;

        canvas.style.boxShadow = '0 0 30px rgba(244, 67, 54, 0.8)';

        setTimeout(() => {
            canvas.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.7)';
        }, 200);

        if (lives <= 0) {
            gameOver();
        }
    }

    // Remove the bug after one touch
    return false;
}

 return true;

});






            questTerminals.forEach(terminal => {
                if (!terminal.completed && checkCollision(player, terminal)) {
                    showQuest(terminal);
                }
            });

            if (doorOpen && checkCollision(player, door)) {
                levelComplete();
            }
        }
        
        function unlockNextLevel() {
            const currentUnlocked = parseInt(localStorage.getItem('unlockedStoryLevel'), 10) || 1;
            const nextUnlocked = storyId >= totalStories ? totalStories + 1 : storyId + 1;
            if (nextUnlocked > currentUnlocked) {
                localStorage.setItem('unlockedStoryLevel', String(nextUnlocked));
            }
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = activeTheme.floor;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            if (themeBgImage.complete && themeBgImage.naturalWidth > 0) {
                ctx.save();
                ctx.globalAlpha = 0.28;
                ctx.drawImage(themeBgImage, 0, 0, canvas.width, canvas.height);
                ctx.restore();
            }
            
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
            ctx.lineWidth = 1;
            
            for (let x = 0; x <= canvas.width; x += 50) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, canvas.height);
                ctx.stroke();
            }
            
            for (let y = 0; y <= canvas.height; y += 50) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(canvas.width, y);
                ctx.stroke();
            }
            
            drawWalls();
            
            questTerminals.forEach(terminal => terminal.draw());
            bugs.forEach(bug => bug.draw());
            door.draw();
            player.draw();
            
            ctx.fillStyle = '#4fc3f7';
            ctx.font = 'bold 20px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(`LEVEL ${storyId} - ${getDifficultyName(storyId)} DIFFICULTY`, canvas.width / 2, 30);
            
            ctx.fillStyle = '#9c27b0';
            ctx.font = 'bold 18px Arial';
            ctx.fillText(`QUESTS: ${questsCompleted}/3`, canvas.width / 2, 60);
            
            if (timeLeft <= 20) {
                ctx.fillStyle = '#f44336';
                ctx.font = 'bold 18px Arial';
                ctx.fillText(`HURRY! ${timeLeft}s LEFT!`, canvas.width / 2, 90);
            } else if (timeLeft <= 40) {
                ctx.fillStyle = '#ff9800';
                ctx.font = 'bold 18px Arial';
                ctx.fillText(`${timeLeft}s REMAINING!`, canvas.width / 2, 90);
            }
            
            
            if (doorOpen) {
                ctx.fillStyle = '#ff9800';
                ctx.font = 'bold 18px Arial';
                ctx.fillText('EXIT DOOR OPEN!', canvas.width / 2, 120);
            }

            if (storyId >= 5 && !lightModeCheatActive) {
                const px = player.x + player.width / 2;
                const py = player.y + player.height / 2;
                const radius = Math.max(player.width, player.height) * 3.00;
                ctx.save();
                ctx.fillStyle = '#000000';
                ctx.beginPath();
                ctx.rect(0, 0, canvas.width, canvas.height);
                ctx.arc(px, py, radius, 0, Math.PI * 2, true);
                ctx.fill('evenodd');
                ctx.restore();
            }
        }
        
        function gameLoop() {
            update();
            draw();
            requestAnimationFrame(gameLoop);
        }
        
        function levelComplete() {
            gameRunning = false;
            clearInterval(timerInterval);

            playSound(levelCompleteSound);
            unlockNextLevel();

            if (storyId < totalStories) {
                levelCompleteMessage.style.display = 'block';
                const titleEl = levelCompleteMessage.querySelector('.message-title');
                const textEl = levelCompleteMessage.querySelector('.message-text');
                if (titleEl) titleEl.textContent = `LEVEL ${storyId} COMPLETE!`;
                if (textEl) {
                    textEl.textContent =
                        `You finished Story ${storyId} (${getDifficultyName(storyId)}). Next level unlocked. Score: ${score}`;
                }
                const nextBtn = document.getElementById('next-level-btn');
                if (nextBtn) nextBtn.textContent = `GO TO LEVEL ${storyId + 1}`;
                setTimeout(() => proceedToNextStory(), 2200);
            } else {
                proceedToNextStory();
            }

            updateUI();
        }
        
        function gameOver() {
            gameRunning = false;
            clearInterval(timerInterval);
            clearInterval(questTimerInterval);
            
            gameOverMessage.style.display = 'block';
            
            playSound(gameOverSound);
        }

        let advancingStory = false;

        function proceedToNextStory() {
            if (advancingStory) return;
            advancingStory = true;
            unlockNextLevel();
            const nextStoryId = storyId + 1;

            if (nextStoryId > totalStories) {
                gameFinished = true;
                finishAnimationTimer = 0;
                if (levelCompleteMessage) {
                    const titleEl = levelCompleteMessage.querySelector('.message-title');
                    const textEl = levelCompleteMessage.querySelector('.message-text');
                    if (titleEl) titleEl.textContent = 'ALL STORIES COMPLETE!';
                    if (textEl) textEl.textContent = 'You escaped every maze. Returning to the menu...';
                    const nextBtn = document.getElementById('next-level-btn');
                    if (nextBtn) nextBtn.textContent = 'BACK TO MENU';
                    levelCompleteMessage.style.display = 'block';
                }
                setTimeout(() => {
                    window.location.href = 'buttons.php';
                }, 1800);
                return;
            }

            if (storyLoadingMessage) {
                storyLoadingMessage.style.display = 'block';
            }

            setTimeout(() => {
                window.location.href = `storyline.php?story=${nextStoryId}`;
            }, 1500);
        }

        function nextLevel() {
            proceedToNextStory();
        }

        function restartGame() {
            gameOverMessage.style.display = 'none';
            levelCompleteMessage.style.display = 'none';
            pauseMenu.style.display = 'none';

            clearInterval(timerInterval);
            clearInterval(questTimerInterval);

            bugs = [];
            questTerminals = [];
            walls = [];

            player.isInvulnerable = false;

            score = 0;
            lives = 3;
            level = storyId;
            gamePaused = false;

            initLevel();
            gameRunning = true;
        }

        
        document.addEventListener('keydown', (e) => {
            if (keys.hasOwnProperty(e.key)) {
                keys[e.key] = true;
            }
            
            if ((e.key === 'p' || e.key === 'P' || e.key === 'Escape') && gameRunning && !questActive) {
                togglePause();
                e.preventDefault();
            }
            
            if (e.key === 'r' || e.key === 'R') {
                restartGame();
            }
        });
        
        document.addEventListener('keyup', (e) => {
            if (keys.hasOwnProperty(e.key)) {
                keys[e.key] = false;
            }
        });
        
        nextLevelBtn.addEventListener('click', nextLevel);
        restartBtn.addEventListener('click', restartGame);
        
        resumeBtn.addEventListener('click', togglePause);
        pauseRestartBtn.addEventListener('click', restartGame);
        quitBtn.addEventListener('click', () => {
            pauseMenu.style.display = 'none';
            gamePaused = false;
            restartGame();
        });
        
        questSubmitBtn.addEventListener('click', () => submitQuestAnswer());
questHintBtn.addEventListener('click', showHint);

questCloseBtn.addEventListener('click', () => {
    clearInterval(questTimerInterval); // Ititigil ang timer ng quest
    
    // 👇 ITO ANG MAG-CLOCLOSE NG MISMONG CONTAINER MO 👇
    const popupContainer = document.getElementById('quest-popup');
    if (popupContainer) {
        popupContainer.style.display = 'none'; // Gagawing 'none' para mawala sa screen
    }
    
    questActive = false;               // Papayagan muli ang player na makagalaw sa maze
    currentQuestTerminal = null;       // I-re-reset ang active terminal block
    
    // Iurong ang player nang kaunti para hindi mag-loop ang popup habang nakatapak sa terminal
    if (player.direction === 'up') player.y += 15;
    else if (player.direction === 'down') player.y -= 15;
    else if (player.direction === 'left') player.x += 15;
    else if (player.direction === 'right') player.x -= 15;
    
    startTimer();                      // Patakbuhin muli ang oras ng main game window
});

// 2. Kapag kinlick ang Exit Game, babalik ito nang tuluyan sa buttons.php
questExitBtn.addEventListener('click', () => {
    clearInterval(timerInterval);
    clearInterval(questTimerInterval);
    window.location.href = 'buttons.php'; 
});

        
        soundToggleBtn.addEventListener('none', toggleSound);
        musicToggleBtn.addEventListener('click', toggleMusic);
        
        initLevel();
        
        document.addEventListener('click', function startMusic() {
            if (musicEnabled) {
                backgroundMusic.volume = 0.3;
                backgroundMusic.play().catch(e => console.log("Music autoplay prevented"));
            }
            document.removeEventListener('click', startMusic);
        }, { once: true });
        
        gameLoop();
        
        const title = document.querySelector('.game-title');
        let hue = 0;
        
        function updateTitleColor() {
            hue = (hue + 1) % 360;
            title.style.color = `hsl(${hue}, 80%, 65%)`;
            requestAnimationFrame(updateTitleColor);
        }
        
        updateTitleColor();

        // Active Quests Modal Event Listeners
const openQuestsBtn = document.getElementById('open-quests-btn');
const closeQuestsModalBtn = document.getElementById('close-quests-modal-btn');
const activeQuestsModal = document.getElementById('active-quests-modal');

if (openQuestsBtn && activeQuestsModal) {
    openQuestsBtn.addEventListener('click', () => {
        activeQuestsModal.style.display = 'flex';
    });
}

if (closeQuestsModalBtn && activeQuestsModal) {
    closeQuestsModalBtn.addEventListener('click', () => {
        activeQuestsModal.style.display = 'none';
    });
}

// Close modal when clicking outside content
window.addEventListener('click', (event) => {
    if (event.target === activeQuestsModal) {
        activeQuestsModal.style.display = 'none';
    }
});


// LEVEL 5 TEMPORARY LIGHT MODE CHEAT
document.addEventListener('keydown', function (e) {
    if (e.key !== 'F6') return;

    // Cheat works only on Level 5
    if (storyId !== 5) {
        alert('This cheat code only works on Level 5!');
        return;
    }

    const code = prompt('Enter cheat code to disable Dark Mode:');

    if (code === null) return;

    if (code.trim().toUpperCase() === 'LIGHTMODE2026') {
        lightModeCheatActive = true;

        // Redraw immediately
        draw();

        alert('LIGHT MODE ACTIVATED! Dark Mode is disabled for this session.');
    } else {
        alert('INVALID CHEAT CODE!');
    }
});


