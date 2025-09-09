    <footer class="modern-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>{{site_title}}</h3>
                    <p>Erstellt mit dem Baukasten CMS - Ein modernes und flexibles Content Management System.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Schnelllinks</h4>
                    <ul class="footer-links">
                        {{navigation}}
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Kontakt</h4>
                    <div class="contact-info">
                        <p><i class="fas fa-envelope"></i> info@beispiel.de</p>
                        <p><i class="fas fa-phone"></i> +49 123 456 789</p>
                        <p><i class="fas fa-map-marker-alt"></i> Musterstraße 123, 12345 Musterstadt</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; 2025 {{site_title}}. Alle Rechte vorbehalten.</p>
                    <div class="footer-meta">
                        <a href="/impressum">Impressum</a>
                        <a href="/datenschutz">Datenschutz</a>
                        <a href="/rss.xml.php" title="RSS Feed"><i class="fas fa-rss"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileMenuToggle && navMenu) {
                mobileMenuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    mobileMenuToggle.classList.toggle('active');
                });
            }
            
            // Smooth scroll for anchor links
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
            let lastScrollTop = 0;
            const header = document.querySelector('.modern-header');
            
            window.addEventListener('scroll', function() {
                let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
                
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            });
        });
    </script>
</body>
</html>
