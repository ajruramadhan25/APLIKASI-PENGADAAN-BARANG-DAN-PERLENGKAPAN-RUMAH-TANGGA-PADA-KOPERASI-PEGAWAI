# Customer CRUD System Documentation

## Overview
Sistem CRUD (Create, Read, Update, Delete) untuk manajemen Customer yang dibangun dengan pemrograman berorientasi objek (OOP) dan menggunakan komponen pre-existing.

## Features

### ✅ **CRUD Operations**
- **Create**: Tambah customer baru
- **Read**: Tampilkan daftar customer dengan pagination dan search
- **Update**: Edit data customer
- **Delete**: Hapus customer (dengan validasi relasi)

### ✅ **Advanced Features**
- Real-time search
- Pagination
- Form validation
- AJAX operations
- Responsive design
- Export to CSV
- Auto-save draft
- Keyboard shortcuts

## File Structure

```
├── classes/
│   └── Customer.php          # Customer class (OOP)
├── customers.php             # Main customer management page
├── assets/
│   ├── css/
│   │   └── customer.css      # Customer-specific styles
│   └── js/
│       └── customer.js       # Customer JavaScript functionality
```

## Database Schema

### Customer Table
```sql
CREATE TABLE customer (
    id_customer INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nama_customer VARCHAR(150) NOT NULL,
    alamat TEXT,
    telp VARCHAR(50),
    fax VARCHAR(50),
    email VARCHAR(150)
);
```

## OOP Implementation

### Customer Class Methods

#### Constructor
```php
public function __construct($database)
```
- Initialize database connection

#### Create Customer
```php
public function create($data)
```
- Insert new customer record
- Returns success/error response

#### Read Operations
```php
public function readAll($search = '', $limit = 10, $offset = 0)
public function readById($id)
```
- Get all customers with search and pagination
- Get single customer by ID

#### Update Customer
```php
public function update($id, $data)
```
- Update existing customer record

#### Delete Customer
```php
public function delete($id)
```
- Delete customer with validation (check for sales relations)

#### Validation
```php
public function validate($data, $id = null)
```
- Validate customer data
- Check for duplicate email

## User Interface

### Main Features
- **Search Bar**: Real-time search by name, email, or phone
- **Add Button**: Open modal to add new customer
- **Edit Button**: Edit existing customer data
- **Delete Button**: Delete customer with confirmation
- **Export Button**: Export data to CSV

### Modal Forms
- **Add Customer Modal**: Form to create new customer
- **Edit Customer Modal**: Pre-filled form to edit customer
- **Delete Confirmation Modal**: Confirmation before deletion

### Form Fields
- **Nama Customer** (Required): Customer name
- **Alamat**: Customer address
- **Telepon**: Phone number
- **Fax**: Fax number
- **Email**: Email address with validation

## JavaScript Functionality

### AJAX Operations
- **Create**: POST request to create customer
- **Read**: GET request to fetch customer data
- **Update**: POST request to update customer
- **Delete**: POST request to delete customer

### Form Validation
- **Real-time validation**: Validate fields on blur
- **Email validation**: Check email format
- **Phone validation**: Check phone format
- **Required field validation**: Ensure required fields are filled

### User Experience
- **Auto-save draft**: Save form data to localStorage
- **Keyboard shortcuts**: 
  - `Ctrl+N`: New customer
  - `Ctrl+F`: Focus search
  - `Escape`: Close modals
- **Loading states**: Show loading during operations
- **Notifications**: Success/error messages

## CSS Styling

### Design System
- **Modern UI**: Clean, professional design
- **Responsive**: Mobile-first approach
- **Animations**: Smooth transitions and hover effects
- **Color Scheme**: Consistent with dashboard theme

### Components
- **Modal**: Backdrop blur with slide-in animation
- **Table**: Sticky header with hover effects
- **Buttons**: Gradient backgrounds with hover states
- **Form**: Focus states and error styling

## API Endpoints

### POST /customers.php
```php
// Create customer
action=create
nama_customer, alamat, telp, fax, email

// Update customer
action=update
id, nama_customer, alamat, telp, fax, email

// Delete customer
action=delete
id

// Get customer
action=get
id
```

## Usage Examples

### Adding New Customer
1. Click "Tambah Customer" button
2. Fill required fields (Nama Customer)
3. Optionally fill other fields
4. Click "Simpan"
5. Success notification appears
6. Table refreshes with new data

### Editing Customer
1. Click edit button (pencil icon) in table row
2. Modal opens with pre-filled data
3. Make changes
4. Click "Simpan"
5. Changes are saved and table updates

### Searching Customers
1. Type in search box
2. Press Enter or click "Cari"
3. Table filters results
4. Pagination updates

### Deleting Customer
1. Click delete button (trash icon)
2. Confirmation modal appears
3. Click "Hapus" to confirm
4. Customer is deleted (if no sales relations)

## Security Features

### Validation
- **Server-side validation**: PHP validation in Customer class
- **Client-side validation**: JavaScript validation for UX
- **SQL injection protection**: Prepared statements
- **XSS protection**: HTML escaping

### Access Control
- **Session-based authentication**: Login required
- **User permissions**: Based on user level
- **CSRF protection**: Form tokens (can be added)

## Error Handling

### Database Errors
- Connection errors
- Query errors
- Constraint violations

### Validation Errors
- Required field errors
- Format validation errors
- Duplicate data errors

### User Feedback
- Success messages
- Error messages
- Loading indicators

## Performance Optimizations

### Database
- **Indexed columns**: For search performance
- **Prepared statements**: For security and performance
- **Pagination**: Limit results per page

### Frontend
- **AJAX**: No page reloads
- **Debounced search**: Reduce API calls
- **Lazy loading**: Load data as needed

## Browser Compatibility

### Supported Browsers
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

### Features Used
- **ES6+**: Modern JavaScript
- **CSS Grid**: Layout system
- **Fetch API**: AJAX requests
- **LocalStorage**: Draft saving

## Future Enhancements

### Planned Features
- **Bulk operations**: Select multiple customers
- **Advanced filters**: Filter by date, status
- **Customer categories**: Group customers
- **Import functionality**: CSV import
- **Customer history**: Track changes
- **Photo upload**: Customer photos

### Technical Improvements
- **Caching**: Redis/Memcached
- **API versioning**: RESTful API
- **Unit tests**: PHPUnit tests
- **Code documentation**: PHPDoc

## Troubleshooting

### Common Issues

#### Customer not saving
- Check required fields
- Verify email format
- Check for duplicate email

#### Search not working
- Clear search and try again
- Check database connection
- Verify search parameters

#### Modal not opening
- Check JavaScript console
- Verify modal HTML structure
- Check CSS conflicts

### Debug Mode
Enable debug mode by adding to config:
```php
define('DEBUG_MODE', true);
```

## Support

For issues or questions:
1. Check browser console for JavaScript errors
2. Check PHP error logs
3. Verify database connection
4. Test with different browsers

---

**Created**: September 2024  
**Version**: 1.0.0  
**Author**: Development Team
