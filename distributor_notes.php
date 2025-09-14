<?php
// distributor_notes.php
// Displays notes sent by distributors in their orders

session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination settings
$notesPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $notesPerPage;

// Get total count of orders with notes
$countSql = "SELECT COUNT(*) as total FROM orders 
             WHERE order_notes IS NOT NULL AND TRIM(order_notes) != ''";
$countResult = $conn->query($countSql);
$totalNotes = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalNotes / $notesPerPage);

// Fetch only orders that have notes with pagination
$sql = "SELECT order_id, customer_name, order_notes, created_at 
        FROM orders 
        WHERE order_notes IS NOT NULL AND TRIM(order_notes) != '' 
        ORDER BY created_at DESC
        LIMIT $notesPerPage OFFSET $offset";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distributor Notes</title>
    <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>" />
        <link rel="stylesheet" type="text/css" href="CSS/notes_ui.css?v=<?= time(); ?>" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
        
</head>
<body>

<button class="mobile-menu-btn" id="mobile-menu-btn" style="display:none;">
    <i class="fa fa-bars"></i>
</button>
<div class="sidebar-overlay"></div>

<div id="dashboardMainContainer">

  <!-- SIDEBAR -->
  <?php include('partials/sidebar.php'); ?>
  <!-- SIDEBAR -->

     <div class="dashboard_content">
        <!-- TOP NAVBAR -->
  <?php include('partials/topnav.php'); ?>
  <!-- TOP NAVBAR -->
          <div class="dashboard_content_main">
      <?php
      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }
      if (!isset($_SESSION['distributor_id'])) {
        header('Location: distri_login.php');
        exit();
      }
      $conn = new mysqli('localhost', 'root', '', 'inventory_negrita');
      if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
      }
      $distributor_id = $_SESSION['distributor_id'];
      $sql = "SELECT o.order_id, o.customer_name, o.created_at, o.order_notes,
          GROUP_CONCAT(oi.product_name SEPARATOR ', ') AS products,
          SUM(oi.quantity) AS total_quantity
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.distributor_id = ? AND o.order_notes IS NOT NULL AND TRIM(o.order_notes) != ''
        GROUP BY o.order_id
        ORDER BY o.created_at DESC";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('i', $distributor_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $notes = [];
      while ($row = $result->fetch_assoc()) {
          $notes[] = $row;
      }
      ?>
      
      <div class="container-fluid px-4">
        <!-- Page Header -->

        <h1 class="fw-bold" style="color: #6a0000;"><i class="bi bi-chat-quote-fill me-2"></i>ORDER NOTES</h1>
          <p class="page-subtitle">Review distributor order notes and comments</p>

        <!-- Search Container -->
        <div class="search-container">
          <div class="position-relative">
            <input type="text" class="form-control search-input" id="searchInput" 
                   placeholder="Search by orderID or customer name...">
          </div>
          
          <div class="filter-tabs">
            <div class="filter-tab active" data-filter="all">
              <i class="bi bi-list-ul me-1"></i>All
            </div>
            <div class="filter-tab" data-filter="recent">
              <i class="bi bi-clock me-1"></i>Recent
            </div>
            <div class="filter-tab" data-filter="month">
              <i class="bi bi-calendar-month me-1"></i>Older
            </div>
          </div>
        </div>

        <!-- Notes List -->
        <div class="notes-grid" id="notesGrid">
          <?php if (!empty($notes)): ?>
            <?php foreach ($notes as $note): ?>
              <div class="note-card" 
                   data-order-id="<?= htmlspecialchars($note['order_id']) ?>"
                   data-customer="<?= htmlspecialchars(strtolower($note['customer_name'])) ?>"
                   data-note="<?= htmlspecialchars(strtolower($note['order_notes'])) ?>"
                   data-date="<?= htmlspecialchars($note['created_at']) ?>">
                
                <div class="note-left">
                  <div class="order-id">
                    <i class="bi bi-receipt"></i>
                    #<?= htmlspecialchars($note['order_id']) ?>
                  </div>
                  
                  <div class="customer-name">
                    <i class="bi bi-person-fill"></i>
                    <?= htmlspecialchars($note['customer_name']) ?>
                  </div>
                  
                  <div class="note-text" title="<?= htmlspecialchars($note['order_notes']) ?>">
                    <?= htmlspecialchars($note['order_notes']) ?>
                  </div>
                </div>
                
                <div class="note-right">
                  <div class="order-date">
                    <i class="bi bi-calendar3"></i>
                    <?= date('M d, Y', strtotime($note['created_at'])) ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-results col-12">
              <i class="bi bi-chat-square-dots"></i>
              <h4>No Order Notes Found</h4>
              <p>There are currently no orders with notes from distributors.</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalNotes > 0): ?>
        <div class="pagination-container">
          <div class="pagination-info">
            Showing <?= min(($currentPage - 1) * $notesPerPage + 1, $totalNotes) ?> - <?= min($currentPage * $notesPerPage, $totalNotes) ?> 
          </div>
          
          <div class="pagination-nav">
            <!-- Previous Button -->
            <?php if ($currentPage > 1): ?>
              <a href="?page=<?= $currentPage - 1 ?>" class="pagination-btn">
                <i class="bi bi-chevron-left"></i> Previous
              </a>
            <?php else: ?>
              <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">
                <i class="bi bi-chevron-left"></i> Previous
              </span>
            <?php endif; ?>
            
            <!-- Page Numbers -->
            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            if ($startPage > 1): ?>
              <a href="?page=1" class="pagination-btn">1</a>
              <?php if ($startPage > 2): ?>
                <span class="pagination-btn" style="cursor: default;">...</span>
              <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
              <a href="?page=<?= $i ?>" class="pagination-btn <?= $i == $currentPage ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
            
            <?php if ($endPage < $totalPages): ?>
              <?php if ($endPage < $totalPages - 1): ?>
                <span class="pagination-btn" style="cursor: default;">...</span>
              <?php endif; ?>
              <a href="?page=<?= $totalPages ?>" class="pagination-btn"><?= $totalPages ?></a>
            <?php endif; ?>
            
            <!-- Next Button -->
            <?php if ($currentPage < $totalPages): ?>
              <a href="?page=<?= $currentPage + 1 ?>" class="pagination-btn">
                Next <i class="bi bi-chevron-right"></i>
              </a>
            <?php else: ?>
              <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">
                Next <i class="bi bi-chevron-right"></i>
              </span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- No Search Results Message -->
        <div class="no-results" id="noResults" style="display: none;">
          <i class="bi bi-search"></i>
          <h4>No Results Found</h4>
          <p>Try adjusting your search terms or filters.</p>
        </div>
      </div>
      
      <?php $stmt->close(); ?>
      </div>
