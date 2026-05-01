<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");

$prefill_name = isset($_GET['team_name']) ? htmlspecialchars($_GET['team_name']) : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo Maker Studio — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        body {
            background: #020617;
            background-image: 
                linear-gradient(rgba(2, 6, 23, 0.8), rgba(2, 6, 23, 0.9)),
                url('https://images2.alphacoders.com/105/1059431.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            padding-bottom: 80px;
            color: #fff;
        }
        .maker-layout {
            max-width: 700px;
            margin: 30px auto;
            display: flex;
            flex-direction: column;
            gap: 25px;
            perspective: 1000px;
        }
        .controls-panel {
            background: linear-gradient(165deg, rgba(15, 23, 42, 0.9), rgba(2, 6, 23, 0.95));
            backdrop-filter: blur(25px);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 24px;
            padding: 45px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.7), inset 0 0 20px rgba(56, 189, 248, 0.05);
            position: relative;
            z-index: 1;
        }
        .controls-panel::after {
            content: ""; position: absolute; top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, transparent, rgba(56, 189, 248, 0.3), transparent);
            z-index: -1; border-radius: 26px; pointer-events: none;
        }

        .control-group { margin-bottom: 20px; }
        .control-group label {
            display: block;
            color: var(--cyan);
            font-family: 'Rajdhani', sans-serif;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            text-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
        }

        input, select {
            background: rgba(2, 6, 23, 0.7) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            height: 50px !important;
            border-radius: 12px !important;
            padding: 0 20px !important;
            font-family: 'Rajdhani', sans-serif !important;
            font-weight: 600 !important;
            letter-spacing: 1px !important;
        }

        input:focus, select:focus {
            border-color: var(--cyan) !important;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.15) !important;
        }

        .color-inputs { display: flex; gap: 15px; }
        .color-inputs input[type="color"] { flex: 1; height: 50px; cursor: pointer; padding: 5px; }
        
        .result-box {
            background: #010409;
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 16px;
            padding: 30px;
            margin-top: 10px;
            position: relative;
            width: 100%;
            text-align: left;
            box-shadow: inset 0 0 30px rgba(0,0,0,0.5);
            animation: slideUp 0.4s ease-out;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        #generatedPrompt {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            color: #38bdf8;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .copy-btn {
            background: var(--cyan);
            color: #020617;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 900;
            font-family: 'Rajdhani';
            cursor: pointer;
            text-transform: uppercase;
            font-size: 11px;
            transition: 0.3s;
        }
        .copy-btn:hover { filter: brightness(1.2); box-shadow: 0 0 15px var(--cyan); }
    </style>
</head>
<body>

<?php render_app_header('logo-maker'); ?>

