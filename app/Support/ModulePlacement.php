<?php

namespace App\Support;

/** TypeScript modulePlacement.ts ile aynı yerleşim kuralları. Sürüm tests/Fixtures/placement-rules-v1.json içindedir. */
class ModulePlacement
{
    /** @return array<string, int> */
    public static function rules(): array
    {
        $path = base_path('tests/Fixtures/placement-rules-v1.json');
        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function cmToMm(float $cm): int
    {
        return (int) round($cm * (int) self::rules()['cmToMm']);
    }

    /**
     * @param  list<int>  $widthsMm
     * @return array{fits: bool, leftoverMm: int, offsetsMm: list<int>, usedMm: int}
     */
    public static function packRun(int $wallMm, array $widthsMm, int $gapMm = 0): array
    {
        $used = array_sum($widthsMm) + max(0, count($widthsMm) - 1) * $gapMm;
        $offsets = [];
        $cursor = 0;
        foreach ($widthsMm as $width) {
            $offsets[] = $cursor;
            $cursor += $width + $gapMm;
        }

        return [
            'fits' => $used <= $wallMm,
            'leftoverMm' => $wallMm - $used,
            'offsetsMm' => $offsets,
            'usedMm' => $used,
        ];
    }

    /**
     * @param  list<int>  $tilesMm
     * @return array{widthsMm: list<int>, exact: bool, leftoverMm: int}|null
     */
    public static function suggestRun(int $lengthMm, array $tilesMm): ?array
    {
        $tiles = array_values(array_unique(array_filter($tilesMm, fn ($value) => $value > 0)));
        rsort($tiles);
        if ($tiles === [] || $lengthMm < $tiles[count($tiles) - 1]) {
            return null;
        }
        $reached = array_fill(0, $lengthMm + 1, null);
        $reached[0] = [];
        for ($used = 0; $used <= $lengthMm; $used++) {
            if (! is_array($reached[$used])) {
                continue;
            }
            $last = $reached[$used] === [] ? PHP_INT_MAX : $reached[$used][count($reached[$used]) - 1];
            foreach ($tiles as $tile) {
                if ($tile > $last) {
                    continue;
                }
                $next = $used + $tile;
                if ($next <= $lengthMm && $reached[$next] === null) {
                    $reached[$next] = array_merge($reached[$used], [$tile]);
                }
            }
        }
        for ($used = $lengthMm; $used > 0; $used--) {
            if (is_array($reached[$used])) {
                $widths = $reached[$used];
                $total = array_sum($widths);

                return [
                    'widthsMm' => $widths,
                    'exact' => $total === $lengthMm,
                    'leftoverMm' => $lengthMm - $total,
                ];
            }
        }

        return null;
    }

    /**
     * @param  list<array{0: int, 1: int}>  $occupied
     * @return list<array{startMm: int, endMm: int, lengthMm: int}>
     */
    public static function freeIntervals(int $wallMm, array $occupied): array
    {
        $spans = $occupied;
        usort($spans, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($spans as [$start, $end]) {
            $span = [min($start, $end), max($start, $end)];
            if ($span[1] <= $span[0]) {
                continue;
            }
            if ($merged === [] || $span[0] > $merged[count($merged) - 1][1]) {
                $merged[] = $span;
            } else {
                $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $span[1]);
            }
        }
        $free = [];
        $cursor = 0;
        foreach ($merged as [$start, $end]) {
            $from = max(0, $start);
            $to = min($wallMm, $end);
            if ($from > $cursor) {
                $free[] = ['startMm' => $cursor, 'endMm' => $from, 'lengthMm' => $from - $cursor];
            }
            $cursor = max($cursor, $to);
        }
        if ($cursor < $wallMm) {
            $free[] = ['startMm' => $cursor, 'endMm' => $wallMm, 'lengthMm' => $wallMm - $cursor];
        }

        return $free;
    }

    /**
     * @param  array<string, mixed>  $module
     * @return list<array{cx: float, cy: float, hx: float, hy: float, cos: float, sin: float}>
     */
    public static function footprint(array $module): array
    {
        $width = self::cmToMm((float) $module['widthCm']);
        $depth = self::cmToMm((float) $module['depthCm']);
        $cx = self::cmToMm((float) $module['xCm']);
        $cy = self::cmToMm((float) $module['yCm']);
        $deg = (float) ($module['rotation'] ?? 0);
        $corner = ! empty($module['corner']);
        $mirror = $corner && (($module['cornerSide'] ?? 'left') === 'right') ? -1 : 1;
        if (! $corner) {
            return [self::rect($cx, $cy, $width / 2, $depth / 2, $deg)];
        }
        $legs = [
            ['lx' => $mirror * -$width / 4, 'ly' => 0, 'hx' => $width / 4, 'hy' => $depth / 2],
            ['lx' => 0, 'ly' => $depth / 4, 'hx' => $width / 2, 'hy' => $depth / 4],
        ];
        $rects = [];
        foreach ($legs as $leg) {
            $center = self::turn($cx, $cy, $leg['lx'], $leg['ly'], $deg);
            $rects[] = self::rect($center['x'], $center['y'], $leg['hx'], $leg['hy'], $deg);
        }

        return $rects;
    }

    /** @param array{cx: float, cy: float, hx: float, hy: float, cos: float, sin: float} $a
     * @param array{cx: float, cy: float, hx: float, hy: float, cos: float, sin: float} $b */
    public static function rectsOverlap(array $a, array $b): bool
    {
        $allow = (float) self::rules()['overlapMm'];
        $axes = [
            [$a['cos'], $a['sin']],
            [-$a['sin'], $a['cos']],
            [$b['cos'], $b['sin']],
            [-$b['sin'], $b['cos']],
        ];
        $dx = $b['cx'] - $a['cx'];
        $dy = $b['cy'] - $a['cy'];
        $penetration = INF;
        foreach ($axes as [$ax, $ay]) {
            $dist = abs($dx * $ax + $dy * $ay);
            $ra = $a['hx'] * abs($a['cos'] * $ax + $a['sin'] * $ay) + $a['hy'] * abs(-$a['sin'] * $ax + $a['cos'] * $ay);
            $rb = $b['hx'] * abs($b['cos'] * $ax + $b['sin'] * $ay) + $b['hy'] * abs(-$b['sin'] * $ax + $b['cos'] * $ay);
            $overlap = $ra + $rb - $dist;
            if ($overlap <= $allow) {
                return false;
            }
            $penetration = min($penetration, $overlap);
        }

        return $penetration > $allow;
    }

    /**
     * @param  array<string, mixed>  $module
     * @param  list<array<string, mixed>>  $others
     * @param  list<array<string, mixed>>  $walls
     */
    public static function issue(array $module, array $others, array $walls, array $openings = []): string
    {
        $rects = self::footprint($module);
        $room = self::room($walls);
        if ($room && self::outside($rects, $room)) {
            return 'outside';
        }
        if (self::blocked($module, $walls, $openings)) {
            return 'opening';
        }
        foreach ($others as $other) {
            if (($other['id'] ?? '') === ($module['id'] ?? '')) {
                continue;
            }
            $hit = false;
            foreach (self::footprint($module) as $left) {
                foreach (self::footprint($other) as $right) {
                    if (self::rectsOverlap($left, $right)) {
                        $hit = true;
                    }
                }
            }
            if (! $hit) {
                continue;
            }

            return ! empty($module['corner']) || ! empty($other['corner']) ? 'corner' : 'overlap';
        }

        return 'ok';
    }

    /**
     * @param  array<string, mixed>  $design
     * @return list<array{0: string, 1: string}>
     */
    public static function problems(array $design): array
    {
        $modules = $design['modules'] ?? [];
        if (! is_array($modules)) {
            return [];
        }
        $walls = [];
        foreach ($design['roomSketch']['walls'] ?? [] as $index => $wall) {
            if (! is_array($wall)) {
                continue;
            }
            $walls[] = [
                'id' => (string) ($wall['id'] ?? $index),
                'start' => ['x' => (float) ($wall['x1'] ?? 0), 'y' => (float) ($wall['y1'] ?? 0)],
                'end' => ['x' => (float) ($wall['x2'] ?? 0), 'y' => (float) ($wall['y2'] ?? 0)],
                'thickness' => (float) ($wall['thickness'] ?? 12),
            ];
        }
        $openings = [];
        foreach ($design['roomSketch']['openings'] ?? [] as $opening) {
            if (! is_array($opening)) {
                continue;
            }
            $start = (float) ($opening['offset'] ?? 0);
            $width = (float) ($opening['width'] ?? 0);
            $openings[] = [
                'wallId' => (string) ($opening['wallId'] ?? ''),
                'startCm' => $start,
                'endCm' => $start + $width,
            ];
        }
        $placed = [];
        foreach ($modules as $module) {
            if (! is_array($module) || ! isset($module['planX'], $module['planY'])) {
                continue;
            }
            $placed[] = [
                'id' => (string) ($module['id'] ?? ''),
                'xCm' => (float) $module['planX'],
                'yCm' => (float) $module['planY'],
                'rotation' => (float) ($module['planRotation'] ?? 0),
                'widthCm' => (float) ($module['width'] ?? 0),
                'depthCm' => (float) ($module['depth'] ?? $design['depth'] ?? 60),
                'corner' => ($module['kind'] ?? '') === 'corner' || str_starts_with((string) ($module['glb'] ?? ''), 'kose'),
                'cornerSide' => $module['planCornerSide'] ?? $module['cornerSide'] ?? 'left',
            ];
        }
        $problems = [];
        foreach ($placed as $index => $module) {
            $issue = self::issue($module, $placed, $walls, $openings);
            if ($issue === 'ok') {
                continue;
            }
            $problems[] = ["modules.$index.planX", self::message($issue)];
        }

        return $problems;
    }

    /**
     * @param  list<array{cx: float, cy: float, hx: float, hy: float, cos: float, sin: float}>  $rects
     * @param  array{minX: float, maxX: float, minY: float, maxY: float}  $room
     */
    private static function outside(array $rects, array $room): bool
    {
        $slack = (float) self::rules()['roomSlackMm'];
        foreach ($rects as $rect) {
            foreach ([[-1, -1], [1, -1], [1, 1], [-1, 1]] as [$sx, $sy]) {
                $x = $rect['cx'] + $rect['cos'] * $rect['hx'] * $sx - $rect['sin'] * $rect['hy'] * $sy;
                $y = $rect['cy'] + $rect['sin'] * $rect['hx'] * $sx + $rect['cos'] * $rect['hy'] * $sy;
                if ($x < $room['minX'] - $slack || $x > $room['maxX'] + $slack || $y < $room['minY'] - $slack || $y > $room['maxY'] + $slack) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param list<array<string, mixed>> $walls
     * @return array{minX: float, maxX: float, minY: float, maxY: float}|null */
    private static function room(array $walls): ?array
    {
        if ($walls === []) {
            return null;
        }
        $minX = INF;
        $maxX = -INF;
        $minY = INF;
        $maxY = -INF;
        $thickness = 120.0;
        foreach ($walls as $wall) {
            $minX = min($minX, self::cmToMm($wall['start']['x']), self::cmToMm($wall['end']['x']));
            $maxX = max($maxX, self::cmToMm($wall['start']['x']), self::cmToMm($wall['end']['x']));
            $minY = min($minY, self::cmToMm($wall['start']['y']), self::cmToMm($wall['end']['y']));
            $maxY = max($maxY, self::cmToMm($wall['start']['y']), self::cmToMm($wall['end']['y']));
            if (($wall['thickness'] ?? 0) > 0) {
                $thickness = self::cmToMm((float) $wall['thickness']);
            }
        }
        $inset = $thickness / 2;
        if ($maxX - $minX <= $inset * 2 || $maxY - $minY <= $inset * 2) {
            return null;
        }

        // The back may sit in the wall thickness. Past the centerline is still outside.
        return ['minX' => $minX, 'maxX' => $maxX, 'minY' => $minY, 'maxY' => $maxY];
    }

    /** @param list<array<string, mixed>> $walls
     * @param list<array{wallId?: string, startCm: float, endCm: float}> $openings */
    private static function blocked(array $module, array $walls, array $openings): bool
    {
        foreach ($openings as $opening) {
            $wall = null;
            foreach ($walls as $candidate) {
                if (($opening['wallId'] ?? '') === '' || ($candidate['id'] ?? '') === $opening['wallId']) {
                    $wall = $candidate;
                    break;
                }
            }
            if (! $wall || ! self::near($module, $wall)) {
                continue;
            }
            $along = self::along($module['xCm'], $module['yCm'], $wall)['along'];
            $start = self::cmToMm($along - $module['widthCm'] / 2);
            $end = self::cmToMm($along + $module['widthCm'] / 2);
            $overlap = min($end, self::cmToMm($opening['endCm'])) - max($start, self::cmToMm($opening['startCm']));
            if ($overlap > (int) self::rules()['overlapMm']) {
                return true;
            }
        }

        return false;
    }

    private static function near(array $module, array $wall): bool
    {
        $perp = self::along($module['xCm'], $module['yCm'], $wall)['perp'];
        $face = (($wall['thickness'] ?? 12) / 2) + ($module['depthCm'] / 2);

        return abs(abs($perp) - $face) <= ((int) self::rules()['snapDistanceMm']) / (int) self::rules()['cmToMm'];
    }

    /** @return array{along: float, perp: float} */
    private static function along(float $x, float $y, array $wall): array
    {
        $dx = $wall['end']['x'] - $wall['start']['x'];
        $dy = $wall['end']['y'] - $wall['start']['y'];
        $length = hypot($dx, $dy) ?: 1;
        $ux = $dx / $length;
        $uy = $dy / $length;
        $nx = -$uy;
        $ny = $ux;
        $px = $x - $wall['start']['x'];
        $py = $y - $wall['start']['y'];

        return ['along' => $px * $ux + $py * $uy, 'perp' => $px * $nx + $py * $ny];
    }

    /** @return array{x: float, y: float} */
    private static function turn(float $cx, float $cy, float $lx, float $ly, float $deg): array
    {
        $rad = deg2rad($deg);

        return [
            'x' => $cx + $lx * cos($rad) - $ly * sin($rad),
            'y' => $cy + $lx * sin($rad) + $ly * cos($rad),
        ];
    }

    /** @return array{cx: float, cy: float, hx: float, hy: float, cos: float, sin: float} */
    private static function rect(float $cx, float $cy, float $hx, float $hy, float $deg): array
    {
        $rad = deg2rad($deg);

        return ['cx' => $cx, 'cy' => $cy, 'hx' => $hx, 'hy' => $hy, 'cos' => cos($rad), 'sin' => sin($rad)];
    }

    private static function message(string $issue): string
    {
        return match ($issue) {
            'outside' => 'Modül oda sınırının dışında.',
            'opening' => 'Modül kapı veya pencere açıklığının önünde.',
            'corner' => 'Köşe modülü komşu gövdeyle çakışıyor.',
            default => 'Modüller birbirinin içine giriyor.',
        };
    }
}
