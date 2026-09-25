---
name: Obsidian Amber
colors:
  surface: '#131316'
  surface-dim: '#131316'
  surface-bright: '#39393c'
  surface-container-lowest: '#0e0e11'
  surface-container-low: '#1b1b1e'
  surface-container: '#1f1f22'
  surface-container-high: '#2a2a2d'
  surface-container-highest: '#353438'
  on-surface: '#e4e1e6'
  on-surface-variant: '#d5c4ab'
  inverse-surface: '#e4e1e6'
  inverse-on-surface: '#303033'
  outline: '#9e8f78'
  outline-variant: '#514532'
  surface-tint: '#ffba20'
  primary: '#ffdca1'
  on-primary: '#412d00'
  primary-container: '#ffb800'
  on-primary-container: '#6b4c00'
  inverse-primary: '#7c5800'
  secondary: '#ffb955'
  on-secondary: '#452b00'
  secondary-container: '#dc9100'
  on-secondary-container: '#4f3100'
  tertiary: '#ffdab6'
  on-tertiary: '#482900'
  tertiary-container: '#ffb662'
  on-tertiary-container: '#764600'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#ffdea8'
  primary-fixed-dim: '#ffba20'
  on-primary-fixed: '#271900'
  on-primary-fixed-variant: '#5e4200'
  secondary-fixed: '#ffddb4'
  secondary-fixed-dim: '#ffb955'
  on-secondary-fixed: '#291800'
  on-secondary-fixed-variant: '#633f00'
  tertiary-fixed: '#ffddbb'
  tertiary-fixed-dim: '#ffb868'
  on-tertiary-fixed: '#2b1700'
  on-tertiary-fixed-variant: '#673d00'
  background: '#131316'
  on-background: '#e4e1e6'
  surface-variant: '#353438'
typography:
  headline-xl:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.015em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 22px
    letterSpacing: -0.005em
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 18px
    letterSpacing: 0.01em
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 10px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.04em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 0.75rem
  gutter-desktop: 1.25rem
  margin: 1rem
  margin-desktop: 2.5rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 0.75rem
  space-lg: 1.25rem
  space-xl: 1.75rem
---

## Brand & Style

This design system delivers a luxury-grade, dark-mode creative environment tailored for professional creators, commercial photographers, and e-commerce merchants producing high-end product visuals. It balances technical precision with tactile warmth, transforming generative AI and photo editing into an intuitive, tactile studio experience.

The design movement merges deep tonal dark minimalism with subtle glassmorphic affordances and warm ambient illumination. Deep charcoal and pitch-black surfaces allow user-generated imagery to command focus, while radiant gold and amber accents supply clear transactional clarity, signifying premium tier capabilities, primary actions, and focused states. The overall aesthetic feels like high-end studio hardware: calibrated, precise, and unobtrusive.

## Colors

The palette establishes an ultra-refined hierarchy using deep, warm-tinted obsidian neutrals paired with high-voltage amber accents:

- **Canvas & Surface Architecture**: The ground foundation sits at `#0F0F12`, followed by elevated card and sheet surfaces at `#18181C` (surface container) and `#222228` (elevated controls and active states). Borders use low-opacity off-white (`rgba(255, 255, 255, 0.08)`) to preserve clean silhouettes without distracting harshness.
- **Accents & Interaction**: Primary amber `#FFB800` handles primary calls to action, active toggle segments, and membership emblems. Secondary gold `#F5A623` drives hover effects, active borders, and visual highlights. Tertiary warm ochre `#E08A00` provides deep tonal backdrops for active chips and pressed states.
- **Content Contrast**: Primary typography utilizes `#FFFFFF` for absolute legibility, secondary metadata relies on `#9D9DA8`, and placeholder/tertiary cues drop to `#5C5C66`.

## Typography

The typography system relies exclusively on **Plus Jakarta Sans**, utilizing its geometric clarity, modern character curves, and distinct high-density legibility on OLED screens.

Headlines leverage tight negative tracking (`-0.02em` to `-0.01em`) to create dense, confident editorial impact within compact mobile headers. Body text maintains natural letter spacing for effortless scanning of generation parameters and prompting instructions. Micro-labels (`label-sm`) use uppercase styling with positive tracking (`0.04em`) to denote computational states, tool badges, and pro-tier badges with distinct architectural clarity.

## Layout & Spacing

The layout is built upon an 8pt base grid adapted for edge-to-edge mobile app screens and fluid responsive containers:

