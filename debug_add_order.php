<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Product Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h2>Debug: Product Selection Test</h2>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Test Product Loading</h3>
                <button class="btn btn-primary" onclick="testProductLoad()">Load Products</button>
                <div id="productStatus" class="mt-2"></div>
                
                <h3 class="mt-4">Products List</h3>
                <div id="productsList" style="max-height: 400px; overflow-y: auto;"></div>
            </div>
            
            <div class="col-md-6">
                <h3>Order Items</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="debug-order-table">
                        <tr>
                            <td colspan="6" class="text-center text-muted">No products added</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mt-3">
                    <strong>Total: ₱<span id="debugTotal">0.00</span></strong>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Debug Console</h3>
            <div id="debugConsole" style="background: #f8f9fa; padding: 15px; border-radius: 5px; height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let debugProducts = [];
        
        function debugLog(message) {
            const console = document.getElementById('debugConsole');
            const timestamp = new Date().toLocaleTimeString();
            console.innerHTML += `[${timestamp}] ${message}<br>`;
            console.scrollTop = console.scrollHeight;
            console.log(message);
        }
        
        function testProductLoad() {
            debugLog('Starting product load test...');
            const statusDiv = document.getElementById('productStatus');
            statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Loading...';
            
            fetch('fetch_products.php')
                .then(response => {
                    debugLog(`Response status: ${response.status}`);
                    debugLog(`Response headers: ${JSON.stringify([...response.headers])}`);
                    return response.text();
                })
                .then(text => {
                    debugLog(`Raw response: ${text.substring(0, 200)}...`);
                    
                    let data;
                    try {
                        data = JSON.parse(text);
                        debugLog('JSON parsed successfully');
                    } catch (e) {
                        throw new Error(`JSON parse error: ${e.message}`);
                    }
                    
                    if (data.success) {
                        debugProducts = data.products;
                        debugLog(`Loaded ${debugProducts.length} products`);
                        statusDiv.innerHTML = `<span class="text-success">✓ Loaded ${debugProducts.length} products</span>`;
                        displayDebugProducts();
                    } else {
                        throw new Error(data.message || 'API returned error');
                    }
                })
                .catch(error => {
                    debugLog(`Error: ${error.message}`);
                    statusDiv.innerHTML = `<span class="text-danger">✗ Error: ${error.message}</span>`;
                });
        }
        
        function displayDebugProducts() {
            const container = document.getElementById('productsList');
            
            if (debugProducts.length === 0) {
                container.innerHTML = '<p class="text-muted">No products found</p>';
                return;
            }
            
            let html = '<div class="list-group">';
            debugProducts.forEach(product => {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${product.name}</strong><br>
                                <small>ID: ${product.id} | Price: ₱${product.price} | Qty: ${product.quantity}</small>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="debugSelectProduct(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price_raw})">
                                Select
                            </button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
            debugLog('Products displayed');
        }
        
        function debugSelectProduct(productId, productName, productPrice) {
            debugLog(`Selecting product: ID=${productId}, Name=${productName}, Price=${productPrice}`);
            
            const tableBody = document.getElementById('debug-order-table');
            
            // Check if product already exists
            const existingRow = tableBody.querySelector(`tr[data-id="${productId}"]`);
            if (existingRow) {
                debugLog('Product already exists, updating quantity');
                const qtyInput = existingRow.querySelector('.qty-input');
                const newQty = parseInt(qtyInput.value) + 1;
                qtyInput.value = newQty;
                updateDebugRowTotal(existingRow, productPrice);
                return;
            }
            
            // Remove "no products" row if it exists
            const noProductsRow = tableBody.querySelector('tr td[colspan="6"]');
            if (noProductsRow) {
                noProductsRow.closest('tr').remove();
            }
            
            // Add new row
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-id', productId);
            newRow.innerHTML = `
                <td>${productId}</td>
                <td>${productName}</td>
                <td><input type="number" class="form-control qty-input" value="1" min="1" style="width: 60px;" onchange="updateDebugRowTotal(this.closest('tr'), ${productPrice})"></td>
                <td>₱${productPrice.toFixed(2)}</td>
                <td class="row-total">₱${productPrice.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-danger" onclick="removeDebugRow(this)">Remove</button></td>
            `;
            
            tableBody.appendChild(newRow);
            updateDebugTotal();
            
            debugLog(`Product added: ${productName}`);
            
            Swal.fire({
                icon: 'success',
                title: 'Product Added!',
                text: `${productName} added to order`,
                timer: 1000,
                showConfirmButton: false
            });
        }
        
        function updateDebugRowTotal(row, unitPrice) {
            const qtyInput = row.querySelector('.qty-input');
            const quantity = parseInt(qtyInput.value) || 1;
            const total = quantity * unitPrice;
            
            row.querySelector('.row-total').textContent = `₱${total.toFixed(2)}`;
            updateDebugTotal();
        }
        
        function updateDebugTotal() {
            const rows = document.querySelectorAll('#debug-order-table tr[data-id]');
            let total = 0;
            
            rows.forEach(row => {
                const totalText = row.querySelector('.row-total').textContent.replace('₱', '');
                total += parseFloat(totalText) || 0;
            });
            
            document.getElementById('debugTotal').textContent = total.toFixed(2);
        }
        
        function removeDebugRow(button) {
            const row = button.closest('tr');
            row.remove();
            updateDebugTotal();
            
            // Add "no products" row if table is empty
            const tableBody = document.getElementById('debug-order-table');
            if (tableBody.children.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No products added</td></tr>';
            }
        }
        
        // Auto-load products on page load
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('Page loaded, testing product fetch...');
            testProductLoad();
        });
    </script>
</body>
</html>
