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
            max-width: 1250px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: 460px 1fr;
            gap: 30px;
            align-items: start;
            text-transform: uppercase;
        }
        .controls-panel {
            background: linear-gradient(165deg, rgba(15, 23, 42, 0.95), rgba(2, 6, 23, 1));
            backdrop-filter: blur(25px);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.8), inset 0 0 20px rgba(56, 189, 248, 0.05);
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
            background: rgba(1, 4, 9, 0.8) !important;
            border: 1px solid rgba(56, 189, 248, 0.2) !important;
            height: 50px !important;
            border-radius: 12px !important;
            padding: 0 20px !important;
            font-family: 'Rajdhani', sans-serif !important;
            font-weight: 700 !important;
            letter-spacing: 1px !important;
            color: #fff !important;
            text-transform: uppercase;
        }
        input::placeholder { color: rgba(255,255,255,0.3); }

        input:focus, select:focus {
            border-color: var(--cyan) !important;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.15) !important;
        }

        .color-inputs { display: flex; gap: 15px; }
        .color-inputs input[type="color"] { flex: 1; height: 50px; cursor: pointer; padding: 5px; }
        
        .section-sub { color: #fff !important; font-weight: 600; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; }

        .result-box {
            background: #010409;
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 16px;
            padding: 40px;
            margin-top: 0;
            position: relative;
            width: 100%;
            min-height: 700px;
            text-align: left;
            box-shadow: inset 0 0 50px rgba(0,0,0,0.8);
            animation: slideUp 0.4s ease-out;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        #generatedPrompt {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.2);
            white-space: pre-wrap;
            word-break: break-all;
            text-transform: uppercase;
        }
        .copy-btn {
            background: var(--cyan);
            color: #020617;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 800;
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
        <div class="hero-label">ESPORTS BRANDING LAB</div>
        <h1 class="hero-title" style="font-family: 'Rajdhani';">MPL STYLE LOGO MAKER</h1>
        <p class="section-sub">DESIGN CUSTOM MASCOTS, MONOGRAMS, AND MINIMALIST BADGES FOR YOUR SQUADS.</p>
    </div>

    <div class="maker-layout">
        <div class="controls-panel">
            <div class="control-group">
                <label>TEAM NAME / MONOGRAM</label>
                <input type="text" id="teamName" value="<?= $prefill_name ?>" placeholder="E.G. MOBA TROPZ" maxlength="20">
            </div>

            <div class="control-group">
                <label>MASCOT / CENTRAL SUBJECT</label>
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
                    <option value="Titan Slayer Berserker with a heavy blood-stained axe">21. TITAN SLAYER BERSERKER</option>
                    <option value="Neon Phantom Speedster with light-trails">22. NEON PHANTOM STRIKER</option>
                    <option value="Solar Flare Lion with a mane of burning sun energy">23. SOLAR FLARE LION</option>
                    <option value="Lunar Eclipse Wolf with glowing moon patterns">24. LUNAR ECLIPSE WOLF</option>
                    <option value="Abyssal Charybdium Monster with crushing whirlpools">25. ABYSSAL CHARYBDIS</option>
                    <option value="Radiant Arch-Angel with massive 6-winged golden feathers">26. RADIANT ARCH-ANGEL</option>
                    <option value="Corrupted Cyber-Demon with rusted plate armor">27. CORRUPTED CYBER-DEMON</option>
                    <option value="Plasma Volt Ram with electric horns">28. PLASMA VOLT RAM</option>
                    <option value="Hyper-Drive Cheetah with blurred mechanical parts">29. HYPER-DRIVE CHEETAH</option>
                    <option value="Ancient Guardian Golem made of mossy temple stones">30. ANCIENT GUARDIAN GOLEM</option>
                    <option value="Zenith Sky Serpent with scales reflecting the clouds">31. ZENITH SKY SERPENT</option>
                    <option value="Omega Warlord Orc with tribal eSports war paint">32. OMEGA WARLORD ORC</option>
                    <option value="Tactical Stealth Panther with infrared goggles">33. TACTICAL STEALTH PANTHER</option>
                    <option value="Molten Magma Core with smoke and heat distortion">34. MOLTEN MAGMA CORE</option>
                    <option value="Glacial Frost Wyvern with freezing aura breath">35. GLACIAL FROST WYVERN</option>
                    <option value="Quantum Time-Wraith flickering between dimensions">36. QUANTUM TIME-WRAITH</option>
                    <option value="Bio-Organic Xenomorph with acid-dripping jaws">37. BIO-ORGANIC XENOMORPH</option>
                    <option value="Iron-Bound Rhino with industrial metal plating">38. IRON-BOUND RHINO</option>
                    <option value="Psychic Mentalist Eye surrounded by levitating cards">39. PSYCHIC MENTALIST EYE</option>
                    <option value="Crystal Shard Golem with translucent glowing interior">40. CRYSTAL SHARD GOLEM</option>
                    <option value="Nuclear Wasteland Mutant with glowing toxic chemicals">41. NUCLEAR WASTELAND MUTANT</option>
                    <option value="Steampunk Brass Aviator with clockwork wings">42. STEAMPUNK BRASS AVIATOR</option>
                    <option value="Divine Justice Themis with a glowing sword and scales">43. DIVINE JUSTICE THEMIS</option>
                    <option value="Primordial Chaos Beast with shifting dark matter form">44. PRIMORDIAL CHAOS BEAST</option>
                    <option value="Dimensional Void Walker stepping through a portal">45. DIMENSIONAL VOID WALKER</option>
                    <option value="Light-Speed Photon Blade slicing through data streams">46. LIGHT-SPEED PHOTON BLADE</option>
                    <option value="Hell-Fire Cerberus with three snarling heads">47. HELL-FIRE CERBERUS</option>
                    <option value="Armored Tusk Mammoth with reinforced steel tusks">48. ARMORED TUSK MAMMOTH</option>
                    <option value="Electric Jellyfish Swarm floating in a digital ocean">49. ELECTRIC JELLYFISH SWARM</option>
                    <option value="Neon Shogun Samurai with a high-frequency katana">50. NEON SHOGUN SAMURAI</option>
                </select>
            </div>

            <div class="control-group">
                <label>STYLE TYPE</label>
                <select id="styleType">
                    <option value="aggressive character design, bold vector mascot logo">1. MPL MASCOT LOGO</option>
                    <option value="athletic crest emblem, pro-league shield branding">2. AGGRESSIVE CREST EMBLEM</option>
                    <option value="futuristic tech lettermark monogram, geometric typography">3. TECH MONOGRAM</option>
                    <option value="minimalist flat vector gaming badge, clean aesthetic">4. MINIMALIST ESPORTS BADGE</option>
                    <option value="neon cybernetic emblem, glowing circuitry lines">5. NEON CYBERNETIC</option>
                    <option value="3D glossy render, high depth and dynamic shadows">6. 3D GLOSSY RENDER</option>
                    <option value="flat vector minimal, simple geometric shapes">7. FLAT VECTOR MINIMAL</option>
                    <option value="vintage retro esports, 90s gaming style, distressed texture">8. VINTAGE RETRO ESPORTS</option>
                    <option value="chrome metallic finish, polished steel reflections">9. CHROME METALLIC</option>
                    <option value="liquid metal flow, organic shiny mercury aesthetic">10. LIQUID METAL</option>
                    <option value="carbon fiber weave pattern, high-tech technical look">11. CARBON FIBER</option>
                    <option value="holographic projected, translucent blue scanlines">12. HOLOGRAPHIC</option>
                    <option value="ancient rune stone, weathered rock carving texture">13. ANCIENT RUNE</option>
                    <option value="bio-luminescent organic glow, internal pulsing light">14. BIO-LUMINESCENT</option>
                    <option value="matte finish tactical, military spec-ops stealth look">15. MATTE TACTICAL</option>
                    <option value="crystalline sharded, fragmented geometric pieces">16. CRYSTALLINE SHARD</option>
                    <option value="fiery elemental burst, burning particle effects">17. FIERY ELEMENTAL</option>
                    <option value="electric voltage spark, crackling lightning bolts">18. ELECTRIC VOLTAGE</option>
                    <option value="ice frosted texture, blue and white sub-zero chill">19. ICE FROSTED</option>
                    <option value="shadow assassin stealth, dark silhouettes and moody lighting">20. SHADOW ASSASSIN</option>
                    <option value="royal gold embossed, 3D luxury gold leaf finish">21. ROYAL GOLD</option>
                    <option value="diamond cut precision, reflective gemstone facets">22. DIAMOND CUT</option>
                    <option value="steampunk brass clockwork, gears and copper pipes">23. STEAMPUNK BRASS</option>
                    <option value="cyberpunk glitch effect, digital corruption artifacts">24. CYBERPUNK GLITCH</option>
                    <option value="tribal war paint, aggressive hand-drawn strokes">25. TRIBAL WAR PAINT</option>
                    <option value="brush stroke calligraphy, artistic ink splash style">26. BRUSH CALLIGRAPHY</option>
                    <option value="geometric abstract, mathematical shape composition">27. GEOMETRIC ABSTRACT</option>
                    <option value="low-poly digital, triangulated mesh structure">28. LOW-POLY DIGITAL</option>
                    <option value="pixel art retro, 8-bit console aesthetic">29. PIXEL ART RETRO</option>
                    <option value="spray graffiti, street art tags and paint drips">30. SPRAY GRAFFITI</option>
                    <option value="oil painting texture, classical canvas art style">31. OIL PAINT TEXTURE</option>
                    <option value="comic book pop art, halftone dot patterns">32. COMIC POP ART</option>
                    <option value="wood carved rustic, natural organic grain texture">33. WOOD CARVED</option>
                    <option value="glass etched translucent, frosted transparent look">34. GLASS ETCHED</option>
                    <option value="plasma energy core, swirling mystical light focus">35. PLASMA ENERGY</option>
                    <option value="magma volcanic cracked, glowing lava between stones">36. MAGMA VOLCANIC</option>
                    <option value="celestial galaxy nebula, deep space color gradients">37. CELESTIAL GALAXY</option>
                    <option value="kinetic motion blur, speed lines and action streaks">38. KINETIC MOTION</option>
                    <option value="asymmetrical modern design, sharp unbalanced edges">39. ASYMMETRICAL MODERN</option>
                    <option value="symmetrical elite balance, perfectly mirrored sides">40. SYMMETRICAL ELITE</option>
                    <option value="monochrome noir, high-contrast black and white only">41. MONOCHROME NOIR</option>
                    <option value="pastel soft esports, modern light-themed aesthetic">42. PASTEL SOFT</option>
                    <option value="iridescent pearl, color-shifting oil-slick finish">43. IRIDESCENT PEARL</option>
                    <option value="techwear strap style, buckles and industrial fabric">44. TECHWEAR STRAP</option>
                    <option value="nano-tech particle, microscopic digital dust effects">45. NANO-TECH</option>
                    <option value="titanium reinforced, heavy-duty metallic plating">46. TITANIUM REINFORCED</option>
                    <option value="silk embroidered, fine thread fabric texture">47. SILK EMBROIDERED</option>
                    <option value="marble sculpted, white polished stone finish">48. MARBLE SCULPTED</option>
                    <option value="smoke and mist vapor, soft ethereal edges">49. SMOKE VAPOR</option>
                    <option value="infinity loop recursive, repeating fractal patterns">50. INFINITY LOOP</option>
                </select>
            </div>

            <div class="control-group">
                <label>SYMBOL RENDERING STYLE</label>
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
                    <option value="polished chrome metallic finish">21. POLISHED CHROME</option>
                    <option value="brushed aluminum industrial texture">22. BRUSHED ALUMINUM</option>
                    <option value="matte carbon fiber weave">23. MATTE CARBON FIBER</option>
                    <option value="glossy plastic toy aesthetic">24. GLOSSY PLASTIC</option>
                    <option value="hand-painted watercolor wash">25. HAND-PAINTED WATERCOLOR</option>
                    <option value="ink splatter chaotic art">26. INK SPLATTER ART</option>
                    <option value="chalkboard sketch rough lines">27. CHALKBOARD SKETCH</option>
                    <option value="embroidered fabric patch style">28. EMBROIDERED PATCH</option>
                    <option value="enamel pin polished shine">29. ENAMEL PIN SHINE</option>
                    <option value="neon tube gas lighting">30. NEON TUBE LIGHTING</option>
                    <option value="digital data pulse scanlines">31. DIGITAL DATA PULSE</option>
                    <option value="low-poly 3D mesh render">32. LOW-POLY MESH</option>
                    <option value="voxel cubic digital structure">33. VOXEL CUBIC STYLE</option>
                    <option value="8-bit retro pixelated console">34. 8-BIT RETRO PIXEL</option>
                    <option value="comic book halftone dot pattern">35. COMIC BOOK HALFTONE</option>
                    <option value="vintage poster distressed paper">36. VINTAGE DISTRESSED</option>
                    <option value="liquid mercury reflective flow">37. LIQUID MERCURY FLOW</option>
                    <option value="crystalline gemstone faceted cut">38. CRYSTALLINE GEMSTONE</option>
                    <option value="frosted etched glass translucent">39. FROSTED ETCHED GLASS</option>
                    <option value="rustic forged pig iron">40. RUSTIC FORGED IRON</option>
                    <option value="weathered ancient mossy stone">41. WEATHERED ANCIENT STONE</option>
                    <option value="bio-luminescent pulsing organic glow">42. BIO-LUMINESCENT GLOW</option>
                    <option value="holographic projection with glitches">43. HOLOGRAPHIC GLITCH</option>
                    <option value="heavy oil paint impasto brush">44. OIL PAINT CANVAS</option>
                    <option value="urban street spray paint graffiti">45. SPRAY PAINT GRAFFITI</option>
                    <option value="cel-shaded high-contrast anime">46. CEL-SHADED ANIME</option>
                    <option value="cinematic volumetric thick smoke">47. VOLUMETRIC SMOKE</option>
                    <option value="plasma energy swirling surge">48. PLASMA ENERGY SURGE</option>
                    <option value="magma cracked volcanic earth">49. MAGMA CRACKED EARTH</option>
                    <option value="galaxy nebula cosmic gradient">50. GALAXY NEBULA</option>
                </select>
            </div>

            <div class="control-group">
                <label>BRANDING COLOR PALETTE</label>
                <select id="colorPalette">
                    <option value="CYAN, PURPLE, AND VIBRANT PINK">1. CYBER NEON (CYAN, PURPLE, PINK)</option>
                    <option value="ROYAL GOLD, OBSIDIAN BLACK, AND PEARL WHITE">2. ROYAL EMPIRE (GOLD, BLACK, WHITE)</option>
                    <option value="TOXIC NEON GREEN, CARBON GREY, AND HAZARD YELLOW">3. TOXIC HAZARD (GREEN, GREY, YELLOW)</option>
                    <option value="DEEP CRIMSON RED, CHROME SILVER, AND JET BLACK">4. CRIMSON FURY (RED, SILVER, BLACK)</option>
                    <option value="ICE BLUE, MIDNIGHT NAVY, AND FROST WHITE">5. MIDNIGHT FROST (ICE, NAVY, WHITE)</option>
                    <option value="SUNSET ORANGE, DEEP PURPLE, AND BURNING RED">6. SUNSET STRIKE (ORANGE, PURPLE, RED)</option>
                    <option value="EMERALD GREEN, RADIANT GOLD, AND PITCH BLACK">7. EMERALD ELITE (GREEN, GOLD, BLACK)</option>
                    <option value="BRONZE METALLIC, ANTIQUE CREAM, AND NAVY BLUE">8. BRONZE WARRIOR (BRONZE, CREAM, NAVY)</option>
                    <option value="AQUA TEAL, POLISHED SILVER, AND PURE WHITE">9. AQUA MARINE (TEAL, SILVER, WHITE)</option>
                    <option value="MOSS GREEN, EARTHEN BROWN, AND DESERT BEIGE">10. FOREST SHADOW (GREEN, BROWN, BEIGE)</option>
                    <option value="HOT NEON PINK, ELECTRIC BLUE, AND ACID YELLOW">11. HOT NEON (PINK, BLUE, YELLOW)</option>
                    <option value="MATTE ANTHRACITE, BRUSHED SILVER, AND PURE BLACK">12. CHARCOAL STEEL (GREY, SILVER, BLACK)</option>
                    <option value="OLIVE DRAB, DESERT KHAKI, AND BURNT ORANGE">13. OLIVE COMMANDER (OLIVE, KHAKI, ORANGE)</option>
                    <option value="DEEP PLUM, SOFT LAVENDER, AND ANTIQUE GOLD">14. PLUM ROYALTY (PLUM, LAVENDER, GOLD)</option>
                    <option value="VIBRANT TURQUOISE, FRESH MINT, AND DARK GRAPHITE">15. MINT TECH (TURQUOISE, MINT, GREY)</option>
                    <option value="DARK MAROON, SOFT TAN, AND OBSIDIAN BLACK">16. MAROON TITAN (MAROON, TAN, BLACK)</option>
                    <option value="SOFT PEACH, CLEAR SKY BLUE, AND CLOUD WHITE">17. PEACH SKY (PEACH, BLUE, WHITE)</option>
                    <option value="BOLD MUSTARD, DEEP NAVY, AND DARK BURGUNDY">18. MUSTARD NAVY (MUSTARD, NAVY, BURGUNDY)</option>
                    <option value="PERIWINKLE BLUE, SAGE GREEN, AND VINTAGE BRONZE">19. PERIWINKLE SAGE (PERIWINKLE, SAGE, BRONZE)</option>
                    <option value="DEEP AMETHYST PURPLE, REGAL GOLD, AND CRIMSON">20. DEEP ABYSS (PURPLE, GOLD, CRIMSON)</option>
                    <option value="ELECTRIC BLUE, BRIGHT WHITE, AND NEON ORANGE">21. VOLTAGE (BLUE, WHITE, ORANGE)</option>
                    <option value="JET BLACK, DARK BLOOD RED, AND REFLECTIVE CHROME">22. CHROME BLOOD (BLACK, RED, CHROME)</option>
                    <option value="RAINBOW IRIDESCENT, GLOWING WHITE, AND SILVER">23. IRIDESCENT (RAINBOW, WHITE, SILVER)</option>
                    <option value="MATRIX GREEN, CODE BLACK, AND NEON LIME">24. MATRIX (GREEN, BLACK, LIME)</option>
                    <option value="AURORA GREEN, NORTHERN BLUE, AND DEEP VIOLET">25. AURORA (GREEN, BLUE, VIOLET)</option>
                    <option value="FIRE RED, ICE CYAN, AND FROSTED WHITE">26. FIRE AND ICE (RED, CYAN, WHITE)</option>
                    <option value="POISON PURPLE, TOXIC GREEN, AND PITCH BLACK">27. POISON (PURPLE, GREEN, BLACK)</option>
                    <option value="MATTE BLACK, DARK SLATE GREY, AND ANTHRACITE">28. STEALTH (BLACK, GREY, ANTHRACITE)</option>
                    <option value="LIQUID GOLD, CHAMPAGNE BEIGE, AND LUXURY BLACK">29. CHAMPAGNE (GOLD, BEIGE, BLACK)</option>
                    <option value="BUBBLEGUM PINK, COTTON CANDY BLUE, AND LEMON YELLOW">30. BUBBLEGUM (PINK, BLUE, YELLOW)</option>
                    <option value="GLITCH RED, SYSTEM BLUE, AND DATA WHITE">31. GLITCH (RED, BLUE, WHITE)</option>
                    <option value="GOLDEN HOUR YELLOW, SUNSET ORANGE, AND DEEP BROWN">32. GOLDEN HOUR (GOLD, ORANGE, BROWN)</option>
                    <option value="LAVA RED, VOLCANIC ASH GREY, AND SULFUR YELLOW">33. VOLCANIC (RED, GREY, YELLOW)</option>
                    <option value="MOSS GREEN, MUD BROWN, AND ELECTRIC LIME">34. JUNGLE TRACKER (GREEN, BROWN, LIME)</option>
                    <option value="DEEP SEA NAVY, OCEAN CORAL, AND BRIGHT TEAL">35. DEEP SEA (NAVY, CORAL, TEAL)</option>
                    <option value="DEEP SPACE PURPLE, VIBRANT MAGENTA, AND STAR WHITE">36. NEBULA (PURPLE, MAGENTA, WHITE)</option>
                    <option value="TAN DESERT, SAND YELLOW, AND DUSTY ORANGE">37. SANDSTORM (TAN, YELLOW, ORANGE)</option>
                    <option value="RUST ORANGE, INDUSTRIAL STEEL, AND BOLT GREY">38. INDUSTRIAL (RUST, STEEL, GREY)</option>
                    <option value="PASTEL PINK, RETRO TEAL, AND SYNTH PURPLE">39. VAPORWAVE (PINK, TEAL, PURPLE)</option>
                    <option value="CAMO GREEN, TACTICAL TAN, AND STEALTH BLACK">40. MILITARY (GREEN, TAN, BLACK)</option>
                    <option value="COMIC RED, HEROIC BLUE, AND BOLD YELLOW">41. SUPERHERO (RED, BLUE, YELLOW)</option>
                    <option value="VILLAIN VIOLET, JET BLACK, AND NEON GREEN">42. VILLAIN (VIOLET, BLACK, GREEN)</option>
                    <option value="BRIGHT CYAN, POLISHED SILVER, AND MIRROR WHITE">43. CHALLENGER (CYAN, SILVER, WHITE)</option>
                    <option value="BAMBOO GREEN, STONE GREY, AND CALM WATER BLUE">44. ZEN (GREEN, GREY, BLUE)</option>
                    <option value="PIXEL RED, ARCADE BLUE, AND CONSOLE BLACK">45. RETRO ARCADE (RED, BLUE, BLACK)</option>
                    <option value="BRUSHED TITANIUM, DARK GREY, AND CYBER CYAN">46. TITANIUM (TITANIUM, GREY, CYAN)</option>
                    <option value="SUN YELLOW, DEEP SOLAR ORANGE, AND BRIGHT WHITE">47. SOLAR FLARE (YELLOW, ORANGE, WHITE)</option>
                    <option value="MOON WHITE, DARK LUNAR GREY, AND MIDNIGHT NAVY">48. LUNAR ECLIPSE (WHITE, GREY, NAVY)</option>
                    <option value="DEEP BLOOD RED, ECLIPSE BLACK, AND DARK CRIMSON">49. BLOOD MOON (RED, BLACK, CRIMSON)</option>
                    <option value="PURE WHITE, BRILLIANT SILVER, AND HOLOGRAPHIC BLUE">50. INFINITY (WHITE, SILVER, BLUE)</option>
                </select>
            </div>

            <div class="control-group">
                <label>MANUAL PROMPT ADD-ON (OPTIONAL)</label>
                <input type="text" id="customDetail" placeholder="E.G. HOLDING A WEAPON, ELECTRIC AURAS">
            </div>

            <button class="btn" style="width:100%; height:55px; margin-top:20px; font-size:14px;" onclick="generateAIPrompt()">GENERATE PROMPT</button>
        </div>

        <div class="result-box" id="resultContainer">
            <div id="resultHeader" style="display:none; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid rgba(56,189,248,0.2);">
                <span style="color:var(--cyan); font-weight:900; font-size:11px; letter-spacing:2px; font-family:'Rajdhani';">SYSTEM_OUTPUT // JSON_PROMPT</span>
                <button class="copy-btn" onclick="copyPrompt()">COPY_JSON</button>
            </div>
            <p id="generatedPrompt" style="text-align:center; padding-top:150px;">// SYSTEM STANDBY: AWAITING PARAMETERS //</p>
        </div>
    </div>
</div>

<script>
function generateAIPrompt() {
    const style = document.getElementById('styleType').value;
    const shape = "shield"; // Fixed to Shield for MPL style after removal of selector
    const palette = document.getElementById('colorPalette').value;
    const name = (document.getElementById('teamName').value || "TEAM").toUpperCase();
    const mascotSubject = document.getElementById('mascotSubject').value || "AN AGGRESSIVE MASCOT";
    const customDetail = document.getElementById('customDetail').value;
    const renderStyle = document.getElementById('symbolRenderStyle').value;
    
    const styleDesc = `Professional ${style}.`;

    const fullPrompt = `A high-end professional eSports logo in the style of MPL (Mobile Legends Pro League). ` +
        `The logo features a ${renderStyle.toUpperCase()} finish with ${styleDesc} ` +
        `Main Subject: ${mascotSubject.toUpperCase()} centered in the design${customDetail ? ', ' + customDetail.toUpperCase() : ''}. ` +
        `Typography: The team name "${name}" in a sharp, bold, athletic eSports font integrated into the bottom banner. ` +
        `Frame: Encapsulated within a professional ${shape} crest. ` +
        `Color Scheme: A professional 3-color palette of ${palette} with high contrast gradients, thick distinct white outlines, and deep black shadows for a pop effect. ` +
        `Technical Specs: Symmetrical composition, flat vector, clean sharp lines, vector illustration, high contrast, 8k resolution, professional gaming branding, white background --no photo, realistic, messy.`;

    const resultJson = {
        "request": "AI Logo Generation",
        "team_branding": {
            "name": name.toUpperCase(),
            "palette": palette
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

    const promptEl = document.getElementById('generatedPrompt');
    promptEl.innerText = JSON.stringify(resultJson, null, 4);
    promptEl.style.color = "#fff";
    promptEl.style.textAlign = "left";
    promptEl.style.paddingTop = "0";
    
    document.getElementById('resultHeader').style.display = 'flex';
    document.getElementById('resultContainer').style.borderColor = 'var(--cyan)';
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