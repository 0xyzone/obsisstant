<?php

namespace App\Services\Obs;

class ObsScreenPresetService
{
    /**
     * @return array<string, string>
     */
    public static function templateOptions(): array
    {
        return [
            'starting_soon' => 'Starting Soon',
            'main_cam' => 'Main Cam Frame',
            'pip' => 'Picture-in-Picture (PiP)',
            'brb' => 'Be Right Back (BRB)',
            'lower_thirds' => 'Lower Thirds & Ticker',
            'ending' => 'Stream Ending / Outro',
            'custom' => 'Custom Overlay Canvas',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultTheme(string $templateType): array
    {
        $base = [
            'accent_color' => '#E5B94C',
            'surface_color' => '#0B0A08',
            'text_color' => '#F7F3E9',
            'glass_opacity' => 0.78,
            'backdrop_blur' => 18,
            'border_radius' => 24,
            'transparent_background' => in_array($templateType, ['main_cam', 'pip', 'lower_thirds', 'custom'], true),
        ];

        return match ($templateType) {
            'starting_soon' => [
                ...$base,
                'transparent_background' => false,
                'accent_color' => '#F3D37A',
            ],
            'brb' => [
                ...$base,
                'transparent_background' => false,
                'accent_color' => '#D4AF37',
            ],
            'ending' => [
                ...$base,
                'transparent_background' => false,
                'accent_color' => '#E5B94C',
            ],
            default => $base,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultState(string $templateType): array
    {
        return match ($templateType) {
            'starting_soon' => [
                'badge_text' => 'UP NEXT',
                'headline' => 'Stream Starting Soon',
                'subheadline' => 'Audio checks complete. Broadcast begins shortly.',
                'guest_name' => 'Obsisstant Live',
                'guest_role' => 'Studio Production',
                'show_lower_third' => true,
                'show_countdown' => true,
                'countdown_seconds' => 300,
                'countdown_target_at' => null,
                'show_ticker' => true,
                'ticker_items' => [
                    'Welcome to the broadcast',
                    'Powered by Obsisstant & Laravel Reverb',
                    'Live interactive overlays enabled',
                ],
                'show_webcam_frame' => false,
                'alert_visible' => false,
                'alert_type' => 'info',
                'alert_title' => 'Live Notice',
                'alert_message' => '',
                'custom_css' => '',
            ],
            'brb' => [
                'badge_text' => 'INTERMISSION',
                'headline' => 'Be Right Back',
                'subheadline' => 'Taking a short break — stay tuned.',
                'guest_name' => 'Back Shortly',
                'guest_role' => 'Stand By',
                'show_lower_third' => true,
                'show_countdown' => true,
                'countdown_seconds' => 180,
                'countdown_target_at' => null,
                'show_ticker' => true,
                'ticker_items' => [
                    'Short intermission — stream resumes soon',
                    'Drop your questions in chat',
                ],
                'show_webcam_frame' => false,
                'alert_visible' => false,
                'alert_type' => 'info',
                'alert_title' => 'Stand By',
                'alert_message' => '',
                'custom_css' => '',
            ],
            'main_cam' => [
                'badge_text' => 'LIVE',
                'headline' => 'Main Camera Feed',
                'subheadline' => 'In the studio',
                'guest_name' => 'Super Admin',
                'guest_role' => 'Host · Obsisstant',
                'show_lower_third' => true,
                'show_countdown' => false,
                'countdown_seconds' => 0,
                'countdown_target_at' => null,
                'show_ticker' => true,
                'ticker_items' => [
                    'Live Session in Progress',
                    'Controlled remotely via Obsisstant',
                ],
                'show_webcam_frame' => true,
                'alert_visible' => false,
                'alert_type' => 'highlight',
                'alert_title' => 'On Air Alert',
                'alert_message' => '',
                'custom_css' => '',
            ],
            'pip' => [
                'badge_text' => 'DUAL CAM',
                'headline' => 'Picture-in-Picture',
                'subheadline' => 'Screen & Presenter View',
                'guest_name' => 'Presenter',
                'guest_role' => 'Live Demo',
                'show_lower_third' => true,
                'show_countdown' => false,
                'countdown_seconds' => 0,
                'countdown_target_at' => null,
                'show_ticker' => false,
                'ticker_items' => [],
                'show_webcam_frame' => true,
                'alert_visible' => false,
                'alert_type' => 'info',
                'alert_title' => 'Update',
                'alert_message' => '',
                'custom_css' => '',
            ],
            'ending' => [
                'badge_text' => 'OFF AIR',
                'headline' => 'Thanks For Watching',
                'subheadline' => 'See you in the next session.',
                'guest_name' => 'Obsisstant Studio',
                'guest_role' => 'Broadcast Complete',
                'show_lower_third' => false,
                'show_countdown' => false,
                'countdown_seconds' => 0,
                'countdown_target_at' => null,
                'show_ticker' => true,
                'ticker_items' => [
                    'Thank you for tuning in!',
                    'Replay and highlights available soon',
                ],
                'show_webcam_frame' => false,
                'alert_visible' => false,
                'alert_type' => 'info',
                'alert_title' => 'Goodbye',
                'alert_message' => '',
                'custom_css' => '',
            ],
            default => [
                'badge_text' => 'LIVE TOPIC',
                'headline' => 'Obsisstant Studio',
                'subheadline' => 'Real-time broadcast overlay',
                'guest_name' => 'Guest Speaker',
                'guest_role' => 'Featured Guest',
                'show_lower_third' => true,
                'show_countdown' => false,
                'countdown_seconds' => 0,
                'countdown_target_at' => null,
                'show_ticker' => true,
                'ticker_items' => [
                    'Breaking update from Obsisstant Control Deck',
                    'Dynamic overlays synced in real time via Laravel Reverb',
                ],
                'show_webcam_frame' => false,
                'alert_visible' => false,
                'alert_type' => 'highlight',
                'alert_title' => 'Live Alert',
                'alert_message' => '',
                'custom_css' => '',
            ],
        };
    }
}
