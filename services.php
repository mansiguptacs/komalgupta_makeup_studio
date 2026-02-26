<?php
$page_title = 'Products & Services';
require_once __DIR__ . '/includes/header.php';

// Function to read CSV and return associative array
function readCsvData($filename) {
    $data = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 0, ",", "\"", "\\");
        while (($row = fgetcsv($handle, 0, ",", "\"", "\\")) !== FALSE) {
            if (count($headers) == count($row)) {
                $data[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
    }
    return $data;
}

// Read services and products
$services = readCsvData(__DIR__ . '/data/services.csv');
$products = readCsvData(__DIR__ . '/data/products.csv');

// Group services by category
$groupedServices = [];
foreach ($services as $service) {
    $category = $service['category'];
    if (!isset($groupedServices[$category])) {
        $groupedServices[$category] = [];
    }
    $groupedServices[$category][] = $service;
}

// Group products by category
$groupedProducts = [];
foreach ($products as $product) {
    $category = $product['category'];
    if (!isset($groupedProducts[$category])) {
        $groupedProducts[$category] = [];
    }
    $groupedProducts[$category][] = $product;
}
?>

<style>
/* Layout styling without enforcing specific color schemes */
.page-header-section {
    padding: 60px 0 30px;
    text-align: center;
    margin-bottom: 20px;
}

.page-header-section h1 {
    font-size: 3rem;
    margin-bottom: 15px;
    font-weight: 700;
}

.page-header-section .lead {
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto;
}

/* Tabs UI */
.tabs-nav {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
}

.tab-btn {
    background: transparent;
    border: 2px solid currentColor;
    color: inherit;
    padding: 12px 30px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 30px;
    cursor: pointer;
    transition: all 0.3s ease;
    opacity: 0.6;
}

.tab-btn.active, .tab-btn:hover {
    opacity: 1;
    background: rgba(0,0,0,0.05); /* Subtle active background */
}

.tab-pane {
    display: none;
    animation: fadeIn 0.4s ease-in-out;
}

.tab-pane.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Category Sidebar/Nav */
.content-wrapper {
    display: flex;
    gap: 40px;
    align-items: flex-start;
}

.category-nav {
    flex: 0 0 250px;
    position: sticky;
    top: 20px;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
}

.category-nav h4 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2rem;
    border-bottom: 2px solid rgba(0,0,0,0.1);
    padding-bottom: 10px;
}

.category-link {
    display: block;
    color: inherit;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 6px;
    margin-bottom: 5px;
    transition: all 0.2s ease;
    opacity: 0.8;
}

.category-link:hover, .category-link.active {
    background: rgba(0,0,0,0.04);
    font-weight: 600;
    opacity: 1;
}

.items-content {
    flex: 1;
    min-width: 0;
}

.category-section {
    margin-bottom: 50px;
    scroll-margin-top: 40px;
}

.category-title {
    font-size: 1.8rem;
    margin: 0 0 20px 0;
    padding-left: 10px;
    border-left: 4px solid rgba(0,0,0,0.2);
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.item-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
}

.item-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.item-title {
    font-size: 1.15rem;
    font-weight: 600;
    margin: 0;
    flex: 1;
    padding-right: 15px;
}

.item-price {
    font-size: 1.15rem;
    font-weight: 700;
}

.item-meta {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    border-top: 1px dashed rgba(0,0,0,0.1);
    padding-top: 12px;
    opacity: 0.7;
}

.item-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

@media (max-width: 768px) {
    .content-wrapper {
        flex-direction: column;
    }
    .category-nav {
        flex: auto;
        width: 100%;
        position: static;
        margin-bottom: 30px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        padding: 15px;
    }
    .category-nav h4 {
        width: 100%;
    }
    .category-link {
        margin-bottom: 0;
        font-size: 0.9rem;
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.05);
    }
}
</style>

<div class="page-header-section">
    <div class="container">
        <h1>Our Offerings</h1>
        <p class="lead">Explore our range of makeup services, beauty treatments, and premium products.</p>
    </div>
</div>

