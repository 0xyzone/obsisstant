import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.186.0/build/three.module.js';
import { RoomEnvironment } from 'https://cdn.jsdelivr.net/npm/three@0.186.0/examples/jsm/environments/RoomEnvironment.js';

const REACTIONS = ['wave', 'spin', 'cheer', 'dance', 'peek'];

const REACTION_LINES = {
    wave: 'Hey there! I am Lumi, your stream buddy.',
    spin: 'Wheee! Everything stays synced in real time.',
    cheer: 'Looking good! Ready whenever you go live.',
    dance: 'Vibing with your broadcast setup!',
    peek: 'Peek-a-boo! Tap me anytime for a trick.',
};

function createMascotInstance(canvas, options = {}) {
    const mode = options.mode || 'badge';
    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({
            canvas,
            alpha: true,
            antialias: true,
            powerPreference: 'high-performance',
        });
    } catch {
        return null;
    }

    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.25;

    const scene = new THREE.Scene();

    const pmrem = new THREE.PMREMGenerator(renderer);
    const envTex = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environment = envTex;
    scene.environmentIntensity = 1.15;

    const camera = new THREE.PerspectiveCamera(36, 1, 0.1, 40);
    camera.position.set(0, 0.25, 4.4);

    // Warm obsidian + metallic gold studio lighting
    const hemi = new THREE.HemisphereLight(0xfff4d2, 0x1a1510, 0.9);
    const keyLight = new THREE.DirectionalLight(0xffeab3, 2.4);
    keyLight.position.set(2.8, 3.8, 4.2);
    const rimLight = new THREE.PointLight(0xf4e185, 8, 12, 2);
    rimLight.position.set(-2.4, 1.6, -1.8);
    const fillLight = new THREE.PointLight(0xcdad69, 4, 10, 2);
    fillLight.position.set(2.2, -1.2, 2.4);
    scene.add(hemi, keyLight, rimLight, fillLight);

    // Shared luxury materials
    const obsidianMat = new THREE.MeshPhysicalMaterial({
        color: 0x141311,
        metalness: 0.28,
        roughness: 0.16,
        clearcoat: 1.0,
        clearcoatRoughness: 0.1,
    });

    const visorMat = new THREE.MeshPhysicalMaterial({
        color: 0x090908,
        metalness: 0.15,
        roughness: 0.06,
        clearcoat: 1.0,
        clearcoatRoughness: 0.05,
    });

    const goldMetalMat = new THREE.MeshStandardMaterial({
        color: 0xf4d87a,
        metalness: 0.92,
        roughness: 0.14,
    });

    const warmGoldMat = new THREE.MeshStandardMaterial({
        color: 0xcdad69,
        metalness: 0.88,
        roughness: 0.22,
    });

    const eyeGlowMat = new THREE.MeshStandardMaterial({
        color: 0xfff7d6,
        emissive: 0xf4e185,
        emissiveIntensity: 1.65,
        roughness: 0.2,
    });

    const blushMat = new THREE.MeshStandardMaterial({
        color: 0xff9e7d,
        emissive: 0xff8a66,
        emissiveIntensity: 0.75,
        transparent: true,
        opacity: 0.45,
        roughness: 0.5,
    });

    const orbGlowMat = new THREE.MeshStandardMaterial({
        color: 0xfff8dc,
        emissive: 0xf4e185,
        emissiveIntensity: 2.2,
        roughness: 0.15,
    });

    // Mascot hierarchy
    const root = new THREE.Group();
    scene.add(root);

    const bodyGroup = new THREE.Group();
    root.add(bodyGroup);

    // Chubby rounded body
    const torso = new THREE.Mesh(new THREE.SphereGeometry(0.62, 32, 24), obsidianMat);
    torso.scale.set(1.0, 0.96, 0.9);
    torso.position.y = -0.34;
    bodyGroup.add(torso);

    // Metallic gold chest ring & core gem
    const chestRing = new THREE.Mesh(new THREE.TorusGeometry(0.24, 0.045, 16, 36), goldMetalMat);
    chestRing.position.set(0, -0.32, 0.53);
    bodyGroup.add(chestRing);

    const chestGem = new THREE.Mesh(new THREE.OctahedronGeometry(0.11, 0), orbGlowMat);
    chestGem.position.set(0, -0.32, 0.54);
    bodyGroup.add(chestGem);

    // Hover thruster ring beneath body
    const thrusterRing = new THREE.Mesh(new THREE.TorusGeometry(0.36, 0.04, 14, 32), goldMetalMat);
    thrusterRing.rotation.x = Math.PI / 2;
    thrusterRing.position.y = -0.92;
    bodyGroup.add(thrusterRing);

    const thrusterGlow = new THREE.Mesh(
        new THREE.CylinderGeometry(0.24, 0.12, 0.12, 24),
        new THREE.MeshBasicMaterial({ color: 0xf4e185, transparent: true, opacity: 0.45 }),
    );
    thrusterGlow.position.y = -0.98;
    bodyGroup.add(thrusterGlow);

    // Cute rounded arms (pivoted at shoulders)
    const armGeo = new THREE.CapsuleGeometry(0.13, 0.28, 12, 16);
    const cuffGeo = new THREE.TorusGeometry(0.135, 0.032, 12, 24);

    const leftArmPivot = new THREE.Group();
    leftArmPivot.position.set(-0.64, -0.22, 0.05);
    const leftArm = new THREE.Mesh(armGeo, obsidianMat);
    leftArm.position.y = -0.16;
    const leftCuff = new THREE.Mesh(cuffGeo, goldMetalMat);
    leftCuff.rotation.x = Math.PI / 2;
    leftCuff.position.y = -0.24;
    leftArmPivot.add(leftArm, leftCuff);
    bodyGroup.add(leftArmPivot);

    const rightArmPivot = new THREE.Group();
    rightArmPivot.position.set(0.64, -0.22, 0.05);
    const rightArm = new THREE.Mesh(armGeo, obsidianMat);
    rightArm.position.y = -0.16;
    const rightCuff = new THREE.Mesh(cuffGeo, goldMetalMat);
    rightCuff.rotation.x = Math.PI / 2;
    rightCuff.position.y = -0.24;
    rightArmPivot.add(rightArm, rightCuff);
    bodyGroup.add(rightArmPivot);

    // Oversized cute head
    const headGroup = new THREE.Group();
    headGroup.position.set(0, 0.48, 0.04);
    bodyGroup.add(headGroup);

    const headShell = new THREE.Mesh(new THREE.SphereGeometry(0.66, 36, 28), obsidianMat);
    headShell.scale.set(1.08, 0.94, 0.96);
    headGroup.add(headShell);

    // Glossy visor faceplate
    const visor = new THREE.Mesh(new THREE.SphereGeometry(0.56, 32, 24), visorMat);
    visor.scale.set(1.04, 0.78, 0.56);
    visor.position.set(0, -0.02, 0.34);
    headGroup.add(visor);

    // Metallic gold visor rim
    const visorRim = new THREE.Mesh(new THREE.TorusGeometry(0.55, 0.038, 16, 48), goldMetalMat);
    visorRim.scale.set(1.04, 0.78, 1);
    visorRim.position.set(0, -0.02, 0.43);
    headGroup.add(visorRim);

    // Cute gold headphones / ear pods
    const earGeo = new THREE.CylinderGeometry(0.18, 0.18, 0.12, 24);
    const leftEar = new THREE.Mesh(earGeo, warmGoldMat);
    leftEar.rotation.z = Math.PI / 2;
    leftEar.position.set(-0.72, 0.02, 0);
    const rightEar = new THREE.Mesh(earGeo, warmGoldMat);
    rightEar.rotation.z = Math.PI / 2;
    rightEar.position.set(0.72, 0.02, 0);
    headGroup.add(leftEar, rightEar);

    // Expressive eyes + blush cheeks
    const faceGroup = new THREE.Group();
    faceGroup.position.set(0, -0.01, 0.62);
    headGroup.add(faceGroup);

    const eyeGeo = new THREE.CapsuleGeometry(0.068, 0.08, 8, 14);
    const leftEye = new THREE.Mesh(eyeGeo, eyeGlowMat);
    leftEye.position.set(-0.21, 0.03, 0);
    const rightEye = new THREE.Mesh(eyeGeo, eyeGlowMat);
    rightEye.position.set(0.21, 0.03, 0);
    faceGroup.add(leftEye, rightEye);

    const cheekGeo = new THREE.SphereGeometry(0.075, 16, 12);
    const leftCheek = new THREE.Mesh(cheekGeo, blushMat);
    leftCheek.scale.set(1.25, 0.75, 0.45);
    leftCheek.position.set(-0.33, -0.1, -0.03);
    const rightCheek = new THREE.Mesh(cheekGeo, blushMat);
    rightCheek.scale.set(1.25, 0.75, 0.45);
    rightCheek.position.set(0.33, -0.1, -0.03);
    faceGroup.add(leftCheek, rightCheek);

    // Spring gold antenna with glowing orb
    const antennaGroup = new THREE.Group();
    antennaGroup.position.set(0, 0.59, 0);
    headGroup.add(antennaGroup);

    const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.024, 0.032, 0.34, 12), goldMetalMat);
    stem.position.y = 0.16;
    antennaGroup.add(stem);

    const antennaOrb = new THREE.Mesh(new THREE.SphereGeometry(0.105, 20, 16), orbGlowMat);
    antennaOrb.position.y = 0.36;
    antennaGroup.add(antennaOrb);

    // Orbiting golden sparkles
    const sparklesGroup = new THREE.Group();
    root.add(sparklesGroup);
    const sparkleMeshes = [];
    const sparkleGeo = new THREE.OctahedronGeometry(0.055, 0);
    for (let i = 0; i < 6; i++) {
        const sp = new THREE.Mesh(sparkleGeo, goldMetalMat);
        sp.userData = {
            angle: (i / 6) * Math.PI * 2,
            radius: 1.08 + (i % 2) * 0.2,
            speed: 0.75 + (i % 3) * 0.25,
            yOffset: (i - 2.5) * 0.18,
        };
        sparklesGroup.add(sp);
        sparkleMeshes.push(sp);
    }

    // Interaction & animation state
    const pointerTarget = new THREE.Vector2(0, 0);
    const pointerCurrent = new THREE.Vector2(0, 0);
    let activeReaction = null;
    let reactionElapsed = 0;
    let reactionDuration = 1.4;
    let reactionIndex = 0;
    let blinkTimer = 2.6;
    let blinkProgress = -1;
    let pointDirection = 0; // -1 = point left, +1 = point right, 0 = neutral
    let onSpeechCallback = typeof options.onSpeech === 'function' ? options.onSpeech : null;

    const triggerReaction = (name = null, customLine = null) => {
        const chosen = name || REACTIONS[reactionIndex % REACTIONS.length];
        reactionIndex++;
        activeReaction = chosen;
        reactionElapsed = 0;
        reactionDuration = chosen === 'spin' ? 1.15 : (chosen === 'dance' ? 1.85 : 1.45);
        if (onSpeechCallback) {
            onSpeechCallback(customLine || REACTION_LINES[chosen] || REACTION_LINES.wave, chosen);
        }
    };

    const setPointDirection = (dir) => {
        pointDirection = Math.max(-1, Math.min(1, Number(dir || 0)));
    };

    const handlePointerMove = (ev) => {
        const rect = canvas.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;
        const nx = Math.max(-1, Math.min(1, (ev.clientX - cx) / Math.max(window.innerWidth * 0.45, rect.width)));
        const ny = Math.max(-1, Math.min(1, -(ev.clientY - cy) / Math.max(window.innerHeight * 0.45, rect.height)));
        pointerTarget.set(nx, ny);
    };

    window.addEventListener('pointermove', handlePointerMove, { passive: true });

    canvas.addEventListener('pointerdown', () => {
        triggerReaction();
    });

    const resize = () => {
        const rect = canvas.getBoundingClientRect();
        const w = Math.max(48, Math.round(rect.width || canvas.clientWidth || 240));
        const h = Math.max(48, Math.round(rect.height || canvas.clientHeight || 240));
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    };

    const ro = new ResizeObserver(resize);
    ro.observe(canvas);
    resize();

    const timer = new THREE.Timer();
    timer.connect(document);

    const animate = () => {
        timer.update();
        const delta = Math.min(0.05, timer.getDelta());
        const elapsed = timer.getElapsed();

        pointerCurrent.lerp(pointerTarget, Math.min(1, delta * 7));

        // Base hover + breathing
        let bobY = reducedMotion ? 0 : Math.sin(elapsed * 2.3) * 0.08;
        let bodyRotY = pointerCurrent.x * 0.36;
        let bodyRotX = -pointerCurrent.y * 0.14;
        let bodyRotZ = 0;
        let headTiltZ = -pointerCurrent.x * 0.1;
        let leftArmZ = 0.24 + (reducedMotion ? 0 : Math.sin(elapsed * 2.3 + 0.6) * 0.08);
        let rightArmZ = -0.24 - (reducedMotion ? 0 : Math.sin(elapsed * 2.3 + 0.6) * 0.08);
        let rightArmX = 0;
        let leftArmX = 0;
        let eyeScaleY = 1;
        let sparkleBoost = 1;
        let orbIntensity = 2.0 + Math.sin(elapsed * 3.5) * 0.35;

        // Scroll-guided pointing pose
        if (pointDirection < -0.2) {
            leftArmZ = 1.15;
            leftArmX = -0.3;
            bodyRotY -= 0.25;
        } else if (pointDirection > 0.2) {
            rightArmZ = -1.15;
            rightArmX = -0.3;
            bodyRotY += 0.25;
        }

        // Natural blinking
        blinkTimer -= delta;
        if (blinkTimer <= 0 && blinkProgress < 0) {
            blinkProgress = 0;
            blinkTimer = 2.4 + Math.random() * 2.6;
        }
        if (blinkProgress >= 0) {
            blinkProgress += delta * 7.5;
            eyeScaleY = Math.max(0.12, Math.abs(Math.cos(blinkProgress * Math.PI)));
            if (blinkProgress >= 1) blinkProgress = -1;
        }

        // Active interactive reaction
        if (activeReaction && !reducedMotion) {
            reactionElapsed += delta;
            const t = Math.min(1, reactionElapsed / reactionDuration);
            const env = Math.sin(t * Math.PI);

            if (activeReaction === 'wave') {
                rightArmZ = -2.25 + Math.sin(elapsed * 18) * 0.35;
                rightArmX = -0.25;
                headTiltZ = 0.18 * env;
                eyeScaleY = 0.45 + 0.55 * (1 - env);
                blushMat.opacity = 0.45 + 0.4 * env;
            } else if (activeReaction === 'spin') {
                bodyRotY += t * Math.PI * 2;
                bobY += Math.sin(t * Math.PI) * 0.38;
                sparkleBoost = 3.2;
                orbIntensity = 3.2;
            } else if (activeReaction === 'cheer') {
                leftArmZ = 2.35 * env + 0.24 * (1 - env);
                rightArmZ = -2.35 * env - 0.24 * (1 - env);
                bobY += Math.abs(Math.sin(t * Math.PI * 3)) * 0.22;
                eyeScaleY = 0.38;
                blushMat.opacity = 0.85;
                orbIntensity = 3.6;
                sparkleBoost = 2.6;
            } else if (activeReaction === 'dance') {
                bodyRotZ = Math.sin(elapsed * 9) * 0.18 * env;
                bodyRotY += Math.sin(elapsed * 4.5) * 0.38 * env;
                leftArmZ = 0.7 + Math.sin(elapsed * 9) * 0.6;
                rightArmZ = -0.7 + Math.sin(elapsed * 9) * 0.6;
                sparkleBoost = 2.2;
            } else if (activeReaction === 'peek') {
                bobY -= 0.28 * env;
                bodyRotX += 0.28 * env;
                headTiltZ = 0.22 * env;
            }

            if (t >= 1) {
                activeReaction = null;
                blushMat.opacity = 0.45;
            }
        } else {
            blushMat.opacity = 0.45;
        }

        bodyGroup.position.y = bobY;
        bodyGroup.rotation.set(bodyRotX, bodyRotY, bodyRotZ);

        headGroup.rotation.y = pointerCurrent.x * 0.28;
        headGroup.rotation.x = -pointerCurrent.y * 0.2;
        headGroup.rotation.z = headTiltZ;

        faceGroup.position.x = pointerCurrent.x * 0.055;
        faceGroup.position.y = -0.01 + pointerCurrent.y * 0.04;

        leftEye.scale.y = eyeScaleY;
        rightEye.scale.y = eyeScaleY;

        leftArmPivot.rotation.set(leftArmX, 0, leftArmZ);
        rightArmPivot.rotation.set(rightArmX, 0, rightArmZ);

        antennaGroup.rotation.z = -bodyRotZ * 0.8 + Math.sin(elapsed * 4.2) * 0.08;
        antennaGroup.rotation.x = Math.cos(elapsed * 3.1) * 0.06;
        orbGlowMat.emissiveIntensity = orbIntensity;

        thrusterGlow.material.opacity = 0.32 + Math.sin(elapsed * 7) * 0.12;
        chestGem.rotation.y = elapsed * 1.3;

        sparkleMeshes.forEach((sp, idx) => {
            sp.userData.angle += delta * sp.userData.speed * sparkleBoost;
            const a = sp.userData.angle;
            sp.position.set(
                Math.cos(a) * sp.userData.radius,
                sp.userData.yOffset + Math.sin(elapsed * 2.5 + idx) * 0.1,
                Math.sin(a) * sp.userData.radius * 0.65,
            );
            sp.rotation.y = elapsed * 2;
            sp.rotation.x = elapsed * 1.4;
        });

        renderer.render(scene, camera);
    };

    renderer.setAnimationLoop(animate);

    return {
        triggerReaction,
        setPointDirection,
        setSpeechCallback(cb) {
            onSpeechCallback = cb;
        },
        destroy() {
            renderer.setAnimationLoop(null);
            ro.disconnect();
            window.removeEventListener('pointermove', handlePointerMove);
            pmrem.dispose();
            envTex.dispose();
            renderer.dispose();
        },
    };
}

