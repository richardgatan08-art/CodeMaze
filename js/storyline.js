const storyThemes = [
    { image: '../image/problema.jpg', name: 'office' },
    { image: '../image/town.jpg', name: 'cave' },
    { image: '../image/excited.jpg', name: 'trap' },
    { image: '../image/4.jpg', name: 'maze' },
    { image: '../image/box.jpg', name: 'core' }
];

const chapters = [
    {
        title: 'THE OVERWORKED EMPLOYEE',
        lines: [
            { type: 'title', text: 'THE OVERWORKED EMPLOYEE' },
            { type: 'spacer' },
            { type: 'body', text: 'The main character is an' },
            { type: 'body', text: 'overworked office employee' },
            { type: 'body', text: 'who finally takes a vacation' },
            { type: 'body', text: 'after months of stress.' },
            { type: 'spacer' },
            { type: 'body', text: 'Wanting a break from city' },
            { type: 'body', text: 'life, he decides to hike up' },
            { type: 'body', text: 'a remote mountain known' },
            { type: 'body', text: 'for its beautiful scenery.' }
        ]
    },
    {
        title: 'THE MYSTERIOUS CAVE',
        lines: [
            { type: 'title', text: 'THE MYSTERIOUS CAVE' },
            { type: 'spacer' },
            { type: 'body', text: 'After reaching the summit,' },
            { type: 'body', text: 'he notices an old cave' },
            { type: 'body', text: 'hidden behind thick vines.' },
            { type: 'body', text: 'Beside the entrance is' },
            { type: 'body', text: 'a faded wooden sign' },
            { type: 'body', text: 'that reads:' }
        ]
    },
    {
        title: 'THE TRAP',
        lines: [
            { type: 'title', text: 'THE TRAP' },
            { type: 'spacer' },
            { type: 'body', text: "Curious and thinking it's" },
            { type: 'body', text: 'just an old legend meant' },
            { type: 'body', text: 'to scare tourists, he' },
            { type: 'body', text: 'enters the cave.' },
            { type: 'spacer' },
            { type: 'body', text: 'As he walks deeper,' },
            { type: 'body', text: 'the entrance suddenly' },
            { type: 'body', text: 'seals behind him with' },
            { type: 'body', text: 'a loud rumble.' },
            { type: 'spacer' },
            { type: 'body', text: 'The cave begins to shake,' },
            { type: 'body', text: 'and a mysterious light' },
            { type: 'body', text: 'surrounds him.' },
            { type: 'spacer' },
            { type: 'body', text: 'When the light fades,' },
            { type: 'body', text: 'he realizes he is no longer' },
            { type: 'body', text: 'in the cave but inside a' },
            { type: 'body', text: 'strange ancient maze.' }
        ]
    },
    {
        title: 'THE ANCIENT MAZE',
        lines: [
            { type: 'title', text: 'THE ANCIENT MAZE' },
            { type: 'spacer' },
            {
                type: 'body',
                text: 'He finds himself in a strange, ancient maze filled with:'
            },
            { type: 'spacer' },
            { type: 'subtitle', text: 'Coding Question' },
            { type: 'body', text: 'Solve coding puzzles to open the path.' },
            { type: 'spacer' },
            { type: 'subtitle', text: 'Bugs' },
            { type: 'body', text: 'Watch out! Bugs try to stop your progress.' },
            { type: 'spacer' },
            { type: 'subtitle', text: 'Rewards' },
            {
                type: 'body',
                text: 'Earn rewards for correct answers and brave moves.'
            },
            { type: 'spacer' },
            {
                type: 'emphasis',
                text: '"Those who seek answers must first find the exit. Only then will you return to your world."'
            }
        ]
    },
    {
        title: 'THE GOAL',
        lines: [
            { type: 'title', text: 'THE GOAL' },
            { type: 'subtitle', text: 'NEXT LEVEL' },
            { type: 'spacer' },
            { type: 'body', text: "The player's goal is to" },
            { type: 'body', text: 'survive the maze, solve' },
            { type: 'body', text: 'coding questions to' },
            { type: 'body', text: 'unlock the exit, escape' },
            { type: 'body', text: 'each level, and continue' },
            { type: 'body', text: 'to the next stage.' },
            { type: 'spacer' },
            { type: 'body', text: 'Every level introduces' },
            { type: 'body', text: 'more difficult coding' },
            { type: 'body', text: 'challenges, traps,' },
            { type: 'body', text: 'and enemies.' },
            { type: 'spacer' },
            {
                type: 'keyword-block',
                keywords: ['SURVIVE', 'SOLVE', 'ESCAPE', 'NEXTLEVEL']
            }
        ]
    }
];

const iconURLs = {
    SURVIVE: '../image/survive.jpg',
    SOLVE: '../image/solve.jpg',
    ESCAPE: '../image/escape.jpg',
    NEXTLEVEL: '../image/level.jpg'
};

