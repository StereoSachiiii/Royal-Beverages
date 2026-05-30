<?php
$heroTitle = $heroTitle ?? 'Title';
$heroSubtitle = $heroSubtitle ?? 'Subtitle';
$heroDescription = $heroDescription ?? '';
$heroId = $heroId ?? 'animatedHero';
$heroOffset = $heroOffset ?? '0%'; // '- 17.5%' for sidebar pages, '+ 15%' for center pages
$heroExtraHtml = $heroExtraHtml ?? '';
$heroBreadcrumbs = $heroBreadcrumbs ?? null;

if ($heroBreadcrumbs instanceof \App\UI\Breadcrumb) {
    $heroBreadcrumbs = $heroBreadcrumbs->getItems();
}
?>

<style>
.animated-hero-wrapper {
    position: relative;
    min-height: 50vh;
    transition: min-height 1s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    width: 100%;
    background: white;
    border-bottom: 1px solid #f3f4f6;
    margin-bottom: 5rem;
}
.animated-hero-wrapper.shrunk {
    min-height: 16vh;
    margin-bottom: 2rem;
}

.animated-hero-container {
    position: relative;
    width: 100%;
    height: 100%;
    max-width: 1440px;
    margin: 0 auto;
}

.animated-hero-inner {
    position: absolute;
    top: 50%;
    left: 50%;
    width: max-content;
    max-width: 100%;
    transform-origin: center center;
    transform: translate(-50%, -50%) scale(1);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: all 1s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    backface-visibility: hidden;
    will-change: transform, left;
}

.animated-hero-wrapper.shrunk .animated-hero-inner {
    left: 0;
    /* 
       Since the width is max-content, the box tightly fits the text. 
       Translating by -17.5% perfectly aligns the left edge of the 65% scaled 
       text to the left edge of the padded parent!
    */
    transform: translate(-17.5%, -50%) scale(0.65);
}

.animated-hero-desc {
    opacity: 1;
    max-height: 400px;
    margin-top: 1rem;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.animated-hero-wrapper.shrunk .animated-hero-desc {
    opacity: 0;
    max-height: 0;
    margin-top: 0;
}

.animated-hero-nav {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    width: 100%;
    margin-bottom: 2rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 18px;
    text-transform: uppercase;
    letter-spacing: 0.3em;
    font-weight: 900;
    color: #9ca3af;
}
</style>

<div id="<?= htmlspecialchars($heroId) ?>" class="animated-hero-wrapper" style="--hero-offset: <?= htmlspecialchars($heroOffset) ?>;">
    <div class="animated-hero-inner px-8">
        <?php if ($heroBreadcrumbs && is_array($heroBreadcrumbs)): ?>
            <nav class="animated-hero-nav">
                <?php foreach ($heroBreadcrumbs as $index => $crumb): ?>
                    <?php if ($index > 0): ?>
                        <span class="text-gray-200">/</span>
                    <?php endif; ?>
                    
                    <?php if (!empty($crumb['url'])): ?>
                        <a href="<?= htmlspecialchars($crumb['url']) ?>" class="hover:text-black transition-colors"><?= $crumb['label'] ?></a>
                    <?php else: ?>
                        <span class="text-black italic"><?= $crumb['label'] ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        
        <div>
            <span class="text-xs uppercase tracking-[0.4em] text-black font-extrabold mb-4 block italic"><?= htmlspecialchars($heroSubtitle) ?></span>
            <h1 class="text-4xl md:text-6xl font-heading font-extrabold uppercase tracking-widest text-black leading-none mb-4"><?= $heroTitle ?></h1>
        </div>
        
        <div class="animated-hero-desc flex flex-col items-center">
            <?php if ($heroDescription): ?>
                <p class="text-gray-400 text-base max-w-2xl mx-auto italic font-light leading-relaxed mb-8">
                    <?= htmlspecialchars($heroDescription) ?>
                </p>
            <?php endif; ?>
            
            <?= $heroExtraHtml ?>
        </div>
    </div>
</div>

<script>
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const hero = document.getElementById('<?= htmlspecialchars($heroId) ?>');
                if (hero) hero.classList.add('shrunk');
            }, 1000);
        });
    } else {
        setTimeout(() => {
            const hero = document.getElementById('<?= htmlspecialchars($heroId) ?>');
            if (hero) hero.classList.add('shrunk');
        }, 1000);
    }
</script>
