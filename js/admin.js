document.addEventListener('DOMContentLoaded', () => {
    loadOverview();
    loadLeaderboards();
    loadBlogs();
    loadSlides();

    const titles = {
        dashboard: 'Dashboard <small>Game Overview</small>',
        leaderboard: 'Leaderboards <small>Player Rankings</small>',
        blog: 'Progress Blog <small>Homepage Posts</small>',
        slides: 'Demo Slides <small>Homepage Slider</small>',
        'create-account': 'Create Account <small>New Player</small>'
    };

    function switchTab(tab, focusId) {
        const panel = document.getElementById(tab + 'Tab');
        if (!panel) return;

        document.querySelectorAll('.sidebar-menu li').forEach(el => {
            el.classList.toggle('active', el.getAttribute('data-tab') === tab);
        });
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        panel.classList.add('active');
        document.getElementById('pageTitle').innerHTML = titles[tab] || '';

        if (focusId) {
            const field = document.getElementById(focusId);
            if (field) {
                field.focus();
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    document.querySelectorAll('.sidebar-menu li').forEach(li => {
        li.addEventListener('click', function () {
            switchTab(this.getAttribute('data-tab'));
        });
    });

    document.querySelectorAll('.stat-card[data-tab]').forEach(card => {
        card.addEventListener('click', function () {
            switchTab(this.getAttribute('data-tab'), this.getAttribute('data-focus'));
        });
    });

    const searchInput = document.getElementById('playerSearchInput');
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        if (!query) {
            document.getElementById('playerProfileCard').hidden = true;
            return;
        }
        searchTimeout = setTimeout(() => searchPlayer(query), 300);
    });

    document.getElementById('addBlogBtn').addEventListener('click', saveBlog);
    document.getElementById('resetBlogBtn').addEventListener('click', resetBlogForm);
    document.getElementById('slideForm').addEventListener('submit', saveSlide);
    document.getElementById('resetSlideBtn').addEventListener('click', resetSlideForm);
    document.getElementById('logoutBtn').addEventListener('click', logoutAdmin);
    document.getElementById('createAccountForm')
    .addEventListener('submit', createAccount);

document.getElementById('resetAccountBtn')
    .addEventListener('click', () => {
        document.getElementById('createAccountForm').reset();
    });

});
function showFlash(message, type = 'success') {
    const flash = document.getElementById('adminFlash');

    flash.hidden = false;
    flash.className = 'admin-flash ' + type;
    flash.textContent = message;

    clearTimeout(showFlash._t);

    showFlash._t = setTimeout(() => {
        flash.hidden = true;
    }, 3500);
}

async function createAccount(e) {
    e.preventDefault();

    const form = document.getElementById('createAccountForm');
    const email = document.getElementById('accountEmail').value.trim();
    const password = document.getElementById('accountPassword').value;
    const confirmPassword = document.getElementById('accountConfirmPassword').value;
    const button = document.getElementById('createAccountBtn');

    if (password.length < 8) {
        showFlash('Password must be at least 8 characters.', 'error');
        return;
    }

    if (password !== confirmPassword) {
        showFlash('Passwords do not match.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    button.disabled = true;
    

    try {
        const response = await fetch('../php/create_player_account.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });
        

        const data = await response.json().catch(() => null);

        if (response.status === 401) {
            window.location.href = 'index.php';
            return;
        }

        if (!response.ok || !data || !data.success) {
            showFlash(
                data?.error || 'Could not create account. Please try again.',
                'error'
            );
            return;
        }

        form.reset();
        showFlash('Player account created successfully.');

        if (typeof loadOverview === 'function') {
            await loadOverview();
        }

        if (typeof loadLeaderboards === 'function') {
            await loadLeaderboards();
        }

    } catch (error) {
        console.error('Create account error:', error);
        showFlash(
            'Unable to contact the server. Check your connection and try again.',
            'error'
        );
    } finally {
        button.disabled = false;
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function showFlash(message, type = 'success') {
    const flash = document.getElementById('adminFlash');
    flash.hidden = false;
    flash.className = 'admin-flash ' + type;
    flash.textContent = message;
    clearTimeout(showFlash._t);
    showFlash._t = setTimeout(() => {
        flash.hidden = true;
    }, 3500);
}

async function parseJson(res) {
    const data = await res.json().catch(() => null);
    if (res.status === 401) {
        window.location.href = 'index.php';
        throw new Error('Unauthorized');
    }
    return data;
}

async function loadOverview() {
    try {
        const res = await fetch('../php/game_admin_api.php?action=get_overview', { credentials: 'same-origin' });
        const data = await parseJson(res);
        if (data && data.success) {
            document.getElementById('totalPlayersCount').textContent = data.total_players;
            document.getElementById('gamesTodayCount').textContent = data.games_today;
            document.getElementById('questsTodayCount').textContent = data.quests_completed_today;
            document.getElementById('leaderboardCountStat').textContent = data.leaderboard_players;
            document.getElementById('blogCountStat').textContent = data.total_blogs;
            document.getElementById('slideCountStat').textContent = data.total_slides;
        }
    } catch (e) {
        console.error(e);
    }
}

async function searchPlayer(query) {
    try {
        const res = await fetch(
            `../php/game_admin_api.php?action=search_player&query=${encodeURIComponent(query)}`,
            { credentials: 'same-origin' }
        );
        const data = await parseJson(res);
        const profileCard = document.getElementById('playerProfileCard');
        const profileDetails = document.getElementById('profileDetails');

        if (data && data.success && data.players && data.players.length) {
            profileDetails.innerHTML = data.players.map(renderPlayerCard).join('');
            profileCard.hidden = false;
        } else {
            profileDetails.innerHTML = `<p class="error-text">${escapeHtml(data && data.error ? data.error : 'No player found.')}</p>`;
            profileCard.hidden = false;
        }
    } catch (e) {
        console.error(e);
        document.getElementById('profileDetails').innerHTML = '<p class="error-text">Could not search players.</p>';
        document.getElementById('playerProfileCard').hidden = false;
    }
}

function renderPlayerCard(p) {
    const joinDate = p.join_date ? new Date(p.join_date).toLocaleDateString() : '—';
    const weak = (p.weak_quests || []).map(q => `
        <div class="weak-row">
            <div class="weak-top">
                <span class="badge">${escapeHtml(q.language || 'Quest')}</span>
                <span>Missed ${escapeHtml(q.wrong_count)} time${Number(q.wrong_count) === 1 ? '' : 's'}</span>
            </div>
            <p>${escapeHtml(q.question_text || '')}</p>
        </div>
    `).join('') || '<p class="text-muted">No missed quests recorded.</p>';

    const recent = (p.recent_days || []).map(d => `
        <tr>
            <td>${escapeHtml(d.progress_date)}</td>
            <td>${escapeHtml(d.games_played)}</td>
            <td>${escapeHtml(d.quests_attempted)}</td>
            <td>${escapeHtml(d.quests_completed)}</td>
            <td>${escapeHtml(d.score_gained)}</td>
        </tr>
    `).join('') || '<tr><td colspan="5" class="text-muted">No play recorded in the last 7 days.</td></tr>';

    return `
        <div class="player-card">
            <div class="profile-details-grid">
                <p><strong>User ID:</strong> ${escapeHtml(p.id)}</p>
                <p><strong>Email:</strong> ${escapeHtml(p.email)}</p>
                <p><strong>Join Date:</strong> ${escapeHtml(joinDate)}</p>
                <p><strong>Lifetime Score:</strong> ${escapeHtml(p.total_score)}</p>
                <p><strong>Games Played:</strong> ${escapeHtml(p.total_games)}</p>
                <p><strong>Quests Tried:</strong> ${escapeHtml(p.quests_attempted)}</p>
                <p><strong>Quests Done:</strong> ${escapeHtml(p.quests_completed)}</p>
            </div>
            <h4 class="sub-heading">Last 7 days</h4>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Games</th>
                            <th>Tried</th>
                            <th>Done</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>${recent}</tbody>
                </table>
            </div>
            <h4 class="sub-heading">Quests to improve</h4>
            ${weak}
        </div>
    `;
}

async function loadLeaderboards() {
    try {
        const res = await fetch('../php/game_admin_api.php?action=get_leaderboard', { credentials: 'same-origin' });
        const data = await parseJson(res);
        const tbody = document.getElementById('leaderboardTableBody');

        if (data && data.success && data.leaderboard && data.leaderboard.length) {
            tbody.innerHTML = data.leaderboard.map((row, index) => `
                <tr>
                    <td>#${index + 1}</td>
                    <td>${escapeHtml(row.id)}</td>
                    <td>${escapeHtml(row.email)}</td>
                    <td>${escapeHtml(row.total_score)}</td>
                    <td>${escapeHtml(row.total_quests)}</td>
                    <td>${escapeHtml(row.total_games)}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-muted" style="text-align:center; padding:15px;">No players yet.</td></tr>';
        }
    } catch (e) {
        console.error(e);
        const tbody = document.getElementById('leaderboardTableBody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-muted" style="text-align:center; padding:15px;">Could not load leaderboard.</td></tr>';
    }
}

async function loadBlogs() {
    try {
        const res = await fetch('../php/blogs.php?action=list', { credentials: 'same-origin' });
        const data = await parseJson(res);
        const blogList = document.getElementById('blogList');

        if (data && data.success && data.blogs && data.blogs.length) {
            blogList.innerHTML = data.blogs.map(b => `
                <div class="blog-entry">
                    <div class="entry-head">
                        <span class="blog-week">Week ${escapeHtml(b.week)}</span>
                        <span class="text-muted">${escapeHtml(b.date || '')}</span>
                    </div>
                    <p>${escapeHtml(b.text)}</p>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="editBlog(${Number(b.id)}, ${Number(b.week)}, '${encodeURIComponent(b.text)}')">Edit</button>
                        <button type="button" class="btn btn-outline btn-danger" onclick="deleteBlog(${Number(b.id)})">Delete</button>
                    </div>
                </div>
            `).join('');
        } else {
            blogList.innerHTML = '<p class="text-muted">No blog entries published yet.</p>';
        }
    } catch (e) {
        console.error(e);
    }
}

function editBlog(id, week, encodedText) {
    document.getElementById('blogId').value = String(id);
    document.getElementById('blogWeek').value = String(week);
    document.getElementById('blogText').value = decodeURIComponent(encodedText || '');
    document.getElementById('addBlogBtn').innerHTML = '<i class="fas fa-save"></i> Update Entry';
}

function resetBlogForm() {
    document.getElementById('blogId').value = '';
    document.getElementById('blogWeek').value = '';
    document.getElementById('blogText').value = '';
    document.getElementById('addBlogBtn').innerHTML = '<i class="fas fa-plus"></i> Save Entry';
}

async function saveBlog() {
    const week = document.getElementById('blogWeek').value;
    const text = document.getElementById('blogText').value;
    const id = document.getElementById('blogId').value;

    const fd = new FormData();
    fd.append('action', id ? 'edit' : 'add');
    if (id) fd.append('id', id);
    fd.append('week', week);
    fd.append('text', text);

    const res = await fetch('../php/blogs.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await parseJson(res);

    if (data && data.success) {
        resetBlogForm();
        loadBlogs();
        loadOverview();
        showFlash(id ? 'Blog entry updated.' : (data.updated ? 'Week updated.' : 'Blog entry saved.'));
    } else {
        showFlash((data && data.error) || 'Could not save blog entry.', 'error');
    }
}

async function deleteBlog(id) {
    if (!confirm('Delete this blog post from the homepage?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    const res = await fetch('../php/blogs.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await parseJson(res);
    if (data && data.success) {
        loadBlogs();
        loadOverview();
        showFlash('Blog entry deleted.');
    } else {
        showFlash((data && data.error) || 'Could not delete blog entry.', 'error');
    }
}

async function loadSlides() {
    try {
        const res = await fetch('../php/slides.php?action=list', { credentials: 'same-origin' });
        const data = await parseJson(res);
        const slideList = document.getElementById('slideList');

        if (data && data.success && data.slides && data.slides.length) {
            slideList.innerHTML = data.slides.map(s => `
                <div class="slider-item">
                    <img src="${escapeHtml(s.image_url)}" alt="${escapeHtml(s.title || 'Demo slide')}">
                    <div>
                        <h4>${escapeHtml(s.title || 'Untitled slide')}</h4>
                        <p>${escapeHtml(s.description || '')}</p>
                        <p class="text-muted">${escapeHtml(s.date || '')}</p>
                        <div class="form-actions">
                            <button type="button" class="btn btn-outline" onclick="editSlide(${Number(s.id)}, '${encodeURIComponent(s.title || '')}', '${encodeURIComponent(s.description || '')}')">Edit</button>
                            <button type="button" class="btn btn-outline btn-danger" onclick="deleteSlide(${Number(s.id)})">Delete</button>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            slideList.innerHTML = '<p class="text-muted">No demo slides published yet.</p>';
        }
    } catch (e) {
        console.error(e);
    }
}

function editSlide(id, encodedTitle, encodedDescription) {
    document.getElementById('slideId').value = String(id);
    document.getElementById('slideTitle').value = decodeURIComponent(encodedTitle || '');
    document.getElementById('slideDescription').value = decodeURIComponent(encodedDescription || '');
    document.getElementById('slideImage').value = '';
    document.getElementById('saveSlideBtn').innerHTML = '<i class="fas fa-save"></i> Update Slide';
}

function resetSlideForm() {
    document.getElementById('slideForm').reset();
    document.getElementById('slideId').value = '';
    document.getElementById('saveSlideBtn').innerHTML = '<i class="fas fa-upload"></i> Save Slide';
}

async function saveSlide(e) {
    e.preventDefault();
    const id = document.getElementById('slideId').value;
    const fd = new FormData();
    fd.append('action', id ? 'edit' : 'add');
    if (id) fd.append('id', id);
    fd.append('title', document.getElementById('slideTitle').value);
    fd.append('description', document.getElementById('slideDescription').value);

    const file = document.getElementById('slideImage').files[0];
    if (file) fd.append('image', file);

    const res = await fetch('../php/slides.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await parseJson(res);

    if (data && data.success) {
        resetSlideForm();
        loadSlides();
        loadOverview();
        showFlash(id ? 'Slide updated.' : 'Slide added.');
    } else {
        showFlash((data && data.error) || 'Could not save slide.', 'error');
    }
}

async function deleteSlide(id) {
    if (!confirm('Delete this demo slide from the homepage?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    const res = await fetch('../php/slides.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await parseJson(res);
    if (data && data.success) {
        loadSlides();
        loadOverview();
        showFlash('Slide deleted.');
    } else {
        showFlash((data && data.error) || 'Could not delete slide.', 'error');
    }
}

async function logoutAdmin() {
    const fd = new FormData();
    fd.append('action', 'logout');
    try {
        const res = await fetch('../php/auth.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await parseJson(res);
        window.location.href = (data && data.redirect) || 'index.php';
    } catch (e) {
        window.location.href = 'index.php';
    }
}
// Toggle at pag-load ng Completed Quests na may Checkboxes, Delete, at Print feature
document.addEventListener('DOMContentLoaded', () => {
    const card = document.getElementById('questsCompletedTodayCard');
    const panel = document.getElementById('completedQuestsCard');
    const list = document.getElementById('completedQuestsList');

    if (!card || !panel || !list) return;

    card.style.cursor = 'pointer';

    card.addEventListener('click', async () => {
        if (!panel.hidden) {
            panel.hidden = true;
            return;
        }

        panel.hidden = false;
        list.innerHTML = '<p class="text-muted">Loading completed quests...</p>';

        try {
            const response = await fetch(
                '../php/game_admin_api.php?action=get_completed_today',
                { credentials: 'same-origin' }
            );

            const data = await parseJson(response);

            if (!data || !data.success) {
                throw new Error('Could not load completed quests.');
            }

            if (!data.quests || data.quests.length === 0) {
                list.innerHTML = `
                    <p class="text-muted">
                        No completed quests recorded today.
                    </p>
                `;
                return;
            }

            // Magdagdag ng Action Controls (Select All, Delete Selected, Print Selected) sa itaas ng listahan
            let htmlContent = `
                <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
                    <label style="cursor: pointer; color: #00ff88; font-size: 0.9rem;">
                        <input type="checkbox" id="selectAllQuests" style="margin-right: 5px;"> Select All
                    </label>
                    <button type="button" id="deleteSelectedBtn" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem; border-color: #ff5252; color: #ff5252;">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button type="button" id="printSelectedBtn" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                        <i class="fas fa-print"></i> Print Selected Profile & Info
                    </button>
                </div>
                <div id="questsPrintArea">
            `;

            htmlContent += data.quests.map((quest, index) => `
                <div class="quest-item-box" style="
                    padding: 14px;
                    margin-bottom: 10px;
                    border: 1px solid #07552d;
                    border-radius: 10px;
                    background: #07140d;
                    position: relative;
                ">
                    <div style="
                        display: flex;
                        justify-content: space-between;
                        gap: 12px;
                        flex-wrap: wrap;
                        margin-bottom: 8px;
                        align-items: center;
                    ">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" class="quest-checkbox" data-id="${quest.id || index}" style="width: 16px; height: 16px; cursor: pointer;">
                            <strong style="color: #00e676;">
                                ${escapeHtml(quest.language || 'General')}
                            </strong>
                        </div>

                        <span class="text-muted">
                            ${escapeHtml(quest.completed_at || '')}
                        </span>
                    </div>

                    <p style="margin-bottom: 8px;">
                        ${escapeHtml(quest.question_text || 'No question text')}
                    </p>

                    <div style="background: rgba(0,0,0,0.2); padding: 8px; border-radius: 6px; margin-top: 6px;">
                        <small class="text-muted" style="display: block; margin-bottom: 4px; color: #a3e6cd;">
                            <strong>Player Profile Info:</strong> ${escapeHtml(quest.email || 'Unknown')}
                        </small>
                    </div>

                    ${quest.code_text ? `
                        <pre style="
                            margin-top: 10px;
                            padding: 10px;
                            white-space: pre-wrap;
                            overflow-wrap: anywhere;
                            background: #030a06;
                            border-radius: 6px;
                            color: #b8f5ce;
                        ">${escapeHtml(quest.code_text)}</pre>
                    ` : ''}
                </div>
            `).join('');

            htmlContent += `</div>`;
            list.innerHTML = htmlContent;

            // Event listener para sa Select All checkbox
            const selectAllCheckbox = document.getElementById('selectAllQuests');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', (e) => {
                    const checkboxes = list.querySelectorAll('.quest-checkbox');
                    checkboxes.forEach(cb => cb.checked = e.target.checked);
                });
            }

            // Event listener para sa Delete Selected button
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', async () => {
                    const selectedCheckboxes = list.querySelectorAll('.quest-checkbox:checked');
                    if (selectedCheckboxes.length === 0) {
                        alert('Mangyaring pumili muna ng kahit isang quest na gustong i-delete.');
                        return;
                    }

                    if (!confirm(`Sigurado ka bang gusto mong burahin ang ${selectedCheckboxes.length} na napiling quest?`)) {
                        return;
                    }

                    // Kunin ang mga IDs o identifiers ng mga napili
                    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.getAttribute('data-id'));

                    try {
                        const deleteRes = await fetch('../php/game_admin_api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'delete_quests', ids: selectedIds }),
                            credentials: 'same-origin'
                        });

                        const deleteData = await parseJson(deleteRes);

                        if (deleteData && deleteData.success) {
                            showFlash('Napiling mga quest ay matagumpay na nabura.');
                            // I-refresh o i-trigger ulit ang pagbukas ng card para mag-update ang listahan
                            card.click();
                            card.click();
                        } else {
                            alert(deleteData?.error || 'Hindi matanggal ang mga quest. Subukan muli.');
                        }
                    } catch (err) {
                        console.error('Delete error:', err);
                        alert('May naganap na error sa koneksyon habang nagbubura.');
                    }
                });
            }

            // Event listener para sa Print Selected button
            // Event listener para sa Print Selected button
            const printBtn = document.getElementById('printSelectedBtn');
            if (printBtn) {
                printBtn.addEventListener('click', async () => {
                    const selectedCheckboxes = list.querySelectorAll('.quest-checkbox:checked');
                    if (selectedCheckboxes.length === 0) {
                        alert('Mangyaring pumili muna ng kahit isang quest na ipi-print.');
                        return;
                    }

                    // Tipunin ang mga email/user sang napili para makuha ang ila profile
                    let printWindow = window.open('', '_blank');
                    let printContent = `
                        <html>
                        <head>
                            <title>CodeMaze - Completed Quests & Player Profile Report</title>
                            <style>
                                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 25px; color: #111; background: #fff; }
                                h2 { border-bottom: 2px solid #00e676; padding-bottom: 8px; color: #0b0f19; }
                                h3 { margin-top: 20px; color: #0b0f19; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
                                .report-card { border: 1px solid #c8e6c9; padding: 18px; margin-bottom: 20px; border-radius: 8px; background: #f1f8f5; }
                                .profile-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 12px; font-size: 0.95rem; }
                                table { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 12px; }
                                th, td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; font-size: 0.85rem; }
                                th { background: #e8f5e9; }
                                pre { background: #0b0f19; color: #b8f5ce; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; }
                                .weak-box { background: #fff3e0; border-left: 4px solid #ff9800; padding: 8px; margin-top: 6px; font-size: 0.85rem; }
                            </style>
                        </head>
                        <body>
                            <h2>CodeMaze - Completed Quests & Full Player Profile Report</h2>
                    `;

                    // I-fetch ang profile sang tagsa ka napili nga player kon kinahanglan, ukon kuhaon ang data
                    for (const cb of selectedCheckboxes) {
                        const parentBox = cb.closest('.quest-item-box');
                        const playerEmail = cb.getAttribute('data-email'); // E-store nato ang email sa checkbox subong
                        
                        if (parentBox) {
                            printContent += `<div class="report-card">`;
                            printContent += parentBox.innerHTML;
                            printContent += `</div>`;
                        }
                    }

                    printContent += `</body></html>`;
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(() => {
                        printWindow.print();
                        printWindow.close();
                    }, 600);
                });
            }
            

        } catch (error) {
            console.error('Completed quests error:', error);
            list.innerHTML = `
                <p class="error-text">
                    Could not load completed quests.
                    Please check the API and database.
                </p>
            `;
        }
    });
});
// ── Load Completed Quests Today ───────────────────────────────────────────
async function loadCompletedQuestsToday() {
    const container = document.getElementById('completedQuestsList');
    const card = document.getElementById('completedQuestsCard');
    
    if (!container || !card) return;

    try {
        const res = await fetch('../php/game_admin_api.php?action=get_completed_today');
        const data = await res.json();

        if (data.success && data.quests && data.quests.length > 0) {
            card.hidden = false;
            container.innerHTML = data.quests.map(q => `
                <div style="background: #0f1422; border: 1px solid #2a354d; padding: 12px; border-radius: 6px; margin-bottom: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-weight: bold; color: #00ff88;">${escHtml(q.email || 'Unknown Player')}</span>
                        <span style="font-size: 0.85rem; color: #8fa9a0;">${escHtml(q.completed_at || 'N/A')}</span>
                    </div>
                    <div style="font-size: 0.9rem; color: #f4fff9; margin-bottom: 4px;">
                        <strong>[${escHtml(q.language)}]</strong> ${escHtml(q.question_text)}
                    </div>
                    ${q.code_text ? `<code style="display: block; background: #010604; padding: 6px; border-radius: 4px; font-size: 0.8rem; color: #65ffb3; overflow-x: auto;">${escHtml(q.code_text)}</code>` : ''}
                </div>
            `).join('');
        } else {
            card.hidden = false;
            container.innerHTML = `<p class="text-muted">No completed quests recorded yet.</p>`;
        }
    } catch (e) {
        console.error('Failed to load completed quests:', e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadCompletedQuestsToday();
});

// Tawagin ang function na ito pagka-load ng pahina
document.addEventListener('DOMContentLoaded', () => {
    loadCompletedQuestsToday();
});