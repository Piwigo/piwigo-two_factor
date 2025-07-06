# Two Factor Authentication
Two Factor Authentication is a security plugin for Piwigo. This plugin adds an extra layer of security to your Piwigo gallery by requiring a second authentication factor after entering your username and password.

![GitHub issues](https://img.shields.io/github/issues/Piwigo/piwigo-two_factor?color=yellow) ![Static Badge](https://img.shields.io/badge/v16.x+-pwg?label=piwigo)

> **⚠️ Important Requirements**  
> This plugin requires **Piwigo 16.x or higher** and **standard pages** must be activated for the two-factor authentication to work properly.

## Summary
- [Installation](#installation)
  - [For Users](#for-users)
  - [For Developers](#for-developers)
- [Features](#features)
- [Supported Methods](#supported-methods)
- [Configuration](#configuration)
- [How It Works](#how-it-works)
- [Security Features](#security-features)
- [License](#license)

## Installation
### For Users
1. **Install from Admin Panel** (Recommended):
   - Log in to your Piwigo administration dashboard.
   - Go to `Administration` → `Plugins` → `Add a new plugin`.
   - Search for "Two Factor" in the plugin repository.
   - Click "Add" and then "Activate it now".

2. **Manual Installation** (Alternative):
   - Go to the [Piwigo Extensions](https://piwigo.org/ext/) page.
   - Search for "Two Factor" and download the latest version.
   - Unzip the downloaded file.
   - Upload the extracted folder to your Piwigo gallery's `plugins` directory. The path should look like `your-gallery/plugins/two_factor`.
   - Go to `Administration` → `Plugins` and activate the plugin.

3. **Configure the Plugin**:
   - Navigate to `Administration` → `Plugins` → `Two Factor`.
   - Configure your preferred settings and enable the desired authentication methods.

### For Developers
1. **Clone the Repository**:
   - Clone the Two Factor Authentication repository to your local machine using:
     ```bash
     git clone https://github.com/Piwigo/piwigo-two_factor.git
     ```
2. **Development Setup**:
   - Navigate to the cloned directory and place it in your Piwigo's `plugins` folder.
   - Rename the folder to `two_factor` if needed.
3. **Development and Contributions**:
   - Make your changes or improvements to the code.
   - Test your changes thoroughly with different authentication methods.
   - Feel free to submit a pull request if you wish to contribute your changes back to the project.

## Features
- **Dual Authentication Methods**: Support for both external authenticator apps (TOTP) and email-based verification.
- **Secure Implementation**: Uses industry-standard TOTP (Time-based One-Time Password) algorithm compatible with Google Authenticator, Authy, and other authenticator apps.
- **Recovery Codes**: Backup recovery codes for external app method in case you lose access to your authenticator device.
- **Rate Limiting**: Built-in protection against brute force attacks with configurable attempt limits and lockout duration.
- **User-Friendly Setup**: Easy-to-follow setup process with QR code generation for authenticator apps.
- **Flexible Configuration**: Administrators can choose which methods to enable and configure security parameters.
- **Session Security**: Secure session management with proper cleanup and validation.

## Supported Methods

### 🔐 External Authenticator App (TOTP)
- Compatible with Google Authenticator, Authy, Microsoft Authenticator, and other TOTP-compliant apps
- Uses standard 6-digit codes that refresh every 30 seconds
- Includes backup recovery codes for emergency access
- QR code setup for easy configuration

### 📧 Email Verification
- Sends 6-digit verification codes to the user's registered email address
- Built-in rate limiting to prevent email spam
- Configurable code expiration time
- No additional app installation required

## Configuration

### Administrator Settings
![Configuration of Two Factor](https://sandbox.piwigo.com/uploads/4/y/1/4y1zzhnrnw//2025/07/06/20250706152431-26426f0b.png)

### User Setup Process
![Two Factor User Setup Processing](https://sandbox.piwigo.com/uploads/4/y/1/4y1zzhnrnw//2025/07/06/20250706152434-7abac417.png)

## How It Works

### Setup Process
1. **Administrator enables the plugin** and configures available methods
2. **Users access their profile** to set up two-factor authentication
3. **For external apps**: Scan QR code with authenticator app and verify setup
4. **For email**: Verify email address and test code delivery
5. **Save recovery codes** (for external app method) in a secure location

### Login Process
1. **Enter username and password** as usual
2. **System redirects to 2FA verification** if authentication is enabled
3. **Enter 6-digit code** from your chosen method:
   - From authenticator app (external method)
   - From email received (email method)
   - Or use a recovery code (external method only)
4. **Access granted** upon successful verification

![Two Factor Login step 1](https://sandbox.piwigo.com/uploads/4/y/1/4y1zzhnrnw//2025/07/06/20250706152435-b0193edc.png)
![Two Factor Login step 2](https://sandbox.piwigo.com/uploads/4/y/1/4y1zzhnrnw//2025/07/06/20250706152436-2165f0fb.png)


## Security Features

- **Rate Limiting**: Configurable maximum attempts before temporary lockout
- **Secure Code Generation**: Uses cryptographically secure random number generation
- **Time-based Validation**: TOTP codes include time-drift tolerance for clock synchronization
- **Recovery Code Security**: Recovery codes are securely hashed and can only be used once
- **Session Management**: Proper session cleanup and validation throughout the authentication process

### Default Security Settings
- Maximum login attempts: 3
- Lockout duration: 300 seconds (5 minutes)
- Email rate limiting: 60 seconds between requests
- TOTP time window: ±1 interval (90 seconds total)

## License
GPL-2.0, the same license as Piwigo itself.

---

**Need help?** Check the [Piwigo Community Forum](https://piwigo.org/forum/) for support and discussions about this plugin.