- **Mobile Rhythm**: Outer canvas margin sits at `1rem` (16px) to maximize screen area for creative asset grids. Component gutters within 2-column or 3-column media feeds use `0.75rem` (12px), creating a cohesive mosaic appearance.
- **Section & Component Spacing**: Internal card padding scales between `0.75rem` (compact parameter sheets) and `1.25rem` (prominent generation staging areas). Stacked toolbars and floating control bars maintain `1.75rem` clearance from home indicator safe areas.
- **Breakpoints**: Mobile views range from 320px to 480px (single column layout with 2-column image feeds), tablet views scale from 481px to 1024px (dual pane split editor), and desktop spans 1025px+ (floating tool pallets with 3-pane canvas centering).

## Elevation & Depth

Visual depth is achieved through a hybrid of deep tonal layering, frosted backdrops, and selective golden luminance:

1. **Base Layer (L0)**: `#0F0F12` — Root canvas background.
2. **Surface Layer (L1)**: `#18181C` — Embedded parameter panels, media cards, and secondary sections, enclosed with a subtle `1px` stroke of `rgba(255, 255, 255, 0.06)`.
3. **Elevated Floating Layer (L2)**: `#222228` with `backdrop-filter: blur(16px)` and `background: rgba(34, 34, 40, 0.75)` — Used for navigation docks, bottom sheets, and floating filter trays. Outlines use `rgba(255, 255, 255, 0.12)`.
4. **Active Amber Glow**: Primary CTAs and selected generation states cast a diffused amber glow rather than standard drop shadows: `box-shadow: 0px 8px 24px rgba(255, 184, 0, 0.28)`. Standard containers cast non-tinted, ultra-deep shadows: `box-shadow: 0px 12px 32px rgba(0, 0, 0, 0.5)`.

## Shapes

The interface embraces a unified, modern squircle aesthetic centered around standard level-2 roundedness with deliberate scaling:

- **Micro Controls & Badges**: Feature 8px (`rounded-md`) corners for tag indicators and compact input handles.
- **Cards & Asset Tiles**: Standard image cards, style selector thumbnails, and setting modules carry 16px (`rounded-lg`) corners, giving visual assets a soft, gallery-grade frame.
- **Interactive Buttons & Bottom Sheets**: Primary CTA bars, pill chips, and floating navigation bars adopt high-curvature geometries up to 24px (`rounded-xl`) or fully circular pills (`rounded-full`) for natural touch interaction and smooth swipe affordances.

## Components

### Buttons
- **Primary Action (Generate/Save)**: Fully saturated solid `#FFB800` fill with `#0F0F12` bold typography. Height is 52px on mobile with a 24px corner radius. Enhanced by an ambient glow `0 8px 24px rgba(255, 184, 0, 0.3)`.
- **Secondary/Utility**: Deep charcoal `#222228` fill, 1px border `rgba(255, 255, 255, 0.08)`, text `#FFFFFF`.
- **Pro Tier Badge Button**: Subtle amber glass container with `#FFB800` border, carrying an inline crown glyph and gold bold micro-label.

### Chips & Style Selectors
- **Glass Filter Chips**: Pill-shaped translucent dark tabs (`rgba(255, 255, 255, 0.06)`) with subtle borders. In active state, background shifts to `#FFB800` with dark text or amber-accented border highlighting.
- **Visual Style Thumbnails**: Square-ratio cards with 12px radius, image preview, subtle bottom gradient overlay, and a bright gold border stroke when selected.

### Input Fields & Text Prompts
- **Prompt Input Box**: Large rounded surface (`#18181C`), 1px outline of `rgba(255, 255, 255, 0.08)` transitioning to `#FFB800` on focus. Internal placeholder text in `#5C5C66`. Bottom-right accessory displays token/character count and magic-wand enhancement button.

### Cards & Media Displays
- **Generation Output Card**: Card surface with 16px radius. Displays stacked version badges, quick-action overlay buttons (download, upscale, variants) housed in glassy dark pill badges (`rgba(15, 15, 18, 0.8)`).

### Sliders & Steppers
- **Detail Slider**: Custom horizontal track with dark charcoal inactive path and filled `#FFB800` active span. Thumb is a luminous solid `#FFB800` circular node with a subtle halo.
- **Segmented Counters**: Grouped pill container housing discrete numerical increments (1, 2, 3, 4 images) where active choice fills with bright gold.

### Navigation Dock
- **Floating Island Dock**: Centered mobile bar elevated above the bottom safe area with heavy frosted glass (`rgba(24, 24, 28, 0.85)` + 20px blur), containing monochromatic icon buttons that turn gold upon selection.