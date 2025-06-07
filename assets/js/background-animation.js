/**
 * Background Particles Animation
 * Creates floating particles in the login page background
 */
document.addEventListener('DOMContentLoaded', function() {
    // Only run on login page
    const loginContainer = document.querySelector('.login-container');
    if (!loginContainer) return;
    
    // Create canvas for particles
    const canvas = document.createElement('canvas');
    canvas.className = 'particles-canvas';
    loginContainer.appendChild(canvas);
    
    // Style canvas
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.zIndex = '-1';
    canvas.style.pointerEvents = 'none';
    
    // Get canvas context
    const ctx = canvas.getContext('2d');
    
    // Set canvas dimensions
    function setCanvasSize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    // Initialize on load and resize
    setCanvasSize();
    window.addEventListener('resize', setCanvasSize);
    
    // Particle class
    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 3 + 1; // Size between 1-4
            this.speedX = Math.random() * 1 - 0.5; // Speed between -0.5 and 0.5
            this.speedY = Math.random() * 1 - 0.5;
            this.opacity = Math.random() * 0.5 + 0.1; // Opacity between 0.1-0.6
        }
        
        // Update particle position
        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            
            // Bounce off edges
            if (this.x > canvas.width || this.x < 0) {
                this.speedX = -this.speedX;
            }
            
            if (this.y > canvas.height || this.y < 0) {
                this.speedY = -this.speedY;
            }
        }
        
        // Draw particle
        draw() {
            ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }
    
    // Create particle array
    const particles = [];
    const particleCount = Math.floor((canvas.width * canvas.height) / 15000); // Adjust density based on screen size
    
    for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
    }
    
    // Animation loop
    function animate() {
        // Clear canvas with semi-transparent background to create trail effect
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Update and draw particles
        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
            
            // Connect particles with lines if they are close enough
            connectParticles(particles[i], particles);
        }
        
        requestAnimationFrame(animate);
    }
    
    // Connect particles with lines when they're close
    function connectParticles(particle, particles) {
        for (let i = 0; i < particles.length; i++) {
            const dx = particle.x - particles[i].x;
            const dy = particle.y - particles[i].y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < 100) { // Connect if within 100px
                const opacity = 1 - distance / 100; // Fade based on distance
                ctx.strokeStyle = `rgba(255, 255, 255, ${opacity * 0.2})`; // Very subtle lines
                ctx.lineWidth = 0.5;
                ctx.beginPath();
                ctx.moveTo(particle.x, particle.y);
                ctx.lineTo(particles[i].x, particles[i].y);
                ctx.stroke();
            }
        }
    }
    
    // Start animation
    animate();
}); 