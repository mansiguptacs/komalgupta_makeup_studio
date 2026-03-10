<?php
$page_title = 'News';
require_once __DIR__ . '/includes/header.php';

$news_file = __DIR__ . '/data/news.csv';
$news_items = [];
if (file_exists($news_file) && is_readable($news_file)) {
    $handle = fopen($news_file, 'r');
    if ($handle !== false) {
        $header = array_map('trim', fgetcsv($handle, 0, ",", "\"", "\\"));
        while (($row = fgetcsv($handle, 0, ",", "\"", "\\")) !== false) {
            if (count($row) >= count($header)) {
                $news_items[] = array_combine($header, array_map('trim', array_pad($row, count($header), '')));
            }
        }
        fclose($handle);
    }
}
// Newest first
usort($news_items, function ($a, $b) {
    return strcmp(isset($b['date']) ? $b['date'] : '', isset($a['date']) ? $a['date'] : '');
});

// Gallery: Indian bridal & makeup imagery (Unsplash — free to use under Unsplash License).
// Swap src to your own assets/gallery/*.jpg when you have studio photos.
$gallery_images = [
    [
        'src' => 'https://images.unsplash.com/photo-1742891602815-fa2a195f0ab3?auto=format&fit=crop&w=800&q=82',
        'alt' => 'Traditional Indian bridal makeup',
    ],
    [
        'src' => 'https://images.unsplash.com/photo-1742891603141-95b87334ce00?auto=format&fit=crop&w=800&q=82',
        'alt' => 'Indian bride in traditional attire',
    ],
    [
        'src' => 'https://images.unsplash.com/photo-1762708594532-46345d574457?auto=format&fit=crop&w=800&q=82',
        'alt' => 'Bridal jewellery and makeup',
    ],
    [
        'src' => 'https://images.unsplash.com/photo-1770747874505-67d0a43379d5?auto=format&fit=crop&w=800&q=82',
        'alt' => 'Wedding attire with makeup',
    ],
    [
        'src' => 'https://images.unsplash.com/photo-1638628064365-f08ad0ec8245?auto=format&fit=crop&w=800&q=82',
        'alt' => 'Makeup artist at work',
    ],
    [
        'src' => 'https://images.unsplash.com/photo-1616513779308-3691c6901813?auto=format&fit=crop&w=800&q=82',
        'alt' => 'Bridal lehenga and look',
    ],
];
?>
<section class="page-section">
    <div class="container">
        <h1>Latest News</h1>
        <p>Updates from Komal Gupta Makeup Studio—new services, offers, and studio news.</p>

        <?php if (!empty($news_items)): ?>
            <h2 class="gallery-section-title" style="margin-top: 2.5rem;">News &amp; updates</h2>
            <div class="news-list">
                <?php foreach ($news_items as $item): ?>
                    <article class="news-item">
                        <time datetime="<?php echo htmlspecialchars(isset($item['date']) ? $item['date'] : ''); ?>"><?php echo date('F j, Y', strtotime(isset($item['date']) ? $item['date'] : 'now')); ?></time>
                        <h2><?php echo htmlspecialchars(isset($item['title']) ? $item['title'] : ''); ?></h2>
                        <p><?php echo htmlspecialchars(isset($item['summary']) ? $item['summary'] : ''); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="message error" style="margin-top: 2rem;">No news items yet. Add rows to <code>data/news.csv</code> (columns: date, title, summary).</p>
        <?php endif; ?>

        <h2 class="gallery-section-title">Gallery</h2>
        <p class="lead" style="margin-top: -0.5rem;">A glimpse of Indian bridal and party makeup styles.</p>
        <div class="news-gallery">
            <?php foreach ($gallery_images as $img): ?>
                <figure class="news-gallery-item">
                    <img src="<?php echo htmlspecialchars($img['src']); ?>" alt="<?php echo htmlspecialchars($img['alt']); ?>" loading="lazy" width="600" height="400">
                    <figcaption><?php echo htmlspecialchars($img['alt']); ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
