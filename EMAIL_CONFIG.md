# Email Configuration for PIN Code Authentication

# Add these settings to your .env file

# Mail Configuration

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@domain.com
MAIL_FROM_NAME="Finboard System"

# Alternative: Use Gmail SMTP

# MAIL_MAILER=smtp

# MAIL_HOST=smtp.gmail.com

# MAIL_PORT=587

# MAIL_USERNAME=your-gmail@gmail.com

# MAIL_PASSWORD=your-gmail-app-password

# MAIL_ENCRYPTION=tls

# MAIL_FROM_ADDRESS=your-gmail@gmail.com

# MAIL_FROM_NAME="Finboard System"

# Alternative: Use Mailgun (Recommended for production)

# MAIL_MAILER=mailgun

# MAILGUN_DOMAIN=your-domain.mailgun.org

# MAILGUN_SECRET=your-mailgun-secret-key

# MAILGUN_ENDPOINT=api.mailgun.net

# MAIL_FROM_ADDRESS=noreply@yourdomain.com

# MAIL_FROM_NAME="Finboard System"

# Alternative: Use SendGrid

# MAIL_MAILER=sendgrid

# SENDGRID_API_KEY=your-sendgrid-api-key

# MAIL_FROM_ADDRESS=noreply@yourdomain.com

# MAIL_FROM_NAME="Finboard System"

# Alternative: Use Log (for development/testing)

# MAIL_MAILER=log

# (PIN codes will be logged to storage/logs/laravel.log)
