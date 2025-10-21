# Purissima Dashboard - PHP Version

A simple PHP version of the Purissima orders dashboard system.

## Features

- **Order Management**: View, filter, and manage orders from Purissima API
- **Order States**: Track active and removed orders
- **Item Aggregation**: View aggregated items across orders
- **Production Control**: Track production progress for items
- **Search & Filter**: Search orders by ID, name, document
- **Date Filtering**: Filter orders by date range
- **Responsive Design**: Mobile-friendly interface using Tailwind CSS

## Installation

1. **Requirements**:

   - PHP 7.4 or higher
   - Web server (Apache/Nginx)
   - cURL extension enabled

2. **Setup**:

   ```bash
   # Clone or copy the PHP files to your web server
   # Ensure the php/ directory is accessible via web server
   ```

3. **Configuration**:
   - Set the `PURISSIMA_ORDERS_URL` environment variable if needed
   - Default API URL: `https://api.purissima.com/provisorio/pedidos-dia.php`

## File Structure

```
php/
├── index.php                 # Main dashboard page
├── includes/
│   ├── functions.php         # Core functions
│   └── order-utils.php       # Order utility functions
├── templates/
│   ├── active-orders.php     # Active orders template
│   ├── removed-orders.php    # Removed orders template
│   ├── items-aggregates.php  # Items aggregation template
│   └── production.php        # Production control template
├── js/
│   └── dashboard.js         # Frontend JavaScript
├── api/
│   └── orders.php           # API endpoints
└── README.md
```

## Usage

1. **Access the Dashboard**:

   - Open `http://your-server/php/index.php` in your browser

2. **Main Features**:

   - **Active Orders**: View and manage current orders
   - **Removed Orders**: View and restore removed orders
   - **Items Aggregates**: View item quantities across orders
   - **Production**: Track production progress

3. **Order Operations**:

   - **View Details**: Click the eye icon to view order details
   - **Remove Orders**: Select orders and click "Remove Selected"
   - **Restore Orders**: In removed orders tab, select and restore

4. **Filtering**:

   - **Date Range**: Set from/to dates to filter orders
   - **Search**: Use the search box to find orders by ID, name, or document
   - **Limit**: Set a limit on the number of orders displayed

5. **Production Control**:
   - **Update Quantities**: Click edit icon to update produced quantities
   - **Track Progress**: View production progress with visual indicators

## API Endpoints

- `GET /api/orders.php` - Fetch orders
- `POST /api/orders.php?action=remove_orders` - Remove orders
- `POST /api/orders.php?action=restore_orders` - Restore orders
- `POST /api/orders.php?action=update_production` - Update production

## Configuration

### Environment Variables

- `PURISSIMA_ORDERS_URL`: API endpoint URL (optional)
- `PSS_VERBOSE`: Enable verbose logging (optional)

### Default Settings

- **Default Status**: `released`
- **Default Lookback**: 30 days
- **Filter Min Date**: `2025-10-08T20:00`

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- CSS Grid and Flexbox support

## Styling

The dashboard uses:

- **Tailwind CSS**: For responsive design and styling
- **Font Awesome**: For icons
- **Custom CSS**: For print styles and additional styling

## Session Management

The application uses PHP sessions to store:

- Removed orders list
- Production state
- User preferences

## Error Handling

- API errors are displayed to users
- Console logging for debugging
- Graceful fallbacks for missing data

## Security Considerations

- Input validation and sanitization
- XSS protection with `htmlspecialchars()`
- CSRF protection (can be added)
- Session security

## Performance

- Efficient data processing
- Minimal database queries (uses sessions)
- Responsive design for mobile devices
- Optimized JavaScript

## Customization

### Adding New Features

1. **New Tabs**: Add new tab content in `templates/`
2. **New API Endpoints**: Extend `api/orders.php`
3. **New Functions**: Add to `includes/functions.php`

### Styling Changes

- Modify Tailwind classes in templates
- Add custom CSS in `index.php`
- Update JavaScript in `js/dashboard.js`

## Troubleshooting

### Common Issues

1. **API Connection Failed**:

   - Check internet connection
   - Verify API URL is accessible
   - Check PHP cURL extension

2. **Session Issues**:

   - Ensure sessions are enabled
   - Check session storage permissions

3. **JavaScript Errors**:
   - Check browser console
   - Verify all JS files are loaded

### Debug Mode

Enable verbose logging by setting `PSS_VERBOSE=true` in environment.

## License

This is a simplified PHP version of the original Next.js dashboard system.
