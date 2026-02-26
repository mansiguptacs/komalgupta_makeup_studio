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
?>
<section class="page-section">
    <div class="container">
        <h1>Latest News</h1>
        <p>Updates from Komal Gupta Makeup Studio—new services, offers, and studio news.

        <?php if (!empty($news_items)): ?>
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
            <p class="message error">No news items yet. Add rows to <code>data/news.csv</code> (columns: date, title, summary).</p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