const totalStories = chapters.length;

// Get the story selected by the player.
const params = new URLSearchParams(window.location.search);
const requestedStoryId = parseInt(params.get('story'), 10) || 1;

// Read the highest unlocked story.
const unlockedStoryLevel = Math.max(
    1,
    parseInt(localStorage.getItem('unlockedStoryLevel'), 10) || 1
);

// Prevent access to locked stories.
const storyId = Math.max(
    1,
    Math.min(requestedStoryId, unlockedStoryLevel, totalStories)
);

// Correct the URL if the selected story is locked or invalid.
if (requestedStoryId !== storyId) {
    window.history.replaceState(
        null,
        '',
        `storyline.php?story=${storyId}`
    );
}

// Select exactly ONE chapter for the selected story.
const chapterIndex = storyId - 1;
const selectedChapter = chapters[chapterIndex];
const selectedTheme = storyThemes[chapterIndex];

const storyEl = document.getElementById('storyContainer');
const bgA = document.getElementById('bgA');
const bgB = document.getElementById('bgB');

function changeBackground() {
    if (!selectedTheme) return;

    document.body.dataset.storyTheme = selectedTheme.name;

    bgA.style.backgroundImage = `url('${selectedTheme.image}')`;
    bgA.classList.add('active');
    bgB.classList.remove('active');
}

function createLine(line, index, fragment) {
    const delay = 0.35 + index * 0.15;
    let el;

    switch (line.type) {
        case 'title':
            el = document.createElement('div');
            el.className = 'line title';
            el.textContent = line.text;
            break;

        case 'subtitle':
            el = document.createElement('div');
            el.className = 'line subtitle';
            el.textContent = line.text;
            break;

        case 'body':
            el = document.createElement('div');
            el.className = 'line body-text';
            el.textContent = line.text;
            break;

        case 'emphasis':
            el = document.createElement('div');
            el.className = 'line emphasis';
            el.textContent = line.text;
            break;

        case 'spacer':
            el = document.createElement('div');
            el.className = 'line spacer';
            el.style.height = '0.8rem';
            break;

        case 'code':
            el = document.createElement('pre');
            el.className = 'code-block';
            el.textContent = line.text;
            break;

        case 'sign': {
            el = document.createElement('div');
            el.className = 'line sign-wrapper';

            const board = document.createElement('div');
            board.className = 'sign-board';

            const mainText = document.createElement('div');
            mainText.className = 'sign-text';
            mainText.textContent = line.text || 'WARNING';
            board.appendChild(mainText);

            if (line.sub) {
                const subText = document.createElement('div');
                subText.className = 'sign-sub';
                subText.textContent = line.sub;
                board.appendChild(subText);
            }

            el.appendChild(board);
            break;
        }

        case 'keyword-block': {
            el = document.createElement('div');
            el.className = 'line';

            const grid = document.createElement('div');
            grid.className = 'image-box-grid';

            line.keywords.forEach(keyword => {
                const box = document.createElement('div');
                box.className = 'image-box';

                const img = document.createElement('img');
                img.className = 'icon-img';
                img.src = iconURLs[keyword] || '';
                img.alt = keyword;
                img.loading = 'lazy';

                const label = document.createElement('span');
                label.className = 'label';
                label.textContent = keyword;

                box.appendChild(img);
                box.appendChild(label);
                grid.appendChild(box);
            });

            el.appendChild(grid);
            break;
        }

        default:
            return;
    }

    el.style.animationDelay = `${delay}s`;
    fragment.appendChild(el);
}

function renderChapter() {
    if (!storyEl || !selectedChapter) return;

    // Clear old content so only the selected story is displayed.
    storyEl.replaceChildren();

    const counter = document.createElement('div');
    counter.className = 'chapter-counter';
    counter.innerHTML =
        `Chapter <span class="num">${storyId}</span> / ${totalStories}`;

    storyEl.appendChild(counter);

    const contentWrapper = document.createElement('div');
    contentWrapper.className = 'story-content';

    const fragment = document.createDocumentFragment();

    // Render lines from the selected chapter only.
    selectedChapter.lines.forEach((line, index) => {
        createLine(line, index, fragment);
    });

    // Add a PLAY button for the selected story.
    const navWrapper = document.createElement('div');
    navWrapper.className = 'line nav-wrapper';

    const navControls = document.createElement('div');
    navControls.className = 'nav-controls';

    const playLink = document.createElement('a');
    playLink.className = 'skip-link';
    playLink.href = `game.php?story=${storyId}`;
    playLink.setAttribute('aria-label', `Play story ${storyId}`);
    playLink.textContent = 'PLAY';

    navControls.appendChild(playLink);
    navWrapper.appendChild(navControls);
    fragment.appendChild(navWrapper);

    contentWrapper.appendChild(fragment);
    storyEl.appendChild(contentWrapper);
}

// No chapter navigation that can switch to another story.
changeBackground();
renderChapter();

