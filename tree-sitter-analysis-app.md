[INFO] Creating new project: httpdocs
[INFO] Detected monorepo with 10 sub-projects
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs
[INFO] Parsing 10 sub-projects
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs
[INFO] Found 1751 files to parse
[INFO] Project parsed successfully: 1720 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com
[INFO] Found 1003 files to parse
[INFO] Project parsed successfully: 994 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/claude-knowledge-base-mcp
[INFO] Found 40 files to parse
[INFO] Project parsed successfully: 39 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/claude-os
[INFO] Found 175 files to parse
[INFO] Project parsed successfully: 171 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/claude-os/frontend
[INFO] Found 49 files to parse
[INFO] Project parsed successfully: 48 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/gemini-cli
[INFO] Found 283 files to parse
[INFO] Project parsed successfully: 280 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/hivemind-install
[INFO] Found 226 files to parse
[INFO] Project parsed successfully: 213 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/hivemind-install/cli
[INFO] Found 5 files to parse
[INFO] Project parsed successfully: 5 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/hivemind-install/frontend
[INFO] Found 84 files to parse
[INFO] Project parsed successfully: 80 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/voice-bridge
[INFO] Found 8 files to parse
[INFO] Project parsed successfully: 8 files
[INFO] Project parsed successfully: 0 files
[INFO] Analyzing /var/www/vhosts/kalfa.me/httpdocs (project: httpdocs)...
[INFO] Starting analysis of /var/www/vhosts/kalfa.me/httpdocs
[INFO] Analyzing 9472 nodes from 0 files
[INFO] Analysis complete: 8983 findings
# Analysis Report

## Summary
- Total findings: 8983
- Critical: 165
- Warnings: 8818
- Info: 0

## Quality Metrics
- Code Quality Score: 3.93/10
- Average Complexity: 2.37
- Average Method Length: 14.6 lines
- Average Parameters: 0.74
- Total Methods: 3973

## Dead Code Metrics
- Total Files: 0
- Unused Files: 0
- Unused Functions: 0
- Unused Variables: 0

## Structure Metrics
- Files Analyzed: 4786
- Circular Dependencies: 0
- High Coupling Files: 0
- Max Nesting Depth: 11

