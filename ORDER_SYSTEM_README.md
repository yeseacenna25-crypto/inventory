# Orders System Setup and Usage

## Database Setup

1. **Create the required tables** by running the SQL commands in `create_orders_tables.sql`:
   - Open phpMyAdmin or your MySQL client
   - Select the `inventory_negrita` database
   - Run the SQL commands from the file

   This will create:
   - `orders` table - stores main order information
   - `order_items` table - stores individual order items
   - `customers` table - optional customer management

## Files Added/Modified

### New Backend Files:
- `create_orders_tables.sql` - Database table creation script
- `process_order.php` - Handles order creation
- `fetch_orders.php` - Retrieves orders list with filters
- `fetch_order_details.php` - Gets detailed order information
- `update_order_status.php` - Updates order status

### Modified Frontend Files:
- `add_order.php` - Enhanced with customer selection and order creation
- `view_order.php` - Complete order management interface

## Features

### Add Order (`add_order.php`)
- **Customer Selection**: Modal form to enter customer details
- **Product Selection**: Browse and add products from inventory
- **Real-time Calculations**: Automatic total calculation
- **Stock Validation**: Prevents ordering more than available stock
- **Order Creation**: Complete order processing with stock deduction

### View Orders (`view_order.php`)
- **Order Listing**: Display all orders with pagination
- **Filtering**: Filter by status, date range
- **Order Details**: View complete order information in modal
- **Status Management**: Update order status (pending, processing, completed, cancelled)

## Order Status Workflow

1. **Pending** - New orders (default status)
2. **Processing** - Orders being prepared
3. **Completed** - Orders fulfilled
4. **Cancelled** - Orders cancelled

## Stock Management

- When an order is created, product quantities are automatically reduced
- Stock validation prevents overselling

## Usage Instructions

### Creating an Order:
1. Go to Add Order page
2. Click "Select Customer" and enter customer information
3. Click "Add Product" to browse and select products
4. Set quantities for each product
5. Review total amount
6. Click "Create Order"

### Managing Orders:
1. Go to View Order page
2. Use filters to find specific orders
3. Click "View Details" to see order information
4. Click "Update Status" to change order status
5. Use date filters to view orders from specific periods

## Error Handling

The system includes comprehensive error handling for:
- Database connection issues
- Invalid data inputs
- Stock validation
- User authentication
- Transaction failures

## Security Features

- Session-based authentication
- SQL injection prevention using prepared statements
- Input validation and sanitization
- CSRF protection through session verification

## Browser Compatibility

- Modern browsers with JavaScript enabled
- Bootstrap 5 for responsive design
- SweetAlert2 for user-friendly notifications
