# Excalidraw Configuration Guide

## 1. Overview

Excalidraw is a powerful Obsidian plugin for creating and managing diagrams, flowcharts, and visual notes. In the **KALFA vault**, Excalidraw serves these purposes:

- **Architecture Diagrams** - Visualize system components, data flows, and service interactions
- **Database Schema** - Draw entity relationships and table structures
- **API Documentation** - Create endpoint diagrams and request/response flows
- **Meeting Visuals** - Sketch out ideas during design reviews and planning sessions
- **Process Flows** - Document RSVP flows, billing processes, and user journeys

Excalidraw drawings are stored as `.excalidraw.md` files that contain both the visual diagram (JSON) and can be edited inline in Obsidian.

---

## 2. Basic Settings

The basic configuration controls how Excalidraw behaves and interacts with your vault.

### Display Release Notes After Update

**Toggle ON:** Display release notes each time you update Excalidraw to a newer version.
**Toggle OFF:** Silent mode. You can still read release notes on [GitHub Releases](https://github.com/zsviczian/obsidian-excalidraw-plugin/releases).

**Recommended:** `OFF` for production, `ON` when evaluating new features.

### Warn About Incomplete Plugin Updates

Checks that the installed Excalidraw executable matches the version shown in Obsidian's plugin list. If they don't match (often after partial sync), you'll see a warning and can update. Disable to stop checking.

**Recommended:** `ON` - Helps catch sync issues early.

### Plugin Update Notification

**Toggle ON:** Show a notification when a new version of Excalidraw is available.
**Toggle OFF:** Silent mode. You need to check for plugin updates manually in Community Plugins.

**Recommended:** `ON` - Stay up to date with bug fixes and features.

### Show Splash Screen in New Drawings

Displays a welcome/overview screen when creating new drawings. Useful for new users, but can be disabled for speed.

**Recommended:** `OFF` - Reduces friction for frequent diagram creation.

### Excalidraw Folder (CaSeNsItIvE!)

The default location for new drawings. If empty, drawings will be created in the Vault root.

**For KALFA:** Set to `Architecture/Diagrams/` or `Architecture/` to keep all architecture visuals organized.

**Recommended:** Create dedicated folder for architecture diagrams.

### Use Excalidraw Folder When Embedding a Drawing Into an Active Document

Define which folder to place newly inserted drawing when using the command palette action: "Create a new drawing and embed into active document".

- **Toggle ON:** Use Excalidraw folder (keeps drawings organized)
- **Toggle OFF:** Use attachments folder defined in Obsidian settings

**Recommended:** `ON` - Use `Architecture/` folder for consistent organization.

### Crop File Folder (CaSE sEnSiTIVE!)

Default location for new drawings created when cropping an image. If empty, drawings will be created following Vault attachments settings.

**Recommended:** Same folder as main drawings (`Architecture/`).

### Image Annotation File Folder (CaSe SeNSiTiVE!)

Default location for new drawings created when annotating an image. If empty, drawings will be created following Vault attachments settings.

**Recommended:** Same folder as main drawings (`Architecture/`).

### Excalidraw Template File or Folder (CaSe SeNSiTiVE!)

Define a template file or folder that's used when creating new drawings.

- **Template File:** Full filepath to template. E.g., if your template is in the default Excalidraw folder and its name is `Template.md`, the setting would be: `Excalidraw/Template.md` (or just `Excalidraw/Template` - you may omit the `.md` file extension).
- **Template Folder:** If you set a folder, you'll be prompted which template to use when creating a new drawing.

**Pro Tip:** If you're using the Obsidian Templater plugin, you can add Templater code to your Excalidraw templates to automate configuration (colors, fonts, element library).

**For KALFA:** Create an architecture diagram template with preset shapes for:
- Database tables
- API endpoints
- Service components
- User flows
- Event entities

### Excalidraw Automate Script Folder (CaSE sENiTiVE!)

Files placed in this folder are treated as Excalidraw Automate scripts. You can access scripts from Excalidraw via Obsidian Command Palette. Assign hotkeys to your favorite scripts like any other Obsidian command. The folder may not be in the root of your Vault.

**For KALFA:** Create scripts for:
- Quick architecture diagram (entities, relationships)
- API flow diagram (request → response)
- User journey map (guest → RSVP → seat)
- System overview (all components connected)

---

## 3. Saving Settings

The saving section controls how Excalidraw stores your drawings and manages auto-saving.

### Compress Excalidraw JSON in Markdown

By enabling this feature, Excalidraw stores the drawing JSON in a Base64 compressed format using the [LZ-String](https://pieroxy.net/blog/pages/lz-string/index.html) algorithm. This has several benefits:

- **Reduced search clutter** - Excalidraw JSON won't appear in search results
- **Smaller file sizes** - Compressed drawings take less space
- **Cleaner Markdown** - Raw JSON is hidden in compressed form

**Trade-offs:**
- Files aren't human-readable when compressed
- Requires Excalidraw to decompress when opening

**Recommended:** `ON` - Keeps vault clean and efficient.

### Autosave Intervals

Sets how frequently Excalidraw automatically saves your work (for both desktop and mobile).

- **Desktop:** Usually every 30-60 seconds
- **Mobile:** More aggressive to prevent data loss

**Recommended:** Enable with reasonable intervals (30-60s) to prevent work loss without excessive I/O.

### Filename Format

Controls how Excalidraw names new drawing files.

- **Date format:** `YYYY-MM-DD HH-mm-ss` (default) or custom
- **Counter:** Incremental numbers (Drawing 1, Drawing 2, etc.)

**For KALFA:** Use descriptive naming like `2026-03-12-multi-tenant-architecture` or `rsvp-flow-diagram`.

### File Extension

Choose between `.excalidraw.md` and `.md` extensions.

- **`.excalidraw.md`:** Explicitly marks Excalidraw files, easier to identify
- **`.md`:** Cleaner, but harder to distinguish from regular notes

**Recommended:** `.excalidraw.md` - Clear separation from documentation notes.

---

## 4. AI Settings

*Note: Excalidraw may include AI-assisted diagramming features. Configure according to your privacy and usage preferences.*

### OpenAI API Usage

If Excalidraw uses AI for suggestions or auto-layout:

- **API Key:** Store securely in Obsidian settings
- **Model:** Choose based on needs (faster vs. smarter)
- **Token Limits:** Monitor usage to avoid unexpected costs

**Recommendations:**
- For KALFA internal use: Disable or use local-only features
- For public documentation: Consider if AI adds value over manual diagramming
- **Privacy:** Be aware that diagram data is sent to AI provider

### Vision Model

If Excalidraw includes image analysis or OCR for importing sketches:

- **Model:** Choose accuracy vs. speed trade-off
- **Use Case:** Helpful for digitizing whiteboard photos during meetings

**Recommended:** Use only if regularly importing photos of diagrams.

---

## 5. Appearance & Behavior

Configure how Excalidraw looks and interacts with your workflow.

### UI Modes

Choose between:

- **Embedded Mode:** Diagrams appear inline in notes (default)
- **Canvas Mode:** Full-screen drawing experience

**For KALFA:**
- Use **Embedded** for architecture diagrams in documentation notes
- Use **Canvas** for initial diagramming before embedding

### Theme Behavior

Choose whether Excalidraw follows Obsidian's dark/light theme:

- **Follow Theme:** Automatically matches vault theme
- **Force Theme:** Manual selection (useful for contrast in diagrams)

**Recommended:** `Follow Theme` - Keeps diagrams consistent with your environment.

### Zoom & Pan

Configure navigation in diagrams:

- **Mouse Wheel Zoom:** Enable for quick zooming
- **Pan with Mouse Drag:** Natural navigation for large diagrams
- **Zoom to Fit:** Auto-scale diagram to window

**Recommended:** Enable all for smooth diagramming workflow.

### Pen Settings

Configure drawing tools:

- **Default Color:** Set to your accent color
- **Pen Width:** Consistent line weights
- **Arrow Styles:** Differentiate flow types (data, control, event)

**For KALFA:** Use a consistent color scheme:
- **Blue:** Database tables/entities
- **Green:** Services/business logic
- **Orange:** API endpoints/external integrations
- **Purple:** User flows/interactions
- **Red:** Error paths or exceptions

### Grid

Toggle visual grid for alignment:

- **Snap to Grid:** Keeps elements aligned
- **Grid Size:** Adjust based on diagram complexity

**Recommended:** `ON` for architecture diagrams, `OFF` for free-form sketches.

### Laser Pointer

Toggle cursor visibility in presentations or screen sharing:

**Recommended:** `OFF` for daily use, `ON` when presenting to team.

---

## 6. Links & Transclusion

Excalidraw integrates deeply with Obsidian's linking system.

### Obsidian Links

Create clickable links within diagrams that navigate to other notes:

- **Entity → Documentation:** Link database table to its migration or model file
- **Endpoint → API Docs:** Link to [[Architecture/APIs/endpoint-name]]
- **Service → Code:** Link to actual service implementation

**Syntax:** `[[Note Name]]` or `[[Folder/Note Name]]`

### Transclusions

Embed content from other notes into diagrams:

`````
{{Note Name}}
`````

**Use Cases:**
- Include requirements table in system diagram
- Show mockups alongside flow diagrams
- Display architecture decisions relevant to diagram

### TODO Parsing

Excalidraw can recognize and render Obsidian's `- [ ]` task syntax.

**For KALFA:** Mark incomplete architecture tasks in diagrams:
`````
- [ ] Add caching layer
- [ ] Implement tenant isolation
- [ ] Document API changes
`````

### Hover Previews

See note content when hovering over linked elements in diagrams.

**Recommended:** `ON` - Quickly assess whether navigation is useful without opening.

---

## 7. Embedding & Export

Control how diagrams integrate with your notes and can be shared.

### Embedding Drawings

Embed a diagram directly into a note using the command palette action: "Create a new drawing and embed into active document".

**For KALFA:**
- Embed architecture diagrams in [[Architecture/]] documentation notes
- Embed flow diagrams in project [[Projects/]] notes
- Keep diagrams near the documentation they illustrate

### SVG/PNG Export

Export diagrams for external use:

- **SVG:** Vector format, scalable, editable in other tools
- **PNG:** Raster format, widely compatible, smaller file size

**Use Cases:**
- **SVG:** Include in documentation sites, share in Git PRs
- **PNG:** Present in slides, share with non-technical stakeholders

### Auto-Export

Automatically export diagrams when notes are saved.

**Recommended:** `OFF` for internal vault use, `ON` if maintaining separate diagram exports.

### Image Caching

Excalidraw caches generated images for performance.

**Trade-offs:**
- **Pros:** Faster rendering, reduced CPU usage
- **Cons:** May show stale images after updates

**Recommended:** `ON` for performance, clear cache after major diagram changes.

---

## 8. Automation

Leverage Excalidraw Automate for repetitive diagramming tasks.

### Scripts

Create reusable scripts for common diagram patterns.

**Example KALFA Scripts:**

#### System Overview Diagram
`````
Title: KALFA System Overview
Elements:
  - Multi-tenant boundary box
  - Authentication service
  - Event management
  - RSVP system
  - Seating engine
  - Payment gateway
  - Notification service (Twilio)
`````

#### API Endpoint Flow
`````
Title: API Request Flow
Elements:
  - User request → Route
  - Middleware (auth, tenant context)
  - Controller
  - Service layer
  - Database
  - Response
`````

#### Database Relationship
`````
Title: Entity Relationships
Elements:
  - Organization table
  - Event table
  - Guest table
  - Invitation table
  - Tables with relationships
`````

### Field Suggester

Use Excalidraw's field suggester to auto-complete common element names and text.

**For KALFA:** Configure suggestions for:
- Table names (organizations, events, guests, invitations)
- Service names (AuthService, EventService, RSVPService)
- Component names (Livewire components, Controllers)

### Startup Scripts

Scripts that run automatically when opening Excalidraw or creating new drawings.

**Use Cases:**
- Load your standard element library
- Set up project-specific colors and styles
- Create default diagram structure

---

## 9. Best Practices for KALFA Vault

### PKM Workflow Integration

**1. Diagram First, Document Second**
- Start with a quick diagram in Excalidraw to visualize the concept
- Add detailed documentation around the embedded diagram
- Update diagram as documentation reveals gaps or changes

**2. Link Aggressively**
- Every element in architecture diagrams should link to:
  - Database tables/migrations
  - Service implementations
  - API documentation
  - Architecture Decision Records (ADRs)
  - Related projects

**3. Version Control Diagrams**
- Excalidraw files should be committed with your vault
- Use descriptive commit messages: "Add multi-tenant architecture diagram"
- Update diagrams when architecture changes

### Architecture Diagrams

**1. Standardize Your Element Library**
- Define consistent shapes for:
  - Databases (cylinders)
  - Services (rectangles with specific border color)
  - APIs (hexagons)
  - External services (dotted borders)
  - Users/Actors (stick figures)

**2. Use Layers for Complexity**
- Background layer: System boundaries (tenant, external)
- Middle layer: Major components
- Foreground layer: Data flows, interactions

**3. Color-Coding Strategy**
- **Blue:** Backend services (Laravel)
- **Purple:** Frontend components (Livewire)
- **Green:** Data stores (PostgreSQL, Redis)
- **Orange:** External integrations (Twilio, SUMIT)
- **Red:** Security/Authentication boundaries

### Documentation

**1. Contextual Diagrams**
- Embed diagrams directly in documentation notes
- Don't create standalone diagram files for documentation
- Keep the diagram near the text it illustrates

**2. Annotated Screenshots**
- For UI flows: Use image annotation to show user journey
- Mark steps (1, 2, 3...) in the diagram
- Link screenshot areas to specific features in code

**3. Version Diagrams**
- Include dates in diagram titles
- Create separate diagrams for major versions
- Archive old diagrams with clear naming: `v1-multi-tenant-arch.excalidraw.md`

### Visual Thinking

**1. Use for Problem-Solving**
- Sketch out the problem space before coding
- Identify component relationships and dependencies
- Make trade-offs visible by drawing alternative approaches

**2. Meeting Notes**
- Use Excalidraw during design reviews to capture decisions visually
- Sketch proposed solutions in real-time
- Export diagram to share with team for feedback

**3. Knowledge Capture**
- When learning a new pattern, create a diagram to cement understanding
- Link pattern diagrams to [[Knowledge/]] articles
- Use visual patterns to quickly recall architectural concepts

---

## 10. Quick Reference

### Recommended Settings Summary

| Setting | Recommended Value | Reason |
|----------|------------------|---------|
| Release notes display | OFF | Cleaner UI, check GitHub manually |
| Plugin update warnings | ON | Catch sync issues early |
| Excalidraw folder | `Architecture/` | Organized architecture visuals |
| Embedding folder | `Architecture/` | Keep diagrams near docs |
| Template folder | `Templates/` | Reusable diagram structures |
| JSON compression | ON | Smaller files, cleaner vault |
| Autosave | 30-60s | Prevent work loss |
| File extension | `.excalidraw.md` | Clear identification |
| Follow theme | ON | Consistent appearance |
| Grid snap | ON (for diagrams) / OFF (for sketches) |
| Hover previews | ON | Faster navigation |
| Image caching | ON | Better performance |

### Keyboard Shortcuts

Assign these for efficiency (customizable in Obsidian Settings → Hotkeys):

| Action | Recommended Hotkey |
|--------|-------------------|
| Create new diagram | `Ctrl/Cmd + D` |
| Embed diagram in note | `Ctrl/Cmd + E` |
| Export to SVG | `Ctrl/Cmd + S` |
| Export to PNG | `Ctrl/Cmd + P` |

---

## Related Resources

- **KALFA Architecture:** [[Architecture/README]] - Main architecture documentation
- **KALFA Projects:** [[Projects/]] - Project-specific diagrams
- **KALFA Knowledge:** [[Knowledge/]] - Pattern libraries and best practices
- **Excalidraw Docs:** [https://github.com/zsviczian/obsidian-excalidraw-plugin](https://github.com/zsviczian/obsidian-excalidraw-plugin)
- **Excalidraw Tutorials:** [YouTube Tutorial](https://www.youtube.com/watch?v=jgUpYznHP9A&t=216)

---

*Last Updated: <% tp.date.now("YYYY-MM-DD") %>*
*For KALFA Documentation Vault*
