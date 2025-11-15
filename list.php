<?php
session_start();

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) die('Uploads folder missing.');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$allowed_exts = ['jpg','jpeg','png','gif','webp','mp3','wav','mp4','webm','pdf'];

$public = [];
foreach (glob($uploadDir . '*') as $fpath) {
    if (is_dir($fpath)) continue;
    $ext = strtolower(pathinfo($fpath, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts)) continue;
    if (preg_match('~\.meta$~i', $fpath)) continue;

    $id = pathinfo($fpath, PATHINFO_FILENAME);
    $metaFile = $uploadDir . $id . '.meta';
    $meta = [];
    if (file_exists($metaFile)) {
        $raw = file_get_contents($metaFile);
        if ($raw) $meta = json_decode($raw, true);
        if (!empty($meta['expire']) && time() > $meta['expire']) continue;
        if (isset($meta['is_public']) && !$meta['is_public']) continue;
    }

    $name = !empty($meta['original_name']) ? $meta['original_name'] : basename($fpath);
    $public[] = [
        'id'       => $id,
        'name'     => $name,
        'ext'      => $ext,
        'url'      => $baseUrl . '/uploads/' . rawurlencode(basename($fpath)),
        'download' => $baseUrl . '/download.php?id=' . urlencode($id),
        'view'     => $baseUrl . '/view.php?id=' . urlencode($id),
        'media'    => in_array($ext, ['jpg','jpeg','png','gif','webp','mp3','wav','mp4','webm','pdf']),
        'password' => !empty($meta['password']) ? true : false
    ];
}

// Handle password check via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_password') {
    $id = $_POST['id'] ?? '';
    $password = $_POST['password'] ?? '';
    $metaFile = $uploadDir . $id . '.meta';
    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true);
        if (!empty($meta['password']) && password_verify($password, $meta['password'])) {
            $_SESSION['allow_' . $id] = true;
            echo json_encode(['allowed' => true]);
            exit;
        }
    }
    echo json_encode(['allowed' => false]);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
 <link rel="icon" type="image/png" href="logo.png">
<title>Public Files – CODER Share</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://unpkg.com/wavesurfer.js"></script>

<style>
:root { --bg:#0a0a1a; --card:rgba(20,20,40,.7); --glow:#00ffff33; --primary:#00dbde; --accent:#ff00ff; --text:#e0f7fa }
.light{ --bg:#f8f9ff; --card:rgba(255,255,255,.95); --glow:#007bff33; --primary:#007bff; --accent:#ff6b6b; --text:#1a1a2e }
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;padding:20px;transition:.4s}
.grid{display:grid;gap:20px;grid-template-columns:repeat(auto-fill,minmax(320px,1fr))}
.card{background:var(--card);padding:22px;border-radius:16px;box-shadow:0 0 30px var(--glow);transition:.3s;position:relative;}
.card:hover{box-shadow:0 0 50px var(--primary), 0 0 12px var(--accent);}
.preview img, .preview video, .preview audio, .preview iframe{width:100%;border-radius:10px;box-shadow:0 0 10px var(--glow);}
.preview{position:relative;}
.waveform{background:rgba(0,0,0,.06);border-radius:7px;margin-bottom:7px;height:60px;}
.fs-btn{position:absolute;top:7px;right:7px;background:var(--primary);color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:16px;cursor:pointer;opacity:0.8;}
.fs-btn:hover{background:var(--accent);opacity:1;}
.btn{padding:10px;border-radius:10px;background:linear-gradient(45deg,var(--primary),var(--accent));border:none;color:#fff;cursor:pointer;font-weight:600;}
.actions{display:flex;gap:8px;margin-top:11px}
.qr{margin-top:10px;width:120px;height:120px;background:#fff;padding:5px;border-radius:12px;display:flex;align-items:center;justify-content:center;}
.name{font-family:'Orbitron';font-size:19px}
.ext{font-size:13px;letter-spacing:2px;color:var(--primary);margin-bottom:6px;}
#theme{position:fixed;right:20px;top:20px;font-size:22px;cursor:pointer;background:none;border:none;color:var(--text);}
.empty{text-align:center;margin-top:40px;}
#search {
    width: 100%;
    padding: 14px;
    margin-bottom: 20px;
    border-radius: 20px;
    border: 2px solid #00dbde;
    outline: none;
    font-size: 16px;
    font-family: 'Orbitron', sans-serif;
    background: #1a1a2e;
    color: #e0f7fa;
    box-shadow: 0 0 10px rgba(0, 219, 222, 0.3);
    transition: all 0.3s ease;
}
#search:focus {
    border-color: #ff00ff;
    box-shadow: 0 0 15px rgba(255, 0, 255, 0.4);
    background: #202030;
}
.preview.blurred img,
.preview.blurred video,
.preview.blurred iframe {
    filter: blur(10px);
    pointer-events: none;
}
.lock-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    border-radius: 10px;
    cursor: pointer;
}
@media (max-width:450px){.grid{grid-template-columns:1fr}.card{padding:12px;}}
.card{animation:flipIn .5s;}
@keyframes flipIn{from{transform:rotateY(40deg);opacity:0;}to{transform:none;opacity:1;}}
</style>
</head>
<body>
<button id="theme"><i class="fas fa-moon"></i></button>
<h1 style="text-align:center;font-family:Orbitron;">Public Files</h1>

