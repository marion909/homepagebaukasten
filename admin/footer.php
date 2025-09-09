    </div> <!-- End container -->
    
    <footer style="background: #343a40; color: white; padding: 2rem 0; margin-top: 3rem; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
            <p style="margin: 0;">
                <strong>Baukasten CMS</strong> - Einfaches Content Management System
            </p>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #adb5bd;">
                Entwickelt mit ❤️ | Version 1.0 | 
                <a href="https://github.com/marion909/homepagebaukasten" target="_blank" style="color: #007cba;">GitHub</a>
            </p>
        </div>
    </footer>
    
    <?= $additionalJS ?? '' ?>
    
    <script>
        // Simple notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                max-width: 300px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                animation: slideIn 0.3s ease-out;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Add slide animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (!alert.hasAttribute('data-permanent')) {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.3s';
                        setTimeout(() => alert.remove(), 300);
                    }, 5000);
                }
            });
        });
        
        // Confirm delete actions
        function confirmDelete(message = 'Möchten Sie diesen Eintrag wirklich löschen?') {
            return confirm(message + '\n\nDiese Aktion kann nicht rückgängig gemacht werden.');
        }
        
        // Simple form validation
        function validateForm(formElement) {
            const requiredFields = formElement.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ced4da';
                }
            });
            
            if (!isValid) {
                showNotification('Bitte füllen Sie alle Pflichtfelder aus.', 'danger');
            }
            
            return isValid;
        }
        
        // Auto-save functionality for textareas
        function enableAutoSave(textareaId, saveKey) {
            const textarea = document.getElementById(textareaId);
            if (!textarea) return;
            
            // Load saved content
            const saved = localStorage.getItem(saveKey);
            if (saved && !textarea.value) {
                textarea.value = saved;
                showNotification('Autosave-Inhalt wiederhergestellt', 'info');
            }
            
            // Save on input
            let saveTimeout;
            textarea.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    localStorage.setItem(saveKey, textarea.value);
                }, 1000);
            });
            
            // Clear on form submit
            const form = textarea.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    localStorage.removeItem(saveKey);
                });
            }
        }
        
        // Table sorting
        function makeSortable(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.style.userSelect = 'none';
                header.addEventListener('click', () => sortTable(table, header.dataset.sort));
            });
        }
        
        function sortTable(table, column) {
            // Simple table sorting implementation
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aVal = a.children[column].textContent.trim();
                const bVal = b.children[column].textContent.trim();
                
                if (!isNaN(aVal) && !isNaN(bVal)) {
                    return Number(aVal) - Number(bVal);
                }
                
                return aVal.localeCompare(bVal);
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Mobile menu toggle (if needed)
        function toggleMobileMenu() {
            const nav = document.querySelector('.nav ul');
            if (nav.style.display === 'none' || !nav.style.display) {
                nav.style.display = 'flex';
                nav.style.flexDirection = 'column';
            } else {
                nav.style.display = '';
            }
        }
        
        // Check for unsaved changes
        let hasUnsavedChanges = false;
        
        function trackChanges() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, textarea, select');
                inputs.forEach(input => {
                    input.addEventListener('change', () => {
                        hasUnsavedChanges = true;
                    });
                });
                
                form.addEventListener('submit', () => {
                    hasUnsavedChanges = false;
                });
            });
        }
        
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
                return 'Sie haben ungespeicherte Änderungen. Möchten Sie die Seite wirklich verlassen?';
            }
        });
        
        // Initialize common functionality
        document.addEventListener('DOMContentLoaded', function() {
            trackChanges();
            
            // Add loading states to buttons
            const buttons = document.querySelectorAll('button[type="submit"], .btn-primary');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.form && !this.form.checkValidity()) return;
                    
                    this.disabled = true;
                    this.style.opacity = '0.6';
                    this.textContent = 'Lädt...';
                    
                    setTimeout(() => {
                        this.disabled = false;
                        this.style.opacity = '1';
                    }, 5000);
                });
            });
        });
    </script>
</body>
</html>
