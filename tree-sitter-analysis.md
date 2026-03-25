[INFO] Creating new project: httpdocs
[INFO] Detected monorepo with 10 sub-projects
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs
[INFO] Parsing 10 sub-projects
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs
[INFO] Found 12085 files to parse
[INFO] Project parsed successfully: 12036 files
[INFO] Parsing project: /var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com
[INFO] Found 1005 files to parse
[INFO] Project parsed successfully: 996 files
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
[INFO] Found 231 files to parse
[INFO] Project parsed successfully: 216 files
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
[INFO] Analyzing 32211 nodes from 0 files
[INFO] Analysis complete: 13396 findings
# Analysis Report

## Summary
- Total findings: 13396
- Critical: 295
- Warnings: 13101
- Info: 0

## Quality Metrics
- Code Quality Score: 3.69/10
- Average Complexity: 2.42
- Average Method Length: 14.8 lines
- Average Parameters: 0.75
- Total Methods: 5772

## Dead Code Metrics
- Total Files: 0
- Unused Files: 0
- Unused Functions: 0
- Unused Variables: 0

## Structure Metrics
- Files Analyzed: 25416
- Circular Dependencies: 0
- High Coupling Files: 0
- Max Nesting Depth: 11

## Critical Issues
- **high_complexity**: scan_sequential_patterns: reduce complexity (29) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/prepass-execution-deps.py:113)
- **high_complexity**: scan_execution_deps: reduce complexity (17) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/prepass-execution-deps.py:200)
- **long_method**: scan_execution_deps: shorten method (101 lines) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/prepass-execution-deps.py:200)
- **high_complexity**: scan_prompt_metrics: reduce complexity (29) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/prepass-prompt-metrics.py:283)
- **high_complexity**: extract_memory_paths: reduce complexity (18) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/prepass-structure-capabilities.py:215)
- **high_complexity**: scan_python_script: reduce complexity (24) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/scan-scripts.py:255)
- **long_method**: scan_python_script: shorten method (140 lines) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/scan-scripts.py:255)
- **high_complexity**: scan_shell_script: reduce complexity (17) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/scan-scripts.py:397)
- **long_method**: scan_shell_script: shorten method (104 lines) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/scan-scripts.py:397)
- **high_complexity**: scan_skill_scripts: reduce complexity (29) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/scan-scripts.py:540)
- **long_method**: scan_skill_scripts: shorten method (169 lines) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-agent-builder/scripts/scan-scripts.py:540)
- **high_complexity**: scan_prompt_metrics: reduce complexity (27) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-workflow-builder/scripts/prepass-prompt-metrics.py:176)
- **high_complexity**: scan_python_script: reduce complexity (24) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-workflow-builder/scripts/scan-scripts.py:255)
- **long_method**: scan_python_script: shorten method (140 lines) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-workflow-builder/scripts/scan-scripts.py:255)
- **high_complexity**: scan_shell_script: reduce complexity (17) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-workflow-builder/scripts/scan-scripts.py:397)
- **long_method**: scan_shell_script: shorten method (104 lines) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-workflow-builder/scripts/scan-scripts.py:397)
- **high_complexity**: scan_skill_scripts: reduce complexity (29) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-workflow-builder/scripts/scan-scripts.py:540)
- **long_method**: scan_skill_scripts: shorten method (169 lines) (/var/www/vhosts/kalfa.me/httpdocs/_bmad/bmb/skills/bmad-workflow-builder/scripts/scan-scripts.py:540)
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
- **unnecessary_abstraction**: Inline short function: Wi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **unnecessary_abstraction**: Inline short function: Ft (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **high_complexity**: Uc: reduce complexity (39) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:5)
- **unnecessary_abstraction**: Inline short function: hf (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:10)
- **high_complexity**: To: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:10)
- **high_complexity**: Kn: reduce complexity (86) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:12)
- **unnecessary_abstraction**: Inline short function: Jn (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ri (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Bi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Wi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: na (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: oa (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ui (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: $i (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ki (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **high_complexity**: insertAttachments: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:61)
- **high_complexity**: loadJSON: reduce complexity (24) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:67)
- **high_complexity**: serializeSelectionToDataTransfer: reduce complexity (22) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:72)
- **unnecessary_abstraction**: Inline short function: insertParagraph (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:75)
- **unnecessary_abstraction**: Inline short function: insertReplacementText (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:75)
- **unnecessary_abstraction**: Inline short function: "x-on:keydown" (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/tags-input.js:1)
- **unnecessary_abstraction**: Inline short function: "x-on:paste" (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/tags-input.js:1)
- **unnecessary_abstraction**: Inline short function: Hr (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/support/support.js:11)
- **unnecessary_abstraction**: Inline short function: Ft (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/widgets/components/chart.js:1)
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
- **long_method**: anonymous: shorten method (114 lines) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/PHP.c:12)
- **unnecessary_abstraction**: Inline short function: capture_php_stdout_output (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/PHP.c:130)
- **unnecessary_abstraction**: Inline short function: override_embed_module_output (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/PHP.h:11)
- **unnecessary_abstraction**: Inline short function: capture_php_output (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/PHP.h:13)
- **unnecessary_abstraction**: Inline short function: copy_file_range (3 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/compat/android_compat.cpp:22)
- **unnecessary_abstraction**: Inline short function: copy_file_range (3 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/compat/android_compat.h:16)
- **unnecessary_abstraction**: Inline short function: print_phdr (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/libphp_wrapper.cpp:19)
- **unnecessary_abstraction**: Inline short function: list_loaded_libraries (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/libphp_wrapper.cpp:27)
- **unnecessary_abstraction**: Inline short function: expose_symbols_to_php (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/libphp_wrapper.cpp:32)
- **unnecessary_abstraction**: Inline short function: cleanup_wrapper (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/libphp_wrapper.cpp:92)
- **unnecessary_abstraction**: Inline short function: php_request_shutdown (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/libphp_wrapper.cpp:115)
- **unnecessary_abstraction**: Inline short function: cleanup_output_buffer (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:89)
- **unnecessary_abstraction**: Inline short function: capture_php_output (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:96)
- **unnecessary_abstraction**: Inline short function: override_embed_module_output (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:121)
- **unnecessary_abstraction**: Inline short function: zend_activate_modules (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:205)
- **unnecessary_abstraction**: Inline short function: native_set_env (3 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:273)
- **unnecessary_abstraction**: Inline short function: native_set_request_info (3 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:288)
- **unnecessary_abstraction**: Inline short function: native_run_artisan_command (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:305)
- **unnecessary_abstraction**: Inline short function: native_get_laravel_root_path (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:379)
- **unnecessary_abstraction**: Inline short function: native_handle_request_once (3 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:414)
- **unnecessary_abstraction**: Inline short function: native_get_laravel_public_path (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:437)
- **unnecessary_abstraction**: Inline short function: native_shutdown (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:473)
- **unnecessary_abstraction**: Inline short function: native_execute_script (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:499)
- **unnecessary_abstraction**: Inline short function: JNI_OnLoad (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/cpp/php_bridge.c:528)
- **unnecessary_abstraction**: Inline short function: nativeExecuteScript (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/bridge/PHPBridge.kt:21)
- **unnecessary_abstraction**: Inline short function: runArtisanCommand (1 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/bridge/PHPBridge.kt:23)
- **unnecessary_abstraction**: Inline short function: shutdown (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/bridge/PHPBridge.kt:28)
- **unnecessary_abstraction**: Inline short function: registerPluginBridgeFunctions (3 lines, only used once) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/bridge/plugins/PluginBridgeFunctionRegistration.kt:9)
- **long_method**: handleAssetRequest: shorten method (124 lines) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/network/PHPWebViewClient.kt:27)
- **high_complexity**: handlePHPRequest: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/network/PHPWebViewClient.kt:152)
- **high_complexity**: createCustomWebViewClient: reduce complexity (26) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/network/WebViewManager.kt:127)
- **long_method**: createCustomWebViewClient: shorten method (207 lines) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/network/WebViewManager.kt:127)
- **high_complexity**: injectJavaScript: reduce complexity (17) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/network/WebViewManager.kt:336)
- **long_method**: injectJavaScript: shorten method (140 lines) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/network/WebViewManager.kt:336)
- **long_method**: getManualMapping: shorten method (116 lines) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/IconHelper.kt:114)
- **high_complexity**: NativeFab: reduce complexity (21) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/NativeFab.kt:62)
- **long_method**: NativeFab: shorten method (136 lines) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/NativeFab.kt:62)
- **high_complexity**: NativeSideDrawer: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/NativeSideNav.kt:37)
- **long_method**: NativeSideDrawer: shorten method (144 lines) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/NativeSideNav.kt:37)
- **high_complexity**: NativeTopBar: reduce complexity (25) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/NativeTopBar.kt:23)
- **long_method**: NativeTopBar: shorten method (150 lines) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/NativeTopBar.kt:23)
- **unnecessary_abstraction**: Inline short function: launchFilePicker (3 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/nativephp/android/app/src/main/java/com/nativephp/mobile/utils/NativeActionCoordinator.kt:26)
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
- **unnecessary_abstraction**: Inline short function: Wi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **unnecessary_abstraction**: Inline short function: Ft (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/file-upload.js:1)
- **high_complexity**: Uc: reduce complexity (39) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:5)
- **unnecessary_abstraction**: Inline short function: hf (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:10)
- **high_complexity**: To: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:10)
- **high_complexity**: Kn: reduce complexity (86) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:12)
- **unnecessary_abstraction**: Inline short function: Jn (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ri (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Bi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Wi (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: na (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: oa (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ui (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: $i (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **unnecessary_abstraction**: Inline short function: Ki (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/markdown-editor.js:40)
- **high_complexity**: insertAttachments: reduce complexity (16) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:61)
- **high_complexity**: loadJSON: reduce complexity (24) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:67)
- **high_complexity**: serializeSelectionToDataTransfer: reduce complexity (22) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:72)
- **unnecessary_abstraction**: Inline short function: insertParagraph (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:75)
- **unnecessary_abstraction**: Inline short function: insertReplacementText (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/rich-editor.js:75)
- **unnecessary_abstraction**: Inline short function: "x-on:keydown" (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/tags-input.js:1)
- **unnecessary_abstraction**: Inline short function: "x-on:paste" (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/forms/components/tags-input.js:1)
- **unnecessary_abstraction**: Inline short function: Hr (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/support/support.js:11)
- **unnecessary_abstraction**: Inline short function: Ft (1 lines, never used) (/var/www/vhosts/kalfa.me/httpdocs/archive/nativephp.com/public/js/filament/widgets/components/chart.js:1)

## Warnings

- ... and 13091 more warnings