<input type="text" id="search" placeholder="Search files..." onkeyup="filterFiles()">

<?php if (empty($public)): ?>
    <p class="empty">No public files available.</p>
<?php else: ?>
<div class="grid" id="fileGrid">
<?php foreach ($public as $f): ?>
    <div class="card" data-name="<?= htmlspecialchars(strtolower($f['name'])) ?>">
        <div class="name">
            <i class="fa fa-file-<?= htmlspecialchars($f['ext']) ?>"></i>
            <b><?= htmlspecialchars($f['name']) ?></b>
        </div>
        <div class="ext">.<?= strtoupper($f['ext']) ?></div>

        <?php if ($f['media']): ?>
        <div class="preview <?= $f['password'] ? 'blurred' : '' ?>" id="preview-<?= $f['id'] ?>">
            <?php if (in_array($f['ext'], ['jpg','jpeg','png','gif','webp'])): ?>
                <img src="<?= $f['url'] ?>" loading="lazy" id="img-<?= $f['id'] ?>">
                <?php if ($f['password']): ?>
                    <div class="lock-overlay" onclick="promptPassword('<?= $f['id'] ?>', '<?= $f['url'] ?>', '<?= $f['ext'] ?>')">🔒 Password</div>
                <?php endif; ?>
                <button class="fs-btn" onclick="openFull('img-<?= $f['id'] ?>')"><i class="fa fa-expand"></i></button>
            <?php elseif ($f['ext'] === 'pdf'): ?>
                <iframe src="<?= $f['url'] ?>" id="pdf-<?= $f['id'] ?>"></iframe>
                <?php if ($f['password']): ?>
                    <div class="lock-overlay" onclick="promptPassword('<?= $f['id'] ?>', '<?= $f['url'] ?>', '<?= $f['ext'] ?>')">🔒 Password</div>
                <?php endif; ?>
                <button class="fs-btn" onclick="openFull('pdf-<?= $f['id'] ?>')"><i class="fa fa-expand"></i></button>
            <?php elseif (in_array($f['ext'], ['mp4','webm'])): ?>
                <video controls preload="metadata" id="vid-<?= $f['id'] ?>" <?= $f['password'] ? 'onplay="promptPassword(\''.$f['id'].'\', \''.$f['url'].'\', \''.$f['ext'].'\')"' : '' ?>>
                    <source src="<?= $f['url'] ?>" type="video/<?= $f['ext'] ?>">
                </video>
                <?php if ($f['password']): ?>
                    <div class="lock-overlay" onclick="promptPassword('<?= $f['id'] ?>', '<?= $f['url'] ?>', '<?= $f['ext'] ?>')">🔒 Password</div>
                <?php endif; ?>
                <button class="fs-btn" onclick="openFull('vid-<?= $f['id'] ?>')"><i class="fa fa-expand"></i></button>
            <?php elseif (in_array($f['ext'], ['mp3','wav'])): ?>
                <div id="waveform-<?= $f['id'] ?>" class="waveform"></div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button class="btn" onclick="<?= $f['password'] ? 'promptPassword(\''.$f['id'].'\', \''.$f['url'].'\', \''.$f['ext'].'\')' : 'togglePlay(\''.$f['id'].'\')' ?>"><i class="fa fa-play"></i> Play</button>
                    <button class="fs-btn" style="position:static;opacity:1;box-shadow:none;" onclick="openFull('waveform-<?= $f['id'] ?>')"><i class="fa fa-expand"></i></button>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="actions">
            <button class="btn" onclick="copyLink('<?= $f['download'] ?>')"><i class="fa fa-copy"></i> Copy</button>
            <button class="btn" onclick="shareLink('<?= addslashes($f['name']) ?>','<?= $f['download'] ?>')"><i class="fa fa-share-alt"></i> Share</button>
            <?php if (in_array($f['ext'], ['mp4','webm'])): ?>
                <button class="btn" id="view-btn-<?= $f['id'] ?>" onclick="openVideoFullscreen('vid-<?= $f['id'] ?>')"><i class="fa fa-eye"></i> View</button>
            <?php else: ?>
                <button class="btn" onclick="window.open('<?= $f['view'] ?>')"><i class="fa fa-eye"></i> View</button>
            <?php endif; ?>
            <button class="btn" onclick="window.location.href='<?= $f['download'] ?>'"><i class="fa fa-download"></i> Download</button>
        </div>
        <div class="qr" id="qr-<?= $f['id'] ?>"></div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
