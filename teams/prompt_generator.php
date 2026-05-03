<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");

// Fetch Tournament Name for Poster Ready Feature
$tournament_id = $_SESSION['active_tournament'] ?? 0;
$t_res = $conn->query("SELECT name FROM tournaments WHERE id = " . intval($tournament_id));
$t_info = $t_res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Creator — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        body {
            background: #020617;
            background-image: 
                linear-gradient(rgba(2, 6, 23, 0.75), rgba(2, 6, 23, 0.85)),
                url('https://images5.alphacoders.com/105/1059432.jpg'); /* Hero Selection vibe background */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            padding-bottom: 50px;
            position: relative;
            overflow-x: hidden;
        }
        /* MLBB Overlay Effect */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                repeating-linear-gradient(0deg, rgba(0,0,0,0.15) 0px, transparent 1px, transparent 2px),
                repeating-linear-gradient(90deg, rgba(56, 189, 248, 0.02) 0px, transparent 1px, transparent 40px);
            background-size: 100% 3px, 40px 100%;
            pointer-events: none;
            z-index: 0;
        }
        /* Live Preview Styles */
        .preview-stage {
            background: radial-gradient(circle at center, rgba(56, 189, 248, 0.1) 0%, rgba(2, 6, 23, 0.9) 100%);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 20px;
            height: 300px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .mockup-player {
            width: 140px;
            height: 220px;
            background: rgba(255,255,255,0.05);
            border: 2px solid var(--cyan);
            border-radius: 10px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 10px;
            transition: 0.5s;
        }
        .mockup-ign { font-family: 'Rajdhani'; font-weight: 800; font-size: 14px; color: #fff; margin-top: 40px; }
        .mockup-team { font-size: 8px; color: var(--gold); position: absolute; bottom: 20px; text-transform: uppercase; }
        .mockup-logo { width: 30px; height: 30px; border: 1px dashed var(--cyan); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 6px; }
        
        .poster-overlay {
            position: absolute;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-family: 'Rajdhani';
            font-weight: 700;
            color: var(--cyan);
            letter-spacing: 2px;
            font-size: 18px;
            opacity: 0.5;
        }

        .wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .prompt-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: var(--radius);
            padding: 40px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .option-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .form-group label {
            color: var(--cyan);
            font-family: 'Rajdhani', sans-serif;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            display: block;
            margin-bottom: 10px;
        }
        select, input {
            width: 100%;
            padding: 14px;
            background: rgba(2, 6, 23, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            transition: 0.3s;
        }
        select:focus, input:focus {
            outline: none;
            border-color: var(--cyan);
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
        }
        .result-box {
            background: rgba(2, 6, 23, 0.9);
            border: 2px dashed rgba(56, 189, 248, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-top: 40px;
            position: relative;
            animation: fadeIn 0.5s ease-out;
        }
        #generatedPrompt {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            color: #f1f5f9;
            margin: 0;
            padding-right: 80px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .copy-btn {
            position: absolute;
            top: 25px;
            right: 25px;
            background: var(--cyan);
            color: #020617;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.2s;
        }
        .copy-btn:hover { filter: brightness(1.2); transform: scale(1.05); }
        .logo-preview-box {
            width: 60px; height: 60px; background: rgba(2, 6, 23, 0.8);
            border: 1px dashed var(--cyan); border-radius: 12px; margin-bottom: 15px;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .logo-preview-box img { width: 100%; height: 100%; object-fit: contain; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
<?php render_app_header('ai-gen'); ?>

<div class="wrapper">
    <div class="hero" style="text-align: center; background: rgba(15, 23, 42, 0.5);">
        <div class="hero-label">eSports Profile Asset Creator</div>
        <h1 class="hero-title" style="font-family: 'Rajdhani';">PRO PLAYER PROFILE CREATOR</h1>
        <p class="section-sub">Generate high-quality prompts to create professional player photos using AI tools like Midjourney or DALL-E 3.</p>
    </div>

    <div class="prompt-card">
        <!-- LIVE PREVIEW BOX -->
        <div class="form-group"><label>Live Design Mockup</label></div>
        <div class="preview-stage" id="previewStage">
            <div class="mockup-player" id="mockupBody">
                <div class="mockup-logo" id="mockupLogoCircle">LOGO</div>
                <div class="mockup-ign" id="mockupIGNText">IGN</div>
                <div class="mockup-team" id="mockupTeamText">TEAM NAME</div>
            </div>
            <div class="poster-overlay"><?= strtoupper($t_info['name'] ?? 'TROPZ LEAGUE') ?></div>
        </div>

        <div class="option-grid">
            <div class="form-group">
                <label>View Type</label>
                <select id="view_type" onchange="updateMockup()">
                    <option value="all views">All Views (3-Way Multi-View)</option>
                    <option value="front view">Front View (Chest Focus)</option>
                    <option value="back view">Back View (IGN Focus)</option>
                    <option value="left side profile">Left Side Profile</option>
                    <option value="right side profile">Right Side Profile</option>
                    <option value="cinematic low angle">Cinematic Low Angle</option>
                </select>
            </div>
            <div class="form-group">
                <label>Pro Player Pose</label>
                <select id="pose">
                    <option value="standing with arms crossed and a confident smirk">1. Confident Stance (Arms Crossed)</option>
                    <option value="sitting in a high-end gaming chair, focused on a smartphone screen">2. In-Game Focus (Smartphone)</option>
                    <option value="aggressive battle stance, one hand on hip, cinematic focus">5. Battle Ready Stance</option>
                    <option value="adjusting gaming glasses with a sharp look">6. Adjusting Glasses</option>
                    <option value="leaning against a neon-lit wall, cool expression">7. Leaning Against Neon Wall</option>
                    <option value="holding a team flag over shoulders">8. Team Flag Cape</option>
                    <option value="dynamic walking towards the camera, motion blur background">9. Dynamic Walk</option>
                    <option value="sitting on the edge of eSports stage, looking at hands">10. Pre-game Meditation</option>
                    <option value="pointing finger at the camera with a playful wink">11. Pointing at Camera</option>
                    <option value="looking over the shoulder with a serious gaze">12. Over-the-shoulder Look</option>
                    <option value="hands in hoodie pockets, street-style gaming look">13. Hands in Pockets</option>
                    <option value="holding a smartphone horizontally with dual cooling fans attached">14. Pro Gaming Grip</option>
                    <option value="clenched fist over heart, pledge of loyalty">15. Heart Pledge</option>
                    <option value="shushing the crowd with a finger to the lips">16. The 'Silence' Gesture</option>
                    <option value="sitting cross-legged on the floor, chill gamer vibe">17. Chill Floor Sit</option>
                    <option value="giving a thumbs up to the crowd">18. Pro Thumbs Up</option>
                    <option value="holding a gaming headset to one ear, listening intensely">19. Comm Check Pose</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subjects (Takes)</label>
                <select id="takes">
                    <option value="single player">Single Player (1 Take)</option>
                    <option value="duo players, back to back">Duo (2 Takes)</option>
                    <option value="trio of players in a triangle formation">Trio (3 Takes)</option>
                    <option value="squad of 4 players">Squad (4 Takes)</option>
                    <option value="full team of 5 players in a cinematic lineup">Full Team (5 Takes)</option>
                    <option value="large group of 10 eSports players, championship celebration">Grand Finale (10 Takes)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Aspect Ratio</label>
                <select id="aspect_ratio">
                    <option value="--ar 3:4">3:4 (Portrait/Mobile)</option>
                    <option value="--ar 9:16">9:16 (TikTok/Reels)</option>
                    <option value="--ar 16:9">16:9 (Widescreen/YouTube)</option>
                    <option value="--ar 1:1">1:1 (Square/Post)</option>
                    <option value="--ar 4:5">4:5 (Instagram Portrait)</option>
                    <option value="--ar 2:3">2:3 (Poster Standard)</option>
                </select>
            </div>
        </div>

        <div class="option-grid">
            <div class="form-group">
                <label>Jersey Concept</label>
                <select id="jersey">
                    <option value="sleek futuristic techwear jersey with glowing LED circuits">1. Futuristic Techwear (Glow)</option>
                    <option value="minimalist modern eSports jersey with geometric matte patterns">2. Minimalist Geometric</option>
                    <option value="classic breathable athletic mesh jersey with team patches">3. Classic Pro Athlete Mesh</option>
                    <option value="cyberpunk street-style gaming hoodie with team branding">4. Cyberpunk Hoodie</option>
                    <option value="carbon fiber textured compression jersey">5. Carbon Fiber Compression</option>
                    <option value="gold-embossed premium silk gaming jersey">6. Gold-Embossed Silk</option>
                    <option value="holographic reflective material jersey">7. Holographic Reflective</option>
                    <option value="sleeveless combat-style gaming jersey">8. Sleeveless Combat</option>
                    <option value="asymmetrical split-color design jersey">9. Asymmetrical Split</option>
                    <option value="retro 90s vintage eSports windbreaker">10. Retro Windbreaker</option>
                    <option value="high-collar tactical gaming suit">11. High-Collar Tactical</option>
                    <option value="liquid-metal finish metallic jersey">12. Liquid Metal Metallic</option>
                    <option value="digital camouflage pattern eSports jersey">13. Digital Camo</option>
                    <option value="honeycomb weave breathable pro jersey">14. Honeycomb Weave</option>
                    <option value="bio-luminescent organic fiber jersey">15. Bio-Luminescent</option>
                    <option value="velvet texture royal gaming robe-style jersey">16. Royal Velvet Jersey</option>
                    <option value="oversized urban streetwear gaming jersey">17. Urban Oversized</option>
                    <option value="neon-piped nocturnal gaming jersey">18. Neon-Piped Black</option>
                    <option value="gradient-fused aero-dry jersey">19. Aero-Dry Gradient</option>
                    <option value="cyber-samurai armored plated jersey">20. Armored Cyber-Samurai</option>
                </select>
            </div>
            <div class="form-group">
                <label>Theme Colors</label>
                <select id="colors">
                    <option value="Cyan, Gold, and Obsidian Black">1. Cyan, Gold, Black</option>
                    <option value="Electric Purple, Magenta, and Deep Space Grey">2. Purple, Magenta, Grey</option>
                    <option value="Neon Green, Carbon Grey, and White">3. Green, Grey, White</option>
                    <option value="Deep Crimson, Metallic Silver, and Jet Black">4. Crimson, Silver, Black</option>
                    <option value="Royal Blue, Bright Orange, and Charcoal">5. Blue, Orange, Charcoal</option>
                    <option value="Rose Gold, Pastel Pink, and Dark Slate">6. Rose Gold, Pink, Slate</option>
                    <option value="Acid Yellow, Toxic Green, and Pitch Black">7. Yellow, Green, Black</option>
                    <option value="Midnight Navy, Sky Blue, and Pearl White">8. Navy, Sky, White</option>
                    <option value="Teal, Copper, and Off-White">9. Teal, Copper, Off-White</option>
                    <option value="Emerald Green, Gold, and Midnight Black">10. Emerald, Gold, Black</option>
                    <option value="Ruby Red, Onyx Black, and Platinum Silver">11. Ruby, Onyx, Platinum</option>
                    <option value="Sapphire Blue, Frost White, and Graphite Grey">12. Sapphire, White, Grey</option>
                    <option value="Sunset Orange, Red-Violet, and Dark Purple">13. Sunset, Violet, Purple</option>
                    <option value="Military Tan, Sand, and Olive Drab">14. Tan, Sand, Olive</option>
                    <option value="Cyberpunk Pink, Electric Blue, and Ultraviolet">15. Pink, Blue, UV</option>
                    <option value="Monochrome Black, Matte Grey, and Glossy White">16. Black, Grey, White</option>
                    <option value="Frost Blue, Ice White, and Steel Grey">17. Ice Blue, White, Steel</option>
                    <option value="Burgundy, Bronze, and Cream">18. Burgundy, Bronze, Cream</option>
                    <option value="Lava Orange, Ash Grey, and Sulfur Yellow">19. Lava, Ash, Yellow</option>
                    <option value="Deep Sea Teal, Coral, and Dark Navy">20. Teal, Coral, Navy</option>
                </select>
            </div>
            <div class="form-group">
                <label>Backdrop / Scenery</label>
                <select id="env">
                    <option value="blurred pro eSports arena stage, bright spotlights and neon atmosphere">1. Tournament Arena Stage</option>
                    <option value="a dark futuristic high-tech training room with cinematic rim lighting">2. Cyber Gaming Studio</option>
                    <option value="fantasy battlefield scenery with magical energy swirls">3. Fantasy Battlefield</option>
                    <option value="clean professional studio background with soft color gradient">4. Clean Pro Studio</option>
                    <option value="cyberpunk city rooftop overlooking a neon metropolis at night">5. Cyberpunk Rooftop</option>
                    <option value="ancient temple ruins in the Land of Dawn with floating crystals">6. Ancient Ruins</option>
                    <option value="floating island in the sky with magical waterfalls">7. Floating Sky Island</option>
                    <option value="digital void with binary codes and data streams floating around">8. Digital Data Void</option>
                    <option value="mist-covered enchanted forest with glowing flora">9. Enchanted Forest</option>
                    <option value="sci-fi spaceship bridge with planetary view from windows">10. Spaceship Bridge</option>
                    <option value="zen garden with cherry blossoms and traditional bridges">11. Zen Garden</option>
                    <option value="undersea research base with giant sea creatures outside the glass">12. Undersea Base</option>
                    <option value="abandoned industrial warehouse with graffiti and mood lighting">13. Industrial Warehouse</option>
                    <option value="volcanic crag with flowing lava and intense orange glow">14. Volcanic Crag</option>
                    <option value="rainy street crossing in futuristic Tokyo style">15. Rainy Cyber City</option>
                    <option value="golden hour beach sunset with palm tree silhouettes">16. Golden Hour Beach</option>
                    <option value="high-speed maglev train interior with motion-blurred scenery">17. Maglev Train</option>
                    <option value="steampunk workshop with brass gears and steam pipes">18. Steampunk Workshop</option>
                    <option value="minimalist white infinite void with soft geometric shadows">19. Infinite White Studio</option>
                    <option value="crystal cave with gigantic amethyst formations">20. Crystal Amethyst Cave</option>
                </select>
            </div>
        </div>
        <div class="option-grid">
            <div class="form-group">
                <label>Player IGN</label>
                <input type="text" id="player_ign" placeholder="e.g. TROPZ" onkeyup="updateMockup()" required>
            </div>
            <div class="form-group">
                <label>Team Name</label>
                <input type="text" id="team_name" placeholder="e.g. MOBA TROPZ eSports" onkeyup="updateMockup()" required>
            </div>
        </div>
        <div class="form-group">
            <label>Team Logo (Upload for Preview)</label>
            <div class="logo-preview-box" id="logoPreview">
                <span style="font-size: 10px; color: var(--muted);">NO LOGO</span>
            </div>
            <input type="file" id="logo_file" accept="image/*" onchange="previewLogo(event)">
        </div>
        <button class="app-action primary" style="width:100%; height:55px; margin-top:25px; font-size:14px;" onclick="generatePrompt()">
            GENERATE JSON AI PROMPT
        </button>
        <div class="result-box" id="resultContainer" style="display:none;">
            <button class="copy-btn" onclick="copyPrompt()">Copy JSON</button>
            <p id="generatedPrompt"></p>
            <div style="margin-top:20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top:15px;">
                <span style="color:var(--gold); font-size:11px; font-weight:800; text-transform:uppercase;">Pro Tip:</span>
                <span style="color:var(--muted); font-size:11px;"> Paste this JSON into Midjourney, ChatGPT, or DALL-E 3 for multi-view generation.</span>
            </div>
        </div>
    </div>
</div>

<script>
function previewLogo(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('logoPreview');
        const mockupLogo = document.getElementById('mockupLogoCircle');
        output.innerHTML = `<img src="${reader.result}">`;
        mockupLogo.innerHTML = `<img src="${reader.result}" style="width:100%; border-radius:50%;">`;
    };
    reader.readAsDataURL(event.target.files[0]);
}

function updateMockup() {
    const view = document.getElementById('view_type').value;
    const ign = document.getElementById('player_ign').value || "IGN";
    const team = document.getElementById('team_name').value || "TEAM NAME";
    const mockupBody = document.getElementById('mockupBody');
    const mockupLogo = document.getElementById('mockupLogoCircle');
    const mockupIGN = document.getElementById('mockupIGNText');
    
    mockupIGN.innerText = ign.toUpperCase();
    document.getElementById('mockupTeamText').innerText = team.toUpperCase();

    if(view === 'back view') {
        mockupBody.style.transform = "rotateY(180deg)";
        mockupIGN.style.transform = "rotateY(180deg)";
        mockupIGN.style.marginTop = "30px";
        mockupLogo.style.display = "none";
    } else {
        mockupBody.style.transform = "rotateY(0deg)";
        mockupIGN.style.transform = "rotateY(0deg)";
        mockupIGN.style.marginTop = "40px";
        mockupLogo.style.display = "flex";
    }
}

function generatePrompt() {
    const pose = document.getElementById('pose').value;
    const jersey = document.getElementById('jersey').value;
    const colors = document.getElementById('colors').value;
    const env = document.getElementById('env').value;
    const ign = document.getElementById('player_ign').value || "PLAYER";
    const team = document.getElementById('team_name').value || "TEAM NAME";
    const view = document.getElementById('view_type').value;
    const takes = document.getElementById('takes').value;
    const ar = document.getElementById('aspect_ratio').value;
    const t_name = "<?= $t_info['name'] ?? 'TROPZ LEAGUE' ?>";

    const logoFile = document.getElementById('logo_file');
    const hasLogo = logoFile && logoFile.files && logoFile.files.length > 0;
    const logoType = hasLogo ? "professional custom logo" : `minimalist monogram logo design based on "${team}"`;

    // Conditional Logic for Views
    let viewInstructions = "";
    let designSpecs = {};

    const frontDetail = `Team logo (${logoType}) and "${team}" prominently displayed on the left side of the chest (heart side)`;
    const backDetail = `Player IGN "${ign}" printed in large, bold, professional eSports typography across the upper back`;
    const sideDetail = `A smaller version of the Team Name and ${logoType} placed on the sleeves`;

    if (view === 'all views') {
        viewInstructions = `The image must show a three-way multi-view character design: 1. FRONT VIEW: ${frontDetail}. 2. BACK VIEW: ${backDetail}. 3. SIDE VIEW: ${sideDetail}.`;
        designSpecs = {
            "front": `Team name and ${hasLogo ? 'logo' : 'monogram'} on heart side`,
            "back": "Player IGN in bold typography",
            "side": `Mini team name/${hasLogo ? 'logo' : 'monogram'} on sleeves`
        };
    } else {
        viewInstructions = `The image shows a ${view}. `;
        if (view === 'front view') {
            viewInstructions += `Design Focus: ${frontDetail}.`;
            designSpecs = { "view": "Front", "detail": frontDetail };
        } else if (view === 'back view') {
            viewInstructions += `Design Focus: ${backDetail}.`;
            designSpecs = { "view": "Back", "detail": backDetail };
        } else if (view.includes('side')) {
            viewInstructions += `Design Focus: ${sideDetail}.`;
            designSpecs = { "view": "Side", "detail": sideDetail };
        } else {
            viewInstructions += `Cinematic focus with team branding elements.`;
            designSpecs = { "view": "Special", "detail": "Cinematic focus" };
        }
    }

    const ai_prompt_text = `A high-resolution professional eSports ${takes} portrait poster for "${t_name}", character ${pose}. ` + 
        `${viewInstructions} The players are wearing ${jersey} in ${colors} theme. ` +
        `Background: ${env}. ` +
        `Poster-ready layout, cinematic lighting, 8k, hyper-realistic, highly detailed texture, eSports arena commercial vibe ${ar}`;

    const resultJson = {
        "request_type": "Pro eSports Player Art",
        "player_details": {
            "ign": ign,
            "team": team
        },
        "visual_config": {
            "pose": pose,
            "jersey_style": jersey,
            "palette": colors,
            "environment": env
        },
        "design_specs": designSpecs,
        "final_ai_prompt": ai_prompt_text
    };

    document.getElementById('generatedPrompt').innerText = JSON.stringify(resultJson, null, 4);
    document.getElementById('resultContainer').style.display = 'block';
    document.getElementById('resultContainer').scrollIntoView({ behavior: 'smooth' });
}
function copyPrompt() {
    const text = document.getElementById('generatedPrompt').innerText;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.copy-btn');
        const oldText = btn.innerText;
        btn.innerText = 'COPIED!';
        btn.style.background = '#4ade80';
        setTimeout(() => {
            btn.innerText = oldText;
            btn.style.background = 'var(--cyan)';
        }, 2000);
    });
}
</script>
<?php render_app_footer(); ?>
</body>