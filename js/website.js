const wForm = document.getElementById('websiteLoginForm');
if (wForm) {
    wForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('wLoginBtn');
        const msg = document.getElementById('wLoginMsg');
        btn.textContent = 'Logging in…';
        btn.disabled = true;
        msg.textContent = '';

        const fd = new FormData();
        fd.append('action', 'login');
        fd.append('email', document.getElementById('wEmail').value.trim());
        fd.append('password', document.getElementById('wPassword').value);

        try {
            const r = await fetch('php/auth.php', { method: 'POST', body: fd });
            const data = await r.json();
            if (data.success) {
                msg.style.color = '#2ecc71';
                msg.textContent = 'Login successful! Redirecting…';
                setTimeout(() => window.location.href = 'admin/admin.php', 800);
            } else {
                msg.style.color = '#e74c3c';
                msg.textContent = data.error || 'Invalid email or password.';
                btn.disabled = false;
                btn.textContent = 'Log In';
            }
        } catch (err) {
            msg.textContent = 'Connection error. Is XAMPP running?';
            btn.disabled = false;
            btn.textContent = 'Log In';
        }
    });
}

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
});

// Blog slider navigation
(function () {
    const blogTimeline = document.querySelector('.blog-timeline');
    const prevBlogBtn = document.getElementById('prevBlogBtn');
    const nextBlogBtn = document.getElementById('nextBlogBtn');

    if (!blogTimeline) return;

    const scrollAmount = 380;

    if (prevBlogBtn) {
        prevBlogBtn.addEventListener('click', () => {
            blogTimeline.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });
    }

    if (nextBlogBtn) {
        nextBlogBtn.addEventListener('click', () => {
            blogTimeline.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });
    }

    document.addEventListener('keydown', (e) => {
        const blogSection = document.getElementById('blog');
        const sectionRect = blogSection.getBoundingClientRect();


        if (sectionRect.top < window.innerHeight && sectionRect.bottom > 0) {
            if (e.key === 'ArrowLeft') {
                blogTimeline.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            } else if (e.key === 'ArrowRight') {
                blogTimeline.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            }
        }
    });
})();

(function () {
    const blogPosts = document.querySelectorAll('.blog-post');
    const scrollIndicator = document.querySelector('.scroll-indicator');
    const blogTimeline = document.querySelector('.blog-timeline');

    if (!blogPosts.length) return;


    let lastScrollY = window.scrollY;

    function animateOnScroll() {
        const currentScrollY = window.scrollY;
        const scrollDirection = currentScrollY > lastScrollY ? 'down' : 'up';
        lastScrollY = currentScrollY;

        const blogSection = document.getElementById('blog');
        const sectionRect = blogSection.getBoundingClientRect();

        if (sectionRect.top < window.innerHeight && sectionRect.bottom > 0) {
            blogPosts.forEach((post, index) => {
                const rect = post.getBoundingClientRect();
                const viewportHeight = window.innerHeight;


                if (rect.top < viewportHeight && rect.bottom > 0) {

                    const distanceFromCenter = Math.abs(rect.top - viewportHeight / 2);
                    const maxDistance = viewportHeight / 2;
                    const progress = 1 - (distanceFromCenter / maxDistance);

                    if (progress > 0) {
                        post.style.opacity = Math.min(1, progress + 0.3);
                        post.style.transform = `translateY(${(1 - progress) * 30}px)`;
                    }
                }
            });
        }



    }

    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const delay = entry.target.getAttribute('data-delay') || 0;
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, parseInt(delay));
            }
        });
    }, observerOptions);

    blogPosts.forEach(post => {
        observer.observe(post);
    });

    window.addEventListener('scroll', animateOnScroll, { passive: true });


    animateOnScroll();
})();

(function () {
    const wrapper = document.getElementById('sliderWrapper');
    const slides = document.querySelectorAll('.slide');
    const prevBtn = document.getElementById('prevSlide');
    const nextBtn = document.getElementById('nextSlide');
    const dotsContainer = document.getElementById('sliderDots');

    if (!wrapper || !slides.length) return;

    let currentIndex = 0;
    const totalSlides = slides.length;

    function createDots() {
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('span');
            dot.classList.add('dot');
            dot.dataset.index = i;
            dot.addEventListener('click', function () {
                goToSlide(parseInt(this.dataset.index));
            });
            dotsContainer.appendChild(dot);
        }
        updateDots();
    }

    function updateDots() {
        const dots = document.querySelectorAll('.dot');
        dots.forEach((dot, idx) => {
            if (idx === currentIndex) dot.classList.add('active');
            else dot.classList.remove('active');
        });
    }

    function goToSlide(index) {
        if (index < 0) index = totalSlides - 1;
        if (index >= totalSlides) index = 0;
        currentIndex = index;
        wrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
        updateDots();
    }

    function nextSlide() { goToSlide(currentIndex + 1); }
    function prevSlide() { goToSlide(currentIndex - 1); }

    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') prevSlide();
        else if (e.key === 'ArrowRight') nextSlide();
    });

    createDots();
    goToSlide(0);

    let touchStartX = 0;
    wrapper.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    wrapper.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].screenX;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50) {
            if (diff > 0) nextSlide();
            else prevSlide();
        }
    });
})();