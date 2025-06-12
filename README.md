# Strathmore University Tutoring Platform

A secure, student-only web platform where Strathmore University students can offer and receive tutoring service.

## Features

### Authentication & Access

- Strathmore email validation (@strathmore.edu)
- Email verification
- Optional two-factor authentication
- Student ID verification

### User Profiles

- Tutor and tutee profiles
- Profile verification for tutors
- Ratings and reviews system
- Subject expertise listing

### Booking System

- Availability scheduling
- Session management
- Calendar integration
- Session reminders

### Communication

- In-app messaging
- Session notes
- Real-time notifications
- Email notifications

### Payment System (Optional)

- In-app credits
- M-Pesa integration
- Secure transactions
- Platform commission handling

### Admin Features

- User management
- Tutor verification
- Dispute resolution
- Analytics dashboard

## Technology Stack

- PHP 7.4+
- MySQL
- Bootstrap 5
- jQuery
- Google Authenticator (for 2FA)
- PHPMailer (for email)

## Installation

1. Clone the repository:

```bash
git clone https://github.com/yourusername/tutorapp.git
cd tutorapp
```

2. Install dependencies:

```bash
composer install
```

3. Create a MySQL database and import the schema:

```bash
mysql -u your_username -p your_database_name < config/schema.sql
```

4. Configure the database connection:

- Copy `config/database.example.php` to `config/database.php`
- Update the database credentials

5. Configure email settings:

- Update SMTP settings in `config/config.php`

6. Set up the web server:

- Point your web server to the project directory
- Ensure the `uploads` directory is writable

## Directory Structure

```
tutorapp/
├── admin/           # Admin panel files
├── assets/          # Static assets
│   ├── css/        # Stylesheets
│   ├── js/         # JavaScript files
│   └── images/     # Images
├── auth/            # Authentication files
├── config/          # Configuration files
├── includes/        # Common includes
├── tutor/           # Tutor panel files
├── tutee/           # Tutee panel files
├── uploads/         # User uploads
└── vendor/          # Composer dependencies
```

## Security Features

- Password hashing
- SQL injection prevention
- XSS protection
- CSRF protection
- Input validation
- Secure session handling
- Two-factor authentication

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, email support@tutorapp.strathmore.edu or visit the Strathmore University IT Help Desk.

## Acknowledgments

- Strathmore University
- All contributors
- Open source libraries used in this project
