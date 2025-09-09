    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>{{site_name}}</h3>
                    <p>Die ultimative Destination für Gaming-Enthusiasten und Tech-Nerds. Entdecke die neuesten Trends, Reviews und Insider-Tipps aus der Welt der Technologie.</p>
                    <div class="footer-stats">
                        <div class="stat">
                            <span class="stat-number">50K+</span>
                            <span class="stat-label">Community</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">1.2M+</span>
                            <span class="stat-label">Views/Monat</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Reviews</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Gaming</h3>
                    <ul class="footer-links">
                        <li><a href="/reviews">Game Reviews</a></li>
                        <li><a href="/news">Gaming News</a></li>
                        <li><a href="/guides">Guides & Tipps</a></li>
                        <li><a href="/esports">eSports</a></li>
                        <li><a href="/streaming">Streaming</a></li>
                        <li><a href="/mods">Mods & Tools</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Hardware</h3>
                    <ul class="footer-links">
                        <li><a href="/gpu">Grafikkarten</a></li>
                        <li><a href="/cpu">Prozessoren</a></li>
                        <li><a href="/builds">PC Builds</a></li>
                        <li><a href="/peripherals">Peripherie</a></li>
                        <li><a href="/deals">Deals & Angebote</a></li>
                        <li><a href="/benchmarks">Benchmarks</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Connect</h3>
                    <div class="social-platforms">
                        <a href="{{discord_url}}" class="social-platform discord">
                            <i class="fab fa-discord"></i>
                            <div class="platform-info">
                                <span class="platform-name">Discord</span>
                                <span class="platform-users">25K Members</span>
                            </div>
                        </a>
                        <a href="{{twitch_url}}" class="social-platform twitch">
                            <i class="fab fa-twitch"></i>
                            <div class="platform-info">
                                <span class="platform-name">Twitch</span>
                                <span class="platform-users">Live Now</span>
                            </div>
                        </a>
                        <a href="{{youtube_url}}" class="social-platform youtube">
                            <i class="fab fa-youtube"></i>
                            <div class="platform-info">
                                <span class="platform-name">YouTube</span>
                                <span class="platform-users">150K Subs</span>
                            </div>
                        </a>
                        <a href="{{twitter_url}}" class="social-platform twitter">
                            <i class="fab fa-twitter"></i>
                            <div class="platform-info">
                                <span class="platform-name">Twitter</span>
                                <span class="platform-users">@{{site_name}}</span>
                            </div>
                        </a>
                    </div>
                    
                    <div class="newsletter-signup">
                        <h4>Gaming Newsletter</h4>
                        <p>Verpasse keine News, Reviews oder Deals!</p>
                        <form class="newsletter-form">
                            <input type="email" placeholder="deine@email.com" required>
                            <button type="submit">
                                <i class="fas fa-rocket"></i>
                                <span>Join</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <div class="footer-left">
                        <p>&copy; {{current_year}} {{site_name}}. Made with <i class="fas fa-heart"></i> for Gamers.</p>
                    </div>
                    <div class="footer-center">
                        <div class="footer-links-inline">
                            <a href="/impressum">Impressum</a>
                            <a href="/datenschutz">Datenschutz</a>
                            <a href="/agb">AGB</a>
                            <a href="/cookies">Cookies</a>
                        </div>
                    </div>
                    <div class="footer-right">
                        <div class="powered-by">
                            <span>Powered by</span>
                            <strong>Baukasten CMS</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileToggle && navMenu) {
                mobileToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    mobileToggle.classList.toggle('active');
                    document.body.classList.toggle('menu-open');
                });
            }
            
            // Search overlay
            const searchToggle = document.querySelector('.search-toggle');
            const searchOverlay = document.querySelector('.search-overlay');
            const searchClose = document.querySelector('.search-close');
            const searchInput = document.querySelector('.search-overlay input');
            
            if (searchToggle && searchOverlay) {
                searchToggle.addEventListener('click', function() {
                    searchOverlay.classList.add('active');
                    document.body.classList.add('search-open');
                    if (searchInput) searchInput.focus();
                });
            }
            
            if (searchClose && searchOverlay) {
                searchClose.addEventListener('click', function() {
                    searchOverlay.classList.remove('active');
                    document.body.classList.remove('search-open');
                });
            }
            
            // Close search on escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && searchOverlay.classList.contains('active')) {
                    searchOverlay.classList.remove('active');
                    document.body.classList.remove('search-open');
                }
            });
            
            // Theme toggle (for demo purposes)
            const themeToggle = document.querySelector('.theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('light-theme');
                    const icon = this.querySelector('i');
                    if (document.body.classList.contains('light-theme')) {
                        icon.className = 'fas fa-sun';
                    } else {
                        icon.className = 'fas fa-moon';
                    }
                });
            }
            
            // Newsletter form
            const newsletterForm = document.querySelector('.newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = this.querySelector('input[type="email"]').value;
                    const button = this.querySelector('button');
                    const originalText = button.innerHTML;
                    
                    button.innerHTML = '<i class="fas fa-check"></i><span>Joined!</span>';
                    button.style.background = 'var(--success-color)';
                    
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.style.background = '';
                        this.reset();
                    }, 2000);
                });
            }
            
            // Smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Header scroll effect
            let lastScroll = 0;
            const header = document.querySelector('.header');
            
            window.addEventListener('scroll', function() {
                const currentScroll = window.pageYOffset;
                
                if (currentScroll > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
                
                // Hide/show header on scroll
                if (currentScroll > lastScroll && currentScroll > 200) {
                    header.classList.add('hidden');
                } else {
                    header.classList.remove('hidden');
                }
                
                lastScroll = currentScroll;
            });
            
            // Parallax effect for particles
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const particles = document.querySelectorAll('.particle');
                
                particles.forEach((particle, index) => {
                    const speed = 0.1 + (index * 0.05);
                    const yPos = -(scrolled * speed);
                    particle.style.transform = `translateY(${yPos}px)`;
                });
            });
            
            // Suggestion tags interaction
            const suggestionTags = document.querySelectorAll('.suggestion-tag');
            suggestionTags.forEach(tag => {
                tag.addEventListener('click', function() {
                    if (searchInput) {
                        searchInput.value = this.textContent;
                        searchInput.focus();
                    }
                });
            });
        });
    </script>
</body>
</html>
