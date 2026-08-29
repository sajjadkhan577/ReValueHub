---
name: Premium Exchange
colors:
  surface: '#faf8ff'
  surface-dim: '#d9d9e5'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f3fe'
  surface-container: '#ededf9'
  surface-container-high: '#e7e7f3'
  surface-container-highest: '#e1e2ed'
  on-surface: '#191b23'
  on-surface-variant: '#434655'
  inverse-surface: '#2e3039'
  inverse-on-surface: '#f0f0fb'
  outline: '#737686'
  outline-variant: '#c3c6d7'
  surface-tint: '#0053db'
  primary: '#004ac6'
  on-primary: '#ffffff'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#b4c5ff'
  secondary: '#006e2f'
  on-secondary: '#ffffff'
  secondary-container: '#6bff8f'
  on-secondary-container: '#007432'
  tertiary: '#784b00'
  on-tertiary: '#ffffff'
  tertiary-container: '#996100'
  on-tertiary-container: '#ffeedd'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#6bff8f'
  secondary-fixed-dim: '#4ae176'
  on-secondary-fixed: '#002109'
  on-secondary-fixed-variant: '#005321'
  tertiary-fixed: '#ffddb8'
  tertiary-fixed-dim: '#ffb95f'
  on-tertiary-fixed: '#2a1700'
  on-tertiary-fixed-variant: '#653e00'
  background: '#faf8ff'
  on-background: '#191b23'
  surface-variant: '#e1e2ed'
typography:
  display-lg:
    fontFamily: Manrope
    fontSize: 48px
    fontWeight: '800'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-xl:
    fontFamily: Manrope
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Manrope
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  headline-md:
    fontFamily: Manrope
    fontSize: 20px
    fontWeight: '600'
    lineHeight: '1.4'
  body-lg:
    fontFamily: Manrope
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Manrope
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  body-sm:
    fontFamily: Manrope
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
  label-md:
    fontFamily: Manrope
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Manrope
    fontSize: 12px
    fontWeight: '500'
    lineHeight: '1.2'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 40px
  2xl: 64px
  container-max: 1280px
  gutter: 24px
---

## Brand & Style

The brand personality for this design system is rooted in **Trust, Professionalism, and Sustainability**. It is designed to facilitate high-value exchanges within a community, requiring a UI that feels reliable like a financial institution but warm like a neighborhood initiative. 

The aesthetic follows a **Modern Corporate Minimalism** approach. It leverages generous whitespace to reduce cognitive load and emphasizes clarity through a strict hierarchy. The visual language uses "soft precision"—combining the mathematical accuracy of a SaaS platform with organic, rounded edges and light-diffused shadows to evoke a sense of approachability and premium quality. The goal is to make the act of revaluing unused items feel like a sophisticated, high-end experience rather than a second-hand marketplace.

## Colors

The color palette is anchored by **Deep Blue**, symbolizing trust and stability. **Sustainability Green** is used for success states and growth-related actions, reinforcing the environmental mission of the platform. **Action Orange** serves as a high-visibility accent for notifications, urgent prompts, or primary conversion points.

The background uses a subtle cool-gray to provide a sophisticated canvas that allows white surface cards to pop. Text colors are strictly tiered: deep navy for primary readability and a muted slate for secondary metadata and descriptions.

## Typography

This design system utilizes **Manrope** for its refined, modern balance. Manrope’s geometric qualities provide the "professional" edge required for a premium SaaS feel, while its open apertures keep the community aspect "approachable."

- **Headlines:** Use Bold or ExtraBold weights with tighter letter spacing for a punchy, editorial feel.
- **Body:** Standardized at 16px for optimal legibility. Use a 1.6x line height to maintain generous vertical rhythm.
- **Labels:** Utilized for categories and tags, often paired with a slight letter-spacing increase to ensure clarity at small sizes.

## Layout & Spacing

The layout is built on a **12-column fixed grid** for desktop, centering the content at a maximum width of 1280px. This ensures that even on ultra-wide monitors, the platform feels contained and professional. 

A **4px base-unit** system governs all spatial relationships. Generous whitespace is a core principle:
- **Margins:** 24px minimum on mobile, 40px+ on desktop.
- **Section Spacing:** Use 64px (2xl) to separate distinct content blocks to give the interface "room to breathe."
- **Component Padding:** Internal card padding should strictly use 24px (lg) to maintain the premium, airy feel.

## Elevation & Depth

This design system uses **Ambient Shadows** to create a sense of tactile layering. Depth is used to signify interactivity and priority rather than just decoration.

- **Level 0 (Flat):** The background (#F8FAFC).
- **Level 1 (Low):** Standard cards and input fields. Use a subtle 1px border (#E2E8F0) and a very soft shadow (0px 4px 6px rgba(0,0,0,0.02)).
- **Level 2 (Medium):** Hover states for cards and dropdown menus. The shadow becomes more diffused (0px 10px 15px rgba(0,0,0,0.05)) to suggest the element is lifting toward the user.
- **Level 3 (High):** Modals and sticky navigation bars. These use a deep, wide blur to establish clear dominance over the rest of the UI.

## Shapes

The shape language is defined by **Rounded (Level 2)** settings. This creates a friendly and safe environment while maintaining a structured, systematic appearance.

- **Buttons & Small Components:** Use 8px (0.5rem) radius.
- **Cards & Large Containers:** Use 16px (1rem) radius to define the "premium" SaaS container style requested.
- **Full Rounds:** Icons and specific status tags (chips) may use pill-shaping to distinguish them from structural containers.

## Components

### Buttons
Primary buttons use a solid Deep Blue fill with white text and an 8px corner radius. Secondary buttons should use a ghost style (blue border/text) or a light gray background to maintain hierarchy. On hover, buttons should subtly darken by 5-10% or show a slight upward lift via shadow transition.

### Cards
Cards are the primary vehicle for "Items." They feature a white background, 16px rounded corners, and a 1px border in a light neutral shade. Imagery within cards should also be rounded to 12px to follow the container's interior curve.

### Input Fields
Inputs must feel robust. Use a 16px vertical padding, a light gray border that transitions to Deep Blue on focus, and clear floating labels or persistent titles for accessibility.

### Chips & Tags
Used for categories like "Furniture" or "Electronics." These use a light tint of the Secondary Green or Primary Blue with high-contrast text and a pill-shaped (100px) radius.

### Navigation
A clean, top-fixed navigation bar with a backdrop blur effect (Glassmorphism Lite) to maintain context as the user scrolls. Use the Primary Blue for active links and the Deep Navy for inactive states.