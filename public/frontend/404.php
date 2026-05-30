<?php 
http_response_code(404);
$pageName = '404';
$pageTitle = 'Page Not Found - Royal Beverages';
require_once __DIR__ . "/components/header.php"; 
?>

<main class="not-found-page">
    <section class="not-found-hero">
        <div class="container text-center">
            <h1 class="not-found-title">404</h1>
            <h2 class="not-found-subtitle">Page Not Found</h2>
            <p class="not-found-tagline">Oops! It looks like the page you are looking for has been moved, deleted, or doesn't exist.</p>
            
            <div class="not-found-actions">
                <a href="<?= getPageUrl('home') ?>" class="btn btn-gold">Return to Home</a>
                <a href="<?= getPageUrl('shop') ?>" class="btn btn-outline">Browse Our Shop</a>
            </div>
        </div>
    </section>
</main>

<style>
.not-found-page {
    background: var(--white);
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.not-found-hero {
    padding: 100px 0;
}

.not-found-title {
    font-family: var(--font-serif);
    font-size: 8rem;
    font-weight: 300;
    color: var(--gold);
    margin: 0;
    line-height: 1;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.not-found-subtitle {
    font-family: var(--font-sans);
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--black);
    margin: var(--space-md) 0 var(--space-sm);
}

.not-found-tagline {
    color: var(--gray-500);
    font-size: 1.125rem;
    max-width: 600px;
    margin: 0 auto var(--space-2xl);
    line-height: 1.6;
}

.not-found-actions {
    display: flex;
    gap: var(--space-md);
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .not-found-title { font-size: 5rem; }
    .not-found-subtitle { font-size: 1.75rem; }
}
</style>

<?php require_once __DIR__ . "/components/footer.php"; ?>
