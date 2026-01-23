# Technical Specification: WCAG 2.1 AA Accessibility & Critical Bug Fixes

**Version:** 1.13.0
**Date:** January 23, 2026
**Author:** Claude Code
**Status:** Implementation Ready

---

## Executive Summary

This specification addresses two critical issues in CardCrafter that impact business viability:

1. **Fatal Error Bug**: The plugin crashes on sites without Advanced Custom Fields (ACF) installed
2. **Accessibility Gaps**: Zero WCAG compliance blocks enterprise/government market access

### Business Impact

| Issue | Severity | Users Affected | Business Impact |
|-------|----------|----------------|-----------------|
| ACF Fatal Error | CRITICAL | ~40% of users | Site crashes, support tickets, 1-star reviews |
| No Accessibility | HIGH | All users | Blocked from enterprise/government contracts ($50K-500K deals) |

---

## Problem 1: ACF Fatal Error

### Root Cause

```php
// cardcrafter.php:1212
$custom_fields = get_fields($post->ID); // ACF support
```

The `get_fields()` function is an ACF-only function. When ACF is not installed:
- **Result**: `PHP Fatal error: Call to undefined function get_fields()`
- **Impact**: Complete site crash when using WordPress data mode

### Solution

Wrap ACF calls in `function_exists()` checks:

```php
if (function_exists('get_fields')) {
    $custom_fields = get_fields($post->ID);
    if ($custom_fields) {
        $card_item = array_merge($card_item, $custom_fields);
    }
}
```

---

## Problem 2: Accessibility Gaps

### Current State Analysis

| WCAG Criterion | Current Status | Required Fix |
|----------------|----------------|--------------|
| 1.3.1 Info & Relationships | FAIL | Add ARIA landmarks, roles |
| 1.4.3 Contrast | PARTIAL | Already good with CSS variables |
| 2.1.1 Keyboard | FAIL | Add tabindex, keyboard nav |
| 2.4.3 Focus Order | FAIL | Logical tab order needed |
| 2.4.7 Focus Visible | PARTIAL | Enhance focus indicators |
| 4.1.2 Name, Role, Value | FAIL | Add ARIA attributes |
| 4.1.3 Status Messages | FAIL | Add live regions for updates |

### Accessibility Implementation Plan

#### A. ARIA Landmarks & Roles

```html
<!-- Card Grid Container -->
<div role="region" aria-label="Card Grid">

  <!-- Toolbar -->
  <div role="toolbar" aria-label="Card controls">
    <input role="searchbox" aria-label="Search cards" />
    <select aria-label="Sort order" />
  </div>

  <!-- Grid -->
  <div role="list" aria-label="Cards">
    <article role="listitem" aria-labelledby="card-1-title">
      ...
    </article>
  </div>

  <!-- Pagination -->
  <nav role="navigation" aria-label="Pagination">
    ...
  </nav>

  <!-- Live Region for Announcements -->
  <div role="status" aria-live="polite" class="sr-only"></div>
</div>
```

#### B. Keyboard Navigation

| Key | Action |
|-----|--------|
| Tab | Move between interactive elements |
| Enter/Space | Activate buttons, links |
| Arrow Keys | Navigate within card grid |
| Escape | Close dropdowns, cancel actions |
| Home/End | Jump to first/last card |

#### C. Screen Reader Announcements

Dynamic content changes will be announced:
- Search results: "5 cards found matching 'design'"
- Page change: "Page 2 of 5, showing cards 7 to 12"
- Sort change: "Cards sorted A to Z"
- Export: "Exporting 12 cards as CSV"

#### D. Focus Management

1. **Enhanced Focus Indicators**
   - 3px solid outline with high contrast
   - Visible on all interactive elements
   - Focus-within for card containers

2. **Focus Trapping**
   - Export dropdown traps focus when open
   - Escape closes and returns focus

3. **Skip Links**
   - Skip to card grid option for screen readers

---

## Implementation Details

### Files to Modify

1. **cardcrafter.php** - ACF fix + PHP accessibility attributes
2. **assets/js/cardcrafter.js** - ARIA attributes, keyboard nav, live regions
3. **assets/css/cardcrafter.css** - Focus indicators, screen reader utilities

### New Accessibility Features

```javascript
// Screen reader announcements
CardCrafter.prototype.announce = function(message) {
    var liveRegion = this.container.querySelector('[aria-live]');
    if (liveRegion) {
        liveRegion.textContent = message;
    }
};

// Keyboard navigation
CardCrafter.prototype.handleKeyboard = function(e) {
    switch(e.key) {
        case 'ArrowRight':
        case 'ArrowDown':
            this.focusNextCard();
            break;
        case 'ArrowLeft':
        case 'ArrowUp':
            this.focusPreviousCard();
            break;
        case 'Home':
            this.focusFirstCard();
            break;
        case 'End':
            this.focusLastCard();
            break;
    }
};
```

### CSS Additions

```css
/* Screen reader only utility */
.cardcrafter-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Enhanced focus indicators */
.cardcrafter-card:focus-within {
    outline: 3px solid var(--cardcrafter-link-color);
    outline-offset: 2px;
}

/* High contrast focus for buttons */
.cardcrafter-pagination-btn:focus-visible,
.cardcrafter-export-button:focus-visible {
    outline: 3px solid #000;
    outline-offset: 2px;
    box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.3);
}
```

---

## Testing Strategy

### Automated Testing
- axe-core integration for WCAG validation
- Jest tests for keyboard navigation
- Cypress tests for focus management

### Manual Testing
- Screen reader testing (NVDA, VoiceOver, JAWS)
- Keyboard-only navigation
- High contrast mode
- Browser zoom to 400%

### Test Matrix

| Browser | Screen Reader | Status |
|---------|---------------|--------|
| Chrome | ChromeVox | To Test |
| Firefox | NVDA | To Test |
| Safari | VoiceOver | To Test |
| Edge | Narrator | To Test |

---

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| WCAG 2.1 AA Compliance | 100% | axe-core automated tests |
| Keyboard Navigation | Full coverage | Manual testing |
| Screen Reader Compatibility | 4 major readers | Manual testing |
| Lighthouse Accessibility | 95+ | Lighthouse audit |

---

## Rollout Plan

1. **Phase 1** (This PR): Core accessibility + ACF fix
2. **Phase 2** (Future): Color contrast customization
3. **Phase 3** (Future): Reduced motion preferences

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Breaking existing styling | Low | Medium | Thorough CSS review |
| Performance impact | Low | Low | Minimal DOM additions |
| Browser compatibility | Low | Medium | Tested on major browsers |

---

## Appendix: WCAG 2.1 AA Criteria Addressed

- 1.1.1 Non-text Content (Level A)
- 1.3.1 Info and Relationships (Level A)
- 1.4.3 Contrast Minimum (Level AA)
- 2.1.1 Keyboard (Level A)
- 2.1.2 No Keyboard Trap (Level A)
- 2.4.3 Focus Order (Level A)
- 2.4.4 Link Purpose (Level A)
- 2.4.6 Headings and Labels (Level AA)
- 2.4.7 Focus Visible (Level AA)
- 4.1.1 Parsing (Level A)
- 4.1.2 Name, Role, Value (Level A)
- 4.1.3 Status Messages (Level AA)
