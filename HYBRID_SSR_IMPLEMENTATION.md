# Hybrid SSR + JS Hydration Implementation

This document describes the implementation of the hybrid Server-Side Rendering (SSR) + JavaScript hydration pattern for the WeTravel Widgets plugin.

## Overview

The hybrid SSR + JS hydration pattern provides the best of both worlds:

- **Fast initial page load**: Server-rendered content is displayed immediately
- **SEO-friendly**: Search engines can index the server-rendered content
- **Fresh data**: JavaScript hydration updates the content with the latest data
- **Progressive enhancement**: The page works even if JavaScript fails

## Implementation Details

### 1. Server-Side Caching (`block-renderer.php`)

**Enhanced caching strategy:**

- Uses `wetravel_trips_` cache key prefix with MD5 hash of the API URL
- Uses `WETRAVEL_CACHE_DURATION` constant (5 minutes / 300 seconds) for cache TTL
- Cache key includes API URL which encodes location filters for proper cache isolation
- Falls back to fresh API call if cache is empty

**New data attributes:**

- `data-hydrate="true"`: Enables automatic hydration
- `data-search-visibility`: Passes search filter state to JavaScript
- `data-cache-status`: Indicates whether cached or fresh data was used

### 2. REST API Endpoint (`fetch-trips.php`)

**Endpoints:**

1. `/wp-json/wetravel/v1/trips` — Main trip data endpoint
2. `/wp-json/wetravel/v1/destinations/search` — Location/destination search

**Features:**

- Fetches fresh data from WeTravel API
- Accepts all necessary parameters (slug, env, trip_type, dates, locations, etc.)
- Returns rendered HTML ready for DOM replacement
- Implements proper error handling and validation
- Caches results using `WETRAVEL_CACHE_DURATION` (5 minutes) for next server render

**Parameters (trips endpoint):**

- `block_id` (required): Unique identifier for the block instance
- `slug`, `env`: WeTravel configuration
- `trip_type`, `date_start`, `date_end`: Trip filtering
- `locations`: Semicolon-separated location filter
- `display_type`, `button_type`, etc.: Display configuration
- `wetravel_user_id`: User identification

### 3. Client-Side Hydration Script (`wetravel-hydration.js`)

**Core functionality:**

- `window.WeTravelTripsHydrate(blockId, config)`: Main hydration function
- Automatic hydration based on `data-hydrate="true"` attribute
- Smooth content updates with fade transitions
- Re-initialization of interactive components

**Features:**

- **Progressive enhancement**: Works even if REST API fails
- **Smooth transitions**: 0.8 opacity during update, animated restore
- **Component re-initialization**:
  - Carousel (Swiper) re-initialization
  - Pagination event re-binding
  - Search filters (Select2) re-initialization
- **Event system**: Triggers custom events for integration
- **Error handling**: Graceful degradation with console logging

### 4. Workflow

1. **Initial page load:**

   - Server renders cached content (or fresh if cache empty)
   - Page displays immediately with loading state
   - JavaScript hydration script is enqueued

2. **After page load (1 second delay):**

   - JavaScript fetches fresh data from REST API
   - Content is smoothly replaced with updated data
   - Interactive components are re-initialized
   - Cache is updated for next server render

3. **Result:**
   - Users see content immediately (SSR)
   - Fresh data appears after ~1-2 seconds (hydration)
   - Search and pagination work correctly
   - SEO-friendly markup is preserved

## Integration Points

### Automatic Hydration

All containers with `data-hydrate="true"` are automatically hydrated on page load with a 1-second delay.

### Manual Hydration

```javascript
// Manual hydration call
window.WeTravelTripsHydrate("block-id", {
  slug: "your-slug",
  env: "https://example.wetravel.com",
  // ... other config options
});
```

### Event System

```javascript
// Listen for hydration completion
$(document).on("wetravel:hydrated", function (event, data) {
  console.log(
    "Block hydrated:",
    data.blockId,
    "with",
    data.tripsCount,
    "trips"
  );
});

// Listen for hydration errors
$(document).on("wetravel:hydration-error", function (event, data) {
  console.error("Hydration failed for block:", data.blockId, data.error);
});
```

### Search Filters Integration

```javascript
// Listen for search filter re-initialization
$(document).on("wetravel:search-filters-init", function (event, data) {
  // Custom search filter initialization logic
});
```

## Performance Benefits

1. **Immediate content display**: Server-rendered content appears instantly
2. **Reduced API calls**: 5-minute caching reduces server load
3. **Progressive enhancement**: Works without JavaScript
4. **SEO optimization**: Search engines can index server content
5. **Fresh data guarantee**: Hydration ensures up-to-date content

## Cache Strategy

- **SSR & Hydration Cache**: 5 minutes (300 seconds), controlled by `WETRAVEL_CACHE_DURATION` constant
- **Destinations Cache**: 5 minutes (300 seconds) for location/destination search results
- **Cache Keys**: `wetravel_trips_` + MD5 of API URL (includes filters for proper isolation)
- **Cache Warm-up**: Hydration results update the cache for next server render

## Backward Compatibility

- Existing shortcodes and blocks work unchanged
- No breaking changes to public APIs
- Falls back gracefully if hydration fails
- Search and pagination continue to work with server-side logic

## Best Practices Applied

1. **Subtle UI transitions**: Fade effects prevent jarring content changes
2. **Error handling**: Graceful degradation with console logging
3. **Performance optimization**: Delayed hydration prevents blocking initial render
4. **Component re-initialization**: Ensures all interactive features work after update
5. **Event-driven architecture**: Allows for easy extension and integration

## Files Modified

1. `includes/block-renderer.php`: SSR caching and hydration setup
2. `includes/fetch-trips.php`: REST API endpoints (trips + destinations search) and fresh data fetching
3. `assets/js/wetravel-hydration.js`: Client-side hydration logic

## Configuration

The implementation uses existing plugin settings and requires no additional configuration. The hybrid pattern is automatically enabled for all WeTravel widgets (blocks and shortcodes).