## Critical Issues
- **long_method**: handle: shorten method (118 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Console/Commands/LoadTest/PulseApmLoadTest.php:23)
- **unnecessary_abstraction**: Inline short function: reportUsage (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/app/Contracts/BillingProvider.php:29)
- **unnecessary_abstraction**: Inline short function: chargeWithToken (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/app/Contracts/PaymentGatewayInterface.php:23)
- **unnecessary_abstraction**: Inline short function: handleWebhook (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/app/Contracts/PaymentGatewayInterface.php:28)
- **long_method**: store: shorten method (133 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Api/BillingCheckoutController.php:26)
- **long_method**: __invoke: shorten method (108 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Api/GuestImportController.php:26)
- **long_method**: __invoke: shorten method (104 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Api/SubscriptionPurchaseController.php:37)
- **high_complexity**: store: reduce complexity (18) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Dashboard/EventController.php:45)
- **parameter_overload**: buildConnectTwiML: reduce parameters (13) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Twilio/RsvpVoiceController.php:92)
- **unnecessary_abstraction**: Inline short function: mjmlView (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/app/Mail/MjmlMailable.php:23)
- **high_complexity**: boot: reduce complexity (22) (/var/www/vhosts/kalfa.me/httpdocs/app/Providers/AppServiceProvider.php:135)
- **high_complexity**: boot: reduce complexity (18) (/var/www/vhosts/kalfa.me/httpdocs/app/Providers/SystemSettingsServiceProvider.php:16)
- **long_method**: storeSingleUseToken: shorten method (107 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Services/Sumit/AccountPaymentMethodManager.php:23)
- **unnecessary_abstraction**: Inline short function: __init__ (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/agent_os_parser.py:78)
- **unnecessary_abstraction**: Inline short function: __setattr__ (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/config.py:23)
- **high_complexity**: sync_kb_folder: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/hooks.py:129)
- **long_method**: sync_kb_folder: shorten method (102 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/hooks.py:129)
- **long_method**: ingest_file: shorten method (133 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/ingestion.py:87)
- **long_method**: ingest_documents: shorten method (102 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/ingestion.py:222)
- **unnecessary_abstraction**: Inline short function: __init__ (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_exporter.py:35)
- **unnecessary_abstraction**: Inline short function: __init__ (2 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py:26)
- **long_method**: consolidate_related: shorten method (108 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py:214)
- **high_complexity**: get_health_report: reduce complexity (25) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py:369)
- **long_method**: get_health_report: shorten method (116 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py:369)
- **high_complexity**: preprocess_markdown: reduce complexity (17) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/markdown_preprocessor.py:214)
- **high_complexity**: __init__: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/rag_engine.py:79)
- **long_method**: __init__: shorten method (105 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/rag_engine.py:79)
- **high_complexity**: _parse_skill: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/skill_manager.py:456)
- **unnecessary_abstraction**: Inline short function: __init__ (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/skill_manager.py:693)
- **unnecessary_abstraction**: Inline short function: __init__ (2 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/spec_manager.py:18)
- **high_complexity**: list_tools: reduce complexity (24) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/mcp_server/claude_code_mcp.py:143)
- **long_method**: list_tools: shorten method (395 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/mcp_server/claude_code_mcp.py:143)
- **long_method**: _execute_tool: shorten method (209 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/mcp_server/claude_code_mcp.py:557)
- **long_method**: captureScreenshots: shorten method (135 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/scripts/capture-screenshots.js:31)
- **high_complexity**: up: reduce complexity (17) (/var/www/vhosts/kalfa.me/httpdocs/database/migrations/2025_11_30_120000_add_subscription_support_to_documents_table.php:14)
- **long_method**: up: shorten method (109 lines) (/var/www/vhosts/kalfa.me/httpdocs/database/migrations/2026_03_08_181300_create_permission_tables.php:12)
- **long_method**: run: shorten method (187 lines) (/var/www/vhosts/kalfa.me/httpdocs/database/seeders/AiVoiceAgentProductSeeder.php:16)
- **high_complexity**: f: reduce complexity (20) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/code-block-lowlight.js:3)
- **high_complexity**: we: reduce complexity (31) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/code-block-lowlight.js:7)
- **high_complexity**: xh: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/code-block-lowlight.js:21)
- **high_complexity**: addAttributes: reduce complexity (28) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/code-block-lowlight.js:29)
- **unnecessary_abstraction**: Inline short function: addInputRules (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/code-block-lowlight.js:45)
- **high_complexity**: kf: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/enhanced-editor.js:19)
- **unnecessary_abstraction**: Inline short function: addCommands (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/enhanced-editor.js:28)
- **unnecessary_abstraction**: Inline short function: addKeyboardShortcuts (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/enhanced-editor.js:28)
- **unnecessary_abstraction**: Inline short function: addInputRules (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/enhanced-editor.js:28)
- **unnecessary_abstraction**: Inline short function: parseHTML (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/enhanced-editor.js:28)
- **unnecessary_abstraction**: Inline short function: $ (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/highlight.js:1)
- **high_complexity**: Ho: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/highlight.js:15)
- **high_complexity**: Va: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/visual-menus.js:15)
- **unnecessary_abstraction**: Inline short function: handleCSRInput (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/public/js/ssl-csr-validator.js:54)
- **unnecessary_abstraction**: Inline short function: BindFormSubmit (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/public/js/sumit-payments.js:1)
- **parameter_overload**: mount: reduce parameters (9) (/var/www/vhosts/kalfa.me/httpdocs/resources/views/components/⚡tree-branch.blade.php:21)
- **parameter_overload**: mount: reduce parameters (10) (/var/www/vhosts/kalfa.me/httpdocs/resources/views/components/⚡tree-node.blade.php:51)
- **long_method**: getHeaderActions: shorten method (120 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Filament/Resources/LicenseResource/Pages/EditLicense.php:24)
- **long_method**: table: shorten method (130 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Filament/Resources/PluginBundleResource.php:108)
- **high_complexity**: getHeaderActions: reduce complexity (23) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Filament/Resources/PluginResource/Pages/EditPlugin.php:23)
- **long_method**: getHeaderActions: shorten method (232 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Filament/Resources/PluginResource/Pages/EditPlugin.php:23)
- **long_method**: form: shorten method (137 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Filament/Resources/PluginResource.php:36)
- **long_method**: table: shorten method (167 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Filament/Resources/PluginResource.php:174)
- **long_method**: table: shorten method (111 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Filament/Resources/ShowcaseResource.php:115)
- **long_method**: table: shorten method (143 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Filament/Resources/WallOfLoveSubmissionResource.php:97)
- **long_method**: createMultiItemCheckoutSession: shorten method (106 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Http/Controllers/CartController.php:381)
- **high_complexity**: store: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Http/Controllers/CustomerPluginController.php:43)
- **long_method**: getPageProperties: shorten method (127 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Http/Controllers/ShowDocumentationController.php:85)
- **long_method**: getNavigation: shorten method (130 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Http/Controllers/ShowDocumentationController.php:213)
- **high_complexity**: refreshPrices: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Services/CartService.php:222)
- **long_method**: refreshPrices: shorten method (143 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Services/CartService.php:222)
- **long_method**: sync: shorten method (109 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Services/PluginSyncService.php:12)
- **long_method**: run: shorten method (116 lines) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/database/seeders/PluginSeeder.php:11)
- **unnecessary_abstraction**: Inline short function: Ct (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **unnecessary_abstraction**: Inline short function: Et (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **unnecessary_abstraction**: Inline short function: Ui (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **unnecessary_abstraction**: Inline short function: Hi (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **unnecessary_abstraction**: Inline short function: ji (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **unnecessary_abstraction**: Inline short function: Wi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **unnecessary_abstraction**: Inline short function: Ft (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **high_complexity**: Uc: reduce complexity (39) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:5)
- **unnecessary_abstraction**: Inline short function: hf (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:10)
- **high_complexity**: To: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:10)
- **high_complexity**: Kn: reduce complexity (86) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:12)
- **unnecessary_abstraction**: Inline short function: ji (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Jn (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ri (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Hi (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Bi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Wi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: na (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: oa (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ui (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: $i (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ki (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: canBeConsolidatedWith (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:48)
- **high_complexity**: insertAttachments: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:61)
- **high_complexity**: loadJSON: reduce complexity (24) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:67)
- **high_complexity**: serializeSelectionToDataTransfer: reduce complexity (22) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:72)
- **unnecessary_abstraction**: Inline short function: insertParagraph (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:75)
- **unnecessary_abstraction**: Inline short function: insertReplacementText (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:75)
- **unnecessary_abstraction**: Inline short function: "x-on:keydown" (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/tags-input.js:1)
- **unnecessary_abstraction**: Inline short function: "x-on:paste" (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/tags-input.js:1)
- **unnecessary_abstraction**: Inline short function: Hr (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/support/support.js:11)
- **unnecessary_abstraction**: Inline short function: Ft (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/widgets/components/chart.js:1)
- **unnecessary_abstraction**: Inline short function: V (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/widgets/components/chart.js:1)
- **unnecessary_abstraction**: Inline short function: __init__ (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/agent_os_parser.py:78)
- **unnecessary_abstraction**: Inline short function: __setattr__ (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/config.py:23)
- **high_complexity**: sync_kb_folder: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/hooks.py:129)
- **long_method**: sync_kb_folder: shorten method (102 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/hooks.py:129)
- **long_method**: ingest_file: shorten method (133 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/ingestion.py:87)
- **long_method**: ingest_documents: shorten method (102 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/ingestion.py:222)
- **unnecessary_abstraction**: Inline short function: __init__ (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_exporter.py:35)
- **unnecessary_abstraction**: Inline short function: __init__ (2 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py:26)
- **long_method**: consolidate_related: shorten method (108 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py:214)
- **high_complexity**: get_health_report: reduce complexity (25) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py:369)
- **long_method**: get_health_report: shorten method (116 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py:369)
- **high_complexity**: preprocess_markdown: reduce complexity (17) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/markdown_preprocessor.py:214)
- **high_complexity**: __init__: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/rag_engine.py:79)
- **long_method**: __init__: shorten method (105 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/rag_engine.py:79)
- **high_complexity**: _parse_skill: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/skill_manager.py:456)
- **unnecessary_abstraction**: Inline short function: __init__ (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/skill_manager.py:693)
- **unnecessary_abstraction**: Inline short function: __init__ (2 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/spec_manager.py:18)
- **high_complexity**: list_tools: reduce complexity (24) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/mcp_server/claude_code_mcp.py:143)
- **long_method**: list_tools: shorten method (395 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/mcp_server/claude_code_mcp.py:143)
- **long_method**: _execute_tool: shorten method (209 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/mcp_server/claude_code_mcp.py:557)
- **long_method**: captureScreenshots: shorten method (135 lines) (/var/www/vhosts/kalfa.me/httpdocs/claude-os/scripts/capture-screenshots.js:31)
- **deep_nesting**: Reduce nesting: 8 levels (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/code-block-lowlight.js:33)
- **deep_nesting**: Reduce nesting: 9 levels (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:8)
- **deep_nesting**: Reduce nesting: 8 levels (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:12)
- **excessive_abstraction**: Inline micro-functions: 4 short/unused (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/enhanced-editor.js)
- **excessive_abstraction**: Inline micro-functions: 7 short/unused (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js)
- **excessive_abstraction**: Inline micro-functions: 12 short/unused (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js)
- **excessive_abstraction**: Inline micro-functions: 3 short/unused (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js)
- **god_class**: Split large file: 22 functions (/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Accounts/Show.php)
- **god_class**: Split large file: 33 functions (/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/CreateProductWizard.php)
- **god_class**: Split large file: 28 functions (/var/www/vhosts/kalfa.me/httpdocs/app/Models/Account.php)
- **god_class**: Split large file: 28 functions (/var/www/vhosts/kalfa.me/httpdocs/app/Services/FeatureResolver.php)
- **god_class**: Split large file: 20 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/agent_os_parser.py)
- **god_class**: Split large file: 44 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/config.py)
- **god_class**: Split large file: 36 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/file_watcher.py)
- **god_class**: Split large file: 22 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/hooks.py)
- **god_class**: Split large file: 28 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/knowledge_lifecycle.py)
- **god_class**: Split large file: 22 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/rag_engine.py)
- **god_class**: Split large file: 26 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/redis_config.py)
- **god_class**: Split large file: 22 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/session_parser.py)
- **god_class**: Split large file: 64 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/skill_manager.py)
- **god_class**: Split large file: 20 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/spec_manager.py)
- **god_class**: Split large file: 22 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/spec_parser.py)
- **god_class**: Split large file: 38 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/spec_watcher.py)
- **god_class**: Split large file: 56 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/app/core/tree_sitter_indexer.py)
- **god_class**: Split large file: 21 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/frontend/src/components/ChatInterface.tsx)
- **god_class**: Split large file: 39 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/frontend/src/components/HooksConfiguration.tsx)
- **god_class**: Split large file: 57 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/frontend/src/components/KBManagement.tsx)
- **god_class**: Split large file: 39 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/frontend/src/components/KanbanBoard.tsx)
- **god_class**: Split large file: 39 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/frontend/src/components/ProjectManagement.tsx)
- **god_class**: Split large file: 33 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/frontend/src/components/ProjectSetup.tsx)
- **god_class**: Split large file: 45 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/frontend/src/pages/MainApp.tsx)
- **god_class**: Split large file: 24 functions (/var/www/vhosts/kalfa.me/httpdocs/claude-os/mcp_server/claude_code_mcp.py)
- **god_class**: Split large file: 38 functions (/var/www/vhosts/kalfa.me/httpdocs/public/js/app/rich-content-plugins/code-block-lowlight.js)
- **god_class**: Split large file: 25 functions (/var/www/vhosts/kalfa.me/httpdocs/public/js/device-fingerprinting.js)
- **god_class**: Split large file: 22 functions (/var/www/vhosts/kalfa.me/httpdocs/public/js/ssl-csr-validator.js)
- **god_class**: Split large file: 53 functions (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Models/Plugin.php)
- **god_class**: Split large file: 30 functions (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Models/PluginBundle.php)
- **god_class**: Split large file: 29 functions (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/app/Models/User.php)
- **god_class**: Split large file: 26 functions (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js)
- **god_class**: Split large file: 33 functions (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js)
- **god_class**: Split large file: 53 functions (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js)

## Warnings
- **long_method**: handle: shorten method (52 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Console/Commands/LoadTest/ConcurrentUsers.php:23)
- **long_method**: handle: shorten method (59 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Console/Commands/LoadTest/DatabaseReadReplica.php:29)
- **long_method**: handle: shorten method (100 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Console/Commands/LoadTest/RedisCacheHitRate.php:22)
- **long_method**: handle: shorten method (63 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Console/Commands/ProductEngine/ProcessProductExpirationsCommand.php:20)
- **long_method**: handle: shorten method (51 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Console/Commands/VerifyWhatsappStatusCommand.php:18)
- **parameter_overload**: __construct: reduce parameters (6) (/var/www/vhosts/kalfa.me/httpdocs/app/Events/ProductEngineEvent.php:21)
- **high_complexity**: store: reduce complexity (13) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Api/BillingCheckoutController.php:26)
- **high_complexity**: __invoke: reduce complexity (15) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Api/GuestImportController.php:26)
- **high_complexity**: __invoke: reduce complexity (13) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Api/SubscriptionPurchaseController.php:37)
- **long_method**: handle: shorten method (55 lines) (/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Api/WebhookController.php:40)
- ... and 8808 more warnings


