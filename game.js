const Game = {
    canvas: null,
    ctx: null,
    currentScreen: 'main-menu',
    coins: 0,
    highScore: 0,
    currentLevel: 1,
    unlockedLevels: 1,
    selectedChar: 'default',
    selectedSkin: 'default',
    unlockedChars: ['default'],
    unlockedSkins: ['default'],
    settings: { music: true, sfx: true, vibrate: true },
    
    // بيانات اللعب الديناميكية
    player: { x: 50, y: 0, width: 40, height: 60, vy: 0, speed: 5, grounded: false },
    obstacles: [],
    collectibles: [],
    powerupsActive: {},
    gameInterval: null,
    levelProgress: 0,
    
    init() {
        this.canvas = document.getElementById('gameCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());
        
        this.loadStorage();
        this.renderMenuStats();
        this.buildLevelMap();
        this.buildShop();
    },

    resizeCanvas() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    },

    showScreen(screenId) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(screenId).classList.add('active');
        this.currentScreen = screenId;
        if (screenId === 'map-screen') this.buildLevelMap();
    },

    loadStorage() {
        this.coins = parseInt(localStorage.getItem('kok_coins') || '0');
        this.highScore = parseInt(localStorage.getItem('kok_highscore') || '0');
        this.unlockedLevels = parseInt(localStorage.getItem('kok_unlockedLevels') || '1');
        this.unlockedChars = JSON.parse(localStorage.getItem('kok_chars') || '["default"]');
        this.unlockedSkins = JSON.parse(localStorage.getItem('kok_skins') || '["default"]');
        this.selectedChar = localStorage.getItem('kok_selChar') || 'default';
        this.selectedSkin = localStorage.getItem('kok_selSkin') || 'default';
    },

    saveStorage() {
        localStorage.setItem('kok_coins', this.coins);
        localStorage.setItem('kok_highscore', this.highScore);
        localStorage.setItem('kok_unlockedLevels', this.unlockedLevels);
        localStorage.setItem('kok_chars', JSON.stringify(this.unlockedChars));
        localStorage.setItem('kok_skins', JSON.stringify(this.unlockedSkins));
        localStorage.setItem('kok_selChar', this.selectedChar);
        localStorage.setItem('kok_selSkin', this.selectedSkin);
    },

    renderMenuStats() {
        document.getElementById('menu-coins').innerText = this.coins;
        document.getElementById('menu-high-score').innerText = this.highScore;
        document.getElementById('shop-coins').innerText = this.coins;
    },

    buildLevelMap() {
        const grid = document.getElementById('levels-grid');
        grid.innerHTML = '';
        for (let i = 1; i <= 50; i++) {
            const btn = document.createElement('button');
            btn.className = `level-btn ${i > this.unlockedLevels ? 'locked' : ''}`;
            btn.innerText = i;
            if (i <= this.unlockedLevels) {
                btn.onclick = () => this.startLevel(i);
            }
            grid.appendChild(btn);
        }
    },

    buildShop() {
        const grid = document.getElementById('shop-grid');
        grid.innerHTML = `
            <div class="card">
                <h3>سكن مميز</h3>
                <p>السعر: 100 عملة</p>
                <button class="btn primary" onclick="Game.buySkin('gold', 100)">شراء</button>
            </div>
        `;
    },

    buySkin(skinId, price) {
        if (this.coins >= price && !this.unlockedSkins.includes(skinId)) {
            this.coins -= price;
            this.unlockedSkins.push(skinId);
            this.saveStorage();
            this.renderMenuStats();
            alert('تم شراء السكن بنجاح!');
        } else {
            alert('رصيدك غير كافٍ أو تمتلكه مسبقاً!');
        }
    },

    startLevel(level) {
        this.currentLevel = level;
        this.showScreen('game-screen');
        document.getElementById('hud-level').innerText = level;
        
        // إعادة تعيين بيانات الكائن والبيئة حسب المراحل الـ 50
        this.player.x = 50;
        this.player.y = this.canvas.height - 150;
        this.player.vy = 0;
        this.obstacles = [];
        this.collectibles = [];
        this.levelProgress = 0;
        this.powerupsActive = {};

        // توليد عقبات أولية تناسب البيئة المتدرجة
        for (let i = 0; i < 5 + level; i++) {
            this.obstacles.push({
                x: 400 + i * 250,
                y: this.canvas.height - 130,
                width: 30,
                height: 40,
                speed: 2 + (level * 0.1)
            });
            this.collectibles.push({
                x: 350 + i * 250,
                y: this.canvas.height - 200,
                radius: 12,
                collected: false
            });
        }

        if (this.gameInterval) cancelAnimationFrame(this.gameInterval);
        this.loop();
    },

    jump() {
        if (this.player.grounded) {
            this.player.vy = -14;
            this.player.grounded = false;
            if (this.settings.vibrate && navigator.vibrate) navigator.vibrate(40);
        }
    },

    moveLeft() { this.player.x = Math.max(0, this.player.x - 20); },
    moveRight() { this.player.x = Math.min(this.canvas.width - this.player.width, this.player.x + 20); },

    loop() {
        this.update();
        this.draw();
        this.gameInterval = requestAnimationFrame(() => this.loop());
    },

    update تحديث() {
        // الجاذبية والحركة
        this.player.vy += 0.6;
        this.player.y += this.player.vy;

        const groundY = this.canvas.height - 90;
        if (this.player.y > groundY - this.player.height) {
            this.player.y = groundY - this.player.height;
            this.player.vy = 0;
            this.player.grounded = true;
        }

        // تحريك العقبات بحسب صعوبة البيئة
        this.obstacles.forEach(obs => {
            obs.x -= obs.speed;
            // فحص الاصطدام الدقيق
            if (
                this.player.x < obs.x + obs.width &&
                this.player.x + this.player.width > obs.x &&
                this.player.y < obs.y + obs.height &&
                this.player.y + this.player.height > obs.y
            ) {
                this.gameOver("اصطدمت بعقبة كوميدية! خوووود ضربة");
            }
        });

        // فحص تجميع العملات
        this.collectibles.forEach(col => {
            if (!col.collected) {
                const dist = Math.hypot((this.player.x + this.player.width/2) - col.x, (this.player.y + this.player.height/2) - col.y);
                if (dist < 30) {
                    col.collected = true;
                    this.coins += 1;
                    document.getElementById('hud-coins').innerText = this.coins;
                    this.saveStorage();
                }
            }
        });

        this.levelProgress += 0.5;
        if (this.levelProgress >= 100) {
            this.levelComplete();
        }
    },

    draw() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // رسم الخلفية الديناميكية حسب البيئة
        const envs = ['الصحراء', 'البحر', 'الحمم', 'الثلج', 'الليل', 'الغابة'];
        const currentEnv = envs[Math.floor((this.currentLevel - 1) / 10) % envs.length];
        
        this.ctx.fillStyle = currentEnv === 'الحمم' ? '#c0392b' : (currentEnv === 'الثلج' ? '#ecf0f1' : '#2c3e50');
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

        // رسم الأرضية
        this.ctx.fillStyle = '#111';
        this.ctx.fillRect(0, this.canvas.height - 90, this.canvas.width, 90);

        // رسم الشخصية (مع الحفاظ على الطابع الكوميدي)
        this.ctx.fillStyle = '#f1c40f';
        this.ctx.fillRect(this.player.x, this.player.y, this.player.width, this.player.height);

        // رسم العقبات
        this.ctx.fillStyle = '#e74c3c';
        this.obstacles.forEach(obs => {
            this.ctx.fillRect(obs.x, obs.y, obs.width, obs.height);
        });

        // رسم العملات
        this.ctx.fillStyle = '#f39c12';
        this.collectibles.forEach(col => {
            if (!col.collected) {
                this.ctx.beginPath();
                this.ctx.arc(col.x, col.y, col.radius, 0, Math.PI * 2);
                this.ctx.fill();
            }
        });
    },

    levelComplete() {
        cancelAnimationFrame(this.gameInterval);
        if (this.currentLevel >= this.unlockedLevels && this.unlockedLevels < 50) {
            this.unlockedLevels++;
        }
        this.saveStorage();
        document.getElementById('complete-score').innerText = this.currentLevel * 150;
        document.getElementById('complete-coins').innerText = this.coins;
        this.showScreen('complete-screen');
    },

    gameOver(reason) {
        cancelAnimationFrame(this.gameInterval);
        document.getElementById('gameover-reason').innerText = reason;
        document.getElementById('gameover-score').innerText = this.currentLevel * 50;
        this.showScreen('gameover-screen');
    },

    restartLevel() {
        this.startLevel(this.currentLevel);
    },

    nextLevel() {
        if (this.currentLevel < 50) {
            this.startLevel(this.currentLevel + 1);
        } else {
            this.showScreen('map-screen');
        }
    },

    toggleMusic() { this.settings.music = !this.settings.music; },
    toggleSfx() { this.settings.sfx = !this.settings.sfx; },
    toggleVibrate() { this.settings.vibrate = !this.settings.vibrate; },
    resetProgress() {
        if (confirm('هل أنت متأكد من إعادة ضبط التقدم بالكامل؟')) {
            localStorage.clear();
            location.reload();
        }
    },
    pauseGame() {
        cancelAnimationFrame(this.gameInterval);
        alert('اللعبة متوقفة مؤقتاً');
        this.loop();
    }
};

window.onload = () => Game.init();
