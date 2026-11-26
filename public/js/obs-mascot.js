import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.186.0/build/three.module.js';
import { RoomEnvironment } from 'https://cdn.jsdelivr.net/npm/three@0.186.0/examples/jsm/environments/RoomEnvironment.js';

const REACTIONS = ['wave', 'spin', 'cheer', 'dance', 'peek'];

const REACTION_LINES = {
    wave: 'Hey there! Tap me anytime!',
    spin: 'Wheee! Everything stays synced live.',
    cheer: 'Looking sharp! Ready to go live.',
    dance: 'Vibing with your stream setup!',
    peek: 'Peek-a-boo! Need a hand?',
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
    scene.environmentIntensity = 1.2;

    const camera = new THREE.PerspectiveCamera(34, 1, 0.1, 40);
    camera.position.set(0, 0.15, 4.45);

    // Studio lighting for glossy obsidian black + shiny metallic gold
    const hemi = new THREE.HemisphereLight(0xfff6dc, 0x16130f, 1.0);
    const keyLight = new THREE.DirectionalLight(0xfff0c8, 2.6);
    keyLight.position.set(2.5, 4.0, 4.5);
    const rimLight = new THREE.PointLight(0xf4e185, 9, 12, 2);
    rimLight.position.set(-2.4, 1.8, -1.6);
    const fillLight = new THREE.PointLight(0xcdad69, 4.5, 10, 2);
    fillLight.position.set(2.4, -1.0, 2.6);
    scene.add(hemi, keyLight, rimLight, fillLight);

    // Black & Gold Chibi Materials
    const obsidianShellMat = new THREE.MeshPhysicalMaterial({
        color: 0x141311,
        metalness: 0.24,
        roughness: 0.12,
        clearcoat: 1.0,
        clearcoatRoughness: 0.06,
    });

    const goldMetalMat = new THREE.MeshPhysicalMaterial({
        color: 0xe6c367,
        metalness: 0.92,
        roughness: 0.14,
        clearcoat: 0.7,
        clearcoatRoughness: 0.1,
    });

    const pearlFaceMat = new THREE.MeshPhysicalMaterial({
        color: 0xfaf5e8,
        metalness: 0.04,
        roughness: 0.24,
        clearcoat: 0.65,
        clearcoatRoughness: 0.14,
    });

    const glossyBlackEyeMat = new THREE.MeshPhysicalMaterial({
        color: 0x050505,
        metalness: 0.1,
        roughness: 0.04,
        clearcoat: 1.0,
        clearcoatRoughness: 0.02,
    });

    const blushMat = new THREE.MeshStandardMaterial({
        color: 0xf4a57a,
        emissive: 0xe68a5c,
        emissiveIntensity: 0.35,
        transparent: true,
        opacity: 0.32,
        roughness: 0.6,
    });

    const root = new THREE.Group();
    scene.add(root);

    const floatGroup = new THREE.Group();
    root.add(floatGroup);

    // 1. Oversized Chubby Teardrop / Onion Head
    const headGroup = new THREE.Group();
    headGroup.position.set(0, 0.3, 0);
    floatGroup.add(headGroup);

    // Main rounded wide head
    const headMain = new THREE.Mesh(new THREE.SphereGeometry(0.78, 40, 32), obsidianShellMat);
    headMain.scale.set(1.16, 0.88, 0.96);
    headGroup.add(headMain);

    // Subtle tapered cute top crown leading to antenna
    const headCrown = new THREE.Mesh(new THREE.ConeGeometry(0.42, 0.42, 32), obsidianShellMat);
    headCrown.position.set(0, 0.63, 0);
    headGroup.add(headCrown);

    // Large soft oval cream/pearl face screen
    const faceOval = new THREE.Mesh(new THREE.SphereGeometry(0.62, 36, 28), pearlFaceMat);
    faceOval.scale.set(1.14, 0.78, 0.46);
    faceOval.position.set(0, -0.02, 0.49);
    headGroup.add(faceOval);

    // Shiny metallic gold rim framing the face
    const faceGoldRim = new THREE.Mesh(new THREE.TorusGeometry(0.61, 0.026, 16, 56), goldMetalMat);
    faceGoldRim.scale.set(1.14, 0.78, 1);
    faceGoldRim.position.set(0, -0.02, 0.57);
    headGroup.add(faceGoldRim);

    // Big glossy black button eyes + tiny white specular catchlights + cute curved smile
    const faceFeatures = new THREE.Group();
    faceFeatures.position.set(0, -0.02, 0.76);
    headGroup.add(faceFeatures);

    const eyeGeo = new THREE.SphereGeometry(0.115, 24, 20);
    const catchGeo = new THREE.SphereGeometry(0.032, 12, 10);
    const catchMat = new THREE.MeshBasicMaterial({ color: 0xffffff });

    const leftEyeGroup = new THREE.Group();
    leftEyeGroup.position.set(-0.24, 0.04, 0);
    const leftEye = new THREE.Mesh(eyeGeo, glossyBlackEyeMat);
    leftEye.scale.set(0.95, 1.08, 0.65);
    const leftCatch = new THREE.Mesh(catchGeo, catchMat);
    leftCatch.position.set(0.036, 0.045, 0.068);
    leftEyeGroup.add(leftEye, leftCatch);

    const rightEyeGroup = new THREE.Group();
    rightEyeGroup.position.set(0.24, 0.04, 0);
    const rightEye = new THREE.Mesh(eyeGeo, glossyBlackEyeMat);
    rightEye.scale.set(0.95, 1.08, 0.65);
    const rightCatch = new THREE.Mesh(catchGeo, catchMat);
    rightCatch.position.set(0.036, 0.045, 0.068);
    rightEyeGroup.add(rightEye, rightCatch);

    faceFeatures.add(leftEyeGroup, rightEyeGroup);

    // Cute curved smile arc
    const smileGeo = new THREE.TorusGeometry(0.085, 0.017, 12, 24, Math.PI * 0.72);
    const smile = new THREE.Mesh(smileGeo, glossyBlackEyeMat);
    smile.rotation.z = Math.PI * 1.14;
    smile.position.set(0, -0.08, 0.02);
    faceFeatures.add(smile);

    // Soft blush cheeks
    const cheekGeo = new THREE.SphereGeometry(0.068, 16, 12);
    const leftCheek = new THREE.Mesh(cheekGeo, blushMat);
    leftCheek.scale.set(1.3, 0.75, 0.35);
    leftCheek.position.set(-0.37, -0.07, -0.02);
    const rightCheek = new THREE.Mesh(cheekGeo, blushMat);
    rightCheek.scale.set(1.3, 0.75, 0.35);
    rightCheek.position.set(0.37, -0.07, -0.02);
    faceFeatures.add(leftCheek, rightCheek);

    // 2. Top Antenna with Floating Gold Halo Ring & Glossy Gold Bobble
    const antennaGroup = new THREE.Group();
    antennaGroup.position.set(0, 0.79, 0);
    headGroup.add(antennaGroup);

    const antennaStem = new THREE.Mesh(new THREE.CylinderGeometry(0.016, 0.022, 0.24, 12), goldMetalMat);
    antennaStem.position.y = 0.12;
    antennaGroup.add(antennaStem);

    const antennaHalo = new THREE.Mesh(new THREE.TorusGeometry(0.115, 0.022, 14, 36), goldMetalMat);
    antennaHalo.rotation.x = Math.PI / 2 - 0.18;
    antennaHalo.position.y = 0.22;
    antennaGroup.add(antennaHalo);

    const antennaBobble = new THREE.Mesh(new THREE.SphereGeometry(0.135, 24, 20), goldMetalMat);
    antennaBobble.position.y = 0.34;
    antennaGroup.add(antennaBobble);

    // 3. Cute Detached Floating Pear-Shaped Body + Gold Chest Button
    const bodyGroup = new THREE.Group();
    bodyGroup.position.set(0, -0.78, 0);
    floatGroup.add(bodyGroup);

    const miniBody = new THREE.Mesh(new THREE.SphereGeometry(0.34, 28, 24), obsidianShellMat);
    miniBody.scale.set(1.06, 1.0, 0.94);
    bodyGroup.add(miniBody);

    const chestButton = new THREE.Mesh(new THREE.SphereGeometry(0.095, 20, 16), goldMetalMat);
    chestButton.scale.set(1, 1, 0.55);
    chestButton.position.set(0, 0.03, 0.3);
    bodyGroup.add(chestButton);

    // 4. Two Cute Detached Floating Teardrop Arms/Flippers
    const armGeo = new THREE.SphereGeometry(0.145, 20, 16);
    const leftArmGroup = new THREE.Group();
    leftArmGroup.position.set(-0.52, -0.68, 0.08);
    const leftArm = new THREE.Mesh(armGeo, obsidianShellMat);
    leftArm.scale.set(0.78, 1.35, 0.78);
    leftArm.rotation.z = 0.55;
    leftArmGroup.add(leftArm);
    floatGroup.add(leftArmGroup);

    const rightArmGroup = new THREE.Group();
    rightArmGroup.position.set(0.52, -0.68, 0.08);
    const rightArm = new THREE.Mesh(armGeo, obsidianShellMat);
    rightArm.scale.set(0.78, 1.35, 0.78);
    rightArm.rotation.z = -0.55;
    rightArmGroup.add(rightArm);
    floatGroup.add(rightArmGroup);

    // 5. Orbiting Tiny Gold Sparkles
    const sparklesGroup = new THREE.Group();
    root.add(sparklesGroup);
    const sparkleMeshes = [];
    const sparkleGeo = new THREE.OctahedronGeometry(0.045, 0);
    for (let i = 0; i < 5; i++) {
        const sp = new THREE.Mesh(sparkleGeo, goldMetalMat);
        sp.userData = {
            angle: (i / 5) * Math.PI * 2,
            radius: 1.15 + (i % 2) * 0.18,
            speed: 0.65 + (i % 3) * 0.22,
            yOffset: (i - 2) * 0.2,
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
    let blinkTimer = 2.4;
    let blinkProgress = -1;
    let pointDirection = 0;
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
    canvas.addEventListener('pointerdown', () => triggerReaction());

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

        let bobY = reducedMotion ? 0 : Math.sin(elapsed * 2.4) * 0.075;
        let rotY = pointerCurrent.x * 0.34;
        let rotX = -pointerCurrent.y * 0.14;
        let rotZ = 0;
        let headTiltZ = -pointerCurrent.x * 0.1;
        let lArmY = -0.68 + (reducedMotion ? 0 : Math.sin(elapsed * 2.4 + 0.5) * 0.05);
        let rArmY = -0.68 + (reducedMotion ? 0 : Math.sin(elapsed * 2.4 + 0.5) * 0.05);
        let lArmZ = 0;
        let rArmZ = 0;
        let eyeScaleY = 1;
        let sparkleBoost = 1;

        if (pointDirection < -0.2) {
            lArmY = -0.45;
            lArmZ = 0.85;
            rotY -= 0.25;
        } else if (pointDirection > 0.2) {
            rArmY = -0.45;
            rArmZ = -0.85;
            rotY += 0.25;
        }

        // Natural blinking
        blinkTimer -= delta;
        if (blinkTimer <= 0 && blinkProgress < 0) {
            blinkProgress = 0;
            blinkTimer = 2.2 + Math.random() * 2.6;
        }
        if (blinkProgress >= 0) {
            blinkProgress += delta * 7.8;
            eyeScaleY = Math.max(0.1, Math.abs(Math.cos(blinkProgress * Math.PI)));
            if (blinkProgress >= 1) blinkProgress = -1;
        }

        if (activeReaction && !reducedMotion) {
            reactionElapsed += delta;
            const t = Math.min(1, reactionElapsed / reactionDuration);
            const env = Math.sin(t * Math.PI);

            if (activeReaction === 'wave') {
                rArmY = -0.36;
                rArmZ = -1.25 + Math.sin(elapsed * 18) * 0.35;
                headTiltZ = 0.18 * env;
                eyeScaleY = 0.45 + 0.55 * (1 - env);
                blushMat.opacity = 0.32 + 0.45 * env;
            } else if (activeReaction === 'spin') {
                rotY += t * Math.PI * 2;
                bobY += Math.sin(t * Math.PI) * 0.34;
                sparkleBoost = 3.0;
            } else if (activeReaction === 'cheer') {
                lArmY = -0.35;
                rArmY = -0.35;
                lArmZ = 1.3 * env;
                rArmZ = -1.3 * env;
                bobY += Math.abs(Math.sin(t * Math.PI * 3)) * 0.22;
                eyeScaleY = 0.36;
                blushMat.opacity = 0.78;
                sparkleBoost = 2.4;
            } else if (activeReaction === 'dance') {
                rotZ = Math.sin(elapsed * 9) * 0.16 * env;
                rotY += Math.sin(elapsed * 4.5) * 0.35 * env;
                lArmY = -0.52 + Math.sin(elapsed * 9) * 0.16;
                rArmY = -0.52 - Math.sin(elapsed * 9) * 0.16;
                sparkleBoost = 2.2;
            } else if (activeReaction === 'peek') {
                bobY -= 0.26 * env;
                rotX += 0.25 * env;
                headTiltZ = 0.22 * env;
            }

            if (t >= 1) {
                activeReaction = null;
                blushMat.opacity = 0.32;
            }
        } else {
            blushMat.opacity = 0.32;
        }

        floatGroup.position.y = bobY;
        floatGroup.rotation.set(rotX, rotY, rotZ);

        headGroup.rotation.y = pointerCurrent.x * 0.25;
        headGroup.rotation.x = -pointerCurrent.y * 0.18;
        headGroup.rotation.z = headTiltZ;

        faceFeatures.position.x = pointerCurrent.x * 0.05;
        faceFeatures.position.y = -0.02 + pointerCurrent.y * 0.04;

        leftEyeGroup.scale.y = eyeScaleY;
        rightEyeGroup.scale.y = eyeScaleY;

        bodyGroup.position.y = -0.78 - bobY * 0.25;
        leftArmGroup.position.y = lArmY;
        leftArmGroup.rotation.z = lArmZ;
        rightArmGroup.position.y = rArmY;
        rightArmGroup.rotation.z = rArmZ;

        antennaGroup.rotation.z = -rotZ * 0.75 + Math.sin(elapsed * 4.2) * 0.08;
        antennaHalo.rotation.z = elapsed * 1.4;

        sparkleMeshes.forEach((sp, idx) => {
            sp.userData.angle += delta * sp.userData.speed * sparkleBoost;
            const a = sp.userData.angle;
            sp.position.set(
                Math.cos(a) * sp.userData.radius,
                sp.userData.yOffset + Math.sin(elapsed * 2.5 + idx) * 0.1,
                Math.sin(a) * sp.userData.radius * 0.6,
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

        const showSpeech = (msg) => {
            if (!speechEl || !msg) return;
            speechEl.textContent = msg;
            speechEl.hidden = false;
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
                { id: 'features', side: 'right', dir: -1, line: 'Taps reach OBS in under a second!', reaction: 'wave' },
                { id: 'overlays', side: 'left', dir: 1, line: 'Try these live controls right here!', reaction: 'cheer' },
                { id: 'features-showcase', side: 'right', dir: -1, line: 'Everything built for smooth streams!', reaction: 'dance' },
                { id: 'faqs', side: 'left', dir: 1, line: 'Got questions? Quick answers here.', reaction: 'peek' },
                { id: 'closing-heading', side: 'right', dir: -1, line: 'Ready? Open Studio or Mobile Deck!', reaction: 'spin' },
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
                    if (el.getBoundingClientRect().top <= midY + 140) {
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
