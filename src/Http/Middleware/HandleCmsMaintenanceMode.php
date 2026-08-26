<?php

namespace Pcteckserv\CmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Pcteckserv\CmsCore\Contracts\CmsAccessUser;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceModeManager;

class HandleCmsMaintenanceMode
{
    public function __construct(private readonly MaintenanceModeManager $maintenance) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! $this->maintenance->isActive() || $this->isExcluded($request)) {
            return $next($request);
        }

        if ($this->hasAdministrativeBypass($request) || $this->maintenance->hasTemporaryAccess($request) || $this->ipIsAllowed($request)) {
            $response = $next($request);

            return $this->withAdminBannerFlag($request, $response);
        }

        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            $request->session()->put(MaintenanceModeManager::INTENDED_URL_KEY, $request->getRequestUri());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'O site encontra-se temporariamente em manutenção.'], 503);
        }

        $settings = $this->maintenance->settings();
        $response = response()->view($settings['template_view'], [
            'maintenance' => $settings,
            'templatePreview' => false,
        ], 503);

        if ($settings['end_at']) {
            $response->header('Retry-After', $settings['end_at']->toRfc7231String());
        }

        return $response->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function isExcluded(Request $request): bool
    {
        $excluded = [
            'admin',
            'admin/*',
            'login',
            'logout',
            'maintenance/access',
            'build/*',
            'storage/*',
            'vendor/*',
            'favicon.ico',
            'robots.txt',
            '_debugbar/*',
        ];

        foreach (array_merge($excluded, $this->maintenance->settings()['allowed_paths']) as $pattern) {
            $pattern = trim($pattern, " \t\n\r\0\x0B/");

            if ($pattern !== '' && $request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function hasAdministrativeBypass(Request $request): bool
    {
        $user = $request->user();
        $settings = $this->maintenance->settings();

        return $settings['admin_bypass']
            && $user instanceof CmsAccessUser
            && $user->can('maintenance.preview');
    }

    private function ipIsAllowed(Request $request): bool
    {
        $ip = $request->ip();

        if (! is_string($ip)) {
            return false;
        }

        return in_array($ip, $this->maintenance->settings()['allowed_ips'], true);
    }

    private function withAdminBannerFlag(Request $request, SymfonyResponse $response): SymfonyResponse
    {
        if (! $request->user() instanceof CmsAccessUser || ! $response instanceof Response) {
            return $response;
        }

        $response->headers->set('X-CMS-Maintenance-Bypass', 'admin');

        if (str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $content = (string) $response->getContent();
            $bar = $this->adminBanner();

            if (str_contains($content, '<body')) {
                $content = preg_replace('/<body([^>]*)>/i', '<body$1>'.$bar, $content, 1) ?? $content;
            } else {
                $content = $bar.$content;
            }

            $response->setContent($content);
        }

        return $response;
    }

    private function adminBanner(): string
    {
        $settingsUrl = route('admin.maintenance.edit');
        $disableUrl = route('admin.maintenance.disable');
        $disableButton = Gate::allows('maintenance.toggle')
            ? '<form method="POST" action="'.$disableUrl.'" style="margin:0"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" style="border:1px solid #111827;background:#111827;color:#fff;border-radius:4px;padding:6px 10px">Desativar manutenção</button></form>'
            : '';

        return '<div style="position:sticky;top:0;z-index:2147483647;display:flex;gap:12px;align-items:center;justify-content:center;padding:10px 14px;background:#f59f00;color:#111827;font:14px system-ui,-apple-system,BlinkMacSystemFont,&quot;Segoe UI&quot;,sans-serif"><strong>Modo de Manutenção ativo</strong><span>Está a visualizar o site porque possui acesso administrativo.</span><a href="'.$settingsUrl.'" style="color:#111827;text-decoration:underline">Ir para configuração</a>'.$disableButton.'</div>';
    }
}