<section class="page-section" style="padding-top: 0;">
    <div class="container">
        
        <!-- Tabs Navigation -->
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab('services', this)">Studio Services</button>
            <button class="tab-btn" onclick="switchTab('products', this)">Premium Products</button>
        </div>

        <!-- Services Tab -->
        <div id="tab-services" class="tab-pane active">
            <div class="content-wrapper">
                <aside class="category-nav">
                    <h4>Service Categories</h4>
                    <?php foreach (array_keys($groupedServices) as $cat): ?>
                        <a href="#service-cat-<?php echo md5($cat); ?>" class="category-link" onclick="smoothScroll(event, 'service-cat-<?php echo md5($cat); ?>')">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </aside>
                
                <div class="items-content">
                    <?php foreach ($groupedServices as $category => $categoryServices): ?>
                        <div id="service-cat-<?php echo md5($category); ?>" class="category-section">
                            <h3 class="category-title"><?php echo htmlspecialchars($category); ?></h3>
                            <div class="items-grid">
                                <?php foreach ($categoryServices as $service): ?>
                                    <div class="item-card">
                                        <div class="item-header">
                                            <h4 class="item-title"><?php echo htmlspecialchars($service['name']); ?></h4>
                                            <div class="item-price">₹<?php echo number_format($service['price']); ?></div>
                                        </div>
                                        <div class="item-meta">
                                            <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($service['duration_minutes']); ?> mins</span>
                                            <!-- <span>ID: #< ?php echo htmlspecialchars($service['service_id']); ?></span> -->
                                            <span>
                                                <i class="fas fa-star"></i> Rating: <?php echo htmlspecialchars($service['rating']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Products Tab -->
        <div id="tab-products" class="tab-pane">
            <div class="content-wrapper">
                <aside class="category-nav">
                    <h4>Product Categories</h4>
                    <?php foreach (array_keys($groupedProducts) as $cat): ?>
                        <a href="#product-cat-<?php echo md5($cat); ?>" class="category-link" onclick="smoothScroll(event, 'product-cat-<?php echo md5($cat); ?>')">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </aside>
                
                <div class="items-content">
                    <?php foreach ($groupedProducts as $category => $categoryProducts): ?>
                        <div id="product-cat-<?php echo md5($category); ?>" class="category-section">
                            <h3 class="category-title"><?php echo htmlspecialchars($category); ?></h3>
                            <div class="items-grid">
                                <?php foreach ($categoryProducts as $product): ?>
                                    <div class="item-card">
                                        <div class="item-header">
                                            <h4 class="item-title"><?php echo htmlspecialchars($product['name']); ?></h4>
                                            <div class="item-price">₹<?php echo number_format($product['price']); ?></div>
                                        </div>
                                        <div class="item-meta">
                                            <!-- <span><i class="fas fa-box"></i> Stock: < ?php echo htmlspecialchars($product['stock']); ?></span>
                                            <span>ID: # < ?php echo htmlspecialchars($product['product_id']); ?></span>
                                         -->
                                            <span>
                                                <i class="fas fa-star"></i> Rating: <?php echo htmlspecialchars($product['rating']); ?>
                                            </span>
                                            
                                            <span>
                                                    <!-- add to cart -->
                                                <!-- <button class="add-to-cart" onclick="addToCart(< ?php echo $product['product_id']; ?>)">Add to Cart</button> -->
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Font Awesome for icons if not already included in header -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

<script>
function switchTab(tabId, btnElement) {
    // Hide all tabs
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    // Show selected tab
    document.getElementById('tab-' + tabId).classList.add('active');
    btnElement.classList.add('active');
}

function smoothScroll(e, targetId) {
    e.preventDefault();
    const target = document.getElementById(targetId);
    if(target) {
        const headerOffset = 40; // Reduced to account for sticky nav
        const elementPosition = target.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.scrollY - headerOffset;
  
        window.scrollTo({
             top: offsetPosition,
             behavior: "smooth"
        });
    }
}

function addToCart(productId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Product added to cart successfully!');
        } else {
            alert('Error adding to cart: ' + (data.error || 'Unknown error'));
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert('Failed to connect to the server.');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
