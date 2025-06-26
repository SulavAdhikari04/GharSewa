# GharSewa

GharSewa is a web-based platform for booking reliable home services in Kathmandu, Lalitpur, and Bhaktapur. It connects customers with verified service providers for a variety of household needs, including plumbing, electrical work, cleaning, carpentry, appliance repair, and more.

## Features
- Book home services online with ease
- Services available: Plumbing, Electrical, Cleaning, Carpentry, Housekeeping, Appliance Repair, AC Servicing, Computer Support, Packers & Movers, Home Renovation
- Separate dashboards for Admin, Customers, and Service Providers
- User registration and login system
- Real-time booking management and status tracking
- Provider verification and transparent pricing
- 24/7 support and emergency services
- Customer reviews and ratings

## Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/SulavAdhikari04/GharSewa.git
   ```
2. Place the project in your XAMPP `htdocs` directory (or your web server root).
3. Import the required MySQL database (see `register.php` and other PHP files for table structure).
4. Update database credentials in PHP files if needed.
5. Start Apache and MySQL from XAMPP.
6. Access the app at `http://localhost/gharsewa/home.php`.

## Usage
- **Customers:** Register, log in, and book services. Track bookings and manage your profile.
- **Service Providers:** Register, log in, manage offered services, view bookings, and update your profile.
- **Admin:** Log in to manage users, providers, bookings, and services.

## File Structure
- `home.php` / `index.html`: Landing page
- `register.php`, `login.php`: Authentication
- `customer-home.php`, `customer-dashboard.php`: Customer interface
- `provider-dashboard.php`: Provider interface
- `admin-dashboard.php`: Admin interface
- `book-service.php`: Service booking form
- CSS and images for UI styling

## Contact
- Email: sulavadhikari69@gmail.com
- Phone: +977-9810029888

---
© 2025 GharSewa. All rights reserved.