</div>

<script src="sidebar-drawer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterTabs = document.querySelectorAll('.filter-tab');
    const noteCards = document.querySelectorAll('.note-card');
    const notesGrid = document.getElementById('notesGrid');
    const noResults = document.getElementById('noResults');
    
    let currentFilter = 'all';
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        filterAndSearch(searchTerm, currentFilter);
    });
    
    // Filter tabs functionality
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            currentFilter = this.getAttribute('data-filter');
            const searchTerm = searchInput.value.toLowerCase().trim();
            filterAndSearch(searchTerm, currentFilter);
        });
    });
    
    function filterAndSearch(searchTerm, filter) {
        let visibleCount = 0;
        const now = new Date();
        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
        
        noteCards.forEach(card => {
            const orderId = card.getAttribute('data-order-id');
            const customer = card.getAttribute('data-customer');
            const noteText = card.getAttribute('data-note');
            const dateStr = card.getAttribute('data-date');
            const cardDate = new Date(dateStr);
            
            // Search filter
            const matchesSearch = searchTerm === '' || 
                orderId.includes(searchTerm) ||
                customer.includes(searchTerm) ||
                noteText.includes(searchTerm);
            
            // Date filter
            let matchesDateFilter = true;
            if (filter === 'recent') {
                matchesDateFilter = cardDate >= weekAgo;
            } else if (filter === 'month') {
                matchesDateFilter = cardDate >= monthAgo;
            }
            
            if (matchesSearch && matchesDateFilter) {
                card.style.display = 'block';
                visibleCount++;
                
                // Add animation
                card.style.animation = 'none';
                card.offsetHeight; // Trigger reflow
                card.style.animation = 'fadeInUp 0.5s ease forwards';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0) {
            noResults.style.display = 'block';
            notesGrid.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            notesGrid.style.display = 'block';
        }
    }
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .note-card {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .search-input:focus + .search-suggestions {
            display: block;
        }
        
        .filter-tab {
            user-select: none;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 576px) {
            .search-input {
                font-size: 14px;
                padding: 10px 16px 10px 45px;
            }
            
            .note-card {
                padding: 1rem;
            }
            
            .order-id {
                font-size: 16px;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === '/') {
            e.preventDefault();
            searchInput.focus();
        }
        
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.blur();
        }
    });
    
    // Initialize with default view
    filterAndSearch('', 'all');
});
</script>

</body>
</html>
<?php
$conn->close();
?>
