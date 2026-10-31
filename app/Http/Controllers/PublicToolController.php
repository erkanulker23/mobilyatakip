<?php

namespace App\Http\Controllers;

use App\Models\Company;
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
                'lead' => 'Tek dolap gövdesi, kapak, çekmece ve DXF. Meemare dolap stüdyosundan ayrıdır.',
                'src' => 'https://petervanderwalt.github.io/cabinet-studio/',
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
