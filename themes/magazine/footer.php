    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-widget">
            <h3>Newsletter</h3>
            <p>Bleiben Sie auf dem Laufenden mit unserem kostenlosen Newsletter.</p>
            <form class="newsletter-form">
                <input type="email" placeholder="Ihre E-Mail-Adresse" required>
                <button type="submit">Abonnieren</button>
            </form>
        </div>
        
        <div class="sidebar-widget">
            <h3>Beliebte Artikel</h3>
            <div class="popular-posts">
                <article class="popular-post">
                    <div class="post-thumbnail">
                        <img src="https://via.placeholder.com/80x60" alt="Artikel">
                    </div>
                    <div class="post-info">
                        <h4><a href="#">Spannende Entwicklungen in der Technologie</a></h4>
                        <span class="post-date">15. März 2024</span>
                    </div>
                </article>
                
                <article class="popular-post">
                    <div class="post-thumbnail">
                        <img src="https://via.placeholder.com/80x60" alt="Artikel">
                    </div>
                    <div class="post-info">
                        <h4><a href="#">Nachhaltigkeit im digitalen Zeitalter</a></h4>
                        <span class="post-date">12. März 2024</span>
                    </div>
                </article>
                
                <article class="popular-post">
                    <div class="post-thumbnail">
                        <img src="https://via.placeholder.com/80x60" alt="Artikel">
                    </div>
                    <div class="post-info">
                        <h4><a href="#">Die Zukunft der Arbeit</a></h4>
                        <span class="post-date">10. März 2024</span>
                    </div>
                </article>
            </div>
        </div>
        
        <div class="sidebar-widget">
            <h3>Kategorien</h3>
            <ul class="category-list">
                <li><a href="/kategorie/technologie">Technologie <span>(12)</span></a></li>
                <li><a href="/kategorie/wirtschaft">Wirtschaft <span>(8)</span></a></li>
                <li><a href="/kategorie/kultur">Kultur <span>(15)</span></a></li>
                <li><a href="/kategorie/sport">Sport <span>(6)</span></a></li>
                <li><a href="/kategorie/politik">Politik <span>(9)</span></a></li>
            </ul>
        </div>
        
        <div class="sidebar-widget">
            <h3>Werbung</h3>
            <div class="ad-widget">
                <div class="ad-placeholder">
                    <span>300 x 250</span>
                </div>
            </div>
        </div>
    </aside>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>{{site_name}}</h3>
                    <p>Ihr vertrauensvolles Online-Magazin für aktuelle Nachrichten, spannende Reportagen und tiefgreifende Analysen aus aller Welt.</p>
                    <div class="footer-contact">
                        <p><i class="fas fa-map-marker-alt"></i> {{contact_address}}</p>
                        <p><i class="fas fa-phone"></i> {{contact_phone}}</p>
                        <p><i class="fas fa-envelope"></i> {{contact_email}}</p>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Rubriken</h3>
                    <ul class="footer-links">
                        <li><a href="/politik">Politik</a></li>
                        <li><a href="/wirtschaft">Wirtschaft</a></li>
                        <li><a href="/sport">Sport</a></li>
                        <li><a href="/kultur">Kultur</a></li>
                        <li><a href="/technologie">Technologie</a></li>
                        <li><a href="/reise">Reise</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Service</h3>
                    <ul class="footer-links">
                        <li><a href="/newsletter">Newsletter</a></li>
                        <li><a href="/archiv">Archiv</a></li>
                        <li><a href="/wetter">Wetter</a></li>
                        <li><a href="/horoskop">Horoskop</a></li>
                        <li><a href="/rezepte">Rezepte</a></li>
                        <li><a href="/sudoku">Sudoku</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Folgen Sie uns</h3>
                    <div class="social-links">
                        <a href="{{facebook_url}}" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="{{twitter_url}}" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                            <span>Twitter</span>
                        </a>
                        <a href="{{instagram_url}}" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                            <span>Instagram</span>
                        </a>
                        <a href="{{youtube_url}}" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                            <span>YouTube</span>
                        </a>
                    </div>
                    
                    <div class="newsletter-signup">
                        <h4>Newsletter abonnieren</h4>
                        <form class="footer-newsletter">
                            <input type="email" placeholder="E-Mail-Adresse">
                            <button type="submit"><i class="fas fa-paper-plane"></i></button>
                        </form>
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
                        <a href="/mediadaten">Mediadaten</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set current date
            const dateElement = document.getElementById('current-date');
            if (dateElement) {
                const today = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                };
                dateElement.textContent = today.toLocaleDateString('de-DE', options);
            }
            
            // Mobile menu toggle
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileToggle && navMenu) {
                mobileToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    mobileToggle.classList.toggle('active');
                });
            }
            
            // Search toggle
            const searchToggle = document.querySelector('.search-toggle');
            const searchBar = document.querySelector('.search-bar');
            
            if (searchToggle && searchBar) {
                searchToggle.addEventListener('click', function() {
                    searchBar.classList.toggle('active');
                    if (searchBar.classList.contains('active')) {
                        searchBar.querySelector('input').focus();
                    }
                });
            }
            
            // Newsletter forms
            const newsletterForms = document.querySelectorAll('.newsletter-form, .footer-newsletter');
            newsletterForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = this.querySelector('input[type="email"]').value;
                    alert('Vielen Dank für Ihre Anmeldung! Sie erhalten in Kürze eine Bestätigungsmail.');
                    this.reset();
                });
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
            
            // Sticky navigation
            const navigation = document.querySelector('.main-navigation');
            const headerMain = document.querySelector('.header-main');
            let headerMainHeight = headerMain ? headerMain.offsetHeight : 0;
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > headerMainHeight) {
                    navigation.classList.add('sticky');
                } else {
                    navigation.classList.remove('sticky');
                }
            });
        });
    </script>
</body>
</html>
