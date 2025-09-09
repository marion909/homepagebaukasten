    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>{{site_name}}</h3>
                    <p>Ihr vertrauensvoller Partner für professionelle Lösungen. Wir stehen für Qualität, Zuverlässigkeit und Innovation.</p>
                    <div class="company-info">
                        <p><i class="fas fa-map-marker-alt"></i> {{contact_address}}</p>
                        <p><i class="fas fa-phone"></i> {{contact_phone}}</p>
                        <p><i class="fas fa-envelope"></i> {{contact_email}}</p>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Services</h3>
                    <ul class="footer-links">
                        <li><a href="/beratung">Beratung</a></li>
                        <li><a href="/entwicklung">Entwicklung</a></li>
                        <li><a href="/support">Support</a></li>
                        <li><a href="/wartung">Wartung</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Unternehmen</h3>
                    <ul class="footer-links">
                        <li><a href="/ueber-uns">Über uns</a></li>
                        <li><a href="/team">Team</a></li>
                        <li><a href="/karriere">Karriere</a></li>
                        <li><a href="/news">News</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Newsletter</h3>
                    <p>Bleiben Sie auf dem Laufenden mit unseren neuesten Entwicklungen.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Ihre E-Mail-Adresse" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                    <div class="social-links">
                        <a href="{{facebook_url}}" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="{{twitter_url}}" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="{{linkedin_url}}" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        <a href="{{instagram_url}}" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <p>&copy; {{current_year}} {{site_name}}. Alle Rechte vorbehalten.</p>
                    <div class="footer-bottom-links">
                        <a href="/impressum">Impressum</a>
                        <a href="/datenschutz">Datenschutz</a>
                        <a href="/agb">AGB</a>
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
                });
            }
            
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
            
            // Newsletter form
            const newsletterForm = document.querySelector('.newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = this.querySelector('input[type="email"]').value;
                    alert('Vielen Dank für Ihre Anmeldung! Sie erhalten in Kürze eine Bestätigungsmail.');
                    this.reset();
                });
            }
        });
    </script>
</body>
</html>
