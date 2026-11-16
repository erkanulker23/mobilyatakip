<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WardrobeDesign
{
    /** Stüdyodaki hazır ürün kimlikleri. custom ve glb/cad/dxf ayrıca geçerlidir. */
    public const PRODUCTS = [
        'tek-raf',
        'cift-raf-pantolon',
        'cift-cekmece',
        'cift-pantolon',
        'cift-raf',
        'tek-boy',
        'cift-aski',
        'kose-raf',
        'cift-ayakkabi',
        'cift-panjur',
        'tek-panjur',
        'end',
        'custom',
    ];

    /**
     * @param  array<string, mixed>  $design
     * @return array<string, mixed>
     */
    public static function validated(array $design): array
    {
        $validator = Validator::make($design, [
            'modules' => 'present|array|max:80',
            'modules.*.id' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'modules.*.width' => 'required|numeric|min:2|max:200',
            'modules.*.kind' => 'nullable|in:wardrobe,corner,end',
            'modules.*.wall' => 'nullable|in:front,left,right',
            'modules.*.offset' => 'nullable|numeric|min:-1|max:10000',
            'modules.*.height' => 'nullable|numeric|min:40|max:320',
            'modules.*.depthOffset' => 'nullable|numeric|min:0|max:120',
            'modules.*.door' => 'nullable|in:none,solid,glass,mixed,panjur,accordion',
            'modules.*.shelves' => 'nullable|integer|min:0|max:12',
            'modules.*.drawers' => 'nullable|integer|min:0|max:8',
            'modules.*.product' => 'nullable|string|max:40',
            'modules.*.glb' => ['nullable', 'string', 'max:48', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'modules.*.cad' => ['nullable', 'string', 'max:48', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'modules.*.dxf' => ['nullable', 'string', 'max:48', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'modules.*.cornerSide' => 'nullable|in:left,right',
            'modules.*.planX' => 'nullable|numeric|min:-2000|max:8000',
            'modules.*.planY' => 'nullable|numeric|min:-2000|max:8000',
            'modules.*.planRotation' => 'nullable|numeric|min:-1080|max:1080',
            'modules.*.name' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'wallWidth' => 'nullable|numeric|min:80|max:800',
            'leftWall' => 'nullable|numeric|min:80|max:800',
            'rightWall' => 'nullable|numeric|min:80|max:800',
            'roomHeight' => 'nullable|numeric|min:160|max:320',
            'height' => 'nullable|numeric|min:40|max:270',
            'depth' => 'nullable|numeric|min:30|max:80',
            'name' => ['nullable', 'string', 'max:80', 'not_regex:/[<>]/'],
            'customerName' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'phone' => 'nullable|string|max:40',
            'note' => 'nullable|string|max:2000',
            'series' => 'nullable|in:duz,cam,karisik,akordeon',
            'roomSketch' => 'nullable|array',
            'roomSketch.walls' => 'nullable|array|max:40',
            'roomSketch.walls.*.x1' => 'required_with:roomSketch.walls|numeric|min:-100|max:2000',
            'roomSketch.walls.*.y1' => 'required_with:roomSketch.walls|numeric|min:-100|max:2000',
            'roomSketch.walls.*.x2' => 'required_with:roomSketch.walls|numeric|min:-100|max:2000',
            'roomSketch.walls.*.y2' => 'required_with:roomSketch.walls|numeric|min:-100|max:2000',
            'roomSketch.openings' => 'nullable|array|max:40',
            'roomSketch.openings.*.wallId' => 'nullable|string|max:40',
            'roomSketch.openings.*.offset' => 'nullable|numeric|min:-1|max:900',
            'roomSketch.openings.*.width' => 'nullable|numeric|min:20|max:400',
        ], [
            'modules.required' => 'Tasarımda modül listesi yok.',
            'modules.*.width.min' => 'Modül genişliği 2–200 cm olmalı.',
            'modules.*.width.max' => 'Modül genişliği 2–200 cm olmalı.',
            'modules.*.product.max' => 'Bilinmeyen modül kimliği.',
        ]);

        $validator->after(function ($validator) use ($design) {
            $modules = $design['modules'] ?? [];
            if (! is_array($modules)) {
                return;
            }
            $ids = [];
            $leftCorners = 0;
            $rightCorners = 0;
            $front = self::limit($design['wallWidth'] ?? null, 800);
            $left = self::limit($design['leftWall'] ?? null, 800);
            $right = self::limit($design['rightWall'] ?? null, 800);
            foreach ($modules as $index => $mod) {
                if (! is_array($mod)) {
                    $validator->errors()->add("modules.$index", 'Modül kaydı bozuk.');
                    continue;
                }
                $id = (string) ($mod['id'] ?? '');
                if ($id !== '' && isset($ids[$id])) {
                    $validator->errors()->add("modules.$index.id", 'Aynı modül kimliği iki kez var.');
                }
                $ids[$id] = true;
                $kind = $mod['kind'] ?? 'wardrobe';
                if ($kind === 'corner') {
                    if (($mod['cornerSide'] ?? 'right') === 'left') {
                        $leftCorners++;
                    } else {
                        $rightCorners++;
                    }
                }
                $product = isset($mod['product']) ? (string) $mod['product'] : '';
                $hasModel = ! empty($mod['glb']) || ! empty($mod['cad']) || ! empty($mod['dxf']);
                if ($product !== '' && ! in_array($product, self::PRODUCTS, true) && ! $hasModel) {
                    $validator->errors()->add("modules.$index.product", 'Bilinmeyen modül kimliği.');
                }
                $width = (float) ($mod['width'] ?? 0);
                $along = $kind === 'end' ? 2 : max(30, $width);
                $offset = (float) ($mod['offset'] ?? 0);
                if ($kind === 'corner') {
                    if ($along > $front + 1) {
                        $validator->errors()->add("modules.$index.width", 'Köşe dolabı ön duvara sığmıyor.');
                    }
                    continue;
                }
                if ($offset > 900) {
                    $validator->errors()->add("modules.$index.offset", 'Bozuk konum verisi.');
                    continue;
                }
                $wall = $mod['wall'] ?? 'front';
                $span = $wall === 'left' ? $left : ($wall === 'right' ? $right : $front);
                if ($offset + $along > $span + 1) {
                    $validator->errors()->add("modules.$index.offset", 'Modül duvarın dışına taşıyor.');
                }
            }
            if ($leftCorners > 1 || $rightCorners > 1) {
                $validator->errors()->add('modules', 'Bir köşeye birden fazla köşe dolabı konamaz.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $design;
    }

    private static function limit(mixed $value, float $fallback): float
    {
        $n = is_numeric($value) ? (float) $value : $fallback;

        return max(80, min(800, $n));
    }
}
