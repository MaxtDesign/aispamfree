Development Instructions for AI Spam Shield WordPress Plugin
Project Overview
AI Spam Shield is a WordPress plugin that uses AI language models (OpenAI, Anthropic, Google Gemini) to detect and filter spam in contact form submissions. This project aims to provide a sophisticated alternative to traditional pattern-matching spam filters by leveraging AI to identify sophisticated spam attempts that bypass standard filters.
Current Status
The plugin is in early development stage with a functional MVP. Core functionality for API integration, form submission handling, and admin UI are implemented but require refinement and expansion. The codebase needs to evolve from a monolithic structure to a more modular OOP architecture.
Code Structure
The plugin currently uses this structure:
ai-spam-shield/
├── ai-spam-shield.php           # Main plugin file (currently monolithic)
├── js/
│   └── admin.js                 # Admin JavaScript (complete)
├── templates/
│   └── admin-page.php           # Admin settings template (complete)
The target architecture should be:
ai-spam-shield/
├── ai-spam-shield.php           # Main plugin file (bootstrap only)
├── uninstall.php                # Clean uninstallation
├── includes/                    # PHP classes
│   ├── class-admin.php          # Admin functionality
│   ├── class-ai-service.php     # Base service class 
│   ├── class-form-handler.php   # Form integration
│   ├── class-openai.php         # OpenAI implementation
│   ├── class-anthropic.php      # Anthropic implementation
│   └── class-gemini.php         # Gemini implementation
├── assets/                      # Assets
│   └── css/
│       └── admin.css            # Admin styles
├── js/                          # JavaScript
│   └── admin.js                 # Admin JavaScript
└── templates/                   # Templates
    └── admin-page.php           # Admin page template

Development Tasks

Phase 2: Enhancement

Implement form-specific settings (ability to select which forms to scan)
Add dashboard widget with spam statistics
Create logging system for detected spam
Implement caching to reduce API calls
Add notification system for detected spam

Phase 3: Advanced Features

Implement machine learning to improve accuracy over time
Add support for more form plugins
Create advanced reporting dashboards
Add batch processing for high-volume sites
Implement whitelist/blacklist functionality

Technical Guidelines
API Integration

Each AI service has different response formats
Anthropic and Gemini require extracting JSON from text responses
All services should implement proper error handling and rate limiting
Include timeout settings to prevent long page loads

WordPress Integration

Use WordPress coding standards and hooks API
Properly sanitize all inputs and escape all outputs
Use nonces for all admin actions
Implement proper capability checks

Performance Considerations

API calls should be asynchronous where possible
Implement caching for repeated submissions
Consider batch processing for high-volume sites
Use transients for API status checks

Testing Approach

Set up test forms with various types of content
Create test suite for each AI provider
Test with different form plugins
Check performance under load
Verify behavior when APIs are unavailable

Security Considerations

Always sanitize form data before sending to AI services
Never store full API keys in log files
Implement capability checks for all admin functions
Follow WordPress security best practices
