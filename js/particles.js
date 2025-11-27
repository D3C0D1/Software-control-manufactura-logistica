class Particle {
    constructor(x, y, ctx) {
        this.ctx = ctx;
        this.x = x;
        this.y = y;
        this.size = Math.random() * 4 + 2; // Partículas más grandes
        this.speedX = (Math.random() * 0.4 - 0.2); // Movimiento lento
        this.speedY = (Math.random() * 0.4 - 0.2);
        // Tonos azules
        this.color = `rgba(70, 130, 180, ${Math.random() * 0.6 + 0.2})`; 
        this.opacity = 1;
    }

    update() {
        this.x += this.speedX;
        this.y += this.speedY;

        // Rebotar en los bordes
        if (this.x > this.ctx.canvas.width || this.x < 0) {
            this.speedX *= -1;
        }
        if (this.y > this.ctx.canvas.height || this.y < 0) {
            this.speedY *= -1;
        }
    }

    draw() {
        this.ctx.fillStyle = this.color;
        this.ctx.beginPath();
        this.ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        this.ctx.fill();
    }
}

class ParticleEffect {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container with id ${containerId} not found.`);
            return;
        }
        this.canvas = document.createElement('canvas');
        this.ctx = this.canvas.getContext('2d');
        this.particles = [];
        this.container.appendChild(this.canvas);
        this.init();
    }

    init() {
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());
        this.generateParticles();
        this.animate();
    }

    resizeCanvas() {
        this.canvas.width = this.container.offsetWidth;
        this.canvas.height = this.container.offsetHeight;
    }

    generateParticles() {
        const numberOfParticles = Math.floor(this.canvas.width / 30); // Más partículas
        this.particles = [];
        for (let i = 0; i < numberOfParticles; i++) {
            const x = Math.random() * this.canvas.width;
            const y = Math.random() * this.canvas.height;
            this.particles.push(new Particle(x, y, this.ctx));
        }
    }

    connectParticles() {
        for (let a = 0; a < this.particles.length; a++) {
            for (let b = a; b < this.particles.length; b++) {
                const dx = this.particles[a].x - this.particles[b].x;
                const dy = this.particles[a].y - this.particles[b].y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 150) { // Mayor distancia para la conexión
                    this.ctx.strokeStyle = `rgba(70, 130, 180, ${1 - distance / 150})`;
                    this.ctx.lineWidth = 0.4;
                    this.ctx.beginPath();
                    this.ctx.moveTo(this.particles[a].x, this.particles[a].y);
                    this.ctx.lineTo(this.particles[b].x, this.particles[b].y);
                    this.ctx.stroke();
                }
            }
        }
    }

    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.particles.forEach(particle => {
            particle.update();
            particle.draw();
        });
        this.connectParticles();
        requestAnimationFrame(() => this.animate());
    }
}

// Inicialización para el login y el sidebar
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('particles-js')) {
        new ParticleEffect('particles-js');
    }
    if (document.getElementById('sidebar-particles')) {
        new ParticleEffect('sidebar-particles');
    }
});