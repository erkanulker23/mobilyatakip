<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PublicToolController extends Controller
{
    public function plan(): View
    {
        return $this->embed('plan');
    }

    public function cabinet(): View
    {
        return $this->embed('cabinet');
    }

    public function cabinetApp(string $path): Response
    {
        $allowed = ['index.html', 'kitchen-layout.html'];
        if (! in_array($path, $allowed, true)) {
            abort(404);
        }

        $origin = 'https://petervanderwalt.github.io/cabinet-studio/';
        $html = Cache::remember('cabinet-studio-html-v1:'.$path, 3600, function () use ($origin, $path) {
            $response = Http::timeout(20)->get($origin.$path);
            abort_unless($response->successful(), 502);

            return $response->body();
        });

        $html = preg_replace_callback(
            '/\b(href|src)="(?!https?:|#|data:|mailto:)([^"]+)"/',
            function (array $match) use ($origin) {
                $url = $match[2];
                $file = strtok($url, '?');
                if ($file === 'index.html' || $file === 'kitchen-layout.html') {
                    $query = str_contains($url, '?') ? '?'.substr($url, strpos($url, '?') + 1) : '';

                    return $match[1].'="'.e(route('tools.cabinet.app', ['path' => $file]).$query).'"';
                }

                return $match[1].'="'.e($origin.$url).'"';
            },
            $html
        );

        $script = '<script src="'.e(asset('js/cabinet-studio-tr.js').'?v=3').'"></script>';
        $html = preg_replace('/<html lang="en">/', '<html lang="tr">', $html, 1);
        $html = preg_replace('/<head>/', '<head>'.$script, $html, 1);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function embed(string $key): View
    {
        $tools = [
            'plan' => [
                'title' => 'Oda planı',
                'lead' => 'Duvar, kapı ve odayı 2D/3D çizin. Dolap stüdyosundan ayrıdır.',
                'src' => 'https://app.openplan3d.com/',
                'source' => 'https://github.com/laanlabs/openPlan3D',
                'credit' => 'OpenPlan3D · MIT · laanlabs',
                'portal' => 'plan',
            ],
            'cabinet' => [
                'title' => 'Üretim dolabı',
                'lead' => 'Tek dolap gövdesi, kapak, çekmece ve DXF. Ekran Türkçe.',
                'src' => route('tools.cabinet.app', ['path' => 'index.html']),
                'source' => 'https://github.com/petervanderwalt/cabinet-studio',
                'credit' => 'Cabinet Studio · petervanderwalt',
                'portal' => 'uretim',
            ],
        ];

        return view('tools.embed', [
            'company' => Company::first(),
            'tool' => $tools[$key],
        ]);
    }
}
