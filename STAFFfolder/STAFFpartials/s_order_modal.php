<!-- Staff Order Modal: s_order_modal.php -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #410101; color: white;">
        <h5 class="modal-title" id="productModalLabel">
          <i class="bi bi-box-seam"></i> Select Product
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle text-center">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Price</th>
                <th>Description</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="productsTableBody">
              <!-- Products will be loaded here dynamically -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary px-4 py-2 shadow-sm" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #6c757d, #5a6268); border: none; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;">
          <i class="bi bi-x-circle me-2"></i>Cancel
        </button>
      </div>
    </div>
  </div>
</div>
