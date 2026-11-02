<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Actions\Action;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * لوحة Octagon: لوحة اطلاع وإحصائيات للبيانات المؤرشفة (عرض فقط).
 * الدخول: /octagon — مسموح لدور octagon و super_admin.
 * الهوية البصرية مطابقة لهوية "رابط" (KFS Royal: أخضر مصري + ذهبي + عاجي).
 */
class OctagonPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('octagon')
            ->path('octagon')
            ->login()
            ->brandName('رابط — Octagon')
            ->brandLogo(fn () => view('filament.octagon.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('favicon.png'))
            ->darkMode(false)
            ->colors([
                // درجات الأخضر المصري (600/700 = ألوان الموقع الأساسية)
                'primary' => [
                    50 => '#F1F6F4', 100 => '#DCE8E3', 200 => '#B8D0C7', 300 => '#8CB1A4',
                    400 => '#5A8B7C', 500 => '#2F6A5A', 600 => '#185447', 700 => '#123F35',
                    800 => '#0E342C', 900 => '#0A2924', 950 => '#061A17',
                ],
                // الذهبي المصري
                'gold' => [
                    50 => '#FBF8F1', 100 => '#F4ECDA', 200 => '#E9D9B5', 300 => '#DCC48E',
                    400 => '#D1B574', 500 => '#B8954F', 600 => '#9C7C3F', 700 => '#7D6233',
                    800 => '#5F4B28', 900 => '#43351C', 950 => '#2A2112',
                ],
                'success' => Color::hex('#237A52'),
                'warning' => Color::hex('#B27A24'),
                'danger' => Color::hex('#A93232'),
                'info' => Color::hex('#2D6477'),
                'gray' => Color::Stone,
            ])
            ->font('IBM Plex Sans Arabic', provider: GoogleFontProvider::class)
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->userMenuItems([
                Action::make('profile')
                    ->label('الملف الشخصي وكلمة المرور')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn () => route('profile.edit')),
            ])
            ->navigationGroups([
                'البيانات المؤرشفة',
                'النماذج والتقارير',
                'الهيكل والمستخدمين',
                'السجلات',
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => Blade::render('<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="{{ asset(\'assets/css/octagon.css\') }}?v={{ filemtime(public_path(\'assets/css/octagon.css\')) }}">'),
            )
            ->renderHook(PanelsRenderHook::BODY_START, fn () => view('filament.octagon.idbar'))
            ->renderHook(PanelsRenderHook::FOOTER, fn () => view('filament.octagon.footer'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                // لوحة Octagon مستثناة من المصادقة الثنائية (كلمة المرور فقط)
                Authenticate::class,
            ]);
    }
}