function initMascots() {
    document.querySelectorAll('[data-obs-mascot]').forEach((host) => {
        if (host.dataset.mascotReady === '1') return;
        host.dataset.mascotReady = '1';

        const canvas = host.querySelector('[data-mascot-canvas]');
        const speechEl = host.querySelector('[data-mascot-speech]');
        const mode = host.dataset.mascotMode || 'badge';
        if (!canvas) return;

        let speechTimer = null;
        const showSpeech = (msg) => {
            if (!speechEl || !msg) return;
            speechEl.textContent = msg;
            speechEl.hidden = false;
            clearTimeout(speechTimer);
            speechTimer = setTimeout(() => {
                if (mode !== 'hero-guide' || window.scrollY < 160) {
                    // keep bubble visible while scrolling guide
                }
            }, 4200);
        };

        const instance = createMascotInstance(canvas, {
            mode,
            onSpeech: showSpeech,
        });

        if (!instance) {
            const fallback = host.querySelector('[data-mascot-fallback]');
            if (fallback) fallback.hidden = false;
            canvas.hidden = true;
            return;
        }

        host.querySelectorAll('[data-mascot-react]').forEach((btn) => {
            btn.addEventListener('click', (ev) => {
                ev.stopPropagation();
                instance.triggerReaction(btn.dataset.mascotReact);
            });
        });

        // Homepage scroll-guided flight companion
        if (mode === 'hero-guide') {
            const floatGuide = document.getElementById('mascot-scroll-guide');
            const floatCanvas = floatGuide?.querySelector('[data-mascot-float-canvas]');
            const floatSpeech = floatGuide?.querySelector('[data-mascot-float-speech]');
            let floatInstance = null;

            if (floatGuide && floatCanvas) {
                floatInstance = createMascotInstance(floatCanvas, {
                    mode: 'float',
                    onSpeech: (msg) => {
                        if (floatSpeech) floatSpeech.textContent = msg;
                    },
                });
            }

            const stops = [
                {
                    id: 'features',
                    side: 'right',
                    dir: -1,
                    line: 'Here is how taps reach OBS in under a second!',
                    reaction: 'wave',
                },
                {
                    id: 'overlays',
                    side: 'left',
                    dir: 1,
                    line: 'Try the live tools here—timers, alerts & scoreboards!',
                    reaction: 'cheer',
                },
                {
                    id: 'closing-heading',
                    side: 'right',
                    dir: -1,
                    line: 'Ready to stream? Open Studio or your Mobile Deck!',
                    reaction: 'spin',
                },
            ];

            let lastStopId = null;

            const updateScrollGuide = () => {
                if (!floatGuide || !floatInstance) return;
                const heroRect = host.getBoundingClientRect();
                const shouldFloat = heroRect.bottom < 120;
                floatGuide.classList.toggle('is-visible', shouldFloat);
                floatGuide.setAttribute('aria-hidden', shouldFloat ? 'false' : 'true');

                if (!shouldFloat) {
                    lastStopId = null;
                    return;
                }

                const midY = window.innerHeight * 0.45;
                let activeStop = stops[0];
                for (const s of stops) {
                    const el = document.getElementById(s.id);
                    if (!el) continue;
                    const r = el.getBoundingClientRect();
                    if (r.top <= midY + 140) {
                        activeStop = s;
                    }
                }

                const isMobile = window.innerWidth < 1024;
                floatGuide.dataset.side = isMobile ? 'right' : activeStop.side;
                floatInstance.setPointDirection(isMobile ? -1 : activeStop.dir);

                if (lastStopId !== activeStop.id) {
                    lastStopId = activeStop.id;
                    if (floatSpeech) floatSpeech.textContent = activeStop.line;
                    floatInstance.triggerReaction(activeStop.reaction, activeStop.line);
                }
            };

            window.addEventListener('scroll', updateScrollGuide, { passive: true });
            window.addEventListener('resize', updateScrollGuide, { passive: true });
            updateScrollGuide();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMascots);
} else {
    initMascots();
}