<div class="wrapper">
    <div class="hero" style="text-align: center; background: rgba(15, 23, 42, 0.5);">
        <div class="hero-label">eSports Branding Lab</div>
        <h1 class="hero-title" style="font-family: 'Rajdhani';">MPL STYLE LOGO MAKER</h1>
        <p class="section-sub">Design custom mascots, monograms, and minimalist badges for your squads.</p>
    </div>

    <div class="maker-layout">
        <div class="controls-panel">
            <div class="control-group">
                <label>Team Name / Monogram</label>
                <input type="text" id="teamName" value="<?= $prefill_name ?>" placeholder="E.G. MOBA TROPZ" maxlength="20">
            </div>

            <div class="control-group">
                <label>Mascot / Central Subject</label>
                <select id="mascotSubject">
                    <option value="Cybernetic Apex Lion Chief with glowing blue eyes">1. CYBERNETIC APEX LION</option>
                    <option value="Mechanical Storm-Bringer Dragon surrounded by lightning">2. MECHANICAL STORM DRAGON</option>
                    <option value="Aggressive Iron-Clad Battle Tiger with scratch marks">3. IRON-CLAD BATTLE TIGER</option>
                    <option value="Void-Stalker Shadow Wolf with purple energy mist">4. VOID-STALKER SHADOW WOLF</option>
                    <option value="Celestial Radiant Phoenix made of blue and gold flames">5. CELESTIAL RADIANT PHOENIX</option>
                    <option value="Legendary Oni Samurai Commander with dual katanas">6. ONI SAMURAI COMMANDER</option>
                    <option value="Mecha-Knight Paladin in heavy futuristic armor">7. MECHA-KNIGHT PALADIN</option>
                    <option value="Bio-Hazard Venom Cobra with toxic green fangs">8. BIO-HAZARD VENOM COBRA</option>
                    <option value="Techno-Organic Cyber Gorilla with hydraulic arms">9. TECHNO-ORGANIC GORILLA</option>
                    <option value="Deep-Sea Kraken Overlord with glowing tentacles">10. DEEP-SEA KRAKEN OVERLORD</option>
                    <option value="Shadow Assassin Reaper with a glowing ethereal scythe">11. SHADOW ASSASSIN REAPER</option>
                    <option value="Cyber-Undead Titan Skull with metallic jaw plates">12. CYBER-UNDEAD TITAN SKULL</option>
                    <option value="High-Voltage Lightning Elemental in human-form armor">13. VOLTAGE ELEMENTAL</option>
                    <option value="Frost-Bound Tundra Yeti with ice crystal spikes">14. FROST-BOUND TUNDRA YETI</option>
                    <option value="Galactic Star-Eater Wraith with nebula trails">15. GALACTIC STAR-EATER</option>
                    <option value="Trench-Stalker Abyss Shark with robotic enhancements">16. TRENCH-STALKER SHARK</option>
                    <option value="Imperial Royal Guard Griffin with gold-tipped feathers">17. ROYAL GUARD GRIFFIN</option>
                    <option value="Inferno Demon Archon with obsidian horns and lava cracks">18. INFERNO DEMON ARCHON</option>
                    <option value="Neon Street Samurai wearing a futuristic techwear mask">19. NEON STREET SAMURAI</option>
                    <option value="Stealth Operative Shadow Lynx with night-vision eyes">20. STEALTH OPERATIVE LYNX</option>
                </select>
            </div>

            <div class="control-group">
                <label>Style Type</label>
                <select id="styleType">
                    <option value="mascot">MPL MASCOT LOGO</option>
                    <option value="crest">AGGRESSIVE CREST EMBLEM</option>
                    <option value="monogram">TECH MONOGRAM</option>
                    <option value="minimal">MINIMALIST ESPORTS BADGE</option>
                </select>
            </div>

            <div class="control-group">
                <label>Symbol Rendering Style</label>
                <select id="symbolRenderStyle">
                    <option value="flat vector mascot">1. FLAT VECTOR MASCOT</option>
                    <option value="aggressive sports illustration">2. AGGRESSIVE SPORTS ILLUSTRATION</option>
                    <option value="thick outline vector">3. THICK OUTLINE VECTOR</option>
                    <option value="glossy 3D eSports render">4. GLOSSY 3D RENDER</option>
                    <option value="matte finish tech style">5. MATTE FINISH TECH</option>
                    <option value="neon cybernetic glow">6. NEON CYBERNETIC</option>
                    <option value="carbon fiber texture">7. CARBON FIBER TEXTURE</option>
                    <option value="gradient layered vector">8. GRADIENT LAYERED</option>
                    <option value="sharp geometric shard">9. SHARP GEOMETRIC</option>
                    <option value="minimalist athletic brand">10. MINIMALIST ATHLETIC</option>
                    <option value="vintage pro league style">11. VINTAGE PRO LEAGUE</option>
                    <option value="abstract futuristic emblem">12. ABSTRACT FUTURISTIC</option>
                    <option value="fiery demonic aesthetic">13. FIERY DEMONIC</option>
                    <option value="ice-cold frosted glass">14. FROSTED GLASS</option>
                    <option value="high-voltage electric">15. HIGH-VOLTAGE ELECTRIC</option>
                    <option value="nocturnal shadow assassin">16. SHADOW ASSASSIN</option>
                    <option value="pristine platinum chrome">17. PLATINUM CHROME</option>
                    <option value="luxury royal gold">18. LUXURY ROYAL GOLD</option>
                    <option value="military tactical stealth">19. TACTICAL STEALTH</option>
                    <option value="holographic eSports projection">20. HOLOGRAPHIC PROJECTION</option>
                </select>
            </div>

            <div class="control-group">
                <label>Branding Colors</label>
                <div class="color-inputs">
                    <input type="color" id="color1" value="#38bdf8" title="Primary Color">
                    <input type="color" id="color2" value="#f59e0b" title="Secondary Color">
                </div>
            </div>

            <div class="control-group">
                <label>Manual Prompt Add-on (Optional)</label>
                <input type="text" id="customDetail" placeholder="E.G. HOLDING A WEAPON, ELECTRIC AURAS">
            </div>

            <button class="btn" style="width:100%; height:55px; margin-top:20px; font-size:14px;" onclick="generateAIPrompt()">INITIATE AI PROMPT GENERATION</button>
        </div>

        <div class="result-box" id="resultContainer" style="display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid rgba(56,189,248,0.2);">
                <span style="color:var(--cyan); font-weight:900; font-size:11px; letter-spacing:2px; font-family:'Rajdhani';">SYSTEM_OUTPUT // JSON_PROMPT</span>
                <button class="copy-btn" onclick="copyPrompt()">COPY_TO_CLIPBOARD</button>
            </div>
            <p id="generatedPrompt"></p>
        </div>
    </div>
