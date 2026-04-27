<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();

// Get search and filter parameters
$search          = $_GET['q'] ?? '';
$category_filter = $_GET['category_id'] ?? '';
$type_filter     = $_GET['type'] ?? '';

// Build query
$sql = "SELECT b.*, c.name AS category, bc.label AS condition_label,
       u.full_name AS seller_name,
       (SELECT image_path FROM book_images WHERE book_id=b.id AND is_primary=1 LIMIT 1) AS cover_image
FROM books b
JOIN categories c ON b.category_id = c.id
JOIN book_conditions bc ON b.condition_id = bc.id
JOIN users u ON b.seller_id = u.id
WHERE b.status = 'available'";

$params = [];

if (!empty($search)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($category_filter)) {
    $sql .= " AND b.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($type_filter)) {
    $sql .= " AND b.listing_type = ?";
    $params[] = $type_filter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories for filter
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories      = $categories_stmt->fetchAll();

$page_title = 'Browse Books — Campus Connect';

// Condition badge colour map
function condition_badge_style(string $label): string {
    return match(strtolower($label)) {
        'new'       => 'background:#dcfce7; color:#15803d; border:1.5px solid #16a34a;',
        'like new'  => 'background:#dbeafe; color:#1d4ed8; border:1.5px solid #3b82f6;',
        'good'      => 'background:#fef9c3; color:#854d0e; border:1.5px solid #ca8a04;',
        'fair'      => 'background:#ffedd5; color:#c2410c; border:1.5px solid #f97316;',
        'poor','used' => 'background:#fee2e2; color:#b91c1c; border:1.5px solid #ef4444;',
        default     => 'background:#f3f4f6; color:#374151; border:1.5px solid #9ca3af;',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Barlow+Condensed:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --red:#E01B1B; --yellow:#F5C518; --black:#111111; --off-white:#F2F0EB; }
        * { font-family: 'Barlow Condensed', sans-serif; box-sizing: border-box; }
        .mono { font-family: 'Space Mono', monospace; }
        body { background-color: var(--off-white); color: var(--black); }

        /* ── Search bar ── */
        .search-wrap {
            display: flex;
            border: 2px solid var(--black);
            box-shadow: 4px 4px 0 var(--black);
            background: #fff;
            overflow: hidden;
        }
        .search-wrap input {
            flex: 1;
            border: none;
            outline: none;
            padding: 14px 18px;
            font-family: 'Space Mono', monospace;
            font-size: 0.85rem;
            background: transparent;
            color: var(--black);
        }
        .search-wrap input::placeholder { color: #aaa; }
        .search-btn {
            background: var(--black);
            color: #fff;
            border: none;
            padding: 0 28px;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 900;
            font-size: 1rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.15s;
        }
        .search-btn:hover { background: var(--red); }

        /* ── Filter pills ── */
        .pill {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 6px 16px;
            border: 2px solid var(--black);
            text-decoration: none;
            color: var(--black);
            background: #fff;
            transition: background 0.12s, color 0.12s;
            white-space: nowrap;
        }
        .pill:hover { background: #e5e3de; }
        .pill.active { background: var(--black); color: #fff; }
        .pill.sale { background: var(--yellow); }

        /* ── Book card ── */
        .book-card {
            background: #fff;
            border: 2px solid var(--black);
            display: flex;
            flex-direction: column;
            transition: transform 0.12s, box-shadow 0.12s;
            position: relative;
        }
        .book-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 5px 5px 0 var(--black);
        }
        .book-card img {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            border-bottom: 2px solid var(--black);
            display: block;
        }
        .book-card .no-img {
            width: 100%;
            aspect-ratio: 3/4;
            background: #f3f4f6;
            border-bottom: 2px solid var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .book-card .body { padding: 14px; flex: 1; display: flex; flex-direction: column; }

        .condition-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.7rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 8px;
        }

        .btn-buy {
            background: var(--black);
            color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 8px 16px;
            border: 2px solid var(--black);
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: auto;
            transition: background 0.12s;
        }
        .btn-buy:hover { background: var(--red); border-color: var(--red); }

        .section-label {
            font-weight: 900;
            font-size: 1.7rem;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flash-success { background:#f0fdf4; border:2px solid #16a34a; color:#15803d; padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.8rem; }
        .flash-error   { background:#fef2f2; border:2px solid var(--red); color:var(--red); padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.8rem; }
        .flash-warning { background:#fffbeb; border:2px solid #f59e0b; color:#92400e; padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.8rem; }
    </style>
</head>
<body>

<?php
// Inline the nav (header.php uses require from pages/ so paths need adjusting)
require_once '../includes/header.php';
?>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="mb-6 <?= match($flash['type']) { 'success'=>'flash-success','error'=>'flash-error','warning'=>'flash-warning', default=>'flash-info' } ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- ── HERO ── -->
    <div style="margin-bottom:48px;">
        <div style="margin-bottom:20px;">
            <h1 style="font-weight:900; font-size:clamp(2.4rem,6vw,4.5rem); line-height:0.95; text-transform:uppercase; color:var(--black);">
                Find your class<br>materials. <span style="color:var(--red); font-style:italic;">Fast.</span>
            </h1>
            <p class="mono" style="font-size:0.8rem; color:#666; margin-top:12px; max-width:440px; line-height:1.7;">
                The retro-cool marketplace for students. Buy, sell, and swap textbooks without the campus bookstore markup.
            </p>
        </div>

        <!-- Search -->
        <form method="GET" action="" style="max-width:620px; margin-bottom:16px;">
            <div class="search-wrap">
                <svg style="margin-left:16px; flex-shrink:0; align-self:center; color:#aaa;" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke-width="2" stroke-linecap="round"/></svg>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search for ISBN, Title, Author, or Class...">
                <button type="submit" class="search-btn">GO</button>
            </div>
        </form>

        <!-- Filter pills -->
        <div class="flex flex-wrap gap-2">
            <a href="home.php" class="pill <?= (empty($category_filter) && empty($type_filter) && empty($search)) ? 'active' : '' ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="home.php?category_id=<?= $cat['id'] ?>" class="pill <?= $category_filter == $cat['id'] ? 'active' : '' ?>">
                    #<?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
            <a href="home.php?type=sell" class="pill sale <?= $type_filter === 'sell' ? 'active' : '' ?>">#Sale</a>
        </div>
    </div>

    <!-- ── RESULTS ── -->
    <?php if (!empty($search) || !empty($category_filter) || !empty($type_filter)): ?>
        <div style="margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
            <div class="section-label">
                Search Results
                <span class="mono" style="font-size:0.85rem; font-weight:400; color:#888;">(<?= count($books) ?> found)</span>
            </div>
        </div>
    <?php else: ?>
        <div style="margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
            <div>
                <div class="section-label">Fresh Drops 📚</div>
                <p class="mono" style="font-size:0.75rem; color:#888; margin-top:2px;">Just listed by students near you</p>
            </div>
            <a href="home.php?q=a" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; font-size:0.9rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--red); text-decoration:none;">View All Listings →</a>
        </div>
    <?php endif; ?>

    <?php if (empty($books)): ?>
        <div style="background:#fff; border:2px solid var(--black); padding:60px; text-align:center;">
            <p style="font-size:1.3rem; font-weight:700; text-transform:uppercase; color:#aaa;">No books found.</p>
            <a href="home.php" style="color:var(--red); font-weight:700; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.06em;">Clear filters →</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <!-- Condition badge -->
                    <span class="condition-tag" style="<?= condition_badge_style($book['condition_label']) ?>">
                        <?= e(strtoupper($book['condition_label'])) ?>
                    </span>

                    <?php if ($book['cover_image']): ?>
                        <img src="/campus-connect/<?= e($book['cover_image']) ?>" alt="<?= e($book['title']) ?>">
                    <?php else: ?>
                        <div class="no-img">
                            <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                    <?php endif; ?>

                    <div class="body">
                        <p style="font-family:'Space Mono',monospace; font-size:0.65rem; color:#aaa; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;"><?= e($book['seller_name']) ?></p>
                        <h3 style="font-weight:700; font-size:1rem; line-height:1.2; margin-bottom:4px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;"><?= e($book['title']) ?></h3>
                        <p style="font-size:0.82rem; color:#777; margin-bottom:12px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;"><?= e($book['author'] ?? 'Unknown Author') ?></p>

                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                            <span style="font-weight:900; font-size:1.15rem; color:var(--red);"><?= format_price($book['price'], $book['listing_type']) ?></span>
                        </div>

                        <a href="book_detail.php?id=<?= $book['id'] ?>" class="btn-buy">BUY</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── BOTTOM CTA STRIP ── -->
    <?php if (empty($search) && empty($category_filter) && empty($type_filter)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" style="margin-top:64px;">
            <!-- Sell -->
            <div style="background:#fce4e4; border:2px solid var(--black); padding:36px 32px; position:relative; overflow:hidden;">
                <div style="position:absolute; right:-20px; bottom:-20px; width:120px; height:120px; background:rgba(224,27,27,0.08); border-radius:50%;"></div>
                <h3 style="font-weight:900; font-size:1.6rem; text-transform:uppercase; margin-bottom:8px;">Sell Your Books</h3>
                <p class="mono" style="font-size:0.75rem; color:#555; line-height:1.6; margin-bottom:20px;">Clear your shelf and fill your wallet. Listing takes less than 2 minutes.</p>
                <a href="book_add.php" style="display:inline-block; background:var(--black); color:#fff; font-weight:700; font-size:0.9rem; letter-spacing:0.1em; text-transform:uppercase; padding:10px 22px; border:2px solid var(--black); text-decoration:none; transition:background 0.12s;" class="hover:bg-red-700">Start Selling</a>
            </div>

            <!-- Community -->
            <div style="background:#fefce8; border:2px solid var(--black); padding:36px 32px; position:relative; overflow:hidden;">
                <div style="position:absolute; right:-20px; bottom:-20px; width:120px; height:120px; background:rgba(245,197,24,0.15); border-radius:50%;"></div>
                <h3 style="font-weight:900; font-size:1.6rem; text-transform:uppercase; margin-bottom:8px;">Join Community</h3>
                <p class="mono" style="font-size:0.75rem; color:#555; line-height:1.6; margin-bottom:20px;">Connect with students on your campus. Swap notes, tips, and books.</p>
                <a href="#" style="display:inline-block; background:var(--black); color:#fff; font-weight:700; font-size:0.9rem; letter-spacing:0.1em; text-transform:uppercase; padding:10px 22px; border:2px solid var(--black); text-decoration:none;" class="hover:bg-gray-900">Join Discord</a>
            </div>
        </div>
    <?php endif; ?>

<?php require_once '../includes/footer.php'; ?>