/* ---------- QR CODE (dynamic colour) ---------- */
function renderQRCodes() {
    document.querySelectorAll('.qr').forEach(el => {
        el.innerHTML = '';
        const id = el.id.replace('qr-','');
        const card = el.closest('.card');
        const btn = card.querySelector('.actions button');
        const url = btn.getAttribute('onclick').match(/'(.*?)'/)[1];
        const isLight = document.body.classList.contains('light');
        const dark = isLight ? '#000' : '#fff';
        const light= isLight ? '#fff' : '#000';
        new QRCode(el, {
            text: url,
            width: 110, height: 110,
            colorDark: dark,
            colorLight: light
        });
    });
}
renderQRCodes();

/* ---------- FULLSCREEN ---------- */
function openFull(elemId){
    const el = document.getElementById(elemId);
    if (!el) return;
    if (el.requestFullscreen) el.requestFullscreen();
    else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    else if (el.msRequestFullscreen) el.msRequestFullscreen();
    else alert('Fullscreen not supported');
}

/* ---------- COPY / SHARE ---------- */
function copyLink(url){
    navigator.clipboard.writeText(url).then(()=>{alert('Copied!')});
}

function shareLink(title,url){
    if (navigator.share) navigator.share({title,url});
    else copyLink(url);
}

/* ---------- THEME TOGGLE (fixed + QR update) ---------- */
const themeBtn = document.getElementById('theme');
themeBtn.onclick = () => {
    document.body.classList.toggle('light');
    const isLight = document.body.classList.contains('light');
    themeBtn.innerHTML = `<i class="fas fa-${isLight?'sun':'moon'}"></i>`;
    renderQRCodes(); // re‑draw QR with correct colours
};

/* --- WAVE SURFER AUDIO --- */
let waveSurfers = {};

function createWaveSurfer(id, url) {
    let container = document.getElementById('waveform-' + id);
    if (!container) return;
    waveSurfers[id] = WaveSurfer.create({
        container: container,
        waveColor: '#00dbde',
        progressColor: '#ff00ff',
        height: 55,
        barWidth: 2,
        barRadius: 2,
        responsive: true,
    });
    waveSurfers[id].load(url);
}

function togglePlay(id) {
    if(waveSurfers[id]) {
        waveSurfers[id].playPause();
    }
}

// New function for video fullscreen
function openVideoFullscreen(videoId) {
    const video = document.getElementById(videoId);
    if (video) {
        if (video.requestFullscreen) {
            video.requestFullscreen();
        } else if (video.webkitRequestFullscreen) {
            video.webkitRequestFullscreen();
        } else if (video.msRequestFullscreen) {
            video.msRequestFullscreen();
        } else {
            alert('Fullscreen not supported');
        }
    }
}

// Update View button text when video plays/pauses
function updateViewButton(id, isPlaying) {
    const btn = document.getElementById('view-btn-' + id);
    if (btn) {
        btn.innerHTML = isPlaying ? '<i class="fa fa-arrows-alt"></i> Fullscreen' : '<i class="fa fa-eye"></i> View';
    }
}

// Initialize all waveforms on DOMContentLoaded
document.addEventListener("DOMContentLoaded", function() {
    <?php foreach ($public as $f): ?>
        <?php if (in_array($f['ext'], ['mp3','wav'])): ?>
        createWaveSurfer('<?= $f['id'] ?>', '<?= $f['url'] ?>');
        <?php endif; ?>
    <?php endforeach; ?>
});

// Search filter
function filterFiles() {
    const input = document.getElementById('search');
    const filter = input.value.toLowerCase();
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        if (name.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Password prompt and check
function promptPassword(id, url, ext) {
    const userPass = prompt('Enter password to view this file:');
    if (userPass) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'check_password', id: id, password: userPass})
        })
        .then(response => response.json())
        .then(data => {
            if (data.allowed) {
                const preview = document.getElementById('preview-' + id);
                preview.classList.remove('blurred');
                preview.querySelector('.lock-overlay')?.remove();
                if (ext === 'mp3' || ext === 'wav') {
                    togglePlay(id);
                }
            } else {
                alert('Incorrect password!');
            }
        });
    }
}
</script>
<script src="https://unpkg.com/wavesurfer.js"></script
