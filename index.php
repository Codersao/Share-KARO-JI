<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
     <link rel="icon" type="image/png" href="logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SendKaroji Share – Secure File Drop</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .donate-btn {
            background: linear-gradient(45deg, #ff00cc, #3333ff);
            padding: 14px 30px;
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(255,0,200,.6);
            transition: 0.3s;
        }
        .donate-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 0 35px rgba(255,0,200,1);
        }

        .popup {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(8px);
        }
        .popup-box {
            background: #111;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            color: white;
            box-shadow: 0 0 30px #000;
        }
        .qr-img {
            width: 230px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .done-btn, .close-btn {
            background:#444;
            padding: 10px 20px;
            border: none;
            color:white;
            border-radius:10px;
            cursor:pointer;
            margin:10px;
        }

        .success-video {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        :root {
            --bg: #0a0a1a; --card: rgba(20,20,40,.6); --glow: #00ffff33;
            --primary: #00dbde; --accent: #ff00ff; --text: #e0f7fa;
        }
        .light {
            --bg:#f8f9ff; --card:rgba(255,255,255,.9); --glow:#007bff33;
            --primary:#007bff; --accent:#ff6b6b; --text:#1a1a2e;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{
            font-family:'Inter',sans-serif;color:var(--text);min-height:100vh;
            background:var(--bg);overflow-y:auto;overflow-x:hidden;display:flex;align-items:center;justify-content:center;
            transition:background .5s;
        }
        canvas{position:absolute;inset:0;z-index:0;}
        .container{
            z-index:10;width:90%;max-width:460px;padding:clamp(20px,5vw,30px);
            border-radius:clamp(16px,4vw,24px);background:var(--card);
            backdrop-filter:blur(16px);box-shadow:0 12px 30px rgba(0,0,0,.3),0 0 50px var(--glow);
            transition:all .4s;position:relative;
        }
        @media (max-width:480px){
            .container{transform:none !important;box-shadow:0 8px 20px rgba(0,0,0,.3),0 0 30px var(--glow);max-height:95vh;overflow-y:auto;-webkit-overflow-scrolling:touch;}
        }
        .container:hover,.container.dragover{
            transform:scale(1.02);box-shadow:0 20px 40px rgba(0,0,0,.4),0 0 70px var(--glow);
        }
        h1{
            font-family:'Orbitron',sans-serif;font-size:clamp(2rem,7vw,2.8rem);
            text-align:center;background:linear-gradient(90deg,var(--primary),var(--accent));
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px;
        }
        .subtitle{text-align:center;font-size:clamp(.9rem,3vw,1rem);opacity:.8;margin-bottom:20px;}

        .drop-zone{
            border:3px dashed rgba(255,255,255,.3);border-radius:16px;padding:clamp(20px,6vw,30px);
            text-align:center;transition:all .3s;cursor:pointer;margin-bottom:20px;position:relative;
        }
        .drop-zone.dragover{
            border-color:var(--primary);background:rgba(0,220,222,.1);transform:scale(1.03);
        }
        .drop-zone i{font-size:clamp(2.5rem,8vw,3rem);color:var(--primary);display:block;margin-bottom:8px;}
        .file-info{margin-top:8px;font-size:clamp(.8rem,2.5vw,.9rem);opacity:.7;}

        .input-group{margin:12px 0;}
        input,select{
            width:100%;padding:clamp(12px,3.5vw,14px) 16px;border-radius:12px;border:none;
            background:rgba(255,255,255,.1);color:var(--text);font-size:1rem;outline:none;transition:.3s;
        }
        input:focus,select:focus{background:rgba(255,255,255,.2);box-shadow:0 0 0 2px var(--primary);}
        select{
            appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23aaa' viewBox='0 0 12 12'%3E%3Cpath d='M6 9L2 4h8L6 9z'/%3E%3C/svg%3E");
            background-repeat:no-repeat;background-position:right 16px center;
        }

        .toggle-group{display:flex;gap:8px;margin:15px 0;}
        .toggle-btn{
            flex:1;padding:clamp(10px,3vw,12px);border-radius:12px;background:rgba(255,255,255,.1);
            text-align:center;cursor:pointer;transition:.3s;font-weight:600;font-size:clamp(.85rem,2.5vw,.95rem);
        }
        .toggle-btn.active{background:var(--primary);color:#fff;}

        button{
            width:100%;padding:clamp(14px,4vw,16px);border:none;border-radius:12px;
            background:linear-gradient(45deg,var(--primary),var(--accent));color:#fff;
            font-weight:600;font-size:clamp(1rem,3.5vw,1.1rem);cursor:pointer;transition:.3s;margin-top:10px;
            box-shadow:0 8px 20px rgba(0,0,0,.2);
        }
        button:hover{transform:translateY(-2px);box-shadow:0 12px 25px rgba(0,0,0,.3);}

        progress{width:100%;height:8px;border-radius:4px;margin:15px 0;display:none;}

        #result {
            margin-top: 20px;
            text-align: center;
        }
        #qr {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 160px;
        }
        .qr-code-container canvas {
            max-width: 100%;
            height: auto;
        }
        .share-btns {
            margin-top: 15px;
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .share-btns button {
            flex:1;
            padding:10px;
            font-size:clamp(.85rem,2.5vw,.9rem);
        }

        #theme{
            position:fixed;top:env(safe-area-inset-top,16px);right:16px;
            background:none;border:none;font-size:clamp(1.5rem,5vw,1.8rem);cursor:pointer;z-index:100;
        }

        .links{text-align:center;margin-top:25px;font-size:clamp(.85rem,2.5vw,.9rem);}
        .links a{color:var(--primary);text-decoration:none;margin:0 8px;}
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <canvas id="particles"></canvas>
    <button id="theme"><i class="fas fa-moon"></i></button>

    <div class="container" id="dropZone">
        <h1>SEND KARO JI</h1>
        <p class="subtitle">Secure • Fast • Temporary</p>

        <form id="uploadForm">
            <div class="drop-zone" id="dropArea">
                <i class="fas fa-cloud-upload-alt"></i>
                <p><strong>Drop file here</strong> or tap to browse</p>
                <input type="file" name="file" id="fileInput" required style="display:none;">
                <div class="file-info" id="fileInfo"></div>
            </div>

            <div class="toggle-group">
                <div class="toggle-btn active" data-value="private"><i class="fas fa-lock"></i> Private</div>
                <div class="toggle-btn" data-value="public"><i class="fas fa-globe"></i> Public</div>
            </div>
            <input type="hidden" name="visibility" value="private">

            <div class="input-group"><input type="password" name="password" placeholder="Optional password"></div>
            <div class="input-group"><input type="text" name="code" placeholder="Secret code (CODER = permanent)"></div>

            <button type="submit">Upload File</button>
        </form>

        <progress id="progress" value="0" max="100"></progress>
        <div id="result"></div>
        <div id="qr" class="qr-code-container"></div>
        <div class="share-btns" id="shareBtns" style="display:none;">
            <button onclick="copyLink()"><i class="fas fa-copy"></i> Copy</button>
            <button onclick="share()"><i class="fas fa-share-alt"></i> Share</button>
        </div>

        <!-- Donation Button -->
        <button id="donateBtn" class="donate-btn">
            <i class="fas fa-heart"></i> Donate
        </button>

        <!-- Popup -->
        <div id="donatePopup" class="popup">
            <div class="popup-box">
                <h2>Support Us ❤️</h2>
                <p>Scan the QR to donate via UPI</p>
                <img src="your-qr.png" class="qr-img">
                <button id="donePayment" class="done-btn">I Have Paid</button>
                <button id="closePopup" class="close-btn">Close</button>
            </div>
        </div>

        <!-- Success Video -->
        <video id="successVideo" class="success-video" src="success.mp4"></video>

        <div class="links">
            <a href="list.php">Public Files</a> |
            <a href="admin.php">Admin</a>
        </div>
    </div>

    <script>
        let donateBtn = document.getElementById("donateBtn");
        let popup = document.getElementById("donatePopup");
        let closePopup = document.getElementById("closePopup");
        let donePayment = document.getElementById("donePayment");
        let video = document.getElementById("successVideo");

        donateBtn.onclick = () => {
            popup.style.display = "flex";
        };

        closePopup.onclick = () => {
            popup.style.display = "none";
        };

        donePayment.onclick = () => {
            popup.style.display = "none";
            video.style.display = "block";
            video.play();
            video.onended = () => {
                video.style.display = "none";
            };
        };

        const canvas = document.getElementById('particles'), ctx = canvas.getContext('2d');
        const isMobile = innerWidth <= 600;
        const particleCount = isMobile ? 40 : 100;
        canvas.width = innerWidth; canvas.height = innerHeight;
        const particles = Array.from({length:particleCount},()=>({
            x:Math.random()*canvas.width, y:Math.random()*canvas.height,
            r:Math.random()*1.5+0.5, vx:Math.random()*1.2-0.6, vy:Math.random()*1.2-0.6
        }));
        function draw(){
            ctx.clearRect(0,0,canvas.width,canvas.height);
            ctx.fillStyle = 'rgba(0,219,222,.3)';
            particles.forEach(p=>{
                ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2); ctx.fill();
                p.x+=p.vx; p.y+=p.vy;
                if(p.x<0||p.x>canvas.width) p.vx=-p.vx;
                if(p.y<0||p.y>canvas.height) p.vy=-p.vy;
            });
            requestAnimationFrame(draw);
        }
        draw();
        addEventListener('resize',()=>{canvas.width=innerWidth;canvas.height=innerHeight;});

        const themeBtn = document.getElementById('theme');
        themeBtn.onclick = () => {
            document.body.classList.toggle('light');
            if (document.body.classList.contains('light')) {
                themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                themeBtn.innerHTML = '<i class="fas fa-moon"></i>';
            }
        };

        const dropArea = document.getElementById('dropArea'), fileInput = document.getElementById('fileInput'), fileInfo = document.getElementById('fileInfo');
        ['dragenter','dragover','dragleave','drop'].forEach(e=>dropArea.addEventListener(e,ev=>ev.preventDefault()));
        ['dragenter','dragover'].forEach(e=>dropArea.addEventListener(e,()=>dropArea.classList.add('dragover')));
        ['dragleave','drop'].forEach(e=>dropArea.addEventListener(e,()=>dropArea.classList.remove('dragover')));
        dropArea.addEventListener('drop',e=>{
            const f = e.dataTransfer.files[0];
            if(f){ fileInput.files = e.dataTransfer.files; updateFileInfo(f); }
        });
        dropArea.addEventListener('click',()=>fileInput.click());
        fileInput.addEventListener('change',e=>updateFileInfo(e.target.files[0]));

        function updateFileInfo(file){
            if(!file) return;
            const size = (file.size/1024/1024).toFixed(2);
            fileInfo.innerHTML = `<strong>${file.name}</strong> (${size} MB)`;
        }

        document.querySelectorAll('.toggle-btn').forEach(b=>{
            b.onclick = () => {
                document.querySelectorAll('.toggle-btn').forEach(x=>x.classList.remove('active'));
                b.classList.add('active');
                document.querySelector('input[name="visibility"]').value = b.dataset.value;
            };
        });

        const form = document.getElementById('uploadForm'), progress = document.getElementById('progress'),
              result = document.getElementById('result'), qrDiv = document.getElementById('qr'),
              shareBtns = document.getElementById('shareBtns');
        let currentLink = '';

        form.onsubmit = e => {
            e.preventDefault();
            if(!fileInput.files[0]) return alert('Select a file');
            progress.style.display = 'block';
            result.innerHTML = ''; qrDiv.innerHTML = ''; shareBtns.style.display = 'none';

            const fd = new FormData(form), xhr = new XMLHttpRequest();
            xhr.open('POST','upload.php',true);
            xhr.upload.onprogress = ev => {if(ev.lengthComputable) progress.value = (ev.loaded/ev.total)*100;};
            xhr.onload = () => {
                progress.style.display = 'none';
                const r = JSON.parse(xhr.responseText);
                if(r.success){
                    currentLink = r.link;
                    result.innerHTML = `<div style="color:#4caf50;font-weight:600;">Uploaded!<br>
                        <a href="${r.link}" target="_blank" style="color:var(--primary);text-decoration:underline;">${r.link}</a></div>`;
                    qrDiv.innerHTML = '';
                    new QRCode(qrDiv,{text:r.link,width:160,height:160,
                        colorDark:document.body.classList.contains('light')?'#000':'#fff',
                        colorLight:'transparent'});
                    shareBtns.style.display = 'flex';
                }else{
                    result.innerHTML = `<div style="color:#f44336;">${r.error}</div>`;
                }
            };
            xhr.send(fd);
        };

        function copyLink(){navigator.clipboard.writeText(currentLink).then(()=>alert('Link copied!'));};
        function share(){navigator.share?navigator.share({url:currentLink}):copyLink();};
    </script>
</body>
</html>
