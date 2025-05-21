# AI Spam Shield

![Development Status](https://img.shields.io/badge/Status-Under%20Development-yellow)
![Version](https://img.shields.io/badge/Version-0.1.0--alpha-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)

AI Spam Shield is a WordPress plugin that uses advanced AI language models to detect and filter spam from contact form submissions. It integrates with popular form plugins and leverages multiple AI providers (OpenAI, Anthropic, Google Gemini) for intelligent spam detection.

> ⚠️ **IMPORTANT**: This plugin is currently in early development (alpha) stage. It is not yet recommended for production environments. APIs, features, and implementation details may change significantly before stable release.

## 🚀 Features

- **Multi-Provider AI Integration**
  - OpenAI (GPT models)
  - Anthropic (Claude models)
  - Google Gemini models

- **Form Plugin Compatibility**
  - Contact Form 7
  - WPForms
  - Gravity Forms
  - WordPress comments (optional)

- **Customizable Spam Detection**
  - Phishing attempts
  - Sales pitches
  - Promotional content
  - Unsolicited collaboration requests

- **Admin Interface**
  - Simple configuration panel
  - API key management
  - Model selection by provider
  - Connection testing

## 🔧 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- API key from at least one of:
  - OpenAI ([Get API Key](https://platform.openai.com/account/api-keys))
  - Anthropic ([Get API Key](https://console.anthropic.com/))
  - Google Gemini ([Get API Key](https://ai.google.dev/))
- One or more supported form plugins installed and activated

## 📦 Installation

> ⚠️ **DEVELOPMENT VERSION**: This plugin is in active development and not yet available via WordPress plugin repository.

### Manual Installation (for testing only):

1. Download the latest release from the [Releases](https://github.com/yourusername/ai-spam-shield/releases) page
2. Upload the `ai-spam-shield` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings under 'Settings > AI Spam Shield'
5. Enter your API key for at least one provider
6. Select which forms you want to protect with AI spam detection

## 🧰 Usage

1. **Configure Provider**: Select your preferred AI provider and enter your API key
2. **Select Model**: Choose which model to use for spam detection
3. **Enable Form Integrations**: Select which form plugins to integrate with
4. **Customize Detection**: Choose which types of spam to detect

The plugin will automatically intercept form submissions, analyze them for spam content using the selected AI provider, and block submissions that are identified as spam.

## ⚙️ Development

This plugin is under active development. If you're interested in contributing:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Roadmap

#### Phase 1: MVP (Current)
- Basic plugin structure and admin settings
- Integration with major form plugins
- AI detection for common spam types
- Multi-provider support (OpenAI, Anthropic, Google Gemini)

#### Phase 2: Enhancements
- Spam confidence threshold settings
- Detailed spam analysis reports
- Custom prompt engineering options
- Form-specific settings
- Smart handling of false positives

#### Phase 3: Advanced Features
- Scheduled batch processing option
- Statistics dashboard
- Blacklist/whitelist management
- Advanced pattern recognition
- Multi-site support
- Adaptive learning from admin feedback

## 📝 Known Issues

- High volume sites may experience performance impacts due to API call latency
- Detection accuracy varies by AI model and provider
- No current handling for API rate limits
- Limited customization for prompt engineering
- No sandbox mode for testing without blocking real submissions

## 🔒 Security Considerations

- API keys are stored in the WordPress database
- Form submission content is sent to third-party AI services
- Review the privacy policies of your chosen AI provider
- Consider data residency requirements for your jurisdiction

## 📜 License

This project is licensed under the GPLv2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgements

- This plugin uses the WordPress Plugin Boilerplate pattern
- Thanks to the developers of Contact Form 7, WPForms, and Gravity Forms
- AI services provided by OpenAI, Anthropic, and Google