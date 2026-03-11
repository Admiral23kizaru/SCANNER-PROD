## LOCATOR_TEMP - Header & Footer Assets

This folder contains the header and footer images used by the Locator Slip TCPDF generator.

### Required Files:
- `header.png` — Header image for the top of the Locator Slip (e.g., DepEd/School logos and letterhead)
- `footer.png` — Footer image for the bottom of the Locator Slip

### How to Prepare:
1. Open the `.psd` files from the project-root `LOCATOR_TEMP/` folder in Photoshop or GIMP
2. Export them as `.png` (recommended) or `.jpg`
3. Place the exported files here: `public/LOCATOR_TEMP/`

### Notes:
- TCPDF **cannot read** `.psd` files — only `.png` and `.jpg` are supported
- The images should be high resolution (at least 300 DPI for print quality)
- Recommended width: ~180mm (matching the content area of the A4 page)
