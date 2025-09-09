    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>{{site_name}}</h3>
                    <p>Kreativität trifft auf Innovation. Wir erschaffen einzigartige digitale Erlebnisse, die inspirieren und begeistern.</p>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>{{contact_address}}</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>{{contact_phone}}</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>{{contact_email}}</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Portfolio</h3>
                    <ul class="footer-links">
                        <li><a href="/portfolio">Alle Projekte</a></li>
                        <li><a href="/webdesign">Web Design</a></li>
                        <li><a href="/branding">Branding</a></li>
                        <li><a href="/fotografie">Fotografie</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Services</h3>
                    <ul class="footer-links">
                        <li><a href="/design">Design</a></li>
                        <li><a href="/entwicklung">Entwicklung</a></li>
                        <li><a href="/beratung">Beratung</a></li>
                        <li><a href="/workshops">Workshops</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Let's Connect</h3>
                    <p>Bereit für Ihr nächstes kreatives Projekt? Lassen Sie uns zusammenarbeiten!</p>
                    <div class="cta-buttons">
                        <a href="/kontakt" class="btn btn-primary">Projekt starten</a>
                        <a href="/portfolio" class="btn btn-outline">Portfolio ansehen</a>
                    </div>
                    <div class="social-links">
                        <a href="{{instagram_url}}" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="{{behance_url}}" aria-label="Behance"><i class="fab fa-behance"></i></a>
                        <a href="{{dribbble_url}}" aria-label="Dribbble"><i class="fab fa-dribbble"></i></a>
                        <a href="{{linkedin_url}}" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <p>&copy; {{current_year}} {{site_name}}. Made with <i class="fas fa-heart"></i> and lots of coffee.</p>
                    <div class="footer-bottom-links">
                        <a href="/impressum">Impressum</a>
                        <a href="/datenschutz">Datenschutz</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Mobile Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileToggle && navMenu) {
                mobileToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    mobileToggle.classList.toggle('active');
                    document.body.classList.toggle('menu-open');
                });
            }
            
            // Parallax effect for background shapes
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const shapes = document.querySelectorAll('.shape');
                
                shapes.forEach((shape, index) => {
                    const speed = 0.5 + (index * 0.1);
                    const yPos = -(scrolled * speed);
                    shape.style.transform = `translateY(${yPos}px)`;
                });
            });
            
            // Smooth scrolling for anchor links
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
            
            // Add scroll effect to header
            window.addEventListener('scroll', function() {
                const header = document.querySelector('.header');
                if (window.scrollY > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
            
            // Intersection Observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, observerOptions);
            
            // Observe elements for animation
            document.querySelectorAll('.content, .footer-section').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
