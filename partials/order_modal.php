          <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                      <div class="modal-header" style="background-color: #410101; color: white;">
                          <h5 class="modal-title" id="productModalLabel">
                              <i class="fa fa-plus-circle"></i> Add Product
                          </h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                          <!-- Search Bar -->
                          <div class="mb-3">
                              <div class="input-group">
                                  <span class="input-group-text"><i class="fa fa-search"></i></span>
                                  <input type="text" id="productSearch" class="form-control" placeholder="Search products by name, category, or description...">
                              </div>
                          </div>
                          
                          <!-- Products Table -->
                          <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                              <table class="table table-hover">
                                  <thead class="table-light sticky-top">
                                       <tr class="text-center">
                                          <th>ID</th>
                                          <th>Picture</th>
                                          <th>Name</th>
                                          <th>Category</th>
                                          <th>Quantity</th>
                                          <th>Price</th>
                                          <th>Description</th>
                                          <th>Action</th>
                                      </tr>
                                  </thead>
                                  <tbody id="productsTableBody">
                                      <!-- Products will be loaded here -->
                                      <tr>
                                          <td colspan="8" class="text-center">
                                              <div class="spinner-border text-primary" role="status">
                                                  <span class="visually-hidden">Loading...</span>
                                              </div>
                                              <div class="mt-2">Loading products...</div>
                                          </td>
                                      </tr>
                                  </tbody>
                              </table>
                          </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary px-4 py-2 shadow-sm" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #6c757d, #5a6268); border: none; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;">
                              <i class="bi bi-x-circle me-2"></i>Close
                          </button>
                      </div>
                  </div>
              </div>
          </div>