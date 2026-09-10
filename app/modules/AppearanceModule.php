<?php

/**
 * Class AppearanceModule
 *
 * Manages the selectable background/text color themes ("appearance").
 *
 * This is deliberately separate from the pre-existing "Themes" admin tab
 * (ThemeModule), which lets admins upload decorative banner/header image
 * packages - a completely different feature that happens to share the
 * word "theme". To avoid confusion with that feature, everything here is
 * called a "color scheme" / "appearance" in code, routes and settings
 * keys, even though the admin/profile UI labels it "Appearance" for the
 * user.
 *
 * How it works: three CSS custom properties (--hf-bg-page, --hf-bg-surface,
 * --hf-text-primary) are defined per color scheme as [data-theme="..."]
 * blocks in app.scss. The active scheme's identifier is written to the
 * <html> tag's data-theme attribute in layout.php, resolved by
 * AppearanceModule::resolve() - a user's own choice (profile preferences)
 * if they made one, otherwise the workspace-wide default (Admin ->
 * Appearance), otherwise "midnight" (this app's original look).
 *
 * To add a new color scheme:
 *   1. Pick a short lowercase identifier, e.g. "sunset".
 *   2. Add it to self::$available_themes below with a human-readable label.
 *   3. Add a matching [data-theme="sunset"] { ... } block in app.scss,
 *      right after the other color scheme blocks near the top of the
 *      file, setting all three custom properties listed above.
 *   4. Run `npm run build` so the new CSS is bundled into public/js/app.js.
 */
class AppearanceModule {
    /**
     * @var array Maps a color scheme identifier to its human-readable label.
     */
    public static $available_themes = [
        'midnight' => 'Midnight',
        'forest' => 'Forest',
        'sandstone' => 'Sandstone',
        'ember' => 'Ember',
        'abyss' => 'Abyss',
        'amber' => 'Amber'
    ];

    /**
     * @var string The color scheme used when nothing else applies.
     */
    const DEFAULT_THEME = 'midnight';

    /**
     * Resolves which color scheme should be active for the current
     * request: the given user's own choice if they made one and it's
     * still a valid/known scheme, otherwise the workspace-wide default,
     * otherwise the built-in fallback.
     *
     * @param $user
     * @return string
     */
    public static function resolve($user = null)
    {
        if ($user) {
            $user_choice = $user->get('color_scheme');

            if (($user_choice) && (array_key_exists($user_choice, static::$available_themes))) {
                return $user_choice;
            }
        }

        $workspace_default = app('color_scheme');

        if (($workspace_default) && (array_key_exists($workspace_default, static::$available_themes))) {
            return $workspace_default;
        }

        return self::DEFAULT_THEME;
    }
}
