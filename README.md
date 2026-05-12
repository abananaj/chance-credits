# Chance Theater Credits Plugin

Custom credit management system for Chance Theater production website.

## Features

- **Production Credits Management**: Manage cast and crew through an ACF repeater field on production pages
- **Artist Production History**: Display all productions an artist has worked on
- **Automatic Sync**: Credits automatically created/updated when you save the repeater
- **Custom Blocks**: Two display blocks for showing credits on frontend

## Installation

1. Activate the plugin through WordPress admin
2. Ensure ACF Pro is installed (required for Repeater field type)
3. Go to a Production page to see the "Cast & Crew" field

## Custom Post Types

- **ct-production**: Productions/shows
- **ct-artist**: Artist profiles
- **ct-credit**: Junction post type (created automatically via auto-sync)

## ACF Integration

### Field Group: Cast & Crew

Applied to: `ct-production` posts

**Repeater Field: `production_credits_repeater`**

- `artist` (Post Object) - Link to ct-artist
- `role-group` (Select) - playwright, actor, director, choreographer, designer, producer, other
- `role` (Text) - Specific role (e.g., "Hamlet", "Stage Manager")

When you save, a `ct-credit` post is automatically created with these meta fields:

- `production` (post ID)
- `artist` (post ID)
- `role-group` (string)
- `role` (string)

## Query Functions

All functions are global scope (no namespaces).

### Production Credits

```php
get_production_credits($production_id, $args)
get_production_credits($production_id, array('role_group' => 'actor'))
```

### Artist Productions

```php
get_artist_productions($artist_id, $args)
get_artist_productions_with_dates($artist_id) // Sorted by opening date
count_artist_productions($artist_id)
```

### Organized Display

```php
$credits = get_credits_by_group($production_id);
// Returns: ['actor' => [...], 'designer' => [...], etc]
```

**Query Arguments:**

- `role_group` (string) - Filter by role-group
- `orderby` (string) - Default: 'menu_order title'
- `order` (string) - ASC or DESC
- `per_page` (int) - Default: 200
- `count_only` (bool) - Return count only
- `fields` (string) - 'ids' for ID-only results

## Blocks

Two custom Gutenberg blocks available:

### Production Credits Block

- **Name**: `chance-credits/production-credits`
- **Used on**: Production pages
- **Displays**: Cast and crew organized by role-group
- **Context**: Uses `postId` and `postType` from block context
- **Attribute**: `groupBy` (filter by specific role-group)

### Artist Productions Block

- **Name**: `chance-credits/artist-productions`
- **Used on**: Artist pages
- **Displays**: All productions artist has worked on
- **Context**: Uses `postId` and `postType` from block context
- **Attribute**: `sortBy` (date, date-asc, title)

## File Structure

```
chance-credits/
├── chance-credits.php              # Main plugin file
├── models/
│   └── credits.php                 # All query functions & auto-sync
├── includes/
│   ├── acf-fields.php              # ACF field group registration
│   └── blocks.php                  # Block registration
└── blocks/
    ├── ProductionCredits/
    │   ├── block.json              # Block config
    │   ├── render.php              # Server-side render
    │   └── index.js                # Block type registration
    └── ArtistProductions/
        ├── block.json
        ├── render.php
        └── index.js
```

## Data Flow

1. **Edit Production**: Go to Production page → Edit "Cast & Crew" repeater
2. **Save Production**: ACF save hook fires `sync_repeater_to_credits()`
3. **Auto-create Credits**: `ct-credit` posts created/updated with repeater data
4. **Display on Frontend**: Use blocks or query functions to display

## Future Extensions

This plugin is designed to be extensible. Additional blocks can be added by:

1. Creating new folder in `blocks/`
2. Adding `block.json`, `render.php`, `index.js`
3. Registering in `includes/blocks.php`

Possible additions:

- Crew by Department filter block
- Timeline of artist productions
- Credits search/filter
- Graphical cast view

## Dependencies

- WordPress 5.0+
- ACF Pro (for Repeater field type)
- Custom post types: ct-artist, ct-production (must exist before plugin activation)

## Notes

- All PHP functions use global scope (no namespaces)
- Blocks are server-side rendered (dynamic blocks)
- Auto-sync prevents orphaned credits when repeater rows are deleted
- Credits are deleted permanently if removed from repeater

