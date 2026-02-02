# SemigApp - Business Management Suite

A comprehensive WordPress plugin for business management featuring Project Management, Event Calendar, Membership Management, Webshop with Swedish Payment Gateways, and Newsletter Management.

## Features

### Project & Task Management
- Create and manage projects with deadlines and priorities
- Task assignment and tracking
- Kanban-style task boards
- Time tracking
- Task comments and collaboration
- Email notifications for assignments and completions

### Event Calendar & Management
- Full event calendar with monthly view
- Event registration system
- **Event Application Management:**
  - Application workflow (submit → review → approve/reject)
  - Waitlist support with automatic promotion
  - Custom application fields per event
  - Application status tracking (pending, approved, rejected, waitlisted)
  - Email notifications for application status changes
  - Admin dashboard for reviewing applications
- Paid event support
- Attendee management and check-in
- Recurring events
- Email reminders for upcoming events

### Membership Management
- Multiple membership levels
- Subscription management
- Content restriction for members
- Automatic expiry handling
- Grace period support
- Trial periods
- Payment history tracking

### Webshop
- Product catalog management
- Shopping cart
- Order management
- Stock tracking
- Tax calculation (Swedish VAT support)
- Shipping management
- **Swedish Payment Gateways:**
  - Klarna (Pay now, Pay later, Slice it)
  - Swish (Mobile payments)
  - Stripe (Card payments)

### Newsletter & Subscriptions
- Email subscriber management
- Multiple subscriber lists
- Campaign creation and scheduling
- Double opt-in support
- Email tracking (opens, clicks)
- GDPR compliance
- Unsubscribe management
- Newsletter widget

### Additional Features
- Full REST API
- Comprehensive admin dashboard
- Activity logging
- Email notification system
- Internationalization ready
- Responsive design

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Installation

1. Upload the `semigapp` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to SemigApp > Settings to configure the plugin
4. Configure payment gateways in Settings > Payments

## Payment Gateway Configuration

### Klarna
1. Create a Klarna merchant account at https://www.klarna.com/business/
2. Obtain API credentials from Klarna Merchant Portal
3. Enter credentials in SemigApp > Settings > Payments > Klarna

### Swish
1. Contact your bank to set up Swish for Business
2. Obtain merchant certificate from Getswish
3. Configure merchant number and certificate path in settings

### Stripe
1. Create a Stripe account at https://stripe.com
2. Get API keys from Stripe Dashboard
3. Enter publishable and secret keys in settings

## Shortcodes

### Shop
- `[semigapp_shop]` - Display product catalog
- `[semigapp_cart]` - Shopping cart
- `[semigapp_checkout]` - Checkout page
- `[semigapp_my_orders]` - Customer order history

### Events
- `[semigapp_events]` - Event list
- `[semigapp_calendar]` - Event calendar
- `[semigapp_event id="123"]` - Single event
- `[semigapp_event_application id="123"]` - Event application form
- `[semigapp_my_applications]` - User's application list

### Membership
- `[semigapp_membership_levels]` - Display membership options
- `[semigapp_member_content]` - Restrict content to members

### Newsletter
- `[semigapp_newsletter_form]` - Subscription form
- `[semigapp_unsubscribe]` - Unsubscribe page

### Projects
- `[semigapp_projects]` - Project list
- `[semigapp_my_tasks]` - User's tasks
- `[semigapp_project_board id="123"]` - Kanban board

## REST API

The plugin provides a full REST API under the `/wp-json/semigapp/v1/` namespace.

### Endpoints
- `GET /projects` - List projects
- `GET /tasks` - List tasks
- `GET /events` - List events
- `POST /events/{id}/apply` - Submit event application
- `GET /events/{id}/applications` - Get event applications (admin)
- `GET /applications` - List all applications (admin)
- `GET /applications/{id}` - Get application details
- `POST /applications/{id}/approve` - Approve application
- `POST /applications/{id}/reject` - Reject application
- `POST /applications/{id}/waitlist` - Add to waitlist
- `DELETE /applications/{id}` - Cancel application
- `GET /products` - List products
- `GET/POST /cart` - Cart operations
- `POST /checkout` - Process checkout
- `POST /newsletter/subscribe` - Subscribe to newsletter

## Hooks & Filters

### Actions
- `semigapp_project_created` - Fired when project is created
- `semigapp_task_assigned` - Fired when task is assigned
- `semigapp_order_created` - Fired when order is placed
- `semigapp_member_created` - Fired when member is created
- `semigapp_application_submitted` - Fired when event application is submitted
- `semigapp_application_approved` - Fired when application is approved
- `semigapp_application_rejected` - Fired when application is rejected
- `semigapp_application_waitlisted` - Fired when application is waitlisted

### Filters
- `semigapp_task_statuses` - Modify task statuses
- `semigapp_order_statuses` - Modify order statuses
- `semigapp_enabled_gateways` - Modify available payment gateways

## Support

For support, please visit https://support.semigapp.com or create an issue on GitHub.

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Changelog

### 1.0.0
- Initial release
- Project & Task Management
- Event Calendar
- Membership Management
- Webshop with Klarna, Swish, Stripe
- Newsletter Management
- Full REST API
- Admin Dashboard