</div>

<script>
function generateAIPrompt() {
    const style = document.getElementById('styleType').value;
    const shape = "shield"; // Fixed to Shield for MPL style after removal of selector
    const c1 = document.getElementById('color1').value;
    const c2 = document.getElementById('color2').value;
    const name = (document.getElementById('teamName').value || "TEAM").toUpperCase();
    const mascotSubject = document.getElementById('mascotSubject').value || "AN AGGRESSIVE MASCOT";
    const customDetail = document.getElementById('customDetail').value;
    const renderStyle = document.getElementById('symbolRenderStyle').value;
    
    let styleDesc = "";
    if(style === 'mascot') styleDesc = "Professional eSports mascot logo, aggressive character design, bold vector art style.";
    else if(style === 'crest') styleDesc = "Aggressive eSports crest emblem, athletic shield branding, symmetrical warrior aesthetic.";
    else if(style === 'monogram') styleDesc = "Futuristic tech lettermark monogram, geometric typography, athletic eSports font.";
    else styleDesc = "Minimalist flat vector eSports badge, clean corporate gaming aesthetic, symmetrical.";

    const fullPrompt = `A high-end professional eSports mascot logo in the style of MPL (Mobile Legends Pro League). ` +
        `The logo features a ${renderStyle.toUpperCase()} ${styleDesc} ` +
        `Main Subject: ${mascotSubject.toUpperCase()} centered in the design${customDetail ? ', ' + customDetail.toUpperCase() : ''}. ` +
        `Typography: The team name "${name}" in a sharp, bold, athletic eSports font integrated into the bottom banner. ` +
        `Frame: Encapsulated within a professional ${shape} crest. ` +
        `Color Scheme: Vibrant ${c1} and ${c2} gradients with high contrast, thick distinct white outlines, and deep black shadows for a pop effect. ` +
        `Technical Specs: Symmetrical composition, flat vector, clean sharp lines, vector illustration, high contrast, 8k resolution, professional gaming branding, white background --no photo, realistic, messy.`;

    const resultJson = {
        "request": "AI Logo Generation",
        "team_branding": {
            "name": name.toUpperCase(),
            "primary_color": c1,
            "secondary_color": c2
        },
        "design_elements": {
            "style": style,
            "shape": shape,
            "mascot": mascotSubject,
            "details": customDetail,
            "rendering_style": renderStyle
        },
        "ai_instructions": {
            "prompt": fullPrompt,
            "negative_prompt": "text, low quality, blurry, distorted, messy lines, watermark, photo-realistic human",
            "aspect_ratio": "1:1"
        }
    };

    document.getElementById('generatedPrompt').innerText = JSON.stringify(resultJson, null, 4);
    document.getElementById('resultContainer').style.display = 'block';
    document.getElementById('resultContainer').scrollIntoView({ behavior: 'smooth' });
}

function copyPrompt() {
    const text = document.getElementById('generatedPrompt').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert("JSON Prompt copied to clipboard!");
    });
}
</script>

<?php render_app_footer(); ?>
</body>
</html>