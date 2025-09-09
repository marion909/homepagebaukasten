    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>{{site_name}}</h3>
                    <p>Klarheit durch Einfachheit. Wir glauben an die Kraft des Wesentlichen und schaffen digitale Erlebnisse, die durch ihre Klarheit überzeugen.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Navigation</h3>
                    <ul class="footer-links">
                        {{navigation}}
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Kontakt</h3>
                    <div class="contact-info">
                        <p>{{contact_email}}</p>
                        <p>{{contact_phone}}</p>
                        <p>{{contact_address}}</p>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Social</h3>
                    <div class="social-links">
                        <a href="{{twitter_url}}" aria-label="Twitter">Twitter</a>
                        <a href="{{linkedin_url}}" aria-label="LinkedIn">LinkedIn</a>
                        <a href="{{github_url}}" aria-label="GitHub">GitHub</a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; {{current_year}} {{site_name}}</p>
                <div class="footer-links-bottom">
                    <a href="/impressum">Impressum</a>
                    <a href="/datenschutz">Datenschutz</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (menuToggle && navMenu) {
                menuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    menuToggle.classList.toggle('active');
                });
            }
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.navigation')) {
                    navMenu?.classList.remove('active');
                    menuToggle?.classList.remove('active');
                }
            });
            
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
                
                lastScroll = currentScroll;
            });
        });
    </script>
</body>
</html>
