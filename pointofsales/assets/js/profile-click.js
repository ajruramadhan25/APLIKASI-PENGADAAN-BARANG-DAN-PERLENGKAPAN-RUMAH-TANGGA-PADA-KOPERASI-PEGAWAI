// Profile Click Handler - Universal script for all pages
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler to user-info elements
    const userInfoElements = document.querySelectorAll('.user-info');
    userInfoElements.forEach(element => {
        element.style.cursor = 'pointer';
        element.addEventListener('click', function() {
            window.location.href = 'profile.php';
        });
        
        // Add hover effect
        element.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
        });
    });
});
