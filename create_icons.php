<?php
// ===== GERAR ÍCONES PWA - Jogos de Festa =====
// Visita UMA VEZ: http://192.168.1.96/jogos/create_icons.php
// Usa Canvas do browser para renderizar emoji + gradientes perfeitos
// Depois grava automaticamente os PNGs no servidor

// Handle save request via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $size = isset($_POST['size']) ? (int)$_POST['size'] : 0;
    $data = isset($_POST['data']) ? $_POST['data'] : '';

    if (!$size || !$data) {
        echo json_encode(['ok' => false, 'error' => 'Dados em falta']);
        exit;
    }

    $dir = __DIR__ . '/icons';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    // Remove data URL prefix
    $data = preg_replace('/^data:image\/png;base64,/', '', $data);
    $decoded = base64_decode($data);

    if (!$decoded) {
        echo json_encode(['ok' => false, 'error' => 'Base64 inválido']);
        exit;
    }

    $file = "$dir/icon-$size.png";
    file_put_contents($file, $decoded);

    echo json_encode(['ok' => true, 'file' => "icon-$size.png", 'bytes' => filesize($file)]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Ícones - Jogos de Festa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #0a0a1a;
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #f9d423, #ff4e50, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .sub { color: #8888aa; margin-bottom: 32px; }
        .icons-row {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 32px;
        }
        .icon-box {
            text-align: center;
        }
        .icon-box canvas {
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.3);
        }
        .icon-box .label {
            margin-top: 12px;
            color: #8888aa;
            font-size: 14px;
        }
        #status {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }
        .msg {
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
        }
        .msg.ok { background: rgba(74, 222, 128, 0.15); color: #4ade80; }
        .msg.err { background: rgba(248, 113, 113, 0.15); color: #f87171; }
        .msg.info { background: rgba(139, 92, 246, 0.15); color: #a78bfa; }
        .done {
            margin-top: 24px;
            font-size: 20px;
            font-weight: 800;
            color: #4ade80;
        }
        .hint {
            margin-top: 12px;
            color: #666;
            font-size: 13px;
        }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(139, 92, 246, 0.3);
            border-top-color: #8b5cf6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <h1>Criar Ícones PWA</h1>
    <p class="sub">Jogos de Festa</p>

    <div class="icons-row" id="icons-row"></div>
    <div id="status"><div class="msg info"><span class="spinner"></span> A gerar ícones...</div></div>

    <script>
    const SIZES = [192, 512];
    const container = document.getElementById('icons-row');
    const status = document.getElementById('status');

    function drawIcon(size) {
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        const s = size; // shorthand

        // === BACKGROUND ===
        // Deep dark gradient
        const bgGrad = ctx.createLinearGradient(0, 0, s, s);
        bgGrad.addColorStop(0, '#12082a');
        bgGrad.addColorStop(0.4, '#1a0f3c');
        bgGrad.addColorStop(0.7, '#150b30');
        bgGrad.addColorStop(1, '#0a0618');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, s, s);

        // Purple radial glow (top-left)
        const glow1 = ctx.createRadialGradient(s * 0.25, s * 0.3, 0, s * 0.25, s * 0.3, s * 0.55);
        glow1.addColorStop(0, 'rgba(139, 92, 246, 0.25)');
        glow1.addColorStop(0.5, 'rgba(139, 92, 246, 0.08)');
        glow1.addColorStop(1, 'transparent');
        ctx.fillStyle = glow1;
        ctx.fillRect(0, 0, s, s);

        // Red radial glow (bottom-right)
        const glow2 = ctx.createRadialGradient(s * 0.75, s * 0.7, 0, s * 0.75, s * 0.7, s * 0.5);
        glow2.addColorStop(0, 'rgba(239, 68, 68, 0.15)');
        glow2.addColorStop(0.5, 'rgba(239, 68, 68, 0.05)');
        glow2.addColorStop(1, 'transparent');
        ctx.fillStyle = glow2;
        ctx.fillRect(0, 0, s, s);

        // === CENTRAL CIRCLE (glass effect) ===
        // Outer glow
        const circGlow = ctx.createRadialGradient(s * 0.5, s * 0.43, s * 0.2, s * 0.5, s * 0.43, s * 0.34);
        circGlow.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
        circGlow.addColorStop(1, 'transparent');
        ctx.fillStyle = circGlow;
        ctx.beginPath();
        ctx.arc(s * 0.5, s * 0.43, s * 0.34, 0, Math.PI * 2);
        ctx.fill();

        // Main circle
        const circGrad = ctx.createLinearGradient(s * 0.3, s * 0.2, s * 0.7, s * 0.65);
        circGrad.addColorStop(0, '#7c3aed');
        circGrad.addColorStop(0.5, '#6d28d9');
        circGrad.addColorStop(1, '#5b21b6');
        ctx.fillStyle = circGrad;
        ctx.beginPath();
        ctx.arc(s * 0.5, s * 0.43, s * 0.26, 0, Math.PI * 2);
        ctx.fill();

        // Glass highlight on circle
        const glassGrad = ctx.createLinearGradient(s * 0.35, s * 0.2, s * 0.55, s * 0.45);
        glassGrad.addColorStop(0, 'rgba(255, 255, 255, 0.2)');
        glassGrad.addColorStop(0.5, 'rgba(255, 255, 255, 0.05)');
        glassGrad.addColorStop(1, 'transparent');
        ctx.fillStyle = glassGrad;
        ctx.beginPath();
        ctx.arc(s * 0.5, s * 0.43, s * 0.26, 0, Math.PI * 2);
        ctx.fill();

        // === GAME CONTROLLER EMOJI ===
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // Shadow for emoji
        ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
        ctx.shadowBlur = s * 0.04;
        ctx.shadowOffsetY = s * 0.015;

        ctx.font = `${s * 0.28}px "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif`;
        ctx.fillText('🎮', s * 0.5, s * 0.42);
        ctx.restore();

        // === "JOGOS" TEXT ===
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // Gold gradient for "JOGOS"
        const textGrad = ctx.createLinearGradient(s * 0.3, s * 0.72, s * 0.7, s * 0.72);
        textGrad.addColorStop(0, '#f9d423');
        textGrad.addColorStop(0.5, '#f59e0b');
        textGrad.addColorStop(1, '#f9d423');

        ctx.shadowColor = 'rgba(0, 0, 0, 0.6)';
        ctx.shadowBlur = s * 0.03;
        ctx.shadowOffsetY = s * 0.01;

        ctx.font = `900 ${s * 0.11}px "Segoe UI", "SF Pro Display", Arial, sans-serif`;
        ctx.fillStyle = textGrad;
        ctx.fillText('JOGOS', s * 0.5, s * 0.73);
        ctx.restore();

        // === "DE FESTA" TEXT ===
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.font = `600 ${s * 0.06}px "Segoe UI", "SF Pro Display", Arial, sans-serif`;
        ctx.fillStyle = 'rgba(255, 255, 255, 0.5)';
        ctx.shadowColor = 'rgba(0, 0, 0, 0.4)';
        ctx.shadowBlur = s * 0.02;
        ctx.fillText('DE FESTA', s * 0.5, s * 0.84);
        ctx.restore();

        // === DECORATIVE DOTS ===
        const dots = [
            { x: 0.15, y: 0.15, r: 0.015, c: 'rgba(245, 158, 11, 0.4)' },
            { x: 0.85, y: 0.2, r: 0.012, c: 'rgba(139, 92, 246, 0.5)' },
            { x: 0.1, y: 0.75, r: 0.01, c: 'rgba(239, 68, 68, 0.4)' },
            { x: 0.88, y: 0.8, r: 0.018, c: 'rgba(245, 158, 11, 0.3)' },
            { x: 0.2, y: 0.5, r: 0.008, c: 'rgba(255, 255, 255, 0.2)' },
            { x: 0.82, y: 0.5, r: 0.008, c: 'rgba(255, 255, 255, 0.15)' },
        ];
        dots.forEach(d => {
            ctx.fillStyle = d.c;
            ctx.beginPath();
            ctx.arc(s * d.x, s * d.y, s * d.r, 0, Math.PI * 2);
            ctx.fill();
        });

        // === SUBTLE BORDER ===
        ctx.strokeStyle = 'rgba(139, 92, 246, 0.15)';
        ctx.lineWidth = s * 0.005;
        ctx.strokeRect(s * 0.01, s * 0.01, s * 0.98, s * 0.98);

        return canvas;
    }

    async function saveIcon(canvas, size) {
        const dataURL = canvas.toDataURL('image/png');
        const formData = new FormData();
        formData.append('size', size);
        formData.append('data', dataURL);

        const resp = await fetch('create_icons.php', { method: 'POST', body: formData });
        return await resp.json();
    }

    (async () => {
        let allOk = true;
        status.innerHTML = '';

        for (const size of SIZES) {
            try {
                // Draw icon
                const canvas = drawIcon(size);

                // Display preview (scale down for display)
                const displaySize = Math.min(size, 192);
                canvas.style.width = displaySize + 'px';
                canvas.style.height = displaySize + 'px';

                const box = document.createElement('div');
                box.className = 'icon-box';
                box.appendChild(canvas);
                const label = document.createElement('div');
                label.className = 'label';
                label.textContent = `${size}×${size}px`;
                box.appendChild(label);
                container.appendChild(box);

                // Save to server
                const result = await saveIcon(canvas, size);

                if (result.ok) {
                    status.innerHTML += `<div class="msg ok">✅ ${result.file} criado (${(result.bytes / 1024).toFixed(1)} KB)</div>`;
                } else {
                    allOk = false;
                    status.innerHTML += `<div class="msg err">❌ Erro: ${result.error || 'desconhecido'}</div>`;
                }
            } catch (e) {
                allOk = false;
                status.innerHTML += `<div class="msg err">❌ Erro icon-${size}: ${e.message}</div>`;
            }
        }

        if (allOk) {
            status.innerHTML += `<div class="done">🎉 Ícones criados com sucesso!</div>`;
            status.innerHTML += `<div class="hint">Podes apagar este ficheiro (create_icons.php) depois.</div>`;
        }
    })();
    </script>
</body>
</html>
