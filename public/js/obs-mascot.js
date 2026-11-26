import * as THREE from 'https://esm.sh/three@0.186.0';
import { RoomEnvironment } from 'https://esm.sh/three@0.186.0/examples/jsm/environments/RoomEnvironment.js';

const REACTIONS = ['wave', 'spin', 'cheer', 'dance', 'peek'];

const REACTION_LINES = {
    wave: 'Hey! Tap Ask Lumi or drag me anywhere.',
    spin: 'Wheee! Everything stays synced live.',
    cheer: 'Looking sharp! Ready to go live.',
    dance: 'Vibing with your stream setup!',
    peek: 'Peek-a-boo! Need a hand?',
};

function createMascotInstance(canvas, options = {}) {
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

    renderer.setClearColor(0x000000, 0);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.28;

    const scene = new THREE.Scene();

    const pmrem = new THREE.PMREMGenerator(renderer);
    const envTex = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environment = envTex;
    scene.environmentIntensity = 1.25;

    const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 40);
    camera.position.set(0, 0.1, 5.75);

    const hemi = new THREE.HemisphereLight(0xfff6dc, 0x16130f, 1.0);
    const keyLight = new THREE.DirectionalLight(0xfff0c8, 2.7);
    keyLight.position.set(2.5, 4.0, 4.5);
    const rimLight = new THREE.PointLight(0xf4e185, 9, 14, 2);
    rimLight.position.set(-2.4, 1.8, -1.6);
    const fillLight = new THREE.PointLight(0xcdad69, 4.5, 12, 2);
    fillLight.position.set(2.4, -1.0, 2.6);
    scene.add(hemi, keyLight, rimLight, fillLight);

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

    const headGroup = new THREE.Group();
    headGroup.position.set(0, 0.28, 0);
    floatGroup.add(headGroup);

    const headMain = new THREE.Mesh(new THREE.SphereGeometry(0.76, 40, 32), obsidianShellMat);
    headMain.scale.set(1.16, 0.88, 0.96);
    headGroup.add(headMain);

    const headCrown = new THREE.Mesh(new THREE.ConeGeometry(0.41, 0.4, 32), obsidianShellMat);
    headCrown.position.set(0, 0.61, 0);
    headGroup.add(headCrown);

    const faceOval = new THREE.Mesh(new THREE.SphereGeometry(0.6, 36, 28), pearlFaceMat);
    faceOval.scale.set(1.14, 0.78, 0.46);
    faceOval.position.set(0, -0.02, 0.48);
    headGroup.add(faceOval);

    const faceGoldRim = new THREE.Mesh(new THREE.TorusGeometry(0.59, 0.025, 16, 56), goldMetalMat);
    faceGoldRim.scale.set(1.14, 0.78, 1);
    faceGoldRim.position.set(0, -0.02, 0.56);
    headGroup.add(faceGoldRim);

    const faceFeatures = new THREE.Group();
    faceFeatures.position.set(0, -0.02, 0.74);
    headGroup.add(faceFeatures);

    const eyeGeo = new THREE.SphereGeometry(0.112, 24, 20);
    const catchGeo = new THREE.SphereGeometry(0.031, 12, 10);
    const catchMat = new THREE.MeshBasicMaterial({ color: 0xffffff });

    const leftEyeGroup = new THREE.Group();
    leftEyeGroup.position.set(-0.23, 0.04, 0);
    const leftEye = new THREE.Mesh(eyeGeo, glossyBlackEyeMat);
    leftEye.scale.set(0.95, 1.08, 0.65);
    const leftCatch = new THREE.Mesh(catchGeo, catchMat);
    leftCatch.position.set(0.035, 0.044, 0.066);
    leftEyeGroup.add(leftEye, leftCatch);

    const rightEyeGroup = new THREE.Group();
    rightEyeGroup.position.set(0.23, 0.04, 0);
    const rightEye = new THREE.Mesh(eyeGeo, glossyBlackEyeMat);
    rightEye.scale.set(0.95, 1.08, 0.65);
    const rightCatch = new THREE.Mesh(catchGeo, catchMat);
    rightCatch.position.set(0.035, 0.044, 0.066);
    rightEyeGroup.add(rightEye, rightCatch);

    faceFeatures.add(leftEyeGroup, rightEyeGroup);

    const smileGeo = new THREE.TorusGeometry(0.082, 0.017, 12, 24, Math.PI * 0.72);
    const smile = new THREE.Mesh(smileGeo, glossyBlackEyeMat);
    smile.rotation.z = Math.PI * 1.14;
    smile.position.set(0, -0.08, 0.02);
    faceFeatures.add(smile);

    const cheekGeo = new THREE.SphereGeometry(0.066, 16, 12);
    const leftCheek = new THREE.Mesh(cheekGeo, blushMat);
    leftCheek.scale.set(1.3, 0.75, 0.35);
    leftCheek.position.set(-0.36, -0.07, -0.02);
    const rightCheek = new THREE.Mesh(cheekGeo, blushMat);
    rightCheek.scale.set(1.3, 0.75, 0.35);
    rightCheek.position.set(0.36, -0.07, -0.02);
    faceFeatures.add(leftCheek, rightCheek);

    const antennaGroup = new THREE.Group();
    antennaGroup.position.set(0, 0.76, 0);
    headGroup.add(antennaGroup);

    const antennaStem = new THREE.Mesh(new THREE.CylinderGeometry(0.016, 0.022, 0.22, 12), goldMetalMat);
    antennaStem.position.y = 0.11;
    antennaGroup.add(antennaStem);

    const antennaHalo = new THREE.Mesh(new THREE.TorusGeometry(0.11, 0.021, 14, 36), goldMetalMat);
    antennaHalo.rotation.x = Math.PI / 2 - 0.18;
    antennaHalo.position.y = 0.21;
    antennaGroup.add(antennaHalo);

    const antennaBobble = new THREE.Mesh(new THREE.SphereGeometry(0.13, 24, 20), goldMetalMat);
    antennaBobble.position.y = 0.32;
    antennaGroup.add(antennaBobble);

    const bodyGroup = new THREE.Group();
    bodyGroup.position.set(0, -0.76, 0);
    floatGroup.add(bodyGroup);

    const miniBody = new THREE.Mesh(new THREE.SphereGeometry(0.33, 28, 24), obsidianShellMat);
    miniBody.scale.set(1.06, 1.0, 0.94);
    bodyGroup.add(miniBody);

    const chestButton = new THREE.Mesh(new THREE.SphereGeometry(0.09, 20, 16), goldMetalMat);
    chestButton.scale.set(1, 1, 0.55);
    chestButton.position.set(0, 0.03, 0.29);
    bodyGroup.add(chestButton);

    const armGeo = new THREE.SphereGeometry(0.14, 20, 16);
    const leftArmGroup = new THREE.Group();
    leftArmGroup.position.set(-0.5, -0.66, 0.08);
    const leftArm = new THREE.Mesh(armGeo, obsidianShellMat);
    leftArm.scale.set(0.78, 1.35, 0.78);
    leftArm.rotation.z = 0.55;
    leftArmGroup.add(leftArm);
    floatGroup.add(leftArmGroup);

    const rightArmGroup = new THREE.Group();
    rightArmGroup.position.set(0.5, -0.66, 0.08);
    const rightArm = new THREE.Mesh(armGeo, obsidianShellMat);
    rightArm.scale.set(0.78, 1.35, 0.78);
    rightArm.rotation.z = -0.55;
    rightArmGroup.add(rightArm);
    floatGroup.add(rightArmGroup);

    const sparklesGroup = new THREE.Group();
    root.add(sparklesGroup);
    const sparkleMeshes = [];
    const sparkleGeo = new THREE.OctahedronGeometry(0.042, 0);
    for (let i = 0; i < 5; i++) {
        const sp = new THREE.Mesh(sparkleGeo, goldMetalMat);
        sp.userData = {
            angle: (i / 5) * Math.PI * 2,
            radius: 1.12 + (i % 2) * 0.16,
            speed: 0.42 + (i % 3) * 0.14,
            yOffset: (i - 2) * 0.18,
        };
        sparklesGroup.add(sp);
        sparkleMeshes.push(sp);
    }

    const pointerTarget = new THREE.Vector2(0, 0);
    const pointerCurrent = new THREE.Vector2(0, 0);
    let activeReaction = null;
    let reactionElapsed = 0;
    let reactionDuration = 1.6;
    let reactionIndex = 0;
    let blinkTimer = 2.6;
    let blinkProgress = -1;
    let pointDirection = 0;
    let flightBankZ = 0;
    const onSpeechCallback = typeof options.onSpeech === 'function' ? options.onSpeech : null;
    const onFrameCallback = typeof options.onFrame === 'function' ? options.onFrame : null;

    const triggerReaction = (name = null, customLine = null) => {
        const chosen = name || REACTIONS[reactionIndex % REACTIONS.length];
        reactionIndex++;
        activeReaction = chosen;
        reactionElapsed = 0;
        reactionDuration = chosen === 'spin' ? 1.4 : (chosen === 'dance' ? 2.1 : 1.65);
        if (onSpeechCallback && customLine !== false) {
            onSpeechCallback(customLine || REACTION_LINES[chosen] || REACTION_LINES.wave, chosen);
        }
    };

    const setPointDirection = (dir, bank = 0) => {
        pointDirection = Math.max(-1, Math.min(1, Number(dir || 0)));
        flightBankZ = Math.max(-0.22, Math.min(0.22, Number(bank || 0)));
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
    if (!options.disableAutoTap) {
        canvas.addEventListener('pointerdown', () => triggerReaction());
    }

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

        if (onFrameCallback) {
            onFrameCallback(delta, elapsed);
        }

        // Smooth, unhurried head/eye tracking
        pointerCurrent.lerp(pointerTarget, Math.min(1, delta * 4.5));

        let bobY = reducedMotion ? 0 : Math.sin(elapsed * 1.45) * 0.07;
        let rotY = pointerCurrent.x * 0.3;
        let rotX = -pointerCurrent.y * 0.12;
        let rotZ = flightBankZ;
        let headTiltZ = -pointerCurrent.x * 0.08;
        let lArmY = -0.66 + (reducedMotion ? 0 : Math.sin(elapsed * 1.45 + 0.5) * 0.045);
        let rArmY = -0.66 + (reducedMotion ? 0 : Math.sin(elapsed * 1.45 + 0.5) * 0.045);
        let lArmZ = 0;
        let rArmZ = 0;
        let eyeScaleY = 1;
        let sparkleBoost = 1;

        if (pointDirection < -0.2) {
            lArmY = -0.46;
            lArmZ = 0.78;
            rotY -= 0.22;
        } else if (pointDirection > 0.2) {
            rArmY = -0.46;
            rArmZ = -0.78;
            rotY += 0.22;
        }

        blinkTimer -= delta;
        if (blinkTimer <= 0 && blinkProgress < 0) {
            blinkProgress = 0;
            blinkTimer = 2.5 + Math.random() * 2.8;
        }
        if (blinkProgress >= 0) {
            blinkProgress += delta * 6.5;
            eyeScaleY = Math.max(0.1, Math.abs(Math.cos(blinkProgress * Math.PI)));
            if (blinkProgress >= 1) blinkProgress = -1;
        }

        if (activeReaction && !reducedMotion) {
            reactionElapsed += delta;
            const t = Math.min(1, reactionElapsed / reactionDuration);
            const env = Math.sin(t * Math.PI);

            if (activeReaction === 'wave') {
                rArmY = -0.36;
                rArmZ = -1.2 + Math.sin(elapsed * 13) * 0.32;
                headTiltZ = 0.16 * env;
                eyeScaleY = 0.45 + 0.55 * (1 - env);
                blushMat.opacity = 0.32 + 0.42 * env;
            } else if (activeReaction === 'spin') {
                const eased = 0.5 - 0.5 * Math.cos(t * Math.PI);
                rotY += eased * Math.PI * 2;
                bobY += Math.sin(t * Math.PI) * 0.26;
                sparkleBoost = 2.4;
            } else if (activeReaction === 'cheer') {
                lArmY = -0.35;
                rArmY = -0.35;
                lArmZ = 1.22 * env;
                rArmZ = -1.22 * env;
                bobY += Math.abs(Math.sin(t * Math.PI * 2.5)) * 0.18;
                eyeScaleY = 0.36;
                blushMat.opacity = 0.75;
                sparkleBoost = 2.0;
            } else if (activeReaction === 'dance') {
                rotZ += Math.sin(elapsed * 6.5) * 0.14 * env;
                rotY += Math.sin(elapsed * 3.2) * 0.3 * env;
                lArmY = -0.52 + Math.sin(elapsed * 6.5) * 0.14;
                rArmY = -0.52 - Math.sin(elapsed * 6.5) * 0.14;
                sparkleBoost = 1.8;
            } else if (activeReaction === 'peek') {
                bobY -= 0.22 * env;
                rotX += 0.2 * env;
                headTiltZ = 0.18 * env;
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

        headGroup.rotation.y = pointerCurrent.x * 0.22;
        headGroup.rotation.x = -pointerCurrent.y * 0.16;
        headGroup.rotation.z = headTiltZ;

        faceFeatures.position.x = pointerCurrent.x * 0.045;
        faceFeatures.position.y = -0.02 + pointerCurrent.y * 0.035;

        leftEyeGroup.scale.y = eyeScaleY;
        rightEyeGroup.scale.y = eyeScaleY;

        bodyGroup.position.y = -0.76 - bobY * 0.25;
        leftArmGroup.position.y = lArmY;
        leftArmGroup.rotation.z = lArmZ;
        rightArmGroup.position.y = rArmY;
        rightArmGroup.rotation.z = rArmZ;

        antennaGroup.rotation.z = -rotZ * 0.7 + Math.sin(elapsed * 2.8) * 0.06;
        antennaHalo.rotation.z = elapsed * 0.95;

        sparkleMeshes.forEach((sp, idx) => {
            sp.userData.angle += delta * sp.userData.speed * sparkleBoost;
            const a = sp.userData.angle;
            sp.position.set(
                Math.cos(a) * sp.userData.radius,
                sp.userData.yOffset + Math.sin(elapsed * 1.8 + idx) * 0.08,
                Math.sin(a) * sp.userData.radius * 0.56,
            );
            sp.rotation.y = elapsed * 1.4;
            sp.rotation.x = elapsed * 1.0;
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

function wireAssistantDrawer(host, instance, showBriefSpeech) {
    const drawer = host.querySelector('[data-lumi-drawer]');
    const toggleBtn = host.querySelector('[data-lumi-toggle]');
    const closeBtn = host.querySelector('[data-lumi-close]');
    const form = host.querySelector('[data-lumi-form]');
    const input = host.querySelector('[data-lumi-input]');
    const log = host.querySelector('[data-lumi-log]');
    const quickRow = host.querySelector('.lumi-quick-row');
    if (!drawer || !form || !input || !log) return;

    const askUrl = host.dataset.oxyUrl || '/oxy/ask';
    const studioSlug = host.dataset.studioSlug || '';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const history = [];

    const setOpen = (open) => {
        drawer.hidden = !open;
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            setTimeout(() => input.focus(), 40);
        }
    };

    toggleBtn?.addEventListener('click', (ev) => {
        ev.stopPropagation();
        setOpen(drawer.hidden);
    });

    closeBtn?.addEventListener('click', (ev) => {
        ev.stopPropagation();
        setOpen(false);
    });

    const appendMsg = (text, role, links = []) => {
        const bubble = document.createElement('div');
        bubble.className = `lumi-msg lumi-msg--${role}`;
        bubble.textContent = text;
        if (Array.isArray(links) && links.length > 0) {
            const linkRow = document.createElement('div');
            linkRow.className = 'lumi-msg-links';
            links.forEach((lnk) => {
                const a = document.createElement('a');
                a.href = lnk.url;
                a.textContent = lnk.label;
                linkRow.appendChild(a);
            });
            bubble.appendChild(linkRow);
        }
        log.appendChild(bubble);
        log.scrollTop = log.scrollHeight;
        return bubble;
    };

    const renderSuggestions = (suggestions) => {
        if (!quickRow || !Array.isArray(suggestions) || suggestions.length === 0) return;
        quickRow.innerHTML = '';
        suggestions.slice(0, 4).forEach((prompt) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lumi-quick-btn';
            btn.textContent = prompt;
            btn.addEventListener('click', () => sendQuestion(prompt));
            quickRow.appendChild(btn);
        });
    };

    const sendQuestion = async (text) => {
        const q = String(text || '').trim();
        if (!q) return;
        appendMsg(q, 'user');
        const priorHistory = history.slice(-6);
        history.push({ role: 'user', text: q.slice(0, 500) });
        input.value = '';
        input.disabled = true;

        const typingBubble = appendMsg('Oxy is typing…', 'bot');
        typingBubble.style.opacity = '0.7';

        try {
            const res = await fetch(askUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                body: JSON.stringify({
                    message: q,
                    studio_slug: studioSlug || null,
                    history: priorHistory,
                }),
            });
            const data = await res.json().catch(() => null);
            typingBubble.remove();
            const replyText = data?.reply || 'Oops, lost my train of thought for a second — try asking me again!';
            appendMsg(replyText, 'bot', data?.links || []);
            history.push({ role: 'assistant', text: replyText.slice(0, 500) });
            renderSuggestions(data?.suggestions);
            instance?.triggerReaction(data?.reaction || 'wave', false);
            if (drawer.hidden) {
                showBriefSpeech(replyText.split('\n')[0]);
            }
        } catch {
            typingBubble.remove();
            appendMsg('Hit a quick connection hiccup — mind trying that again?', 'bot');
        } finally {
            input.disabled = false;
            input.focus();
        }
    };

    form.addEventListener('submit', (ev) => {
        ev.preventDefault();
        sendQuestion(input.value);
    });

    host.querySelectorAll('[data-lumi-prompt]').forEach((btn) => {
        btn.addEventListener('click', () => sendQuestion(btn.dataset.lumiPrompt));
    });

    return { setOpen, isOpen: () => !drawer.hidden };
}

function initMascots() {
    document.querySelectorAll('[data-obs-mascot]').forEach((host) => {
        if (host.dataset.mascotReady === '1') return;
        host.dataset.mascotReady = '1';

        const mode = host.dataset.mascotMode || 'floating-assistant';
        const canvas = host.querySelector('[data-mascot-canvas]');
        const speechEl = host.querySelector('[data-mascot-speech]');
        const flyerEl = host.querySelector('[data-mascot-flyer]');
        if (!canvas || !flyerEl) return;

        let speechTimer = null;
        const showBriefSpeech = (msg, durationMs = 3200) => {
            if (!speechEl || !msg) return;
            speechEl.textContent = msg;
            speechEl.classList.add('is-visible');
            clearTimeout(speechTimer);
            speechTimer = setTimeout(() => {
                speechEl.classList.remove('is-visible');
            }, durationMs);
        };

        const isHeroGuide = mode === 'hero-guide';
        const anchorEl = host.querySelector('[data-mascot-anchor]');
        const controlsEl = host.querySelector('[data-mascot-controls]');

        const stops = [
            { id: 'features', xPct: 0.85, yPct: 0.62, dir: -1, line: 'Taps reach OBS in under a second!', reaction: 'wave' },
            { id: 'overlays', xPct: 0.12, yPct: 0.56, dir: 1, line: 'Try these live controls right here!', reaction: 'cheer' },
            { id: 'features-showcase', xPct: 0.86, yPct: 0.44, dir: -1, line: 'Everything built for smooth streams!', reaction: 'dance' },
            { id: 'faqs', xPct: 0.14, yPct: 0.52, dir: 1, line: 'Got questions? Quick answers here.', reaction: 'peek' },
            { id: 'closing-heading', xPct: 0.85, yPct: 0.66, dir: -1, line: 'Ready? Open Studio or Mobile Deck!', reaction: 'spin' },
        ];

        const baseSize = 320;
        let curX = 0;
        let curY = 0;
        let curScale = 1;
        let targetX = 0;
        let targetY = 0;
        let targetScale = 1;
        let initializedPos = false;

        let isDragging = false;
        let dragMoved = false;
        let dragStartX = 0;
        let dragStartY = 0;
        let dragOriginX = 0;
        let dragOriginY = 0;
        let pinnedUntil = 0;
        let pinnedX = 0;
        let pinnedY = 0;
        let wasPinned = false;
        let lastStopId = null;

        const computeTarget = () => {
            const isMobile = window.innerWidth < 1024;
            const anchorRect = anchorEl?.getBoundingClientRect();
            const inHero = isHeroGuide && anchorRect && anchorRect.bottom > 150 && window.scrollY < 160;

            if (Date.now() < pinnedUntil) {
                wasPinned = true;
                targetScale = inHero ? Math.min(1, anchorRect.width / baseSize) : (isMobile ? 0.36 : 0.52);
                const effSize = baseSize * targetScale;
                targetX = Math.max(8, Math.min(window.innerWidth - effSize - 8, pinnedX));
                targetY = Math.max(56, Math.min(window.innerHeight - effSize - 8, pinnedY));
                flyerEl.dataset.side = targetX < window.innerWidth / 2 ? 'left' : 'right';
                return;
            }

            if (wasPinned) {
                wasPinned = false;
                showBriefSpeech('Flying back to my spot!', 2200);
            }

            if (inHero) {
                const s = Math.min(1.05, Math.max(0.62, anchorRect.width / baseSize));
                targetScale = s;
                const effSize = baseSize * s;
                targetX = anchorRect.left + (anchorRect.width - effSize) / 2;
                targetY = anchorRect.top + (anchorRect.height - effSize) / 2;
                flyerEl.dataset.side = 'hero';
                if (controlsEl) controlsEl.style.opacity = '1';
                instance?.setPointDirection(0, 0);
                if (lastStopId !== 'hero') {
                    lastStopId = 'hero';
                }
                return;
            }

            if (controlsEl) controlsEl.style.opacity = '0';

            if (!isHeroGuide) {
                targetScale = isMobile ? 0.34 : 0.46;
                const effSize = baseSize * targetScale;
                targetX = window.innerWidth - effSize - (isMobile ? 10 : 18);
                targetY = window.innerHeight - effSize - (isMobile ? 56 : 18);
                flyerEl.dataset.side = 'right';
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

            if (isMobile) {
                targetScale = 0.38;
                const effSize = baseSize * targetScale;
                targetX = window.innerWidth - effSize - 10;
                targetY = window.innerHeight - effSize - 14;
                flyerEl.dataset.side = 'right';
                instance?.setPointDirection(-1, 0);
            } else {
                targetScale = 0.62;
                const effSize = baseSize * targetScale;
                const docMax = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
                const scrollFrac = Math.max(0, Math.min(1, window.scrollY / docMax));
                const swayX = Math.sin(scrollFrac * Math.PI * 3) * 20;
                const swayY = Math.cos(scrollFrac * Math.PI * 2.5) * 18;
                targetX = Math.max(12, Math.min(window.innerWidth - effSize - 12, window.innerWidth * activeStop.xPct - effSize / 2 + swayX));
                targetY = Math.max(72, Math.min(window.innerHeight - effSize - 18, window.innerHeight * activeStop.yPct - effSize / 2 + swayY));
                flyerEl.dataset.side = activeStop.dir > 0 ? 'left' : 'right';
                instance?.setPointDirection(activeStop.dir, activeStop.dir * 0.06);
            }

            if (lastStopId !== activeStop.id) {
                lastStopId = activeStop.id;
                instance?.triggerReaction(activeStop.reaction, activeStop.line);
            }
        };

        const instance = createMascotInstance(canvas, {
            mode,
            disableAutoTap: true,
            onSpeech: (msg) => showBriefSpeech(msg, 3200),
            onFrame: (delta) => {
                computeTarget();
                if (!initializedPos) {
                    curX = targetX;
                    curY = targetY;
                    curScale = targetScale;
                    initializedPos = true;
                } else if (!isDragging) {
                    // Slower, buttery-smooth lerp
                    const k = 1 - Math.exp(-delta * 2.4);
                    const vx = targetX - curX;
                    curX += vx * k;
                    curY += (targetY - curY) * k;
                    curScale += (targetScale - curScale) * k;
                    if (Math.abs(vx) > 2.5) {
                        instance?.setPointDirection(vx > 0 ? 1 : -1, Math.max(-0.16, Math.min(0.16, -vx * 0.0009)));
                    }
                }
                flyerEl.style.transform = `translate3d(${curX.toFixed(1)}px, ${curY.toFixed(1)}px, 0) scale(${curScale.toFixed(4)})`;
                flyerEl.style.setProperty('--mascot-inv-scale', (1 / Math.max(curScale, 0.3)).toFixed(3));
            },
        });

        if (!instance) {
            const fallback = host.querySelector('[data-mascot-fallback]');
            if (fallback) fallback.hidden = false;
            flyerEl.hidden = true;
            return;
        }

        const drawerCtrl = wireAssistantDrawer(host, instance, showBriefSpeech);

        // Brief initial greeting only on hero, then fades out cleanly
        if (isHeroGuide && speechEl?.textContent?.trim()) {
            showBriefSpeech(speechEl.textContent.trim(), 2800);
        }

        canvas.addEventListener('pointerdown', (ev) => {
            isDragging = true;
            dragMoved = false;
            dragStartX = ev.clientX;
            dragStartY = ev.clientY;
            dragOriginX = curX;
            dragOriginY = curY;
            canvas.style.cursor = 'grabbing';
            canvas.setPointerCapture(ev.pointerId);
        });

        canvas.addEventListener('pointermove', (ev) => {
            if (!isDragging) return;
            const dx = ev.clientX - dragStartX;
            const dy = ev.clientY - dragStartY;
            if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
                dragMoved = true;
            }
            if (dragMoved) {
                const effSize = baseSize * curScale;
                curX = Math.max(8, Math.min(window.innerWidth - effSize - 8, dragOriginX + dx));
                curY = Math.max(56, Math.min(window.innerHeight - effSize - 8, dragOriginY + dy));
                pinnedX = curX;
                pinnedY = curY;
                pinnedUntil = Date.now() + 5000;
                instance.setPointDirection(0, Math.max(-0.2, Math.min(0.2, -dx * 0.0015)));
                flyerEl.style.transform = `translate3d(${curX.toFixed(1)}px, ${curY.toFixed(1)}px, 0) scale(${curScale.toFixed(4)})`;
                flyerEl.style.setProperty('--mascot-inv-scale', (1 / Math.max(curScale, 0.3)).toFixed(3));
            }
        });

        const endDrag = (ev) => {
            if (!isDragging) return;
            isDragging = false;
            canvas.style.cursor = 'grab';
            if (canvas.hasPointerCapture?.(ev.pointerId)) {
                canvas.releasePointerCapture(ev.pointerId);
            }
            if (dragMoved) {
                pinnedX = curX;
                pinnedY = curY;
                pinnedUntil = Date.now() + 5000;
                showBriefSpeech('Staying right here for 5s!', 2500);
            } else {
                instance.triggerReaction();
                if (!isHeroGuide && drawerCtrl) {
                    drawerCtrl.setOpen(!drawerCtrl.isOpen());
                }
            }
        };

        canvas.addEventListener('pointerup', endDrag);
        canvas.addEventListener('pointercancel', endDrag);

        host.querySelectorAll('[data-mascot-react]').forEach((btn) => {
            btn.addEventListener('click', (ev) => {
                ev.stopPropagation();
                instance.triggerReaction(btn.dataset.mascotReact);
            });
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMascots);
} else {
    initMascots();
}
